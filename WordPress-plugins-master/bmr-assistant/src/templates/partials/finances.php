<?php
//    $currencies = get_terms('supported_currencies', [
//        'hide_empty' => true,
//        'acf_meta' => [
//            'order' => 'ASC',
//            'key' => 'term_popular',
//            'value' => '1'
//        ]
//    ]);

    global $wpdb;
    $currencies = $wpdb->get_results(
        "SELECT t.*, tt.*
        FROM $wpdb->terms t
        INNER JOIN $wpdb->term_taxonomy tt
        ON tt.term_id = t.term_id
        INNER JOIN $wpdb->term_relationships tr
        ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN $wpdb->options o1
        ON o1.option_name LIKE CONCAT_WS('_', tt.taxonomy, tt.term_id, 'term_popular') AND o1.option_value = 1
        INNER JOIN $wpdb->options o2
        ON o2.option_name LIKE CONCAT_WS('_', tt.taxonomy, tt.term_id, 'currency_name')
        WHERE tt.count > 0 AND tt.taxonomy = 'supported_currencies'
        GROUP BY t.term_id
        ORDER BY o2.option_value ASC"
    );
    $currencies = !is_array($currencies) ? [] : $currencies;

    $locale = get_locale();
    $defaultCurrencyMap = [
        'en_US' => 'USD',
        'uk'    => 'UAH',
        'hy'    => 'AMD',
    ];
    $defaultCurrency = isset($defaultCurrencyMap[$locale]) ? $defaultCurrencyMap[$locale] : 'RUB';
?>
<div class="assistant-finances-content is-hidden" id="finances">
    <div class="top_cont">
        <div class="container">
            <div class="title"><?php _e('Средняя сумма вашей ставки', 'bmr') ?></div>
            <div class="slide">
                <div class="slider"></div>
                <input
                    type="hidden"
                    name="finances[avg]"
                    id="finances-avg"
                    data-default-value="15000"
                    value="15000"
                />
            </div>

            <div class="slider" id="avg-slider"></div>

            <div class="text">
                <span>1 <i class="currency-code"><?= $defaultCurrency ?></i></span>
                <span>50000 <i  class="currency-code"><?= $defaultCurrency ?></i>+ </span>
            </div>
        </div>
        <div class="question">
            <div class="title"><?php _e('Вам урезали максимумы?', 'bmr') ?></div>
            <div class="cont">
                <div class="scroll-bar">
                    <div class="first point" data-type="no"></div>
                    <div class="second point" data-type="yes"></div>
                    <div class="scroller transition"></div>
                </div>
                <div class="name">
                    <div class="no point active" data-type="no"><?php _e('Нет', 'bmr') ?></div>
                    <div class="yes point" data-type="yes"><?php _e('Да', 'bmr') ?></div>
                    <div class="clear"></div>
                </div>
                <input
                    type="hidden"
                    name="finances[highs]"
                    id="finances-highs"
                    data-default-value="no"
                    value="no"
                >
            </div>
            <div class="clear"></div>
        </div>
    </div>
    <?php if($currencies): ?>
    <div class="bottom_cont">
        <div class="title"><?php _e('Предпочитаемая валюта', 'bmr') ?></div>
        <div class="currency-container">
            <?php foreach($currencies as $term): ?>
                <?php
                    $name = get_field('currency_name', $term);
                    $name = $name ? $name : $term->name;
                ?>
                <div class="checkbox-group">
                    <input
                        type="radio"
                        name="finances[currency]"
                        id="currency_<?= $term->term_id ?>"
                        class="css-radio"
                        value="<?= $term->term_id ?>"
                        data-term-name="<?= $term->name ?>"
                        <?= checked($defaultCurrency, $term->name) ?>
                        />
                    <label for="currency_<?= $term->term_id ?>" class="css-label-radio"><?= $name ?></label>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>