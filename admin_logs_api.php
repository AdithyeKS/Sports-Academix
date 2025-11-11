<?php
// File: admin_logs_api.php
header('Content-Type: application/json');
session_start();
require 'db_connection.php';

// Security: Only logged-in admins can view logs
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

if ($action === 'get_logs') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15; // Number of logs to show per page
    $offset = ($page - 1) * $limit;

    // Get the total number of logs for pagination
    $total_logs = $pdo->query("SELECT count(*) FROM admin_logs")->fetchColumn();
    
    // Fetch the logs for the current page, joining to get the admin's name
    $stmt = $pdo->prepare(
        "SELECT al.log_id, al.action, al.timestamp, u.name as admin_name
         FROM admin_logs al
         JOIN users u ON al.admin_id = u.user_id
         ORDER BY al.timestamp DESC
         LIMIT :limit OFFSET :offset"
    );

    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
    // Return the logs along with pagination info
    echo json_encode([
        'status' => 'success',
        'data' => [
            'logs' => $logs,
            'total' => $total_logs,
            'page' => $page,
            'limit' => $limit
        ]
    ]);
}
?>