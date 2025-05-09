<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'restaurant') {
    header('Location: login.php');
    exit;
}

// Fetch restaurant_id from Restaurant table using person_id
$personId = $_SESSION['user_id'];
$restaurantQuery = "SELECT restaurant_id FROM Restaurant WHERE person_id = ?";
$restaurantStmt = $conn->prepare($restaurantQuery);
$restaurantStmt->execute([$personId]);
$restaurant = $restaurantStmt->fetch(PDO::FETCH_ASSOC);

if (!$restaurant) {
    echo "<p class='text-red-600 text-center'>Error: Restaurant not found.</p>";
    exit;
}
$restaurantId = $restaurant['restaurant_id'];

// Handle order actions (accept, reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)$_POST['order_id'];
    if (isset($_POST['accept_order'])) {
        $updateQuery = "UPDATE `Order` SET restaurant_status = 'accepted' WHERE order_id = ? AND restaurant_id = ? AND restaurant_status = 'pending'";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$orderId, $restaurantId]);
    } elseif (isset($_POST['reject_order'])) {
        $updateQuery = "UPDATE `Order` SET restaurant_status = 'rejected', order_status = 'canceled' WHERE order_id = ? AND restaurant_id = ? AND restaurant_status = 'pending'";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$orderId, $restaurantId]);
    }
    header('Location: restaurant-dashboard.php');
    exit;
}

// Fetch all orders for the restaurant
$orderQuery = "SELECT order_id, order_time, order_status, restaurant_status, payment_method, total_price, customer_id 
               FROM `Order` 
               WHERE restaurant_id = ? 
               ORDER BY order_time DESC";
$orderStmt = $conn->prepare($orderQuery);
$orderStmt->execute([$restaurantId]);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate metrics
$totalOrders = count($orders);
$pendingOrders = count(array_filter($orders, function($order) {
    return $order['restaurant_status'] === 'pending';
}));
$acceptedOrders = count(array_filter($orders, function($order) {
    return $order['restaurant_status'] === 'accepted';
}));
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Restaurant Dashboard - DhakaMeal</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <h3 class="text-lg font-semibold text-gray-900">Total Orders</h3>
            <p class="text-2xl font-bold text-pink-600"><?php echo $totalOrders; ?></p>
        </div>
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <h3 class="text-lg font-semibold text-gray-900">Pending Orders</h3>
            <p class="text-2xl font-bold text-pink-600"><?php echo $pendingOrders; ?></p>
        </div>
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <h3 class="text-lg font-semibold text-gray-900">Accepted Orders</h3>
            <p class="text-2xl font-bold text-pink-600"><?php echo $acceptedOrders; ?></p>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Orders</h2>
        <?php if (empty($orders)): ?>
            <p class="text-gray-600 text-center">No orders found.</p>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Order #<?php echo $order['order_id']; ?></h3>
                            <span class="text-sm text-gray-500"><?php echo date('d M Y, H:i', strtotime($order['order_time'])); ?></span>
                        </div>
                        <p class="text-sm text-gray-600">Customer ID: <?php echo htmlspecialchars($order['customer_id']); ?></p>
                        <p class="text-sm text-gray-600">Payment Method: <?php echo htmlspecialchars($order['payment_method']); ?></p>
                        <p class="text-sm text-gray-600">Order Status: <?php echo htmlspecialchars($order['order_status']); ?></p>
                        <p class="text-sm text-gray-600">Restaurant Status: <?php echo htmlspecialchars($order['restaurant_status']); ?></p>
                        <p class="text-lg font-bold text-pink-600 mt-2">Total: ৳<?php echo number_format($order['total_price'], 2); ?></p>
                        <?php
                        // Fetch items for this order
                        $itemQuery = "SELECT oi.quantity, oi.price, f.name 
                                      FROM OrderItem oi 
                                      JOIN Food f ON oi.food_id = f.food_id 
                                      WHERE oi.order_id = ?";
                        $itemStmt = $conn->prepare($itemQuery);
                        $itemStmt->execute([$order['order_id']]);
                        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="mt-4">
                            <h4 class="text-md font-medium text-gray-700">Items:</h4>
                            <ul class="list-disc pl-5 mt-2">
                                <?php foreach ($items as $item): ?>
                                    <li class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($item['name']); ?> 
                                        (৳<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?>)
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php if ($order['restaurant_status'] === 'pending'): ?>
                            <form method="POST" class="mt-4 flex space-x-4">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <button type="submit" name="accept_order" class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 transition duration-300">Accept Order</button>
                                <button type="submit" name="reject_order" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition duration-300">Reject Order</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="mt-6">
        <a href="index.php" class="inline-block bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700 transition duration-300">Back to Home</a>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>