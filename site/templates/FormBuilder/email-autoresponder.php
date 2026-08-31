<?php namespace ProcessWire;

/**
 * This is the email template used by the 'Autoresponder' feature in Form Builder
 *
 * CUSTOMIZE
 * =========
 * To customize this email, copy this file to /site/templates/FormBuilder/email-autoresponder.php and modify it as needed.
 * To make it just for a one form, name it email-autoresponder-myform.php, replacing "myform" with form name.
 * It's preferable to do this so that your email template doesn't get overwritten during FormBuilder upgrades.
 * Inline styles are recommended in the markup since not all email clients will use <style></style> declarations.
 *
 * VARIABLES
 * =========
 * @var array $values This is an array of all submitted field values with ('field name' => 'field value') where the 'field value' is ready for output.
 * @var array $labels This is an array of all field labels with ('field name' => 'field label') where the 'field label' is ready for output.
 * @var array $formData Raw form data array, which is the same as $values but unformatted and with additional properties like 'entryID' and '_savePage' id.
 * @var string $body Optional body text for email, if provided. 
 * @var string $subject Subject line for email, if needed. 
 * @var InputfieldForm $form Containing the entire form if you want grab anything else from it.
 * @var FormBuilderEmail $formBuilderEmail Instance of FormBuilderEmail that is sending the message.
 * @var string $emailField Name of field in form that auto-responder email address came from. 
 * @var string $viewEntryUrl Direct URL to view the form entry in the admin. 
 *
 *
 */

if(!defined("PROCESSWIRE")) die();

$styles = array(
	'table' => 'width:100%;border-bottom: 1px solid #ccc',
	'label' => 'width:30%;text-align:right;font-weight:bold;padding:10px 10px 10px 0;vertical-align:top;border-top:1px solid #ccc',
	'value' => 'width:70%;padding: 10px 0 10px 0;border-top: 1px solid #ccc',
);

?><!DOCTYPE html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?php echo $form->name; ?> auto-response</title>
</head>
<body>

<?php if(strlen($body)):
	// htmlentities() first, THEN swap in any raw HTML placeholders — swapping before this
	// point would get the injected markup escaped too, showing as literal text in the email
	// instead of rendering (e.g. a real <a> link showing up as visible "<a href=...>" text).
	$bodyHtml = nl2br(htmlentities($body, ENT_QUOTES, 'UTF-8', false));
	$bodyHtml = str_replace('{secure_pdf_link}', $secure_pdf_link_html ?? '', $bodyHtml);
?>

	<p><?php echo $bodyHtml; ?></p>

<?php else: ?>

	<p><?php echo __('Thank you for the form submission. This is an auto-responder to let you know your submission was received. Below is a summary of what you submitted.'); ?></p>

	<table style='<?php echo $styles['table']?>' cellspacing='0'>

		<?php foreach($values as $name => $value): ?>

		<tr>
			<th class='label' style='<?php echo $styles['label']?>'>
				<?php echo $labels[$name]; ?>
			</th>
			
			<td class='value' style='<?php echo $styles['value']?>'>
				<?php echo $value; ?>
			</td>
		</tr>

		<?php endforeach; ?>

	</table>

	<p><small><?php echo __('Sent by ProcessWire Form Builder'); ?> &bull; <?php echo date('Y/m/d g:ia'); ?></small></p>

<?php endif; ?>

</body>
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
</html>
