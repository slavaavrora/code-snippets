<?php
/**
 * Plugin Name: PPS Updater
 * Description: Updates PPS list from original site
 * Version: 1.0.0
 * Author: Alexandr Krupko, Slava Krasnyansky
 * Author URI: www.avrora.team
 * License: MIT
 */

if (!defined('WPINC')) {
    die;
}

if (!is_admin()) {
    return;
}


define('PPS_UPDATER_VERSION', '1.0');
define('PPS_UPDATER_ROOT', __DIR__);
define('PPS_UPDATER_TEMPLATES', PPS_UPDATER_ROOT . '/templates');
define('PPS_UPDATER_BOOKMAKERS', PPS_UPDATER_ROOT . '/Bookmakers');
//define('PPS_UPDATER_UPLOADS', PPS_UPDATER_ROOT . '/uploads');
//define('PPS_UPDATER_READER', PPS_UPDATER_ROOT . '/spreadsheet-reader-master');
define('PPS_UPDATER_ASSETS_URI', plugin_dir_url(__FILE__) . 'assets');


/**
 * PSR-4 Autoloader

 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function($class)
{
    $prefix = 'PpsUpdater\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        spl_autoload($class);
        return;
    }

    $file = PPS_UPDATER_ROOT . DIRECTORY_SEPARATOR . str_replace('\\', '/', substr($class, $len)) . '.php';
    is_file($file) && require $file;
});

PpsUpdater\Core::init();