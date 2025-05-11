<?php
// Determine the current page
$currentPage = basename($_SERVER['PHP_SELF']);
$isHomepage = ($currentPage === 'index.php' || $currentPage === 'home.php');

// Calculate cart item count for both headers
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DhakaMeal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .category-icon {
            transition: transform 0.3s ease;
        }
        .category-icon:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body class="bg-gray-50">

<?php if ($isHomepage): ?>
    <!-- Second Header (for Homepage: index.php) -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
            <!-- Logo -->
            <a href="index.php" class="text-2xl font-bold text-pink-600 flex items-center">
                <svg class="w-8 h-8 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                DhakaMeal
            </a>
            <!-- Search Bar -->
            <form method="GET" action="index.php" class="flex-1 mx-4">
                <div class="flex items-center border border-gray-300 rounded-full overflow-hidden">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Search for restaurants or food..." class="w-full px-4 py-2 focus:outline-none">
                    <button type="submit" class="bg-pink-600 text-white px-4 py-2 hover:bg-pink-700 transition duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </div>
            </form>
            <!-- User Options -->
            <div class="flex items-center space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $_SESSION['user_type'] === 'customer' ? 'order-details.php' : ($_SESSION['user_type'] === 'restaurant' ? 'restaurant-dashboard.php' : 'delivery-dashboard.php'); ?>" class="text-gray-700 hover:text-pink-600">Dashboard</a>
                    <a href="logout.php" class="text-gray-700 hover:text-pink-600">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="text-gray-700 hover:text-pink-600">Login</a>
                    <a href="register.php" class="text-gray-700 hover:text-pink-600">Sign Up</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer'): ?>
                    <a href="cart.php" class="relative text-gray-700 hover:text-pink-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <?php if ($cartCount > 0): ?>
                            <span id="cart-count" class="absolute -top-1 -right-1 bg-pink-600 text-white text-xs rounded-full px-2 py-1"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
<?php else: ?>
    <!-- First Header (for All Other Pages) -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
            <a href="index.php" class="text-2xl font-bold text-pink-600">DhakaMeal</a>
            <?php if (isset($_GET['restaurant_id'])): ?>
                <form method="GET" action="restaurant.php" class="flex-1 mx-4">
                    <input type="hidden" name="restaurant_id" value="<?php echo htmlspecialchars($_GET['restaurant_id']); ?>">
                    <div class="flex items-center border border-gray-300 rounded-full overflow-hidden">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Search for food..." class="w-full px-4 py-2 focus:outline-none">
                        <button type="submit" class="bg-pink-600 text-white px-4 py-2 hover:bg-pink-700 transition duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="flex-1 mx-4"></div>
            <?php endif; ?>
            <div class="flex items-center space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_type'] === 'restaurant'): ?>
                        <a href="manage-food.php" class="text-gray-700 hover:text-pink-600">Manage Food</a>
                    <?php endif; ?>
                    <a href="<?php echo $_SESSION['user_type'] === 'customer' ? 'order-details.php' : ($_SESSION['user_type'] === 'restaurant' ? 'restaurant-dashboard.php' : 'delivery-dashboard.php'); ?>" class="text-gray-700 hover:text-pink-600">Dashboard</a>
                    <a href="logout.php" class="text-gray-700 hover:text-pink-600">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="text-gray-700 hover:text-pink-600">Login</a>
                    <a href="register.php" class="text-gray-700 hover:text-pink-600">Sign Up</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer'): ?>
                    <a href="cart.php" class="relative text-gray-700 hover:text-pink-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <?php if ($cartCount > 0): ?>
                            <span id="cart-count" class="absolute -top-1 -right-1 bg-pink-600 text-white text-xs rounded-full px-2 py-1"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
<?php endif; ?>