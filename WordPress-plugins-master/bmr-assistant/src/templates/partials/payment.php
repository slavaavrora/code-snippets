<?php
    $supportedPayments = get_terms('supported_payments', [
        'hide_empty' => true,
        'order' => 'DESC',
        'orderby' => 'count',
        'acf_meta' => [
            'key' => 'term_popular',
            'value' => '1'
        ]
    ]);
?>
<div class="assistant-payment-content is-hidden" id="payment">
    <?php foreach($supportedPayments as $system): ?>
        <?php
            $img = get_field('category_icon', $system);
        ?>
        <div class="item">
            <div class="item-wrap">
                <input
                    type="checkbox"
                    name="payment[]"
                    id="payment_<?= $system->term_id ?>"
                    class="css-checkbox"
                    value="<?= $system->term_id ?>"
                />
                <label for="payment_<?= $system->term_id ?>" class="css-label-with-img">
                    <?php if($img): ?>
                    <div class="img-place needsclick"><img src="<?= $img ?>" alt=""/></div>
                    <?php endif; ?>
                    <span class="label-text needsclick"><?= $system->name ?></span>
                </label>
            </div>
        </div>
    <?php endforeach; ?>
</div>