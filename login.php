<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        echo "<p class='text-red-600 text-center'>Email and password are required.</p>";
    } else {
        $query = "SELECT person_id, email, password, user_type FROM Person WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $password === $user['password']) { // Plaintext comparison for simplicity; use password_hash() in production
            $_SESSION['user_id'] = $user['person_id'];
            $_SESSION['user_type'] = $user['user_type'];

            // Redirect based on user type
            if ($user['user_type'] === 'customer') {
                header('Location: index.php');
            } elseif ($user['user_type'] === 'restaurant') {
                header('Location: restaurant-dashboard.php');
            } elseif ($user['user_type'] === 'delivery_person') {
                header('Location: delivery-dashboard.php');
            }
            exit;
        } else {
            echo "<p class='text-red-600 text-center'>Invalid email or password.</p>";
        }
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">Login to DhakaMeal</h1>
    <div class="max-w-md mx-auto bg-white shadow rounded-lg p-6">
        <form method="POST">
            <div class="mb-4">
                <label for="email" class="block text-gray-700">Email</label>
                <input type="email" name="email" id="email" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700">Password</label>
                <input type="password" name="password" id="password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
            </div>
            <button type="submit" class="w-full bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 transition duration-300">Login</button>
        </form>
        <p class="mt-4 text-center">Don't have an account? <a href="register.php" class="text-pink-600 hover:underline">Register here</a></p>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>