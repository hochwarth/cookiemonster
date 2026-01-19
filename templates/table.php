<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var array $headers Assoziatives Array der Header ['key' => 'Label']
 * @var array $cookies Das Array mit den gruppierten Cookies
 * @var Sanitizer $sanitizer
 */

?>

<div class='cmnstr-data-wrap'>
	<table class='cmnstr-table'>
		<thead>
			<tr>
				<?php foreach ($headers as $label): ?>
					<th><?= $sanitizer->entities($label); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>

		<tbody>
			<?php foreach ($cookies as $category): ?>
				<?php if (!empty($category['title'])): ?>
					<tr class="cmnstr-category-row">
						<td colspan="<?= count($headers); ?>" class="cmnstr-category-cell">
							<strong><?= $sanitizer->entities($category['title']); ?></strong>
						</td>
					</tr>
				<?php endif; ?>

				<?php foreach ($category['groups'] as $group): ?>

					<?php if (!empty($group['title'])): ?>
						<tr class="cmnstr-group-row">
							<td colspan="<?= count($headers); ?>" class="cmnstr-group-cell">
								<strong><?= $sanitizer->entities($group['title']); ?></strong>
								<?php if (!empty($group['description'])): ?>
									<br><small><?= $sanitizer->entities($group['description']); ?></small>
								<?php endif; ?>
							</td>
						</tr>
					<?php endif; ?>

					<?php foreach ($group['cookies'] as $cookie): ?>
						<tr>
							<?php foreach ($headers as $key => $label): ?>
								<td data-label="<?= $sanitizer->entities($label); ?>">
									<?= $sanitizer->entities($cookie[$key] ?? ''); ?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
