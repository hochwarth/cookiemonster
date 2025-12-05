<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string $html
 * @var string $category
 * @var string $prompt
 * @var string $buttonEdit
 */

?>

<div
	class="cmnstr-content-mask"
	data-content='<?= \json_encode($html) ?>'
	data-category="<?= $category ?>"
	role="region"
	aria-live="polite">
	<p><?= $prompt ?></p>

	<button
		type="button"
		class="cmnstr-button"
		data-cmnstr-action="edit">
		<?= $buttonEdit; ?>
	</button>
</div>
