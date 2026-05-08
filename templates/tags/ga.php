<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string $propertyId
 * @var array<string, string> $googleConsentStates
 * @var string $cspNonce
 * @var bool $noScript
 */
$updateConsent = \json_encode($googleConsentStates);

?>

<?php if (!$noScript): ?>
	<!-- Global Site Tag (gtag.js) - Google Analytics -->
	<script nonce="<?= $cspNonce; ?>" async src="https://www.googletagmanager.com/gtag/js?id=<?= $propertyId ?>"></script>
	<script nonce="<?= $cspNonce; ?>" async>
		window.dataLayer = window.dataLayer || [];
		function gtag() { dataLayer.push(arguments); }
		gtag('consent', 'update', <?= $updateConsent ?>);
		gtag('js', new Date());
		gtag('config', '<?= $propertyId ?>');
	</script>
<?php endif; ?>
