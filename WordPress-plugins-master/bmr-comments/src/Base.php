<?php
namespace Bmr\Comments;

class Base
{
    private $actions;
    private $tokens;
    private $isUserLoggedIn;
    private $pages;
    private $userCanModerate;

    public function __construct()
    {
        $this->actions = [
            'comment-rate'                      => [$this, 'rateCommentAction'],
            'comment-upload-attachment.private' => [$this, 'uploadCommentAttachmentAction'],
            'comment-delete-attachment.private' => [$this, 'deleteCommentAttachmentAction'],
            'comments-load-more'                => [$this, 'loadMoreCommentsAction'],
            'comments-check-new'                => [$this, 'checkForNewCommentsAction'],
            'comment-moderate.private'          => [$this, 'commentModerationAction'],
            'comments-count.private'            => [$this, 'commentsCountAction'],
            'comment-update'                    => [$this, 'commentUpdateAction']
        ];

        $this->pages = [];

        $this->isUserLoggedIn = is_user_logged_in();

        if ($this->isUserLoggedIn) {
            $user                  = new User(get_current_user_id());
            $this->userCanModerate = $user->isModerator;
        } else {
            $this->userCanModerate = false;
        }

        foreach ($this->actions as $action => $handler) {
            $action = explode('.', $action);
            $access = isset($action[1]) ? $action[1] : '';
            $action = $action[0];

            if ($access !== 'private' || $this->isUserLoggedIn) {
                $this->tokens[$action] = wp_create_nonce($action);
            }
            if ($access === '' || $access === 'private') {
                add_action('wp_ajax_' . $action, $handler);
            }
            if ($access === '' || $access === 'public') {
                add_action('wp_ajax_nopriv_' . $action, $handler);
            }
        }
    }

    public function commentsCountAction()
    {
        $data['count'] = (array)Helper::countComments();
        $data['count']['all'] = $data['count']['total_comments'];

        foreach($data['count'] as $status => $count) {
            $data['count_i18n'][$status] = number_format((float)$count, 0, '.', ' ');
        }

        die(json_encode($data));
    }

    public static function init()
    {
        $t = new self();
        add_action('comments_template', [$t, 'commentsTemplate'], 10, 0);
        add_action('wp_enqueue_scripts', [$t, 'loadFrontendAssets'], 11);
        add_action('admin_enqueue_scripts', [$t, 'loadBackendAssets'], 11, 1);
        add_action('comment_post', [$t, 'commentPostAction'], 10, 2);
        add_filter('comments_clauses', [$t, 'changeCommentClauses'], 10, 2);
        add_filter('query_vars', [$t, 'addCommentOrderVar'], 10, 1);
        add_filter('wp_die_handler', [$t, 'changeCommentErrorHandler']);
        add_action('admin_menu', [$t, 'registerMenuPages'], 999);
        add_action('profile_update', [$t, 'resetUserCache'], 10, 2);
        add_action('after_body_open_tag', [$t, 'initSocialProviders']);
        add_filter('comment_moderation_recipients', [$t, 'filterModerationRecipients'], 10, 2);
        add_filter('comment_moderation_text', [$t, 'commentModerationText'], 10, 2);
        add_filter('wp_redirect', [$t, 'commentRedirectUrl'], 10, 2);
        add_filter('admin_url', [$t, 'commentsAdminUrl'], 10, 3);
    }

    public function commentsAdminUrl($url, $path, $blog_id)
    {
        if ($path === 'edit-comments.php') {
            $url = admin_url('edit-comments.php?page=comments');
        }
        return $url;
    }

    public function commentRedirectUrl($location, $status)
    {
        if (strpos($location, 'edit-comments.php') !== false) {
            $location = str_replace('edit-comments.php?', 'edit-comments.php?page=comments&', $location);
        }
        return $location;
    }

    public function commentModerationText($notify_message, $comment_id)
    {
        return str_replace('comment_status=moderated', 'page=comments&comment_status=moderated', $notify_message);
    }

    public function filterModerationRecipients($emails, $comment_id)
    {
        global $blog_id;
        $moderators = get_users([
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => Moderators::EDIT_COMMENTS_FIELD . '_' . $blog_id,
                    'compare' => 'EXISTS'
                ]
            ],
            'fields' => ['user_email']
        ]);
        $moderators = wp_list_pluck($moderators, 'user_email');
        $emails = array_merge($emails, $moderators);
        $emails = array_unique($emails);
        return $emails;
    }

    public function initSocialProviders()
    {
        $blogLangs = [
            BLOG_ID_BMR    => 'ru_RU',
            BLOG_ID_PPS    => 'ru_RU',
            BLOG_ID_MC     => 'ru_RU',
            BLOG_ID_BMR_EN => 'en_US',
            BLOG_ID_BMR_UA => 'uk_UA',
            BLOG_ID_BMR_AM => 'hy_AM',
            BLOG_ID_BMR_KZ => 'kk_KZ'
        ];
        $settings = get_option('bmr_comments_settings', []);

        if (!empty($settings['vk_id'])) { ?>
            <div id="vk_api_transport"></div>
            <script type="text/javascript">
                !('vkAsyncInit' in window) && (window.vkAsyncInit = function() {
                    VK.init({
                        apiId: <?= $settings['vk_id'] ?>,
                        onlyWidgets: true
                    });
                });
                setTimeout(function() {
                    var el = document.createElement("script");
                    el.type = "text/javascript";
                    el.src = "//vk.com/js/api/openapi.js";
                    el.async = true;
                    document.getElementById("vk_api_transport").appendChild(el);
                }, 0);
            </script>
        <?php }

        if (!empty($settings['fb_id'])) { ?>
            <div id="fb-root"></div>
            <script  type="text/javascript">
                !('fbAsyncInit' in window) && (window.fbAsyncInit = function() {
                    FB.init({
                        appId: <?= $settings['fb_id'] ?>,
                        cookie: true,   // enable cookies to allow the server to access the session
                        xfbml: true,    // parse social plugins on this page
                        version: 'v2.4'
                    });
                });
                (function(d, s, id) {
                    var js, fjs = d.getElementsByTagName(s)[0];
                    if (d.getElementById(id)) return;
                    js = d.createElement(s); js.id = id;
                    js.src = "//connect.facebook.net/<?= $blogLangs[get_current_blog_id()] ?>/all.js";
                    fjs.parentNode.insertBefore(js, fjs);
                }(document, 'script', 'facebook-jssdk'));
            </script>
        <?php }
    }

    /**
     * Reset user cache set in Helper::getUserInfoByComment()
     * @param $userId
     * @param $oldUserData
     */
    public function resetUserCache($userId, $oldUserData)
    {
        global $blog_id;
        $cacheKey = $oldUserData->user_email . '_' . $blog_id;
        wp_cache_delete($cacheKey, 'users');
    }

    public function registerMenuPages()
    {
        global $menu;
        $userId = get_current_user_id();

        if (User::userCanEditComments($userId)) {
            $awaiting_mod = Helper::countComments();
            $awaiting_mod = $awaiting_mod->moderated;
            $menu[25] = array(
                sprintf(
                    __( 'Comments %s' ),'<span class="awaiting-mod count-' . absint( $awaiting_mod ) . '"><span class="pending-count">' . number_format_i18n( $awaiting_mod ) . '</span></span>' ),
                'read',
                'edit-comments.php',
                '',
                'menu-top menu-icon-comments',
                'menu-comments',
                'dashicons-admin-comments',
            );

            $this->pages['comments'] = add_comments_page(
                __('Все Комментарии', 'bmr'),
                __('Все Комментарии', 'bmr'),
                'read',
                'comments',
                function () {
                    include_once BMR_COMMENTS_PARTIALS . '/backend/comments.php';
                }
            );
        }

        if (User::userCanChangeSettings($userId)) {
            $this->pages['comments-settings'] = add_comments_page(
                __('Настройки', 'bmr'),
                __('Настройки', 'bmr'),
                'read',
                'comments-settings',
                function () {
                    include_once BMR_COMMENTS_PARTIALS . '/backend/settings.php';
                }
            );
        }
        remove_submenu_page('edit-comments.php', 'edit-comments.php');
        $this->registerSettings();
    }

    public function registerSettings()
    {
        $settings = get_option('bmr_comments_settings', []);
        add_settings_section('general', '', '', BMR_COMMENTS_SLUG . '-settings');

        add_settings_field(
            'content_height',
            __('Максимальная высота комментария', 'bmr'),
            [Backend::class, 'fieldInput'],
            BMR_COMMENTS_SLUG . '-settings',
            'general',
            [
                'name' => 'bmr_comments_settings[content_height]',
                'value' => isset($settings['content_height']) ? $settings['content_height'] : 250,
                'type' => 'number'
            ]
        );

        add_settings_field(
            'moderation_keys',
            __('Модерация комментариев'),
            [Backend::class, 'fieldTextarea'],
            BMR_COMMENTS_SLUG . '-settings',
            'general',
            [
                'name'        => 'moderation_keys',
                'value'       => get_option('moderation_keys', ''),
                'description' => __('When a comment contains any of these words in its content, name, URL, email, or IP, it will be held in the <a href="edit-comments.php?comment_status=moderated">moderation queue</a>. One word or IP per line. It will match inside words, so &#8220;press&#8221; will match &#8220;WordPress&#8221;.')
            ]
        );

        add_settings_field(
            'blacklist_keys',
            __('Чёрный список'),
            [Backend::class, 'fieldTextarea'],
            BMR_COMMENTS_SLUG . '-settings',
            'general',
            [
                'name'        => 'blacklist_keys',
                'value'       => get_option('blacklist_keys', ''),
                'description' => __('When a comment contains any of these words in its content, name, URL, email, or IP, it will be put in the trash. One word or IP per line. It will match inside words, so &#8220;press&#8221; will match &#8220;WordPress&#8221;.')
            ]
        );

        add_settings_field(
            'vk_id',
            __('Vkontakte Widget Id', 'bmr'),
            [Backend::class, 'fieldInput'],
            BMR_COMMENTS_SLUG . '-settings',
            'general',
            [
                'name' => 'bmr_comments_settings[vk_id]',
                'value' => isset($settings['vk_id']) ? $settings['vk_id'] : '',
            ]
        );

        add_settings_field(
            'fb_id',
            __('Facebook Widget Id', 'bmr'),
            [Backend::class, 'fieldInput'],
            BMR_COMMENTS_SLUG . '-settings',
            'general',
            [
                'name'  => 'bmr_comments_settings[fb_id]',
                'value' => isset($settings['fb_id']) ? $settings['fb_id'] : '',
            ]
        );

        register_setting(BMR_COMMENTS_SLUG . '-settings', 'bmr_comments_settings');
        register_setting(BMR_COMMENTS_SLUG . '-settings', 'moderation_keys');
        register_setting(BMR_COMMENTS_SLUG . '-settings', 'blacklist_keys');
    }

    public function changeCommentErrorHandler($handler)
    {
        if (isset($_POST['comment_post_ID']) && \Base\Helpers\Main::isAjax()) {
            $handler = [$this, 'commentErrorHandler'];
        }
        return $handler;
    }

    public function commentErrorHandler($message, $title, $args)
    {
        $message = preg_replace('#(.+?>:\\s?)#', '', $message);
        $message = mb_strtoupper(mb_substr($message, 0, 1)) . mb_substr($message, 1);
        die(json_encode([
            'success' => false,
            'error' => $message,
        ]));
    }

    public function addCommentOrderVar($vars)
    {
        $vars[] = 'comment_order_by';
        return $vars;
    }

    public function checkForNewCommentsAction()
    {
        $postId              = isset($_GET['post_id']) ? (int)$_GET['post_id'] : false;
        $commentsIds         = !empty($_GET['comment_ids']) ? explode(',', $_GET['comment_ids']) : false;
        $orderBy             = isset($_GET['comment_order_by']) ? $_GET['comment_order_by'] : false;
        $response['success'] = false;

        if ($postId === false || $commentsIds === false) {
            die(json_encode($response));
        }

        $commentsIds = array_map('intval', $commentsIds);
        $userId = get_current_user_id();
        $args   = [
            'type'    => 'comment',
            'post_id' => $postId,
            'status'  => 'approve'
        ];
        $userId && ($args['include_unapproved'] = [$userId]);
        $comments = [];

        foreach ($commentsIds as $id) {
            $args['parent'] = $id;
            $comments = array_merge($comments, get_comments($args));
        }

        if ($orderBy === 'new') {
            $commentId = end($commentsIds);
            $comment = get_comment($commentId);
            $args['parent'] = 0;
            $args['date_query'] = [
                'after' => $comment->comment_date
            ];
            $comments = array_merge($comments, $this->getComments($args));
        }

        $data = [];
        foreach($comments as $comment) {
            if (in_array($comment->comment_ID, $commentsIds)) {
                continue;
            }
            ob_start();
            self::renderComment($comment, [], 0);
            $data[$comment->comment_parent][] = ob_get_clean();
        }

        if ($data) {
            $response['success'] = true;
            $response['content'] = $data;
        }
        die(json_encode($response));
    }

    public function changeCommentClauses($clauses, $wp_comment_query)
    {
        if (isset($_GET['order'])) {
            return $clauses;
        }

        global $wpdb;
        $clauses['orderby'] = "$wpdb->comments.comment_date_gmt ASC, $wpdb->comments.comment_ID ASC";

        if (isset($wp_comment_query->query_vars['parent']) && $wp_comment_query->query_vars['parent'] == 0) {
            $orderBy            = isset($_GET['comment_order_by']) ? $_GET['comment_order_by'] : 'new';
            $order              = $orderBy === 'old' ? 'ASC' : 'DESC';
            $clauses['orderby'] = "$wpdb->comments.comment_date_gmt {$order}, $wpdb->comments.comment_ID ASC";

            if ($orderBy === 'best') {
                $clauses['join']    = "LEFT JOIN $wpdb->commentmeta cm_r ON cm_r.comment_id = $wpdb->comments.comment_ID "
                                      . "AND cm_r.meta_key = 'rating' {$clauses['join']}";
                $clauses['orderby'] = "CAST(IFNULL(cm_r.meta_value, 0) AS SIGNED INTEGER) DESC,"
                                      . "$wpdb->comments.comment_date_gmt DESC, $wpdb->comments.comment_ID ASC";
            }
        }
        return $clauses;
    }

    public function commentUpdateAction()
    {
        $id                  = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
        $content             = isset($_POST['content']) ? $_POST['content'] : '';
        $content             = wp_kses_post($content);
        $response['success'] = false;

        if (!$content || !$this->userCanModerate) {
            die(json_encode($response));
        }

        $result = wp_update_comment([
            'comment_ID' => $id,
            'comment_content' => $content
        ]);

        if ($result) {
            $response['success'] = true;
            $response['content'] = wpautop($content);
        }
        die(json_encode($response));
    }

    public function loadMoreCommentsAction()
    {
        $postId  = isset($_GET['post_id']) ? (int)$_GET['post_id'] : false;
        $current = isset($_GET['current']) ? (int)$_GET['current'] : false;

        if (!$postId || $current === false) {
            die;
        }

        $userId          = get_current_user_id();
        $commentsPerPage = get_option('comments_per_page');

        $args = [
            'type'    => 'comment',
            'post_id' => $postId,
            'number'  => $commentsPerPage,
            'offset'  => $commentsPerPage * $current,
            'status'  => 'approve',
        ];
        $userId && ($args['include_unapproved'] = [$userId]);
        $comments = $this->getComments($args);

        ob_start();
        wp_list_comments([
            'type'              => 'comment',
            'callback'          => [\Bmr\Comments\Base::class, 'renderComment'],
            'reverse_top_level' => false,
            'reverse_children'  => false
        ], $comments);
        die(ob_get_clean());
    }

    public function getComments($args) {
        $args['parent'] = !isset($args['parent']) ? 0 : $args['parent'];
        $mainComments = get_comments($args);
        unset($args['offset'], $args['number']);
        foreach($mainComments as $comment) {
            $args['parent'] = $comment->comment_ID;
            $mainComments = array_merge($mainComments, $this->getComments($args));
        }
        return $mainComments;
    }

    public function commentsTemplate()
    {
        return BMR_COMMENTS_PARTIALS . '/comments.php';
    }

    public function loadBackendAssets($page)
    {
        if (!in_array($page, $this->pages, true)) {
            return;
        }

        // STYLES
        wp_enqueue_style(
            'fonts-main',
            get_theme_root_uri() . '/base/assets/css/fonts-main.css',
            [],
            BASE_THEME_VERSION
        );

        wp_enqueue_style(
            'fonts-base',
            get_theme_root_uri()
            . '/base/assets/css/fonts'
            . (get_current_blog_id() === BLOG_ID_BMR_AM ? '-am' : '')
            . '.css',
            [],
            BASE_THEME_VERSION
        );

        wp_enqueue_style(
            'bk-icons',
            get_theme_root_uri() . '/base/assets/fonts/icons/style.css',
            [],
            BASE_THEME_VERSION
        );

        wp_enqueue_style(
            'nativeSelect',
            BASE_THEME_ASSETS . 'components/cool-select/css/cool-select.css',
            [],
            BASE_THEME_VERSION
        );

        wp_enqueue_style(
            BMR_COMMENTS_SLUG . '-admin-styles',
            BMR_COMMENTS_ASSETS_URI . '/css/admin.css',
            [],
            BMR_COMMENTS_VERSION
        );

        // SCRIPTS
        wp_enqueue_script(
            'nativeSelect',
            BASE_THEME_ASSETS . 'components/cool-select/cool-select.min.js',
            [],
            BASE_THEME_VERSION,
            true
        );

        wp_enqueue_script(
            BMR_COMMENTS_SLUG . '-admin-scripts',
            BMR_COMMENTS_ASSETS_URI . '/js/admin.js',
            [],
            BMR_COMMENTS_VERSION,
            true
        );
    }

    public function loadFrontendAssets()
    {
        // STYLES
        wp_enqueue_style(
            'nativeSelect',
            BASE_THEME_ASSETS . 'components/cool-select/css/cool-select.css',
            [],
            BASE_THEME_VERSION
        );

        wp_enqueue_style(
            BMR_COMMENTS_SLUG . '-styles',
            BMR_COMMENTS_ASSETS_URI . '/css/style.css',
            [],
            BMR_COMMENTS_VERSION
        );

        // SCRIPTS
        wp_enqueue_script(
            'nativeSelect',
            BASE_THEME_ASSETS . 'components/cool-select/cool-select.min.js',
            [],
            BASE_THEME_VERSION,
            true
        );

//        wp_enqueue_script(
//            'hidpiCanvas',
//            BASE_THEME_JS_URI . 'hidpi-canvas.min.js',
//            [],
//            BASE_THEME_VERSION,
//            true
//        );

        wp_enqueue_script(
            'ES6Promise',
            BASE_THEME_JS_URI . 'es6-promise.min.js',
            [],
            BASE_THEME_VERSION,
            true
        );

        wp_enqueue_script(
            BMR_COMMENTS_SLUG . '-tooltips',
            BMR_COMMENTS_ASSETS_URI . '/js/tooltips.js',
            [],
            BMR_COMMENTS_VERSION,
            true
        );

        wp_enqueue_script(
            BMR_COMMENTS_SLUG . '-scripts',
            BMR_COMMENTS_ASSETS_URI . '/js/script.js',
            [],
            BMR_COMMENTS_VERSION,
            true
        );

        $settings = get_option('bmr_comments_settings', []);

        wp_localize_script(
            BMR_COMMENTS_SLUG . '-scripts',
            'bmrComments',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'tokens' => $this->tokens,
                'errors' => [
                    'file_type' => sprintf(
                        __('Вы можете прикреплять только следующие типы файлов: %s', 'bmr'),
                        'jpg, jpeg, jpe, gif, png, bmp'
                    ),
                    'file_size' => sprintf(__('Максимальный размер прикрепляемого файла %s', 'bmr'), '2 МБ'),
                    'file_delete' => __('Не удалось удалить файл, пожалуйста попробуйте еще раз.', 'bmr'),
                    'unknown' => __('Произошла неизвестная ошибка', 'bmr')
                ],
                'i18n' => [
                    'new_comments_plural' => [
                        __('новый комментарий', 'bmr'),
                        __('новых комментария', 'bmr'),
                        __('новых комментариев', 'bmr'),
                    ],
                    'reveal-button-text' => __('Показать больше', 'bmr')
                ],
                'options' => $settings
            ]
        );
    }

    public function uploadCommentAttachmentAction()
    {
        $response['success'] = false;
        $response['error'] = __('Ошибка загрузки файла.', 'bmr');

        if (!isset($_FILES['file']) || !check_ajax_referer($_POST['action'], '_token', false)) {
            exit(json_encode($response));
        }

        $file = $_FILES['file'];
        $overrides = array(
            'test_form' => false,
            'mimes'     => array(
                'jpg|jpeg|jpe' => 'image/jpeg',
                'gif'          => 'image/gif',
                'png'          => 'image/png',
                'bmp'          => 'image/bmp',
            )
        );
        $attachment = wp_handle_upload($file, $overrides);

        if ($attachment && !isset($attachment['error'])) {
            $attachId = wp_insert_attachment([
                'guid' => $attachment['url'],
                'post_mime_type' => $attachment['type'],
                'post_title'     => preg_replace( '/\.[^.]+$/', '', basename($attachment['file'])),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ], $attachment['file']);

            if ($attachId) {
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                $attachData = wp_generate_attachment_metadata($attachId, $attachment['file']);
                wp_update_attachment_metadata($attachId, $attachData);
                $response['success'] = true;
                $response['data'] =  [
                    'url' => $attachment['url'],
                    'id'  => $attachId
                ];
            }
        } else {
            if ($attachment['error'] === __( 'Sorry, this file type is not permitted for security reasons.' )) {
                $response['error'] = sprintf(
                    __('Вы можете прикреплять только следующие типы файлов: %s', 'bmr'),
                    'jpg, jpeg, jpe, gif, png, bmp'
                );
            }
        }
        exit(json_encode($response));
    }

    public function deleteCommentAttachmentAction()
    {
        $attachId            = (int)$_GET['id'];
        $response['success'] = false;
        $attachment          = get_post($attachId);
        $userId              = get_current_user_id();

        if (
            $attachment
            && $attachment->post_author == $userId
            && check_ajax_referer($_GET['action'], '_token', false)
        ) {
            $response['success'] = (bool)wp_delete_attachment($attachId, true);
        }
        exit(json_encode($response));
    }

    public function commentModerationAction()
    {
        $action              = filter_input(INPUT_GET, 'sub_action', FILTER_SANITIZE_STRING);
        $comments            = filter_input(INPUT_GET, 'comments', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
        $type                = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
        $response['success'] = true;


        if (!$comments || !$this->userCanModerate) {
            $response['success'] = false;
            die(json_encode($response));
        }

        $isSingle  = $type === 'single';
        $returnMsg = $isSingle && in_array($action, ['approve', 'spam', 'trash', 'blacklist', 'pin']);
        $lastId    = -1;

        foreach($comments as $commentId) {
            $status = wp_get_comment_status($commentId);
            if (!$isSingle) {
                if ($action === 'trash' && in_array($status, ['trash', 'deleted', 'spam'], true)) {
                    $action = 'delete';
                }
                if ($action === 'spam' && $status === 'spam') {
                    $response['success'] = false;
                    break;
                }
            }
            $lastId = $commentId;
            switch ($action) {
                case 'approve':
                    wp_set_comment_status($commentId, 'approve');
                    break;
                case 'unapprove':
                    wp_set_comment_status($commentId, 'hold');
                    break;
                case 'spam':
                    wp_spam_comment($commentId);
                    break;
                case 'unspam':
                    wp_unspam_comment($commentId);
                    break;
                case 'trash':
                    wp_trash_comment($commentId);
                    break;
                case 'untrash':
                    wp_untrash_comment($commentId);
                    break;
                case 'delete':
                    wp_delete_comment($commentId, true);
                    break;
                case 'blacklist':
                    $this->setAuthorBlacklistStatus($commentId, 1);
                    break;
                case 'unblacklist':
                    $this->setAuthorBlacklistStatus($commentId, 0);
                    break;
                case 'pin':
                    $this->setCommentPinnedStatus($commentId, 1);
                    break;
                case 'unpin':
                    $this->setCommentPinnedStatus($commentId, 0);
                    break;
                default:
                    break;
            }
        }
        $returnMsg && ($response['message'] = Helper::generateUndoBlock($lastId, $action));
        die(json_encode($response));
    }

    public function setCommentPinnedStatus($commentId, $status = 1)
    {
        $comment = get_comment($commentId);
        if ($status) {
            $pinnedComment = get_comments([
                'type'       => 'comment',
                'post_id'    => $comment->comment_post_ID,
                'number'     => 1,
                'meta_key'   => 'pinned',
                'meta_value' => 1,
                'fields'     => 'ids'
            ]);

            if ($pinnedComment) {
                $pinnedCommentId = reset($pinnedComment);
                delete_comment_meta($pinnedCommentId, 'pinned');
            }
            return update_comment_meta($commentId, 'pinned', 1);
        } else {
            return delete_comment_meta($commentId, 'pinned');
        }
    }

    public function setAuthorBlacklistStatus($comment, $status = 1)
    {
        if (!$comment = get_comment($comment)) {
            return;
        }
        $author    = Helper::getUserInfoByComment($comment);
        $blacklist = new Blacklist();
        $action = $status ? 'addToBlackList' : 'removeFromBlacklist';
        $blacklist->$action($author->ID ? $author->ID : $author->user_email);
    }

    public function rateCommentAction()
    {
        $commentId = (int)$_GET['id'];
        $type      = filter_var($_GET['type'], FILTER_VALIDATE_BOOLEAN);
        $result    = ['success' => false];

        if ($comment = get_comment($commentId)) {
            $userId = get_current_user_id();

            $like   = $type ? 1 : -1;
            $likes  = get_comment_meta($commentId, 'likes', true);
            $rating = (int)get_comment_meta($commentId, 'rating', true);
            $likes  = $likes ?: [];

            if (isset($likes[$userId]) && $likes[$userId] == $like) {
                $rating -= $like;
                unset($likes[$userId]);
            } else {
                $val = $like;
                if (isset($likes[$userId]) && $likes[$userId] != $like) {
                    $val = $like * 2;
                }
                $rating += $val;
                $likes[$userId] = $like;
            }

            $comment->comment_parent == 0 && update_comment_meta($commentId, 'rating', $rating);
            update_comment_meta($commentId, 'likes', $likes);
            $result['likes'] = $likes ? array_count_values($likes) : [1 => 0, -1 => 0];
            $result['success'] = true;
        }
        die(json_encode($result));
    }



    public function commentPostAction($comment_ID, $comment_approved)
    {
        if (!\Base\Helpers\Main::isAjax()) {
            return;
        }
        $isAdmin = isset($_POST['is_admin']);

        // Filter attachments
        $attachments = $_POST['comment_attachments'];
        if ($attachments) {
            $attachments = explode(',', $attachments);
            $attachments = array_map('intval', $attachments);
            update_comment_meta($comment_ID, 'attachments', $attachments);
        }

        switch ($comment_approved) {
            case '0':
                wp_notify_moderator($comment_ID);

            case '1': //Approved comment
                $comment = get_comment($comment_ID);
                wp_notify_postauthor($comment_ID, $comment->comment_type);

                // Check if user is in a blacklist
                $list = new Blacklist();
                $isBanned = $list->isBanned($comment);
                $isBanned && wp_set_comment_status($comment_ID, 'spam');

                ob_start();
                $isAdmin ? self::renderComment($comment, [], 0, true) : self::renderComment($comment, [], 0);
                $response['success'] = true;
                $response['content'] = ob_get_clean();
                break;

            default:
                $response['success'] = false;
        }
        exit(json_encode($response));
    }

    public static function renderComment($comment, $args, $depth, $admin = false)
    {
        $subPath = $admin ? '/backend' : '';
        ob_start();
        include BMR_COMMENTS_PARTIALS . $subPath .  '/_single-comment.php';
        echo ob_get_clean();
    }

    public static function renderSpecialPartial($slug, $name = '')
    {
        $filePath = BMR_COMMENTS_PARTIALS . '/specials/_' . $slug . ($name ? '-' . $name : '') . '.php';
        if (!file_exists($filePath)) {
            return;
        }

        ob_start();
        include $filePath;
        echo ob_get_clean();
    }

    public function activation() {}

    public function deactivation() {}
}