<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string $matomoUrl
 * @var string $documentTitle
 * @var string $siteId
 * @var bool $shareTracking
 * @var bool $useCookies
 * @var string $shareDomain
 * @var string $cspNonce
 * @var bool $noScript
 * @var Sanitizer $sanitizer
 */
$matomoUrl = \rtrim($sanitizer->httpUrl($matomoUrl), '/') . '/';
$siteId = $sanitizer->digits($siteId) ?: '1';
$shareDomain = $sanitizer->nameFilter($shareDomain, ['.', '-', '*'], '');

?>

<!-- Matomo -->
<?php if (!$noScript): ?>
	<script
		nonce="<?= $cspNonce; ?>"
		data-matomo-url="<?= $sanitizer->entities($matomoUrl); ?>"
		data-document-title="<?= $sanitizer->entities($documentTitle); ?>">
		var _paq = window._paq = window._paq || [];
		_paq.push(["setDocumentTitle", document.currentScript.dataset.documentTitle || document.title]);
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
		((script) => {
			const u = script.dataset.matomoUrl;
			_paq.push(["setTrackerUrl", `${u}matomo.php`]);
			_paq.push(["setSiteId", "<?= $siteId; ?>"]);
			const g = document.createElement("script");
			g.async = true;
			g.src = `${u}matomo.js`;
			script.replaceWith(g);
		})(document.currentScript);
	</script>
<?php elseif ($useCookies): ?>
	<noscript>
		<img
			src="<?= $sanitizer->entities($matomoUrl); ?>matomo.php?idsite=<?= $siteId; ?>&amp;rec=1"
			referrerpolicy="no-referrer-when-downgrade"
			width="1"
			height="1"
			class="cmnstr-sr-only"
			alt="">
	</noscript>
<?php endif; ?>
