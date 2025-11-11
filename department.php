<?php
// FILE: api/endpoints/departments.php

switch ($action) {
    case 'get_departments':
        $result = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");
        send_json_response('success', 'Departments fetched.', $result->fetch_all(MYSQLI_ASSOC));
        break;
    case 'add_department':
        $stmt = $conn->prepare("INSERT INTO departments (department_name) VALUES (?)");
        $stmt->bind_param("s", $input['department_name']);
        if (!$stmt->execute()) { send_json_response('error', 'Failed to add department. It may already exist.'); }
        send_json_response('success', 'Department added successfully.');
        break;
    case 'update_department':
        $stmt = $conn->prepare("UPDATE departments SET department_name = ? WHERE department_id = ?");
        $stmt->bind_param("si", $input['department_name'], $input['id']);
        if (!$stmt->execute()) { send_json_response('error', 'Failed to update department.'); }
        send_json_response('success', 'Department updated successfully.');
        break;
    case 'delete_department':
        $stmt = $conn->prepare("DELETE FROM departments WHERE department_id = ?");
        $stmt->bind_param("i", $input['id']);
        $stmt->execute();
        send_json_response('success', 'Department deleted successfully.');
        break;
}