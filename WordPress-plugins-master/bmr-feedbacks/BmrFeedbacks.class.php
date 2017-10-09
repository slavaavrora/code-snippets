<?php

abstract class BmrFeedbacks
{
    const POST_TYPE = 'feedbacks';
    const TYPE_TAXONOMY = 'feedback_type';
    const ITEMS_PER_PAGE = 10;
    const ALL_FEEDBACKS_SLUG = 'all-feedbacks';

    public static function init()
    {
        static $isCalled = false;

        if ($isCalled) {
            return;
        }
        $isCalled = true;

        if (!function_exists('get_field')) {
            die('Function get_field doesn\'t exist');
        }

        add_action('init', function()
        {
            register_post_type(self::POST_TYPE, [
                'labels'        => [
                    'name'               => __('Отзывы', 'bmr'),
                    'singular_name'      => __('Отзыв', 'bmr'),
                    'add_new'            => __('Добавить отзыв', 'bmr'),
                    'add_new_item'       => __('Добавить отзыв', 'bmr'),
                    'edit_item'          => __('Редактировать', 'bmr'),
                    'new_item'           => __('Новый отзыв', 'bmr'),
                    'view_item'          => __('Просмотр', 'bmr'),
                    'search_items'       => __('Поиск отзывов', 'bmr'),
                    'not_found'          => __('Отзывы не найдены', 'bmr'),
                    'not_found_in_trash' => __('Отзывы не найдены в корзине', 'bmr'),
                    'parent_item_colon'  => '',
                ],
                'public'        => true,
                'has_archive'   => false,
                'supports'      => ['title', 'editor', 'author', 'revisions'],
                'rewrite'       => ['slug' => 'feedbacks'],
            ]);

            register_taxonomy(self::TYPE_TAXONOMY, self::POST_TYPE, [
                'hierarchical'      => false,
                'public'            => true,
                'show_ui'           => true,
                'label'             => __('Тип отзыва', 'bmr'),
                'sort'              => true,
                'args'              => ['orderby' => 'term_order'],
                'rewrite'           => ['slug' => 'feedback_type'],
                'query_var'         => 'feedback_type',
                'show_admin_column' => true,
            ]);

            add_rewrite_tag('%' . self::ALL_FEEDBACKS_SLUG . '%', '([^&]+)');

            global $wp_post_types;
            foreach ($wp_post_types as $postType => $data) {
                !empty($data->rewrite['slug']) && $data->rewrite['slug'] !== self::POST_TYPE && add_rewrite_rule(
                    ($data->rewrite['slug'] !== 'page' ? $data->rewrite['slug'] . '/' : '') . '(.+?)/' . self::ALL_FEEDBACKS_SLUG . '/?$',
                    'index.php?' . $postType . '=$matches[1]&' . self::ALL_FEEDBACKS_SLUG . '=1',
                    'top'
                );
            }

            add_rewrite_rule(
                '(.?.+?)(/[0-9]+)?/' . self::ALL_FEEDBACKS_SLUG . '/?$',
                'index.php?pagename=$matches[1]&page=$matches[2]&' . self::ALL_FEEDBACKS_SLUG . '=1',
                'top'
            );
        }, 999);

        add_action('wp_enqueue_scripts', function()
        {
            wp_enqueue_style('bmrReviews', plugins_url('/assets/css/style.css', __FILE__), [], BMR_FEEDBACKS_VERSION);
            wp_enqueue_script('bmrReviews', plugins_url('/assets/js/scripts.js', __FILE__), ['base-helpers'], BMR_FEEDBACKS_VERSION);
        }, 11);

        self::_registerAjaxAction('feedbacks_new', [__CLASS__, 'addNewFeedback']);
        self::_registerAjaxAction('feedbacks_like_dislike', [__CLASS__, 'addLikeDislike']);
        self::_registerAjaxAction('feedbacks_items_page', [__CLASS__, 'getItemsByPage']);

        add_filter('acf/update_value/name=feedbacks_page', function($value, $post_id, $field)
        {
            isset($value[0]) && $value = $value[0];
            update_post_meta($post_id, '_feedbacks_page_id', $value);

            return $value;
        }, 10, 3);
    }


    public static function getTotalPagesNum($postID, $perPage = self::ITEMS_PER_PAGE)
    {
        return ceil(self::getItemsNum($postID) /$perPage);
    }


    public static function getAllPageLink()
    {
        return substr_replace($_SERVER['REQUEST_URI'], '/' . self::ALL_FEEDBACKS_SLUG . '/', strrpos($_SERVER['REQUEST_URI'], '/'), 1);
    }


    public static function getItemsByPage()
    {
        if (!empty($_POST['page']) && !empty($_POST['postID'])) {
            self::getItems($_POST['postID'], $_POST['page'], self::ITEMS_PER_PAGE);
        }

        die;
    }


    public static function allFeedbacks(array $breadCrumbs = [])
    {
        if (get_query_var('all-feedbacks') != 1) {
            return;
        }

        ob_clean();
        $breadCrumbs[__('Все отзывы', 'bmr')] = '';
        require_once __DIR__ . '/templates/all.php';
        die;
    }


    public static function getForm($type, $withoutRating = false)
    {
        include_once __DIR__ . '/templates/form.php';
    }


    public static function getItemsNum($postID)
    {
        $feedbacks = get_posts([
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_key'       => '_feedbacks_page_id',
            'meta_value'     => $postID,
            'fields'         => 'ids'
        ]);

        return count($feedbacks);
    }


    public static function getItemsNumByRating($postID, $rating)
    {
        $feedbacks = get_posts([
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'   => '_feedbacks_page_id',
                    'value' => $postID,
                ],
                [
                    'key'   => 'feedbacks_rating',
                    'value' => $rating,
                ],
            ],
            'fields'         => 'ids'
        ]);

        return count($feedbacks);
    }


    public static function getItemsData($postID, $page = 1, $byPage = 3, array &$args = [])
    {
        $args += [
            'rating' => true,
            'title'  => true,
        ];

        return get_posts([
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => $byPage,
            'offset'         => $byPage * ($page - 1),
            'meta_key'       => '_feedbacks_page_id',
            'meta_value'     => $postID
        ]);
    }


    public static function getItems($postID, $page = 1, $byPage = 3, array $args = [])
    {
        $feedbacks = self::getItemsData($postID, $page, $byPage, $args);
        include_once __DIR__ . '/templates/list.php';
    }


    public static function addNewFeedback()
    {
        if (is_user_logged_in() && isset($_POST['comment'], $_POST['rating'], $_POST['postID'], $_POST['type'])) {
            $type = trim(strip_tags($_POST['type']));
            $comment = trim(strip_tags($_POST['comment']));
            $rating = (int) $_POST['rating'];
            $postID = (int) $_POST['postID'];

            if (!$comment || !$postID || ($rating !== -1 && ($rating < 1 || $rating > 5)) || !$type) {
                die(json_encode([
                    'success' => false,
                    'message' => $rating !== -1
                        ? __('Введены некорректные данные, Ваш отзыв не был добавлен!<br>Заполните все поля и выберите оценку.', 'bmr')
                        : __('Введены некорректные данные, Ваш отзыв не был добавлен!<br>Заполните все поля.', 'bmr')
                ]));
            }

            $allTerms = get_terms(self::TYPE_TAXONOMY, [
                'fields'     => 'id=>slug',
                'hide_empty' => false,
            ]);

            if (!in_array($type, $allTerms)) {
                die(json_encode([
                    'success' => false,
                    'message' => __('Неверный тип отзыва.', 'bmr'),
                ]));
            }

            $userName = self::getUserName();

            $months = [
                __('января', 'bmr'), __('февраля', 'bmr'), __('марта', 'bmr'), __('апреля', 'bmr'),
                __('мая', 'bmr'), __('июня', 'bmr'), __('июля', 'bmr'), __('августа', 'bmr'),
                __('сентября', 'bmr'), __('октября', 'bmr'), __('ноября', 'bmr'), __('декабря', 'bmr'),
            ];

            $date = get_current_blog_id() === BLOG_ID_BMR_EN
                ? date('Y, ') . $months[date('n') - 1] . date(' d')
                : date('d ') . $months[date('n') - 1] . date(' Y');
            /* translators: Пользователь Иван Иванов оставил(а) отзыв, 10 января 2015 */
            $title = sprintf(__('Пользователь %s оставил(а) отзыв, %s', 'bmr'), $userName, $date);

            $post = wp_insert_post([
                'post_title'   => $title,
                'post_content' => $comment,
                'post_type'    => self::POST_TYPE,
                'post_status'  => 'pending'
            ]);

            if ($post) {
                update_field('feedbacks_page', $postID, $post);
                update_post_meta($post, '_feedbacks_page_id', $postID);
                $rating !== -1 && update_field('feedbacks_rating', $rating, $post);

                wp_set_post_terms($post, $type, self::TYPE_TAXONOMY);

                $emails = get_field('feedbacks_emails', 'option');
                $originalPost = get_post($postID);

                if (is_array($emails) && $originalPost instanceof WP_Post) {
                    remove_all_filters('wp_mail_from');
                    remove_all_filters('wp_mail_from_name');

                    $headers = [
                        'Content-Type: text/html; charset=UTF-8',
                        sprintf('From: %s <%s>', get_bloginfo('name'), get_bloginfo('admin_email'))
                    ];
                    $message = 'Пользователь %s оставил <a href="%s" target="_blank">отзыв</a> к странице <a href="%s" target="_blank">%s</a><br>';
                    $message = sprintf($message, $userName, get_edit_post_link($post), get_permalink($originalPost), $originalPost->post_title) . $comment;

                    foreach ($emails as $to) {
                        @wp_mail($to['email'], $title, $message, $headers);
                    }
                }

                $data = [
                    'success' => true,
                    'message' => __('Ваш отзыв добавлен!', 'bmr'),
                ];
            } else {
                $data = [
                    'success' => false,
                    'message' => __('Произошла какая-то ошибка, Ваш отзыв не был добавлен!', 'bmr')
                ];
            }

            echo json_encode($data);
        }

        die;
    }


    public static function addLikeDislike()
    {
        $result = ['success' => false];
        $userID = get_current_user_id();

        if (isset($_POST['feedbackID'], $_POST['type']) && $userID) {
            $result['likes'] = get_post_meta($_POST['feedbackID'], 'feedbacks_likes', true) ?: 0;
            $result['dislikes'] = get_post_meta($_POST['feedbackID'], 'feedbacks_dislikes', true) ?: 0;
            $feedbackUsers = unserialize(get_post_meta($_POST['feedbackID'], '_feedback_users', true)) ?: [];
            $isLike = $_POST['type'] === 'like';

            if (!isset($feedbackUsers[$userID])) {
                $feedbackUsers[$userID] = $isLike ? 1 : -1;
                $result[$isLike ? 'likes' : 'dislikes'] += 1;
            } else if ($feedbackUsers[$userID] === ($isLike ? -1 : 1)) {
                $feedbackUsers[$userID] = $isLike ? 1 : -1;
                $result[$isLike ? 'likes' : 'dislikes'] += 1;
                $result[$isLike ? 'dislikes' : 'likes'] -= 1;
            } else {
                unset($feedbackUsers[$userID]);
                $result[$isLike ? 'likes' : 'dislikes'] -= 1;
            }

            update_post_meta($_POST['feedbackID'], '_feedback_users', serialize($feedbackUsers));
            update_post_meta($_POST['feedbackID'], 'feedbacks_likes', $result['likes']);
            update_post_meta($_POST['feedbackID'], 'feedbacks_dislikes', $result['dislikes']);

            $result['success'] = true;
        }

        die(json_encode($result));
    }


    public static function getUserAvatarUrl($userID)
    {
        $avatar = get_avatar($userID, 150);
        preg_match("/src=['\"](.*?)['\"]/i", $avatar, $matches);

        return self::_isDefaultAvatar($userID, $matches[1]) ? '' : $matches[1];
    }


    public static function getUserInitials($userID)
    {
        $name = explode(' ', self::getUserName($userID));

        return isset($name[1]) ? mb_substr($name[0], 0, 1) . mb_substr($name[1], 0, 1) : mb_substr($name[0], 0, 2);
    }


    private static function _isDefaultAvatar($userID, $avaURI = '')
    {
        $user = get_userdata((int) $userID);
        $email = $user ? $user->user_email : '';
        $hashkey = md5(strtolower(trim($email)));
        $uri = 'http://www.gravatar.com/avatar/' . $hashkey . '?d=404';

        $data = wp_cache_get($hashkey);
        if (false === $data) {
            $response = wp_remote_head($uri);
            $data = is_wp_error($response) ? 0 : $response['response']['code'];
            wp_cache_set($hashkey, $data);
        }

        return $data == 200 ? false : strpos($avaURI, 'gravatar.com/avatar') !== false;
    }


    public static function getUserName($userID = null)
    {
        $userID === null && $userID = get_current_user_id();
        $user = get_userdata($userID);
        $firstName = $user->first_name;
        $lastName = $user->last_name;

        return !empty($firstName) && !empty($lastName)
            ? $firstName . ' ' . $lastName
            : (!empty($firstName) ? $firstName : $user->get('display_name'));
    }

    private static function _registerAjaxAction($action, $handler)
    {
        add_action('wp_ajax_' . $action, $handler);
        add_action('wp_ajax_nopriv_' . $action, $handler);
    }
}