<?php
session_start();
require_once 'config/db_connection.php';

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

// Fetch test categories for filter
$stmt = $pdo->prepare("SELECT * FROM test_categories");
$stmt->execute();
$test_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all patient reports for the doctor
$stmt = $pdo->prepare("
    SELECT tr.*, p.id as pat_id, p.first_name, p.last_name, p.email, p.phone, p.date_of_birth, p.gender,
           t.name as test_name, t.price, t.category_id, tc.name as category_name,
           tr.created_at, tr.status, tr.results
    FROM test_results tr
    JOIN patients p ON tr.patient_id = p.id
    JOIN tests t ON tr.test_id = t.id
    JOIN test_categories tc ON t.category_id = tc.id
    WHERE tr.doctor_id = ?
    ORDER BY tr.created_at DESC
");
$stmt->execute([$doctor_id]);
$all_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter reports based on status
$recent_reports = array_slice($all_reports, 0, 5); // Get 5 most recent reports
$pending_reports = array_filter($all_reports, function($report) {
    return $report['status'] == 'pending';
});
$abnormal_reports = array_filter($all_reports, function($report) {
    return $report['status'] == 'abnormal';
});

// Get report statistics
$total_reports = count($all_reports);
$total_patients = count(array_unique(array_column($all_reports, 'patient_id')));
$pending_count = count($pending_reports);
$abnormal_count = count($abnormal_reports);

// Get detailed report for a specific test if ID is provided
$selected_report = null;
if (isset($_GET['report_id'])) {
    $report_id = $_GET['report_id'];
    $stmt = $pdo->prepare("
        SELECT tr.*, p.id as pat_id, p.first_name, p.last_name, p.email, p.phone, p.date_of_birth, p.gender,
               t.name as test_name, t.price, t.category_id, tc.name as category_name,
               tr.created_at, tr.status, tr.results
        FROM test_results tr
        JOIN patients p ON tr.patient_id = p.id
        JOIN tests t ON tr.test_id = t.id
        JOIN test_categories tc ON t.category_id = tc.id
        WHERE tr.id = ? AND tr.doctor_id = ?
    ");
    $stmt->execute([$report_id, $doctor_id]);
    $selected_report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If report not found or not belonging to this doctor, redirect
    if (!$selected_report) {
        header("Location: patient-reports.php");
        exit();
    }
    
    // Get patient's test history for trends
    $stmt = $pdo->prepare("
        SELECT tr.*, t.name as test_name
        FROM test_results tr
        JOIN tests t ON tr.test_id = t.id
        WHERE tr.patient_id = ? AND t.id = ?
        ORDER BY tr.created_at ASC
    ");
    $stmt->execute([$selected_report['patient_id'], $selected_report['test_id']]);
    $patient_test_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle report actions (download, share, etc.)
if (isset($_POST['action']) && isset($_POST['report_id'])) {
    $action = $_POST['action'];
    $report_id = $_POST['report_id'];
    
    // Verify the report belongs to this doctor
    $stmt = $pdo->prepare("SELECT * FROM test_results WHERE id = ? AND doctor_id = ?");
    $stmt->execute([$report_id, $doctor_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($report) {
        switch ($action) {
            case 'download':
                // Generate PDF and trigger download
                // This would typically use a PDF library like TCPDF or FPDF
                // For now, we'll just redirect to a download script
                header("Location: process/download_report.php?id=" . $report_id);
                exit();
                break;
                
            case 'share':
                // Handle sharing logic
                // This could send an email or generate a shareable link
                // For now, we'll just show a success message
                $_SESSION['success_message'] = "Report shared successfully";
                break;
                
            case 'print':
                // Print functionality is handled client-side
                break;
        }
    }
}

// Determine which tab is active
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';

// Filter reports based on the active tab
$filtered_reports = $all_reports;
if ($active_tab === 'recent') {
    $filtered_reports = array_slice($all_reports, 0, 5); // Get 5 most recent reports
} elseif ($active_tab === 'pending') {
    $filtered_reports = array_filter($all_reports, function($report) {
        return $report['status'] == 'pending';
    });
} elseif ($active_tab === 'abnormal') {
    $filtered_reports = array_filter($all_reports, function($report) {
        return $report['status'] == 'abnormal';
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Reports | Smart Hospital Laboratory System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
        }

        .page-header h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #777;
        }

        .tabs {
            margin-bottom: 20px;
            display: flex;
            border-bottom: 1px solid #ddd;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            border-bottom: 2px solid transparent;
        }

        .tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab:hover {
            color: var(--primary-color);
        }

        .search-filter {
            display: flex;
            margin-bottom: 20px;
            gap: 10px;
        }

        .search-filter .form-control {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
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

        .card {
            background-color: var(--white-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            overflow: hidden;
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

        .card-body {
            padding: 20px;
        }

        .patient-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .patient-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 20px;
        }

        .patient-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .patient-details h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .patient-details .id {
            color: #777;
            margin-bottom: 10px;
        }

        .patient-meta {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .meta-item i {
            color: var(--primary-color);
            margin-right: 5px;
        }

        .patient-stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .stat-card {
            background-color: var(--light-color);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card h4 {
            font-size: 16px;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-card p {
            font-size: 24px;
            font-weight: 600;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th, .report-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .report-table th {
            font-weight: 600;
            background-color: var(--light-color);
        }

        .report-table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-normal {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .status-abnormal {
            background-color: #ffebee;
            color: #d32f2f;
        }

        .status-pending {
            background-color: #fff8e1;
            color: #ff8f00;
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

        .chart-container {
            height: 300px;
            margin-top: 20px;
        }

        .test-details {
            margin-top: 20px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .detail-item {
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
        }

        .detail-item h4 {
            margin-bottom: 10px;
            color: var(--primary-color);
            font-size: 16px;
        }

        .detail-value {
            font-size: 18px;
            font-weight: 600;
        }

        .detail-reference {
            font-size: 14px;
            color: #777;
            margin-top: 5px;
        }

        .detail-item.abnormal {
            border-color: var(--danger-color);
            background-color: rgba(239, 83, 80, 0.05);
        }

        .detail-item.abnormal h4 {
            color: var(--danger-color);
        }

        .report-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .report-notes {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }

        .report-notes h4 {
            margin-bottom: 10px;
            font-size: 16px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #777;
            margin-bottom: 20px;
        }

        @media (max-width: 992px) {
            .details-grid {
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

            .patient-info {
                flex-direction: column;
                text-align: center;
            }

            .patient-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }

            .patient-meta {
                justify-content: center;
                flex-wrap: wrap;
            }

            .tabs {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 5px;
            }
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
                <a href="test-request.php" class="menu-item">
                    <i class="fas fa-flask"></i>
                    <span>Test Requests</span>
                </a>
                <a href="patient-reports.php" class="menu-item active">
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
                <a href="logout.php" class="menu-item">
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
                    <div class="action-item">
                        <i class="fas fa-bell"></i>
                        <div class="notification-badge">5</div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="page-header">
                    <h2>Patient Reports</h2>
                    <p>View and analyze test results for your patients</p>
                </div>

                <div class="tabs">
                    <a href="patient-reports.php?tab=all" class="tab <?php echo $active_tab === 'all' ? 'active' : ''; ?>" data-tab="all">All Reports (<?php echo $total_reports; ?>)</a>
                    <a href="patient-reports.php?tab=recent" class="tab <?php echo $active_tab === 'recent' ? 'active' : ''; ?>" data-tab="recent">Recent (<?php echo count($recent_reports); ?>)</a>
                    <a href="patient-reports.php?tab=pending" class="tab <?php echo $active_tab === 'pending' ? 'active' : ''; ?>" data-tab="pending">Pending (<?php echo $pending_count; ?>)</a>
                    <a href="patient-reports.php?tab=abnormal" class="tab <?php echo $active_tab === 'abnormal' ? 'active' : ''; ?>" data-tab="abnormal">Abnormal Results (<?php echo $abnormal_count; ?>)</a>
                </div>

                <div class="search-filter">
                    <input type="text" class="form-control" placeholder="Search by patient name or ID">
                    <select class="form-control">
                        <option value="">All Test Types</option>
                        <?php foreach ($test_categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="form-control">
                        <option value="">All Dates</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>
                    <button class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>

                <?php if ($selected_report): ?>
                <!-- Detailed Report View -->
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo htmlspecialchars($selected_report['test_name']); ?> Report</h3>
                        <div>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="report_id" value="<?php echo $selected_report['id']; ?>">
                                <button type="submit" name="action" value="print" class="btn btn-secondary" id="printReport">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="patient-info">
                            <div class="patient-avatar">
                                <img src="https://via.placeholder.com/100x100?text=<?php echo substr($selected_report['first_name'], 0, 1) . substr($selected_report['last_name'], 0, 1); ?>" alt="Patient">
                            </div>
                            <div class="patient-details">
                                <h3><?php echo htmlspecialchars($selected_report['first_name'] . ' ' . $selected_report['last_name']); ?></h3>
                                <div class="id">Patient ID: PAT-<?php echo sprintf('%04d', $selected_report['pat_id']); ?></div>
                                <div class="patient-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-user"></i> 
                                        <?php 
                                        $dob = new DateTime($selected_report['date_of_birth']);
                                        $now = new DateTime();
                                        $age = $now->diff($dob)->y;
                                        echo $age . ' Years, ' . ucfirst($selected_report['gender']);
                                        ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-calendar-alt"></i> Tested: <?php echo date('F d, Y', strtotime($selected_report['created_at'])); ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-clock"></i> Report: <?php echo date('F d, Y', strtotime($selected_report['created_at'])); ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-hospital"></i> Lab: Main Laboratory
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($patient_test_history) && count($patient_test_history) > 1): ?>
                        <div class="chart-container">
                            <canvas id="trendsChart"></canvas>
                        </div>
                        <?php endif; ?>

                        <div class="test-details">
                            <h3>Test Results</h3>
                            <div class="details-grid">
                                <?php 
                                // Parse the results JSON if available
                                $results = [];
                                if (!empty($selected_report['results'])) {
                                    // Check if results is already an array
                                    if (is_array($selected_report['results'])) {
                                        $results = $selected_report['results'];
                                    } else {
                                        // Try to decode JSON
                                        $decoded_results = json_decode($selected_report['results'], true);
                                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_results)) {
                                            $results = $decoded_results;
                                        } else {
                                            // If not valid JSON, treat as a simple string value
                                            $results = ['Result' => ['value' => $selected_report['results'], 'unit' => '']];
                                        }
                                    }
                                }
                                
                                // If no results data, show a message
                                if (empty($results)) {
                                    echo '<div class="detail-item"><p>No detailed results available for this test.</p></div>';
                                } else {
                                    // Display each result parameter
                                    foreach ($results as $param => $value) {
                                        // Check if value is an array with expected structure
                                        if (is_array($value) && isset($value['value'])) {
                                            $isAbnormal = isset($value['is_abnormal']) && $value['is_abnormal'];
                                            $class = $isAbnormal ? 'abnormal' : '';
                                            echo '<div class="detail-item ' . $class . '">';
                                            echo '<h4>' . htmlspecialchars($param) . '</h4>';
                                            echo '<div class="detail-value">' . htmlspecialchars($value['value']) . ' ' . htmlspecialchars($value['unit'] ?? '') . '</div>';
                                            if (isset($value['reference'])) {
                                                echo '<div class="detail-reference">Reference: ' . htmlspecialchars($value['reference']) . '</div>';
                                            }
                                            echo '</div>';
                                        } else {
                                            // Handle simple string values
                                            echo '<div class="detail-item">';
                                            echo '<h4>' . htmlspecialchars($param) . '</h4>';
                                            echo '<div class="detail-value">' . htmlspecialchars(is_array($value) ? json_encode($value) : $value) . '</div>';
                                            echo '</div>';
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </div>

                        <?php if (!empty($selected_report['notes'])): ?>
                        <div class="report-notes">
                            <h4>Laboratory Notes</h4>
                            <p><?php echo nl2br(htmlspecialchars($selected_report['notes'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <div class="report-actions">
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="report_id" value="<?php echo $selected_report['id']; ?>">
                                <button type="submit" name="action" value="share" class="btn btn-secondary">
                                    <i class="fas fa-share-alt"></i> Share
                                </button>
                            </form>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="report_id" value="<?php echo $selected_report['id']; ?>">
                                <button type="submit" name="action" value="download" class="btn btn-primary">
                                    <i class="fas fa-file-download"></i> Download PDF
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- Reports List View -->
                <div class="card">
                    <div class="card-header">
                        <h3>Patient Reports</h3>
                    </div>
                    <div class="card-body">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Patient ID</th>
                                    <th>Patient Name</th>
                                    <th>Test Type</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($filtered_reports)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No reports found</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($filtered_reports as $report): ?>
                                <tr>
                                    <td>PAT-<?php echo sprintf('%04d', $report['pat_id']); ?></td>
                                    <td><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($report['test_name']); ?></td>
                                    <td><?php echo date('F d, Y', strtotime($report['created_at'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $report['status']; ?>">
                                            <?php echo ucfirst($report['status']); ?>
                                        </span>
                                    </td>
                                    <td class="actions-cell">
                                        <a href="patient-reports.php?report_id=<?php echo $report['id']; ?>" class="action-btn" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                            <button type="submit" name="action" value="download" class="action-btn" title="Download">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </form>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                            <button type="submit" name="action" value="share" class="action-btn" title="Share">
                                                <i class="fas fa-share-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
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

        // Print report functionality
        document.getElementById('printReport')?.addEventListener('click', function() {
            window.print();
        });

        <?php if ($selected_report && !empty($patient_test_history) && count($patient_test_history) > 1): ?>
        // Chart.js initialization for trends
        const ctx = document.getElementById('trendsChart').getContext('2d');
        const trendsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php 
                    $labels = [];
                    foreach ($patient_test_history as $test) {
                        $date = new DateTime($test['created_at']);
                        $labels[] = $date->format('M');
                    }
                    echo json_encode($labels);
                ?>,
                datasets: [
                    <?php
                    // Get unique parameters from all test results
                    $parameters = [];
                    foreach ($patient_test_history as $test) {
                        if (!empty($test['results'])) {
                            // Check if results is already an array
                            if (is_array($test['results'])) {
                                $results = $test['results'];
                            } else {
                                // Try to decode JSON
                                $results = json_decode($test['results'], true);
                                if (json_last_error() !== JSON_ERROR_NONE || !is_array($results)) {
                                    continue; // Skip if not valid JSON
                                }
                            }
                            
                            foreach ($results as $param => $value) {
                                if (is_array($value) && isset($value['value']) && !in_array($param, $parameters)) {
                                    $parameters[] = $param;
                                }
                            }
                        }
                    }
                    
                    // Limit to 3 parameters for the chart
                    $parameters = array_slice($parameters, 0, 3);
                    
                    // Generate datasets for each parameter
                    $colors = ['#1e88e5', '#ef5350', '#66bb6a'];
                    foreach ($parameters as $index => $param) {
                        $color = $colors[$index % count($colors)];
                        $data = [];
                        foreach ($patient_test_history as $test) {
                            if (!empty($test['results'])) {
                                // Check if results is already an array
                                if (is_array($test['results'])) {
                                    $results = $test['results'];
                                } else {
                                    // Try to decode JSON
                                    $results = json_decode($test['results'], true);
                                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($results)) {
                                        $data[] = null;
                                        continue;
                                    }
                                }
                                
                                if (isset($results[$param]) && is_array($results[$param]) && isset($results[$param]['value'])) {
                                    $data[] = $results[$param]['value'];
                                } else {
                                    $data[] = null;
                                }
                            } else {
                                $data[] = null;
                            }
                        }
                        
                        echo "{
                            label: '" . addslashes($param) . "',
                            data: " . json_encode($data) . ",
                            borderColor: '$color',
                            backgroundColor: '" . str_replace(')', ', 0.1)', $color) . "',
                            tension: 0.4,
                            fill: true
                        },";
                    }
                    ?>
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: '<?php echo addslashes($selected_report['test_name']); ?> Trends Over Time',
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>