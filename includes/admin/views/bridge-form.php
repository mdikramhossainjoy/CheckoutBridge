<?php
if (!defined('ABSPATH')) {
    exit;
}

$op_cb_is_edit    = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
$op_cb_landing_id = $op_cb_is_edit ? intval($_GET['id']) : 0;
$op_cb_landing    = ($op_cb_is_edit && ($found = OP_CB_Bridge_Repository::get_by_id($op_cb_landing_id))) ? $found : array();

$op_cb_name              = isset($op_cb_landing['name'])              ? $op_cb_landing['name']              : '';
$op_cb_token             = isset($op_cb_landing['token'])             ? $op_cb_landing['token']             : OP_CB_Bridge_Repository::generate_token();
$op_cb_allowed_origins   = isset($op_cb_landing['allowed_origins'])   ? $op_cb_landing['allowed_origins']   : '';
$op_cb_assigned_products = isset($op_cb_landing['assigned_products']) ? $op_cb_landing['assigned_products'] : array();
$op_cb_thank_you_url     = isset($op_cb_landing['thank_you_url'])     ? $op_cb_landing['thank_you_url']     : '';
$op_cb_status            = isset($op_cb_landing['status'])            ? $op_cb_landing['status']            : 'active';

// Fetch WooCommerce Products with Lightweight Transient Caching for Instant Page Loading
$op_cb_wc_products = array();
if (class_exists('WooCommerce')) {
    $cached = get_transient('op_cb_products_light_cache');
    if (false !== $cached && is_array($cached)) {
        $op_cb_wc_products = $cached;
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

                $op_cb_wc_products[] = array(
                    'id'        => $p_id,
                    'name'      => $p->get_name(),
                    'price'     => $p->get_price_html(),
                    'thumb_src' => $thumb_src,
                );
            }
        }
        set_transient('op_cb_products_light_cache', $op_cb_wc_products, 600);
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
                    <?php echo $op_cb_is_edit
                        ? esc_html__('Edit Bridge Campaign', 'op-checkoutbridge')
                        : esc_html__('New Bridge Campaign', 'op-checkoutbridge'); ?>
                </h1>
                <p class="op-cb-subtitle">
                    <?php esc_html_e('Assign WooCommerce products, configure CORS origins, and set redirect target', 'op-checkoutbridge'); ?>
                </p>
            </div>
        </div>
        <div class="op-cb-header-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=checkoutbridge-bridges')); ?>" class="button button-secondary">
                <i class="fa-solid fa-arrow-left" style="margin-right:4px;"></i>
                <?php esc_html_e('Back to List', 'op-checkoutbridge'); ?>
            </a>
        </div>
    </div>

    <!-- ── Form ── -->
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="op_cb_main_form">
        <?php wp_nonce_field('op_cb_save_bridge', 'op_cb_nonce'); ?>
        <input type="hidden" name="action" value="op_cb_save_bridge">
        <input type="hidden" name="bridge_id" value="<?php echo esc_attr($op_cb_landing_id); ?>">

        <div class="op-cb-form-layout">

            <!-- ── Main Column ── -->
            <div class="op-cb-form-main">

                <!-- Basic Info Card -->
                <div class="op-cb-card">
                    <div class="op-cb-card-header">
                        <h2>
                            <span class="op-cb-section-num">1</span>
                            <?php esc_html_e('Bridge Details', 'op-checkoutbridge'); ?>
                        </h2>
                    </div>
                    <div class="op-cb-card-body">

                        <!-- Bridge Name -->
                        <div class="op-cb-form-group">
                            <label for="op_cb_name">
                                <?php esc_html_e('Bridge Name', 'op-checkoutbridge'); ?>
                                <span class="op-cb-required">*</span>
                            </label>
                            <input
                                type="text"
                                id="op_cb_name"
                                name="name"
                                class="op-cb-input"
                                value="<?php echo esc_attr($op_cb_name); ?>"
                                placeholder="<?php esc_attr_e('e.g. Dubai Summer Offer Bridge', 'op-checkoutbridge'); ?>"
                                required
                            >
                        </div>

                        <!-- Thank You URL -->
                        <div class="op-cb-form-group">
                            <label for="op_cb_thank_you_url">
                                <?php esc_html_e('Thank You Page URL', 'op-checkoutbridge'); ?>
                                <span class="op-cb-required">*</span>
                            </label>
                            <input
                                type="url"
                                id="op_cb_thank_you_url"
                                name="thank_you_url"
                                class="op-cb-input"
                                value="<?php echo esc_attr($op_cb_thank_you_url); ?>"
                                placeholder="https://landing.yourdomain.com/thank-you"
                                required
                            >
                            <p class="op-cb-field-hint">
                                <?php esc_html_e('Customers are redirected here after successful order creation, with a signed token appended.', 'op-checkoutbridge'); ?>
                            </p>
                        </div>

                        <!-- Allowed Origins -->
                        <div class="op-cb-form-group">
                            <label for="op_cb_allowed_origins">
                                <?php esc_html_e('Allowed Origins (CORS Whitelist)', 'op-checkoutbridge'); ?>
                            </label>
                            <textarea
                                id="op_cb_allowed_origins"
                                name="allowed_origins"
                                class="op-cb-textarea"
                                rows="3"
                                placeholder="https://landing.yourdomain.com&#10;https://offers.yourbrand.com&#10;*"
                            ><?php echo esc_textarea($op_cb_allowed_origins); ?></textarea>
                            <p class="op-cb-field-hint">
                                <?php esc_html_e('Enter allowed domain origins (one per line). Use * to allow any origin.', 'op-checkoutbridge'); ?>
                            </p>
                        </div>

                    </div>
                </div>

                <!-- Products Selection Card -->
                <div class="op-cb-card op-cb-mt-4">
                    <div class="op-cb-card-header">
                        <h2>
                            <span class="op-cb-section-num">2</span>
                            <?php esc_html_e('Assigned WooCommerce Products', 'op-checkoutbridge'); ?>
                        </h2>
                        <span class="op-cb-header-badge">
                            <?php esc_html_e('Used strictly for internal server-side payload validation. Assigned products are never exposed or fetchable over REST API.', 'op-checkoutbridge'); ?>
                        </span>
                        <span class="op-cb-counter-pill" id="op_cb_selected_counter">
                            0 <?php esc_html_e('selected', 'op-checkoutbridge'); ?>
                        </span>
                    </div>
                    <div class="op-cb-card-body">

                        <?php if (empty($op_cb_wc_products)) : ?>
                            <div class="op-cb-notice-inline">
                                <i class="fa-solid fa-circle-info"></i>
                                <p><?php esc_html_e('No published WooCommerce products found. Please add products to WooCommerce first.', 'op-checkoutbridge'); ?></p>
                            </div>
                        <?php else : ?>
                            <div class="op-cb-product-search-row">
                                <div class="op-cb-search-wrap">
                                    <input
                                        type="text"
                                        id="op_cb_product_search"
                                        class="op-cb-input has-icon-right"
                                        style="width: 100%;"
                                        placeholder="<?php esc_attr_e('Search products by name…', 'op-checkoutbridge'); ?>"
                                    >
                                    <i class="fa-solid fa-magnifying-glass" style="position:absolute;right:0.75em;top:50%;transform:translateY(-50%);color:var(--cb-text-400);pointer-events:none;"></i>
                                </div>
                                <span class="op-cb-product-hint" id="op_cb_product_count_hint"></span>
                            </div>

                            <div class="op-cb-product-grid">
                                <?php foreach ($op_cb_wc_products as $op_cb_p) :
                                    $op_cb_p_id        = is_array($op_cb_p) ? $op_cb_p['id'] : $op_cb_p->get_id();
                                    $op_cb_p_name      = is_array($op_cb_p) ? $op_cb_p['name'] : $op_cb_p->get_name();
                                    $op_cb_p_price     = is_array($op_cb_p) ? $op_cb_p['price'] : $op_cb_p->get_price_html();
                                    $op_cb_thumb_src   = is_array($op_cb_p) ? $op_cb_p['thumb_src'] : ($op_cb_p->get_image_id() ? wp_get_attachment_image_url($op_cb_p->get_image_id(), 'thumbnail') : '');
                                    $op_cb_is_selected = in_array($op_cb_p_id, $op_cb_assigned_products, true);
                                ?>
                                    <label class="op-cb-product-item <?php echo $op_cb_is_selected ? 'is-selected' : ''; ?>">
                                        <input
                                            type="checkbox"
                                            name="assigned_products[]"
                                            class="op-cb-checkbox"
                                            value="<?php echo esc_attr($op_cb_p_id); ?>"
                                            <?php checked($op_cb_is_selected); ?>
                                        >
                                        <span class="op-cb-checkbox-box">
                                            <i class="fa-solid fa-check"></i>
                                        </span>
                                        <?php if ($op_cb_thumb_src) : ?>
                                            <img src="<?php echo esc_url($op_cb_thumb_src); ?>" alt="<?php echo esc_attr($op_cb_p_name); ?>" class="op-cb-product-thumb">
                                        <?php else : ?>
                                            <div class="op-cb-product-thumb op-cb-product-thumb-icon">
                                                <i class="fa-solid fa-box"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="op-cb-product-info">
                                            <span class="op-cb-product-name"><?php echo esc_html($op_cb_p_name); ?></span>
                                            <span class="op-cb-product-id">#<?php echo esc_html($op_cb_p_id); ?></span>
                                        </div>
                                        <span class="op-cb-product-price"><?php echo wp_kses_post($op_cb_p_price); ?></span>
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
                            <?php esc_html_e('Status & Token', 'op-checkoutbridge'); ?>
                        </h2>
                    </div>
                    <div class="op-cb-card-body">

                        <!-- Status Toggle -->
                        <div class="op-cb-form-group">
                            <label><?php esc_html_e('Bridge Status', 'op-checkoutbridge'); ?></label>
                            <div class="op-cb-toggle-row">
                                <div>
                                    <div class="op-cb-toggle-label"><?php esc_html_e('Accepting Orders', 'op-checkoutbridge'); ?></div>
                                    <div class="op-cb-toggle-desc"><?php esc_html_e('Toggle off to pause this bridge.', 'op-checkoutbridge'); ?></div>
                                </div>
                                <label class="op-cb-switch">
                                    <input
                                        type="checkbox"
                                        id="op_cb_status_toggle"
                                        <?php checked($op_cb_status, 'active'); ?>
                                        onchange="document.getElementById('op_cb_status_val').value = this.checked ? 'active' : 'inactive';"
                                    >
                                    <span class="op-cb-slider"></span>
                                </label>
                                <input type="hidden" name="status" id="op_cb_status_val" value="<?php echo esc_attr($op_cb_status); ?>">
                            </div>
                        </div>

                        <hr class="op-cb-divider">

                        <!-- Global Dual-Shield Anti-Bot Settings -->
                        <div class="op-cb-form-group">
                            <label style="color:var(--cb-indigo-600);font-weight:700;">
                                <i class="fa-solid fa-shield-halved" style="margin-right:4px;"></i>
                                <?php esc_html_e('Dual-Shield Anti-Bot Protection', 'op-checkoutbridge'); ?>
                            </label>
                            <div style="font-size:12px;color:var(--cb-text-500);margin:4px 0 10px 0;">
                                <?php esc_html_e('Limits COD orders per phone & IP to block spam bots globally.', 'op-checkoutbridge'); ?>
                            </div>
                            
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
                                <div>
                                    <label for="op_cb_phone_limit" style="font-size:11.5px;font-weight:600;"><?php esc_html_e('Max per Number', 'op-checkoutbridge'); ?></label>
                                    <input type="number" min="0" max="10" name="phone_velocity_limit" id="op_cb_phone_limit" class="op-cb-input" value="<?php echo esc_attr(isset($op_cb_landing['phone_velocity_limit']) ? $op_cb_landing['phone_velocity_limit'] : 1); ?>">
                                </div>
                                <div>
                                    <label for="op_cb_ip_limit" style="font-size:11.5px;font-weight:600;"><?php esc_html_e('Max per IP', 'op-checkoutbridge'); ?></label>
                                    <input type="number" min="0" max="20" name="ip_velocity_limit" id="op_cb_ip_limit" class="op-cb-input" value="<?php echo esc_attr(isset($op_cb_landing['ip_velocity_limit']) ? $op_cb_landing['ip_velocity_limit'] : 3); ?>">
                                </div>
                            </div>
                            <div>
                                <label for="op_cb_velocity_hours" style="font-size:11.5px;font-weight:600;"><?php esc_html_e('Time Window (Hours)', 'op-checkoutbridge'); ?></label>
                                <input type="number" min="1" max="168" name="velocity_hours" id="op_cb_velocity_hours" class="op-cb-input" value="<?php echo esc_attr(isset($op_cb_landing['velocity_hours']) ? $op_cb_landing['velocity_hours'] : 24); ?>">
                            </div>
                        </div>

                        <hr class="op-cb-divider">

                        <!-- Smart Customer Autofill Toggle -->
                        <div class="op-cb-form-group">
                            <label style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;">
                                <span style="font-weight:600;font-size:13px;color:var(--cb-text-900);">
                                    <i class="fa-solid fa-address-book" style="color:var(--cb-indigo-600);margin-right:4px;"></i>
                                    <?php esc_html_e('Customer Phone Autofill Lookup', 'op-checkoutbridge'); ?>
                                </span>
                                <input 
                                    type="checkbox" 
                                    name="enable_autofill_lookup" 
                                    value="1" 
                                    <?php checked(isset($op_cb_landing['enable_autofill_lookup']) ? $op_cb_landing['enable_autofill_lookup'] : true); ?>
                                >
                            </label>
                            <div style="font-size:12px;color:var(--cb-text-500);margin-top:4px;line-height:1.45;">
                                <?php esc_html_e('Allows external landing forms to auto-fill buyer name & address when they type their phone number.', 'op-checkoutbridge'); ?>
                            </div>
                        </div>

                        <hr class="op-cb-divider">

                        <!-- Token Field -->
                        <div class="op-cb-form-group">
                            <label for="op_cb_token_display"><?php esc_html_e('Bridge Token Key (op_cb_)', 'op-checkoutbridge'); ?></label>
                            <div class="op-cb-token-field">
                                <input
                                    type="password"
                                    id="op_cb_token_display"
                                    class="op-cb-input"
                                    value="<?php echo esc_attr($op_cb_token); ?>"
                                    readonly
                                    autocomplete="off"
                                >
                                <input type="hidden" name="token" id="op_cb_token" value="<?php echo esc_attr($op_cb_token); ?>">
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
                                    data-cb-key="<?php echo esc_attr(base64_encode($op_cb_token)); ?>"
                                >
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                            </div>
                            <p class="op-cb-field-hint">
                                <?php esc_html_e('Confidential. Send this as bridge_token in your API payload. Starts with op_cb_.', 'op-checkoutbridge'); ?>
                            </p>
                            <?php if ($op_cb_is_edit && $op_cb_landing_id > 0) :
                                $op_cb_form_revoke_url = wp_nonce_url(
                                    admin_url('admin.php?page=checkoutbridge-bridges&action=revoke_token&id=' . $op_cb_landing_id),
                                    'op_cb_revoke_token_' . $op_cb_landing_id
                                );
                            ?>
                                <div style="margin-top:10px;">
                                    <a href="<?php echo esc_url($op_cb_form_revoke_url); ?>" class="button button-secondary button-small op-cb-btn-revoke">
                                        <i class="fa-solid fa-key" style="margin-right:4px;"></i>
                                        <?php esc_html_e('Revoke & Regenerate Token', 'op-checkoutbridge'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                    <div class="op-cb-card-footer">
                        <button type="submit" class="button button-primary button-hero">
                            <i class="fa-solid fa-check" style="margin-right:4px;"></i>
                            <?php echo $op_cb_is_edit
                                ? esc_html__('Update Bridge', 'op-checkoutbridge')
                                : esc_html__('Save Bridge', 'op-checkoutbridge'); ?>
                        </button>
                    </div>
                </div>
            </div><!-- /op-cb-form-sidebar -->

        </div>
    </form>

</div>
