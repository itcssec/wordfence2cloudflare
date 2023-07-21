<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;
$table_name = $wpdb->prefix . 'wtc_blocked_ips'; // replace with your table name

$wpdb->query("DROP TABLE IF EXISTS $table_name");
