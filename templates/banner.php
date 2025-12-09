<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var CookieMonster $module
 * @var string $title
 * @var string $label
 * @var string $body
 * @var bool $open
 * @var string $buttonEdit
 * @var string $buttonConfirm
 * @var string $buttonDecline
 * @var string $buttonAccept
 * @var Page|NullPage $imprintPage
 * @var Page|NullPage $privacyPage
 * @var array $categories
 * @var Modules $modules
 * @var Pages $pages
 */

?>

<section aria-labelledby="cmnstr-title">
	<dialog
		<?= $open ? '' : 'open'; ?>
		id="cmnstr-alert"
		class="cmnstr"
		aria-labelledby="cmnstr-title">
		<header class="cmnstr-category-content"><?= \nl2br($body); ?></header>

		<footer class="cmnstr-footer">
			<button
				class="cmnstr-button"
				type="button"
				data-cmnstr-action="edit">
				<?= $buttonEdit; ?>
			</button>
			<button
				class="cmnstr-button"
				type="button"
				data-cmnstr-action="decline">
				<?= $buttonDecline; ?>
			</button>
			<button
				class="cmnstr-button"
				type="button"
				data-cmnstr-action="accept">
				<?= $buttonAccept; ?>
			</button>
		</footer>

		<?php if ($imprintPage->id || $privacyPage->id): ?>
			<nav class="cmnstr-nav">
				<?php if ($imprintPage->id): ?>
					<a href="<?= $imprintPage->url ?>"><?= $imprintPage->title ?></a>
				<?php endif; ?>
				<?php if ($privacyPage->id): ?>
					<a href="<?= $privacyPage->url ?>"><?= $privacyPage->title ?></a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>
	</dialog>

	<button
		class="cmnstr-button-hover"
		type="button"
		data-cmnstr-action="edit"
		title="<?= $buttonEdit; ?>">
		<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" viewBox="0 0 640 640">
			<path d="M322 92q-2-9-11-11-34-4-64 11l-74 40q-30 16-46 47l-36 76q-15 30-9 65l14 82q7 34 31 58l60 59q25 23 59 28l83 12q34 4 64-11l74-40q31-16 46-47l36-76q15-30 9-64-2-9-10-11c-52-8-93-47-105-97q-4-12-15-15c-55-8-98-52-107-106zm-50 116a32 32 0 1 1 0 64 32 32 0 0 1 0-64m-64 192a32 32 0 1 1 64 0 32 32 0 0 1-64 0m224-64a32 32 0 1 1 0 64 32 32 0 0 1 0-64" />
		</svg>
	</button>

	</button>

	<dialog
		id="cmnstr-dialog"
		class="cmnstr"
		aria-labelledby="cmnstr-title">
		<header class="cmnstr-header">
			<h2 id="cmnstr-title" class="cmnstr-title"><?= $title ?></h2>

			<form method="dialog" class="cmnstr-close-form">
				<button type="submit" class="cmnstr-close-button" aria-label="<?= __('Schließen'); ?>">
					<span aria-hidden="true">×</span>
				</button>
			</form>
		</header>

		<form class="cmnstr-form" id="cmnstr-form">
			<div class="cmnstr-categories">
				<details class="cmnstr-category-detail" name="cmnstr-details" open>
					<summary class="cmnstr-category-summary">
						<span class="cmnstr-category-title"><?= $label; ?></span>
					</summary>

					<div class="cmnstr-category-content"><?= \nl2br($body); ?></div>
				</details>

				<?php foreach ($categories as $category): ?>
					<?php if ($category['enabled'] && !empty($category['cookies'])): ?>
						<details class="cmnstr-category-detail" name="cmnstr-details">
							<summary class="cmnstr-category-summary">
								<span class="cmnstr-category-title"><?= $category['title'] ?></span>
								<span
									class="cmnstr-category-state"
									data-active="<?= $category['checked'] ? 'true' : 'false'; ?>">
									<?= __('Aktiv'); ?>
								</span>
							</summary>

							<div class="cmnstr-category-content">
								<div class="cmnstr-category-grid">
									<p class="cmnstr-category-description"><?= $category['description'] ?></p>

									<label class="cmnstr-field" for="cmnstr-<?= $category['key'] ?>">
										<span class="cmnstr-label"><?= __('Aktiv'); ?></span>
										<input
											type="checkbox"
											id="cmnstr-<?= $category['key'] ?>"
											name="cmnstr-<?= $category['key'] ?>"
											class="cmnstr-checkbox"
											<?php if ($category['key'] === 'essential'): ?>
											checked
											disabled
											<?php else: ?>
											<?php if ($category['checked'] ?? false): ?>checked<?php endif; ?>
											<?php endif; ?>
											aria-label="<?= $category['title'] ?>">
									</label>
								</div>

								<?php if ($category['cookies']): ?>
									<div class="cmnstr-table-wrap">
										<?= $module->renderCookieList($category['cookies']) ?>
									</div>
								<?php endif; ?>
							</div>
						</details>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</form>

		<footer class="cmnstr-footer">
			<button
				class="cmnstr-button"
				type="button"
				data-cmnstr-action="save">
				<?= $buttonConfirm; ?>
			</button>
			<button
				class="cmnstr-button"
				type="button"
				data-cmnstr-action="decline">
				<?= $buttonDecline; ?>
			</button>
			<button
				class="cmnstr-button"
				type="button"
				data-cmnstr-action="accept">
				<?= $buttonAccept; ?>
			</button>
		</footer>

		<?php if ($imprintPage->id || $privacyPage->id): ?>
			<nav class="cmnstr-nav">
				<?php if ($imprintPage->id): ?>
					<a href="<?= $imprintPage->url ?>"><?= $imprintPage->title ?></a>
				<?php endif; ?>
				<?php if ($privacyPage->id): ?>
					<a href="<?= $privacyPage->url ?>"><?= $privacyPage->title ?></a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>
	</dialog>
</section>
