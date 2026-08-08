=== CheckoutBridge ===
Contributors: checkoutbridge
Tags: woocommerce, landing page, checkout, cod, api, headless
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 9.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

CheckoutBridge connects external custom landing pages directly to WooCommerce for automated Cash on Delivery (COD) order creation.

== Description ==

CheckoutBridge acts as a high-performance, enterprise-grade headless bridge between WooCommerce and external custom landing pages (built in React, Next.js, Vue, Laravel, PHP, Python, Node.js, Go, or static HTML).

It maintains WooCommerce as the single source of truth for product pricing, inventory tracking, order management, and analytics, while enabling frictionless 1-click checkout on external sales funnels.

=== Key Features ===

* **Single Source of Truth**: Product pricing, inventory deduction, and order creation logic executed securely on WooCommerce.
* **Multi-Product Payload Ingestion**: Supports single or multi-product item selection with custom order quantities per line item.
* **Real-Time Dynamic Coupon Validator**: Dedicated `/validate-coupon` REST endpoint for real-time promo code validation and discount calculation.
* **Meta (Facebook) Conversion CAPI**: Native High-Performance Order Storage (HPOS) metadata (`_op_cb_fbp`, `_op_cb_fbc`, `_op_cb_event_id`) and server-side tracking action hook.
* **Global Dual-Shield Anti-Bot Engine**: E.164 international phone number normalization and Client IP velocity rate limiting to eliminate spam orders.
* **Stateless Signed Redirect Tokens**: Secure HMAC SHA-256 tokens for tamper-proof thank-you page receipt rendering.
* **WAF & Shared Host Shield**: Base64 payload decoding and custom headers to bypass aggressive host firewall filters (Imunify360, LiteSpeed, ModSecurity).
* **In-Admin Developer Center**: Complete integration documentation with interactive code snippets for 8 programming languages.

== Installation ==

1. Upload the `checkoutbridge` directory to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure **WooCommerce** is active.
4. Navigate to **CheckoutBridge > Bridges Manager** to create your first landing campaign.

== Frequently Asked Questions ==

= Do I need to enable WooCommerce REST API keys? =
No. CheckoutBridge uses standalone, high-performance campaign token keys (`op_cb_...`) with CORS domain whitelisting, eliminating complex WooCommerce API key management.

= Is High-Performance Order Storage (HPOS) supported? =
Yes! CheckoutBridge is fully compatible with WooCommerce HPOS native storage.

== Changelog ==

= 1.0.0 =
* Initial official release of CheckoutBridge.
* Real-time coupon validator endpoint (`/validate-coupon`).
* Meta CAPI server-side conversion tracking helper.
* Global Dual-Shield Anti-Bot velocity limits.
