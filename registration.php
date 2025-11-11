<?php
// FILE: api/endpoints/registrations.php

switch ($action) {
    case 'get_pending_registrations':
        $sql = "SELECT r.registration_id, u.name AS user_name, s.name AS sport_name, r.registered_at, CASE WHEN r.team_id IS NOT NULL THEN t.team_name ELSE r.department END AS registration_detail, CASE WHEN r.team_id IS NOT NULL THEN 'Team' ELSE 'Department' END AS registration_type FROM registrations r JOIN users u ON r.user_id = u.user_id LEFT JOIN sports s ON r.sport_id = s.sport_id LEFT JOIN teams t ON r.team_id = t.team_id WHERE r.status = 'pending' ORDER BY r.registered_at DESC";
        $result = $conn->query($sql);
        send_json_response('success', 'Pending registrations fetched.', $result->fetch_all(MYSQLI_ASSOC));
        break;
    case 'approve_reg':
    case 'reject_reg':
        $new_status = ($action === 'approve_reg') ? 'approved' : 'rejected';
        $stmt = $conn->prepare("UPDATE registrations SET status = ? WHERE registration_id = ?");
        $stmt->bind_param("si", $new_status, $input['id']);
        $stmt->execute();
        send_json_response('success', 'Registration status updated.');
        break;
}