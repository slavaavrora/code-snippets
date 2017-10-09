<?php
    $GLOBALS['comment'] = $comment;
    $classes   = ['comment'];
    $status    = wp_get_comment_status($comment->comment_ID);
    $post      = get_post($comment->comment_post_ID);
    $commentUser = \Bmr\Comments\Helper::getUserInfoByComment($comment);
    $classes[] = 'is-' . $status;

    $commentUri = esc_url( get_comment_link( $comment->comment_ID ));
    $commentDate =
        /* translators: comment date в comment time */
        sprintf( __('%s в %s'),
            /* translators: comment date format. See http://php.net/date */
            get_comment_date( __( 'Y/m/d' ) ),
            get_comment_date( get_option( 'time_format' ) )
    );
    $userLink =  esc_attr(get_edit_user_link($commentUser->ID))
?>

<li
    id="comment-<?= $comment->comment_ID ?>"
    class="<?= implode(' ', $classes) ?>"
    data-id="<?= $comment->comment_ID ?>"
    data-parent="<?= $comment->comment_parent ?>"
    data-post-id="<?= $comment->comment_post_ID ?>"
>
    <div class="comment-id">
        <input type="checkbox" name="comments[]" value="<?= $comment->comment_ID ?>" />
    </div>
    <div class="comment-outer">
        <?php if ($post): ?>
        <h4 class="comment-post-title">
            <a href="<?= $commentUri ?>">
                <?= get_the_title($post) ?>
            </a>
        </h4>
        <?php endif; ?>
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
        <div class="comment-inner">
            <div class="comment-head">
                <div class="comment-author">
                    <?php if ($commentUser->ID): ?>
                    <a href="<?= $userLink ?>" class="comment-name"><?= $commentUser->name ?></a>
                    <?php else: ?>
                    <span class="comment-name"><?= $commentUser->name ?></span>
                    <?php endif; ?>
                    <span class="bullet" aria-hidden="true">•</span>
                    <a href="<?= $commentUri ?>">
                        <time title="<?= $commentDate ?>">
                        <?php
                            printf(
                                __('%s назад', 'bmr'),
                                human_time_diff(get_comment_date('U'), current_time('timestamp')
                            ));
                        ?>
                        </time>
                    </a>
                </div>
                <div class="comment-meta">
                    <a
                        href="mailto:<?= esc_attr($comment->comment_author_email) ?>"
                        class="comment-email"
                    ><?= esc_attr($comment->comment_author_email) ?></a>
                    <span class="bullet" aria-hidden="true">•</span>
                    <a
                        href="<?= add_query_arg('s', $comment->comment_author_IP) ?>"
                        class="comment-ip"
                    >
                        <?= $comment->comment_author_IP ?>
                    </a>
                </div>
            </div>
            <div class="comment-body">
                <?php comment_text(); ?>
            </div>
        </div>
        <div class="comment-menu">
            <?php \Bmr\Comments\Helper::commentRowActions($comment); ?>
<!--            <a href="">Редактировать</a>-->
<!--            <a href="">Ответить</a>-->
        </div>
    </div>

</li>