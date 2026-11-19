<?php
session_start();

require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $bill_name = $_POST['bill_name'];
    $bill_type = $_POST['bill_type'];
    $total_amount = floatval($_POST['total_amount']);
    $split_method = $_POST['split_method'];
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // 1. Insert bill
        $sql = "INSERT INTO bills (bill_name, total_amount, bill_type, split_method, created_by) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$bill_name, $total_amount, $bill_type, $split_method, $user_id]);
        $bill_id = $conn->lastInsertId();
        
        // 2. Get all roommates
        $roommates_sql = "SELECT id FROM users";
        $roommates_stmt = $conn->query($roommates_sql);
        $roommates = $roommates_stmt->fetchAll();
        
        // Check if there are roommates to split with
        if (count($roommates) < 2) {
            throw new Exception("Need at least 2 roommates to split bills");
        }
        
        // 3. Insert bill shares
        if ($split_method == 'equal') {
            // Equal split
            $each_share = $total_amount / count($roommates);
            
            $share_sql = "INSERT INTO bill_shares (bill_id, user_id, amount_owed) VALUES (?, ?, ?)";
            $share_stmt = $conn->prepare($share_sql);
            
            foreach ($roommates as $roommate) {
                $share_stmt->execute([$bill_id, $roommate['id'], $each_share]);
            }
        }         else {
            // Custom split - exclude users with 0 or negative amount
            $custom_amounts = $_POST['custom_amounts'];
            
            $share_sql = "INSERT INTO bill_shares (bill_id, user_id, amount_owed) VALUES (?, ?, ?)";
            $share_stmt = $conn->prepare($share_sql);
            
            $participants_count = 0;
            $total_allocated = 0;
            
            foreach ($custom_amounts as $roommate_id => $amount) {
                $amount = floatval($amount);
                
                // Only include users with positive amount (> 0)
                if ($amount > 0.01) { // Using 0.01 to account for rounding
                    $share_stmt->execute([$bill_id, $roommate_id, $amount]);
                    $participants_count++;
                    $total_allocated += $amount;
                }
            }
            
            // Validate that we have at least 2 participants
            if ($participants_count < 2) {
                throw new Exception("Custom split requires at least 2 participants with positive amounts");
            }
            
            // Validate total matches bill amount (with small tolerance for rounding)
            if (abs($total_amount - $total_allocated) > 0.01) {
                throw new Exception("Total allocated amount (ETB " . number_format($total_allocated, 2) . ") does not match bill total (ETB " . number_format($total_amount, 2) . ")");
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Success
        header('Location: dashboard.php?success=1');
        exit();
        
    } catch (Exception $e) {
        // Rollback on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        header('Location: add-bill.php?error=1');
        exit();
    }
}
?>