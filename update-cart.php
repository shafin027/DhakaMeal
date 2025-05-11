<?php
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'cartCount' => 0, 'cartTotal' => 0, 'quantity' => 0, 'itemTotal' => 0];

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'customer') {
    $response['message'] = 'You must be logged in as a customer to update the cart.';
    echo json_encode($response);
    exit;
}

$foodId = $_POST['food_id'] ?? null;
$action = $_POST['action'] ?? '';

if (!$foodId || !$action) {
    $response['message'] = 'Invalid request: Missing food_id or action.';
    echo json_encode($response);
    exit;
}

if (!isset($_SESSION['cart'])) {
    $response['message'] = 'Cart is empty.';
    echo json_encode($response);
    exit;
}

$found = false;
foreach ($_SESSION['cart'] as $index => &$item) {
    if ($item['food_id'] == $foodId) {
        $found = true;
        if ($action === 'increase') {
            $item['quantity'] += 1;
        } elseif ($action === 'decrease' && $item['quantity'] > 1) {
            $item['quantity'] -= 1;
        } elseif ($action === 'remove') {
            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            if (empty($_SESSION['cart'])) {
                unset($_SESSION['cart']);
            }
        } else {
            $response['message'] = 'Invalid action or quantity cannot go below 1.';
            echo json_encode($response);
            exit;
        }
        $response['action'] = $action;
        $response['quantity'] = $item['quantity'] ?? 0;
        $response['itemTotal'] = isset($item) ? $item['price'] * $item['quantity'] : 0;
        break;
    }
}

if (!$found) {
    $response['message'] = 'Item not found in cart.';
    echo json_encode($response);
    exit;
}

// Calculate cart total and count
$cartTotal = 0;
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartTotal += $item['price'] * $item['quantity'];
        $cartCount += $item['quantity'];
    }
}

$response['success'] = true;
$response['cartTotal'] = $cartTotal;
$response['cartCount'] = $cartCount;

echo json_encode($response);
exit;