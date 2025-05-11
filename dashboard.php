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
$restaurantQuery = "SELECT r.*, p.name as owner_name FROM Restaurant r 
                   JOIN Person p ON r.person_id = p.person_id 
                   WHERE p.person_id = ?";
$restaurantStmt = $conn->prepare($restaurantQuery);
$restaurantStmt->execute([$userId]);
$restaurant = $restaurantStmt->fetch();

if (!$restaurant) {
    header("Location: index.php");
    exit;
}

// Get total orders
$totalOrdersQuery = "SELECT COUNT(*) FROM `Order` o 
                    JOIN OrderItem oi ON o.order_id = oi.order_id 
                    JOIN Food f ON oi.food_id = f.food_id 
                    WHERE f.restaurant_id = ?";
$totalOrdersStmt = $conn->prepare($totalOrdersQuery);
$totalOrdersStmt->execute([$restaurant['restaurant_id']]);
$totalOrders = $totalOrdersStmt->fetchColumn();

// Get total revenue
$totalRevenueQuery = "SELECT SUM(oi.price * oi.quantity) FROM OrderItem oi 
                      JOIN Food f ON oi.food_id = f.food_id 
                      JOIN `Order` o ON oi.order_id = o.order_id 
                      WHERE f.restaurant_id = ? AND o.order_status != 'canceled'";
$totalRevenueStmt = $conn->prepare($totalRevenueQuery);
$totalRevenueStmt->execute([$restaurant['restaurant_id']]);
$totalRevenue = $totalRevenueStmt->fetchColumn() ?: 0;

// Get total food items
$totalFoodQuery = "SELECT COUNT(*) FROM Food WHERE restaurant_id = ?";
$totalFoodStmt = $conn->prepare($totalFoodQuery);
$totalFoodStmt->execute([$restaurant['restaurant_id']]);
$totalFood = $totalFoodStmt->fetchColumn();

// Get average rating
$ratingQuery = "SELECT AVG(r.rating) FROM Review r 
                JOIN `Order` o ON r.order_id = o.order_id 
                JOIN OrderItem oi ON o.order_id = oi.order_id 
                JOIN Food f ON oi.food_id = f.food_id 
                WHERE f.restaurant_id = ?";
$ratingStmt = $conn->prepare($ratingQuery);
$ratingStmt->execute([$restaurant['restaurant_id']]);
$avgRating = $ratingStmt->fetchColumn() ?: 0;

// Get recent orders
$recentOrdersQuery = "SELECT DISTINCT o.order_id, o.order_time, o.order_status, o.total_price, 
                     c.customer_id, p.name as customer_name 
                     FROM `Order` o 
                     JOIN OrderItem oi ON o.order_id = oi.order_id 
                     JOIN Food f ON oi.food_id = f.food_id 
                     JOIN Customer c ON o.customer_id = c.customer_id 
                     JOIN Person p ON c.person_id = p.person_id 
                     WHERE f.restaurant_id = ? 
                     ORDER BY o.order_time DESC 
                     LIMIT 5";
$recentOrdersStmt = $conn->prepare($recentOrdersQuery);
$recentOrdersStmt->execute([$restaurant['restaurant_id']]);
$recentOrders = $recentOrdersStmt->fetchAll();

// Get popular food items
$popularFoodQuery = "SELECT f.food_id, f.name, f.price, f.image_url, 
                    COUNT(oi.order_item_id) as order_count,
                    (SELECT GROUP_CONCAT(c.name SEPARATOR ', ') 
                     FROM FoodCategory fc 
                     JOIN Category c ON fc.category_id = c.category_id 
                     WHERE fc.food_id = f.food_id) as categories
                    FROM Food f 
                    LEFT JOIN OrderItem oi ON f.food_id = oi.food_id 
                    WHERE f.restaurant_id = ? 
                    GROUP BY f.food_id 
                    ORDER BY order_count DESC, f.featured DESC 
                    LIMIT 5";
$popularFoodStmt = $conn->prepare($popularFoodQuery);
$popularFoodStmt->execute([$restaurant['restaurant_id']]);
$popularFood = $popularFoodStmt->fetchAll();

// Get recent reviews
$reviewsQuery = "SELECT r.review_id, r.rating, r.comment, r.review_time, 
                p.name as customer_name, f.name as food_name 
                FROM Review r 
                JOIN `Order` o ON r.order_id = o.order_id 
                JOIN OrderItem oi ON o.order_id = oi.order_id 
                JOIN Food f ON oi.food_id = f.food_id 
                JOIN Customer c ON r.customer_id = c.customer_id 
                JOIN Person p ON c.person_id = p.person_id 
                WHERE f.restaurant_id = ? 
                ORDER BY r.review_time DESC 
                LIMIT 3";
$reviewsStmt = $conn->prepare($reviewsQuery);
$reviewsStmt->execute([$restaurant['restaurant_id']]);
$reviews = $reviewsStmt->fetchAll();
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
                    <a href="dashboard.php" class="bg-pink-100 text-pink-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-tachometer-alt mr-3 text-pink-500"></i> Dashboard
                    </a>
                    <a href="orders.php" class="text-gray-600 hover:bg-gray-100 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-shopping-bag mr-3 text-gray-400"></i> Orders
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
                <a href="dashboard.php" class="bg-pink-100 text-pink-700 block pl-3 pr-4 py-2 border-l-4 border-pink-500 text-base font-medium">
                    Dashboard
                </a>
                <a href="orders.php" class="text-gray-600 hover:bg-gray-100 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium">
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
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                    <p class="text-sm text-gray-500">Welcome back, <span class="font-medium text-gray-700"><?= htmlspecialchars($restaurant['owner_name']) ?></span></p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white shadow rounded-lg p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-pink-100 rounded-md p-3">
                                <i class="fas fa-shopping-bag text-pink-600 text-xl"></i>
                            </div>
                            <div class="ml-5">
                                <dt class="text-sm font-medium text-gray-500">Total Orders</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $totalOrders ?></dd>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3 mt-3">
                            <a href="orders.php" class="text-sm text-pink-600 hover:text-pink-500">View all orders</a>
                        </div>
                    </div>
                    <div class="bg-white shadow rounded-lg p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-5">
                                <dt class="text-sm font-medium text-gray-500">Total Revenue</dt>
                                <dd class="text-lg font-medium text-gray-900">৳<?= number_format($totalRevenue, 2) ?></dd>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3 mt-3">
                            <a href="#" class="text-sm text-pink-600 hover:text-pink-500">View financial reports</a>
                        </div>
                    </div>
                    <div class="bg-white shadow rounded-lg p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                                <i class="fas fa-utensils text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-5">
                                <dt class="text-sm font-medium text-gray-500">Food Items</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $totalFood ?></dd>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3 mt-3">
                            <a href="menu.php" class="text-sm text-pink-600 hover:text-pink-500">Manage menu</a>
                        </div>
                    </div>
                    <div class="bg-white shadow rounded-lg p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                                <i class="fas fa-star text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-5">
                                <dt class="text-sm font-medium text-gray-500">Average Rating</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= number_format($avgRating, 1) ?>/5.0</dd>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3 mt-3">
                            <a href="reviews.php" class="text-sm text-pink-600 hover:text-pink-500">View all reviews</a>
                        </div>
                    </div>
                </div>

                <!-- Main sections -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Recent Orders -->
                    <div class="lg:col-span-2 bg-white shadow rounded-lg">
                        <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-lg font-medium text-gray-900">Recent Orders</h2>
                            <a href="orders.php" class="text-sm text-pink-600 hover:text-pink-500">View all</a>
                        </div>
                        <div class="px-6 py-4">
                            <?php if (count($recentOrders) === 0): ?>
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
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($recentOrders as $order): ?>
                                            <tr>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <a href="orders.php?id=<?= $order['order_id'] ?>" class="text-pink-600 hover:text-pink-700">
                                                        #<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?>
                                                    </a>
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
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Reviews -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-lg font-medium text-gray-900">Recent Reviews</h2>
                            <a href="reviews.php" class="text-sm text-pink-600 hover:text-pink-500">View all</a>
                        </div>
                        <div class="px-6 py-4">
                            <?php if (count($reviews) === 0): ?>
                                <p class="text-center text-gray-500 py-4">No reviews yet</p>
                            <?php else: ?>
                                <div class="space-y-6">
                                    <?php foreach ($reviews as $review): ?>
                                        <div class="border-b pb-4 last:border-b-0 last:pb-0">
                                            <div class="flex items-center mb-2">
                                                <div class="flex text-yellow-400">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?= $i <= $review['rating'] ? '' : 'far' ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <div class="ml-2 text-sm text-gray-500"><?= date('M d, Y', strtotime($review['review_time'])) ?></div>
                                            </div>
                                            <p class="text-sm text-gray-600"><?= htmlspecialchars($review['comment']) ?></p>
                                            <div class="mt-2 flex justify-between">
                                                <span class="text-sm text-gray-500">By: <?= htmlspecialchars($review['customer_name']) ?></span>
                                                <span class="text-sm text-gray-500">For: <?= htmlspecialchars($review['food_name']) ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Popular Food Items -->
                <div class="mt-8 bg-white shadow rounded-lg">
                    <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-lg font-medium text-gray-900">Popular Fast Food Items</h2>
                        <a href="menu.php" class="text-sm text-pink-600 hover:text-pink-500">Manage menu</a>
                    </div>
                    <div class="px-6 py-4">
                        <?php if (count($popularFood) === 0): ?>
                            <p class="text-center text-gray-500 py-4">No food items available</p>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($popularFood as $food): ?>
                                    <div class="flex border rounded-lg overflow-hidden">
                                        <div class="w-1/3 h-24 bg-gray-200">
                                            <img src="<?= htmlspecialchars($food['image_url'] ?? 'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg') ?>" 
                                                 alt="<?= htmlspecialchars($food['name']) ?>" class="w-full h-full object-cover">
                                        </div>
                                        <div class="w-2/3 p-3">
                                            <h3 class="font-medium text-gray-900 text-sm"><?= htmlspecialchars($food['name']) ?></h3>
                                            <div class="flex justify-between items-center mt-2">
                                                <span class="text-sm font-medium text-pink-600">৳<?= number_format($food['price'], 2) ?></span>
                                                <span class="text-xs text-gray-500"><?= $food['order_count'] ?> orders</span>
                                            </div>
                                            <?php if ($food['categories']): ?>
                                                <div class="flex flex-wrap gap-2 mt-2">
                                                    <?php foreach (explode(', ', $food['categories']) as $category): ?>
                                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                                            <?= htmlspecialchars($category) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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