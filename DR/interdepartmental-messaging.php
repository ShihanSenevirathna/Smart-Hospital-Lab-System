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
    error_log("Error in interdepartmental-messaging.php: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interdepartmental Messaging | Smart Hospital Laboratory System</title>
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
        
        .messaging-container {
            display: flex;
            height: calc(100vh - 150px);
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .contacts-sidebar {
            width: 300px;
            border-right: 1px solid #eee;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .contacts-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .contacts-search {
            position: relative;
            margin-bottom: 15px;
        }
        
        .contacts-search input {
            width: 100%;
            padding: 10px 15px;
            padding-left: 40px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .contacts-search i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
        }
        
        .new-message-btn {
            width: 100%;
            padding: 10px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .new-message-btn:hover {
            background-color: #2980b9;
        }
        
        .new-message-btn i {
            margin-right: 8px;
        }
        
        .contacts-filter {
            display: flex;
            margin-top: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .filter-tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            color: #7f8c8d;
            cursor: pointer;
            transition: all 0.3s;
            border-bottom: 2px solid transparent;
        }
        
        .filter-tab.active {
            color: var(--secondary-color);
            border-bottom-color: var(--secondary-color);
        }
        
        .contacts-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .contact-item {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .contact-item:hover, .contact-item.active {
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .contact-avatar {
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
        
        .contact-avatar.department {
            background-color: rgba(52, 152, 219, 0.2);
            color: var(--secondary-color);
        }
        
        .contact-avatar i {
            font-size: 18px;
        }
        
        .contact-info {
            flex: 1;
            overflow: hidden;
        }
        
        .contact-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 3px;
            color: var(--dark-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .contact-preview {
            font-size: 12px;
            color: #7f8c8d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .contact-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            margin-left: 10px;
        }
        
        .message-time {
            font-size: 11px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .message-status {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            color: white;
            font-size: 10px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chat-contact {
            display: flex;
            align-items: center;
        }
        
        .chat-contact-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(52, 152, 219, 0.2);
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .chat-contact-avatar i {
            font-size: 18px;
        }
        
        .chat-contact-info {
            flex: 1;
        }
        
        .chat-contact-name {
            font-weight: 600;
            font-size: 16px;
            color: var(--dark-color);
        }
        
        .chat-contact-status {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .chat-actions {
            display: flex;
        }
        
        .chat-action {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .chat-action:hover {
            background-color: #eee;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .message {
            max-width: 70%;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .message.sent {
            align-self: flex-end;
        }
        
        .message.received {
            align-self: flex-start;
        }
        
        .message-bubble {
            padding: 12px 15px;
            border-radius: 18px;
            font-size: 14px;
            position: relative;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .sent .message-bubble {
            background-color: var(--secondary-color);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .received .message-bubble {
            background-color: #f0f2f5;
            color: var(--dark-color);
            border-bottom-left-radius: 4px;
        }
        
        .message-time-small {
            font-size: 11px;
            color: #7f8c8d;
            margin-top: 5px;
            align-self: flex-end;
        }
        
        .message-sender {
            font-size: 12px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 3px;
        }
        
        .chat-input-container {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .chat-input-actions {
            display: flex;
            margin-right: 15px;
        }
        
        .input-action {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
            color: #7f8c8d;
        }
        
        .input-action:hover {
            background-color: #f0f2f5;
            color: var(--secondary-color);
        }
        
        .chat-input {
            flex: 1;
            position: relative;
        }
        
        .chat-input textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 14px;
            resize: none;
            max-height: 120px;
            outline: none;
        }
        
        .chat-input textarea:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .send-button {
            margin-left: 15px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        
        .send-button:hover {
            background-color: #2980b9;
        }
        
        .empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #7f8c8d;
            text-align: center;
        }
        
        .empty-state i {
            font-size: 60px;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .new-chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            padding: 20px;
        }
        
        .new-chat-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 20px;
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
            min-height: 150px;
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
        }
        
        @media (max-width: 768px) {
            .search-box {
                display: none;
            }
            
            .contacts-sidebar {
                width: 250px;
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
            
            .contacts-sidebar {
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
                z-index: 10;
                transform: translateX(-100%);
                transition: all 0.3s;
            }
            
            .contacts-sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .mobile-chat-toggle {
                display: block;
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                background-color: var(--secondary-color);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
                z-index: 100;
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
                <a href="test-processing.php" class="nav-link">
                    <i class="fas fa-vial"></i>
                    <span class="nav-text">Test Processing</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="interdepartmental-messaging.php" class="nav-link active">
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
                <input type="text" placeholder="Search...">
            </div>
            
            <div class="user-actions">
                <div class="action-icon">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"><?php echo htmlspecialchars($notification_count); ?></span>
                </div>
                <div class="action-icon">
                    <i class="fas fa-envelope"></i>
                    <span class="notification-badge"><?php echo htmlspecialchars($message_count); ?></span>
                </div>
            </div>
        </div>
        
        <h1 class="page-title">Interdepartmental Messaging</h1>
        
        <div class="messaging-container">
            <!-- Contacts Sidebar -->
            <div class="contacts-sidebar">
                <div class="contacts-header">
                    <div class="contacts-search">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search contacts...">
                    </div>
                    <button class="new-message-btn">
                        <i class="fas fa-plus"></i> New Message
                    </button>
                    <div class="contacts-filter">
                        <div class="filter-tab active" data-filter="all">All</div>
                        <div class="filter-tab" data-filter="departments">Departments</div>
                        <div class="filter-tab" data-filter="staff">Staff</div>
                    </div>
                </div>
                
                <div class="contacts-list">
                    <!-- Department Contact -->
                    <div class="contact-item active">
                        <div class="contact-avatar department">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="contact-info">
                            <div class="contact-name">Emergency Department</div>
                            <div class="contact-preview">Dr. Chen: Could you expedite the CBC results for patient in bed 4?</div>
                        </div>
                        <div class="contact-meta">
                            <div class="message-time">10:45 AM</div>
                            <div class="message-status">2</div>
                        </div>
                    </div>
                    
                    <!-- Doctor Contact -->
                    <div class="contact-item">
                        <div class="contact-avatar">
                            <span>WB</span>
                        </div>
                        <div class="contact-info">
                            <div class="contact-name">Dr. William Brown</div>
                            <div class="contact-preview">When will the lipid panel results be ready for Mrs. Johnson?</div>
                        </div>
                        <div class="contact-meta">
                            <div class="message-time">9:30 AM</div>
                            <div class="message-status">1</div>
                        </div>
                    </div>
                    
                    <!-- Department Contact -->
                    <div class="contact-item">
                        <div class="contact-avatar department">
                            <i class="fas fa-procedures"></i>
                        </div>
                        <div class="contact-info">
                            <div class="contact-name">ICU</div>
                            <div class="contact-preview">You: The cardiac enzyme test results will be available by 3 PM.</div>
                        </div>
                        <div class="contact-meta">
                            <div class="message-time">Last Week</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Chat Container -->
            <div class="chat-container">
                <div class="chat-header">
                    <div class="chat-contact">
                        <div class="chat-contact-avatar">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="chat-contact-info">
                            <div class="chat-contact-name">Emergency Department</div>
                            <div class="chat-contact-status">5 members • Last active: 5 min ago</div>
                        </div>
                    </div>
                    <div class="chat-actions">
                        <div class="chat-action">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="chat-action">
                            <i class="fas fa-video"></i>
                        </div>
                        <div class="chat-action">
                            <i class="fas fa-info-circle"></i>
                        </div>
                    </div>
                </div>
                
                <div class="chat-messages">
                    <!-- Received message -->
                    <div class="message received">
                        <div class="message-sender">Dr. Michael Chen</div>
                        <div class="message-bubble">
                            Good morning, Lab team. We have an emergency case in the ER. Could you expedite the CBC and chemistry panel for patient James Wilson in bed 4?
                        </div>
                        <div class="message-time-small">9:30 AM</div>
                    </div>
                    
                    <!-- Sent message -->
                    <div class="message sent">
                        <div class="message-bubble">
                            Good morning, Dr. Chen. We'll prioritize that sample. What's the sample ID?
                        </div>
                        <div class="message-time-small">9:32 AM</div>
                    </div>
                    
                    <!-- Received message -->
                    <div class="message received">
                        <div class="message-sender">Nurse Rodriguez</div>
                        <div class="message-bubble">
                            The sample ID is SMP-2023-0477. It was collected at 9:15 AM and should be arriving at the lab shortly.
                        </div>
                        <div class="message-time-small">9:35 AM</div>
                    </div>
                    
                    <!-- Sent message -->
                    <div class="message sent">
                        <div class="message-bubble">
                            Thanks, Nurse Rodriguez. We've just received the sample and marked it as urgent. We'll start processing it immediately.
                        </div>
                        <div class="message-time-small">9:42 AM</div>
                    </div>
                    
                    <!-- Received message -->
                    <div class="message received">
                        <div class="message-sender">Dr. Michael Chen</div>
                        <div class="message-bubble">
                            Great, thank you. When do you think we can expect the results?
                        </div>
                        <div class="message-time-small">9:44 AM</div>
                    </div>
                    
                    <!-- Sent message -->
                    <div class="message sent">
                        <div class="message-bubble">
                            We'll have the CBC results within 30 minutes. The chemistry panel will take a bit longer, approximately 1 hour. I'll notify you as soon as they're ready.
                        </div>
                        <div class="message-time-small">9:45 AM</div>
                    </div>
                    
                    <!-- Received message -->
                    <div class="message received">
                        <div class="message-sender">Dr. Michael Chen</div>
                        <div class="message-bubble">
                            Perfect. Could you also expedite the CBC results for the patient in bed 4? The patient's condition is deteriorating.
                        </div>
                        <div class="message-time-small">10:45 AM</div>
                    </div>
                </div>
                
                <div class="chat-input-container">
                    <div class="chat-input-actions">
                        <div class="input-action">
                            <i class="fas fa-paperclip"></i>
                        </div>
                        <div class="input-action">
                            <i class="fas fa-image"></i>
                        </div>
                        <div class="input-action">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                    </div>
                    <div class="chat-input">
                        <textarea placeholder="Type a message..."></textarea>
                    </div>
                    <button class="send-button">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
            
            <!-- New Message Container (Hidden by default, would show when New Message is clicked) -->
            <div class="new-chat-container" style="display: none;">
                <h2 class="new-chat-title">Create New Message</h2>
                
                <form>
                    <div class="form-group">
                        <label for="recipient-type">Recipient Type</label>
                        <select id="recipient-type">
                            <option value="">Select Type</option>
                            <option value="department">Department</option>
                            <option value="individual">Individual Staff</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="recipient">Recipient</label>
                        <select id="recipient">
                            <option value="">Select Recipient</option>
                            <optgroup label="Departments">
                                <option value="emergency">Emergency Department</option>
                                <option value="cardiology">Cardiology</option>
                                <option value="pediatrics">Pediatrics</option>
                                <option value="icu">ICU</option>
                                <option value="surgery">Surgery</option>
                                <option value="radiology">Radiology</option>
                            </optgroup>
                            <optgroup label="Individual Staff">
                                <option value="dr-brown">Dr. William Brown</option>
                                <option value="dr-johnson">Dr. Sarah Johnson</option>
                                <option value="dr-patel">Dr. Raj Patel</option>
                                <option value="dr-chen">Dr. Michael Chen</option>
                                <option value="nurse-rodriguez">Nurse Rodriguez</option>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" placeholder="Enter message subject">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" placeholder="Type your message here..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority">
                            <option value="normal">Normal</option>
                            <option value="urgent">Urgent</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="attachments">Attachments</label>
                        <input type="file" id="attachments" multiple>
                    </div>
                    
                    <div class="action-buttons" style="margin-top: 20px;">
                        <button type="button" class="btn btn-outline" style="padding: 10px 20px; margin-right: 10px;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="padding: 10px 20px; background-color: #3498DB; color: white; border: none; border-radius: 5px;">Send Message</button>
                    </div>
                </form>
            </div>
            
            <!-- Empty State (would show when no conversation is selected) -->
            <div class="empty-state" style="display: none;">
                <i class="fas fa-comments"></i>
                <h3>No Conversation Selected</h3>
                <p>Select a conversation from the sidebar or start a new message.</p>
                <button class="new-message-btn" style="margin-top: 15px;">
                    <i class="fas fa-plus"></i> New Message
                </button>
            </div>
        </div>
    </div>
    
    <!-- Mobile Chat Toggle (visible only on mobile) -->
    <div class="mobile-chat-toggle" style="display: none;">
        <i class="fas fa-comments"></i>
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
        
        // Filter tabs
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.filter-tab').forEach(t => {
                    t.classList.remove('active');
                });
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Filter contacts based on data-filter attribute
                // This would be implemented with actual filtering logic
            });
        });
        
        // Contact item click
        document.querySelectorAll('.contact-item').forEach(item => {
            item.addEventListener('click', function() {
                // Remove active class from all contact items
                document.querySelectorAll('.contact-item').forEach(i => {
                    i.classList.remove('active');
                });
                
                // Add active class to clicked item
                this.classList.add('active');
                
                // Show chat container and hide empty state
                document.querySelector('.chat-container').style.display = 'flex';
                document.querySelector('.empty-state').style.display = 'none';
                document.querySelector('.new-chat-container').style.display = 'none';
                
                // On mobile, close contacts sidebar
                if (window.innerWidth <= 576) {
                    document.querySelector('.contacts-sidebar').classList.remove('mobile-open');
                }
            });
        });
        
        // New message button
        document.querySelectorAll('.new-message-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Show new message container, hide other containers
                document.querySelector('.new-chat-container').style.display = 'block';
                document.querySelector('.chat-container').style.display = 'none';
                document.querySelector('.empty-state').style.display = 'none';
                
                // On mobile, close contacts sidebar
                if (window.innerWidth <= 576) {
                    document.querySelector('.contacts-sidebar').classList.remove('mobile-open');
                }
            });
        });
        
        // Mobile chat toggle
        if (document.querySelector('.mobile-chat-toggle')) {
            document.querySelector('.mobile-chat-toggle').addEventListener('click', function() {
                document.querySelector('.contacts-sidebar').classList.toggle('mobile-open');
            });
        }
        
        // Mobile responsiveness
        function checkMobile() {
            if (window.innerWidth <= 576) {
                if (document.querySelector('.mobile-chat-toggle')) {
                    document.querySelector('.mobile-chat-toggle').style.display = 'flex';
                }
            } else {
                if (document.querySelector('.mobile-chat-toggle')) {
                    document.querySelector('.mobile-chat-toggle').style.display = 'none';
                }
                document.querySelector('.contacts-sidebar').classList.remove('mobile-open');
            }
        }
        
        // Check on load and resize
        window.addEventListener('load', checkMobile);
        window.addEventListener('resize', checkMobile);
    </script>
</body>
</html>