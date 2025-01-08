<?php
// Database Configuration
$host = 'localhost'; // Database host
$dbname = 'budgetapp'; // Database name
$username = 'root'; // Database username
$password = 'ds2046'; // Database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set error mode to exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
