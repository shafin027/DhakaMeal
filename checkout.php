<?php
include_once 'includes/header.php';
include_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: login.php");
    exit;
}

$customerQuery = "SELECT c.customer_id, c.address FROM Customer c 
                  JOIN Person p ON c.person_id = p.person_id 
                  WHERE p.person_id = ?";
$customerStmt = $conn->prepare($customerQuery);
$customerStmt->execute([$_SESSION['user_id']]);
$customer = $customerStmt->fetch();

$cart = json_decode($_SESSION['cart'] ?? '[]', true);
$total = array_sum(array_map(function($item) { return $item['price'] * $item['quantity']; }, $cart));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = $_POST['address'];
    $payment_method = $_POST['payment_method'];

    try {
        $conn->beginTransaction();

        // Insert order
        $orderQuery = "INSERT INTO `Order` (customer_id, order_time, order_status, restaurant_status, payment_method, payment_status, total_price) 
                       VALUES (?, NOW(), 'pending', 'pending', ?, ?, ?)";
        $orderStmt = $conn->prepare($orderQuery);
        $payment_status = $payment_method === 'online' ? 'completed' : 'pending';
        $orderStmt->execute([$customer['customer_id'], $payment_method, $payment_status, $total]);
        $orderId = $conn->lastInsertId();

        // Insert order items
        $stockIssues = [];
        foreach ($cart as $item) {
            $foodQuery = "SELECT stock_qty FROM Food WHERE food_id = ? FOR UPDATE";
            $foodStmt = $conn->prepare($foodQuery);
            $foodStmt->execute([$item['id']]);
            $food = $foodStmt->fetch();

            if ($food['stock_qty'] < $item['quantity']) {
                $stockIssues[] = $item['name'];
                continue;
            }

            $orderItemQuery = "INSERT INTO OrderItem (order_id, food_id, quantity, price) VALUES (?, ?, ?, ?)";
            $orderItemStmt = $conn->prepare($orderItemQuery);
            $orderItemStmt->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
        }

        if (!empty($stockIssues)) {
            $conn->rollBack();
            $error = "Insufficient stock for: " . implode(', ', $stockIssues);
        } else {
            $conn->commit();
            $_SESSION['cart'] = json_encode([]);
            header("Location: index.php?success=Order placed successfully!");
            exit;
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Order placement failed: " . $e->getMessage();
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6 text-gray-900">Checkout</h1>
    <?php if (isset($error)): ?>
        <p class="text-red-600 mb-4"><?php echo $error; ?></p>
    <?php endif; ?>
    <div class="bg-white rounded-lg shadow-md p-6 max-w-lg mx-auto">
        <form id="checkout-form" method="POST">
            <div class="mb-4">
                <label class="block text-gray-700">Delivery Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($customer['address'] ?? '') ?>" required 
                       class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Payment Method</label>
                <select name="payment_method" required class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                    <option value="cod">Cash on Delivery</option>
                    <option value="online">Online Payment (bKash)</option>
                </select>
            </div>
            <div class="mb-4">
                <p class="text-lg font-bold text-gray-900">Total: ৳<?= number_format($total, 2) ?></p>
            </div>
            <button type="submit" class="w-full bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700">Place Order</button>
            <div class="loading-spinner mt-4" id="loading-spinner"></div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('checkout-form');
    const submitButton = form.querySelector('button[type="submit"]');
    const loadingSpinner = document.getElementById('loading-spinner');

    form.addEventListener('submit', () => {
        submitButton.disabled = true;
        loadingSpinner.style.display = 'block';
    });
});
</script>

<?php include_once 'includes/footer.php'; ?>