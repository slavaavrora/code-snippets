<?php
/**
 * @var string $title         Page title
 * @var array  $errorMessages Error messages
 * @var string $content       Page content
 */
?>

<div class="pps-updater">
    <h2><?= $title ?></h2>

    <?php foreach ($errorMessages as $m) : ?>
    <div class="message <?= !empty($m['type']) ? $m['type'] : '' ?>"><?= $m['message'] ?></div>
    <?php endforeach ?>

    <div class="content"><?= $content ?></div>
</div>