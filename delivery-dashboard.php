<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'delivery_person') {
    header('Location: login.php');
    exit;
}

// Fetch delivery_person_id from DeliveryPerson table using person_id
$personId = $_SESSION['user_id'];
$deliveryQuery = "SELECT delivery_person_id FROM DeliveryPerson WHERE person_id = ?";
$deliveryStmt = $conn->prepare($deliveryQuery);
$deliveryStmt->execute([$personId]);
$delivery = $deliveryStmt->fetch(PDO::FETCH_ASSOC);

if (!$delivery) {
    echo "<p class='text-red-600 text-center'>Error: Delivery person not found.</p>";
    exit;
}
$deliveryPersonId = $delivery['delivery_person_id'];

// Handle order actions (accept, reject, update status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)$_POST['order_id'];
    if (isset($_POST['accept_delivery'])) {
        $updateQuery = "UPDATE `Order` SET order_status = 'picked_up' WHERE order_id = ? AND delivery_person_id = ? AND restaurant_status = 'accepted' AND order_status = 'pending'";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$orderId, $deliveryPersonId]);
    } elseif (isset($_POST['reject_delivery'])) {
        $updateQuery = "UPDATE `Order` SET order_status = 'canceled', delivery_person_id = NULL WHERE order_id = ? AND delivery_person_id = ? AND restaurant_status = 'accepted' AND order_status = 'pending'";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$orderId, $deliveryPersonId]);
    } elseif (isset($_POST['update_status'])) {
        $newStatus = $_POST['new_status'];
        $allowedStatuses = ['picked_up', 'out_for_delivery', 'delivered'];
        if (in_array($newStatus, $allowedStatuses)) {
            $updateQuery = "UPDATE `Order` SET order_status = ? WHERE order_id = ? AND delivery_person_id = ? AND restaurant_status = 'accepted' AND order_status != 'canceled'";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->execute([$newStatus, $orderId, $deliveryPersonId]);
        }
    }
    header('Location: delivery-dashboard.php');
    exit;
}

// Fetch all orders for the delivery person
$orderQuery = "SELECT order_id, order_time, order_status, restaurant_status, payment_method, total_price, customer_id 
               FROM `Order` 
               WHERE delivery_person_id = ? AND (restaurant_status = 'accepted' OR order_status != 'pending')
               ORDER BY order_time DESC";
$orderStmt = $conn->prepare($orderQuery);
$orderStmt->execute([$deliveryPersonId]);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Delivery Dashboard</h1>
    
    <div class="bg-white shadow rounded-lg p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Assigned Orders</h2>
        <?php if (empty($orders)): ?>
            <p class="text-gray-600 text-center">No orders assigned.</p>
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
                        <?php
                        // Style order_status based on value
                        $statusClass = '';
                        switch (strtolower($order['order_status'])) {
                            case 'delivered':
                                $statusClass = 'text-green-600 bg-green-100';
                                break;
                            case 'canceled':
                                $statusClass = 'text-red-600 bg-red-100';
                                break;
                            case 'pending':
                                $statusClass = 'text-yellow-600 bg-yellow-100';
                                break;
                            case 'picked_up':
                            case 'out_for_delivery':
                                $statusClass = 'text-blue-600 bg-blue-100';
                                break;
                            default:
                                $statusClass = 'text-gray-600 bg-gray-100';
                        }
                        ?>
                        <p class="text-sm font-medium px-3 py-1 rounded-full inline-block <?php echo $statusClass; ?>">
                            Order Status: <?php echo htmlspecialchars($order['order_status']); ?>
                        </p>
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
                        <?php if ($order['restaurant_status'] === 'accepted' && $order['order_status'] === 'pending'): ?>
                            <form method="POST" class="mt-4 flex space-x-4">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <button type="submit" name="accept_delivery" class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 transition duration-300">Accept Delivery</button>
                                <button type="submit" name="reject_delivery" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition duration-300">Reject Delivery</button>
                            </form>
                        <?php elseif (in_array($order['order_status'], ['picked_up', 'out_for_delivery'])): ?>
                            <form method="POST" class="mt-4 flex space-x-4">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <select name="new_status" class="px-4 py-2 border rounded-md">
                                    <?php if ($order['order_status'] === 'picked_up'): ?>
                                        <option value="out_for_delivery">Out for Delivery</option>
                                    <?php endif; ?>
                                    <option value="delivered">Delivered</option>
                                </select>
                                <button type="submit" name="update_status" class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 transition duration-300">Update Status</button>
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