<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: login.php');
    exit;
}

// Fetch customer_id from Customer table using person_id
$personId = $_SESSION['user_id'];
$customerQuery = "SELECT customer_id FROM Customer WHERE person_id = ?";
$customerStmt = $conn->prepare($customerQuery);
$customerStmt->execute([$personId]);
$customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo "<p class='text-red-600 text-center'>Error: Customer not found.</p>";
    exit;
}
$customerId = $customer['customer_id'];

// Handle adding to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $foodId = (int)$_POST['food_id'];
    $name = htmlspecialchars($_POST['name']);
    $price = (float)$_POST['price'];

    $cart = isset($_SESSION['cart']) ? json_decode($_SESSION['cart'], true) : [];
    $itemExists = false;
    foreach ($cart as &$item) {
        if ($item['id'] == $foodId) {
            $item['quantity'] += 1;
            $itemExists = true;
            break;
        }
    }
    if (!$itemExists) {
        $cart[] = ['id' => $foodId, 'name' => $name, 'price' => $price, 'quantity' => 1];
    }
    $_SESSION['cart'] = json_encode($cart);
    header('Location: cart.php');
    exit;
}

// Fetch cart items
$cart = isset($_SESSION['cart']) ? json_decode($_SESSION['cart'], true) : [];
$total = 0;
$items = [];

if ($cart) {
    $foodIds = array_map(function($item) { return $item['id']; }, $cart);
    $placeholders = implode(',', array_fill(0, count($foodIds), '?'));
    $foodQuery = "SELECT food_id, name, price FROM Food WHERE food_id IN ($placeholders) AND restaurant_id = (SELECT restaurant_id FROM Restaurant WHERE name = 'DhakaMeal')";
    $foodStmt = $conn->prepare($foodQuery);
    $foodStmt->execute($foodIds);
    $foods = $foodStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cart as $item) {
        foreach ($foods as $food) {
            if ($food['food_id'] == $item['id']) {
                $itemTotal = $food['price'] * $item['quantity'];
                $items[] = [
                    'id' => $food['food_id'],
                    'name' => $food['name'],
                    'price' => $food['price'],
                    'quantity' => $item['quantity'],
                    'total' => $itemTotal
                ];
                $total += $itemTotal;
                break;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $paymentMethod = $_POST['payment_method'] ?? 'cod';
    $paymentStatus = 'pending';

    $conn->beginTransaction();
    try {
        $restaurantId = $conn->query("SELECT restaurant_id FROM Restaurant WHERE name = 'DhakaMeal'")->fetchColumn();
        // Assign delivery person (default to delivery1@example.com)
        $deliveryPersonId = $conn->query("SELECT delivery_person_id FROM DeliveryPerson WHERE person_id = (SELECT person_id FROM Person WHERE email = 'delivery1@example.com')")->fetchColumn();
        
        $orderQuery = "INSERT INTO `Order` (customer_id, restaurant_id, delivery_person_id, order_time, order_status, restaurant_status, payment_method, payment_status, total_price) VALUES (?, ?, ?, NOW(), 'pending', 'pending', ?, ?, ?)";
        $orderStmt = $conn->prepare($orderQuery);
        $orderStmt->execute([$customerId, $restaurantId, $deliveryPersonId, $paymentMethod, $paymentStatus, $total]);

        $orderId = $conn->lastInsertId();
        $orderItemQuery = "INSERT INTO OrderItem (order_id, food_id, quantity, price) VALUES (?, ?, ?, ?)";
        $orderItemStmt = $conn->prepare($orderItemQuery);

        foreach ($cart as $item) {
            $orderItemStmt->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
        }

        unset($_SESSION['cart']);
        $conn->commit();

        echo "<p class='text-green-600 text-center mt-4'>Order placed successfully! Order ID: #$orderId</p>";
    } catch (Exception $e) {
        $conn->rollBack();
        echo "<p class='text-red-600 text-center mt-4'>Error placing order: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Your Cart</h1>
    <?php if (empty($items)): ?>
        <p class='text-gray-600 text-center'>Your cart is empty.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($items as $item): ?>
                <div class="flex justify-between items-center bg-white shadow rounded-lg p-4">
                    <div>
                        <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p class="text-sm text-gray-600">৳<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?></p>
                    </div>
                    <p class="text-lg font-bold text-pink-600">৳<?php echo number_format($item['total'], 2); ?></p>
                </div>
            <?php endforeach; ?>
            <div class="mt-6 text-right">
                <p class="text-xl font-bold text-gray-900">Total: ৳<?php echo number_format($total, 2); ?></p>
            </div>
            <form method="POST" class="mt-6">
                <div class="mb-4">
                    <label class="block text-gray-700">Payment Method:</label>
                    <select name="payment_method" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <option value="cod">Cash on Delivery</option>
                    </select>
                </div>
                <button type="submit" name="place_order" class="w-full bg-pink-600 text-white px-6 py-3 rounded-lg hover:bg-pink-700 transition duration-300">Place Order</button>
            </form>
        </div>
    <?php endif; ?>
    <div class="mt-6">
        <a href="index.php" class="inline-block bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700 transition duration-300">Back to Home</a>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>