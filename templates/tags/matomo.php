<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string $matomoUrl - https://matomo.example.com/
 * @var string $documentTitle
 * @var string $siteId - 1
 * @var bool $shareTracking - false
 * @var bool $useCookies - true
 * @var string $shareDomain - https://*.example.com/
 * @var string $cspNonce
 * @var bool $noScript
 */

?>

<!-- Matomo -->
<?php if (!$noScript): ?>
	<script nonce="<?= $cspNonce; ?>" async>
		var _paq = window._paq = window._paq || [];
		_paq.push(["setDocumentTitle", <?= $documentTitle ? "'{$documentTitle}'" : 'document.title'; ?>]);
		<?php if (!$useCookies): ?>
			_paq.push(["disableCookies"]);
			_paq.push(["disableBrowserFeatureDetection"]);
		<?php endif; ?>
		<?php if ($shareTracking): ?>
			_paq.push(["setCookieDomain", "<?= $shareDomain; ?>"]);
		<?php endif; ?>
		_paq.push(['setVisitorCookieTimeout', 15778800]); // 6 Monate in Sekunden
		_paq.push(["trackPageView"]);
		_paq.push(["enableLinkTracking"]);
		(() => {
			const u = "<?= rtrim($matomoUrl, '/') . '/'; ?>";
			_paq.push(["setTrackerUrl", `${u}matomo.php`]);
			_paq.push(["setSiteId", "<?= $siteId; ?>"]);
			const g = document.createElement("script");
			const s = document.currentScript;
			g.async = true;
			g.src = `${u}matomo.js`;
			s?.replaceWith(g);
		})();
	</script>
<?php elseif ($useCookies): ?>
	<noscript>
		<img
			src="<?= $matomoUrl; ?>/matomo.php?idsite=<?= $siteId; ?>&amp;rec=1"
			referrerpolicy="no-referrer-when-downgrade"
			class="sr-only"
			loading="lazy"
			decoding="async"
			fetchpriority="low"
			alt="Tracking" />
	</noscript>
<?php endif; ?>
