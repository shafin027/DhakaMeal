<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include_once 'config/database.php';
include_once 'includes/header.php';

$restaurantId = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 0;
if (!$restaurantId) {
    header('Location: index.php');
    exit;
}

// Fetch Restaurant Details
try {
    $restaurantQuery = "SELECT name FROM Restaurant WHERE restaurant_id = ?";
    $restaurantStmt = $conn->prepare($restaurantQuery);
    $restaurantStmt->execute([$restaurantId]);
    $restaurant = $restaurantStmt->fetch(PDO::FETCH_ASSOC);
    if (!$restaurant) {
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    echo "<p class='text-red-600'>Error fetching restaurant: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

$foods = [];
$categories = ['Starters', 'Main Course', 'Desserts'];
$selectedCategory = isset($_GET['category']) && in_array($_GET['category'], $categories) ? $_GET['category'] : null;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle Add to Cart without redirect
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $foodId = $_POST['food_id'] ?? null;
    $name = $_POST['name'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $discountPercentage = floatval($_POST['discount_percentage'] ?? 0);
    if ($foodId && $name && $price) {
        $effectivePrice = $discountPercentage > 0 ? $price * (1 - $discountPercentage / 100) : $price;
        $quantity = 1;
        $item = [
            'food_id' => $foodId,
            'name' => $name,
            'price' => $effectivePrice, // Use discounted price if applicable
            'original_price' => $price, // Store original price for display
            'discount_percentage' => $discountPercentage,
            'quantity' => $quantity,
            'restaurant_id' => $restaurantId
        ];
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        // Ensure cart only contains items from the same restaurant
        if (!empty($_SESSION['cart']) && $_SESSION['cart'][0]['restaurant_id'] != $restaurantId) {
            $_SESSION['cart'] = []; // Clear cart if switching restaurants
        }
        $found = false;
        foreach ($_SESSION['cart'] as &$cartItem) {
            if ($cartItem['food_id'] == $foodId) {
                $cartItem['quantity'] += 1;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = $item;
        }
    }
}

// Fetch foods with discount information
try {
    $whereClauses = ["f.restaurant_id = :restaurant_id"];
    $params = [':restaurant_id' => $restaurantId];
    if ($selectedCategory) {
        $whereClauses[] = "f.category = :category";
        $params[':category'] = $selectedCategory;
    }
    if ($searchQuery) {
        $whereClauses[] = "(f.name LIKE :search OR f.description LIKE :search)";
        $params[':search'] = "%$searchQuery%";
    }
    $whereClause = "WHERE " . implode(" AND ", $whereClauses);
    $query = "SELECT f.food_id, f.name, f.description, f.price, f.stock_qty, f.image_url, f.category, 
                     p.discount_percentage 
              FROM Food f 
              LEFT JOIN Promotion p ON f.food_id = p.food_id 
              AND p.start_date <= NOW() 
              AND p.end_date >= NOW() 
              $whereClause";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $allFoods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p class='text-red-600'>Error fetching foods: " . htmlspecialchars($e->getMessage()) . "</p>";
    $allFoods = [];
}

// Fetch average ratings and reviews from FoodReview
try {
    $ratingsQuery = "SELECT food_id, AVG(rating) as avg_rating, COUNT(rating) as review_count 
                     FROM FoodReview 
                     WHERE food_id IN (SELECT food_id FROM Food WHERE restaurant_id = :restaurant_id) 
                     GROUP BY food_id";
    $ratingsStmt = $conn->prepare($ratingsQuery);
    $ratingsStmt->execute([':restaurant_id' => $restaurantId]);
    $ratings = $ratingsStmt->fetchAll(PDO::FETCH_ASSOC);
    $ratingsByFood = array_column($ratings ?? [], null, 'food_id');

    $reviewsQuery = "SELECT fr.food_id, fr.rating, fr.comment, fr.anonymous, p.name 
                     FROM FoodReview fr 
                     JOIN Customer c ON fr.customer_id = c.customer_id
                     JOIN Person p ON c.person_id = p.person_id 
                     WHERE fr.food_id IN (SELECT food_id FROM Food WHERE restaurant_id = :restaurant_id)";
    $reviewsStmt = $conn->prepare($reviewsQuery);
    $reviewsStmt->execute([':restaurant_id' => $restaurantId]);
    $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
    $reviewsByFood = [];
    foreach ($reviews ?? [] as $review) {
        $reviewsByFood[$review['food_id']][] = $review;
    }
} catch (PDOException $e) {
    echo "<p class='text-red-600'>Error fetching ratings/reviews: " . htmlspecialchars($e->getMessage()) . "</p>";
    $reviewsByFood = [];
}
?>

<!-- Restaurant Header -->
<section class="bg-gray-100 py-8">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($restaurant['name']); ?></h1>
        <p class="text-gray-600 mt-2">Explore a variety of dishes from <?php echo htmlspecialchars($restaurant['name']); ?>!</p>
    </div>
</section>

<!-- Category Tiles -->
<section class="container mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Categories</h2>
    <div class="flex space-x-4 overflow-x-auto pb-4">
        <a href="restaurant.php?restaurant_id=<?php echo $restaurantId; ?>" class="flex flex-col items-center p-4 bg-pink-100 border-2 border-pink-600 rounded-lg hover:shadow-md transition duration-300">
            <svg class="w-10 h-10 text-pink-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <span class="text-sm font-medium text-pink-600">All</span>
        </a>
        <?php foreach ($categories as $category): ?>
            <a href="restaurant.php?restaurant_id=<?php echo $restaurantId; ?>&category=<?php echo urlencode($category); ?>" class="flex flex-col items-center p-4 bg-white shadow rounded-lg hover:shadow-md transition duration-300">
                <svg class="w-10 h-10 text-pink-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <?php if ($category === 'Starters'): ?>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13H5v-2h14v2zM12 3v2m0 16v-2m-9-9h2m14 0h-2m-4.5-6.5l1.5 1.5m0 10l-1.5 1.5m-4.5-4.5l-1.5-1.5m0-4.5l1.5-1.5"></path>
                    <?php elseif ($category === 'Main Course'): ?>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m-4-4h8m-4-8v2m0 12v-2m-6-6h2m10 0h-2m-4-6l1 1m0 10l-1 1m-4-4l-1-1m0-4l1-1"></path>
                    <?php elseif ($category === 'Desserts'): ?>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v6l3 3m-6 0l3-3m-3 7v2m-5-5h10m-5-7v2m-5 5h2m8 0h-2m-4-6l1 1m0 8l-1 1"></path>
                    <?php endif; ?>
                </svg>
                <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($category); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Food Items -->
<section class="container mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Menu</h2>
    <?php if (empty($allFoods)): ?>
        <p class="text-gray-600 text-center">No food items found for this restaurant.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($allFoods as $food): ?>
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <img src="<?php echo htmlspecialchars($food['image_url'] ?: 'images/default_food.jpg'); ?>" alt="<?php echo htmlspecialchars($food['name']); ?>" class="w-full h-40 object-cover">
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($food['name']); ?></h3>
                        <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($food['description'] ?: 'No description'); ?></p>
                        <div class="flex items-center justify-between mb-2">
                            <?php
                            $originalPrice = $food['price'];
                            $discountPercentage = $food['discount_percentage'] ?? 0;
                            $discountedPrice = $discountPercentage > 0 ? $originalPrice * (1 - $discountPercentage / 100) : $originalPrice;
                            ?>
                            <div>
                                <?php if ($discountPercentage > 0): ?>
                                    <p class="text-lg font-bold text-green-600">৳<?php echo number_format($discountedPrice, 2); ?></p>
                                    <p class="text-sm text-gray-500 line-through">৳<?php echo number_format($originalPrice, 2); ?></p>
                                    <p class="text-xs text-green-600"><?php echo number_format($discountPercentage, 2); ?>% off</p>
                                <?php else: ?>
                                    <p class="text-lg font-bold text-pink-600">৳<?php echo number_format($originalPrice, 2); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php
                            $avgRating = isset($ratingsByFood[$food['food_id']]['avg_rating']) ? number_format($ratingsByFood[$food['food_id']]['avg_rating'], 1) : 'N/A';
                            $reviewCount = isset($ratingsByFood[$food['food_id']]['review_count']) ? $ratingsByFood[$food['food_id']]['review_count'] : 0;
                            ?>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                <span class="text-sm text-gray-600 ml-1"><?php echo $avgRating; ?> (<?php echo $reviewCount; ?>)</span>
                            </div>
                        </div>
                        <?php if ($food['stock_qty'] > 0 && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer'): ?>
                            <form method="POST">
                                <input type="hidden" name="food_id" value="<?php echo $food['food_id']; ?>">
                                <input type="hidden" name="name" value="<?php echo htmlspecialchars($food['name']); ?>">
                                <input type="hidden" name="price" value="<?php echo $originalPrice; ?>">
                                <input type="hidden" name="discount_percentage" value="<?php echo $discountPercentage; ?>">
                                <button type="submit" name="add_to_cart" class="w-full bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 transition duration-300">Add to Cart</button>
                            </form>
                        <?php elseif ($food['stock_qty'] <= 0): ?>
                            <p class="text-sm text-red-600">Out of Stock</p>
                        <?php endif; ?>
                        <?php if (isset($reviewsByFood[$food['food_id']])): ?>
                            <div class="mt-4 max-h-32 overflow-y-auto">
                                <?php foreach ($reviewsByFood[$food['food_id']] as $review): ?>
                                    <div class="border-t pt-2 mt-2">
                                        <p class="text-sm text-gray-600">Rating: <?php echo $review['rating']; ?>/5</p>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($review['comment'] ?: 'No comment'); ?></p>
                                        <p class="text-xs text-gray-500">By: <?php echo $review['anonymous'] ? 'Anonymous' : htmlspecialchars($review['name']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>


<?php include_once 'includes/footer.php'; ?>