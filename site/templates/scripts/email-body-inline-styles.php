<?php namespace ProcessWire;

/**
 * Newsletter body HTML: apply inline styles at render time (str_replace map),
 * mirroring promailer-email.php <head> rules for typographic elements.
 * Field value in the DB is unchanged.
 *
 * For RSS: use promailerEmailBodyHtmlForRss() (full HTML + images, sanitized) with MarkupRSS
 * itemDescriptionLength=0, or promailerEmailBodyPlainForRss() for a short text summary.
 * Do not use promailerEmailInlineBodyHtml() in RSS (email table wrapper only).
 */

/**
 * First non-empty paragraph from newsletter body HTML (for meta descriptions).
 *
 * @param string|null $html Raw body field HTML
 * @return string Plain text, truncated to ~300 chars when longer
 */
function promailerEmailFirstParagraphPlain($html): string {
	if ($html === null || $html === '') {
		return '';
	}
	$html = trim((string) $html);
	if ($html === '' || !preg_match_all('#<p[^>]*>(.*?)</p>#is', $html, $matches)) {
		return '';
	}
	foreach ($matches[1] as $inner) {
		$plain = strip_tags($inner);
		$plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$plain = preg_replace('/\s+/u', ' ', $plain);
		$plain = trim((string) $plain);
		if ($plain === '') {
			continue;
		}
		if (strlen($plain) > 300) {
			$plain = substr($plain, 0, 297) . '…';
		}
		return $plain;
	}

	return '';
}

/**
 * Plain-text excerpt of newsletter body for RSS (MarkupRSS / validators).
 *
 * @param string|null $html Raw body field HTML
 * @return string Always a string (never null), safe for MarkupRSS strip/truncate
 */
function promailerEmailBodyPlainForRss($html): string {
	if ($html === null || $html === '') {
		return '';
	}
	$html = trim((string) $html);
	if ($html === '') {
		return '';
	}
	$plain = strip_tags($html);
	$plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$plain = preg_replace('/\s+/u', ' ', $plain);
	$plain = trim((string) $plain);
	if (strlen($plain) > 8000) {
		$plain = substr($plain, 0, 8000) . '…';
	}
	return $plain;
}

/**
 * Newsletter body HTML for the RSS item description (MarkupRSS with itemDescriptionLength = 0).
 * Allowlists common content tags (incl. images), strips executable/active content, returns a string
 * safe to place inside CDATA after MarkupRSS::relativeToAbsoluteHtml().
 *
 * @param string|null $html Raw body field HTML
 * @return string Non-null HTML fragment (may be empty)
 */
function promailerEmailBodyHtmlForRss($html): string {
	if ($html === null || $html === '') {
		return '';
	}
	$html = (string) $html;
	// Drop obvious active content
	$html = preg_replace('#<(script|style|iframe|object|embed|link|meta|base|form)[^>]*>.*?</\1>#is', '', $html);
	$html = preg_replace('#<(script|style|iframe|object|embed|link|meta|base|form)\b[^>]*/>#is', '', $html);
	// Inline event handlers
	$html = preg_replace('#\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
	// javascript: / data: URLs on navigation-sensitive attributes
	$html = preg_replace('#\b(href|src)\s*=\s*(["\'])\s*(?:javascript:|data:|vbscript:)#i', '$1=$2#', $html);
	$allowed = '<p><br><a><img><strong><em><b><i><u><h1><h2><h3><h4><h5><h6><blockquote><cite><ul><ol><li><div><span>'
		. '<table><tr><td><th><tbody><thead><tfoot><caption><colgroup><col><figure><figcaption><hr><code><pre><sup><sub><small><section><article><header><footer><aside><nav>';
	$html = strip_tags($html, $allowed);

	return trim($html);
}

/**
 * @param string $str Raw body field HTML
 * @return string Wrapped fragment with inline styles on common tags
 */
function promailerEmailInlineBodyHtml(string $str): string {
	$str = trim($str);
	if ($str === '') {
		return '';
	}

	$s = wire('sanitizer');
	$styles = [
		'fontFamily' => $s->entities(
			'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif'
		),
		'linkColor' => '#e83561',
		'lineHeight' => '1.8em',
		'fontSize' => '16px',
		'blockquoteBorder' => '#a9a9a9',
		'blockquoteColor' => 'rgb(100, 100, 100)',
		'blockquoteSize' => '16px',
		// Fluid in column: width 100% + max-width helps iOS Mail resolve width vs the table shell.
		// SOGo often ignores margin on <img>; padding-bottom on the tag still reserves space below the image.
		'imgBlock' => 'width: 100%; max-width: 600px; height: auto; display: block; padding-bottom: 10px',
		'pImageOnly' => 'margin: 0; padding: 0; line-height: 0',
		'h1Block' => 'background-color: #000; padding: 20px 15px; color: #fff; margin: 0; font-size: 28px; font-weight: 200',
		'h2Block' => 'font-size: 24px;',
	];

	$ff = $styles['fontFamily'];
	$lc = $styles['linkColor'];
	$lh = $styles['lineHeight'];
	$bb = $styles['blockquoteBorder'];
	$bc = $styles['blockquoteColor'];
	$bs = $styles['blockquoteSize'];
	$im = $styles['imgBlock'];
	$pi = $styles['pImageOnly'];
	$fs = $styles['fontSize'];
	$h1 = $styles['h1Block'];
	$h2 = $styles['h2Block'];

	// Order matters: more specific <p>…<img> patterns before bare <p>.
	$replacements = [
		'<p><img' => "<p style=\"{$pi}\"><img",
		'<p> <img' => "<p style=\"{$pi}\"> <img",
		'<p>' . "\n" . '<img' => "<p style=\"{$pi}\">\n<img",
		'<p>' . "\r\n" . '<img' => "<p style=\"{$pi}\">\r\n<img",
		'<p dir="ltr">' => "<p dir=\"ltr\" style=\"line-height: {$lh}; font-size: {$fs};\">",
		'<p>' => "<p style=\"line-height: {$lh}; font-size: {$fs};\">",
		'<h1>' => "<h1 style=\"{$h1};\">",
		'<h2>' => "<h2 style=\"{$h2};\">",
		'<a href' => "<a style=\"color: {$lc};\" href",
		'<img ' => "<img style=\"{$im};\" ",
		'<blockquote>' => "<blockquote style=\"border-left: 4px solid {$bb}; margin: 0.5em 1em; padding: 0 15px; color: {$bc}; font-size: {$bs};\">",
	];

	$str = str_replace(array_keys($replacements), array_values($replacements), $str);

	// Table shell: gives Mail.app/iOS a concrete width context so fluid images (width:100% +
	// max-width:600px) size to the column, not the viewport alone. See .outer-wrapper td.
	return '<table role="presentation" class="outer-wrapper" width="100%" border="0" cellpadding="0" cellspacing="0" style="width: 100%; max-width: 600px; border-collapse: collapse; margin: 0 auto;">'
		. '<tr><td width="600" class="outer-wrapper__cell" style="width: 600px; max-width: 100%; padding: 0; vertical-align: top; font-family: '
		. $ff
		. '; font-size: '
		. $fs
		. '; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">'
		. $str
		. '</td></tr></table>';
}
