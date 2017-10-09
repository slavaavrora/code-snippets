<?php


class BmrHelper
{
    /**
     * Gets complaint types
     *
     * @return array|WP_Error
     */
    public static function getComplaintTypes()
    {
        $types = get_terms(BmrConfig::TAXONOMY_COMPLAINT_TYPE, 'hide_empty=0&orderby=term_order');
        return !is_wp_error($types) ? $types : array();
    }

    public static function getUserDisplayName($id)
    {
        $user = get_user_by('id', $id);
        $usrFirstname = @$user->first_name;
        $usrLastname = @$user->last_name;

        if (!empty($usrFirstname)) {
            $usrName = esc_attr(trim($usrFirstname . ' ' . $usrLastname));
        } else {
            $usrName  = !empty($user->display_name) ? esc_attr($user->display_name) : '';
        }
        return $usrName;
    }

    /**
     * Get chat users list
     * @return array
     */
    public static function getChatUsersList()
    {
        global $post;
        $currentUserId = get_current_user_id();

        $bookmaker = get_post_meta($post->ID, 'bmr_bookmaker', true);
        $bookmakerId   = !empty($bookmaker) ? (int)$bookmaker : 0;
        unset($bookmaker);

        $author = get_post_meta($post->ID, 'bmr_author', true);
        $authorId = !empty($author) ? (int)$author : 0;
        unset($author);

        if ($currentUserId !== $authorId) {
            $users[0] = __('Всем', 'bmr');

        }
        $users[1] = __('Администрация', 'bmr');

        if ($bookmakerId !== 0 && $currentUserId !== $authorId) {
            $users[$bookmakerId] = __('Букмекерская контора', 'bmr');
        }
        if ($authorId !== 0) {
            $users[$authorId] = __('Автор жалобы', 'bmr');
        }
        return $users;
    }

    /**
     * Get user roles by user ID.
     *
     * @param  int $id
     * @return array
     */
    public static function getUserRolesById($id)
    {
        $user = new WP_User($id);

        if (empty($user->roles) || !is_array($user->roles)) {
            return array();
        }

        $wpRoles = new WP_Roles;
        $names    = $wpRoles->get_names();
        $out      = array ();

        foreach ($user->roles as $role)
        {
            if (isset($names[$role])) {
                if ($role == 'administrator') {
                    $out[$role] = 'Администратор';
                } else {
                    $out[$role] = $names[$role];
                }
            }
        }
        return $out;
    }

    /**
     * Gets other comment users participating in discussion
     *
     * @param int  $commentUserId
     * @param int  $postId
     * @param bool $mailing
     * @return array
     */
    public static function getOtherCommentsUsers($commentUserId, $postId = 0, $mailing = false)
    {
        global $wpdb, $post;
        $debug = isset($_GET['debug']);

        $commentAuthorIsAdmin = user_can($commentUserId, 'manage_options')/* || user_can($commentUserId, 'edit_complaints')*/;
        $commentAuthorIsMod = user_can($commentUserId, 'edit_complaints');
        $postId = ($postId == 0) ? $post->ID : $postId;

        $bookmaker = get_post_meta($postId, 'bmr_bookmaker', true);
        $bookmakerId   = !empty($bookmaker) ? (int)$bookmaker : 0;

        $author = get_post_meta($postId, 'bmr_author', true);
        $authorId = !empty($author) ? (int)$author : 0;

        $mods = self::getUserIdsByRole('complaints_moderator');
        $modKey =  array_search($commentUserId, $mods);
        if ($modKey) {
            unset($mods[$modKey]);
        }

        //reply_to <> 0 AND
        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT user_id FROM $wpdb->bmr_complaint_comments WHERE comment_post_ID = %d AND user_id <> %d ORDER BY comment_date_gmt",
                $postId,
                $commentUserId
            )
        );

        if ($debug) {
            echo PHP_EOL . 'Mods: ' . print_r($mods, true) . '<br>';
            echo PHP_EOL . 'Ids initial: ' . print_r($ids, true) . PHP_EOL;
        }
        foreach ($ids as $id => &$userId) {
            // comment to show all users that involved in discussion
            if ((user_can($userId, 'manage_options') || user_can($userId, 'edit_complaints')) && $userId != 1 && $mailing == false) {
                $userId = 1;
            }
            // unset admin if it's comment from moderator or another admin
            if (($commentAuthorIsMod || $commentAuthorIsAdmin) && $userId == 1 && $mailing == false) {
                unset($ids[$id]);
            }
        }
        unset($userId);

        if ($mailing === true) {
            $ids = array_merge($ids, $mods);
//            echo '<br><br>' . 'After merge: ' . print_r($ids, true) . '<br><br>';
        }

        if ($debug) {
            echo PHP_EOL . 'Ids before: ' . print_r($ids, true) . PHP_EOL;
        }

        if ($bookmakerId != 0
            && $bookmakerId != $commentUserId
            && array_search($bookmakerId, $ids) === false
            && $commentUserId !== $authorId
        ) {
            $ids[] = $bookmakerId;
        }
        if ($authorId != 0 && $authorId != $commentUserId && array_search($authorId, $ids) === false) {
            $ids[] = $authorId;
        }

        // whem sending email always check if admin exists in list
        if (array_search(1, $ids) === false && !$commentAuthorIsAdmin && $mailing == true) {
            $ids[] = 1;
        }

        // when user sends message but to all but no admin in list
        if (array_search(1, $ids) === false && $mailing == false && (!$commentAuthorIsAdmin && !$commentAuthorIsMod)) {
            $ids[] = 1;
        }

        $ids = array_unique($ids);
        if ($debug) {
            echo PHP_EOL . 'Ids after: ' . print_r($ids, true) . PHP_EOL;
        }
        return $ids;
    }

    /**
     * @param $type
     * @param $bookmaker
     * @return string
     */
    public static function generateComplaintTitle($type, $bookmaker)
    {
        $date = self::dateExt();
        return sanitize_text_field(sprintf('%s - %s - %s', $type, $bookmaker, $date));
    }

    /**
     * @param $date
     * @param string $lang
     * @return mixed|string
     */
    public static function dateExt($date = null)
    {
        $locale = get_locale();
        $format = $locale === 'en_US' ? '%B %e, %G' : '%e %B %G';
        $date = isset($date) ? $date : strftime($format);

        if ($locale == 'en_US' || !in_array($locale, array('uk', 'ru_RU'))) {
            return $date;
        }
        $patterns = array("/January/","/February/","/March/","/April/","/May/","/June/","/July/","/August/","/September/","/October/","/November/","/December/");

        $replacements = array(
            'uk' => array("Січня","Лютого","Березня","Квітня","Травня","Червня","Липня","Серпня","Вересня","Жовтня","Листопада","Грудня"),
            'ru_RU' => array("Января","Февраля","Марта","Апреля","Мая","Июня","Июля","Августа","Сентября","Октября","Ноября","Декабря"),
        );
        $date = preg_replace($patterns, $replacements[$locale], $date);
        return $date;
    }

    /**
     * Gets ids of users by role
     *
     * @param string $role
     * @return array admin users ids
     */
    public static function getUserIdsByRole($role = null)
    {
        if (is_null($role)) {
            return false;
        }

        $users = new WP_User_Query(array('role' => $role, 'fields' => 'ID'));
        $results = $users->get_results();
        return !empty($results) ? $results : array();
    }

    /**
     * @param string $role
     * @param int $userId
     * @return bool
     */
    public static function checkUserRole($role, $userId = null)
    {
        if (is_numeric($userId)) {
            $user = get_userdata($userId);
        } else {
            $user = wp_get_current_user();
        }

        if (empty($user)) {
            return false;
        }
        return in_array($role, (array)$user->roles);
    }

    /**
     * @param string $key
     * @param string $type
     * @param string $status
     * @return mixed
     */
    public static function getMetaValues($key = '', $type = 'post', $status = 'publish')
    {
        global $wpdb;
        $metas = array();
        if (empty($key)) {
            return array();
        }
        $r = $wpdb->get_results(
            $wpdb->prepare(
                "
        SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
        JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        WHERE pm.meta_key = '%s'
        AND p.post_type = '%s'
        AND pm.meta_value <> 'null'
        ",
                $key,
                $type
            )
        );

        foreach ($r as $my_r) {
            $metas[] = $my_r->meta_value;
        }

        return $metas;
    }

    public static function getComplaintsComments($postId)
    {
        global $wpdb;
        $currentUserId = get_current_user_id();

        //$isAdmin = BmrHelper::checkUserRole('administrator', $currentUserId);
        $isAdmin = current_user_can('manage_options') || current_user_can('edit_complaints');

        if (!$isAdmin) {
            $comments = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $wpdb->bmr_complaint_comments WHERE comment_post_ID = %d AND (reply_to = %d OR user_id = %d OR reply_to = 0) ORDER BY comment_date_gmt",
                    $postId,
                    $currentUserId,
                    $currentUserId
                )
            );
        } else {
            $comments = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $wpdb->bmr_complaint_comments WHERE comment_post_ID = %d ORDER BY comment_date_gmt",
                    $postId,
                    $currentUserId,
                    $currentUserId
                )
            );
        }
        return $comments;
    }

    public static function getComplaintCommentClass($class = '', &$comment, $post_id = null)
    {
        global $comment_alt, $comment_depth, $comment_thread_alt;

        $classes[] = 'comment';

        // If the comment author has an id (registered), then print the log in name
        if ( $comment->user_id > 0 && $user = get_userdata($comment->user_id) ) {
            // For all registered users, 'byuser'
            $classes[] = 'byuser';
            $classes[] = 'comment-author-' . sanitize_html_class($user->user_nicename, $comment->user_id);
            // For comment authors who are the author of the post
            if ( $post = get_post($post_id) ) {
                if ( $comment->user_id === $post->post_author )
                    $classes[] = 'bypostauthor';
            }
        }

        if ( empty($comment_alt) )
            $comment_alt = 0;
        if ( empty($comment_depth) )
            $comment_depth = 1;
        if ( empty($comment_thread_alt) )
            $comment_thread_alt = 0;

        if ( $comment_alt % 2 ) {
            $classes[] = 'odd';
            $classes[] = 'alt';
        } else {
            $classes[] = 'even';
        }

        $comment_alt++;

        // Alt for top-level comments
        if ( 1 == $comment_depth ) {
            if ( $comment_thread_alt % 2 ) {
                $classes[] = 'thread-odd';
                $classes[] = 'thread-alt';
            } else {
                $classes[] = 'thread-even';
            }
            $comment_thread_alt++;
        }

        $classes[] = "depth-$comment_depth";

        if ( !empty($class) ) {
            if ( !is_array( $class ) )
                $class = preg_split('#\s+#', $class);
            $classes = array_merge($classes, $class);
        }

        $classes = array_map('esc_attr', $classes);
        return 'class="' . (join(' ', $classes)) . '"';
    }

    public static function isUserBookmaker($user_id)
    {
        static $bookmakerId;

        if (!isset($bookmakerId)) {
            global $post;
            $bookmakerId = get_post_meta($post->ID, 'bmr_bookmaker', true);
            $bookmakerId   = !empty($bookmakerId) ? (int)$bookmakerId : 0;
        }
        return $bookmakerId == $user_id;
    }

    public static function isUserAuthor($user_id)
    {
        static $authorId;

        if (!isset($authorId)) {
            global $post;
            $authorId  = get_post_meta($post->ID, 'bmr_author', true);
            $authorId  = !empty($authorId) ? (int)$authorId : 0;
        }
        return $authorId == $user_id;
    }

    public static function getBlackList()
    {
        return get_option('bmr_complaints_blacklist', array());
    }

    public static function getCurrencyList()
    {
//        $currFieldObj = get_field_object('bmr_dispute_currency', 321194);
//        $choices = $currFieldObj['choices'];
        return array(
          'USD' => __('Доллар США', 'bmr'),
          'EUR' => __('Евро', 'bmr'),
          'RUB' => __('Российский рубль', 'bmr'),
          'UAH' => __('Украинская гривна', 'bmr'),
          'GBP' => __('Британский фунт', 'bmr'),
          'BYR' => __('Белорусский рубль', 'bmr'),
        );
    }

    public static function getNumberWithK($num)
    {
        $num = (float)$num;
        $suffix = ['', __('тыс.', 'bmr'), __('млн.', 'bmr'), __('млрд.', 'bmr')];

        for ($n = 0; $num >= 1e3; $num /= 1e3, $n++);
        return $n ? (str_replace('.0', '', number_format($num, 1, '.', ' ')) . ' ' . $suffix[$n]) : $num;
    }

    public static function getComplaintsTermPluralForm($queriedObj)
    {
        if ($queriedObj->taxonomy === 'bmr_complaint_tag') {
            $title = sprintf(__('Жалобы на букмекерскую контору %s', 'bmr'), $queriedObj->name);
        }
        elseif ($queriedObj->taxonomy === 'bmr_complaint_group_tag') {
            $title = sprintf(__('Жалобы - %s', 'bmr'), $queriedObj->name);
        } else {
            $title = $queriedObj->name;
        }

        $slugAssoc = [
            'complaint-status-groundless' => __('Безосновательные жалобы ', 'bmr'),
            'complaint-status-refused'    => __('Неудовлетворённые жалобы ', 'bmr'),
            'complaint-status-processing' => __('Жалобы, которые обрабатываются ', 'bmr'),
            'complaint-status-ignored'    => __('Проигнорированные жалобы ', 'bmr'),
            'complaint-status-solved'     => __('Удовлетворённые жалобы ', 'bmr'),

            'complaint-type-payout'        => __('Жалобы: задержка выплаты', 'bmr'),
            'complaint-type-bonus'         => __('Жалобы: незачисленный бонус', 'bmr'),
            'complaint-type-wager-dispute' => __('Жалобы: неправильно рассчитанная ставка', 'bmr'),
            'complaint-type-support-issue' => __('Жалобы: служба поддержки', 'bmr'),
            'blokirovka-scheta'            => __('Жалобы: блокировка счета', 'bmr'),
            'complaint-type-other'         => __('Жалобы: другое', 'bmr'),
            'confiscation'                 => __('Жалобы: конфискация баланса', 'bmr'),
        ];

        $title = isset($slugAssoc[$queriedObj->slug]) ? $slugAssoc[$queriedObj->slug] : $title;
        return $title;
    }

    /**
     * Adds UTM tags to link
     *
     * @param string       $link
     * @param \WP_Post|int $post
     * @param string       $type (empty string or 'subscribe')
     * @return string
     */
    public static function addUtmTagsToEmailLink($link, $post, $type = '')
    {
        if (!$link || !($post = get_post($post))) {
            return $link;
        }

        $campaign    = '';
        $format      = (strpos($link, '?') !== false ? '&' : '?') . 'utm_content=%d&utm_source=%s&utm_medium=email';
        $postTypeObj = get_post_type_object($post->post_type);
        $source      = $postTypeObj->rewrite['slug'];
        $args        = [$post->ID];

        if ($type === 'subscribe') {
            $format .= '&utm_campaign=%s';
            $source = 'subscribe';
            $campaign = get_the_author_meta('user_login', $post->post_author);
        }

        $args[] = $source;
        $campaign && $args[] = $campaign;

        if (strpos($link, '#') !== false) {
            $link = str_replace('#', (vsprintf($format, $args) . '#'), $link);
        } else {
            $link .= vsprintf($format, $args);
        }
        return $link;
    }

    public static function getPostTypeFromTaxonomy($taxonomy)
    {
        $postTax = get_taxonomy($taxonomy);

        if (!empty($postTax->object_type)) {
            $postTax = reset($postTax->object_type);
            return $postTax;
        } else {
            return false;
        }
    }

    public static function isComplaints()
    {
        $isComplaints = (bool)stripos($_SERVER['REQUEST_URI'],'forma-zhalob')
                        || (bool)stripos($_SERVER['REQUEST_URI'],'complaint-form');

        if (!$isComplaints
            && get_post_type() !== BmrConfig::POST_TYPE && get_post_type() !== BmrConfig::POST_TYPE_KAPPER
            && !is_tax([
                BmrConfig::TAXONOMY_COMPLAINT_TYPE,
                BmrConfig::TAXONOMY_COMPLAINT_STATUS,
                BmrConfig::TAXONOMY_COMPLAINT_STATUS_KAPPER,
                BmrConfig::TAXONOMY_COMPLAINT_TAG,
                BmrConfig::TAXONOMY_COMPLAINT_COMPANY
            ])
        ) {
            return false;
        } else {
            return true;
        }
    }
}