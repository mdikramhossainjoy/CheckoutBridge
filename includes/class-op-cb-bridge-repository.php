<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OP_CB_Bridge_Repository Class
 * Manages database CRUD operations for bridges in wp_op_cb_landings
 */
class OP_CB_Bridge_Repository {

    /**
     * Get table name with WordPress prefix
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'op_cb_landings';
    }

    /**
     * Generate unique bridge token starting with op_cb_
     */
    public static function generate_token() {
        try {
            return 'op_cb_' . bin2hex(random_bytes(16));
        } catch (\Throwable $e) {
            // Fallback: use WordPress salt-enhanced random generation with extra entropy
            $entropy  = wp_generate_password(32, true, true);
            $entropy .= microtime(true);
            $entropy .= uniqid('op_cb_', true);
            $entropy .= defined('AUTH_SALT') ? AUTH_SALT : '';
            return 'op_cb_' . substr(hash('sha256', $entropy), 0, 32);
        }
    }

    /**
     * Retrieve all bridges
     */
    public static function get_all($status = null) {
        global $wpdb;
        $table = self::get_table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        if ($status) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE status = %s ORDER BY id DESC", $status), ARRAY_A);
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $results = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A);
        }

        return array_map(array(__CLASS__, 'format_landing'), $results ? $results : array());
    }

    /**
     * Get single bridge by ID
     */
    public static function get_by_id($id) {
        global $wpdb;
        $table = self::get_table_name();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
        return $row ? self::format_landing($row) : null;
    }

    /**
     * Get single bridge by Token
     */
    public static function get_by_token($token) {
        global $wpdb;
        $table = self::get_table_name();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE token = %s", $token), ARRAY_A);
        return $row ? self::format_landing($row) : null;
    }

    /**
     * Insert new bridge record
     */
    public static function create($data) {
        global $wpdb;
        $table = self::get_table_name();

        $token = !empty($data['token']) ? sanitize_text_field($data['token']) : self::generate_token();

        $assigned_products = isset($data['assigned_products']) && is_array($data['assigned_products']) 
            ? json_encode(array_map('intval', $data['assigned_products'])) 
            : '[]';

        $shipping_options = isset($data['shipping_options']) && is_array($data['shipping_options']) 
            ? $data['shipping_options'] 
            : array();

        if (isset($data['phone_velocity_limit'])) {
            $shipping_options['phone_velocity_limit'] = intval($data['phone_velocity_limit']);
        }
        if (isset($data['ip_velocity_limit'])) {
            $shipping_options['ip_velocity_limit'] = intval($data['ip_velocity_limit']);
        }
        if (isset($data['velocity_hours'])) {
            $shipping_options['velocity_hours'] = intval($data['velocity_hours']);
        }
        if (isset($data['enable_autofill_lookup'])) {
            $shipping_options['enable_autofill_lookup'] = intval($data['enable_autofill_lookup']);
        }

        $insert_data = array(
            'name' => isset($data['name']) ? sanitize_text_field($data['name']) : '',
            'token' => $token,
            'allowed_origins' => sanitize_textarea_field(isset($data['allowed_origins']) ? $data['allowed_origins'] : ''),
            'assigned_products' => $assigned_products,
            'shipping_options' => json_encode($shipping_options),
            'thank_you_url' => esc_url_raw(isset($data['thank_you_url']) ? $data['thank_you_url'] : ''),
            'status' => isset($data['status']) && in_array($data['status'], array('active', 'inactive')) ? $data['status'] : 'active',
            'orders_count' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        $result = $wpdb->insert($table, $insert_data);
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update existing bridge record
     */
    public static function update($id, $data) {
        global $wpdb;
        $table = self::get_table_name();

        $update_data = array(
            'updated_at' => current_time('mysql')
        );

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['allowed_origins'])) {
            $update_data['allowed_origins'] = sanitize_textarea_field($data['allowed_origins']);
        }
        if (isset($data['assigned_products']) && is_array($data['assigned_products'])) {
            $update_data['assigned_products'] = json_encode(array_map('intval', $data['assigned_products']));
        }
        $existing = self::get_by_id($id);
        $shipping_options = ($existing && !empty($existing['shipping_options'])) ? $existing['shipping_options'] : array();

        if (isset($data['shipping_options']) && is_array($data['shipping_options'])) {
            $shipping_options = array_merge($shipping_options, $data['shipping_options']);
        }
        if (isset($data['phone_velocity_limit'])) {
            $shipping_options['phone_velocity_limit'] = intval($data['phone_velocity_limit']);
        }
        if (isset($data['ip_velocity_limit'])) {
            $shipping_options['ip_velocity_limit'] = intval($data['ip_velocity_limit']);
        }
        if (isset($data['velocity_hours'])) {
            $shipping_options['velocity_hours'] = intval($data['velocity_hours']);
        }
        if (isset($data['enable_autofill_lookup'])) {
            $shipping_options['enable_autofill_lookup'] = intval($data['enable_autofill_lookup']);
        }
        $update_data['shipping_options'] = json_encode($shipping_options);

        if (isset($data['thank_you_url'])) {
            $update_data['thank_you_url'] = esc_url_raw($data['thank_you_url']);
        }
        if (isset($data['status'])) {
            $update_data['status'] = in_array($data['status'], array('active', 'inactive')) ? $data['status'] : 'active';
        }

        $result = $wpdb->update($table, $update_data, array('id' => intval($id)));
        return $result !== false;
    }

    /**
     * Delete bridge record by ID
     */
    public static function delete($id) {
        global $wpdb;
        $table = self::get_table_name();
        return $wpdb->delete($table, array('id' => intval($id)));
    }

    /**
     * Revoke existing token and generate a new token for a bridge
     */
    public static function regenerate_token($id) {
        global $wpdb;
        $table = self::get_table_name();
        $new_token = self::generate_token();

        $updated = $wpdb->update(
            $table,
            array(
                'token'      => $new_token,
                'updated_at' => current_time('mysql')
            ),
            array('id' => intval($id))
        );

        return $updated !== false ? $new_token : false;
    }

    /**
     * Increment order count for a bridge
     */
    public static function increment_orders_count($id) {
        global $wpdb;
        $table = self::get_table_name();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->query($wpdb->prepare("UPDATE {$table} SET orders_count = orders_count + 1 WHERE id = %d", $id));
    }

    /**
     * Get system statistics for Dashboard
     */
    public static function get_stats() {
        global $wpdb;
        $table = self::get_table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $total_landings = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $active_landings = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active'");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $total_orders = (int) $wpdb->get_var("SELECT SUM(orders_count) FROM {$table}");

        return array(
            'total_landings' => $total_landings,
            'active_landings' => $active_landings,
            'orders_created' => $total_orders
        );
    }

    /**
     * Helper to decode JSON columns into arrays
     */
    private static function format_landing($row) {
        if (!$row) return null;
        
        $row['id'] = (int) $row['id'];
        $row['orders_count'] = (int) $row['orders_count'];
        $row['assigned_products'] = !empty($row['assigned_products']) ? json_decode($row['assigned_products'], true) : array();
        $row['shipping_options'] = !empty($row['shipping_options']) ? json_decode($row['shipping_options'], true) : array();
        
        if (!is_array($row['assigned_products'])) {
            $row['assigned_products'] = array();
        }
        if (!is_array($row['shipping_options'])) {
            $row['shipping_options'] = array();
        }

        $row['phone_velocity_limit']  = isset($row['shipping_options']['phone_velocity_limit']) ? intval($row['shipping_options']['phone_velocity_limit']) : 1;
        $row['ip_velocity_limit']     = isset($row['shipping_options']['ip_velocity_limit']) ? intval($row['shipping_options']['ip_velocity_limit']) : 3;
        $row['velocity_hours']        = isset($row['shipping_options']['velocity_hours']) ? intval($row['shipping_options']['velocity_hours']) : 24;
        $row['enable_autofill_lookup'] = isset($row['shipping_options']['enable_autofill_lookup']) ? (intval($row['shipping_options']['enable_autofill_lookup']) === 1) : true;

        return $row;
    }
}

/**
 * Backwards compatibility alias class
 */
class OP_CB_Landing_Repository extends OP_CB_Bridge_Repository {}
