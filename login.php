<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';

// Display success message from registration
if (isset($_GET['success'])) {
    echo "<p class='text-green-600 text-center'>" . htmlspecialchars($_GET['success']) . "</p>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $errors = [];

    if (empty($email)) $errors[] = "Email is required.";
    if (empty($password)) $errors[] = "Password is required.";

    if (empty($errors)) {
        try {
            $query = "SELECT person_id, email, password, user_type FROM Person WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $password === $user['password']) { // Compare plaintext passwords
                $_SESSION['user_id'] = $user['person_id'];
                $_SESSION['user_type'] = $user['user_type'];

                // Redirect based on user type
                if ($user['user_type'] === 'customer') {
                    header('Location: index.php');
                } elseif ($user['user_type'] === 'restaurant') {
                    header('Location: restaurant-dashboard.php');
                } elseif ($user['user_type'] === 'delivery_person') {
                    header('Location: delivery-dashboard.php');
                } else {
                    $errors[] = "Invalid user type: " . htmlspecialchars($user['user_type']);
                }
                exit;
            } else {
                $errors[] = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }

    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "<p class='text-red-600 text-center'>$error</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DhakaMeal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
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
        <div class="container mx-auto px-4 py-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">Login to DhakaMeal</h1>
            <div class="max-w-md mx-auto bg-white shadow rounded-lg p-6">
                <form method="POST">
                    <div class="mb-4">
                        <label for="email" class="block text-gray-700">Email</label>
                        <input type="email" name="email" id="email" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
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
    </div>

    <?php include_once 'includes/footer.php'; ?>
</body>
</html>

<?php
$conn = null;
?>