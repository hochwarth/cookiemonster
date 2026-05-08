<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string $gtmId
 * @var string $cspNonce
 * @var bool $noScript
 */

?>

<!-- Google Tag Manager -->
<?php if (!$noScript): ?>
	<script nonce="<?= $cspNonce; ?>" async>
		(function(w, d, s, l, i) {
			w[l] = w[l] || [];
			w[l].push({
				'gtm.start': new Date().getTime(),
				event: 'gtm.js'
			});
			var f = d.getElementsByTagName(s)[0],
				j = d.createElement(s),
				dl = l != 'dataLayer' ? '&l=' + l : '';
			j.async = true;
			j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
			f.parentNode.insertBefore(j, f);
		})(window, document, 'script', 'dataLayer', '<?= $gtmId; ?>');
	</script>
<?php else: ?>
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= $gtmId; ?>"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>
