<?php
/**
 * @package   Bmr_Sync
 * @author  Alexander Krupko, Slava Krasnyansky
 * @license MIT
 * @link    www.avrora.team
 *
 * @wordpress-plugin
 *
 * Plugin Name: Bookmakers Sync Between Blogs
 * Plugin URI:
 * Description:
 * Version:     1.0.0
 * Author:      Alexander Krupko, Slava Krasnyansky
 * Author URI:  www.avrora.team
 * License:     MIT
 */

if (!defined('WPINC')) {
    die;
}

if (!is_admin() || get_current_blog_id() === 1) {
    return;
}

define('BMR_SYNC_VERSION', '1.10091406');
define('BMT_SYNC_SLUG', 'bmr-sync');
define('BMR_SYNC_ROOT', __DIR__);
define('BMR_SYNC_SRC', __DIR__ . '/src');
define('BMR_SYNC_PARTIALS', __DIR__ . '/src/partials');
define('BMR_SYNC_ASSETS', __DIR__ . '/src/assets');
define('BMR_SYNC_ASSETS_URI', plugin_dir_url(__FILE__) . 'src/assets');

/**
 * PSR-4 Autoloader

 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class) {

    $prefix = 'Bmr\\Sync\\';
    $base_dir = BMR_SYNC_SRC . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        spl_autoload($class);
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

register_activation_hook(__FILE__, ['\\Bmr\\Sync\\Base', 'activate']);
register_deactivation_hook(__FILE__, ['\\Bmr\\Sync\\Base', 'deactivate']);

add_action('plugins_loaded', function() {
    $core = new \Bmr\Sync\Base();
    $core->init();
});