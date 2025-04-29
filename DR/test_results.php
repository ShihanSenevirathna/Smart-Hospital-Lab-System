<?php
session_start();
require_once 'config/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['patient_id'])) {
    header('Location: patient_login.php');
    exit;
}

// Initialize variables
$recent_results = [];
$all_results = [];
$error = null;

// Fetch patient's test results
try {
    $patient_id = $_SESSION['patient_id'];
    
    // Fetch recent test results (last 3)
    $sql = "SELECT tr.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name 
            FROM test_results tr 
            LEFT JOIN doctors d ON tr.doctor_id = d.id 
            WHERE tr.patient_id = :patient_id 
            ORDER BY tr.test_date DESC 
            LIMIT 3";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $recent_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch all test results
    $sql = "SELECT tr.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name 
            FROM test_results tr 
            LEFT JOIN doctors d ON tr.doctor_id = d.id 
            WHERE tr.patient_id = :patient_id 
            ORDER BY tr.test_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $all_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Failed to fetch test results: " . $e->getMessage();
    // Log the error for debugging
    error_log("Database error in test_results.php: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Results - Smart Hospital Laboratory System</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 1.5rem;
            color: #333;
        }
        
        .page-actions {
            display: flex;
            gap: 1rem;
        }
        
        .action-btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 5px;
            background-color: #1976d2;
            color: white;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .action-btn:hover {
            background-color: #0d47a1;
        }
        
        /* Results section */
        .test-results-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .search-filter {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .search-box {
            flex: 1;
            max-width: 300px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.6rem 1rem 0.6rem 2.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .search-box input:focus {
            border-color: #1976d2;
            outline: none;
        }
        
        .search-box i {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .filter-options {
            display: flex;
            gap: 0.8rem;
            align-items: center;
        }
        
        .filter-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .filter-dropdown {
            padding: 0.6rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
        }
        
        .filter-dropdown:focus {
            border-color: #1976d2;
            outline: none;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .results-table th {
            text-align: left;
            padding: 1rem;
            background-color: #f5f7fa;
            color: #555;
            font-weight: 500;
            border-bottom: 1px solid #eee;
        }
        
        .results-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
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
        
        .status-cancelled {
            background-color: #ffebee;
            color: #e53935;
        }
        
        .result-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .result-btn {
            padding: 0.3rem 0.8rem;
            border: none;
            border-radius: 5px;
            font-size: 0.8rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .view-btn {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .view-btn:hover {
            background-color: #bbdefb;
        }
        
        .download-btn {
            background-color: #e8f5e9;
            color: #43a047;
        }
        
        .download-btn:hover {
            background-color: #c8e6c9;
        }
        
        .print-btn {
            background-color: #f5f5f5;
            color: #555;
        }
        
        .print-btn:hover {
            background-color: #e0e0e0;
        }
        
        .share-btn {
            background-color: #e8f4fd;
            color: #039be5;
        }
        
        .share-btn:hover {
            background-color: #b3e0ff;
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
        
        /* Recent tests section */
        .recent-tests {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .result-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        .result-card {
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            padding: 1rem;
            background-color: #f5f7fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .test-info {
            display: flex;
            flex-direction: column;
        }
        
        .test-name {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.3rem;
        }
        
        .test-date {
            font-size: 0.8rem;
            color: #666;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        .result-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .result-item:last-child {
            border-bottom: none;
        }
        
        .result-label {
            color: #666;
        }
        
        .result-value {
            font-weight: 500;
        }
        
        .result-normal {
            color: #43a047;
        }
        
        .result-high {
            color: #e53935;
        }
        
        .result-low {
            color: #f57c00;
        }
        
        .card-footer {
            padding: 1rem;
            background-color: #f9f9f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .doctor-info {
            font-size: 0.8rem;
            color: #666;
        }
        
        .card-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        /* Modal for sharing test results */
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
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #1976d2;
            outline: none;
        }
        
        .share-options {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .share-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            cursor: pointer;
            padding: 1rem;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .share-option:hover {
            background-color: #f5f7fa;
        }
        
        .share-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.8rem;
            font-size: 1.5rem;
        }
        
        .email-icon {
            background-color: #f5f5f5;
            color: #555;
        }
        
        .whatsapp-icon {
            background-color: #e8f5e9;
            color: #43a047;
        }
        
        .doctor-icon {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .share-label {
            font-size: 0.9rem;
            color: #555;
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
        }
        
        @media (max-width: 768px) {
            .search-filter {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .search-box {
                max-width: 100%;
                width: 100%;
            }
            
            .filter-options {
                width: 100%;
                flex-wrap: wrap;
            }
            
            .result-cards {
                grid-template-columns: 1fr;
            }
            
            .results-table {
                display: block;
                overflow-x: auto;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .page-actions {
                width: 100%;
            }
            
            .action-btn {
                flex: 1;
                justify-content: center;
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
                    <span class="notification-count">3</span>
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
                <a href="test_results.php" class="menu-item active">
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
            <!-- Page Header -->
            <div class="page-header">
                <h2 class="page-title">Test Results</h2>
                <div class="page-actions">
                    <button class="action-btn">
                        <i class="fas fa-download"></i> Export All
                    </button>
                    <button class="action-btn">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
            
            <!-- Recent Test Results -->
            <div class="recent-tests">
                <h3 class="section-title">Recent Test Results</h3>
                <div class="result-cards">
                    <?php if (!empty($recent_results)): ?>
                        <?php foreach ($recent_results as $result): ?>
                    <div class="result-card">
                        <div class="card-header">
                            <div class="test-info">
                                        <div class="test-name"><?php echo htmlspecialchars($result['test_name']); ?></div>
                                        <div class="test-date"><?php echo date('M d, Y', strtotime($result['test_date'])); ?></div>
                            </div>
                                    <span class="test-status status-<?php echo strtolower($result['status']); ?>"><?php echo ucfirst($result['status']); ?></span>
                        </div>
                        <div class="card-body">
                                    <?php 
                                    $parameters = isset($result['parameters']) ? json_decode($result['parameters'], true) : [];
                                    if (!empty($parameters)): 
                                        foreach ($parameters as $param): 
                                    ?>
                            <div class="result-item">
                                            <div class="result-label"><?php echo htmlspecialchars($param['name'] ?? ''); ?></div>
                                            <div class="result-value result-<?php echo $param['status'] ?? 'normal'; ?>">
                                                <?php echo htmlspecialchars(($param['value'] ?? '') . ' ' . ($param['unit'] ?? '')); ?>
                            </div>
                            </div>
                                    <?php 
                                        endforeach;
                                    else: 
                                    ?>
                            <div class="result-item">
                                <div class="result-label">No parameters available</div>
                                <div class="result-value">-</div>
                            </div>
                                    <?php endif; ?>
                        </div>
                        <div class="card-footer">
                                    <div class="doctor-info">Dr. <?php echo htmlspecialchars($result['doctor_first_name'] . ' ' . $result['doctor_last_name']); ?></div>
                            <div class="card-actions">
                                        <button class="result-btn view-btn" data-id="<?php echo $result['id']; ?>">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                        <button class="result-btn download-btn" data-id="<?php echo $result['id']; ?>">
                                    <i class="fas fa-download"></i> PDF
                                </button>
                            </div>
                        </div>
                    </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-medical-alt"></i>
                            <p>No recent test results</p>
                            </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- All Test Results Table -->
            <div class="test-results-container">
                <div class="search-filter">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search test results...">
                    </div>
                    <div class="filter-options">
                        <span class="filter-label">Filter by:</span>
                        <select class="filter-dropdown">
                            <option value="all">All Time</option>
                            <option value="month">This Month</option>
                            <option value="3months">Last 3 Months</option>
                            <option value="6months">Last 6 Months</option>
                            <option value="year">Last Year</option>
                        </select>
                        <select class="filter-dropdown">
                            <option value="all">All Status</option>
                            <option value="completed">Completed</option>
                            <option value="processing">Processing</option>
                            <option value="pending">Pending</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <select class="filter-dropdown">
                            <option value="all">All Types</option>
                            <option value="blood">Blood Tests</option>
                            <option value="urine">Urine Tests</option>
                            <option value="imaging">Imaging</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Test Name</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Doctor</th>
                            <th>Lab Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($all_results)): ?>
                            <?php foreach ($all_results as $result): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result['test_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($result['test_date'])); ?></td>
                                    <td><span class="test-status status-<?php echo strtolower($result['status']); ?>"><?php echo ucfirst($result['status']); ?></span></td>
                                    <td>Dr. <?php echo htmlspecialchars($result['doctor_first_name'] . ' ' . $result['doctor_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($result['lab_location'] ?? 'Not specified'); ?></td>
                            <td>
                                <div class="result-actions">
                                            <button class="result-btn view-btn" data-id="<?php echo $result['id']; ?>"><i class="fas fa-eye"></i> View</button>
                                            <button class="result-btn download-btn" data-id="<?php echo $result['id']; ?>"><i class="fas fa-download"></i> PDF</button>
                                            <button class="result-btn print-btn" data-id="<?php echo $result['id']; ?>"><i class="fas fa-print"></i></button>
                                            <button class="result-btn share-btn" data-id="<?php echo $result['id']; ?>"><i class="fas fa-share-alt"></i></button>
                                </div>
                            </td>
                        </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-file-medical-alt"></i>
                                    <p>No test results found</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="pagination">
                    <div class="page-info">
                        <?php if (!empty($all_results)): ?>
                            Showing 1-<?php echo count($all_results); ?> of <?php echo count($all_results); ?> results
                        <?php else: ?>
                            No results found
                        <?php endif; ?>
                    </div>
                    <div class="page-buttons">
                        <button class="page-btn disabled"><i class="fas fa-chevron-left"></i></button>
                        <button class="page-btn active">1</button>
                        <button class="page-btn"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Share Results Modal -->
    <div class="modal" id="share-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Share Test Results</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="share-description">Choose how you want to share your <span class="share-test-name">Complete Blood Count</span> test results:</p>
                
                <div class="share-options">
                    <div class="share-option" data-option="email">
                        <div class="share-icon email-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="share-label">Email</div>
                    </div>
                    <div class="share-option" data-option="whatsapp">
                        <div class="share-icon whatsapp-icon">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div class="share-label">WhatsApp</div>
                    </div>
                    <div class="share-option" data-option="doctor">
                        <div class="share-icon doctor-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="share-label">Share with Doctor</div>
                    </div>
                </div>
                
                <div class="share-forms">
                    <!-- Email Form (Initially Shown) -->
                    <div class="share-form active" id="email-form">
                        <div class="form-group">
                            <label for="recipient-email">Recipient's Email Address</label>
                            <input type="email" id="recipient-email" placeholder="Enter email address">
                        </div>
                        <div class="form-group">
                            <label for="email-subject">Subject</label>
                            <input type="text" id="email-subject" value="My Lab Test Results">
                        </div>
                        <div class="form-group">
                            <label for="email-message">Message (Optional)</label>
                            <textarea id="email-message" rows="3" placeholder="Add a personal message"></textarea>
                        </div>
                    </div>
                    
                    <!-- WhatsApp Form (Initially Hidden) -->
                    <div class="share-form" id="whatsapp-form">
                        <div class="form-group">
                            <label for="whatsapp-number">Recipient's Phone Number</label>
                            <input type="tel" id="whatsapp-number" placeholder="Enter phone number with country code">
                        </div>
                        <div class="form-group">
                            <label for="whatsapp-message">Message (Optional)</label>
                            <textarea id="whatsapp-message" rows="3" placeholder="Add a personal message"></textarea>
                        </div>
                    </div>
                    
                    <!-- Doctor Form (Initially Hidden) -->
                    <div class="share-form" id="doctor-form">
                        <div class="form-group">
                            <label for="doctor-select">Select Doctor</label>
                            <select id="doctor-select">
                                <option value="">Select a doctor</option>
                                <option value="dr-johnson">Dr. Sarah Johnson</option>
                                <option value="dr-chen">Dr. Michael Chen</option>
                                <option value="dr-rodriguez">Dr. Emily Rodriguez</option>
                                <option value="dr-wilson">Dr. James Wilson</option>
                                <option value="dr-patel">Dr. Ravi Patel</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="doctor-message">Note to Doctor (Optional)</label>
                            <textarea id="doctor-message" rows="3" placeholder="Add additional information for the doctor"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn cancel-modal-btn">Cancel</button>
                <button class="modal-btn submit-modal-btn">Share Results</button>
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
            
            // Share modal functionality
            const shareButtons = document.querySelectorAll('.share-btn');
            const shareModal = document.getElementById('share-modal');
            const closeModalBtn = document.querySelector('.close-modal');
            const cancelModalBtn = document.querySelector('.cancel-modal-btn');
            const submitModalBtn = document.querySelector('.submit-modal-btn');
            const shareTestName = document.querySelector('.share-test-name');
            
            // Open share modal
            shareButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const resultId = this.getAttribute('data-id');
                    fetch(`get_test_result.php?id=${resultId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                shareTestName.textContent = data.result.test_name;
                    shareModal.classList.add('active');
                            } else {
                                alert('Failed to load test result details');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Failed to load test result details');
                        });
                });
            });
            
            // Close modal functions
            function closeModal() {
                shareModal.classList.remove('active');
            }
            
            // Close modal on close button click
            closeModalBtn.addEventListener('click', closeModal);
            
            // Close modal on cancel button click
            cancelModalBtn.addEventListener('click', closeModal);
            
            // Close modal when clicking outside
            shareModal.addEventListener('click', function(event) {
                if (event.target === shareModal) {
                    closeModal();
                }
            });
            
            // Share options toggle
            const shareOptions = document.querySelectorAll('.share-option');
            const shareForms = document.querySelectorAll('.share-form');
            
            shareOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Get the selected option
                    const optionType = this.getAttribute('data-option');
                    
                    // Highlight the selected option
                    shareOptions.forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    this.classList.add('selected');
                    
                    // Show the corresponding form
                    shareForms.forEach(form => {
                        form.classList.remove('active');
                    });
                    document.getElementById(`${optionType}-form`).classList.add('active');
                });
            });
            
            // Submit share form
            submitModalBtn.addEventListener('click', function() {
                const selectedOption = document.querySelector('.share-option.selected');
                if (!selectedOption) {
                    alert('Please select a sharing option');
                    return;
                }
                
                const optionType = selectedOption.getAttribute('data-option');
                const formData = new FormData(document.getElementById(`${optionType}-form`));
                
                fetch('share_test_result.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Test results shared successfully!');
                closeModal();
                    } else {
                        alert('Failed to share test results: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to share test results. Please try again.');
                });
            });
            
            // View result button functionality
            const viewButtons = document.querySelectorAll('.view-btn');
            
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const resultId = this.getAttribute('data-id');
                    window.location.href = `view_test_result.php?id=${resultId}`;
                });
            });
            
            // Download button functionality
            const downloadButtons = document.querySelectorAll('.download-btn');
            
            downloadButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const resultId = this.getAttribute('data-id');
                    window.location.href = `download_test_result.php?id=${resultId}`;
                });
            });
            
            // Print button functionality
            const printButtons = document.querySelectorAll('.print-btn');
            
            printButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const resultId = this.getAttribute('data-id');
                    window.open(`print_test_result.php?id=${resultId}`, '_blank');
                });
            });
            
            // Search functionality
            const searchInput = document.querySelector('.search-box input');
            const tableRows = document.querySelectorAll('.results-table tbody tr');
            
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                
                tableRows.forEach(row => {
                    const testName = row.cells[0].textContent.toLowerCase();
                    const doctor = row.cells[3].textContent.toLowerCase();
                    
                    if (testName.includes(searchTerm) || doctor.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            // Filter functionality
            const filterDropdowns = document.querySelectorAll('.filter-dropdown');
            
            filterDropdowns.forEach(dropdown => {
                dropdown.addEventListener('change', function() {
                    const timeFilter = document.querySelector('.filter-dropdown:nth-child(2)').value;
                    const statusFilter = document.querySelector('.filter-dropdown:nth-child(3)').value;
                    const typeFilter = document.querySelector('.filter-dropdown:nth-child(4)').value;
                    
                    fetch(`filter_test_results.php?time=${timeFilter}&status=${statusFilter}&type=${typeFilter}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                updateResultsTable(data.results);
                            } else {
                                alert('Failed to filter results: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Failed to filter results. Please try again.');
                        });
                });
            });
            
            function updateResultsTable(results) {
                const tbody = document.querySelector('.results-table tbody');
                tbody.innerHTML = '';
                
                if (results.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-file-medical-alt"></i>
                                <p>No test results found</p>
                            </td>
                        </tr>
                    `;
                    return;
                }
                
                results.forEach(result => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${result.test_name}</td>
                        <td>${new Date(result.test_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                        <td><span class="test-status status-${result.status.toLowerCase()}">${result.status}</span></td>
                        <td>Dr. ${result.doctor_first_name} ${result.doctor_last_name}</td>
                        <td>${result.lab_location}</td>
                        <td>
                            <div class="result-actions">
                                <button class="result-btn view-btn" data-id="${result.id}"><i class="fas fa-eye"></i> View</button>
                                <button class="result-btn download-btn" data-id="${result.id}"><i class="fas fa-download"></i> PDF</button>
                                <button class="result-btn print-btn" data-id="${result.id}"><i class="fas fa-print"></i></button>
                                <button class="result-btn share-btn" data-id="${result.id}"><i class="fas fa-share-alt"></i></button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
                
                // Update pagination info
                document.querySelector('.page-info').textContent = `Showing 1-${results.length} of ${results.length} results`;
            }
        });
    </script>
</body>
</html>