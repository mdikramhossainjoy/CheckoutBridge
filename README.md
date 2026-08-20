# CheckoutBridge for WooCommerce

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg?style=for-the-badge&logo=wordpress)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-purple.svg?style=for-the-badge&logo=woocommerce)](https://woocommerce.com)
[![PHP](https://img.shields.io/badge/PHP-7.4%20%7C%208.0%20%7C%208.1%20%7C%208.2%20%7C%208.3-777BB4.svg?style=for-the-badge&logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg?style=for-the-badge)](README.txt)
[![Version](https://img.shields.io/badge/Version-1.0.0-indigo.svg?style=for-the-badge)](checkoutbridge.php)

> **Enterprise Headless Cash on Delivery (COD) & REST API Order Engine for WooCommerce.**
> Connect custom landing pages (built in React, Next.js, Laravel, Vue, PHP, Python, Node.js, Go, or HTML) directly to WooCommerce while maintaining WooCommerce as the single source of truth for inventory, pricing, order fulfillment, and analytics.

---

## Key Features

- **Single Source of Truth**: All pricing calculations, inventory deductions, order creation logic, and status transitions occur securely inside WooCommerce.
- **Multi-Product Payload Ingestion**: Supports single or multi-product item selections with individual quantity counters per product line item.
- **Real-Time Dynamic Coupon Validator**: Dedicated `/validate-coupon` REST endpoint for real-time promo code validation and discount calculation before order submission.
- **Smart Customer Phone Autofill Lookup**: Dedicated `/customer-lookup` REST endpoint to search WooCommerce past orders by phone number across all order statuses and return buyer details for 1-click form auto-filling.
- **Meta (Facebook) Conversion CAPI**: Native High-Performance Order Storage (HPOS) metadata (`_op_cb_fbp`, `_op_cb_fbc`, `_op_cb_event_id`) and server-side tracking action hook for 100% Event Match Quality (EMQ).
- **Global Dual-Shield Anti-Bot Defense**: E.164 international phone number normalization and Client IP velocity rate limiting to eliminate fake and spam COD orders.
- **Stateless Signed Redirect Tokens**: Secure HMAC SHA-256 tokens for tamper-proof thank-you page receipt rendering without exposing sensitive order credentials.
- **Shared Host & WAF Compatibility Shield**: Built-in Base64 payload decoding and custom headers to bypass aggressive host WAF filters (Imunify360, LiteSpeed, ModSecurity).
- **In-Admin Developer Center**: Complete integration guide with interactive code snippets for 8 programming languages (JS Fetch, PHP cURL, React/Next.js, Python, Node.js, Go, Ruby, cURL CLI).

---

## REST API Endpoints

| Endpoint | Method | Description |
| :--- | :---: | :--- |
| `/wp-json/checkoutbridge/v1/create-order` | `POST` | Ingests landing page customer details, line items, and shipping selection to create a WooCommerce COD order. Returns a signed redirect token. |
| `/wp-json/checkoutbridge/v1/validate-coupon` | `POST` | Validates a WooCommerce coupon code in real time and returns subtotal, discount amount, and updated total. |
| `/wp-json/checkoutbridge/v1/customer-lookup` | `POST` | Searches WooCommerce for existing customer records by phone number and returns name, address, and city for form auto-filling. |
| `/wp-json/checkoutbridge/v1/order-details` | `POST` | Validates signed thank-you token and returns formatted customer, shipping, line items, and discount summary. |
| `/wp-json/checkoutbridge/v1/health` | `GET` | Verifies REST API infrastructure health, WAF shield status, and plugin version. |

---

## Integration Examples

### 1. Customer Phone Autofill Lookup (JavaScript Fetch)

```javascript
async function lookupCustomer(phone) {
  const response = await fetch('https://yourstore.com/wp-json/checkoutbridge/v1/customer-lookup', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      bridge_token: 'op_cb_YOUR_CAMPAIGN_TOKEN',
      phone: phone
    })
  });

  const data = await response.json();
  if (data.success && data.found) {
    document.getElementById('full_name').value = data.customer.full_name;
    document.getElementById('address').value   = data.customer.address;
  }
}
```

### 2. Coupon Validation & Order Creation (PHP cURL)

```php
<?php
// 1. Validate Coupon Code (Optional)
$coupon_payload = json_encode([
    'bridge_token'  => 'op_cb_YOUR_CAMPAIGN_TOKEN',
    'coupon_code'   => 'FLASH50',
    'items'         => [['id' => 14, 'quantity' => 2]],
    'shipping_cost' => 60,
]);

$ch = curl_init('https://yourstore.com/wp-json/checkoutbridge/v1/validate-coupon');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => $coupon_payload,
]);

$coupon_response = json_decode(curl_exec($ch), true);
curl_close($ch);

// 2. Submit Cash on Delivery (COD) Order
$order_payload = json_encode([
    'bridge_token'  => 'op_cb_YOUR_CAMPAIGN_TOKEN',
    'items'         => [['id' => 14, 'quantity' => 2]],
    'coupon_code'   => 'FLASH50',
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

## License

Distributed under the **GPLv2 or later** License. See `README.txt` for details.
