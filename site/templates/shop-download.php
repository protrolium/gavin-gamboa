<?php namespace ProcessWire;

// Template for the /shop-download/ page. Verifies a signed, expiring link and streams the
// associated digital file — the only sanctioned way to reach a file in
// site/assets/shop-downloads/, which is otherwise blocked from direct access by its own
// .htaccess.
//
// URL format: /shop-download/?form=purchase-name&entry=123&exp=1787600000&sig=...
// form = FormBuilder form name, entry = entry ID, exp = Unix expiry timestamp,
// sig = HMAC-SHA256 over "form|entry|exp".
//
// Add an entry below for each new digitally-native product. The mapped filename is never
// taken from the URL — only the form name is, and that's checked against this table before
// it's used for anything — so there's no path-traversal surface to sanitize against.
$digitalDownloads = [
    'purchase-aelfie-impromptus' => [
        'file' => 'aelfie-impromptus.pdf',
        'downloadName' => 'Aelfie Impromptus.pdf',
        // Every paid entry gets the PDF, Printed Score included — no variant restriction.
        'variantField' => null,
        'variantValue' => null,
    ],
];

function shopDownloadDeny($message, $code = 403) {
    http_response_code($code);
    echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    exit;
}

$formName = (string) $input->get('form');
$entryId = (int) $input->get('entry');
$exp = (int) $input->get('exp');
$sig = (string) $input->get('sig');
$secret = (string) ($config->pdfDownloadSecret ?? '');

if (!$formName || !$entryId || !$exp || !$sig || !$secret) {
    shopDownloadDeny('This download link is invalid or has expired.');
}

if (!isset($digitalDownloads[$formName])) {
    shopDownloadDeny('This download link is invalid or has expired.');
}
$product = $digitalDownloads[$formName];

$expectedSig = hash_hmac('sha256', "$formName|$entryId|$exp", $secret);
if (!hash_equals($expectedSig, $sig)) {
    shopDownloadDeny('This download link is invalid or has expired.');
}

if (time() > $exp) {
    shopDownloadDeny('This download link has expired. Contact us if you need it resent.');
}

// Confirm this is a real, paid entry — not just a validly-signed URL for an ID that was
// never actually a completed purchase.
$form = $forms->form($formName);
$entry = $form->entries()->getById($entryId);

if (!$entry) {
    shopDownloadDeny('This download link is invalid or has expired.');
}

$status = $entry['payment_data']['status'] ?? '';
if (!in_array($status, ['paid', 'captured'], true)) {
    shopDownloadDeny('This download link is invalid or has expired.');
}

if ($product['variantField'] !== null) {
    $actual = (string) ($entry[$product['variantField']] ?? '');
    if ($actual !== (string) $product['variantValue']) {
        shopDownloadDeny('This download link is invalid or has expired.');
    }
}

$filePath = $config->paths->assets . 'shop-downloads/' . $product['file'];
if (!is_file($filePath)) {
    shopDownloadDeny('The file for this download is currently unavailable. Contact us for help.', 404);
}

while (ob_get_level()) ob_end_clean();
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . addslashes($product['downloadName']) . '"');
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');
readfile($filePath);
exit;
