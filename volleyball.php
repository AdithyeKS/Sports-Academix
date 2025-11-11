<?php
// FILE: volleyball.php (Fully Functional with Appeals, Comments, and Team Details in Cards)
session_start();
require_once 'config.php'; // Ensure config.php connects to your database

// --- SESSION CHECK ---
if (!isset($_SESSION['loggedin'])) {
    header('Location: log.php');
    exit();
}

$logged_in_user_id = $_SESSION['user_id'];
$sport_id_for_this_page = 3; // Volleyball's sport_id is 3 (as per your database dump)

// --- HANDLE FORM SUBMISSIONS ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle Match Appeal Submission
    if (isset($_POST['submit_appeal'])) {
        $event_id = (int)$_POST['event_id'];
        $reason = trim($_POST['appeal_reason']);
        if ($event_id > 0 && !empty($reason)) {
            $check_stmt = $conn->prepare("SELECT appeal_id FROM match_appeals WHERE user_id = ? AND event_id = ?");
            $check_stmt->bind_param("ii", $logged_in_user_id, $event_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $_SESSION['form_status'] = ['page' => 'volleyball', 'type' => 'error', 'message' => 'You have already submitted an appeal for this match.'];
            } else {
                $stmt = $conn->prepare("INSERT INTO match_appeals (event_id, user_id, reason) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $event_id, $logged_in_user_id, $reason);
                $_SESSION['form_status'] = $stmt->execute() ? ['page' => 'volleyball', 'type' => 'success', 'message' => 'Your appeal has been submitted successfully.'] : ['page' => 'volleyball', 'type' => 'error', 'message' => 'Error: Could not submit your appeal.'];
                $stmt->close();
            }
            $check_stmt->close();
        } else {
            $_SESSION['form_status'] = ['page' => 'volleyball', 'type' => 'error', 'message' => 'Error: Please provide a reason for your appeal.'];
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Handle Comment Submission
    if (isset($_POST['submit_comment'])) {
        $event_id = (int)$_POST['event_id'];
        $comment_text = trim($_POST['comment_text']);
        if ($event_id > 0 && !empty($comment_text)) {
            $stmt = $conn->prepare("INSERT INTO match_comments (event_id, user_id, comment) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $event_id, $logged_in_user_id, $comment_text);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=results");
        exit();
    }
    
    // Handle Comment Deletion
    if (isset($_POST['delete_comment'])) {
        $comment_id = (int)$_POST['comment_id'];
        if ($comment_id > 0) {
            $stmt = $conn->prepare("DELETE FROM match_comments WHERE comment_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $comment_id, $logged_in_user_id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=results");
        exit();
    }
}

// --- DATA FETCHING (Using MySQLi) ---
// Upcoming Matches for Dashboard
$stmt_all_upcoming = $conn->prepare("SELECT e.event_date, t1.team_name as team1, t2.team_name as team2, e.venue FROM events e JOIN teams t1 ON e.team1_id = t1.team_id JOIN teams t2 ON e.team2_id = t2.team_id WHERE e.sport_id = ? AND e.status = 'schedule' AND e.event_date >= NOW() ORDER BY e.event_date ASC");
$stmt_all_upcoming->bind_param("i", $sport_id_for_this_page);
$stmt_all_upcoming->execute();
$upcoming_matches_full = $stmt_all_upcoming->get_result()->fetch_all(MYSQLI_ASSOC);
$upcoming_matches_dashboard = array_slice($upcoming_matches_full, 0, 3);
$stmt_all_upcoming->close();

// Recent Results for Dashboard
$stmt_results_dashboard = $conn->prepare("SELECT e.result, t1.team_name as team1, t2.team_name as team2, e.team1_score, e.team2_score FROM events e JOIN teams t1 ON e.team1_id = t1.team_id JOIN teams t2 ON e.team2_id = t2.team_id WHERE e.sport_id = ? AND e.status = 'completed' ORDER BY e.event_date DESC LIMIT 3");
$stmt_results_dashboard->bind_param("i", $sport_id_for_this_page);
$stmt_results_dashboard->execute();
$recent_results_dashboard = $stmt_results_dashboard->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_results_dashboard->close();

// All Recent Matches for Results Tab
$stmt_all_results = $conn->prepare("SELECT e.event_id, e.event_date, e.venue, t1.team_name as team1, t2.team_name as team2, e.team1_score, e.team2_score, e.result FROM events e JOIN teams t1 ON e.team1_id = t1.team_id JOIN teams t2 ON e.team2_id = t2.team_id WHERE e.sport_id = ? AND e.status = 'completed' ORDER BY e.event_date DESC");
$stmt_all_results->bind_param("i", $sport_id_for_this_page);
$stmt_all_results->execute();
$recent_matches_full = $stmt_all_results->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_all_results->close();

// Comments for Results Tab
$event_ids = array_column($recent_matches_full, 'event_id');
$comments_by_event = [];
if (!empty($event_ids)) {
    $placeholders = implode(',', array_fill(0, count($event_ids), '?'));
    $types = str_repeat('i', count($event_ids));
    $stmt_comments = $conn->prepare("SELECT c.comment_id, c.event_id, c.user_id, c.comment, c.is_edited, c.commented_at, u.name as user_name FROM match_comments c JOIN users u ON c.user_id = u.user_id WHERE c.event_id IN ($placeholders) ORDER BY c.commented_at ASC");
    if ($stmt_comments) {
        $stmt_comments->bind_param($types, ...$event_ids);
        $stmt_comments->execute();
        $comments_result = $stmt_comments->get_result();
        while ($comment = $comments_result->fetch_assoc()) {
            $comments_by_event[$comment['event_id']][] = $comment;
        }
        $stmt_comments->close();
    }
}

// Points Table
$stmt_points = $conn->prepare("SELECT t.team_name, COALESCE(SUM(sub.played), 0) AS played, COALESCE(SUM(sub.win), 0) AS wins, COALESCE(SUM(sub.draw), 0) AS draws, COALESCE(SUM(sub.loss), 0) AS losses, (COALESCE(SUM(sub.win), 0) * s.points_for_win) + (COALESCE(SUM(sub.draw), 0) * s.points_for_draw) + (COALESCE(SUM(sub.loss), 0) * s.points_for_loss) AS points FROM teams t JOIN sports s ON t.sport_id = s.sport_id LEFT JOIN (SELECT team1_id as team_id, 1 as played, (result = 'team1_win') as win, (result = 'draw') as draw, (result = 'team2_win') as loss FROM events WHERE status = 'completed' AND sport_id = ? UNION ALL SELECT team2_id as team_id, 1 as played, (result = 'team2_win') as win, (result = 'draw') as draw, (result = 'team1_win') as loss FROM events WHERE status = 'completed' AND sport_id = ?) AS sub ON t.team_id = sub.team_id WHERE t.sport_id = ? GROUP BY t.team_id, t.team_name, s.points_for_win, s.points_for_draw, s.points_for_loss ORDER BY points DESC, wins DESC, t.team_name ASC;");
$stmt_points->bind_param("iii", $sport_id_for_this_page, $sport_id_for_this_page, $sport_id_for_this_page);
$stmt_points->execute();
$points_table = $stmt_points->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_points->close();

// Total Teams Count (for dashboard header)
$stmt_teams_count = $conn->prepare("SELECT COUNT(*) as total FROM teams WHERE sport_id = ?");
$stmt_teams_count->bind_param("i", $sport_id_for_this_page);
$stmt_teams_count->execute();
$total_teams = $stmt_teams_count->get_result()->fetch_assoc()['total'];
$stmt_teams_count->close();

// NEW: Fetch all Volleyball teams for the "Teams" tab with player count
$stmt_sport_teams = $conn->prepare("
    SELECT 
        t.team_id, 
        t.team_name,
        (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = t.team_id) AS player_count
    FROM teams t 
    WHERE t.sport_id = ? 
    ORDER BY t.team_name ASC
");
$stmt_sport_teams->bind_param("i", $sport_id_for_this_page);
$stmt_sport_teams->execute();
$sport_teams = $stmt_sport_teams->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_sport_teams->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volleyball Dashboard – SportsAcademix</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-hue: 250; --primary: hsl(var(--primary-hue), 90%, 65%); --primary-glow: hsl(var(--primary-hue), 90%, 75%);
            --bg-dark: #0D0E1B; --bg-mid: #1A1B2A; --text-light: #EAEBF0; --text-muted: #8A8B9F;
            --border-glass: rgba(255, 255, 255, 0.1); --bg-glass: rgba(26, 27, 42, 0.6);
            --green: #22c55e; --red: #ef4444; --gold: #f59e0b; --blue: #3b82f6;
        }
        body { font-family: 'Poppins', sans-serif; margin: 0; color: var(--text-light); background-color: var(--bg-dark); background-image: radial-gradient(circle at 10% 10%, hsla(var(--primary-hue), 90%, 30%, 0.3) 0, transparent 40%), radial-gradient(circle at 90% 80%, hsla(190, 90%, 40%, 0.3) 0, transparent 40%); background-attachment: fixed; overflow-x: hidden; }
        .page-container { max-width: 1200px; margin: 3rem auto; padding: 0 1.5rem; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-on-load { opacity: 0; animation: fadeInUp 0.6s ease-out forwards; }
        .page-header { display: flex; justify-content: space-between; align-items: center; padding: 2rem; margin-bottom: 2rem; background: var(--bg-glass); border: 1px solid var(--border-glass); border-radius: 1rem; backdrop-filter: blur(12px); }
        .header-title { display: flex; align-items: center; gap: 1.5rem; }
        .header-title i { font-size: 3rem; color: var(--gold); }
        .header-title h1 { margin: 0; font-size: 2.25rem; font-weight: 700; }
        .header-title p { margin: 0.25rem 0 0; color: var(--text-muted); font-size: 1rem; }
        .header-title .team-count { font-weight: 700; color: var(--primary-glow); }
        .btn-home { text-decoration: none; background-color: var(--bg-glass); color: var(--text-light); padding: 0.75rem 1.25rem; border-radius: 0.5rem; font-weight: 500; transition: all 0.2s ease; border: 1px solid var(--border-glass); }
        .btn-home:hover { background-color: var(--primary); color: white; border-color: var(--primary); }
        .tab-nav { display: flex; gap: 0.5rem; margin-bottom: 2rem; background: var(--bg-glass); padding: 0.5rem; border-radius: 0.75rem; border: 1px solid var(--border-glass); }
        .tab-btn { flex: 1; padding: 0.75rem 1rem; border: none; background: none; font-family: 'Poppins', sans-serif; font-size: 1rem; font-weight: 500; color: var(--text-muted); cursor: pointer; border-radius: 0.5rem; transition: all 0.3s ease; }
        .tab-btn:hover { background-color: rgba(255, 255, 255, 0.05); color: var(--text-light); }
        .tab-btn.active { background-color: var(--primary); color: white; box-shadow: 0 0 20px hsla(var(--primary-hue), 90%, 75%, 0.4); }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; animation: fadeInUp 0.5s ease-out; }
        .content-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; }
        .content-card { background: var(--bg-glass); border-radius: 1rem; border: 1px solid var(--border-glass); backdrop-filter: blur(12px); }
        .content-card h2 { font-size: 1.25rem; padding: 1.5rem; margin: 0; border-bottom: 1px solid var(--border-glass); }
        .list { padding: 0.5rem; margin: 0; list-style: none; }
        .list-item { display: flex; gap: 1rem; padding: 1rem 1.25rem; border-radius: 0.5rem; transition: background-color 0.2s ease; align-items: center; }
        .list-item:hover { background-color: rgba(255, 255, 255, 0.05); }
        .list-item-icon { font-size: 1rem; width: 36px; height: 36px; flex-shrink: 0; display: grid; place-items: center; border-radius: 50%; color: var(--primary-glow); background-color: rgba(0,0,0,0.2); }
        .list-item-details { flex-grow: 1; } .list-item-details p { margin: 0; font-weight: 500; }
        .list-item-details span { font-size: 0.85rem; color: var(--text-muted); }
        .list-item-aside { text-align: right; font-weight: 600; }
        .list-item-aside .win { color: var(--green); } .list-item-aside .loss { color: var(--red); }
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; }
        .match-card { background: var(--bg-glass); border: 1px solid var(--border-glass); border-radius: 1rem; padding: 1.5rem; display: flex; flex-direction: column; }
        .match-card .teams { display: flex; align-items: center; justify-content: space-between; text-align: center; margin-bottom: 1.5rem; }
        .match-card .teams h3 { font-size: 1.2rem; margin: 0; font-weight: 600; flex: 1; }
        .match-card .teams .vs { font-size: 1rem; font-weight: 400; color: var(--primary); margin: 0 1rem; }
        .match-card .details { list-style: none; padding: 0; margin: 0; border-top: 1px solid var(--border-glass); padding-top: 1rem; }
        .match-card .details li { display: flex; align-items: center; gap: 1rem; color: var(--text-muted); font-size: 0.9rem; }
        .match-card .details li + li { margin-top: 0.75rem; }
        .match-card .details i { font-size: 1rem; color: var(--primary-glow); width: 20px; text-align: center; }
        .match-card .details span { color: var(--text-light); font-weight: 500; }
        .match-card .score { font-size: 1.2rem; font-weight: 700; color: var(--gold); }
        .match-card .outcome { margin-top: 1.5rem; padding: 0.75rem; border-radius: 0.5rem; text-align: center; font-weight: 600; }
        .outcome.win { background-color: rgba(34, 197, 94, 0.2); color: var(--green); }
        .outcome.draw { background-color: rgba(59, 130, 246, 0.2); color: var(--blue); }
        .data-table-container { background: var(--bg-glass); border-radius: 1rem; border: 1px solid var(--border-glass); backdrop-filter: blur(12px); overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table td, .data-table th { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid var(--border-glass); }
        .data-table thead th { color: var(--text-muted); font-weight: 500; text-transform: uppercase; font-size: 0.8rem; }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover { background-color: rgba(255, 255, 255, 0.05); }
        .empty-state { text-align: center; padding: 4rem; color: var(--text-muted); }
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); z-index: 1000; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
        .modal.active { opacity: 1; pointer-events: all; }
        .modal-content { background: #1e293b; padding: 2rem; border-radius: 1rem; width: 90%; max-width: 500px; border: 1px solid var(--border-glass); transform: scale(0.9); transition: transform 0.3s ease; }
        .modal.active .modal-content { transform: scale(1); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h3 { margin: 0; font-size: 1.25rem; }
        .modal-header .close-modal { background: none; border: none; font-size: 1.5rem; color: var(--text-muted); cursor: pointer; }
        .appeal-btn { background: none; border: 1px solid #f59e0b; color: #f59e0b; padding: 0.5rem 1rem; border-radius: 9999px; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease; }
        .appeal-btn:hover { background: #f59e0b; color: #1e293b; font-weight: 600; }
        .form-group { margin-bottom: 1.5rem; }
        form label { display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--text-light); }
        .form-textarea { width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-glass); background-color: rgba(17, 24, 39, 0.8); color: var(--text-light); border-radius: 0.5rem; box-sizing: border-box; font-family: 'Poppins', sans-serif; transition: all 0.2s ease; }
        .form-textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px hsla(var(--primary-hue), 85%, 60%, 0.3); outline: none; }
        .submit-btn { background: var(--primary); color: #fff; font-weight: 600; padding: 0.85rem 1.5rem; border: none; border-radius: 0.5rem; cursor: pointer; transition: all 0.3s ease; }
        .submit-btn:hover { background-color: hsl(var(--primary-hue), 85%, 50%); }
        .status-message { padding: 1rem; margin-bottom: 1.5rem; border-radius: .5rem; border: 1px solid transparent; }
        .status-success { background-color: rgba(34, 197, 94, 0.2); color: #4ade80; border-color: #22c55e; }
        .status-error { background-color: rgba(239, 68, 68, 0.2); color: #f87171; border-color: #ef4444; }
        .comments-section { border-top: 1px solid var(--border-glass); margin-top: 1.5rem; padding-top: 1.5rem; }
        .comments-section h4 { font-size: 1rem; font-weight: 600; color: var(--text-muted); margin-bottom: 1rem; }
        .comments-list { max-height: 200px; overflow-y: auto; padding-right: 10px; margin-bottom: 1.5rem; }
        .comment { background: rgba(0,0,0,0.2); padding: 0.75rem 1rem; border-radius: 0.5rem; }
        .comment + .comment { margin-top: 0.75rem; }
        .comment-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .comment-meta { font-size: 0.75rem; color: var(--text-muted); font-style: italic; }
        .comment-actions button { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 0.8rem; margin-left: 0.5rem; }
        .comment-actions button:hover { color: var(--text-light); }
        .comment-body { margin: 0; font-size: 0.9rem; }
        .no-comments { font-size: 0.9rem; color: var(--text-muted); text-align: center; padding: 1rem; }
        .comment-form { display: flex; gap: 1rem; }
        .comment-form textarea { flex-grow: 1; background: var(--bg-dark); border-color: var(--border-color); min-height: 40px; }
        .comment-form button { background-color: var(--primary); color: white; border: none; border-radius: 0.5rem; padding: 0.5rem 1rem; cursor: pointer; font-weight: 500; transition: background-color 0.2s; }
        .comment-form button:hover { background-color: hsl(var(--primary-hue), 90%, 55%); }
        .match-card .actions { margin-top: 1.5rem; border-top: 1px solid var(--border-glass); padding-top: 1.5rem; text-align: center; }

        /* NEW Team Card Styles */
        .team-card {
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .team-card-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: var(--text-light);
        }
        .team-card-detail {
            font-size: 0.95rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        .team-card-detail strong {
            color: var(--primary-glow);
        }
        .team-card-actions {
            margin-top: 1rem; /* Space between details and button */
            text-align: right;
        }

        /* Re-using existing card-grid for layout */
        /* .card-grid for match-cards is already defined, will apply here too */

        /* NEW Team Members Specific Styles (adjusted for card layout) */
        .btn-view-members {
            background: var(--blue);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
            display: inline-flex; /* Align icon and text */
            align-items: center;
            gap: 0.5rem;
        }
        .btn-view-members:hover {
            background-color: hsl(217, 91%, 45%); /* Darker blue */
        }
        .btn-view-members.active-members-btn {
            background-color: hsl(217, 91%, 35%); /* Darker blue when active/expanded */
        }
        
        .team-members-separator {
            border: none;
            border-top: 1px solid var(--border-glass);
            margin: 1.5rem 0;
        }
        .team-members-expanded-content {
            margin-top: 1rem; /* Space between the button and the expanded content */
            animation: fadeIn 0.3s ease-out; /* Add a subtle fade-in */
        }
        .team-members-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .team-members-list li {
            padding: 0.75rem 1rem; /* Adjusted padding */
            border-bottom: 1px dashed rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .team-members-list li:last-child {
            border-bottom: none;
        }
        .team-members-list .member-name {
            font-weight: 500;
            color: var(--text-light);
        }
        .team-members-list .member-role {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-align: right;
        }
        .team-members-list .no-members {
            text-align: center;
            padding: 1rem;
            color: var(--text-muted);
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <header class="page-header animate-on-load">
            <div class="header-title">
                <i class="fas fa-volleyball-ball"></i> <!-- Volleyball icon -->
                <div>
                    <h1>Volleyball Dashboard</h1>
                    <p><span class="team-count" data-count="<?php echo $total_teams; ?>">0</span> Teams Competing in the League</p>
                </div>
            </div>
            <a href="home.php" class="btn-home"><i class="fas fa-arrow-left"></i> Back to Main Dashboard</a>
        </header>

        <?php if (isset($_SESSION['form_status']) && $_SESSION['form_status']['page'] === 'volleyball') { $status = $_SESSION['form_status']; echo '<div class="status-message ' . ($status['type'] === 'success' ? 'status-success' : 'status-error') . '">' . htmlspecialchars($status['message']) . '</div>'; unset($_SESSION['form_status']); } ?>

        <nav class="tab-nav animate-on-load">
            <button class="tab-btn active" data-tab="overview">Overview</button>
            <button class="tab-btn" data-tab="upcoming">Upcoming Matches</button>
            <button class="tab-btn" data-tab="results">Match Results</button>
            <button class="tab-btn" data-tab="points">Point Table</button>
            <button class="tab-btn" data-tab="teams">Teams</button> <!-- NEW TAB -->
        </nav>

        <div class="tab-content-container">
            <div id="overview" class="tab-pane active">
                <div class="content-grid">
                     <div class="content-card">
                        <h2>Upcoming Matches</h2>
                        <ul class="list">
                            <?php if(empty($upcoming_matches_dashboard)): ?><li class="list-item"><p>No upcoming matches.</p></li><?php endif; ?>
                            <?php foreach($upcoming_matches_dashboard as $i => $match): $dt = new DateTime($match['event_date']); ?>
                            <li class="list-item">
                                <div class="list-item-icon"><i class="fas fa-calendar-day"></i></div>
                                <div class="list-item-details"><p><?php echo htmlspecialchars($match['team1']) . ' vs ' . htmlspecialchars($match['team2']); ?></p><span><?php echo htmlspecialchars($match['venue']); ?></span></div>
                                <div class="list-item-aside"><?php echo $dt->format('M d'); ?></div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="content-card">
                        <h2>Recent Results</h2>
                        <ul class="list">
                            <?php if(empty($recent_results_dashboard)): ?><li class="list-item"><p>No recent results.</p></li><?php endif; ?>
                            <?php foreach($recent_results_dashboard as $i => $result): ?>
                            <li class="list-item">
                                <div class="list-item-icon" style="color: var(--gold);"><i class="fas fa-trophy"></i></div>
                                <div class="list-item-details"><p><?php echo htmlspecialchars($result['team1'])." vs ".htmlspecialchars($result['team2']); ?></p><span><?php echo $result['team1_score'] . ' - ' . $result['team2_score']; ?></span></div>
                                <div class="list-item-aside">
                                    <?php
                                        if($result['result']==='team1_win'){ echo htmlspecialchars(explode(' ', $result['team1'])[0])." Won"; }
                                        elseif($result['result']==='team2_win'){ echo htmlspecialchars(explode(' ', $result['team2'])[0])." Won"; }
                                        else{ echo "Draw"; }
                                    ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div id="upcoming" class="tab-pane">
                <div class="card-grid">
                    <?php if(empty($upcoming_matches_full)): ?>
                        <p class="empty-state">No upcoming matches on the schedule.</p>
                    <?php else: foreach($upcoming_matches_full as $i => $match): $dt = new DateTime($match['event_date']); ?>
                        <div class="match-card">
                            <div class="teams">
                                <h3><?php echo htmlspecialchars($match['team1']); ?></h3>
                                <span class="vs">vs</span>
                                <h3><?php echo htmlspecialchars($match['team2']); ?></h3>
                            </div>
                            <ul class="details">
                                <li><i class="fas fa-map-marker-alt"></i><span><?php echo htmlspecialchars($match['venue']); ?></span></li>
                                <li><i class="fas fa-calendar-alt"></i><span><?php echo $dt->format('l, F j, Y'); ?></span></li>
                                <li><i class="fas fa-clock"></i><span><?php echo $dt->format('h:i A'); ?></span></li>
                            </ul>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <div id="results" class="tab-pane">
                 <div class="card-grid">
                    <?php if(empty($recent_matches_full)): ?>
                        <p class="empty-state">No recent results found.</p>
                    <?php else: foreach($recent_matches_full as $i => $match):
                        $dt = new DateTime($match['event_date']);
                        $outcome_text = 'Pending';
                        $outcome_class = '';
                        if($match['result'] === 'team1_win') { $outcome_text = htmlspecialchars($match['team1']) . " Won"; $outcome_class = 'win'; } 
                        elseif($match['result'] === 'team2_win') { $outcome_text = htmlspecialchars($match['team2']) . " Won"; $outcome_class = 'win'; } 
                        elseif($match['result'] === 'draw') { $outcome_text = "Match Drawn"; $outcome_class = 'draw'; }
                    ?>
                        <div class="match-card">
                            <div class="teams">
                                <h3><?php echo htmlspecialchars($match['team1']); ?></h3>
                                <span class="score"><?php echo htmlspecialchars($match['team1_score'] ?? 'N/A') . ' - ' . htmlspecialchars($match['team2_score'] ?? 'N/A'); ?></span>
                                <h3><?php echo htmlspecialchars($match['team2']); ?></h3>
                            </div>
                            <ul class="details">
                                <li><i class="fas fa-map-marker-alt"></i><span><?php echo htmlspecialchars($match['venue']); ?></span></li>
                                <li><i class="fas fa-calendar-alt"></i><span><?php echo $dt->format('F j, Y'); ?></span></li>
                            </ul>
                            <?php if($outcome_class): ?><div class="outcome <?php echo $outcome_class; ?>"><?php echo $outcome_text; ?></div><?php endif; ?>
                            <div class="actions">
                                <button class="appeal-btn" data-event-id="<?php echo $match['event_id']; ?>" data-match-details="<?php echo htmlspecialchars($match['team1'] . ' vs ' . $match['team2']); ?>">
                                    <i class="fas fa-gavel"></i> Appeal Result
                                </button>
                            </div>
                            <div class="comments-section">
                                <h4>Comments</h4>
                                <div class="comments-list">
                                    <?php 
                                    $current_event_id = $match['event_id'];
                                    if (isset($comments_by_event[$current_event_id]) && !empty($comments_by_event[$current_event_id])):
                                        foreach ($comments_by_event[$current_event_id] as $comment):
                                    ?>
                                        <div class="comment">
                                            <div class="comment-header">
                                                <small class="comment-meta">
                                                    By <strong><?php echo htmlspecialchars($comment['user_name']); ?></strong> on <?php echo date('M d, Y', strtotime($comment['commented_at'])); ?>
                                                </small>
                                                <?php if($comment['user_id'] == $logged_in_user_id): ?>
                                                <div class="comment-actions">
                                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                                        <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                                        <button type="submit" name="delete_comment"><i class="fas fa-trash"></i> Delete</button>
                                                    </form>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <p class="comment-body"><?php echo htmlspecialchars($comment['comment']); ?></p>
                                        </div>
                                    <?php 
                                        endforeach;
                                    else:
                                    ?>
                                        <p class="no-comments">No comments yet.</p>
                                    <?php endif; ?>
                                </div>
                                <form class="comment-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                    <input type="hidden" name="event_id" value="<?php echo $match['event_id']; ?>">
                                    <textarea name="comment_text" class="form-textarea" rows="1" placeholder="Add a comment..." required></textarea>
                                    <button type="submit" name="submit_comment">Post</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <div id="points" class="tab-pane">
                <div class="data-table-container">
                    <table class="data-table">
                        <thead><tr><th>Rank</th><th>Team</th><th>Matches</th><th>Wins</th><th>Losses</th><th>Ties</th><th>Points</th></tr></thead>
                        <tbody>
                            <?php if(empty($points_table)): ?>
                                <tr><td colspan="7" style="text-align: center;">Point table is empty.</td></tr>
                            <?php else: $rank = 1; foreach ($points_table as $row): ?>
                                <tr>
                                    <td><?php echo $rank++; ?></td>
                                    <td><?php echo htmlspecialchars($row['team_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['played']); ?></td>
                                    <td><?php echo htmlspecialchars($row['wins']); ?></td>
                                    <td><?php echo htmlspecialchars($row['losses']); ?></td>
                                    <td><?php echo htmlspecialchars($row['draws']); ?></td>
                                    <td><?php echo htmlspecialchars($row['points']); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- NEW: Teams Tab Pane (Card Layout) -->
            <div id="teams" class="tab-pane">
                <div class="card-grid">
                    <?php if (empty($sport_teams)): ?>
                        <p class="empty-state">No teams registered for Volleyball yet.</p>
                    <?php else: ?>
                        <?php foreach ($sport_teams as $team): ?>
                            <div class="team-card">
                                <h3 class="team-card-title"><?php echo htmlspecialchars($team['team_name']); ?></h3>
                                <p class="team-card-detail">Players: <strong><?php echo htmlspecialchars($team['player_count']); ?></strong></p>
                                
                                <div class="team-card-actions">
                                    <button class="btn-view-members" data-team-id="<?php echo htmlspecialchars($team['team_id']); ?>">
                                        <i class="fas fa-users"></i> View Members
                                    </button>
                                </div>

                                <!-- Member list will be a div inside the card -->
                                <div id="team-members-list-<?php echo htmlspecialchars($team['team_id']); ?>" class="team-members-expanded-content" style="display: none;">
                                    <hr class="team-members-separator">
                                    <div class="team-members-list">
                                        <div class="team-members-loading" style="text-align: center; padding: 10px;">Loading members...</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <!-- END NEW: Teams Tab Pane -->

        </div>
    </div>

    <div class="modal" id="appealModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Appeal Match Result</h3>
                <button class="close-modal" id="closeAppealModal">&times;</button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <input type="hidden" name="event_id" id="appeal_event_id">
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

    <script>
        // Helper function to sanitize HTML output
        const sanitizeHTML = (text) => {
            if (text === null || typeof text === 'undefined') return '';
            const temp = document.createElement('div');
            temp.textContent = String(text);
            return temp.innerHTML;
        };

        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabPanes = document.querySelectorAll('.tab-pane');

            function switchTab(tabId) {
                tabButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tabId));
                tabPanes.forEach(pane => pane.classList.toggle('active', pane.id === tabId));
            }

            tabButtons.forEach(button => {
                button.addEventListener('click', () => switchTab(button.dataset.tab));
            });

            const urlParams = new URLSearchParams(window.location.search);
            const requestedTab = urlParams.get('tab');
            if (requestedTab) {
                switchTab(requestedTab);
            }

            const counter = document.querySelector('.team-count');
            if (counter) {
                const target = +counter.getAttribute('data-count');
                let current = 0;
                const increment = Math.max(1, target / 100);
                const updateCount = () => {
                    if (current < target) {
                        current = Math.min(target, current + increment);
                        counter.innerText = Math.ceil(current);
                        requestAnimationFrame(updateCount);
                    } else {
                        counter.innerText = target;
                    }
                };
                setTimeout(updateCount, 300);
            }

            const appealModal = document.getElementById('appealModal');
            const closeAppealModalBtn = document.getElementById('closeAppealModal');
            const appealEventIdInput = document.getElementById('appeal_event_id');
            const appealMatchDetailsText = document.getElementById('appeal_match_details');

            document.querySelectorAll('.appeal-btn').forEach(button => {
                button.addEventListener('click', function() {
                    appealEventIdInput.value = this.dataset.eventId;
                    appealMatchDetailsText.textContent = this.dataset.matchDetails;
                    appealModal.classList.add('active');
                });
            });

            function closeAppealModal() { appealModal.classList.remove('active'); }
            closeAppealModalBtn.addEventListener('click', closeAppealModal);
            appealModal.addEventListener('click', e => { if (e.target === appealModal) closeAppealModal(); });


            // NEW: Team Members functionality (adjusted for card layout)
            async function fetchAndDisplayTeamMembers(teamId, targetDiv) {
                targetDiv.innerHTML = '<div class="team-members-loading">Loading members...</div>';
                try {
                    const response = await fetch(`api.php?endpoint=teams&action=get_team_members&team_id=${teamId}`);
                    const result = await response.json();

                    if (result.status === 'success' && result.data.length > 0) {
                        let membersHtml = '<ul>';
                        result.data.forEach(member => {
                            membersHtml += `<li>
                                                <span class="member-name">${sanitizeHTML(member.name)}</span>
                                                <span class="member-role">${sanitizeHTML(member.role_in_team)}</span>
                                            </li>`;
                        });
                        membersHtml += '</ul>';
                        targetDiv.innerHTML = membersHtml;
                    } else if (result.status === 'success' && result.data.length === 0) {
                        targetDiv.innerHTML = '<div class="no-members">No members found for this team.</div>';
                    } else {
                        targetDiv.innerHTML = `<div class="no-members" style="color: var(--red);">Error loading members: ${sanitizeHTML(result.message)}</div>`;
                    }
                } catch (error) {
                    console.error('Error fetching team members:', error);
                    targetDiv.innerHTML = `<div class="no-members" style="color: var(--red);">Failed to load team members.</div>`;
                }
            }

            document.querySelectorAll('.btn-view-members').forEach(button => {
                button.addEventListener('click', function() {
                    const teamId = this.dataset.teamId;
                    const expandedContentDiv = document.getElementById(`team-members-list-${teamId}`);
                    const membersListDiv = expandedContentDiv.querySelector('.team-members-list');
                    
                    if (expandedContentDiv.style.display === 'none' || expandedContentDiv.style.display === '') {
                        expandedContentDiv.style.display = 'block'; // Show the expanded content
                        fetchAndDisplayTeamMembers(teamId, membersListDiv);
                        this.innerHTML = '<i class="fas fa-users"></i> Hide Members';
                        this.classList.add('active-members-btn'); // Add a class to indicate active state
                    } else {
                        expandedContentDiv.style.display = 'none'; // Hide the expanded content
                        membersListDiv.innerHTML = '<div class="team-members-loading">Loading members...</div>'; // Reset content when hiding for next load
                        this.innerHTML = '<i class="fas fa-users"></i> View Members';
                        this.classList.remove('active-members-btn'); // Remove active state class
                    }
                });
            });
            // END NEW: Team Members functionality
        });
    </script>
</body>
</html>