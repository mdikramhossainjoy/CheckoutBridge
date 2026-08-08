<?php
/**
 * Plugin Name: CheckoutBridge
 * Plugin URI: https://github.com/checkoutbridge/checkoutbridge
 * Description: Secure bridge connecting WooCommerce with external custom landing pages for automated COD order creation and order details management.
 * Version: 1.0.0
 * Author: CheckoutBridge Team
 * Author URI: https://checkoutbridge.com
 * Text Domain: checkoutbridge
 * Domain Path: /languages
 * WC requires at least: 5.0
 * WC tested up to: 9.0
 * Requires PHP: 7.4
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define Plugin Constants with OP_CB_ prefix
define('OP_CB_VERSION', '1.0.0');
define('OP_CB_FILE', __FILE__);
define('OP_CB_PATH', plugin_dir_path(__FILE__));
define('OP_CB_URL', plugin_dir_url(__FILE__));

// Require Core Classes
require_once OP_CB_PATH . 'includes/class-op-cb-activator.php';
require_once OP_CB_PATH . 'includes/class-op-cb-deactivator.php';
require_once OP_CB_PATH . 'includes/class-op-cb-security.php';
require_once OP_CB_PATH . 'includes/class-op-cb-bridge-repository.php';
require_once OP_CB_PATH . 'includes/class-op-cb-order-engine.php';
require_once OP_CB_PATH . 'includes/api/class-op-cb-rest-controller.php';
require_once OP_CB_PATH . 'includes/admin/class-op-cb-admin.php';

/**
 * Activation & Deactivation Hooks
 */
register_activation_hook(__FILE__, array('OP_CB_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('OP_CB_Deactivator', 'deactivate'));

/**
 * Declare WooCommerce High-Performance Order Storage (HPOS) Compatibility
 */
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_orders_table', OP_CB_FILE, true);
    }
});

/**
 * Main Plugin Initialization Class
 */
final class OP_CB_Plugin {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Initialize plugin functionality after all plugins are loaded
     */
    public function init() {
        // WooCommerce Dependency Check
        if (!self::is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Initialize Core Components
        OP_CB_Security::init();
        OP_CB_REST_Controller::init();

        if (is_admin()) {
            OP_CB_Admin::init();
        }
    }

    /**
     * Check if WooCommerce plugin is active
     */
    public static function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }

    /**
     * Admin notice if WooCommerce is missing or inactive
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error is-dismissible op-cb-notice">
            <p>
                <strong><?php esc_html_e('CheckoutBridge Warning:', 'checkoutbridge'); ?></strong> 
                <?php esc_html_e('WooCommerce is required for CheckoutBridge to function properly. Please install and activate WooCommerce.', 'checkoutbridge'); ?>
            </p>
        </div>
        <?php
    }
}

/**
 * Instantiate CheckoutBridge Plugin
 */
OP_CB_Plugin::get_instance();
