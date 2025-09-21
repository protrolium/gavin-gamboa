<?php namespace ProcessWire;

/**
 * ProMailer “Subscribe” page template file
 * 
 * Please see this URL for these instructions in HTML:
 * https://processwire.com/store/pro-mailer/manual/#about-the-promailer-subscribe-page-template-file
 * 
 * This template file should be named: /site/templates/promailer-subscribe.php
 * and should be used by 1 page in your site (which ProMailer sets up for you). 
 * Feel free to modify anything in this file as you see fit. 
 * 
 * The purpose of this template file is to manage subscriptions and un-subscriptions
 * for ProMailer. It also is also the endpoint for webhook submissions from your mail
 * delivery service (if used). If you are not using user initiated subscribe/unsubscribe 
 * features then you do not need this template file or the page using it. If you do 
 * want these features (and most likely you do) then read on…
 * 
 * 1. As it currently stands, this template file just outputs a form to subscribe or 
 *    unsubscribe. It needs to be updated to work with your output strategy. Depending
 *    on your output strategy, you should modify this file as follows: 
 * 
 *    - If you are using direct output, you'll want to add include() statements for your 
 *      header/footer files where output is rendered at the bottom of this file. 
 * 
 *    - If you are using delayed output, you'll want to modify the last line in this file 
 *      containing the `$out` variable to populate whatever variable you need, i.e. 
 *     `$bodycopy = $out;` or similar. 
 * 
 *    - If you are using Markup Regions, you'll want to modify the last line in this file 
 *      to be something like `echo "<div id='content'>$out</div>";` where "content" would 
 *      be the id of the element you want to populate.
 * 
 *    - If you aren't sure what you are using, then leave things as they are for now and
 *      try to view the page using this template file to see whether or not it works. Make
 *      adjustments as needed, or post in the ProMailer support board and we can help
 *      you figure it out. 
 * 
 * 2. This file contains several options and markup that you can modify as needed. For
 *    instance, you may want to update the form markup, and the error/success message markup.
 *    Or you may want to modify the labels used for fields and/or error/success messages. 
 *    Or maybe you want to modify the contents of the confirmation email that a user receives
 *    after subscribing to your list. When it comes to markup, numerous placeholders like 
 *    “{this}” are used, and those are tags that ProMailer replaces with the appropriate 
 *    text during rendering. 
 * 
 * 3. If you'd like, you can make your own subscribe form completely independent of this file, 
 *    and then simply use the page (represented by this template file) as the form processor.      
 *    If you go that route, then specify false for the 'useCSRF' option on the subscribe form,
 *    make sure you use the same form 'action' attribute and input name attributes. For instance,
 *    the email field should be named "subscribe_email" and the submit field should be named
 *    "subscribe_submit". 
 * 
 * 4. If you want to use webhooks to manage things like bounces, copy the file having the name
 *    /site/modules/ProMailer/promailer-webhooks.inc to your /site/templates/ directory. This
 *    template file will detect and include it when present. Read the documentation in the 
 *    promailer-webhooks.inc file for more information about how to use it. 
 * 
 * 5. If you want to render a subscribe form from another page template file, you may 
 *    include this file from other template files and it will simply output the subscribe form
 *    at the location where you include it: include('./promailer-subscribe.php'); 
 * 
 * There are a few examples of hooks you can use in the promailer-hooks.php file included
 * with ProMailer. Feel free to copy/paste any of those into your /site/ready.php file if
 * they suit your needs. 
 * 
 */

if(!defined("PROCESSWIRE")) die();

/** @var WireInput $input */
/** @var Sanitizer $sanitizer */
/** @var Modules $modules */
/** @var Page $page */

/******************************************************************************************************
 * COMMON MARKUP
 * 
 * Common markup to use in output, modify as needed, keeping the {placeholders}
 * 
 */
$successMarkup = "<p class='success'>{message}</p>";
$errorMarkup = "<p class='error'>{message}</p>";
$wrapMarkup = "<div id='promailer'>{out}</div>";

/*****************************************************************************************************
 * SUBSCRIBE FORM AND OPTIONS
 * 
 * 1. Modify the form markup for the subscribe form below as needed. You may use the following 
 *    placeholders in the markup, which are automatically replaced with the necessary values by
 *    ProMailer when the form is rendered: 
 *
 *    {list} - Title of the list being subscribed to
 *    {list_id} - ID of the list being subscribed to (you probably do not need this).
 *    {url} - URL the form should submit to, aka action attribute (required). 
 *    {email_name} - Name for the email input field (required).
 *    {email_placeholder} - Placeholder text for email input (optional).
 *    {submit_name} - Name attribute for the submit button (required).
 *    {submit_label} - Text label for submit button (optional). 
 *    {honeypot} - This is replaced with an invisible spam-prevention honeypot input (optional). 
 *    {extras} - Extra markup & hidden inputs that ProMailer needs to place in the form (required). 
 * 
 * 2. If you have any custom fields you also want to use, please do the following:
 * 
 *   - Add new inputs for them in the form markup, using your own markup. You do NOT have to use
 *     placeholders for attributes or labels like the default fields, just write the markup how
 *     you usually would. 
 * 
 *   - For the name attributes, use the same names you defined for the “Define custom fields” 
 *     section of your List in the ProMailer admin. 
 * 
 *   - For any required fields, add an HTML5 required="required" attribute to the <input>.
 * 
 * 3. Modify the “Subscribe options” ($subscribeOptions) array that appears below the form 
 *    markup. There are many settings, feel free to modify as you see fit, though for most the
 *    defaults are just fine. Please at least populate the 'emailFrom' option, which will be 
 *    used as the “from” address on the double opt-in confirmation email that the user receives
 *    to confirm their subscription. 
 * 
 * 4. If using multi-language support, translate this file (/site/templates/promailer-subscribe.php)
 *    in Setup > Languages > each language, as needed. 
 * 
 */
$subscribeForm = <<< _OUT
	<form id='promailer-form' action='{url}#promailer-form' method='post'>
		<label style="font-family: 'NyghtSerif'; font-size: 24px;" for='{email_name}'>{email_label}</label>
		<br>
		<input style="font-family: 'NyghtSerif'; font-size: 16px; margin-top: 5px;" type='email' id='{email_name}' name='{email_name}' placeholder='{email_placeholder}' value='{email_value}' required>
		{honeypot}
		<button style="font-size: 19px; margin-top: 5px;" type='submit' name='{submit_name}' value='1'>{submit_label}</button>
		{extras}
	</form>
_OUT;

/**
 * Confirmation email body
 * 
 * Below are both the text and HTML versions of the email that a user receives 
 * after submitting the subscribe form. This email simply asks them to confirm their
 * subscription by clicking a button/URL. Available placeholders include {url} which is the
 * confirmation URL, and {email_note} which is replaced with a sentence of instructions
 * (see the `emailNote` setting further down if you want to change that). 
 * 
 */
$emailBodyText = "{email_note}\n\n{url}";
$emailBodyHTML = <<< _OUT
	<html><body>
		<p>{email_note}</p>
		<p><a href='{url}'>{email_confirm}</a></p>
	</body></html>
_OUT;

/**
 * Options and labels for the subscription form
 * 
 */
$subscribeOptions = array(
	// settings
	'listId' => (int) $input->get('list'), // ID of the subscribers list that this form is for
	'useCSRF' => true,

	// markup
	'errorMarkup' => $errorMarkup,
	'successMarkup' => $successMarkup,
	'formMarkup' => $subscribeForm,
	'wrapMarkup' => $wrapMarkup, 
	
	// double opt-in confirmation email settings
	'emailSubject' => __('{list} - Confirm your subscription'),
	'emailSubject2' => __('Reminder: {list} - Please confirm your subscription'),
	'emailNote' => __('Thank you for your interest in my newsletter. Please confirm your subscription by clicking below:'), // Please confirm your subscription to the “{list}” list by clicking below:
	'emailConfirm' => __('Confirm Subscription'), 
	'emailBodyHTML' => trim($emailBodyHTML),
	'emailBodyText' => $emailBodyText, 
	'emailFrom' => 'newsletter@gavingamboa.net', // optional email address from, i.e. "news@processwire.com" 
	'emailFromName' => 'Gavin Gamboa', // optional email name from, i.e. "ProcessWire"
	
	// field labels
	'emailFieldLabel' => __('Newsletter'),
	'emailFieldPlaceholder' => __('email@domain.com'),
	'submitFieldLabel' => __('➩'),

	// success messages
	'successEmailSent' => __('EMAIL-SENT'),
	'successConfirmed' => __('SUBSCRIPTION-CONFIRMED'),

	// error messages
	'errorListUnknown' => __('Unknown mailing list'),
	'errorListClosed' => __('List is closed'),
	'errorCSRF' => __('Invalid request, please try again'),
	'errorRequired' => __('Missing one or more required fields'),
	'errorDuplicate' => __('You are already subscribed to this list'),
	'errorEmail' => __('Email address did not validate'),
	'errorSend' => __('Error sending confirmation email'),
	'errorAdd' => __('Error adding you to this mailing list'),
	'errorHoneypot' => __('Please look for the confirmation email. If you do not receive it in 5 minutes, please try to subscribe again.'), // pretend success
);

/*****************************************************************************************************
 * UN-SUBSCRIBE FORM AND OPTIONS
 *
 * 1. Modify the form markup for the unsubscribe form below as needed. You may use the following
 *    placeholders in the markup, which are automatically replaced with the necessary values by
 *    ProMailer when the form is rendered:
 *
 *    {list} - Title of the list being unsubscribed from. 
 *    {list_id} - ID of the list being unsubscribed from (you probably do not need this).
 *    {url} - URL the form should submit to, aka action attribute (required).
 *    {email_name} - Name for the email input field (required).
 *    {email_value} - Pre-populated value for the email field (required). 
 *    {submit_name} - Name attribute for the submit button (required).
 *    {submit_label} - Text label for submit button (optional).
 *    {extras} - Extra markup & hidden inputs that ProMailer needs to place in the form (required).
 *
 * 2. Modify the “Unsubscribe options” ($unsubscribeOptions) array that appears below the form
 *    markup. There are many settings, feel free to modify as you see fit, though for most the
 *    defaults are just fine. 
 *
 */

$unsubscribeForm = <<< _OUT
	<form id='promailer-form' action='{url}#promailer-form' method='post'>
		<label for='{email_name}'>{email_label}</label>
		<input type='email' id='{email_name}' name='{email_name}' value='{email_value}'> 
		<button type='submit' name='{submit_name}' value='1'>{submit_label}</button>
		{extras}
	</form>
_OUT;

$unsubscribeOptions = array(
	'useCSRF' => true,
	'errorMarkup' => $errorMarkup,
	'successMarkup' => $successMarkup,
	'formMarkup' => $unsubscribeForm,
	'wrapMarkup' => $wrapMarkup,
	'emailFieldLabel' => __('Email address to unsubscribe'),
	'submitFieldLabel' => __('Confirm unsubscribe'),
	'errorInvalid' => __('Invalid unsubscribe request'),
	'successMessage' => __('You have successfully unsubscribed from the “{list}” mailing list.'),
	'confirmMessage' => __('Please confirm that you want to unsubscribe from “{list}”.'),
);

/****************************************************************************************************
 * STRING TOKENS SETTINGS
 * 
 */

$replacements = [
	'EMAIL-SENT' => '<p>Thank you! A confirmation email has been sent to your inbox.</p>',
    'SUBSCRIPTION-CONFIRMED' => '<h1>Your subscription has been confirmed.</h1> <h2>My newsletter is low-frequency and it is self-hosted, which means that your inbox will not be cluttered, and your email addresses will not be sent via—nor sold to—a third party, ever.</h2> <h3>Thank you for your support.</h3>'
];

/*************************************************************************************************************
 * EXECUTE 
 * 
 */

/** @var ProMailer $promailer */
$promailer = $modules->getModule('ProMailer');

// is this file included from another?
$isInclude = $page->template->name != 'promailer-subscribe';

// determine what kind of request we are going to serve: webhook, unsubscribe or subscribe
if($input->get('webhook') && !$isInclude) {
	// webhooks (optional)
	if(is_file('./promailer-webhooks.inc')) include('./promailer-webhooks.inc');
	throw new Wire404Exception();
	
} else if($input->get('unsub') && !$isInclude) {
	// unsubscribe
	$out = $promailer->forms->unsubscribe($unsubscribeOptions); 
	
} else {
	// subscribe 
	$out = $promailer->forms->subscribe($subscribeOptions);
}

// update string tokens
$out = str_replace(array_keys($replacements), array_values($replacements), $out);

// if this file was included from another, we echo the output now, otherwise we go to MAIN OUTPUT
if($isInclude): echo $out; else: 
	
/*************************************************************************************************************
 * MAIN OUTPUT
 * 
 * Uncomment your intended output strategy below (and remove or comment out any others)
 *
 */

// A. DIRECT OUTPUT (like that used in the Processwire “site-beginner” profile)
// -----------------------------------------------------------------------------------------------------------
// include('./_head.php');
// echo $out;
// include('./_foot.php'); 

// B. DELAYED OUTPUT (like that used in the ProcessWire “site-default” profile)
// -----------------------------------------------------------------------------------------------------------
// $content = $out; 

// C. MARKUP REGIONS (like that used in the ProcessWire “site-regular” profile)
// -----------------------------------------------------------------------------------------------------------
echo "<div id='content-body'>$out</div>"; 

// do not modify below this line
endif; 
unset($out);