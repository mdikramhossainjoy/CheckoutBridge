<?php
if (!defined('ABSPATH')) {
    exit;
}

$bridges = OP_CB_Bridge_Repository::get_all();
?>

<div class="wrap op-cb-wrap" id="op_cb_bridges_list">

    <!-- ── Header ── -->
    <div class="op-cb-header op-cb-header-compact">
        <div class="op-cb-brand">
            <div class="op-cb-brand-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <h1><?php esc_html_e('Bridges Manager', 'checkoutbridge'); ?></h1>
                <p class="op-cb-subtitle"><?php esc_html_e('Manage bridge campaigns, assigned products, and op_cb_ token keys', 'checkoutbridge'); ?></p>
            </div>
        </div>
        <div class="op-cb-header-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=checkoutbridge-bridges&action=add')); ?>" class="button button-primary">
                <i class="fa-solid fa-plus" style="margin-right:4px;"></i>
                <?php esc_html_e('Add New Bridge', 'checkoutbridge'); ?>
            </a>
        </div>
    </div>

    <div class="op-cb-card">
        <div class="op-cb-card-body op-cb-p-0">
            <?php if (empty($bridges)) : ?>

                <div class="op-cb-empty">
                    <div class="op-cb-empty-icon">
                        <i class="fa-solid fa-folder-open"></i>
                    </div>
                    <h3><?php esc_html_e('No bridge campaigns yet', 'checkoutbridge'); ?></h3>
                    <p><?php esc_html_e('Create your first bridge campaign to start connecting external pages with WooCommerce orders.', 'checkoutbridge'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=checkoutbridge-bridges&action=add')); ?>" class="button button-primary">
                        <i class="fa-solid fa-plus" style="margin-right:4px;"></i>
                        <?php esc_html_e('Create First Bridge', 'checkoutbridge'); ?>
                    </a>
                </div>

            <?php else : ?>

                <!-- Toolbar -->
                <div class="op-cb-toolbar" style="padding: 0.875em 1em 0;">
                    <div class="op-cb-toolbar-left">
                        <div class="op-cb-search-wrap">
                            <input
                                type="text"
                                id="op_cb_search_landings"
                                class="op-cb-input has-icon-right"
                                placeholder="<?php esc_attr_e('Search bridges…', 'checkoutbridge'); ?>"
                            >
                            <i class="fa-solid fa-magnifying-glass" style="position:absolute;right:0.75em;top:50%;transform:translateY(-50%);color:var(--cb-text-400);pointer-events:none;"></i>
                        </div>
                    </div>
                    <div class="op-cb-toolbar-right">
                        <select id="op_cb_filter_status" class="op-cb-select">
                            <option value="all"><?php esc_html_e('All Statuses', 'checkoutbridge'); ?></option>
                            <option value="active"><?php esc_html_e('Active', 'checkoutbridge'); ?></option>
                            <option value="inactive"><?php esc_html_e('Inactive', 'checkoutbridge'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="op-cb-table-wrap" style="margin-top:0.875em;">
                    <table class="op-cb-table" id="op_cb_landings_table">
                        <thead>
                            <tr>
                                <th style="width:22%;"><?php esc_html_e('Bridge Campaign', 'checkoutbridge'); ?></th>
                                <th style="width:24%;"><?php esc_html_e('Bridge Token', 'checkoutbridge'); ?></th>
                                <th style="width:10%;"><?php esc_html_e('Products', 'checkoutbridge'); ?></th>
                                <th style="width:22%;"><?php esc_html_e('Thank You URL', 'checkoutbridge'); ?></th>
                                <th style="width:10%;"><?php esc_html_e('Status', 'checkoutbridge'); ?></th>
                                <th style="width:12%;text-align:start;"><?php esc_html_e('Actions', 'checkoutbridge'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($bridges as $l) :
                            $masked_token = mb_strlen($l['token']) > 12
                                ? mb_substr($l['token'], 0, 6) . '••••••••' . mb_substr($l['token'], -4)
                                : 'op_cb_••••••••';
                            $product_count = count($l['assigned_products']);
                            $token_b64 = base64_encode($l['token']);

                            $raw_name       = !empty($l['name']) ? $l['name'] : '-';
                            $truncated_name = (function_exists('mb_strimwidth'))
                                ? mb_strimwidth($raw_name, 0, 26, '…')
                                : (strlen($raw_name) > 26 ? substr($raw_name, 0, 26) . '…' : $raw_name);

                            $raw_ty_url       = !empty($l['thank_you_url']) ? $l['thank_you_url'] : '-';
                            $truncated_ty_url = (function_exists('mb_strimwidth'))
                                ? mb_strimwidth($raw_ty_url, 0, 26, '…')
                                : (strlen($raw_ty_url) > 26 ? substr($raw_ty_url, 0, 26) . '…' : $raw_ty_url);
                        ?>
                            <tr data-status="<?php echo esc_attr($l['status']); ?>">

                                <td class="op-cb-landing-name">
                                    <strong>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=checkoutbridge-bridges&action=edit&id=' . $l['id'])); ?>">
                                            <?php echo esc_html($truncated_name); ?>
                                        </a>
                                    </strong>
                                </td>

                                <td>
                                    <div class="op-cb-token-chip">
                                        <code><?php echo esc_html($masked_token); ?></code>
                                        <button
                                            type="button"
                                            class="op-cb-btn-icon op-cb-btn-copy"
                                            data-cb-key="<?php echo esc_attr($token_b64); ?>"
                                        >
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                    </div>
                                </td>

                                <td>
                                    <span class="op-cb-badge op-cb-badge-primary">
                                        <?php echo esc_html($product_count); ?>P
                                    </span>
                                </td>

                                <td>
                                    <span class="op-cb-truncate">
                                        <?php echo esc_html($truncated_ty_url); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($l['status'] === 'active') : ?>
                                        <span class="op-cb-status-pill op-cb-status-active"><?php esc_html_e('Active', 'checkoutbridge'); ?></span>
                                    <?php else : ?>
                                        <span class="op-cb-status-pill op-cb-status-inactive"><?php esc_html_e('Inactive', 'checkoutbridge'); ?></span>
                                    <?php endif; ?>
                                </td>

                                <td style="text-align:start;">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=checkoutbridge-bridges&action=edit&id=' . $l['id'])); ?>" class="button button-small">
                                        <i class="fa-solid fa-pen-to-square" style="margin-right:0.125em;"></i>
                                        <?php esc_html_e('Edit', 'checkoutbridge'); ?>
                                    </a>
                                    <?php
                                    $revoke_url = wp_nonce_url(
                                        admin_url('admin.php?page=checkoutbridge-bridges&action=revoke_token&id=' . $l['id']),
                                        'op_cb_revoke_token_' . $l['id']
                                    );
                                    $delete_url = wp_nonce_url(
                                        admin_url('admin.php?page=checkoutbridge-bridges&action=delete_bridge&id=' . $l['id']),
                                        'op_cb_delete_bridge_' . $l['id']
                                    );
                                    ?>
                                    <a href="<?php echo esc_url($revoke_url); ?>" class="button button-small op-cb-btn-revoke">
                                        <i class="fa-solid fa-key" style="margin-right:0.125em;"></i>
                                        <?php esc_html_e('Revoke', 'checkoutbridge'); ?>
                                    </a>
                                    <a href="<?php echo esc_url($delete_url); ?>" class="button button-small op-cb-btn-delete">
                                        <i class="fa-solid fa-trash-can" style="margin-right:2px;"></i>
                                        <?php esc_html_e('Delete', 'checkoutbridge'); ?>
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
