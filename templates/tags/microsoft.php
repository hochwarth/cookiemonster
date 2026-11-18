<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string $tagId
 * @var string $cspNonce
 * @var bool $noScript
 * @var Sanitizer $sanitizer
 */
$id = $sanitizer->digits($tagId);

?>

<!-- Microsoft Advertising UET -->
<?php if (!$noScript): ?>
	<script nonce="<?= $cspNonce; ?>">
		window.uetq = window.uetq || [];
		window.uetq.push('consent', 'default', { 'ad_storage': 'granted' });
		(function(w,d,t,r,u){var f,n,i;w[u]=w[u]||[],f=function(){var o={ti:"<?= $id; ?>", enableAutoSpaTracking: true};o.q=w[u],w[u]=new UET(o),w[u].push("pageLoad")},n=d.createElement(t),n.src=r,n.async=1,n.onload=n.onreadystatechange=function(){var s=this.readyState;s&&s!=="loaded"&&s!=="complete"||(f(),n.onload=n.onreadystatechange=null)},i=d.getElementsByTagName(t)[0],i.parentNode.insertBefore(n,i)})(window,document,"script","//bat.bing.com/bat.js","uetq");
	</script>
<?php else: ?>
	<noscript>
		<img height="1" width="1" class="cmnstr-sr-only" alt=""
			src="https://bat.bing.com/action/0?ti=<?= $id; ?>&amp;Ver=2">
	</noscript>
<?php endif; ?>
