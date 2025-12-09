<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string[] $headers
 * @var array[] $cookies
 * @var Sanitizer $sanitizer
 */

?>

<ul class='cmnstr-list'>
	<?php foreach ($cookies as $cookie): ?>
		<li>
			<dl>
				<?php foreach ($headers as $header): ?>
					<dt><?= $header; ?></dt>
					<dd><?= $cookie[$header]; ?></dd>
				<?php endforeach; ?>
			</dl>
		</li>
	<?php endforeach; ?>
</ul>
