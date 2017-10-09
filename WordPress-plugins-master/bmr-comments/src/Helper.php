<?php
namespace Bmr\Comments;

class Helper
{
    public static function getUserInfoByComment($comment)
    {
        if (!$comment = get_comment($comment)) {
            return false;
        }
        global $blog_id;
        $cacheKey = $comment->comment_author_email . '_' . $blog_id;
        $user     = !\Base\Helpers\Cache::isNeedClear('comment-users')
                  ? wp_cache_get($cacheKey, 'users')
                  : false;

        if ($user === false) {
            if (!$comment->user_id && $comment->comment_author_email) {
                $user = get_user_by('email', $comment->comment_author_email);
                $user && ($comment->user_id = $user->ID);
            }
            $user = new \Bmr\Comments\User($comment->user_id);

            if (!$user->ID) {
                $user->name       = $comment->comment_author;
                $user->initials   = mb_substr($user->name, 0, 2);
                $user->user_email = $comment->comment_author_email;
            }
            wp_cache_set($cacheKey, $user, 'users', WEEK_IN_SECONDS);
        }
        return $user;
    }

    public static function generateUndoBlock($comment, $originalAction, $echo = false)
    {
        if (!$comment = get_comment($comment)) {
            return '';
        }

        $messageMap = [
            'spam'      => __("Комментарий был помечен как спам", 'bmr'),
            'trash'     => __("Комментарий был перемещён в корзину", 'bmr'),
            'blacklist' => __("Пользователь был добавлен в черный список", 'bmr'),
            'pin'       => __("Комментарий был отмечен как особый", 'bmr'),
            'approve'   => __("Комментарий был одобрен", 'bmr')
        ];

        $message = $messageMap[$originalAction];
        $nonce   = esc_html('_wpnonce=' . wp_create_nonce("delete-comment_$comment->comment_ID"));
        $uri     = esc_url("comment.php?c={$comment->comment_ID}&action=un{$originalAction}comment&{$nonce}");

        ob_start();
        ?>
        <div class="undo">
            <span class="undo-message"><?= $message ?>.</span>
            <a class="undo-link" data-action="un<?= $originalAction ?>" href="<?= $uri ?>">
                <?php _e('Отменить', 'bmr') ?>
            </a>
        </div>

        <?php
        $content = ob_get_clean();
        if ($echo) {
            echo $content;
        }
        return $content;
    }

    public static function getLikesCount($comment_id, $type = '') {
        $likes = get_comment_meta($comment_id, 'likes', true);
        $likes = $likes ? array_count_values($likes) : [1 => 0, -1 => 0];
        !isset($likes[1]) && ($likes[1] = 0);
        !isset($likes[-1]) && ($likes[-1] = 0);
        return !$type ? $likes : ($type === 'positive' ? $likes[1] : $likes[-1]);
    }

    public static function navTabIsActive($current, $active, $echo = true)
    {
        $isActive = $current === $active;

        if ($echo) {
            echo $isActive ? 'nav-tab-active' : '';
        }
        return $isActive;
    }

    public static function commentRowActions($comment)
    {
        $post          = get_post();
        $commentStatus = wp_get_comment_status($comment->comment_ID);
        $delNonce      = esc_html('_wpnonce=' . wp_create_nonce("delete-comment_$comment->comment_ID"));
        $approveNonce  = esc_html('_wpnonce=' . wp_create_nonce("approve-comment_$comment->comment_ID"));
        $url           = "comment.php?c=$comment->comment_ID";

        $actions = [
            'approve'   => [
                'ico'   => 'icon-check',
                'label' => __('Approve'),
                'title' => esc_attr__('Approve this comment'),
                'uri'   => esc_url($url . "&action=approvecomment&$approveNonce"),
            ],
            'unapprove' => [
                'ico'   => 'icon-forbidden',
                'label' => __('Unapprove'),
                'title' => esc_attr__('Unapprove this comment'),
                'uri'   => esc_url($url . "&action=unapprovecomment&$approveNonce"),
            ],
            'spam'      => [
                'ico'   => 'icon-black-list-bookmakers',
                'label' => _x('Spam', 'verb'),
                'title' => esc_attr__('Mark this comment as spam'),
                'uri'   => esc_url($url . "&action=spamcomment&$delNonce"),
            ],
            'unspam'    => [
                'ico'   => 'icon-smiley',
                'label' => _x('Not Spam', 'comment'),
                'title' => '',
                'uri'   => esc_url($url . "&action=unspamcomment&$delNonce"),
            ],
            'trash'     => [
                'ico'   => 'icon-trash',
                'label' => _x('Trash', 'verb'),
                'title' => esc_attr__('Move this comment to the trash'),
                'uri'   => esc_url($url . "&action=trashcomment&$delNonce"),
            ],
            'untrash'   => [
                'ico'   => 'icon-time-history',
                'label' => __('Restore'),
                'title' => '',
                'uri'   => esc_url($url . "&action=untrashcomment&$delNonce"),
            ],
            'delete'    => [
                'ico'   => 'icon-open-box',
                'label' => __('Delete Permanently'),
                'title' => '',
                'uri'   => esc_url($url . "&action=deletecomment&$delNonce"),
            ],
            'reply'     => [
                'ico'   => 'icon-reply',
                'label' => __('Reply'),
                'title' => esc_attr__('Reply to this comment'),
                'uri'   => '#'
            ],
            'edit'      => [
                'ico'   => 'icon-pen-edit',
                'label' => __('Edit'),
                'title' => esc_attr__('Edit comment'),
                'uri'   => '#'
            ],
        ];

        if ($commentStatus === 'spam' || $commentStatus === 'trash' || !EMPTY_TRASH_DAYS) {
            unset($actions['trash']);
        } else {
            unset($actions['delete']);
        }

        $actions = apply_filters('comment_row_actions', array_filter($actions), $comment);

        foreach ($actions as $action => $attr) {
        ?>
            <a
                href='<?= $attr['uri'] ?>'
                title='<?= $attr['title'] ?>'
                class="<?= $action ?>"
                data-action='<?= $action ?>'
            >
                <i class='<?= $attr['ico'] ?>'></i><?= $attr['label'] ?>
            </a>
        <?php
        }
    }

    public static function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
               && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    public static function getPopularTopics()
    {
        global $wpdb, $post, $menu_active;
        $cacheKey = 'popular-topics-'
                  . (in_array($menu_active, ['handicappers', 'forecast']) ? 'tips-' : '')
                  . get_current_blog_id();

        $popular = isset($_GET['clean-cache']) && $_GET['clean-cache'] === 'comments-popular-topics'
                 ? false
                 : wp_cache_get($cacheKey, 'comments');

        if ($popular === false) {
            $specific = false;
            $join     = $where = '';

            switch ($menu_active) {
                case 'handicappers':
                case 'forecast':
                    $specific = true;
                    $where    = 'AND p.post_type = "forecast"';
                    break;
            }

            $popular = $wpdb->get_results("
                SELECT
                    p.ID,
                    p.post_title,
                    c.comment_content,
                    COUNT(c.comment_ID) AS count,
                    c.comment_date AS last_date,
                    c.comment_ID
                FROM {$wpdb->posts} AS p
                INNER JOIN {$wpdb->comments} AS c
                    ON c.comment_post_ID = p.ID AND c.comment_approved = 1
                WHERE p.comment_count >= 5 AND c.comment_post_ID <> {$post->ID} $where
                GROUP BY p.ID
                ORDER BY YEARWEEK(c.comment_date) DESC, p.comment_count DESC
                LIMIT 4
            ");

            if ($specific && !$popular) {
                $popular = $wpdb->get_results("
                    SELECT
                        p.ID,
                        p.post_title,
                        c.comment_content,
                        COUNT(c.comment_ID) AS count,
                        c.comment_date AS last_date,
                        c.comment_ID
                    FROM {$wpdb->posts} AS p
                    INNER JOIN {$wpdb->comments} AS c
                        ON c.comment_post_ID = p.ID AND c.comment_approved = 1
                    WHERE p.comment_count >= 5 AND c.comment_post_ID <> {$post->ID} $where
                    GROUP BY p.ID
                    ORDER BY YEARWEEK(c.comment_date) DESC, p.comment_count DESC
                    LIMIT 4
                ");
                $popular = $popular ? : [];
            }
            $popular = $popular ? : [];
            wp_cache_set($cacheKey, $popular, 'comments', HOUR_IN_SECONDS * 2);
        }
        return $popular;
    }

    public static function countComments($post_id = 0)
    {
        global $wpdb;
        $post_id = (int)$post_id;

        $where = '';
        if ($post_id > 0) {
            $where = $wpdb->prepare("AND c.comment_post_ID = %d", $post_id);
        }

//        $count = wp_cache_get("comments-{$post_id}", 'counts');
//        if ( false !== $count ) {
//            return $count;
//        }

        $totals = $wpdb->get_results("
            SELECT c.comment_approved AS status, COUNT(0) AS total
            FROM {$wpdb->comments} c
            WHERE c.comment_type = '' {$where}
            GROUP BY c.comment_approved
        ", ARRAY_A);

        $comment_count = array(
            'approved'            => 0,
            'awaiting_moderation' => 0,
            'spam'                => 0,
            'trash'               => 0,
            'post-trashed'        => 0,
            'total_comments'      => 0,
            'all'                 => 0,
        );

        foreach ( $totals as $row ) {
            switch ( $row['status'] ) {
                case 'trash':
                    $comment_count['trash'] = $row['total'];
                    break;
                case 'post-trashed':
                    $comment_count['post-trashed'] = $row['total'];
                    break;
                case 'spam':
                    $comment_count['spam'] = $row['total'];
                    $comment_count['total_comments'] += $row['total'];
                    break;
                case '1':
                    $comment_count['approved'] = $row['total'];
                    $comment_count['total_comments'] += $row['total'];
                    $comment_count['all'] += $row['total'];
                    break;
                case '0':
                    $comment_count['awaiting_moderation'] = $row['total'];
                    $comment_count['total_comments'] += $row['total'];
                    $comment_count['all'] += $row['total'];
                    break;
                default:
                    break;
            }
        }

        $stats = $comment_count;
	    $stats['moderated'] = $stats['awaiting_moderation'];
        unset($stats['awaiting_moderation']);

	    $stats_object = (object)$stats;
//        wp_cache_set("comments-{$post_id}", $stats_object, 'counts');
	    return $stats_object;
    }

}