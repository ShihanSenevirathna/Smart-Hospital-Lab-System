<?php
session_start();
require_once 'config/db_connection.php';

// Initialize variables
$recent_requests = [];
$patient_info = null;
$patient_history = [];
$error_message = '';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor-login.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];

// Fetch doctor's information
$stmt = $pdo->prepare("
    SELECT u.*, d.specialization, d.department 
    FROM users u 
    LEFT JOIN doctors d ON u.id = d.id 
    WHERE u.id = ? AND u.role = 'doctor'
");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    header("Location: doctor-login.php");
    exit();
}

// Fetch test categories
$stmt = $pdo->prepare("SELECT * FROM test_categories");
$stmt->execute();
$test_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available tests
$stmt = $pdo->prepare("SELECT * FROM tests");
$stmt->execute();
$available_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent test requests
$stmt = $pdo->prepare("
    SELECT tr.*, p.id as pat_id, p.first_name, p.last_name, p.email, t.name as test_name, t.price
    FROM test_results tr
    JOIN patients p ON tr.patient_id = p.id
    JOIN tests t ON tr.test_id = t.id
    WHERE tr.doctor_id = ?
    ORDER BY tr.created_at DESC
    LIMIT 10
");
$stmt->execute([$doctor_id]);
$recent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch patient statistics
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT patient_id) as total_patients,
           COUNT(*) as total_tests,
           COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_tests,
           COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tests
    FROM test_results
    WHERE doctor_id = ?
");
$stmt->execute([$doctor_id]);
$test_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch patient test history
$stmt = $pdo->prepare("
    SELECT tr.*, p.id as pat_id, p.first_name, p.last_name, t.name as test_name, t.price,
           tc.name as category_name, tr.created_at, tr.status, tr.results
    FROM test_results tr
    JOIN patients p ON tr.patient_id = p.id
    JOIN tests t ON tr.test_id = t.id
    JOIN test_categories tc ON t.category_id = tc.id
    WHERE tr.doctor_id = ?
    ORDER BY tr.created_at DESC
");
$stmt->execute([$doctor_id]);
$patient_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

try {
    // Fetch recent test requests
    $stmt = $pdo->prepare("
        SELECT 
            tr.id,
            tr.patient_id,
            tr.test_name,
            tr.status,
            tr.created_at,
            p.name as patient_name,
            p.id as pat_id
        FROM test_results tr
        JOIN patients p ON tr.patient_id = p.id
        WHERE tr.doctor_id = ?
        ORDER BY tr.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$doctor_id]);
    $recent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch patient test history (for the most recent patient)
    if (!empty($recent_requests)) {
        $latest_patient_id = $recent_requests[0]['patient_id'];
        
        // Get patient info
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.name,
                p.age,
                p.gender,
                p.profile_picture
            FROM patients p
            WHERE p.id = ?
        ");
        $stmt->execute([$latest_patient_id]);
        $patient_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($patient_info) {
            // Get patient's test history
            $stmt = $pdo->prepare("
                SELECT 
                    tr.id,
                    tr.test_name,
                    tr.status,
                    tr.results,
                    tr.created_at
                FROM test_results tr
                WHERE tr.patient_id = ?
                ORDER BY tr.created_at DESC
                LIMIT 4
            ");
            $stmt->execute([$latest_patient_id]);
            $patient_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "An error occurred while fetching the data. Please try again later.";
}

// Function to format the date
function formatDate($date) {
    $date_obj = new DateTime($date);
    $today = new DateTime('today');
    $yesterday = new DateTime('yesterday');

    if ($date_obj->format('Y-m-d') === $today->format('Y-m-d')) {
        return 'Today, ' . $date_obj->format('h:i A');
    } elseif ($date_obj->format('Y-m-d') === $yesterday->format('Y-m-d')) {
        return 'Yesterday';
    } else {
        return $date_obj->format('M j, Y');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Request | Smart Hospital Laboratory System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .content {
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h2 {
            font-size: 24px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white-color);
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--dark-color);
        }

        .btn-secondary {
            background-color: var(--white-color);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-secondary:hover {
            background-color: var(--light-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: var(--white-color);
            border: none;
        }

        .btn-danger:hover {
            background-color: #d32f2f;
        }

        /* Test Request Form */
        .test-request-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .card {
            background-color: var(--white-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .card-header h3 {
            font-size: 18px;
        }

        .card-body {
            padding: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23555' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 35px;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
        }

        .checkbox-item input {
            margin-right: 8px;
        }

        .test-type-categories {
            margin-bottom: 20px;
        }

        .category-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--primary-color);
        }

        .form-footer {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        /* Recent Requests Table */
        .recent-requests table {
            width: 100%;
            border-collapse: collapse;
        }

        .recent-requests th, .recent-requests td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .recent-requests th {
            font-weight: 600;
            background-color: var(--light-color);
        }

        .recent-requests tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-pending {
            background-color: #fff8e1;
            color: #ff8f00;
        }

        .badge-processing {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .badge-completed {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .actions-cell {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
            color: #555;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .action-btn:hover {
            background-color: var(--light-color);
            color: var(--primary-color);
        }

        .action-btn.view:hover {
            background-color: var(--light-color);
            color: var(--primary-color);
        }

        .action-btn.edit:hover {
            background-color: #fff8e1;
            color: #ff8f00;
        }

        .action-btn.delete:hover {
            background-color: #ffebee;
            color: var(--danger-color);
        }

        .test-history {
            margin-top: 20px;
        }

        .patient-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .patient-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 20px;
        }

        .patient-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .patient-details h4 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .patient-details p {
            color: #777;
            margin-bottom: 5px;
        }

        .test-history-list {
            margin-top: 20px;
        }

        .test-history-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .test-history-item:last-child {
            border-bottom: none;
        }

        .test-history-date {
            font-size: 14px;
            color: #777;
            margin-bottom: 5px;
        }

        .test-history-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .test-history-result {
            display: flex;
            align-items: center;
        }

        .result-label {
            background-color: var(--success-color);
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-right: 10px;
        }

        .result-label.abnormal {
            background-color: var(--danger-color);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .test-request-container {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
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

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .checkbox-group {
                grid-template-columns: 1fr;
            }

            .recent-requests {
                overflow-x: auto;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            width: 80%;
            max-width: 800px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
            padding: 20px 0;
        }

        .modal-footer {
            padding-top: 15px;
            border-top: 1px solid #eee;
            text-align: right;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .close-btn:hover {
            color: #333;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-icon.view-btn {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .btn-icon.edit-btn {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .btn-icon.delete-btn {
            background-color: #ffebee;
            color: #d32f2f;
        }

        .btn-icon:hover {
            opacity: 0.8;
            transform: translateY(-1px);
        }

        /* Test History Table */
        .test-history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .test-history-table th,
        .test-history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .test-history-table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }

        .test-history-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Status Badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .status-completed {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .status-cancelled {
            background-color: #ffebee;
            color: #d32f2f;
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
                <a href="doctor-dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="test-request.php" class="menu-item active">
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
                <a href="#" class="menu-item">
                    <i class="fas fa-user-md"></i>
                    <span>Profile</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="process/doctor_logout.php" class="menu-item" id="logoutLink">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
            <div class="doctor-profile">
                <img src="https://via.placeholder.com/40x40?text=DR" alt="Doctor">
                <div class="doctor-profile-info">
                    <h4>Dr. Sarah Johnson</h4>
                    <p>Cardiologist</p>
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
                    <div class="action-item">
                        <i class="fas fa-bell"></i>
                        <div class="notification-badge">5</div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="page-header">
                    <h2>Test Request Management</h2>
                    <button class="btn btn-primary" id="newRequestBtn">
                        <i class="fas fa-plus"></i> New Test Request
                    </button>
                </div>

                <div class="test-request-container">
                    <div>
                        <div class="card recent-requests">
                            <div class="card-header">
                                <h3>Recent Test Requests</h3>
                            </div>
                            <div class="card-body">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Patient ID</th>
                                            <th>Patient Name</th>
                                            <th>Test Type</th>
                                            <th>Date Requested</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recent_requests)): ?>
                                            <?php foreach ($recent_requests as $request): ?>
                                                <tr>
                                                    <td>PAT-<?php echo sprintf('%04d', $request['pat_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($request['test_name']); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $request['status']; ?>">
                                                            <?php echo ucfirst($request['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn-icon view-btn" title="View Details" data-id="<?php echo $request['id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn-icon edit-btn" title="Edit Request" data-id="<?php echo $request['id']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No test requests found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="card">
                            <div class="card-header">
                                <h3>Patient Test History</h3>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($error_message)): ?>
                                    <div class="alert alert-danger">
                                        <?php echo htmlspecialchars($error_message); ?>
                                    </div>
                                <?php elseif ($patient_info): ?>
                                    <div class="patient-info">
                                        <div class="patient-avatar">
                                            <img src="<?php echo htmlspecialchars($patient_info['profile_picture'] ?: 'https://via.placeholder.com/80x80?text=' . substr($patient_info['name'], 0, 2)); ?>" alt="Patient">
                                        </div>
                                        <div class="patient-details">
                                            <h4><?php echo htmlspecialchars($patient_info['name']); ?></h4>
                                            <p><strong>Patient ID:</strong> PAT-<?php echo sprintf('%04d', $patient_info['id']); ?></p>
                                            <p><strong>Age:</strong> <?php echo htmlspecialchars($patient_info['age']); ?> | <strong>Gender:</strong> <?php echo htmlspecialchars($patient_info['gender']); ?></p>
                                            <p><strong>Last Visit:</strong> <?php echo !empty($patient_history) ? formatDate($patient_history[0]['created_at']) : 'N/A'; ?></p>
                                        </div>
                                    </div>
                                    <?php if (!empty($patient_history)): ?>
                                        <div class="test-history-list">
                                            <?php foreach ($patient_history as $test): ?>
                                                <div class="test-history-item">
                                                    <div class="test-history-date"><?php echo formatDate($test['created_at']); ?></div>
                                                    <div class="test-history-name"><?php echo htmlspecialchars($test['test_name']); ?></div>
                                                    <div class="test-history-result">
                                                        <?php if ($test['status'] == 'completed'): ?>
                                                            <span class="result-label <?php echo strpos(strtolower($test['results']), 'normal') === false ? 'abnormal' : ''; ?>">
                                                                <?php echo ucfirst($test['results']); ?>
                                                            </span>
                                                            <a href="view-report.php?id=<?php echo $test['id']; ?>" class="btn-link">View Report</a>
                                                        <?php else: ?>
                                                            <span class="badge badge-<?php echo strtolower($test['status']); ?>"><?php echo ucfirst($test['status']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center">
                                            <p>No test history available for this patient.</p>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-center">
                                        <p>No patient selected or patient information not found.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Request Modal -->
    <div class="modal-backdrop" id="newRequestModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Create New Test Request</h3>
                <button class="close-btn" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="testRequestForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="patientId">Patient ID</label>
                            <input type="text" id="patientId" class="form-control" placeholder="Enter patient ID">
                        </div>
                        <div class="form-group">
                            <label for="patientName">Patient Name</label>
                            <input type="text" id="patientName" class="form-control" placeholder="Enter patient name">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Test Categories</label>
                        <div class="test-type-categories">
                            <div class="category-title">Hematology</div>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" id="cbc" name="testType" value="Complete Blood Count">
                                    <label for="cbc">Complete Blood Count</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="coagulation" name="testType" value="Coagulation Profile">
                                    <label for="coagulation">Coagulation Profile</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="hemoglobin" name="testType" value="Hemoglobin A1C">
                                    <label for="hemoglobin">Hemoglobin A1C</label>
                                </div>
                            </div>
                        </div>
                        <div class="test-type-categories">
                            <div class="category-title">Chemistry</div>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" id="lft" name="testType" value="Liver Function Test">
                                    <label for="lft">Liver Function Test</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="rft" name="testType" value="Renal Function Test">
                                    <label for="rft">Renal Function Test</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="lipid" name="testType" value="Lipid Profile">
                                    <label for="lipid">Lipid Profile</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="electrolytes" name="testType" value="Electrolytes Panel">
                                    <label for="electrolytes">Electrolytes Panel</label>
                                </div>
                            </div>
                        </div>
                        <div class="test-type-categories">
                            <div class="category-title">Endocrinology</div>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" id="thyroid" name="testType" value="Thyroid Function Test">
                                    <label for="thyroid">Thyroid Function Test</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="glucose" name="testType" value="Blood Glucose">
                                    <label for="glucose">Blood Glucose</label>
                                </div>
                            </div>
                        </div>
                        <div class="test-type-categories">
                            <div class="category-title">Microbiology</div>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" id="urinalysis" name="testType" value="Urinalysis">
                                    <label for="urinalysis">Urinalysis</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="culture" name="testType" value="Culture & Sensitivity">
                                    <label for="culture">Culture & Sensitivity</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority" class="form-control">
                            <option value="routine">Routine</option>
                            <option value="urgent">Urgent</option>
                            <option value="stat">STAT (Emergency)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="clinicalNotes">Clinical Notes</label>
                        <textarea id="clinicalNotes" class="form-control" placeholder="Add any relevant clinical information, symptoms, or concerns"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelRequest">Cancel</button>
                <button class="btn btn-primary" id="submitRequest">Submit Request</button>
            </div>
        </div>
    </div>

    <!-- View Test Details Modal -->
    <div id="viewTestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Test Details</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div id="testDetailsContent"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary close-modal">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Test Request Modal -->
    <div id="editTestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Test Request</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editTestForm">
                    <input type="hidden" id="editTestId" name="testId">
                    <div class="form-group">
                        <label for="editPatient">Patient</label>
                        <input type="text" id="editPatient" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label for="editTest">Test</label>
                        <select id="editTest" name="test" class="form-control" required>
                            <?php foreach ($available_tests as $test): ?>
                            <option value="<?php echo $test['id']; ?>">
                                <?php echo htmlspecialchars($test['name']); ?> - $<?php echo number_format($test['price'], 2); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editNotes">Notes</label>
                        <textarea id="editNotes" name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary close-modal">Cancel</button>
                <button class="btn btn-primary" id="saveEditBtn">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Patient Test History Modal -->
    <div id="patientHistoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Patient Test History</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div id="patientHistoryContent"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary close-modal">Close</button>
            </div>
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

        // Modal functionality
        const modal = document.getElementById('newRequestModal');
        const newRequestBtn = document.getElementById('newRequestBtn');
        const closeModal = document.getElementById('closeModal');
        const cancelRequest = document.getElementById('cancelRequest');
        const submitRequest = document.getElementById('submitRequest');

        newRequestBtn.addEventListener('click', function() {
            modal.classList.add('active');
        });

        closeModal.addEventListener('click', function() {
            modal.classList.remove('active');
        });

        cancelRequest.addEventListener('click', function() {
            modal.classList.remove('active');
        });

        submitRequest.addEventListener('click', function() {
            // In a real application, you'd validate and submit the form data here
            alert('Test request submitted successfully!');
            modal.classList.remove('active');
        });

        // Close modal when clicking outside of it
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.classList.remove('active');
            }
        });

        // View buttons functionality
        const viewButtons = document.querySelectorAll('.action-btn.view');
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                // In a real application, you'd show details of the specific test request
                // For simplicity, we'll just redirect to a different page
                window.location.href = 'patient-reports.php';
            });
        });

        // Logout confirmation
        document.getElementById('logoutLink').addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Logout Confirmation',
                text: 'Are you sure you want to logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = this.href;
                }
            });
        });

        // Modal handling
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // View test details
        document.querySelectorAll('.view-btn').forEach(button => {
            button.addEventListener('click', function() {
                const testId = this.dataset.id;
                const test = <?php echo json_encode($recent_requests); ?>.find(t => t.id === testId);
                
                const content = `
                    <div class="test-details">
                        <h4>Patient Information</h4>
                        <p><strong>Name:</strong> ${test.first_name} ${test.last_name}</p>
                        <p><strong>ID:</strong> PAT-${String(test.pat_id).padStart(4, '0')}</p>
                        
                        <h4>Test Information</h4>
                        <p><strong>Test:</strong> ${test.test_name}</p>
                        <p><strong>Status:</strong> <span class="status-badge status-${test.status}">${test.status}</span></p>
                        <p><strong>Date:</strong> ${new Date(test.created_at).toLocaleDateString()}</p>
                        
                        <h4>Test History</h4>
                        <table class="test-history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Test</th>
                                    <th>Status</th>
                                    <th>Results</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${<?php echo json_encode($patient_history); ?>
                                    .filter(h => h.patient_id === test.patient_id)
                                    .map(h => `
                                        <tr>
                                            <td>${new Date(h.created_at).toLocaleDateString()}</td>
                                            <td>${h.test_name}</td>
                                            <td><span class="status-badge status-${h.status}">${h.status}</span></td>
                                            <td>${h.results || 'N/A'}</td>
                                        </tr>
                                    `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                
                document.getElementById('testDetailsContent').innerHTML = content;
                openModal('viewTestModal');
            });
        });

        // Edit test request
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const testId = this.dataset.id;
                const test = <?php echo json_encode($recent_requests); ?>.find(t => t.id === testId);
                
                document.getElementById('editTestId').value = testId;
                document.getElementById('editPatient').value = `${test.first_name} ${test.last_name}`;
                document.getElementById('editTest').value = test.test_id;
                document.getElementById('editNotes').value = test.notes || '';
                
                openModal('editTestModal');
            });
        });

        // Save edit changes
        document.getElementById('saveEditBtn').addEventListener('click', function() {
            const form = document.getElementById('editTestForm');
            const formData = new FormData(form);
            
            // Add AJAX call to save changes
            fetch('process/update_test_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('editTestModal');
                    location.reload(); // Refresh to show updated data
                } else {
                    alert('Error updating test request: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the test request');
            });
        });

        // Close modal buttons
        document.querySelectorAll('.close-btn, .close-modal').forEach(button => {
            button.addEventListener('click', function() {
                const modal = this.closest('.modal');
                modal.style.display = 'none';
            });
        });
    </script>
</body>
</html>