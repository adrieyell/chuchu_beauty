<?php
session_start();
include('includes/db_config.php');

// Check for successful registration message (from register.php)
$success_message = '';
if (isset($_SESSION['registration_success'])) {
    $success_message = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']); // Clear the message after display
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Sanitize and collect form data
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // 2. Query the user by email - ADDED missing fields for checkout pre-fill
    $sql = "SELECT user_id, password, role, fullname, phone_number, address FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
  
        // FIXED: Use password_verify instead of md5
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = TRUE;
            
            // Store user details for checkout pre-fill
            $_SESSION['user_fullname'] = $user['fullname'];
            $_SESSION['user_phone'] = $user['phone_number'];
            $_SESSION['user_address'] = $user['address'];

            // 4. Redirect based on role
            if ($user['role'] == 'admin') {
                header("Location: admin/dashboard.php"); // Redirect to admin dashboard
            } elseif ($user['role'] == 'staff') {
                header("Location: staff/dashboard.php"); // Redirect to staff dashboard
            } else {
                header("Location: index.php"); // Redirect to product page (home)
            }
            exit();
        } else {
            $error_message = "Invalid email or password.";
        }
    } else {
        $error_message = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sol Beauty | Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">
            <i class="fas fa-sun"></i> 
        </div>

        <h2 class="login-welcome">Welcome back!</h2>

        <?php if (!empty($success_message)): ?>
            <div class="message success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="message error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="login-form">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="name@personal.com" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn-pink">Login</button>
            
            <p class="signup-link">
                Don't have an account? <a href="register.php">Create Account</a>
            </p>
        </form>
    </div>
</div>
</body>
</html>