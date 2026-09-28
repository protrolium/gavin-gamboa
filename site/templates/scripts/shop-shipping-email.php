<?php namespace ProcessWire;

/**
 * Shop shipping notification helpers — used by the "Send shipping notification"
 * FormBuilder entry action (hooks in site/ready.php, email template in
 * site/templates/FormBuilder/email-shipped.php).
 * Safe to include multiple times (functions are guarded by the early return).
 */

if (function_exists(__NAMESPACE__ . '\shopSendShippedEmail')) return;

function shopTrackingUrl(string $carrier, string $trackingNumber): string {
    $urls = [
        'USPS'  => 'https://tools.usps.com/go/TrackConfirmAction?tLabels=',
        'UPS'   => 'https://www.ups.com/track?tracknum=',
        'FEDEX' => 'https://www.fedex.com/fedextrack/?trknbr=',
        'DHL'   => 'https://www.dhl.com/global-en/home/tracking.html?submit=1&tracking-id=',
    ];
    $key = strtoupper(preg_replace('/\s+/', '', $carrier));
    if (!isset($urls[$key])) return '';
    return $urls[$key] . rawurlencode(preg_replace('/\s+/', '', $trackingNumber));
}

// Product name for a shop form: the title of the shop-item page that embeds it (via its
// form_selection field), e.g. "Transmogrificantus Octet · Cassette". Falls back to the
// form's Stripe "Charge name" if no page uses the form. '' if neither is set.
function shopProductName(FormBuilderForm $form): string {
    $page = wire('pages')->get('template=shop-item, include=all, form_selection=' . wire('sanitizer')->selectorValue($form->name));
    if ($page->id && $page->title) return (string) $page->title;

    $stripe = $form->FormBuilderProcessorStripe;
    return trim((string) (is_array($stripe) ? ($stripe['chargeName'] ?? '') : ''));
}

function shopSendShippedEmail(array $entry, FormBuilderForm $form, Wire $notices): bool {
    $id = (int) $entry['id'];

    if (($entry['order_fulfilled'] ?? '') === 'Yes') {
        $notices->warning("Entry #$id already marked fulfilled — skipped (clear Order Fulfilled to re-send)");
        return false;
    }

    $trackingNumber = trim((string) ($entry['tracking_number'] ?? ''));
    $carrier        = trim((string) ($entry['carrier'] ?? ''));
    $payment        = is_array($entry['payment_data'] ?? null) ? $entry['payment_data'] : [];
    $email          = ($entry['email_address'] ?? '') ?: ($payment['email'] ?? '');

    if ($trackingNumber === '' || $carrier === '' || !$email) {
        $notices->warning("Entry #$id is missing a tracking number, carrier, or email — skipped");
        return false;
    }

    $trackingUrl = shopTrackingUrl($carrier, $trackingNumber);
    if (!$trackingUrl) {
        $notices->warning("Entry #$id: unrecognized carrier “{$carrier}” — email sent without a tracking link");
    }

    // first name from the Stripe billing name only — your_name is a spam honeypot, never a real name
    $fullName  = trim((string) ($payment['name'] ?? ''));
    $firstName = $fullName === '' ? '' : explode(' ', $fullName)[0];

    $productName = shopProductName($form);

    $html = wire('files')->render(wire('config')->paths->templates . 'FormBuilder/email-shipped.php', [
        'firstName'      => $firstName,
        'productName'    => $productName,
        'carrier'        => $carrier,
        'trackingNumber' => $trackingNumber,
        'trackingUrl'    => $trackingUrl,
    ]);

    $text = ($firstName !== '' ? "Hello $firstName," : 'Hello,') . "\n\n"
        . "Your item" . ($productName !== '' ? " $productName" : '') . " has shipped via $carrier.\n\n"
        . "Tracking number: $trackingNumber\n"
        . ($trackingUrl ? "$trackingUrl\n" : '')
        . "\nThank you for your continued support, and for supporting artists directly.\n"
        . "Gavin Gamboa\n";

    $sent = wireMail()
        ->to($email)
        ->from('shop@gavingamboa.net')
        ->fromName('Gavin Gamboa • Shop')
        ->replyTo('in@gav.cloud', 'Gavin Gamboa')
        ->subject('Your order has shipped')
        ->body($text)
        ->bodyHTML($html)
        ->send();

    if (!$sent) {
        $notices->error("Entry #$id: shipping email to $email failed to send");
        return false;
    }

    $form->entries()->saveField($id, 'order_fulfilled', 'Yes');
    return true;
}
