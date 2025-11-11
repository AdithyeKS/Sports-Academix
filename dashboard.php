<?php
// FILE: api/endpoints/dashboard.php

if ($action == 'get_dashboard_data') {
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