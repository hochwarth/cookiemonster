<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string $tag
 * @var string $referrer
 * @var string $cspNonce
 * @var Sanitizer $sanitizer
 */

?>

<script nonce="<?= $cspNonce; ?>" data-referrer="<?= $sanitizer->entities($referrer); ?>">
	<?php if ($tag === 'ga' || $tag === 'gtm'): ?>
		gtag('set', {
			page_referrer: document.currentScript.dataset.referrer
		});
	<?php elseif ($tag === 'matomo'): ?>
		window._paq = window._paq || [];
		window._paq.push(["setReferrerUrl", document.currentScript.dataset.referrer]);
	<?php endif; ?>
</script>
