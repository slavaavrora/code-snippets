<?php
/**
 * @var string $subtitle
 * @var array  $types    Array of pps types (id => title)
 * @var array  $serviecs Array of services (id => title)
 * @var array  $data     Bookmaker data
 */
?>

<div>
    <h3><?= $subtitle ?></h3>
    <form action="<?= $_SERVER['REQUEST_URI'] ?>" method="post">
        <div class="row">
            <div class="col">123</div>
        </div>
        <div class="row">
            <div class="col">
                <input type="hidden" name="step" value="3">
                <button type="submit">Обновить ППС</button>
            </div>
        </div>
    </form>
</div>