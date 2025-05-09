<?php
session_start();
include_once 'config/database.php';

header('Content-Type: application/json');

$response = ['success' => false, 'error' => ''];

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
        $response['error'] = 'Please log in as a customer.';
        throw new Exception();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['food_id']) || !isset($_POST['quantity'])) {
        $response['error'] = 'Invalid request.';
        throw new Exception();
    }

    $foodId = (int)$_POST['food_id'];
    $quantity = (int)$_POST['quantity'];

    if ($foodId <= 0 || $quantity <= 0) {
        $response['error'] = 'Invalid food ID or quantity.';
        throw new Exception();
    }

    $foodQuery = "SELECT food_id, name, price, stock_qty FROM Food WHERE food_id = ? AND stock_qty >= ?";
    $foodStmt = $conn->prepare($foodQuery);
    $foodStmt->execute([$foodId, $quantity]);
    $food = $foodStmt->fetch(PDO::FETCH_ASSOC);

    if (!$food) {
        $response['error'] = 'Food item not available or out of stock.';
        throw new Exception();
    }

    $cart = isset($_SESSION['cart']) ? json_decode($_SESSION['cart'], true) : [];
    $found = false;
    foreach ($cart as &$item) {
        if ($item['id'] == $foodId) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $cart[] = [
            'id' => $foodId,
            'name' => $food['name'],
            'price' => $food['price'],
            'quantity' => $quantity
        ];
    }

    $_SESSION['cart'] = json_encode($cart);
    $response['success'] = true;

} catch (Exception $e) {
    if (empty($response['error'])) {
        $response['error'] = 'An unexpected error occurred.';
    }
}

echo json_encode($response);
?>