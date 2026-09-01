<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string $propertyId
 * @var string $cspNonce
 * @var bool $noScript
 */

?>

<?php if (!$noScript): ?>
	<!-- Global Site Tag (gtag.js) - Google Analytics -->
	<script nonce="<?= $cspNonce; ?>" async src="https://www.googletagmanager.com/gtag/js?id=<?= $propertyId ?>"></script>
	<script nonce="<?= $cspNonce; ?>">
		gtag('js', new Date());
		gtag('config', '<?= $propertyId ?>');
	</script>
<?php endif; ?>
