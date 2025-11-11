<?php
// Database Configuration
define('DB_SERVER', '127.0.0.1'); // Or 'localhost'
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'sports_management1');

// Create a database connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for full Unicode support
$conn->set_charset("utf8mb4");
?>