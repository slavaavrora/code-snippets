<?php

/*
Plugin Name: Advanced Custom Fields: Table of ACF fields
Description: Table of ACF fields
Version: 1.0.0
Author: Alexandr Krupko, Slava Krasnyansky
Author URI: www.avrora.team
License: MIT
*/


add_action('acf/register_fields', function()
{
    include_once('acf-table-fields-v4.php');
});