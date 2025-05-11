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

// Get categories
$categoryQuery = "SELECT * FROM Category ORDER BY name";
$categoryStmt = $conn->prepare($categoryQuery);
$categoryStmt->execute();
$categories = $categoryStmt->fetchAll();

// Add food item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock_qty = $_POST['stock_qty'];
    $image_url = $_POST['image_url'];
    $featured = isset($_POST['featured']) ? 1 : 0;
    $selectedCategories = $_POST['categories'] ?? [];

    try {
        $conn->beginTransaction();

        // Insert food
        $foodQuery = "INSERT INTO Food (restaurant_id, name, description, price, stock_qty, image_url, featured) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        $foodStmt = $conn->prepare($foodQuery);
        $foodStmt->execute([$restaurant['restaurant_id'], $name, $description, $price, $stock_qty, $image_url, $featured]);
        $foodId = $conn->lastInsertId();

        // Insert food categories
        foreach ($selectedCategories as $categoryId) {
            $foodCategoryQuery = "INSERT INTO FoodCategory (food_id, category_id) VALUES (?, ?)";
            $foodCategoryStmt = $conn->prepare($foodCategoryQuery);
            $foodCategoryStmt->execute([$foodId, $categoryId]);
        }

        $conn->commit();
        $success = "Food item added successfully!";
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Failed to add food item: " . $e->getMessage();
    }
}

// Edit food item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_food'])) {
    $foodId = $_POST['food_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock_qty = $_POST['stock_qty'];
    $image_url = $_POST['image_url'];
    $featured = isset($_POST['featured']) ? 1 : 0;
    $selectedCategories = $_POST['categories'] ?? [];

    try {
        $conn->beginTransaction();

        // Update food
        $foodQuery = "UPDATE Food SET name = ?, description = ?, price = ?, stock_qty = ?, image_url = ?, featured = ? 
                      WHERE food_id = ? AND restaurant_id = ?";
        $foodStmt = $conn->prepare($foodQuery);
        $foodStmt->execute([$name, $description, $price, $stock_qty, $image_url, $featured, $foodId, $restaurant['restaurant_id']]);

        // Delete existing categories
        $deleteCategoryQuery = "DELETE FROM FoodCategory WHERE food_id = ?";
        $deleteCategoryStmt = $conn->prepare($deleteCategoryQuery);
        $deleteCategoryStmt->execute([$foodId]);

        // Insert new categories
        foreach ($selectedCategories as $categoryId) {
            $foodCategoryQuery = "INSERT INTO FoodCategory (food_id, category_id) VALUES (?, ?)";
            $foodCategoryStmt = $conn->prepare($foodCategoryQuery);
            $foodCategoryStmt->execute([$foodId, $categoryId]);
        }

        $conn->commit();
        $success = "Food item updated successfully!";
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Failed to update food item: " . $e->getMessage();
    }
}

// Delete food item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_food'])) {
    $foodId = $_POST['food_id'];

    try {
        $conn->beginTransaction();

        // Delete food categories
        $deleteCategoryQuery = "DELETE FROM FoodCategory WHERE food_id = ?";
        $deleteCategoryStmt = $conn->prepare($deleteCategoryQuery);
        $deleteCategoryStmt->execute([$foodId]);

        // Delete food
        $deleteFoodQuery = "DELETE FROM Food WHERE food_id = ? AND restaurant_id = ?";
        $deleteFoodStmt = $conn->prepare($deleteFoodQuery);
        $deleteFoodStmt->execute([$foodId, $restaurant['restaurant_id']]);

        $conn->commit();
        $success = "Food item deleted successfully!";
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Failed to delete food item: " . $e->getMessage();
    }
}

// Get food items
$foodQuery = "SELECT f.*, (SELECT GROUP_CONCAT(c.name SEPARATOR ', ') 
              FROM FoodCategory fc 
              JOIN Category c ON fc.category_id = c.category_id 
              WHERE fc.food_id = f.food_id) as categories
              FROM Food f WHERE f.restaurant_id = ? ORDER BY f.name";
$foodStmt = $conn->prepare($foodQuery);
$foodStmt->execute([$restaurant['restaurant_id']]);
$foods = $foodStmt->fetchAll();

// Get food item for editing
$editFood = null;
if (isset($_GET['edit_id'])) {
    $editFoodQuery = "SELECT * FROM Food WHERE food_id = ? AND restaurant_id = ?";
    $editFoodStmt = $conn->prepare($editFoodQuery);
    $editFoodStmt->execute([$_GET['edit_id'], $restaurant['restaurant_id']]);
    $editFood = $editFoodStmt->fetch();

    // Get current categories
    $editCategoriesQuery = "SELECT category_id FROM FoodCategory WHERE food_id = ?";
    $editCategoriesStmt = $conn->prepare($editCategoriesQuery);
    $editCategoriesStmt->execute([$_GET['edit_id']]);
    $editFood['categories'] = array_column($editCategoriesStmt->fetchAll(), 'category_id');
}
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
                    <a href="orders.php" class="text-gray-600 hover:bg-gray-100 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-shopping-bag mr-3 text-gray-400"></i> Orders
                    </a>
                    <a href="menu.php" class="bg-pink-100 text-pink-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-utensils mr-3 text-pink-500"></i> Menu Management
                    </a>
                    <a href="reviews.php" class="text-gray-600 hover:bg-gray-100 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-star mr-3 text-gray-400"></i> Reviews
                    </a>
                    <a href="reports.php" class="text-gray-600 hover:bg-gray-100 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-chart-line mr-3 text-gray-400"></i> Financial Reports
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
                <a href="orders.php" class="text-gray-600 hover:bg-gray-100 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium">
                    Orders
                </a>
                <a href="menu.php" class="bg-pink-100 text-pink-700 block pl-3 pr-4 py-2 border-l-4 border-pink-500 text-base font-medium">
                    Menu Management
                </a>
                <a href="reviews.php" class="text-gray-600 hover:bg-gray-100 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium">
                    Reviews
                </a>
                <a href="reports.php" class="text-gray-600 hover:bg-gray-100 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium">
                    Financial Reports
                </a>
                <a href="logout.php" class="text-gray-600 hover:bg-gray-100 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium">
                    Logout
                </a>
            </div>
        </div>

        <!-- Page content -->
        <main class="flex-1 overflow-y-auto bg-gray-100">
            <div class="py-6 px-4 sm:px-6 lg:px-8">
                <h1 class="text-2xl font-bold mb-6 text-gray-900">Menu Management</h1>
                
                <!-- Add/Edit Food Form -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4"><?= $editFood ? 'Edit Food Item' : 'Add New Food Item' ?></h2>
                    <?php if (isset($success)): ?>
                        <p class="text-green-600 mb-4"><?php echo $success; ?></p>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <p class="text-red-600 mb-4"><?php echo $error; ?></p>
                    <?php endif; ?>
                    <form method="POST">
                        <?php if ($editFood): ?>
                            <input type="hidden" name="food_id" value="<?= $editFood['food_id'] ?>">
                        <?php endif; ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700">Name</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($editFood['name'] ?? '') ?>" 
                                       class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                            </div>
                            <div>
                                <label class="block text-gray-700">Price (৳)</label>
                                <input type="number" name="price" step="0.01" value="<?= $editFood['price'] ?? '' ?>" 
                                       class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                            </div>
                            <div>
                                <label class="block text-gray-700">Stock Quantity</label>
                                <input type="number" name="stock_qty" value="<?= $editFood['stock_qty'] ?? '' ?>" 
                                       class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                            </div>
                            <div>
                                <label class="block text-gray-700">Image URL</label>
                                <input type="url" name="image_url" value="<?= htmlspecialchars($editFood['image_url'] ?? '') ?>" 
                                       class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700">Description</label>
                                <textarea name="description" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                                    <?= htmlspecialchars($editFood['description'] ?? '') ?>
                                </textarea>
                            </div>
                            <div>
                                <label class="block text-gray-700">Categories</label>
                                <select name="categories[]" multiple class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['category_id'] ?>" 
                                                <?= $editFood && in_array($category['category_id'], $editFood['categories']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="featured" id="featured" 
                                       class="mr-2" <?= $editFood && $editFood['featured'] ? 'checked' : '' ?>>
                                <label for="featured" class="text-gray-700">Featured</label>
                            </div>
                        </div>
                        <button type="submit" name="<?= $editFood ? 'edit_food' : 'add_food' ?>" 
                                class="mt-4 bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700">
                            <?= $editFood ? 'Update Food Item' : 'Add Food Item' ?>
                        </button>
                        <?php if ($editFood): ?>
                            <a href="menu.php" class="mt-4 ml-4 text-pink-600 hover:text-pink-700">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Food List -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Your Menu</h2>
                    </div>
                    <div class="px-6 py-4">
                        <?php if (count($foods) === 0): ?>
                            <p class="text-center text-gray-500 py-4">No food items available</p>
                        <?php else: ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categories</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Featured</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($foods as $food): ?>
                                        <tr>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($food['name']) ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">৳<?= number_format($food['price'], 2) ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?= $food['stock_qty'] ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($food['categories'] ?? '-') ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                <?= $food['featured'] ? '<i class="fas fa-check text-green-600"></i>' : '-' ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                                <a href="menu.php?edit_id=<?= $food['food_id'] ?>" 
                                                   class="text-blue-600 hover:text-blue-700 mr-2">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <form method="POST" class="inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this item?')">
                                                    <input type="hidden" name="food_id" value="<?= $food['food_id'] ?>">
                                                    <button type="submit" name="delete_food" 
                                                            class="text-red-600 hover:text-red-700">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
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