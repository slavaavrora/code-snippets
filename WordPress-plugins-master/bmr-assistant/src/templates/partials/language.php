<?php
    $languages = get_terms('supported_languages', [
        'hide_empty' => true,
        'order' => 'ASC',
        'orderby' => 'name',
        'acf_meta' => [
            'key' => 'term_popular',
            'value' => '1'
        ]
    ]);
    $countries = get_terms('forbidden_countries', [
        'order' => 'ASC',
        'orderby' => 'name',
        'hide_empty' => true,
    ]);

    usort($countries, function($a, $b)
    {
        $priorityA = (int)get_field('term_priority', $a);
        $priorityB = (int)get_field('term_priority', $b);

        if ($priorityA === $priorityB) {
            return strcmp($a->name, $b->name);
        }

        return $priorityA < $priorityB ? 1 : -1;
        // -1 вверх, 1 вниз, 0 на месте
    });

    // Make Russia first in list
    if (get_current_blog_id() === BLOG_ID_BMR) {
        $russia = false;
        foreach ($countries as $key => $country) {
            if ($country->name === 'Россия') {
                $russia = clone $country;
                unset($countries[$key]);
                break;
            }
        }
        unset($country);
        $russia && array_unshift($countries, $russia);
    }
?>
<div class="assistant-language-content is-hidden" id="language">

    <?php if($languages): ?>
    <div class="language-cont">
        <div class="title"><?php _e('Выберите предпочитаемые языки:', 'bmr') ?></div>
        <?php foreach($languages as $lang): ?>
        <?php
            $img = get_field('category_icon', $lang);
        ?>
        <div class="item">
            <input
                type="checkbox"
                name="language[languages]"
                id="language_<?= $lang->term_id ?>"
                class="css-checkbox"
                value="<?= $lang->term_id ?>"
            />
            <label for="language_<?= $lang->term_id ?>" class="css-label">
                <?php if($img): ?>
                <img src="<?= $img ?>" alt=""/><?= $lang->name ?>
                <?php endif; ?>
            </label>
        </div>
        <?php endforeach ?>
    </div>
    <?php endif; ?>

    <?php if($countries): ?>
        <div class="country group">
            <div class="title">
                <?php _e('Выберите страну проживания:', 'bmr') ?>
            </div>

            <select name="language[country]" id="language-country">
                <option value="0" selected disabled><?php _e('Выберите страну', 'bmr') ?></option>
                <?php foreach($countries as $country): ?>
                    <?php $img = get_field('category_icon', $country); ?>
                    <option value="<?= $country->term_id ?>" data-flag="<?= $img ?>"><?= $country->name ?></option>
                <?php endforeach; ?>
            </select>
            <div class="text">
                <?php _e('Это поможет нам подобрать для вас лучшую контору, принимая во внимание законодательство страны, в которой вы проживаете.', 'bmr') ?>
            </div>

        </div>
    <?php endif; ?>
</div>