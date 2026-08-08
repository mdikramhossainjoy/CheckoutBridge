<?php
/**
 * CheckoutBridge Uninstall
 *
 * Cleans up all plugin data when the plugin is deleted via WordPress admin.
 *
 * @package CheckoutBridge
 */

// Exit if not called from WordPress uninstall process
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Drop custom database table
global $wpdb;
$table_name = $wpdb->prefix . 'op_cb_landings';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Delete plugin options
delete_option('op_cb_db_version');
delete_option('op_cb_secret_key');

// Clear any transients related to rate limiting
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_op_cb_rl_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_op_cb_rl_%'");
