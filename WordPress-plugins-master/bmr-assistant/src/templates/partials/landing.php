<?php global $post; ?>
<div class="assistant-landing" id="landing">
    <h2><?php _e('Мы поможем подобрать Вам лучшего букмекера!', 'bmr') ?></h2>
    <div class="use-default-ui">
        <?= apply_filters('the_content', $post->post_content) ?>
    </div>
    <a class="button-default" id="start-quiz" href="#product"><?php _e('Пройти тест', 'bmr') ?></a>
    <p class="help-the-rest"><?php _e('Помоги другим найти своего букмекера:', 'bmr') ?></p>
    <?php include get_template_directory() . '/_parts/share.php'; ?>
</div>