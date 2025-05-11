<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';

// Fetch restaurant_id for logged-in restaurant user
$restaurantUserId = null;
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'restaurant') {
    $personId = $_SESSION['user_id'];
    $restaurantQuery = "SELECT restaurant_id FROM Restaurant WHERE person_id = ?";
    $restaurantStmt = $conn->prepare($restaurantQuery);
    $restaurantStmt->execute([$personId]);
    $restaurant = $restaurantStmt->fetch(PDO::FETCH_ASSOC);
    $restaurantUserId = $restaurant ? $restaurant['restaurant_id'] : null;
}

// Handle stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock']) && $restaurantUserId) {
    $foodId = (int)$_POST['food_id'];
    $newStock = (int)$_POST['stock_qty'];

    if ($newStock >= 0) {
        try {
            $updateStockQuery = "UPDATE Food SET stock_qty = ? WHERE food_id = ? AND restaurant_id = ?";
            $updateStockStmt = $conn->prepare($updateStockQuery);
            $updateStockStmt->execute([$newStock, $foodId, $restaurantUserId]);
            echo "<p class='text-green-600 text-center'>Stock updated successfully!</p>";
        } catch (PDOException $e) {
            echo "<p class='text-red-600 text-center'>Error updating stock: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='text-red-600 text-center'>Stock quantity cannot be negative.</p>";
    }
}

// Initially fetch all restaurants and their food items
$restaurants = [];
$foodsByRestaurant = [];

try {
    // Fetch Restaurants and their average ratings
    $restaurantQuery = "SELECT r.restaurant_id, r.name, r.description, r.address,
                           COALESCE(AVG(rr.rating), 0) as avg_rating,
                           COUNT(rr.rating) as review_count
                    FROM Restaurant r
                    LEFT JOIN RestaurantReview rr ON r.restaurant_id = rr.restaurant_id
                    GROUP BY r.restaurant_id";
    $stmt = $conn->prepare($restaurantQuery);
    $stmt->execute();
    $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Food Items for Each Restaurant with their average ratings
    foreach ($restaurants as $restaurant) {
        $restaurantId = $restaurant['restaurant_id'];
        $foodQuery = "SELECT f.food_id, f.name, f.description, f.price, f.stock_qty, f.image_url, f.category, 
                             p.discount_percentage,
                             COALESCE(AVG(fr.rating), 0) AS avg_food_rating,
                             COUNT(fr.rating) AS food_review_count
                      FROM Food f 
                      LEFT JOIN Promotion p ON f.food_id = p.food_id 
                      AND p.start_date <= NOW() 
                      AND p.end_date >= NOW()
                      LEFT JOIN FoodReview fr ON f.food_id = fr.food_id
                      WHERE f.restaurant_id = :restaurant_id
                      GROUP BY f.food_id, f.name, f.description, f.price, f.stock_qty, f.image_url, f.category, p.discount_percentage";
        $foodStmt = $conn->prepare($foodQuery);
        $foodStmt->execute([':restaurant_id' => $restaurantId]);
        $foods = $foodStmt->fetchAll(PDO::FETCH_ASSOC);
        $foodsByRestaurant[$restaurantId] = $foods;
    }
} catch (PDOException $e) {
    error_log("Error fetching initial data: " . $e->getMessage());
    echo "<p class='text-red-600'>Error fetching data: " . htmlspecialchars($e->getMessage()) . "</p>";
    $restaurants = [];
    $foodsByRestaurant = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DhakaMeal - Discover Restaurants</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Sticky Footer Styles */
        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            display: flex;
            flex-direction: column;
        }
        .content {
            flex: 1 0 auto;
        }
        footer {
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="content">
        <!-- Hero Section (No Carousel) -->
        <section class="bg-gray-100 py-16">
            <div class="container mx-auto px-4 text-center">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Welcome to DhakaMeal</h1>
                <p class="text-lg text-gray-600 mb-8">Discover the best restaurants in Dhaka and order your favorite meals with ease!</p>
            </div>
        </section>

        <!-- Restaurants and Food Items Section -->
        <section id="restaurants" class="container mx-auto px-4 py-16">
            <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Our Restaurants</h2>
            <div id="restaurantList">
                <?php if (empty($restaurants)): ?>
                    <p class="text-gray-600 text-center">No restaurants found.</p>
                <?php else: ?>
                    <?php foreach ($restaurants as $restaurant): ?>
                        <div class="mb-12 restaurant-item">
                            <div class="bg-white shadow-md rounded-lg p-6 flex items-center justify-between">
                                <div class="flex items-center">
                                    <img src="images/restaurants/restaurant_<?php echo $restaurant['restaurant_id']; ?>.jpg" alt="<?php echo htmlspecialchars($restaurant['name']); ?>" class="w-24 h-24 object-cover rounded-lg mr-4" onerror="this.src='images/restaurant_placeholder.jpg'">
                                    <div>
                                        <h3 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($restaurant['name']); ?></h3>
                                        <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($restaurant['description'] ?: 'No description available'); ?></p>
                                        <p class="text-sm text-gray-500 mb-2"><?php echo htmlspecialchars($restaurant['address']); ?></p>
                                        <div class="flex items-center mb-2">
                                            <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                            </svg>
                                            <span class="text-sm text-gray-600 ml-1"><?php echo number_format($restaurant['avg_rating'], 1); ?> (<?php echo $restaurant['review_count']; ?> reviews)</span>
                                        </div>
                                    </div>
                                </div>
                                <a href="restaurant.php?restaurant_id=<?php echo $restaurant['restaurant_id']; ?>" class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 transition duration-300">View Full Menu</a>
                            </div>

                            <!-- Category Tiles -->
                            <div class="mt-6">
                                <h3 class="text-xl font-semibold text-gray-900 mb-4">Categories</h3>
                                <div class="flex space-x-4 overflow-x-auto pb-4">
                                    <a href="restaurant.php?restaurant_id=<?php echo $restaurant['restaurant_id']; ?>" class="flex flex-col items-center p-4 bg-pink-100 border-2 border-pink-600 rounded-lg hover:shadow-md transition duration-300">
                                        <svg class="w-10 h-10 text-pink-600 mb-2 category-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-pink-600">All</span>
                                    </a>
                                    <a href="restaurant.php?restaurant_id=<?php echo $restaurant['restaurant_id']; ?>&category=Starters" class="flex flex-col items-center p-4 bg-white shadow rounded-lg hover:shadow-md transition duration-300">
                                        <svg class="w-10 h-10 text-pink-600 mb-2 category-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13H5v-2h14v2zM12 3v2m0 16v-2m-9-9h2m14 0h-2m-4.5-6.5l1.5 1.5m0 10l-1.5 1.5m-4.5-4.5l-1.5-1.5m0-4.5l1.5-1.5"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-700">Starters</span>
                                    </a>
                                    <a href="restaurant.php?restaurant_id=<?php echo $restaurant['restaurant_id']; ?>&category=Main Course" class="flex flex-col items-center p-4 bg-white shadow rounded-lg hover:shadow-md transition duration-300">
                                        <svg class="w-10 h-10 text-pink-600 mb-2 category-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m-4-4h8m-4-8v2m0 12v-2m-6-6h2m10 0h-2m-4-6l1 1m0 10l-1 1m-4-4l-1-1m0-4l1-1"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-700">Main Course</span>
                                    </a>
                                    <a href="restaurant.php?restaurant_id=<?php echo $restaurant['restaurant_id']; ?>&category=Desserts" class="flex flex-col items-center p-4 bg-white shadow rounded-lg hover:shadow-md transition duration-300">
                                        <svg class="w-10 h-10 text-pink-600 mb-2 category-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v6l3 3m-6 0l3-3m-3 7v2m-5-5h10m-5-7v2m-5 5h2m8 0h-2m-4-6l1 1m0 8l-1 1"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-700">Desserts</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Food Items for this Restaurant -->
                            <?php $foods = $foodsByRestaurant[$restaurant['restaurant_id']] ?? []; ?>
                            <?php if (!empty($foods)): ?>
                                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 food-list">
                                    <?php foreach ($foods as $food): ?>
                                        <div class="bg-white shadow-md rounded-lg overflow-hidden food-item">
                                            <img src="<?php echo htmlspecialchars($food['image_url'] ?: 'images/default_food.jpg'); ?>" alt="<?php echo htmlspecialchars($food['name']); ?>" class="w-full h-40 object-cover">
                                            <div class="p-4">
                                                <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($food['name']); ?></h3>
                                                <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($food['description'] ?: 'No description'); ?></p>
                                                <div class="flex items-center mb-2">
                                                    <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3 .921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784 .57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81 .588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                    </svg>
                                                    <span class="text-sm text-gray-600 ml-1">
                                                        <?php echo number_format($food['avg_food_rating'], 1); ?> 
                                                        (<?php echo $food['food_review_count']; ?> reviews)
                                                    </span>
                                                </div>
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
                                                </div>
                                                <p class="text-sm text-gray-600 mb-2">
                                                    <?php if ($food['stock_qty'] > 0): ?>
                                                        Stock: <?php echo $food['stock_qty']; ?>
                                                    <?php else: ?>
                                                        Out of Stock
                                                    <?php endif; ?>
                                                </p>
                                                <?php if ($food['stock_qty'] > 0 && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer'): ?>
                                                    <button onclick="addToCart(<?php echo $food['food_id']; ?>, '<?php echo htmlspecialchars($food['name']); ?>', <?php echo $discountedPrice; ?>, <?php echo $discountPercentage; ?>, <?php echo $originalPrice; ?>, <?php echo $restaurant['restaurant_id']; ?>)" class="w-full bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 transition duration-300">Add to Cart</button>
                                                <?php elseif ($food['stock_qty'] <= 0): ?>
                                                    <p class="text-sm text-red-600">Out of Stock</p>
                                                <?php endif; ?>
                                                <?php if ($restaurantUserId && $restaurantUserId == $restaurant['restaurant_id']): ?>
                                                    <div class="mt-4">
                                                        <form method="POST" class="flex items-center space-x-2">
                                                            <input type="hidden" name="food_id" value="<?php echo $food['food_id']; ?>">
                                                            <label class="text-sm text-gray-700">Update Stock:</label>
                                                            <input type="number" name="stock_qty" value="<?php echo $food['stock_qty']; ?>" min="0" class="w-20 px-2 py-1 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                                                            <button type="submit" name="update_stock" class="bg-pink-600 text-white px-4 py-1 rounded hover:bg-pink-700 transition duration-300 text-sm">Update</button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-600 mt-4">No food items available for this restaurant.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php include_once 'includes/footer.php'; ?>
</body>
</html>

<script>
// AJAX function to add items to cart
async function addToCart(foodId, name, price, discountPercentage, originalPrice, restaurantId) {
    try {
        const response = await fetch('add-to-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `food_id=${foodId}&name=${encodeURIComponent(name)}&price=${price}&discount_percentage=${discountPercentage}&original_price=${originalPrice}&restaurant_id=${restaurantId}`
        });

        const data = await response.json();
        if (data.success) {
            const cartCountElement = document.getElementById('cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = data.cartCount;
                cartCountElement.style.display = data.cartCount > 0 ? 'inline-block' : 'none';
            }
            alert('Item added to cart!');
        } else {
            alert('Error adding item to cart: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        alert('Error adding item to cart.');
    }
}
</script>