<?php
/**
 * @var array $args
 * @var bool  $withoutRating
*/

$userID = get_current_user_id();
?>

<div id="feedbacks-list">
    <?php foreach ($feedbacks as $item) :
        $avatar = self::getUserAvatarUrl($item->post_author);
    ?>
    <div class="single">
        <div class="head">
            <?php if ($avatar) : ?>
            <div class="avatar" style="background-image: url(<?= $avatar ?>)"></div>
            <?php else : ?>
            <div class="avatar" data-initials="<?= self::getUserInitials($item->post_author) ?>"></div>
            <?php endif ?>
            <div class="name"><?= self::getUserName($item->post_author) ?></div>
            <?php if ($args['rating']) :
                $rating = get_post_meta( $item->ID, 'feedbacks_rating', true) ?: 0;
            ?>
            <span class="feedbacks-rating-stars">
                <span class="stars"><i style="width: <?= $rating * 20 ?>%"></i></span><?php printf(__('%s из 5', 'bmr'), '<span class="num">' . $rating . '</span>') ?>
            </span>
            <?php endif ?>
        </div>
        <div class="content">
            <?php if ($args['title']) : ?>
            <p class="title"><?= $item->post_title ?></p>
            <?php endif ?>
            <p class="text"><?= $item->post_content ?></p>
        </div>
        <div class="bottom">
            <div class="date"><?=
                /* translators: 10.09.2015 в 20:45 */
                date(sprintf(__('%s в %s', 'bmr'), 'd.m.Y', 'H:i'), strtotime($item->post_date))
            ?></div>
            <div class="likes">
                <?php $feedbackUsers = unserialize(get_post_meta($item->ID, '_feedback_users', true)) ?: []; ?>
                <a href="#" class="like <?= isset($feedbackUsers[$userID]) && $feedbackUsers[$userID] === 1 ? 'active' : '' ?>" data-id="<?= $item->ID ?>">
                    <?= get_post_meta($item->ID, 'feedbacks_likes', true) ?: 0 ?>
                </a>
                <a href="#" class="dislike <?= isset($feedbackUsers[$userID]) && $feedbackUsers[$userID] === -1 ? 'active' : '' ?>" data-id="<?= $item->ID ?>">
                    <?= get_post_meta($item->ID, 'feedbacks_dislikes', true) ?: 0 ?>
                </a>
            </div>
        </div>
    </div>
    <?php endforeach ?>
</div>