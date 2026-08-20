<?php
if (!defined('ABSPATH')) {
    exit;
}

$op_cb_stats     = OP_CB_Bridge_Repository::get_stats();
$op_cb_wc_active = OP_CB_Plugin::is_woocommerce_active();
$op_cb_rest_url  = esc_url_raw(rest_url('checkoutbridge/v1/'));
?>

<div class="wrap op-cb-wrap" id="op_cb_dashboard">

    <!-- ── Header ── -->
    <div class="op-cb-header">
        <div class="op-cb-brand">
            <div class="op-cb-brand-icon">
                <i class="fa-solid fa-code-compare"></i>
            </div>
            <div>
                <h1>
                    CheckoutBridge
                    <span class="op-cb-badge op-cb-badge-secondary">v<?php echo esc_html(OP_CB_VERSION); ?></span>
                </h1>
                <p class="op-cb-subtitle"><?php esc_html_e('Secure WooCommerce bridge for external landing pages', 'op-checkoutbridge'); ?></p>
            </div>
        </div>
        <div class="op-cb-header-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=checkoutbridge-docs')); ?>" class="button button-secondary">
                <i class="fa-solid fa-book" style="margin-right:4px;"></i>
                <?php esc_html_e('Docs', 'op-checkoutbridge'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=checkoutbridge-bridges&action=add')); ?>" class="button button-primary">
                <i class="fa-solid fa-plus" style="margin-right:4px;"></i>
                <?php esc_html_e('New Bridge', 'op-checkoutbridge'); ?>
            </a>
        </div>
    </div>

    <!-- ── Stat Cards ── -->
    <div class="op-cb-grid op-cb-grid-4">

        <div class="op-cb-stat-card op-cb-stat-wc">
            <div class="op-cb-stat-top">
                <div class="op-cb-stat-icon">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
            </div>
            <div class="op-cb-stat-bottom">
                <span class="op-cb-stat-value"><?php echo $op_cb_wc_active ? esc_html__('WC Connected', 'op-checkoutbridge') : esc_html__('WC Offline', 'op-checkoutbridge'); ?></span>
                <span class="op-cb-stat-label"><?php esc_html_e('WooCommerce Status', 'op-checkoutbridge'); ?></span>
            </div>
        </div>

        <div class="op-cb-stat-card op-cb-stat-landings">
            <div class="op-cb-stat-top">
                <div class="op-cb-stat-icon">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
            </div>
            <div class="op-cb-stat-bottom">
                <span class="op-cb-stat-value"><?php echo esc_html($op_cb_stats['total_landings']); ?></span>
                <span class="op-cb-stat-label"><?php esc_html_e('Total Bridges', 'op-checkoutbridge'); ?></span>
            </div>
        </div>

        <div class="op-cb-stat-card op-cb-stat-active">
            <div class="op-cb-stat-top">
                <div class="op-cb-stat-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="op-cb-stat-bottom">
                <span class="op-cb-stat-value"><?php echo esc_html($op_cb_stats['active_landings']); ?></span>
                <span class="op-cb-stat-label"><?php esc_html_e('Active Bridges', 'op-checkoutbridge'); ?></span>
            </div>
        </div>

        <div class="op-cb-stat-card op-cb-stat-orders">
            <div class="op-cb-stat-top">
                <div class="op-cb-stat-icon">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
            <div class="op-cb-stat-bottom">
                <span class="op-cb-stat-value"><?php echo esc_html($op_cb_stats['orders_created']); ?></span>
                <span class="op-cb-stat-label"><?php esc_html_e('Orders Processed', 'op-checkoutbridge'); ?></span>
            </div>
        </div>

    </div>

    <!-- ── System Status ── -->
    <div class="op-cb-card op-cb-mt-4">
        <div class="op-cb-card-header">
            <h2>
                <i class="fa-solid fa-shield-halved"></i>
                <?php esc_html_e('System & Security Status', 'op-checkoutbridge'); ?>
            </h2>
        </div>
        <div class="op-cb-card-body">
            <table class="op-cb-info-table">
                <tbody>
                    <tr>
                        <td><?php esc_html_e('Plugin Version', 'op-checkoutbridge'); ?></td>
                        <td><code><?php echo esc_html(OP_CB_VERSION); ?></code></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('PHP Version', 'op-checkoutbridge'); ?></td>
                        <td><code><?php echo esc_html(PHP_VERSION); ?></code></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('REST Namespace', 'op-checkoutbridge'); ?></td>
                        <td><code>checkoutbridge/v1</code></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('REST Base URL', 'op-checkoutbridge'); ?></td>
                        <td>
                            <code><?php echo esc_html($op_cb_rest_url); ?></code>
                            <button type="button" class="op-cb-btn-icon op-cb-btn-copy" data-clipboard="<?php echo esc_attr($op_cb_rest_url); ?>" style="margin-left:0.375em;">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Token Security', 'op-checkoutbridge'); ?></td>
                        <td><span class="op-cb-badge op-cb-badge-success">HMAC-SHA256</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Full Width Single Line API Diagnostic Card ── -->
    <div class="op-cb-card op-cb-mt-4">
        <div class="op-cb-card-body op-cb-diagnostic-full">
            <div class="op-cb-diagnostic-left">
                <h2 class="op-cb-diagnostic-title">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    <?php esc_html_e('API Endpoint Diagnostic', 'op-checkoutbridge'); ?>
                </h2>
                <p class="op-cb-diagnostic-desc">
                    <?php esc_html_e('Confirm that the WordPress REST API is reachable and the checkoutbridge/v1 namespace is responding correctly.', 'op-checkoutbridge'); ?>
                </p>
            </div>
            <div class="op-cb-diagnostic-right">
                <button type="button" id="op_cb_btn_test_api" class="button button-secondary">
                    <i class="fa-solid fa-arrows-rotate" style="margin-right:4px;"></i>
                    <?php esc_html_e('Run Diagnostic', 'op-checkoutbridge'); ?>
                </button>
            </div>
        </div>
    </div>

</div>
