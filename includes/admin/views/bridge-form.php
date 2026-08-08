<?php
if (!defined('ABSPATH')) {
    exit;
}

$is_edit    = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
$landing_id = $is_edit ? intval($_GET['id']) : 0;
$landing    = ($is_edit && ($found = OP_CB_Bridge_Repository::get_by_id($landing_id))) ? $found : array();

$name              = isset($landing['name'])              ? $landing['name']              : '';
$token             = isset($landing['token'])             ? $landing['token']             : OP_CB_Bridge_Repository::generate_token();
$allowed_origins   = isset($landing['allowed_origins'])   ? $landing['allowed_origins']   : '';
$assigned_products = isset($landing['assigned_products']) ? $landing['assigned_products'] : array();
$thank_you_url     = isset($landing['thank_you_url'])     ? $landing['thank_you_url']     : '';
$status            = isset($landing['status'])            ? $landing['status']            : 'active';

// Fetch WooCommerce Products with Lightweight Transient Caching for Instant Page Loading
$wc_products = array();
if (class_exists('WooCommerce')) {
    $cached = get_transient('op_cb_products_light_cache');
    if (false !== $cached && is_array($cached)) {
        $wc_products = $cached;
    } else {
        $product_ids = get_posts(array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ));
        foreach ($product_ids as $p_id) {
            $p = wc_get_product($p_id);
            if ($p) {
                $img_id      = $p->get_image_id();
                $placeholder = function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src('thumbnail') : '';
                $thumb_src   = $img_id ? wp_get_attachment_image_url($img_id, 'thumbnail') : $placeholder;

                $wc_products[] = array(
                    'id'        => $p_id,
                    'name'      => $p->get_name(),
                    'price'     => $p->get_price_html(),
                    'thumb_src' => $thumb_src,
                );
            }
        }
        set_transient('op_cb_products_light_cache', $wc_products, 600);
    }
}
?>

<div class="wrap op-cb-wrap" id="op_cb_bridge_form">

    <!-- ── Header ── -->
    <div class="op-cb-header op-cb-header-compact">
        <div class="op-cb-brand">
            <div class="op-cb-brand-icon">
                <i class="fa-solid fa-pen-to-square"></i>
            </div>
            <div>
                <h1>
                    <?php echo $is_edit
                        ? esc_html__('Edit Bridge Campaign', 'checkoutbridge')
                        : esc_html__('New Bridge Campaign', 'checkoutbridge'); ?>
                </h1>
                <p class="op-cb-subtitle">
                    <?php esc_html_e('Assign WooCommerce products, configure CORS origins, and set redirect target', 'checkoutbridge'); ?>
                </p>
            </div>
        </div>
        <div class="op-cb-header-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=checkoutbridge-bridges')); ?>" class="button button-secondary">
                <i class="fa-solid fa-arrow-left" style="margin-right:4px;"></i>
                <?php esc_html_e('Back to List', 'checkoutbridge'); ?>
            </a>
        </div>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action"    value="op_cb_save_bridge">
        <input type="hidden" name="landing_id" value="<?php echo esc_attr($landing_id); ?>">
        <?php wp_nonce_field('op_cb_save_bridge_nonce'); ?>

        <div class="op-cb-grid op-cb-grid-main">

            <!-- ── Main Column ── -->
            <div class="op-cb-form-main">

                <!-- Section 1: Campaign Details -->
                <div class="op-cb-card">
                    <div class="op-cb-card-header">
                        <h2>
                            <span class="op-cb-section-num">1</span>
                            <?php esc_html_e('Bridge Details', 'checkoutbridge'); ?>
                        </h2>
                    </div>
                    <div class="op-cb-card-body">

                        <div class="op-cb-form-group">
                            <label for="op_cb_name">
                                <?php esc_html_e('Bridge Name', 'checkoutbridge'); ?>
                                <span class="required">*</span>
                            </label>
                            <input
                                type="text"
                                id="op_cb_name"
                                name="name"
                                class="op-cb-input"
                                value="<?php echo esc_attr($name); ?>"
                                required
                                placeholder="<?php esc_attr_e('e.g. Dubai Summer Offer Bridge', 'checkoutbridge'); ?>"
                            >
                        </div>

                        <div class="op-cb-form-group">
                            <label for="op_cb_thank_you_url">
                                <?php esc_html_e('Thank You Page URL', 'checkoutbridge'); ?>
                                <span class="required">*</span>
                            </label>
                            <input
                                type="url"
                                id="op_cb_thank_you_url"
                                name="thank_you_url"
                                class="op-cb-input"
                                value="<?php echo esc_attr($thank_you_url); ?>"
                                required
                                placeholder="https://yourlanding.com/thank-you.php"
                            >
                            <p class="op-cb-field-hint">
                                <?php esc_html_e('Customers are redirected here after successful order creation, with a signed token appended.', 'checkoutbridge'); ?>
                            </p>
                        </div>

                        <div class="op-cb-form-group">
                            <label for="op_cb_allowed_origins">
                                <?php esc_html_e('Allowed Origins (CORS Whitelist)', 'checkoutbridge'); ?>
                            </label>
                            <textarea
                                id="op_cb_allowed_origins"
                                name="allowed_origins"
                                class="op-cb-textarea"
                                rows="3"
                                placeholder="https://offer1.example.com&#10;https://m.offer1.example.com"
                            ><?php echo esc_textarea($allowed_origins); ?></textarea>
                            <p class="op-cb-field-hint">
                                <?php esc_html_e('Enter allowed domain origins (one per line). Use * to allow any origin.', 'checkoutbridge'); ?>
                            </p>
                        </div>

                    </div>
                </div>

                <!-- Section 2: WooCommerce Products -->
                <div class="op-cb-card">
                    <div class="op-cb-card-header">
                        <div>
                            <h2>
                                <span class="op-cb-section-num">2</span>
                                <?php esc_html_e('Assigned WooCommerce Products', 'checkoutbridge'); ?>
                            </h2>
                            <p style="font-size:12px;color:var(--cb-text-500);margin:2px 0 0 0;">
                                <?php esc_html_e('Used strictly for internal server-side payload validation. Assigned products are never exposed or fetchable over REST API.', 'checkoutbridge'); ?>
                            </p>
                        </div>
                        <span class="op-cb-product-count-badge" id="op_cb_selected_count">
                            0 <?php esc_html_e('selected', 'checkoutbridge'); ?>
                        </span>
                    </div>
                    <div class="op-cb-card-body">

                        <?php if (empty($wc_products)) : ?>
                            <div class="op-cb-empty op-cb-empty-compact">
                                <p><?php esc_html_e('No published WooCommerce products found. Please add products to WooCommerce first.', 'checkoutbridge'); ?></p>
                            </div>
                        <?php else : ?>
                            <div class="op-cb-product-toolbar" style="width: 100%; margin-bottom: 0.75em;">
                                <div class="op-cb-search-wrap" style="width: 100%; max-width: 100%;">
                                    <input
                                        type="text"
                                        id="op_cb_product_search"
                                        class="op-cb-input has-icon-right"
                                        style="width: 100%;"
                                        placeholder="<?php esc_attr_e('Search products by name…', 'checkoutbridge'); ?>"
                                    >
                                    <i class="fa-solid fa-magnifying-glass" style="position:absolute;right:0.75em;top:50%;transform:translateY(-50%);color:var(--cb-text-400);pointer-events:none;"></i>
                                </div>
                                <span class="op-cb-product-hint" id="op_cb_product_count_hint"></span>
                            </div>

                            <div class="op-cb-product-grid">
                                <?php foreach ($wc_products as $p) :
                                    $p_id        = is_array($p) ? $p['id'] : $p->get_id();
                                    $p_name      = is_array($p) ? $p['name'] : $p->get_name();
                                    $p_price     = is_array($p) ? $p['price'] : $p->get_price_html();
                                    $thumb_src   = is_array($p) ? $p['thumb_src'] : ($p->get_image_id() ? wp_get_attachment_image_url($p->get_image_id(), 'thumbnail') : '');
                                    $is_selected = in_array($p_id, $assigned_products, true);
                                ?>
                                    <label class="op-cb-product-item <?php echo $is_selected ? 'is-selected' : ''; ?>">
                                        <input
                                            type="checkbox"
                                            name="assigned_products[]"
                                            class="op-cb-checkbox"
                                            value="<?php echo esc_attr($p_id); ?>"
                                            <?php checked($is_selected); ?>
                                        >
                                        <span class="op-cb-checkbox-box">
                                            <i class="fa-solid fa-check"></i>
                                        </span>
                                        <?php if ($thumb_src) : ?>
                                            <img src="<?php echo esc_url($thumb_src); ?>" alt="<?php echo esc_attr($p_name); ?>" class="op-cb-product-thumb">
                                        <?php else : ?>
                                            <div class="op-cb-product-thumb op-cb-product-thumb-icon">
                                                <i class="fa-solid fa-box"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="op-cb-product-info">
                                            <span class="op-cb-product-name"><?php echo esc_html($p_name); ?></span>
                                            <span class="op-cb-product-id">#<?php echo esc_html($p_id); ?></span>
                                        </div>
                                        <span class="op-cb-product-price"><?php echo wp_kses_post($p_price); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div><!-- /op-cb-form-main -->

            <!-- ── Sidebar ── -->
            <div class="op-cb-form-sidebar">
                <div class="op-cb-card">
                    <div class="op-cb-card-header">
                        <h2>
                            <span class="op-cb-section-num">3</span>
                            <?php esc_html_e('Status & Token', 'checkoutbridge'); ?>
                        </h2>
                    </div>
                    <div class="op-cb-card-body">

                        <!-- Status Toggle -->
                        <div class="op-cb-form-group">
                            <label><?php esc_html_e('Bridge Status', 'checkoutbridge'); ?></label>
                            <div class="op-cb-toggle-row">
                                <div>
                                    <div class="op-cb-toggle-label"><?php esc_html_e('Accepting Orders', 'checkoutbridge'); ?></div>
                                    <div class="op-cb-toggle-desc"><?php esc_html_e('Toggle off to pause this bridge.', 'checkoutbridge'); ?></div>
                                </div>
                                <label class="op-cb-switch">
                                    <input
                                        type="checkbox"
                                        id="op_cb_status_toggle"
                                        <?php checked($status, 'active'); ?>
                                        onchange="document.getElementById('op_cb_status_val').value = this.checked ? 'active' : 'inactive';"
                                    >
                                    <span class="op-cb-slider"></span>
                                </label>
                                <input type="hidden" name="status" id="op_cb_status_val" value="<?php echo esc_attr($status); ?>">
                            </div>
                        </div>

                        <hr class="op-cb-divider">

                        <!-- Global Dual-Shield Anti-Bot Settings -->
                        <div class="op-cb-form-group">
                            <label style="color:var(--cb-indigo-600);font-weight:700;">
                                <i class="fa-solid fa-shield-halved" style="margin-right:4px;"></i>
                                <?php esc_html_e('Dual-Shield Anti-Bot Protection', 'checkoutbridge'); ?>
                            </label>
                            <div style="font-size:12px;color:var(--cb-text-500);margin:4px 0 10px 0;">
                                <?php esc_html_e('Limits COD orders per phone & IP to block spam bots globally.', 'checkoutbridge'); ?>
                            </div>
                            
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
                                <div>
                                    <label for="op_cb_phone_limit" style="font-size:11.5px;font-weight:600;"><?php esc_html_e('Max per Number', 'checkoutbridge'); ?></label>
                                    <input type="number" min="0" max="10" name="phone_velocity_limit" id="op_cb_phone_limit" class="op-cb-input" value="<?php echo esc_attr(isset($landing['phone_velocity_limit']) ? $landing['phone_velocity_limit'] : 1); ?>">
                                </div>
                                <div>
                                    <label for="op_cb_ip_limit" style="font-size:11.5px;font-weight:600;"><?php esc_html_e('Max per IP', 'checkoutbridge'); ?></label>
                                    <input type="number" min="0" max="20" name="ip_velocity_limit" id="op_cb_ip_limit" class="op-cb-input" value="<?php echo esc_attr(isset($landing['ip_velocity_limit']) ? $landing['ip_velocity_limit'] : 3); ?>">
                                </div>
                            </div>
                            <div>
                                <label for="op_cb_velocity_hours" style="font-size:11.5px;font-weight:600;"><?php esc_html_e('Time Window (Hours)', 'checkoutbridge'); ?></label>
                                <input type="number" min="1" max="168" name="velocity_hours" id="op_cb_velocity_hours" class="op-cb-input" value="<?php echo esc_attr(isset($landing['velocity_hours']) ? $landing['velocity_hours'] : 24); ?>">
                            </div>
                        </div>

                        <hr class="op-cb-divider">

                        <!-- Token Field -->
                        <div class="op-cb-form-group">
                            <label for="op_cb_token_display"><?php esc_html_e('Bridge Token Key (op_cb_)', 'checkoutbridge'); ?></label>
                            <div class="op-cb-token-field">
                                <input
                                    type="password"
                                    id="op_cb_token_display"
                                    class="op-cb-input"
                                    value="<?php echo esc_attr($token); ?>"
                                    readonly
                                    autocomplete="off"
                                >
                                <input type="hidden" name="token" id="op_cb_token" value="<?php echo esc_attr($token); ?>">
                                <button
                                    type="button"
                                    id="op_cb_btn_toggle_token"
                                    class="op-cb-btn-icon"
                                >
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button
                                    type="button"
                                    id="op_cb_btn_copy_token"
                                    class="op-cb-btn-icon op-cb-btn-copy"
                                    data-cb-key="<?php echo esc_attr(base64_encode($token)); ?>"
                                >
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                            </div>
                            <p class="op-cb-field-hint">
                                <?php esc_html_e('Confidential. Send this as landing_token in your API payload. Starts with op_cb_.', 'checkoutbridge'); ?>
                            </p>
                            <?php if ($is_edit && $landing_id > 0) :
                                $form_revoke_url = wp_nonce_url(
                                    admin_url('admin.php?page=checkoutbridge-bridges&action=revoke_token&id=' . $landing_id),
                                    'op_cb_revoke_token_' . $landing_id
                                );
                            ?>
                                <div style="margin-top:10px;">
                                    <a href="<?php echo esc_url($form_revoke_url); ?>" class="button button-secondary button-small op-cb-btn-revoke">
                                        <i class="fa-solid fa-key" style="margin-right:4px;"></i>
                                        <?php esc_html_e('Revoke & Regenerate Token', 'checkoutbridge'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                    <div class="op-cb-card-footer">
                        <button type="submit" class="button button-primary button-hero">
                            <i class="fa-solid fa-check" style="margin-right:4px;"></i>
                            <?php echo $is_edit
                                ? esc_html__('Update Bridge', 'checkoutbridge')
                                : esc_html__('Save Bridge', 'checkoutbridge'); ?>
                        </button>
                    </div>
                </div>
            </div><!-- /op-cb-form-sidebar -->

        </div>
    </form>

</div>
