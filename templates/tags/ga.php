<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string $propertyId
 * @var string $cspNonce
 * @var bool $noScript
 * @var Sanitizer $sanitizer
 */
$id = $sanitizer->name($propertyId);

?>

<?php if (!$noScript): ?>
	<!-- Global Site Tag (gtag.js) - Google Analytics -->
	<script nonce="<?= $cspNonce; ?>" async src="https://www.googletagmanager.com/gtag/js?id=<?= $id; ?>"></script>
	<script nonce="<?= $cspNonce; ?>">
		gtag('js', new Date());
		gtag('config', '<?= $id; ?>');
	</script>
<?php endif; ?>
