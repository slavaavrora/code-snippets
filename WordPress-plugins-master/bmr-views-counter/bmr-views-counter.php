<?php
/*
Plugin Name: Bookmakers Views Counter
Description: Count page views
Version: 1.0
Author: Alexandr Krupko, Slava Krasnyansky
Author URI: www.avrora.team
License: MIT
License URI:
*/

namespace Bmr\ViewsCounter;

if (!defined('WPINC')) {
    die;
}

class ViewsCounter
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts'], 999);
    }

    public function enqueueScripts()
    {
        wp_enqueue_script('views-counter', plugins_url( '/js/views-counter.js', __FILE__ ), [], false, true);
        wp_localize_script('views-counter', 'bmrViewsCounter',  ['blog_id' => get_current_blog_id()]);
    }

    public static function bump($postId, $blogId, $count)
    {
        $mysqli = new \mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        if ($mysqli->connect_errno) {
            return;
        }

        $blogId = (int)$blogId;
        $postId = (int)$postId;
        $prefix = 'wp_' . ($blogId == 1 ? '' : $blogId . '_');
        $table  = "{$prefix}postmeta";
        $count  = (int)$count;

        $data = $mysqli->query("
            SELECT meta_id, meta_value
            FROM $table
            WHERE post_id = {$postId} AND meta_key = 'views'"
        );

        if (!$data->num_rows) {
            $mysqli->query("
                INSERT INTO $table (`post_id`, `meta_key`, `meta_value`)
                VALUES ($postId, 'views', $count)
            ");
        } else {
            $data      = $data->fetch_assoc();
            $metaId    = $data['meta_id'];
            $metaValue = $data['meta_value'];

            $mysqli->query('
                UPDATE ' . $table . '
                SET meta_value = ' . ($metaValue + $count) . '
                WHERE meta_id = ' . $metaId
            );
        }
    }
}

add_action('plugins_loaded', function() {
    new ViewsCounter();
});
