<?php

/**
 * @var array $breadCrumbs
 */

get_header();

global $post;

$allFeedbacks = self::getItemsNum($post->ID);
$feedbacksByPage = self::ITEMS_PER_PAGE;;
$totalPages = ceil($allFeedbacks / $feedbacksByPage);

$totalRating = $ratingCnt = 0;
for ($i = 1; $i < 6; $i++) {
    $tmp = self::getItemsNumByRating($post->ID, $i);
    $totalRating += $tmp * $i;
    $ratingCnt += $tmp;
}
$totalRating = $ratingCnt !== 0 ? ceil($totalRating * 10 / $ratingCnt) / 10 : 0;
?>

<div class="content inner" id="all-feedbacks-page">
    <?php get_template_part('sidebars/menu', 'left') ?>

    <div class="content-middle">

        <section class="header">
            <h2><?= $post->post_title ?></h2>

            <ul class="breadcrumbs" itemprop="breadcrumb"><span prefix="v: http://rdf.data-vocabulary.org/#">
                <?php foreach ($breadCrumbs as $title => $link) : ?>
                <li typeof="v:Breadcrumb">
                    <?= empty($link)
                        ? '<span property="v:title">' . $title . '</span>'
                        : '<a href="' . $link . '" title="' . $title . '" rel="v:url" property="v:title">' . $title . '</a>'
                    ?>
                </li>
                <?php endforeach ?>
            </span></ul>
        </section>

        <section class="all-feedbacks-header">
            <p class="block-title"><?php printf(__('Отзывы пользователей %d', 'bmr'), $allFeedbacks) ?></p>
            <div class="container">
                <div class="average"><?php _e('Средняя оценка:', 'bmr') ?></div>
                <div class="rating">
                    <span class="feedbacks-rating-stars" id="user-rating">
                        <span class="stars"><i style="width: <?= $totalRating * 20 ?>%"></i></span>
                        <?php printf(__('%s из 5', 'bmr'), '<span class="num">' . $totalRating . '</span>') ?>
                    </span>
                </div>
                <div class="btns">
                    <a href="#" class="review-btn blue" id="all-feedbacks-open-add-form"><?php _e('Оставить отзыв', 'bmr') ?></a>
                </div>
            </div>
        </section>

        <?php if (is_user_logged_in()) : ?>
        <section class="all-feedbacks-comment-form disabled" id="all-feedbacks-add-form">
            <p class="block-title"><?php _e('Оставьте отзыв', 'bmr') ?></p>
            <div class="container"><?php self::getForm('reviews') ?></div>
        </section>
        <?php endif ?>

        <section class="all-feedbacks-list" id="all-feedbacks-list">
            <?php self::getItems($post->ID, 1, $feedbacksByPage) ?>
        </section>

        <button class="more-button <?= $totalPages > 1 ? '' : 'disabled' ?>"
                id="all-feedbacks-more-btn"
                data-current-page="1"
                data-total-pages="<?= $totalPages ?>"
                data-postid="<?= $post->ID ?>">
            <span><?php _e('Еще Отзывов', 'bmr') ?></span>
        </button>

    </div>
</div>

<?php
get_footer();
?>