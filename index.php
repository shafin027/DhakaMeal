<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';

$foods = [];
$categories = ['Starters', 'Main Course', 'Desserts'];

// Fetch foods for each category
foreach ($categories as $category) {
    $query = "SELECT food_id, name, description, price, stock_qty, image_url 
              FROM Food 
              WHERE category = ? AND restaurant_id = (SELECT restaurant_id FROM Restaurant WHERE name = 'DhakaMeal')";
    $stmt = $conn->prepare($query);
    $stmt->execute([$category]);
    $foods[$category] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-gray-900 mb-8 text-center">Welcome to DhakaMeal</h1>
    <?php foreach ($categories as $category): ?>
        <?php if (!empty($foods[$category])): ?>
            <h2 class="text-2xl font-semibold text-gray-900 mb-4"><?php echo htmlspecialchars($category); ?></h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 mb-8">
                <?php foreach ($foods[$category] as $food): ?>
                    <div class="bg-white shadow rounded-lg p-4">
                        <img src="<?php echo htmlspecialchars($food['image_url']); ?>" alt="<?php echo htmlspecialchars($food['name']); ?>" class="w-full h-48 object-cover rounded-md mb-4">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($food['name']); ?></h3>
                        <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($food['description']); ?></p>
                        <p class="text-sm text-gray-600 mb-2">Price: ৳<?php echo number_format($food['price'], 2); ?></p>
                        <p class="text-sm text-gray-600 mb-4">Stock: <?php echo $food['stock_qty']; ?></p>
                        <?php if ($food['stock_qty'] > 0 && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer'): ?>
                            <form method="POST" action="cart.php">
                                <input type="hidden" name="food_id" value="<?php echo $food['food_id']; ?>">
                                <input type="hidden" name="name" value="<?php echo htmlspecialchars($food['name']); ?>">
                                <input type="hidden" name="price" value="<?php echo $food['price']; ?>">
                                <button type="submit" name="add_to_cart" class="w-full bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 transition duration-300">Add to Cart</button>
                            </form>
                        <?php else: ?>
                            <p class="text-sm text-red-600">Out of Stock</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<?php include_once 'includes/footer.php'; ?>