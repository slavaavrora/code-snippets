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
            <div class="col title"><label for="type">Выберите тип заведения</label></div>
            <div class="col">
                <select name="type" id="type">
                    <?php foreach ($types as $id => $title) : ?>
                    <option value="<?= $id ?>"><?= $title ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col title"><label for="services">Выберите услуги</label></div>
            <div class="col">
                <select name="services[]" id="services" multiple required>
                    <?php foreach ($services as $id => $title) : ?>
                    <option value="<?= $id ?>"><?= $title ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col title"><label for="services">Выберите страну</label></div>
            <div class="col">
                <select name="country" id="services">
                    <?php foreach ($country as $id => $title) : ?>
                    <option value="<?= $id ?>"><?= $title ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col">Выбранные тип заведения и услуги будут утсановлены в новых ППС.<br>Выбранная страна будет установлена у новых добавленых городов.</div>
        </div>
        <div class="row">
            <div class="col">
                <input type="hidden" name="step" value="2">
                <button type="submit">Обновить ППС</button>
            </div>
        </div>
    </form>
</div>


<?php
$itemsByCity = [];
foreach ($data as $id => $item) {
    !isset($itemsByCity[$item['city']]) && $itemsByCity[$item['city']] = [];
    $itemsByCity[$item['city']][] = $id;
}

ksort($itemsByCity);

foreach ($itemsByCity as $city => $ids) : ?>
<div class="items-list">
    <h3><?= $city ?></h3>
    <div class="row titles">
        <div class="col">Адрес</div>
        <div class="col">Телефон</div>
        <div class="col">Сайт</div>
        <div class="col">E-mail</div>
        <div class="col">Координаты</div>
        <div class="col">Расписание</div>
    </div>
    <?php foreach ($ids as $id) :
        $schedule = [];
        $dayOfWeekTitles = [1 => 'Пн.', 'Вт.', 'Ср.', 'Чт.', 'Пт.', 'Сб.', 'Вс.'];

        foreach ($data[$id]['schedule'] as $s) {
            $time = $s['time_start'] . '-' . $s['time_end'];

            $tmp = $dayOfWeekTitles[min((array) $s['day_of_week'])];
            $cnt = count($s['day_of_week']);
            $cnt > 1 && ($tmp .= '-' . $dayOfWeekTitles[max((array) $s['day_of_week'])]);
            $tmp .= ': ' . $time;

            $schedule[] = $tmp;
        }
    ?>
    <div class="row item">
        <div class="col"><?= $data[$id]['address'] ?></div>
        <div class="col"><?= $data[$id]['phone'] ?></div>
        <div class="col"><?= $data[$id]['site'] ?></div>
        <div class="col"><?= $data[$id]['email'] ?></div>
        <div class="col"><?= 'lat: ', $data[$id]['coord']['lat'], ' lng: ', $data[$id]['coord']['lng'] ?></div>
        <div class="col"><?= implode(', ', $schedule) ?></div>
    </div>
    <?php endforeach ?>
</div>
<?php endforeach ?>