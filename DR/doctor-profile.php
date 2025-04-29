<?php
session_start();
require_once 'config/db_connection.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header('Location: doctor-login.php');
    exit();
}

// Get doctor information
$stmt = $pdo->prepare("
    SELECT u.*, d.first_name, d.last_name, d.specialization, d.department
    FROM users u
    LEFT JOIN doctors d ON u.id = d.id
    WHERE u.id = ? AND u.role = 'doctor'
");
$stmt->execute([$_SESSION['doctor_id']]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    header('Location: doctor-login.php');
    exit();
}

// Get doctor's recent activity
$stmt = $pdo->prepare("
    SELECT * FROM activity_logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$_SESSION['doctor_id']]);
$recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get doctor's statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT patient_id) as total_patients,
        COUNT(DISTINCT CASE WHEN status = 'completed' THEN id END) as completed_tests,
        COUNT(DISTINCT CASE WHEN status = 'pending' THEN id END) as pending_tests
    FROM test_results 
    WHERE doctor_id = ?
");
$stmt->execute([$_SESSION['doctor_id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile - Hospital Laboratory System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--gray-color);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--dark-color);
            color: var(--white-color);
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        .sidebar-header img {
            width: 120px;
            margin-bottom: 10px;
        }

        .sidebar-menu {
            flex: 1;
            padding: 20px 0;
        }

        .sidebar-menu h4 {
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
            padding: 0 20px;
            margin-bottom: 10px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--white-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .menu-item:hover, .menu-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--secondary-color);
        }

        .menu-item i {
            width: 20px;
            margin-right: 10px;
        }

        .doctor-profile {
            padding: 20px;
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
            color: var(--white-color);
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

        .profile-content {
            padding: 30px;
        }

        .profile-header {
            background-color: var(--white-color);
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-right: 30px;
            overflow: hidden;
            border: 5px solid var(--light-color);
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info {
            flex: 1;
        }

        .profile-info h1 {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .profile-info p {
            color: var(--secondary-color);
            margin-bottom: 5px;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--white-color);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .stat-card h3 {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-card p {
            color: var(--secondary-color);
            font-size: 14px;
        }

        .profile-details {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .details-card {
            background-color: var(--white-color);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .details-card h2 {
            font-size: 18px;
            color: var(--dark-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .detail-item {
            margin-bottom: 15px;
        }

        .detail-item label {
            display: block;
            color: var(--secondary-color);
            font-size: 14px;
            margin-bottom: 5px;
        }

        .detail-item p {
            color: var(--dark-color);
            font-size: 16px;
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 35px;
            height: 35px;
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
            font-size: 14px;
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .activity-details p {
            font-size: 12px;
            color: var(--secondary-color);
        }

        .activity-time {
            font-size: 12px;
            color: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -250px;
                height: 100vh;
                z-index: 1000;
            }

            .sidebar.active {
                left: 0;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-avatar {
                margin: 0 0 20px 0;
            }

            .profile-stats {
                grid-template-columns: 1fr;
            }

            .profile-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="assets/images/logo.png" alt="Hospital Logo">
            <h3>Hospital Laboratory</h3>
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
            <a href="doctor-profile.php" class="menu-item active">
                <i class="fas fa-user-md"></i>
                <span>Profile</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="doctor-login.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
        <div class="doctor-profile">
            <img src="https://ui-avatars.com/api/?name=Dr+<?php echo urlencode($doctor['first_name'] . '+' . $doctor['last_name']); ?>&background=1e88e5&color=fff&size=40" alt="Doctor">
            <div class="doctor-profile-info">
                <h4>Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h4>
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
        </div>

        <div class="profile-content">
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="https://ui-avatars.com/api/?name=Dr+<?php echo urlencode($doctor['first_name'] . '+' . $doctor['last_name']); ?>&background=1e88e5&color=fff&size=150" alt="Doctor">
                </div>
                <div class="profile-info">
                    <h1>Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h1>
                    <p><i class="fas fa-user-md"></i> <?php echo htmlspecialchars($doctor['specialization'] ?? 'Doctor'); ?></p>
                    <p><i class="fas fa-hospital"></i> <?php echo htmlspecialchars($doctor['department'] ?? 'General Medicine'); ?></p>
                </div>
            </div>

            <div class="profile-stats">
                <div class="stat-card">
                    <h3><?php echo $stats['total_patients']; ?></h3>
                    <p>Total Patients</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['completed_tests']; ?></h3>
                    <p>Completed Tests</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['pending_tests']; ?></h3>
                    <p>Pending Tests</p>
                </div>
            </div>

            <div class="profile-details">
                <div class="details-card">
                    <h2>Personal Information</h2>
                    <div class="detail-item">
                        <label>Full Name</label>
                        <p>Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Email</label>
                        <p><?php echo htmlspecialchars($doctor['email']); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Phone</label>
                        <p><?php echo htmlspecialchars($doctor['phone'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Specialization</label>
                        <p><?php echo htmlspecialchars($doctor['specialization'] ?? 'General Medicine'); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Department</label>
                        <p><?php echo htmlspecialchars($doctor['department'] ?? 'General Medicine'); ?></p>
                    </div>
                </div>

                <div class="details-card">
                    <h2>Recent Activity</h2>
                    <ul class="activity-list">
                        <?php foreach ($recent_activity as $activity): ?>
                            <li class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-<?php 
                                        echo match($activity['action']) {
                                            'login' => 'sign-in-alt',
                                            'logout' => 'sign-out-alt',
                                            'test_request' => 'flask',
                                            'test_result' => 'file-medical-alt',
                                            default => 'circle'
                                        };
                                    ?>"></i>
                                </div>
                                <div class="activity-details">
                                    <h4><?php echo htmlspecialchars($activity['action']); ?></h4>
                                    <p><?php echo htmlspecialchars($activity['details'] ?? ''); ?></p>
                                    <span class="activity-time"><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle Sidebar
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html> 