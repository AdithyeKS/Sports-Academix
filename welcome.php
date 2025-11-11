<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
    <style>
        body { font-family: 'DM Sans', sans-serif; text-align: center; padding: 50px; }
        h1 { font-size: 2.5em; }
        p { font-size: 1.2em; }
        a { color: #096bc2; }
    </style>
</head>
<body>
    <h1>Hi, <b><?php echo htmlspecialchars($_SESSION["name"]); ?></b>. Welcome to Sports Academix!</h1>
    <p>Your role is: <b><?php echo htmlspecialchars($_SESSION["role"]); ?></b>.</p>
    <p><a href="logout.php">Sign Out of Your Account</a></p>
</body>
</html>