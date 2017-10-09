<?php
/**
 * @package Bmr_Auth
 * @author  Alexander Krupko, Slava Krasnyansky
 * @license MIT
 * @link    www.avrora.team
 *
 * @wordpress-plugin
 *
 * Plugin Name: Bookmakers Authentification
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

!defined('BMR_AUTH_VERSION') && define('BMR_AUTH_VERSION', '1.10091603');
!defined('BMR_AUTH_ROOT') && define('BMR_AUTH_ROOT', __DIR__);
!defined('BMR_AUTH_SRC') && define('BMR_AUTH_SRC', __DIR__ . '/src');
!defined('BMR_AUTH_PARTIALS') && define('BMR_AUTH_PARTIALS', __DIR__ . '/src/partials');
!defined('BMR_AUTH_ASSETS') && define('BMR_AUTH_ASSETS', __DIR__ . '/src/assets');

/**
 * PSR-4 Autoloader

 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class) {

    $prefix = 'Bmr\\Auth\\';
    $base_dir = BMR_AUTH_SRC . DIRECTORY_SEPARATOR;

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

add_action('plugins_loaded', function() {
    $core = new \Bmr\Auth\Base();
    $core->init();
    register_activation_hook(__FILE__, array($core, 'activate'));
    register_deactivation_hook(__FILE__, array($core, 'deactivate'));
});