<?php
// FILE: api/api_helpers.php

// Central function to send consistent JSON responses.
function send_json_response($status, $message, $data = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit();
}

// --- SECURITY CHECK ---
// This function must be called at the start of the router.
function authorize_admin() {
    if (!isset($_SESSION['loggedin']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin')) {
        http_response_code(403); // Forbidden
        send_json_response('error', 'Unauthorized: Admin access required.');
    }
}
?>