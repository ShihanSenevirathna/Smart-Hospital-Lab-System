<?php
session_start();
require_once 'config/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['patient_id'])) {
    header('Location: patient_login.php');
    exit;
}

// Initialize variables
$notifications = [];
$settings = [];
$channels = [];
$error = null;

try {
    $patient_id = $_SESSION['patient_id'];
    
    // Fetch notification settings
    $sql = "SELECT * FROM notification_settings WHERE patient_id = :patient_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings) {
        // If no settings exist, create default settings
        $sql = "INSERT INTO notification_settings (patient_id, email_notifications, sms_notifications, appointment_reminders, test_results_notifications, doctor_messages_notifications, medication_reminders, health_tips_notifications) 
                VALUES (:patient_id, 1, 1, 1, 1, 1, 0, 0)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':patient_id' => $patient_id]);
        
        // Fetch the newly created settings
        $sql = "SELECT * FROM notification_settings WHERE patient_id = :patient_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':patient_id' => $patient_id]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Fetch notification channels
    $sql = "SELECT * FROM notification_channels WHERE patient_id = :patient_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch notifications
    $sql = "SELECT * FROM notifications WHERE patient_id = :patient_id ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Failed to fetch data: " . $e->getMessage();
    error_log("Database error in notification_settings.php: " . $e->getMessage());
}

// Function to get channel value by type
function getChannelValue($channels, $type) {
    foreach ($channels as $channel) {
        if ($channel['channel_type'] === $type) {
            return $channel['channel_value'];
        }
    }
    return null;
}

// Function to check if a channel is active
function isChannelActive($channels, $type) {
    foreach ($channels as $channel) {
        if ($channel['channel_type'] === $type) {
            return $channel['is_active'];
        }
    }
    return false;
}

// Handle notification preferences update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_preferences'])) {
    try {
        // Here you would typically update notification preferences in the database
        // For now, we'll just show a success message
        $success_message = "Notification preferences updated successfully!";
    } catch(PDOException $e) {
        $error = "Failed to update preferences: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
    $appointment_reminders = isset($_POST['appointment_reminders']) ? 1 : 0;
    $test_results_notifications = isset($_POST['test_results']) ? 1 : 0;
    $doctor_messages_notifications = isset($_POST['doctor_messages']) ? 1 : 0;
    $medication_reminders = isset($_POST['medication']) ? 1 : 0;
    $health_tips_notifications = isset($_POST['health_tip']) ? 1 : 0;
    
    $sql = "UPDATE notification_settings SET 
            email_notifications = :email_notifications,
            sms_notifications = :sms_notifications,
            appointment_reminders = :appointment_reminders,
            test_results_notifications = :test_results_notifications,
            doctor_messages_notifications = :doctor_messages_notifications,
            medication_reminders = :medication_reminders,
            health_tips_notifications = :health_tips_notifications
            WHERE patient_id = :patient_id";
            
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([
        ':email_notifications' => $email_notifications,
        ':sms_notifications' => $sms_notifications,
        ':appointment_reminders' => $appointment_reminders,
        ':test_results_notifications' => $test_results_notifications,
        ':doctor_messages_notifications' => $doctor_messages_notifications,
        ':medication_reminders' => $medication_reminders,
        ':health_tips_notifications' => $health_tips_notifications,
        ':patient_id' => $patient_id
    ]);
    
    if ($success) {
        $message = "Notification settings updated successfully!";
        $message_type = "success";
        
        // Update the settings variable with new values
        $settings = [
            'email_notifications' => $email_notifications,
            'sms_notifications' => $sms_notifications,
            'appointment_reminders' => $appointment_reminders,
            'test_results_notifications' => $test_results_notifications,
            'doctor_messages_notifications' => $doctor_messages_notifications,
            'medication_reminders' => $medication_reminders,
            'health_tips_notifications' => $health_tips_notifications
        ];
    } else {
        $message = "Failed to update notification settings. Please try again.";
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Settings - Smart Hospital Laboratory System</title>
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
        }
        
        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ff5252;
            color: white;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
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
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: white;
            height: calc(100vh - 70px);
            position: fixed;
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
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Notification settings */
        .settings-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #1976d2;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        
        .notification-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .notification-option:last-child {
            border-bottom: none;
        }
        
        .notification-info {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.3rem;
        }
        
        .notification-description {
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Notification channels */
        .channels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .channel-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }
        
        .channel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .channel-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
        }
        
        .email-icon {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .sms-icon {
            background-color: #e8f5e9;
            color: #43a047;
        }
        
        .push-icon {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .channel-info {
            flex: 1;
        }
        
        .channel-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
        }
        
        .channel-status {
            display: inline-block;
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            margin-left: 0.5rem;
        }
        
        .status-active {
            background-color: #e8f5e9;
            color: #43a047;
        }
        
        .status-inactive {
            background-color: #ffebee;
            color: #e53935;
        }
        
        .channel-description {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }
        
        .channel-value {
            font-weight: 500;
            color: #333;
            margin-bottom: 1rem;
            word-break: break-all;
        }
        
        .channel-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }
        
        .channel-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            flex: 1;
            text-align: center;
        }
        
        .primary-btn {
            background-color: #1976d2;
            color: white;
        }
        
        .primary-btn:hover {
            background-color: #0d47a1;
        }
        
        .secondary-btn {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .secondary-btn:hover {
            background-color: #e0e0e0;
        }
        
        /* Notification history */
        .history-filter {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .filter-options {
            display: flex;
            gap: 0.8rem;
        }
        
        .filter-dropdown {
            padding: 0.6rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
        }
        
        .search-box {
            position: relative;
            width: 300px;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.6rem 1rem 0.6rem 2.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .search-box i {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .notifications-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .notifications-table th {
            text-align: left;
            padding: 1rem;
            background-color: #f5f7fa;
            color: #555;
            font-weight: 500;
            border-bottom: 1px solid #eee;
        }
        
        .notifications-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .notification-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-delivered {
            background-color: #e8f5e9;
            color: #43a047;
        }
        
        .status-pending {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .status-failed {
            background-color: #ffebee;
            color: #e53935;
        }
        
        .channel-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-email {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-sms {
            background-color: #e8f5e9;
            color: #43a047;
        }
        
        .badge-push {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .pagination {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        
        .page-info {
            margin-right: 1rem;
            color: #666;
        }
        
        .page-buttons {
            display: flex;
            gap: 0.3rem;
        }
        
        .page-btn {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
            border-radius: 3px;
            color: #555;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .page-btn:hover {
            border-color: #1976d2;
            color: #1976d2;
        }
        
        .page-btn.active {
            background-color: #1976d2;
            color: white;
            border-color: #1976d2;
        }
        
        .page-btn.disabled {
            color: #ccc;
            cursor: not-allowed;
        }
        
        /* Buttons */
        .save-btn {
            background-color: #1976d2;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .save-btn:hover {
            background-color: #0d47a1;
        }
        
        /* Modal for adding/editing channels */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1100;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.3rem;
            color: #333;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group input:focus, .form-group select:focus {
            border-color: #1976d2;
            outline: none;
        }
        
        .form-hint {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.3rem;
        }
        
        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        .modal-btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
        }
        
        .cancel-modal-btn {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .submit-modal-btn {
            background-color: #1976d2;
            color: white;
        }
        
        .submit-modal-btn:hover {
            background-color: #0d47a1;
        }
        
        /* Responsive menu toggle */
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
            
            .sidebar {
                transform: translateX(-100%);
                z-index: 990;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .content-area {
                margin-left: 0;
            }
            
            .channels-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .history-filter {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .search-box {
                width: 100%;
            }
            
            .filter-options {
                width: 100%;
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 0.5rem;
            }
            
            .notifications-table {
                display: block;
                overflow-x: auto;
            }
            
            .user-name {
                display: none;
            }
        }
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <span class="notification-count"><?php echo count($notifications); ?></span>
                    </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo substr($_SESSION['patient_name'] ?? 'P', 0, 2); ?>
                    </div>
                    <span class="user-name"><?php echo $_SESSION['patient_name'] ?? 'Patient'; ?></span>
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
                <a href="patient_dashboard.php" class="menu-item">
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
                <a href="notification_settings.php" class="menu-item active">
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
            <!-- Page Header -->
            <div class="page-header">
                <h2 class="page-title">Notification Settings</h2>
                <p class="page-subtitle">Manage how and when you receive notifications from the system</p>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Notification Channels -->
            <div class="settings-container">
                <h3 class="section-title">Notification Channels</h3>
                <div class="channels-grid">
                    <!-- Email Channel -->
                    <div class="channel-card">
                        <div class="channel-header">
                            <div style="display: flex; align-items: center;">
                                <div class="channel-icon email-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="channel-info">
                                    <div class="channel-title">
                                        Email 
                                        <span class="channel-status <?php echo isChannelActive($channels, 'email') ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo isChannelActive($channels, 'email') ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="channel-description">Receive notifications directly to your email inbox.</p>
                        <p class="channel-value"><?php echo getChannelValue($channels, 'email') ?: 'No email set'; ?></p>
                        <div class="channel-actions">
                            <button class="channel-btn primary-btn" id="edit-email-btn">Edit</button>
                            <button class="channel-btn secondary-btn" id="test-email-btn">Test</button>
                        </div>
                    </div>
                    
                    <!-- SMS Channel -->
                    <div class="channel-card">
                        <div class="channel-header">
                            <div style="display: flex; align-items: center;">
                                <div class="channel-icon sms-icon">
                                    <i class="fas fa-sms"></i>
                                </div>
                                <div class="channel-info">
                                    <div class="channel-title">
                                        SMS 
                                        <span class="channel-status <?php echo isChannelActive($channels, 'sms') ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo isChannelActive($channels, 'sms') ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="channel-description">Get text messages for important notifications.</p>
                        <p class="channel-value"><?php echo getChannelValue($channels, 'sms') ?: 'No phone number set'; ?></p>
                        <div class="channel-actions">
                            <button class="channel-btn primary-btn" id="edit-sms-btn">Edit</button>
                            <button class="channel-btn secondary-btn" id="test-sms-btn">Test</button>
                        </div>
                    </div>
                    
                    <!-- Push Notifications -->
                    <div class="channel-card">
                        <div class="channel-header">
                            <div style="display: flex; align-items: center;">
                                <div class="channel-icon push-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="channel-info">
                                    <div class="channel-title">
                                        Push Notifications
                                        <span class="channel-status <?php echo isChannelActive($channels, 'push') ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo isChannelActive($channels, 'push') ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="channel-description">Receive instant notifications on your mobile device.</p>
                        <p class="channel-value"><?php echo getChannelValue($channels, 'push') ?: 'No devices registered'; ?></p>
                        <div class="channel-actions">
                            <button class="channel-btn primary-btn" id="add-push-btn">Add Device</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Notification Preferences -->
            <div class="settings-container">
                <h3 class="section-title">Notification Preferences</h3>
                <div class="notification-preferences">
                    <div class="notification-option">
                        <div class="notification-info">
                            <div class="notification-title">Appointment Reminders</div>
                            <div class="notification-description">Receive notifications about upcoming appointments</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" data-type="appointment" <?php echo $settings['appointment_reminders'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="notification-option">
                        <div class="notification-info">
                            <div class="notification-title">Test Results</div>
                            <div class="notification-description">Get notified when new test results are available</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" data-type="test_result" <?php echo $settings['test_results_notifications'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="notification-option">
                        <div class="notification-info">
                            <div class="notification-title">Doctor Messages</div>
                            <div class="notification-description">Receive notifications when doctors send you messages</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" data-type="doctor_message" <?php echo $settings['doctor_messages_notifications'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="notification-option">
                        <div class="notification-info">
                            <div class="notification-title">Medication Reminders</div>
                            <div class="notification-description">Get reminded to take your medications</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" data-type="medication" <?php echo $settings['medication_reminders'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="notification-option">
                        <div class="notification-info">
                            <div class="notification-title">Health Tips & Updates</div>
                            <div class="notification-description">Receive health tips and hospital updates</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" data-type="health_tip" <?php echo $settings['health_tips_notifications'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                <button class="save-btn" style="margin-top: 1.5rem;">Save Preferences</button>
            </div>
            
            <!-- Notification History -->
            <div class="settings-container">
                <h3 class="section-title">Notification History</h3>
                <div class="history-filter">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search notifications...">
                    </div>
                </div>
                <table class="notifications-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($notifications)): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <tr>
                                    <td><?php echo date('M d, Y, h:i A', strtotime($notification['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($notification['title']); ?></td>
                                    <td><?php echo htmlspecialchars($notification['message']); ?></td>
                                    <td>
                                        <span class="notification-status <?php echo $notification['is_read'] ? 'status-delivered' : 'status-pending'; ?>">
                                            <?php echo $notification['is_read'] ? 'Read' : 'Unread'; ?>
                                        </span>
                                    </td>
                        </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-state">
                                    <i class="fas fa-bell"></i>
                                    <p>No notifications found</p>
                                </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <!-- Edit Email Modal -->
    <div class="modal" id="email-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Email Address</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="email-form">
                    <div class="form-group">
                        <label for="email-address">Email Address</label>
                        <input type="email" id="email-address" value="john.patient@email.com" required>
                        <p class="form-hint">We'll send notifications to this email address.</p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="modal-btn cancel-modal-btn">Cancel</button>
                <button class="modal-btn submit-modal-btn" id="save-email-btn">Save Changes</button>
            </div>
        </div>
    </div>
    
    <!-- Edit SMS Modal -->
    <div class="modal" id="sms-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Phone Number</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="sms-form">
                    <div class="form-group">
                        <label for="phone-number">Phone Number</label>
                        <input type="tel" id="phone-number" value="(555) 123-4567" required>
                        <p class="form-hint">We'll send SMS notifications to this phone number.</p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="modal-btn cancel-modal-btn">Cancel</button>
                <button class="modal-btn submit-modal-btn" id="save-sms-btn">Save Changes</button>
            </div>
        </div>
    </div>
    
    <!-- Add Push Device Modal -->
    <div class="modal" id="push-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Device for Push Notifications</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="push-form">
                    <div class="form-group">
                        <label for="device-name">Device Name</label>
                        <input type="text" id="device-name" placeholder="e.g., My iPhone" required>
                        <p class="form-hint">Give your device a recognizable name.</p>
                    </div>
                    <div class="form-group">
                        <label for="device-type">Device Type</label>
                        <select id="device-type" required>
                            <option value="">Select device type</option>
                            <option value="ios">iOS (iPhone/iPad)</option>
                            <option value="android">Android</option>
                            <option value="windows">Windows</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <p>To complete the setup, you will need to:</p>
                    <ol style="margin-left: 1.5rem; margin-top: 0.5rem;">
                        <li>Install our mobile app on your device</li>
                        <li>Sign in with your account</li>
                        <li>Allow notifications when prompted</li>
                    </ol>
                </form>
            </div>
            <div class="modal-footer">
                <button class="modal-btn cancel-modal-btn">Cancel</button>
                <button class="modal-btn submit-modal-btn" id="add-device-btn">Add Device</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // User dropdown toggle
            const userProfile = document.querySelector('.user-profile');
            const userDropdown = document.querySelector('.user-dropdown');
            const dropdownIcon = document.querySelector('.dropdown-icon');
            
            userProfile.addEventListener('click', function(event) {
                event.stopPropagation();
                userDropdown.classList.toggle('active');
                dropdownIcon.style.transform = userDropdown.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0)';
            });
            
            // Close dropdown when clicking elsewhere
            document.addEventListener('click', function() {
                userDropdown.classList.remove('active');
                dropdownIcon.style.transform = 'rotate(0)';
            });
            
            // Mobile sidebar toggle
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
            
            // Toggle switch functionality
            const toggleSwitches = document.querySelectorAll('.toggle-switch input');
            
            toggleSwitches.forEach(toggle => {
                toggle.addEventListener('change', function() {
                    const notificationTitle = this.closest('.notification-option').querySelector('.notification-title').textContent;
                    const isEnabled = this.checked;
                    
                    // Update the UI immediately
                    this.closest('.notification-option').querySelector('.notification-status').textContent = 
                        isEnabled ? 'Enabled' : 'Disabled';
                    
                    // Save to database
                    const preferences = {
                        appointment_reminders: document.querySelector('input[data-type="appointment"]').checked,
                        test_results_notifications: document.querySelector('input[data-type="test_result"]').checked,
                        doctor_messages_notifications: document.querySelector('input[data-type="doctor_message"]').checked,
                        medication_reminders: document.querySelector('input[data-type="medication"]').checked,
                        health_tips_notifications: document.querySelector('input[data-type="health_tip"]').checked
                    };

                    fetch('process/update_notification_settings.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            type: 'preferences',
                            value: preferences
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            // Revert the toggle if the update failed
                            this.checked = !isEnabled;
                            throw new Error(data.error || 'Failed to update preferences');
                        }
                    })
                    .catch(error => {
                        alert('Error: ' + error.message);
                    });
                });
            });
            
            // Edit Email Button
            const editEmailBtn = document.getElementById('edit-email-btn');
            const emailModal = document.getElementById('email-modal');
            
            editEmailBtn.addEventListener('click', function() {
                emailModal.classList.add('active');
            });

            // Edit SMS Button
            const editSmsBtn = document.getElementById('edit-sms-btn');
            const smsModal = document.getElementById('sms-modal');
            
            editSmsBtn.addEventListener('click', function() {
                smsModal.classList.add('active');
            });

            // Add Push Device Button
            const addPushBtn = document.getElementById('add-push-btn');
            const pushModal = document.getElementById('push-modal');
            
            addPushBtn.addEventListener('click', function() {
                pushModal.classList.add('active');
            });

            // Close Modal Buttons
            const closeModalBtns = document.querySelectorAll('.close-modal');
            const cancelModalBtns = document.querySelectorAll('.cancel-modal-btn');
            
            function closeModal(modal) {
                modal.classList.remove('active');
            }
            
            closeModalBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const modal = this.closest('.modal');
                    closeModal(modal);
                });
            });
            
            cancelModalBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const modal = this.closest('.modal');
                    closeModal(modal);
                });
            });
            
            // Close modal when clicking outside
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', function(event) {
                    if (event.target === this) {
                        closeModal(this);
                    }
                });
            });

            // Save Email Changes
            const saveEmailBtn = document.getElementById('save-email-btn');
            
            saveEmailBtn.addEventListener('click', function() {
                const emailAddress = document.getElementById('email-address').value;
                
                if (!emailAddress) {
                    alert('Please enter an email address.');
                    return;
                }

                // Send form data instead of JSON
                const formData = new FormData();
                formData.append('type', 'email');
                formData.append('value', emailAddress);

                fetch('process/update_notification_settings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error(text || 'Error updating email');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        // Update displayed email
                        const emailCard = document.querySelector('.email-icon').closest('.channel-card');
                        emailCard.querySelector('.channel-value').textContent = emailAddress;
                        emailCard.querySelector('.channel-status').textContent = 'Active';
                        emailCard.querySelector('.channel-status').className = 'channel-status status-active';
                        alert('Email address updated successfully!');
                        closeModal(emailModal);
                    } else {
                        throw new Error(data.error || 'Failed to update email');
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            });

            // Save SMS Changes
            const saveSmsBtn = document.getElementById('save-sms-btn');
            
            saveSmsBtn.addEventListener('click', function() {
                const phoneNumber = document.getElementById('phone-number').value;
                
                if (!phoneNumber) {
                    alert('Please enter a phone number.');
                    return;
                }

                // Send form data instead of JSON
                const formData = new FormData();
                formData.append('type', 'sms');
                formData.append('value', phoneNumber);

                fetch('process/update_notification_settings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error(text || 'Error updating phone number');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        // Update displayed phone number
                        const smsCard = document.querySelector('.sms-icon').closest('.channel-card');
                        smsCard.querySelector('.channel-value').textContent = phoneNumber;
                        smsCard.querySelector('.channel-status').textContent = 'Active';
                        smsCard.querySelector('.channel-status').className = 'channel-status status-active';
                        alert('Phone number updated successfully!');
                        closeModal(smsModal);
                    } else {
                        throw new Error(data.error || 'Failed to update phone number');
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            });

            // Add Push Device
            const addDeviceBtn = document.getElementById('add-device-btn');
            
            addDeviceBtn.addEventListener('click', function() {
                const deviceName = document.getElementById('device-name').value;
                const deviceType = document.getElementById('device-type').value;
                
                if (!deviceName || !deviceType) {
                    alert('Please fill in all required fields.');
                    return;
                }

                // Send form data instead of JSON
                const formData = new FormData();
                formData.append('type', 'push');
                formData.append('device_name', deviceName);
                formData.append('device_type', deviceType);

                fetch('process/update_notification_settings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error(text || 'Error adding device');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        // Update push notification card
                        const pushCard = document.querySelector('.push-icon').closest('.channel-card');
                        pushCard.querySelector('.channel-status').textContent = 'Pending';
                        pushCard.querySelector('.channel-status').className = 'channel-status status-pending';
                        pushCard.querySelector('.channel-value').textContent = `${deviceName} (Setup in progress)`;
                        
                        alert('Device added successfully! Please complete the setup on your device.');
                        closeModal(pushModal);
                    } else {
                        throw new Error(data.error || 'Failed to add device');
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            });

            // Test Notification Buttons
            const testEmailBtn = document.getElementById('test-email-btn');
            const testSmsBtn = document.getElementById('test-sms-btn');
            
            testEmailBtn.addEventListener('click', function() {
                const emailAddress = document.getElementById('email-address').value;
                if (!emailAddress) {
                    alert('Please set up your email address first.');
                    return;
                }

                // Send form data instead of JSON
                const formData = new FormData();
                formData.append('type', 'email');
                formData.append('value', emailAddress);

                fetch('process/send_test_notification.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error(text || 'Error sending test email');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        alert('Test email sent successfully!');
                    } else {
                        throw new Error(data.error || 'Failed to send test email');
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            });
            
            testSmsBtn.addEventListener('click', function() {
                const phoneNumber = document.getElementById('phone-number').value;
                if (!phoneNumber) {
                    alert('Please set up your phone number first.');
                    return;
                }

                // Send form data instead of JSON
                const formData = new FormData();
                formData.append('type', 'sms');
                formData.append('value', phoneNumber);

                fetch('process/send_test_notification.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error(text || 'Error sending test SMS');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        alert('Test SMS sent successfully!');
                    } else {
                        throw new Error(data.error || 'Failed to send test SMS');
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            });
            
            // Search and filter functionality
            const searchInput = document.querySelector('.search-box input');
            const tableRows = document.querySelectorAll('.notifications-table tbody tr');
            
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                
                tableRows.forEach(row => {
                    const subject = row.cells[1].textContent.toLowerCase();
                    
                    if (subject.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            // Filter dropdowns
            const filterDropdowns = document.querySelectorAll('.filter-dropdown');
            
            filterDropdowns.forEach(dropdown => {
                dropdown.addEventListener('change', function() {
                    // In a real application, you would implement filtering based on selected values
                    // For this example, we'll just log the values
                    console.log('Filter selected:', this.value);
                });
            });
            
            // Pagination
            const pageButtons = document.querySelectorAll('.page-btn');
            
            pageButtons.forEach(button => {
                if (!button.classList.contains('disabled')) {
                    button.addEventListener('click', function() {
                        // Remove active class from all page buttons
                        pageButtons.forEach(btn => {
                            btn.classList.remove('active');
                        });
                        
                        // Add active class to clicked button if it's a number
                        if (!this.innerHTML.includes('fas')) {
                            this.classList.add('active');
                        }
                        
                        // In a real application, this would load the corresponding page of results
                        // For this example, we'll just log the page number
                        console.log('Page selected:', this.textContent);
                    });
                }
            });
        });
    </script>
</body>
</html>