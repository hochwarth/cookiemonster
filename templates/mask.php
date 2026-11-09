<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string $category
 * @var bool $matchAny
 * @var string $maskId
 * @var string $serviceNames HTML-escaped names of the blocked services
 * @var string $prompt
 * @var string $info
 * @var string $buttonEdit
 * @var string $buttonRequired
 * @var string $buttonAll
 * @var string $buttonOnce
 * @var Page|NullPage $privacyPage
 * @var string $consentUrl
 * @var string $redirectUrl
 * @var string $settingsUrl
 * @var int $languageId
 * @var Sanitizer $sanitizer
 */

?>

<form
	method="post"
	action="<?= $sanitizer->entities($consentUrl); ?>"
	class="cmnstr-content-mask"
	role="group"
	aria-labelledby="<?= $maskId; ?>"
	data-cmnstr-category="<?= $sanitizer->entities($category); ?>" <?= $matchAny ? ' data-cmnstr-match="any"' : ''; ?>>
	<input type="hidden" name="cmnstr-redirect" value="<?= $sanitizer->entities($redirectUrl . '#' . $maskId); ?>">
	<?php if ($languageId): ?>
		<input type="hidden" name="cmnstr-lang" value="<?= $languageId; ?>">
	<?php endif; ?>

	<div class="cmnstr-mask-body">
		<p id="<?= $maskId; ?>" class="cmnstr-mask-text"><?= $prompt ?></p>
		<span id="<?= $maskId; ?>-service" hidden><?= $serviceNames; ?></span>
	</div>

	<div class="cmnstr-mask-actions">
		<div class="cmnstr-button-list">
			<?php if ($category !== CookieMonster::ALL_SERVICES): ?>
				<button
					type="submit"
					id="<?= $maskId; ?>-grant"
					class="cmnstr-button"
					aria-labelledby="<?= $maskId; ?>-grant <?= $maskId; ?>-service"
					name="cmnstr-grant"
					value="<?= $sanitizer->entities($category); ?>">
					<?= $buttonRequired; ?>
				</button>
			<?php endif; ?>

			<?php if ($buttonOnce !== ''): ?>
				<button
					type="submit"
					id="<?= $maskId; ?>-once"
					class="cmnstr-button"
					aria-labelledby="<?= $maskId; ?>-once <?= $maskId; ?>-service"
					name="cmnstr-once"
					value="<?= $sanitizer->entities($category); ?>">
					<?= $buttonOnce; ?>
				</button>
			<?php endif; ?>

			<button
				type="submit"
				class="cmnstr-button cmnstr-button-accept"
				name="cmnstr-all"
				value="1">
				<?= $buttonAll; ?>
			</button>
		</div>

		<p class="cmnstr-mask-settings">
			<a
				href="<?= $sanitizer->entities($settingsUrl); ?>"
				rel="nofollow"
				aria-haspopup="dialog"
				class="cmnstr-link cmnstr-button-edit">
				<?= $buttonEdit; ?>
			</a>
		</p>
	</div>

	<?php if ($privacyPage->id): ?>
		<p class="cmnstr-mask-footer">
			<?= $info; ?>
			<a href="<?= $privacyPage->url ?>" class="cmnstr-link"><?= $privacyPage->title ?></a>
		</p>
	<?php endif; ?>

</form>
