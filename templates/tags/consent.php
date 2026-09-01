<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var array<string, string> $googleConsentStates
 * @var string $cspNonce
 */
$defaultConsent = \json_encode($googleConsentStates);

?>

<!-- Google Consent Mode v2 -->
<script nonce="<?= $cspNonce; ?>">
	window.dataLayer = window.dataLayer || [];
	function gtag() { dataLayer.push(arguments); }
	gtag('consent', 'default', <?= $defaultConsent ?>);
</script>
