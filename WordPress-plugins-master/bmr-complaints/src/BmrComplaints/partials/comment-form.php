<?php
$replyTo       = BmrHelper::getChatUsersList();
$currentUserId = get_current_user_id();
$allowed       = (array_key_exists($currentUserId, $replyTo) && $currentUserId != 0) || current_user_can('manage_options') || current_user_can('edit_complaints');

if (current_user_can('manage_options') || current_user_can('edit_complaints')) {
    //unset($replyTo[1]);
} else {
    unset($replyTo[$currentUserId]);
}

global $post;
$comments = BmrHelper::getComplaintsComments($post->ID);
$mutedMembers = get_post_meta($post->ID, '_muted_members', true);

if (!is_array($mutedMembers)) {
    $mutedMembers = array($mutedMembers);
}

$isMuted = in_array($currentUserId, $mutedMembers);

/** Get author name */
$author = get_field('bmr_author', $post->ID);
$author = get_userdata($author['ID']);
$authorLogin = get_field('bmr_username', $post->ID);
$authorEmail = get_field('bmr_email', $post->ID);
$authorName = '';
$authorId = $author->ID;
$firstName = $author->first_name;
$lastName = $author->last_name;

if (!empty($firstName) && !empty($lastName)) {
    $authorName = $firstName . ' ' . $lastName;
    $author = get_avatar_text($authorName);
} else {
    $authorName = !empty($firstName) ? $firstName : $author->get('display_name');
    $author = get_avatar_text($authorName);
}
$author = mb_strtoupper($author);

?>
<?php if($allowed):?>
<div id="comments" class="comments-area fleft complaints-discussion">
    <div class="notice-container">
        <div class="notice fadeIn animated">
            <div class="notice-left bounceInLeft animated">
                <i class="notice-line"></i><i class="fa fa-lock"></i>
            </div>
            <div class="notice-content">
                <span class="notice-txt flash animated"> <?php _e('Это приватное обсуждение, другие пользователи его не видят!', 'bmr') ?> </span>
            </div>
            <div class="notice-right bounceInRight animated">
                <i class="fa fa-lock"></i><i class="notice-line"></i>
            </div>
        </div>
    </div>
    <div class="author-block-container">
        <span class="author-circle">
            <a href="<?php echo get_author_posts_url($authorId) ?>">
                <?php echo $author ?>
            </a>
        </span>
        <div class="author-info-container">
            <div class="author">
                <span class="author-name">
                    <?php _e('Автор жалобы:', 'bmr') ?>
                    <a href="<?php echo get_author_posts_url($authorId) ?>">
                         <?php echo $authorName ?>
                    </a>
                </span>
            </div>
            <div class="author-info">
                <span class="author-login author-meta"><?php _e('Логин:', 'bmr') ?> <?php echo $authorLogin ?></span>
                <span class="author-email author-meta"><?php _e('E-mail:', 'bmr') ?> <?php echo "<a href='mailto:$authorEmail'>$authorEmail</a>" ?></span>
            </div>
        </div>
    </div>
    <?php if(!$isMuted) { ?>
    <div id="respond" class="comment-respond">
        <form id="complaints-comment-form" action="<?php echo admin_url('admin-ajax.php') ?>" method="post" id="commentform" class="comment-form">
            <div class="form-heading">
                <h3><?php _e('Обсуждение жалобы', 'bmr') ?> (<?php echo count($comments) ?>)</h3>
            </div>
            <p class="comment-form-comment">
                <label for="comment"></label>
                <textarea id="comment" class="comment-txtarea input-control" name="comment" cols="45" rows="8" aria-required="true" placeholder="Введите текст сообщения"></textarea>
            </p>
            <div class="form-submit">
                <div class="complaint-form-buttons">

                    <div class="complaint-form-first-group">

                        <div class="recipient-select-container">
                            <select name="bmr_reply_to" id="reply-to" class="pull-left bmr-comment-chosen chosen-select" data-placeholder="<?php _e('Выберите получателя', 'bmr') ?>">
                                <option value=""></option>
                                <?php $usersCount = count($replyTo); ?>
                                <?php foreach($replyTo as $id => $user) { ?>
                                    <option value="<?php echo $id ?>" <?php selected(1, $usersCount) ?>><?php echo $user; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="bmr-file-container form-group">
                            <input name="bmr_attachments" id="bmr_attachments" type="hidden" value="" />
                            <label for="bmr_file" class="bmr_file"><i class="icon-new icon-icon7 bmr-paperclip"></i></label>
                            <input type="file" class="form-control" id="bmr_file" name="bmr_file">
                        </div>
                    </div>
                    <div class="comment-submit-btn">
                        <button name="submit" type="submit" id="submit" class="has-spinner btn-comment-submit button-default button-default-s"><span class="btn-spinner"><i class="fa fa-refresh fa-spin"></i></span><?php _e('Отправить', 'bmr') ?></button>
                    </div>
                </div>
                <div class="file-error">
                    <span class="icon-Flaticon_23628"></span>
                    <span class="file-error-msg"></span>
                </div>
                <div class="fileLoadedPARENT hidden">
                    <div class="progressBar">
                        <div class="progressBar-inner"></div>
                        <div class="fileLoaded"></div>
                    </div>
                    <div class="cancelFile"><span class="file-action-txt"><?php _e('Отменить', 'bmr') ?></span></div>
                </div>
                <div class="fileArchive hidden"></div>
                <?php comment_id_fields(); ?>
                <input name="action" type="hidden" value="<?php echo BmrConfig::COMMENT_ACTION ?>"/>
                <input name="action_type" type="hidden" value="insert"/>
                <?php wp_nonce_field(BmrConfig::NONCE_ACTION, 'security'); ?>
            </div>
            <input type="hidden" id="_wp_unfiltered_html_comment_disabled" name="_wp_unfiltered_html_comment"
                   value="b7f6268a7a">
            <script>
                (function () {
                    if (window === window.parent) {
                        document.getElementById('_wp_unfiltered_html_comment_disabled').name = '_wp_unfiltered_html_comment';
                    }
                })();
            </script>
        </form>
    </div><!-- /.comment-respond -->
    <div id="bmr-comment-reply" class="comment-reply-container">
        <form id="complaints-reply-form" action="<?php echo admin_url('admin-ajax.php') ?>" method="post" id="replyform" class="comment-form">
            <div class="comment-form-comment">
                <textarea id="reply-comment" class="comment-txtarea input-control" name="comment" cols="45" rows="8" aria-required="true" placeholder="Введите текст сообщения"></textarea>
            </div>
            <div class="form-submit">
                <div class="complaint-form-buttons">
                    <div class="recipient-select-container">
                        <select name="bmr_reply_to" id="bmr-reply-to" class="pull-left bmr-comment-chosen" data-placeholder="<?php _e('Выберите получателя', 'bmr') ?>">
                            <option value=""></option>
                            <?php foreach($replyTo as $id => $user) { ?>
                                <option value="<?php echo $id ?>"><?php echo $user; ?></option>
                            <?php } ?>
                        </select>
                        <input type="hidden" name="bmr_reply_to_original" id="bmr-reply-to-original" value="">
                    </div>
                    <div class="bmr-file-container form-group">
                        <input name="bmr_attachments" id="bmr_attachments" type="hidden" value="" />
                        <label for="bmr_file" class="bmr_file"><i class="icon-new icon-icon7 bmr-paperclip"></i><span class="bmr-file-attach-text"><?php _e('Прикрепить файл', 'bmr') ?></span></label>
                        <input type="file" class="form-control" id="bmr_file_2" name="bmr_file">

                    </div>
                    <div class="comment-submit-btn">
                        <button name="submit" type="submit" id="reply-submit" class="has-spinner btn btn-reply"><span class="btn-spinner"><i class="fa fa-refresh fa-spin"></i></span><?php _e('Отправить', 'bmr') ?></button>
                    </div>
                </div>
                <div class="file-error">
                    <span class="icon-Flaticon_23628"></span>
                    <span class="file-error-msg"></span>
                </div>
                <div class="fileLoadedPARENT hidden">
                    <div class="progressBar">
                        <div class="progressBar-inner"></div>
                        <div class="fileLoaded"></div>
                    </div>
                    <div class="cancelFile"><span class="file-action-txt"><?php _e('Отменить', 'bmr') ?></span></div>
                </div>
                <div class="fileArchive hidden"></div>
                <?php comment_id_fields(); ?>
                <input name="action" type="hidden" value="<?php echo BmrConfig::COMMENT_ACTION ?>"/>
                <input name="action_type" type="hidden" value="insert"/>
                <?php wp_nonce_field(BmrConfig::NONCE_ACTION, 'security'); ?>
            </div>
        </form>
    </div>
    <!-- /#bmr-comment-reply -->
    <?php } ?>
    <?php include_once 'comment-list.php'; ?>
    <div class="notice-container">
        <div class="notice fadeIn animaxed">
            <div class="notice-left bounceInLeft animated">
                <i class="notice-line"></i><i class="fa fa-lock"></i>
            </div>
            <div class="notice-content">
                <span class="notice-txt flash animated"> <?php _e('Это конец приватного обсуждения, которое доступно только Вам!', 'bmr') ?> </span>
            </div>
            <div class="notice-right bounceInRight animated">
                <i class="fa fa-lock"></i><i class="notice-line"></i>
            </div>
        </div>
    </div>
</div><!-- /.comments-area -->
<?php endif ?>

