<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string $prompt
 * @var string $buttonEdit
 * @var string $buttonAccept
 * @var Page|NullPage $privacyPage
 */

?>

<div class="cmnstr-content-mask" role="region">
	<p><?= $prompt ?></p>

	<div class="cmnstr-button-list">
		<button
			type="button"
			class="cmnstr-button"
			data-cmnstr-action="edit">
			<?= $buttonEdit; ?>
		</button>

		<button
			type="button"
			class="cmnstr-button"
			data-cmnstr-action="accept">
			<?= $buttonAccept; ?>
		</button>
	</div>

	<?php if ($privacyPage->id): ?>
		<p class="cmnstr-small">
			<?= __('Weitere Informationen finden Sie hier:'); ?>
			<a href="<?= $privacyPage->url ?>" class="cmnstr-link"><?= $privacyPage->title ?></a>
		</p>
	<?php endif; ?>
</div>
