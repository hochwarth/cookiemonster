<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string $pixelId
 * @var string $cspNonce
 * @var Sanitizer $sanitizer
 */
$id = $sanitizer->name($pixelId);

?>

<!-- ChatGPT Ads Pixel -->
<script nonce="<?= $cspNonce; ?>">
	(function (w, d, s, u) {
		if (w.oaiq) return;
		var q = function () { q.q.push(arguments); };
		q.q = [];
		w.oaiq = q;
		var js = d.createElement(s);
		js.async = true;
		js.src = u;
		var f = d.getElementsByTagName(s)[0];
		f.parentNode.insertBefore(js, f);
	})(window, document, "script", "https://bzrcdn.openai.com/sdk/oaiq.min.js");

	oaiq("init", { pixelId: "<?= $id; ?>" });
	oaiq("measure", "page_viewed", { type: "contents" });
</script>
