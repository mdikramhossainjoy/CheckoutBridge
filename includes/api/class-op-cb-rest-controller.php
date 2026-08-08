<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OP_CB_REST_Controller Class
 * Registers and handles REST API endpoints for CheckoutBridge (/checkoutbridge/v1)
 * Equipped with Imunify360, ModSecurity, LiteSpeed & Shared Hosting WAF Bypass Shield.
 */
class OP_CB_REST_Controller {

    const NAMESPACE = 'checkoutbridge/v1';

    /**
     * Hook REST API initialization & Early WAF/CORS Interceptor
     */
    public static function init() {
        // 1. Early WAF & CORS Preflight Interceptor (runs before standard WP REST routing)
        add_action('init', array(__CLASS__, 'handle_early_waf_cors'), 1);

        // 2. Register REST API Routes
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));

        // 3. Imunify360 & Shared Hosting Header Filter
        add_filter('rest_pre_serve_request', array(__CLASS__, 'append_waf_headers'), 10, 4);
    }

    /**
     * Early WAF & CORS Preflight (OPTIONS) Interceptor
     * Prevents Imunify360, ModSecurity, LiteSpeed WAF & cPanel proxies from dropping cross-origin requests
     */
    public static function handle_early_waf_cors() {
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        // Check if hitting CheckoutBridge REST API or sending token headers
        if (strpos($uri, '/checkoutbridge/v1/') !== false || !empty($_SERVER['HTTP_X_OP_CB_TOKEN'])) {

            // Send CORS & WAF Bypass Headers early before output buffering
            if (!headers_sent()) {
                $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-OP-CB-Token, X-OP-CB-Signature, X-HTTP-Method-Override');
                header('Access-Control-Allow-Credentials: true');
                header('Vary: Origin');
            }

            // Immediately answer OPTIONS preflight requests so Imunify360 WAF never blocks preflight calls
            if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
                status_header(204);
                header('Content-Type: text/plain; charset=UTF-8');
                header('Content-Length: 0');
                exit;
            }
        }
    }

    /**
     * Append WAF & Cache Control Headers to REST API Responses
     */
    public static function append_waf_headers($served, $result, $request, $server) {
        if (strpos($request->get_route(), '/checkoutbridge/v1') !== false) {
            // Completely disable email notifications for all CheckoutBridge REST API routes (0ms response time)
            add_filter('pre_wp_mail', '__return_true');
            add_filter('woocommerce_email_enabled_new_order', '__return_false');
            add_filter('woocommerce_email_enabled_customer_processing_order', '__return_false');
            add_filter('woocommerce_allow_send_queued_transactional_email', '__return_false');
            add_filter('woocommerce_defer_transactional_emails', '__return_true');
            add_filter('woocommerce_email_classes', '__return_empty_array');

            remove_all_actions('woocommerce_order_status_pending_to_processing_notification');
            remove_all_actions('woocommerce_order_status_processing_notification');
            remove_all_actions('woocommerce_order_status_new_order_notification');
            remove_all_actions('woocommerce_new_order');

            if (!headers_sent()) {
                header('X-CheckoutBridge-WAF-Shield: Active');
                header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
                header('Pragma: no-cache');
            }
        }
        return $served;
    }

    /**
     * Register REST API routes
     */
    public static function register_routes() {
        // Endpoint 1: Create Order (POST)
        register_rest_route(self::NAMESPACE, '/create-order', array(
            'methods'             => array('POST', 'GET'), // Supports GET fallback for strict host WAFs
            'callback'            => array(__CLASS__, 'create_order_handler'),
            'permission_callback' => '__return_true'
        ));

        // Endpoint 2: Get Order Details for Thank You page (POST)
        register_rest_route(self::NAMESPACE, '/order-details', array(
            'methods'             => array('POST', 'GET'),
            'callback'            => array(__CLASS__, 'order_details_handler'),
            'permission_callback' => '__return_true'
        ));

        // Endpoint 3: Integration Health Check (GET)
        register_rest_route(self::NAMESPACE, '/health', array(
            'methods'             => 'GET',
            'callback'            => array(__CLASS__, 'health_check_handler'),
            'permission_callback' => '__return_true'
        ));

        // Endpoint 4: Validate Coupon / Promo Code (POST)
        register_rest_route(self::NAMESPACE, '/validate-coupon', array(
            'methods'             => array('POST', 'GET'),
            'callback'            => array(__CLASS__, 'validate_coupon_handler'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * Robust Multi-Format Parameter Extraction (JSON + Form Data + Base64 + Query Fallbacks)
     * Defeats Imunify360 WAF body filtering and LiteSpeed parameter stripping
     */
    public static function extract_request_params($request) {
        $params = array();

        // 1. Try WP REST JSON params
        $json_params = $request->get_json_params();
        if (is_array($json_params) && !empty($json_params)) {
            $params = $json_params;
        }

        // 2. Fallback to WP REST Body Params (Form POST data)
        if (empty($params)) {
            $body_params = $request->get_body_params();
            if (is_array($body_params) && !empty($body_params)) {
                $params = $body_params;
            }
        }

        // 3. Fallback to raw php://input stream (for LiteSpeed/Nginx edge cases)
        if (empty($params)) {
            $raw_body = file_get_contents('php://input');
            if (!empty($raw_body)) {
                $decoded = json_decode($raw_body, true);
                if (is_array($decoded)) {
                    $params = $decoded;
                } else {
                    parse_str($raw_body, $parsed_str);
                    if (is_array($parsed_str) && !empty($parsed_str)) {
                        $params = $parsed_str;
                    }
                }
            }
        }

        // 4. Fallback to WP REST Query Params, $_GET & $_POST
        $query_params = $request->get_query_params();
        if (is_array($query_params) && !empty($query_params)) {
            $params = array_merge($query_params, $params);
        }
        $params = array_merge($_GET, $_POST, $params);

        // 5. Imunify360 WAF Evasion: Support Base64 Encoded Payload (`payload=...` or `data=...`)
        $raw_payload = '';
        if (!empty($params['payload']) && is_string($params['payload'])) {
            $raw_payload = $params['payload'];
        } elseif (!empty($params['data']) && is_string($params['data'])) {
            $raw_payload = $params['data'];
        }

        if (!empty($raw_payload)) {
            $decoded_payload = json_decode(base64_decode($raw_payload), true);
            if (is_array($decoded_payload)) {
                $params = array_merge($params, $decoded_payload);
            }
        }

        // 6. Header Token Fallback (If WAF or cPanel proxy strips landing_token parameter or query string)
        if (empty($params['landing_token'])) {
            if (!empty($_SERVER['HTTP_X_OP_CB_TOKEN'])) {
                $params['landing_token'] = sanitize_text_field($_SERVER['HTTP_X_OP_CB_TOKEN']);
            } elseif (!empty($_GET['landing_token'])) {
                $params['landing_token'] = sanitize_text_field($_GET['landing_token']);
            } elseif (!empty($_GET['token'])) {
                $params['landing_token'] = sanitize_text_field($_GET['token']);
            }
        }

        // 7. Parse nested form data keys if sent flat (e.g., full_name, phone, address -> customer array)
        if (empty($params['customer']) && (isset($params['full_name']) || isset($params['phone']) || isset($params['address']))) {
            $params['customer'] = array(
                'full_name' => isset($params['full_name']) ? sanitize_text_field($params['full_name']) : '',
                'phone'     => isset($params['phone']) ? sanitize_text_field($params['phone']) : '',
                'address'   => isset($params['address']) ? sanitize_text_field($params['address']) : '',
                'city'      => isset($params['city']) ? sanitize_text_field($params['city']) : '',
                'note'      => isset($params['note']) ? sanitize_text_field($params['note']) : '',
            );
        }

        // 9. Coupon Code Extraction & Normalization
        $coupon_code = '';
        if (!empty($params['coupon_code'])) {
            $coupon_code = sanitize_text_field($params['coupon_code']);
        } elseif (!empty($params['coupon'])) {
            $coupon_code = sanitize_text_field($params['coupon']);
        } elseif (!empty($params['promo_code'])) {
            $coupon_code = sanitize_text_field($params['promo_code']);
        }
        $params['coupon_code'] = strtoupper(trim($coupon_code));

        return $params;
    }

    /**
     * Route Handler: POST /create-order
     */
    public static function create_order_handler($request) {
        try {
            $params = self::extract_request_params($request);

            // Rate Limiting: 15 requests per 60 seconds per IP
            $client_ip = self::get_client_ip();
            if (!OP_CB_Security::check_rate_limit('create_order_' . $client_ip, 15, 60)) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error'   => 'rate_limited',
                    'message' => __('Too many requests. Please try again shortly.', 'checkoutbridge')
                ), 429);
            }

            $landing_token = isset($params['landing_token']) ? sanitize_text_field($params['landing_token']) : '';
            if (empty($landing_token)) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error'   => 'missing_landing_token',
                    'message' => __('Landing token is required.', 'checkoutbridge')
                ), 400);
            }

            // Fetch Bridge Record
            $landing = OP_CB_Bridge_Repository::get_by_token($landing_token);
            if (!$landing) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error'   => 'invalid_landing_token',
                    'message' => __('Invalid or unknown landing token.', 'checkoutbridge')
                ), 404);
            }

            if ($landing['status'] !== 'active') {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error'   => 'landing_inactive',
                    'message' => __('This landing campaign is currently inactive.', 'checkoutbridge')
                ), 403);
            }

            // Check Allowed Origins (CORS Check)
            if (!OP_CB_Security::is_origin_allowed($landing['allowed_origins'])) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error'   => 'origin_forbidden',
                    'message' => __('Domain origin is not authorized for this landing page.', 'checkoutbridge')
                ), 403);
            }

            // Extract Customer & Shipping Payloads
            $customer_data = isset($params['customer']) && is_array($params['customer']) ? $params['customer'] : array();
            $shipping_data = isset($params['shipping']) && is_array($params['shipping']) ? $params['shipping'] : array();

            // Dual-Shield Anti-Bot & Velocity Verification (Global Phone & IP)
            $customer_phone = isset($customer_data['phone']) ? sanitize_text_field($customer_data['phone']) : '';
            $phone_limit    = isset($landing['phone_velocity_limit']) ? intval($landing['phone_velocity_limit']) : 1;
            $ip_limit       = isset($landing['ip_velocity_limit']) ? intval($landing['ip_velocity_limit']) : 3;
            $velocity_hours = isset($landing['velocity_hours']) ? intval($landing['velocity_hours']) : 24;

            if (!empty($customer_phone) && !OP_CB_Security::check_phone_order_velocity($customer_phone, $phone_limit, $velocity_hours)) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error'   => 'phone_velocity_limit_exceeded',
                    'message' => sprintf(__('Maximum order limit (%d) reached for this phone number within %d hours. Please contact customer support.', 'checkoutbridge'), $phone_limit, $velocity_hours)
                ), 429);
            }

            if (!empty($client_ip) && !OP_CB_Security::check_ip_order_velocity($client_ip, $ip_limit, $velocity_hours)) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error'   => 'ip_velocity_limit_exceeded',
                    'message' => sprintf(__('Maximum order limit (%d) reached from your IP address within %d hours.', 'checkoutbridge'), $ip_limit, $velocity_hours)
                ), 429);
            }

            // Verify Shipping Signature (if provided by external landing page)
            $shipping_signature = isset($shipping_data['signature']) ? sanitize_text_field($shipping_data['signature']) : '';
            if (!empty($shipping_signature)) {
                $shipping_id   = isset($shipping_data['id']) ? sanitize_key($shipping_data['id']) : '';
                $shipping_cost = isset($shipping_data['cost']) ? floatval($shipping_data['cost']) : 0;

                if (!OP_CB_Security::verify_shipping_signature($shipping_id, $shipping_cost, $landing['token'], $shipping_signature)) {
                    return new WP_REST_Response(array(
                        'success' => false,
                        'error'   => 'invalid_shipping_signature',
                        'message' => __('Shipping signature verification failed. The shipping data may have been tampered with.', 'checkoutbridge')
                    ), 403);
                }
                // Mark as pre-verified so the order engine trusts this shipping data
                $shipping_data['_signature_verified'] = true;
            }

            // Create Order via Engine
            $order_result = OP_CB_Order_Engine::create_order($landing, $customer_data, $shipping_data, $params);

            if (is_wp_error($order_result)) {
                $error_code = $order_result->get_error_code();
                $status_code = $order_result->get_error_data('status') ? $order_result->get_error_data('status') : 400;

                return new WP_REST_Response(array(
                    'success' => false,
                    'error'   => $error_code,
                    'message' => $order_result->get_error_message()
                ), $status_code);
            }

            $order_id = $order_result->get_id();

            // Generate Signed Token for Redirect
            $signed_token = OP_CB_Security::generate_signed_token($order_id, $landing['token']);
            $thank_you_url = !empty($landing['thank_you_url']) ? $landing['thank_you_url'] : '';

            return new WP_REST_Response(array(
                'success'  => true,
                'redirect' => array(
                    'url'   => $thank_you_url,
                    'token' => $signed_token
                )
            ), 201);

        } catch (\Throwable $e) {
            // Log the error for admin debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[CheckoutBridge] Order creation exception: ' . $e->getMessage());
            }

            return new WP_REST_Response(array(
                'success' => false,
                'error'   => 'internal_error',
                'message' => __('An internal server error occurred while creating the order. Please try again.', 'checkoutbridge')
            ), 500);
        }
    }

    /**
     * Route Handler: POST /order-details
     */
    public static function order_details_handler($request) {
        // Rate Limiting: 30 requests per 60 seconds per IP
        $client_ip = self::get_client_ip();
        if (!OP_CB_Security::check_rate_limit('details_' . $client_ip, 30, 60)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error'   => 'rate_limited',
                'message' => __('Too many requests. Please try again shortly.', 'checkoutbridge')
            ), 429);
        }

        $params = self::extract_request_params($request);

        $token = isset($params['token']) ? trim(sanitize_text_field($params['token'])) : '';
        if (empty($token)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error'   => 'missing_token',
                'message' => __('Order redirect token is required.', 'checkoutbridge')
            ), 400);
        }

        // Verify Signed HMAC Token
        $payload = OP_CB_Security::verify_signed_token($token);
        if (!$payload) {
            return new WP_REST_Response(array(
                'success' => false,
                'error'   => 'invalid_token',
                'message' => __('Order redirect token is invalid or has expired.', 'checkoutbridge')
            ), 401);
        }

        $order_id = $payload['order_id'];
        $details = OP_CB_Order_Engine::get_order_details($order_id);

        if (is_wp_error($details)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error'   => $details->get_error_code(),
                'message' => $details->get_error_message()
            ), 404);
        }

        return new WP_REST_Response($details, 200);
    }

    /**
     * Route Handler: POST /validate-coupon
     */
    public static function validate_coupon_handler($request) {
        // Rate limiting: 30 requests per 60 seconds per IP
        $client_ip = self::get_client_ip();
        if (!OP_CB_Security::check_rate_limit('val_coupon_' . $client_ip, 30, 60)) {
            return new WP_REST_Response(array(
                'success' => false,
                'valid'   => false,
                'error'   => 'rate_limited',
                'message' => __('Too many requests. Please try again shortly.', 'checkoutbridge')
            ), 429);
        }

        $params = self::extract_request_params($request);

        $landing_token = isset($params['landing_token']) ? sanitize_text_field($params['landing_token']) : '';
        if (empty($landing_token)) {
            return new WP_REST_Response(array(
                'success' => false,
                'valid'   => false,
                'error'   => 'missing_landing_token',
                'message' => __('Landing token is required.', 'checkoutbridge')
            ), 400);
        }

        $landing = OP_CB_Bridge_Repository::get_by_token($landing_token);
        if (!$landing) {
            return new WP_REST_Response(array(
                'success' => false,
                'valid'   => false,
                'error'   => 'invalid_landing_token',
                'message' => __('Invalid or unknown landing token.', 'checkoutbridge')
            ), 404);
        }

        $coupon_code = isset($params['coupon_code']) ? sanitize_text_field($params['coupon_code']) : (isset($params['coupon']) ? sanitize_text_field($params['coupon']) : '');
        if (empty($coupon_code)) {
            return new WP_REST_Response(array(
                'success' => false,
                'valid'   => false,
                'error'   => 'missing_coupon',
                'message' => __('Coupon code is required.', 'checkoutbridge')
            ), 400);
        }

        $result = OP_CB_Order_Engine::validate_coupon_code($landing, $coupon_code, $params);

        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'valid'   => false,
                'error'   => $result->get_error_code(),
                'message' => $result->get_error_message()
            ), 400);
        }

        return new WP_REST_Response($result, 200);
    }

    /**
     * Route Handler: GET /health
     */
    public static function health_check_handler($request) {
        // Lightweight rate limiting: 50 requests per 60 seconds per IP
        $client_ip = self::get_client_ip();

        if (!OP_CB_Security::check_rate_limit('health_' . $client_ip, 50, 60)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error'   => 'rate_limited',
                'message' => __('Too many requests. Please try again shortly.', 'checkoutbridge')
            ), 429);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'status'  => 'ok',
            'waf_shield' => 'active',
            'message' => __('CheckoutBridge REST API is active and healthy.', 'checkoutbridge'),
            'version' => OP_CB_VERSION
        ), 200);
    }

    /**
     * Helper to extract & sanitize client IP address securely (Supports Cloudflare, LiteSpeed, Incapsula)
     */
    private static function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_INCAP_CLIENT_IP',  // Incapsula
            'HTTP_X_REAL_IP',        // LiteSpeed / Nginx
            'HTTP_X_FORWARDED_FOR',  // Standard Proxy
            'REMOTE_ADDR'            // Standard Fallback
        );

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $parts = explode(',', $ip);
                    $ip = trim($parts[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return sanitize_text_field($ip);
                }
            }
        }

        return '0.0.0.0';
    }
}
