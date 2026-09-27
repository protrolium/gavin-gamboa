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

    // first name from the Stripe billing name, falling back to the form's own name field
    $fullName  = trim((string) (($payment['name'] ?? '') ?: ($entry['your_name'] ?? '')));
    $firstName = $fullName === '' ? '' : explode(' ', $fullName)[0];

    $html = wire('files')->render(wire('config')->paths->templates . 'FormBuilder/email-shipped.php', [
        'firstName'      => $firstName,
        'carrier'        => $carrier,
        'trackingNumber' => $trackingNumber,
        'trackingUrl'    => $trackingUrl,
    ]);

    $text = ($firstName !== '' ? "Hello $firstName," : 'Hello,') . "\n\n"
        . "Your order has shipped via $carrier.\n\n"
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
