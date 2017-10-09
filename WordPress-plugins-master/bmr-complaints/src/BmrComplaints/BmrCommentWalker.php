<?php
/**
 * Class BmrCommentWalker
 */
class BmrCommentWalker extends Walker_Comment
{
    /**
     * @var string
     */
    private $nonce;

    /**
     * @var array
     */
    private $links;

    /**
     * @var bool
     */
    private $isUserAdmin;

    /**
     * @var bool
     */
    private $commentAuthorIsAdmin;

    /**
     * @var array
     */
    private $mutedMembers;

    /**
     * @var bool
     */
    private $isMuted;

    /**
     * @var int
     */
    private $currentUserId;

    function __construct()
    {
        global $post;
        $this->nonce        = wp_create_nonce(BmrConfig::NONCE_ACTION);
        $this->links        = array(
            'delete' => '#',
            'view'   => '#',
            'mute'   => '#',
            'edit'   => '#',
            'unmute' => '#',
        );
        $this->isUserAdmin  = current_user_can('manage_options') || current_user_can('edit_complaints');
        $this->mutedMembers = get_post_meta($post->ID, '_muted_members', true);
        $this->commentAuthorIsAdmin = false;
        $this->currentUserId = get_current_user_id();

        if (!is_array($this->mutedMembers)) {
            $this->mutedMembers = array($this->mutedMembers);
        }

        $this->isMuted = in_array($this->currentUserId, $this->mutedMembers);
    }

    public function start_lvl( &$output, $depth = 0, $args = array() ) {
        $GLOBALS['comment_depth'] = $depth + 1;

        $style = isset($args['style']) ? $args['style'] : 'ol';
        switch ($style) {
            case 'div':
                break;
            case 'ol':
                $output .= '<ol class="children list-unstyled">' . "\n";
                break;
            case 'ul':
            default:
                $output .= '<ul class="children list-unstyled">' . "\n";
                break;
        }
    }

    public function end_lvl( &$output, $depth = 0, $args = array() ) {
        $GLOBALS['comment_depth'] = $depth + 1;

        $style = isset($args['style']) ? $args['style'] : 'ol';
        switch ($style) {
            case 'div':
                break;
            case 'ol':
                $output .= "</ol><!-- .children -->\n";
                break;
            case 'ul':
            default:
                $output .= "</ul><!-- .children -->\n";
                break;
        }
    }

    protected function comment($comment, $depth, $args)
    {
        $tag = 'li';
        $add_below = 'div-comment';

        // Get user main role
        $roles = BmrHelper::getUserRolesById($comment->user_id);
        $mainRole = reset($roles);

        $hideReply = ($comment->user_id == $this->currentUserId) || (BmrHelper::isUserBookmaker($comment->user_id) && BmrHelper::isUserAuthor($this->currentUserId));

        $this->commentAuthorIsAdmin = false;
        if (user_can($comment->user_id, 'manage_options') || user_can($comment->user_id, 'edit_complaints')) {
            $this->commentAuthorIsAdmin = true;
            $replyTo = 1;
        } else {
            $replyTo = $comment->user_id;
        }

        $commentAuthorIsMuted =  in_array($comment->user_id, $this->mutedMembers);
        // Show mute button if u r admin and comment author not
        if ($this->isUserAdmin && !$this->commentAuthorIsAdmin) {

            global $post;

            $params = array(
                'action'      => BmrConfig::COMMENT_ACTION,
                'user_id'     => $comment->user_id,
                'post_id'     => $post->ID,
                'security'    => $this->nonce
            );

            if (!$commentAuthorIsMuted) {
                $params['action_type'] = 'mute';
                $this->links['mute'] = add_query_arg($params, admin_url('admin-ajax.php'));
            } else {
                $params['action_type'] = 'unmute';
                $this->links['unmute'] = add_query_arg($params, admin_url('admin-ajax.php'));
            }
        }

        $userName = BmrHelper::getUserDisplayName($comment->user_id);

        if ($this->commentAuthorIsAdmin) {
            $userName .= " (". __($mainRole) . ")";
        }

        if ($comment->reply_to == 1) {
            $parentComment = BmrCommentSystem::getComment($comment->comment_parent);
            $id = !empty($parentComment) ? $parentComment->user_id : $comment->reply_to;
        } else {
            $id = $comment->reply_to;
        }
        $replyToUserName = BmrHelper::getUserDisplayName($id);

        if ($comment->reply_to == 0) {
            $userIds = BmrHelper::getOtherCommentsUsers($comment->user_id);

            if (is_array($userIds)) {
                $userIds = array_flip($userIds);

                foreach($userIds as $id => &$v) {
                    $v = BmrHelper::getUserDisplayName($id);
                }
                $replyToUserName = implode(', ',$userIds);
            }
        }
        if (is_admin()) {
            $params                = array(
                'action'      => BmrConfig::COMMENT_ACTION,
                'action_type' => 'delete',
                'id'          => $comment->comment_ID,
                'security'    => $this->nonce
            );
            $this->links['delete'] = add_query_arg($params, admin_url('admin-ajax.php'));

            $commentUrl = sprintf('%s#comment-%s', get_permalink((int)$comment->comment_post_ID), $comment->comment_ID);
            $this->links['view'] = $commentUrl;
        }
        $replyUrl   = esc_url(add_query_arg('replytocom', $comment->comment_ID)) . "#respond";
        ?>
        <<?php echo $tag; ?> <?php comment_class( $this->has_children ? 'parent' : '' ); ?> id="comment-<?php comment_ID(); ?>">
        <div id="div-comment-<?php comment_ID(); ?>" class="comment-body">
            <div class="comment-author vcard">
                <div class="avatar" style="background-image: url('<?php echo getAvatarUrl($comment->user_id, 32) ?>');"></div>
                <?php if(isset($commentUrl)) { ?>
                    <a href="<?php echo $commentUrl ?>">
                <?php } ?>
                <div class="comment-author-data">
                    <div class="comment-author-text">
                        <div class="comment-from">
                    <?php printf(
                        __('<cite class="fn" id="user-%d" data-reply-to="%d" data-reply-to-original="%d"><span class="from">от: </span>%s</cite>'),
                        $comment->user_id,
                        $replyTo,
                        $comment->user_id,
                        $userName
                    ); ?>
                        </div>
                    <?php if(!empty($replyToUserName)) { ?>
                        <div class="reply-to-name">
                            <?php _e('кому:', 'bmr') ?> <cite><?php echo $replyToUserName; ?></cite>
                        </div>
                <?php } ?>
                    </div>
                </div>
                 <?php if(isset($commentUrl)) { ?>
                    </a>
                    <?php } ?>
            </div>
            <!-- /.comment-author -->
            <div class="comment-text"><?=
                apply_filters(
                    'comment_text',
                    $comment->comment_content,
                    $comment,
                    array_merge(
                        $args,
                        ['add_below' => $add_below, 'depth' => $depth, 'max_depth' => @$args['max_depth']]
                    )
                );
            ?></div>
            <?php
            $fileIds = BmrCommentSystem::getCommentMeta($comment->comment_ID, 'bmr_comment_file_ids');
            $fileIds = explode(',', $fileIds);
            ?>
            <div class="fileArchive">
            <?php if (!empty($fileIds[0])) { ?>

                <?php foreach($fileIds as $fileId) { ?>
                    <?php
                    $thumb = wp_get_attachment_image_src($fileId, 'thumbnail');
                    $thumb = $thumb[0];

                    $full = wp_get_attachment_image_src($fileId, 'full');
                    $full = $full[0];

                    $meta =  wp_get_attachment_metadata($fileId);
                    $size = '';
                    if (!empty($meta['sizes']['thumbnail']['file'])) {
                        $fileName = str_replace('-150x150', '', $meta['sizes']['thumbnail']['file']);
                        $fileSize = @filesize(get_attached_file($fileId));

                        if (empty($fileSize)) {
                            continue;
                        }
                        $fileSize  = fruitframe_format_bytes($fileSize);
                        ?>
                        <a class="comment-file" href="<?php echo $full; ?>" data-img-id="<?php echo $fileId; ?>"
                           data-img-size="<?php echo $fileSize ?>" data-img-url="<?php echo $thumb ?>"
                           title="<?php echo $fileName ?>" style="background-image: url(<?php echo $thumb ?>)">
                            <span class="comment-file-title"><?php echo $fileName ?></span>
                        </a>
                        <?php
                    }
                    ?>
                <?php } ?>
        <?php }
            unset($fileIds); ?>
            </div>
            <!-- /.file-archive -->

            <div class="complaint-comment-meta">
                <div class="comment-meta commentmetadata">
                    <span>
                    <?php
                        printf(
                            __('%1$s at %2$s'),
                            mysql2date(get_option('date_format'), $comment->comment_date),
                            mysql2date(get_option('time_format'), $comment->comment_date, true)
                        );
                    ?>
                    </span>
                </div>
                <!-- Ответить , Заблокировать, Редактировать -->

                <div class="complaint-comment-actions">
                    <?php if (!is_admin() && !$this->isMuted && !$hideReply) { ?>
                        <a href="<?php echo $replyUrl ?>" class="comment-reply-link comment-action">
                            <i class="comment-meta-link-icon icon-reply"></i>
                            <span class="complaint-meta-text"><?php _e('Reply') ?></span>
                        </a>
                    <?php } ?>
                    <?php if ($this->isUserAdmin && !$this->commentAuthorIsAdmin) { ?>
                    <?php
                        if ($commentAuthorIsMuted) {
                            $muteTxt = __('Разблокировать', 'bmr');
                            $muteLink = $this->links['unmute'];
                        } else {
                            $muteTxt = __('Заблокировать', 'bmr');
                            $muteLink = $this->links['mute'];
                        }
                    ?>
                        <a class="comment-action comment-mute-author" href="<?php echo $muteLink ?>">
                            <i class="comment-meta-link-icon icon-forbidden"></i>
                            <span class="complaint-meta-text"><?php echo $muteTxt ?></span>
                        </a>
                    <?php } ?>
                    <?php if ($this->isUserAdmin) { ?>
                        <a href="<?php echo $this->links['edit'] ?>" class="comment-action comment-edit">
                            <i class="comment-meta-link-icon icon-icon40"></i>
                            <span class="complaint-meta-text"><?php _e('Редактировать', 'bmr') ?></span>
                        </a>
                    <?php } ?>
                    <?php if (is_admin()) { ?>
                        <a class="comment-action comment-delete" href="<?php echo $this->links['delete']; ?>">
                            <i class="comment-meta-link-icon icon-close"></i>
                            <span class="complaint-meta-text"><?php _e('Удалить', 'bmr') ?></span>
                        </a>
                    <?php } ?>
                </div>
                <!-- /.complaint-comment-actions -->
            </div>
            <!-- /.complaint-comment-meta -->
        </div>
        <!-- /.comment-body -->
    <?php
    }

}