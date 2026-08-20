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
$op_cb_table_name = $wpdb->prefix . 'op_cb_landings';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query("DROP TABLE IF EXISTS {$op_cb_table_name}");

// Delete plugin options
delete_option('op_cb_db_version');
delete_option('op_cb_secret_key');

// Clear any transients related to rate limiting
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_op_cb_%'");
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_op_cb_%'");
