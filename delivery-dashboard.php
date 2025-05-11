<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'delivery_person') {
    header('Location: login.php');
    exit;
}

// Fetch delivery_id from DeliveryPerson table using person_id
$personId = $_SESSION['user_id'];
$deliveryQuery = "SELECT delivery_id FROM DeliveryPerson WHERE person_id = ?";
$deliveryStmt = $conn->prepare($deliveryQuery);
$deliveryStmt->execute([$personId]);
$delivery = $deliveryStmt->fetch(PDO::FETCH_ASSOC);

if (!$delivery) {
    echo "<p class='text-red-600 text-center'>Error: Delivery person not found.</p>";
    exit;
}
$deliveryPersonId = $delivery['delivery_id'];

// Handle order actions (accept, reject, update status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)$_POST['order_id'];
    if (isset($_POST['accept_delivery'])) {
        $updateQuery = "UPDATE `Order` SET delivery_person_id = ?, order_status = 'picked_up' WHERE order_id = ? AND delivery_person_id IS NULL AND restaurant_status = 'accepted' AND order_status IN ('pending', 'accepted')";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$deliveryPersonId, $orderId]);
        if ($updateStmt->rowCount() > 0) {
            echo "<p class='text-green-600 text-center'>Order accepted successfully!</p>";
        } else {
            echo "<p class='text-red-600 text-center'>Failed to accept order. It may already be taken or not in an acceptable state.</p>";
        }
    } elseif (isset($_POST['reject_delivery'])) {
        $updateQuery = "UPDATE `Order` SET order_status = 'canceled', delivery_person_id = NULL WHERE order_id = ? AND delivery_person_id IS NULL AND restaurant_status = 'accepted' AND order_status IN ('pending', 'accepted')";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$orderId]);
        if ($updateStmt->rowCount() > 0) {
            echo "<p class='text-green-600 text-center'>Order rejected successfully!</p>";
        } else {
            echo "<p class='text-red-600 text-center'>Failed to reject order. It may already be assigned.</p>";
        }
    } elseif (isset($_POST['update_status'])) {
        $newStatus = $_POST['new_status'];
        $allowedTransitions = [
            'picked_up' => ['out_for_delivery', 'delivered'],
            'out_for_delivery' => ['delivered']
        ];
        $currentStatusQuery = "SELECT order_status FROM `Order` WHERE order_id = ? AND delivery_person_id = ?";
        $currentStmt = $conn->prepare($currentStatusQuery);
        $currentStmt->execute([$orderId, $deliveryPersonId]);
        $currentStatus = $currentStmt->fetchColumn();

        if ($currentStatus && isset($allowedTransitions[$currentStatus]) && in_array($newStatus, $allowedTransitions[$currentStatus])) {
            $updateQuery = "UPDATE `Order` SET order_status = ? WHERE order_id = ? AND delivery_person_id = ? AND restaurant_status = 'accepted' AND order_status = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $result = $updateStmt->execute([$newStatus, $orderId, $deliveryPersonId, $currentStatus]);
            if ($result && $updateStmt->rowCount() > 0) {
                echo "<p class='text-green-600 text-center'>Status updated to '$newStatus' successfully!</p>";
            } else {
                error_log("Update failed for order $orderId: " . print_r($updateStmt->errorInfo(), true));
                echo "<p class='text-red-600 text-center'>Failed to update status. Check current status or permissions. See server logs for details.</p>";
            }
        } else {
            echo "<p class='text-red-600 text-center'>Invalid status transition or order not found.</p>";
        }
    }
    header('Location: delivery-dashboard.php');
    exit;
}

// Fetch all orders for the delivery person, including unassigned accepted orders, with restaurant rating
$orderQuery = "SELECT o.order_id, o.order_time, o.order_status, o.restaurant_status, o.payment_method, o.total_price, 
                      o.delivery_person_id, o.restaurant_id, p.name AS customer_name, r.name AS restaurant_name,
                      COALESCE(AVG(fr.rating), 0) AS restaurant_avg_rating, COUNT(fr.rating) AS restaurant_review_count
               FROM `Order` o
               JOIN Customer c ON o.customer_id = c.customer_id
               JOIN Person p ON c.person_id = p.person_id
               JOIN Restaurant r ON o.restaurant_id = r.restaurant_id
               LEFT JOIN Food f ON f.restaurant_id = r.restaurant_id
               LEFT JOIN FoodReview fr ON f.food_id = fr.food_id
               WHERE (o.delivery_person_id = ? OR (o.delivery_person_id IS NULL AND o.restaurant_status = 'accepted' AND o.order_status IN ('pending', 'accepted')))
               GROUP BY o.order_id, o.order_time, o.order_status, o.restaurant_status, o.payment_method, o.total_price, 
                        o.delivery_person_id, o.restaurant_id, p.name, r.name
               ORDER BY o.order_time DESC";
$orderStmt = $conn->prepare($orderQuery);
$orderStmt->execute([$deliveryPersonId]);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Delivery Dashboard</h1>
    
    <div class="bg-white shadow rounded-lg p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Assigned Orders</h2>
        <?php if (empty($orders)): ?>
            <p class="text-gray-600 text-center">No orders assigned or available for acceptance.</p>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Order #<?php echo $order['order_id']; ?></h3>
                            <span class="text-sm text-gray-500"><?php echo date('d M Y, H:i', strtotime($order['order_time'])); ?></span>
                        </div>
                        <p class="text-sm text-gray-600">Customer: <?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p class="text-sm text-gray-600">
                            Restaurant: <?php echo htmlspecialchars($order['restaurant_name']); ?>
                            <?php if ($order['restaurant_review_count'] > 0): ?>
                                - Avg Rating: <?php echo number_format($order['restaurant_avg_rating'], 1); ?> (<?php echo $order['restaurant_review_count']; ?> reviews)
                            <?php else: ?>
                                - No ratings yet
                            <?php endif; ?>
                        </p>
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
                        // Fetch items for this order with average rating
                        $itemQuery = "SELECT oi.quantity, oi.price, f.name, f.food_id,
                                             COALESCE(AVG(fr.rating), 0) AS avg_rating, COUNT(fr.rating) AS review_count
                                      FROM OrderItem oi
                                      JOIN Food f ON oi.food_id = f.food_id
                                      LEFT JOIN FoodReview fr ON f.food_id = fr.food_id
                                      WHERE oi.order_id = ?
                                      GROUP BY oi.order_item_id, oi.quantity, oi.price, f.name, f.food_id";
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
                                        <?php if ($item['review_count'] > 0): ?>
                                            - Avg Rating: <?php echo number_format($item['avg_rating'], 1); ?> (<?php echo $item['review_count']; ?> reviews)
                                        <?php else: ?>
                                            - No ratings yet
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php if ($order['delivery_person_id'] === null && $order['restaurant_status'] === 'accepted' && in_array($order['order_status'], ['pending', 'accepted'])): ?>
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