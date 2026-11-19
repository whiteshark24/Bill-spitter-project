<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'bill_splitter_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input_username = trim($_POST['username']);
    $input_password = $_POST['password'];
    
    // Get user by username
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$input_username]);
    $user = $stmt->fetch();
    
    // Verify password using password_verify()
    if ($user && password_verify($input_password, $user['password'])) {
        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        
        header('Location: dashboard.php');
        exit();
    } else {
        // Login failed
        header('Location: index.html?error=1');
        exit();
    }
}

// If not POST request
header('Location: index.html');
exit();
?>