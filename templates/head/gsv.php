<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @var string $verificationString
 * @var Sanitizer $sanitizer
 */

?>

<!-- Google Site Verification -->
<meta name="google-site-verification" content="<?= $sanitizer->entities($verificationString); ?>">
