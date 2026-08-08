<?php
if (!defined('ABSPATH')) {
    exit;
}

$stats    = OP_CB_Bridge_Repository::get_stats();
$wc_active = OP_CB_Plugin::is_woocommerce_active();
$rest_url  = esc_url_raw(rest_url('checkoutbridge/v1/'));
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
                <p class="op-cb-subtitle"><?php esc_html_e('Secure WooCommerce bridge for external landing pages', 'checkoutbridge'); ?></p>
            </div>
        </div>
        <div class="op-cb-header-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=checkoutbridge-docs')); ?>" class="button button-secondary">
                <i class="fa-solid fa-book" style="margin-right:4px;"></i>
                <?php esc_html_e('Docs', 'checkoutbridge'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=checkoutbridge-bridges&action=add')); ?>" class="button button-primary">
                <i class="fa-solid fa-plus" style="margin-right:4px;"></i>
                <?php esc_html_e('New Bridge', 'checkoutbridge'); ?>
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
                <span class="op-cb-stat-value"><?php echo $wc_active ? 'WC Connected' : 'WC Offline'; ?></span>
                <span class="op-cb-stat-label"><?php esc_html_e('WooCommerce Status', 'checkoutbridge'); ?></span>
            </div>
        </div>

        <div class="op-cb-stat-card op-cb-stat-landings">
            <div class="op-cb-stat-top">
                <div class="op-cb-stat-icon">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
            </div>
            <div class="op-cb-stat-bottom">
                <span class="op-cb-stat-value"><?php echo esc_html($stats['total_landings']); ?></span>
                <span class="op-cb-stat-label"><?php esc_html_e('Total Bridges', 'checkoutbridge'); ?></span>
            </div>
        </div>

        <div class="op-cb-stat-card op-cb-stat-active">
            <div class="op-cb-stat-top">
                <div class="op-cb-stat-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="op-cb-stat-bottom">
                <span class="op-cb-stat-value"><?php echo esc_html($stats['active_landings']); ?></span>
                <span class="op-cb-stat-label"><?php esc_html_e('Active Bridges', 'checkoutbridge'); ?></span>
            </div>
        </div>

        <div class="op-cb-stat-card op-cb-stat-orders">
            <div class="op-cb-stat-top">
                <div class="op-cb-stat-icon">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
            <div class="op-cb-stat-bottom">
                <span class="op-cb-stat-value"><?php echo esc_html($stats['orders_created']); ?></span>
                <span class="op-cb-stat-label"><?php esc_html_e('Orders Processed', 'checkoutbridge'); ?></span>
            </div>
        </div>

    </div>

    <!-- ── System Status ── -->
    <div class="op-cb-card op-cb-mt-4">
        <div class="op-cb-card-header">
            <h2>
                <i class="fa-solid fa-shield-halved"></i>
                <?php esc_html_e('System & Security Status', 'checkoutbridge'); ?>
            </h2>
        </div>
        <div class="op-cb-card-body">
            <table class="op-cb-info-table">
                <tbody>
                    <tr>
                        <td><?php esc_html_e('Plugin Version', 'checkoutbridge'); ?></td>
                        <td><code><?php echo esc_html(OP_CB_VERSION); ?></code></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('PHP Version', 'checkoutbridge'); ?></td>
                        <td><code><?php echo esc_html(PHP_VERSION); ?></code></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('REST Namespace', 'checkoutbridge'); ?></td>
                        <td><code>checkoutbridge/v1</code></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('REST Base URL', 'checkoutbridge'); ?></td>
                        <td>
                            <code><?php echo esc_html($rest_url); ?></code>
                            <button type="button" class="op-cb-btn-icon op-cb-btn-copy" data-clipboard="<?php echo esc_attr($rest_url); ?>" style="margin-left:0.375em;">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Token Security', 'checkoutbridge'); ?></td>
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
                    <?php esc_html_e('API Endpoint Diagnostic', 'checkoutbridge'); ?>
                </h2>
                <p class="op-cb-diagnostic-desc">
                    <?php esc_html_e('Confirm that the WordPress REST API is reachable and the checkoutbridge/v1 namespace is responding correctly.', 'checkoutbridge'); ?>
                </p>
            </div>
            <div class="op-cb-diagnostic-right">
                <button type="button" id="op_cb_btn_test_api" class="button button-secondary">
                    <i class="fa-solid fa-arrows-rotate" style="margin-right:4px;"></i>
                    <?php esc_html_e('Run Diagnostic', 'checkoutbridge'); ?>
                </button>
            </div>
        </div>
    </div>

</div>
