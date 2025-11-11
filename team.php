<?php
// FILE: api/endpoints/teams.php

switch ($action) {
    case 'get_teams':
        $sql = "SELECT t.team_id, t.team_name, s.name as sport_name, t.sport_id, u.name as creator_name, (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = t.team_id) as player_count FROM teams t LEFT JOIN sports s ON t.sport_id = s.sport_id LEFT JOIN users u ON t.created_by = u.user_id ORDER BY t.team_name ASC";
        $result = $conn->query($sql);
        send_json_response('success', 'Teams fetched.', $result->fetch_all(MYSQLI_ASSOC));
        break;
    case 'add_team':
        $stmt = $conn->prepare("INSERT INTO teams (team_name, sport_id, created_by) VALUES (?, ?, ?)");
        $admin_id = $_SESSION['user_id'];
        $stmt->bind_param("sii", $input['team_name'], $input['sport_id'], $admin_id);
        if (!$stmt->execute()) { send_json_response('error', 'Failed to create team. Error: ' . $stmt->error); }
        send_json_response('success', 'Team created successfully.');
        break;
    case 'update_team':
        $stmt = $conn->prepare("UPDATE teams SET team_name = ?, sport_id = ? WHERE team_id = ?");
        $stmt->bind_param("sii", $input['team_name'], $input['sport_id'], $input['id']);
        if (!$stmt->execute()) { send_json_response('error', 'Failed to update team. Error: ' . $stmt->error); }
        send_json_response('success', 'Team updated successfully.');
        break;
    case 'delete_team':
        $stmt = $conn->prepare("DELETE FROM teams WHERE team_id = ?");
        $stmt->bind_param("i", $input['id']);
        $stmt->execute();
        send_json_response('success', 'Team deleted successfully.');
        break;
}```

#### `api/endpoints/feedback.php`
```php
<?php
// FILE: api/endpoints/feedback.php

switch ($action) {
    case 'get_feedback':
        $sql = "SELECT f.*, u.name as user_name, u.email as user_email FROM feedback f JOIN users u ON f.user_id = u.user_id ORDER BY f.is_read ASC, f.submitted_at DESC";
        $result = $conn->query($sql);
        send_json_response('success', 'Feedback fetched.', $result->fetch_all(MYSQLI_ASSOC));
        break;
    case 'toggle_feedback_read':
        $id = $input['id'] ?? 0;
        if ($id > 0) {
            $stmt_get = $conn->prepare("SELECT is_read FROM feedback WHERE feedback_id = ?");
            $stmt_get->bind_param("i", $id);
            $stmt_get->execute();
            $result = $stmt_get->get_result();
            if ($row = $result->fetch_assoc()) {
                $new_status = $row['is_read'] == 1 ? 0 : 1;
                $stmt_update = $conn->prepare("UPDATE feedback SET is_read = ? WHERE feedback_id = ?");
                $stmt_update->bind_param("ii", $new_status, $id);
                $stmt_update->execute();
                send_json_response('success', 'Feedback status toggled.');
            } else {
                send_json_response('error', 'Feedback item not found.');
            }
        }
        break;
    case 'delete_feedback':
        $stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id = ?");
        $stmt->bind_param("i", $input['id']);
        $stmt->execute();
        send_json_response('success', 'Feedback deleted.');
        break;
}