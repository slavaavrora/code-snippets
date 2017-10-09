<?php
global $post;
?>

<form id="feedbacks-form">
    <textarea name="comment" placeholder="<?php _e('Ваш отзыв', 'bmr') ?>"></textarea>
    <?php if (!$withoutRating) : ?>
    <div class="feedbacks-rating"><?php _e('Ваша оценка', 'bmr') ?>
        <span class="feedbacks-rating-stars" id="user-rating">
            <span class="stars"><i style="width: 0"></i></span><?php printf(__('%s из 5', 'bmr'), '<span class="num">0</span>') ?>
        </span>
    </div>
    <?php endif ?>
    <input type="hidden" name="rating" value="<?= $withoutRating ? -1 : 0 ?>">
    <input type="hidden" name="postid" value="<?= $post->ID ?>">
    <input type="hidden" name="type" value="<?= $type ?>">
    <button type="submit" class="feedbacks-button"><?php _e('Отправить', 'bmr') ?></button>
</form>

<div id="feedbacks-popup" data-type="success">
    <div class="feedbacks-popup">
        <div class="feedbacks-popup-title" data-success-title="<?php _e('Отзыв добавлен', 'bmr') ?>" data-error-title="<?php _e('Сообщение об ошибке', 'bmr') ?>">
            <i class="feedbacks-popup-close"></i>
        </div>
        <div class="feedbacks-popup-content"></div>
    </div>
</div>