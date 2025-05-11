<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';

// Ensure user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: login.php');
    exit;
}

$cartItems = $_SESSION['cart'] ?? [];
$restaurantId = !empty($cartItems) ? $cartItems[0]['restaurant_id'] : 0;
$customerId = $_SESSION['user_id'];

// Fetch restaurant details
$restaurant = null;
if ($restaurantId) {
    try {
        $query = "SELECT name FROM Restaurant WHERE restaurant_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$restaurantId]);
        $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<p class='text-red-600'>Error fetching restaurant: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Handle cart updates (increase, decrease, remove) - Fallback for non-JS scenarios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        $foodId = $_POST['food_id'] ?? null;
        $action = $_POST['action'] ?? '';
        if ($foodId && $action) {
            foreach ($_SESSION['cart'] as $index => &$item) {
                if ($item['food_id'] == $foodId) {
                    if ($action === 'increase') {
                        $item['quantity'] += 1;
                    } elseif ($action === 'decrease' && $item['quantity'] > 1) {
                        $item['quantity'] -= 1;
                    } elseif ($action === 'remove') {
                        unset($_SESSION['cart'][$index]);
                    }
                    break;
                }
            }
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
            if (empty($_SESSION['cart'])) {
                unset($_SESSION['cart']);
            }
            header('Location: cart.php');
            exit;
        }
    } elseif (isset($_POST['place_order'])) {
        $paymentMethod = $_POST['payment_method'] ?? 'cod';
        $totalPrice = 0;
        foreach ($cartItems as $item) {
            $totalPrice += $item['price'] * $item['quantity'];
        }

        try {
            $customerQuery = "SELECT customer_id FROM Customer WHERE person_id = ?";
            $customerStmt = $conn->prepare($customerQuery);
            $customerStmt->execute([$_SESSION['user_id']]);
            $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
            if (!$customer) {
                echo "<p class='text-red-600'>Customer not found.</p>";
                exit;
            }
            $customerId = $customer['customer_id'];
        } catch (PDOException $e) {
            echo "<p class='text-red-600'>Error fetching customer: " . htmlspecialchars($e->getMessage()) . "</p>";
            exit;
        }

        try {
            $orderQuery = "INSERT INTO `Order` (customer_id, restaurant_id, delivery_person_id, order_time, order_status, restaurant_status, payment_method, payment_status, total_price) 
                           VALUES (?, ?, NULL, NOW(), 'pending', 'pending', ?, 'pending', ?)";
            $orderStmt = $conn->prepare($orderQuery);
            $orderStmt->execute([$customerId, $restaurantId, $paymentMethod, $totalPrice]);
            $orderId = $conn->lastInsertId();

            foreach ($cartItems as $item) {
                $orderItemQuery = "INSERT INTO OrderItem (order_id, food_id, quantity, price) VALUES (?, ?, ?, ?)";
                $orderItemStmt = $conn->prepare($orderItemQuery);
                $orderItemStmt->execute([$orderId, $item['food_id'], $item['quantity'], $item['price']]);
            }

            unset($_SESSION['cart']);
            header("Location: order-details.php?order_id=$orderId");
            exit;
        } catch (PDOException $e) {
            echo "<p class='text-red-600'>Error placing order: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// Calculate cart total
$totalPrice = 0;
foreach ($cartItems as $item) {
    $totalPrice += $item['price'] * $item['quantity'];
}
?>

<!-- Cart Section -->
<section class="container mx-auto px-4 py-16">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Your Cart</h1>
    <?php if (empty($cartItems)): ?>
        <p class="text-gray-600 text-center">Your cart is empty. <a href="index.php" class="text-pink-600 hover:underline">Explore restaurants</a> to add items!</p>
    <?php else: ?>
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Order from <?php echo htmlspecialchars($restaurant['name'] ?? 'Unknown Restaurant'); ?></h2>
            <div class="space-y-4">
                <?php foreach ($cartItems as $index => $item): ?>
                    <div class="flex items-center justify-between border-b pb-4 cart-item" data-food-id="<?php echo $item['food_id']; ?>">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="text-gray-600">
                                <?php if ($item['discount_percentage'] > 0): ?>
                                    ৳<?php echo number_format($item['price'], 2); ?> (After <?php echo number_format($item['discount_percentage'], 2); ?>% off) 
                                    <span class="text-sm text-gray-500 line-through">৳<?php echo number_format($item['original_price'], 2); ?></span>
                                <?php else: ?>
                                    ৳<?php echo number_format($item['price'], 2); ?>
                                <?php endif; ?>
                                x <span class="item-quantity"><?php echo $item['quantity']; ?></span> = <span class="item-total">৳<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <!-- Decrease Quantity -->
                            <form method="POST" class="update-form decrease-form">
                                <input type="hidden" name="food_id" value="<?php echo $item['food_id']; ?>">
                                <input type="hidden" name="action" value="decrease">
                                <button type="submit" name="update_cart" class="bg-gray-200 text-gray-700 px-2 py-1 rounded hover:bg-gray-300">-</button>
                            </form>
                            <!-- Quantity Display -->
                            <span class="px-4 item-quantity-display"><?php echo $item['quantity']; ?></span>
                            <!-- Increase Quantity -->
                            <form method="POST" class="update-form increase-form">
                                <input type="hidden" name="food_id" value="<?php echo $item['food_id']; ?>">
                                <input type="hidden" name="action" value="increase">
                                <button type="submit" name="update_cart" class="bg-gray-200 text-gray-700 px-2 py-1 rounded hover:bg-gray-300">+</button>
                            </form>
                            <!-- Remove Item -->
                            <form method="POST" class="update-form remove-form">
                                <input type="hidden" name="food_id" value="<?php echo $item['food_id']; ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" name="update_cart" class="text-red-600 hover:text-red-800">Remove</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-6 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Total: <span id="cart-total">৳<?php echo number_format($totalPrice, 2); ?></span></h3>
            </div>
        </div>

        <!-- Checkout Form -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Checkout</h2>
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Payment Method</label>
                    <select name="payment_method" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600">
                        <option value="cod">Cash on Delivery</option>
                        <option value="online">Online Payment</option>
                    </select>
                </div>
                <button type="submit" name="place_order" class="w-full bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 transition duration-300">Place Order</button>
            </form>
        </div>
    <?php endif; ?>
</section>

<script>
// AJAX for cart updates
document.querySelectorAll('.update-form').forEach(form => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const foodId = formData.get('food_id');
        const action = formData.get('action');

        console.log('Submitting form for food_id:', foodId, 'with action:', action);

        try {
            const response = await fetch('update-cart.php', {
                method: 'POST',
                body: formData
            });

            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Response data:', data);

            if (data.success) {
                const cartItem = form.closest('.cart-item');
                if (action === 'remove') {
                    cartItem.remove();
                    if (!document.querySelector('.cart-item')) {
                        window.location.reload(); // Reload to show "Cart is empty"
                    }
                } else {
                    // Update quantity and total
                    const quantityDisplay = cartItem.querySelector('.item-quantity-display');
                    const quantityElement = cartItem.querySelector('.item-quantity');
                    const totalElement = cartItem.querySelector('.item-total');

                    console.log('Updating quantity to:', data.quantity, 'and item total to:', data.itemTotal);
                    quantityDisplay.textContent = data.quantity;
                    quantityElement.textContent = data.quantity;
                    totalElement.textContent = '৳' + Number(data.itemTotal).toFixed(2);
                }

                // Update cart total and header cart count
                console.log('Updating cart total to:', data.cartTotal, 'and cart count to:', data.cartCount);
                document.getElementById('cart-total').textContent = '৳' + Number(data.cartTotal).toFixed(2);
                const cartCountElement = document.getElementById('cart-count');
                if (cartCountElement) {
                    cartCountElement.textContent = data.cartCount;
                    cartCountElement.style.display = data.cartCount > 0 ? 'inline-block' : 'none';
                }
            } else {
                console.error('Update failed:', data.message);
                alert('Error updating cart: ' + (data.message || 'Unknown error'));
                // Fallback to page reload
                form.submit();
            }
        } catch (error) {
            console.error('AJAX error:', error);
            alert('Error updating cart: ' + error.message);
            // Fallback to page reload
            form.submit();
        }
    });
});
</script>

<?php include_once 'includes/footer.php'; ?>