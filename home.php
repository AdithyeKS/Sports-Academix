<?php
// FILE: home.php (Complete - NOTIFICATIONS REMOVED, CALENDAR WITH DETAILS ADDED, EDIT PROFILE & CHANGE PASSWORD ADDED)
// ==========================================================================================
// SECTION 1: PHP BACKEND LOGIC
// ==========================================================================================

// Start a session to manage user data and messages.
session_start();

// --- PREVENT BROWSER CACHING (SECURITY ADDITION) ---
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Include the database connection file.
require_once 'config.php'; // IMPORTANT: This config.php should point to your SportsAcademix database

// --- SESSION CHECK ---
if (!isset($_SESSION['loggedin'])) {
    header('Location: log.php');
    exit();
}

// --- USER VARIABLE SETUP ---
$logged_in_user_id = $_SESSION['user_id'];
// Re-fetch user name and email from DB to ensure it's up-to-date, especially after profile edits
// NOTE: Assuming your 'users' table has 'user_id' and 'name' columns, matching SportsAcademix schema.
// If using MyCycle DB, these column names would be 'id' and 'username'.
$stmt_user_data = $conn->prepare("SELECT name, email FROM users WHERE user_id = ?");
$stmt_user_data->bind_param("i", $logged_in_user_id);
$stmt_user_data->execute();
$user_data_result = $stmt_user_data->get_result();
$user_data = $user_data_result->fetch_assoc();
$logged_in_user_name = $user_data['name'] ?? 'User';
$logged_in_user_email = $user_data['email'] ?? 'No email set';
$stmt_user_data->close();

// Split the full name into first and last name for the edit profile form
$name_parts = explode(' ', $logged_in_user_name, 2); // Limit to 2 parts in case of middle names
$logged_in_user_first_name = $name_parts[0] ?? '';
$logged_in_user_last_name = $name_parts[1] ?? '';

// Update session name if it was just changed, to reflect immediately in the welcome banner
if (isset($_SESSION['name']) && $_SESSION['name'] !== $logged_in_user_name) {
    $_SESSION['name'] = $logged_in_user_name;
}


// --- FORM SUBMISSION LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle Feedback Submission
    if (isset($_POST['submit_feedback'])) {
        $subject = trim($_POST['feedbackSubject']);
        $message = trim($_POST['feedbackMessage']);

        if (empty($subject) || empty($message)) {
            $_SESSION['form_status'] = ['page' => 'feedback', 'type' => 'error', 'message' => 'Subject and message cannot be empty.'];
        } else {
            // NOTE: This feedback table schema (user_id, subject, message, is_read, submitted_at)
            // is different from your MyCycle DB feedback table (id, user_id, email, subject, message, status, created_at)
            $stmt = $conn->prepare("INSERT INTO feedback (user_id, subject, message, is_read, submitted_at) VALUES (?, ?, ?, 0, NOW())");
            $stmt->bind_param("iss", $logged_in_user_id, $subject, $message);
            $_SESSION['form_status'] = $stmt->execute() ? ['page' => 'feedback', 'type' => 'success', 'message' => 'Thank you! Your feedback has been submitted.'] : ['page' => 'feedback', 'type' => 'error', 'message' => 'Error: Could not submit your feedback. ' . $stmt->error];
            $stmt->close();
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=feedback");
        exit();
    }
    
    // Handle Registration Submission
    if (isset($_POST['submit_registration'])) {
        $sport_id = (int)$_POST['sport_id'];
        $department_id = (int)$_POST['department_id'];

        if ($sport_id > 0 && $department_id > 0) {
            $dept_stmt = $conn->prepare("SELECT department_name FROM departments WHERE department_id = ?");
            $dept_stmt->bind_param("i", $department_id);
            $dept_stmt->execute();
            $dept_result = $dept_stmt->get_result();
            if ($dept_result->num_rows > 0) {
                $department_name = $dept_result->fetch_assoc()['department_name'];

                // Check for existing pending/approved registration for this user, sport, and department
                $check_stmt = $conn->prepare("SELECT registration_id FROM registrations WHERE user_id = ? AND sport_id = ? AND department = ? AND (status = 'pending' OR status = 'approved')");
                $check_stmt->bind_param("iis", $logged_in_user_id, $sport_id, $department_name);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    $_SESSION['form_status'] = ['page' => 'registration', 'type' => 'error', 'message' => 'You already have a pending or approved registration for this sport and department.'];
                } else {
                    $stmt = $conn->prepare("INSERT INTO registrations (user_id, sport_id, department, status, registered_at) VALUES (?, ?, ?, 'pending', NOW())");
                    $stmt->bind_param("iis", $logged_in_user_id, $sport_id, $department_name);
                    $_SESSION['form_status'] = $stmt->execute() ? ['page' => 'registration', 'type' => 'success', 'message' => 'Your registration request has been sent!'] : ['page' => 'registration', 'type' => 'error', 'message' => 'Error: Could not submit your request. ' . $stmt->error];
                    $stmt->close();
                }
                $check_stmt->close();
            } else { 
                $_SESSION['form_status'] = ['page' => 'registration', 'type' => 'error', 'message' => 'Error: The selected department is invalid.']; 
            }
            $dept_stmt->close();
        } else { 
            $_SESSION['form_status'] = ['page' => 'registration', 'type' => 'error', 'message' => 'Error: Please select a valid sport and department.']; 
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=registration");
        exit();
    }
    
    // Handle Match Appeal Submission (from My Profile page)
    if (isset($_POST['submit_appeal'])) {
        $event_id = (int)$_POST['event_id'];
        $reason = trim($_POST['appeal_reason']);

        if ($event_id > 0 && !empty($reason)) {
            $check_stmt = $conn->prepare("SELECT appeal_id FROM match_appeals WHERE user_id = ? AND event_id = ?");
            $check_stmt->bind_param("ii", $logged_in_user_id, $event_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $_SESSION['form_status'] = ['page' => 'profile', 'type' => 'error', 'message' => 'You have already submitted an appeal for this match.'];
            } else {
                $stmt = $conn->prepare("INSERT INTO match_appeals (event_id, user_id, reason) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $event_id, $logged_in_user_id, $reason);
                if ($stmt->execute()) {
                    $_SESSION['form_status'] = ['page' => 'profile', 'type' => 'success', 'message' => 'Your appeal has been submitted successfully.'];
                } else {
                    $_SESSION['form_status'] = ['page' => 'profile', 'type' => 'error', 'message' => 'Error: Could not submit your appeal. ' . $stmt->error];
                }
                $stmt->close();
            }
            $check_stmt->close();
        } else {
            $_SESSION['form_status'] = ['page' => 'profile', 'type' => 'error', 'message' => 'Error: Please provide a reason for your appeal.'];
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=profile");
        exit();
    }

    // Handle Update Profile (Edit Name)
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $new_full_name = trim($first_name . ' ' . $last_name);

        // Server-side validation (mirroring client-side for security)
        $name_regex_php = '/^[A-Z][a-zA-Z]*$/';
        if (empty($first_name) || empty($last_name)) {
            $_SESSION['form_status'] = ['page' => 'edit_profile', 'type' => 'error', 'message' => 'First Name and Last Name cannot be empty.'];
        } elseif (!preg_match($name_regex_php, $first_name)) {
            $_SESSION['form_status'] = ['page' => 'edit_profile', 'type' => 'error', 'message' => 'First Name must start with a capital letter, contain only letters, and no spaces.'];
        } elseif (!preg_match($name_regex_php, $last_name)) {
            $_SESSION['form_status'] = ['page' => 'edit_profile', 'type' => 'error', 'message' => 'Last Name must start with a capital letter, contain only letters, and no spaces.'];
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new_full_name, $logged_in_user_id);
            if ($stmt->execute()) {
                $_SESSION['name'] = $new_full_name; // Update session variable immediately
                $_SESSION['form_status'] = ['page' => 'edit_profile', 'type' => 'success', 'message' => 'Your name has been updated successfully.'];
            } else {
                $_SESSION['form_status'] = ['page' => 'edit_profile', 'type' => 'error', 'message' => 'Error: Could not update your name. ' . $stmt->error];
            }
            $stmt->close();
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=edit_profile");
        exit();
    }

    // Handle Change Password
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_new_password = $_POST['confirm_new_password'];

        // Fetch current hashed password from DB
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $logged_in_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Server-side validation (mirroring client-side for security)
        $password_regex_php = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';

        if (!$user || !password_verify($current_password, $user['password'])) {
            $_SESSION['form_status'] = ['page' => 'change_password', 'type' => 'error', 'message' => 'Current password is incorrect.'];
        } elseif ($new_password !== $confirm_new_password) {
            $_SESSION['form_status'] = ['page' => 'change_password', 'type' => 'error', 'message' => 'New password and confirmation do not match.'];
        } elseif (!preg_match($password_regex_php, $new_password)) {
            $_SESSION['form_status'] = ['page' => 'change_password', 'type' => 'error', 'message' => 'New password must be at least 8 characters long and include uppercase, lowercase, numbers, and special characters.'];
        } else {
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, otp_hash = NULL, otp_expires_at = NULL WHERE user_id = ?"); // Clear OTP fields on successful password change
            $stmt->bind_param("si", $hashed_new_password, $logged_in_user_id);
            if ($stmt->execute()) {
                $_SESSION['form_status'] = ['page' => 'change_password', 'type' => 'success', 'message' => 'Your password has been changed successfully.'];
            } else {
                $_SESSION['form_status'] = ['page' => 'change_password', 'type' => 'error', 'message' => 'Error: Could not change your password. ' . $stmt->error];
            }
            $stmt->close();
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=change_password");
        exit();
    }
}

// --- DATA FETCHING FOR PAGE DISPLAY ---
// These queries assume the Sports Academix database schema.
// They will fail if run against the MyCycle database.
function getSportStats($conn, $sport_id) {
    $stats = ['teams' => 0, 'players' => 0, 'matches' => 0];

    // Teams count
    $stmt_teams = $conn->prepare("SELECT COUNT(*) as count FROM teams WHERE sport_id = ?");
    $stmt_teams->bind_param("i", $sport_id);
    $stmt_teams->execute();
    $teams_result = $stmt_teams->get_result();
    if ($teams_result) $stats['teams'] = $teams_result->fetch_assoc()['count'];
    $stmt_teams->close();

    // Players count
    $stmt_players = $conn->prepare("SELECT COUNT(DISTINCT tm.user_id) as count FROM team_members tm JOIN teams t ON tm.team_id = t.team_id WHERE t.sport_id = ?");
    $stmt_players->bind_param("i", $sport_id);
    $stmt_players->execute();
    $players_result = $stmt_players->get_result();
    if ($players_result) $stats['players'] = $players_result->fetch_assoc()['count'];
    $stmt_players->close();

    // Matches count
    $stmt_matches = $conn->prepare("SELECT COUNT(*) as count FROM events WHERE sport_id = ? AND status = 'schedule'");
    $stmt_matches->bind_param("i", $sport_id);
    $stmt_matches->execute();
    $matches_result = $stmt_matches->get_result();
    if ($matches_result) $stats['matches'] = $matches_result->fetch_assoc()['count'];
    $stmt_matches->close();

    return $stats;
}

// Fetch stats for each sport
$cricket_stats = getSportStats($conn, 1);
$football_stats = getSportStats($conn, 2);
$basketball_stats = getSportStats($conn, 5); // Assuming Basketball is sport_id 5
$volleyball_stats = getSportStats($conn, 3);

// Fetch recent completed matches
$recent_matches_stmt = $conn->prepare("SELECT e.event_name, s.name as sport_name, t1.team_name as team1_name, t2.team_name as team2_name, DATE_FORMAT(e.event_date, '%b %d, %Y') as event_date, e.team1_score, e.team2_score, e.result FROM events e JOIN sports s ON e.sport_id = s.sport_id LEFT JOIN teams t1 ON e.team1_id = t1.team_id LEFT JOIN teams t2 ON e.team2_id = t2.team_id WHERE e.status = 'completed' ORDER BY e.event_date DESC LIMIT 5");
$recent_matches_stmt->execute();
$recent_matches = $recent_matches_stmt->get_result();
$recent_matches_stmt->close();


// Fetch upcoming scheduled matches
$sql_upcoming = "SELECT e.event_id, e.event_name, s.name as sport_name, t1.team_name as team1_name, t2.team_name as team2_name, DATE_FORMAT(e.event_date, '%b %d, %l:%i %p') as event_date, e.venue FROM events e JOIN sports s ON e.sport_id = s.sport_id LEFT JOIN teams t1 ON e.team1_id = t1.team_id LEFT JOIN teams t2 ON e.team2_id = t2.team_id WHERE e.status = 'schedule' AND e.event_date > NOW() ORDER BY e.event_date ASC LIMIT 5";
$stmt_upcoming = $conn->prepare($sql_upcoming);
$stmt_upcoming->execute();
$upcoming_schedule = $stmt_upcoming->get_result();
$stmt_upcoming->close();

// Fetch active sports for registration form
$sports_for_reg = $conn->query("SELECT sport_id, name FROM sports WHERE status = 'active' ORDER BY name");

// Fetch departments for registration form
$departments_for_reg = $conn->query("SELECT department_id, department_name FROM departments ORDER BY department_name");

// Fetch user's department registration history
$stmt_history = $conn->prepare("SELECT r.status, r.registered_at, r.department, s.name as sport_name FROM registrations r JOIN sports s ON r.sport_id = s.sport_id WHERE r.user_id = ? AND r.team_id IS NULL ORDER BY r.registered_at DESC");
$stmt_history->bind_param("i", $logged_in_user_id);
$stmt_history->execute();
$user_registrations = $stmt_history->get_result();
$stmt_history->close();

// Fetch user's teams
$stmt_teams = $conn->prepare("SELECT t.team_name, s.name as sport_name FROM team_members tm JOIN teams t ON tm.team_id = t.team_id JOIN sports s ON t.sport_id = s.sport_id WHERE tm.user_id = ? ORDER BY t.team_name ASC");
$stmt_teams->bind_param("i", $logged_in_user_id);
$stmt_teams->execute();
$user_teams = $stmt_teams->get_result();
$stmt_teams->close();

// Fetch user's completed match history
$stmt_matches = $conn->prepare("
    SELECT 
        e.event_id, e.event_date, s.name as sport_name, 
        t1.team_name as team1_name, t2.team_name as team2_name, 
        e.team1_score, e.team2_score, e.result, e.team1_id, e.team2_id, 
        tm.team_id as user_team_id 
    FROM events e 
    JOIN sports s ON e.sport_id = s.sport_id 
    LEFT JOIN teams t1 ON e.team1_id = t1.team_id 
    LEFT JOIN teams t2 ON e.team2_id = t2.team_id 
    JOIN team_members tm ON (tm.team_id = e.team1_id OR tm.team_id = e.team2_id) 
    WHERE tm.user_id = ? AND e.status = 'completed' 
    GROUP BY e.event_id, e.event_date, s.name, t1.team_name, t2.team_name, e.team1_score, e.team2_score, e.result, e.team1_id, e.team2_id, user_team_id
    ORDER BY e.event_date DESC
");
$stmt_matches->bind_param("i", $logged_in_user_id);
$stmt_matches->execute();
$match_history = $stmt_matches->get_result();
$stmt_matches->close();

// Fetch user's submitted appeals
$stmt_appeals = $conn->prepare("
    SELECT 
        ma.reason, ma.submitted_at, ma.status,
        e.event_name, t1.team_name as team1_name, t2.team_name as team2_name
    FROM match_appeals ma
    JOIN events e ON ma.event_id = e.event_id
    LEFT JOIN teams t1 ON e.team1_id = t1.team_id
    LEFT JOIN teams t2 ON e.team2_id = t2.team_id
    WHERE ma.user_id = ?
    ORDER BY ma.submitted_at DESC
");
$stmt_appeals->bind_param("i", $logged_in_user_id);
$stmt_appeals->execute();
$user_appeals = $stmt_appeals->get_result();
$stmt_appeals->close();

// Determine current page for active navigation highlighting
$currentPage = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportsAcademix - <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $currentPage))); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Vanilla Calendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/vanilla-calendar-pro/build/vanilla-calendar.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-hue: 245;
            --primary: hsl(var(--primary-hue), 85%, 60%);
            --bg-dark: #0f172a;
            --bg-glass: rgba(30, 41, 59, 0.5);
            --border-glass: rgba(255, 255, 255, 0.1);
            --text-light: #f1f5f9;
            --text-muted: #94a3b8;
            --shadow-lg: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }
        body {
            font-family: 'Poppins', sans-serif; background-color: var(--bg-dark);
            background-image: radial-gradient(circle at 15% 15%, hsla(var(--primary-hue), 80%, 25%, 0.3) 0, transparent 40%),
                              radial-gradient(circle at 85% 75%, hsla(190, 80%, 20%, 0.2) 0, transparent 40%);
            background-attachment: fixed; margin: 0; color: var(--text-light);
        }
        .top-navbar {
            display: flex; align-items: center; padding: 0 1.5rem; height: 70px;
            background-color: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-glass); position: sticky; top: 0; z-index: 100;
        }
        .menu-toggle {
            background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer; padding: 0.5rem; transition: color 0.2s;
        }
        .menu-toggle:hover {
            color: var(--text-light);
        }
        .brand { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; color: var(--text-light); margin: 0 auto; }
        .brand h1 { font-size: 1.25rem; font-weight: 600; margin: 0; }
        .header-actions {
            display: flex; align-items: center; gap: 1rem;
            position: relative; /* Needed for calendar popup positioning */
        }
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100%; width: 280px;
            background-color: rgba(17, 24, 39, 0.9); backdrop-filter: blur(10px);
            border-right: 1px solid var(--border-glass);
            display: flex; flex-direction: column; z-index: 200;
            transform: translateX(-100%);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar.open { transform: translateX(0); }
        .sidebar-header { padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
        .sidebar-header h2 { font-size: 1.25rem; font-weight: 600; margin: 0; color: #fff; }
        .close-sidebar { background: none; border: none; color: var(--text-muted); font-size: 1.75rem; cursor: pointer; }
        nav { flex: 1; overflow-y: auto; padding: 1rem 0; }
        nav ul { list-style: none; padding: 0; margin: 0; }
        .nav-link { display: flex; align-items: center; height: 50px; text-decoration: none; color: var(--text-muted); margin: 0.5rem 1rem; border-radius: 0.5rem; gap: 1rem; }
        .nav-link i { font-size: 1.1rem; width: 50px; text-align: center; flex-shrink: 0; }
        .nav-link .link-text { white-space: nowrap; font-weight: 500; }
        .nav-link:hover { background-color: rgba(255, 255, 255, 0.05); color: var(--text-light); }
        .nav-link.active { background: var(--primary); color: white; }
        .sidebar-footer { padding: 1.5rem 1rem; border-top: 1px solid var(--border-glass); }
        .logout-button { justify-content: center; }
        .main-content { flex: 1; }
        main { padding: 2.5rem; max-width: 1200px; margin: 0 auto; }
        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 150; opacity: 0; pointer-events: none; transition: opacity 0.4s ease; }
        .overlay.active { opacity: 1; pointer-events: all; }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .welcome-banner, .sport-card, .content-box, .page-content {
            background: var(--bg-glass); border: 1px solid var(--border-glass); backdrop-filter: blur(10px);
            box-shadow: var(--shadow-lg); border-radius: 1rem;
        }
        .welcome-banner { color: #fff; padding: 2.5rem; margin-bottom: 2.5rem; }
        .welcome-banner h1 { margin: 0; font-size: 1.75rem; }
        .welcome-banner p { margin: 0.25rem 0 0; color: #d1d5db; }
        h2, h3.h3 { font-size: 1.5rem; font-weight: 600; color: var(--text-light); margin-bottom: 1.5rem; }
        .grid { display: grid; gap: 1.5rem; }
        .grid-cols-4 { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
        .grid-cols-2 { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
        .sport-card{padding:1.5rem; transition: all .3s ease}.sport-card:hover{transform:translateY(-8px); box-shadow: 0 10px 40px rgba(0,0,0,0.4);}
        .sport-card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem}.sport-card h3{font-size:1.25rem;font-weight:700;color:var(--text-light)}.sport-card-icon{padding:.75rem;border-radius:50%}.sport-card-icon i{font-size:2rem}.icon-bg-green{background-color:#052e16}.icon-green{color:#4ade80}.icon-bg-red{background-color:#450a0a}.icon-red{color:#f87171}.icon-bg-purple{background-color:#2e1065}.icon-purple{color:#a78bfa}.icon-bg-blue{background-color:#1e3a8a}.icon-blue{color:#60a5fa}.sport-card-body p{margin:.25rem 0}.text-gray{color:var(--text-muted)}.font-semibold{font-weight:600}.text-lg{font-size:1.125rem}.card-button{margin-top:1rem;width:100%;padding:.5rem 1rem;color:#fff;border:none;border-radius:.5rem;cursor:pointer; transition: background-color 0.3s ease;}.btn-green{background-color:#166534}.btn-green:hover{background-color:#15803d}.btn-red{background-color:#991b1b}.btn-red:hover{background-color:#b91c1c}.btn-purple{background-color:#5b21b6}.btn-purple:hover{background-color:#7e22ce}.btn-blue{background-color:#1e40af}.btn-blue:hover{background-color:#1d4ed8}
        .content-box { padding: 0; }
        .content-box h3 { padding: 1.5rem; font-size: 1.1rem; font-weight: 600; margin: 0; border-bottom: 1px solid var(--border-glass); }
        .list-item { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-glass); }
        .list-item:last-child { border-bottom: none; }
        .list-item-icon { font-size: 1rem; width: 36px; height: 36px; display: grid; place-items: center; border-radius: 50%; color: var(--primary); background-color: rgba(0,0,0,0.2); }
        .list-item-details { flex-grow: 1; } .list-item-details p { margin: 0; } .font-medium { font-weight: 500; }
        .text-sm { font-size: 0.875rem; color: var(--text-muted); } .list-item-aside { text-align: right; }
        .page-content { padding: 2.5rem; }
        .divider { border-top: 1px solid var(--border-glass); margin: 2.5rem 0; }
        .form-group { margin-bottom: 1.5rem; position: relative; } /* Added position: relative for validation popups */
        form label { display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--text-light); }
        .form-select, .form-textarea, .form-input { width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-glass); background-color: rgba(17, 24, 39, 0.8); color: var(--text-light); border-radius: 0.5rem; box-sizing: border-box; font-family: 'Poppins', sans-serif; transition: all 0.2s ease; }
        .form-select:focus, .form-textarea:focus, .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px hsla(var(--primary-hue), 85%, 60%, 0.3); outline: none; }
        .submit-btn { background: var(--primary); color: #fff; font-weight: 600; padding: 0.85rem 1.5rem; border: none; border-radius: 0.5rem; cursor: pointer; transition: all 0.3s ease; }
        .submit-btn:hover { background-color: hsl(var(--primary-hue), 85%, 50%); }
        .status-message { padding: 1rem; margin-bottom: 1.5rem; border-radius: .5rem; border: 1px solid transparent; }
        .status-success { background-color: rgba(34, 197, 94, 0.2); color: #4ade80; border-color: #22c55e; }
        .status-error { background-color: rgba(239, 68, 68, 0.2); color: #f87171; border-color: #ef4444; }
        .status-tag { padding: .25rem .75rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 500; }
        .status-pending { background-color: rgba(234, 179, 8, 0.2); color: #facc15; }
        .status-approved { background-color: rgba(34, 197, 94, 0.2); color: #4ade80; }
        .status-rejected { background-color: rgba(239, 68, 68, 0.2); color: #f87171; }
        .status-reviewed { background-color: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .status-resolved { background-color: rgba(16, 185, 129, 0.2); color: #34d399; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: .75rem 1rem; text-align: left; border-bottom: 1px solid var(--border-glass); }
        .data-table th { font-weight: 500; font-size: 0.8rem; color: var(--text-muted); }
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); z-index: 1000; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
        .modal.active { opacity: 1; pointer-events: all; }
        .modal-content { background: #1e293b; padding: 2rem; border-radius: 1rem; width: 90%; max-width: 500px; border: 1px solid var(--border-glass); transform: scale(0.9); transition: transform 0.3s ease; }
        .modal.active .modal-content { transform: scale(1); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h3 { margin: 0; font-size: 1.25rem; }
        .modal-header .close-modal { background: none; border: none; font-size: 1.5rem; color: var(--text-muted); cursor: pointer; }
        .appeal-btn { background: none; border: 1px solid #f59e0b; color: #f59e0b; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.8rem; cursor: pointer; transition: all 0.2s ease; }
        .appeal-btn:hover { background: #f59e0b; color: #1e293b; }

        /* NEW: Calendar specific styles */
        .calendar-icon {
            font-size: 1.5rem;
            color: #FFD700; /* Gold color for the calendar icon */
            cursor: pointer;
            padding: 0.5rem;
            transition: color 0.2s;
        }
        .calendar-icon:hover {
            color: #FFA500; /* Darker orange on hover */
        }
        .calendar-popup {
            display: none; /* Hidden by default */
            position: absolute;
            top: calc(100% + 10px); /* Position below the header */
            right: 0;
            background-color: #1e293b;
            border: 1px solid var(--border-glass);
            border-radius: 0.5rem;
            box-shadow: var(--shadow-lg);
            width: 320px; /* Adjust size as needed */
            z-index: 50;
            padding: 1rem;
        }
        .calendar-popup.active {
            display: block;
        }
        #calendar-container {
            width: 100%;
            height: auto;
            /* Adjust Vanilla Calendar variables for dark theme */
            --vc-font-family: 'Poppins', sans-serif; /* Use your main font */
            --vc-primary-color: var(--primary); /* Blue for selected days */
            --vc-selected-color: var(--primary); /* Blue for selected days */
            --vc-range-color: var(--primary); /* Blue for range */
            --vc-text-color: var(--text-light);
            --vc-light-text-color: var(--text-muted); /* Less prominent text */
            --vc-border-color: var(--border-glass);
            --vc-background-color: #1e293b;
            --vc-week-background-color: #1e293b; /* Same as background */
            --vc-day-hover-background-color: rgba(255,255,255,0.05);

            /* For specific marked dates, ensure these are distinct */
            --vc-mark-color-1: #4ade80; /* Green for upcoming events */
            --vc-mark-color-2: #facc15; /* Optional: another color */
        }
        /* Custom styling for marked dates in Vanilla Calendar */
        .vanilla-calendar .vanilla-calendar-day__btn.event-marked {
            background-color: rgba(67, 190, 100, 0.2); /* Light green/teal background for marked dates */
            color: #4ade80; /* Brighter text color for marked dates */
            font-weight: 600;
        }
        .vanilla-calendar .vanilla-calendar-day__btn.event-marked:hover {
            background-color: rgba(67, 190, 100, 0.3); /* Slightly darker on hover */
        }
        /* Dot styling remains, can be customized further if needed */
        .vanilla-calendar .vanilla-calendar-day__btn.event-marked::after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            background-color: #4ade80; /* Green dot for events */
            border-radius: 50%;
            pointer-events: none; /* Ensure it doesn't interfere with click events */
        }
        
        /* NEW: Event Details Popup (Tooltip) styles */
        #event-details-popup {
            display: none;
            position: absolute;
            z-index: 100;
            background-color: #2d3748; /* Darker background */
            color: #f1f5f9;
            border: 1px solid #4a5568;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
            max-width: 250px;
            font-size: 0.875rem;
            line-height: 1.4;
            pointer-events: none; /* Allows clicks to pass through to elements behind it if needed */
            opacity: 0;
            transition: opacity 0.2s ease-in-out, transform 0.2s ease-in-out;
            transform: translateY(10px);
        }
        #event-details-popup.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        #event-details-popup h4 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-size: 1rem;
            color: var(--primary);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 0.5rem;
        }
        #event-details-popup p {
            margin: 0.25rem 0;
        }
        #event-details-popup .event-item {
            padding-top: 0.5rem;
            margin-top: 0.5rem;
            border-top: 1px dashed rgba(255,255,255,0.05);
        }
        #event-details-popup .event-item:first-of-type {
            border-top: none;
            padding-top: 0;
            margin-top: 0;
        }
        #event-details-popup .event-no-details {
            color: var(--text-muted);
            font-style: italic;
        }

        /* NEW CSS FOR VALIDATION POPUP (from log.php) */
        .validation-popup {
            position: absolute;
            top: -45px; /* Position it above the input box */
            left: 0;
            background-color: #D32F2F; /* A material design error red */
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            z-index: 10;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .validation-popup.show {
            opacity: 1;
            transform: translateY(0);
        }
        .validation-popup::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 20px;
            border-width: 5px;
            border-style: solid;
            border-color: #D32F2F transparent transparent transparent;
        }
    </style>
</head>
<body>
    <div id="overlay" class="overlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Menu</h2>
            <button class="close-sidebar" id="close-sidebar"><i class="fas fa-times"></i></button>
        </div>
        <nav>
            <ul>
                <li><a href="?page=dashboard" class="nav-link <?php if($currentPage == 'dashboard') echo 'active'; ?>"><i class="fas fa-home"></i><span class="link-text">Dashboard</span></a></li>
                <li><a href="?page=profile" class="nav-link <?php if($currentPage == 'profile') echo 'active'; ?>"><i class="fas fa-user"></i><span class="link-text">My Profile</span></a></li>
                <li><a href="?page=edit_profile" class="nav-link <?php if($currentPage == 'edit_profile') echo 'active'; ?>"><i class="fas fa-edit"></i><span class="link-text">Edit Profile</span></a></li>
                <li><a href="?page=change_password" class="nav-link <?php if($currentPage == 'change_password') echo 'active'; ?>"><i class="fas fa-key"></i><span class="link-text">Change Password</span></a></li>
                <li><a href="?page=registration" class="nav-link <?php if($currentPage == 'registration') echo 'active'; ?>"><i class="fas fa-user-plus"></i><span class="link-text">Sport Registration</span></a></li>
                <li><a href="?page=appeals" class="nav-link <?php if($currentPage == 'appeals') echo 'active'; ?>"><i class="fas fa-gavel"></i><span class="link-text">My Appeals</span></a></li>
                <li><a href="?page=about" class="nav-link <?php if($currentPage == 'about') echo 'active'; ?>"><i class="fas fa-info-circle"></i><span class="link-text">About</span></a></li>
                <li><a href="?page=feedback" class="nav-link <?php if($currentPage == 'feedback') echo 'active'; ?>"><i class="fas fa-comment-dots"></i><span class="link-text">Feedback</span></a></li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="nav-link logout-button"><i class="fas fa-sign-out-alt"></i><span class="link-text">Logout</span></a>
        </div>
    </aside>
    
    <div class="main-content">
        <header class="top-navbar">
            <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <a href="?page=dashboard" class="brand">
                <h1>Sports Academix</h1>
            </a>
            <div class="header-actions">
                <!-- Calendar Icon and Popup -->
                <button class="calendar-icon" id="calendar-toggle" title="View Schedule"><i class="fas fa-calendar-alt"></i></button>
                <div class="calendar-popup" id="calendar-popup">
                    <div id="calendar-container"></div>
                </div>
            </div>
        </header>
        <main>
            <div id="dashboard" class="tab-content <?php if($currentPage == 'dashboard') echo 'active'; ?>">
                <div class="welcome-banner"><h1>Welcome back, <?php echo htmlspecialchars($logged_in_user_name); ?>!</h1><p>Here's your overview of all sports activities. Ready to play?</p></div>
                <h2>Sports Overview</h2>
                <div class="grid grid-cols-4">
                    <div class="sport-card cricket-card"><div class="sport-card-header"><h3>Cricket</h3><div class="sport-card-icon icon-bg-green"><i class="fas fa-baseball-ball icon-green"></i></div></div><div class="sport-card-body"><div><p class="text-gray">Teams</p><p class="text-lg font-semibold"><?php echo $cricket_stats['teams']; ?></p></div><div><p class="text-gray">Players</p><p class="text-lg font-semibold"><?php echo $cricket_stats['players']; ?></p></div></div><a href="cricket.php"><button class="card-button btn-green">View Details</button></a></div>
                    <div class="sport-card football-card"><div class="sport-card-header"><h3>Football</h3><div class="sport-card-icon icon-bg-red"><i class="fas fa-futbol icon-red"></i></div></div><div class="sport-card-body"><div><p class="text-gray">Teams</p><p class="text-lg font-semibold"><?php echo $football_stats['teams']; ?></p></div><div><p class="text-gray">Players</p><p class="text-lg font-semibold"><?php echo $football_stats['players']; ?></p></div></div><a href="football.php"><button class="card-button btn-red">View Details</button></a></div>
                    <div class="sport-card basketball-card"><div class="sport-card-header"><h3>Basketball</h3><div class="sport-card-icon icon-bg-purple"><i class="fas fa-basketball-ball icon-purple"></i></div></div><div class="sport-card-body"><div><p class="text-gray">Teams</p><p class="text-lg font-semibold"><?php echo $basketball_stats['teams']; ?></p></div><div><p class="text-lg font-semibold"><?php echo $basketball_stats['players']; ?></p></div></div><a href="basketball.php"><button class="card-button btn-purple">View Details</button></a></div>
                    <div class="sport-card volleyball-card"><div class="sport-card-header"><h3>Volleyball</h3><div class="sport-card-icon icon-bg-blue"><i class="fas fa-volleyball-ball icon-blue"></i></div></div><div class="sport-card-body"><div><p class="text-gray">Teams</p><p class="text-lg font-semibold"><?php echo $volleyball_stats['teams']; ?></p></div><div><p class="text-lg font-semibold"><?php echo $volleyball_stats['players']; ?></p></div></div><a href="volleyball.php"><button class="card-button btn-blue">View Details</button></a></div>
                </div>
                <div class="grid grid-cols-2" style="margin-top: 2.5rem;">
                    <div class="content-box"><h3>Upcoming Schedule</h3><?php if ($upcoming_schedule->num_rows > 0): while($schedule = $upcoming_schedule->fetch_assoc()): ?><div class="list-item"><div class="list-item-icon"><i class="fas fa-calendar-day"></i></div><div class="list-item-details"><p class="font-medium"><?php echo htmlspecialchars($schedule['team1_name'] ?? 'TBD'); ?> vs <?php echo htmlspecialchars($schedule['team2_name'] ?? 'TBD'); ?></p><p class="text-sm"><?php echo htmlspecialchars($schedule['sport_name']); ?></p></div><div class="list-item-aside"><p class="font-medium"><?php echo $schedule['event_date']; ?></p><p class="text-sm"><?php echo htmlspecialchars($schedule['venue']); ?></p></div></div><?php endwhile; else: ?><p style="text-align: center; padding: 1rem;">No upcoming matches.</p><?php endif; ?></div>
                    <div class="content-box"><h3>Recent Results</h3><?php if($recent_matches->num_rows > 0): mysqli_data_seek($recent_matches, 0); while($match = $recent_matches->fetch_assoc()): ?><div class="list-item"><div class="list-item-icon"><i class="fas fa-trophy"></i></div><div class="list-item-details"><p class="font-medium"><?php echo htmlspecialchars($match['team1_name']); ?> vs <?php echo htmlspecialchars($match['team2_name']); ?></p><p class="text-sm"><?php echo htmlspecialchars($match['sport_name']); ?></p></div><div class="list-item-aside"><p class="font-medium"><?php echo $match['team1_score']; ?> - <?php echo $match['team2_score']; ?></p><p class="text-sm"><?php echo str_replace('_', ' ', ucfirst($match['result'])); ?></p></div></div><?php endwhile; else: ?><p style="text-align: center; padding: 1rem;">No recent results.</p><?php endif; ?></div>
                </div>
            </div>
            <div id="profile" class="tab-content <?php if($currentPage == 'profile') echo 'active'; ?>"><div class="page-content"><h2>My Profile</h2><?php if (isset($_SESSION['form_status']) && $_SESSION['form_status']['page'] === 'profile') { $status = $_SESSION['form_status']; echo '<div class="status-message ' . ($status['type'] === 'success' ? 'status-success' : 'status-error') . '">' . htmlspecialchars($status['message']) . '</div>'; unset($_SESSION['form_status']); } ?><div class="content-box" style="margin-bottom: 2rem;"><div class="list-item"><div class="list-item-icon"><i class="fas fa-user"></i></div><div class="list-item-details"><p class="text-sm">Full Name</p><p class="font-medium"><?php echo htmlspecialchars($logged_in_user_name); ?></p></div></div><div class="list-item"><div class="list-item-icon"><i class="fas fa-envelope"></i></div><div class="list-item-details"><p class="text-sm">Email Address</p><p class="font-medium"><?php echo htmlspecialchars($logged_in_user_email); ?></p></div></div></div><h3 class="h3">My Teams</h3><div class="content-box" style="margin-bottom: 2rem;"><?php if ($user_teams->num_rows > 0): ?><?php while($team = $user_teams->fetch_assoc()): ?><div class="list-item"><div class="list-item-icon"><i class="fas fa-users"></i></div><div class="list-item-details"><p class="font-medium"><?php echo htmlspecialchars($team['team_name']); ?></p></div><div class="list-item-aside"><p class="text-sm"><?php echo htmlspecialchars($team['sport_name']); ?></p></div></div><?php endwhile; ?><?php else: ?><p style="text-align:center; padding: 1rem;">You are not a member of any teams yet.</p><?php endif; ?></div><h3 class="h3">Match History</h3><div class="content-box" style="margin-bottom: 2rem;"><table class="data-table"><thead><tr><th>Date</th><th>Match</th><th>Score</th><th>Outcome</th><th>Actions</th></tr></thead><tbody><?php if ($match_history->num_rows > 0): ?><?php while($match = $match_history->fetch_assoc()): ?><?php $outcome = 'Draw'; if ($match['result'] === 'team1_win') { $outcome = ($match['user_team_id'] == $match['team1_id']) ? 'Win' : 'Loss'; } elseif ($match['result'] === 'team2_win') { $outcome = ($match['user_team_id'] == $match['team2_id']) ? 'Win' : 'Loss'; } ?><tr><td><?php echo date('M d, Y', strtotime($match['event_date'])); ?></td><td><?php echo htmlspecialchars($match['team1_name']) . " vs " . htmlspecialchars($match['team2_name']); ?></td><td><?php echo $match['team1_score'] . " - " . $match['team2_score']; ?></td><td><?php echo $outcome; ?></td><td><button class="appeal-btn" data-event-id="<?php echo $match['event_id']; ?>" data-match-details="<?php echo htmlspecialchars($match['team1_name'] . ' vs ' . $match['team2_name']); ?>">Appeal</button></td></tr><?php endwhile; ?><?php else: ?><tr><td colspan="5" style="text-align:center; padding: 1rem;">You have no completed match history.</td></tr><?php endif; ?></tbody></table></div><h3 class="h3">Department Registration History</h3><div class="content-box"><?php if ($user_registrations->num_rows > 0): ?><?php mysqli_data_seek($user_registrations, 0); while($reg = $user_registrations->fetch_assoc()): ?><div class="list-item"><div class="list-item-icon"><i class="fas fa-clipboard-list"></i></div><div class="list-item-details"><p class="font-medium">Sport: <?php echo htmlspecialchars($reg['sport_name']); ?> (<?php echo htmlspecialchars($reg['department']); ?>)</p><p class="text-sm text-gray">Requested on: <?php echo date('M d, Y, g:i A', strtotime($reg['registered_at'])); ?></p></div><div class="list-item-aside"><span class="status-tag status-<?php echo strtolower($reg['status']); ?>"><?php echo htmlspecialchars(ucfirst($reg['status'])); ?></span></div></div><?php endwhile; ?><?php else: ?><p style="text-align:center; padding: 1rem;">You have no department registration history.</p><?php endif; ?></div></div></div>
            
            <!-- UPDATED: Edit Profile Tab Content -->
            <div id="edit_profile" class="tab-content <?php if($currentPage == 'edit_profile') echo 'active'; ?>">
                <div class="page-content">
                    <h2>Edit Profile</h2>
                    <?php if (isset($_SESSION['form_status']) && $_SESSION['form_status']['page'] === 'edit_profile') { $status = $_SESSION['form_status']; echo '<div class="status-message ' . ($status['type'] === 'success' ? 'status-success' : 'status-error') . '">' . htmlspecialchars($status['message']) . '</div>'; unset($_SESSION['form_status']); } ?>
                    <p>Update your personal information here.</p>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?page=edit_profile" method="POST" novalidate>
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-input" value="<?php echo htmlspecialchars($logged_in_user_first_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-input" value="<?php echo htmlspecialchars($logged_in_user_last_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="current_email">Email Address</label>
                            <input type="email" id="current_email" name="current_email" class="form-input" value="<?php echo htmlspecialchars($logged_in_user_email); ?>" disabled>
                            <small class="text-sm text-muted">Email cannot be changed directly from here.</small>
                        </div>
                        <div style="text-align: right;">
                            <button type="submit" name="update_profile" class="submit-btn">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- NEW: Change Password Tab Content -->
            <div id="change_password" class="tab-content <?php if($currentPage == 'change_password') echo 'active'; ?>">
                <div class="page-content">
                    <h2>Change Password</h2>
                    <?php if (isset($_SESSION['form_status']) && $_SESSION['form_status']['page'] === 'change_password') { $status = $_SESSION['form_status']; echo '<div class="status-message ' . ($status['type'] === 'success' ? 'status-success' : 'status-error') . '">' . htmlspecialchars($status['message']) . '</div>'; unset($_SESSION['form_status']); } ?>
                    <p>Change your account password securely.</p>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?page=change_password" method="POST" novalidate>
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_new_password">Confirm New Password</label>
                            <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-input" required>
                        </div>
                        <div style="text-align: right;">
                            <button type="submit" name="change_password" class="submit-btn">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- --- NEW: Sports Registration Page Content --- -->
            <div id="registration" class="tab-content <?php if($currentPage == 'registration') echo 'active'; ?>">
                <div class="page-content">
                    <h2>Sport Registration</h2>
                    <?php if (isset($_SESSION['form_status']) && $_SESSION['form_status']['page'] === 'registration') { $status = $_SESSION['form_status']; echo '<div class="status-message ' . ($status['type'] === 'success' ? 'status-success' : 'status-error') . '">' . htmlspecialchars($status['message']) . '</div>'; unset($_SESSION['form_status']); } ?>
                    <p>Register for an active sport by selecting a sport and your department below.</p>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?page=registration" method="POST" id="registration-form" novalidate>
                        <div class="form-group">
                            <label for="sport_id">Select Sport</label>
                            <select id="sport_id" name="sport_id" class="form-select" required>
                                <option value="" disabled selected>-- Select a Sport --</option>
                                <?php 
                                if ($sports_for_reg->num_rows > 0) {
                                    while($sport = $sports_for_reg->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($sport['sport_id']) . '">' . htmlspecialchars($sport['name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No active sports available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="department_id">Select Department</label>
                            <select id="department_id" name="department_id" class="form-select" required>
                                <option value="" disabled selected>-- Select your Department --</option>
                                <?php 
                                if ($departments_for_reg->num_rows > 0) {
                                    while($department = $departments_for_reg->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($department['department_id']) . '">' . htmlspecialchars($department['department_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No departments available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div style="text-align: right;">
                            <button type="submit" name="submit_registration" class="submit-btn">Submit Registration</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="appeals" class="tab-content <?php if($currentPage == 'appeals') echo 'active'; ?>">
                <div class="page-content">
                    <h2>My Appeals</h2>
                    <p>Here is the history of match appeals you have submitted.</p>
                    <div class="content-box">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Match</th>
                                    <th>Date Submitted</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($user_appeals->num_rows > 0): ?>
                                    <?php while($appeal = $user_appeals->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($appeal['team1_name'] . " vs " . $appeal['team2_name']); ?><br><span class="text-sm"><?php echo htmlspecialchars($appeal['event_name']); ?></span></td>
                                            <td><?php echo date('M d, Y, g:i A', strtotime($appeal['submitted_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($appeal['reason']); ?></td>
                                            <td><span class="status-tag status-<?php echo strtolower($appeal['status']); ?>"><?php echo htmlspecialchars(ucfirst($appeal['status'])); ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center; padding: 1rem;">You have not submitted any appeals.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="about" class="tab-content <?php if($currentPage == 'about') echo 'active'; ?>"><div class="page-content"><h2>About SportsAcademix</h2><p>Welcome to SportsAcademix, the premier platform for managing all sports activities for our institution...</p></div></div>
            <div id="feedback" class="tab-content <?php if($currentPage == 'feedback') echo 'active'; ?>"><div class="page-content"><h2>Submit Feedback</h2><?php if (isset($_SESSION['form_status']) && $_SESSION['form_status']['page'] === 'feedback') { $status = $_SESSION['form_status']; echo '<div class="status-message ' . ($status['type'] === 'success' ? 'status-success' : 'status-error') . '">' . htmlspecialchars($status['message']) . '</div>'; unset($_SESSION['form_status']); } ?><p>We value your opinion! Please let us know if you have any suggestions...</p><form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?page=feedback" method="POST"><div class="form-group"><label for="feedbackSubject">Subject</label><select id="feedbackSubject" name="feedbackSubject" class="form-select" required><option value="" disabled selected>Please select a subject...</option><option value="General Feedback">General Feedback</option><option value="Bug Report">Bug Report</option><option value="Feature Request">Feature Request</option></select></div><div class="form-group"><label for="feedbackMessage">Your Message</label><textarea id="feedbackMessage" name="feedbackMessage" rows="5" class="form-textarea" placeholder="Please describe your feedback in detail..." required></textarea></div><div style="text-align: right;"><button type="submit" name="submit_feedback" class="submit-btn">Send Feedback</button></div></form></div></div>
        </main>
    </div>

    <div class="modal" id="appealModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Appeal Match Result</h3>
                <button class="close-modal" id="closeAppealModal">&times;</button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?page=profile" method="POST">
                <input type="hidden" name="event_id" id="appeal_event_id" class="form-input">
                <p>You are submitting an appeal for the match: <strong id="appeal_match_details"></strong></p>
                <div class="form-group">
                    <label for="appeal_reason">Reason for Appeal</label>
                    <textarea id="appeal_reason" name="appeal_reason" rows="4" class="form-textarea" placeholder="Please clearly explain why you are appealing this result..." required></textarea>
                </div>
                <div style="text-align: right;">
                    <button type="submit" name="submit_appeal" class="submit-btn">Submit Appeal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- NEW: Event Details Popup HTML -->
    <div id="event-details-popup">
        <h4>Events on <span id="event-details-date"></span></h4>
        <div id="event-details-list">
            <!-- Event details will be injected here -->
        </div>
    </div>
    
    <!-- Vanilla Calendar JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/vanilla-calendar-pro/build/vanilla-calendar.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menu-toggle');
            const closeSidebarBtn = document.getElementById('close-sidebar');
            const overlay = document.getElementById('overlay');

            // Calendar elements
            const calendarToggle = document.getElementById('calendar-toggle');
            const calendarPopup = document.getElementById('calendar-popup');
            const calendarContainer = document.getElementById('calendar-container');
            const eventDetailsPopup = document.getElementById('event-details-popup');
            const eventDetailsDate = document.getElementById('event-details-date');
            const eventDetailsList = document.getElementById('event-details-list');

            let hideDetailsTimeout; // To manage the auto-hide for event details popup


            // --- API Request Helper Function ---
            // NOTE: This apiRequest function is designed for 'api.php?endpoint=...&action=...'
            // Your admin.php uses 'admin.php?action=...' directly.
            // If you use home.php with admin.php, you might need to adjust this.
            const apiRequest = async (endpoint, action, method = 'GET', body = null, queryParams = null) => {
                let url = `api.php?endpoint=${endpoint}&action=${action}`;
                if (queryParams) {
                    url += '&' + new URLSearchParams(queryParams).toString();
                }
                const options = { method, headers: {} };
                if (method !== 'GET' && body) {
                    options.headers['Content-Type'] = 'application/json';
                    options.body = JSON.stringify(body);
                }
                try {
                    const response = await fetch(url, options);
                    const text = await response.text();
                    if (!response.ok) {
                        try {
                            const result = JSON.parse(text);
                            // Adjusted error message check for better robustness
                            if (result.message && (result.message.includes('not logged in') || result.message.includes('Unauthorized'))) {
                                console.error("API Authorization Error:", result.message); // Log the specific error
                                throw new Error('Session expired or unauthorized. Redirecting to login.');
                            }
                            throw new Error(result.message || `HTTP error ${response.status}`);
                        }
                        catch (e) {
                            console.error('Failed to parse error response (non-JSON or malformed):', e, 'Raw response:', text.substring(0, 500)); // Log raw response part
                            throw new Error(`Server returned non-JSON error: ${response.status}. Check server logs or network tab for ${url}.`);
                        }
                    }
                    const result = JSON.parse(text);
                    if (result.status !== 'success') { throw new Error(result.message || 'An unknown API error occurred'); }
                    return result;
                } catch (error) {
                    console.error(`API Request Error on ${url}:`, error);
                    if (error.message.includes('Session expired') || error.message.includes('unauthorized')) { // Check for custom message
                        alert(error.message);
                        window.location.href = 'log.php';
                    } else {
                        alert(`API Error: ${error.message}`);
                    }
                    throw error;
                }
            };


            // --- Sidebar Logic ---
            function openSidebar() { sidebar.classList.add('open'); overlay.classList.add('active'); }
            function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); }

            menuToggle.addEventListener('click', openSidebar);
            closeSidebarBtn.addEventListener('click', closeSidebar);
            overlay.addEventListener('click', closeSidebar);

            // Desktop-only (optional) edge-swipe to open sidebar
            if (window.innerWidth > 768) {
                document.addEventListener('mousemove', function(e) {
                    if (e.clientX < 20 && !sidebar.classList.contains('open')) {
                        openSidebar();
                    }
                });
            }


            // --- Match Appeal Modal Logic (for My Profile page) ---
            // Note: This modal is separate from any sport-specific appeal modals.
            const profileAppealModal = document.getElementById('appealModal'); // Renamed for clarity in home.php context
            const closeProfileAppealModalBtn = profileAppealModal.querySelector('#closeAppealModal');
            const profileAppealEventIdInput = profileAppealModal.querySelector('#appeal_event_id');
            const profileAppealMatchDetailsText = profileAppealModal.querySelector('#appeal_match_details');

            document.querySelectorAll('#profile .appeal-btn').forEach(button => { // Target only buttons in the profile tab
                button.addEventListener('click', function() {
                    const eventId = this.dataset.eventId;
                    const matchDetails = this.dataset.matchDetails;
                    
                    profileAppealEventIdInput.value = eventId;
                    profileAppealMatchDetailsText.textContent = matchDetails;
                    profileAppealModal.classList.add('active');
                });
            });

            function closeProfileAppealModal() { // Renamed
                profileAppealModal.classList.remove('active');
            }

            closeProfileAppealModalBtn.addEventListener('click', closeProfileAppealModal);
            profileAppealModal.addEventListener('click', function(e) {
                if (e.target === profileAppealModal) {
                    closeProfileAppealModal();
                }
            });


            // --- CALENDAR LOGIC ---
            let calendarInstance = null;
            let eventDates = []; // Stores dates like ['YYYY-MM-DD', ...]

            // Helper function to sanitize HTML output (already defined, but good to ensure accessibility)
            const sanitizeHTML = (text) => {
                if (text === null || typeof text === 'undefined') return '';
                const temp = document.createElement('div');
                temp.textContent = String(text);
                return temp.innerHTML;
            };

            const fetchEventDates = async () => {
                console.log("Fetching event dates...");
                try {
                    const response = await apiRequest('events', 'get_event_dates');
                    eventDates = response.data || [];
                    console.log("Fetched event dates:", eventDates);
                    if (calendarInstance) {
                        updateCalendarMarkedDates();
                    }
                } catch (error) {
                    console.error('Error fetching event dates:', error);
                    eventDates = []; // Clear dates on error
                }
            };

            const updateCalendarMarkedDates = () => {
                if (!calendarInstance) {
                    console.warn("Calendar instance not initialized yet for marking.");
                    return;
                }

                console.log("Updating calendar with marked dates:", eventDates);
                calendarInstance.settings.selected.dates = eventDates;
                
                // This correctly tells Vanilla Calendar to apply the 'event-marked' class
                // to any day whose date string is present in the `eventDates` array.
                calendarInstance.settings.classes = {
                    'event-marked': eventDates 
                };
                
                calendarInstance.update(); // Re-render the calendar to apply changes
                console.log("Calendar updated with marked dates.");
            };

            const initializeCalendar = () => {
                if (calendarInstance) {
                    console.log("Calendar already initialized.");
                    return;
                }
                console.log("Initializing Vanilla Calendar...");
                calendarInstance = new VanillaCalendar(calendarContainer, {
                    settings: {
                        lang: 'en',
                        range: {
                            min: new Date().toISOString().slice(0, 10), // Mark from today onwards
                        },
                        selection: {
                            day: 'multiple', // Allows marking multiple days if needed, good for visual only
                            month: false, 
                            year: false, 
                        },
                        visibility: {
                            theme: 'dark', 
                            today: true,
                            weekNumbers: false,
                            weekend: true, 
                            disabled: true, // Disable interaction by default, we'll enable specific clicks
                        },
                    },
                    actions: {
                        clickArrow: () => {
                            console.log("Calendar arrow clicked, refetching event dates.");
                            fetchEventDates(); // Fetch and update marks when month changes
                        },
                        clickDay: async (e, dates) => {
                            console.log("Calendar day clicked:", dates);
                            clearTimeout(hideDetailsTimeout); 
                            eventDetailsPopup.classList.remove('active'); 

                            if (!dates || dates.length === 0) {
                                console.log("No date selected or invalid date array.");
                                return;
                            }

                            const clickedDate = dates[0]; 
                            const isMarked = eventDates.includes(clickedDate);
                            console.log(`Clicked date: ${clickedDate}, Is marked: ${isMarked}`);

                            if (isMarked) {
                                try {
                                    console.log(`Fetching event details for date: ${clickedDate}...`);
                                    const response = await apiRequest('events', 'get_events_by_date', 'GET', null, { date: clickedDate });
                                    const events = response.data;
                                    console.log("Fetched events for date:", events);
                                    
                                    eventDetailsDate.textContent = new Date(clickedDate).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
                                    eventDetailsList.innerHTML = ''; 

                                    if (events.length > 0) {
                                        events.forEach(event => {
                                            const eventHtml = `
                                                <div class="event-item">
                                                    <p><strong>${sanitizeHTML(event.event_name)}</strong></p>
                                                    <p>${sanitizeHTML(event.sport_name)}</p>
                                                    <p>${sanitizeHTML(event.team1_name)} vs ${sanitizeHTML(event.team2_name)}</p>
                                                    <p>${sanitizeHTML(event.event_time)} - ${sanitizeHTML(event.venue)}</p>
                                                </div>
                                            `;
                                            eventDetailsList.insertAdjacentHTML('beforeend', eventHtml);
                                        });
                                    } else {
                                        eventDetailsList.innerHTML = '<p class="event-no-details">No specific event details available for this date.</p>';
                                    }

                                    const dayButton = e.target.closest('.vanilla-calendar-day__btn');
                                    if (dayButton) {
                                        const headerActionsRect = document.querySelector('.header-actions').getBoundingClientRect();
                                        const dayRect = dayButton.getBoundingClientRect();

                                        // Position relative to the .header-actions container
                                        // Take into account scroll position if header-actions is not fixed at top
                                        eventDetailsPopup.style.left = (dayRect.left - headerActionsRect.left) + 'px';
                                        eventDetailsPopup.style.top = (dayRect.bottom - headerActionsRect.top + 10) + 'px'; // 10px below the day button

                                        eventDetailsPopup.classList.add('active');
                                        console.log("Event details popup activated.");
                                        hideDetailsTimeout = setTimeout(() => {
                                            eventDetailsPopup.classList.remove('active');
                                            console.log("Event details popup auto-hidden.");
                                        }, 5000); 
                                    }
                                } catch (error) {
                                    console.error('Failed to fetch event details for date:', clickedDate, error);
                                    eventDetailsList.innerHTML = '<p class="event-no-details">Error loading details.</p>';
                                    eventDetailsPopup.classList.add('active');
                                    hideDetailsTimeout = setTimeout(() => {
                                        eventDetailsPopup.classList.remove('active');
                                    }, 3000);
                                }
                            } else {
                                console.log("Clicked day is not marked with an event, no details to show.");
                            }
                        }
                    }
                });
                calendarInstance.init();
                console.log("Vanilla Calendar initialized.");
                // Initial update of marked dates is handled by fetchEventDates in toggleCalendarPopup
            };


            let calendarPopupOpen = false;
            const toggleCalendarPopup = async () => { // Made async for proper await
                calendarPopupOpen = !calendarPopupOpen;
                console.log("Toggling calendar popup. Open:", calendarPopupOpen);

                if (calendarPopupOpen) {
                    calendarPopup.classList.add('active');
                    if (!calendarInstance) { 
                        initializeCalendar();
                    }
                    // Crucial: Await fetching of dates *before* the calendar is updated
                    await fetchEventDates(); 
                    console.log("Calendar popup opened and dates fetched.");
                } else {
                    calendarPopup.classList.remove('active');
                    eventDetailsPopup.classList.remove('active'); 
                    clearTimeout(hideDetailsTimeout);
                    console.log("Calendar popup closed.");
                }
            };

            calendarToggle.addEventListener('click', (e) => {
                e.stopPropagation(); 
                toggleCalendarPopup();
            });

            document.addEventListener('click', (e) => {
                const isClickInsideCalendarOrToggle = calendarPopup.contains(e.target) || calendarToggle.contains(e.target);
                const isClickInsideEventDetails = eventDetailsPopup.contains(e.target);

                if (calendarPopupOpen && !isClickInsideCalendarOrToggle && !isClickInsideEventDetails) {
                    console.log("Click outside calendar/details detected, closing popup.");
                    toggleCalendarPopup(); 
                }
            });

            eventDetailsPopup.addEventListener('click', (e) => {
                console.log("Event details popup clicked, clearing auto-hide timeout.");
                clearTimeout(hideDetailsTimeout);
            });


            // --- NEW: Validation Helper Functions (from log.php) ---
            function showValidationPopup(inputElement, message) {
                // Find the .form-group parent of the input
                const formGroup = inputElement.closest('.form-group');
                if (!formGroup) {
                    console.warn("Input element not inside a .form-group for validation popup:", inputElement);
                    return;
                }

                // Remove any existing popup first within this form-group
                const existingPopup = formGroup.querySelector('.validation-popup');
                if (existingPopup) {
                    existingPopup.remove();
                }

                const popup = document.createElement('div');
                popup.className = 'validation-popup';
                popup.textContent = message;

                formGroup.appendChild(popup); // Append to form-group

                setTimeout(() => {
                    popup.classList.add('show');
                }, 10);

                setTimeout(() => {
                    popup.classList.remove('show');
                    setTimeout(() => {
                        popup.remove();
                    }, 300);
                }, 3000);
            }

            // Regex definitions
            const nameRegex = /^[A-Z][a-zA-Z]*$/; // Starts with Capital, only letters, no spaces
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/; // 8+ chars, 1 uppercase, 1 lowercase, 1 number, 1 special char


            // --- NEW: Event Listeners for Edit Profile and Change Password forms ---
            const editProfileForm = document.querySelector('#edit_profile form');
            if (editProfileForm) {
                editProfileForm.addEventListener('submit', function(event) {
                    let isValid = true;

                    const firstNameInput = this.querySelector('input[name="first_name"]');
                    const lastNameInput = this.querySelector('input[name="last_name"]');

                    if (firstNameInput) {
                        const firstNameValue = firstNameInput.value.trim();
                        if (firstNameValue === '') {
                            showValidationPopup(firstNameInput, 'First Name is required.');
                            isValid = false;
                        } else if (!nameRegex.test(firstNameValue)) {
                            showValidationPopup(firstNameInput, 'First Name: Capital first letter, letters only, no spaces.');
                            isValid = false;
                        }
                    }

                    if (lastNameInput) {
                        const lastNameValue = lastNameInput.value.trim();
                        if (lastNameValue === '') {
                            showValidationPopup(lastNameInput, 'Last Name is required.');
                            isValid = false;
                        } else if (!nameRegex.test(lastNameValue)) {
                            showValidationPopup(lastNameInput, 'Last Name: Capital first letter, letters only, no spaces.');
                            isValid = false;
                        }
                    }

                    if (!isValid) {
                        event.preventDefault(); // Stop form submission
                    }
                });
            }

            const changePasswordForm = document.querySelector('#change_password form');
            if (changePasswordForm) {
                changePasswordForm.addEventListener('submit', function(event) {
                    let isValid = true;

                    const currentPasswordInput = this.querySelector('input[name="current_password"]');
                    const newPasswordInput = this.querySelector('input[name="new_password"]');
                    const confirmNewPasswordInput = this.querySelector('input[name="confirm_new_password"]');

                    // Validate Current Password (only check if required and not empty, backend does actual verification)
                    if (currentPasswordInput && currentPasswordInput.value.trim() === '') {
                        showValidationPopup(currentPasswordInput, 'Current password is required.');
                        isValid = false;
                    }

                    // Validate New Password
                    if (newPasswordInput) {
                        const newPasswordValue = newPasswordInput.value.trim();
                        if (newPasswordValue === '') {
                            showValidationPopup(newPasswordInput, 'New password is required.');
                            isValid = false;
                        } else if (!passwordRegex.test(newPasswordValue)) {
                            showValidationPopup(newPasswordInput, 'Password must be 8+ characters with uppercase, lowercase, number, and special symbol.');
                            isValid = false;
                        }
                    }

                    // Validate Confirm New Password
                    if (confirmNewPasswordInput) {
                        if (confirmNewPasswordInput.value.trim() === '') {
                            showValidationPopup(confirmNewPasswordInput, 'Confirm password is required.');
                            isValid = false;
                        } else if (newPasswordInput && newPasswordInput.value !== confirmNewPasswordInput.value) {
                            showValidationPopup(confirmNewPasswordInput, 'New password and confirmation do not match.');
                            isValid = false;
                        }
                    }

                    if (!isValid) {
                        event.preventDefault(); // Stop form submission
                    }
                });
            }

            // --- NEW: Event Listener for Sport Registration Form ---
            const registrationForm = document.getElementById('registration-form');
            if (registrationForm) {
                registrationForm.addEventListener('submit', function(event) {
                    let isValid = true;

                    const sportSelect = this.querySelector('select[name="sport_id"]');
                    const departmentSelect = this.querySelector('select[name="department_id"]');

                    if (sportSelect && sportSelect.value === '') {
                        showValidationPopup(sportSelect, 'Please select a sport.');
                        isValid = false;
                    }
                    if (departmentSelect && departmentSelect.value === '') {
                        showValidationPopup(departmentSelect, 'Please select a department.');
                        isValid = false;
                    }

                    if (!isValid) {
                        event.preventDefault(); // Stop form submission
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php
// Close the database connection at the very end of the script
$conn->close();
?>