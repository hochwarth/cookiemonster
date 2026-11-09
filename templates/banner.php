<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var CookieMonster $cmnstr
 * @var string $title
 * @var string $label
 * @var string $body
 * @var string $details
 * @var bool $open
 * @var bool $settingsOpen
 * @var string $buttonEdit
 * @var string $buttonConfirm
 * @var string $buttonDecline
 * @var string $buttonAccept
 * @var Page|NullPage $imprintPage
 * @var Page|NullPage $privacyPage
 * @var array $categories
 * @var string $signalNotice
 * @var string $consentUrl
 * @var string $redirectUrl
 * @var string $settingsUrl
 * @var int $languageId
 * @var string $status
 * @var Pages $pages
 * @var Sanitizer $sanitizer
 */

// Hidden fields
$formFields = '<input type="hidden" name="cmnstr-redirect" value="' . $sanitizer->entities($redirectUrl) . '">';
if ($languageId) {
    $formFields .= '<input type="hidden" name="cmnstr-lang" value="' . (int) $languageId . '">';
}

$renderLegalNav = static function () use ($cmnstr, $imprintPage, $privacyPage): void {
?>
	<?php if ($imprintPage->id || $privacyPage->id): ?>
		<nav class="cmnstr-footer" aria-label="<?= $cmnstr->text('legal_nav'); ?>">
			<?php if ($imprintPage->id): ?>
				<a href="<?= $imprintPage->url ?>"><?= $imprintPage->title ?></a>
			<?php endif; ?>
			<?php if ($privacyPage->id): ?>
				<a href="<?= $privacyPage->url ?>"><?= $privacyPage->title ?></a>
			<?php endif; ?>
		</nav>
	<?php endif; ?>
<?php
};

?>

<div id="cmnstr">
	<p id="cmnstr-status" class="cmnstr-sr-only" role="status" data-message="<?= $sanitizer->entities($status); ?>"></p>
	<?php if ($status !== ''): ?>
		<noscript>
			<p class="cmnstr-notice"><?= $sanitizer->entities($status); ?></p>
		</noscript>
	<?php endif; ?>

	<dialog
		<?= $open ? 'open' : ''; ?>
		id="cmnstr-alert"
		class="cmnstr"
		aria-label="<?= $sanitizer->entities(\strip_tags($title)); ?>"
		aria-describedby="cmnstr-alert-content">
		<form
			method="post"
			action="<?= $sanitizer->entities($consentUrl); ?>"
			aria-label="<?= $cmnstr->text('dialog_label'); ?>">
			<?= $formFields; ?>
			<section id="cmnstr-alert-content" class="cmnstr-content">
				<?= \nl2br($body); ?>
			</section>

			<section class="cmnstr-actions">
				<a
					href="<?= $sanitizer->entities($settingsUrl); ?>"
					rel="nofollow"
					aria-haspopup="dialog"
					class="cmnstr-button cmnstr-button-edit">
					<?= $buttonEdit; ?>
				</a>
				<button
					type="submit"
					class="cmnstr-button cmnstr-button-decline"
					name="cmnstr-all"
					value="0">
					<?= $buttonDecline; ?>
				</button>
				<button
					type="submit"
					class="cmnstr-button cmnstr-button-accept"
					name="cmnstr-all"
					value="1">
					<?= $buttonAccept; ?>
				</button>
			</section>

			<?php $renderLegalNav(); ?>
		</form>
	</dialog>

	<aside aria-label="<?= $sanitizer->entities(\strip_tags($buttonEdit)); ?>">
		<a
			href="<?= $sanitizer->entities($settingsUrl); ?>"
			rel="nofollow"
			aria-haspopup="dialog"
			class="cmnstr-button-hover cmnstr-button-edit"
			title="<?= $sanitizer->entities(\strip_tags($buttonEdit)); ?>"
			aria-label="<?= $sanitizer->entities(\strip_tags($buttonEdit)); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" viewBox="0 0 640 640">
				<path d="M322 92q-2-9-11-11-34-4-64 11l-74 40q-30 16-46 47l-36 76q-15 30-9 65l14 82q7 34 31 58l60 59q25 23 59 28l83 12q34 4 64-11l74-40q31-16 46-47l36-76q15-30 9-64-2-9-10-11c-52-8-93-47-105-97q-4-12-15-15c-55-8-98-52-107-106zm-50 116a32 32 0 1 1 0 64 32 32 0 0 1 0-64m-64 192a32 32 0 1 1 64 0 32 32 0 0 1-64 0m224-64a32 32 0 1 1 0 64 32 32 0 0 1 0-64" />
			</svg>
		</a>
	</aside>

	<dialog
		<?= $settingsOpen ? 'open' : ''; ?>
		id="cmnstr-dialog"
		class="cmnstr"
		aria-labelledby="cmnstr-title">
		<form
			method="post"
			action="<?= $sanitizer->entities($consentUrl); ?>"
			class="cmnstr-form" id="cmnstr-form">
			<?= $formFields; ?>
			<div class="cmnstr-header">
				<h2 id="cmnstr-title" class="cmnstr-title"><?= $title ?></h2>

				<button
					formmethod="dialog"
					type="submit"
					class="cmnstr-close-button"
					aria-label="<?= $cmnstr->text('close'); ?>">
					<span aria-hidden="true">×</span>
				</button>
			</div>

			<section class="cmnstr-categories">
				<details class="cmnstr-category-details" name="cmnstr-details" open>
					<summary class="cmnstr-category-summary">
						<span class="cmnstr-category-title"><?= $label; ?></span>
					</summary>

					<div class="cmnstr-category-content">
						<p class="cmnstr-category-description"><?= \nl2br($details); ?></p>

						<?php if ($signalNotice): ?>
							<p class="cmnstr-signal" role="note"><?= $sanitizer->entities($signalNotice); ?></p>
						<?php endif; ?>
					</div>
				</details>

				<?php foreach ($categories as $category): ?>
					<?php if ($category['enabled'] && !empty($category['cookies'])): ?>
						<details class="cmnstr-category-details" name="cmnstr-details">
							<summary class="cmnstr-category-summary">
								<span id="cmnstr-<?= $category['key'] ?>-title" class="cmnstr-category-title"><?= $category['title'] ?></span>
								<span
									class="cmnstr-category-state"
									data-active="<?= $cmnstr->isCategoryActive($category['key']) ? 'true' : 'false'; ?>">
									<?= $cmnstr->text('active'); ?>
									<span class="cmnstr-pending cmnstr-sr-only"><?= $cmnstr->text('pending'); ?></span>
								</span>
							</summary>

							<div class="cmnstr-category-content">
								<div class="cmnstr-grid">
									<p id="cmnstr-<?= $category['key'] ?>-description" class="cmnstr-category-description"><?= $category['description'] ?></p>

									<label class="cmnstr-field" for="cmnstr-<?= $category['key'] ?>">
										<span id="cmnstr-<?= $category['key'] ?>-label" class="cmnstr-label"><?= $cmnstr->text('active'); ?></span>
										<span id="cmnstr-<?= $category['key'] ?>-scope" hidden><?= $cmnstr->text('scope_all'); ?></span>
										<input
											type="checkbox"
											id="cmnstr-<?= $category['key'] ?>"
											class="cmnstr-checkbox cmnstr-category-checkbox"
											<?php if ($category['key'] === 'essential'): ?>
											checked
											disabled
											<?php elseif ($category['checked'] ?? false): ?>
											checked
											<?php endif; ?>
											aria-labelledby="cmnstr-<?= $category['key'] ?>-title cmnstr-<?= $category['key'] ?>-label cmnstr-<?= $category['key'] ?>-scope"
											aria-describedby="cmnstr-<?= $category['key'] ?>-description"
											hidden>
									</label>
								</div>

								<?php if ($category['cookies']): ?>
									<div class="cmnstr-data-wrap">
										<?= $cmnstr->renderCookieList($category, true) ?>
									</div>
								<?php endif; ?>
							</div>
						</details>
					<?php endif; ?>
				<?php endforeach; ?>
			</section>

			<section class="cmnstr-actions">
				<button
					type="submit"
					class="cmnstr-button cmnstr-button-save">
					<?= $buttonConfirm; ?>
				</button>
				<button
					type="submit"
					class="cmnstr-button cmnstr-button-decline"
					name="cmnstr-all"
					value="0">
					<?= $buttonDecline; ?>
				</button>
				<button
					type="submit"
					class="cmnstr-button cmnstr-button-accept"
					name="cmnstr-all"
					value="1">
					<?= $buttonAccept; ?>
				</button>
			</section>

			<?php $renderLegalNav(); ?>
		</form>
	</dialog>
</div>
