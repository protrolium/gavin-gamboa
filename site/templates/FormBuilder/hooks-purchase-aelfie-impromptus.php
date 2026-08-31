<?php namespace ProcessWire;

// Loaded automatically by FormBuilder for the "purchase-aelfie-impromptus" form only.
// Generic hooks (quantity reading, receipt_email fix) live in FormBuilder/hooks.php instead,
// since those apply to every form, not just this one.

// score_type selects the product variant (Printed Score = 3000 cents, PDF = 1000 cents).
// Its option value is the full unit price in cents — PHP's (int) cast handles the leading
// "+" in the admin config ("+3000") correctly, it's not a delta. Replaces the $45 base
// amount entirely; FormBuilder loads hooks.php before this file, so the generic quantity
// hook there runs first and this has the final say.
//
// Quantity only makes sense for the physical option. FormBuilder's showIf hides the
// quantity field when PDF is selected, but a hidden field's value still gets submitted —
// relying on client-side JS to reset it is fragile (JS bugs, timing, disabled JS). Forcing
// it here, server-side, means the actual Stripe charge is correct no matter what the
// browser did or didn't do.
//
// Description gets the variant name appended, so the Stripe receipt/dashboard line item
// (and the customer's card statement/receipt) reads e.g. "...solo piano — PDF Download"
// rather than the same generic text regardless of which option was actually bought.
wire()->addHookAfter('FormBuilderProcessorStripe::getChargeInfo', function(HookEvent $event) {
    $scoreType = (string) wire('input')->post('score_type');
    $amount = (int) $scoreType;
    $info = $event->return;
    if ($amount > 0) {
        $info['amount'] = $amount;
    }
    if ($scoreType === '4500') {
        $variantLabel = 'Printed Score';
    } else {
        $variantLabel = 'PDF Download';
        $info['quantity'] = 1;
    }
    $info['description'] = trim(($info['description'] ?? '') . ' • ' . $variantLabel, ' •');
    $event->return = $info;
});

// PDF download link: every purchaser gets one, Printed Score included — not just the PDF
// variant. Uses shopBuildDigitalDownloadLink() (FormBuilder/hooks.php) to build the actual
// signed URL; this hook's own job is handing the result to the auto-responder template as a
// pre-built <a> tag via setTemplateVar — NOT a plain string substitution into $email->body,
// because email-autoresponder.php runs htmlentities() on the whole body; a raw <a href> tag
// inserted before that point would get escaped and show up as literal text instead of a link.
// The admin's Auto-responder Body should use the {secure_pdf_link} placeholder, which
// email-autoresponder.php replaces with this template var AFTER its own htmlentities() call.
wire()->addHookAfter('FormBuilderProcessor::emailFormResponderReady', function(HookEvent $event) {
    /** @var FormBuilderEmail $email */
    $email = $event->arguments(1);
    $data = $email->getRawFormData();
    $entryId = (int) ($data['entryID'] ?? 0);
    if (!$entryId) return;

    $linkHtml = shopBuildDigitalDownloadLink('purchase-aelfie-impromptus', $entryId, 'Download PDF');
    if ($linkHtml) $email->setTemplateVar('secure_pdf_link_html', $linkHtml);
});
