<?php namespace ProcessWire;

/** @var Modules $modules */
/** @var Pages $pages */
/** @var Wire $wire */

/**
 * Escape URL for use as text inside RSS/XML elements (<link>, <guid>): bare & is invalid XML.
 */
function rss_xml_escape_url(string $url): string {
	return htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

require_once __DIR__ . '/scripts/email-body-inline-styles.php';

$rss = $modules->get('MarkupRSS');
if (!$rss || !method_exists($rss, 'render')) {
	throw new WireException('Install the MarkupRSS module to use the RSS template.');
}

$rss->title = 'Gavin Gamboa · Newsletter RSS Feed';
$rss->description = 'Dispatches from Gavin Gamboa, composer and creative technologist.';
$rss->itemTitleField = 'title';
// Full HTML body in <description> (CDATA + relativeToAbsoluteUrl for img/src). itemDescriptionLength=0 is required.
$rss->itemDescriptionField = 'body_rss_html';
$rss->itemDescriptionLength = 0;
$rss->itemContentField = '';

$items = $pages->find('parent=newsletter, template=promailer-email, sort=-published');

// MarkupRSS calls strip_tags($page->get(itemTitleField)) before empty check — skip pages with no title.
$withTitles = new PageArray();
foreach ($items as $p) {
	if (!$p->viewable()) {
		continue;
	}
	$title = $p->get('title');
	if ($title === null || trim(strip_tags((string) $title)) === '') {
		continue;
	}
	$withTitles->add($p);
}
$items = $withTitles;

$hookId = $wire->addHookAfter('Page::getUnknown', function (HookEvent $e) {
	if ($e->arguments(0) !== 'body_rss_html') {
		return;
	}
	$page = $e->object;
	if (!$page instanceof Page || !$page->id || $page->template->name !== 'promailer-email') {
		return;
	}
	$e->return = promailerEmailBodyHtmlForRss($page->getUnformatted('body'));
});

try {
	$xml = $rss->renderFeed($items);
	header((string) $rss->header);
	echo $xml;
} finally {
	$wire->removeHook($hookId);
}

exit(0);
