<?php
session_start();
require_once 'config/db_connection.php';

// Check if user is logged in and is lab staff
if (!isset($_SESSION['is_lab_staff']) || $_SESSION['is_lab_staff'] !== true) {
    header('Location: lab-staff-login.php');
    exit;
}

// Initialize variables
$error = null;
$success = null;

// Get user details
try {
    $sql = "SELECT * FROM users WHERE id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $error = "User not found. Please log in again.";
        session_destroy();
        header('Location: lab-staff-login.php');
        exit;
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Error in test-processing.php: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'start_processing':
                    // Update test status to processing
                    $sql = "UPDATE test_results SET 
                            status = 'processing',
                            lab_staff_id = :staff_id,
                            updated_at = CURRENT_TIMESTAMP
                            WHERE id = :test_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':staff_id' => $_SESSION['user_id'],
                        ':test_id' => $_POST['test_id']
                    ]);
                    $success = "Test processing started successfully.";
                    break;

                case 'save_results':
                    // Validate and save test results
                    $results = [
                        'wbc' => $_POST['wbc'],
                        'rbc' => $_POST['rbc'],
                        'hgb' => $_POST['hgb'],
                        'hct' => $_POST['hct'],
                        'mcv' => $_POST['mcv'],
                        'platelets' => $_POST['platelets'],
                        'comments' => $_POST['additional-findings'],
                        'tested_by' => $_SESSION['staff_name'],
                        'verified_by' => $_POST['verified-by'],
                        'test_date' => $_POST['test-date']
                    ];

                    // Update test results
                    $sql = "UPDATE test_results SET 
                            results = :results,
                            status = 'completed',
                            test_date = :test_date,
                            updated_at = CURRENT_TIMESTAMP
                            WHERE id = :test_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':results' => json_encode($results),
                        ':test_date' => $_POST['test-date'],
                        ':test_id' => $_POST['test_id']
                    ]);
                    $success = "Test results saved successfully.";
                    break;
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        error_log("Error in test-processing.php: " . $e->getMessage());
    }
}

// Fetch pending samples
try {
    $sql = "SELECT tr.*, p.first_name, p.last_name, d.first_name as doctor_first_name, d.last_name as doctor_last_name
            FROM test_results tr
            LEFT JOIN patients p ON tr.patient_id = p.id
            LEFT JOIN doctors d ON tr.doctor_id = d.id
            WHERE tr.status = 'pending'
            ORDER BY tr.order_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $pending_samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch processing samples
    $sql = "SELECT tr.*, p.first_name, p.last_name, d.first_name as doctor_first_name, d.last_name as doctor_last_name,
            u.full_name as technician_name
            FROM test_results tr
            LEFT JOIN patients p ON tr.patient_id = p.id
            LEFT JOIN doctors d ON tr.doctor_id = d.id
            LEFT JOIN users u ON tr.lab_staff_id = u.id
            WHERE tr.status = 'processing'
            ORDER BY tr.updated_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $processing_samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Failed to fetch samples: " . $e->getMessage();
    error_log("Error in test-processing.php: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Processing | Smart Hospital Laboratory System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2C3E50;
            --secondary-color: #3498DB;
            --accent-color: #2ECC71;
            --warning-color: #F39C12;
            --danger-color: #E74C3C;
            --light-color: #ECF0F1;
            --dark-color: #34495E;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: white;
            padding-top: 20px;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 100;
        }
        
        .sidebar-collapsed {
            width: 70px;
        }
        
        .logo-container {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
        }
        
        .logo-container img {
            height: 40px;
            margin-right: 10px;
        }
        
        .logo-text {
            font-size: 18px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
        }
        
        .user-info {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .user-details {
            white-space: nowrap;
            overflow: hidden;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 15px;
        }
        
        .user-role {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .nav-menu {
            list-style: none;
            padding: 20px 0;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .nav-link i {
            font-size: 18px;
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }
        
        .nav-text {
            white-space: nowrap;
            overflow: hidden;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .main-collapsed {
            margin-left: 70px;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .menu-toggle {
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 24px;
            cursor: pointer;
        }
        
        .search-box {
            flex: 1;
            max-width: 300px;
            margin: 0 20px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 15px;
            padding-left: 40px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
        }
        
        .user-actions {
            display: flex;
            align-items: center;
        }
        
        .action-icon {
            font-size: 18px;
            color: var(--dark-color);
            margin-left: 20px;
            cursor: pointer;
            position: relative;
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--danger-color);
            color: white;
            font-size: 10px;
            font-weight: bold;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .page-title {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .sample-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .sample-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .sample-header {
            padding: 15px;
            background-color: var(--light-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sample-id {
            font-weight: 600;
            font-size: 16px;
            color: var(--dark-color);
        }
        
        .sample-priority {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .priority-normal {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--accent-color);
        }
        
        .priority-urgent {
            background-color: rgba(243, 156, 18, 0.2);
            color: var(--warning-color);
        }
        
        .priority-stat {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
        }
        
        .sample-body {
            padding: 15px;
        }
        
        .sample-info {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .sample-info-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-label {
            flex: 1;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .info-value {
            flex: 2;
            color: #7f8c8d;
        }
        
        .sample-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            flex: 1;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--secondary-color);
            border: 1px solid var(--secondary-color);
        }
        
        .btn-outline:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .filter-row {
            display: flex;
            margin-bottom: 20px;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
        }
        
        .filter-label {
            margin-right: 10px;
            font-weight: 500;
            color: var(--dark-color);
            white-space: nowrap;
        }
        
        .filter-select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            min-width: 150px;
        }
        
        .processing-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .processing-header {
            padding: 20px;
            background-color: var(--light-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .processing-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .processing-body {
            padding: 20px;
        }
        
        .test-info-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 5px;
            display: block;
        }
        
        .info-value {
            color: #7f8c8d;
        }
        
        .test-result-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 15px;
        }
        
        .result-form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-col {
            flex: 1;
        }
        
        .reference-range {
            color: #7f8c8d;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .result-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .result-table th, .result-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .result-table th {
            font-weight: 600;
            color: var(--dark-color);
            background-color: #f8f9fa;
        }
        
        .result-table tr:last-child td {
            border-bottom: none;
        }
        
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }
        
        .no-results-message {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-badge.received {
            background-color: rgba(52, 152, 219, 0.2);
            color: var(--secondary-color);
        }
        
        .status-badge.processing {
            background-color: rgba(243, 156, 18, 0.2);
            color: var(--warning-color);
        }
        
        .status-badge.completed {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--accent-color);
        }
        
        .status-badge.pending {
            background-color: rgba(149, 165, 166, 0.2);
            color: #7f8c8d;
        }
        
        .status-badge.abnormal {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            
            .logo-text, .user-details, .nav-text {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .info-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
        
        @media (max-width: 768px) {
            .search-box {
                display: none;
            }
            
            .sample-grid {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-select {
                flex: 1;
            }
        }
        
        @media (max-width: 576px) {
            .sidebar {
                width: 0;
            }
            
            .sidebar.sidebar-mobile-open {
                width: 250px;
            }
            
            .sidebar.sidebar-mobile-open .logo-text, 
            .sidebar.sidebar-mobile-open .user-details, 
            .sidebar.sidebar-mobile-open .nav-text {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-container">
            <img src="logo-placeholder.png" alt="SHLS Logo">
            <div class="logo-text">SHLS</div>
        </div>
        
        <div class="user-info">
            <div class="user-avatar">
                <?php 
                $initials = strtoupper(substr($user['full_name'], 0, 1) . 
                           (strpos($user['full_name'], ' ') !== false ? substr(strrchr($user['full_name'], ' '), 1, 1) : ''));
                echo htmlspecialchars($initials);
                ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div class="user-role"><?php echo ucwords(str_replace('_', ' ', $user['role'])); ?></div>
            </div>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="lab-dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="sample-tracking.php" class="nav-link">
                    <i class="fas fa-qrcode"></i>
                    <span class="nav-text">Sample Tracking</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="test-processing.php" class="nav-link active">
                    <i class="fas fa-vial"></i>
                    <span class="nav-text">Test Processing</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="interdepartmental-messaging.php" class="nav-link">
                    <i class="fas fa-comment-medical"></i>
                    <span class="nav-text">Messaging</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span class="nav-text">Settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../index.html" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <button class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search samples...">
            </div>
            
            <div class="user-actions">
                <div class="action-icon">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="action-icon">
                    <i class="fas fa-envelope"></i>
                    <span class="notification-badge">5</span>
                </div>
            </div>
        </div>
        
        <h1 class="page-title">Test Processing</h1>
        
        <!-- Filter Section -->
        <div class="filter-row">
            <div class="filter-group">
                <span class="filter-label">Filter:</span>
                <select class="filter-select">
                    <option value="all">All Samples</option>
                    <option value="pending">Pending Processing</option>
                    <option value="processing">Currently Processing</option>
                    <option value="completed">Completed Tests</option>
                </select>
            </div>
            
            <div class="filter-group">
                <span class="filter-label">Test Type:</span>
                <select class="filter-select">
                    <option value="all">All Types</option>
                    <option value="blood-chemistry">Blood Chemistry</option>
                    <option value="cbc">CBC</option>
                    <option value="lipid-profile">Lipid Profile</option>
                    <option value="urinalysis">Urinalysis</option>
                    <option value="blood-culture">Blood Culture</option>
                </select>
            </div>
            
            <div class="filter-group">
                <span class="filter-label">Priority:</span>
                <select class="filter-select">
                    <option value="all">All Priorities</option>
                    <option value="stat">STAT (Emergency)</option>
                    <option value="urgent">Urgent</option>
                    <option value="normal">Normal</option>
                </select>
            </div>
            
            <div class="filter-group">
                <span class="filter-label">Date:</span>
                <select class="filter-select">
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="week">Last 7 Days</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
        </div>
        
        <!-- Samples Pending Processing -->
        <h2 class="section-title">Pending Processing (<?php echo count($pending_samples); ?>)</h2>
        <div class="sample-grid">
            <?php if (empty($pending_samples)): ?>
                <div class="no-results-message">
                    <i class="fas fa-info-circle"></i>
                    <p>No pending samples found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pending_samples as $sample): ?>
        <div class="sample-card">
            <div class="sample-header">
                            <div class="sample-id">SMP-<?php echo str_pad($sample['id'], 4, '0', STR_PAD_LEFT); ?></div>
                            <span class="sample-priority <?php 
                                echo isset($sample['priority']) ? 
                                    ($sample['priority'] === 'STAT' ? 'priority-stat' : 
                                    ($sample['priority'] === 'urgent' ? 'priority-urgent' : 'priority-normal')) : 
                                    'priority-normal'; 
                            ?>">
                                <?php echo isset($sample['priority']) ? ucfirst($sample['priority']) : 'Normal'; ?>
                            </span>
            </div>
            <div class="sample-body">
                <div class="sample-info">
                    <div class="sample-info-row">
                        <div class="info-label">Patient:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($sample['first_name'] . ' ' . $sample['last_name']); ?></div>
                    </div>
                    <div class="sample-info-row">
                        <div class="info-label">Test Type:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($sample['test_name']); ?></div>
                    </div>
                    <div class="sample-info-row">
                        <div class="info-label">Received:</div>
                                    <div class="info-value"><?php echo date('M j, Y, g:i A', strtotime($sample['order_date'])); ?></div>
                    </div>
                    <div class="sample-info-row">
                        <div class="info-label">Doctor:</div>
                                    <div class="info-value">Dr. <?php echo htmlspecialchars($sample['doctor_first_name'] . ' ' . $sample['doctor_last_name']); ?></div>
                    </div>
                    <div class="sample-info-row">
                        <div class="info-label">Status:</div>
                        <div class="info-value"><span class="status-badge received">Received</span></div>
                    </div>
                </div>
                <div class="sample-actions">
                                <form method="POST" style="display: flex; gap: 10px; width: 100%;">
                                    <input type="hidden" name="action" value="start_processing">
                                    <input type="hidden" name="test_id" value="<?php echo $sample['id']; ?>">
                                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-flask"></i> Process
                    </button>
                                    <button type="button" class="btn btn-outline" onclick="showTestDetails(<?php echo $sample['id']; ?>)">
                        <i class="fas fa-info-circle"></i> Details
                    </button>
                                </form>
                </div>
            </div>
        </div>
                <?php endforeach; ?>
            <?php endif; ?>
    </div>
    
    <!-- Currently Processing -->
        <h2 class="section-title">Currently Processing (<?php echo count($processing_samples); ?>)</h2>
    <div class="sample-grid">
            <?php if (empty($processing_samples)): ?>
                <div class="no-results-message">
                    <i class="fas fa-info-circle"></i>
                    <p>No samples currently being processed.</p>
                </div>
            <?php else: ?>
                <?php foreach ($processing_samples as $sample): ?>
        <div class="sample-card">
            <div class="sample-header">
                            <div class="sample-id">SMP-<?php echo str_pad($sample['id'], 4, '0', STR_PAD_LEFT); ?></div>
                            <span class="sample-priority <?php 
                                echo isset($sample['priority']) ? 
                                    ($sample['priority'] === 'STAT' ? 'priority-stat' : 
                                    ($sample['priority'] === 'urgent' ? 'priority-urgent' : 'priority-normal')) : 
                                    'priority-normal'; 
                            ?>">
                                <?php echo isset($sample['priority']) ? ucfirst($sample['priority']) : 'Normal'; ?>
                            </span>
            </div>
            <div class="sample-body">
                <div class="sample-info">
                    <div class="sample-info-row">
                        <div class="info-label">Patient:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($sample['first_name'] . ' ' . $sample['last_name']); ?></div>
                    </div>
                    <div class="sample-info-row">
                        <div class="info-label">Test Type:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($sample['test_name']); ?></div>
                    </div>
                    <div class="sample-info-row">
                        <div class="info-label">Processing:</div>
                                    <div class="info-value">Started <?php echo date('M j, Y, g:i A', strtotime($sample['updated_at'])); ?></div>
                    </div>
                    <div class="sample-info-row">
                        <div class="info-label">Technician:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($sample['technician_name']); ?></div>
                    </div>
                    <div class="sample-info-row">
                        <div class="info-label">Status:</div>
                        <div class="info-value"><span class="status-badge processing">Processing</span></div>
                    </div>
                </div>
                <div class="sample-actions">
                                <button class="btn btn-primary" onclick="showTestResultForm(<?php echo $sample['id']; ?>)">
                        <i class="fas fa-edit"></i> Edit Results
                    </button>
                                <button class="btn btn-outline" onclick="completeTest(<?php echo $sample['id']; ?>)">
                        <i class="fas fa-check-circle"></i> Complete
                    </button>
                </div>
            </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                    </div>
        <?php endif; ?>

        <!-- Test Processing Form -->
        <div class="processing-container" id="processing-form" style="display: none;">
        <div class="processing-header">
                <h2 class="processing-title">Process Sample: <span id="sample-id"></span></h2>
            <span class="status-badge processing">Currently Processing</span>
        </div>
        
        <div class="processing-body">
            <!-- Sample Information Section -->
            <div class="test-info-section">
                <h3 class="section-title">Sample Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Patient Name:</span>
                            <span class="info-value" id="patient-name"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Patient ID:</span>
                            <span class="info-value" id="patient-id"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Sample Type:</span>
                            <span class="info-value" id="sample-type"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Collection Date/Time:</span>
                            <span class="info-value" id="collection-time"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Received Date/Time:</span>
                            <span class="info-value" id="received-time"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Requested By:</span>
                            <span class="info-value" id="requested-by"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Test Type:</span>
                            <span class="info-value" id="test-type"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Priority:</span>
                            <span class="info-value" id="priority"></span>
                    </div>
                </div>
            </div>
            
            <!-- Test Results Section -->
            <div class="test-result-section">
                <h3 class="section-title">Test Results Input</h3>
                    <p>Enter the test results below. Fields marked with * are required.</p>
                
                    <form class="result-form" method="POST" id="result-form">
                        <input type="hidden" name="action" value="save_results">
                        <input type="hidden" name="test_id" id="test-id">
                        
                    <table class="result-table">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Result</th>
                                <th>Units</th>
                                <th>Reference Range</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>White Blood Cell (WBC) Count*</td>
                                    <td><input type="text" name="wbc" required placeholder="Enter value"></td>
                                <td>× 10^9/L</td>
                                <td>4.5-11.0</td>
                                <td>
                                        <select name="wbc_status" required>
                                        <option value="">Select</option>
                                        <option value="normal">Normal</option>
                                        <option value="low">Below Normal</option>
                                        <option value="high">Above Normal</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Red Blood Cell (RBC) Count*</td>
                                    <td><input type="text" name="rbc" required placeholder="Enter value"></td>
                                <td>× 10^12/L</td>
                                <td>4.5-5.9 (M), 4.0-5.2 (F)</td>
                                <td>
                                        <select name="rbc_status" required>
                                        <option value="">Select</option>
                                        <option value="normal">Normal</option>
                                        <option value="low">Below Normal</option>
                                        <option value="high">Above Normal</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Hemoglobin (Hgb)*</td>
                                    <td><input type="text" name="hgb" required placeholder="Enter value"></td>
                                <td>g/dL</td>
                                <td>13.5-17.5 (M), 12.0-16.0 (F)</td>
                                <td>
                                        <select name="hgb_status" required>
                                        <option value="">Select</option>
                                        <option value="normal">Normal</option>
                                        <option value="low">Below Normal</option>
                                        <option value="high">Above Normal</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Hematocrit (Hct)*</td>
                                    <td><input type="text" name="hct" required placeholder="Enter value"></td>
                                <td>%</td>
                                <td>41-53 (M), 36-46 (F)</td>
                                <td>
                                        <select name="hct_status" required>
                                        <option value="">Select</option>
                                        <option value="normal">Normal</option>
                                        <option value="low">Below Normal</option>
                                        <option value="high">Above Normal</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Mean Corpuscular Volume (MCV)</td>
                                <td><input type="text" name="mcv" placeholder="Enter value"></td>
                                <td>fL</td>
                                <td>80-100</td>
                                <td>
                                        <select name="mcv_status">
                                        <option value="">Select</option>
                                        <option value="normal">Normal</option>
                                        <option value="low">Below Normal</option>
                                        <option value="high">Above Normal</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Platelet Count*</td>
                                    <td><input type="text" name="platelets" required placeholder="Enter value"></td>
                                <td>× 10^9/L</td>
                                <td>150-450</td>
                                <td>
                                        <select name="platelets_status" required>
                                        <option value="">Select</option>
                                        <option value="normal">Normal</option>
                                        <option value="low">Below Normal</option>
                                        <option value="high">Above Normal</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="form-group">
                        <label for="additional-findings">Additional Findings/Comments</label>
                            <textarea id="additional-findings" name="additional-findings" placeholder="Enter any additional observations or comments about the test results"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="tested-by">Tested By*</label>
                                    <input type="text" id="tested-by" value="<?php echo htmlspecialchars($_SESSION['staff_name']); ?>" readonly>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="verified-by">Verified By</label>
                                    <select id="verified-by" name="verified-by">
                                    <option value="">Select Verifier</option>
                                        <?php
                                        // Fetch verifiers from database
                                        try {
                                            $sql = "SELECT id, full_name FROM users WHERE role = 'lab_supervisor' OR role = 'lab_manager'";
                                            $stmt = $pdo->prepare($sql);
                                            $stmt->execute();
                                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<option value="' . htmlspecialchars($row['id']) . '">' . 
                                                     htmlspecialchars($row['full_name']) . '</option>';
                                            }
                                        } catch (PDOException $e) {
                                            error_log("Error fetching verifiers: " . $e->getMessage());
                                        }
                                        ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                            <label for="test-date">Test Completed Date/Time*</label>
                            <input type="datetime-local" id="test-date" name="test-date" required>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="button" class="btn btn-outline" onclick="saveDraft()">Save Draft</button>
                            <button type="button" class="btn btn-outline" onclick="previewReport()">Preview Report</button>
                            <button type="submit" class="btn btn-primary">Complete & Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle sidebar on menu button click
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('sidebar-collapsed');
            document.querySelector('.main-content').classList.toggle('main-collapsed');
        });
        
        // Mobile sidebar toggle
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            if (window.innerWidth <= 576) {
                document.querySelector('.sidebar').classList.toggle('sidebar-mobile-open');
            }
        });

        // Function to show test details
        function showTestDetails(testId) {
            // Fetch test details from server and show in a modal
            console.log('Showing details for test:', testId);
        }

        // Function to show test result form
        function showTestResultForm(testId) {
            // Fetch test details and populate the form
            fetch('get_test_details.php?test_id=' + testId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('processing-form').style.display = 'block';
                    document.getElementById('sample-id').textContent = 'SMP-' + String(data.id).padStart(4, '0');
                    document.getElementById('test-id').value = data.id;
                    document.getElementById('patient-name').textContent = data.first_name + ' ' + data.last_name;
                    document.getElementById('patient-id').textContent = 'PT-' + String(data.patient_id).padStart(5, '0');
                    document.getElementById('sample-type').textContent = data.sample_type || 'Blood Sample';
                    document.getElementById('collection-time').textContent = new Date(data.collection_date).toLocaleString();
                    document.getElementById('received-time').textContent = new Date(data.order_date).toLocaleString();
                    document.getElementById('requested-by').textContent = 'Dr. ' + data.doctor_first_name + ' ' + data.doctor_last_name;
                    document.getElementById('test-type').textContent = data.test_name;
                    document.getElementById('priority').textContent = data.priority || 'Normal';
                    
                    // Scroll to form
                    document.getElementById('processing-form').scrollIntoView({ behavior: 'smooth' });
                })
                .catch(error => {
                    console.error('Error fetching test details:', error);
                    alert('Failed to load test details. Please try again.');
                });
        }

        // Function to complete test
        function completeTest(testId) {
            if (confirm('Are you sure you want to mark this test as complete?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="complete_test">
                    <input type="hidden" name="test_id" value="${testId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Function to save draft
        function saveDraft() {
            // Implement draft saving functionality
            alert('Draft saved successfully');
        }

        // Function to preview report
        function previewReport() {
            // Implement report preview functionality
            alert('Report preview functionality will be implemented soon');
        }

        // Set default test date to current date and time
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('test-date').value = now.toISOString().slice(0, 16);
        });
    </script>
</body>
</html> 