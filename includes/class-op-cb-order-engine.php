<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OP_CB_Order_Engine Class
 * Creates WooCommerce orders and retrieves formatted order details
 */
class OP_CB_Order_Engine {

    /**
     * Create WooCommerce Order programmatically
     */
    public static function create_order($landing, $customer_data, $shipping_data, $raw_params = array()) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error('wc_missing', __('WooCommerce plugin is not active.', 'op-checkoutbridge'), array('status' => 500));
        }

        // Validate Customer Data
        $full_name = isset($customer_data['full_name']) ? trim(sanitize_text_field($customer_data['full_name'])) : '';
        $phone     = isset($customer_data['phone']) ? trim(sanitize_text_field($customer_data['phone'])) : '';
        $address   = isset($customer_data['address']) ? trim(sanitize_textarea_field($customer_data['address'])) : '';

        if (empty($full_name)) {
            return new WP_Error('invalid_customer', __('Customer full name is required.', 'op-checkoutbridge'), array('status' => 400));
        }
        if (empty($phone)) {
            return new WP_Error('invalid_customer', __('Customer phone number is required.', 'op-checkoutbridge'), array('status' => 400));
        }
        if (empty($address)) {
            return new WP_Error('invalid_customer', __('Customer full address is required.', 'op-checkoutbridge'), array('status' => 400));
        }

        // Name Splitter (handles multi-space and leading whitespace cleanly)
        $name_parts = preg_split('/\s+/', trim($full_name), 2);
        $first_name = isset($name_parts[0]) ? $name_parts[0] : $full_name;
        $last_name  = isset($name_parts[1]) ? $name_parts[1] : '';

        // Validate Assigned Campaign Products
        $assigned_product_ids = !empty($landing['assigned_products']) ? array_unique(array_map('intval', $landing['assigned_products'])) : array();
        if (empty($assigned_product_ids)) {
            return new WP_Error('no_products', __('No products are assigned to this landing campaign.', 'op-checkoutbridge'), array('status' => 400));
        }

        // Build Requested Items Map from Payload: items = [ {id: 14, quantity: 2}, ... ]
        $requested_items = array();
        if (!empty($raw_params['items']) && is_array($raw_params['items'])) {
            foreach ($raw_params['items'] as $item) {
                $p_id = isset($item['id']) ? intval($item['id']) : (isset($item['product_id']) ? intval($item['product_id']) : 0);
                $qty  = isset($item['quantity']) ? min(99, max(1, intval($item['quantity']))) : 1;
                if ($p_id > 0 && in_array($p_id, $assigned_product_ids, true)) {
                    $requested_items[$p_id] = $qty;
                }
            }
        } elseif (!empty($raw_params['product_id'])) {
            $p_id = intval($raw_params['product_id']);
            $qty  = isset($raw_params['quantity']) ? min(99, max(1, intval($raw_params['quantity']))) : 1;
            if ($p_id > 0 && in_array($p_id, $assigned_product_ids, true)) {
                $requested_items[$p_id] = $qty;
            }
        }

        // Strictly enforce: items array is required! No automatic fallback allowed.
        if (empty($requested_items)) {
            return new WP_Error('missing_items', __('Order payload must contain an items array with at least one valid assigned product ID.', 'op-checkoutbridge'), array('status' => 400));
        }

        // Query & validate WooCommerce products for requested items
        $order_line_items = array();
        foreach ($requested_items as $prod_id => $qty) {
            $product = wc_get_product($prod_id);
            if ($product && $product->is_purchasable() && $product->is_in_stock()) {
                $order_line_items[] = array(
                    'product'  => $product,
                    'quantity' => $qty
                );
            }
        }

        if (empty($order_line_items)) {
            return new WP_Error('products_unavailable', __('Selected products are unavailable or out of stock in WooCommerce.', 'op-checkoutbridge'), array('status' => 400));
        }

        // Resolve Shipping Choice & Cost Dynamically
        $shipping_label = isset($shipping_data['label']) ? trim(sanitize_text_field($shipping_data['label'])) : '';
        $shipping_id    = isset($shipping_data['id']) ? sanitize_key($shipping_data['id']) : (!empty($shipping_label) ? sanitize_key($shipping_label) : 'standard_delivery');
        $shipping_cost  = isset($shipping_data['cost']) ? floatval($shipping_data['cost']) : (isset($shipping_data['amount']) ? floatval($shipping_data['amount']) : null);
        $signature_verified = !empty($shipping_data['_signature_verified']);

        $first_enabled_option = null;

        if ($signature_verified && $shipping_cost !== null) {
            // Signature verified by REST controller — trust cost
        } elseif ($shipping_cost === null && !empty($landing['shipping_options']) && is_array($landing['shipping_options'])) {
            foreach ($landing['shipping_options'] as $option) {
                if (isset($option['status']) && $option['status'] !== 'enabled') {
                    continue;
                }
                if ($first_enabled_option === null) {
                    $first_enabled_option = $option;
                }
                if (isset($option['id']) && $option['id'] === $shipping_id) {
                    $shipping_cost = floatval(isset($option['cost']) ? $option['cost'] : 0);
                    if (empty($shipping_label)) {
                        $shipping_label = isset($option['label']) ? $option['label'] : $shipping_id;
                    }
                    break;
                }
            }

            // Fallback: If requested shipping ID is unverified/unrecognized, use first enabled option
            if ($shipping_cost === null && $first_enabled_option !== null) {
                $shipping_id    = isset($first_enabled_option['id']) ? $first_enabled_option['id'] : 'standard_delivery';
                $shipping_label = isset($first_enabled_option['label']) ? $first_enabled_option['label'] : __('Standard Delivery', 'op-checkoutbridge');
                $shipping_cost  = floatval(isset($first_enabled_option['cost']) ? $first_enabled_option['cost'] : 0);
            }
        }

        if ($shipping_cost === null) {
            $shipping_cost = 0.0;
        }
        if (empty($shipping_label)) {
            $shipping_label = !empty($shipping_id) ? ucwords(str_replace(array('_', '-'), ' ', $shipping_id)) : __('Standard Delivery', 'op-checkoutbridge');
        }

        // Bulletproof Mailer Bypass: Short-circuit wp_mail & strip email notification hooks before wc_create_order
        add_filter('pre_wp_mail', '__return_true');
        add_filter('woocommerce_email_enabled_new_order', '__return_false');
        add_filter('woocommerce_email_enabled_customer_processing_order', '__return_false');
        add_filter('woocommerce_allow_send_queued_transactional_email', '__return_false');
        add_filter('woocommerce_defer_transactional_emails', '__return_true');

        remove_all_actions('woocommerce_order_status_pending_to_processing_notification');
        remove_all_actions('woocommerce_order_status_processing_notification');
        remove_all_actions('woocommerce_order_status_new_order_notification');
        remove_all_actions('woocommerce_new_order');

        // Create WooCommerce Order with status processing directly
        $order = wc_create_order(array(
            'status'        => 'processing',
            'customer_note' => !empty($customer_data['note']) ? sanitize_text_field($customer_data['note']) : ''
        ));
        if (is_wp_error($order)) {
            return $order;
        }

        // Add Line Items from validated order_line_items array
        foreach ($order_line_items as $line_item) {
            $order->add_product($line_item['product'], $line_item['quantity']);
        }

        // Add Shipping Line Item
        if (class_exists('WC_Order_Item_Shipping')) {
            $shipping_item = new WC_Order_Item_Shipping();
            $shipping_item->set_method_title($shipping_label);
            $shipping_item->set_method_id($shipping_id);
            $shipping_item->set_total($shipping_cost);
            $order->add_item($shipping_item);
        }

        // Customer Billing & Shipping Address
        $email = isset($customer_data['email']) ? sanitize_email($customer_data['email']) : '';
        $city  = isset($customer_data['city']) ? sanitize_text_field($customer_data['city']) : '';
        $country = isset($customer_data['country']) ? sanitize_text_field($customer_data['country']) : (class_exists('WC_Countries') ? WC()->countries->get_base_country() : '');
        $address_data = array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'address_1'  => $address,
            'city'       => $city,
            'country'    => $country,
            'phone'      => $phone,
            'email'      => $email,
        );

        $order->set_address($address_data, 'billing');
        $order->set_address($address_data, 'shipping');

        // Set Cash on Delivery (COD) payment method
        $order->set_payment_method('cod');
        $order->set_payment_method_title('Cash on delivery');

        // Save Custom Order Metadata
        $order->update_meta_data('_op_cb_bridge_name', $landing['name']);
        $order->update_meta_data('_op_cb_bridge_token', $landing['token']);
        $order->update_meta_data('_op_cb_shipping_id', $shipping_id);
        $order->update_meta_data('_op_cb_shipping_label', $shipping_label);
        $order->update_meta_data('_op_cb_shipping_cost', $shipping_cost);

        // Process Dynamic Coupon / Promo Code Validation & Application
        $coupon_code = isset($raw_params['coupon_code']) ? strtoupper(trim(sanitize_text_field($raw_params['coupon_code']))) : '';
        if (!empty($coupon_code)) {
            $coupon = new \WC_Coupon($coupon_code);
            if (!$coupon->get_id()) {
                $order->delete(true);
                /* translators: %s: Coupon code */
                return new WP_Error('invalid_coupon', sprintf(__('The coupon code "%s" does not exist.', 'op-checkoutbridge'), $coupon_code), array('status' => 400));
            }

            // Apply coupon to WooCommerce Order
            $applied = $order->apply_coupon($coupon_code);
            if (is_wp_error($applied)) {
                $order->delete(true);
                return new WP_Error('coupon_application_failed', $applied->get_error_message(), array('status' => 400));
            }
            $order->update_meta_data('_op_cb_coupon_code', $coupon_code);
        }

        // Save Meta (Facebook) CAPI Tracking Metadata (HPOS Native)
        $meta_capi = isset($raw_params['meta_capi']) && is_array($raw_params['meta_capi']) ? $raw_params['meta_capi'] : array();
        if (!empty($meta_capi)) {
            if (!empty($meta_capi['fbp'])) {
                $order->update_meta_data('_op_cb_fbp', sanitize_text_field($meta_capi['fbp']));
            }
            if (!empty($meta_capi['fbc'])) {
                $order->update_meta_data('_op_cb_fbc', sanitize_text_field($meta_capi['fbc']));
            }
            if (!empty($meta_capi['event_id'])) {
                $order->update_meta_data('_op_cb_event_id', sanitize_text_field($meta_capi['event_id']));
            }
            if (!empty($meta_capi['user_agent'])) {
                $order->update_meta_data('_op_cb_user_agent', sanitize_text_field($meta_capi['user_agent']));
            }
        }

        // Save server-verified client IP for reliable HPOS tracking and anti-bot verification
        $trusted_client_ip = OP_CB_Security::get_trusted_ip();
        $order->update_meta_data('_op_cb_client_ip', $trusted_client_ip);

        // Completely disable WooCommerce email notifications for CheckoutBridge orders (phone/COD landing pages don't require emails)
        add_filter('pre_wp_mail', '__return_true');
        add_filter('woocommerce_email_enabled_new_order', '__return_false');
        add_filter('woocommerce_email_enabled_customer_processing_order', '__return_false');
        add_filter('woocommerce_allow_send_queued_transactional_email', '__return_false');
        add_filter('woocommerce_email_classes', '__return_empty_array');

        // Set Order Status & Recalculate Totals
        $order->set_status('processing', __('Order created via CheckoutBridge.', 'op-checkoutbridge'));
        $order->calculate_totals(true);
        $order->save();

        // Trigger WordPress Server-Side Meta CAPI Action Hook for 3rd Party Integrations
        do_action('op_cb_order_created_meta_capi', $order->get_id(), $meta_capi, $landing);
        do_action('checkoutbridge_order_created_meta_capi', $order->get_id(), $meta_capi, $landing);

        // Increment order counter for this bridge and record velocity transient
        OP_CB_Bridge_Repository::increment_orders_count($landing['id']);
        $velocity_hours = isset($landing['velocity_hours']) ? intval($landing['velocity_hours']) : 24;
        OP_CB_Security::record_order_velocity($phone, $trusted_client_ip, $velocity_hours);

        return $order;
    }

    /**
     * Retrieve Order Details for Thank You page
     */
    public static function get_order_details($order_id) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error('wc_missing', __('WooCommerce plugin is not active.', 'op-checkoutbridge'), array('status' => 500));
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return new WP_Error('order_not_found', __('Order not found.', 'op-checkoutbridge'), array('status' => 404));
        }

        // Format Customer Info
        $first_name = $order->get_billing_first_name();
        $last_name  = $order->get_billing_last_name();
        $full_name  = trim($first_name . ' ' . $last_name);

        $customer = array(
            'full_name' => $full_name,
            'phone'     => $order->get_billing_phone(),
            'address'   => $order->get_billing_address_1()
        );

        // Format Shipping Choice
        $shipping_id    = $order->get_meta('_op_cb_shipping_id');
        $shipping_label = $order->get_meta('_op_cb_shipping_label');
        $shipping_cost  = floatval($order->get_shipping_total());

        if (empty($shipping_id)) {
            $shipping_id = 'default';
        }
        if (empty($shipping_label)) {
            $shipping_methods = $order->get_shipping_methods();
            if (!empty($shipping_methods)) {
                $first_method = reset($shipping_methods);
                $shipping_label = $first_method->get_name();
            } else {
                $shipping_label = 'Standard Shipping';
            }
        }

        $shipping = array(
            'id'    => $shipping_id,
            'label' => $shipping_label,
            'cost'  => $shipping_cost
        );

        // Format Line Items
        $items = array();
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $qty = $item->get_quantity();
            $subtotal = floatval($item->get_subtotal());
            $unit_price = $qty > 0 ? ($subtotal / $qty) : 0;

            $image_url = '';
            if ($product) {
                $image_id = $product->get_image_id();
                if ($image_id) {
                    $image_url = wp_get_attachment_image_url($image_id, 'full');
                }
            }
            if (empty($image_url) && function_exists('wc_placeholder_img_src')) {
                $image_url = wc_placeholder_img_src('full');
            }

            $items[] = array(
                'product_id' => $item->get_product_id(),
                'name'       => $item->get_name(),
                'quantity'   => $qty,
                'unit_price' => $unit_price,
                'subtotal'   => $subtotal,
                'image_url'  => $image_url ? esc_url_raw($image_url) : ''
            );
        }

        // Order Summary
        $coupons = $order->get_coupon_codes();
        $discount_total = floatval($order->get_discount_total());
        $coupon_code_meta = $order->get_meta('_op_cb_coupon_code');
        $applied_coupon = !empty($coupons) ? implode(', ', $coupons) : ($coupon_code_meta ? $coupon_code_meta : '');

        $order_summary = array(
            'id'             => $order->get_id(),
            'number'         => $order->get_order_number(),
            'status'         => $order->get_status(),
            'subtotal'       => floatval($order->get_subtotal()),
            'shipping'       => $shipping_cost,
            'discount_total' => $discount_total,
            'coupon_code'    => $applied_coupon,
            'total'          => floatval($order->get_total())
        );

        return array(
            'success'  => true,
            'order'    => $order_summary,
            'customer' => $customer,
            'shipping' => $shipping,
            'items'    => $items
        );
    }

    /**
     * Validate Coupon / Promo Code and calculate preview discount
     */
    public static function validate_coupon_code($landing, $coupon_code, $raw_params = array()) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error('wc_missing', __('WooCommerce plugin is not active.', 'op-checkoutbridge'), array('status' => 500));
        }

        $code = strtoupper(trim(sanitize_text_field($coupon_code)));
        if (empty($code)) {
            return new WP_Error('missing_coupon', __('Coupon code is required.', 'op-checkoutbridge'), array('status' => 400));
        }

        $coupon = new \WC_Coupon($code);
        if (!$coupon->get_id()) {
            /* translators: %s: Coupon code */
            return new WP_Error('invalid_coupon', sprintf(__('The coupon code "%s" is invalid or does not exist.', 'op-checkoutbridge'), $code), array('status' => 400));
        }

        // Validate assigned products & build items array
        $assigned_product_ids = !empty($landing['assigned_products']) ? array_unique(array_map('intval', $landing['assigned_products'])) : array();
        
        $requested_items = array();
        if (!empty($raw_params['items']) && is_array($raw_params['items'])) {
            foreach ($raw_params['items'] as $item) {
                $p_id = isset($item['id']) ? intval($item['id']) : (isset($item['product_id']) ? intval($item['product_id']) : 0);
                $qty  = isset($item['quantity']) ? min(99, max(1, intval($item['quantity']))) : 1;
                if ($p_id > 0 && (empty($assigned_product_ids) || in_array($p_id, $assigned_product_ids, true))) {
                    $requested_items[$p_id] = $qty;
                }
            }
        } elseif (!empty($raw_params['product_id'])) {
            $p_id = intval($raw_params['product_id']);
            $qty  = isset($raw_params['quantity']) ? min(99, max(1, intval($raw_params['quantity']))) : 1;
            if ($p_id > 0 && (empty($assigned_product_ids) || in_array($p_id, $assigned_product_ids, true))) {
                $requested_items[$p_id] = $qty;
            }
        }

        // Calculate subtotal
        $subtotal = 0;
        foreach ($requested_items as $prod_id => $qty) {
            $product = wc_get_product($prod_id);
            if ($product && $product->is_purchasable()) {
                $subtotal += floatval($product->get_price()) * $qty;
            }
        }

        // Create temporary order in memory to calculate exact WooCommerce coupon discount
        $temp_order = wc_create_order(array('status' => 'pending'));
        if (is_wp_error($temp_order)) {
            return new WP_Error('temp_order_error', __('Unable to calculate coupon discount.', 'op-checkoutbridge'), array('status' => 500));
        }

        foreach ($requested_items as $prod_id => $qty) {
            $product = wc_get_product($prod_id);
            if ($product) {
                $temp_order->add_product($product, $qty);
            }
        }

        $shipping_cost = isset($raw_params['shipping_cost']) ? floatval($raw_params['shipping_cost']) : (isset($raw_params['shipping']['cost']) ? floatval($raw_params['shipping']['cost']) : 0);
        if ($shipping_cost > 0 && class_exists('WC_Order_Item_Shipping')) {
            $shipping_item = new WC_Order_Item_Shipping();
            $shipping_item->set_method_title('Shipping');
            $shipping_item->set_method_id('standard');
            $shipping_item->set_total($shipping_cost);
            $temp_order->add_item($shipping_item);
        }

        $applied = $temp_order->apply_coupon($code);
        if (is_wp_error($applied)) {
            $temp_order->delete(true);
            return new WP_Error('coupon_invalid', $applied->get_error_message(), array('status' => 400));
        }

        $temp_order->calculate_totals(false);
        $discount_amount = floatval($temp_order->get_discount_total());
        $grand_total     = floatval($temp_order->get_total());
        $discount_type   = $coupon->get_discount_type();
        $coupon_amount   = floatval($coupon->get_amount());

        $temp_order->delete(true); // Clean up temporary order

        /* translators: %s: Coupon code */
        $applied_msg = sprintf(__('Coupon code "%s" applied successfully.', 'op-checkoutbridge'), $code);

        return array(
            'success'            => true,
            'valid'              => true,
            'coupon' => array(
                'code'               => $code,
                'discount_type'      => $discount_type,
                'coupon_amount'      => $coupon_amount,
                'discount_amount'    => $discount_amount,
                'discount_formatted' => function_exists('wc_price') ? wp_strip_all_tags(wc_price($discount_amount)) : number_format($discount_amount, 2),
                'subtotal'           => $subtotal,
                'shipping'           => $shipping_cost,
                'total'              => $grand_total,
            ),
            'message'            => $applied_msg
        );
    }

    /**
     * Search WooCommerce past orders for customer details by phone number (Autofill feature)
     */
    public static function lookup_customer_by_phone($landing, $phone) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error('wc_missing', __('WooCommerce plugin is not active.', 'op-checkoutbridge'), array('status' => 500));
        }

        $phone_raw = trim(sanitize_text_field($phone));
        if (empty($phone_raw)) {
            return new WP_Error('missing_phone', __('Phone number is required for lookup.', 'op-checkoutbridge'), array('status' => 400));
        }

        $normalized_phone = OP_CB_Security::normalize_phone_number($phone_raw);
        if (empty($normalized_phone) || strlen($normalized_phone) < 7) {
            return new WP_Error('invalid_phone', __('Phone number must be at least 7 digits.', 'op-checkoutbridge'), array('status' => 400));
        }

        // Check if autofill lookup feature is enabled for this bridge campaign
        $is_enabled = isset($landing['enable_autofill_lookup']) ? (bool)$landing['enable_autofill_lookup'] : true;
        if (!$is_enabled) {
            return new WP_Error('autofill_disabled', __('Customer phone lookup is disabled for this campaign.', 'op-checkoutbridge'), array('status' => 403));
        }

        // Query last matching order with this billing phone across ALL order statuses (HPOS & legacy compatible)
        $phone_variants = OP_CB_Security::generate_phone_variants($normalized_phone);

        $orders = wc_get_orders(array(
            'limit'         => 5,
            'status'        => 'any',
            'orderby'       => 'date',
            'order'         => 'DESC',
            'billing_phone' => $phone_variants,
        ));

        // Fallback for custom metadata setups
        if (empty($orders)) {
            $orders = wc_get_orders(array(
                'limit'        => 5,
                'status'       => 'any',
                'orderby'      => 'date',
                'order'        => 'DESC',
                'meta_key'     => '_billing_phone',
                'meta_value'   => $phone_variants,
                'meta_compare' => 'IN'
            ));
        }

        if (empty($orders)) {
            return array(
                'success' => true,
                'found'   => false,
                'message' => __('No previous order found for this phone number.', 'op-checkoutbridge')
            );
        }

        $matched_order = reset($orders);
        $first_name    = $matched_order->get_billing_first_name();
        $last_name     = $matched_order->get_billing_last_name();
        $full_name     = trim($first_name . ' ' . $last_name);

        return array(
            'success'  => true,
            'found'    => true,
            'customer' => array(
                'full_name' => $full_name,
                'phone'     => $matched_order->get_billing_phone(),
                'address'   => $matched_order->get_billing_address_1(),
                'city'      => $matched_order->get_billing_city(),
            ),
            'stats' => array(
                'total_orders' => count($orders)
            )
        );
    }
}
