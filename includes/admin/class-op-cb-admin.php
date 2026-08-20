<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OP_CB_Admin Class
 * Manages admin menus, assets, and views
 */
class OP_CB_Admin {

    /**
     * Hook admin actions
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('admin_init', array(__CLASS__, 'handle_actions'));
        add_action('admin_post_op_cb_save_bridge', array(__CLASS__, 'handle_save_landing'));
        add_action('admin_post_op_cb_save_landing', array(__CLASS__, 'handle_save_landing'));
        add_action('add_meta_boxes', array(__CLASS__, 'register_order_meta_boxes'));
        add_action('save_post_product', function() {
            delete_transient('op_cb_products_cache');
            delete_transient('op_cb_products_light_cache');
        });
    }

    /**
     * Register WordPress admin menu & submenus
     */
    public static function register_admin_menu() {
        $parent_slug = 'checkoutbridge';

        add_menu_page(
            __('CheckoutBridge', 'op-checkoutbridge'),
            __('CheckoutBridge', 'op-checkoutbridge'),
            'manage_options',
            $parent_slug,
            array(__CLASS__, 'render_dashboard_page'),
            'dashicons-randomize',
            56
        );

        add_submenu_page(
            $parent_slug,
            __('Dashboard', 'op-checkoutbridge'),
            __('Dashboard', 'op-checkoutbridge'),
            'manage_options',
            $parent_slug,
            array(__CLASS__, 'render_dashboard_page')
        );

        add_submenu_page(
            $parent_slug,
            __('Bridges Manager', 'op-checkoutbridge'),
            __('Bridges Manager', 'op-checkoutbridge'),
            'manage_options',
            'checkoutbridge-bridges',
            array(__CLASS__, 'render_landings_page')
        );

        add_submenu_page(
            $parent_slug,
            __('Documentation & Integration', 'op-checkoutbridge'),
            __('Documentation', 'op-checkoutbridge'),
            'manage_options',
            'checkoutbridge-docs',
            array(__CLASS__, 'render_documentation_page')
        );
    }

    /**
     * Enqueue Admin Styles & Scripts (Zero Remote CDN Requests - 100% Local)
     */
    public static function enqueue_assets($hook) {
        // Enqueue only on CheckoutBridge pages or WooCommerce Order screens
        $is_cb_page = (strpos($hook, 'checkoutbridge') !== false);
        $is_wc_order = ($hook === 'post.php' || $hook === 'post-new.php' || strpos($hook, 'woocommerce_page_wc-orders') !== false);

        if (!$is_cb_page && !$is_wc_order) {
            return;
        }

        // 1. Local Space Grotesk Typography Font
        wp_enqueue_style(
            'op-cb-fonts',
            OP_CB_URL . 'assets/vendor/fonts/space-grotesk.css',
            array(),
            OP_CB_VERSION
        );

        // 2. Local FontAwesome Icons
        wp_enqueue_style(
            'op-cb-fontawesome',
            OP_CB_URL . 'assets/vendor/fontawesome/css/all.min.css',
            array(),
            OP_CB_VERSION
        );

        // 3. Local Nice-Select Form Dropdowns
        wp_enqueue_style(
            'op-cb-nice-select-css',
            OP_CB_URL . 'assets/vendor/nice-select/nice-select.min.css',
            array(),
            OP_CB_VERSION
        );

        wp_enqueue_script(
            'op-cb-nice-select-js',
            OP_CB_URL . 'assets/vendor/nice-select/jquery.nice-select.min.js',
            array('jquery'),
            OP_CB_VERSION,
            true
        );

        // 4. CheckoutBridge Core Production Admin Stylesheet
        wp_enqueue_style(
            'op-cb-admin-css',
            OP_CB_URL . 'assets/css/admin.css',
            array('op-cb-fonts', 'op-cb-fontawesome', 'op-cb-nice-select-css'),
            OP_CB_VERSION
        );

        // 5. CheckoutBridge Core Production Admin JavaScript
        wp_enqueue_script(
            'op-cb-admin-js',
            OP_CB_URL . 'assets/js/admin.js',
            array('jquery', 'op-cb-nice-select-js'),
            OP_CB_VERSION,
            true
        );

        // Retrieve flash messages from transient if present
        $user_id = get_current_user_id();
        $flash   = get_transient('op_cb_flash_' . $user_id);
        if ($flash) {
            delete_transient('op_cb_flash_' . $user_id);
        }

        wp_localize_script('op-cb-admin-js', 'op_cb_vars', array(
            'rest_url' => esc_url_raw(rest_url('checkoutbridge/v1/')),
            'flash'    => $flash ? $flash : null,
            'i18n'     => array(
                'confirm_delete' => __('Are you sure you want to delete this bridge campaign config?', 'op-checkoutbridge'),
                'confirm_revoke' => __('Are you sure you want to revoke and regenerate this token? Any external landing pages using the old token will immediately lose access.', 'op-checkoutbridge'),
                'copied'         => __('Copied to clipboard!', 'op-checkoutbridge'),
                'selected'       => __('selected', 'op-checkoutbridge'),
                'created_msg'    => __('Bridge campaign created successfully.', 'op-checkoutbridge'),
                'updated_msg'    => __('Bridge campaign updated successfully.', 'op-checkoutbridge'),
                'deleted_msg'    => __('Bridge campaign deleted successfully.', 'op-checkoutbridge'),
                'revoked_msg'    => __('Bridge token key revoked & regenerated successfully.', 'op-checkoutbridge'),
            )
        ));
    }

    /**
     * Handle admin GET actions scoped to CheckoutBridge pages only
     */
    public static function handle_actions() {
        // Only run on CheckoutBridge admin pages
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        if (strpos($page, 'checkoutbridge') === false) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $user_id = get_current_user_id();

        // Action: Delete Bridge
        if (isset($_GET['action']) && ($_GET['action'] === 'delete_bridge' || $_GET['action'] === 'delete_landing') && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])), 'op_cb_delete_bridge_' . $id)) {
                wp_die(esc_html__('Security check failed.', 'op-checkoutbridge'), 403);
            }

            OP_CB_Bridge_Repository::delete($id);
            set_transient('op_cb_flash_' . $user_id, array('message' => __('Bridge campaign deleted successfully.', 'op-checkoutbridge'), 'type' => 'info'), 60);
            wp_safe_redirect(admin_url('admin.php?page=checkoutbridge-bridges'));
            exit;
        }

        // Action: Revoke & Regenerate Token
        if (isset($_GET['action']) && $_GET['action'] === 'revoke_token' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])), 'op_cb_revoke_token_' . $id)) {
                wp_die(esc_html__('Security check failed.', 'op-checkoutbridge'), 403);
            }

            OP_CB_Bridge_Repository::regenerate_token($id);
            set_transient('op_cb_flash_' . $user_id, array('message' => __('Bridge token key revoked & regenerated successfully.', 'op-checkoutbridge'), 'type' => 'success'), 60);
            wp_safe_redirect(admin_url('admin.php?page=checkoutbridge-bridges'));
            exit;
        }
    }

    /**
     * Handle admin_post save bridge form submission
     */
    public static function handle_save_landing() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'op-checkoutbridge'), 403);
        }

        $nonce = isset($_POST['op_cb_nonce']) ? sanitize_text_field(wp_unslash($_POST['op_cb_nonce'])) : (isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '');
        if (!wp_verify_nonce($nonce, 'op_cb_save_bridge') && !wp_verify_nonce($nonce, 'op_cb_save_bridge_nonce')) {
            wp_die(esc_html__('Security check failed.', 'op-checkoutbridge'), 403);
        }

        $user_id    = get_current_user_id();
        $landing_id = !empty($_POST['bridge_id']) ? intval($_POST['bridge_id']) : (!empty($_POST['landing_id']) ? intval($_POST['landing_id']) : 0);
        $name       = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $token      = !empty($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : OP_CB_Bridge_Repository::generate_token();
        $origins    = isset($_POST['allowed_origins']) ? sanitize_textarea_field(wp_unslash($_POST['allowed_origins'])) : '';
        $thank_you  = isset($_POST['thank_you_url']) ? esc_url_raw(wp_unslash($_POST['thank_you_url'])) : '';
        $raw_status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : 'active';
        $status     = in_array($raw_status, array('active', 'inactive'), true) ? $raw_status : 'active';

        // Assigned products array
        $assigned_products = isset($_POST['assigned_products']) && is_array($_POST['assigned_products'])
            ? array_map('intval', wp_unslash($_POST['assigned_products']))
            : array();

        // Retrieve existing bridge data to preserve shipping_options array if present
        $existing_shipping = array();
        if ($landing_id > 0) {
            $existing_landing = OP_CB_Bridge_Repository::get_by_id($landing_id);
            if (!empty($existing_landing['shipping_options']) && is_array($existing_landing['shipping_options'])) {
                $existing_shipping = $existing_landing['shipping_options'];
            }
        }

        $phone_velocity_limit = isset($_POST['phone_velocity_limit']) ? intval($_POST['phone_velocity_limit']) : 1;
        $ip_velocity_limit    = isset($_POST['ip_velocity_limit']) ? intval($_POST['ip_velocity_limit']) : 3;
        $velocity_hours       = isset($_POST['velocity_hours']) ? intval($_POST['velocity_hours']) : 24;
        $enable_autofill      = isset($_POST['enable_autofill_lookup']) ? 1 : 0;

        $data = array(
            'name'                   => $name,
            'token'                  => $token,
            'allowed_origins'        => $origins,
            'assigned_products'      => $assigned_products,
            'shipping_options'       => $existing_shipping,
            'phone_velocity_limit'   => $phone_velocity_limit,
            'ip_velocity_limit'      => $ip_velocity_limit,
            'velocity_hours'         => $velocity_hours,
            'enable_autofill_lookup' => $enable_autofill,
            'thank_you_url'          => $thank_you,
            'status'                 => $status
        );

        if ($landing_id > 0) {
            OP_CB_Bridge_Repository::update($landing_id, $data);
            set_transient('op_cb_flash_' . $user_id, array('message' => __('Bridge campaign updated successfully.', 'op-checkoutbridge'), 'type' => 'success'), 60);
        } else {
            OP_CB_Bridge_Repository::create($data);
            set_transient('op_cb_flash_' . $user_id, array('message' => __('Bridge campaign created successfully.', 'op-checkoutbridge'), 'type' => 'success'), 60);
        }

        wp_safe_redirect(admin_url('admin.php?page=checkoutbridge-bridges'));
        exit;
    }

    /**
     * Render Module 2: Dashboard View
     */
    public static function render_dashboard_page() {
        require_once OP_CB_PATH . 'includes/admin/views/dashboard.php';
    }

    /**
     * Render Bridges List & Edit View
     */
    public static function render_landings_page() {
        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : 'list';
        if ($action === 'add' || $action === 'edit') {
            require_once OP_CB_PATH . 'includes/admin/views/bridge-form.php';
        } else {
            require_once OP_CB_PATH . 'includes/admin/views/bridges-list.php';
        }
    }

    /**
     * Render Module 14: Documentation View
     */
    public static function render_documentation_page() {
        require_once OP_CB_PATH . 'includes/admin/views/documentation.php';
    }

    /**
     * Register Meta (Facebook) CAPI Order Details Meta Box in WooCommerce Admin Order View
     */
    public static function register_order_meta_boxes() {
        $screen = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') && wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';

        add_meta_box(
            'op_cb_order_capi_box',
            __('CAPI Details', 'op-checkoutbridge'),
            array(__CLASS__, 'render_order_meta_box'),
            $screen,
            'side',
            'default'
        );
    }

    /**
     * Render Meta (Facebook) CAPI Details Meta Box
     */
    public static function render_order_meta_box($post_or_order_object) {
        $order = ($post_or_order_object instanceof \WC_Order) ? $post_or_order_object : wc_get_order($post_or_order_object->ID);
        if (!$order) {
            return;
        }

        $bridge_name  = $order->get_meta('_op_cb_bridge_name');
        $landing_name = !empty($bridge_name) ? $bridge_name : $order->get_meta('_op_cb_landing_name');
        $fbp          = $order->get_meta('_op_cb_fbp');
        $fbc          = $order->get_meta('_op_cb_fbc');
        $event_id     = $order->get_meta('_op_cb_event_id');
        $client_ip    = $order->get_meta('_op_cb_client_ip');

        if (empty($landing_name) && empty($event_id)) {
            echo '<p style="color:#64748b;font-size:12px;margin:0;">' . esc_html__('This order was not created via CheckoutBridge.', 'op-checkoutbridge') . '</p>';
            return;
        }

        echo '<div style="font-family:sans-serif;font-size:12px;line-height:1.6;color:#334155;">';
        if ($landing_name) {
            echo '<p style="margin:0 0 6px 0;"><strong>' . esc_html__('Bridge Campaign:', 'op-checkoutbridge') . '</strong> <br><span class="op-cb-badge op-cb-badge-primary" style="margin-top:2px;display:inline-block;">' . esc_html($landing_name) . '</span></p>';
        }
        if ($event_id) {
            echo '<p style="margin:0 0 6px 0;"><strong>' . esc_html__('Meta Event ID:', 'op-checkoutbridge') . '</strong> <br><code style="background:#f1f5f9;color:#3730a3;padding:2px 6px;border-radius:4px;font-size:11px;">' . esc_html($event_id) . '</code></p>';
        }
        if ($fbp) {
            echo '<p style="margin:0 0 6px 0;"><strong>' . esc_html__('Facebook _fbp:', 'op-checkoutbridge') . '</strong> <br><code style="background:#f1f5f9;color:#0f172a;padding:2px 6px;border-radius:4px;font-size:11px;">' . esc_html($fbp) . '</code></p>';
        }
        if ($fbc) {
            echo '<p style="margin:0 0 6px 0;"><strong>' . esc_html__('Facebook _fbc (fbclid):', 'op-checkoutbridge') . '</strong> <br><code style="background:#f1f5f9;color:#0f172a;padding:2px 6px;border-radius:4px;font-size:11px;">' . esc_html($fbc) . '</code></p>';
        }
        if ($client_ip) {
            echo '<p style="margin:0 0 4px 0;"><strong>' . esc_html__('Customer IP:', 'op-checkoutbridge') . '</strong> <code style="background:#f1f5f9;color:#475569;padding:2px 4px;border-radius:3px;font-size:11px;">' . esc_html($client_ip) . '</code></p>';
        }
        echo '</div>';
    }
}
