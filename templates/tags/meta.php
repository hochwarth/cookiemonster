<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string $pixel
 * @var string $event - 'PageView'
 * @var string $cspNonce
 * @var bool $noScript
 */

?>

<!-- Meta Pixel -->
<?php if (!$noScript): ?>
	<script nonce="<?= $cspNonce; ?>" async>
		!function(f,b,e,v,n,t,s)
		{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
		n.callMethod.apply(n,arguments):n.queue.push(arguments)};
		if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
		n.queue=[];t=b.createElement(e);t.async=!0;
		t.src=v;s=b.getElementsByTagName(e)[0];
		s.parentNode.insertBefore(t,s)}(window, document,'script',
		'https://connect.facebook.net/en_US/fbevents.js');
		fbq('init', '<?= $pixel ?>');
		fbq('track', '<?= $event; ?>');
	</script>
<?php else: ?>
	<noscript>
		<img height="1" width="1" style="display:none"
			src="https://www.facebook.com/tr?id=<?= $pixel; ?>&ev=<?= $event; ?>&noscript=1" />
	</noscript>
<?php endif; ?>
