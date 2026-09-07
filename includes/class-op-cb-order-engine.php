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

        // Process Quantity Package Deal / Tier Pricing Verification & Application
        $tier_id           = isset($raw_params['tier_id']) ? sanitize_key($raw_params['tier_id']) : '';
        $is_discount_order = false;
        $matched_tier      = null;

        if (!empty($tier_id)) {
            if (empty($landing['enable_quantity_pricing']) || empty($landing['quantity_pricing_tiers']) || !is_array($landing['quantity_pricing_tiers'])) {
                $order->delete(true);
                return new WP_Error('quantity_pricing_disabled', __('Quantity package pricing is not enabled for this campaign.', 'op-checkoutbridge'), array('status' => 400));
            }

            foreach ($landing['quantity_pricing_tiers'] as $tier) {
                if (isset($tier['tier_id']) && $tier['tier_id'] === $tier_id) {
                    $matched_tier = $tier;
                    break;
                }
            }

            if (!$matched_tier) {
                $order->delete(true);
                /* translators: %s: Tier ID */
                return new WP_Error('invalid_tier_id', sprintf(__('Invalid package deal tier ID: "%s".', 'op-checkoutbridge'), $tier_id), array('status' => 400));
            }

            // Validate that total quantity of assigned products is at least the tier's required quantity
            $total_qty = 0;
            foreach ($order_line_items as $line_item) {
                $total_qty += $line_item['quantity'];
            }

            $required_qty = isset($matched_tier['quantity']) ? intval($matched_tier['quantity']) : 1;
            if ($total_qty < $required_qty) {
                $order->delete(true);
                /* translators: 1: required quantity, 2: selected quantity */
                return new WP_Error('insufficient_tier_quantity', sprintf(__('This package deal requires at least %1$d items. You selected %2$d.', 'op-checkoutbridge'), $required_qty, $total_qty), array('status' => 400));
            }

            $is_discount_order = true;
        }

        if ($is_discount_order && $matched_tier) {
            // Calculate regular items subtotal
            $regular_subtotal = 0.0;
            foreach ($order_line_items as $line_item) {
                $regular_subtotal += floatval($line_item['product']->get_price()) * $line_item['quantity'];
            }

            $package_price   = floatval($matched_tier['price']);
            $discount_amount = max(0.0, round($regular_subtotal - $package_price, 2));

            if ($discount_amount > 0 && class_exists('WC_Order_Item_Fee')) {
                $fee_item = new \WC_Order_Item_Fee();
                /* translators: %s: Deal ID */
                $fee_name = sprintf(__('Package Deal Discount (%s)', 'op-checkoutbridge'), $matched_tier['tier_id']);
                $fee_item->set_name($fee_name);
                $fee_item->set_amount(-$discount_amount);
                $fee_item->set_total(-$discount_amount);
                $order->add_item($fee_item);
            }

            $order->update_meta_data('_op_cb_order_type', 'discount');
            $order->update_meta_data('_op_cb_tier_id', $matched_tier['tier_id']);
            $order->update_meta_data('_op_cb_tier_quantity', $matched_tier['quantity']);
            $order->update_meta_data('_op_cb_tier_price', $package_price);
            $order->update_meta_data('_op_cb_discount_amount', $discount_amount);
        } else {
            $order->update_meta_data('_op_cb_order_type', 'normal');
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
        $order_type      = $order->get_meta('_op_cb_order_type') ?: 'normal';
        $tier_id         = $order->get_meta('_op_cb_tier_id') ?: '';
        $package_price   = floatval($order->get_meta('_op_cb_tier_price'));
        $discount_amount = floatval($order->get_meta('_op_cb_discount_amount'));
        if ($discount_amount <= 0) {
            $discount_amount = floatval($order->get_discount_total());
        }

        $order_summary = array(
            'id'             => $order->get_id(),
            'number'         => $order->get_order_number(),
            'status'         => $order->get_status(),
            'order_type'     => $order_type,
            'tier_id'        => $tier_id,
            'subtotal'       => floatval($order->get_subtotal()),
            'package_price'  => $package_price > 0 ? $package_price : null,
            'shipping'       => $shipping_cost,
            'discount_total' => $discount_amount,
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
}

