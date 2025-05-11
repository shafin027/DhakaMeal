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

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $orderId = (int)$_POST['order_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    if ($rating < 1 || $rating > 5) {
        echo "<p class='text-red-600 text-center'>Rating must be between 1 and 5.</p>";
    } else {
        $reviewQuery = "INSERT INTO OrderReview (order_id, customer_id, rating, comment) VALUES (?, ?, ?, ?)";
        $reviewStmt = $conn->prepare($reviewQuery);
        $reviewStmt->execute([$orderId, $customerId, $rating, $comment]);
        echo "<p class='text-green-600 text-center'>Review submitted successfully!</p>";
    }
}

// Fetch all orders for the customer
$orderQuery = "SELECT order_id, order_time, order_status, restaurant_status, payment_method, total_price 
               FROM `Order` 
               WHERE customer_id = ? 
               ORDER BY order_time DESC";
$orderStmt = $conn->prepare($orderQuery);
$orderStmt->execute([$customerId]);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Your Orders</h1>
    
    <div class="bg-white shadow rounded-lg p-6">
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

                        // Fetch reviews for this order
                        $reviewQuery = "SELECT rating, comment, review_time 
                                        FROM OrderReview 
                                        WHERE order_id = ?";
                        $reviewStmt = $conn->prepare($reviewQuery);
                        $reviewStmt->execute([$order['order_id']]);
                        $reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="mt-4">
                            <h4 class="text-md font-medium text-gray-700">Items:</h4>
                            <ul class="list-disc pl-5 mt-2">
                                <?php foreach ($items as $index => $item): ?>
                                    <li class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($item['name']); ?> 
                                        (৳<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?>)
                                        <?php if (!empty($reviews)): ?>
                                            <button onclick="toggleReview(<?php echo $order['order_id'] . $index; ?>)" class="text-pink-600 hover:underline ml-2">Reviews (<?php echo count($reviews); ?>)</button>
                                            <div id="review-<?php echo $order['order_id'] . $index; ?>" class="hidden mt-2 pl-4">
                                                <?php foreach ($reviews as $review): ?>
                                                    <div class="text-sm text-gray-600">
                                                        <p>Rating: <?php echo htmlspecialchars($review['rating']); ?>/5</p>
                                                        <p>Comment: <?php echo htmlspecialchars($review['comment'] ?: 'No comment'); ?></p>
                                                        <p class="text-xs text-gray-500">Reviewed on: <?php echo date('d M Y, H:i', strtotime($review['review_time'])); ?></p>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php
                        // Check if a review already exists
                        $reviewQuery = "SELECT * FROM OrderReview WHERE order_id = ? AND customer_id = ?";
                        $reviewStmt = $conn->prepare($reviewQuery);
                        $reviewStmt->execute([$order['order_id'], $customerId]);
                        $review = $reviewStmt->fetch(PDO::FETCH_ASSOC);
                        ?>
                        <?php if ($order['order_status'] === 'delivered' && !$review): ?>
                            <div class="mt-4">
                                <h4 class="text-md font-medium text-gray-700">Leave a Review</h4>
                                <form method="POST" class="mt-2">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <div class="mb-2">
                                        <label class="block text-gray-700">Rating (1-5):</label>
                                        <input type="number" name="rating" min="1" max="5" required class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                                    </div>
                                    <div class="mb-2">
                                        <label class="block text-gray-700">Comment:</label>
                                        <textarea name="comment" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" rows="3"></textarea>
                                    </div>
                                    <button type="submit" name="submit_review" class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 transition duration-300">Submit Review</button>
                                </form>
                            </div>
                        <?php elseif ($review): ?>
                            <div class="mt-4">
                                <h4 class="text-md font-medium text-gray-700">Your Review</h4>
                                <p class="text-sm text-gray-600">Rating: <?php echo htmlspecialchars($review['rating']); ?>/5</p>
                                <p class="text-sm text-gray-600">Comment: <?php echo htmlspecialchars($review['comment'] ?: 'No comment'); ?></p>
                                <p class="text-sm text-gray-500">Reviewed on: <?php echo date('d M Y, H:i', strtotime($review['review_time'])); ?></p>
                            </div>
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

<script>
function toggleReview(id) {
    const reviewDiv = document.getElementById('review-' + id);
    reviewDiv.classList.toggle('hidden');
}
</script>

<?php include_once 'includes/footer.php'; ?>