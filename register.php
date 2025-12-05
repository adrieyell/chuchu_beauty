<?php
// Start session to use it later for redirection/messages
session_start();

// Include the database connection file
include('includes/db_config.php');

// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Sanitize and collect form data
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password']; // Raw password
    $confirm_password = $_POST['confirm_password']; // Raw confirm password
    $phone_number = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);

    // 2. Validate passwords
    if ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // 3. Hash the password for security
        // Use PASSWORD_DEFAULT for the best current hashing algorithm
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 4. Prepare SQL statement to insert new user
        $sql = "INSERT INTO users (fullname, email, password, phone_number, address) 
                VALUES ('$fullname', '$email', '$hashed_password', '$phone_number', '$address')";

        // 5. Execute query
        if ($conn->query($sql) === TRUE) {
            // Success: Set success message in session and redirect
            $_SESSION['registration_success'] = "Account created successfully! You may log in now.";
            header("Location: login.php");
            exit();
        } else {
            // Error handling, especially for unique constraints (e.g., email already exists)
            $error_message = "Error creating account: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChuChu Beauty | Register</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            
            <div class="auth-logo">
                <i class="fas fa-heart"></i> 
                </div>

            <h2 class="login-welcome">Create Your Account</h2>
            
            <?php if (isset($error_message)): ?>
                <div class="message error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required>

                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="name@example.com" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Create a password" required>

                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>

                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone_number" placeholder="Enter your phone number" required>

                <label for="address">Address</label>
                <input type="text" id="address" name="address" placeholder="Enter your address" required>

                <button type="submit" class="btn-pink">Create Account</button>
            </form>

            <p class="signup-link">
                Already have an account? <a href="login.php">Log in</a>
            </p>
        </div>
    </div>
</body>
</html>