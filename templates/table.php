<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var array<string, string> $headers
 * @var array $cookies
 * @var CookieMonster $cmnstr
 * @var Sanitizer $sanitizer
 */

?>

<div class='cmnstr-data-wrap' role="region" tabindex="0" aria-label="<?= $sanitizer->entities($cmnstr->text('table_label')); ?>">
	<table class='cmnstr-table'>
		<caption class="cmnstr-sr-only"><?= $sanitizer->entities($cmnstr->text('table_label')); ?></caption>

		<thead>
			<tr>
				<?php foreach ($headers as $label): ?>
					<th scope="col"><?= $sanitizer->entities($label); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>

		<?php foreach ($cookies as $category): ?>
			<tbody>
				<?php if (!empty($category['title'])): ?>
					<tr class="cmnstr-category-row">
						<th scope="colgroup" colspan="<?= \count($headers); ?>" class="cmnstr-category-cell">
							<?= $sanitizer->entities($category['title']); ?>
						</th>
					</tr>
				<?php endif; ?>

				<?php foreach ($category['groups'] as $group): ?>

					<?php if (!empty($group['title'])): ?>
						<tr class="cmnstr-group-row">
							<th scope="colgroup" colspan="<?= \count($headers); ?>" class="cmnstr-group-cell">
								<?= $sanitizer->entities($group['title']); ?>
								<?php if (!empty($group['description'])): ?>
									<br><small><?= $sanitizer->entities($group['description']); ?></small>
								<?php endif; ?>
							</th>
						</tr>
					<?php endif; ?>

					<?php foreach ($group['cookies'] as $cookie): ?>
						<tr>
							<?php foreach ($headers as $key => $label): ?>
								<?php if ($key === 'name'): ?>
									<th scope="row" class="cmnstr-cookie-name"><?= $sanitizer->entities($cookie[$key] ?? ''); ?></th>
								<?php else: ?>
									<td><?= $sanitizer->entities($cookie[$key] ?? ''); ?></td>
								<?php endif; ?>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</tbody>
		<?php endforeach; ?>
	</table>
</div>

<?= $cmnstr->renderConsentIdPlaceholder(); ?>
