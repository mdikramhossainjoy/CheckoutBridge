# CheckoutBridge for WooCommerce

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg?style=for-the-badge&logo=wordpress)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-purple.svg?style=for-the-badge&logo=woocommerce)](https://woocommerce.com)
[![PHP](https://img.shields.io/badge/PHP-7.4%20%7C%208.0%20%7C%208.1%20%7C%208.2%20%7C%208.3-777BB4.svg?style=for-the-badge&logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg?style=for-the-badge)](README.txt)
[![Version](https://img.shields.io/badge/Version-1.1.1-indigo.svg?style=for-the-badge)](checkoutbridge.php)

> **Enterprise Headless Cash on Delivery (COD) & REST API Order Engine for WooCommerce.**
> Connect custom landing pages (built in React, Next.js, Laravel, Vue, PHP, Python, Node.js, Go, or HTML) directly to WooCommerce while maintaining WooCommerce as the single source of truth for inventory, pricing, order fulfillment, and analytics.

---

## Key Features

- **Single Source of Truth**: All pricing calculations, inventory deductions, order creation logic, and status transitions occur securely inside WooCommerce.
- **Multi-Product Payload Ingestion**: Supports single or multi-product item selections with individual quantity counters per product line item.
- **Automated Quantity Package Deals**: Configure multi-pack / bundle pricing tiers per campaign. Each deal generates a Unique Deal ID (`tier_id`) allowing the server to automatically recognize discount vs. normal orders.
- **Server-Side Meta (Facebook) Conversions API (CAPI)**: Automatic server-to-server SHA-256 hashed `Purchase` event dispatch directly to Meta Graph API when orders transition to Processing status, with browser-server deduplication and native HPOS metadata (`_op_cb_fbp`, `_op_cb_fbc`, `_op_cb_event_id`).
- **Global Dual-Shield Anti-Bot Defense**: E.164 international phone number normalization and Client IP velocity rate limiting to eliminate fake and spam COD orders.
- **Stateless Signed Redirect Tokens**: Secure HMAC SHA-256 tokens for tamper-proof thank-you page receipt rendering without exposing sensitive order credentials.
- **Shared Host & WAF Compatibility Shield**: Built-in Base64 payload decoding and custom headers to bypass aggressive host WAF filters (Imunify360, LiteSpeed, ModSecurity).
- **In-Admin Developer Center**: Complete integration guide with interactive code snippets for 8 programming languages (JS Fetch, PHP cURL, React/Next.js, Python, Node.js, Go, Ruby, cURL CLI).

---

## REST API Endpoints

| Endpoint | Method | Description |
| :--- | :---: | :--- |
| `/wp-json/checkoutbridge/v1/create-order` | `POST` | Ingests landing page customer details, line items, optional `tier_id`, and shipping selection to create a WooCommerce COD order. Returns order type, tier ID, and signed redirect token. |
| `/wp-json/checkoutbridge/v1/order-details` | `POST` | Validates signed thank-you token and returns formatted customer, shipping, line items, package price, and discount summary. |
| `/wp-json/checkoutbridge/v1/health` | `GET` | Verifies REST API infrastructure health, WAF shield status, and plugin version. |

---

## Integration Examples

### Order Creation with Quantity Package Deal (PHP cURL)

```php
<?php
// Submit Cash on Delivery (COD) Order with Quantity Package Deal
$order_payload = json_encode([
    'bridge_token'  => 'op_cb_YOUR_CAMPAIGN_TOKEN',
    'tier_id'       => 'tier_6a1f8b', // Unique Deal ID copied from Bridge Manager
    'items'         => [
        ['id' => 14, 'quantity' => 2]
    ],
    'customer'      => [
        'full_name' => 'Tanvir Hassan',
        'phone'     => '01711000000',
        'address'   => 'House 45, Road 7, Mirpur 10, Dhaka',
    ],
    'shipping'      => [
        'id'    => 'inside_dhaka',
        'label' => 'Inside Dhaka Delivery',
        'cost'  => 60,
    ],
]);

$ch = curl_init('https://yourstore.com/wp-json/checkoutbridge/v1/create-order');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => $order_payload,
]);

$order_response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!empty($order_response['success'])) {
    // Redirect customer to Thank-You page with signed token
    $redirect_url = $order_response['redirect']['url'] . '?token=' . urlencode($order_response['redirect']['token']);
    header('Location: ' . $redirect_url);
    exit;
}
```

---

## Installation & Setup

1. **Upload Plugin**: Download or clone this repository into `/wp-content/plugins/checkoutbridge`.
2. **Activate**: Activate **CheckoutBridge** from the WordPress admin plugins menu.
3. **Prerequisites**: Ensure **WooCommerce** is active.
4. **Create Bridge Campaign**: Navigate to **CheckoutBridge > Bridges Manager > Add New Bridge**.
5. **Assign Products**: Select assigned WooCommerce products allowed for this campaign and copy your unique `op_cb_...` Bridge Token Key.

---

## Security & Architecture Standards

- **Prefix Scoping**: All classes, tables, options, transient keys, action hooks, and DOM selectors are strictly scoped with `OP_CB_` / `op_cb_` to prevent namespace collisions.
- **HPOS Ready**: Full native compatibility with WooCommerce High-Performance Order Storage (HPOS).
- **Sanitization & Escaping**: Strict input sanitization (`sanitize_text_field`, `sanitize_textarea_field`) and context-aware output escaping (`esc_html`, `esc_attr`, `esc_url`).
- **CSRF & Nonce Protection**: All admin form submissions and query actions enforce cryptographic nonces and WordPress user capability checks.

---

## External Services

This plugin can optionally connect to an external third-party service:

### Meta (Facebook) Graph API / Conversions API
* **What the service is and what it is used for**: When enabled by the site administrator in a Bridge Campaign, CheckoutBridge connects to the Meta (Facebook) Graph API (Conversions API) to send server-side `Purchase` events to track conversions, measure ad effectiveness, and attribute sales in Meta Ads Manager.
* **What data is sent and when**: Data is only sent when the store administrator explicitly enables Meta Conversions API for a bridge campaign AND an order created through that bridge transitions to the `Processing` status. Data sent to `https://graph.facebook.com/` includes:
  - Cryptographically hashed customer data (SHA-256): Customer email, normalized phone number (E.164), first name, last name, city, and 2-letter country code.
  - Unhashed technical and tracking metadata: Customer IP address, browser user-agent string, Facebook click ID (`_fbc`), and Facebook browser ID (`_fbp`).
  - Order details: Order currency, order total value, item product IDs, quantities, and unit prices.
* **Service Provider**: Meta Platforms, Inc.
* **Service Endpoint**: `https://graph.facebook.com/`
* **Terms of Service and Privacy Policy**:
  - [Meta Terms of Service](https://www.facebook.com/legal/terms)
  - [Meta Commercial Terms / Business Tools Terms](https://www.facebook.com/legal/technology_terms)
  - [Meta Privacy Policy](https://www.facebook.com/privacy/policy)

---

## Changelog

### v1.1.1
* **Bridges Manager Table Controls**: Added generous breathing space padding (`18px 24px`) and horizontal `justify-between` alignment for search bar and status filter dropdown with responsive mobile stacking.
* **Full Single-Column Form Layout**: Transformed Bridge Manager edit/add form into a clean vertical sequence ("down by down"), eliminating the cramped right sidebar and expanding anti-bot velocity limits into a spacious 3-column grid.
* **Full Multi-Platform Documentation**: Upgraded the Quantity Package Deals feature guide to include complete interactive code examples across all 8 supported platforms (JS Fetch, PHP cURL, React/Next.js, Python, Node.js, Go, Ruby, cURL CLI).

### v1.1.0
* **Quantity Package Deals**: Introduced multi-pack bundle pricing in Bridge Manager. Set fixed total prices for specific item quantities with auto-generated Unique Deal IDs (`tier_id`) and one-click copy functionality.
* **Server-Side Order Recognition**: Automatically recognizes normal orders vs. discount deal orders based on `tier_id` presence and validity.
* **Native WooCommerce Discounting**: Applies package discounts via native negative fee line items for 100% HPOS, invoice, and reporting compatibility.
* **Coupon Validation Removal**: Completely removed deprecated `/validate-coupon` REST route and coupon code handling.
* **Documentation Update**: Updated Developer Center and API reference for package deals.

### v1.0.1
* **UI Spacing & Gaps**: Unified global section gaps and standardized border-radius system across all admin cards, forms, and buttons.
* **Header Layout**: Restructured Assigned WooCommerce Products card header into a clean two-line layout with description beneath the title and selected counter pill on the right.
* **Centered Empty State**: Centered the Bridges Manager empty state with generous vertical padding and centered action button.
* **Optional Thank You Page URL**: Added an optional toggle for Thank You Page Redirect in Bridge Settings, enabling seamless modal/inline confirmation workflows without redirecting.
* **REST API**: Enhanced `/create-order` endpoint to return `redirect.enabled: false` when redirect is disabled.

### v1.0.0
* Initial official release of CheckoutBridge.
* Real-time coupon validator endpoint (`/validate-coupon`).
* Meta CAPI server-side conversion tracking helper.
* Global Dual-Shield Anti-Bot velocity limits.

---

## License

Distributed under the **GPLv2 or later** License. See `README.txt` for details.
