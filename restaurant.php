<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';

// Fetch restaurants
$restaurantQuery = "SELECT name, description FROM Restaurant ORDER BY name";
$restaurantStmt = $conn->prepare($restaurantQuery);
$restaurantStmt->execute();
$restaurants = $restaurantStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Our Restaurants</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($restaurants as $restaurant): ?>
            <div class="bg-white shadow rounded-lg p-6 transition duration-300 hover:shadow-lg">
                <h2 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($restaurant['name']) ?></h2>
                <p class="text-sm text-gray-600 mt-2"><?= htmlspecialchars($restaurant['description']) ?></p>
                <a href="foods.php?restaurant=<?= urlencode($restaurant['name']) ?>" class="mt-4 inline-block text-pink-600 hover:text-pink-700 transition duration-300">View Menu</a>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-6">
        <a href="index.php" class="inline-block bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700 transition duration-300">Back to Home</a>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>