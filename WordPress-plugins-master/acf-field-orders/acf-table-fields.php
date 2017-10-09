<?php

/*
Plugin Name: Advanced Custom Fields: Field orders
Description: Field orders
Version: 1.0.0
Author: Alexandr Krupko, Slava Krasnyansky
Author URI: www.avrora.team
License: MIT
*/


add_action('acf/register_fields', function()
{
    include_once('acf-table-fields-v4.php');
});