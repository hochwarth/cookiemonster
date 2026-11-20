<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var array<string, string> $defaultStates
 * @var array<string, string>|null $updateStates null = noch keine Entscheidung
 * @var array<string, mixed> $dataLayerState
 * @var string $cspNonce
 */
$flags = \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_THROW_ON_ERROR;

?>

<!-- Google Consent Mode v2 -->
<script nonce="<?= $cspNonce; ?>">
	window.dataLayer = window.dataLayer || [];
	function gtag() { dataLayer.push(arguments); }
	gtag('consent', 'default', <?= \json_encode($defaultStates, $flags) ?>);
	gtag('set', 'ads_data_redaction', true);
<?php if ($updateStates !== null): ?>
	gtag('consent', 'update', <?= \json_encode($updateStates, $flags) ?>);
<?php endif; ?>
	dataLayer.push({ cmnstr: <?= \json_encode($dataLayerState, $flags) ?> });
	dataLayer.push({ event: 'cmnstr_consent' });
</script>
