<?php
$user              = new \Bmr\Comments\User(get_current_user_id());
$isLoggedIn        = is_user_logged_in();
?>
<div class="comment-form-container form-is-hidden">
    <div class="bmr-loading-spinner"></div>
    <div class="summary is-hidden"></div>
    <div class="comment-form-group">
        <div class="comment-avatar">
            <?php if (!$isLoggedIn): ?>
                <span class="icon-circle7 plus"></span>
            <?php elseif ($user->avatar && !$user->hasDefaultAvatar): ?>
                <img
                    data-role="user-avatar"
                    src="<?= $user->avatar ?>"
                    alt="<?php _e('Аватар', 'bmr') ?>"
                    />
            <?php else: ?>
                <span class="comment-user-initials"><?= $user->initials ?></span>
            <?php endif ?>
        </div>
        <form id="comment-form" action="<?= site_url('/wp-comments-post.php'); ?>" method="post" role="form">

            <label for="message" class="is-hidden"><?php _e('Сообщение:', 'bmr') ?></label>
            <textarea name="comment" id="message" placeholder="Написать комментарий..."></textarea>
            <div class="attachments-preview is-hidden"></div>
            <div class="file-group">
                <label for="file" class="file-label"><i class="icon-photo-landscape"></i></label>
                <input type="file" class="file-input" id="file" name="file">
            </div>
            <input type="hidden" name="comment_post_ID" value="<?= get_the_ID() ?>" id="comment_post_ID">
            <input type="hidden" name="comment_parent" id="comment_parent" value="0">
            <input type="hidden" name="comment_attachments" id="comment_attachment" value="">
            <button type="submit" class="comment-send-btn"><?php _e('Отправить', 'bmr') ?></button>
            <?php wp_comment_form_unfiltered_html_nonce(); ?>
        </form>
    </div>
</div>
<ul id="comments-list">
<?php
$commentPagesCount = get_comment_pages_count();
$currentPage       = get_query_var('cpage', 1);

wp_list_comments([
    'type'              => 'comment',
    'callback'          => [\Bmr\Comments\Base::class, 'renderComment'],
    'reverse_top_level' => false,
    'reverse_children'  => false,
    'page'              => $currentPage,
]);
?>
</ul>
<div id="edit-comment">
<?php
$quicktags = ['buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,close'];
wp_editor(
    '',
    'editcontent',
    [
        'media_buttons' => false,
        'tinymce'       => false,
        'quicktags'     => $quicktags,
        'editor_height' => 250
    ]
);
?>
<button type="button" class="comment-btn cancel-btn"><?php _e('Отменить', 'bmr') ?></button>
<button type="submit" class="comment-btn save-btn"><?php _e('Сохранить', 'bmr') ?></button>
</div>

<button
    id="comments-load-more"
    class="load-more-btn <?= $commentPagesCount > 1 && $currentPage < $commentPagesCount ? '' : 'is-hidden' ?>"
    data-total-pages="<?= $commentPagesCount ?>"
    data-current-page="<?= $currentPage ?>"
>
    <?php _e('Загрузить еще', 'bmr') ?>
</button>
<div class="comments-native-pagination">
    <?php paginate_comments_links(); ?>
</div>

<?php \Bmr\Comments\Base::renderSpecialPartial('moderation-menu'); ?>