<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug session
echo "<!-- Session Debug: ";
var_dump($_SESSION);
echo " -->";

// Handle logout
if (isset($_GET['logout']) && isset($_GET['confirm']) && $_GET['confirm'] === 'true') {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header('Location: lab-staff-login.php');
    exit;
}

// Check if user is logged in and is lab staff
if (!isset($_SESSION['is_lab_staff']) || $_SESSION['is_lab_staff'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: lab-staff-login.php');
    exit;
}

// Database connection
try {
    require_once 'config/db_connection.php';
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize default values
$alerts_count = 0;
$messages_count = 0;
$today_orders = 0;
$completed_tests = 0;
$in_progress = 0;
$urgent_orders = 0;
$recent_samples = [];
$upcoming_tests = [];
$alerts = [];

// Fetch dashboard data
try {
    // Debug: Check if we can query the database
    $test_query = $pdo->query("SELECT 1");
    echo "<!-- Database connection successful -->";
    
    // Debug: Check user_id
    echo "<!-- Using user_id: " . $_SESSION['user_id'] . " -->";
    
    // Recent Samples Query
    $recent_samples_sql = "SELECT 
        tr.id as sample_id,
        CONCAT('SMP-', DATE_FORMAT(tr.created_at, '%Y-'), LPAD(tr.id, 4, '0')) as sample_code,
        p.first_name,
        p.last_name,
        tr.test_name,
        tr.order_date,
        tr.status,
        tr.lab_staff_id
        FROM test_results tr
        LEFT JOIN patients p ON tr.patient_id = p.id
        WHERE tr.lab_staff_id = :staff_id
        ORDER BY tr.order_date DESC 
        LIMIT 5";
    
    $stmt = $pdo->prepare($recent_samples_sql);
    $stmt->execute([':staff_id' => $_SESSION['user_id']]);
    $recent_samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Upcoming Tests Query
    $upcoming_tests_sql = "SELECT 
        tr.id,
        p.first_name,
        p.last_name,
        tr.test_name,
        tr.order_date,
        tr.test_date,
        tr.status
        FROM test_results tr
        LEFT JOIN patients p ON tr.patient_id = p.id
        WHERE tr.lab_staff_id = :staff_id 
        AND tr.status IN ('pending', 'processing')
        AND tr.test_date >= CURDATE()
        ORDER BY tr.test_date ASC, tr.order_date ASC
        LIMIT 3";
    
    $stmt = $pdo->prepare($upcoming_tests_sql);
    $stmt->execute([':staff_id' => $_SESSION['user_id']]);
    $upcoming_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Dashboard Summary Counts
    $counts_sql = "SELECT 
        COUNT(CASE WHEN DATE(order_date) = CURDATE() THEN 1 END) as today_orders,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tests,
        COUNT(CASE WHEN status = 'processing' THEN 1 END) as in_progress,
        COUNT(CASE WHEN status = 'urgent' THEN 1 END) as urgent_orders
        FROM test_results 
        WHERE lab_staff_id = :staff_id";
    
    $stmt = $pdo->prepare($counts_sql);
    $stmt->execute([':staff_id' => $_SESSION['user_id']]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);

    $today_orders = $counts['today_orders'] ?? 0;
    $completed_tests = $counts['completed_tests'] ?? 0;
    $in_progress = $counts['in_progress'] ?? 0;
    $urgent_orders = $counts['urgent_orders'] ?? 0;

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo "<!-- Database error: " . htmlspecialchars($e->getMessage()) . " -->";
}

// Helper function to format time
function format_time($datetime) {
    return date('g:i A', strtotime($datetime));
}

// Helper function to get status class
function get_status_class($status) {
    switch(strtolower($status)) {
        case 'completed':
            return 'completed';
        case 'urgent':
            return 'urgent';
        case 'processing':
            return 'processing';
        default:
            return 'received';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Dashboard | Smart Hospital Laboratory System</title>
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
        
        .dashboard-title {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .dashboard-summary {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }
        
        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
        }
        
        .card-icon.blue {
            background-color: rgba(52, 152, 219, 0.2);
            color: var(--secondary-color);
        }
        
        .card-icon.green {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--accent-color);
        }
        
        .card-icon.orange {
            background-color: rgba(243, 156, 18, 0.2);
            color: var(--warning-color);
        }
        
        .card-icon.red {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
        }
        
        .card-info h3 {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .card-info p {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .dashboard-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 18px;
            color: var(--dark-color);
        }
        
        .section-action {
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
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
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status.completed {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status.urgent {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .status.processing {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .status.received {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .action-buttons a {
            color: #1565c0;
            text-decoration: none;
            margin-right: 10px;
            font-size: 14px;
        }
        
        .action-buttons a:hover {
            text-decoration: underline;
        }
        
        .upcoming-tests {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .test-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }
        
        .test-type-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e3f2fd;
            color: #1565c0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .test-details {
            flex: 1;
        }
        
        .test-patient {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .test-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .test-time {
            color: #1565c0;
            font-size: 14px;
            font-weight: 500;
        }
        
        .priority-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            margin-left: 8px;
        }
        
        .priority-badge.urgent {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .alerts-section ul {
            list-style: none;
        }
        
        .alert-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .alert-item:last-child {
            border-bottom: none;
        }
        
        .alert-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 16px;
        }
        
        .alert-icon.warning {
            background-color: rgba(243, 156, 18, 0.2);
            color: var(--warning-color);
        }
        
        .alert-icon.danger {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
        }
        
        .alert-details {
            flex: 1;
        }
        
        .alert-message {
            font-size: 14px;
            color: var(--dark-color);
            margin-bottom: 3px;
        }
        
        .alert-time {
            font-size: 12px;
            color: #7f8c8d;
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
            .dashboard-summary {
                grid-template-columns: 1fr 1fr;
            }
            
            .search-box {
                display: none;
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
            
            .dashboard-summary {
                grid-template-columns: 1fr;
            }
            
            .upcoming-tests {
                grid-template-columns: 1fr;
            }
        }
        
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .popup-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        
        .popup-content h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .popup-content p {
            margin-bottom: 20px;
            color: #666;
        }
        
        .popup-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-confirm, .btn-cancel {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn-confirm {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-confirm:hover {
            background-color: #c0392b;
        }
        
        .btn-cancel {
            background-color: var(--light-color);
            color: var(--dark-color);
        }
        
        .btn-cancel:hover {
            background-color: #d5dbdb;
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
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['staff_name'], 0, 2)); ?></div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['staff_name']); ?></div>
                <div class="user-role">Lab Staff</div>
            </div>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="lab-dashboard.php" class="nav-link active">
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
                <a href="#" class="nav-link" onclick="showLogoutPopup(); return false;">
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
                    <span class="notification-badge"><?php echo $alerts_count; ?></span>
                </div>
                <div class="action-icon">
                    <i class="fas fa-envelope"></i>
                    <span class="notification-badge"><?php echo $messages_count; ?></span>
                </div>
            </div>
        </div>
        
        <h1 class="dashboard-title">Laboratory Dashboard</h1>
        
        <!-- Dashboard Summary -->
        <div class="dashboard-summary">
            <div class="summary-card">
                <div class="card-icon blue">
                    <i class="fas fa-vial"></i>
                </div>
                <div class="card-info">
                    <h3>Today's Orders</h3>
                    <p><?php echo $today_orders; ?></p>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-info">
                    <h3>Completed Tests</h3>
                    <p><?php echo $completed_tests; ?></p>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-icon orange">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="card-info">
                    <h3>In Progress</h3>
                    <p><?php echo $in_progress; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Recent Samples Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">Recent Samples</h2>
                <a href="view-all-samples.php" class="section-action">View All</a>
            </div>
            
            <table class="sample-list">
                <thead>
                    <tr>
                        <th>Sample ID</th>
                        <th>Patient</th>
                        <th>Test Type</th>
                        <th>Received Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_samples)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No recent samples found</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($recent_samples as $sample): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sample['sample_code']); ?></td>
                            <td><?php echo htmlspecialchars($sample['first_name'] . ' ' . $sample['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($sample['test_name']); ?></td>
                            <td><?php echo date('g:i A', strtotime($sample['order_date'])); ?></td>
                            <td>
                                <span class="status <?php echo get_status_class($sample['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($sample['status'])); ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="track-sample.php?id=<?php echo $sample['sample_id']; ?>">Track</a>
                                <?php if ($sample['status'] !== 'completed'): ?>
                                <a href="process-sample.php?id=<?php echo $sample['sample_id']; ?>">Process</a>
                                <?php else: ?>
                                <a href="view-report.php?id=<?php echo $sample['sample_id']; ?>">View Report</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Upcoming Tests Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">Upcoming Tests</h2>
                <a href="view-schedule.php" class="section-action">View Schedule</a>
            </div>
            
            <div class="upcoming-tests">
                <?php if (empty($upcoming_tests)): ?>
                <p class="text-center">No upcoming tests scheduled</p>
                <?php else: ?>
                    <?php foreach ($upcoming_tests as $test): ?>
                    <div class="test-card">
                        <div class="test-type-icon">
                            <i class="fas fa-vial"></i>
                        </div>
                        <div class="test-details">
                            <div class="test-patient"><?php echo htmlspecialchars($test['first_name'] . ' ' . $test['last_name']); ?></div>
                            <div class="test-info"><?php echo htmlspecialchars($test['test_name']); ?></div>
                            <div class="test-time">
                                <?php echo date('g:i A', strtotime($test['test_date'])); ?>
                                <?php if ($test['status'] === 'urgent'): ?>
                                <span class="priority-badge urgent">URGENT</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Alerts Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">Important Alerts</h2>
                <a href="alerts.php" class="section-action">View All Alerts</a>
            </div>
            
            <div class="alerts-section">
                <ul>
                    <?php foreach ($alerts as $alert): ?>
                    <li class="alert-item">
                        <div class="alert-icon <?php echo $alert['priority'] === 'high' ? 'danger' : 'warning'; ?>">
                            <i class="fas fa-<?php echo $alert['priority'] === 'high' ? 'exclamation-circle' : 'clock'; ?>"></i>
                        </div>
                        <div class="alert-details">
                            <div class="alert-message"><?php echo htmlspecialchars($alert['message']); ?></div>
                            <div class="alert-time"><?php echo get_time_ago(strtotime($alert['created_at'])); ?></div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Logout Popup -->
    <div id="logoutPopup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to logout?</p>
            <div class="popup-buttons">
                <button onclick="confirmLogout()" class="btn-confirm">Yes, Logout</button>
                <button onclick="closeLogoutPopup()" class="btn-cancel">Cancel</button>
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
        
        function showLogoutPopup() {
            document.getElementById('logoutPopup').style.display = 'flex';
        }
        
        function closeLogoutPopup() {
            document.getElementById('logoutPopup').style.display = 'none';
        }
        
        function confirmLogout() {
            window.location.href = '?logout=1&confirm=true';
        }
        
        // Close popup when clicking outside
        document.getElementById('logoutPopup').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLogoutPopup();
            }
        });
    </script>
</body>
</html>
                    