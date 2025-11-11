<?php
// FILE: api/endpoints/events.php

switch ($action) {
    case 'get_events':
        $sql = "SELECT e.*, s.name as sport_name, t1.team_name as team1_name, t2.team_name as team2_name FROM events e LEFT JOIN sports s ON e.sport_id = s.sport_id LEFT JOIN teams t1 ON e.team1_id = t1.team_id LEFT JOIN teams t2 ON e.team2_id = t2.team_id ORDER BY e.event_date DESC";
        $result = $conn->query($sql);
        send_json_response('success', 'All events fetched.', $result->fetch_all(MYSQLI_ASSOC));
        break;
    case 'get_upcoming_events':
        $sql = "SELECT e.event_id, e.event_name, s.name as sport_name, e.event_date, e.venue, e.status, t1.team_name as team1_name, t2.team_name as team2_name FROM events e LEFT JOIN sports s ON e.sport_id = s.sport_id LEFT JOIN teams t1 ON e.team1_id = t1.team_id LEFT JOIN teams t2 ON e.team2_id = t2.team_id WHERE e.status IN ('schedule', 'ongoing') AND e.event_date >= NOW() ORDER BY e.event_date ASC";
        $result = $conn->query($sql);
        send_json_response('success', 'Upcoming events fetched.', $result->fetch_all(MYSQLI_ASSOC));
        break;
    case 'add_event':
        $stmt = $conn->prepare("INSERT INTO events (event_name, sport_id, event_date, venue, description, team1_id, team2_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'schedule')");
        $stmt->bind_param("sisssii", $input['event_name'], $input['sport_id'], $input['event_date'], $input['venue'], $input['description'], $input['team1_id'], $input['team2_id']);
        if (!$stmt->execute()) { send_json_response('error', 'Failed to create event. Error: ' . $stmt->error); }
        send_json_response('success', 'Event created successfully.');
        break;
    case 'update_event':
        $stmt = $conn->prepare("UPDATE events SET event_name = ?, sport_id = ?, event_date = ?, venue = ?, description = ?, team1_id = ?, team2_id = ?, status = ? WHERE event_id = ?");
        $stmt->bind_param("sisssiisi", $input['event_name'], $input['sport_id'], $input['event_date'], $input['venue'], $input['description'], $input['team1_id'], $input['team2_id'], $input['status'], $input['id']);
        if (!$stmt->execute()) { send_json_response('error', 'Failed to update event. Error: ' . $stmt->error); }
        send_json_response('success', 'Event updated successfully.');
        break;
    case 'delete_event':
        $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
        $stmt->bind_param("i", $input['id']);
        $stmt->execute();
        send_json_response('success', 'Event deleted successfully.');
        break;
    case 'update_score':
        $score1 = (int)$input['team1_score']; $score2 = (int)$input['team2_score'];
        $result = ($score1 > $score2) ? 'team1_win' : (($score2 > $score1) ? 'team2_win' : 'draw');
        $stmt = $conn->prepare("UPDATE events SET team1_score = ?, team2_score = ?, status = 'completed', result = ? WHERE event_id = ?");
        $stmt->bind_param("iisi", $score1, $score2, $result, $input['id']);
        if (!$stmt->execute()) { send_json_response('error', 'Failed to update score. Error: ' . $stmt->error); }
        send_json_response('success', 'Score updated and event finalized.');
        break;
}