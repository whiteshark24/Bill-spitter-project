<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all roommates 
$sql = "SELECT id, full_name, username FROM users ORDER BY full_name";
$stmt = $conn->query($sql);
$roommates = $stmt->fetchAll();
$roommate_count = count($roommates);

// Check if there are enough roommates to split bills
if ($roommate_count < 2) {
    die('<div style="padding: 20px; text-align: center;">
        <h2>Not Enough Roommates</h2>
        <p>You need at least 2 roommates to split bills.</p>
        <p>Please ask other roommates to register first.</p>
        <a href="dashboard.php">Back to Dashboard</a>
    </div>');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Bill - Bill Splitter</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="user-info">
            <div class="avatar">
                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
            </div>
            <h3><?php echo htmlspecialchars($_SESSION['full_name']); ?></h3>
            <p>@<?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
        
        <nav class="menu">
            <a href="dashboard.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="add-bill.php" class="menu-item active">
                <i class="fas fa-plus-circle"></i> Add Bill
            </a>
            <a href="logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-plus-circle"></i> Add New Bill</h1>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="form-container">
            <form id="billForm" action="save-bill.php" method="POST">
                <div class="form-row">
                    <div class="input-group">
                        <label for="bill_name">
                            <i class="fas fa-file-invoice"></i> Bill Name
                        </label>
                        <input type="text" id="bill_name" name="bill_name" required 
                               placeholder="e.g., January Electricity">
                    </div>
                    
                    <div class="input-group">
                        <label for="bill_type">
                            <i class="fas fa-tag"></i> Bill Type
                        </label>
                        <select id="bill_type" name="bill_type" required>
                            <option value="">Select type</option>
                            <option value="electricity">⚡ Electricity</option>
                            <option value="water">💧 Water</option>
                            <option value="internet">🌐 Internet</option>
                            <option value="rent">🏠 Rent</option>
                            <option value="gas">🔥 Gas</option>
                            <option value="other">📦 Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="total_amount">
                        <i class="fas fa-money-bill"></i> Total Amount (ETB)
                    </label>
                    <input type="number" id="total_amount" name="total_amount" required 
                           step="0.01" min="1" placeholder="0.00" value="120" oninput="updateSplit()">
                </div>
                
                <div class="split-section">
                    <label><i class="fas fa-calculator"></i> Split Method</label>
                    <div class="split-options">
                        <div class="split-option equal-split selected" onclick="selectSplit('equal')">
                            <i class="fas fa-equals"></i>
                            <h4>Equal Split</h4>
                            <p>Divide equally among all</p>
                        </div>
                        <div class="split-option custom-split" onclick="selectSplit('custom')">
                            <i class="fas fa-sliders-h"></i>
                            <h4>Custom Split</h4>
                            <p>Set custom amounts</p>
                        </div>
                    </div>
                    <input type="hidden" id="split_method" name="split_method" value="equal">
                </div>
                
                <!-- Custom Split Fields -->
                <div id="customSplitSection" class="custom-split-fields" style="display: none;">
                    <h3><i class="fas fa-users"></i> Set Amounts</h3>
                    <div id="customSplitList">
                        <?php 
                        $default = 120 / $roommate_count;
                        foreach($roommates as $roommate): 
                        ?>
                        <div class="split-item">
                            <div class="roommate-info">
                                <div class="roommate-avatar">
                                    <?php echo strtoupper(substr($roommate['full_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="roommate-name"><?php echo $roommate['full_name']; ?></div>
                                    <div class="roommate-username">@<?php echo $roommate['username']; ?></div>
                                </div>
                            </div>
                            <div class="amount-input">
                                <span class="currency">ETB </span>
                                <input type="number" 
                                       class="custom-amount" 
                                       data-user-id="<?php echo $roommate['id']; ?>"
                                       step="0.01" 
                                       min="0" 
                                       value="<?php echo number_format($default, 2); ?>"
                                       oninput="updateCustomAmount(this)">
                                <input type="hidden" 
                                       name="custom_amounts[<?php echo $roommate['id']; ?>]" 
                                       value="<?php echo number_format($default, 2); ?>">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="split-summary">
                        <div class="total-info">
                            <div>Allocated: <span id="allocatedTotal">ETB 120.00</span></div>
                            <div>Bill Total: <span id="billTotal">ETB 120.00</span></div>
                            <div id="remainingInfo">Remaining: <span id="remainingAmount">ETB 0.00</span></div>
                        </div>
                        <button type="button" class="reset-btn" onclick="resetToEqual()">
                            <i class="fas fa-redo"></i> Reset to Equal
                        </button>
                    </div>
                </div>
                
                <!-- Preview -->
                <div class="preview-box">
                    <h3><i class="fas fa-eye"></i> Preview</h3>
                    <div class="preview-row">
                        <span>Total Bill:</span>
                        <strong>ETB <span id="previewTotal">120.00</span></strong>
                    </div>
                    <div class="preview-row">
                        <span>Split Method:</span>
                        <strong><span id="previewMethod">Equal</span></strong>
                    </div>
                                       <div class="preview-row">
                        <span>Each pays (avg):</span>
                        <strong>ETB <span id="previewEach">20.00</span></strong>
                    </div>
                    <div class="preview-row">
                        <span>Participants:</span>
                        <strong><span id="previewParticipants">6</span> users</strong>
                    </div>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i> Save Bill
                    </button>
                    <a href="dashboard.php" class="cancel-btn">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
    const roommateCount = <?php echo $roommate_count; ?>;
    let totalAmount = 120;
    
    function selectSplit(method) {
        document.getElementById('split_method').value = method;
        
        // Update UI
        document.querySelectorAll('.split-option').forEach(option => {
            option.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
        
        // Show/hide custom section
        const customSection = document.getElementById('customSplitSection');
        if (method === 'custom') {
            customSection.style.display = 'block';
            document.getElementById('previewMethod').textContent = 'Custom';
            calculateCustomSplit();
        } else {
            customSection.style.display = 'none';
            document.getElementById('previewMethod').textContent = 'Equal';
            calculateEqualSplit();
        }
    }
    
    function updateSplit() {
        totalAmount = parseFloat(document.getElementById('total_amount').value) || 120;
        
        if (document.getElementById('split_method').value === 'equal') {
            calculateEqualSplit();
        } else {
            calculateCustomSplit();
        }
    }
    
    function calculateEqualSplit() {
        const eachAmount = totalAmount / roommateCount;
        
        document.getElementById('previewTotal').textContent = totalAmount.toFixed(2);
        document.getElementById('previewEach').textContent = eachAmount.toFixed(2);
        document.getElementById('billTotal').textContent = totalAmount.toFixed(2);
        
        // Update all custom inputs
        document.querySelectorAll('.custom-amount').forEach(input => {
            input.value = eachAmount.toFixed(2);
            const hiddenInput = input.nextElementSibling;
            hiddenInput.value = eachAmount.toFixed(2);
        });
        document.getElementById('previewParticipants').textContent = participants;
    }
    
      function calculateCustomSplit() {
        document.getElementById('previewTotal').textContent = totalAmount.toFixed(2);
        document.getElementById('billTotal').textContent = totalAmount.toFixed(2);
        
        let allocated = 0;
        let participants = 0;
        
        document.querySelectorAll('.custom-amount').forEach(input => {
            const amount = parseFloat(input.value) || 0;
            allocated += amount;
            
            // Count participants (amount > 0)
            if (amount > 0.01) {
                participants++;
            }
            
            // Update UI to show excluded users
            const splitItem = input.closest('.split-item');
            if (amount < 0.01) {
                splitItem.style.opacity = '0.6';
                splitItem.style.background = '#f8f9fa';
                splitItem.querySelector('.roommate-name').innerHTML = 
                    splitItem.querySelector('.roommate-name').textContent + ' <span style="color:#e74c3c; font-size:12px;">(excluded)</span>';
            } else {
                splitItem.style.opacity = '1';
                splitItem.style.background = 'white';
                // Remove any existing "excluded" text
                const nameElement = splitItem.querySelector('.roommate-name');
                nameElement.innerHTML = nameElement.textContent.replace(/ \(excluded\)$/, '');
            }
        });

                document.getElementById('previewParticipants').textContent = participants;

        
        const remaining = totalAmount - allocated;
        document.getElementById('allocatedTotal').textContent = 'ETB ' + allocated.toFixed(2);
        
        // Update participants count
        document.getElementById('previewEach').textContent = participants > 0 ? 
            (allocated / participants).toFixed(2) : '0.00';
        
        const remainingInfo = document.getElementById('remainingInfo');
        if (remaining > 0.01) {
            remainingInfo.style.color = '#e74c3c';
            remainingInfo.innerHTML = 'Remaining: <span id="remainingAmount">ETB ' + remaining.toFixed(2) + '</span>';
        } else if (remaining < -0.01) {
            remainingInfo.style.color = '#e74c3c';
            remainingInfo.innerHTML = 'Over by: <span id="remainingAmount">ETB ' + Math.abs(remaining).toFixed(2) + '</span>';
        } else {
            remainingInfo.style.color = '#27ae60';
            remainingInfo.innerHTML = 'Balanced: <span id="remainingAmount">ETB 0.00</span> (<span style="color:#27ae60">' + participants + ' participants</span>)';
        }
        
        // Validate minimum participants
        if (participants < 2 && allocated > 0) {
            remainingInfo.innerHTML += '<br><span style="color:#e74c3c; font-size:12px;">Need at least 2 participants</span>';
        }
    }
    
    function updateCustomAmount(input) {
        const value = parseFloat(input.value) || 0;
        const hiddenInput = input.nextElementSibling;
        hiddenInput.value = value.toFixed(2);
        calculateCustomSplit();
    }
    
    function resetToEqual() {
        const eachAmount = totalAmount / roommateCount;
        document.querySelectorAll('.custom-amount').forEach(input => {
            input.value = eachAmount.toFixed(2);
            updateCustomAmount(input);
        });
    }
    
    // Form validation
   
    document.getElementById('billForm').addEventListener('submit', function(e) {
        if (document.getElementById('split_method').value === 'custom') {
            const remainingText = document.getElementById('remainingAmount').textContent;
            const remaining = parseFloat(remainingText.replace('ETB ', '')) || 0;
            
            // Count participants
            let participants = 0;
            let allocated = 0;
            document.querySelectorAll('.custom-amount').forEach(input => {
                const amount = parseFloat(input.value) || 0;
                allocated += amount;
                if (amount > 0.01) {
                    participants++;
                }
            });
            
            if (Math.abs(remaining) > 0.01) {
                e.preventDefault();
                alert('Please allocate the exact bill amount. Current difference: ETB ' + Math.abs(remaining).toFixed(2));
                return false;
            }
            
            if (participants < 2) {
                e.preventDefault();
                alert('Custom split requires at least 2 participants with positive amounts. Currently you have ' + participants + ' participant(s).');
                return false;
            }
            
            if (allocated <= 0) {
                e.preventDefault();
                alert('Total allocated amount must be greater than 0.');
                return false;
            }
        }
        return true;
    });
    
    // Initialize
    calculateEqualSplit();
    </script>
</body>
</html>