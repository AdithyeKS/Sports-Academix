<?php
// FILE: api/endpoints/sports.php

switch ($action) {
    case 'get_sports':
        $result = $conn->query("SELECT * FROM sports ORDER BY name ASC");
        send_json_response('success', 'Sports fetched.', $result->fetch_all(MYSQLI_ASSOC));
        break;
    case 'add_sport':
        $stmt = $conn->prepare("INSERT INTO sports (name, max_players, points_for_win, points_for_draw, points_for_loss, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiiis", $input['name'], $input['max_players'], $input['points_for_win'], $input['points_for_draw'], $input['points_for_loss'], $input['status']);
        if (!$stmt->execute()) { send_json_response('error', 'Failed to add sport. Error: ' . $stmt->error); }
        send_json_response('success', 'Sport added successfully.');
        break;
    case 'update_sport':
        $stmt = $conn->prepare("UPDATE sports SET name = ?, max_players = ?, points_for_win = ?, points_for_draw = ?, points_for_loss = ?, status = ? WHERE sport_id = ?");
        $stmt->bind_param("siiiisi", $input['name'], $input['max_players'], $input['points_for_win'], $input['points_for_draw'], $input['points_for_loss'], $input['status'], $input['id']);
        if (!$stmt->execute()) { send_json_response('error', 'Failed to update sport. Error: ' . $stmt->error); }
        send_json_response('success', 'Sport updated successfully.');
        break;
    case 'delete_sport':
        $stmt = $conn->prepare("DELETE FROM sports WHERE sport_id = ?");
        $stmt->bind_param("i", $input['id']);
        $stmt->execute();
        send_json_response('success', 'Sport deleted successfully.');
        break;
}