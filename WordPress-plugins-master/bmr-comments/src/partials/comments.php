<?php
    if ((is_user_logged_in() && isUserBanned(get_current_user_id())) || !comments_open()) {
        return;
    }
    global $post, $wp_query;
    $order = get_query_var('comment_order_by', 'new');
    $nativeCommentsCount = \Bmr\Comments\Helper::countComments($post->ID)->approved;

    $pinnedComment = get_comments([
        'type'       => 'comment',
        'post_id'    => $post->ID,
        'number'     => 1,
        'meta_key'   => 'pinned',
        'meta_value' => 1,
    ]);
    $pinnedComment = $pinnedComment ? reset($pinnedComment) : false;
    $settings = get_option('bmr_comments_settings', []);
?>
<div id="comments" class="comments-area">
    <div id="pinned-comment" class="pinned-comment <?= !$pinnedComment ? 'is-hidden' : '' ?>">
        <h2 class="pinned-comment-heading"><?php _e('Особый комментарий', 'bmr') ?></h2>
        <?php
            if($pinnedComment) {
                ob_start();
                \Bmr\Comments\Base::renderComment($pinnedComment, ['no_id' => true], 0);
                echo str_replace('<li>', '', ob_get_clean());
            }
        ?>
    </div>
    <div class="comments-tabs" data-current-tab="native">
        <div class="tabs-nav" role="tablist">
            <span role="tab" class='tab-nav-item active' data-tab="native"><?php _e('Комментарии', 'bmr') ?> <i class="comments-count"><?= $nativeCommentsCount ?></i></span>
            <?php if (!empty($settings['vk_id'])): ?>
            <span role="tab" class='tab-nav-item icon-vkontakte' data-tab="vk"><i class="comments-count">0</i></span>
            <?php endif; ?>
            <?php if (!empty($settings['fb_id'])): ?>
            <span role="tab" class='tab-nav-item icon-facebook' data-tab="fb"><i class="fb-comments-count comments-count" data-href="<?= getCurrentUrl() ?>">0</i></span>
            <?php endif; ?>
        </div>
        <select id="comments-sort" class="comments-sort-select">
            <option value="best" <?php selected($order, 'best') ?>><?php _e('Лучшие', 'bmr') ?></option>
            <option value="new" <?php selected($order, 'new') ?>><?php _e('Новые', 'bmr') ?></option>
            <option value="old" <?php selected($order, 'old') ?>><?php _e('Старые', 'bmr') ?></option>
        </select>
        <!-- /.tabs-nav -->
        <div class="tabs-content">
            <div role="tabpanel" class="tab-pane active" data-tab="native">
                <?php include_once '_native.php' ?>
            </div>
            <?php if (!empty($settings['vk_id'])): ?>
            <div role="tabpanel" class="tab-pane" data-tab="vk">
                <?php include_once '_vkontakte.php' ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($settings['fb_id'])): ?>
            <div role="tabpanel" class="tab-pane " data-tab="fb">
                <?php include_once '_facebook.php' ?>
            </div>
            <?php endif; ?>
            <?php \Bmr\Comments\Base::renderSpecialPartial('popular-topics'); ?>
        </div>
        <!-- /.tabs-content -->
    </div>
</div>
<!-- /#comments -->

