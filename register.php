<?php
session_start();
require_once 'includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($full_name) || empty($password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Check if username exists
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute([$username]);
        
        if ($check_stmt->rowCount() > 0) {
            $error = 'Username already exists';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $sql = "INSERT INTO users (username, password, full_name, created_at) 
                    VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            
            if ($stmt->execute([$username, $hashed_password, $full_name])) {
                $success = 'Registration successful! You can now login.';
                header('Location: index.html');
exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Bill Splitter</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <i class="fas fa-money-bill-wave"></i>
                <h1>Register</h1>
            </div>
            <p class="tagline">Create a new account</p>
            
            <?php if($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if($success): ?>
            <div class="success-box" style="margin: 20px 0;">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="input-group">
                    <label for="full_name"> <i class="fas fa-user"></i> Full Name </label>
                    <input type="text" id="full_name" name="full_name" required placeholder="Enter your full name">
                </div>
                
                <div class="input-group">
                    <label for="username"> <i class="fas fa-user-tag"></i> Username </label>
                    <input type="text" id="username" name="username" required placeholder="Choose a username">
                </div>
                
                <div class="input-group">
                    <label for="password"> <i class="fas fa-lock"></i> Password </label>
                    <input type="password" id="password" name="password" required placeholder="Minimum 6 characters">
                </div>
                
                <div class="input-group">
                    <label for="confirm_password"> <i class="fas fa-lock"></i> Confirm Password </label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter password">
                </div>
                
                <button type="submit" class="login-btn">
                    <i class="fas fa-user-plus"></i> Register
                </button>
                
                <div style="text-align: center; margin-top: 20px;">
                    <p>Already have an account? <a href="index.html" style="color: #6a11cb;">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>