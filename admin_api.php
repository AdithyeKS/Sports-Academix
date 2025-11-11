<?php
// --- admin_api.php ---

// Include the database connection file
require_once 'db_connection.php';

// Set the response header to indicate JSON content
header('Content-Type: application/json');

// Get the action parameter from the request
$action = $_GET['action'] ?? '';

// Simple routing based on the 'action' parameter
switch ($action) {
    case 'get_all_data':
        fetchAllData($pdo);
        break;

    // --- You would add more cases here for each action (add, edit, delete) ---
    // Example:
    // case 'add_user':
    //    addUser($pdo);
    //    break;
    
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
        http_response_code(400);
}

/**
 * Fetches all necessary data for the admin dashboard from the database.
 * This includes stats, users, sports, events, registrations, etc.
 * @param PDO $pdo The database connection object.
 */
function fetchAllData(PDO $pdo) {
    try {
        $response = [];

        // --- Dashboard Stats ---
        $response['stats']['totalUsers'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $response['stats']['activeSports'] = $pdo->query("SELECT COUNT(*) FROM sports WHERE status = 'active'")->fetchColumn();
        $response['stats']['upcomingEvents'] = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'scheduled' AND event_date > NOW()")->fetchColumn();
        $response['stats']['pendingRegistrations'] = $pdo->query("SELECT COUNT(*) FROM registrations WHERE status = 'pending'")->fetchColumn();

        // --- Tables Data ---
        $response['users'] = $pdo->query("SELECT user_id, name, email, student_id, role, status, created_at FROM users ORDER BY created_at DESC")->fetchAll();
        $response['sports'] = $pdo->query("SELECT sport_id, name, max_players, status FROM sports ORDER BY name ASC")->fetchAll();
        
        // Fetch Events with Sport Name
        $response['events'] = $pdo->query("
            SELECT e.*, s.name as sport_name 
            FROM events e
            JOIN sports s ON e.sport_id = s.sport_id
            ORDER BY e.event_date DESC
        ")->fetchAll();

        // Fetch Registrations with User and Event names
        $response['registrations'] = $pdo->query("
            SELECT r.registration_id, u.name as user_name, e.event_name, r.status, r.registered_at
            FROM registrations r
            JOIN users u ON r.user_id = u.user_id
            JOIN events e ON r.event_id = e.event_id
            WHERE r.status = 'pending'
            ORDER BY r.registered_at DESC
        ")->fetchAll();
        
        // Fetch Teams with Sport and Captain names
        $response['teams'] = $pdo->query("
            SELECT 
                t.team_id, t.team_name, s.name as sport_name, 
                (SELECT u.name FROM team_members tm JOIN users u ON tm.user_id = u.user_id WHERE tm.team_id = t.team_id AND tm.role_in_team = 'Captain' LIMIT 1) as captain_name,
                (SELECT COUNT(*) FROM team_members WHERE team_id = t.team_id) as player_count
            FROM teams t
            JOIN sports s ON t.sport_id = s.sport_id
            ORDER BY t.team_name ASC
        ")->fetchAll();

        // For simplicity, we'll combine Scores/Results into the Events logic on the front-end
        // The `events` fetch already includes score columns

        // Fetch Admin Logs with Admin Name
        $response['adminLogs'] = $pdo->query("
            SELECT a.log_id, u.name as admin_name, a.action, a.timestamp
            FROM admin_logs a
            JOIN users u ON a.admin_id = u.user_id
            ORDER BY a.timestamp DESC
            LIMIT 20
        ")->fetchAll();

        // Fetch Feedback with User Name
        $response['feedback'] = $pdo->query("
            SELECT f.*, u.name as user_name, u.email as user_email
            FROM feedback f
            JOIN users u ON f.user_id = u.user_id
            ORDER BY f.submitted_at DESC
        ")->fetchAll();

        // --- Log Action Helper (Example) ---
        // You can create a function to log admin actions like this
        // logAction($pdo, 1, 'Fetched all dashboard data.'); // Assuming admin user_id is 1

        // Send the successful response
        echo json_encode(['status' => 'success', 'data' => $response]);

    } catch (PDOException $e) {
        // Handle potential SQL errors
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch data: ' . $e->getMessage()]);
    }
}

// NOTE: In a real application, you would create separate functions for each C-U-D action
// (Create, Update, Delete) and call them from the switch statement above.
// For example: addUser(), updateUser(), deleteUser(), addSport(), etc.
// These functions would receive data from $_POST, use prepared statements to prevent
// SQL injection, and execute the queries.

?>