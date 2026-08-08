<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OP_CB_Activator Class
 * Runs on plugin activation to initialize DB table & plugin secret options
 */
class OP_CB_Activator {

    /**
     * Main activation method
     */
    public static function activate() {
        self::create_tables();
        self::initialize_options();
        self::run_migrations();
        flush_rewrite_rules();
    }

    /**
     * Create custom DB table wp_op_cb_landings
     */
    private static function create_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'op_cb_landings';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            token varchar(64) NOT NULL,
            allowed_origins text DEFAULT NULL,
            assigned_products text DEFAULT NULL,
            shipping_options text DEFAULT NULL,
            thank_you_url varchar(500) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            orders_count bigint(20) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY token (token),
            KEY status (status)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('op_cb_db_version', OP_CB_VERSION);
    }

    /**
     * Initialize secret key option for HMAC token signing if missing
     */
    private static function initialize_options() {
        if (!get_option('op_cb_secret_key')) {
            $secret_key = wp_generate_password(64, true, true);
            update_option('op_cb_secret_key', $secret_key);
        }
    }

    /**
     * Run database migrations for version upgrades
     *
     * Compares stored op_cb_db_version against current OP_CB_VERSION
     * and applies incremental schema changes as needed.
     */
    private static function run_migrations() {
        $installed_version = get_option('op_cb_db_version', '0.0.0');

        if (version_compare($installed_version, OP_CB_VERSION, '>=')) {
            return; // Already up to date
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'op_cb_landings';

        // Future migration examples — add new version blocks here:
        //
        // if (version_compare($installed_version, '1.1.0', '<')) {
        //     $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN new_column varchar(255) DEFAULT NULL");
        // }
        //
        // if (version_compare($installed_version, '1.2.0', '<')) {
        //     $wpdb->query("ALTER TABLE {$table_name} ADD INDEX new_index (new_column)");
        // }

        // Always re-run dbDelta to ensure schema matches latest definition
        self::create_tables();

        // Bump stored version to current
        update_option('op_cb_db_version', OP_CB_VERSION);
    }
}
