<?php
if (!defined('ABSPATH')) {
    exit;
}

$op_cb_site_rest_url = esc_url_raw(rest_url('checkoutbridge/v1/'));
?>

<div class="wrap op-cb-wrap" id="op_cb_docs">

    <!-- ── Header ── -->
    <div class="op-cb-header op-cb-header-compact">
        <div class="op-cb-brand">
            <div class="op-cb-brand-icon">
                <i class="fa-solid fa-book-bookmark"></i>
            </div>
            <div>
                <h1><?php esc_html_e('Developer Integration Guide', 'op-checkoutbridge'); ?></h1>
                <p class="op-cb-subtitle"><?php esc_html_e('Complete REST API reference, payload examples, and integration workflows', 'op-checkoutbridge'); ?></p>
            </div>
        </div>
    </div>

    <div class="op-cb-grid" style="grid-template-columns: 1fr;">

        <!-- ── 2-Step Quickstart Guide ── -->
        <div class="op-cb-card">
            <div class="op-cb-card-header">
                <h2>
                    <i class="fa-solid fa-bolt"></i>
                    <?php esc_html_e('2-Step Developer Quickstart Flow', 'op-checkoutbridge'); ?>
                </h2>
            </div>
            <div class="op-cb-card-body">
                <div class="op-cb-step-flow-wrap">
                    <div class="op-cb-step-grid">
                        <div class="op-cb-step-card op-cb-step-1">
                            <span class="op-cb-badge op-cb-badge-success" style="margin-bottom:0.5em;">STEP 1: POST ORDER PAYLOAD</span>
                            <h4 style="font-size:0.875em;margin:0 0 0.375em 0;color:var(--cb-text-900);font-weight:600;">Submit Ingestion Payload</h4>
                            <p style="font-size:0.8125em;color:var(--cb-text-500);margin:0;line-height:1.5;">
                                Send buyer info + assigned product item array + shipping choice to <code>POST /create-order</code>. Receive signed redirect token.
                            </p>
                        </div>

                        <div class="op-cb-step-card op-cb-step-2">
                            <span class="op-cb-badge op-cb-badge-primary" style="margin-bottom:0.5em;">STEP 2: THANK YOU RECEIPT</span>
                            <h4 style="font-size:0.875em;margin:0 0 0.375em 0;color:var(--cb-text-900);font-weight:600;">Render Order Receipt</h4>
                            <p style="font-size:0.8125em;color:var(--cb-text-500);margin:0;line-height:1.5;">
                                Redirect buyer to your thank-you page with the signed token and retrieve verified receipt details via <code>POST /order-details</code>.
                            </p>
                        </div>
                    </div>

                    <!-- Connected S-Curve SVG Overlay physically touching Card 1 and Card 2 borders -->
                    <div class="op-cb-flow-connector-overlay">
                        <svg class="op-cb-curve-svg-connected" viewBox="0 0 48 48" preserveAspectRatio="none">
                            <path class="op-cb-curve-path" d="M 0,12 C 16,12 32,36 48,36" />
                            <circle class="op-cb-curve-dot" cx="0" cy="12" r="4.5" />
                            <circle class="op-cb-curve-dot" cx="48" cy="36" r="4.5" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Core Architecture ── -->
        <div class="op-cb-card">
            <div class="op-cb-card-header">
                <h2>
                    <i class="fa-solid fa-shield-halved"></i>
                    <?php esc_html_e('Architecture & Security Principles', 'op-checkoutbridge'); ?>
                </h2>
            </div>
            <div class="op-cb-card-body">
                <p style="font-size:13.5px;color:var(--cb-text-500);margin:0 0 12px 0;line-height:1.65;">
                    <?php esc_html_e('CheckoutBridge acts as a stateless, signed API bridge. All pricing, totals, and tax calculations happen exclusively on the WordPress server based on assigned WooCommerce products — developers never send price or total fields.', 'op-checkoutbridge'); ?>
                </p>
                <div class="op-cb-grid op-cb-grid-3">

                    <!-- Security Card 1: Server-Side Price Resolution -->
                    <div style="padding: 1.125em; background: var(--cb-surface-2); border: 1px solid var(--cb-border); border-radius: var(--cb-r-md); display: flex; flex-direction: column; gap: 0.5em;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="width: 2.25em; height: 2.25em; border-radius: var(--cb-r-sm); background: var(--cb-indigo-50); color: var(--cb-indigo-600); display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-lock" style="font-size: 1.1em;"></i>
                            </div>
                            <span class="op-cb-badge op-cb-badge-primary"><?php esc_html_e('PRICE INTEGRITY', 'op-checkoutbridge'); ?></span>
                        </div>
                        <h4 style="font-size: 0.875em; margin: 0; color: var(--cb-text-900); font-weight: 600; font-family: var(--cb-font-display);"><?php esc_html_e('Server-Side Resolution', 'op-checkoutbridge'); ?></h4>
                        <p style="font-size: 0.8125em; color: var(--cb-text-500); margin: 0; line-height: 1.5;">
                            <?php esc_html_e('Product prices and order totals are always resolved directly from WooCommerce — payload price tampering is impossible.', 'op-checkoutbridge'); ?>
                        </p>
                    </div>

                    <!-- Security Card 2: Dynamic Shipping Payload -->
                    <div style="padding: 1.125em; background: var(--cb-surface-2); border: 1px solid var(--cb-border); border-radius: var(--cb-r-md); display: flex; flex-direction: column; gap: 0.5em;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="width: 2.25em; height: 2.25em; border-radius: var(--cb-r-sm); background: var(--cb-sky-50); color: var(--cb-sky-600); display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-truck-fast" style="font-size: 1.1em;"></i>
                            </div>
                            <span class="op-cb-badge op-cb-badge-info"><?php esc_html_e('SHIPPING PAYLOAD', 'op-checkoutbridge'); ?></span>
                        </div>
                        <h4 style="font-size: 0.875em; margin: 0; color: var(--cb-text-900); font-weight: 600; font-family: var(--cb-font-display);"><?php esc_html_e('Shipping Payload Choice', 'op-checkoutbridge'); ?></h4>
                        <p style="font-size: 0.8125em; color: var(--cb-text-500); margin: 0; line-height: 1.5;">
                            <?php esc_html_e('Developers pass shipping choice ID (e.g. inside_dhaka, outside_dhaka, free_shipping) with cost included in payload.', 'op-checkoutbridge'); ?>
                        </p>
                    </div>

                    <!-- Security Card 3: Cryptographic HMAC Signature -->
                    <div style="padding: 1.125em; background: var(--cb-surface-2); border: 1px solid var(--cb-border); border-radius: var(--cb-r-md); display: flex; flex-direction: column; gap: 0.5em;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="width: 2.25em; height: 2.25em; border-radius: var(--cb-r-sm); background: var(--cb-green-50); color: var(--cb-green-500); display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-file-signature" style="font-size: 1.1em;"></i>
                            </div>
                            <span class="op-cb-badge op-cb-badge-success"><?php esc_html_e('HMAC SHA-256', 'op-checkoutbridge'); ?></span>
                        </div>
                        <h4 style="font-size: 0.875em; margin: 0; color: var(--cb-text-900); font-weight: 600; font-family: var(--cb-font-display);"><?php esc_html_e('Signed Redirect Tokens', 'op-checkoutbridge'); ?></h4>
                        <p style="font-size: 0.8125em; color: var(--cb-text-500); margin: 0; line-height: 1.5;">
                            <?php esc_html_e('Thank-you page redirect tokens are cryptographically signed using HMAC-SHA256 signatures and expire automatically.', 'op-checkoutbridge'); ?>
                        </p>
                    </div>

                </div>
            </div>
        </div>

        <!-- ── Endpoint 1: Create Order ── -->
        <div class="op-cb-card">
            <div class="op-cb-card-header">
                <h2>
                    <i class="fa-solid fa-code"></i>
                    <?php esc_html_e('Endpoint 1: Create Order', 'op-checkoutbridge'); ?>
                </h2>
                <span class="op-cb-badge op-cb-badge-success">POST</span>
            </div>
            <div class="op-cb-card-body">

                <div class="op-cb-endpoint-block">
                    <h3 class="op-cb-endpoint-title">
                        <span class="op-cb-badge op-cb-badge-success">POST</span>
                        <code>/wp-json/checkoutbridge/v1/create-order</code>
                    </h3>
                    <p class="op-cb-endpoint-desc">
                        <?php esc_html_e('Creates a Cash on Delivery WooCommerce order from a custom landing page. Returns a signed redirect token for the thank-you page.', 'op-checkoutbridge'); ?>
                    </p>

                    <div class="op-cb-base-url-chip">
                        <span><?php esc_html_e('Base URL:', 'op-checkoutbridge'); ?></span>
                        <code><?php echo esc_html($op_cb_site_rest_url); ?>create-order</code>
                        <button type="button" class="op-cb-btn-icon op-cb-btn-copy" data-clipboard="<?php echo esc_attr($op_cb_site_rest_url . 'create-order'); ?>">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>

                    <div class="op-cb-callout op-cb-mt-3" style="margin-bottom:16px;">
                        <div class="op-cb-callout-icon">
                            <i class="fa-solid fa-list-check"></i>
                        </div>
                        <div class="op-cb-callout-content">
                            <h4><?php esc_html_e('Mandatory Items Payload Specification', 'op-checkoutbridge'); ?></h4>
                            <p style="font-size:12.5px;color:var(--cb-text-700);margin:4px 0 0 0;line-height:1.55;">
                                <strong>Required Field:</strong> All order requests <em>must</em> include the <code>items</code> array (e.g. <code>items: [{id: 14, quantity: 2}, {id: 20, quantity: 1}]</code>). Each item ID is strictly validated against the campaign's assigned WooCommerce products.
                            </p>
                        </div>
                    </div>

                    <!-- Tabbed Code Block -->
                    <div class="op-cb-code-wrapper">
                        <div class="op-cb-code-header">
                            <div class="op-cb-code-tabs">
                                <button type="button" class="op-cb-tab-btn is-active" data-tab="tab_js_create">JS Fetch</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_php_create">PHP cURL</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_react_create">React / Next.js</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_python_create">Python</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_node_create">Node.js</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_go_create">Go</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_ruby_create">Ruby</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_curl_create">cURL CLI</button>
                            </div>
                            <button type="button" class="op-cb-btn-copy-code" data-target="code_create_active">
                                <i class="fa-solid fa-copy" style="margin-right:0.125em;"></i>
                                <?php esc_html_e('Copy', 'op-checkoutbridge'); ?>
                            </button>
                        </div>

                        <!-- JS Fetch -->
                        <div id="tab_js_create" class="op-cb-code-tab-content">
                            <pre class="op-cb-code-block"><code>async function submitOrder(formData) {
  const res = await fetch('<?php echo esc_js($op_cb_site_rest_url . 'create-order'); ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      bridge_token: 'op_cb_YOUR_BRIDGE_TOKEN',
      items: [
        { id: 14, quantity: 2 },
        { id: 20, quantity: 1 }
      ],
      coupon_code: 'FLASH50', // Optional WooCommerce Promo Coupon Code
      customer: {
        full_name: formData.fullName,
        phone:     formData.phone,
        address:   formData.address
      },
      shipping: {
        id:    'inside_dhaka',   // inside_dhaka | outside_dhaka | free_shipping
        label: 'Inside Dhaka',
        cost:  80
      }
    })
  });

  const data = await res.json();
  if (data.success) {
    // Redirect to Thank-You page with signed token
    window.location.href = `${data.redirect.url}?token=${encodeURIComponent(data.redirect.token)}`;
  }
}</code></pre>
                        </div>

                        <!-- PHP cURL -->
                        <div id="tab_php_create" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>&lt;?php
$payload = [
    'bridge_token' => 'op_cb_YOUR_BRIDGE_TOKEN',
    'items' => [
        ['id' => 14, 'quantity' => 2],
        ['id' => 20, 'quantity' => 1],
    ],
    'customer' => [
        'full_name' => $_POST['full_name'],
        'phone'     => $_POST['phone'],
        'address'   => $_POST['address'],
    ],
    'shipping' => [
        'id'    => 'inside_dhaka',
        'label' => 'Inside Dhaka',
        'cost'  => 80,
    ],
];

$ch = curl_init('<?php echo esc_js($op_cb_site_rest_url . 'create-order'); ?>');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload),
]);

$data = json_decode(curl_exec($ch), true);
curl_close($ch);

if ($data['success'] ?? false) {
    $url = $data['redirect']['url'] . '?token=' . urlencode($data['redirect']['token']);
    header('Location: ' . $url);
    exit;
}</code></pre>
                        </div>

                        <!-- React -->
                        <div id="tab_react_create" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>// React / Next.js (App Router or Pages Router)
export async function submitLandingOrder(formData) {
  const response = await fetch('<?php echo esc_js($op_cb_site_rest_url . 'create-order'); ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      bridge_token: process.env.NEXT_PUBLIC_BRIDGE_TOKEN,
      items: formData.selectedItems, // [{ id: 14, quantity: 2 }, { id: 20, quantity: 1 }]
      customer: {
        full_name: formData.name,
        phone:     formData.phone,
        address:   formData.address,
      },
      shipping: {
        id:    formData.shippingId,   // 'inside_dhaka' | 'outside_dhaka' | 'free_shipping'
        label: formData.shippingLabel,
        cost:  formData.shippingCost,
      },
    }),
  });

  const data = await response.json();
  if (data.success) {
    window.location.href = `${data.redirect.url}?token=${encodeURIComponent(data.redirect.token)}`;
  }
}</code></pre>
                        </div>

                        <!-- Python -->
                        <div id="tab_python_create" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>import requests

response = requests.post(
    '<?php echo esc_js($op_cb_site_rest_url . 'create-order'); ?>',
    json={
        'bridge_token': 'op_cb_YOUR_BRIDGE_TOKEN',
        'items': [
            {'id': 14, 'quantity': 2},
            {'id': 20, 'quantity': 1}
        ],
        'customer': {
            'full_name': request.form['full_name'],
            'phone':     request.form['phone'],
            'address':   request.form['address'],
        },
        'shipping': {
            'id':    'inside_dhaka',
            'label': 'Inside Dhaka',
            'cost':  80,
        }
    }
)

data = response.json()
if data.get('success'):
    redirect_url = f"{data['redirect']['url']}?token={data['redirect']['token']}"
</code></pre>
                        </div>

                        <!-- Node.js -->
                        <div id="tab_node_create" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>// Node.js Express / Axios backend
const axios = require('axios');

async function createBridgeOrder(orderPayload) {
  const { data } = await axios.post('<?php echo esc_js($op_cb_site_rest_url . 'create-order'); ?>', {
    bridge_token: 'op_cb_YOUR_BRIDGE_TOKEN',
    items: [
      { id: 14, quantity: 2 },
      { id: 20, quantity: 1 }
    ],
    customer: {
      full_name: orderPayload.fullName,
      phone:     orderPayload.phone,
      address:   orderPayload.address
    },
    shipping: {
      id:    'inside_dhaka',
      label: 'Inside Dhaka',
      cost:  80
    }
  });

  return data;
}</code></pre>
                        </div>

                        <!-- Go -->
                        <div id="tab_go_create" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>package main

import (
    "bytes"
    "encoding/json"
    "fmt"
    "net/http"
)

func main() {
    payload := map[string]interface{}{
        "bridge_token": "op_cb_YOUR_BRIDGE_TOKEN",
        "items": []map[string]int{
            {"id": 14, "quantity": 2},
            {"id": 20, "quantity": 1},
        },
        "customer": map[string]string{
            "full_name": "John Doe",
            "phone":     "01700000000",
            "address":   "Dhaka, Bangladesh",
        },
        "shipping": map[string]interface{}{
            "id":    "inside_dhaka",
            "label": "Inside Dhaka",
            "cost":  80,
        },
    }

    body, _ := json.Marshal(payload)
    resp, err := http.Post("<?php echo esc_js($op_cb_site_rest_url . 'create-order'); ?>", "application/json", bytes.NewBuffer(body))
    if err != nil {
        panic(err)
    }
    defer resp.Body.Close()

    var res map[string]interface{}
    json.NewDecoder(resp.Body).Decode(&res)
    fmt.Println(res)
}</code></pre>
                        </div>

                        <!-- Ruby -->
                        <div id="tab_ruby_create" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>require 'net/http'
require 'json'
require 'uri'

uri = URI("<?php echo esc_js($op_cb_site_rest_url . 'create-order'); ?>")
http = Net::HTTP.new(uri.host, uri.port)
request = Net::HTTP::Post.new(uri.path, {'Content-Type' => 'application/json'})

request.body = {
  bridge_token: 'op_cb_YOUR_BRIDGE_TOKEN',
  items: [{ id: 14, quantity: 2 }, { id: 20, quantity: 1 }],
  customer: { full_name: 'John Doe', phone: '01700000000', address: 'Dhaka' },
  shipping: { id: 'inside_dhaka', label: 'Inside Dhaka', cost: 80 }
}.to_json

response = http.request(request)
puts response.body</code></pre>
                        </div>

                        <!-- cURL CLI -->
                        <div id="tab_curl_create" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>curl -X POST "<?php echo esc_js($op_cb_site_rest_url . 'create-order'); ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "bridge_token": "op_cb_YOUR_BRIDGE_TOKEN",
    "items": [
      { "id": 14, "quantity": 2 },
      { "id": 20, "quantity": 1 }
    ],
    "customer": {
      "full_name": "Rahim Ahmed",
      "phone": "01700000000",
      "address": "House 12, Road 4, Uttara, Dhaka"
    },
    "shipping": {
      "id": "inside_dhaka",
      "label": "Inside Dhaka",
      "cost": 80
    }
  }'</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Endpoint 2: Order Details & Receipt ── -->
        <div class="op-cb-card">
            <div class="op-cb-card-header">
                <h2>
                    <i class="fa-solid fa-receipt"></i>
                    <?php esc_html_e('Endpoint 2: Order Details & Receipt', 'op-checkoutbridge'); ?>
                </h2>
                <span class="op-cb-badge op-cb-badge-primary">POST</span>
            </div>
            <div class="op-cb-card-body">

                <div class="op-cb-endpoint-block">
                    <h3 class="op-cb-endpoint-title">
                        <span class="op-cb-badge op-cb-badge-primary">POST</span>
                        <code>/wp-json/checkoutbridge/v1/order-details</code>
                    </h3>
                    <p class="op-cb-endpoint-desc">
                        <?php esc_html_e('Validates the signed redirect token and returns order, customer, and line item details for rendering on the thank-you page.', 'op-checkoutbridge'); ?>
                    </p>

                    <div class="op-cb-code-wrapper">
                        <div class="op-cb-code-header">
                            <div class="op-cb-code-tabs">
                                <button type="button" class="op-cb-tab-btn is-active" data-tab="tab_js_details">JS Fetch</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_php_details">PHP cURL</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_react_details">React / Next.js</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_python_details">Python</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_node_details">Node.js</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_go_details">Go</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_ruby_details">Ruby</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_curl_details">cURL CLI</button>
                            </div>
                            <button type="button" class="op-cb-btn-copy-code" data-target="code_details_active">
                                <i class="fa-solid fa-copy" style="margin-right:0.125em;"></i>
                                <?php esc_html_e('Copy', 'op-checkoutbridge'); ?>
                            </button>
                        </div>

                        <!-- JS Fetch -->
                        <div id="tab_js_details" class="op-cb-code-tab-content">
                            <pre class="op-cb-code-block"><code>// Fetch order details on Thank-You page using signed redirect token
async function fetchOrderReceipt(token) {
  const res = await fetch('<?php echo esc_js($op_cb_site_rest_url . 'order-details'); ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ token: token })
  });
  const data = await res.json();
  if (data.success) {
    console.log("Order #", data.order.number, "Total:", data.order.total);
  }
}</code></pre>
                        </div>

                        <!-- PHP -->
                        <div id="tab_php_details" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>&lt;?php
$token   = $_GET['token'] ?? '';
$payload = json_encode(['token' => $token]);

$ch = curl_init('<?php echo esc_js($op_cb_site_rest_url . 'order-details'); ?>');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => $payload,
]);

$data = json_decode(curl_exec($ch), true);
curl_close($ch);</code></pre>
                        </div>

                        <!-- React / Next.js -->
                        <div id="tab_react_details" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>import { useEffect, useState } from 'react';

export default function ThankYouReceipt({ token }) {
  const [order, setOrder] = useState(null);

  useEffect(() => {
    async function fetchReceipt() {
      const res = await fetch('<?php echo esc_js($op_cb_site_rest_url . 'order-details'); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token: token })
      });
      const data = await res.json();
      if (data.success) setOrder(data.order);
    }
    if (token) fetchReceipt();
  }, [token]);

  return order ? &lt;div&gt;Order #{order.number} - Total: {order.total}&lt;/div&gt; : &lt;p&gt;Loading receipt...&lt;/p&gt;;
}</code></pre>
                        </div>

                        <!-- Python -->
                        <div id="tab_python_details" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>import requests

token = 'SIGNED_THANK_YOU_TOKEN'
response = requests.post(
    '<?php echo esc_js($op_cb_site_rest_url . 'order-details'); ?>',
    json={'token': token}
)

order_data = response.json()</code></pre>
                        </div>

                        <!-- Node.js -->
                        <div id="tab_node_details" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>const axios = require('axios');

async function getOrderDetails(token) {
  const { data } = await axios.post('<?php echo esc_js($op_cb_site_rest_url . 'order-details'); ?>', {
    token: token
  });
  return data;
}</code></pre>
                        </div>

                        <!-- Go -->
                        <div id="tab_go_details" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>package main

import (
    "bytes"
    "encoding/json"
    "fmt"
    "net/http"
)

func main() {
    body, _ := json.Marshal(map[string]string{"token": "SIGNED_THANK_YOU_TOKEN"})
    resp, err := http.Post("<?php echo esc_js($op_cb_site_rest_url . 'order-details'); ?>", "application/json", bytes.NewBuffer(body))
    if err != nil {
        panic(err)
    }
    defer resp.Body.Close()

    var res map[string]interface{}
    json.NewDecoder(resp.Body).Decode(&res)
    fmt.Println(res)
}</code></pre>
                        </div>

                        <!-- Ruby -->
                        <div id="tab_ruby_details" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>require 'net/http'
require 'json'
require 'uri'

uri = URI("<?php echo esc_js($op_cb_site_rest_url . 'order-details'); ?>")
response = Net::HTTP.post(uri, { token: 'SIGNED_THANK_YOU_TOKEN' }.to_json, "Content-Type" => "application/json")
puts response.body</code></pre>
                        </div>

                        <!-- cURL -->
                        <div id="tab_curl_details" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>curl -X POST "<?php echo esc_js($op_cb_site_rest_url . 'order-details'); ?>" \
  -H "Content-Type: application/json" \
  -d '{"token": "SIGNED_THANK_YOU_TOKEN"}'</code></pre>
                        </div>
                    </div>

                    <!-- Response JSON Block -->
                    <div class="op-cb-code-wrapper op-cb-mt-3">
                        <div class="op-cb-code-header">
                            <span class="op-cb-code-lang-chip"><?php esc_html_e('JSON — Success Response (200 OK)', 'op-checkoutbridge'); ?></span>
                            <button type="button" class="op-cb-btn-copy-code" data-target="code_order_details">
                                <i class="fa-solid fa-copy" style="margin-right:0.125em;"></i>
                                <?php esc_html_e('Copy JSON', 'op-checkoutbridge'); ?>
                            </button>
                        </div>
                        <pre class="op-cb-code-block"><code id="code_order_details">{
  "success": true,
  "order": {
    "id":             1042,
    "number":         "1042",
    "status":         "processing",
    "subtotal":       1900,
    "shipping":       80,
    "discount_total": 100,
    "coupon_code":    "FLASH50",
    "total":          1880
  },
  "customer": {
    "full_name": "Tanvir Hassan",
    "phone":     "01812345678",
    "address":   "House 45, Road 7, Mirpur 10, Dhaka"
  },
  "shipping": {
    "id":    "inside_dhaka",
    "label": "Inside Dhaka Delivery",
    "cost":  80
  },
  "items": [
    {
      "product_id": 14,
      "name":       "Premium Leather Wallet",
      "quantity":   2,
      "unit_price": 750,
      "subtotal":   1500,
      "image_url":  "https://example.com/wp-content/uploads/wallet.jpg"
    },
    {
      "product_id": 26,
      "name":       "Test Product",
      "quantity":   1,
      "unit_price": 400,
      "subtotal":   400,
      "image_url":  "https://example.com/wp-content/uploads/test.jpg"
    }
  ]
}</code></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Endpoint 3: Validate Coupon / Promo Code ── -->
        <div class="op-cb-card">
            <div class="op-cb-card-header">
                <h2>
                    <i class="fa-solid fa-ticket"></i>
                    <?php esc_html_e('Endpoint 3: Validate Coupon / Promo Code', 'op-checkoutbridge'); ?>
                </h2>
                <span class="op-cb-badge op-cb-badge-success">POST</span>
            </div>
            <div class="op-cb-card-body">

                <div class="op-cb-endpoint-block">
                    <h3 class="op-cb-endpoint-title">
                        <span class="op-cb-badge op-cb-badge-success">POST</span>
                        <code>/wp-json/checkoutbridge/v1/validate-coupon</code>
                    </h3>
                    <p class="op-cb-endpoint-desc">
                        <?php esc_html_e('Validates a WooCommerce promo coupon code in real-time and returns the calculated discount amount, new subtotal, and total before order submission.', 'op-checkoutbridge'); ?>
                    </p>

                    <div class="op-cb-base-url-chip">
                        <span><?php esc_html_e('Base URL:', 'op-checkoutbridge'); ?></span>
                        <code><?php echo esc_html($op_cb_site_rest_url); ?>validate-coupon</code>
                        <button type="button" class="op-cb-btn-icon op-cb-btn-copy" data-clipboard="<?php echo esc_attr($op_cb_site_rest_url . 'validate-coupon'); ?>">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>

                    <!-- Code Snippet Tabs -->
                    <div class="op-cb-code-wrapper">
                        <div class="op-cb-code-header">
                            <div class="op-cb-code-tabs">
                                <button type="button" class="op-cb-tab-btn is-active" data-tab="tab_js_coupon">JS Fetch</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_php_coupon">PHP cURL</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_react_coupon">React / Next.js</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_python_coupon">Python</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_node_coupon">Node.js</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_go_coupon">Go</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_ruby_coupon">Ruby</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_curl_coupon">cURL CLI</button>
                            </div>
                            <button type="button" class="op-cb-btn-copy-code" data-target="code_coupon_active">
                                <i class="fa-solid fa-copy" style="margin-right:0.125em;"></i>
                                <?php esc_html_e('Copy', 'op-checkoutbridge'); ?>
                            </button>
                        </div>

                        <!-- JS Fetch -->
                        <div id="tab_js_coupon" class="op-cb-code-tab-content">
                            <pre class="op-cb-code-block"><code>async function validateCoupon(code, items, shippingCost) {
  const res = await fetch('<?php echo esc_js($op_cb_site_rest_url . 'validate-coupon'); ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      bridge_token: 'op_cb_YOUR_BRIDGE_TOKEN',
      coupon_code: code,
      items: items, // [{ id: 14, quantity: 2 }]
      shipping_cost: shippingCost
    })
  });
  const data = await res.json();
  if (data.success && data.coupon) {
    console.log("Discount Saved:", data.coupon.discount_amount, "New Total:", data.coupon.total);
  }
}</code></pre>
                        </div>

                        <!-- PHP cURL -->
                        <div id="tab_php_coupon" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>&lt;?php
$payload = json_encode([
    'bridge_token' => 'op_cb_YOUR_BRIDGE_TOKEN',
    'coupon_code'   => 'FLASH50',
    'items'         => [['id' => 14, 'quantity' => 2]],
    'shipping_cost' => 60
]);

$ch = curl_init('<?php echo esc_js($op_cb_site_rest_url . 'validate-coupon'); ?>');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => $payload,
]);

$data = json_decode(curl_exec($ch), true);
curl_close($ch);</code></pre>
                        </div>

                        <!-- React / Next.js -->
                        <div id="tab_react_coupon" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>import { useState } from 'react';

export default function CouponValidator() {
  const [discount, setDiscount] = useState(0);

  const applyCoupon = async (code) => {
    const res = await fetch('<?php echo esc_js($op_cb_site_rest_url . 'validate-coupon'); ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        bridge_token: 'op_cb_YOUR_BRIDGE_TOKEN',
        coupon_code: code,
        items: [{ id: 14, quantity: 2 }],
        shipping_cost: 60
      })
    });
    const data = await res.json();
    if (data.success && data.coupon) {
      setDiscount(data.coupon.discount_amount);
    }
  };

  return &lt;button onClick={() => applyCoupon('FLASH50')}&gt;Apply Coupon&lt;/button&gt;;
}</code></pre>
                        </div>

                        <!-- Python -->
                        <div id="tab_python_coupon" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>import requests

payload = {
    'bridge_token': 'op_cb_YOUR_BRIDGE_TOKEN',
    'coupon_code': 'FLASH50',
    'items': [{'id': 14, 'quantity': 2}],
    'shipping_cost': 60
}

response = requests.post(
    '<?php echo esc_js($op_cb_site_rest_url . 'validate-coupon'); ?>',
    json=payload
)

result = response.json()
if result.get('success'):
    print("Discount Amount:", result['coupon']['discount_amount'])</code></pre>
                        </div>

                        <!-- Node.js -->
                        <div id="tab_node_coupon" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>const axios = require('axios');

async function validateCoupon(code) {
  const { data } = await axios.post('<?php echo esc_js($op_cb_site_rest_url . 'validate-coupon'); ?>', {
    bridge_token: 'op_cb_YOUR_BRIDGE_TOKEN',
    coupon_code: code,
    items: [{ id: 14, quantity: 2 }],
    shipping_cost: 60
  });
  return data;
}</code></pre>
                        </div>

                        <!-- Go -->
                        <div id="tab_go_coupon" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>package main

import (
    "bytes"
    "encoding/json"
    "fmt"
    "net/http"
)

func main() {
    payload := map[string]interface{}{
        "bridge_token": "op_cb_YOUR_BRIDGE_TOKEN",
        "coupon_code":   "FLASH50",
        "items":         []map[string]int{{"id": 14, "quantity": 2}},
        "shipping_cost": 60,
    }
    body, _ := json.Marshal(payload)
    resp, err := http.Post("<?php echo esc_js($op_cb_site_rest_url . 'validate-coupon'); ?>", "application/json", bytes.NewBuffer(body))
    if err != nil {
        panic(err)
    }
    defer resp.Body.Close()

    var res map[string]interface{}
    json.NewDecoder(resp.Body).Decode(&res)
    fmt.Println(res)
}</code></pre>
                        </div>

                        <!-- Ruby -->
                        <div id="tab_ruby_coupon" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>require 'net/http'
require 'json'
require 'uri'

uri = URI.parse('<?php echo esc_js($op_cb_site_rest_url . 'validate-coupon'); ?>')
payload = {
  bridge_token: 'op_cb_YOUR_BRIDGE_TOKEN',
  coupon_code: 'FLASH50',
  items: [{ id: 14, quantity: 2 }],
  shipping_cost: 60
}

http = Net::HTTP.new(uri.host, uri.port)
request = Net::HTTP::Post.new(uri.path, {'Content-Type' => 'application/json'})
request.body = payload.to_json

response = http.request(request)
puts response.body</code></pre>
                        </div>

                        <!-- cURL CLI -->
                        <div id="tab_curl_coupon" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>curl -X POST "<?php echo esc_js($op_cb_site_rest_url . 'validate-coupon'); ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "bridge_token": "op_cb_YOUR_BRIDGE_TOKEN",
    "coupon_code": "FLASH50",
    "items": [{"id": 14, "quantity": 2}],
    "shipping_cost": 60
  }'</code></pre>
                        </div>
                    </div>

                    <!-- Response Sample -->
                    <div class="op-cb-code-wrapper op-cb-mt-3">
                        <div class="op-cb-code-header">
                            <span class="op-cb-code-lang-chip"><?php esc_html_e('JSON — Success Response (200 OK)', 'op-checkoutbridge'); ?></span>
                            <button type="button" class="op-cb-btn-copy-code" data-target="code_coupon_resp">
                                <i class="fa-solid fa-copy" style="margin-right:0.125em;"></i>
                                <?php esc_html_e('Copy JSON', 'op-checkoutbridge'); ?>
                            </button>
                        </div>
                        <pre class="op-cb-code-block"><code id="code_coupon_resp">{
  "success": true,
  "valid": true,
  "coupon": {
    "code": "FLASH50",
    "discount_type": "percent",
    "coupon_amount": 10,
    "discount_amount": 80,
    "discount_formatted": "৳80.00",
    "subtotal": 800,
    "shipping": 60,
    "total": 780
  },
  "message": "Coupon code \"FLASH50\" applied successfully."
}</code></pre>
                    </div>

                </div>

            </div>
        </div>

        <!-- ── Endpoint 4: Integration Health Check ── -->
        <div class="op-cb-card">
            <div class="op-cb-card-header">
                <h2>
                    <i class="fa-solid fa-heart-pulse"></i>
                    <?php esc_html_e('Endpoint 4: Integration Health Check', 'op-checkoutbridge'); ?>
                </h2>
                <span class="op-cb-badge op-cb-badge-info">GET</span>
            </div>
            <div class="op-cb-card-body">

                <div class="op-cb-endpoint-block">
                    <h3 class="op-cb-endpoint-title">
                        <span class="op-cb-badge op-cb-badge-info">GET</span>
                        <code>/wp-json/checkoutbridge/v1/health</code>
                    </h3>
                    <p class="op-cb-endpoint-desc">
                        <?php esc_html_e('Verifies that the CheckoutBridge REST API infrastructure is operational, active, and unblocked by server WAF rules.', 'op-checkoutbridge'); ?>
                    </p>

                    <div class="op-cb-base-url-chip">
                        <span><?php esc_html_e('Base URL:', 'op-checkoutbridge'); ?></span>
                        <code><?php echo esc_html($op_cb_site_rest_url); ?>health</code>
                        <button type="button" class="op-cb-btn-icon op-cb-btn-copy" data-clipboard="<?php echo esc_attr($op_cb_site_rest_url . 'health'); ?>">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>

                    <!-- Code Snippet Tabs -->
                    <div class="op-cb-code-wrapper op-cb-mt-3">
                        <div class="op-cb-code-header">
                            <div class="op-cb-code-tabs">
                                <button type="button" class="op-cb-tab-btn is-active" data-tab="tab_js_health">JS Fetch</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_php_health">PHP cURL</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_react_health">React / Next.js</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_python_health">Python</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_node_health">Node.js</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_go_health">Go</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_ruby_health">Ruby</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_curl_health">cURL CLI</button>
                            </div>
                            <button type="button" class="op-cb-btn-copy-code" data-target="code_health_active">
                                <i class="fa-solid fa-copy" style="margin-right:0.125em;"></i>
                                <?php esc_html_e('Copy', 'op-checkoutbridge'); ?>
                            </button>
                        </div>

                        <!-- JS Fetch -->
                        <div id="tab_js_health" class="op-cb-code-tab-content">
                            <pre class="op-cb-code-block"><code>async function checkHealth() {
  const res = await fetch('<?php echo esc_js($op_cb_site_rest_url . 'health'); ?>');
  const data = await res.json();
  if (data.status === 'ok') console.log("CheckoutBridge API Active v" + data.version);
}</code></pre>
                        </div>

                        <!-- PHP cURL -->
                        <div id="tab_php_health" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>&lt;?php
$res = file_get_contents('<?php echo esc_js($op_cb_site_rest_url . 'health'); ?>');
$data = json_decode($res, true);
if (($data['status'] ?? '') === 'ok') {
    echo "API Healthy v" . $data['version'];
}</code></pre>
                        </div>

                        <!-- React / Next.js -->
                        <div id="tab_react_health" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>import { useEffect, useState } from 'react';

export default function ApiHealthBadge() {
  const [healthy, setHealthy] = useState(false);

  useEffect(() => {
    fetch('<?php echo esc_js($op_cb_site_rest_url . 'health'); ?>')
      .then(res => res.json())
      .then(data => setHealthy(data.status === 'ok'));
  }, []);

  return &lt;span&gt;API Status: {healthy ? '🟢 Operational' : '🔴 Offline'}&lt;/span&gt;;
}</code></pre>
                        </div>

                        <!-- Python -->
                        <div id="tab_python_health" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>import requests

res = requests.get('<?php echo esc_js($op_cb_site_rest_url . 'health'); ?>').json()
if res.get('status') == 'ok':
    print("API Operational v" + res.get('version'))</code></pre>
                        </div>

                        <!-- Node.js -->
                        <div id="tab_node_health" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>const axios = require('axios');

async function checkHealth() {
  const { data } = await axios.get('<?php echo esc_js($op_cb_site_rest_url . 'health'); ?>');
  return data.status === 'ok';
}</code></pre>
                        </div>

                        <!-- Go -->
                        <div id="tab_go_health" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>package main

import (
    "encoding/json"
    "fmt"
    "net/http"
)

func main() {
    resp, err := http.Get("<?php echo esc_js($op_cb_site_rest_url . 'health'); ?>")
    if err != nil { panic(err) }
    defer resp.Body.Close()

    var res map[string]interface{}
    json.NewDecoder(resp.Body).Decode(&res)
    fmt.Println(res)
}</code></pre>
                        </div>

                        <!-- Ruby -->
                        <div id="tab_ruby_health" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>require 'net/http'
require 'json'

res = Net::HTTP.get(URI('<?php echo esc_js($op_cb_site_rest_url . 'health'); ?>'))
puts JSON.parse(res)</code></pre>
                        </div>

                        <!-- cURL CLI -->
                        <div id="tab_curl_health" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>curl -X GET "<?php echo esc_js($op_cb_site_rest_url . 'health'); ?>"</code></pre>
                        </div>
                    </div>

                    <!-- Response Sample -->
                    <div class="op-cb-code-wrapper op-cb-mt-3">
                        <div class="op-cb-code-header">
                            <span class="op-cb-code-lang-chip"><?php esc_html_e('JSON — Success Response (200 OK)', 'op-checkoutbridge'); ?></span>
                        </div>
                        <pre class="op-cb-code-block"><code>{
  "success": true,
  "status": "ok",
  "waf_shield": "active",
  "message": "CheckoutBridge REST API is active and healthy.",
  "version": "1.0.0"
}</code></pre>
                    </div>

                </div>

            </div>
        </div>

        <!-- ── Endpoint 5: Customer Phone Autofill Lookup ── -->
        <div class="op-cb-card">
            <div class="op-cb-card-header">
                <h2>
                    <i class="fa-solid fa-address-book"></i>
                    <?php esc_html_e('Endpoint 5: Customer Phone Autofill Lookup', 'op-checkoutbridge'); ?>
                </h2>
                <span class="op-cb-badge op-cb-badge-success">POST</span>
            </div>
            <div class="op-cb-card-body">

                <div class="op-cb-endpoint-block">
                    <h3 class="op-cb-endpoint-title">
                        <span class="op-cb-badge op-cb-badge-success">POST</span>
                        <code>/wp-json/checkoutbridge/v1/customer-lookup</code>
                    </h3>
                    <p class="op-cb-endpoint-desc">
                        <?php esc_html_e('Searches WooCommerce for existing customer records matching a phone number and returns name, address, and city for real-time 1-click form auto-filling.', 'op-checkoutbridge'); ?>
                    </p>

                    <div class="op-cb-base-url-chip">
                        <span><?php esc_html_e('Base URL:', 'op-checkoutbridge'); ?></span>
                        <code><?php echo esc_html($op_cb_site_rest_url); ?>customer-lookup</code>
                        <button type="button" class="op-cb-btn-icon op-cb-btn-copy" data-clipboard="<?php echo esc_attr($op_cb_site_rest_url . 'customer-lookup'); ?>">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>

                    <!-- Code Snippet Tabs -->
                    <div class="op-cb-code-wrapper op-cb-mt-3">
                        <div class="op-cb-code-header">
                            <div class="op-cb-code-tabs">
                                <button type="button" class="op-cb-tab-btn is-active" data-tab="tab_js_lookup">JS Fetch</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_php_lookup">PHP cURL</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_react_lookup">React / Next.js</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_python_lookup">Python</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_node_lookup">Node.js</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_go_lookup">Go</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_ruby_lookup">Ruby</button>
                                <button type="button" class="op-cb-tab-btn" data-tab="tab_curl_lookup">cURL CLI</button>
                            </div>
                            <button type="button" class="op-cb-btn-copy-code" data-target="code_lookup_active">
                                <i class="fa-solid fa-copy" style="margin-right:0.125em;"></i>
                                <?php esc_html_e('Copy', 'op-checkoutbridge'); ?>
                            </button>
                        </div>

                        <!-- JS Fetch -->
                        <div id="tab_js_lookup" class="op-cb-code-tab-content">
                            <pre class="op-cb-code-block"><code>async function lookupCustomer(phone) {
  const res = await fetch('<?php echo esc_js($op_cb_site_rest_url . 'customer-lookup'); ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      bridge_token: 'op_cb_your_token_here',
      phone: phone
    })
  });
  const data = await res.json();
  if (data.found) {
    document.getElementById('full_name').value = data.customer.full_name;
    document.getElementById('address').value = data.customer.address;
    document.getElementById('city').value = data.customer.city;
  }
}</code></pre>
                        </div>

                        <!-- PHP cURL -->
                        <div id="tab_php_lookup" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>&lt;?php
$payload = json_encode([
    'bridge_token' => 'op_cb_your_token_here',
    'phone'        => '01711000000'
]);

$ch = curl_init('<?php echo esc_js($op_cb_site_rest_url . 'customer-lookup'); ?>');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => $payload,
]);

$data = json_decode(curl_exec($ch), true);
curl_close($ch);</code></pre>
                        </div>

                        <!-- React / Next.js -->
                        <div id="tab_react_lookup" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>const handlePhoneBlur = async (phone) => {
  if (phone.length < 10) return;
  const res = await fetch('<?php echo esc_js($op_cb_site_rest_url . 'customer-lookup'); ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ bridge_token: 'op_cb_your_token_here', phone })
  });
  const data = await res.json();
  if (data.found) {
    setFormData(prev => ({
      ...prev,
      full_name: data.customer.full_name,
      address: data.customer.address,
      city: data.customer.city
    }));
  }
};</code></pre>
                        </div>

                        <!-- Python -->
                        <div id="tab_python_lookup" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>import requests

response = requests.post(
    '<?php echo esc_js($op_cb_site_rest_url . 'customer-lookup'); ?>',
    json={'bridge_token': 'op_cb_your_token_here', 'phone': '01711000000'}
)
print(response.json())</code></pre>
                        </div>

                        <!-- Node.js -->
                        <div id="tab_node_lookup" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>const axios = require('axios');

async function lookupCustomer(phone) {
  const { data } = await axios.post('<?php echo esc_js($op_cb_site_rest_url . 'customer-lookup'); ?>', {
    bridge_token: 'op_cb_your_token_here',
    phone: phone
  });
  return data;
}</code></pre>
                        </div>

                        <!-- Go -->
                        <div id="tab_go_lookup" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>package main

import (
    "bytes"
    "encoding/json"
    "fmt"
    "net/http"
)

func main() {
    body, _ := json.Marshal(map[string]string{
        "bridge_token": "op_cb_your_token_here",
        "phone": "01711000000",
    })
    resp, err := http.Post("<?php echo esc_js($op_cb_site_rest_url . 'customer-lookup'); ?>", "application/json", bytes.NewBuffer(body))
    if err != nil { panic(err) }
    defer resp.Body.Close()

    var result map[string]interface{}
    json.NewDecoder(resp.Body).Decode(&result)
    fmt.Println(result)
}</code></pre>
                        </div>

                        <!-- Ruby -->
                        <div id="tab_ruby_lookup" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>require 'net/http'
require 'json'

uri = URI('<?php echo esc_js($op_cb_site_rest_url . 'customer-lookup'); ?>')
req = Net::HTTP::Post.new(uri, 'Content-Type' => 'application/json')
req.body = { bridge_token: 'op_cb_your_token_here', phone: '01711000000' }.to_json

res = Net::HTTP.start(uri.hostname, uri.port, use_ssl: uri.scheme == 'https') do |http|
  http.request(req)
end
puts JSON.parse(res.body)</code></pre>
                        </div>

                        <!-- cURL CLI -->
                        <div id="tab_curl_lookup" class="op-cb-code-tab-content op-cb-hidden">
                            <pre class="op-cb-code-block"><code>curl -X POST "<?php echo esc_js($op_cb_site_rest_url . 'customer-lookup'); ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "bridge_token": "op_cb_your_token_here",
    "phone": "01711000000"
  }'</code></pre>
                        </div>
                    </div>

                    <!-- Response Sample -->
                    <div class="op-cb-code-wrapper op-cb-mt-3">
                        <div class="op-cb-code-header">
                            <span class="op-cb-code-lang-chip"><?php esc_html_e('JSON — Success Response (200 OK)', 'op-checkoutbridge'); ?></span>
                        </div>
                        <pre class="op-cb-code-block"><code>{
  "success": true,
  "found": true,
  "customer": {
    "full_name": "Tanvir Hassan",
    "address": "House 45, Road 7, Mirpur 10",
    "city": "Dhaka",
    "email": "tanvir@example.com"
  },
  "stats": {
    "total_orders": 3
  }
}</code></pre>
                    </div>

                </div>

            </div>
        </div>

        <!-- ── Error Reference ── -->
        <div class="op-cb-card">
            <div class="op-cb-card-header">
                <h2>
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <?php esc_html_e('REST API Error Reference', 'op-checkoutbridge'); ?>
                </h2>
            </div>
            <div class="op-cb-card-body op-cb-p-0">
                <div class="op-cb-table-wrap">
                    <table class="op-cb-table">
                        <thead>
                            <tr>
                                <th style="width:28%;text-align:start;"><?php esc_html_e('Error Code', 'op-checkoutbridge'); ?></th>
                                <th style="width:14%;text-align:start;"><?php esc_html_e('HTTP', 'op-checkoutbridge'); ?></th>
                                <th style="text-align:start;"><?php esc_html_e('Description & Resolution', 'op-checkoutbridge'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>missing_bridge_token</code></td>
                                <td><span class="op-cb-badge op-cb-badge-warning">400</span></td>
                                <td style="font-size:13px;"><?php esc_html_e('bridge_token field is missing from the request payload.', 'op-checkoutbridge'); ?></td>
                            </tr>
                            <tr>
                                <td><code>invalid_bridge_token</code></td>
                                <td><span class="op-cb-badge op-cb-badge-warning">404</span></td>
                                <td style="font-size:13px;"><?php esc_html_e('The token does not match any campaign in CheckoutBridge.', 'op-checkoutbridge'); ?></td>
                            </tr>
                            <tr>
                                <td><code>landing_inactive</code></td>
                                <td><span class="op-cb-badge op-cb-badge-danger">403</span></td>
                                <td style="font-size:13px;"><?php esc_html_e('Campaign is set to Inactive in WordPress admin.', 'op-checkoutbridge'); ?></td>
                            </tr>
                            <tr>
                                <td><code>origin_forbidden</code></td>
                                <td><span class="op-cb-badge op-cb-badge-danger">403</span></td>
                                <td style="font-size:13px;"><?php esc_html_e('Request origin is not in the campaign CORS whitelist.', 'op-checkoutbridge'); ?></td>
                            </tr>
                            <tr>
                                <td><code>invalid_customer</code></td>
                                <td><span class="op-cb-badge op-cb-badge-warning">400</span></td>
                                <td style="font-size:13px;"><?php esc_html_e('One or more of full_name, phone, address fields are missing.', 'op-checkoutbridge'); ?></td>
                            </tr>
                            <tr>
                                <td><code>invalid_coupon</code></td>
                                <td><span class="op-cb-badge op-cb-badge-warning">400</span></td>
                                <td style="font-size:13px;"><?php esc_html_e('The coupon code provided is invalid, expired, or does not exist.', 'op-checkoutbridge'); ?></td>
                            </tr>
                            <tr>
                                <td><code>invalid_token</code></td>
                                <td><span class="op-cb-badge op-cb-badge-danger">401</span></td>
                                <td style="font-size:13px;"><?php esc_html_e('Redirect token signature invalid or expired.', 'op-checkoutbridge'); ?></td>
                            </tr>
                            <tr>
                                <td><code>rate_limited</code></td>
                                <td><span class="op-cb-badge op-cb-badge-danger">429</span></td>
                                <td style="font-size:13px;"><?php esc_html_e('Too many requests from this IP. Retry after the rate limit window.', 'op-checkoutbridge'); ?></td>
                            </tr>
                            <tr>
                                <td><code>internal_error</code></td>
                                <td><span class="op-cb-badge op-cb-badge-dark">500</span></td>
                                <td style="font-size:13px;"><?php esc_html_e('Unexpected server error. Check WordPress debug log for details.', 'op-checkoutbridge'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── Shared Hosting & Imunify360 WAF Compatibility Shield ── -->
        <div class="op-cb-card">
            <div class="op-cb-card-header">
                <h2>
                    <i class="fa-solid fa-shield-cat"></i>
                    <?php esc_html_e('Shared Hosting & Imunify360 WAF Compatibility Shield', 'op-checkoutbridge'); ?>
                </h2>
            </div>
            <div class="op-cb-card-body">
                <p style="font-size:13.5px;color:var(--cb-text-500);margin:0 0 16px 0;line-height:1.65;">
                    <?php esc_html_e('Shared hosting platforms (cPanel, Plesk, LiteSpeed, Apache) often run Imunify360 or ModSecurity Web Application Firewalls (WAF) that may block cross-origin REST API calls or JSON POST payloads. CheckoutBridge includes a built-in 5-layer WAF compatibility shield to ensure 100% order delivery success.', 'op-checkoutbridge'); ?>
                </p>

                <div class="op-cb-grid op-cb-grid-3">
                    <div style="padding: 1.125em; background: var(--cb-surface-2); border: 1px solid var(--cb-border); border-radius: var(--cb-r-md);">
                        <span class="op-cb-badge op-cb-badge-success" style="margin-bottom:0.5em;">LAYER 1: PREFLIGHT</span>
                        <h4 style="font-size:0.875em;margin:0 0 0.375em 0;color:var(--cb-text-900);font-weight:600;">CORS Interceptor</h4>
                        <p style="font-size:0.8125em;color:var(--cb-text-500);margin:0;line-height:1.5;">
                            Early <code>OPTIONS</code> preflight requests are intercepted before WP REST routing runs, sending instant 204 status codes to prevent preflight drops.
                        </p>
                    </div>
                    <div style="padding: 1.125em; background: var(--cb-surface-2); border: 1px solid var(--cb-border); border-radius: var(--cb-r-md);">
                        <span class="op-cb-badge op-cb-badge-info" style="margin-bottom:0.5em;">LAYER 2: MULTI-FORMAT</span>
                        <h4 style="font-size:0.875em;margin:0 0 0.375em 0;color:var(--cb-text-900);font-weight:600;">Form Data & JSON</h4>
                        <p style="font-size:0.8125em;color:var(--cb-text-500);margin:0;line-height:1.5;">
                            Accepts raw JSON, standard Form Data (<code>multipart/form-data</code>), or URL-encoded payloads seamlessly if a host WAF inspects JSON bodies.
                        </p>
                    </div>
                    <div style="padding: 1.125em; background: var(--cb-surface-2); border: 1px solid var(--cb-border); border-radius: var(--cb-r-md);">
                        <span class="op-cb-badge op-cb-badge-primary" style="margin-bottom:0.5em;">LAYER 3: BASE64 EVASION</span>
                        <h4 style="font-size:0.875em;margin:0 0 0.375em 0;color:var(--cb-text-900);font-weight:600;">Base64 Payload Option</h4>
                        <p style="font-size:0.8125em;color:var(--cb-text-500);margin:0;line-height:1.5;">
                            Send payloads encoded as <code>payload: btoa(JSON.stringify(data))</code> to bypass aggressive ModSecurity body inspection rules completely.
                        </p>
                    </div>
                </div>

                <div class="op-cb-callout op-cb-mt-4" style="margin-bottom:0;">
                    <div class="op-cb-callout-icon">
                        <i class="fa-solid fa-server" style="color:var(--cb-indigo-600);"></i>
                    </div>
                    <div class="op-cb-callout-content">
                        <h4>Optional Shared Host .htaccess Rule (cPanel / Apache)</h4>
                        <p style="font-size:12.5px;color:var(--cb-text-700);margin:4px 0 8px 0;line-height:1.55;">
                            If your hosting provider runs strict Imunify360 rule filters, add this rule to your site's <code>.htaccess</code> file to whitelist CheckoutBridge REST routes:
                        </p>
                        <pre class="op-cb-code-block" style="padding:12px 16px;"><code style="font-size:11.5px;">&lt;IfModule mod_rewrite.c&gt;
  RewriteEngine On
  RewriteCond %{REQUEST_URI} ^/wp-json/checkoutbridge/v1/ [NC]
  RewriteRule .* - [E=no-abort:1,E=dont-vari:1]
&lt;/IfModule&gt;</code></pre>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── Meta (Facebook) Conversion CAPI Helper ── -->
        <div class="op-cb-card">
            <div class="op-cb-card-header">
                <h2>
                    <i class="fa-brands fa-facebook" style="color:#1877f2;"></i>
                    <?php esc_html_e('Meta (Facebook) Conversion CAPI (Server-Side Tracking) Helper', 'op-checkoutbridge'); ?>
                </h2>
            </div>
            <div class="op-cb-card-body">
                <p style="font-size:13.5px;color:var(--cb-text-500);margin:0 0 16px 0;line-height:1.65;">
                    <?php esc_html_e('Bypass iOS 14+ ad blockers and achieve 100% Facebook Event Match Quality (EMQ) by sending Meta Server-Side Conversions API tokens directly with your CheckoutBridge order creation payload.', 'op-checkoutbridge'); ?>
                </p>

                <div class="op-cb-callout" style="margin-bottom:16px;">
                    <div class="op-cb-callout-icon">
                        <i class="fa-solid fa-code" style="color:var(--cb-indigo-600);"></i>
                    </div>
                    <div class="op-cb-callout-content">
                        <h4>JavaScript Cookie & Event ID Extraction Helper Snippet</h4>
                        <p style="font-size:12.5px;color:var(--cb-text-700);margin:4px 0 8px 0;line-height:1.55;">
                            Copy and paste this helper function into your custom landing page to automatically extract Meta <code>_fbp</code> and <code>_fbc</code> browser cookies:
                        </p>
                        <pre class="op-cb-code-block" style="padding:14px 16px;"><code style="font-size:11.5px;">// Helper to extract cookie values by name
function getMetaCookie(name) {
  const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
  return match ? match[2] : '';
}

// Generate unique deduplication Event ID for Meta CAPI
const metaEventId = 'cb_evt_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

// Construct API payload with Meta CAPI tokens
const orderPayload = {
  bridge_token: "op_cb_your_token_here",
  customer: {
    full_name: "Customer Name",
    phone: "01711000000",
    address: "Dhaka, Bangladesh"
  },
  meta_capi: {
    fbp: getMetaCookie('_fbp'),
    fbc: getMetaCookie('_fbc'),
    event_id: metaEventId
  }
};</code></pre>
                    </div>
                </div>

                <div class="op-cb-grid op-cb-grid-2">
                    <div style="padding: 1em; background: var(--cb-surface-2); border: 1px solid var(--cb-border); border-radius: var(--cb-r-md);">
                        <h4 style="font-size:0.875em;margin:0 0 0.375em 0;color:var(--cb-text-900);font-weight:600;">Stored Metadata Keys (HPOS Native)</h4>
                        <p style="font-size:0.8125em;color:var(--cb-text-500);margin:0;line-height:1.5;">
                            CheckoutBridge automatically attaches <code>_op_cb_fbp</code>, <code>_op_cb_fbc</code>, <code>_op_cb_event_id</code>, and <code>_op_cb_client_ip</code> to WooCommerce orders.
                        </p>
                    </div>
                    <div style="padding: 1em; background: var(--cb-surface-2); border: 1px solid var(--cb-border); border-radius: var(--cb-r-md);">
                        <h4 style="font-size:0.875em;margin:0 0 0.375em 0;color:var(--cb-text-900);font-weight:600;">Server Action Hook</h4>
                        <p style="font-size:0.8125em;color:var(--cb-text-500);margin:0;line-height:1.5;">
                            Fires <code>op_cb_order_created_meta_capi</code> on every order for instant server-side dispatch to Meta Graph CAPI endpoints.
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── Global Dual-Shield Anti-Bot & Velocity Protection ── -->
        <div class="op-cb-card">
            <div class="op-cb-card-header">
                <h2>
                    <i class="fa-solid fa-shield-halved" style="color:var(--cb-indigo-600);"></i>
                    <?php esc_html_e('Global Dual-Shield Anti-Bot & Order Velocity Protection', 'op-checkoutbridge'); ?>
                </h2>
            </div>
            <div class="op-cb-card-body">
                <p style="font-size:13.5px;color:var(--cb-text-500);margin:0 0 16px 0;line-height:1.65;">
                    <?php esc_html_e('Prevent competitors, proxy scrapers, and malicious automated bots from placing fake COD orders using E.164 International Phone Number and Client IP velocity limits.', 'op-checkoutbridge'); ?>
                </p>

                <div class="op-cb-grid op-cb-grid-2">
                    <div style="padding: 1.125em; background: var(--cb-surface-2); border: 1px solid var(--cb-border); border-radius: var(--cb-r-md);">
                        <span class="op-cb-badge op-cb-badge-primary" style="margin-bottom:0.5em;">SHIELD 1: GLOBAL E.164 PHONE VELOCITY</span>
                        <h4 style="font-size:0.875em;margin:0 0 0.375em 0;color:var(--cb-text-900);font-weight:600;">E.164 International Normalization</h4>
                        <p style="font-size:0.8125em;color:var(--cb-text-500);margin:0;line-height:1.5;">
                            Normalizes all global phone number formats (US <code>+1</code>, GCC <code>+971</code>/<code>+966</code>, UK <code>+44</code>, BD <code>+880</code>) into clean digit strings before enforcing configurable order velocity limits.
                        </p>
                    </div>
                    <div style="padding: 1.125em; background: var(--cb-surface-2); border: 1px solid var(--cb-border); border-radius: var(--cb-r-md);">
                        <span class="op-cb-badge op-cb-badge-info" style="margin-bottom:0.5em;">SHIELD 2: CLIENT IP VELOCITY</span>
                        <h4 style="font-size:0.875em;margin:0 0 0.375em 0;color:var(--cb-text-900);font-weight:600;">Client IP Proxy Defense</h4>
                        <p style="font-size:0.8125em;color:var(--cb-text-500);margin:0;line-height:1.5;">
                            Tracks order creation frequency per IP address across Cloudflare, NGINX, and WAF headers to block automated bot clusters trying multiple fake phone numbers.
                        </p>
                    </div>
                </div>

                <div class="op-cb-callout op-cb-mt-4" style="margin-bottom:0;">
                    <div class="op-cb-callout-icon">
                        <i class="fa-solid fa-triangle-exclamation" style="color:var(--cb-amber-600);"></i>
                    </div>
                    <div class="op-cb-callout-content">
                        <h4>API Velocity Exceeded HTTP 429 Response Handling</h4>
                        <p style="font-size:12.5px;color:var(--cb-text-700);margin:4px 0 8px 0;line-height:1.55;">
                            If a request exceeds either velocity threshold, CheckoutBridge returns an instant HTTP 429 response so your landing page can display a friendly notice:
                        </p>
                        <pre class="op-cb-code-block" style="padding:12px 16px;"><code style="font-size:11.5px;">{
  "success": false,
  "error": "phone_velocity_limit_exceeded", // or "ip_velocity_limit_exceeded"
  "message": "Maximum order limit reached for this phone number within 24 hours. Please contact customer support."
}</code></pre>
                    </div>
                </div>

            </div>
        </div>

    </div><!-- /grid -->

</div>


