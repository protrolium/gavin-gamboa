<?php namespace ProcessWire;

/**
 * This is an example of what you might use for a ProcessWire template file for an email
 * 
 * Please see this URL for these instructions in HTML:
 * https://processwire.com/store/pro-mailer/manual/#working-with-email-page-template-files
 *
 * PLACEHOLDERS
 * ============
 * There are several {placeholders} that you can use which will be automatically populated
 * in the email:
 *
 * - {email} Email address of recipient (TO email)
 * - {from_email} Email address of the sender (FROM email)
 * - {from_name} Name of the sender
 * - {subject} Subject of the message
 * - {title} Title of the message (as identified in ProMailer admin)
 * - {unsubscribe_url} URL to unsubscribe user from this list
 * - {subscribe_url} URL to subscribe to this list
 * - PLUS any custom fields that you defined
 *
 * Note: if you are viewing a page using this template on its own (without ProMailer)
 * then none of these placeholders will be populated. That’s to be expected.
 *
 * URL VARIABLES
 * =============
 * Each rendered message will also receive several GET variables in the URL. The only
 * one that you may initially want is the `$type` variable, but the others are also present
 * should you ever want them:
 *
 * - $input->get('type');          // Message type, either "html" or "text" (string)
 * - $input->get('subscriber_id'); // ID of subscriber (int)
 * - $input->get('message_id');    // ID of message (int)
 * - $input->get('list_id');       // ID of subscriber list (int)
 * 
 * Note: if you are viewing a page using this template on its own (without ProMailer)
 * then none of these GET variables will be present, unless you populate them yourself.
 *
 * TIPS
 * ====
 * Use absolute (not relative) URLs for links to any assets such as other URLs or images.
 * For instance, your URLs should begin with "https://domain.com/" and not "/". However,
 * there are places where you may not have control over this (like in CKEditor body copy),
 * so do not worry, ProMailer will convert any relative URLs to absolute for you.
 *
 * Email clients can sometimes be pretty primitive with their HTML rendering ability,
 * especially Microsoft Outlook. But even Gmail has its quirks. If you don’t have the time
 * or patience to test your email in various email clients, one option is to use an
 * existing HTML/CSS email framework. Here are a few examples:
 *
 *  - Foundation for Emails: https://foundation.zurb.com/emails.html
 *  - MJML (Mail Jet Markup Language): https://mjml.io
 *  - Maizzle: https://maizzle.com/
 *  - Cerberus: https://tedgoas.github.io/Cerberus/
 *
 */

if(!defined("PROCESSWIRE")) die();

/** @var Page $page */
/** @var Pages $pages */
/** @var WireInput $input */
/** @var Sanitizer $sanitizer */
/** …and all the other ProcessWire API variables */

require_once __DIR__ . '/scripts/email-body-inline-styles.php';

// HTML email output
if($input->get('type') === 'html') { ?>

	<!DOCTYPE html>
	<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<title>{subject}</title>
		<style type='text/css'>
			body {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
				width: 100%;
				max-width: 600px;
				margin: 0 auto;
				padding: 0 16px;
				box-sizing: border-box;
				-webkit-text-size-adjust: 100%;
				-ms-text-size-adjust: 100%; 
			}
			a {
				color: #e83561;
			}
			h1 {
				background-color: #000;
				padding: 20px 15px;
				color: #fff;
				margin: 0;
				font-size: 28px;
				font-weight: 200;
			}
			footer {
				border-top: 1px solid #ccc;
				font-size: smaller;
				margin-top: 18px;
				padding-top: 5px;
			}
			blockquote {
				border-left: 4px solid darkgray;
				margin: 0.5em 1em;
				padding: 0px 15px;
				color:rgb(100, 100, 100);
				font-size: 16px;
			}
			p {
				line-height: 1.8em;
				font-size: 16px;
			}
			p:has(> img:only-child), p:has(> a:only-child > img) {
				margin: 0;
				padding: 0;
				line-height: 0;
			}
		</style>
	</head>
	<body style="width: 100%; max-width: 600px; margin: 0 auto; padding: 0 16px; box-sizing: border-box;">
		<h1 style="background-color: #000; padding: 20px 15px; color: #fff; margin: 0; font-size: 28px; font-weight: 200;">
			<span style="font-weight: 200; text-transform: uppercase;">Gavin Gamboa</span>
			<span style="font-size: 14px; font-weight: 400; color:darkgray; text-transform: uppercase;">Newsletter</span>
			<span style="display: block; font-size: 14px; font-weight: 400; color:darkgray; line-height: 0.6em; margin: 0 0 7px 0;">
				<br>
				<span style="text-transform: capitalize; color:darkgray;">composer · creative technologist</span>
			</span>
			<span style="color:thistle; font-weight: 700;"><strong><?=$page->title?></strong></span>
			<span style="display: block; font-size: 12px; color: #fff; font-weight: 300; line-height: 0.75em;">
				<br>
				Bulletin • <strong><?=date('Y F j', $page->modified)?></strong> → Los Angeles • Issue 1
			</span>
		</h1>

		<?php if($page->hasField('featured_image') && $page->featured_image->first): $fi = $page->featured_image->first; ?>
			<p style="margin:0;padding:0;line-height:0;">
				<img src="<?=$fi->url?>" width="600" alt="<?=$sanitizer->entities($fi->description)?>" style="width: 100%; max-width: 600px; height: auto; display: block;">
			</p>
		<?php endif; ?>
		
		<!-- force the browser view url to be HTML markup version of the page -->
		<?php
			$browserViewUrl = $page->httpUrl;
			$browserViewUrl .= (strpos($browserViewUrl, '?') === false ? '?' : '&') . 'type=html&preview=1';
		?>
		<!-- display links to browser view and RSS feed -->
		<p style="font-size: 12px;">view this message in the 
			<a style="color: #e83561;" href="<?=$sanitizer->entities($browserViewUrl)?>" target="_blank">browser</a>
			· or subscribe via 
			<a style="color: #e83561;" href="https://gavingamboa.net/rss/" target="_blank">RSS</a></p>
		
		<!-- <?=$page->get('body')?> original body code -->
		<?= promailerEmailInlineBodyHtml($page->get('body')) ?>
		
		<footer>
			<h4 style="margin-bottom: 2px;">Gavin Gamboa · <a style="color: #e83561;" href="https://gavingamboa.net" target="_blank">website</a> · <a style="color: #e83561;"href="https://gavart.ist" target="_blank">wiki</a></h4>
			<span><em>composer · creative technologist</em></span>
			<br>
			<?php 
				$juliaImage = $pages->get('name=julia-set-001, template=image');
				if($juliaImage->id && $juliaImage->featured_image->first) {
					$juliaUrl = $juliaImage->featured_image->first->url;
					echo '<img src="' . $sanitizer->entities($juliaUrl) . '" width="100" alt="" style="width: 100px; max-width: 100px; height: auto; display: block;">';
				}
			?>
			<br>
			<a style="color: #e83561;" href="https://gav.cloud">Bandcamp</a> • 
			<a style="color: #e83561;" href="https://alpha.subvert.fm/gavin-gamboa">Subvert</a> • 
			<a style="color: #e83561;" href="https://youtube.com/@gavcloud">YouTube</a> 
			<br>
			<a style="color: #e83561;" href="https://sonomu.club/@gavcloud">Mastodon</a> • 
			<a style="color: #e83561;" href="https://bsky.app/profile/gav.cloud">Bluesky</a> • 
			<a style="color: #e83561;" href="https://instagram.com/gavcloud">Instagram</a>
			<br>
			&nbsp;
			<br>
			<a style="color: #e83561;" href="https://tonestrukt.org" target="_blank">Tonestrukt Editions</a> • Managing Editor
			<br>
			<a style="color: #e83561;" href="https://teachingmachine.tv" target="_blank">The Teaching Machine</a> • Creative Director
			<br>
			<a style="color: #e83561;" href="https://strangeloop-studios.com" target="_blank">Strangeloop Studios</a> • Content Operative
			<br>
			<h4 style="margin-top: 2px;">Newsletter <a style="color: #e83561;" href='{unsubscribe_url}'>Unsubscribe</a></h4>
		</footer>
	</body>
	</html>

	<?php

} else if($input->get('type') === 'text') {

	// Text-based email output (optional)
	if($input->get('preview')) header('content-type: text/plain');
	echo $sanitizer->getTextTools()->markupToText(strtoupper($page->title) . "\n\n" . $page->get('body'));
	echo "\n\n---\n\nTo unsubscribe visit: {unsubscribe_url}";

} else { // show preview links to our text and HTML emails ?>

	<html>
	<body>
		<ul>
			<li><a href='./?type=html&preview=1'>Preview HTML email</a></li>	
			<li><a href='./?type=text&preview=1'>Preview TEXT-only email</a></li>
			<?php if($page->editable()) echo "<li><a href='$page->editUrl'>Edit this email</a></li>"; ?>
		</ul>
	</body>
	</html>

	<?php
} // finished



