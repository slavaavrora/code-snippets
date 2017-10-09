<?php
/**
 * @package Bmr_Comments
 * @author  Alexander Krupko, Slava Krasnyansky
 * @license MIT
 * @link    www.avrora.team
 *
 * @wordpress-plugin
 *
 * Plugin Name: Bookmakers Comments System
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

define('BMR_COMMENTS_VERSION', '1.0.0');
define('BMR_COMMENTS_SLUG', 'bmr-comments');
define('BMR_COMMENTS_ROOT', __DIR__);
define('BMR_COMMENTS_SRC', __DIR__ . '/src');
define('BMR_COMMENTS_PARTIALS', __DIR__ . '/src/partials');
define('BMR_COMMENTS_ASSETS', __DIR__ . '/src/assets');
define('BMR_COMMENTS_ASSETS_URI', plugin_dir_url(__FILE__) . 'src/assets');

/**
 * PSR-4 Autoloader

 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class)
{
    $prefix = 'Bmr\\Comments\\';
    $baseDir = BMR_COMMENTS_SRC . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        spl_autoload($class);
        return;
    }

    $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
    file_exists($file) && require $file;
});

register_activation_hook(__FILE__, ['\\Bmr\\Comments\\Base', 'activation']);
register_deactivation_hook(__FILE__, ['\\Bmr\\Comments\\Base', 'deactivation']);
add_action('plugins_loaded', ['\\Bmr\\Comments\\Base', 'init']);