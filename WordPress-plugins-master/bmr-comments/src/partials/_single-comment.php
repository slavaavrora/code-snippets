<?php
    $GLOBALS['comment'] = $comment;
    $currentUserId      = get_current_user_id();
    $commentUser        = \Bmr\Comments\Helper::getUserInfoByComment($comment);
    $commentRespondent  = false;
    $status             = '';
    $authorLink         = false;
    $respondentLink     = false;

    if ($comment->comment_parent) {
        $parent = get_comment($comment->comment_parent);
        $commentRespondent = \Bmr\Comments\Helper::getUserInfoByComment($parent);
        $commentRespondent->ID && $respondentLink = get_author_posts_url($commentRespondent->ID);
    }

    $commentClasses = ['bmr-comment'];

    !empty($args['has_children']) && ($commentClasses[] = 'parent');

    if ($comment->comment_approved == 0) {
        $commentClasses[] = 'is-not-approved';
        $status = 'not-approved';
    }

    if ($isPinned = (bool)get_comment_meta($comment->comment_ID, 'pinned', true)) {
        $commentClasses[] = 'is-pinned';
        $status = 'pined';
    }

    // Attachments
    $attachments = get_comment_meta($comment->comment_ID, 'attachments', true);
    $attachments = !is_array($attachments) ? [] : $attachments;

    // Likes
    $likes = get_comment_meta($comment->comment_ID, 'likes', true);
    $like  = isset($likes[$currentUserId]) ? $likes[$currentUserId] : 0;
    $likes = $likes ? array_count_values($likes) : [1 => 0, -1 => 0];

    $userCanAnswer = is_user_logged_in() && $currentUserId != $commentUser->ID;
    $noId = isset($args['no_id']) && $args['no_id'] === true;

    $commentUser->ID && $authorLink = get_author_posts_url($commentUser->ID);
?>
<li>
<div
    <?= $noId ? '' : "id=\"comment-{$comment->comment_ID}\"" ?>
    <?php comment_class($commentClasses) ?>
    data-id="<?= $comment->comment_ID ?>"
    data-parent="<?= $comment->comment_parent ?>"
    data-status="<?= $status ?>"
>
    <div class="comment-avatar">
        <?php if ($commentUser->avatar && !$commentUser->hasDefaultAvatar): ?>
            <img
                data-role="user-avatar"
                src="<?= $commentUser->avatar ?>"
                alt="<?php _e('Аватар', 'bmr') ?>"
            />
        <?php else: ?>
            <span class="comment-user-initials"><?= $commentUser->initials ?></span>
        <?php endif ?>
    </div>
    <div class="comment-body">
        <div class="comment-head">
            <div class="interlocutors">
                <?php
                $author = sprintf('<span class="from">%s</span>', $commentUser->name);
                $authorLink && ($author = sprintf('<a href="%s" target="_blank">%s</a>', $authorLink, $author));

                $respondent = $commentRespondent
                            ? sprintf('<span class="to">%s</span>', $commentRespondent->name)
                            : '';
                $respondent
                    && $respondentLink
                    && ($respondent = sprintf('<a href="%s" target="_blank">%s</a>', $respondentLink, $respondent));
                ?>
                <?= $author ?>
                <?php  if ($commentUser->isModerator): ?>
                <i class="role"><?php _e('Модератор', 'bmr') ?></i>
                <?php endif; ?>
                <?php if ($commentRespondent): ?>
                <?= $respondent ?>
                <?php endif; ?>
                <time>
                    <?php
                        printf(
                            __('%s назад', 'bmr'),
                            human_time_diff(get_comment_date('U'), current_time('timestamp')
                        ));
                    ?>
                </time>
            </div>
            <div class="menu menu-top">
                <i class="toggle icon-minus"></i>
                <?php if (current_user_can('manage_options')): ?>
                    <i class="moderation icon-arrow-down"></i>
                <?php elseif ($userCanAnswer): ?>
                    <i class="inappropriate icon-forbidden" data-tooltip="<?php _e("Пометить как неуместное", 'bmr') ?>"></i>
                <?php endif ?>
                <i class="not-approved icon-eye" data-tooltip="<?php _e("На модерации", 'bmr') ?>"></i>
            </div>
        </div>
        <div class="comment-content">
            <?php comment_text(); ?>
            <?php if ($attachments): ?>
            <p class="comment-attachments">
            <?php
                foreach ($attachments as $attachId) {
                    $url = wp_get_attachment_url($attachId);
                    echo '<img src="' . $url . '">' . "\n";
                }
            ?>
            </p>
            <?php endif; ?>
        </div>
        <div class="comment-meta">
            <div class="comment-likes">
                <a href="#" class="like icon-ilike <?= $like == 1 ? 'active' : '' ?>">
                    <i class="likes-count"><?= isset($likes[1]) ? $likes[1] : 0 ?></i>
                </a>
                <a href="#" class="dislike icon-idontlike <?= $like == -1 ? 'active' : '' ?>">
                    <i class="likes-count"><?= isset($likes[-1]) ? $likes[-1] : 0 ?></i>
                </a>
            </div>
            <div class="menu menu-bottom" role="menubar">
                <?php if ($userCanAnswer): ?>
                <a href="#" role="menuitem" class="menu-item comment-answer"><?php _e('Ответить', 'bmr') ?></a>
                <?php endif; ?>
                <div class="comment-share-group">
                    <a href="#" role="menuitem" class="menu-item comment-share"><?php _e('Поделиться', 'bmr') ?></a>
                    <div class="comment-socials">
                        <a href="<?= Base\Helpers\Socials::shareLink('vk') ?>" data-social="vk" rel="nofollow"></a>
                        <a href="<?= Base\Helpers\Socials::shareLink('fb') ?>" data-social="fb" rel="nofollow"></a>
                        <a href="<?= Base\Helpers\Socials::shareLink('tw') ?>" data-social="tw" rel="nofollow"></a>
                        <a href="#comment-<?= $comment->comment_ID ?>" class='comment-link'></a>
                    </div>
                </div>
            </div>
        </div>
        <!-- < comment-meta -->
    </div>
</div>
