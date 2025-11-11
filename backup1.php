<?php
// ==========================================================================================
// SECTION 1: PHP BACKEND API
// ==========================================================================================
if (isset($_REQUEST['action'])) {

    // --- Database Configuration & Connection ---
    $db_host = 'localhost';
    $db_name = 'sports_management';
    $db_user = 'root';
    $db_pass = '';

    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
        exit();
    }

    // --- Helper Functions ---
    function getPostData() { return json_decode(file_get_contents('php://input'), true); }
    function logAction($pdo, $adminId, $actionMessage) {
        try {
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action) VALUES (?, ?)");
            $stmt->execute([$adminId, $actionMessage]);
        } catch (Exception $e) { /* Silently fail */ }
    }
    
    $currentAdminId = 1; 
    header('Content-Type: application/json');
    $action = $_REQUEST['action'];
    $data = getPostData();

    // --- API ROUTER ---
    try {
        switch ($action) {
            case 'get_all_data':
                $response = [];
                // Stats
                $response['stats']['totalUsers'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                $response['stats']['activeSports'] = $pdo->query("SELECT COUNT(*) FROM sports WHERE status = 'active'")->fetchColumn();
                $response['stats']['upcomingEvents'] = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'scheduled' AND event_date > NOW()")->fetchColumn();
                $response['stats']['pendingRegistrations'] = $pdo->query("SELECT COUNT(*) FROM registrations WHERE status = 'pending'")->fetchColumn();
                
                // Main Data Tables
                $response['users'] = $pdo->query("SELECT user_id, name, email, student_id, role, status FROM users ORDER BY created_at DESC")->fetchAll();
                $response['sports'] = $pdo->query("SELECT sport_id, name, max_players, status, points_for_win, points_for_draw, points_for_loss FROM sports ORDER BY name ASC")->fetchAll();
                $response['events'] = $pdo->query("SELECT e.*, s.name as sport_name, t1.team_name as team1_name, t2.team_name as team2_name FROM events e JOIN sports s ON e.sport_id = s.sport_id LEFT JOIN teams t1 ON e.team1_id = t1.team_id LEFT JOIN teams t2 ON e.team2_id = t2.team_id ORDER BY e.event_date DESC")->fetchAll();
                $response['registrations'] = $pdo->query("SELECT r.registration_id, u.name as user_name, e.event_name, r.status, r.registered_at FROM registrations r JOIN users u ON r.user_id = u.user_id JOIN events e ON r.event_id = e.event_id WHERE r.status = 'pending' ORDER BY r.registered_at DESC")->fetchAll();
                $response['teams'] = $pdo->query("SELECT t.*, s.name as sport_name, (SELECT u.name FROM users u JOIN team_members tm ON u.user_id = tm.user_id WHERE tm.team_id = t.team_id AND tm.role_in_team = 'Captain' LIMIT 1) as captain_name, (SELECT COUNT(*) FROM team_members WHERE team_id = t.team_id) as player_count FROM teams t JOIN sports s ON t.sport_id = s.sport_id ORDER BY t.team_name ASC")->fetchAll();
                $response['adminLogs'] = $pdo->query("SELECT a.log_id, u.name as admin_name, a.action, a.timestamp FROM admin_logs a JOIN users u ON a.admin_id = u.user_id ORDER BY a.timestamp DESC LIMIT 20")->fetchAll();
                $response['feedback'] = $pdo->query("SELECT f.*, u.name as user_name, u.email as user_email FROM feedback f JOIN users u ON f.user_id = u.user_id ORDER BY f.submitted_at DESC")->fetchAll();
                
                echo json_encode(['status' => 'success', 'data' => $response]);
                break;
            
            case 'get_points_table':
                $sportId = $_GET['sport_id'] ?? 0;
                if (empty($sportId)) {
                    echo json_encode(['status' => 'success', 'data' => []]);
                    exit();
                }

                $sql = "
                    SELECT
                        t.team_name,
                        s.name as sport_name,
                        COALESCE(SUM(sub.played), 0) AS played,
                        COALESCE(SUM(sub.win), 0) AS wins,
                        COALESCE(SUM(sub.draw), 0) AS draws,
                        COALESCE(SUM(sub.loss), 0) AS losses,
                        (COALESCE(SUM(sub.win), 0) * s.points_for_win) + 
                        (COALESCE(SUM(sub.draw), 0) * s.points_for_draw) + 
                        (COALESCE(SUM(sub.loss), 0) * s.points_for_loss) AS points
                    FROM teams t
                    JOIN sports s ON t.sport_id = s.sport_id
                    LEFT JOIN (
                        SELECT team1_id as team_id, 1 as played, (result = 'team1_win') as win, (result = 'draw') as draw, (result = 'team2_win') as loss FROM events WHERE status = 'completed'
                        UNION ALL
                        SELECT team2_id as team_id, 1 as played, (result = 'team2_win') as win, (result = 'draw') as draw, (result = 'team1_win') as loss FROM events WHERE status = 'completed'
                    ) AS sub ON t.team_id = sub.team_id
                    WHERE t.sport_id = ?
                    GROUP BY t.team_id, t.team_name, s.name, s.points_for_win, s.points_for_draw, s.points_for_loss
                    ORDER BY points DESC, wins DESC, t.team_name ASC;
                ";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$sportId]);
                $pointsTable = $stmt->fetchAll();

                echo json_encode(['status' => 'success', 'data' => $pointsTable]);
                break;

            case 'add_sport':
                 $stmt = $pdo->prepare("INSERT INTO sports (name, max_players, status, points_for_win, points_for_draw, points_for_loss) VALUES (?, ?, ?, ?, ?, ?)");
                 $stmt->execute([$data['name'], $data['max_players'], $data['status'], $data['points_for_win'], $data['points_for_draw'], $data['points_for_loss']]);
                 logAction($pdo, $currentAdminId, "Added new sport: '{$data['name']}'.");
                 echo json_encode(['status' => 'success', 'message' => 'Sport added successfully.']);
                 break;
            case 'update_sport':
                 $stmt = $pdo->prepare("UPDATE sports SET name = ?, max_players = ?, status = ?, points_for_win = ?, points_for_draw = ?, points_for_loss = ? WHERE sport_id = ?");
                 $stmt->execute([$data['name'], $data['max_players'], $data['status'], $data['points_for_win'], $data['points_for_draw'], $data['points_for_loss'], $data['id']]);
                 logAction($pdo, $currentAdminId, "Updated sport ID #{$data['id']}.");
                 echo json_encode(['status' => 'success', 'message' => 'Sport updated successfully.']);
                 break;
            case 'add_event':
                $stmt = $pdo->prepare("INSERT INTO events (event_name, sport_id, event_date, venue, description, team1_id, team2_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled')");
                $stmt->execute([$data['event_name'], $data['sport_id'], $data['event_date'], $data['venue'], $data['description'], $data['team1_id'] ?: null, $data['team2_id'] ?: null]);
                logAction($pdo, $currentAdminId, "Created event: '{$data['event_name']}'.");
                echo json_encode(['status' => 'success', 'message' => 'Event created successfully.']);
                break;
            case 'add_team':
                $stmt = $pdo->prepare("INSERT INTO teams (team_name, sport_id, created_by) VALUES (?, ?, ?)");
                $stmt->execute([$data['team_name'], $data['sport_id'], $currentAdminId]); 
                logAction($pdo, $currentAdminId, "Created team: '{$data['team_name']}'.");
                echo json_encode(['status' => 'success', 'message' => 'Team created successfully.']);
                break;
            case 'update_event':
                $stmt = $pdo->prepare("UPDATE events SET event_name=?, sport_id=?, event_date=?, venue=?, description=?, team1_id=?, team2_id=?, status=? WHERE event_id=?");
                $stmt->execute([$data['event_name'], $data['sport_id'], $data['event_date'], $data['venue'], $data['description'], $data['team1_id'] ?: null, $data['team2_id'] ?: null, $data['status'], $data['id']]);
                logAction($pdo, $currentAdminId, "Updated event ID #{$data['id']}.");
                echo json_encode(['status' => 'success', 'message' => 'Event updated successfully.']);
                break;
            case 'update_score':
                if ($data['team1_score'] > $data['team2_score']) { $result = 'team1_win'; } 
                elseif ($data['team2_score'] > $data['team1_score']) { $result = 'team2_win'; } 
                else { $result = 'draw'; }
                $stmt = $pdo->prepare("UPDATE events SET team1_score=?, team2_score=?, result=?, status='completed' WHERE event_id=?");
                $stmt->execute([$data['team1_score'], $data['team2_score'], $result, $data['id']]);
                logAction($pdo, $currentAdminId, "Updated score for event ID #{$data['id']}.");
                echo json_encode(['status' => 'success', 'message' => 'Score updated and event marked as completed.']);
                break;
            
            case 'delete-user': case 'delete-sport': case 'delete-event': case 'delete-team': case 'delete-feedback':
                $tableMap = [
                    'delete-user' => ['table' => 'users', 'id_col' => 'user_id'],
                    'delete-sport' => ['table' => 'sports', 'id_col' => 'sport_id'],
                    'delete-event' => ['table' => 'events', 'id_col' => 'event_id'],
                    'delete-team' => ['table' => 'teams', 'id_col' => 'team_id'],
                    'delete-feedback' => ['table' => 'feedback', 'id_col' => 'feedback_id'],
                ];
                $tableInfo = $tableMap[$action];
                $stmt = $pdo->prepare("DELETE FROM {$tableInfo['table']} WHERE {$tableInfo['id_col']} = ?");
                $stmt->execute([$data['id']]);
                logAction($pdo, $currentAdminId, "Deleted item ID #{$data['id']} from {$tableInfo['table']}.");
                echo json_encode(['status' => 'success', 'message' => 'Item deleted successfully.']);
                break;
            
            case 'approve-reg': case 'reject-reg':
                $newStatus = ($action === 'approve-reg') ? 'approved' : 'rejected';
                $stmt = $pdo->prepare("UPDATE registrations SET status = ? WHERE registration_id = ?");
                $stmt->execute([$newStatus, $data['id']]);
                logAction($pdo, $currentAdminId, "Set registration ID #{$data['id']} to {$newStatus}.");
                echo json_encode(['status' => 'success', 'message' => 'Registration status updated.']);
                break;
            case 'toggle-feedback-read':
                $stmt = $pdo->prepare("UPDATE feedback SET is_read = NOT is_read WHERE feedback_id = ?");
                $stmt->execute([$data['id']]);
                logAction($pdo, $currentAdminId, "Toggled read status for feedback ID #{$data['id']}.");
                echo json_encode(['status' => 'success', 'message' => 'Feedback status toggled.']);
                break;
            default:
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'An internal server error occurred: ' . $e->getMessage()]);
    }
    
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sports Management System - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body{background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;margin:0;color:#1f2937}.h-screen{height:100vh}.flex{display:flex}.flex-col{flex-direction:column}.flex-1{flex:1 1 0%}.flex-shrink-0{flex-shrink:0}.overflow-hidden{overflow:hidden}.overflow-y-auto{overflow-y:auto}.overflow-x-auto{overflow-x:auto}.h-full{height:100%}.p-4{padding:1rem}.p-6{padding:1.5rem}.px-2{padding-left:.5rem;padding-right:.5rem}.py-1{padding-top:.25rem;padding-bottom:.25rem}.px-3{padding-left:.75rem;padding-right:.75rem}.py-2{padding-top:.5rem;padding-bottom:.5rem}.px-4{padding-left:1rem;padding-right:1rem}.py-3{padding-top:.75rem;padding-bottom:.75rem}.px-6{padding-left:1.5rem;padding-right:1.5rem}.py-4{padding-top:1rem;padding-bottom:1rem}.mt-1{margin-top:.25rem}.mr-2{margin-right:.5rem}.mb-2{margin-bottom:.5rem}.mr-3{margin-right:.75rem}.mr-4{margin-right:1rem}.mt-4{margin-top:1rem}.mb-4{margin-bottom:1rem}.mt-6{margin-top:1.5rem}.mb-6{margin-bottom:1.5rem}.mb-8{margin-bottom:2rem}.mt-auto{margin-top:auto}.space-x-2>*:not(:first-child){margin-left:.5rem}.space-x-4>*:not(:first-child){margin-left:1rem}.space-y-4>*:not(:first-child){margin-top:1rem}.gap-4{gap:1rem}.gap-6{gap:1.5rem}.items-center{align-items:center}.items-start{align-items:flex-start}.justify-between{justify-content:space-between}.grid{display:grid}.grid-cols-1{grid-template-columns:repeat(1,minmax(0,1fr))}.grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}@media (min-width:768px){.md\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}}@media (min-width:1024px){.lg\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}.lg\:grid-cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}}.w-10{width:2.5rem}.h-10{height:2.5rem}.w-16{width:4rem}.h-16{height:4rem}.w-64{width:16rem}.min-w-full{min-width:100%}.text-xs{font-size:.75rem}.text-sm{font-size:.875rem}.text-lg{font-size:1.125rem}.text-xl{font-size:1.25rem}.text-2xl{font-size:1.5rem}.text-3xl{font-size:1.875rem}.font-bold{font-weight:700}.font-semibold{font-weight:600}.font-medium{font-weight:500}.uppercase{text-transform:uppercase}.tracking-wider{letter-spacing:.05em}.text-left{text-align:left}.text-center{text-align:center}.text-right{text-align:right}.whitespace-nowrap{white-space:nowrap}.bg-black{background-color:#000}.bg-white{background-color:#fff}.bg-gray-50{background-color:#f9fafb}.bg-blue-50{background-color:#eff6ff}.bg-blue-100{background-color:#dbeafe}.bg-blue-600{background-color:#2563eb}.bg-blue-900{background-color:#1e3a8a}.bg-green-50{background-color:#f0fdf4}.bg-green-100{background-color:#dcfce7}.bg-green-500{background-color:#22c55e}.bg-green-600{background-color:#16a34a}.bg-purple-50{background-color:#faf5ff}.bg-purple-100{background-color:#f3e8ff}.bg-yellow-50{background-color:#fefce8}.bg-yellow-100{background-color:#fef08a}.bg-red-100{background-color:#fee2e2}.bg-red-500{background-color:#ef4444}.bg-red-600{background-color:#dc2626}.bg-gray-100{background-color:#f3f4f6}.text-white{color:#fff}.text-gray-500{color:#6b7281}.text-gray-600{color:#4b5563}.text-gray-800{color:#1f2937}.text-blue-500{color:#3b82f6}.text-blue-800{color:#1e40af}.text-green-600{color:#16a34a}.text-green-800{color:#166534}.text-purple-800{color:#5b21b6}.text-yellow-800{color:#854d0e}.text-red-600{color:#dc2626}.text-red-800{color:#991b1b}.rounded-md{border-radius:.375rem}.rounded-lg{border-radius:.5rem}.rounded-full{border-radius:9999px}.shadow{box-shadow:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -2px rgba(0,0,0,.1)}.shadow-sm{box-shadow:0 1px 2px 0 rgba(0,0,0,.05)}.divide-y>*:not(:first-child){border-top:1px solid #e5e7eb}.cursor-pointer{cursor:pointer}.transition{transition:all .15s ease-in-out}.hover\:bg-blue-700:hover{background-color:#1d4ed8}.hover\:bg-blue-100:hover{background-color:#dbeafe}.hover\:bg-green-100:hover{background-color:#dcfce7}.hover\:bg-purple-100:hover{background-color:#f3e8ff}.hover\:bg-yellow-100:hover{background-color:#fef08a}.hover\:bg-red-700:hover{background-color:#b91c1c}.hover\:text-blue-900:hover{color:#1e3a8a}.hover\:text-red-900:hover{color:#7f1d1d}.sidebar{transition:all .3s ease-in-out}.dashboard-card{transition:all .3s ease-in-out}.dashboard-card:hover{transform:translateY(-5px);box-shadow:0 10px 20px rgba(0,0,0,.1)}.tab-content{display:none}.tab-content.active{display:block;animation:fadeIn .5s ease-in-out}@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}.modal-hidden{display:none}.modal-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background-color:rgba(0,0,0,.5);z-index:999}.modal-container{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background-color:#fff;padding:1.5rem;border-radius:.5rem;box-shadow:0 10px 25px -5px rgba(0,0,0,.1);z-index:1000;width:90%;max-width:600px;max-height:90vh;overflow-y:auto}.modal-header{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e5e7eb;padding-bottom:1rem;margin-bottom:1rem}.modal-header .close-btn{font-size:1.5rem;font-weight:700;line-height:1;border:none;background:none;cursor:pointer}.modal-body form label{display:block;margin-bottom:.5rem;font-weight:600;color:#374151}.modal-body form input,.modal-body form select,.modal-body form textarea{width:100%;box-sizing:border-box;padding:.5rem .75rem;border:1px solid #d1d5db;border-radius:.375rem;margin-bottom:1rem}.modal-body form button[type=submit]{width:100%;padding:.75rem;border-radius:.375rem;color:#fff;background-color:#2563eb;cursor:pointer;font-weight:600;transition:background-color .2s}.modal-body form button[type=submit]:hover{background-color:#1d4ed8}#pointsSportSelect{padding: .5rem .75rem; border: 1px solid #d1d5db; border-radius: .375rem; background-color: white;}
        /* --- ADDED --- Styling for the new logout link */
        a.logout-link { display: block; text-decoration: none; color: white; }
    </style>
</head>
<body>
    <div class="flex h-screen">
        <!-- Sidebar Navigation -->
        <div class="sidebar w-64 bg-black text-white p-4 flex flex-col flex-shrink-0">
            <div class="p-4 flex items-center space-x-2"><img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48Y2lyY2xlIGN4PSI1MCIgY3k9IjUwIiByPSI0NSIgZmlsbD0iIzFlM2E4YSIvPjxwYXRoIGQ9Ik0zMCA3MEw1MCAzMGwzMCA0MEg3MGwtMjAtMjAtMjAgMjBIMzBaIiBmaWxsPSIjZmZmIi8+PC9zdmc+" alt="Logo" class="w-16 h-16"><h1 class="text-xl font-bold">Sports Academix</h1></div>
            <nav class="mt-6 flex flex-col flex-1">
                <div>
                    <div class="px-4 py-3 rounded-md bg-blue-900 cursor-pointer tab-button" data-tab="dashboard"><i class="fas fa-tachometer-alt fa-fw mr-2"></i> Dashboard</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="users"><i class="fas fa-users fa-fw mr-2"></i> User Management</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="sports"><i class="fas fa-futbol fa-fw mr-2"></i> Sports Management</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="events"><i class="fas fa-calendar-alt fa-fw mr-2"></i> Event Management</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="points"><i class="fas fa-trophy fa-fw mr-2"></i> Points Table</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="schedules"><i class="fas fa-clock fa-fw mr-2"></i> Schedule Management</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="registrations"><i class="fas fa-clipboard-list fa-fw mr-2"></i> Registrations</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="teams"><i class="fas fa-people-group fa-fw mr-2"></i> Team Management</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="scores"><i class="fas fa-star fa-fw mr-2"></i> Score Management</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="logs"><i class="fas fa-history fa-fw mr-2"></i> Admin Logs</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="feedback"><i class="fas fa-comments fa-fw mr-2"></i> User Feedback</div>
                </div>
                <!-- ============================================ -->
                <!-- MODIFIED: The logout button is now a proper link -->
                <!-- ============================================ -->
                <a href="logout.php" class="logout-link mt-auto px-4 py-3 rounded-md hover:bg-red-700 cursor-pointer transition">
                    <i class="fas fa-sign-out-alt fa-fw mr-2"></i><span>Logout</span>
                </a>
            </nav>
        </div>
        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm p-4 flex justify-between items-center"><h2 class="text-2xl font-semibold text-gray-800" id="page-title">Dashboard</h2><div class="flex items-center space-x-4"><img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48Y2lyY2xlIGN4PSI1MCIgY3k9IjUwIiByPSI1MCIgZmlsbD0iIzk5OWI5YyIvPjxwYXRoIGQ9Ik01MCAxMUMzNS4xIDExIDIzIDI3LjYgMjMgNDVDMjMgNjIuNCAzNi4xIDgwIDUwIDgwQzY4LjkgODAgNzcgNjIuNCA3NyA0NUM3NyAyNy42IDY4LjkgMTEgNTAgMTF6TTUwIDM1YzUuNSAwIDEwIDQuNSAxMCAxMHMtNC41IDEwLTEwIDEwLTEwLTQuNS0xMC0xMFM0NC41IDM1IDUwIDM1ek01MCA3MmMtOS40IDAtMTcuNy01LjQtMjEuMi0xMy4yQzMxIDUzLjYgNDAuMSA0OSA1MCA0OXM5IDEgMTIgMy44Yy44IDIuNCAxLjIgNC45IDEuMiA3LjJDNjMuMiA2Ni42IDU3IDcyIDUwIDcyeiIgZmlsbD0iI2ZmZiIvPjwvc3ZnPg==" alt="Admin profile" class="rounded-full w-10 h-10"><span>Admin</span><i class="fas fa-caret-down text-gray-500"></i></div></header>
            <main class="flex-1 overflow-y-auto p-6">
                
                <div id="dashboard" class="tab-content active"><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8"><div class="dashboard-card bg-white rounded-lg shadow p-6 flex justify-between items-center"><div><h3 class="text-gray-500 text-sm">Total Users</h3><p id="totalUsersStat" class="text-3xl font-bold">0</p></div><i class="fas fa-users text-blue-500 text-3xl"></i></div><div class="dashboard-card bg-white rounded-lg shadow p-6 flex justify-between items-center"><div><h3 class="text-gray-500 text-sm">Active Sports</h3><p id="activeSportsStat" class="text-3xl font-bold">0</p></div><i class="fas fa-futbol text-green-500 text-3xl"></i></div><div class="dashboard-card bg-white rounded-lg shadow p-6 flex justify-between items-center"><div><h3 class="text-gray-500 text-sm">Upcoming Events</h3><p id="upcomingEventsStat" class="text-3xl font-bold">0</p></div><i class="fas fa-calendar-alt text-purple-500 text-3xl"></i></div><div class="dashboard-card bg-white rounded-lg shadow p-6 flex justify-between items-center"><div><h3 class="text-gray-500 text-sm">Pending Registrations</h3><p id="pendingRegsStat" class="text-3xl font-bold">0</p></div><i class="fas fa-clipboard-list text-yellow-500 text-3xl"></i></div></div><div class="grid grid-cols-1 lg:grid-cols-2 gap-6"><div class="bg-white rounded-lg shadow p-6"><h3 class="text-lg font-semibold mb-4">Recent Activities</h3><div id="recentActivitiesContainer" class="space-y-4"></div></div><div class="bg-white rounded-lg shadow p-6"><h3 class="text-lg font-semibold mb-4">Quick Actions</h3><div class="grid grid-cols-2 gap-4"><button data-tab-link="sports" class="p-4 bg-blue-50 text-blue-800 rounded-lg hover:bg-blue-100 transition flex flex-col items-center"><i class="fas fa-plus-circle text-2xl mb-2"></i><span>Add New Sport</span></button><button data-tab-link="events" class="p-4 bg-green-50 text-green-800 rounded-lg hover:bg-green-100 transition flex flex-col items-center"><i class="fas fa-calendar-plus text-2xl mb-2"></i><span>Create Event</span></button><button data-tab-link="teams" class="p-4 bg-purple-50 text-purple-800 rounded-lg hover:bg-purple-100 transition flex flex-col items-center"><i class="fas fa-users text-2xl mb-2"></i><span>Manage Teams</span></button><button data-tab-link="registrations" class="p-4 bg-yellow-50 text-yellow-800 rounded-lg hover:bg-yellow-100 transition flex flex-col items-center"><i class="fas fa-clipboard-check text-2xl mb-2"></i><span>Review Registrations</span></button></div></div></div></div>
                <div id="users" class="tab-content"><div class="flex justify-between items-center mb-6"><h2 class="text-xl font-semibold">User Management</h2></div><div class="bg-white rounded-lg shadow overflow-x-auto"><table class="min-w-full"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th></tr></thead><tbody id="usersTableBody" class="divide-y divide-gray-200"></tbody></table></div></div>
                <div id="sports" class="tab-content"><div class="flex justify-between items-center mb-6"><h2 class="text-xl font-semibold">Sports Management</h2><button id="addSportBtn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="fas fa-plus mr-2"></i> Add Sport</button></div><div class="bg-white rounded-lg shadow overflow-x-auto"><table class="min-w-full"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sport Name</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Players</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points (W/D/L)</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th></tr></thead><tbody id="sportsTableBody" class="divide-y divide-gray-200"></tbody></table></div></div>
                <div id="events" class="tab-content"><div class="flex justify-between items-center mb-6"><h2 class="text-xl font-semibold">Event Management</h2><button id="addEventBtn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="fas fa-calendar-plus mr-2"></i> Create Event</button></div><div class="bg-white rounded-lg shadow overflow-x-auto"><table class="min-w-full"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Name</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sport</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teams</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th></tr></thead><tbody id="eventsTableBody" class="divide-y divide-gray-200"></tbody></table></div></div>
                <div id="points" class="tab-content"><div class="flex justify-between items-center mb-6"><h2 class="text-xl font-semibold">Points Table / Standings</h2><div><label for="pointsSportSelect" class="mr-2 font-medium">Select Sport:</label><select id="pointsSportSelect"></select></div></div><div class="bg-white rounded-lg shadow overflow-x-auto"><table class="min-w-full"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team Name</th><th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Played</th><th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Wins</th><th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Draws</th><th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Losses</th><th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th></tr></thead><tbody id="pointsTableBody" class="divide-y divide-gray-200"></tbody></table></div></div>
                <div id="schedules" class="tab-content"><div class="flex justify-between items-center mb-6"><h2 class="text-xl font-semibold">Schedule Management</h2></div><div class="bg-white rounded-lg shadow overflow-x-auto"><table class="min-w-full"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event / Match</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Venue</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th></tr></thead><tbody id="schedulesTableBody" class="divide-y divide-gray-200"></tbody></table></div></div>
                <div id="registrations" class="tab-content"><h2 class="text-xl font-semibold mb-6">Pending Registrations</h2><div class="bg-white rounded-lg shadow overflow-x-auto"><table class="min-w-full"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Name</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered At</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th></tr></thead><tbody id="registrationsTableBody" class="divide-y divide-gray-200"></tbody></table></div></div>
                <div id="teams" class="tab-content"><div class="flex justify-between items-center mb-6"><h2 class="text-xl font-semibold">Team Management</h2><button id="addTeamBtn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="fas fa-plus mr-2"></i> Create Team</button></div><div class="bg-white rounded-lg shadow overflow-x-auto"><table class="min-w-full"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team Name</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sport</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Captain</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Players</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th></tr></thead><tbody id="teamsTableBody" class="divide-y divide-gray-200"></tbody></table></div></div>
                <div id="scores" class="tab-content"><h2 class="text-xl font-semibold mb-6">Score Management</h2><div class="bg-white rounded-lg shadow overflow-x-auto"><table class="min-w-full"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Match</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Result</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th></tr></thead><tbody id="scoresTableBody" class="divide-y divide-gray-200"></tbody></table></div></div>
                <div id="logs" class="tab-content"><h2 class="text-xl font-semibold mb-6">Admin Activity Logs</h2><div class="bg-white rounded-lg shadow overflow-x-auto"><table class="min-w-full"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th></tr></thead><tbody id="logsTableBody" class="divide-y divide-gray-200"></tbody></table></div></div>
                <div id="feedback" class="tab-content"><h2 class="text-xl font-semibold mb-6">User Feedback</h2><div id="feedbackContainer" class="space-y-4"></div></div>

            </main>
        </div>
    </div>
    <!-- Modal for Forms -->
    <div id="mainModal" class="modal-hidden"><div class="modal-overlay"></div><div class="modal-container"><div class="modal-header"><h3 id="modalTitle" class="text-xl font-semibold">Modal Title</h3><button id="closeModalBtn" class="close-btn">×</button></div><div id="modalBody" class="modal-body"></div></div></div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const pageTitle = document.getElementById('page-title');
        const modal = document.getElementById('mainModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        const closeModalBtn = document.getElementById('closeModalBtn');
        let db = {};

        async function apiRequest(action, method = 'GET', body = null) {
            let url = `?action=${action}`;
            if (method === 'GET' && body) {
                 url += '&' + new URLSearchParams(body).toString();
            }
            const options = { method, headers: {} };
            if (method === 'POST' && body) {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(body);
            }
            try {
                const response = await fetch(url, options);
                const result = await response.json();
                if (!response.ok || result.status !== 'success') throw new Error(result.message || `HTTP error ${response.status}`);
                return result;
            } catch (error) {
                console.error('API Request Error:', error);
                showAlert(`API Error: ${error.message}`, 'error');
                throw error;
            }
        }
        
        const showAlert = (message, type = 'info') => alert((type === 'error' ? 'Error: ' : 'Success: ') + message);
        const getStatusClass = (status) => { if (!status) return 'bg-gray-100 text-gray-800'; status = status.toLowerCase(); const classes = { 'active': 'bg-green-100 text-green-800', 'ongoing': 'bg-green-100 text-green-800', 'scheduled': 'bg-green-100 text-green-800', 'approved': 'bg-green-100 text-green-800', 'inactive': 'bg-red-100 text-red-800', 'blocked': 'bg-red-100 text-red-800', 'cancelled': 'bg-red-100 text-red-800', 'rejected': 'bg-red-100 text-red-800', 'pending': 'bg-blue-100 text-blue-800', 'postponed': 'bg-yellow-100 text-yellow-800', 'completed': 'bg-gray-100 text-gray-800' }; return classes[status] || 'bg-gray-100 text-gray-800'; };
        const renderTable = (tbodyId, data, rowGenerator, colCount) => { const tbody = document.getElementById(tbodyId); if (!tbody) return; tbody.innerHTML = data && data.length > 0 ? data.map(rowGenerator).join('') : `<tr><td colspan="${colCount}" class="text-center p-4 text-gray-500">No data available.</td></tr>`; };
        const renderRecentActivities = () => { const container = document.getElementById('recentActivitiesContainer'); if (!container) return; const logs = db.adminLogs || []; if (logs.length === 0) { container.innerHTML = `<p class="text-gray-500">No recent activities found.</p>`; return; } container.innerHTML = logs.slice(0, 5).map(log => { const d = new Date(log.timestamp); const diff = Math.round((new Date() - d) / 60000); const timeAgo = diff < 60 ? `${diff}m ago` : `${Math.floor(diff / 60)}h ago`; return `<div class="flex items-start"><i class="fas fa-history text-blue-500 mt-1 mr-3"></i><div><p class="text-sm text-gray-800">${log.action}</p><p class="text-xs text-gray-500">${timeAgo} by ${log.admin_name}</p></div></div>`; }).join(''); };
        const populatePointsSportSelect = () => { const select = document.getElementById('pointsSportSelect'); if (!select) return; select.innerHTML = '<option value="">-- Select a Sport --</option>'; (db.sports || []).forEach(sport => { select.innerHTML += `<option value="${sport.sport_id}">${sport.name}</option>`; }); };
        const renderPointsTable = (pointsData) => { renderTable('pointsTableBody', pointsData, (row, index) => `<tr class="hover:bg-gray-50"><td class="px-6 py-4 font-bold text-center">${index + 1}</td><td class="px-6 py-4 font-medium">${row.team_name}</td><td class="px-6 py-4 text-center">${row.played}</td><td class="px-6 py-4 text-center text-green-600">${row.wins}</td><td class="px-6 py-4 text-center text-gray-600">${row.draws}</td><td class="px-6 py-4 text-center text-red-600">${row.losses}</td><td class="px-6 py-4 text-center font-bold text-xl">${row.points}</td></tr>`, 7); };

        const renderFeedback = () => {
            const container = document.getElementById('feedbackContainer');
            if (!container) return;
            const feedbackData = db.feedback || [];
            if (feedbackData.length === 0) {
                container.innerHTML = '<p class="text-center text-gray-500 p-4">No user feedback received yet.</p>';
                return;
            }
            container.innerHTML = feedbackData.map(fb => {
                const readClass = fb.is_read == 1 ? 'bg-gray-50' : 'bg-white border-l-4 border-blue-500';
                const readBtnText = fb.is_read == 1 ? 'Mark as Unread' : 'Mark as Read';
                const readBtnIcon = fb.is_read == 1 ? 'fa-eye-slash' : 'fa-eye';
                const readBtnClass = fb.is_read == 1 ? 'text-gray-500 hover:text-gray-800' : 'text-blue-600 hover:text-blue-900';
                
                return `
                <div class="shadow rounded-lg p-4 ${readClass}">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-bold text-lg">${fb.subject}</p>
                            <p class="text-sm text-gray-600">Category: <span class="font-semibold">${fb.category}</span></p>
                            <p class="text-sm text-gray-500">From: ${fb.user_name} (${fb.user_email}) on ${new Date(fb.submitted_at).toLocaleString()}</p>
                        </div>
                        <div class="flex items-center space-x-4">
                           <button class="${readBtnClass}" data-action="toggle-feedback-read" data-id="${fb.feedback_id}" title="${readBtnText}"><i class="fas ${readBtnIcon}"></i></button>
                           <button class="text-red-600 hover:text-red-900" data-action="delete-feedback" data-id="${fb.feedback_id}" title="Delete Feedback"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-gray-800">${fb.message.replace(/\n/g, '<br>')}</p>
                    </div>
                </div>`;
            }).join('');
        };

        const renderAll = () => {
            const stats = db.stats || {};
            document.getElementById('totalUsersStat').textContent = stats.totalUsers || 0;
            document.getElementById('activeSportsStat').textContent = stats.activeSports || 0;
            document.getElementById('upcomingEventsStat').textContent = stats.upcomingEvents || 0;
            document.getElementById('pendingRegsStat').textContent = stats.pendingRegistrations || 0;
            renderRecentActivities();
            populatePointsSportSelect();
            renderPointsTable([]);
            renderTable('usersTableBody', db.users, user => `<tr class="hover:bg-gray-50"><td class="px-6 py-4">${user.user_id}</td><td class="px-6 py-4">${user.name}</td><td class="px-6 py-4">${user.email}</td><td class="px-6 py-4">${user.student_id || 'N/A'}</td><td class="px-6 py-4">${user.role}</td><td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full ${getStatusClass(user.status)}">${user.status}</span></td><td class="px-6 py-4"><button class="text-blue-600 hover:text-blue-900 mr-4" data-action="edit-user" data-id="${user.user_id}" title="Edit User"><i class="fas fa-edit"></i></button><button class="text-red-600 hover:text-red-900" data-action="delete-user" data-id="${user.user_id}" title="Delete User"><i class="fas fa-trash"></i></button></td></tr>`, 7);
            renderTable('sportsTableBody', db.sports, sport => `<tr class="hover:bg-gray-50"><td class="px-6 py-4 font-medium">${sport.name}</td><td class="px-6 py-4">${sport.max_players}</td><td class="px-6 py-4 font-semibold">${sport.points_for_win} / ${sport.points_for_draw} / ${sport.points_for_loss}</td><td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full ${getStatusClass(sport.status)}">${sport.status}</span></td><td class="px-6 py-4"><button class="text-blue-600 hover:text-blue-900 mr-4" data-action="edit-sport" data-id="${sport.sport_id}" title="Edit Sport"><i class="fas fa-edit"></i></button><button class="text-red-600 hover:text-red-900" data-action="delete-sport" data-id="${sport.sport_id}" title="Delete Sport"><i class="fas fa-trash"></i></button></td></tr>`, 5);
            renderTable('eventsTableBody', db.events, event => `<tr class="hover:bg-gray-50"><td class="px-6 py-4 font-medium">${event.event_name}</td><td class="px-6 py-4">${event.sport_name}</td><td class="px-6 py-4 whitespace-nowrap">${new Date(event.event_date).toLocaleString()}</td><td class="px-6 py-4 text-sm">${event.team1_name || 'N/A'} vs ${event.team2_name || 'N/A'}</td><td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full ${getStatusClass(event.status)}">${event.status}</span></td><td class="px-6 py-4 whitespace-nowrap"><button class="text-blue-600 hover:text-blue-900 mr-4" data-action="edit-event" data-id="${event.event_id}" title="Edit Event"><i class="fas fa-edit"></i></button><button class="text-red-600 hover:text-red-900" data-action="delete-event" data-id="${event.event_id}" title="Delete Event"><i class="fas fa-trash"></i></button></td></tr>`, 6);
            renderTable('schedulesTableBody', db.events, item => { const d = new Date(item.event_date); return `<tr class="hover:bg-gray-50"><td class="px-6 py-4 font-medium">${item.event_name} (${item.sport_name})</td><td class="px-6 py-4">${d.toLocaleDateString()}</td><td class="px-6 py-4">${d.toLocaleTimeString()}</td><td class="px-6 py-4">${item.venue || 'N/A'}</td><td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full ${getStatusClass(item.status)}">${item.status}</span></td></tr>` }, 5);
            renderTable('scoresTableBody', db.events, event => `<tr class="hover:bg-gray-50"><td class="px-6 py-4 font-medium">${event.team1_name || 'Team 1'} vs ${event.team2_name || 'Team 2'}<br><span class="text-xs text-gray-500">${event.event_name}</span></td><td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full ${getStatusClass(event.status)}">${event.status}</span></td><td class="px-6 py-4 font-bold text-xl">${event.team1_score} - ${event.team2_score}</td><td class="px-6 py-4">${event.result.replace(/_/g, ' ').replace('team1', event.team1_name || 'Team 1').replace('team2', event.team2_name || 'Team 2')}</td><td class="px-6 py-4"><button class="bg-blue-600 text-white px-3 py-1 text-sm rounded-md hover:bg-blue-700" data-action="update-score" data-id="${event.event_id}">Update</button></td></tr>`, 5);
            renderTable('teamsTableBody', db.teams, team => `<tr class="hover:bg-gray-50"><td class="px-6 py-4 font-medium">${team.team_name}</td><td class="px-6 py-4">${team.sport_name}</td><td class="px-6 py-4">${team.captain_name || 'N/A'}</td><td class="px-6 py-4">${team.player_count}</td><td class="px-6 py-4"><button class="text-blue-600 hover:text-blue-900 mr-4" data-action="edit-team" data-id="${team.team_id}" title="Edit Team"><i class="fas fa-edit"></i></button><button class="text-red-600 hover:text-red-900" data-action="delete-team" data-id="${team.team_id}" title="Delete Team"><i class="fas fa-trash"></i></button></td></tr>`, 5);
            renderTable('logsTableBody', db.adminLogs, log => `<tr class="hover:bg-gray-50"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${new Date(log.timestamp).toLocaleString()}</td><td class="px-6 py-4 font-medium">${log.admin_name}</td><td class="px-6 py-4">${log.action}</td></tr>`, 3);
            renderTable('registrationsTableBody', db.registrations, reg => `<tr class="hover:bg-gray-50"><td class="px-6 py-4">${reg.user_name}</td><td class="px-6 py-4">${reg.event_name}</td><td class="px-6 py-4">${new Date(reg.registered_at).toLocaleString()}</td><td class="px-6 py-4"><button class="text-green-600 hover:text-green-900 mr-4" data-action="approve-reg" data-id="${reg.registration_id}">Approve</button><button class="text-red-600 hover:text-red-900" data-action="reject-reg" data-id="${reg.registration_id}">Reject</button></td></tr>`, 4);
            renderFeedback();
        };
        
        const showModal = (title, content) => { modalTitle.textContent = title; modalBody.innerHTML = content; modal.classList.remove('modal-hidden'); };
        const hideModal = () => modal.classList.add('modal-hidden');
        const getSportFormHtml = (sport = {}) => `<form id="sportForm" data-id="${sport.sport_id || ''}"><label for="name">Sport Name</label><input type="text" id="name" name="name" value="${sport.name || ''}" required><label for="max_players">Max Players</label><input type="number" id="max_players" name="max_players" value="${sport.max_players || '0'}" required><div style="display:flex; gap: 1rem;"><div style="flex:1;"><label for="points_for_win">Points for Win</label><input type="number" id="points_for_win" name="points_for_win" value="${sport.points_for_win != null ? sport.points_for_win : '3'}" required></div><div style="flex:1;"><label for="points_for_draw">Points for Draw</label><input type="number" id="points_for_draw" name="points_for_draw" value="${sport.points_for_draw != null ? sport.points_for_draw : '1'}" required></div><div style="flex:1;"><label for="points_for_loss">Points for Loss</label><input type="number" id="points_for_loss" name="points_for_loss" value="${sport.points_for_loss != null ? sport.points_for_loss : '0'}" required></div></div><label for="status">Status</label><select id="status" name="status"><option value="active" ${sport.status === 'active' ? 'selected' : ''}>Active</option><option value="inactive" ${sport.status === 'inactive' ? 'selected' : ''}>Inactive</option></select><button type="submit" class="mt-4">${sport.sport_id ? 'Update' : 'Add'} Sport</button></form>`;
        const getEventFormHtml = (event = {}, sports = [], teams = []) => { const isEdit = !!event.event_id; const sportOptions = sports.map(s => `<option value="${s.sport_id}" ${s.sport_id == event.sport_id ? 'selected':''}>${s.name}</option>`).join(''); const teamOptions = teams.map(t => `<option value="${t.team_id}" data-sport-id="${t.sport_id}">${t.team_name}</option>`).join(''); const eventDate = event.event_date ? new Date(event.event_date).toISOString().slice(0, 16) : ''; return `<form id="eventForm" data-id="${isEdit ? event.event_id : ''}"><label for="event_name">Event Name</label><input type="text" id="event_name" name="event_name" value="${event.event_name || ''}" required><label for="sport_id">Sport</label><select id="sport_id" name="sport_id" required><option value="">-- Select Sport --</option>${sportOptions}</select><label for="event_date">Date and Time</label><input type="datetime-local" id="event_date" name="event_date" value="${eventDate}" required><label for="venue">Venue</label><input type="text" id="venue" name="venue" value="${event.venue || ''}"><div style="display:flex; gap:1rem;"><div style="flex:1;"><label for="team1_id">Team 1</label><select id="team1_id" name="team1_id"><option value="">-- Select --</option>${teamOptions}</select></div><div style="flex:1;"><label for="team2_id">Team 2</label><select id="team2_id" name="team2_id"><option value="">-- Select --</option>${teamOptions}</select></div></div>${isEdit ? `<label for="status">Event Status</label><select id="status" name="status"><option value="scheduled" ${event.status === 'scheduled' ? 'selected':''}>Scheduled</option><option value="ongoing" ${event.status === 'ongoing' ? 'selected':''}>Ongoing</option><option value="completed" ${event.status === 'completed' ? 'selected':''}>Completed</option><option value="postponed" ${event.status === 'postponed' ? 'selected':''}>Postponed</option><option value="cancelled" ${event.status === 'cancelled' ? 'selected':''}>Cancelled</option></select>` : ''}<label for="description">Description</label><textarea id="description" name="description" rows="3">${event.description || ''}</textarea><button type="submit" class="mt-4">${isEdit ? 'Update' : 'Create'} Event</button></form><script>const sportSelect = document.getElementById('sport_id'); const teamSelects = [document.getElementById('team1_id'), document.getElementById('team2_id')]; function filterTeams() { const selectedSportId = sportSelect.value; teamSelects.forEach(select => { for (const option of select.options) { if (option.value) { option.style.display = (option.dataset.sportId === selectedSportId) ? '' : 'none'; } } if (select.options[select.selectedIndex] && select.options[select.selectedIndex].style.display === 'none') { select.value = ''; } }); } sportSelect.addEventListener('change', filterTeams); document.getElementById('team1_id').value = '${event.team1_id || ''}'; document.getElementById('team2_id').value = '${event.team2_id || ''}'; filterTeams();<\/script>`;};
        const getTeamFormHtml = (team = {}, sports = []) => { const isEdit = !!team.team_id; const sportOptions = sports.filter(s => s.status === 'active').map(s => `<option value="${s.sport_id}" ${s.sport_id == team.sport_id ? 'selected':''}>${s.name}</option>`).join(''); return `<form id="teamForm" data-id="${isEdit ? team.team_id : ''}"><label for="team_name">Team Name</label><input type="text" id="team_name" name="team_name" value="${team.team_name || ''}" required><label for="sport_id">Sport</label><select id="sport_id" name="sport_id" required><option value="">-- Select a Sport --</option>${sportOptions}</select><button type="submit" class="mt-4">${isEdit ? 'Update' : 'Create'} Team</button></form>`;};
        const getScoreFormHtml = (event = {}) => `<form id="scoreForm" data-id="${event.event_id}"><div style="display:flex; gap:1rem; align-items:center; justify-content:center; text-align:center;"><div style="flex:1;"><label for="team1_score">${event.team1_name || 'Team 1'}</label><input type="number" id="team1_score" name="team1_score" value="${event.team1_score || 0}" required style="font-size:1.5rem; text-align:center;"></div><div style="font-size:2rem; font-weight:bold;">-</div><div style="flex:1;"><label for="team2_score">${event.team2_name || 'Team 2'}</label><input type="number" id="team2_score" name="team2_score" value="${event.team2_score || 0}" required style="font-size:1.5rem; text-align:center;"></div></div><p class="text-sm text-center text-gray-500 mt-2 mb-4">Updating the score will automatically mark the event as 'Completed'.</p><button type="submit" class="mt-4">Update Score & Finalize</button></form>`;

        async function refreshDataAndRender() {
            try {
                const result = await apiRequest('get_all_data');
                db = result.data;
                renderAll();
            } catch (error) { document.body.innerHTML = `<div style="padding: 2rem; text-align: center; color: red;"><strong>Fatal Error:</strong> Could not load data. Check server connection.</div>`; }
        }

        function handleTabSwitch(tabId) {
            pageTitle.textContent = document.querySelector(`.tab-button[data-tab="${tabId}"]`).textContent.trim();
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.getElementById(tabId)?.classList.add('active');
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('bg-blue-900'));
            document.querySelector(`.tab-button[data-tab="${tabId}"]`)?.classList.add('bg-blue-900');
        }

        document.querySelectorAll('.tab-button').forEach(button => button.addEventListener('click', function() { handleTabSwitch(this.dataset.tab); }));
        document.body.addEventListener('click', e => { const ql = e.target.closest('button[data-tab-link]'); if(ql) handleTabSwitch(ql.dataset.tabLink); });
        document.getElementById('pointsSportSelect').addEventListener('change', async function() { const sportId = this.value; if (sportId) { try { const result = await apiRequest('get_points_table', 'GET', { sport_id: sportId }); renderPointsTable(result.data); } catch (error) { renderPointsTable([]); } } else { renderPointsTable([]); } });
        closeModalBtn.addEventListener('click', hideModal);
        modal.addEventListener('click', e => { if(e.target === modal.querySelector('.modal-overlay')) hideModal(); });
        document.getElementById('addSportBtn').addEventListener('click', () => showModal('Add New Sport', getSportFormHtml()));
        document.getElementById('addEventBtn').addEventListener('click', () => showModal('Create New Event', getEventFormHtml({}, db.sports, db.teams)));
        document.getElementById('addTeamBtn').addEventListener('click', () => showModal('Create New Team', getTeamFormHtml({}, db.sports)));
        
        document.body.addEventListener('click', async e => {
            const button = e.target.closest('button[data-action]');
            if (!button) return;
            const { action, id } = button.dataset;
            const recordId = parseInt(id);
            
            if (action.startsWith('delete-')) {
                 if (confirm('Are you sure? This cannot be undone.')) { 
                    await apiRequest(action, 'POST', { id: recordId }).then(refreshDataAndRender); 
                } 
            } 
            else if (action === 'edit-sport') { showModal('Edit Sport', getSportFormHtml(db.sports.find(s => s.sport_id === recordId))); } 
            else if (action === 'edit-event') { showModal('Edit Event', getEventFormHtml(db.events.find(ev => ev.event_id === recordId), db.sports, db.teams)); } 
            else if (action === 'update-score') { showModal(`Update Score`, getScoreFormHtml(db.events.find(ev => ev.event_id === recordId))); } 
            else if (action === 'approve-reg' || action === 'reject-reg' || action === 'toggle-feedback-read') { 
                await apiRequest(action, 'POST', { id: recordId }).then(refreshDataAndRender); 
            } 
            else if (action.startsWith('edit-')) { showAlert('Edit for this module is not implemented yet.', 'info'); }
        });

        document.body.addEventListener('submit', async e => {
            if (!e.target.id) return;
            e.preventDefault();
            const form = e.target;
            const recordId = form.dataset.id ? parseInt(form.dataset.id) : null;
            const data = Object.fromEntries(new FormData(form).entries());
            let action = '';
            if (form.id === 'sportForm') action = recordId ? 'update_sport' : 'add_sport';
            if (form.id === 'eventForm') action = recordId ? 'update_event' : 'add_event';
            if (form.id === 'scoreForm') action = 'update_score';
            if (form.id === 'teamForm') action = recordId ? 'update_team' : 'add_team';
            if (action) {
                if (recordId) data.id = recordId;
                try {
                    await apiRequest(action, 'POST', data).then(result => showAlert(result.message, 'success'));
                    hideModal();
                    await refreshDataAndRender();
                } catch(error) {/* handled */}
            }
        });

        refreshDataAndRender();
    });
    </script>
</body>
</html>
