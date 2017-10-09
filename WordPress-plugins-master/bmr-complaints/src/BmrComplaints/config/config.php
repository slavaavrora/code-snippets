<?php
return array(
    'slug'       => 'bmr',
    'actions'    => array(
        'submit' => 'bmr_submit',
        'nonce'  => 'bmr_compaint',
    ),
    'post_type'  => 'bmr_complaint',
    'taxonomies' => array(
        'bmr_complaint_type'   => array(
            'complaint-type-other'         => __('Другое', 'bmr'),
            'complaint-type-payout'        => __('Задержка выплаты', 'bmr'),
            'complaint-type-bonus'         => __('Незачисленный бонус', 'bmr'),
            'complaint-type-wager-dispute' => __('Неправильно рассчитанная ставка', 'bmr'),
            'complaint-type-support-issue' => __('Служба поддержки', 'bmr'),
        ),
        'bmr_complaint_status' => array(
            'complaint-status-groundless' => __('Безосновательная', 'bmr'),
            'complaint-status-duplicate'  => __('Дубликат', 'bmr'),
            'complaint-status-refused'    => __('Не удовлетворена', 'bmr'),
            'complaint-status-processing' => __('Обрабатывается', 'bmr'),
            'complaint-status-ignored'    => __('Проигнорирована', 'bmr'),
            'complaint-status-solved'     => __('Удовлетворена', 'bmr'),
        )
    )
);
