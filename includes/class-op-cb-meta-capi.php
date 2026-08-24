<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OP_CB_Meta_CAPI Class
 * Handles server-side Meta (Facebook) Conversions API (CAPI) Purchase event dispatch
 * when WooCommerce order status transitions to Processing.
 */
class OP_CB_Meta_CAPI {

    const GRAPH_API_VERSION = 'v19.0';

    /**
     * Initialize Meta CAPI hooks
     */
    public static function init() {
        // Trigger on WooCommerce Order Status Processing (both creation and status updates)
        add_action('woocommerce_order_status_processing', array(__CLASS__, 'on_order_status_processing'), 10, 2);

        // Fallback for general status transitions
        add_action('woocommerce_order_status_changed', array(__CLASS__, 'on_order_status_changed'), 10, 4);

        // Allow direct trigger via CheckoutBridge custom action
        add_action('op_cb_order_created_meta_capi', array(__CLASS__, 'on_order_created_action'), 10, 3);
    }

    /**
     * Handle order status transition to processing
     *
     * @param int       $order_id Order ID
     * @param \WC_Order $order    Order object (optional)
     */
    public static function on_order_status_processing($order_id, $order = null) {
        if (!$order) {
            $order = wc_get_order($order_id);
        }

        if (!$order) {
            return;
        }

        // Prevent duplicate CAPI Purchase event firing
        if ($order->get_meta('_op_cb_capi_purchase_fired')) {
            return;
        }

        // Retrieve Bridge campaign configuration
        $bridge_token = $order->get_meta('_op_cb_bridge_token');
        if (empty($bridge_token)) {
            return; // Not a CheckoutBridge order
        }

        $bridge = OP_CB_Bridge_Repository::get_by_token($bridge_token);
        if (!$bridge) {
            return;
        }

        // Check if Meta CAPI is enabled and credentials are configured
        if (empty($bridge['enable_meta_capi']) || empty($bridge['meta_pixel_id']) || empty($bridge['meta_access_token'])) {
            return;
        }

        self::send_purchase_event($order, $bridge);
    }

    /**
     * Handle generic WooCommerce order status changes
     *
     * @param int       $order_id    Order ID
     * @param string    $status_from Previous status
     * @param string    $status_to   New status
     * @param \WC_Order $order       Order object
     */
    public static function on_order_status_changed($order_id, $status_from, $status_to, $order) {
        if ($status_to === 'processing' && $status_from !== 'processing') {
            self::on_order_status_processing($order_id, $order);
        }
    }

    /**
     * Handle CheckoutBridge custom action trigger
     *
     * @param int   $order_id  Order ID
     * @param array $meta_capi Client Meta tracking data
     * @param array $landing   Bridge campaign data
     */
    public static function on_order_created_action($order_id, $meta_capi, $landing) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        if (!empty($landing['enable_meta_capi']) && !empty($landing['meta_pixel_id']) && !empty($landing['meta_access_token'])) {
            if (!$order->get_meta('_op_cb_capi_purchase_fired')) {
                self::send_purchase_event($order, $landing);
            }
        }
    }

    /**
     * Dispatch Purchase Event to Meta Graph API
     *
     * @param \WC_Order $order   WooCommerce Order Object
     * @param array     $bridge  Bridge campaign config
     * @return bool True if successfully dispatched, false otherwise
     */
    public static function send_purchase_event($order, $bridge) {
        $pixel_id     = trim($bridge['meta_pixel_id']);
        $access_token = trim($bridge['meta_access_token']);
        $test_code    = !empty($bridge['meta_test_code']) ? trim($bridge['meta_test_code']) : '';

        if (empty($pixel_id) || empty($access_token)) {
            return false;
        }

        $order_id = $order->get_id();

        // 1. Resolve or generate event_id for browser-server deduplication
        $event_id = $order->get_meta('_op_cb_event_id');
        if (empty($event_id)) {
            $event_id = 'cb_order_' . $order_id . '_' . time();
            $order->update_meta_data('_op_cb_event_id', $event_id);
        }

        // 2. Build User Data (SHA-256 Hashed per Meta specifications)
        $user_data = array();

        // Email (Hashed)
        $billing_email = $order->get_billing_email();
        if (!empty($billing_email)) {
            $user_data['em'] = array(self::hash_field($billing_email));
        }

        // Phone (Normalized E.164 and Hashed)
        $billing_phone = $order->get_billing_phone();
        if (!empty($billing_phone)) {
            $normalized_phone = OP_CB_Security::normalize_phone_number($billing_phone);
            if (!empty($normalized_phone)) {
                $user_data['ph'] = array(self::hash_field($normalized_phone));
            }
        }

        // First Name (Hashed)
        $first_name = $order->get_billing_first_name();
        if (!empty($first_name)) {
            $user_data['fn'] = array(self::hash_field($first_name));
        }

        // Last Name (Hashed)
        $last_name = $order->get_billing_last_name();
        if (!empty($last_name)) {
            $user_data['ln'] = array(self::hash_field($last_name));
        }

        // City (Hashed)
        $city = $order->get_billing_city();
        if (!empty($city)) {
            $user_data['ct'] = array(self::hash_field($city));
        }

        // Country (Hashed 2-letter ISO)
        $country = $order->get_billing_country();
        if (!empty($country)) {
            $user_data['country'] = array(self::hash_field(strtolower($country)));
        }

        // Unhashed Meta browser & click cookies
        $fbp = $order->get_meta('_op_cb_fbp');
        if (!empty($fbp)) {
            $user_data['fbp'] = $fbp;
        }

        $fbc = $order->get_meta('_op_cb_fbc');
        if (!empty($fbc)) {
            $user_data['fbc'] = $fbc;
        }

        // Unhashed IP Address & User Agent
        $client_ip = $order->get_meta('_op_cb_client_ip');
        if (empty($client_ip) || $client_ip === '127.0.0.1') {
            $client_ip = OP_CB_Security::get_trusted_ip();
        }
        if (!empty($client_ip) && filter_var($client_ip, FILTER_VALIDATE_IP)) {
            $user_data['client_ip_address'] = $client_ip;
        }

        $user_agent = $order->get_meta('_op_cb_user_agent');
        if (empty($user_agent) && isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));
        }
        if (!empty($user_agent)) {
            $user_data['client_user_agent'] = $user_agent;
        }

        // 3. Build Custom Data (Contents, Currency, Value)
        $contents = array();
        $num_items = 0;
        foreach ($order->get_items() as $item) {
            $qty = $item->get_quantity();
            $subtotal = floatval($item->get_subtotal());
            $unit_price = $qty > 0 ? ($subtotal / $qty) : 0;
            $num_items += $qty;

            $contents[] = array(
                'id'         => (string) $item->get_product_id(),
                'quantity'   => $qty,
                'item_price' => round($unit_price, 2)
            );
        }

        $currency = $order->get_currency();
        $total    = floatval($order->get_total());

        $custom_data = array(
            'currency'     => !empty($currency) ? $currency : 'BDT',
            'value'        => round($total, 2),
            'content_type' => 'product',
            'contents'     => $contents,
            'num_items'    => $num_items
        );

        // 4. Resolve Event Source URL
        $event_source_url = !empty($bridge['thank_you_url']) ? $bridge['thank_you_url'] : home_url();

        // 5. Construct Single Event Object
        $event_data = array(
            'event_name'       => 'Purchase',
            'event_time'       => time(),
            'event_id'         => $event_id,
            'event_source_url' => esc_url_raw($event_source_url),
            'action_source'    => 'website',
            'user_data'        => $user_data,
            'custom_data'      => $custom_data
        );

        // 6. Build Final API Request Payload
        $payload = array(
            'data' => array($event_data)
        );

        if (!empty($test_code)) {
            $payload['test_event_code'] = $test_code;
        }

        // 7. Send Request to Meta Graph API
        $endpoint = sprintf('https://graph.facebook.com/%s/%s/events', self::GRAPH_API_VERSION, rawurlencode($pixel_id));

        $args = array(
            'method'      => 'POST',
            'timeout'     => 10,
            'redirection' => 3,
            'httpversion' => '1.1',
            'blocking'    => true,
            'headers'     => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'CheckoutBridge/' . OP_CB_VERSION . '; ' . home_url()
            ),
            'body'        => wp_json_encode($payload),
            'data_format' => 'body'
        );

        $response = wp_remote_post($endpoint, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $order->update_meta_data('_op_cb_capi_status', 'failed');
            $order->update_meta_data('_op_cb_capi_error', $error_message);
            $order->update_meta_data('_op_cb_capi_timestamp', time());
            $order->save();
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body_raw    = wp_remote_retrieve_body($response);
        $body        = json_decode($body_raw, true);

        if ($status_code === 200 && !empty($body['events_received'])) {
            $order->update_meta_data('_op_cb_capi_purchase_fired', 1);
            $order->update_meta_data('_op_cb_capi_status', 'success');
            $order->update_meta_data('_op_cb_capi_response_code', $status_code);
            $order->update_meta_data('_op_cb_capi_timestamp', time());
            if (!empty($body['fbtrace_id'])) {
                $order->update_meta_data('_op_cb_capi_fbtrace_id', sanitize_text_field($body['fbtrace_id']));
            }
            $order->save();
            return true;
        }

        // If Meta returns an error object in JSON
        $error_msg = isset($body['error']['message']) ? sanitize_text_field($body['error']['message']) : 'HTTP ' . $status_code;
        $order->update_meta_data('_op_cb_capi_status', 'failed');
        $order->update_meta_data('_op_cb_capi_response_code', $status_code);
        $order->update_meta_data('_op_cb_capi_error', $error_msg);
        $order->update_meta_data('_op_cb_capi_timestamp', time());
        $order->save();

        return false;
    }

    /**
     * SHA-256 Hash helper according to Meta Conversions API requirements
     *
     * @param string $value Raw string value
     * @return string Normalized and SHA-256 hashed string
     */
    public static function hash_field($value) {
        $normalized = strtolower(trim((string) $value));
        return hash('sha256', $normalized);
    }
}