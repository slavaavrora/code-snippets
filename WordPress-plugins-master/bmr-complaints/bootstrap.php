<?php
/**
 * @package Bmr_Complaints
 * @author  Alexander Krupko, Slava Krasnyansky
 * @license MIT
 * @link    www.avrora.team
 *
 * @wordpress-plugin
 *
 * Plugin Name: Complaints System
 * Plugin URI:
 * Description:
 * Version:     1.0.0
 * Author:      Alexander Krupko, Slava Krasnyansky
 * Author URI:  www.avrora.team
 * License:     MIT
 */

require_once 'vendor/autoload_52.php';

if (!defined('WPINC')) {
    die;
}

!defined('DS') && define('DS', DIRECTORY_SEPARATOR);

!defined('BMR_PLUGIN_VERSION') && define('BMR_PLUGIN_VERSION', '05011736');
!defined('BMR_PLUGIN_SLUG') && define('BMR_PLUGIN_SLUG', 'bmr');
!defined('BMR_PLUGIN_DIR') && define('BMR_PLUGIN_DIR', dirname(__FILE__));
!defined('BMR_PLUGIN_SRC_DIR') && define('BMR_PLUGIN_SRC_DIR', BMR_PLUGIN_DIR . DS . 'src' . DS . 'BmrComplaints');
!defined('BMR_PLUGIN_TEMPLATE_DIR') && define('BMR_PLUGIN_TEMPLATE_DIR', BMR_PLUGIN_SRC_DIR . DS . 'templates');
!defined('BMR_DEBUG') && define('BMR_DEBUG', false);

register_activation_hook(__FILE__, array('BmrCore', 'activate'));
register_deactivation_hook(__FILE__, array('BmrCore', 'deactivate'));
add_action('plugins_loaded', array('BmrCore', 'init'));
