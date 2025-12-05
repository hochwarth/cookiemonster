<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string[] $headers
 * @var array[] $cookies
 * @var Sanitizer $sanitizer
 */

?>

<table class='cmnstr-table'>
	<thead>
		<tr>
			<?php foreach ($headers as $header): ?>
				<th><?= $header; ?></th>
			<?php endforeach; ?>
		</tr>
	</thead>

	<tbody>
		<?php foreach ($cookies as $cookie): ?>
			<tr>
				<?php foreach ($cookie as $value): ?>
					<td><?= $value; ?></td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
