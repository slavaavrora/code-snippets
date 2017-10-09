<?php

namespace Bmr\Sync;

class Base
{
    protected $pages;
    protected $actions;
    protected $postTypes;

    public function __construct()
    {
        $this->postTypes = ['bookreviews', 'acf'];
    }

    public function init()
    {
        if (get_current_blog_id() == 1) {
            return;
        }

        $this->actions = [
            'sync-post' => [$this, 'actionSyncPost']
        ];
        add_action('admin_menu', [$this, 'registerMenu'], 12, 0);
        add_filter('set-screen-option', [$this, 'setScreenOption'], 10, 3);
        add_action('admin_enqueue_scripts', [$this, 'loadStyles'], 11);
        add_action('admin_enqueue_scripts', [$this, 'loadScripts'], 11);

        foreach($this->actions as $action => $handler) {
            add_action('wp_ajax_' . $action, $handler);
        }
    }

    public function actionSyncPost()
    {
        $response = [
            'success' => false,
            'error'   => ''
        ];

        $postId   = (int)$_GET['post_id'];
        $postType = $_GET['post_type'];
        $transfer = filter_var($_GET['transfer'], FILTER_VALIDATE_BOOLEAN);

        switch($postType) {
            case 'bookreviews':
                $syncer = $transfer ? new Posts($postType) : new Bookmakers();
                break;
            case 'acf':
                $syncer = new Acf();
                break;
            default:
                $syncer = new Posts($postType);
                break;
        }

        $result = $syncer->sync($postId);

        if (is_wp_error($result)) {
            $response['error'] = $result->get_error_message();
        } else {
            $response['success'] = true;
        }

        die(json_encode($response));
    }

    public function registerMenu()
    {
        foreach ($this->postTypes as $postType) {
            $page = add_submenu_page(
                'edit.php?post_type=' . $postType,
                __('Список записей требующих синхронизации', 'bmr'),
                __('Синхронизация', 'bmr'),
                'manage_network',
                BMT_SYNC_SLUG . '-' . $postType,
                [$this, 'renderPage']
            );
            add_action("load-{$page}", [$this, 'addScreenOptions']);
            $this->pages[] = $page;
        }
    }

    public function addScreenOptions()
    {
        $option = 'per_page';
        $args   = [
            'label'   => __('Кол-во записей', 'bmr'),
            'default' => 20,
            'option'  => 'posts_per_page'
        ];
        add_screen_option($option, $args);
    }

    public function setScreenOption($status, $option, $value)
    {
        return $value;
    }

    public function renderPage()
    {
        include_once BMR_SYNC_PARTIALS . DIRECTORY_SEPARATOR . 'sync-list.php';
    }

    public function loadStyles($page)
    {
        if (!in_array($page, $this->pages, true)) {
            return;
        }
    }

    public function loadScripts($page)
    {
        if (!in_array($page, $this->pages, true)) {
            return;
        }
        wp_enqueue_script(
            BMT_SYNC_SLUG . '-main',
            BMR_SYNC_ASSETS_URI . '/js/sync.js',
            [],
            BMR_SYNC_VERSION,
            true
        );

        wp_localize_script(
            BMT_SYNC_SLUG . '-main',
            'bmrSync',
            [
                'status' => [
                    'error'      => __('Произошла ошибка', 'bmr'),
                    'processing' => __('Идет синхронизация', 'bmr'),
                ]
            ]
        );
    }

    public static function activate() {}
    public static function deactivate() {}
}
