<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$restaurant = isset($_GET['restaurant']) ? trim($_GET['restaurant']) : '';

$query = "SELECT f.food_id, f.name, f.description, f.price, f.image_url, f.stock_qty, r.name AS restaurant_name 
          FROM Food f JOIN Restaurant r ON f.restaurant_id = r.restaurant_id 
          WHERE f.stock_qty > 0";
$params = [];

if ($search) {
    $query .= " AND (f.name LIKE ? OR f.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category) {
    $query .= " AND f.category = ?";
    $params[] = $category;
}
if ($restaurant) {
    $query .= " AND r.name LIKE ?";
    $params[] = "%$restaurant%";
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$foods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories and restaurants for filters
$categoryQuery = "SELECT DISTINCT category FROM Food WHERE category IS NOT NULL AND stock_qty > 0";
$categoryStmt = $conn->prepare($categoryQuery);
$categoryStmt->execute();
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

$restaurantQuery = "SELECT DISTINCT name FROM Restaurant";
$restaurantStmt = $conn->prepare($restaurantQuery);
$restaurantStmt->execute();
$restaurants = $restaurantStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Our Foods</h1>
        <a href="/dhakameal/index.php" class="bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700 transition duration-300">Back to Home</a>
    </div>

    <!-- Filters -->
    <form method="GET" class="mb-6 flex flex-col md:flex-row gap-4">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search foods..." 
               class="px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
        <select name="category" class="px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="restaurant" class="px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
            <option value="">All Restaurants</option>
            <?php foreach ($restaurants as $rest): ?>
                <option value="<?= htmlspecialchars($rest) ?>" <?= $restaurant === $rest ? 'selected' : '' ?>><?= htmlspecialchars($rest) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700 transition duration-300">Filter</button>
    </form>

    <!-- Food List -->
    <?php if (empty($foods)): ?>
        <p class="text-gray-500 text-center">No foods found.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($foods as $food): ?>
                <div class="bg-white shadow rounded-lg overflow-hidden transition duration-300 hover:shadow-lg">
                    <img src="<?= htmlspecialchars($food['image_url']) ?>" alt="<?= htmlspecialchars($food['name']) ?>" class="w-full h-48 object-cover">
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($food['name']) ?></h3>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($food['restaurant_name']) ?></p>
                        <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($food['description']) ?></p>
                        <p class="text-lg font-bold text-pink-600 mt-2">৳<?= number_format($food['price'], 2) ?></p>
                        <button data-food-id="<?= $food['food_id'] ?>" class="add-to-cart-btn mt-4 w-full bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 transition duration-300 <?= $food['stock_qty'] <= 0 ? 'opacity-50 cursor-not-allowed' : '' ?>" 
                                <?= $food['stock_qty'] <= 0 ? 'disabled' : '' ?>>Add to Cart</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="/dhakameal/cart.js"></script>
<?php include_once 'includes/footer.php'; ?>