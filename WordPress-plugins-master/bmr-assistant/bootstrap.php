<?php
/**
 * @package   Bmr_Assistant
 * @author  Alexander Krupko, Slava Krasnyansky
 * @license MIT
 * @link    www.avrora.team
 *
 * @wordpress-plugin
 *
 * Plugin Name: Bookmakers Assistant
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

define('BMR_ASSISTANT_VERSION', '1.0.1');
define('BMT_ASSISTANT_SLUG', 'bmr-assistant');
define('BMR_ASSISTANT_ROOT', __DIR__);
define('BMR_ASSISTANT_SRC', __DIR__ . '/src');
define('BMR_ASSISTANT_TEMPLATES', __DIR__ . '/src/templates');
define('BMR_ASSISTANT_PARTIALS', __DIR__ . '/src/templates/partials');
define('BMR_ASSISTANT_ASSETS', __DIR__ . '/src/assets');
define('BMR_ASSISTANT_ASSETS_URI', plugin_dir_url(__FILE__) . 'src/assets');

/**
 * PSR-4 Autoloader

 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class) {

    $prefix = 'Bmr\\Assistant\\';
    $base_dir = BMR_ASSISTANT_SRC . DIRECTORY_SEPARATOR;

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

register_activation_hook(__FILE__, ['\\Bmr\\Assistant\\Base', 'activate']);
register_deactivation_hook(__FILE__, ['\\Bmr\\Assistant\\Base', 'deactivate']);

add_action('plugins_loaded', function() {
    $core = new \Bmr\Assistant\Base();
    $core->init();
});