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
	<!-- Google tag (gtag.js) -->
	<script nonce="<?= $cspNonce; ?>" async src="https://www.googletagmanager.com/gtag/js?id=<?= $id; ?>"></script>
	<script nonce="<?= $cspNonce; ?>">
		gtag('js', new Date());
		gtag('config', '<?= $id; ?>');
	</script>
<?php endif; ?>
