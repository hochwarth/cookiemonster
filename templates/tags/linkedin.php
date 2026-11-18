<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string $insightId
 * @var string $cspNonce
 * @var bool $noScript
 * @var Sanitizer $sanitizer
 */
$id = $sanitizer->digits($insightId);

?>

<!-- LinkedIn Insight -->
<?php if (!$noScript): ?>
	<script nonce="<?= $cspNonce; ?>">
		_linkedin_partner_id = "<?= $id; ?>";
		window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
		window._linkedin_data_partner_ids.push(_linkedin_partner_id);
	</script>
	<script nonce="<?= $cspNonce; ?>">
		(function(l) {
		if (!l){window.lintrk = function(a,b){window.lintrk.q.push([a,b])};
		window.lintrk.q=[]}
		var s = document.getElementsByTagName("script")[0];
		var b = document.createElement("script");
		b.type = "text/javascript";b.async = true;
		b.src = "https://snap.licdn.com/li.lms-analytics/insight.min.js";
		s.parentNode.insertBefore(b, s);})(window.lintrk);
	</script>
<?php else: ?>
	<noscript>
		<img height="1" width="1" class="cmnstr-sr-only" alt=""
			src="https://px.ads.linkedin.com/collect/?pid=<?= $id; ?>&amp;fmt=gif">
	</noscript>
<?php endif; ?>
