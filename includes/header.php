<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DhakaMeal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-pink-600">DhakaMeal</a>
            <div class="space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_type'] === 'customer'): ?>
                        <a href="index.php" class="text-gray-600 hover:text-pink-600 transition duration-300">Home</a>
                        <a href="cart.php" class="text-gray-600 hover:text-pink-600 transition duration-300">Cart</a>
                        <a href="order-details.php" class="text-gray-600 hover:text-pink-600 transition duration-300">Orders</a>
                    <?php elseif ($_SESSION['user_type'] === 'restaurant'): ?>
                        <a href="index.php" class="text-gray-600 hover:text-pink-600 transition duration-300">Home</a>
                        <a href="restaurant-dashboard.php" class="text-gray-600 hover:text-pink-600 transition duration-300">Dashboard</a>
                    <?php elseif ($_SESSION['user_type'] === 'delivery_person'): ?>
                        <a href="index.php" class="text-gray-600 hover:text-pink-600 transition duration-300">Home</a>
                        <a href="delivery-dashboard.php" class="text-gray-600 hover:text-pink-600 transition duration-300">Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php" class="text-gray-600 hover:text-pink-600 transition duration-300">Logout</a>
                <?php else: ?>
                    <a href="index.php" class="text-gray-600 hover:text-pink-600 transition duration-300">Home</a>
                    <a href="login.php" class="text-gray-600 hover:text-pink-600 transition duration-300">Login</a>
                    <a href="register.php" class="text-gray-600 hover:text-pink-600 transition duration-300">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>