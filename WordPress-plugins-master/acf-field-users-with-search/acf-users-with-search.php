<?php

/*
Plugin Name: Advanced Custom Fields: Users with search
Description: Users with search
Version: 1.0.0
Author: Alexandr Krupko, Slava Krasnyansky
Author URI: www.avrora.team
License: MIT
*/


add_action('acf/register_fields', function()
{
    include_once('acf-users-with-search-v4.php');
});