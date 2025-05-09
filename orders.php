<?php
include_once 'config/database.php';
include_once 'includes/header.php';

// Check if user is logged in and is a restaurant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'restaurant') {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Get restaurant info
$restaurantQuery = "SELECT r.restaurant_id, r.name FROM Restaurant r 
                   JOIN Person p ON r.person_id = p.person_id 
                   WHERE p.person_id = ?";
$restaurantStmt = $conn->prepare($restaurantQuery);
$restaurantStmt->execute([$userId]);
$restaurant = $restaurantStmt->fetch();

if (!$restaurant) {
    header("Location: index.php");
    exit;
}

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];
    $updateQuery = "UPDATE `Order` SET order_status = ? WHERE order_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->execute([$status, $orderId]);
}

// Get orders
$ordersQuery = "SELECT DISTINCT o.order_id, o.order_time, o.order_status, o.total_price, 
                c.customer_id, p.name as customer_name 
                FROM `Order` o 
                JOIN OrderItem oi ON o.order_id = oi.order_id 
                JOIN Food f ON oi.food_id = f.food_id 
                JOIN Customer c ON o.customer_id = c.customer_id 
                JOIN Person p ON c.person_id = p.person_id 
                WHERE f.restaurant_id = ? 
                ORDER BY o.order_time DESC";
$ordersStmt = $conn->prepare($ordersQuery);
$ordersStmt->execute([$restaurant['restaurant_id']]);
$orders = $ordersStmt->fetchAll();
?>

<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <div class="hidden md:flex md:flex-shrink-0">
        <div class="flex flex-col w-64 bg-white border-r">
            <div class="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto">
                <div class="flex items-center flex-shrink-0 px-4 mb-5">
                    <span class="text-2xl font-bold text-pink-600">DhakaMeal</span>
                </div>
                <nav class="flex-1 px-2 space-y-1 bg-white">
                    <a href="dashboard.php" class="text-gray-600 hover:bg-gray-100 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-tachometer-alt mr-3 text-gray-400"></i> Dashboard
                    </a>
                    <a href="orders.php" class="bg-pink-100 text-pink-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-shopping-bag mr-3 text-pink-500"></i> Orders
                    </a>
                    <a href="menu.php" class="text-gray-600 hover:bg-gray-100 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-utensils mr-3 text-gray-400"></i> Menu Management
                    </a>
                    <a href="reviews.php" class="text-gray-600 hover:bg-gray-100 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-star mr-3 text-gray-400"></i> Reviews
                    </a>
                </nav>
                <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="bg-pink-600 rounded-full h-8 w-8 flex items-center justify-center text-white text-sm font-medium">
                            <?= strtoupper(substr($restaurant['name'], 0, 1)) ?>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($restaurant['name']) ?></p>
                            <a href="logout.php" class="text-xs font-medium text-gray-500 hover:text-gray-700">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="flex flex-col flex-1 overflow-hidden">
        <!-- Mobile top bar -->
        <div class="md:hidden flex items-center justify-between bg-white border-b px-4 py-2">
            <div class="flex items-center">
                <button id="mobile-menu-button" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="text-xl font-bold text-pink-600 ml-3">DhakaMeal</span>
            </div>
        </div>
        <!-- Mobile menu -->
        <div id="mobile-menu" class="md:hidden bg-white absolute inset-x-0 top-12 z-10 hidden">
            <div class="pt-2 pb-3 space-y-1">
                <a href="dashboard.php" class="text-gray-600 hover:bg-gray-100 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium">
                    Dashboard
                </a>
                <a href="orders.php" class="bg-pink-100 text-pink-700 block pl-3 pr-4 py-2 border-l-4 border-pink-500 text-base font-medium">
                    Orders
                </a>
                <a href="menu.php" class="text-gray-600 hover:bg-gray-100 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium">
                    Menu Management
                </a>
                <a href="reviews.php" class="text-gray-600 hover:bg-gray-100 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium">
                    Reviews
                </a>
                <a href="logout.php" class="text-gray-600 hover:bg-gray-100 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium">
                    Logout
                </a>
            </div>
        </div>

        <!-- Page content -->
        <main class="flex-1 overflow-y-auto bg-gray-100">
            <div class="py-6 px-4 sm:px-6 lg:px-8">
                <h1 class="text-2xl font-bold mb-6 text-gray-900">Orders</h1>
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Order List</h2>
                    </div>
                    <div class="px-6 py-4">
                        <?php if (count($orders) === 0): ?>
                            <p class="text-center text-gray-500 py-4">No orders yet</p>
                        <?php else: ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                #<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($order['customer_name']) ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= date('M d, h:i A', strtotime($order['order_time'])) ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                                    <?= $order['order_status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                        ($order['order_status'] === 'delivered' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') ?>">
                                                    <?= ucfirst($order['order_status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">৳<?= number_format($order['total_price'], 2) ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <form method="POST">
                                                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                    <select name="status" onchange="this.form.submit()" class="px-2 py-1 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                                                        <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                        <option value="canceled" <?= $order['order_status'] === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                                                    </select>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
    }
});
</script>

<?php include_once 'includes/footer.php'; ?>