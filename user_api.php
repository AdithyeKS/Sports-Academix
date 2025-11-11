<?php
// File: user_api.php
header('Content-Type: application/json');
require_once 'config.php';

function send_json_response($status, $message, $data = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit();
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

switch ($action) {
    case 'get_users':
        $result = $conn->query("SELECT user_id, name, email, student_id, role, status FROM users ORDER BY created_at DESC");
        $users = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        send_json_response('success', 'Users fetched.', $users);
        break;

    case 'delete_user':
        $id = $input['id'] ?? 0;
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                send_json_response('success', 'User deleted successfully.');
            } else {
                send_json_response('error', 'Failed to delete user.');
            }
            $stmt->close();
        } else {
            send_json_response('error', 'Invalid user ID.');
        }
        break;
        
    default:
        send_json_response('error', 'Invalid action.');
        break;
}
$conn->close();
?>