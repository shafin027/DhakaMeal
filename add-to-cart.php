<?php
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'cartCount' => 0];

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'customer') {
    $response['message'] = 'You must be logged in as a customer to add items to the cart.';
    echo json_encode($response);
    exit;
}

$foodId = $_POST['food_id'] ?? null;
$name = $_POST['name'] ?? '';
$price = floatval($_POST['price'] ?? 0);
$discountPercentage = floatval($_POST['discount_percentage'] ?? 0);
$originalPrice = floatval($_POST['original_price'] ?? 0);
$restaurantId = $_POST['restaurant_id'] ?? null;

if (!$foodId || !$restaurantId) {
    $response['message'] = 'Invalid food or restaurant ID.';
    echo json_encode($response);
    exit;
}

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if cart has items from a different restaurant
if (!empty($_SESSION['cart']) && $_SESSION['cart'][0]['restaurant_id'] != $restaurantId) {
    $response['message'] = 'You can only add items from one restaurant at a time. Please clear your cart to add items from this restaurant.';
    echo json_encode($response);
    exit;
}

// Check if item already exists in cart
$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['food_id'] == $foodId) {
        $item['quantity'] += 1;
        $found = true;
        break;
    }
}

if (!$found) {
    $_SESSION['cart'][] = [
        'food_id' => $foodId,
        'name' => $name,
        'price' => $price,
        'discount_percentage' => $discountPercentage,
        'original_price' => $originalPrice,
        'quantity' => 1,
        'restaurant_id' => $restaurantId
    ];
}

// Calculate cart count (total quantity of items)
$cartCount = 0;
foreach ($_SESSION['cart'] as $item) {
    $cartCount += $item['quantity'];
}

$response['success'] = true;
$response['cartCount'] = $cartCount;

echo json_encode($response);
exit;