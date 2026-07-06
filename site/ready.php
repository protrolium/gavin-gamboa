<?php namespace ProcessWire;

if(!defined("PROCESSWIRE")) die();

/** @var Wire $wire */
/** @var Page $page */

/**
 * ProcessWire Bootstrap API Ready
 * ===============================
 * This ready.php file is called during ProcessWire bootstrap initialization process.
 * This occurs after the current page has been determined and the API is fully ready
 * to use, but before the current page has started rendering. This file receives a
 * copy of all ProcessWire API variables.
 *
 */

// Standard.site publication verification (/.well-known/site.standard.publication/newsletter)
$standardSitePath = wire('input')->url();
if (preg_match('#\.well-known/site\.standard\.publication/newsletter/?$#', $standardSitePath)) {
	$uri = (string) wire('config')->standardSitePublicationUri;
	if ($uri !== '') {
		header('Content-Type: text/plain; charset=utf-8');
		echo $uri;
		exit;
	}
	throw new Wire404Exception();
}

// RSS template: raw XML only (no _init / _main). Page must use template name "rss".
$p = wire('page');
if ($p && $p->id && $p->template->name === 'rss') {
	wire('config')->appendTemplateFile = '';
	wire('config')->prependTemplateFile = '';
}

// ─── TIDDLYWIKI IMPORT TAB ───────────────────────────────────────────────────
//
// Adds a "TiddlyWiki Import" tab to the promailer-email page editor in admin.
// The tab contains a URL textarea + buttons that POST via AJAX to the same
// admin URL, intercepted by the hook below before ProcessPageEdit::execute().

$wire->addHookAfter('ProcessPageEdit::buildForm', function(HookEvent $event) {
    /** @var Page $page */
    $page = $event->object->getPage();
    if (!$page || $page->template->name !== 'promailer-email') return;

    $form = $event->return;

    /** @var InputfieldWrapper $tab */
    $tab = wire('modules')->get('InputfieldWrapper');
    $tab->attr('id+name', 'tw_import');
    $tab->addClass('WireTab');

    /** @var InputfieldMarkup $field */
    $field = wire('modules')->get('InputfieldMarkup');
    $field->label = '';
    $field->attr('value', nli_adminTabHtml($page->id));
    $field->collapsed = Inputfield::collapsedNo;

    $tab->add($field);
    $form->add($tab);
});

// Inject tw_import into the pre-rendered tab bar before the View entry.
// WireTabs pre-renders #PageEditTabs from getTabs(); tabs not in that list are
// appended last by JS. By adding our entry here, it lands in the right slot.
$wire->addHookAfter('ProcessPageEdit::getTabs', function(HookEvent $event) {
    $page = $event->object->getPage();
    if (!$page || $page->template->name !== 'promailer-email') return;

    $tabs = $event->return;
    if (isset($tabs['tw_import'])) return;

    $ordered = [];
    $inserted = false;
    foreach ($tabs as $id => $label) {
        if ($id === 'ProcessPageEditView') {
            $ordered['tw_import'] = 'TiddlyWiki Import';
            $inserted = true;
        }
        $ordered[$id] = $label;
    }
    if (!$inserted) $ordered['tw_import'] = 'TiddlyWiki Import';

    $event->return = $ordered;
});

// Intercept the AJAX import POST before ProcessPageEdit processes it normally.
$wire->addHookBefore('ProcessPageEdit::execute', function(HookEvent $event) {
    if (wire('input')->post('tw_import_action') !== 'import') return;

    set_time_limit(180);

    $pageId     = (int)  wire('input')->post('tw_page_id');
    $rawUrls    =        wire('input')->post->textarea('tw_tiddler_urls');
    $dryRun     = (bool) wire('input')->post->int('tw_dry_run');
    $noCache    = (bool) wire('input')->post->int('tw_no_cache');
    $noGenerate = (bool) wire('input')->post->int('tw_no_generate');

    $urls = array_filter(array_map('trim', explode("\n", $rawUrls)));

    $page = wire('pages')->get($pageId);
    if (!$page->id || $page->template->name !== 'promailer-email') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid page ID']);
        exit;
    }

    $apiKey = wire('config')->anthropicApiKey
           ?? getenv('ANTHROPIC_API_KEY')
           ?: '';

    require_once wire('config')->paths->templates . 'scripts/newsletter-importer.php';

    ob_start();
    echo "Newsletter: {$page->title} (id={$page->id})\n\n";
    $result = nli_run($page, $urls, $dryRun, 'https://gavart.ist/', $noCache, $noGenerate, $apiKey);
    $output = ob_get_clean();

    header('Content-Type: application/json');
    echo json_encode(['output' => $output] + $result);
    exit;
});

function nli_adminTabHtml(int $pageId): string {
    return <<<HTML
<style>
/* suppress save buttons rendered inside the fields list, keep the header bar */
#tw_import ul.Inputfields .InputfieldSubmit { display:none !important; }
/* hide redundant Publish / Save+Keep Unpublished in form body (header versions stay) */
.Inputfields .InputfieldSubmit:has(#submit_publish), 
.Inputfields .InputfieldSubmit:has(#submit_save),
.Inputfields .InputfieldSubmit:has(#submit_save_unpublished) { display:none !important; }
#tw-import textarea { width:100%; font-family:monospace; font-size:13px; padding:8px; margin:6px 0 12px; box-sizing:border-box; }
#tw-import .tw-row  { display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin:10px 0; }
#tw-import pre      { background:#1a1a1a; color:#e0e0e0; padding:16px; font-size:12px; white-space:pre-wrap; overflow-y:auto; max-height:500px; border-radius:4px; margin-top:16px; display:none; }
</style>

<div id="tw-import">
  <p>Paste one TiddlyWiki tiddler URL per line. Images will be added to this page's gallery and newsletter body HTML will be generated via Claude. To view changes in Content tab, this page must be force refreshed in the browser.</p>

  <label for="tw-urls"><strong>Tiddler URLs</strong></label>
  <textarea id="tw-urls" rows="8" placeholder="https://gavart.ist/#Tiddler%20Title"></textarea>

  <div class="tw-row">
    <button type="button" id="tw-btn-run"    class="ui-button ui-widget ui-corner-all">Import &amp; Generate</button>
    <button type="button" id="tw-btn-dryrun" class="ui-button ui-widget ui-corner-all ui-state-default">Dry Run</button>
    <label><input type="checkbox" id="tw-no-generate"> Images only</label>
    <label><input type="checkbox" id="tw-no-cache"> Force refresh wiki</label>
  </div>

  <pre id="tw-output"></pre>
</div>

<script>
(function () {
  function run(dryRun) {
    var urls = document.getElementById('tw-urls').value.trim();
    if (!urls) { alert('Paste at least one tiddler URL.'); return; }

    var btn = document.getElementById(dryRun ? 'tw-btn-dryrun' : 'tw-btn-run');
    var out = document.getElementById('tw-output');
    btn.disabled = true;
    out.textContent = 'Running…';
    out.style.display = 'block';

    fetch(window.location.href, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        tw_import_action : 'import',
        tw_page_id       : '$pageId',
        tw_tiddler_urls  : urls,
        tw_dry_run       : dryRun ? '1' : '0',
        tw_no_cache      : document.getElementById('tw-no-cache').checked      ? '1' : '0',
        tw_no_generate   : document.getElementById('tw-no-generate').checked   ? '1' : '0'
      }).toString()
    })
    .then(r => r.json())
    .then(data => {
      out.textContent = data.error ? 'Error: ' + data.error : (data.output || JSON.stringify(data, null, 2));
      btn.disabled = false;
    })
    .catch(err => {
      out.textContent = 'Network error: ' + err.message;
      btn.disabled = false;
    });
  }

  document.getElementById('tw-btn-run')   .addEventListener('click', () => run(false));
  document.getElementById('tw-btn-dryrun').addEventListener('click', () => run(true));
}());
</script>
HTML;
}

// ─── STANDARD.SITE AUTO-SYNC ─────────────────────────────────────────────────
//
// When a promailer-email page is published and has no at_uri yet, create a
// site.standard.document record on the AT Protocol PDS and store the URI.
// Requires $config->bskyAppPassword and $config->standardSitePublicationUri
// to be set (in config-local.php locally, or config.php on production).
// Silently skips if either is absent.

$wire->addHookAfter('Pages::saved', function(HookEvent $e) {
	/** @var Page $page */
	$page = $e->arguments(0);

	if (!$page || $page->template->name !== 'promailer-email') return;
	if ($page->isUnpublished()) return;
	if (!$page->hasField('at_uri') || $page->at_uri) return;

	$config   = wire('config');
	$password = (string) ($config->bskyAppPassword ?? '');
	$pubUri   = (string) ($config->standardSitePublicationUri ?? '');
	if ($password === '' || $pubUri === '') return;

	$pds    = 'https://inkcap.us-east.host.bsky.network';
	$handle = 'gav.cloud';

	try {
		require_once wire('config')->paths->templates . 'scripts/standard-site-xrpc.php';
		require_once wire('config')->paths->templates . 'scripts/email-body-inline-styles.php';

		$session = standardSiteCreateSession($pds, $handle, $password);

		$body        = $page->getUnformatted('body');
		$publishedTs = $page->published ?: $page->created;

		$record = [
			'$type'       => 'site.standard.document',
			'site'        => $pubUri,
			'title'       => trim(strip_tags((string) $page->title)),
			'path'        => '/' . $page->name . '/',
			'description' => promailerEmailFirstParagraphPlain($body),
			'publishedAt' => gmdate('Y-m-d\TH:i:s\Z', $publishedTs),
			'textContent' => promailerEmailBodyPlainForRss($body),
			'tags'        => ['newsletter'],
			'createdAt'   => gmdate('Y-m-d\TH:i:s\Z'),
		];

		$uri = standardSiteCreateDocument($pds, $session['did'], $session['token'], $record);

		$page->of(false);
		$page->at_uri = $uri;
		$page->save('at_uri');
		$page->of(true);

		wire('log')->save('standard-site', "Created document record for '{$page->name}': {$uri}");

	} catch (Throwable $ex) {
		wire('log')->save('standard-site', "Failed for '{$page->name}': " . $ex->getMessage());
	}
});

// ─── WEBP IMAGE SUPPORT ──────────────────────────────────────────────────────

//  webp image support
 if($page->template != 'admin') {
    $wire->addHookAfter('Pageimage::url', function($event) {
      static $n = 0;
      if(++$n === 1) $event->return = $event->object->webp()->url();
      $n--;
    });
  }