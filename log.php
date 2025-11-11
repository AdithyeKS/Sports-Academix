<?php
// Start a session to manage user login state. Must be the very first thing.
ini_set('session.cookie_httponly', 1);
// ini_set('session.cookie_secure', 1); // Uncomment and set to 1 in production when using HTTPS
ini_set('session.use_strict_mode', 1); // Prevents session fixation
session_start();

// Assumes config.php creates a mysqli connection object named $conn
include "config.php";

// Initialize variables to avoid errors
$register_error = '';
$login_error = '';
$forgot_error = '';
$forgot_success = '';
$reset_error = '';
$post_data = []; // To repopulate form fields on error

// --- HANDLE REGISTRATION REQUEST ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_action'])) {
    $post_data = $_POST;
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Concatenate first and last name for storage in the 'name' column
    $full_name = trim($first_name . ' ' . $last_name);

    // Name validation regex: starts with capital, only letters, no spaces
    $name_regex = '/^[A-Z][a-zA-Z]*$/';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $register_error = "All fields are required.";
    }
    // Validate First Name
    elseif (!preg_match($name_regex, $first_name)) {
        $register_error = "First Name must start with a capital letter, contain only letters, and no spaces.";
    }
    // Validate Last Name
    elseif (!preg_match($name_regex, $last_name)) {
        $register_error = "Last Name must start with a capital letter, contain only letters, and no spaces.";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "Invalid email format.";
    } elseif (strlen($password) < 8) {
        $register_error = "Password must be at least 8 characters long.";
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $register_error = "An account with this email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Insert full_name into the 'name' column
            $stmt_insert = $conn->prepare("INSERT INTO users(name, email, password, role, status, created_at) VALUES (?, ?, ?, 'user', 'active', NOW())");
            $stmt_insert->bind_param("sss", $full_name, $email, $hashed_password);

            if ($stmt_insert->execute()) {
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $stmt_insert->insert_id;
                $_SESSION['name'] = $full_name; // Store full name in session
                $_SESSION['role'] = 'user';
                session_regenerate_id(true); // Regenerate session ID after successful login
                header("location: home.php");
                exit;
            } else {
                $register_error = "Something went wrong. Please try again later.";
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
}

// --- HANDLE LOGIN REQUEST ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_action'])) {
    $post_data = $_POST;
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $login_error = "Email and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, name, password, role, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                if ($user['status'] === 'active') {
                    $_SESSION['loggedin'] = true;
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = $user['role'];
                    session_regenerate_id(true); // Regenerate session ID after successful login

                    if ($user['role'] === 'admin') {
                        header("location: admin.php");
                    } else {
                        header("location: home.php");
                    }
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
        $stmt->close();
    }
}

// --- HANDLE FORGOT PASSWORD (STEP 1: SEND OTP) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['forgot_action'])) {
    $post_data = $_POST;
    $email = trim($_POST['email']);

    if (empty($email)) {
        $forgot_error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $forgot_error = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $otp = random_int(100000, 999999);
            $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
            $otp_expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $stmt_update = $conn->prepare("UPDATE users SET otp_hash = ?, otp_expires_at = ? WHERE email = ?");
            $stmt_update->bind_param("sss", $otp_hash, $otp_expires_at, $email);
            $stmt_update->execute();
            $stmt_update->close();

            $_SESSION['reset_email'] = $email;
            // !!! IMPORTANT: REMOVE THIS LINE IN PRODUCTION. This exposes the OTP for testing.
            $_SESSION['otp_info'] = "For testing purposes, your OTP is: " . $otp;
            // In a real application, you would send this OTP via email to $email
            // mail($email, "Password Reset OTP", "Your OTP is: " . $otp);

            header("Location: log.php?view=reset");
            exit();
        } else {
            $forgot_success = "If an account with that email exists, an OTP has been sent.";
        }
        $stmt->close();
    }
}

// --- HANDLE RESET PASSWORD (STEP 2: VERIFY OTP & UPDATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_action'])) {
    $post_data = $_POST;
    $otp = $_POST['otp'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_SESSION['reset_email'] ?? null;

    if (empty($otp) || empty($password) || empty($confirm_password)) {
        $reset_error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $reset_error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $reset_error = "Password must be at least 8 characters long.";
    } elseif (!$email) {
        $reset_error = "Session expired. Please request a new OTP.";
    } else {
        $stmt = $conn->prepare("SELECT otp_hash, otp_expires_at FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || is_null($user['otp_hash']) || is_null($user['otp_expires_at'])) {
            $reset_error = "Invalid request. Please start over.";
        } elseif (strtotime($user['otp_expires_at']) < time()) {
            $reset_error = "The OTP has expired. Please request a new one.";
        } elseif (!password_verify($otp, $user['otp_hash'])) {
            $reset_error = "The OTP you entered is incorrect.";
        } else {
            $new_hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE users SET password = ?, otp_hash = NULL, otp_expires_at = NULL WHERE email = ?");
            $stmt_update->bind_param("ss", $new_hashed_password, $email);

            if ($stmt_update->execute()) {
                unset($_SESSION['reset_email']);
                unset($_SESSION['otp_info']); // Also remove the testing info
                $login_error = "Password has been reset successfully. Please log in.";
            } else {
                $reset_error = "Failed to update password. Please try again.";
            }
            $stmt_update->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" >
<head>
  <meta charset="UTF-8">
  <title>Sports Academix - Login & Registration</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
        color: #fff;
    }

    body{
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background: #0F172A;
    }

    .container{
        position: relative;
        width: 850px;
        height: 550px;
        border: 2px solid #1E40AF;
        box-shadow: 0 0 25px #1E40AF;
        overflow: hidden;
    }

    .container .form-box{
        position: absolute;
        top: 0;
        width: 50%;
        height: 100%;
        display: flex;
        justify-content: center;
        flex-direction: column;
        transition: all .7s ease;
    }

    .form-box.Login{
        left: 0;
        padding: 0 40px;
    }
    .container.show-register .form-box.Login,
    .container.show-forgot .form-box.Login,
    .container.show-reset .form-box.Login {
        transform: translateX(-120%);
        opacity: 0;
        pointer-events: none;
    }

    .form-box.Register{
        right: 0;
        padding: 0 60px;
        transform: translateX(120%);
        opacity: 0;
        pointer-events: none;
    }
    .container.show-register .form-box.Register{
        transform: translateX(0);
        opacity: 1;
        pointer-events: all;
    }

    .form-box.Forgot {
        left: 0;
        padding: 0 40px;
        transform: translateX(-120%);
        opacity: 0;
        pointer-events: none;
    }
    .container.show-forgot .form-box.Forgot {
        transform: translateX(0);
        opacity: 1;
        pointer-events: all;
    }

    .form-box.Reset {
        left: 0;
        padding: 0 40px;
        transform: translateX(-120%);
        opacity: 0;
        pointer-events: none;
    }
    .container.show-reset .form-box.Reset {
        transform: translateX(0);
        opacity: 1;
        pointer-events: all;
    }

    .form-box h2{
        font-size: 32px;
        text-align: center;
    }

    .form-box .input-box{
        position: relative;
        width: 100%;
        height: 50px;
        margin-top: 25px;
    }

    .input-box input{
        width: 100%;
        height: 100%;
        background: transparent;
        border: none;
        outline: none;
        font-size: 16px;
        color: #fff;
        font-weight: 600;
        border-bottom: 2px solid #fff;
        padding-right: 23px;
        transition: .5s;
    }

    .input-box input:focus,
    .input-box input:valid{
        border-bottom: 2px solid #1E40AF;
    }

    .input-box label{
        position: absolute;
        top: 50%;
        left: 0;
        transform: translateY(-50%);
        font-size: 16px;
        color: #fff;
        pointer-events: none;
        transition: .5s;
    }

    .input-box input:focus ~ label,
    .input-box input:valid ~ label{
        top: -5px;
        color: #1E40AF;
    }

    .input-box box-icon{
        position: absolute;
        top: 50%;
        right: 0;
        font-size: 18px;
        transform: translateY(-50%);
        color: #fff;
    }

    .input-box .password-toggle {
        cursor: pointer;
    }

    .input-box input:focus ~ box-icon,
    .input-box input:valid ~ box-icon{
        color: #1E40AF;
    }

    .btn{
        position: relative;
        width: 100%;
        height: 45px;
        background: transparent;
        border-radius: 40px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        border: 2px solid #1E40AF;
        overflow: hidden;
        z-index: 1;
    }

    .btn::before{
        content: "";
        position: absolute;
        height: 300%;
        width: 100%;
        background: linear-gradient(#0F172A, #1E40AF, #0F172A, #1E40AF);
        top: -100%;
        left: 0;
        z-index: -1;
        transition: .5s;
    }

    .btn:hover:before{
        top: 0;
    }

    .regi-link{
        font-size: 14px;
        text-align: center;
        margin: 20px 0 10px;
    }

    .regi-link a{
        text-decoration: none;
        color: #1E40AF;
        font-weight: 600;
    }

    .regi-link a:hover{
        text-decoration: underline;
    }

    .info-content{
        position: absolute;
        top: 0;
        height: 100%;
        width: 50%;
        display: flex;
        justify-content: center;
        flex-direction: column;
        transition: all .7s ease;
    }

    .info-content.Login{
        right: 0;
        text-align: right;
        padding: 0 40px 60px 150px;
    }
    .container.show-register .info-content.Login,
    .container.show-forgot .info-content.Login,
    .container.show-reset .info-content.Login {
        transform: translateX(120%);
        opacity: 0;
        pointer-events: none;
    }

    .info-content.Register{
        left: 0;
        text-align: left;
        padding: 0 150px 60px 38px;
        transform: translateX(-120%);
        opacity: 0;
        pointer-events: none;
    }
    .container.show-register .info-content.Register{
        transform: translateX(0);
        opacity: 1;
        pointer-events: all;
    }

    .info-content h2{
        text-transform: uppercase;
        font-size: 36px;
        line-height: 1.3;
    }

    .info-content p{
        font-size: 16px;
    }

    .container .curved-shape{
        position: absolute;
        right: 0;
        top: -5px;
        height: 600px;
        width: 850px;
        background: linear-gradient(45deg, #0F172A, #1E40AF);
        transform: rotate(10deg) skewY(40deg);
        transform-origin: bottom right;
        transition: 1.5s ease;
    }
    .container.show-register .curved-shape,
    .container.show-forgot .curved-shape,
    .container.show-reset .curved-shape {
        transform: rotate(0deg) skewY(0deg);
    }

    .container .curved-shape2{
        position: absolute;
        left: 250px;
        top: 100%;
        height: 700px;
        width: 850px;
        background: #0F172A;
        border-top: 3px solid #1E40AF;
        transform: rotate(0deg) skewY(0deg);
        transform-origin: bottom left;
        transition: 1.5s ease;
    }
    .container.show-register .curved-shape2,
    .container.show-forgot .curved-shape2,
    .container.show-reset .curved-shape2 {
        transform: rotate(-11deg) skewY(-41deg);
    }

    .server-error, .server-success {
        text-align: center;
        margin-bottom: 15px;
        font-weight: 600;
        font-size: 14px;
    }
    .server-error { color: #ff9999; }
    .server-success { color: #99ff99; }

    .input-box .input-error {
        position: absolute;
        bottom: -5px;
        left: 0;
        font-size: 13px;
        font-weight: 500;
        color: #ff9999;
    }

    /* --- NEW CSS FOR VALIDATION POPUP --- */
    .validation-popup {
        position: absolute;
        top: -45px; /* Position it above the input box */
        left: 0;
        background-color: #D32F2F; /* A material design error red */
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 500;
        z-index: 10;
        opacity: 0;
        transform: translateY(10px);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    .validation-popup.show {
        opacity: 1;
        transform: translateY(0);
    }
    .validation-popup::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 20px;
        border-width: 5px;
        border-style: solid;
        border-color: #D32F2F transparent transparent transparent;
    }
  </style>
</head>
<body>
    <div class="container">
        <div class="curved-shape"></div>
        <div class="curved-shape2"></div>

        <!-- LOGIN FORM -->
        <div class="form-box Login">
            <h2>Login</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" novalidate>
                <?php if (!empty($login_error)): ?>
                    <div class="server-error"><?php echo $login_error; ?></div>
                <?php endif; ?>
                <div class="input-box">
                    <input type="email" name="email" value="<?php echo htmlspecialchars($post_data['email'] ?? ''); ?>" required>
                    <label>Email</label>
                    <box-icon name='envelope' type='solid' color="gray"></box-icon>
                </div>
                <div class="input-box">
                    <input type="password" name="password" required>
                    <label>Password</label>
                    <box-icon name='eye-outline' class="password-toggle"></box-icon>
                </div>
                <div class="regi-link">
                    <p><a href="#" class="ForgotLink">Forgot Password?</a></p>
                </div>
                <div class="input-box">
                    <button class="btn" type="submit" name="login_action">Login</button>
                </div>
                <div class="regi-link">
                    <p>Don't have an account? <a href="#" class="SignUpLink">Sign Up</a></p>
                </div>
            </form>
        </div>

        <!-- REGISTRATION FORM -->
        <div class="form-box Register">
            <h2>Registration</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" novalidate>
                <?php if (!empty($register_error)): ?>
                    <div class="server-error"><?php echo $register_error; ?></div>
                <?php endif; ?>
                <div class="input-box">
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($post_data['first_name'] ?? ''); ?>" required>
                    <label>First Name</label>
                    <box-icon type='solid' name='user' color="gray"></box-icon>
                </div>
                <div class="input-box">
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($post_data['last_name'] ?? ''); ?>" required>
                    <label>Last Name</label>
                    <box-icon type='solid' name='user' color="gray"></box-icon>
                </div>
                <div class="input-box">
                    <input type="email" name="email" value="<?php echo htmlspecialchars($post_data['email'] ?? ''); ?>" required>
                    <label>Email</label>
                    <box-icon name='envelope' type='solid' color="gray"></box-icon>
                </div>
                <div class="input-box">
                    <input type="password" name="password" required>
                    <label>Password</label>
                    <box-icon name='eye-outline' class="password-toggle"></box-icon>
                </div>
                <div class="input-box">
                    <button class="btn" type="submit" name="register_action">Register</button>
                </div>
                <div class="regi-link">
                    <p>Already have an account? <a href="#" class="SignInLink">Sign In</a></p>
                </div>
            </form>
        </div>

        <!-- FORGOT PASSWORD FORM -->
        <div class="form-box Forgot">
            <h2>Forgot Password</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" novalidate>
                <?php if (!empty($forgot_error)): ?>
                    <div class="server-error"><?php echo $forgot_error; ?></div>
                <?php endif; ?>
                <?php if (!empty($forgot_success)): ?>
                    <div class="server-success"><?php echo $forgot_success; ?></div>
                <?php endif; ?>
                <div class="input-box">
                    <input type="email" name="email" value="<?php echo htmlspecialchars($post_data['email'] ?? ''); ?>" required>
                    <label>Enter your Email</label>
                    <box-icon name='envelope' type='solid' color="gray"></box-icon>
                </div>
                <div class="input-box">
                    <button class="btn" type="submit" name="forgot_action">Send OTP</button>
                </div>
                <div class="regi-link">
                    <p>Remember your password? <a href="#" class="SignInLink">Sign In</a></p>
                </div>
            </form>
        </div>

        <!-- RESET PASSWORD FORM -->
        <div class="form-box Reset">
            <h2>Reset Password</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" novalidate>
                <?php if (isset($_SESSION['otp_info'])): ?>
                    <div class="server-success"><?php echo $_SESSION['otp_info']; ?></div>
                <?php endif; ?>
                <?php if (!empty($reset_error)): ?>
                    <div class="server-error"><?php echo $reset_error; ?></div>
                <?php endif; ?>
                <div class="input-box">
                    <input type="text" name="otp" required>
                    <label>Enter OTP</label>
                    <box-icon name='key' type='solid' color="gray"></box-icon>
                </div>
                <div class="input-box">
                    <input type="password" name="password" required>
                    <label>New Password</label>
                    <box-icon name='eye-outline' class="password-toggle"></box-icon>
                </div>
                <div class="input-box">
                    <input type="password" name="confirm_password" required>
                    <label>Confirm New Password</label>
                    <box-icon name='eye-outline' class="password-toggle"></box-icon>
                </div>
                <div class="input-box">
                    <button class="btn" type="submit" name="reset_action">Reset Password</button>
                </div>
                <div class="regi-link">
                    <p>Didn't receive an OTP? <a href="#" class="ForgotLink">Request Again</a></p>
                </div>
            </form>
        </div>

        <!-- INFO CONTENT (Shared by all forms) -->
        <div class="info-content Login">
            <h2 class="animation" style="--D:0; --S:20">WELCOME BACK!</h2>
            <p class="animation" style="--D:1; --S:21">We are happy to have you with us again.</p>
        </div>
        <div class="info-content Register">
            <h2 class="animation" style="--li:17; --S:0">WELCOME!</h2>
            <p class="animation" style="--li:18; --S:1">We’re delighted to have you here.</p>
        </div>
    </div>

    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
    <script>
      const container = document.querySelector('.container');
      const AllSignInLinks = document.querySelectorAll('.SignInLink');
      const SignUpLink = document.querySelector('.SignUpLink');
      const AllForgotLinks = document.querySelectorAll('.ForgotLink');

      SignUpLink.addEventListener('click', (e) => {
          e.preventDefault();
          container.className = 'container show-register';
      });

      AllSignInLinks.forEach(link => {
          link.addEventListener('click', (e) => {
              e.preventDefault();
              container.className = 'container';
          });
      });

      AllForgotLinks.forEach(link => {
          link.addEventListener('click', (e) => {
              e.preventDefault();
              container.className = 'container show-forgot';
          });
      });

      const togglePasswordIcons = document.querySelectorAll('.password-toggle');
      togglePasswordIcons.forEach(icon => {
          icon.addEventListener('click', () => {
              const passwordInput = icon.parentElement.querySelector('input');
              if (passwordInput.type === 'password') {
                  passwordInput.type = 'text';
                  icon.setAttribute('name', 'eye-off-outline');
              } else {
                  passwordInput.type = 'password';
                  icon.setAttribute('name', 'eye-outline');
              }
          });
      });

      // --- NEW SCRIPT for client-side validation popups ---
      function showValidationPopup(inputElement, message) {
          // Remove any existing popup first
          const existingPopup = inputElement.parentElement.querySelector('.validation-popup');
          if (existingPopup) {
              existingPopup.remove();
          }

          const popup = document.createElement('div');
          popup.className = 'validation-popup';
          popup.textContent = message;

          inputElement.parentElement.appendChild(popup);

          // Trigger the animation
          setTimeout(() => {
              popup.classList.add('show');
          }, 10);

          // Automatically remove the popup after 3 seconds
          setTimeout(() => {
              popup.classList.remove('show');
              setTimeout(() => {
                  popup.remove();
              }, 300); // Wait for fade out transition to finish
          }, 3000);
      }

      document.querySelectorAll('form').forEach(form => {
          form.addEventListener('submit', function(event) {
              let isValid = true;
              const nameRegex = /^[A-Z][a-zA-Z]*$/; // Starts with Capital, only letters, no spaces

              // Client-side validation for the Registration form only
              if (form.closest('.form-box').classList.contains('Register')) {
                  const firstNameInput = form.querySelector('input[name="first_name"]');
                  const lastNameInput = form.querySelector('input[name="last_name"]');

                  if (firstNameInput && firstNameInput.value.trim() === '') {
                      showValidationPopup(firstNameInput, 'First Name is required.');
                      isValid = false;
                  } else if (firstNameInput && !nameRegex.test(firstNameInput.value.trim())) {
                      showValidationPopup(firstNameInput, 'First Name: Capital first letter, letters only, no spaces.');
                      isValid = false;
                  }

                  if (lastNameInput && lastNameInput.value.trim() === '') {
                      showValidationPopup(lastNameInput, 'Last Name is required.');
                      isValid = false;
                  } else if (lastNameInput && !nameRegex.test(lastNameInput.value.trim())) {
                      showValidationPopup(lastNameInput, 'Last Name: Capital first letter, letters only, no spaces.');
                      isValid = false;
                  }
              }

              // Email validation (applies to Login, Register, Forgot)
              const emailInput = form.querySelector('input[type="email"]');
              if (emailInput) {
                  const emailValue = emailInput.value.trim();
                  if (emailInput.hasAttribute('required') && emailValue === '') {
                      showValidationPopup(emailInput, 'Email is required.');
                      isValid = false;
                  } else if (emailValue !== '') {
                      const emailRegex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
                      if (!emailRegex.test(emailValue)) {
                          showValidationPopup(emailInput, 'Please enter a valid email address.');
                          isValid = false;
                      }
                  }
              }


              // Password validation (only for forms that have a password field)
              const passwordInput = form.querySelector('input[name="password"]');
              if (passwordInput) {
                  const passwordValue = passwordInput.value.trim();
                  if (passwordInput.hasAttribute('required') && passwordValue === '') {
                       showValidationPopup(passwordInput, 'Password is required.');
                       isValid = false;
                  } else if (passwordValue !== '') {
                      // Strong password: 8+ chars, 1 uppercase, 1 lowercase, 1 number, 1 special char
                      const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
                      if (!passwordRegex.test(passwordValue)) {
                          showValidationPopup(passwordInput, 'Password must be 8+ characters with uppercase, lowercase, number, and special symbol.');
                          isValid = false;
                      }
                  }
              }


              const confirmPasswordInput = form.querySelector('input[name="confirm_password"]');
              if (passwordInput && confirmPasswordInput) {
                  if (passwordInput.value !== confirmPasswordInput.value) {
                      showValidationPopup(confirmPasswordInput, 'Passwords do not match.');
                      isValid = false;
                  }
              }

              if (!isValid) {
                  event.preventDefault(); // Stop form submission
              }
          });
      });


      <?php
        // If an error occurred or a specific view is requested, show the correct form on page load.
        // The order matters here if multiple errors could potentially trigger different forms.
        // Reset password takes precedence if 'view=reset' is in URL or reset_error exists.
        if (!empty($reset_error) || ($_GET['view'] ?? '') === 'reset') {
            echo "container.className = 'container show-reset';";
        } elseif (!empty($forgot_error) || !empty($forgot_success)) {
            echo "container.className = 'container show-forgot';";
        } elseif (!empty($register_error)) {
            echo "container.className = 'container show-register';";
        }
      ?>
    </script>

</body>
</html>