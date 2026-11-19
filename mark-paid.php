<?php
session_start();
require_once 'includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$bill_id = intval($input['bill_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($bill_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid bill']);
    exit();
}

try {
    $sql = "UPDATE bill_shares SET is_paid = 1, paid_at = NOW() 
            WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$bill_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Payment recorded']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Bill not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>