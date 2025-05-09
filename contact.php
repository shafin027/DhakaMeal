<?php
session_start();
include_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Contact Us</h1>
    <p class="text-gray-600 mb-4">Have questions or need assistance? Reach out to us!</p>
    <p class="text-gray-600 mb-4">Email: support@dhakameal.com</p>
    <p class="text-gray-600 mb-4">Phone: +880 1234 567 890</p>
    <p class="text-gray-600 mb-4">Address: 123 Food Street, Dhaka, Bangladesh</p>
    <div class="mt-6">
        <a href="index.php" class="inline-block bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700 transition duration-300">Back to Home</a>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>