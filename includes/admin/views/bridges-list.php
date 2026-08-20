<?php
if (!defined('ABSPATH')) {
    exit;
}

$op_cb_bridges = OP_CB_Bridge_Repository::get_all();
?>

<div class="wrap op-cb-wrap" id="op_cb_bridges_list">

    <!-- ── Header ── -->
    <div class="op-cb-header op-cb-header-compact">
        <div class="op-cb-brand">
            <div class="op-cb-brand-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <h1><?php esc_html_e('Bridges Manager', 'op-checkoutbridge'); ?></h1>
                <p class="op-cb-subtitle"><?php esc_html_e('Manage bridge campaigns, assigned products, and op_cb_ token keys', 'op-checkoutbridge'); ?></p>
            </div>
        </div>
        <div class="op-cb-header-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=checkoutbridge-bridges&action=add')); ?>" class="button button-primary">
                <i class="fa-solid fa-plus" style="margin-right:4px;"></i>
                <?php esc_html_e('Add New Bridge', 'op-checkoutbridge'); ?>
            </a>
        </div>
    </div>

    <div class="op-cb-card">
        <div class="op-cb-card-body op-cb-p-0">
            <?php if (empty($op_cb_bridges)) : ?>

                <!-- Empty State -->
                <div class="op-cb-empty-state">
                    <div class="op-cb-empty-icon">
                        <i class="fa-solid fa-inbox"></i>
                    </div>
                    <h3><?php esc_html_e('No bridge campaigns yet', 'op-checkoutbridge'); ?></h3>
                    <p><?php esc_html_e('Create your first bridge campaign to start connecting external pages with WooCommerce orders.', 'op-checkoutbridge'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=checkoutbridge-bridges&action=add')); ?>" class="button button-primary" style="margin-top:1em;">
                        <i class="fa-solid fa-plus" style="margin-right:4px;"></i>
                        <?php esc_html_e('Create First Bridge', 'op-checkoutbridge'); ?>
                    </a>
                </div>

            <?php else : ?>

                <!-- Table Filter Controls -->
                <div class="op-cb-table-controls">
                    <div class="op-cb-search-box" style="position:relative;display:inline-block;width:240px;">
                        <input
                            type="text"
                            id="op_cb_table_search"
                            class="op-cb-input has-icon-right"
                            style="width: 100%;"
                            placeholder="<?php esc_attr_e('Search bridges…', 'op-checkoutbridge'); ?>"
                        >
                        <i class="fa-solid fa-magnifying-glass" style="position:absolute;right:0.75em;top:50%;transform:translateY(-50%);color:var(--cb-text-400);pointer-events:none;"></i>
                    </div>
                    <div class="op-cb-filter-group">
                        <select id="op_cb_filter_status" class="op-cb-select">
                            <option value="all"><?php esc_html_e('All Statuses', 'op-checkoutbridge'); ?></option>
                            <option value="active"><?php esc_html_e('Active', 'op-checkoutbridge'); ?></option>
                            <option value="inactive"><?php esc_html_e('Inactive', 'op-checkoutbridge'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Bridges Data Table -->
                <div class="op-cb-table-responsive">
                    <table class="op-cb-table" id="op_cb_landings_table">
                        <thead>
                            <tr>
                                <th style="width:22%;"><?php esc_html_e('Bridge Campaign', 'op-checkoutbridge'); ?></th>
                                <th style="width:24%;"><?php esc_html_e('Bridge Token', 'op-checkoutbridge'); ?></th>
                                <th style="width:10%;"><?php esc_html_e('Products', 'op-checkoutbridge'); ?></th>
                                <th style="width:22%;"><?php esc_html_e('Thank You URL', 'op-checkoutbridge'); ?></th>
                                <th style="width:10%;"><?php esc_html_e('Status', 'op-checkoutbridge'); ?></th>
                                <th style="width:12%;text-align:start;"><?php esc_html_e('Actions', 'op-checkoutbridge'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($op_cb_bridges as $op_cb_l) :
                            $op_cb_masked_token = mb_strlen($op_cb_l['token']) > 12
                                ? mb_substr($op_cb_l['token'], 0, 6) . '••••••••' . mb_substr($op_cb_l['token'], -4)
                                : 'op_cb_••••••••';
                            $op_cb_product_count = count($op_cb_l['assigned_products']);
                            $op_cb_token_b64     = base64_encode($op_cb_l['token']);

                            $op_cb_raw_name       = !empty($op_cb_l['name']) ? $op_cb_l['name'] : '-';
                            $op_cb_truncated_name = (function_exists('mb_strimwidth'))
                                ? mb_strimwidth($op_cb_raw_name, 0, 26, '…')
                                : (strlen($op_cb_raw_name) > 26 ? substr($op_cb_raw_name, 0, 26) . '…' : $op_cb_raw_name);

                            $op_cb_raw_ty_url       = !empty($op_cb_l['thank_you_url']) ? $op_cb_l['thank_you_url'] : '-';
                            $op_cb_truncated_ty_url = (function_exists('mb_strimwidth'))
                                ? mb_strimwidth($op_cb_raw_ty_url, 0, 26, '…')
                                : (strlen($op_cb_raw_ty_url) > 26 ? substr($op_cb_raw_ty_url, 0, 26) . '…' : $op_cb_raw_ty_url);
                        ?>
                            <tr data-status="<?php echo esc_attr($op_cb_l['status']); ?>">

                                <td class="op-cb-landing-name">
                                    <strong>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=checkoutbridge-bridges&action=edit&id=' . $op_cb_l['id'])); ?>">
                                            <?php echo esc_html($op_cb_truncated_name); ?>
                                        </a>
                                    </strong>
                                </td>

                                <td>
                                    <div class="op-cb-token-chip">
                                        <code><?php echo esc_html($op_cb_masked_token); ?></code>
                                        <button
                                            type="button"
                                            class="op-cb-btn-icon op-cb-btn-copy"
                                            data-cb-key="<?php echo esc_attr($op_cb_token_b64); ?>"
                                        >
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                    </div>
                                </td>

                                <td>
                                    <span class="op-cb-badge op-cb-badge-primary">
                                        <?php echo esc_html($op_cb_product_count); ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="op-cb-truncate">
                                        <?php echo esc_html($op_cb_truncated_ty_url); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($op_cb_l['status'] === 'active') : ?>
                                        <span class="op-cb-status-pill op-cb-status-active"><?php esc_html_e('Active', 'op-checkoutbridge'); ?></span>
                                    <?php else : ?>
                                        <span class="op-cb-status-pill op-cb-status-inactive"><?php esc_html_e('Inactive', 'op-checkoutbridge'); ?></span>
                                    <?php endif; ?>
                                </td>

                                <td style="text-align:start;">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=checkoutbridge-bridges&action=edit&id=' . $op_cb_l['id'])); ?>" class="button button-small">
                                        <i class="fa-solid fa-pen-to-square" style="margin-right:0.125em;"></i>
                                        <?php esc_html_e('Edit', 'op-checkoutbridge'); ?>
                                    </a>
                                    <?php
                                    $op_cb_revoke_url = wp_nonce_url(
                                        admin_url('admin.php?page=checkoutbridge-bridges&action=revoke_token&id=' . $op_cb_l['id']),
                                        'op_cb_revoke_token_' . $op_cb_l['id']
                                    );
                                    $op_cb_delete_url = wp_nonce_url(
                                        admin_url('admin.php?page=checkoutbridge-bridges&action=delete_bridge&id=' . $op_cb_l['id']),
                                        'op_cb_delete_bridge_' . $op_cb_l['id']
                                    );
                                    ?>
                                    <a href="<?php echo esc_url($op_cb_revoke_url); ?>" class="button button-small op-cb-btn-revoke">
                                        <i class="fa-solid fa-key" style="margin-right:0.125em;"></i>
                                        <?php esc_html_e('Revoke', 'op-checkoutbridge'); ?>
                                    </a>
                                    <a href="<?php echo esc_url($op_cb_delete_url); ?>" class="button button-small op-cb-btn-delete">
                                        <i class="fa-solid fa-trash-can" style="margin-right:2px;"></i>
                                        <?php esc_html_e('Delete', 'op-checkoutbridge'); ?>
                                    </a>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </div>
    </div>

</div>
