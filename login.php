<?php
// Start a session to manage user login state. Must be the very first thing.
session_start();
include "config.php";

// --- HANDLE REGISTRATION REQUEST ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_action'])) {
    // $conn = getDbConnection();

    // Sanitize user inputs to prevent XSS and SQL Injection
    $name = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Server-side validation
    if (empty($name) || empty($email) || empty($password)) {
        $register_error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "Invalid email format.";
    } elseif (strlen($password) < 8) {
        $register_error = "Password must be at least 8 characters long.";
    } else {
        $sql = "SELECT * FROM user WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $register_error = "An account with this email already exists.";
        } else {

            // Hash the password for secure storage
            // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert the new user into the database
            $sql = "INSERT INTO user(name, email, password, role, status, created_at) VALUES ('$name', '$email', '$password', 'user', 'active', NOW())";
            $result = mysqli_query($conn ,$sql);
            if ($result) {
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = mysqli_insert_id($conn);;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = 'user';
                header("location: home.html");
                exit;
            } else {
                $register_error = "Something went wrong. Please try again later.";
            }
        }
    }
}
// LOGIN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_action'])) {
    // $conn = getDbConnection();

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $login_error = "Email and password are required.";
    } else {
        $sql = "SELECT * FROM user WHERE email = '$email'";
        
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password and account status
            if ($password == $user['password']) {
                if ($user['status'] === 'active') {
                    // Password is correct, start a new session
                    $_SESSION['loggedin'] = true;
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = $user['role'];

                    if($user['role'] === 'admin'){
                        header("location: admin.html");
                        exit;
                    }
                    // Redirect user to the dashboard/welcome page
                    header("location: home.html"); // Make sure welcome.php exists
                    exit;
                } else {
                    $login_error = "Your account is blocked. Please contact an administrator.";
                }
            } else {
                $login_error = "The email or password you entered is incorrect.";
            }
        } else {
            $login_error = "The email or password you entered is incorrect.";
        }
    }
}
?>