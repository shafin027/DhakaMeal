<?php
$host = 'localhost';
$db = 'dhakameal';
$user = 'root';
$pass = ''; // No password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
    $conn = new PDO($dsn, $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("Database connection successful: host=$host, db=$db");
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>