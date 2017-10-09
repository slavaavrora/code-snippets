<?php

/**
 * Class BmrCommentSystem
 */
class BmrCommentSystem
{
    /**
     * Current user id
     * @var int
     */
    private $currentUserId;

    /**
     * Is current user admin
     * @var bool
     */
    private $isAdmin;

    /**
     * Is current user complaints moderator
     * @var bool
     */
    private $isModerator;

    /**
     * Allowed comment actions
     * @var array
     */
    private $allowedActions;


    public function __construct()
    {
        $this->currentUserId  = get_current_user_id();
        $this->isAdmin        = BmrHelper::checkUserRole('administrator', $this->currentUserId);
        $this->isModerator    = current_user_can('edit_complaints');
        $this->allowedActions = array('insert', 'delete', 'mute', 'unmute', 'edit');
    }

    public function init()
    {
        add_action('wp_ajax_' . BmrConfig::COMMENT_ACTION, array($this, 'commentAction'));
        add_action('wp_ajax_nopriv_' . BmrConfig::COMMENT_ACTION, array($this, 'commentAction'));
    }

    /**
     * Creating new comment in database
     * @param array $commentdata
     * @return int|bool Id of inserted comment or false on failure
     */
    public function insertComment($commentdata = array())
    {
        if (isset($commentdata['user_ID'])) {
            $commentdata['user_id'] = $commentdata['user_ID'] = (int)$commentdata['user_ID'];
        }
        $prefiltered_user_id = (isset($commentdata['user_id'])) ? (int)$commentdata['user_id'] : 0;

        $commentdata['comment_post_ID'] = (int)$commentdata['comment_post_ID'];
        if (isset($commentdata['user_ID']) && $prefiltered_user_id !== (int)$commentdata['user_ID']) {
            $commentdata['user_id'] = $commentdata['user_ID'] = (int)$commentdata['user_ID'];
        } elseif (isset($commentdata['user_id'])) {
            $commentdata['user_id'] = (int)$commentdata['user_id'];
        }

        $commentdata['comment_parent']   = isset($commentdata['comment_parent']) ? absint(
            $commentdata['comment_parent']
        ) : 0;
        $commentdata['comment_date']     = current_time('mysql');
        $commentdata['comment_date_gmt'] = current_time('mysql', 1);

        global $wpdb;
        $data = wp_unslash($commentdata);

        $comment_author     = !isset($data['comment_author']) ? '' : $data['comment_author'];
        $comment_date       = !isset($data['comment_date']) ? current_time('mysql') : $data['comment_date'];
        $comment_date_gmt   = !isset($data['comment_date_gmt']) ? get_gmt_from_date(
            $comment_date
        ) : $data['comment_date_gmt'];
        $comment_post_ID    = !isset($data['comment_post_ID']) ? '' : $data['comment_post_ID'];
        $comment_content    = !isset($data['comment_content']) ? '' : $data['comment_content'];
        $comment_parent     = !isset($data['comment_parent']) ? 0 : $data['comment_parent'];
        $user_id            = !isset($data['user_id']) ? 0 : $data['user_id'];
        $reply_to           = !isset($data['reply_to']) ? 0 : $data['reply_to'];;

        $compacted = compact(
            'comment_post_ID',
            'comment_author',
            'comment_date',
            'comment_date_gmt',
            'comment_content',
            'comment_parent',
            'user_id',
            'reply_to'
        );
        if (!$wpdb->insert($wpdb->bmr_complaint_comments, $compacted)) {
            return false;
        }

        $id = (int)$wpdb->insert_id;
        $this->updateCommentCount($comment_post_ID);
        $comment = $this->getComment($id);

        /**
         * @param int $id The comment ID.
         * @param obj $comment Comment object.
         */
        do_action('bmr_insert_comment', $id, $comment);
        wp_cache_set('last_changed', microtime(), 'bmr_comment');

        return $id;
    }

    public function updateComment($commentdata)
    {

    }

    public function updateCommentCount($postId)
    {
        global $wpdb;
        $postId = (int)$postId;

        if (!$postId) {
            return false;
        }
        if (!$post = get_post($postId)) {
            return false;
        }

        $new = (int)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->bmr_complaint_comments} WHERE comment_post_ID = %d",
                $postId
            )
        );
        update_post_meta($postId, 'bmr_comment_count', $new);
        return true;
    }

    public static function getComment(&$comment, $output = OBJECT)
    {
        global $wpdb;

        if (empty($comment)) {
            $_comment = null;
        } elseif (is_object($comment)) {
            wp_cache_add($comment->comment_ID, $comment, 'bmr_comment');
            $_comment = $comment;
        } else {
            if (!$_comment = wp_cache_get($comment, 'bmr_comment')) {
                $_comment = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $wpdb->bmr_complaint_comments WHERE comment_ID = %d LIMIT 1",
                        $comment
                    )
                );
                if (!$_comment) {
                    return null;
                }
                wp_cache_add($_comment->comment_ID, $_comment, 'bmr_comment');
            }
        }
        if ($output == OBJECT) {
            return $_comment;
        } elseif ($output == ARRAY_A) {
            $__comment = get_object_vars($_comment);
            return $__comment;
        } elseif ($output == ARRAY_N) {
            $__comment = array_values(get_object_vars($_comment));
            return $__comment;
        } else {
            return $_comment;
        }
    }

    /**
     * @param $commentId
     * @param $key
     * @param $value
     * @return false|int
     */
    public static function updateCommentMeta($commentId, $key, $value)
    {
        global $wpdb;

        $key   = wp_unslash($key);
        $value = wp_unslash($value);
        $value = sanitize_meta($key, $value, 'comment');
        $value = maybe_serialize($value);

        $metaId = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_id FROM {$wpdb->bmr_complaint_commentmeta} WHERE meta_key = %s AND comment_id = %d",
                $key,
                $commentId
            )
        );

        if (!$metaId) {
            $res = $wpdb->insert(
                $wpdb->bmr_complaint_commentmeta,
                array(
                    'comment_id' => $commentId,
                    'meta_key'   => $key,
                    'meta_value' => $value
                ),
                array(
                    '%d',
                    '%s',
                    '%s'
                )
            );
        } else {
            $res = $wpdb->update(
                $wpdb->bmr_complaint_commentmeta,
                array(
                    'meta_value' => $value
                ),
                array(
                    'meta_key'   => $key,
                    'comment_id' => $commentId,
                ),
                array(
                    '%s'
                ),
                array(
                    '%s',
                    '%d'
                )
            );
        }
        return $res;

    }

    /**
     * @param $commentId
     * @param $key
     * @return bool|mixed
     */
    public static function getCommentMeta($commentId, $key)
    {
        global $wpdb;
        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->bmr_complaint_commentmeta} WHERE meta_key = %s AND comment_id = %d",
                $key,
                $commentId
            )
        );
        if ($value) {
            $value = maybe_unserialize($value);
            return $value;
        } else {
            return false;
        }
    }

    public function deleteComment($commentId)
    {
        global $wpdb;
        if (!$comment = $this->getComment($commentId)) {
            return false;
        }
        // Move children up a level.
        $children = $wpdb->get_col(
            $wpdb->prepare("SELECT comment_ID FROM $wpdb->bmr_complaint_comments WHERE comment_parent = %d", $commentId)
        );
        if (!empty($children)) {
            $wpdb->update(
                $wpdb->bmr_complaint_comments,
                array('comment_parent' => $comment->comment_parent),
                array('comment_parent' => $commentId)
            );

            foreach ((array)$children as $id) {
                wp_cache_delete($id, 'bmr_comment');
            }
            wp_cache_set('last_changed', microtime(), 'bmr_comment');
        }

        // Delete metadata
        $meta_ids = $wpdb->get_col(
            $wpdb->prepare("SELECT meta_id FROM $wpdb->bmr_complaint_commentmeta WHERE comment_id = %d", $commentId)
        );
        foreach ($meta_ids as $mid) {
            delete_metadata_by_mid('comment', $mid);
        }

        if (!$wpdb->delete($wpdb->bmr_complaint_comments, array('comment_ID' => $commentId))) {
            return false;
        }

        /** Delete comment attachments if they exists */
        $attachments = self::getCommentMeta($commentId, 'bmr_comment_file_ids');
        if (!empty($attachments)) {
            $attachments = explode(',', $attachments);

            if (!empty($attachments[0])) {
                foreach ($attachments as $attachmentId) {
                    wp_delete_attachment($attachmentId, true);
                }
            }
        }

        $postId = $comment->comment_post_ID;
        if ($postId) {
            $this->updateCommentCount($postId);
        }

        foreach ((array)$commentId as $id) {
            wp_cache_delete($id, 'bmr_comment');
        }
        wp_cache_set('last_changed', microtime(), 'bmr_comment');

        return true;
    }

    public function getComments()
    {
        global $post, $wpdb;

        if (!$this->isAdmin && !$this->isModerator) {
            $comments = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $wpdb->bmr_complaint_comments WHERE comment_post_ID = %d AND (reply_to = %d OR user_id = %d OR reply_to = 0) ORDER BY comment_date_gmt",
                    $post->ID,
                    $this->currentUserId,
                    $this->currentUserId
                )
            );
        } else {
            $comments = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $wpdb->bmr_complaint_comments WHERE comment_post_ID = %d ORDER BY comment_date_gmt",
                    $post->ID
                )
            );
        }
        return $comments;
    }

    public function commentAction()
    {
        $nonce = !empty($_REQUEST['security']) ? $_REQUEST['security'] : '';
        $location = isset($_REQUEST['_wp_http_referer']) ? $_REQUEST['_wp_http_referer'] : $_SERVER['HTTP_REFERER'];

        if (!wp_verify_nonce($nonce, BmrConfig::NONCE_ACTION)) {
            wp_safe_redirect($location);
        }

        if (!isset($_REQUEST['action_type']) || !in_array($_REQUEST['action_type'], $this->allowedActions)) {
            wp_safe_redirect($location);
        }
        $actionType = $_REQUEST['action_type'];

        if (in_array($actionType, array('delete','mute', 'unmute', 'edit'))
            && !current_user_can('manage_options')
            && !current_user_can('edit_complaints')
        ) {
            wp_safe_redirect($location);
        }

        if ($actionType === 'insert') {
            $this->addCommentAction();
        } elseif ($actionType === 'delete') {
            $this->deleteCommentAction();
        } elseif ($actionType === 'mute') {
            $this->muteUserAction();
        } elseif ($actionType === 'unmute') {
            $this->unMuteUserAction();
        } elseif ($actionType === 'edit') {
            $this->editCommentAction();
        }
    }

    public function deleteCommentAction()
    {
        $location = isset($_REQUEST['_wp_http_referer']) ? $_REQUEST['_wp_http_referer'] : $_SERVER['HTTP_REFERER'];
        if (!isset($_GET['id'])) {
            wp_safe_redirect($location);
        }
        $commentId = $_GET['id'];
        $this->deleteComment($commentId);
        wp_safe_redirect($location);
    }

    public function addCommentAction()
    {
        $replyTo           = isset($_POST['bmr_reply_to']) ? sanitize_text_field($_POST['bmr_reply_to']) : 0;
        $replyToOriginal   = isset($_POST['bmr_reply_to_original']) ? sanitize_text_field($_POST['bmr_reply_to_original']) : '';
        $content           = isset($_POST['comment']) ? $_POST['comment'] : '';
        $postId            = isset($_POST['comment_post_ID']) ? $_POST['comment_post_ID'] : '';
        $commentParent     = isset($_POST['comment_parent']) ? $_POST['comment_parent'] : '';
        $commentId         = null;
        $fileIds           = sanitize_text_field($_POST['bmr_attachments']);

        if (!empty($content) && !empty($postId)) {

            $user = get_user_by('id', $this->currentUserId);
            if ($user) {
                $userName = $user->display_name;
            } else {
                die;
            }

            $data = array(
                'user_id'            => $this->currentUserId,
                'reply_to'           => (int)$replyTo,
                'comment_author'     => $userName,
                'comment_post_ID'    => (int)$postId,
                'comment_content'    => $content,
                'comment_parent'     => (int)$commentParent,
            );
            $commentId = $this->insertComment($data);
        }

        $location = isset($_REQUEST['_wp_http_referer']) ? $_REQUEST['_wp_http_referer'] : $_SERVER['HTTP_REFERER'];
        if ($commentId) {

            if (!empty($fileIds)) {
                self::updateCommentMeta($commentId, 'bmr_comment_file_ids', $fileIds);
            }

            $location .= '#comment-' . $commentId;

            if ($replyTo != 0) {
                if ($replyTo == 1) {
                    $ids = BmrHelper::getUserIdsByRole('complaints_moderator');
                    $ids[] = 1;

                    if (!empty($replyToOriginal) && !in_array($replyToOriginal, $ids)) {
                        $ids[] = $replyToOriginal;
                    }
                    foreach ($ids as $userId) {
                        $data = $this->getUserDataForNotification($userId, $postId, $commentId);
                        $this->sendNotification($data);
                    }
                }
                $data = $this->getUserDataForNotification($replyTo, $postId, $commentId);
                $this->sendNotification($data);
            } else {
                $usersReplyTo = BmrHelper::getOtherCommentsUsers($this->currentUserId, $postId, true);

                foreach($usersReplyTo as $userId) {
                    $data = $this->getUserDataForNotification($userId, $postId, $commentId);
                    $this->sendNotification($data);
                }
            }
        }
        wp_safe_redirect($location);
    }

    public function getUserDataForNotification($replyTo, $postId, $commentId)
    {
        $user  = get_user_by('id', $replyTo);
        $email = $user->user_email;
        $name  = BmrHelper::getUserDisplayName($replyTo);
        $reply_username = BmrHelper::getUserDisplayName($this->currentUserId);
        $url = sprintf('%s?notify=1#comment-%s', get_permalink((int)$postId), $commentId);

        $data = array(
            'name' => $name,
            'email' => $email,
            'reply_username' => $reply_username,
            'url' => $url,
            'post_id' => (int)$postId
        );
        return $data;
    }

    public function sendNotification($data)
    {
        if (empty($data['name']) || empty($data['email'])) {
            return false;
        }
        remove_all_filters( 'wp_mail_from' );
        remove_all_filters( 'wp_mail_from_name' );

        $post = get_post($data['post_id']);

        if ($post) {
            $subject = __('Новое сообщение по вашей жалобе', 'bmr') . ' - ' . $post->post_title;
        } else {
            $subject   = BmrOptions::option('bmr_comment_mail_subject');
        }

        $to        = sprintf('%s<%s>', $data['name'], $data['email']);
        $email_author = $this->currentUserId;
        $email_title = get_the_title($post->ID);
        $email_link = BmrHelper::addUtmTagsToEmailLink($data['url'], $post);
        $email_content = isset($_POST['comment']) ? $_POST['comment']: '';

        ob_start();
        include get_template_directory() . '/templates/emails/new-complaint-answer.php';
        $message = ob_get_clean();

        $from = sprintf('From: %s <%s>', __('Уведомление о вашей жалобе', 'bmr'),
            'complaints@bookmakersrating.ru');
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $headers[] = $from;

        $send = @wp_mail($to, $subject, $message, $headers);

        $txt = sprintf('%s, To: %s, Subject: %s, Success: %b, Time: %s', $from, $to, $subject, $send, current_time('mysql', 1));
        update_option('bmr_sent_emails_p' . $data['post_id'] . '_' . uniqid(), $txt, false);

//        print_r('To: ' . $data['name']  . ' : ' . $data['email'] . '<br><br>');
//        print_r('Headers: ' . PHP_EOL);
//        print_r($headers);
//        print_r(PHP_EOL . 'Message: ' . $message . PHP_EOL);
//        print_r('Subject: ' . $subject . PHP_EOL);
//        echo '----------------------------------' . PHP_EOL;

        return $send;
    }

    public function muteUserAction()
    {
        $location = isset($_REQUEST['_wp_http_referer']) ? $_REQUEST['_wp_http_referer'] : $_SERVER['HTTP_REFERER'];
        if (!isset($_GET['user_id']) || !isset($_GET['post_id'])) {
            wp_safe_redirect($location);
        }
        $userId = sanitize_text_field($_GET['user_id']);
        $postId = sanitize_text_field($_GET['post_id']);

        $mutedUsers = get_post_meta($postId, '_muted_members', true);
        $mutedUsers[] = $userId;

        update_post_meta($postId, '_muted_members', $mutedUsers);
        wp_safe_redirect($location);
    }

    public function unMuteUserAction()
    {
        $location = isset($_REQUEST['_wp_http_referer']) ? $_REQUEST['_wp_http_referer'] : $_SERVER['HTTP_REFERER'];
        if (!isset($_GET['user_id']) || !isset($_GET['post_id'])) {
            wp_safe_redirect($location);
        }
        $userId = sanitize_text_field($_GET['user_id']);
        $postId = sanitize_text_field($_GET['post_id']);

        $mutedUsers = get_post_meta($postId, '_muted_members', true);
        $mutedUser = array_search($userId, $mutedUsers);

        if ($mutedUser !== false) {
            unset($mutedUsers[$mutedUser]);
            update_post_meta($postId, '_muted_members', $mutedUsers);
        }
        wp_safe_redirect($location);
    }

    public function editCommentAction()
    {
        header('Content-type: application/json');
        global $wpdb;
        $commentText = $_POST['comment'];
        $commentId = $_POST['comment_id'];
        $fileIds = sanitize_text_field($_POST['bmr_attachments']);

        if (empty($commentId) || empty($commentText)) {
            $response['success'] = false;
        } else {
            $attachments = self::getCommentMeta($commentId, 'bmr_comment_file_ids');

            if (!empty($fileIds) || !empty($attachments)) {

                if ($attachments !== false) {
                    $fileIds .= ',' . $attachments;
                }
                $fileIds = trim($fileIds, ',');
                self::updateCommentMeta($commentId, 'bmr_comment_file_ids', $fileIds);
                $fileIds = explode(',', $fileIds);

                foreach ($fileIds as $fileId) {
                    $thumb = wp_get_attachment_image_src($fileId, 'thumbnail');
                    $thumb = $thumb[0];

                    $full = wp_get_attachment_image_src($fileId, 'full');
                    $full = $full[0];

                    $meta = wp_get_attachment_metadata($fileId);
                    $fileName = '';
                    $size = '';
                    if (!empty($meta['sizes']['thumbnail']['file'])) {
                        $fileName = str_replace('-150x150', '', $meta['sizes']['thumbnail']['file']);
                        $size = fruitframe_format_bytes(filesize(get_attached_file($fileId)));
                    }
                    $response['data']['attachments'][] =
                        "<a class='comment-file' href='$full' data-img-id='$fileId'
                            data-img-size='$size' data-img-url='$thumb'
                            title='$fileName' style='background-image: url($thumb)'>
                            <span class='comment-file-title'>$fileName</span>
                        </a>";
                }
            }
            $result = $wpdb->update(
                $wpdb->bmr_complaint_comments,
                array('comment_content' => $commentText),
                array('comment_ID' => $commentId),
                array('%s'),
                array('%d')
            );
            $response['success'] = true;
            $response['data']['comment_content'] = wpautop($commentText);

        }
        echo json_encode($response);
        die;
    }

}
