<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $user_type = $_POST['user_type'] ?? '';
    $address = trim($_POST['address'] ?? null);
    $restaurant_name = trim($_POST['restaurant_name'] ?? null);
    $vehicle_type = trim($_POST['vehicle_type'] ?? null);
    $image_url = '';

    // Validate inputs
    $errors = [];
    if (empty($name)) $errors[] = "Full name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($password) || strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if (!in_array($user_type, ['customer', 'restaurant', 'delivery_person'])) $errors[] = "Invalid user type.";
    if ($user_type === 'restaurant' && empty($restaurant_name)) $errors[] = "Restaurant name is required.";
    if ($user_type === 'delivery_person' && empty($vehicle_type)) $errors[] = "Vehicle type is required.";

    // Check for duplicate email
    $query = "SELECT COUNT(*) FROM Person WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Email already registered.";
    }

    // Handle restaurant image upload
    if ($user_type === 'restaurant' && isset($_FILES['restaurant_image']) && $_FILES['restaurant_image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        $fileType = mime_content_type($_FILES['restaurant_image']['tmp_name']);
        $fileSize = $_FILES['restaurant_image']['size'];
        $fileName = uniqid() . '_' . basename($_FILES['restaurant_image']['name']);
        $uploadDir = 'Uploads/';
        $uploadPath = $uploadDir . $fileName;

        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Only JPEG, PNG, and GIF images are allowed.";
        } elseif ($fileSize > $maxFileSize) {
            $errors[] = "Image size must not exceed 5MB.";
        } else {
            // Ensure upload directory exists
            if (!file_exists($uploadDir)) {
                $oldUmask = umask(0);
                $created = mkdir($uploadDir, 0777, true);
                umask($oldUmask);
                if (!$created) {
                    $errors[] = "Failed to create Uploads directory. Check server permissions.";
                }
            }

            // Check if directory is writable
            if (file_exists($uploadDir) && is_writable($uploadDir)) {
                if (!move_uploaded_file($_FILES['restaurant_image']['tmp_name'], $uploadPath)) {
                    $errors[] = "Failed to move uploaded file. Error: " . var_export(error_get_last(), true);
                } else {
                    $image_url = $uploadPath;
                }
            } else {
                $errors[] = "Uploads directory is not writable. Check permissions.";
            }
        }
    } elseif ($user_type === 'restaurant') {
        $errors[] = "Restaurant image is required.";
    }

    // Proceed with registration if no errors
    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Insert into Person (store password as plaintext)
            $personQuery = "INSERT INTO Person (name, email, password, phone, user_type) VALUES (?, ?, ?, ?, ?)";
            $personStmt = $conn->prepare($personQuery);
            $personStmt->execute([$name, $email, $password, $phone, $user_type]); // Store password as plaintext
            $personId = $conn->lastInsertId();

            if ($user_type === 'customer') {
                $customerQuery = "INSERT INTO Customer (person_id, address) VALUES (?, ?)";
                $customerStmt = $conn->prepare($customerQuery);
                $customerStmt->execute([$personId, $address]);
            } elseif ($user_type === 'restaurant') {
                $restaurantQuery = "INSERT INTO Restaurant (person_id, name, address, image_url) VALUES (?, ?, ?, ?)";
                $restaurantStmt = $conn->prepare($restaurantQuery);
                $restaurantStmt->execute([$personId, $restaurant_name, $address, $image_url]);
            } elseif ($user_type === 'delivery_person') {
                $deliveryQuery = "INSERT INTO DeliveryPerson (person_id, vehicle_type, availability) VALUES (?, ?, ?)";
                $deliveryStmt = $conn->prepare($deliveryQuery);
                $deliveryStmt->execute([$personId, $vehicle_type, 1]); // Use 1 for TRUE
            }

            $conn->commit();
            header("Location: login.php?success=Registration successful! Please login.");
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "Registration failed: " . htmlspecialchars($e->getMessage());
        }
    }

    if (!empty($errors)) {
        $error = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - DhakaMeal</title>
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
        .loading-spinner {
            display: none;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #db2777;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Sign Up</h1>
                <?php if (isset($error)): ?>
                    <p class="text-red-600 mb-4"><?php echo $error; ?></p>
                <?php endif; ?>
                <form id="register-form" method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="block text-gray-700">Full Name</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700">Email</label>
                        <input type="email" name="email" required class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700">Password</label>
                        <input type="password" name="password" required class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700">Phone</label>
                        <input type="text" name="phone" required class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700">User Type</label>
                        <select name="user_type" id="user-type" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                            <option value="customer">Customer</option>
                            <option value="restaurant">Restaurant</option>
                            <option value="delivery_person">Delivery Person</option>
                        </select>
                    </div>
                    <div id="customer-fields" class="mb-4">
                        <label class="block text-gray-700">Address</label>
                        <input type="text" name="address" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div id="restaurant-fields" class="mb-4 hidden">
                        <label class="block text-gray-700">Restaurant Name</label>
                        <input type="text" name="restaurant_name" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <label class="block text-gray-700 mt-4">Address</label>
                        <input type="text" name="address" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <label class="block text-gray-700 mt-4">Restaurant Image</label>
                        <input type="file" name="restaurant_image" accept="image/*" class="w-full px-4 py-2 border rounded-md">
                    </div>
                    <div id="delivery-fields" class="mb-4 hidden">
                        <label class="block text-gray-700">Vehicle Type</label>
                        <input type="text" name="vehicle_type" placeholder="e.g., Motorcycle" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                    <button type="submit" class="w-full bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700">Sign Up</button>
                    <div class="loading-spinner mt-4" id="loading-spinner"></div>
                </form>
                <p class="mt-4 text-center text-gray-600">Already have an account? <a href="login.php" class="text-pink-600 hover:text-pink-500">Login</a></p>
            </div>
        </div>
    </div>

    <?php include_once 'includes/footer.php'; ?>
</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const userType = document.getElementById('user-type');
    const customerFields = document.getElementById('customer-fields');
    const restaurantFields = document.getElementById('restaurant-fields');
    const deliveryFields = document.getElementById('delivery-fields');
    const form = document.getElementById('register-form');
    const submitButton = form.querySelector('button[type="submit"]');
    const loadingSpinner = document.getElementById('loading-spinner');

    userType.addEventListener('change', () => {
        customerFields.classList.add('hidden');
        restaurantFields.classList.add('hidden');
        deliveryFields.classList.add('hidden');
        if (userType.value === 'customer') {
            customerFields.classList.remove('hidden');
        } else if (userType.value === 'restaurant') {
            restaurantFields.classList.remove('hidden');
        } else if (userType.value === 'delivery_person') {
            deliveryFields.classList.remove('hidden');
        }
    });

    form.addEventListener('submit', () => {
        submitButton.disabled = true;
        loadingSpinner.style.display = 'block';
    });
});
</script>

<?php
// Close the database connection
$conn = null;
?>