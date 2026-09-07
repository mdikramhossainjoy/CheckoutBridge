=== CheckoutBridge ===
Contributors: ikramjoy
Tags: woocommerce, checkout, cod, api, headless
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: woocommerce
WC requires at least: 5.0
WC tested up to: 9.3
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

CheckoutBridge connects external custom landing pages directly to WooCommerce for automated Cash on Delivery (COD) order creation.

== Description ==

CheckoutBridge acts as a high-performance, enterprise-grade headless bridge between WooCommerce and external custom landing pages (built in React, Next.js, Vue, Laravel, PHP, Python, Node.js, Go, or static HTML).

It maintains WooCommerce as the single source of truth for product pricing, inventory tracking, order management, and analytics, while enabling frictionless 1-click checkout on external sales funnels.

=== Key Features ===

* **Single Source of Truth**: Product pricing, inventory deduction, and order creation logic executed securely on WooCommerce.
* **Multi-Product Payload Ingestion**: Supports single or multi-product item selection with custom order quantities per line item.
* **Automated Quantity Package Deals**: Configure multi-pack / bundle pricing tiers per campaign. Each deal generates a Unique Deal ID (`tier_id`) allowing the server to automatically recognize discount vs. normal orders.
* **Server-Side Meta (Facebook) Conversions API (CAPI)**: Automatic server-to-server SHA-256 hashed Purchase event dispatch directly to Meta Graph API on Processing order status.
* **Global Dual-Shield Anti-Bot Engine**: E.164 international phone number normalization and Client IP velocity rate limiting to eliminate spam orders.
* **Stateless Signed Redirect Tokens**: Secure HMAC SHA-256 tokens for tamper-proof thank-you page receipt rendering.
* **WAF & Shared Host Shield**: Base64 payload decoding and custom headers to bypass aggressive host firewall filters (Imunify360, LiteSpeed, ModSecurity).
* **In-Admin Developer Center**: Complete integration documentation with interactive code snippets for 8 programming languages.

== Installation ==

1. Upload the `op-checkoutbridge` directory to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure **WooCommerce** is active.
4. Navigate to **CheckoutBridge > Bridges Manager** to create your first landing campaign.

== Frequently Asked Questions ==

= Do I need to enable WooCommerce REST API keys? =
No. CheckoutBridge uses standalone, high-performance campaign token keys (`op_cb_...`) with CORS domain whitelisting, eliminating complex WooCommerce API key management.

= Is High-Performance Order Storage (HPOS) supported? =
Yes! CheckoutBridge is fully compatible with WooCommerce HPOS native storage.

== External Services ==

This plugin can optionally connect to an external third-party service:

= Meta (Facebook) Graph API / Conversions API =
* What the service is and what it is used for:
  When enabled by the site administrator in a Bridge Campaign, CheckoutBridge connects to the Meta (Facebook) Graph API (Conversions API) to send server-side "Purchase" events to track conversions, measure ad effectiveness, and attribute sales in Meta Ads Manager.
* What data is sent and when:
  Data is only sent when the store administrator explicitly enables Meta Conversions API for a bridge campaign AND an order created through that bridge transitions to the "Processing" status. Data sent to `https://graph.facebook.com/` includes:
  - Cryptographically hashed customer data (SHA-256): Customer email, normalized phone number (E.164), first name, last name, city, and 2-letter country code.
  - Unhashed technical and tracking metadata: Customer IP address, browser user-agent string, Facebook click ID (`_fbc`), and Facebook browser ID (`_fbp`).
  - Order details: Order currency, order total value, item product IDs, quantities, and unit prices.
* Service Provider:
  Meta Platforms, Inc.
* Service Endpoint:
  https://graph.facebook.com/
* Terms of Service and Privacy Policy:
  - Meta Terms of Service: https://www.facebook.com/legal/terms
  - Meta Commercial Terms / Business Tools Terms: https://www.facebook.com/legal/technology_terms
  - Meta Privacy Policy: https://www.facebook.com/privacy/policy

== Changelog ==

= 1.1.0 =
* Feature: Introduced Quantity Package Deals in Bridge Manager with Unique Deal IDs (`tier_id`) and one-click copy functionality.
* Architecture: Native server-side order type recognition — distinguishes normal orders from discount bundle orders automatically based on `tier_id`.
* WooCommerce Integration: Automated package discounts applied directly via negative fee line items for 100% HPOS and invoice compatibility.
* Refactor: Completely removed deprecated `/validate-coupon` endpoint and legacy coupon code processing.
* Docs: Updated In-Admin Developer Center, interactive code samples, and error codes table for Quantity Package Deals integration.

= 1.0.1 =
* UI: Unified global section gaps and standardized border-radius system across all admin cards, forms, and buttons.
* UI: Restructured Assigned WooCommerce Products card header into a clean two-line layout with description beneath the title.
* UI: Centered the Bridges Manager empty state with generous vertical padding and centered action button.
* Feature: Added an optional toggle for Thank You Page Redirect in Bridge Settings, enabling seamless modal/inline confirmation workflows without redirecting.
* API: Enhanced `/create-order` endpoint to return `redirect.enabled: false` when redirect is disabled.

= 1.0.0 =
* Initial official release of CheckoutBridge.
* Real-time coupon validator endpoint (`/validate-coupon`).
* Meta CAPI server-side conversion tracking helper.
* Global Dual-Shield Anti-Bot velocity limits.
