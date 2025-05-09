<?php
include_once 'config/database.php';
include_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $user_type = $_POST['user_type'];
    $address = $_POST['address'] ?? null;
    $restaurant_name = $_POST['restaurant_name'] ?? null;
    $vehicle_type = $_POST['vehicle_type'] ?? null;

    try {
        $conn->beginTransaction();

        // Insert into Person
        $personQuery = "INSERT INTO Person (name, email, password, phone, user_type) VALUES (?, ?, ?, ?, ?)";
        $personStmt = $conn->prepare($personQuery);
        $personStmt->execute([$name, $email, $password, $phone, $user_type]);
        $personId = $conn->lastInsertId();

        if ($user_type === 'customer') {
            $customerQuery = "INSERT INTO Customer (person_id, address) VALUES (?, ?)";
            $customerStmt = $conn->prepare($customerQuery);
            $customerStmt->execute([$personId, $address]);
        } elseif ($user_type === 'restaurant') {
            $restaurantQuery = "INSERT INTO Restaurant (person_id, name, address) VALUES (?, ?, ?)";
            $restaurantStmt = $conn->prepare($restaurantQuery);
            $restaurantStmt->execute([$personId, $restaurant_name, $address]);
        } elseif ($user_type === 'delivery_person') {
            $deliveryQuery = "INSERT INTO DeliveryPerson (person_id, vehicle_type, availability) VALUES (?, ?, ?)";
            $deliveryStmt = $conn->prepare($deliveryQuery);
            $deliveryStmt->execute([$personId, $vehicle_type, TRUE]);
        }

        $conn->commit();
        header("Location: login.php?success=Registration successful! Please login.");
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Sign Up</h1>
        <?php if (isset($error)): ?>
            <p class="text-red-600 mb-4"><?php echo $error; ?></p>
        <?php endif; ?>
        <form id="register-form" method="POST">
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
                <input type="text" name="phone" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
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

<?php include_once 'includes/footer.php'; ?>
<?php
// Close the database connection
$conn = null;
?>  