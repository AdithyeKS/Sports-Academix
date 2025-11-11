<?php
// File: db_connection.php

// --- Database Configuration ---
$db_host = 'localhost';
$db_name = 'sports_management1';
$db_user = 'root';
$db_pass = '';
$charset = 'utf8mb4';

// --- PDO Connection Options ---
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// --- Create PDO instance ---
try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=$charset";
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Stop script execution if connection fails. In a production environment,
    // you should log this error to a file instead of echoing it.
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

/**
 * Logs an action performed by an administrator.
 * @param PDO $pdo The database connection object.
 * @param string $actionMessage The message to log.
 */
function logAction(PDO $pdo, string $actionMessage) {
    // In a real application, this ID should be retrieved from the user's session.
    // For example: $adminId = $_SESSION['user_id'] ?? 1;
    $adminId = 1; 
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action) VALUES (?, ?)");
        $stmt->execute([$adminId, $actionMessage]);
    } catch (Exception $e) {
        // Silently fail or log to a file. Don't let logging break the main application flow.
    }
}

/**
 * Gets the JSON input from the request body.
 * @return array The decoded JSON data.
 */
function getPostData() {
    return json_decode(file_get_contents('php://input'), true);
}
?>