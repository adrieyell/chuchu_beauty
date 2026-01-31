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
    $phone_number = $conn->real_escape_string($_POST['phone_number']);
    $address = $conn->real_escape_string($_POST['address']);

    // 2. Validate passwords
    $validation_errors = [];
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        $validation_errors[] = "Passwords do not match.";
    }
    
    // Check password length (minimum 8 characters)
    if (strlen($password) < 8) {
        $validation_errors[] = "Password must be at least 8 characters long.";
    }
    
    // Optional: Check if password contains at least one number
    if (!preg_match('/[0-9]/', $password)) {
        $validation_errors[] = "Password must contain at least one number.";
    }
    
    // Optional: Check if password contains at least one letter
    if (!preg_match('/[a-zA-Z]/', $password)) {
        $validation_errors[] = "Password must contain at least one letter.";
    }
    
    // If there are validation errors, display them
    if (!empty($validation_errors)) {
        $error_message = implode("<br>", $validation_errors);
    } else {
        // 3. Hash the password for security
        // Use PASSWORD_DEFAULT for the best current hashing algorithm
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 4. Check if email already exists
        $check_email_sql = "SELECT email FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_email_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "This email is already registered. Please use a different email or <a href='login.php'>log in</a>.";
        } else {
            // 5. Prepare SQL statement to insert new user using prepared statements
            $sql = "INSERT INTO users (fullname, email, password, phone_number, address) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $fullname, $email, $hashed_password, $phone_number, $address);

            // 6. Execute query
            if ($stmt->execute()) {
                // Success: Set success message in session and redirect
                $_SESSION['registration_success'] = "Account created successfully! You may log in now.";
                header("Location: login.php");
                exit();
            } else {
                // Error handling
                $error_message = "Error creating account. Please try again.";
            }
            
            $stmt->close();
        }
        
        $check_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sol Beauty | Register</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            border: 1px solid #f5c6cb;
            font-size: 0.9em;
            line-height: 1.6;
        }
        
        .password-requirements {
            background-color: #fff5f8;
            border: 1px solid var(--pink-medium);
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 15px;
            font-size: 0.85em;
        }
        
        .password-requirements h4 {
            color: var(--pink-dark);
            margin: 0 0 8px 0;
            font-size: 0.95em;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
            color: var(--gray-text);
        }
        
        .password-requirements li {
            margin: 3px 0;
        }
        
        .password-strength {
            height: 5px;
            background-color: #e0e0e0;
            border-radius: 3px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 3px;
        }
        
        .strength-weak { width: 33%; background-color: #dc3545; }
        .strength-medium { width: 66%; background-color: #ffc107; }
        .strength-strong { width: 100%; background-color: #28a745; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            
            <div class="auth-logo">
                <i class="fas fa-sun"></i> 
            </div>

            <h2 class="login-welcome">Create Your Account</h2>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST" id="registerForm">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required value="<?php echo isset($fullname) ? htmlspecialchars($fullname) : ''; ?>">

                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="name@example.com" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">

                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Create a password" required minlength="8">
                
                <!-- Password strength indicator -->
                <div class="password-strength" id="passwordStrength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                <small id="strengthText" style="color: var(--gray-text); font-size: 0.8em;"></small>

                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required minlength="8">

                <!-- Password requirements info box -->
                <div class="password-requirements">
                    <h4><i class="fas fa-info-circle"></i> Password Requirements:</h4>
                    <ul>
                        <li>At least 8 characters long</li>
                        <li>Must contain at least one letter</li>
                        <li>Must contain at least one number</li>
                    </ul>
                </div>

                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone_number" placeholder="Enter your phone number" required value="<?php echo isset($phone_number) ? htmlspecialchars($phone_number) : ''; ?>">

                <label for="address">Address</label>
                <input type="text" id="address" name="address" placeholder="Enter your address" required value="<?php echo isset($address) ? htmlspecialchars($address) : ''; ?>">

                <button type="submit" class="btn-pink">Create Account</button>
            </form>

            <p class="signup-link">
                Already have an account? <a href="login.php">Log in</a>
            </p>
        </div>
    </div>
    
    <script>
        // Real-time password strength checker
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Check length
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Check for numbers
            if (/[0-9]/.test(password)) strength++;
            
            // Check for letters
            if (/[a-zA-Z]/.test(password)) strength++;
            
            // Check for special characters
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            // Update strength bar
            strengthBar.className = 'password-strength-bar';
            
            if (password.length === 0) {
                strengthBar.style.width = '0%';
                strengthText.textContent = '';
            } else if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Weak password';
                strengthText.style.color = '#dc3545';
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Medium strength';
                strengthText.style.color = '#ffc107';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Strong password!';
                strengthText.style.color = '#28a745';
            }
        });
        
        // Confirm password validation
        const confirmPasswordInput = document.getElementById('confirm_password');
        const form = document.getElementById('registerForm');
        
        form.addEventListener('submit', function(e) {
            if (passwordInput.value !== confirmPasswordInput.value) {
                e.preventDefault();
                alert('Passwords do not match!');
                confirmPasswordInput.focus();
            }
        });
    </script>
</body>
</html>