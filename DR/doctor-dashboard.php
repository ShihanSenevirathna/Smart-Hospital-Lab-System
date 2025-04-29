<?php
session_start();
require_once 'config/db_connection.php';

// Initialize variables with default values
$pending_tests = 0;
$completed_tests = 0;
$scheduled_appointments = 0;
$abnormal_results = 0;
$recent_results = [];
$recent_activity = [];
$upcoming_appointments = [];
$pending_requests = [];
$doctor_name = "Doctor";
$doctor_role = "Doctor";

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor-login.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];

// Fetch doctor's information from users table
$stmt = $pdo->prepare("
    SELECT u.*, d.specialization, d.department 
    FROM users u 
    LEFT JOIN doctors d ON u.id = d.id 
    WHERE u.id = ? AND u.role = 'doctor'
");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    // If doctor not found, redirect to login
    header("Location: doctor-login.php");
    exit();
}

// Initialize notification variables
$notification_count = 0;
$recent_notifications = [];

// Fetch notifications for the doctor
try {
    // Get unread notification count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as unread_count 
        FROM notifications 
        WHERE doctor_id = ? AND is_read = 0
    ");
    $stmt->execute([$doctor_id]);
    $notification_count = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'] ?? 0;

    // Fetch recent notifications with patient information
    $stmt = $pdo->prepare("
        SELECT n.*, p.first_name, p.last_name 
        FROM notifications n
        LEFT JOIN patients p ON n.patient_id = p.id
        WHERE n.doctor_id = ? 
        ORDER BY n.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$doctor_id]);
    $recent_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
}

// Fetch recent appointments
$stmt = $pdo->prepare("
    SELECT a.*, p.first_name, p.last_name, p.email, p.phone 
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.id 
    WHERE a.doctor_id = ? AND a.status = 'scheduled'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC 
    LIMIT 5
");
$stmt->execute([$doctor_id]);
$recent_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch test results
$stmt = $pdo->prepare("
    SELECT tr.*, p.first_name, p.last_name, t.name as test_name 
    FROM test_results tr 
    JOIN patients p ON tr.patient_id = p.id 
    JOIN tests t ON tr.test_id = t.id 
    WHERE tr.doctor_id = ? 
    ORDER BY tr.created_at DESC 
    LIMIT 5
");
$stmt->execute([$doctor_id]);
$test_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch patient statistics
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT patient_id) as total_patients 
    FROM appointments 
    WHERE doctor_id = ?
");
$stmt->execute([$doctor_id]);
$patient_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent activities
$stmt = $pdo->prepare("
    SELECT * FROM activity_logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$doctor_id]);
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch doctor's statistics
try {
    // Get pending test requests count from test_results table
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM test_results 
        WHERE doctor_id = ? AND status = 'pending'
    ");
    $stmt->execute([$doctor_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pending_tests = $result['count'] ?? 0;

    // Get completed tests count from test_results table
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM test_results 
        WHERE doctor_id = ? AND status = 'completed'
    ");
    $stmt->execute([$doctor_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $completed_tests = $result['count'] ?? 0;

    // Get scheduled appointments count from Appointments table
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM Appointments 
        WHERE doctor_id = ? AND appointment_date >= CURDATE() AND status = 'scheduled'
    ");
    $stmt->execute([$doctor_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $scheduled_appointments = $result['count'] ?? 0;

    // Get abnormal results count from test_results table
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM test_results 
        WHERE doctor_id = ? AND status = 'abnormal'
    ");
    $stmt->execute([$doctor_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $abnormal_results = $result['count'] ?? 0;

    // Get recent test results
    $stmt = $pdo->prepare("
        SELECT 
            tr.id,
            tr.test_name,
            tr.status,
            tr.results,
            tr.created_at,
            tr.patient_id,
            tr.doctor_id,
            p.name as patient_name,
            p.id as pat_id
        FROM test_results tr
        JOIN patients p ON tr.patient_id = p.id
        WHERE tr.doctor_id = ? 
        AND tr.status = 'completed'
        ORDER BY tr.created_at DESC
        LIMIT 4
    ");
    $stmt->execute([$_SESSION['doctor_id']]);
    $recent_results = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // Debug information
    if (empty($recent_results)) {
        error_log("No completed test results found for doctor_id: " . $doctor_id);
    }

    // Get recent activity
    $stmt = $pdo->prepare("
        SELECT * FROM activity_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 4
    ");
    $stmt->execute([$doctor_id]);
    $recent_activity = $stmt->fetchAll() ?: [];

    // Get upcoming appointments
    $stmt = $pdo->prepare("
        SELECT a.*, p.full_name as patient_name, p.profile_picture
        FROM Appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.doctor_id = ? AND a.appointment_date >= CURDATE() AND a.status = 'scheduled'
        ORDER BY a.appointment_date, a.appointment_time
        LIMIT 4
    ");
    $stmt->execute([$doctor_id]);
    $upcoming_appointments = $stmt->fetchAll() ?: [];

    // Get pending test requests
    $stmt = $pdo->prepare("
        SELECT tr.*, p.full_name as patient_name
        FROM test_results tr
        JOIN patients p ON tr.patient_id = p.id
        WHERE tr.doctor_id = ? AND tr.status = 'pending'
        ORDER BY tr.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([$doctor_id]);
    $pending_requests = $stmt->fetchAll() ?: [];

} catch (PDOException $e) {
    // Handle database errors
    error_log("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | Smart Hospital Laboratory System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        :root {
            --primary-color: #1e88e5;
            --secondary-color: #26c6da;
            --dark-color: #0d47a1;
            --light-color: #e3f2fd;
            --success-color: #66bb6a;
            --warning-color: #ffb74d;
            --danger-color: #ef5350;
            --white-color: #ffffff;
            --gray-color: #f5f5f5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--gray-color);
            color: #333;
            line-height: 1.6;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--dark-color);
            color: var(--white-color);
            padding: 20px 0;
            transition: all 0.3s;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header img {
            height: 40px;
            margin-right: 10px;
        }

        .sidebar-header h3 {
            font-size: 18px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu h4 {
            padding: 0 20px 10px;
            opacity: 0.7;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            text-decoration: none;
            color: var(--white-color);
        }

        .menu-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--secondary-color);
        }

        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .menu-item i {
            margin-right: 15px;
            font-size: 18px;
        }

        .doctor-profile {
            padding: 20px;
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
        }

        .doctor-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .doctor-profile-info {
            flex: 1;
        }

        .doctor-profile-info h4 {
            font-size: 14px;
        }

        .doctor-profile-info p {
            font-size: 12px;
            opacity: 0.7;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            overflow: auto;
        }

        .topbar {
            background-color: var(--white-color);
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .toggle-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #555;
        }

        .search-box {
            position: relative;
            margin: 0 20px;
            flex: 1;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 35px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .search-box i {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            color: #999;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
        }

        .topbar-actions .action-item {
            margin-left: 20px;
            font-size: 20px;
            color: #555;
            position: relative;
            cursor: pointer;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dashboard-content {
            padding: 30px;
        }

        .dashboard-header {
            margin-bottom: 30px;
        }

        .dashboard-header h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .dashboard-header p {
            color: #777;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--white-color);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
        }

        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 15px;
        }

        .stat-card.pending .icon {
            background-color: rgba(255, 183, 77, 0.2);
            color: var(--warning-color);
        }

        .stat-card.completed .icon {
            background-color: rgba(102, 187, 106, 0.2);
            color: var(--success-color);
        }

        .stat-card.scheduled .icon {
            background-color: rgba(30, 136, 229, 0.2);
            color: var(--primary-color);
        }

        .stat-card.abnormal .icon {
            background-color: rgba(239, 83, 80, 0.2);
            color: var(--danger-color);
        }

        .stat-info h3 {
            font-size: 22px;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #777;
            font-size: 14px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .card {
            background-color: var(--white-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 18px;
        }

        .card-header .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-primary {
            background-color: var(--light-color);
            color: var(--primary-color);
        }

        .card-body {
            padding: 20px;
        }

        .activity-item {
            display: flex;
            margin-bottom: 20px;
        }

        .activity-item:last-child {
            margin-bottom: 0;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary-color);
        }

        .activity-details {
            flex: 1;
        }

        .activity-details h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .activity-details p {
            color: #777;
            font-size: 14px;
        }

        .activity-time {
            font-size: 12px;
            color: #aaa;
        }

        .appointment-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .appointment-item:last-child {
            border-bottom: none;
        }

        .patient-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            overflow: hidden;
        }

        .patient-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .appointment-details {
            flex: 1;
        }

        .appointment-details h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .appointment-details p {
            font-size: 14px;
            color: #777;
        }

        .appointment-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .status-scheduled {
            background-color: var(--primary-color);
        }

        .status-completed {
            background-color: var(--success-color);
        }

        .status-pending {
            background-color: var(--warning-color);
        }

        .btn-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        .btn-link:hover {
            text-decoration: underline;
        }

        .test-result {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .test-result:last-child {
            border-bottom: none;
        }

        .test-result-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
        }

        .test-result.normal .test-result-icon {
            background-color: var(--success-color);
        }

        .test-result.abnormal .test-result-icon {
            background-color: var(--danger-color);
        }

        .test-result-details {
            flex: 1;
        }

        .test-result-details h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .test-result-details p {
            font-size: 14px;
            color: #777;
        }

        .test-date {
            font-size: 12px;
            color: #aaa;
        }

        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -250px;
                height: 100%;
                z-index: 100;
            }

            .sidebar.open {
                left: 0;
            }

            .stats-container {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        .notification-icon {
            position: relative;
            cursor: pointer;
            margin-left: 20px;
        }

        .notification-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            min-width: 18px;
            text-align: center;
        }

        .notification-dropdown {
            display: none;
            position: fixed;
            top: 70px; /* Adjust based on your topbar height */
            right: 20px;
            width: 350px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            z-index: 1000;
            max-height: 80vh;
            overflow-y: auto;
        }

        .notification-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
            position: sticky;
            top: 0;
        }

        .notification-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .mark-all-read {
            color: #1e88e5;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .notification-list {
            padding: 10px 0;
        }

        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background-color: #f8f9fa;
        }

        .notification-item.unread {
            background-color: #e3f2fd;
        }

        .notification-item.unread:hover {
            background-color: #bbdefb;
        }

        .notification-content {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .notification-title {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .notification-message {
            color: #666;
            font-size: 13px;
            line-height: 1.4;
        }

        .notification-time {
            color: #999;
            font-size: 12px;
            margin-top: 5px;
        }

        .no-notifications {
            padding: 30px 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        /* Add a subtle animation for the dropdown */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notification-dropdown.show {
            display: block;
            animation: slideIn 0.2s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="images/lab-logo.png" alt="Logo">
                <h3>SHLS</h3>
            </div>
            <div class="sidebar-menu">
                <h4>MAIN MENU</h4>
                <a href="doctor-dashboard.php" class="menu-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="test-request.php" class="menu-item">
                    <i class="fas fa-flask"></i>
                    <span>Test Requests</span>
                </a>
                <a href="patient-reports.php" class="menu-item">
                    <i class="fas fa-file-medical-alt"></i>
                    <span>Patient Reports</span>
                </a>
                <a href="appointment-scheduling.php" class="menu-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Appointments</span>
                </a>
                <a href="ehr-integration.php" class="menu-item">
                    <i class="fas fa-database"></i>
                    <span>EHR Integration</span>
                </a>
                <h4>SETTINGS</h4>
                <a href="doctor-profile.php" class="menu-item">
                    <i class="fas fa-user-md"></i>
                    <span>Profile</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="doctor_logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
            <div class="doctor-profile">
                <img src="https://via.placeholder.com/40x40?text=DR" alt="Doctor">
                <div class="doctor-profile-info">
                    <h4>Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h4>
                    <p><?php echo htmlspecialchars($doctor['specialization'] ?? 'Doctor'); ?></p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="topbar">
                <button class="toggle-btn" id="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search for patients, tests, reports...">
                </div>
                <div class="topbar-actions">
                    <div class="action-item">
                        <i class="fas fa-envelope"></i>
                        <div class="notification-badge">3</div>
                    </div>
                    <div class="action-item notification-icon">
                        <i class="fas fa-bell"></i>
                        <?php if ($notification_count > 0): ?>
                            <span class="notification-count"><?php echo $notification_count; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h2>Welcome, Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h2>
                    <p>Here's an overview of your laboratory activity</p>
                </div>

                <div class="stats-container">
                    <div class="stat-card pending">
                        <div class="icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $patient_stats['total_patients']; ?></h3>
                            <p>Total Patients</p>
                        </div>
                    </div>
                    <div class="stat-card completed">
                        <div class="icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($recent_appointments); ?></h3>
                            <p>Recent Appointments</p>
                        </div>
                    </div>
                    <div class="stat-card scheduled">
                        <div class="icon">
                            <i class="fas fa-flask"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($test_results); ?></h3>
                            <p>Test Results</p>
                        </div>
                    </div>
                    <div class="stat-card abnormal">
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($recent_activities); ?></h3>
                            <p>Recent Activities</p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div>
                        <div class="card">
                            <div class="card-header">
                                <h3>Recent Test Results</h3>
                                <span class="badge badge-primary">Latest</span>
                            </div>
                            <div class="card-body">
                                <?php foreach ($test_results as $result): ?>
                                <div class="test-result <?php echo $result['status'] === 'completed' ? 'normal' : 'abnormal'; ?>">
                                    <div class="test-result-icon">
                                        <i class="fas fa-<?php echo $result['status'] === 'completed' ? 'check' : 'exclamation'; ?>"></i>
                                    </div>
                                    <div class="test-result-details">
                                        <h4><?php echo htmlspecialchars($result['test_name']); ?></h4>
                                        <p>Patient: <?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></p>
                                        <span class="test-date"><?php echo date('M j, Y', strtotime($result['created_at'])); ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3>Recent Activity</h3>
                            </div>
                            <div class="card-body">
                                <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-<?php echo $activity['action'] === 'login' ? 'sign-in-alt' : 'user'; ?>"></i>
                                    </div>
                                    <div class="activity-details">
                                        <h4><?php echo htmlspecialchars($activity['action']); ?></h4>
                                        <p><?php echo htmlspecialchars($activity['details']); ?></p>
                                        <span class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="card">
                            <div class="card-header">
                                <h3>Scheduled Appointments</h3>
                                <a href="appointment-scheduling.php" class="btn-link">Schedule New</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_appointments)): ?>
                                    <p class="text-muted">No scheduled appointments</p>
                                <?php else: ?>
                                    <?php foreach ($recent_appointments as $appointment): ?>
                                    <div class="appointment-item">
                                        <div class="patient-avatar">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($appointment['first_name'] . '+' . $appointment['last_name']); ?>&background=1e88e5&color=fff&size=50" alt="Patient">
                                        </div>
                                        <div class="appointment-details">
                                            <h4><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></h4>
                                            <p>
                                                <span class="appointment-status status-scheduled"></span>
                                                <?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?>, 
                                                <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                            </p>
                                            <p><?php echo htmlspecialchars($appointment['test_type']); ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add notification dropdown -->
    <div class="notification-dropdown">
        <div class="notification-header">
            <h3>Notifications</h3>
            <?php if ($notification_count > 0): ?>
                <a href="mark-notifications-read.php" class="mark-all-read">Mark all as read</a>
            <?php endif; ?>
        </div>
        <div class="notification-list">
            <?php if (empty($recent_notifications)): ?>
                <div class="no-notifications">
                    <i class="fas fa-bell-slash" style="font-size: 24px; color: #ccc; margin-bottom: 10px;"></i>
                    <p>No notifications</p>
                </div>
            <?php else: ?>
                <?php foreach ($recent_notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                        <div class="notification-content">
                            <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                            <div class="notification-message">
                                <?php if (!empty($notification['first_name']) && !empty($notification['last_name'])): ?>
                                    <strong>Patient:</strong> <?php echo htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']); ?><br>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($notification['message']); ?>
                            </div>
                            <div class="notification-time">
                                <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggleSidebar');
            
            if (window.innerWidth <= 768 && !sidebar.contains(event.target) && event.target !== toggleBtn) {
                sidebar.classList.remove('open');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const notificationIcon = document.querySelector('.notification-icon');
            const notificationDropdown = document.querySelector('.notification-dropdown');

            notificationIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!notificationDropdown.contains(e.target) && !notificationIcon.contains(e.target)) {
                    notificationDropdown.classList.remove('show');
                }
            });

            // Close dropdown when scrolling
            window.addEventListener('scroll', function() {
                notificationDropdown.classList.remove('show');
            });
        });
    </script>
</body>
</html>