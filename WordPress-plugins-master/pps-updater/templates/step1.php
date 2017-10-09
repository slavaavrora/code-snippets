<?php
/**
 * @var string $subtitle
 * @var array  $bookmakers Array of bookmaker names
 */
?>

<div class="bookmaker-select">
    <h3><?= $subtitle ?></h3>
    <form action="<?= $_SERVER['REQUEST_URI'] ?>" method="post">
        <div class="row">
            <div class="col title"><label for="bookmaker"></label>Выберите букмекера</div>
            <div class="col">
                <select name="bookmaker" id="bookmaker">
                    <?php foreach ($bookmakers as $b) : ?>
                    <option value="<?= $b ?>"><?= $b ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <input type="hidden" name="step" value="1">
                <button type="submit">Получить данные</button>
            </div>
        </div>
    </form>
</div>