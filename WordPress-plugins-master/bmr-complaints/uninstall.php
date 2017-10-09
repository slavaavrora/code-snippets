<?php
//if uninstall not called from WordPress exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Deleting all options
global $wpdb;
//$wpdb->query("DROP TABLE IF EXISTS {$wpdb->bmr_complaint_commentmeta}");
//$wpdb->query("DROP TABLE IF EXISTS {$wpdb->bmr_complaint_comments}");