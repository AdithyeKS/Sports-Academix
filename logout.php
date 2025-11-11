<?php
// File: logout.php

// 1. Initialize the session
session_start();

// 2. Unset all of the session variables
$_SESSION = array();

// 3. Destroy the session cookie.
// This will delete the session cookie, so on the next page load,
// the browser won't send the old session ID.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finally, destroy the session data on the server.
session_destroy();

// 5. Redirect the user to the login page.
// The user is now fully logged out.
header("Location: log.php");
exit(); // Important to prevent any further script execution
?>