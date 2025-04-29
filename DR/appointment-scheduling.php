<?php
// Start session and include database connection
session_start();
require_once 'config/db_connection.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor-login.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];

// Get current month and year
$current_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$current_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Calculate first day of the month
$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$days_in_month = date('t', $first_day);
$day_of_week = date('w', $first_day);
$month_name = date('F', $first_day);

// Calculate previous and next month/year for navigation
$prev_month = $current_month - 1;
$prev_year = $current_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $current_month + 1;
$next_year = $current_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Fetch appointments for the current month
$stmt = $pdo->prepare("
    SELECT a.*, p.first_name, p.last_name, p.id as patient_id
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.doctor_id = ? 
    AND MONTH(a.appointment_date) = ? 
    AND YEAR(a.appointment_date) = ?
    AND a.status = 'scheduled'
    ORDER BY a.appointment_date, a.appointment_time
");
$stmt->execute([$doctor_id, $current_month, $current_year]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize appointments by day
$appointments_by_day = [];
foreach ($appointments as $appointment) {
    $day = intval(date('j', strtotime($appointment['appointment_date'])));
    if (!isset($appointments_by_day[$day])) {
        $appointments_by_day[$day] = [];
    }
    $appointments_by_day[$day][] = $appointment;
}

// Fetch upcoming appointments for the sidebar
$stmt = $pdo->prepare("
    SELECT a.*, p.first_name, p.last_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.doctor_id = ? 
    AND a.appointment_date >= CURDATE()
    AND a.status = 'scheduled'
    ORDER BY a.appointment_date, a.appointment_time
    LIMIT 5
");
$stmt->execute([$doctor_id]);
$upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch doctor information
$stmt = $pdo->prepare("
    SELECT u.*, d.specialization, d.department 
    FROM users u 
    LEFT JOIN doctors d ON u.id = d.id 
    WHERE u.id = ? AND u.role = 'doctor'
");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch test types for the appointment form
$stmt = $pdo->prepare("SELECT id, name FROM tests ORDER BY name");
$stmt->execute();
$test_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Scheduling | Smart Hospital Laboratory System</title>
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

        /* Calendar Styles */
        .calendar-container {
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 20px;
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

        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .calendar-nav h3 {
            font-size: 20px;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
        }

        .nav-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--light-color);
            color: var(--primary-color);
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .nav-btn:hover {
            background-color: var(--primary-color);
            color: var(--white-color);
        }

        .calendar {
            width: 100%;
            border-collapse: collapse;
        }

        .calendar th {
            padding: 10px;
            text-align: center;
            font-weight: 600;
            color: #777;
        }

        .calendar td {
            padding: 0;
            border: 1px solid #eee;
            height: 100px;
            vertical-align: top;
            width: 14.28%;
        }

        .calendar-day {
            padding: 8px;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .day-number {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .empty-day {
            background-color: #f9f9f9;
        }

        .today {
            background-color: var(--light-color);
        }

        .calendar-event {
            padding: 5px;
            border-radius: 3px;
            margin-bottom: 5px;
            font-size: 12px;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .event-test {
            background-color: rgba(30, 136, 229, 0.1);
            color: var(--primary-color);
            border-left: 3px solid var(--primary-color);
        }

        .event-urgent {
            background-color: rgba(239, 83, 80, 0.1);
            color: var(--danger-color);
            border-left: 3px solid var(--danger-color);
        }

        .event-followup {
            background-color: rgba(102, 187, 106, 0.1);
            color: var(--success-color);
            border-left: 3px solid var(--success-color);
        }

        /* Upcoming Appointments */
        .upcoming-appointment {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
        }

        .upcoming-appointment:last-child {
            border-bottom: none;
        }

        .appointment-time {
            width: 80px;
            text-align: center;
            padding-right: 15px;
        }

        .appointment-time .time {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary-color);
        }

        .appointment-time .date {
            font-size: 12px;
            color: #777;
        }

        .appointment-info {
            flex: 1;
        }

        .appointment-info h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .appointment-info p {
            font-size: 14px;
            color: #777;
            margin-bottom: 5px;
        }

        .appointment-label {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            margin-right: 5px;
        }

        .label-test {
            background-color: rgba(30, 136, 229, 0.1);
            color: var(--primary-color);
        }

        .label-urgent {
            background-color: rgba(239, 83, 80, 0.1);
            color: var(--danger-color);
        }

        .appointment-actions {
            margin-top: 10px;
        }

        .appointment-actions button {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 14px;
            margin-right: 15px;
        }

        .appointment-actions button:hover {
            text-decoration: underline;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
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

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row > * {
            flex: 1;
        }

        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        /* Modal Styles */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            display: none;
        }

        .modal {
            background-color: var(--white-color);
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 18px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #777;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .modal-backdrop.active {
            display: flex;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .calendar-container {
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

            .calendar {
                font-size: 12px;
            }

            .calendar td {
                height: 80px;
            }

            .calendar-day {
                padding: 5px;
            }

            .form-row {
                flex-direction: column;
                gap: 10px;
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
                <a href="patient-reports.php" class="menu-item">
                    <i class="fas fa-file-medical-alt"></i>
                    <span>Patient Reports</span>
                </a>
                <a href="appointment-scheduling.php" class="menu-item active">
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
                <a href="doctor-login.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
            <div class="doctor-profile">
                <img src="https://via.placeholder.com/40x40?text=DR" alt="Doctor">
                <div class="doctor-profile-info">
                    <h4><?php echo htmlspecialchars($doctor['full_name']); ?></h4>
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
                    <input type="text" placeholder="Search for patients, tests, appointments...">
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
                    <h2>Laboratory Appointment Scheduling</h2>
                </div>

                <div class="calendar-container">
                    <div>
                        <div class="card">
                            <div class="card-header">
                                <div class="calendar-nav">
                                    <h3><?php echo $month_name . ' ' . $current_year; ?></h3>
                                    <div class="nav-buttons">
                                        <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="nav-btn" id="prevMonth">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                        <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="nav-btn" id="nextMonth">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="calendar">
                                    <thead>
                                        <tr>
                                            <th>Sun</th>
                                            <th>Mon</th>
                                            <th>Tue</th>
                                            <th>Wed</th>
                                            <th>Thu</th>
                                            <th>Fri</th>
                                            <th>Sat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Calculate the number of empty cells before the first day of the month
                                        $empty_cells = $day_of_week;
                                        
                                        // Get today's date for highlighting
                                        $today = date('j');
                                        $current_month_today = date('n');
                                        $current_year_today = date('Y');
                                        $is_current_month = ($current_month == $current_month_today && $current_year == $current_year_today);
                                        
                                        // Start the calendar
                                        echo '<tr>';
                                        
                                        // Add empty cells for days before the first day of the month
                                        for ($i = 0; $i < $empty_cells; $i++) {
                                            echo '<td><div class="calendar-day empty-day"></div></td>';
                                        }
                                        
                                        // Add days of the month
                                        for ($day = 1; $day <= $days_in_month; $day++) {
                                            // Check if this is today
                                            $is_today = $is_current_month && $day == $today;
                                            $today_class = $is_today ? ' today' : '';
                                            
                                            echo '<td><div class="calendar-day' . $today_class . '">';
                                            echo '<div class="day-number">' . $day . '</div>';
                                            
                                            // Display appointments for this day
                                            if (isset($appointments_by_day[$day])) {
                                                foreach ($appointments_by_day[$day] as $appointment) {
                                                    // Determine appointment type class
                                                    $appointment_type = 'test';
                                                    if (strpos(strtolower($appointment['test_type']), 'urgent') !== false) {
                                                        $appointment_type = 'urgent';
                                                    } elseif (strpos(strtolower($appointment['test_type']), 'follow') !== false) {
                                                        $appointment_type = 'followup';
                                                    }
                                                    
                                                    // Format time
                                                    $appointment_time = date('g:i A', strtotime($appointment['appointment_time']));
                                                    
                                                    echo '<div class="calendar-event event-' . $appointment_type . '" data-id="' . $appointment['id'] . '">';
                                                    echo $appointment_time . ' - ' . htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']);
                                                    echo '</div>';
                                                }
                                            }
                                            
                                            echo '</div></td>';
                                            
                                            // Start a new row if this is the last day of the week
                                            if (($day + $empty_cells) % 7 == 0) {
                                                echo '</tr>';
                                                if ($day < $days_in_month) {
                                                    echo '<tr>';
                                                }
                                            }
                                        }
                                        
                                        // Add empty cells for days after the last day of the month
                                        $remaining_cells = 7 - (($days_in_month + $empty_cells) % 7);
                                        if ($remaining_cells < 7) {
                                            for ($i = 0; $i < $remaining_cells; $i++) {
                                                echo '<td><div class="calendar-day empty-day"></div></td>';
                                            }
                                        }
                                        
                                        echo '</tr>';
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="card">
                            <div class="card-header">
                                <h3>Today's Appointments</h3>
                            </div>
                            <div class="card-body">
                                <?php
                                // Get today's date
                                $today_date = date('Y-m-d');
                                
                                // Fetch today's appointments
                                $stmt = $pdo->prepare("
                                    SELECT a.*, p.first_name, p.last_name
                                    FROM appointments a
                                    JOIN patients p ON a.patient_id = p.id
                                    WHERE a.doctor_id = ? 
                                    AND a.appointment_date = ?
                                    AND a.status = 'scheduled'
                                    ORDER BY a.appointment_time
                                ");
                                $stmt->execute([$doctor_id, $today_date]);
                                $today_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (count($today_appointments) > 0) {
                                    foreach ($today_appointments as $appointment) {
                                        $appointment_time = date('g:i A', strtotime($appointment['appointment_time']));
                                        $appointment_date = date('j M', strtotime($appointment['appointment_date']));
                                        
                                        // Determine appointment type class
                                        $appointment_type = 'test';
                                        if (strpos(strtolower($appointment['test_type']), 'urgent') !== false) {
                                            $appointment_type = 'urgent';
                                        } elseif (strpos(strtolower($appointment['test_type']), 'follow') !== false) {
                                            $appointment_type = 'followup';
                                        }
                                        
                                        echo '<div class="upcoming-appointment">';
                                        echo '<div class="appointment-time">';
                                        echo '<div class="time">' . $appointment_time . '</div>';
                                        echo '<div class="date">' . $appointment_date . '</div>';
                                        echo '</div>';
                                        echo '<div class="appointment-info">';
                                        echo '<h4>' . htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) . '</h4>';
                                        echo '<p><span class="appointment-label label-' . $appointment_type . '">' . ucfirst($appointment_type) . '</span></p>';
                                        echo '<p>' . htmlspecialchars($appointment['test_type']) . '</p>';
                                        echo '<div class="appointment-actions">';
                                        echo '<button class="reschedule-btn" data-id="' . $appointment['id'] . '">Reschedule</button>';
                                        echo '<button class="cancel-btn" data-id="' . $appointment['id'] . '">Cancel</button>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<p>No appointments scheduled for today.</p>';
                                }
                                ?>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3>Upcoming Appointments</h3>
                            </div>
                            <div class="card-body">
                                <?php
                                if (count($upcoming_appointments) > 0) {
                                    foreach ($upcoming_appointments as $appointment) {
                                        $appointment_time = date('g:i A', strtotime($appointment['appointment_time']));
                                        $appointment_date = date('j M', strtotime($appointment['appointment_date']));
                                        
                                        // Determine appointment type class
                                        $appointment_type = 'test';
                                        if (strpos(strtolower($appointment['test_type']), 'urgent') !== false) {
                                            $appointment_type = 'urgent';
                                        } elseif (strpos(strtolower($appointment['test_type']), 'follow') !== false) {
                                            $appointment_type = 'followup';
                                        }
                                        
                                        echo '<div class="upcoming-appointment">';
                                        echo '<div class="appointment-time">';
                                        echo '<div class="time">' . $appointment_time . '</div>';
                                        echo '<div class="date">' . $appointment_date . '</div>';
                                        echo '</div>';
                                        echo '<div class="appointment-info">';
                                        echo '<h4>' . htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) . '</h4>';
                                        echo '<p><span class="appointment-label label-' . $appointment_type . '">' . ucfirst($appointment_type) . '</span></p>';
                                        echo '<p>' . htmlspecialchars($appointment['test_type']) . '</p>';
                                        echo '<div class="appointment-actions">';
                                        echo '<button class="reschedule-btn" data-id="' . $appointment['id'] . '">Reschedule</button>';
                                        echo '<button class="cancel-btn" data-id="' . $appointment['id'] . '">Cancel</button>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<p>No upcoming appointments scheduled.</p>';
                                }
                                ?>
                                    </div>
                                        </div>
                                    </div>
                                </div>
            </div>
        </div>
    </div>

    <!-- New Appointment Modal -->
    <div class="modal-backdrop" id="newAppointmentModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Schedule New Appointment</h3>
                <button class="close-btn" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="appointmentForm">
                    <div class="form-group">
                        <label for="patientId">Patient ID</label>
                        <input type="text" id="patientId" class="form-control" placeholder="Enter patient ID">
                    </div>
                    <div class="form-group">
                        <label for="patientName">Patient Name</label>
                        <input type="text" id="patientName" class="form-control" placeholder="Enter patient name">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="appointmentDate">Date</label>
                            <input type="date" id="appointmentDate" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="appointmentTime">Time</label>
                            <input type="time" id="appointmentTime" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="appointmentType">Appointment Type</label>
                        <select id="appointmentType" class="form-control">
                            <option value="regular">Regular Test</option>
                            <option value="urgent">Urgent Test</option>
                            <option value="followup">Follow-up Consultation</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="testType">Test Type</label>
                        <select id="testType" class="form-control">
                            <option value="">-- Select Test --</option>
                            <?php foreach ($test_types as $test): ?>
                                <option value="<?php echo $test['id']; ?>"><?php echo htmlspecialchars($test['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" class="form-control" placeholder="Add any additional information"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelAppointment">Cancel</button>
                <button class="btn btn-primary" id="submitAppointment">Schedule Appointment</button>
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
        const modal = document.getElementById('newAppointmentModal');
        const newAppointmentBtn = document.getElementById('newAppointmentBtn');
        const closeModal = document.getElementById('closeModal');
        const cancelAppointment = document.getElementById('cancelAppointment');
        const submitAppointment = document.getElementById('submitAppointment');

        newAppointmentBtn.addEventListener('click', function() {
            modal.classList.add('active');
            
            // Set default date to today
            const today = new Date();
            const formattedDate = today.toISOString().substr(0, 10);
            document.getElementById('appointmentDate').value = formattedDate;
        });

        closeModal.addEventListener('click', function() {
            modal.classList.remove('active');
        });

        cancelAppointment.addEventListener('click', function() {
            modal.classList.remove('active');
        });

        submitAppointment.addEventListener('click', function() {
            // In a real application, you'd validate and submit the form data here
            alert('Appointment scheduled successfully!');
            modal.classList.remove('active');
        });

        // Close modal when clicking outside of it
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.classList.remove('active');
            }
        });

        // Calendar event click
        const calendarEvents = document.querySelectorAll('.calendar-event');
        calendarEvents.forEach(event => {
            event.addEventListener('click', function() {
                const appointmentId = this.getAttribute('data-id');
                alert('Appointment ID: ' + appointmentId + '\nDetails: ' + this.textContent.trim());
                // In a real application, you'd open a modal with appointment details
            });
        });

        // Reschedule and Cancel buttons
        document.querySelectorAll('.reschedule-btn, .cancel-btn').forEach(button => {
            button.addEventListener('click', function() {
                const appointmentId = this.getAttribute('data-id');
                const action = this.classList.contains('reschedule-btn') ? 'reschedule' : 'cancel';
                alert('Appointment ID: ' + appointmentId + ' will be ' + action + 'ed');
                // In a real application, you'd handle the reschedule or cancel action
            });
        });
    </script>
</body>
</html>