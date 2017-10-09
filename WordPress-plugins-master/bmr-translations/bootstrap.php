<?php
/**
 * @package Bmr_Translations
 * @author  Alexander Krupko, Slava Krasnyansky
 * @license MIT
 * @link    www.avrora.team
 *
 * @wordpress-plugin
 *
 * Plugin Name: Bookmakers Translations
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

if (!is_admin()) {
    return;
}

define('BMR_TRANSLATIONS_VERSION', '1.0.1');
define('BMR_TRANSLATIONS_ROOT', __DIR__);
define('BMR_TRANSLATIONS_SRC', __DIR__ . '/src');
define('BMR_TRANSLATIONS_TEMPLATES', BMR_TRANSLATIONS_SRC . '/templates');
define('BMR_TRANSLATIONS_PARTIALS', BMR_TRANSLATIONS_TEMPLATES . '/partials');
define('BMR_TRANSLATIONS_ASSETS', __DIR__ . '/assets');
define('BMR_TRANSLATIONS_ASSETS_URI', plugin_dir_url(__FILE__) . 'src/assets');
define('BMR_TRANSLATIONS_THEME_LANG_DIR', get_template_directory() . '/languages');

/**
 * PSR-4 Autoloader

 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class) {

    $prefix = 'Bmr\\Translations\\';
    $base_dir = BMR_TRANSLATIONS_SRC . DIRECTORY_SEPARATOR;

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
    $core = new \Bmr\Translations\Base();
    $core->init();
});

register_activation_hook(__FILE__, ['\\Bmr\\Translations\\Base', 'activate']);
register_deactivation_hook(__FILE__, ['\\Bmr\\Translations\\Base', 'deactivate']);