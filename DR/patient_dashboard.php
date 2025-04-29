<?php
session_start();
require_once 'config/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['patient_id'])) {
    header('Location: patient_login.php');
    exit;
}

// Fetch patient data
try {
    $patient_id = $_SESSION['patient_id'];
    
    // Fetch patient details
    $sql = "SELECT * FROM patients WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        // If patient not found, redirect to login
        header('Location: patient_login.php');
        exit;
    }
    
    // Fetch upcoming appointments
    $sql = "SELECT a.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name 
            FROM appointments a 
            LEFT JOIN doctors d ON a.doctor_id = d.id 
            WHERE a.patient_id = :patient_id 
            AND a.appointment_date >= CURDATE() 
            AND a.status = 'scheduled'
            ORDER BY a.appointment_date ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch recent test results
    $sql = "SELECT * FROM test_results 
            WHERE patient_id = :patient_id 
            ORDER BY test_date DESC 
            LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $recent_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch pending results count
    $sql = "SELECT COUNT(*) as count FROM test_results 
            WHERE patient_id = :patient_id 
            AND status = 'pending'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $pending_results_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Fetch unread notifications count
    $sql = "SELECT COUNT(*) as unread_count FROM notifications WHERE patient_id = :patient_id AND is_read = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $notification_count = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];

    // Fetch recent notifications with doctor information
    $sql = "SELECT n.*, d.full_name as doctor_name 
            FROM notifications n 
            LEFT JOIN users d ON n.doctor_id = d.id 
            WHERE n.patient_id = :patient_id 
            ORDER BY n.created_at DESC 
            LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $recent_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch health metrics for chart
    $sql = "SELECT * FROM health_metrics 
            WHERE patient_id = :patient_id 
            AND metric_type = 'blood_glucose'
            ORDER BY test_date DESC 
            LIMIT 7";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $health_metrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Smart Hospital Laboratory System</title>
    <style>
        /* Global styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        /* Header styling */
        header {
            background: linear-gradient(135deg, #1976d2, #0d47a1);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        
        .header-container {
            width: 95%;
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 40px;
            margin-right: 15px;
        }
        
        .logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
        }
        
        .notifications {
            position: relative;
            margin-right: 20px;
            cursor: pointer;
        }
        
        .notification-icon {
            font-size: 1.3rem;
            color: #666;
        }
        
        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #e53935;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            cursor: pointer;
            position: relative;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #bbdefb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: 500;
            color: #0d47a1;
            border: 2px solid #fff;
        }
        
        .user-name {
            font-weight: 500;
        }
        
        .dropdown-icon {
            margin-left: 5px;
            transition: transform 0.3s;
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            width: 200px;
            display: none;
            z-index: 100;
            margin-top: 10px;
        }
        
        .user-dropdown.active {
            display: block;
        }
        
        .dropdown-item {
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: background-color 0.3s;
        }
        
        .dropdown-item:hover {
            background-color: #f5f5f5;
        }
        
        .dropdown-item i {
            margin-right: 10px;
            color: #1976d2;
            width: 20px;
            text-align: center;
        }
        
        .dropdown-divider {
            height: 1px;
            background-color: #eee;
        }
        
        .logout-item {
            color: #e53935;
        }
        
        .logout-item i {
            color: #e53935;
        }
        
        /* Main container */
        .main-container {
            display: flex;
            margin-top: 70px;
            min-height: calc(100vh - 70px);
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: white;
            height: calc(100vh - 70px);
            position: fixed;
            left: 0;
            top: 70px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
            overflow-y: auto;
            transition: transform 0.3s;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .menu-item {
            padding: 0.8rem 1.5rem;
            color: #555;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: background-color 0.3s, color 0.3s;
            position: relative;
        }
        
        .menu-item:hover {
            background-color: #f5f7fa;
            color: #1976d2;
        }
        
        .menu-item.active {
            background-color: #e3f2fd;
            color: #1976d2;
            font-weight: 500;
        }
        
        .menu-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background-color: #1976d2;
        }
        
        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .menu-category {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #999;
            padding: 1.2rem 1.5rem 0.5rem;
            letter-spacing: 1px;
        }
        
        /* Content area */
        .content-area {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            min-height: calc(100vh - 70px);
        }
        
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .welcome-message {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .date-time {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Dashboard cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .dashboard-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.5rem;
        }
        
        .blue-card .card-icon {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .green-card .card-icon {
            background-color: #e8f5e9;
            color: #43a047;
        }
        
        .orange-card .card-icon {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .purple-card .card-icon {
            background-color: #f3e5f5;
            color: #8e24aa;
        }
        
        .card-content h3 {
            font-size: 1.8rem;
            margin-bottom: 0.2rem;
        }
        
        .card-content p {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Upcoming appointments section */
        .section-title {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .view-all {
            font-size: 0.9rem;
            color: #1976d2;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .view-all:hover {
            color: #0d47a1;
        }
        
        .appointment-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            padding: 1rem;
        }
        
        .appointment-date {
            width: 70px;
            height: 70px;
            background-color: #e3f2fd;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        
        .appointment-day {
            font-size: 1.5rem;
            font-weight: 500;
            color: #1976d2;
        }
        
        .appointment-month {
            font-size: 0.8rem;
            color: #1976d2;
        }
        
        .appointment-details {
            flex: 1;
        }
        
        .appointment-type {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.3rem;
        }
        
        .appointment-info {
            color: #666;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        
        .appointment-info i {
            margin-right: 5px;
            color: #1976d2;
            font-size: 0.8rem;
        }
        
        .appointment-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .appointment-button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        
        .reschedule-btn {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .reschedule-btn:hover {
            background-color: #bbdefb;
        }
        
        .cancel-btn {
            background-color: #ffebee;
            color: #e53935;
        }
        
        .cancel-btn:hover {
            background-color: #ffcdd2;
        }
        
        /* Recent tests & results section */
        .dashboard-columns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
        }
        
        .test-results-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        
        .test-results-table th {
            background-color: #f5f7fa;
            color: #333;
            text-align: left;
            padding: 1rem;
            font-weight: 500;
        }
        
        .test-results-table td {
            padding: 1rem;
            border-top: 1px solid #eee;
        }
        
        .test-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-completed {
            background-color: #e8f5e9;
            color: #43a047;
        }
        
        .status-pending {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .status-processing {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .action-button {
            color: #1976d2;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .action-button:hover {
            color: #0d47a1;
        }
        
        /* Health metrics section */
        .metrics-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
        }
        
        .metrics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .metrics-title {
            font-size: 1.2rem;
            color: #333;
        }
        
        .metrics-selector {
            display: flex;
            gap: 0.5rem;
        }
        
        .metrics-selector button {
            padding: 0.4rem 0.8rem;
            border: 1px solid #ddd;
            background-color: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .metrics-selector button.active {
            background-color: #1976d2;
            color: white;
            border-color: #1976d2;
        }
        
        .chart-container {
            height: 300px;
        }
        
        /* Responsive sidebar */
        .menu-toggle {
            display: none;
            cursor: pointer;
            font-size: 1.5rem;
        }
        
        /* Responsive design */
        @media (max-width: 992px) {
            .menu-toggle {
                display: block;
            }
            
            .content-area {
                margin-left: 0;
            }
            
            .sidebar {
                transform: translateX(-100%);
                z-index: 990;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-columns {
                grid-template-columns: 1fr;
            }
            
            .header-container {
                padding: 0 1rem;
            }
            
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .logo h1 {
                font-size: 1.2rem;
            }
            
            .user-name {
                display: none;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.show {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background-color: white;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
        }

        .close-modal {
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .appointment-details-modal {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .appointment-details-modal p {
            margin: 5px 0;
            color: #555;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .modal-button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .cancel-confirm {
            background-color: #e53935;
            color: white;
        }

        .cancel-confirm:hover {
            background-color: #c62828;
        }

        .cancel-dismiss {
            background-color: #f5f5f5;
            color: #333;
        }

        .cancel-dismiss:hover {
            background-color: #e0e0e0;
        }

        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 350px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: none;
            z-index: 1000;
            margin-top: 10px;
        }

        .notification-dropdown.show {
            display: block;
        }

        .notification-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-header h4 {
            margin: 0;
            font-size: 1rem;
            color: #333;
        }

        .mark-all-read {
            font-size: 0.8rem;
            color: #1976d2;
            text-decoration: none;
            transition: color 0.3s;
        }

        .mark-all-read:hover {
            color: #0d47a1;
        }

        .notification-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
            transition: background-color 0.3s;
        }

        .notification-item.unread {
            background-color: #f8f9fa;
        }

        .notification-item:hover {
            background-color: #f5f5f5;
        }

        .notification-item .notification-icon {
            margin-right: 10px;
            color: #1976d2;
        }

        .notification-content {
            flex: 1;
        }

        .notification-content h5 {
            margin: 0 0 5px 0;
            font-size: 0.9rem;
            color: #333;
        }

        .notification-content p {
            margin: 0 0 5px 0;
            font-size: 0.85rem;
            color: #666;
        }

        .notification-content small {
            display: block;
            font-size: 0.75rem;
            color: #999;
        }

        .notification-time {
            margin-top: 5px;
        }

        .no-notifications {
            padding: 20px;
            text-align: center;
            color: #999;
        }

        .notification-footer {
            padding: 10px 15px;
            border-top: 1px solid #eee;
            text-align: center;
        }

        .notification-footer a {
            color: #1976d2;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .notification-footer a:hover {
            color: #0d47a1;
        }
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js for health metrics charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="header-container">
            <div class="logo">
                <i class="menu-toggle fas fa-bars"></i>
                <img src="images/lab-logo.png" alt="SHLS Logo">
                <h1>Smart Hospital Laboratory System</h1>
            </div>
            <div class="user-menu">
                <div class="notifications">
                    <i class="notification-icon fas fa-bell"></i>
                    <?php if ($notification_count > 0): ?>
                        <span class="notification-count"><?php echo $notification_count; ?></span>
                    <?php endif; ?>
                    <div class="notification-dropdown">
                        <div class="notification-header">
                            <h4>Notifications</h4>
                            <?php if ($notification_count > 0): ?>
                                <a href="patient_markasread.php" class="mark-all-read">Mark all as read</a>
                            <?php endif; ?>
                        </div>
                        <div class="notification-list">
                            <?php if (!empty($recent_notifications)): ?>
                                <?php foreach ($recent_notifications as $notification): ?>
                                    <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                                        <div class="notification-icon">
                                            <i class="fas fa-bell"></i>
                                        </div>
                                        <div class="notification-content">
                                            <h5><?php echo htmlspecialchars($notification['title']); ?></h5>
                                            <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <?php if ($notification['doctor_name']): ?>
                                                <small>From: Dr. <?php echo htmlspecialchars($notification['doctor_name']); ?></small>
                                            <?php endif; ?>
                                            <small class="notification-time"><?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-notifications">
                                    <p>No notifications</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="notification-footer">
                            <a href="notifications.php">View All Notifications</a>
                        </div>
                    </div>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1); ?>
                    </div>
                    <span class="user-name"><?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></span>
                    <i class="dropdown-icon fas fa-chevron-down"></i>
                    <div class="user-dropdown">
                        <a href="patient_profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a href="notification_settings.php" class="dropdown-item">
                            <i class="fas fa-bell"></i> Notifications
                        </a>
                        <a href="settings.php" class="dropdown-item">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="help.php" class="dropdown-item">
                            <i class="fas fa-question-circle"></i> Help & Support
                        </a>
                        <a href="logout.php" class="dropdown-item logout-item">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container with Sidebar and Content Area -->
    <div class="main-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <nav class="sidebar-menu">
                <a href="patient_dashboard.php" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="test_appointments.php" class="menu-item">
                    <i class="fas fa-calendar-check"></i> Appointments
                </a>
                <a href="test_results.php" class="menu-item">
                    <i class="fas fa-file-medical-alt"></i> Test Results
                </a>
                <a href="patient_profile.php" class="menu-item">
                    <i class="fas fa-user"></i> My Profile
                </a>
                
                <div class="menu-category">Health Management</div>
                <a href="health_records.php" class="menu-item">
                    <i class="fas fa-heartbeat"></i> Health Records
                </a>
                <a href="medication_tracker.php" class="menu-item">
                    <i class="fas fa-pills"></i> Medications
                </a>
                <a href="health_insights.php" class="menu-item">
                    <i class="fas fa-chart-line"></i> Health Insights
                </a>
                
                <div class="menu-category">Communications</div>
                <a href="messages.php" class="menu-item">
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="notification_settings.php" class="menu-item">
                    <i class="fas fa-bell"></i> Notifications
                </a>
                
                <div class="menu-category">Support</div>
                <a href="faq.php" class="menu-item">
                    <i class="fas fa-question-circle"></i> FAQ
                </a>
                <a href="support.php" class="menu-item">
                    <i class="fas fa-headset"></i> Support
                </a>
            </nav>
        </aside>

        <!-- Content Area -->
        <main class="content-area">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h2 class="welcome-message">Welcome back, <?php echo $patient['first_name']; ?>!</h2>
                <p class="date-time"><?php echo date('l, F j, Y'); ?> | Last login: <?php echo date('l, F j, Y, H:i', strtotime($patient['last_login'] ?? 'now')); ?></p>
            </div>
            
            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="dashboard-card blue-card">
                    <div class="card-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo count($upcoming_appointments); ?></h3>
                        <p>Upcoming Appointments</p>
                    </div>
                </div>
                <div class="dashboard-card green-card">
                    <div class="card-icon">
                        <i class="fas fa-file-medical-alt"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo count(array_filter($recent_tests, function($test) { return $test['status'] === 'completed'; })); ?></h3>
                        <p>Completed Tests</p>
                    </div>
                </div>
                <div class="dashboard-card orange-card">
                    <div class="card-icon">
                        <i class="fas fa-flask"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $pending_results_count; ?></h3>
                        <p>Pending Results</p>
                    </div>
                </div>
                <div class="dashboard-card purple-card">
                    <div class="card-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo $notification_count; ?></h3>
                        <p>New Notifications</p>
                    </div>
                </div>
            </div>
            
            <!-- Upcoming Appointments Section -->
            <div class="upcoming-appointments">
                <div class="section-title">
                    <h3>Upcoming Appointments</h3>
                    <a href="test_appointments.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <?php if (!empty($upcoming_appointments)): ?>
                    <?php foreach ($upcoming_appointments as $appointment): ?>
                        <div class="appointment-card" data-appointment-id="<?php echo $appointment['id']; ?>">
                            <div class="appointment-date">
                                <div class="appointment-day"><?php echo date('d', strtotime($appointment['appointment_date'])); ?></div>
                                <div class="appointment-month"><?php echo date('M', strtotime($appointment['appointment_date'])); ?></div>
                            </div>
                            <div class="appointment-details">
                                <div class="appointment-type"><?php echo htmlspecialchars($appointment['test_type']); ?></div>
                                <div class="appointment-info">
                                    <i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?> |
                                    <i class="fas fa-user-md ml-3"></i> Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?> |
                                    <i class="fas fa-map-marker-alt ml-3"></i> <?php echo htmlspecialchars($appointment['lab_room']); ?>
                                </div>
                            </div>
                            <div class="appointment-actions">
                                <button class="appointment-button reschedule-btn" onclick="rescheduleAppointment(<?php echo $appointment['id']; ?>)">Reschedule</button>
                                <button class="appointment-button cancel-btn" onclick="cancelAppointment(<?php echo $appointment['id']; ?>)">Cancel</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>No upcoming appointments</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent Tests & Health Metrics Columns -->
            <div class="dashboard-columns">
                <!-- Recent Tests Results -->
                <div class="recent-tests">
                    <div class="section-title">
                        <h3>Recent Test Results</h3>
                        <a href="test_results.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <?php if (!empty($recent_tests)): ?>
                        <table class="test-results-table">
                            <thead>
                                <tr>
                                    <th>Test Name</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_tests as $test): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($test['test_date'])); ?></td>
                                        <td>
                                            <span class="test-status status-<?php echo $test['status']; ?>">
                                                <?php echo ucfirst($test['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($test['status'] === 'completed'): ?>
                                                <a href="view_test_result.php?id=<?php echo $test['id']; ?>" class="action-button">View</a>
                                            <?php else: ?>
                                                <a href="#" class="action-button">Track</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-medical-alt"></i>
                            <p>No test results available</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Health Metrics Chart -->
                <div class="health-metrics">
                    <div class="section-title">
                        <h3>Health Metrics</h3>
                        <a href="health_insights.php" class="view-all">View Details <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <div class="metrics-container">
                        <div class="metrics-header">
                            <h4 class="metrics-title">Blood Glucose Levels</h4>
                            <div class="metrics-selector">
                                <button class="time-period" data-period="week">Week</button>
                                <button class="time-period active" data-period="month">Month</button>
                                <button class="time-period" data-period="year">Year</button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="glucoseChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Cancel Appointment Modal -->
    <div id="cancelAppointmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cancel Appointment</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this appointment?</p>
                <div class="appointment-details-modal">
                    <p><strong>Test Type:</strong> <span id="modalTestType"></span></p>
                    <p><strong>Date:</strong> <span id="modalAppointmentDate"></span></p>
                    <p><strong>Time:</strong> <span id="modalAppointmentTime"></span></p>
                    <p><strong>Doctor:</strong> <span id="modalDoctorName"></span></p>
                </div>
                <div class="modal-actions">
                    <button class="modal-button cancel-confirm">Yes, Cancel Appointment</button>
                    <button class="modal-button cancel-dismiss">No, Keep Appointment</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // User dropdown toggle
            const userProfile = document.querySelector('.user-profile');
            const userDropdown = document.querySelector('.user-dropdown');
            const dropdownIcon = document.querySelector('.dropdown-icon');
            
            userProfile.addEventListener('click', function() {
                userDropdown.classList.toggle('active');
                dropdownIcon.style.transform = userDropdown.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0)';
            });
            
            // Close dropdown when clicking elsewhere
            document.addEventListener('click', function(event) {
                if (!userProfile.contains(event.target)) {
                    userDropdown.classList.remove('active');
                    dropdownIcon.style.transform = 'rotate(0)';
                }
            });
            
            // Mobile sidebar toggle
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
            
            // Blood glucose chart
            const ctx = document.getElementById('glucoseChart').getContext('2d');
            const glucoseChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_map(function($metric) { 
                        return date('M d', strtotime($metric['test_date'])); 
                    }, array_reverse($health_metrics))); ?>,
                    datasets: [{
                        label: 'Blood Glucose (mg/dL)',
                        data: <?php echo json_encode(array_map(function($metric) { 
                            return $metric['result_value']; 
                        }, array_reverse($health_metrics))); ?>,
                        fill: false,
                        borderColor: '#1976d2',
                        tension: 0.1,
                        pointBackgroundColor: '#1976d2',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 70,
                            max: 130,
                            grid: {
                                color: '#f5f5f5'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            
            // Time period selection
            const timePeriodButtons = document.querySelectorAll('.time-period');
            
            timePeriodButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    timePeriodButtons.forEach(btn => {
                        btn.classList.remove('active');
                    });
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Update chart data based on selected time period
                    // This would be connected to a real API in a production app
                    const period = this.getAttribute('data-period');
                    
                    if (period === 'week') {
                        glucoseChart.data.labels = ['Mar 26', 'Mar 27', 'Mar 28', 'Mar 29', 'Mar 30', 'Mar 31', 'Apr 1'];
                        glucoseChart.data.datasets[0].data = [92, 94, 96, 90, 95, 100, 95];
                    } else if (period === 'month') {
                        glucoseChart.data.labels = ['Mar 1', 'Mar 5', 'Mar 10', 'Mar 15', 'Mar 20', 'Mar 25', 'Apr 1'];
                        glucoseChart.data.datasets[0].data = [95, 105, 92, 98, 90, 102, 95];
                    } else if (period === 'year') {
                        glucoseChart.data.labels = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'];
                        glucoseChart.data.datasets[0].data = [100, 102, 98, 95, 97, 103, 101, 99, 96, 94, 98, 95];
                    }
                    
                    glucoseChart.update();
                });
            });
            
            // Hide sidebar when clicking on a menu item (mobile view)
            const menuItems = document.querySelectorAll('.menu-item');
            
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth <= 992) {
                        sidebar.classList.remove('active');
                    }
                });
            });

            // Notification dropdown toggle
            const notificationIcon = document.querySelector('.notifications');
            const notificationDropdown = document.querySelector('.notification-dropdown');
            
            notificationIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!notificationIcon.contains(e.target)) {
                    notificationDropdown.classList.remove('show');
                }
            });

            // Show success/error messages if they exist
            <?php if (isset($_SESSION['success_message'])): ?>
                alert('<?php echo $_SESSION['success_message']; ?>');
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                alert('<?php echo $_SESSION['error_message']; ?>');
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
        });
        
        // Appointment management functions
        function rescheduleAppointment(appointmentId) {
            // Redirect to appointments page with reschedule parameter
            window.location.href = `test_appointments.php?reschedule=${appointmentId}`;
        }
        
        // Modal functionality
        const modal = document.getElementById('cancelAppointmentModal');
        const closeModal = document.querySelector('.close-modal');
        const cancelDismissBtn = document.querySelector('.cancel-dismiss');
        let currentAppointmentId = null;

        function showCancelModal(appointmentId, appointmentData) {
            currentAppointmentId = appointmentId;
            
            // Populate modal with appointment details
            document.getElementById('modalTestType').textContent = appointmentData.testType;
            document.getElementById('modalAppointmentDate').textContent = appointmentData.date;
            document.getElementById('modalAppointmentTime').textContent = appointmentData.time;
            document.getElementById('modalDoctorName').textContent = appointmentData.doctor;
            
            modal.classList.add('show');
        }

        function hideCancelModal() {
            modal.classList.remove('show');
            currentAppointmentId = null;
        }

        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                hideCancelModal();
            }
        });

        // Close modal when clicking close button
        closeModal.addEventListener('click', hideCancelModal);
        cancelDismissBtn.addEventListener('click', hideCancelModal);

        // Update the cancelAppointment function
        function cancelAppointment(appointmentId) {
            // Get appointment details from the card
            const appointmentCard = document.querySelector(`[data-appointment-id="${appointmentId}"]`);
            const appointmentData = {
                testType: appointmentCard.querySelector('.appointment-type').textContent,
                date: appointmentCard.querySelector('.appointment-day').textContent + ' ' + 
                      appointmentCard.querySelector('.appointment-month').textContent,
                time: appointmentCard.querySelector('.appointment-info').textContent.split('|')[0].trim(),
                doctor: appointmentCard.querySelector('.appointment-info').textContent.split('|')[1].trim()
            };
            
            showCancelModal(appointmentId, appointmentData);
        }

        // Handle confirm cancellation
        document.querySelector('.cancel-confirm').addEventListener('click', function() {
            if (currentAppointmentId) {
                fetch('test_appointments.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'cancel',
                        appointment_id: currentAppointmentId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        hideCancelModal();
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to cancel appointment');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while canceling the appointment');
                });
            }
        });
    </script>
</body>
</html>