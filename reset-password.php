<?php
// Start the session to access session variables
session_start();
// Include the database configuration file
include "config.php";

// If the user hasn't been redirected from the "forgot password" page,
// send them back to the login page.
if (!isset($_SESSION['reset_email'])) {
    header("location: index.php");
    exit;
}

// Initialize variables
$reset_error = '';
$email = $_SESSION['reset_email']; // Get the user's email from the session

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve form inputs
    $otp = trim($_POST['otp']);
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // --- VALIDATION ---
    if (empty($otp) || empty($new_password) || empty($confirm_password)) {
        $reset_error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $reset_error = "Passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $reset_error = "Password must be at least 8 characters long.";
    } else {
        // --- OTP VERIFICATION ---
        // Prepare a statement to find a user with the matching email and OTP,
        // and ensure the OTP has not expired.
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND reset_token = ? AND reset_token_expires_at > NOW()");
        $stmt->bind_param("ss", $email, $otp);
        $stmt->execute();
        $stmt->store_result();

        // Check if a matching, valid OTP was found
        if ($stmt->num_rows > 0) {
            // OTP is valid. Hash the new password for secure storage.
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Prepare a statement to update the password and clear the reset token fields
            $stmt_update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE email = ?");
            $stmt_update->bind_param("ss", $hashed_password, $email);
            
            if ($stmt_update->execute()) {
                // Password updated successfully. Clean up the session.
                unset($_SESSION['reset_email']);
                // Set a success message to display on the login page
                $_SESSION['login_message'] = "Your password has been reset successfully. Please log in.";
                // Redirect to the login page
                header("location: index.php");
                exit;
            } else {
                $reset_error = "Failed to update password. Please try again.";
            }
            $stmt_update->close();
        } else {
            // No matching record was found, so the OTP was invalid or expired.
            $reset_error = "Invalid or expired OTP. Please request a new one.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - Sports Academix</title>
    
    <!-- CSS is embedded directly in the HTML head -->
    <style>
        /* General Styling */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #0b1a2e; /* Fallback color */
            background-size: cover;
            background-position: center;
        }

        /* Wrapper for the form box */
        .wrapper {
            position: relative;
            width: 400px;
            height: auto;
            background: transparent;
            border: 2px solid rgba(255, 255, 255, .5);
            border-radius: 20px;
            backdrop-filter: blur(20px);
            box-shadow: 0 0 30px rgba(0, 0, 0, .5);
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            padding: 30px 40px;
        }

        /* Form Box Styling */
        .form-box {
            width: 100%;
        }

        .form-box h2 {
            font-size: 2em;
            color: #fff;
            text-align: center;
            margin-bottom: 20px;
        }
        
        /* Input Field Styling */
        .input-box {
            position: relative;
            width: 100%;
            height: 50px;
            border-bottom: 2px solid #fff;
            margin: 30px 0;
        }

        .input-box label {
            position: absolute;
            top: 50%;
            left: 5px;
            transform: translateY(-50%);
            font-size: 1em;
            color: #fff;
            font-weight: 500;
            pointer-events: none;
            transition: .5s;
        }

        .input-box input:focus~label,
        .input-box input:valid~label {
            top: -5px;
        }

        .input-box input {
            width: 100%;
            height: 100%;
            background: transparent;
            border: none;
            outline: none;
            font-size: 1em;
            color: #fff;
            font-weight: 600;
            padding: 0 35px 0 5px;
        }

        .input-box .icon {
            position: absolute;
            right: 8px;
            font-size: 1.2em;
            color: #fff;
            line-height: 57px;
        }

        /* Button Styling */
        .btn {
            width: 100%;
            height: 45px;
            background: #f8f8f8;
            border: none;
            outline: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            color: #162938;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: #dcdcdc;
        }
        
        /* Server Message Styling */
        .server-message {
            padding: 12px;
            margin-bottom: 16px;
            border-radius: 5px;
            text-align: center;
            font-size: 0.9em;
            font-weight: 500;
            color: #fff;
            border: 1px solid #f5c6cb;
            background-color: #721c24;
        }

        /* Informational Text */
        .info-text {
            color: #fff;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="form-box">
            <h2>Set New Password</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" novalidate>

                <?php if (!empty($reset_error)): ?>
                    <div class="server-message"><?php echo $reset_error; ?></div>
                <?php endif; ?>

                <p class="info-text">
                    An OTP has been sent to <strong><?php echo htmlspecialchars($email); ?></strong>.
                </p>

                <div class="input-box">
                    <span class="icon"><ion-icon name="keypad-outline"></ion-icon></span>
                    <input type="text" name="otp" required autocomplete="off">
                    <label>Enter OTP</label>
                </div>
                <div class="input-box">
                    <span class="icon"><ion-icon name="lock-closed"></ion-icon></span>
                    <input type="password" name="password" required>
                    <label>New Password</label>
                </div>
                <div class="input-box">
                    <span class="icon"><ion-icon name="lock-closed-outline"></ion-icon></span>
                    <input type="password" name="confirm_password" required>
                    <label>Confirm New Password</label>
                </div>

                <button type="submit" class="btn">Reset Password</button>
            </form>
        </div>
    </div>
    
    <!-- Scripts for Icons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>