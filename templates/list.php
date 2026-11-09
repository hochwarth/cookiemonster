<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var CookieMonster $cmnstr
 * @var array $headers
 * @var array $cookies
 * @var bool $interactive
 * @var Sanitizer $sanitizer
 */

?>

<ul class='cmnstr-list'>
	<?php foreach ($cookies as $category): ?>
		<?php
        $isEssential = ($category['key'] === 'essential');
        ?>
		<li class="cmnstr-category">
			<?php if (!empty($category['groups'])): ?>
				<ul class='cmnstr-groups' data-parent="<?= $category['key'] ?>">
					<?php foreach ($category['groups'] as $group): ?>
						<?php
                        $groupId = "{$category['key']}-{$group['id']}";
                        $isChecked = $cmnstr->isUnlocked($groupId);
                        ?>
						<li class='cmnstr-group'>
							<div class="cmnstr-grid">
								<div class="cmnstr-group-info">
									<p<?= $interactive ? " id=\"cmnstr-{$groupId}-title\"" : ''; ?> class="cmnstr-group-title"><strong><?= $sanitizer->entities($group['title'] ?: $category['title']); ?></strong></p>

										<?php if (!empty($group['description'])): ?>
											<p<?= $interactive ? " id=\"cmnstr-{$groupId}-description\"" : ''; ?> class='cmnstr-group-description'><?= $sanitizer->entities($group['description']); ?></p>
											<?php endif; ?>
								</div>

								<?php if ($interactive): ?>
									<label class="cmnstr-field" for="cmnstr-<?= $groupId; ?>">
										<span id="cmnstr-<?= $groupId; ?>-label" class="cmnstr-label"><?= $cmnstr->text('active'); ?></span>
										<input
											type="checkbox"
											id="cmnstr-<?= $groupId; ?>"
											name="cmnstr-<?= $groupId; ?>"
											class="cmnstr-checkbox cmnstr-group-checkbox"
											data-parent-category="cmnstr-<?= $category['key']; ?>"
											<?php if ($isChecked || $isEssential): ?>checked<?php endif; ?>
											<?php if ($isEssential): ?>disabled<?php endif; ?>
											aria-labelledby="cmnstr-<?= $groupId; ?>-title cmnstr-<?= $groupId; ?>-label"
											<?php if (!empty($group['description'])): ?>aria-describedby="cmnstr-<?= $groupId; ?>-description" <?php endif; ?>>
									</label>
								<?php endif; ?>
							</div>

							<?php if (\count($group['cookies']) > 0): ?>
								<details class='cmnstr-group-details'>
									<summary
										class='cmnstr-group-summary'
										aria-label="<?= $sanitizer->entities(\sprintf($cmnstr->text('list_show_for'), $group['title'] ?: $category['title'])); ?>">
										<?= $cmnstr->text('list_show'); ?>
									</summary>

									<div class="cmnstr-group-content">
										<ul class='cmnstr-cookies'>
											<?php foreach ($group['cookies'] as $cookie): ?>
												<li>
													<dl class="cmnstr-cookie">
														<?php foreach ($headers as $key => $label): ?>
															<dt class="cmnstr-cookie-label"><?= $sanitizer->entities($label); ?></dt>
															<dd class="cmnstr-cookie-description"><?= $sanitizer->entities($cookie[$key] ?? ''); ?></dd>
														<?php endforeach; ?>
													</dl>
												</li>
											<?php endforeach; ?>
										</ul>
									</div>
								</details>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>

<?php if (!$interactive): ?>
	<?= $cmnstr->renderConsentIdPlaceholder(); ?>
<?php endif; ?>
