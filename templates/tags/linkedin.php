<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string $insightId
 * @var string $cspNonce
 * @var bool $noScript
 * @var Sanitizer $sanitizer
 */
$insightId = $sanitizer->digits($insightId);

?>

<!-- LinkedIn Insight -->
<?php if (!$noScript): ?>
	<script nonce="<?= $cspNonce; ?>" async>
		_linkedin_partner_id = "<?= $insightId; ?>";
		window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
		window._linkedin_data_partner_ids.push(_linkedin_partner_id);
	</script>
	<script nonce="<?= $cspNonce; ?>" async>
		(function(){var s = document.getElementsByTagName("script")[0];
		var b = document.createElement("script");
		b.type = "text/javascript";b.async = true;
		b.src = "https://snap.licdn.com/li.lms-analytics/insight.min.js";
		s.parentNode.insertBefore(b, s);})();
	</script>
<?php else: ?>
	<noscript>
		<img height="1" width="1" class="cmnstr-sr-only" alt=""
			src="https://px.ads.linkedin.com/collect/?pid=<?= $insightId; ?>&amp;fmt=gif">
	</noscript>
<?php endif; ?>
