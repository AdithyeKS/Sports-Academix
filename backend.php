<?php
// File: backend.php
// This single file handles all backend logic, including auth and API requests.
// VERSION: Corrected Total Users Count

// --- INITIAL SETUP ---
header('Content-Type: application/json');
session_start();

// --- DATABASE CONNECTION ---
// IMPORTANT: Update this with your actual database credentials.
$host = 'localhost';
$dbname = 'sports_management1'; // From your SQL file
$username = 'root';              // Your database username
$password = '';                  // Your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit();
}

// --- ROUTER LOGIC ---
$page = $_GET['page'] ?? null;
$api = $_GET['api'] ?? null;

// --- PAGE ROUTER (for Login/Logout) ---
if ($page) {
    if ($page === 'login') {
        // --- LOGIN LOGIC ---
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id();
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $admin['user_id'];
                header('Location: admin.php');
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        }
        
        // --- LOGIN HTML ---
        header('Content-Type: text/html');
        echo <<<HTML
        <!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Admin Login</title>
        <style>
            body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f3f4f6; margin: 0; }
            .login-box { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 320px; }
            .login-box h2 { text-align: center; margin-bottom: 1.5rem; color: #1f2937; }
            .login-box input { width: 100%; padding: 0.75rem; margin-bottom: 1rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; }
            .login-box button { width: 100%; padding: 0.75rem; border: none; border-radius: 4px; background-color: #2563eb; color: white; font-size: 1rem; cursor: pointer; transition: background-color 0.2s; }
            .login-box button:hover { background-color: #1d4ed8; }
            .login-box .error { color: #dc2626; text-align: center; margin-bottom: 1rem; }
        </style>
        </head><body><div class="login-box"><h2>Admin Login</h2>
        HTML;
        if ($error) { echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; }
        echo <<<HTML
        <form action="backend.php?page=login" method="post">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form></div></body></html>
        HTML;
        exit();

    } else if ($page === 'logout') {
        // --- LOGOUT LOGIC ---
        session_start();
        session_unset();
        session_destroy();
        header('Location: log.php');
        exit();
    }
}

// --- API ROUTER ---
if ($api) {
    if (!isset($_SESSION['loggedin'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Please log in.']);
        exit();
    }
    
    $action = $_GET['action'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($api) {
        case 'dashboard':
            if ($action == 'get_dashboard_data') {
                $stats = [];
                // *** THIS IS THE CORRECTED LINE ***
                // It now counts ALL rows in the users table, regardless of role.
                $stats['totalUsers'] = $pdo->query("SELECT count(*) FROM users")->fetchColumn();
                
                $stats['activeSports'] = $pdo->query("SELECT count(*) FROM sports WHERE status = 'active'")->fetchColumn();
                $stats['upcomingEvents'] = $pdo->query("SELECT count(*) FROM events WHERE status = 'scheduled' AND event_date >= NOW()")->fetchColumn();
                $stats['pendingRegistrations'] = $pdo->query("SELECT count(*) FROM registrations WHERE status = 'pending'")->fetchColumn();
                $adminLogs = $pdo->query("SELECT * FROM admin_logs ORDER BY timestamp DESC LIMIT 5")->fetchAll();
                echo json_encode(['status' => 'success', 'data' => ['stats' => $stats, 'adminLogs' => $adminLogs]]);
            }
            break;

        case 'users':
            switch ($action) {
                case 'get_users':
                    $stmt = $pdo->query("SELECT user_id, name, email, student_id, role, status FROM users ORDER BY name");
                    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
                    break;
                case 'delete_user':
                    if ($input['id'] == $_SESSION['user_id']) { http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'Cannot delete yourself.']); exit(); }
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->execute([$input['id']]);
                    echo json_encode(['status' => 'success', 'message' => 'User deleted.']);
                    break;
            }
            break;

        // ... ALL OTHER API CASES (sports, teams, events, etc.) REMAIN UNCHANGED ...
        case 'sports':
            switch ($action) {
                case 'get_sports':
                    $stmt = $pdo->query("SELECT * FROM sports ORDER BY name");
                    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
                    break;
                case 'add_sport':
                    $stmt = $pdo->prepare("INSERT INTO sports (name, max_players, points_for_win, points_for_draw, points_for_loss, status) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$input['name'], $input['max_players'], $input['points_for_win'], $input['points_for_draw'], $input['points_for_loss'], $input['status']]);
                    echo json_encode(['status' => 'success', 'message' => 'Sport added.']);
                    break;
                case 'update_sport':
                    $stmt = $pdo->prepare("UPDATE sports SET name = ?, max_players = ?, points_for_win = ?, points_for_draw = ?, points_for_loss = ?, status = ? WHERE sport_id = ?");
                    $stmt->execute([$input['name'], $input['max_players'], $input['points_for_win'], $input['points_for_draw'], $input['points_for_loss'], $input['status'], $input['id']]);
                    echo json_encode(['status' => 'success', 'message' => 'Sport updated.']);
                    break;
                case 'delete_sport':
                    $stmt = $pdo->prepare("DELETE FROM sports WHERE sport_id = ?");
                    $stmt->execute([$input['id']]);
                    echo json_encode(['status' => 'success', 'message' => 'Sport deleted.']);
                    break;
            }
            break;

        case 'teams':
             switch ($action) {
                case 'get_teams':
                    $sql = "SELECT t.*, s.name as sport_name, u.name as creator_name, (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = t.team_id) as player_count
                            FROM teams t JOIN sports s ON t.sport_id = s.sport_id LEFT JOIN users u ON t.created_by = u.user_id ORDER BY t.team_name";
                    $stmt = $pdo->query($sql);
                    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
                    break;
                case 'add_team':
                    $stmt = $pdo->prepare("INSERT INTO teams (team_name, sport_id, created_by) VALUES (?, ?, ?)");
                    $stmt->execute([$input['team_name'], $input['sport_id'], $_SESSION['user_id']]);
                    echo json_encode(['status' => 'success', 'message' => 'Team created.']);
                    break;
                case 'delete_team':
                    $stmt = $pdo->prepare("DELETE FROM teams WHERE team_id = ?");
                    $stmt->execute([$input['id']]);
                    echo json_encode(['status' => 'success', 'message' => 'Team deleted.']);
                    break;
            }
            break;

        case 'events':
            $base_query = "SELECT e.*, s.name as sport_name, t1.team_name as team1_name, t2.team_name as team2_name 
                           FROM events e JOIN sports s ON e.sport_id = s.sport_id LEFT JOIN teams t1 ON e.team1_id = t1.team_id LEFT JOIN teams t2 ON e.team2_id = t2.team_id";
            switch($action) {
                case 'get_events':
                    $stmt = $pdo->query("$base_query ORDER BY e.event_date DESC");
                    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
                    break;
                case 'get_upcoming_events':
                    $stmt = $pdo->query("$base_query WHERE e.status = 'scheduled' AND e.event_date >= NOW() ORDER BY e.event_date ASC");
                    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
                    break;
                case 'add_event':
                    $stmt = $pdo->prepare("INSERT INTO events (event_name, sport_id, event_date, venue, team1_id, team2_id, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$input['event_name'], $input['sport_id'], $input['event_date'], $input['venue'], $input['team1_id'] ?: null, $input['team2_id'] ?: null, $input['description']]);
                    echo json_encode(['status' => 'success', 'message' => 'Event created.']);
                    break;
                case 'update_event':
                    $stmt = $pdo->prepare("UPDATE events SET event_name=?, sport_id=?, event_date=?, venue=?, team1_id=?, team2_id=?, status=?, description=? WHERE event_id=?");
                    $stmt->execute([$input['event_name'], $input['sport_id'], $input['event_date'], $input['venue'], $input['team1_id'] ?: null, $input['team2_id'] ?: null, $input['status'], $input['description'], $input['id']]);
                    echo json_encode(['status' => 'success', 'message' => 'Event updated.']);
                    break;
                case 'update_score':
                    $result = 'draw';
                    if ($input['team1_score'] > $input['team2_score']) $result = 'team1_win';
                    if ($input['team2_score'] > $input['team1_score']) $result = 'team2_win';
                    $stmt = $pdo->prepare("UPDATE events SET team1_score = ?, team2_score = ?, status = 'completed', result = ? WHERE event_id = ?");
                    $stmt->execute([$input['team1_score'], $input['team2_score'], $result, $input['id']]);
                    echo json_encode(['status' => 'success', 'message' => 'Score finalized.']);
                    break;
                case 'delete_event':
                    $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = ?");
                    $stmt->execute([$input['id']]);
                    echo json_encode(['status' => 'success', 'message' => 'Event deleted.']);
                    break;
                case 'get_points_table':
                    $sport_id = $_GET['sport_id'] ?? 0;
                    if (!$sport_id) { echo json_encode(['status'=>'success', 'data'=>[]]); exit(); }
                    $points_stmt = $pdo->prepare("SELECT points_for_win, points_for_draw, points_for_loss FROM sports WHERE sport_id = ?");
                    $points_stmt->execute([$sport_id]);
                    $points = $points_stmt->fetch();
                    $sql = "SELECT t.team_id, t.team_name,
                                SUM(CASE WHEN e.status = 'completed' AND (e.team1_id = t.team_id OR e.team2_id = t.team_id) THEN 1 ELSE 0 END) AS played,
                                SUM(CASE WHEN (e.team1_id = t.team_id AND e.result = 'team1_win') OR (e.team2_id = t.team_id AND e.result = 'team2_win') THEN 1 ELSE 0 END) AS wins,
                                SUM(CASE WHEN e.result = 'draw' AND (e.team1_id = t.team_id OR e.team2_id = t.team_id) THEN 1 ELSE 0 END) AS draws,
                                SUM(CASE WHEN (e.team1_id = t.team_id AND e.result = 'team2_win') OR (e.team2_id = t.team_id AND e.result = 'team1_win') THEN 1 ELSE 0 END) AS losses
                            FROM teams t LEFT JOIN events e ON (t.team_id = e.team1_id OR t.team_id = e.team2_id) AND e.status = 'completed' AND e.sport_id = t.sport_id
                            WHERE t.sport_id = :sport_id GROUP BY t.team_id, t.team_name";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':sport_id' => $sport_id]);
                    $table = $stmt->fetchAll();
                    foreach($table as &$row) { $row['points'] = ($row['wins'] * $points['points_for_win']) + ($row['draws'] * $points['points_for_draw']) + ($row['losses'] * $points['points_for_loss']); }
                    usort($table, fn($a, $b) => $b['points'] <=> $a['points']);
                    echo json_encode(['status' => 'success', 'data' => $table]);
                    break;
            }
            break;

        case 'registrations':
            switch ($action) {
                case 'get_pending_registrations':
                    $sql = "SELECT r.*, u.name as user_name, t.team_name, s.name as sport_name 
                            FROM registrations r JOIN users u ON r.user_id = u.user_id JOIN teams t ON r.team_id = t.team_id JOIN sports s ON t.sport_id = s.sport_id
                            WHERE r.status = 'pending' ORDER BY r.registered_at DESC";
                    $stmt = $pdo->query($sql);
                    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
                    break;
                case 'approve_reg':
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("UPDATE registrations SET status = 'approved' WHERE registration_id = ?");
                    $stmt->execute([$input['id']]);
                    $stmt = $pdo->prepare("SELECT user_id, team_id FROM registrations WHERE registration_id = ?");
                    $stmt->execute([$input['id']]);
                    if ($reg_data = $stmt->fetch()) {
                        $stmt = $pdo->prepare("INSERT INTO team_members (team_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE team_id=team_id");
                        $stmt->execute([$reg_data['team_id'], $reg_data['user_id']]);
                    }
                    $pdo->commit();
                    echo json_encode(['status' => 'success', 'message' => 'Registration approved.']);
                    break;
                case 'reject_reg':
                    $stmt = $pdo->prepare("UPDATE registrations SET status = 'rejected' WHERE registration_id = ?");
                    $stmt->execute([$input['id']]);
                    echo json_encode(['status' => 'success', 'message' => 'Registration rejected.']);
                    break;
            }
            break;

        case 'feedback':
            switch ($action) {
                case 'get_feedback':
                    $sql = "SELECT f.*, u.name as user_name, u.email as user_email FROM feedback f JOIN users u ON f.user_id = u.user_id ORDER BY f.submitted_at DESC";
                    $stmt = $pdo->query($sql);
                    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
                    break;
                case 'toggle_feedback_read':
                    $stmt = $pdo->prepare("UPDATE feedback SET is_read = NOT is_read WHERE feedback_id = ?");
                    $stmt->execute([$input['id']]);
                    echo json_encode(['status' => 'success', 'message' => 'Status toggled.']);
                    break;
                case 'delete_feedback':
                    $stmt = $pdo->prepare("DELETE FROM feedback WHERE feedback_id = ?");
                    $stmt->execute([$input['id']]);
                    echo json_encode(['status' => 'success', 'message' => 'Feedback deleted.']);
                    break;
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'API endpoint not found.']);
            break;
    }
    exit();
}

// Fallback for any other request
http_response_code(404);
echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
exit();

?>```