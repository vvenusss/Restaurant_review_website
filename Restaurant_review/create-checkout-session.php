<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Only authenticated diners can proceed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'diner') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/stripe-config.php';

// ── Validate tip amount ───────────────────────────────────────────────────────
$tipAmount      = isset($_POST['tip_amount']) ? (float) $_POST['tip_amount'] : 0;
$restaurantId   = isset($_POST['restaurant_id']) ? (int) $_POST['restaurant_id'] : 0;
$restaurantName = isset($_POST['restaurant_name']) ? trim($_POST['restaurant_name']) : 'Restaurant';

if ($tipAmount < 0.50) {
    // Stripe minimum is 0.50 in most currencies
    header('Location: tip-prompt.php?restaurant_id=' . $restaurantId . '&error=min_amount');
    exit;
}

// Convert to cents (Stripe uses smallest currency unit)
$amountInCents = (int) round($tipAmount * 100);

// ── Create Stripe Checkout Session via cURL (no SDK needed) ──────────────────
$successUrl = SITE_BASE_URL . '/tip-success.php?restaurant_id=' . $restaurantId;
$cancelUrl  = SITE_BASE_URL . '/tip-cancel.php?restaurant_id='  . $restaurantId;

$postFields = http_build_query([
    'payment_method_types[]'                        => 'card',
    'line_items[0][price_data][currency]'           => STRIPE_CURRENCY,
    'line_items[0][price_data][product_data][name]' => 'Tip for ' . $restaurantName,
    'line_items[0][price_data][product_data][description]' => 'Thank you for supporting ' . $restaurantName . '!',
    'line_items[0][price_data][unit_amount]'        => $amountInCents,
    'line_items[0][quantity]'                       => 1,
    'mode'                                          => 'payment',
    'success_url'                                   => $successUrl,
    'cancel_url'                                    => $cancelUrl,
]);

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postFields,
    CURLOPT_USERPWD        => STRIPE_SECRET_KEY . ':',
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpCode === 200 && isset($data['url'])) {
    // Redirect customer to Stripe-hosted checkout page
    header('Location: ' . $data['url']);
    exit;
} else {
    // Something went wrong — show a friendly error
    $errorMsg = $data['error']['message'] ?? 'Payment setup failed. Please try again.';
    header('Location: tip-prompt.php?restaurant_id=' . $restaurantId . '&stripe_error=' . urlencode($errorMsg));
    exit;
}
