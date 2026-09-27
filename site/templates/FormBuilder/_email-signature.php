<?php namespace ProcessWire;

// Shared shop email signature — included by email-autoresponder.php (purchase confirmation)
// and email-shipped.php (shipping notification) so both always match. Expects the
// ProcessWire API vars $pages and $sanitizer in scope (both templates render via TemplateFile).

if(!defined("PROCESSWIRE")) die();

?>
<footer>
	<h4 style="margin-bottom: 2px;">Gavin Gamboa · <a style="color: #e83561;" href="https://gavingamboa.net" target="_blank">website</a> · <a style="color: #e83561;"href="https://gavart.ist" target="_blank">wiki</a></h4>
	<span><em>composer · creative technologist</em></span>
	<br>
	<?php
		$juliaImage = $pages->get('name=julia-set-001, template=image');
		if($juliaImage->id && $juliaImage->featured_image->first) {
			$juliaUrl = $juliaImage->featured_image->first->httpUrl;
			echo '<img src="' . $sanitizer->entities($juliaUrl) . '" width="100" alt="" style="width: 100px; max-width: 100px; height: auto; display: block;">';
		}
	?>
	<br>
	<a style="color: #e83561;" href="https://gav.cloud">Bandcamp</a> •
	<a style="color: #e83561;" href="https://subvert.fm/gavin-gamboa">Subvert</a> •
	<a style="color: #e83561;" href="https://youtube.com/@gavcloud">YouTube</a>
	<br>
	<a style="color: #e83561;" href="https://sonomu.club/@gavcloud">Mastodon</a> •
	<a style="color: #e83561;" href="https://bsky.app/profile/gav.cloud">Bluesky</a> •
	<a style="color: #e83561;" href="https://instagram.com/gavcloud">Instagram</a>
	<br>
</footer>
