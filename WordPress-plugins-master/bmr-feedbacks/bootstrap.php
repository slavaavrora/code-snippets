<?php

/*
Plugin Name: BMR Feedbacks
Description:
Version: 1.0.0
Author: Alexandr Krupko, Slava Krasnyansky
Author URI: www.avrora.team
License: MIT
License URI:
*/

define('BMR_FEEDBACKS_VERSION', '1.0');

add_action('plugins_loaded', function()
{
    require_once __DIR__ . '/BmrFeedbacks.class.php';

    BmrFeedbacks::init();
});