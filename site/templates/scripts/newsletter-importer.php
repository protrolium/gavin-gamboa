<?php namespace ProcessWire;

/**
 * Newsletter Importer — shared library
 *
 * Contains all logic for importing TiddlyWiki tiddler images into a
 * ProcessWire newsletter page's gallery field and generating newsletter
 * body content via the Anthropic API.
 *
 * Requires ProcessWire to be bootstrapped before inclusion.
 * Used by both the CLI script and the admin tab hook in ready.php.
 */

if (!defined('GALLERY_FIELD'))    define('GALLERY_FIELD',    'gallery');
if (!defined('BODY_FIELD'))       define('BODY_FIELD',       'body');
if (!defined('NEWSLETTERS_ROOT')) define('NEWSLETTERS_ROOT', '/newsletter/');
if (!defined('WIKI_CACHE_TTL'))   define('WIKI_CACHE_TTL',   3600);
if (!defined('ANTHROPIC_API'))    define('ANTHROPIC_API',    'https://api.anthropic.com/v1/messages');
if (!defined('CLAUDE_MODEL'))     define('CLAUDE_MODEL',     'claude-sonnet-4-6');

// TEMP_DIR depends on wire() so it's initialised on first use.
function nli_tempDir(): string {
    static $dir;
    if (!$dir) {
        $dir = wire('config')->paths->cache . 'img_import/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }
    return $dir;
}

function nli_imageFields(): array { return ['album-art', 'poster', 'image']; }


// ─── TIDDLYWIKI STORE ────────────────────────────────────────────────────────

function nli_fetchStore(string $wikiBase, bool $noCache = false): array {
    $cacheFile = nli_tempDir() . 'tw-store-' . md5($wikiBase) . '.json';
    $cacheAge  = file_exists($cacheFile) ? time() - filemtime($cacheFile) : PHP_INT_MAX;

    if (!$noCache && $cacheAge < WIKI_CACHE_TTL) {
        echo "  (tiddler store: cached, " . round($cacheAge / 60) . "m old)\n";
        return json_decode(file_get_contents($cacheFile), true) ?: [];
    }

    echo "  Fetching wiki from {$wikiBase}...\n";
    $ctx  = stream_context_create(['http' => ['timeout' => 60, 'user_agent' => 'ProcessWire Importer/1.0']]);
    $html = @file_get_contents($wikiBase, false, $ctx);
    if (!$html) { echo "ERROR: Could not fetch TiddlyWiki from {$wikiBase}\n"; return []; }

    $open  = strpos($html, '<script class="tiddlywiki-tiddler-store"');
    $start = $open !== false ? strpos($html, '>', $open) + 1 : false;
    $end   = $start !== false ? strpos($html, '</script>', $start) : false;
    if (!$start || !$end) { echo "ERROR: Tiddler store not found in wiki HTML.\n"; return []; }

    $raw = json_decode(substr($html, $start, $end - $start), true);
    if (!$raw) { echo "ERROR: Failed to parse tiddler store JSON.\n"; return []; }

    $store = array_column($raw, null, 'title');
    file_put_contents($cacheFile, json_encode($store));
    echo "  Loaded " . count($store) . " tiddlers.\n";
    return $store;
}


// ─── IMAGE RESOLUTION ────────────────────────────────────────────────────────

function nli_resolveRef(string $ref, array $store, string $wikiBase): ?string {
    if (str_starts_with($ref, 'http://') || str_starts_with($ref, 'https://')) return $ref;
    $t = $store[$ref] ?? null;
    if ($t && !empty($t['_canonical_uri'])) {
        return rtrim($wikiBase, '/') . '/' . ltrim($t['_canonical_uri'], './');
    }
    return null;
}

function nli_extractImages(array $tiddler, array $store, string $wikiBase): array {
    $urls = [];
    foreach (nli_imageFields() as $field) {
        if (!empty($tiddler[$field])) {
            $url = nli_resolveRef($tiddler[$field], $store, $wikiBase);
            if ($url) $urls[] = $url;
        }
    }
    if (!empty($tiddler['text'])) {
        preg_match_all('/\[img[^\[]*\[([^\]]+)\]\]/', $tiddler['text'], $m);
        foreach ($m[1] as $ref) {
            $url = nli_resolveRef($ref, $store, $wikiBase);
            if ($url) $urls[] = $url;
        }
    }
    return array_values(array_unique($urls));
}


// ─── TEXT CLEANING ───────────────────────────────────────────────────────────

function nli_cleanWikitext(string $text): string {
    $text = preg_replace('/<!--.*?-->/s',                '',    $text);
    $text = preg_replace('/\{\{[^}]*\}\}/',              '',    $text);
    $text = preg_replace('/\[img[^\[]*\[[^\]]*\]\]/',    '',    $text);
    $text = preg_replace('/\[\[([^|\]]+)\|[^\]]+\]\]/', '$1',  $text);
    $text = preg_replace('/\[\[([^\]]+)\]\]/',           '$1',  $text);
    $text = preg_replace('/^!!+\s*/m',                   '',    $text);
    $text = preg_replace('/^<<<.*$/m',                   '',    $text);
    $text = preg_replace('/\/\/(.+?)\/\//',              '$1',  $text);
    $text = preg_replace("/''/",                         '',    $text);
    return trim(preg_replace('/\n{3,}/', "\n\n", $text));
}

function nli_cleanField(string $val): string {
    $val = preg_replace('/\[\[([^|\]]+)\|[^\]]+\]\]/', '$1', $val);
    return trim(preg_replace('/\[\[([^\]]+)\]\]/', '$1', $val));
}


// ─── HELPERS ─────────────────────────────────────────────────────────────────

function nli_download(string $url, string $basename): string|false {
    $dest = nli_tempDir() . $basename;
    $ctx  = stream_context_create(['http' => ['timeout' => 30, 'user_agent' => 'ProcessWire Importer/1.0']]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false) { echo "  ⚠  Could not download: $url\n"; return false; }
    file_put_contents($dest, $data);
    echo "  ↓  Downloaded: $basename\n";
    return $dest;
}

function nli_galleryHas(Page $page, string $basename): bool {
    foreach ($page->{GALLERY_FIELD} as $img) {
        if ($img->basename === $basename) return true;
    }
    return false;
}

function nli_pwImageUrl(Page $page, string $basename): string {
    return wire('config')->urls->assets . 'files/' . $page->id . '/' . $basename;
}


// ─── ANTHROPIC API ───────────────────────────────────────────────────────────

function nli_buildPrompt(array $sections): string {
    $body = '';
    foreach ($sections as $s) {
        $body .= "### {$s['title']}\nType: {$s['type']}\n";
        foreach (['date', 'venue', 'program', 'organization', 'platforms'] as $k) {
            if (!empty($s[$k])) $body .= ucfirst($k) . ": {$s[$k]}\n";
        }
        if (!empty($s['imageUrl'])) $body .= "Image URL: {$s['imageUrl']}\n";
        if (!empty($s['text']))     $body .= "Notes:\n{$s['text']}\n";
        $body .= "\n";
    }

    return <<<PROMPT
Write a newsletter update for Gavin Gamboa (pianist, composer) to send to his fans.

Source material:

{$body}
---

Format as HTML:
- Brief opening paragraph (1–2 sentences, warm and personal).
- One section per item:
    <h2>Title</h2>
    <p><img class="hidpi" src="IMAGE_URL" alt="description" width="619"></p>
    <p>2–3 sentences of engaging prose.</p>
- Brief closing line.

Rules:
- Output only inner HTML — no <html>/<head>/<body> tags, no markdown fences.
- Preserve the exact order of items as given in the source material above.
- Do not fabricate details absent from the source material.
- Write in first person. Use specific, concrete details over general impressions.
- Use <sup> for superscripts (m³ → m<sup>3</sup>).
- Omit <img> if no image URL is provided for a section.
- For discography items, include one platform link for bandcamp, and one for subvert.
PROMPT;
}

function nli_callAPI(string $apiKey, array $sections): string {
    $payload = json_encode([
        'model'      => CLAUDE_MODEL,
        'max_tokens' => 4096,
        'system'     => 'You are writing in the first-person voice of Gavin Gamboa, a Los Angeles-based pianist and composer. Write with specificity and a literary sensibility: concrete imagery, surprising details, genuine intellectual curiosity. Be confident and personal without being precious — favor showing the moment over summarizing how it felt, and let specific details carry the emotion rather than stating it.',
        'messages'   => [['role' => 'user', 'content' => nli_buildPrompt($sections)]],
    ]);

    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'ignore_errors' => true,
        'header'        => implode("\r\n", [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ]),
        'content' => $payload,
        'timeout' => 60,
    ]]);

    $response = @file_get_contents(ANTHROPIC_API, false, $ctx);
    if ($response === false) { echo "ERROR: Anthropic API request failed.\n"; return ''; }

    $status = $http_response_header[0] ?? '';
    if (!str_contains($status, '200')) { echo "ERROR: Anthropic API: $status\n$response\n"; return ''; }

    $data = json_decode($response, true);
    $text = $data['content'][0]['text'] ?? null;
    if (!$text) { echo "ERROR: Unexpected API response: " . json_encode($data) . "\n"; return ''; }

    return $text;
}


// ─── MAIN ORCHESTRATOR ───────────────────────────────────────────────────────

/**
 * Import images from TiddlyWiki tiddlers into a newsletter page's gallery
 * and optionally generate body HTML via the Anthropic API.
 *
 * Progress is echoed to stdout — wrap in ob_start()/ob_get_clean() to capture.
 *
 * @return array{added: int, skipped: int, errors: int}
 */
function nli_run(
    Page   $page,
    array  $tiddlerUrls,
    bool   $dryRun     = false,
    string $wikiBase   = 'https://gavart.ist/',
    bool   $noCache    = false,
    bool   $noGenerate = false,
    string $apiKey     = ''
): array {
    $store = nli_fetchStore($wikiBase, $noCache);
    if (empty($store)) return ['added' => 0, 'skipped' => 0, 'errors' => 1];

    echo "\n";

    $added    = 0;
    $skipped  = 0;
    $errors   = 0;
    $tempFiles = [];
    $sections  = [];

    foreach ($tiddlerUrls as $rawUrl) {
        $rawUrl   = trim($rawUrl);
        $fragment = parse_url($rawUrl, PHP_URL_FRAGMENT);
        if (!$fragment) {
            echo "✗  No fragment: $rawUrl\n\n"; $errors++; continue;
        }

        $title   = urldecode($fragment);
        $tiddler = $store[$title] ?? null;
        if (!$tiddler) {
            echo "✗  Tiddler not found: '$title'\n\n"; $errors++; continue;
        }

        echo "◆ $title\n";

        $tags = $tiddler['tags'] ?? '';
        $type = str_contains($tags, 'Live Performance') ? 'Live Performance'
              : (str_contains($tags, 'Discography')     ? 'Discography' : 'Other');

        $section = [
            'title'        => $title,
            'type'         => $type,
            'date'         => $tiddler['performance-date'] ?? $tiddler['release-date'] ?? '',
            'venue'        => nli_cleanField($tiddler['venue']        ?? ''),
            'program'      => nli_cleanField($tiddler['program']      ?? ''),
            'organization' => nli_cleanField($tiddler['organization'] ?? ''),
            'platforms'    => nli_cleanField($tiddler['platforms']    ?? ''),
            'text'         => nli_cleanWikitext($tiddler['text']      ?? ''),
            'imageUrl'     => '',
        ];

        foreach (nli_extractImages($tiddler, $store, $wikiBase) as $imageUrl) {
            $basename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename(parse_url($imageUrl, PHP_URL_PATH)));

            if (nli_galleryHas($page, $basename)) {
                echo "  →  Already in gallery: $basename\n";
                $skipped++;
                if (!$section['imageUrl']) $section['imageUrl'] = nli_pwImageUrl($page, $basename);
                continue;
            }

            if ($dryRun) {
                echo "  [DRY RUN] Would add: $basename\n            from: $imageUrl\n";
                $added++;
                if (!$section['imageUrl']) $section['imageUrl'] = $imageUrl;
                continue;
            }

            $local = nli_download($imageUrl, $basename);
            if (!$local) { $errors++; continue; }
            $tempFiles[] = $local;

            try {
                $page->of(false);
                $page->{GALLERY_FIELD}->add($local);
                $page->save(GALLERY_FIELD);
                echo "  ✓  Added: $basename\n";
                $added++;
                if (!$section['imageUrl']) $section['imageUrl'] = nli_pwImageUrl($page, $basename);
            } catch (\Exception $e) {
                echo "  ✗  " . $e->getMessage() . "\n"; $errors++;
            }
        }

        $sections[] = $section;
        echo "\n";
    }

    if (!$dryRun && $tempFiles) {
        foreach ($tempFiles as $f) { if (file_exists($f)) unlink($f); }
        echo "Cleaned up " . count($tempFiles) . " temp file(s).\n\n";
    }

    echo "Images — added: $added  skipped: $skipped  errors: $errors\n\n";

    if ($noGenerate || empty($sections)) {
        return compact('added', 'skipped', 'errors');
    }

    if ($dryRun) {
        echo "[DRY RUN] Would call " . CLAUDE_MODEL . " with " . count($sections) . " section(s) and save body.\n";
        return compact('added', 'skipped', 'errors');
    }

    if (!$apiKey) {
        echo "Skipping body generation — no API key configured.\n";
        return compact('added', 'skipped', 'errors');
    }

    echo "Calling Anthropic API...\n";
    $html = nli_callAPI($apiKey, $sections);
    if ($html) {
        $page->of(false);
        $page->{BODY_FIELD} = $html;
        $page->save(BODY_FIELD);
        echo "  ✓  Body saved (" . number_format(strlen($html)) . " chars).\n";
    }

    return compact('added', 'skipped', 'errors');
}
