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
/** @var WireInput $input */
/** @var Sanitizer $sanitizer */
/** …and all the other ProcessWire API variables */

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
				width: 600px;
				-webkit-text-size-adjust: 100%;
				-ms-text-size-adjust: 100%; 
			}
			a {
				color: #e83561;
			}
			h1 {
				background:rgb(0, 0, 0);
				padding: 20px 15px;
				color: #fff;
				margin: 0;
				font-size: 18px;
			}
			img {
				max-width: 600px;
				height: auto;
			}
			footer {
				border-top: 1px solid #ccc;
				font-size: smaller;
				margin-top: 30px;
				padding-top: 15px;
			}
		</style>
	</head>
	<body style="width: 600px;">
		<h1>Gavin Gamboa • Newsletter</h1>
		<?=$page->get('body')?>
		<footer>
			<h4 style="margin-bottom: 2px;">Gavin Gamboa</h4>
			<span><em>composer · creative technologist</em></span>
			<br>
			<?php 
				$juliaImage = $pages->get('name=julia-set-001, template=image');
				if($juliaImage->id && $juliaImage->featured_image->first) {
					echo '<img src="' . $juliaImage->featured_image->first->url . '" width="100" alt="">';
				}
			?>
			<br>
			<a href="https://gav.cloud">Bandcamp</a> • <a href="https://youtube.com/@gavcloud">YouTube</a> • <a href="https://instagram.com/gavcloud">Instagram</a> • <a href="https://sonomu.club/@gavcloud">Mastodon</a> • <a href="https://bsky.app/profile/gav.cloud">Bluesky</a> • <a href="https://gavart.ist">Wiki</a>
			<h4 style="margin-top: 2px;">Newsletter <a href='{unsubscribe_url}'>Unsubscribe</a></h4>
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



