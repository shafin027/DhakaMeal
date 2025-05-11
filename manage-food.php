<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';

// Ensure user is logged in and is a restaurant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'restaurant') {
    header('Location: login.php');
    exit;
}

// Fetch restaurant_id for the logged-in user
try {
    $restaurantQuery = "SELECT restaurant_id FROM Restaurant WHERE person_id = ?";
    $restaurantStmt = $conn->prepare($restaurantQuery);
    $restaurantStmt->execute([$_SESSION['user_id']]);
    $restaurant = $restaurantStmt->fetch(PDO::FETCH_ASSOC);
    if (!$restaurant) {
        echo "<p class='text-red-600'>Restaurant not found.</p>";
        exit;
    }
    $restaurantId = $restaurant['restaurant_id'];
} catch (PDOException $e) {
    echo "<p class='text-red-600'>Error fetching restaurant: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Handle form submission for adding a new food item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stockQty = intval($_POST['stock_qty'] ?? 0);
    $category = $_POST['category'] ?? '';
    $discountPercentage = floatval($_POST['discount_percentage'] ?? 0);
    $imageUrl = '';

    // Validate inputs
    $errors = [];
    if (empty($name)) $errors[] = "Food name is required.";
    if ($price <= 0) $errors[] = "Price must be greater than 0.";
    if ($stockQty < 0) $errors[] = "Stock quantity cannot be negative.";
    if (!in_array($category, ['Starters', 'Main Course', 'Desserts'])) $errors[] = "Invalid category.";
    if ($discountPercentage < 0 || $discountPercentage > 100) $errors[] = "Discount percentage must be between 0 and 100.";

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        $fileType = $_FILES['image']['type'];
        $fileSize = $_FILES['image']['size'];
        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $uploadDir = 'Uploads/';
        $uploadPath = $uploadDir . $fileName;

        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Only JPEG, PNG, and GIF images are allowed.";
        }
        if ($fileSize > $maxFileSize) {
            $errors[] = "Image size must not exceed 5MB.";
        }

        if (empty($errors)) {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $imageUrl = $uploadPath;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    // If no errors, proceed to save the food item and promotion
    if (empty($errors)) {
        try {
            // Insert into Food table
            $foodQuery = "INSERT INTO Food (restaurant_id, name, description, price, stock_qty, image_url, category) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $foodStmt = $conn->prepare($foodQuery);
            $foodStmt->execute([$restaurantId, $name, $description, $price, $stockQty, $imageUrl, $category]);
            $foodId = $conn->lastInsertId();

            // If discount percentage is provided, add a promotion
            if ($discountPercentage > 0) {
                $startDate = date('Y-m-d H:i:s'); // Current date/time
                $endDate = date('Y-m-d H:i:s', strtotime('+30 days')); // Valid for 30 days
                $promotionCode = 'DISCOUNT_' . $foodId . '_' . time(); // Unique code
                $promotionDescription = "$discountPercentage% off on $name";

                $promotionQuery = "INSERT INTO Promotion (restaurant_id, food_id, code, description, discount_percentage, start_date, end_date) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)";
                $promotionStmt = $conn->prepare($promotionQuery);
                $promotionStmt->execute([$restaurantId, $foodId, $promotionCode, $promotionDescription, $discountPercentage, $startDate, $endDate]);
            }

            echo "<p class='text-green-600'>Food item added successfully!</p>";
        } catch (PDOException $e) {
            echo "<p class='text-red-600'>Error adding food item: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        foreach ($errors as $error) {
            echo "<p class='text-red-600'>$error</p>";
        }
    }
}

// Handle food item deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_food'])) {
    $foodId = intval($_POST['food_id'] ?? 0);
    try {
        // Delete associated promotions
        $deletePromotionQuery = "DELETE FROM Promotion WHERE food_id = ?";
        $deletePromotionStmt = $conn->prepare($deletePromotionQuery);
        $deletePromotionStmt->execute([$foodId]);

        // Delete food item
        $deleteFoodQuery = "DELETE FROM Food WHERE food_id = ? AND restaurant_id = ?";
        $deleteFoodStmt = $conn->prepare($deleteFoodQuery);
        $deleteFoodStmt->execute([$foodId, $restaurantId]);

        echo "<p class='text-green-600'>Food item deleted successfully!</p>";
    } catch (PDOException $e) {
        echo "<p class='text-red-600'>Error deleting food item: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Handle food item editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_food'])) {
    $foodId = intval($_POST['food_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stockQty = intval($_POST['stock_qty'] ?? 0);
    $category = $_POST['category'] ?? '';
    $imageUrl = $_POST['existing_image'] ?? '';

    // Validate inputs
    $errors = [];
    if (empty($name)) $errors[] = "Food name is required.";
    if ($price <= 0) $errors[] = "Price must be greater than 0.";
    if ($stockQty < 0) $errors[] = "Stock quantity cannot be negative.";
    if (!in_array($category, ['Starters', 'Main Course', 'Desserts'])) $errors[] = "Invalid category.";

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        $fileType = $_FILES['image']['type'];
        $fileSize = $_FILES['image']['size'];
        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $uploadDir = 'Uploads/';
        $uploadPath = $uploadDir . $fileName;

        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Only JPEG, PNG, and GIF images are allowed.";
        }
        if ($fileSize > $maxFileSize) {
            $errors[] = "Image size must not exceed 5MB.";
        }

        if (empty($errors)) {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $imageUrl = $uploadPath;
                // Delete old image if it exists
                if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                    unlink($_POST['existing_image']);
                }
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $updateQuery = "UPDATE Food SET name = ?, description = ?, price = ?, stock_qty = ?, image_url = ?, category = ? 
                           WHERE food_id = ? AND restaurant_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->execute([$name, $description, $price, $stockQty, $imageUrl, $category, $foodId, $restaurantId]);

            echo "<p class='text-green-600'>Food item updated successfully!</p>";
        } catch (PDOException $e) {
            echo "<p class='text-red-600'>Error updating food item: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        foreach ($errors as $error) {
            echo "<p class='text-red-600'>$error</p>";
        }
    }
}

// Handle discount update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_discount'])) {
    $foodId = intval($_POST['food_id'] ?? 0);
    $discountPercentage = floatval($_POST['discount_percentage'] ?? 0);

    // Validate discount
    $errors = [];
    if ($discountPercentage < 0 || $discountPercentage > 100) $errors[] = "Discount percentage must be between 0 and 100.";

    if (empty($errors)) {
        try {
            // Delete existing promotion
            $deletePromotionQuery = "DELETE FROM Promotion WHERE food_id = ?";
            $deletePromotionStmt = $conn->prepare($deletePromotionQuery);
            $deletePromotionStmt->execute([$foodId]);

            // Add new promotion if discount > 0
            if ($discountPercentage > 0) {
                $foodQuery = "SELECT name FROM Food WHERE food_id = ? AND restaurant_id = ?";
                $foodStmt = $conn->prepare($foodQuery);
                $foodStmt->execute([$foodId, $restaurantId]);
                $food = $foodStmt->fetch(PDO::FETCH_ASSOC);

                if ($food) {
                    $startDate = date('Y-m-d H:i:s');
                    $endDate = date('Y-m-d H:i:s', strtotime('+30 days'));
                    $promotionCode = 'DISCOUNT_' . $foodId . '_' . time();
                    $promotionDescription = "$discountPercentage% off on {$food['name']}";

                    $promotionQuery = "INSERT INTO Promotion (restaurant_id, food_id, code, description, discount_percentage, start_date, end_date) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $promotionStmt = $conn->prepare($promotionQuery);
                    $promotionStmt->execute([$restaurantId, $foodId, $promotionCode, $promotionDescription, $discountPercentage, $startDate, $endDate]);
                }
            }

            echo "<p class='text-green-600'>Discount updated successfully!</p>";
        } catch (PDOException $e) {
            echo "<p class='text-red-600'>Error updating discount: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        foreach ($errors as $error) {
            echo "<p class='text-red-600'>$error</p>";
        }
    }
}

// Fetch existing food items for the restaurant
try {
    $foodQuery = "SELECT f.food_id, f.name, f.description, f.price, f.stock_qty, f.image_url, f.category, 
                         p.discount_percentage 
                  FROM Food f 
                  LEFT JOIN Promotion p ON f.food_id = p.food_id 
                  WHERE f.restaurant_id = ? 
                  AND (p.end_date IS NULL OR p.end_date >= NOW())";
    $foodStmt = $conn->prepare($foodQuery);
    $foodStmt->execute([$restaurantId]);
    $foods = $foodStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p class='text-red-600'>Error fetching food items: " . htmlspecialchars($e->getMessage()) . "</p>";
    $foods = [];
}
?>

<!-- Manage Food Section -->
<section class="container mx-auto px-4 py-16">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Manage Food Items</h1>

    <!-- Add Food Form -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Add New Food Item</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Food Name</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Description</label>
                <textarea name="description" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600" rows="4"></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Price (৳)</label>
                <input type="number" name="price" min="0" step="0.01" required class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Stock Quantity</label>
                <input type="number" name="stock_qty" min="0" required class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Category</label>
                <select name="category" required class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600">
                    <option value="Starters">Starters</option>
                    <option value="Main Course">Main Course</option>
                    <option value="Desserts">Desserts</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Discount Percentage (%)</label>
                <input type="number" name="discount_percentage" min="0" max="100" step="0.01" placeholder="e.g., 10 for 10%" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Image</label>
                <input type="file" name="image" accept="image/*" class="w-full border border-gray-300 rounded px-4 py-2">
            </div>
            <button type="submit" name="add_food" class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 transition duration-300">Add Food Item</button>
        </form>
    </div>

    <!-- Existing Food Items -->
    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Existing Food Items</h2>
        <?php if (empty($foods)): ?>
            <p class="text-gray-600">No food items found. Add some above!</p>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                <?php foreach ($foods as $food): ?>
                    <div class="bg-gray-100 rounded-lg p-4">
                        <img src="<?php echo htmlspecialchars($food['image_url'] ?: 'images/default_food.jpg'); ?>" alt="<?php echo htmlspecialchars($food['name']); ?>" class="w-full h-32 object-cover rounded mb-2">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($food['name']); ?></h3>
                        <p class="text-sm text-gray-600 mb-1"><?php echo htmlspecialchars($food['description'] ?: 'No description'); ?></p>
                        <p class="text-sm text-gray-600 mb-1"><strong>Price:</strong> ৳<?php echo number_format($food['price'], 2); ?></p>
                        <?php if ($food['discount_percentage'] > 0): ?>
                            <p class="text-sm text-green-600 mb-1"><strong>Discount:</strong> <?php echo number_format($food['discount_percentage'], 2); ?>%</p>
                            <p class="text-sm text-gray-600 mb-1"><strong>Discounted Price:</strong> ৳<?php echo number_format($food['price'] * (1 - $food['discount_percentage'] / 100), 2); ?></p>
                        <?php endif; ?>
                        <p class="text-sm text-gray-600 mb-1"><strong>Stock:</strong> <?php echo $food['stock_qty']; ?></p>
                        <p class="text-sm text-gray-600 mb-2"><strong>Category:</strong> <?php echo htmlspecialchars($food['category']); ?></p>

                        <!-- Edit Food Form -->
                        <details class="mb-2">
                            <summary class="text-sm text-pink-600 cursor-pointer">Edit Item</summary>
                            <form method="POST" enctype="multipart/form-data" class="mt-2">
                                <input type="hidden" name="food_id" value="<?php echo $food['food_id']; ?>">
                                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($food['image_url']); ?>">
                                <div class="mb-2">
                                    <label class="block text-gray-700 text-sm">Food Name</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($food['name']); ?>" required class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                                </div>
                                <div class="mb-2">
                                    <label class="block text-gray-700 text-sm">Description</label>
                                    <textarea name="description" class="w-full border border-gray-300 rounded px-2 py-1 text-sm" rows="3"><?php echo htmlspecialchars($food['description']); ?></textarea>
                                </div>
                                <div class="mb-2">
                                    <label class="block text-gray-700 text-sm">Price (৳)</label>
                                    <input type="number" name="price" value="<?php echo $food['price']; ?>" min="0" step="0.01" required class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                                </div>
                                <div class="mb-2">
                                    <label class="block text-gray-700 text-sm">Stock Quantity</label>
                                    <input type="number" name="stock_qty" value="<?php echo $food['stock_qty']; ?>" min="0" required class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                                </div>
                                <div class="mb-2">
                                    <label class="block text-gray-700 text-sm">Category</label>
                                    <select name="category" required class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                                        <option value="Starters" <?php echo $food['category'] === 'Starters' ? 'selected' : ''; ?>>Starters</option>
                                        <option value="Main Course" <?php echo $food['category'] === 'Main Course' ? 'selected' : ''; ?>>Main Course</option>
                                        <option value="Desserts" <?php echo $food['category'] === 'Desserts' ? 'selected' : ''; ?>>Desserts</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="block text-gray-700 text-sm">New Image</label>
                                    <input type="file" name="image" accept="image/*" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                                </div>
                                <button type="submit" name="edit_food" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 text-sm">Update Item</button>
                            </form>
                        </details>

                        <!-- Update Discount Form -->
                        <details class="mb-2">
                            <summary class="text-sm text-pink-600 cursor-pointer">Update Discount</summary>
                            <form method="POST" class="mt-2">
                                <input type="hidden" name="food_id" value="<?php echo $food['food_id']; ?>">
                                <div class="mb-2">
                                    <label class="block text-gray-700 text-sm">Discount Percentage (%)</label>
                                    <input type="number" name="discount_percentage" value="<?php echo $food['discount_percentage'] ?? 0; ?>" min="0" max="100" step="0.01" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                                </div>
                                <button type="submit" name="update_discount" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm">Update Discount</button>
                            </form>
                        </details>

                        <!-- Delete Food Form -->
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this item?');">
                            <input type="hidden" name="food_id" value="<?php echo $food['food_id']; ?>">
                            <button type="submit" name="delete_food" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm">Delete Item</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>


<?php include_once 'includes/footer.php'; ?>