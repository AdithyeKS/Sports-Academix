<?php
// FILE: api.php (Backend Logic for Admin Panel)

header('Content-Type: application/json');

// --- 1. DATABASE CONNECTION ---
require_once 'config.php'; 

// --- 2. SESSION & HELPERS ---
session_start();

function send_json_response($status, $message, $data = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit();
}

// --- 3. SECURITY CHECK ---
// Define endpoints that *any* logged-in user can access.
// In this admin panel context, most are restricted, but some might be universally accessible (e.g., getting specific event details).
$user_accessible_endpoints = [
    'events/get_event_dates',
    'events/get_events_by_date',
    'teams/get_team_members', // For fetching members of a specific team
    'teams/get_all_team_members' // For client-side validation logic in admin panel
];

$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['role'] ?? 'guest';
$endpoint_requested = $_GET['endpoint'] ?? '';
$action_requested = $_GET['action'] ?? '';
$full_endpoint_action = ($endpoint_requested && $action_requested) ? $endpoint_requested . '/' . $action_requested : '';

// First, check if *any* user is logged in for any API call.
if (!isset($_SESSION['loggedin']) || !$user_id) {
    http_response_code(401); // Unauthorized
    send_json_response('error', 'User not logged in.');
}

// Second, if the user is *not* an admin, check if the requested endpoint/action is explicitly allowed for non-admins.
// If not an admin AND the endpoint/action is NOT in the user_accessible_endpoints list, then deny access.
if ($user_role !== 'admin' && !in_array($full_endpoint_action, $user_accessible_endpoints)) {
    http_response_code(403); // Forbidden
    send_json_response('error', 'Unauthorized: Admin access required for this action.');
}


// --- 4. ROUTER ---
$endpoint = $_GET['endpoint'] ?? '';
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

switch ($endpoint) {
    case 'dashboard': handle_dashboard($conn); break;
    case 'users': handle_users($conn, $action, $input); break;
    case 'departments': handle_departments($conn, $action, $input); break;
    case 'sports': handle_sports($conn, $action, $input); break;
    case 'events': handle_events($conn, $action, $input); break;
    case 'registrations': handle_registrations($conn, $action, $input); break;
    case 'teams': handle_teams($conn, $action, $input); break;
    case 'feedback': handle_feedback($conn, $action, $input); break;
    case 'appeals': handle_appeals($conn, $action, $input); break;
    default: send_json_response('error', 'Invalid API endpoint specified.'); break;
}

// --- 5. LOGIC HANDLERS (ALL FUNCTIONS) ---

function handle_dashboard($conn) {
    $stats = [];
    $result = $conn->query("SELECT COUNT(user_id) as total FROM users WHERE role = 'user'");
    $stats['totalUsers'] = $result ? (int)$result->fetch_assoc()['total'] : 0;
    $result = $conn->query("SELECT COUNT(sport_id) as total FROM sports WHERE status = 'active'");
    $stats['activeSports'] = $result ? (int)$result->fetch_assoc()['total'] : 0;
    $result = $conn->query("SELECT COUNT(event_id) as total FROM events WHERE status = 'schedule' AND event_date >= NOW()");
    $stats['upcomingEvents'] = $result ? (int)$result->fetch_assoc()['total'] : 0;
    $result = $conn->query("SELECT COUNT(registration_id) as total FROM registrations WHERE status = 'pending'");
    $stats['pendingRegistrations'] = $result ? (int)$result->fetch_assoc()['total'] : 0;
    send_json_response('success', 'Dashboard stats fetched.', ['stats' => $stats]);
}

function handle_users($conn, $action, $input) {
    switch ($action) {
        case 'get_users':
            $role_filter = $_GET['role'] ?? '';
            $sql = "SELECT user_id, name, email, student_id, role, status FROM users";
            $params = [];
            $types = "";

            if ($role_filter === 'all') {
                // No role filter applied, fetch all users for dropdowns
            } elseif ($role_filter === 'admin') {
                $sql .= " WHERE role = ?";
                $params[] = 'admin';
                $types .= "s";
            } else { // Default to 'user' if no specific role or empty filter (for main user list)
                $sql .= " WHERE role = ?";
                $params[] = 'user';
                $types .= "s";
            }
            
            $sql .= " ORDER BY name ASC"; // Order by name for better usability

            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            send_json_response('success', 'Users fetched.', $result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'delete_user':
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            if ($id > 0) {
                if ($id == ($_SESSION['user_id'] ?? 0)) {
                    send_json_response('error', 'You cannot delete your own account.');
                }
                // Prevent deleting an admin account through this route if the current user is not the super admin or for security
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role != 'admin'"); 
                $stmt->bind_param("i", $id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    send_json_response('success', 'User deleted successfully.');
                } else {
                    send_json_response('error', 'User not found or is an admin account and cannot be deleted via this interface.');
                }
            } else {
                send_json_response('error', 'Invalid User ID.');
            }
            break;
        case 'update_user_status':
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            $status = $input['status'] ?? ''; // 'active' or 'blocked'

            if (!$id || !in_array($status, ['active', 'blocked'])) {
                send_json_response('error', 'Invalid User ID or status provided.');
            }
            if ($id == ($_SESSION['user_id'] ?? 0) && $status === 'blocked') {
                send_json_response('error', 'You cannot block your own account.');
            }
            
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
            $stmt->bind_param("si", $status, $id);
            if (!$stmt->execute()) {
                send_json_response('error', 'Failed to update user status: ' . $stmt->error);
            }
            if ($stmt->affected_rows > 0) {
                send_json_response('success', 'User status updated successfully.');
            } else {
                send_json_response('error', 'User not found or status already set.');
            }
            break;
    }
}

function handle_departments($conn, $action, $input) {
    switch ($action) {
        case 'get_departments':
            $result = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");
            send_json_response('success', 'Departments fetched.', $result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'add_department':
            $name = trim($input['department_name'] ?? '');
            if (empty($name)) { send_json_response('error', 'Department name cannot be empty.'); }
            if (strlen($name) > 100) { send_json_response('error', 'Department name is too long (max 100 characters).'); }
            
            $stmt = $conn->prepare("INSERT INTO departments (department_name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if (!$stmt->execute()) { 
                if ($conn->errno == 1062) { // Duplicate entry error code
                    send_json_response('error', 'Failed to add department. A department with this name already exists.'); 
                }
                send_json_response('error', 'Failed to add department: ' . $stmt->error); 
            }
            send_json_response('success', 'Department added successfully.');
            break;
        case 'update_department':
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            $name = trim($input['department_name'] ?? '');
            if (!$id) { send_json_response('error', 'Invalid department ID.'); }
            if (empty($name)) { send_json_response('error', 'Department name cannot be empty.'); }
            if (strlen($name) > 100) { send_json_response('error', 'Department name is too long (max 100 characters).'); }
            
            $stmt = $conn->prepare("UPDATE departments SET department_name = ? WHERE department_id = ?");
            $stmt->bind_param("si", $name, $id);
            if (!$stmt->execute()) { 
                 if ($conn->errno == 1062) { // Duplicate entry error code
                    send_json_response('error', 'Failed to update department. A department with this name already exists.'); 
                }
                send_json_response('error', 'Failed to update department: ' . $stmt->error); 
            }
            if ($stmt->affected_rows === 0) { send_json_response('error', "No changes were made. The department may not exist or the name was the same."); }
            send_json_response('success', 'Department updated successfully.');
            break;
        case 'delete_department':
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM departments WHERE department_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    send_json_response('success', 'Department deleted successfully.');
                } else {
                    send_json_response('error', 'Department not found.');
                }
            } else {
                send_json_response('error', 'Invalid Department ID.');
            }
            break;
    }
}

function handle_sports($conn, $action, $input) {
    switch ($action) {
        case 'get_sports':
            $result = $conn->query("SELECT * FROM sports ORDER BY name ASC");
            send_json_response('success', 'Sports fetched.', $result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'add_sport':
            $name = trim($input['name'] ?? '');
            if (empty($name)) { send_json_response('error', 'Sport name cannot be empty.'); }
            if (strlen($name) > 100) { send_json_response('error', 'Sport name is too long (max 100 characters).'); }
            
            $max_players = filter_var($input['max_players'] ?? 0, FILTER_VALIDATE_INT);
            $points_win = filter_var($input['points_for_win'] ?? 3, FILTER_VALIDATE_INT);
            $points_draw = filter_var($input['points_for_draw'] ?? 1, FILTER_VALIDATE_INT);
            $points_loss = filter_var($input['points_for_loss'] ?? 0, FILTER_VALIDATE_INT);
            $status = in_array($input['status'], ['active', 'inactive']) ? $input['status'] : 'inactive';
            
            if ($max_players === false || $max_players < 1) { send_json_response('error', 'Max players must be a number greater than 0.'); }

            $stmt = $conn->prepare("INSERT INTO sports (name, max_players, points_for_win, points_for_draw, points_for_loss, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siiiis", $name, $max_players, $points_win, $points_draw, $points_loss, $status);
            if (!$stmt->execute()) { 
                send_json_response('error', 'Failed to add sport: ' . $stmt->error); 
            }
            send_json_response('success', 'Sport added successfully.');
            break;
        case 'update_sport':
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            $name = trim($input['name'] ?? '');
            if (!$id) { send_json_response('error', 'Invalid sport ID.'); }
            if (empty($name)) { send_json_response('error', 'Sport name cannot be empty.'); }
            if (strlen($name) > 100) { send_json_response('error', 'Sport name is too long (max 100 characters).'); }

            $max_players = filter_var($input['max_players'] ?? 0, FILTER_VALIDATE_INT);
            $points_win = filter_var($input['points_for_win'] ?? 3, FILTER_VALIDATE_INT);
            $points_draw = filter_var($input['points_for_draw'] ?? 1, FILTER_VALIDATE_INT);
            $points_loss = filter_var($input['points_for_loss'] ?? 0, FILTER_VALIDATE_INT);
            $status = in_array($input['status'], ['active', 'inactive']) ? $input['status'] : 'inactive';

            if ($max_players === false || $max_players < 1) { send_json_response('error', 'Max players must be a number greater than 0.'); }

            $stmt = $conn->prepare("UPDATE sports SET name = ?, max_players = ?, points_for_win = ?, points_for_draw = ?, points_for_loss = ?, status = ? WHERE sport_id = ?");
            $stmt->bind_param("siiiisi", $name, $max_players, $points_win, $points_draw, $points_loss, $status, $id);
            if (!$stmt->execute()) { send_json_response('error', 'Failed to update sport: ' . $stmt->error); }
            if ($stmt->affected_rows === 0) { send_json_response('error', "No changes were made. The sport may not exist or the data was the same."); }
            send_json_response('success', 'Sport updated successfully.');
            break;
        case 'delete_sport':
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM sports WHERE sport_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    send_json_response('success', 'Sport deleted successfully.');
                } else {
                    send_json_response('error', 'Sport not found.');
                }
            } else {
                send_json_response('error', 'Invalid Sport ID.');
            }
            break;
    }
}

function handle_events($conn, $action, $input) {
    switch ($action) {
        case 'get_events':
            $sql = "SELECT e.*, s.name as sport_name, t1.team_name as team1_name, t2.team_name as team2_name FROM events e LEFT JOIN sports s ON e.sport_id = s.sport_id LEFT JOIN teams t1 ON e.team1_id = t1.team_id LEFT JOIN teams t2 ON e.team2_id = t2.team_id ORDER BY e.event_date DESC";
            $result = $conn->query($sql);
            send_json_response('success', 'All events fetched.', $result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'get_upcoming_events':
            $sql = "SELECT e.*, s.name as sport_name, t1.team_name as team1_name, t2.team_name as team2_name FROM events e JOIN sports s ON e.sport_id = s.sport_id LEFT JOIN teams t1 ON e.team1_id = t1.team_id LEFT JOIN teams t2 ON e.team2_id = t2.team_id WHERE e.status IN ('schedule', 'ongoing') AND e.event_date > NOW() ORDER BY e.event_date ASC LIMIT 5";
            $result = $conn->query($sql);
            send_json_response('success', 'Upcoming events fetched.', $result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'get_event_dates': // Action to fetch distinct event dates for calendar
            $stmt = $conn->prepare("SELECT DISTINCT DATE_FORMAT(event_date, '%Y-%m-%d') as event_date_only FROM events WHERE status IN ('schedule', 'ongoing') AND event_date >= CURDATE() ORDER BY event_date ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            $dates = [];
            while ($row = $result->fetch_assoc()) {
                $dates[] = $row['event_date_only'];
            }
            send_json_response('success', 'Event dates fetched.', $dates);
            break;
        case 'get_events_by_date': // NEW: Action to fetch event details for a specific date
            $date = $_GET['date'] ?? ''; // Format YYYY-MM-DD
            if (empty($date) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                send_json_response('error', 'Invalid date format. Expected YYYY-MM-DD.');
            }

            $sql = "SELECT 
                        e.event_name, 
                        s.name as sport_name, 
                        DATE_FORMAT(e.event_date, '%h:%i %p') as event_time,
                        t1.team_name as team1_name, 
                        t2.team_name as team2_name,
                        e.venue 
                    FROM events e 
                    LEFT JOIN sports s ON e.sport_id = s.sport_id 
                    LEFT JOIN teams t1 ON e.team1_id = t1.team_id 
                    LEFT JOIN teams t2 ON e.team2_id = t2.team_id 
                    WHERE DATE(e.event_date) = ? AND e.status IN ('schedule', 'ongoing')
                    ORDER BY e.event_date ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $result = $stmt->get_result();
            send_json_response('success', "Events for {$date} fetched.", $result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'add_event':
            $event_name = trim($input['event_name'] ?? '');
            $venue = trim($input['venue'] ?? '');
            if (empty($event_name) || empty($venue)) { send_json_response('error', 'Event name and venue are required.'); }
            
            $sport_id = filter_var($input['sport_id'] ?? 0, FILTER_VALIDATE_INT);
            $event_date = $input['event_date'] ?? '';
            $description = $input['description'] ?? null; // Allow null description
            $team1_id = filter_var($input['team1_id'] ?? 0, FILTER_VALIDATE_INT);
            $team2_id = filter_var($input['team2_id'] ?? 0, FILTER_VALIDATE_INT);
            
            if (!$sport_id || !$team1_id || !$team2_id) { send_json_response('error', 'A valid sport and two teams must be selected.'); }
            if ($team1_id === $team2_id) { send_json_response('error', 'A team cannot play against itself.'); }

            $stmt = $conn->prepare("INSERT INTO events (event_name, sport_id, event_date, venue, description, team1_id, team2_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'schedule')");
            $stmt->bind_param("sisssii", $event_name, $sport_id, $event_date, $venue, $description, $team1_id, $team2_id);
            if (!$stmt->execute()) { send_json_response('error', 'Failed to create event: ' . $stmt->error); }
            send_json_response('success', 'Event created successfully.');
            break;
        case 'update_event':
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            $event_name = trim($input['event_name'] ?? '');
            $venue = trim($input['venue'] ?? '');
            
            if (!$id) { send_json_response('error', 'Invalid event ID.'); }
            if (empty($event_name) || empty($venue)) { send_json_response('error', 'Event name and venue are required.'); }
            
            $sport_id = filter_var($input['sport_id'] ?? 0, FILTER_VALIDATE_INT);
            $event_date = $input['event_date'] ?? '';
            $description = $input['description'] ?? null;
            $team1_id = filter_var($input['team1_id'] ?? 0, FILTER_VALIDATE_INT);
            $team2_id = filter_var($input['team2_id'] ?? 0, FILTER_VALIDATE_INT);
            $status = in_array($input['status'], ['schedule','ongoing','completed','postponed','cancelled']) ? $input['status'] : 'schedule';

            if (!$sport_id || !$team1_id || !$team2_id) { send_json_response('error', 'A valid sport and two teams must be selected.'); }
            if ($team1_id === $team2_id) { send_json_response('error', 'A team cannot play against itself.'); }

            $stmt = $conn->prepare("UPDATE events SET event_name = ?, sport_id = ?, event_date = ?, venue = ?, description = ?, team1_id = ?, team2_id = ?, status = ? WHERE event_id = ?");
            $stmt->bind_param("sisssiisi", $event_name, $sport_id, $event_date, $venue, $description, $team1_id, $team2_id, $status, $id);
            if (!$stmt->execute()) { send_json_response('error', 'Failed to update event: ' . $stmt->error); }
            if ($stmt->affected_rows === 0) { send_json_response('error', "No changes were made. The event may not exist or the data was the same."); }
            send_json_response('success', 'Event updated successfully.');
            break;
        case 'delete_event':
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    send_json_response('success', 'Event deleted successfully.');
                } else {
                    send_json_response('error', 'Event not found.');
                }
            } else {
                send_json_response('error', 'Invalid Event ID.');
            }
            break;
        case 'update_score':
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            $score1 = filter_var($input['team1_score'] ?? 0, FILTER_VALIDATE_INT);
            $score2 = filter_var($input['team2_score'] ?? 0, FILTER_VALIDATE_INT);
            
            if ($id === false || $score1 === false || $score2 === false || $score1 < 0 || $score2 < 0) {
                send_json_response('error', 'Invalid event ID or scores provided. Scores cannot be negative.');
            }
            
            $result_text = ($score1 > $score2) ? 'team1_win' : (($score2 > $score1) ? 'team2_win' : 'draw');
            $stmt = $conn->prepare("UPDATE events SET team1_score = ?, team2_score = ?, result = ?, status = 'completed' WHERE event_id = ?");
            $stmt->bind_param("iisi", $score1, $score2, $result_text, $id);
            if (!$stmt->execute()) { send_json_response('error', 'Failed to update score: ' . $stmt->error); }
            if ($stmt->affected_rows === 0) { send_json_response('error', "Score not updated for Match ID: {$id}. The ID may be invalid or the scores were the same as before."); }
            
            send_json_response('success', 'Score updated and event finalized.');
            break;
    }
}

function handle_registrations($conn, $action, $input) {
    switch ($action) {
        case 'get_pending_registrations':
            $sql = "SELECT r.registration_id, r.user_id, r.sport_id, r.team_id, u.name AS user_name, s.name AS sport_name, r.registered_at, CASE WHEN r.team_id IS NOT NULL THEN t.team_name ELSE r.department END AS registration_detail, CASE WHEN r.team_id IS NOT NULL THEN 'Team' ELSE 'Department' END AS registration_type FROM registrations r JOIN users u ON r.user_id = u.user_id LEFT JOIN sports s ON r.sport_id = s.sport_id LEFT JOIN teams t ON r.team_id = t.team_id WHERE r.status = 'pending' ORDER BY r.registered_at DESC";
            $result = $conn->query($sql);
            send_json_response('success', 'Pending registrations fetched.', $result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'get_approved_registrations':
            $sql = "SELECT r.registration_id, r.user_id, r.sport_id, r.team_id, u.name AS user_name, s.name AS sport_name, r.registered_at, CASE WHEN r.team_id IS NOT NULL THEN t.team_name ELSE r.department END AS registration_detail, CASE WHEN r.team_id IS NOT NULL THEN 'Team' ELSE 'Department' END AS registration_type FROM registrations r JOIN users u ON r.user_id = u.user_id LEFT JOIN sports s ON r.sport_id = s.sport_id LEFT JOIN teams t ON r.team_id = t.team_id WHERE r.status = 'approved' ORDER BY r.registered_at DESC";
            $result = $conn->query($sql);
            send_json_response('success', 'Approved registrations fetched.', $result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'get_rejected_registrations': // NEW ACTION
            $sql = "SELECT r.registration_id, r.user_id, r.sport_id, r.team_id, u.name AS user_name, s.name AS sport_name, r.registered_at, CASE WHEN r.team_id IS NOT NULL THEN t.team_name ELSE r.department END AS registration_detail, CASE WHEN r.team_id IS NOT NULL THEN 'Team' ELSE 'Department' END AS registration_type FROM registrations r JOIN users u ON r.user_id = u.user_id LEFT JOIN sports s ON r.sport_id = s.sport_id LEFT JOIN teams t ON r.team_id = t.team_id WHERE r.status = 'rejected' ORDER BY r.registered_at DESC";
            $result = $conn->query($sql);
            send_json_response('success', 'Rejected registrations fetched.', $result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'approve_reg':
            $registration_id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$registration_id) {
                send_json_response('error', 'Invalid registration ID.');
            }

            // Start transaction
            $conn->begin_transaction();

            try {
                // 1. Fetch registration details
                $stmt = $conn->prepare("SELECT user_id, sport_id, team_id FROM registrations WHERE registration_id = ? AND status = 'pending'");
                $stmt->bind_param("i", $registration_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $registration = $result->fetch_assoc();
                $stmt->close();

                if (!$registration) {
                    throw new Exception('Registration not found or already processed.');
                }

                $user_id = $registration['user_id'];
                // $registered_sport_id = $registration['sport_id']; // This is the sport the user *registered* for (might be redundant if team_id is present)
                $target_team_id = $registration['team_id'];      // This is the team they specified (can be NULL)

                $message_prefix = "Registration approved successfully.";

                // 2. If registration includes a team_id, attempt to add player to the team
                if ($target_team_id !== null) {
                    // a. Get the sport_id and name of the target team itself
                    $stmt_team_sport = $conn->prepare("SELECT sport_id, team_name FROM teams WHERE team_id = ?");
                    $stmt_team_sport->bind_param("i", $target_team_id);
                    $stmt_team_sport->execute();
                    $result_team_sport = $stmt_team_sport->get_result();
                    $team_sport_data = $result_team_sport->fetch_assoc();
                    $stmt_team_sport->close();

                    if (!$team_sport_data) {
                        throw new Exception("Error: Target team not found for this registration. Cannot proceed with team assignment.");
                    }
                    $actual_team_sport_id = $team_sport_data['sport_id'];
                    $target_team_name = $team_sport_data['team_name'];


                    // b. Validation: Check if the user is already a member of ANY team for this specific sport
                    // This is the CRITICAL server-side check
                    $stmt_check_conflict = $conn->prepare("SELECT t.team_name 
                                                        FROM team_members tm 
                                                        JOIN teams t ON tm.team_id = t.team_id 
                                                        WHERE tm.user_id = ? AND t.sport_id = ?");
                    $stmt_check_conflict->bind_param("ii", $user_id, $actual_team_sport_id);
                    $stmt_check_conflict->execute();
                    $result_conflict = $stmt_check_conflict->get_result();
                    $existing_member = $result_conflict->fetch_assoc();
                    $stmt_check_conflict->close();

                    if ($existing_member) {
                        throw new Exception("Validation failed: User is already a member of team '" . htmlspecialchars($existing_member['team_name']) . "' for the same sport. A player can only be in one team per sport.");
                    }

                    // c. Add user to team_members
                    $stmt_add_member = $conn->prepare("INSERT INTO team_members (team_id, user_id, role_in_team) VALUES (?, ?, 'Player')");
                    $stmt_add_member->bind_param("ii", $target_team_id, $user_id);
                    if (!$stmt_add_member->execute()) {
                        throw new Exception("Database error: Failed to add user to team: " . $stmt_add_member->error);
                    }
                    $stmt_add_member->close();
                    $message_prefix .= " User added to team '" . htmlspecialchars($target_team_name) . "'.";
                } else {
                    $message_prefix .= " (No team specified for this registration, user not added to a team.)";
                }

                // 3. Update registration status to 'approved'
                $stmt_update_reg = $conn->prepare("UPDATE registrations SET status = 'approved' WHERE registration_id = ?");
                $stmt_update_reg->bind_param("i", $registration_id);
                if (!$stmt_update_reg->execute()) {
                    throw new Exception("Database error: Failed to update registration status: " . $stmt_update_reg->error);
                }
                $stmt_update_reg->close();

                // Commit transaction
                $conn->commit();
                send_json_response('success', $message_prefix);

            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                send_json_response('error', $e->getMessage());
            }
            break;

        case 'reject_reg':
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$id) {
                send_json_response('error', 'Invalid registration ID.');
            }
            $stmt = $conn->prepare("UPDATE registrations SET status = 'rejected' WHERE registration_id = ?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                send_json_response('error', 'Failed to update registration status: ' . $stmt->error);
            }
            if ($stmt->affected_rows > 0) {
                send_json_response('success', 'Registration rejected.');
            } else {
                send_json_response('error', 'Registration not found or status already set.');
            }
            break;
    }
}

function handle_teams($conn, $action, $input) {
    switch ($action) {
        case 'get_teams':
            $sql = "SELECT t.team_id, t.team_name, s.name as sport_name, t.sport_id, u.name as creator_name, (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = t.team_id) as player_count FROM teams t LEFT JOIN sports s ON t.sport_id = s.sport_id LEFT JOIN users u ON t.created_by = u.user_id ORDER BY t.team_name ASC";
            $result = $conn->query($sql);
            send_json_response('success', 'Teams fetched.', $result->fetch_all(MYSQLI_ASSOC));
            break;
        
        case 'get_teams_by_sport':
            $sport_id = filter_var($_GET['sport_id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$sport_id) {
                send_json_response('error', 'Sport ID required.'); 
                return;
            }
            $stmt = $conn->prepare("SELECT team_id, team_name FROM teams WHERE sport_id = ? ORDER BY team_name ASC");
            $stmt->bind_param("i", $sport_id);
            $stmt->execute();
            $result = $stmt->get_result();
            send_json_response('success', 'Teams for sport fetched.', $result->fetch_all(MYSQLI_ASSOC));
            break;

        case 'add_team':
            $team_name = trim($input['team_name'] ?? '');
            $sport_id = filter_var($input['sport_id'] ?? 0, FILTER_VALIDATE_INT);
            if (empty($team_name)) { send_json_response('error', 'Team name cannot be empty.'); }
            if (!$sport_id) { send_json_response('error', 'A valid sport must be selected.'); }

            $admin_id = (int)($_SESSION['user_id'] ?? 0);
            $stmt = $conn->prepare("INSERT INTO teams (team_name, sport_id, created_by) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $team_name, $sport_id, $admin_id);
            if (!$stmt->execute()) { send_json_response('error', 'Failed to create team: ' . $stmt->error); }
            send_json_response('success', 'Team created successfully.');
            break;
        case 'update_team':
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            $team_name = trim($input['team_name'] ?? '');
            $sport_id = filter_var($input['sport_id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$id) { send_json_response('error', 'Invalid team ID.'); }
            if (empty($team_name)) { send_json_response('error', 'Team name cannot be empty.'); }
            if (!$sport_id) { send_json_response('error', 'A valid sport must be selected.'); }

            $stmt = $conn->prepare("UPDATE teams SET team_name = ?, sport_id = ? WHERE team_id = ?");
            $stmt->bind_param("sii", $team_name, $sport_id, $id);
            if (!$stmt->execute()) { send_json_response('error', 'Failed to update team: ' . $stmt->error); }
            if ($stmt->affected_rows === 0) { send_json_response('error', "No changes were made for Team ID: {$id}. The team may not exist or the data was the same."); }
            send_json_response('success', 'Team updated successfully.');
            break;
        case 'delete_team':
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM teams WHERE team_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    send_json_response('success', 'Team deleted successfully.');
                } else {
                    send_json_response('error', 'Team not found.');
                }
            } else {
                send_json_response('error', 'Invalid Team ID.');
            }
            break;
        case 'get_team_members': 
            $team_id = filter_var($_GET['team_id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$team_id) {
                send_json_response('error', 'Team ID is required.');
            }
            $sql = "SELECT tm.team_member, u.user_id, u.name, tm.role_in_team 
                    FROM team_members tm 
                    JOIN users u ON tm.user_id = u.user_id 
                    WHERE tm.team_id = ?
                    ORDER BY u.name ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $team_id);
            $stmt->execute();
            $result = $stmt->get_result();
            send_json_response('success', 'Team members fetched.', $result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'get_all_team_members': // NEW ACTION for client-side validation
            $sql = "SELECT tm.user_id, t.team_id, t.sport_id, u.name as user_name, t.team_name as team_name
                    FROM team_members tm
                    JOIN teams t ON tm.team_id = t.team_id
                    JOIN users u ON tm.user_id = u.user_id";
            $result = $conn->query($sql);
            send_json_response('success', 'All team members fetched.', $result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'add_team_member': // NEW ACTION with validation for one team per sport per player
            $team_id = filter_var($input['team_id'] ?? 0, FILTER_VALIDATE_INT);
            $user_id = filter_var($input['user_id'] ?? 0, FILTER_VALIDATE_INT);
            $role_in_team = trim($input['role_in_team'] ?? 'Player');

            if (!$team_id || !$user_id) {
                send_json_response('error', 'Team ID and User ID are required.');
            }

            // 1. Get the sport_id of the target team
            $stmt_get_sport = $conn->prepare("SELECT sport_id, team_name FROM teams WHERE team_id = ?");
            $stmt_get_sport->bind_param("i", $team_id);
            $stmt_get_sport->execute();
            $result_sport = $stmt_get_sport->get_result();
            $team_sport_data = $result_sport->fetch_assoc();
            $stmt_get_sport->close();

            if (!$team_sport_data) {
                send_json_response('error', 'Could not determine the sport for the selected team.');
            }
            $team_sport_id = $team_sport_data['sport_id'];
            $target_team_name = $team_sport_data['team_name'];


            // 2. Check if the user is ALREADY in ANY team for this sport
            $stmt_check_conflict = $conn->prepare("SELECT t.team_name FROM team_members tm JOIN teams t ON tm.team_id = t.team_id WHERE tm.user_id = ? AND t.sport_id = ?");
            $stmt_check_conflict->bind_param("ii", $user_id, $team_sport_id);
            $stmt_check_conflict->execute();
            $result_conflict = $stmt_check_conflict->get_result();
            $existing_team = $result_conflict->fetch_assoc();
            $stmt_check_conflict->close();

            if ($existing_team) {
                send_json_response('error', 'This user is already a member of "' . htmlspecialchars($existing_team['team_name']) . '" in the same sport. A player can only be in one team per sport.');
            }
            
            // 3. Add the team member if no conflict
            $stmt = $conn->prepare("INSERT INTO team_members (team_id, user_id, role_in_team) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $team_id, $user_id, $role_in_team);
            if (!$stmt->execute()) {
                send_json_response('error', 'Failed to add team member: ' . $stmt->error);
            }
            send_json_response('success', 'Team member added successfully to ' . htmlspecialchars($target_team_name) . '.');
            break;
        case 'remove_team_member': // NEW ACTION
            $team_member_id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT); // Corrected to use 'id' consistent with other deletes
            if (!$team_member_id) {
                send_json_response('error', 'Team Member ID is required.');
            }
            $stmt = $conn->prepare("DELETE FROM team_members WHERE team_member = ?");
            $stmt->bind_param("i", $team_member_id);
            if (!$stmt->execute()) {
                send_json_response('error', 'Failed to remove team member: ' . $stmt->error);
            }
            if ($stmt->affected_rows > 0) {
                send_json_response('success', 'Team member removed successfully.');
            } else {
                send_json_response('error', 'Team member not found.');
            }
            break;
    }
}

function handle_feedback($conn, $action, $input) {
      switch ($action) {
        case 'get_feedback':
            $sql = "SELECT f.*, u.name as user_name, u.email as user_email FROM feedback f JOIN users u ON f.user_id = u.user_id ORDER BY f.is_read ASC, f.submitted_at DESC";
            $result = $conn->query($sql);
            send_json_response('success', 'Feedback fetched.', $result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'toggle_feedback_read':
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE feedback SET is_read = 1 - is_read WHERE feedback_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    send_json_response('success', 'Feedback status toggled.');
                } else {
                    send_json_response('error', 'Feedback not found.');
                }
            } else {
                send_json_response('error', 'Invalid Feedback ID.');
            }
            break;
        case 'delete_feedback':
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    send_json_response('success', 'Feedback deleted.');
                } else {
                    send_json_response('error', 'Feedback not found.');
                }
            } else {
                send_json_response('error', 'Invalid Feedback ID.');
            }
            break;
    }
}

function handle_appeals($conn, $action, $input) {
    switch ($action) {
        case 'get_appeals':
            $sql = "SELECT 
                        ma.appeal_id, ma.reason, ma.submitted_at, ma.status,
                        u.name as user_name,
                        e.event_name, t1.team_name as team1_name, t2.team_name as team2_name
                    FROM match_appeals ma
                    JOIN users u ON ma.user_id = u.user_id
                    JOIN events e ON ma.event_id = e.event_id
                    LEFT JOIN teams t1 ON e.team1_id = t1.team_id
                    LEFT JOIN teams t2 ON e.team2_id = t2.team_id
                    ORDER BY FIELD(ma.status, 'pending', 'reviewed', 'resolved'), ma.submitted_at DESC";
            $result = $conn->query($sql);
            send_json_response('success', 'Appeals fetched.', $result->fetch_all(MYSQLI_ASSOC));
            break;

        case 'update_appeal_status':
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            $status = $input['status'] ?? '';
            if ($id > 0 && in_array($status, ['reviewed', 'resolved'])) {
                $stmt = $conn->prepare("UPDATE match_appeals SET status = ? WHERE appeal_id = ?");
                $stmt->bind_param("si", $status, $id);
                if (!$stmt->execute()) { send_json_response('error', 'Failed to update appeal status: ' . $stmt->error); }
                if ($stmt->affected_rows === 0) { send_json_response('error', 'No changes were made. The appeal may not exist.'); }
                send_json_response('success', 'Appeal status updated successfully.');
            } else {
                send_json_response('error', 'Invalid appeal ID or status provided.');
            }
            break;

        case 'delete_appeal':
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM match_appeals WHERE appeal_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    send_json_response('success', 'Appeal deleted successfully.');
                } else {
                    send_json_response('error', 'Appeal not found.');
                }
            } else {
                send_json_response('error', 'Invalid Appeal ID.');
            }
            break;
    }
}

// --- 6. CLEANUP ---
$conn->close();
?>