<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OP_CB_Security Class
 * Handles Origin/CORS validation, HMAC SHA-256 signed redirect token generation/verification,
 * shipping signature generation/verification, and rate limiting.
 */
class OP_CB_Security {

    private static $secret_key = null;

    /**
     * Get or initialize plugin secret key (cached in memory)
     */
    private static function get_secret_key() {
        if (null === self::$secret_key) {
            self::$secret_key = get_option('op_cb_secret_key');
            if (!self::$secret_key) {
                self::$secret_key = wp_generate_password(64, true, true);
                update_option('op_cb_secret_key', self::$secret_key);
            }
        }
        return self::$secret_key;
    }

    /**
     * Hook initial security filters
     */
    public static function init() {
        add_filter('rest_pre_serve_request', array(__CLASS__, 'handle_cors'), 10, 4);
    }

    /**
     * Get trusted client IP address strictly from server environment
     *
     * @return string Validated IP address
     */
    public static function get_trusted_ip() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $cf_ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
            if (filter_var($cf_ip, FILTER_VALIDATE_IP)) {
                $ip = $cf_ip;
            }
        }

        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return '127.0.0.1';
    }

    /**
     * Handle CORS headers for rest endpoints
     */
    public static function handle_cors($served, $result, $request, $server) {
        $route = $request->get_route();
        if (strpos($route, '/checkoutbridge/v1') !== false) {
            $origin = get_http_origin();
            if ($origin) {
                header("Access-Control-Allow-Origin: " . esc_url_raw($origin));
                header("Access-Control-Allow-Credentials: true");
            } else {
                // Server-to-server requests: reflect no specific origin
                header("Access-Control-Allow-Origin: " . esc_url_raw(home_url()));
            }
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
            header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With, X-OP-CB-Token");
            header("Access-Control-Max-Age: 3600");
        }
        return $served;
    }

    /**
     * Validate request origin against allowed origins configured for landing
     */
    public static function is_origin_allowed($landing_allowed_origins) {
        if (empty($landing_allowed_origins) || trim($landing_allowed_origins) === '*') {
            return true;
        }

        $request_origin = get_http_origin();
        if (!$request_origin && isset($_SERVER['HTTP_REFERER'])) {
            $referer = sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER']));
            $parsed  = wp_parse_url($referer);
            if ($parsed && isset($parsed['scheme']) && isset($parsed['host'])) {
                $request_origin = $parsed['scheme'] . '://' . $parsed['host'];
                if (isset($parsed['port'])) {
                    $request_origin .= ':' . $parsed['port'];
                }
            }
        }

        if (!$request_origin) {
            // Allow server-to-server calls if origin header is omitted (e.g. cURL from backend)
            return true;
        }

        $request_origin = rtrim(strtolower($request_origin), '/');
        
        // Always allow same-site requests and local development loopbacks (localhost / 127.0.0.1 / ::1)
        $home_origin = rtrim(strtolower(home_url()), '/');
        $site_origin = rtrim(strtolower(site_url()), '/');
        $origin_host = wp_parse_url($request_origin, PHP_URL_HOST);
        $is_loopback = in_array($origin_host, array('localhost', '127.0.0.1', '::1'), true);
        if ($request_origin === $home_origin || $request_origin === $site_origin || $is_loopback) {
            return true;
        }

        $lines = explode("\n", str_replace("\r", "", $landing_allowed_origins));

        foreach ($lines as $line) {
            $line = rtrim(strtolower(trim($line)), '/');
            if (empty($line)) continue;

            if ($line === '*' || $line === $request_origin) {
                return true;
            }

            // Wildcard matching (e.g., https://*.example.com)
            if (strpos($line, '*') !== false) {
                $pattern = '/^' . str_replace('\*', '[a-zA-Z0-9\-\.]+', preg_quote($line, '/')) . '$/';
                if (preg_match($pattern, $request_origin)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generate HMAC SHA-256 signed redirect token
     */
    public static function generate_signed_token($order_id, $bridge_token, $ttl = 86400) {
        $secret_key = self::get_secret_key();

        try {
            $nonce = bin2hex(random_bytes(8));
        } catch (\Throwable $e) {
            $nonce = wp_generate_password(16, false);
        }

        $payload = array(
            'order_id'     => (int) $order_id,
            'bridge_token' => sanitize_text_field($bridge_token),
            'exp'          => time() + intval($ttl),
            'nonce'        => $nonce
        );

        $encoded_payload = self::base64url_encode(json_encode($payload));
        $signature = hash_hmac('sha256', $encoded_payload, $secret_key);

        return $encoded_payload . '.' . $signature;
    }

    /**
     * Verify HMAC SHA-256 signed redirect token
     */
    public static function verify_signed_token($token) {
        if (empty($token) || strpos($token, '.') === false) {
            return false;
        }

        list($encoded_payload, $signature) = explode('.', $token, 2);
        $secret_key = self::get_secret_key();

        if (!$secret_key) {
            return false;
        }

        $expected_signature = hash_hmac('sha256', $encoded_payload, $secret_key);
        if (!hash_equals($expected_signature, $signature)) {
            return false;
        }

        $decoded_json = self::base64url_decode($encoded_payload);
        $payload = json_decode($decoded_json, true);

        if (!$payload || !isset($payload['order_id']) || !isset($payload['exp'])) {
            return false;
        }

        if (time() > $payload['exp']) {
            return false; // Token expired
        }

        return $payload;
    }

    /**
     * Generate HMAC SHA-256 signature for a shipping option
     *
     * @param string $shipping_id   Shipping option slug (e.g. "inside_dhaka")
     * @param float  $shipping_cost Shipping cost amount
     * @param string $bridge_token Bridge token to bind the signature to
     * @return string HMAC signature
     */
    public static function generate_shipping_signature($shipping_id, $shipping_cost, $bridge_token) {
        $secret_key = self::get_secret_key();

        $payload = 'ship:' . $shipping_id . ':' . number_format((float) $shipping_cost, 2, '.', '') . ':' . $bridge_token;
        return hash_hmac('sha256', $payload, $secret_key);
    }

    /**
     * Verify HMAC SHA-256 shipping signature
     *
     * @param string $shipping_id   Shipping option slug
     * @param float  $shipping_cost Shipping cost amount
     * @param string $bridge_token  Bridge token the signature is bound to
     * @param string $signature     The signature to verify
     * @return bool True if signature is valid
     */
    public static function verify_shipping_signature($shipping_id, $shipping_cost, $bridge_token, $signature) {
        if (empty($shipping_id) || empty($signature) || empty($bridge_token)) {
            return false;
        }

        $expected = self::generate_shipping_signature($shipping_id, $shipping_cost, $bridge_token);
        return hash_equals($expected, $signature);
    }

    /**
     * Rate limiting using WordPress transients
     *
     * @param string $identifier  Unique key for the rate limit bucket (e.g. IP + endpoint)
     * @param int    $max_requests Maximum allowed requests in the time window
     * @param int    $window_seconds Time window in seconds
     * @return bool True if request is allowed, false if rate limited
     */
    public static function check_rate_limit($identifier, $max_requests = 10, $window_seconds = 60) {
        $transient_key = 'op_cb_rl_' . md5($identifier);
        $data = get_transient($transient_key);

        $now = time();
        if (false === $data || !is_array($data) || empty($data['reset']) || $now >= $data['reset']) {
            set_transient($transient_key, array('count' => 1, 'reset' => $now + $window_seconds), $window_seconds);
            return true;
        }

        if ($data['count'] >= $max_requests) {
            return false;
        }

        $remaining_ttl = max(1, $data['reset'] - $now);
        set_transient($transient_key, array('count' => $data['count'] + 1, 'reset' => $data['reset']), $remaining_ttl);
        return true;
    }

    /**
     * Base64URL encode helper
     */
    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64URL decode helper
     */
    private static function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    /**
     * Normalize phone numbers globally to clean E.164 international digit strings
     * Supports US (+1), GCC (+971/+966), Europe (+44), SEA (+60), South Asia (+91/+880), etc.
     */
    public static function normalize_phone_number($phone) {
        if (empty($phone)) {
            return '';
        }

        // Convert Arabic/Persian numerals to standard ASCII digits
        $eastern = array('٠','١','٢','٣','٤','٥','٦','٧','٨','٩','۰','۱','۲','۳','۴','۵','۶','۷','۸','۹');
        $western = array('0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9');
        $phone = str_replace($eastern, $western, $phone);

        // Strip non-digit characters
        $digits = preg_replace('/[^\d]/', '', $phone);

        // Normalize local 11-digit phone numbers starting with local prefix 01 to international E.164 format
        if (strlen($digits) === 11 && substr($digits, 0, 2) === '01') {
            $digits = '88' . $digits;
        }

        return $digits;
    }

    /**
     * Generate common search variations for a phone number (E.164, local, plus prefix)
     *
     * @param string $phone Phone number string
     * @return array Array of unique normalized and local phone variants
     */
    public static function generate_phone_variants($phone) {
        $normalized = self::normalize_phone_number($phone);
        if (empty($normalized)) {
            return array();
        }

        $variants = array($normalized);
        if (substr($normalized, 0, 2) === '88') {
            $local = substr($normalized, 2);
            $variants[] = $local;
            $variants[] = '0' . $local;
            $variants[] = '+88' . $local;
            $variants[] = '+880' . $local;
        } elseif (substr($normalized, 0, 1) === '1' && strlen($normalized) === 11) {
            $variants[] = substr($normalized, 1);
            $variants[] = '+1' . substr($normalized, 1);
        } else {
            $variants[] = '+' . $normalized;
            $variants[] = '0' . $normalized;
        }

        return array_values(array_unique($variants));
    }

    /**
     * Check Phone Number Order Velocity Limit (Global Anti-Spam / Anti-Bot)
     *
     * @param string $phone Customer phone number
     * @param int    $limit Max orders per phone (0 to disable)
     * @param int    $timeframe_hours Time window in hours
     * @return bool True if allowed, false if phone velocity exceeded
     */
    public static function check_phone_order_velocity($phone, $limit = 1, $timeframe_hours = 24) {
        if ($limit <= 0) {
            return true; // Disabled
        }

        $normalized = self::normalize_phone_number($phone);
        if (empty($normalized)) {
            return true;
        }

        $transient_key = 'op_cb_pvel_' . md5($normalized);
        $count = get_transient($transient_key);

        if (false !== $count) {
            return intval($count) < $limit;
        }

        // DB Fallback Check in WooCommerce Orders created via CheckoutBridge within timeframe
        // Ultra-fast DB fallback check with early limit stopping
        $hours = max(1, intval($timeframe_hours));
        $phone_variants = array_unique(array_filter(array(
            $phone,
            $normalized,
            substr($normalized, -10),
            '0' . substr($normalized, -10)
        )));

        $orders = wc_get_orders(array(
            'limit'        => $limit,
            'return'       => 'ids',
            'date_created' => '>=' . (time() - ($hours * 3600)),
            'meta_key'     => '_billing_phone',
            'meta_value'   => $phone_variants
        ));

        $db_count = is_array($orders) ? count($orders) : 0;
        set_transient($transient_key, $db_count, $hours * 3600);

        return $db_count < $limit;
    }

    /**
     * Instantly record order velocity transient increments on successful order creation
     */
    public static function record_order_velocity($phone, $client_ip, $timeframe_hours = 24) {
        $hours = max(1, intval($timeframe_hours));
        $ttl = $hours * 3600;

        if (!empty($phone)) {
            $normalized = self::normalize_phone_number($phone);
            if (!empty($normalized)) {
                $key = 'op_cb_pvel_' . md5($normalized);
                $val = get_transient($key);
                $count = (false !== $val) ? intval($val) + 1 : 1;
                set_transient($key, $count, $ttl);
            }
        }

        if (!empty($client_ip) && $client_ip !== '0.0.0.0' && $client_ip !== '127.0.0.1') {
            $key = 'op_cb_ivel_' . md5($client_ip);
            $val = get_transient($key);
            $count = (false !== $val) ? intval($val) + 1 : 1;
            set_transient($key, $count, $ttl);
        }
    }

    /**
     * Check IP Address Order Velocity Limit (Global Anti-Bot / Proxy Protection)
     *
     * @param string $client_ip Client IP address
     * @param int    $limit Max orders per IP (0 to disable)
     * @param int    $timeframe_hours Time window in hours
     * @return bool True if allowed, false if IP velocity exceeded
     */
    public static function check_ip_order_velocity($client_ip, $limit = 3, $timeframe_hours = 24) {
        if ($limit <= 0 || empty($client_ip) || $client_ip === '0.0.0.0' || $client_ip === '127.0.0.1') {
            return true; // Disabled or localhost
        }

        $transient_key = 'op_cb_ivel_' . md5($client_ip);
        $count = get_transient($transient_key);

        if (false !== $count) {
            return intval($count) < $limit;
        }

        global $wpdb;
        $hours = max(1, intval($timeframe_hours));
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($hours * 3600));

        $is_hpos = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') && wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled();
        $table_meta = $wpdb->prefix . ($is_hpos ? 'wc_orders_meta' : 'postmeta');
        $table_orders = $wpdb->prefix . ($is_hpos ? 'wc_orders' : 'posts');
        $id_col = $is_hpos ? 'id' : 'ID';
        $order_id_col = $is_hpos ? 'order_id' : 'post_id';
        $date_col = $is_hpos ? 'date_created_gmt' : 'post_date_gmt';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $db_count = intval($wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT o.{$id_col}) 
                 FROM {$table_orders} o 
                 INNER JOIN {$table_meta} m1 ON o.{$id_col} = m1.{$order_id_col} AND m1.meta_key = '_op_cb_client_ip' 
                 WHERE o.{$date_col} >= %s AND m1.meta_value = %s",
                $cutoff,
                $client_ip
            )
        ));

        set_transient($transient_key, $db_count, $hours * 3600);

        return $db_count < $limit;
    }
}
