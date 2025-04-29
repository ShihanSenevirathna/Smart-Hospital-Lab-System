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
$notification_count = 0;
$message_count = 0;

try {
    // Fetch all samples with related information
    $sql = "SELECT tr.*, 
            p.first_name as patient_first_name, p.last_name as patient_last_name,
            d.first_name as doctor_first_name, d.last_name as doctor_last_name,
            u.full_name as technician_name
            FROM test_results tr
            LEFT JOIN patients p ON tr.patient_id = p.id
            LEFT JOIN doctors d ON tr.doctor_id = d.id
            LEFT JOIN users u ON tr.lab_staff_id = u.id
            ORDER BY tr.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch test types for dropdown
    $sql = "SELECT DISTINCT test_name FROM test_results WHERE test_name IS NOT NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $test_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get user details
    $sql = "SELECT * FROM users WHERE id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get unread notifications count
    $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $notification_count = $stmt->fetchColumn();

    // Get unread messages count
    $sql = "SELECT COUNT(*) FROM messages WHERE recipient_id = :user_id AND is_read = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $message_count = $stmt->fetchColumn();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Error in sample-tracking.php: " . $e->getMessage());
}

// Handle QR code generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_qr') {
    try {
        $sql = "INSERT INTO test_results (
            patient_id, doctor_id, test_name, order_date, status, priority, 
            sample_type, collection_date, notes, created_at
        ) VALUES (
            :patient_id, :doctor_id, :test_name, NOW(), 'pending', :priority,
            :sample_type, :collection_date, :notes, NOW()
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':patient_id' => $_POST['patient_id'],
            ':doctor_id' => $_POST['doctor_id'],
            ':test_name' => $_POST['test_type'],
            ':priority' => $_POST['priority'],
            ':sample_type' => $_POST['sample_type'] ?? null,
            ':collection_date' => $_POST['collection_date'],
            ':notes' => $_POST['sample_notes']
        ]);
        
        $success = "QR code generated successfully for sample ID: SMP-" . str_pad($pdo->lastInsertId(), 4, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        $error = "Failed to generate QR code: " . $e->getMessage();
        error_log("Error generating QR code: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Tracking | Smart Hospital Laboratory System</title>
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
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.3s;
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
        
        .tab-container {
            margin-bottom: 30px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 500;
            color: #7f8c8d;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .tab.active {
            color: var(--secondary-color);
            border-bottom-color: var(--secondary-color);
        }
        
        .tab-content {
            display: none;
            padding: 20px 0;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .scan-container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .qr-scanner {
            width: 300px;
            height: 300px;
            border: 2px dashed #ddd;
            border-radius: 10px;
            margin: 20px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .qr-scanner i {
            font-size: 60px;
            color: var(--secondary-color);
            margin-bottom: 20px;
        }
        
        .scan-message {
            color: #7f8c8d;
        }
        
        .qr-generator {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
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
        
        .qr-preview {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .qr-code {
            width: 200px;
            height: 200px;
            background-color: #f5f7fa;
            border-radius: 5px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qr-code img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .qr-details {
            width: 100%;
            margin-top: 20px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            flex: 1;
            font-weight: 500;
            color: var(--dark-color);
            text-align: left;
        }
        
        .detail-value {
            flex: 2;
            text-align: left;
            color: #7f8c8d;
        }
        
        .sample-list-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .filter-section {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
        }
        
        .filter-label {
            margin-right: 10px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .filter-select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .sample-list {
            width: 100%;
            border-collapse: collapse;
        }
        
        .sample-list th, .sample-list td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .sample-list th {
            font-weight: 600;
            color: var(--dark-color);
            background-color: #f8f9fa;
        }
        
        .sample-list tr:last-child td {
            border-bottom: none;
        }
        
        .sample-list tr:hover {
            background-color: #f8f9fa;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status.received {
            background-color: rgba(52, 152, 219, 0.2);
            color: var(--secondary-color);
        }
        
        .status.processing {
            background-color: rgba(243, 156, 18, 0.2);
            color: var(--warning-color);
        }
        
        .status.completed {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--accent-color);
        }
        
        .status.urgent {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
        }
        
        .action-buttons a {
            color: var(--secondary-color);
            margin-right: 10px;
            text-decoration: none;
        }
        
        .action-buttons a:hover {
            text-decoration: underline;
        }
        
        .stages-container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }
        
        .stages-title {
            font-size: 18px;
            color: var(--dark-color);
            margin-bottom: 20px;
        }
        
        .stages-timeline {
            position: relative;
            padding-left: 45px;
        }
        
        .timeline-line {
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #ddd;
        }
        
        .stage {
            position: relative;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }
        
        .stage:last-child {
            margin-bottom: 0;
        }
        
        .stage-marker {
            position: absolute;
            left: -45px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: white;
            border: 2px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }
        
        .stage-marker.completed {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
        }
        
        .stage-marker.current {
            background-color: white;
            border-color: var(--secondary-color);
            color: var(--secondary-color);
        }
        
        .stage-content {
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .stage:last-child .stage-content {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .stage-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .stage-title {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .stage-time {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .stage-description {
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .stage-details {
            font-size: 14px;
            color: var(--dark-color);
        }
        
        .stage-user {
            display: flex;
            align-items: center;
            margin-top: 15px;
        }
        
        .user-avatar-sm {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .user-name-sm {
            font-size: 14px;
            color: var(--dark-color);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        
        .pagination-item {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
            margin: 0 5px;
            border-radius: 5px;
            color: var(--dark-color);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .pagination-item:hover {
            background-color: #f8f9fa;
        }
        
        .pagination-item.active {
            background-color: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
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
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
        
        @media (max-width: 768px) {
            .search-box {
                display: none;
            }
            
            .qr-scanner, .qr-code {
                width: 250px;
                height: 250px;
            }
            
            .filter-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
                flex-wrap: wrap;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .tabs {
                overflow-x: auto;
                white-space: nowrap;
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
                <a href="sample-tracking.php" class="nav-link active">
                    <i class="fas fa-qrcode"></i>
                    <span class="nav-text">Sample Tracking</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="test-processing.php" class="nav-link">
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
                <a href="../index.php" class="nav-link">
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
                    <?php if ($notification_count > 0): ?>
                    <span class="notification-badge"><?php echo $notification_count; ?></span>
                    <?php endif; ?>
                </div>
                <div class="action-icon">
                    <i class="fas fa-envelope"></i>
                    <?php if ($message_count > 0): ?>
                    <span class="notification-badge"><?php echo $message_count; ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <h1 class="page-title">Sample Tracking</h1>
        
        <div class="action-buttons">
            <button class="btn btn-primary">
                <i class="fas fa-qrcode"></i> Generate New QR Code
            </button>
            <button class="btn btn-outline">
                <i class="fas fa-print"></i> Print QR Labels
            </button>
            <button class="btn btn-outline">
                <i class="fas fa-file-export"></i> Export Data
            </button>
        </div>
        
        <!-- Tabs Container -->
        <div class="tab-container">
            <div class="tabs">
                <div class="tab active" data-tab="scan">Scan QR Code</div>
                <div class="tab" data-tab="generate">Generate QR Code</div>
                <div class="tab" data-tab="all">All Samples</div>
            </div>
            
            <!-- Scan QR Tab -->
            <div class="tab-content active" id="scan-tab">
                <div class="scan-container">
                    <h2>Scan Sample QR Code</h2>
                    <p>Position the QR code within the scanner to track sample status</p>
                    
                    <div class="qr-scanner">
                        <i class="fas fa-camera"></i>
                        <p class="scan-message">Camera access required for scanning</p>
                        <button class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-camera"></i> Start Scanner
                        </button>
                    </div>
                    
                    <p>Or enter sample ID manually</p>
                    
                    <div class="form-group" style="margin-top: 20px; max-width: 400px;">
                        <input type="text" placeholder="Enter Sample ID (e.g., SMP-2023-0478)" style="padding: 10px; width: 100%;">
                        <button class="btn btn-primary" style="width: 100%; margin-top: 10px;">Track Sample</button>
                    </div>
                </div>
                
                <!-- Sample Tracking Details (would show after scanning or entering ID) -->
                <div class="stages-container">
                    <h2 class="stages-title">Sample: SMP-2023-0478 (Blood Chemistry)</h2>
                    
                    <div class="stages-timeline">
                        <div class="timeline-line"></div>
                        
                        <div class="stage">
                            <div class="stage-marker completed">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="stage-content">
                                <div class="stage-header">
                                    <div class="stage-title">Sample Collection</div>
                                    <div class="stage-time">Today, 10:15 AM</div>
                                </div>
                                <div class="stage-description">Blood sample collected from patient: Maria Garcia</div>
                                <div class="stage-details">
                                    <strong>Collection Method:</strong> Venipuncture<br>
                                    <strong>Amount:</strong> 10ml<br>
                                    <strong>Collection Site:</strong> Outpatient Department
                                </div>
                                <div class="stage-user">
                                    <div class="user-avatar-sm">RN</div>
                                    <div class="user-name-sm">Rebecca Nurse (Phlebotomist)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stage">
                            <div class="stage-marker completed">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="stage-content">
                                <div class="stage-header">
                                    <div class="stage-title">Sample Received at Lab</div>
                                    <div class="stage-time">Today, 10:45 AM</div>
                                </div>
                                <div class="stage-description">Sample received and logged into laboratory system</div>
                                <div class="stage-details">
                                    <strong>Receiving Technician:</strong> John Doe<br>
                                    <strong>Condition:</strong> Good<br>
                                    <strong>Storage:</strong> Placed in refrigerated storage unit R4
                                </div>
                                <div class="stage-user">
                                    <div class="user-avatar-sm">JD</div>
                                    <div class="user-name-sm">John Doe (Lab Technician)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stage">
                            <div class="stage-marker current">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="stage-content">
                                <div class="stage-header">
                                    <div class="stage-title">Sample Processing</div>
                                    <div class="stage-time">Started: Today, 11:30 AM</div>
                                </div>
                                <div class="stage-description">Sample being processed and analyzed</div>
                                <div class="stage-details">
                                    <strong>Processor:</strong> Sarah Johnson<br>
                                    <strong>Machine:</strong> Automated Blood Analyzer (ABA-3000)<br>
                                    <strong>Expected Completion:</strong> Today, 1:30 PM
                                </div>
                                <div class="stage-user">
                                    <div class="user-avatar-sm">SJ</div>
                                    <div class="user-name-sm">Sarah Johnson (Senior Lab Tech)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stage">
                            <div class="stage-marker">
                                <i class="fas fa-flask"></i>
                            </div>
                            <div class="stage-content">
                                <div class="stage-header">
                                    <div class="stage-title">Quality Check</div>
                                    <div class="stage-time">Pending</div>
                                </div>
                                <div class="stage-description">Results verification and quality assessment</div>
                            </div>
                        </div>
                        
                        <div class="stage">
                            <div class="stage-marker">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div class="stage-content">
                                <div class="stage-header">
                                    <div class="stage-title">Report Generation</div>
                                    <div class="stage-time">Pending</div>
                                </div>
                                <div class="stage-description">Final report creation and approval</div>
                            </div>
                        </div>
                        
                        <div class="stage">
                            <div class="stage-marker">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                            <div class="stage-content">
                                <div class="stage-header">
                                    <div class="stage-title">Results Delivery</div>
                                    <div class="stage-time">Pending</div>
                                </div>
                                <div class="stage-description">Results sent to doctor and patient portal</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Generate QR Tab -->
            <div class="tab-content" id="generate-tab">
                <div class="form-row">
                    <div class="form-col">
                        <div class="qr-generator">
                            <h2 style="margin-bottom: 20px;">Create New Sample QR Code</h2>
                            
                            <div class="form-group">
                                <label for="patient-id">Patient ID</label>
                                <input type="text" id="patient-id" placeholder="Enter or scan patient ID">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="patient-name">Patient Name</label>
                                        <input type="text" id="patient-name" placeholder="Patient full name">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="doctor-name">Requesting Doctor</label>
                                        <input type="text" id="doctor-name" placeholder="Doctor name">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="test-type">Test Type</label>
                                <select id="test-type">
                                    <option value="">Select Test Type</option>
                                    <option value="blood-chemistry">Blood Chemistry</option>
                                    <option value="cbc">Complete Blood Count (CBC)</option>
                                    <option value="lipid-profile">Lipid Profile</option>
                                    <option value="urinalysis">Urinalysis</option>
                                    <option value="blood-culture">Blood Culture</option>
                                    <option value="hba1c">HbA1c</option>
                                    <option value="other">Other (specify)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="sample-notes">Sample Notes</label>
                                <textarea id="sample-notes" placeholder="Any additional notes about the sample"></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="collection-date">Collection Date & Time</label>
                                        <input type="datetime-local" id="collection-date">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="priority">Priority</label>
                                        <select id="priority">
                                            <option value="normal">Normal</option>
                                            <option value="urgent">Urgent</option>
                                            <option value="stat">STAT (Emergency)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <button class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                                <i class="fas fa-qrcode"></i> Generate QR Code
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-col">
                        <div class="qr-preview">
                            <h2 style="margin-bottom: 20px;">QR Code Preview</h2>
                            
                            <div class="qr-code">
                                <img src="sample-qr.png" alt="Sample QR Code">
                            </div>
                            
                            <button class="btn btn-outline" style="width: 100%;">
                                <i class="fas fa-print"></i> Print QR Label
                            </button>
                            
                            <div class="qr-details">
                                <div class="detail-row">
                                    <div class="detail-label">Sample ID:</div>
                                    <div class="detail-value">SMP-2023-0479</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Patient:</div>
                                    <div class="detail-value">James Smith (PT-10045)</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Test Type:</div>
                                    <div class="detail-value">Complete Blood Count (CBC)</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Collection Date:</div>
                                    <div class="detail-value">Today, 11:30 AM</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Requested By:</div>
                                    <div class="detail-value">Dr. William Brown</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Priority:</div>
                                    <div class="detail-value">Urgent</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- All Samples Tab -->
            <div class="tab-content" id="all-tab">
                <div class="sample-list-container">
                    <div class="filter-section">
                        <div class="filter-group">
                            <span class="filter-label">Status:</span>
                            <select class="filter-select">
                                <option value="all">All Statuses</option>
                                <option value="received">Received</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
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
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <span class="filter-label">Date Range:</span>
                            <select class="filter-select">
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="week">Last 7 Days</option>
                                <option value="month">Last 30 Days</option>
                            </select>
                        </div>
                    </div>
                    
                    <table class="sample-list">
                        <thead>
                            <tr>
                                <th>Sample ID</th>
                                <th>Patient</th>
                                <th>Test Type</th>
                                <th>Collected</th>
                                <th>Current Stage</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($samples)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No samples found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($samples as $sample): ?>
                            <tr>
                                    <td>SMP-<?php echo str_pad($sample['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($sample['patient_first_name'] . ' ' . $sample['patient_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sample['test_name']); ?></td>
                                    <td><?php 
                                        $collection_date = $sample['collection_date'] ?? $sample['order_date'] ?? $sample['created_at'];
                                        echo $collection_date ? date('M j, Y, g:i A', strtotime($collection_date)) : 'Not specified';
                                    ?></td>
                                    <td><?php 
                                        switch($sample['status']) {
                                            case 'pending':
                                                echo 'Sample Received';
                                                break;
                                            case 'processing':
                                                echo 'Processing';
                                                break;
                                            case 'completed':
                                                echo 'Results Delivered';
                                                break;
                                            default:
                                                echo ucfirst($sample['status']);
                                        }
                                    ?></td>
                                    <td>
                                        <span class="status <?php 
                                            echo $sample['status'] === 'completed' ? 'completed' : 
                                                ($sample['status'] === 'processing' ? 'processing' : 
                                                (($sample['priority'] ?? 'normal') === 'urgent' || ($sample['priority'] ?? 'normal') === 'STAT' ? 'urgent' : 'received')); 
                                        ?>">
                                            <?php 
                                            echo $sample['status'] === 'completed' ? 'Completed' : 
                                                ($sample['status'] === 'processing' ? 'Processing' : 
                                                (($sample['priority'] ?? 'normal') === 'urgent' || ($sample['priority'] ?? 'normal') === 'STAT' ? ucfirst($sample['priority'] ?? 'normal') : 'Received')); 
                                            ?>
                                        </span>
                                </td>
                                <td class="action-buttons">
                                        <a href="#" onclick="viewSample(<?php echo $sample['id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if ($sample['status'] !== 'completed'): ?>
                                        <a href="#" onclick="updateSample(<?php echo $sample['id']; ?>)">
                                            <i class="fas fa-edit"></i> Update
                                        </a>
                                        <?php else: ?>
                                        <a href="#" onclick="viewReport(<?php echo $sample['id']; ?>)">
                                            <i class="fas fa-file-pdf"></i> Report
                                        </a>
                                        <?php endif; ?>
                                </td>
                            </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <div class="pagination">
                        <a href="#" class="pagination-item"><i class="fas fa-angle-left"></i></a>
                        <a href="#" class="pagination-item active">1</a>
                        <a href="#" class="pagination-item">2</a>
                        <a href="#" class="pagination-item">3</a>
                        <a href="#" class="pagination-item">4</a>
                        <a href="#" class="pagination-item">5</a>
                        <a href="#" class="pagination-item"><i class="fas fa-angle-right"></i></a>
                    </div>
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
        
        // Tab switching functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.tab').forEach(t => {
                    t.classList.remove('active');
                });
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all tab content
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // Show content for active tab
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId + '-tab').classList.add('active');
            });
        });
        
        // Add functions for sample actions
        function viewSample(sampleId) {
            // Implement sample viewing logic
            console.log('Viewing sample:', sampleId);
        }
        
        function updateSample(sampleId) {
            // Implement sample update logic
            console.log('Updating sample:', sampleId);
        }
        
        function viewReport(sampleId) {
            // Implement report viewing logic
            console.log('Viewing report for sample:', sampleId);
        }
    </script>
</body>
</html>