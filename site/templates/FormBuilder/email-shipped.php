<?php namespace ProcessWire;

/**
 * Shipping notification email — sent from Setup > Forms > entries via the
 * "Send shipping notification" action (see the FORMBUILDER section in site/ready.php).
 *
 * VARIABLES
 * =========
 * @var string $firstName Customer's first name (from Stripe billing name), may be empty
 * @var string $carrier Carrier as entered on the entry (USPS, UPS, DHL, FedEx)
 * @var string $trackingNumber Tracking number as entered on the entry
 * @var string $trackingUrl Carrier tracking URL, or '' if the carrier isn't recognized
 *
 */

if(!defined("PROCESSWIRE")) die();

$e = function($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };

?><!DOCTYPE html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Your order has shipped</title>
</head>
<body>

	<p>Hello<?php echo $firstName !== '' ? ' ' . $e($firstName) : ''; ?>,</p>

	<p>Your order has shipped via <?php echo $e($carrier); ?>.</p>

	<p>Tracking number: <?php if($trackingUrl): ?><a style="color: #e83561;" href="<?php echo $e($trackingUrl); ?>"><?php echo $e($trackingNumber); ?></a><?php else: echo $e($trackingNumber); endif; ?></p>

	<p>Thank you for your continued support, and for supporting artists directly.</p>

</body>
<?php include __DIR__ . '/_email-signature.php'; ?>
</html>
