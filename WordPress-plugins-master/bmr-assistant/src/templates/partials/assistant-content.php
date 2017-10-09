<?php
    global $quizMenu;
    $total = count($quizMenu);
    $cnt = 0;
?>
<section class="container-assistant-menu">
    <ul id="assistant-breadcrumbs">
        <?php foreach($quizMenu as $id => $menuItem): $cnt++; ?>
            <li
                data-partial-slug="<?= $id ?>"
                data-partial-heading="<?= $menuItem['heading'] ?>"
                class="<?= $id === 'results' ? 'is-hidden' : '' ?>"
            >
                <a href="#<?= $id ?>">
                    <div><span class="menu-position"><?= $cnt ?></span></div>
                    <span class="menu-title"><?= $menuItem['arrow_title'] ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<div class="assistant-header" id="assistant-controls">
    <div class="prev is-disabled">
        <i class="icon-left"></i>
        <span class="arrow-title"></span>
    </div>
    <div class="middle">
        <h2 class="quiz-heading"><?php _e('Загрузка...', 'bmr') ?></h2>
    </div>
    <div class="next">
        <i class="icon-right-01"></i>
        <span class="arrow-title"></span>
    </div>
</div>

<section id="assistant-content">
    <div class="bmr-loading-spinner"></div>
    <form id="quiz-form">
        <?php \Bmr\Assistant\Base::getTemplatePart('product');  ?>
        <?php \Bmr\Assistant\Base::getTemplatePart('devices');  ?>
        <?php \Bmr\Assistant\Base::getTemplatePart('player');   ?>
        <?php \Bmr\Assistant\Base::getTemplatePart('time');     ?>
        <?php \Bmr\Assistant\Base::getTemplatePart('finances'); ?>
        <?php \Bmr\Assistant\Base::getTemplatePart('payment');  ?>
        <?php \Bmr\Assistant\Base::getTemplatePart('language'); ?>
        <?php \Bmr\Assistant\Base::getTemplatePart('criteria'); ?>
        <div id="js-ajax-content-placeholder">
        <?php if(isset($_GET['results'])): ?>
            <?php \Bmr\Assistant\Base::getTemplatePart('results', 'new'); ?>
        <?php else: ?>
            <?php \Bmr\Assistant\Base::getTemplatePart('landing'); ?>
        <?php endif; ?>
        </div>
    </form>
    <?php $cnt = 1; foreach($quizMenu as $id => $menuItem): ?>
        <?php
        $linkText = $cnt !== $total
                  ? __('Далее: ', 'bmr') . $menuItem['arrow_title']
                  : __('К Результатам!', 'bmr');
        ?>
        <a href="#<?= $id ?>" class="next-btn is-hidden"><?= $linkText ?></a>
    <?php $cnt++; endforeach; ?>
</section>

            