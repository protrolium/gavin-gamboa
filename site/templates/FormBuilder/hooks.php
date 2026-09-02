<?php namespace ProcessWire;

// Loaded automatically by FormBuilder for EVERY form (unlike hooks-{formName}.php, which
// only loads for that one named form). Only put hooks here that are genuinely generic —
// product-specific logic (e.g. variant pricing) belongs in a per-form hooks file instead.

// Reads a "quantity" field if the form has one, multiplies the unit price by it. Safe no-op
// for forms without a quantity field ($qty stays at the floor of 1, same as the module's own
// default). Needed because FormBuilderProcessorStripe::getChargeInfo() hardcodes quantity=1
// otherwise — without this, quantity fields are collected but silently ignored at checkout.
wire()->addHookAfter('FormBuilderProcessorStripe::getChargeInfo', function(HookEvent $event) {
    $qty = (int) wire('input')->post('quantity');
    if ($qty < 1) $qty = 1;
    $info = $event->return;
    $info['quantity'] = $qty;
    $event->return = $info;
});

// Module sets payment_intent_data.receipt_email unconditionally when useEmailReceipt is on,
// even to an empty string when the entry has no email — Stripe then rejects the empty value
// as "invalid email address". Applies to any form that either has no on-site email field
// (relying on Stripe Checkout's own hosted email collection instead) or has one that wasn't
// filled in. Strip it whenever it's empty, regardless of which form.
wire()->addHookAfter('FormBuilderProcessorStripe::createStripeSessionData', function(HookEvent $event) {
    $data = $event->return;
    if (isset($data['payment_intent_data']['receipt_email']) && empty($data['payment_intent_data']['receipt_email'])) {
        unset($data['payment_intent_data']['receipt_email']);
        if (empty($data['payment_intent_data'])) unset($data['payment_intent_data']);
    }
    $event->return = $data;
});

// subscribe_to_the_newsletter checkbox opts the customer into the ProMailer newsletter list
//
// Email: prefers the form's own on-site email_address field (what the customer actually typed
// on this site) when present; falls back to payment_data.email (from Stripe's billing/customer
// details, populated by chargeSuccess itself) for a form with no on-site email field at all —
// so this keeps working regardless of which shop form is used.
//
// confirmed=true skips ProMailer's double opt-in email — they just completed a real purchase
// with a verified email via Stripe, so a "please confirm your subscription" email right after
// checkout would be redundant. Revisit if compliance requirements call for opt-in confirmation.
wire()->addHookAfter('FormBuilderProcessorStripe::chargeSuccess', function(HookEvent $event) {
    $entry = $event->return;
    if (empty($entry['subscribe_to_the_newsletter'])) return;

    $email = $entry['email_address'] ?? ($entry['payment_data']['email'] ?? '');
    if (!$email) return;

    $newsletterListId = 1;
    wire('promailer')->subscribers->add($email, $newsletterListId, true, []);
});

// Builds a signed, expiring download link for a digital-download product, verified/streamed
// by site/templates/shop-download.php — see that file's $digitalDownloads mapping for adding
// a new product. Returns a ready-to-use <a> tag, or '' if anything required is missing.
//
// Per-form hooks files call this — it doesn't hook anything itself, just centralizes the
// signing/URL-building so each product's hook only needs its own entry-gating logic (e.g.
// "only the PDF variant gets a link"), not a copy of the whole signing mechanism. Loaded via
// include_once (FormBuilder's own loader), so this function only ever gets declared once
// per request regardless of how many forms load hooks.php.
function shopBuildDigitalDownloadLink($formName, $entryId, $linkText) {
    $secret = (string) (wire('config')->pdfDownloadSecret ?? '');
    if (!$secret || !$formName || !$entryId) return '';

    $downloadPage = wire('pages')->get('/shop-download/');
    if (!$downloadPage->id) return '';

    $exp = time() + 7 * 86400; // 7-day window
    $sig = hash_hmac('sha256', "$formName|$entryId|$exp", $secret);
    $url = $downloadPage->httpUrl . '?form=' . urlencode($formName) . "&entry=$entryId&exp=$exp&sig=$sig";

    return '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '">'
        . htmlspecialchars($linkText, ENT_QUOTES) . '</a>';
}
