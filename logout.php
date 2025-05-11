<?php
session_start();

error_log("Logout initiated: user_id=" . ($_SESSION['user_id'] ?? 'none') . ", user_type=" . ($_SESSION['user_type'] ?? 'none'));

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?>