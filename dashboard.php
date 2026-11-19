<?php
session_start();
require_once 'includes/config.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];

// Get user's pending bills
$sql = "SELECT bs.*, b.bill_name, b.bill_type, b.total_amount, u.username as creator
        FROM bill_shares bs
        JOIN bills b ON bs.bill_id = b.id
        JOIN users u ON b.created_by = u.id
        WHERE bs.user_id = ? AND bs.is_paid = 0
        ORDER BY b.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$bills = $stmt->fetchAll();

// Calculate totals
$total_owed = 0;
foreach ($bills as $bill) {
    $total_owed += $bill['amount_owed'];
}

$pending_count = count($bills);
$all_paid = ($pending_count == 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bill Splitter</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="user-info">
            <div class="avatar">
                <?php echo strtoupper(substr($full_name, 0, 1)); ?>
            </div>
            <h3><?php echo htmlspecialchars($full_name); ?></h3>
            <p>@<?php echo htmlspecialchars($username); ?></p>
        </div>
        
        <nav class="menu">
            <a href="dashboard.php" class="menu-item active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="add-bill.php" class="menu-item">
                <i class="fas fa-plus-circle"></i> Add Bill
            </a>

            <a href="logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            <div class="date"><?php echo date('F j, Y'); ?></div>
        </div>

        <!-- All Paid Message -->
        <?php if($all_paid): ?>
        <div class="success-box">
            <i class="fas fa-check-circle"></i>
            <div>
                <h3>All Bills Paid!</h3>
                <p>No pending payments. Great job!</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-details">
                    <h4>Total Owed</h4>
                    <h2>ETB <?php echo number_format($total_owed, 2); ?></h2>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-details">
                    <h4>Pending Bills</h4>
                    <h2><?php echo $pending_count; ?></h2>
                </div>
            </div>
            
                        <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
            
                <div class="stat-details">
                    <h4>Roommates</h4>
                    <?php
                    // Get total roommate count
                    $count_sql = "SELECT COUNT(*) as total FROM users";
                    $count_stmt = $conn->query($count_sql);
                    $total_roommates = $count_stmt->fetch()['total'];
                    ?>
                    <h2><?php echo $total_roommates; ?></h2>
                </div>
            </div>




        </div>

        <!-- Bills Table -->
        <div class="bills-section">
            <div class="section-header">
                <h2><i class="fas fa-file-invoice"></i> Your Bills</h2>
                <a href="add-bill.php" class="add-btn">
                    <i class="fas fa-plus"></i> Add Bill
                </a>
            </div>

            <?php if($pending_count > 0): ?>
            <div class="table-container">
                <table class="bills-table">
                    <thead>
                        <tr>
                            <th>Bill Name</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>From</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($bills as $bill): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($bill['bill_name']); ?></td>
                            <td>
                                <span class="bill-type <?php echo $bill['bill_type']; ?>">
                                    <?php echo ucfirst($bill['bill_type']); ?>
                                </span>
                            </td>
                            <td class="amount">ETB <?php echo number_format($bill['amount_owed'], 2); ?></td>
                            <td>@<?php echo htmlspecialchars($bill['creator']); ?></td>
                            <td><span class="status pending">Pending</span></td>
                            <td>
                                <button class="pay-btn" onclick="payBill(<?php echo $bill['id']; ?>)">
                                    <i class="fas fa-check"></i> Pay
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h3>No Pending Bills</h3>
                <p>You're all caught up with payments.</p>
                <a href="add-bill.php" class="add-btn">
                    <i class="fas fa-plus"></i> Add First Bill
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function payBill(billId) {
        if(confirm('Mark this bill as paid?')) {
            fetch('mark-paid.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ bill_id: billId })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    }
    </script>
</body>
</html>