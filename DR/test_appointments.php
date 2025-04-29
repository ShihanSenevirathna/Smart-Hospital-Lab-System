<?php
session_start();
require_once 'config/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['patient_id'])) {
    header('Location: patient_login.php');
    exit;
}

// Handle new appointment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['status' => 'error', 'message' => ''];
    
    try {
        // Check if it's a JSON request (for cancel action)
        $json = file_get_contents('php://input');
        if (!empty($json)) {
            $data = json_decode($json, true);
            if (isset($data['action']) && $data['action'] === 'cancel') {
                // Handle cancellation
                $sql = "UPDATE appointments SET status = 'cancelled' WHERE id = :id AND patient_id = :patient_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $data['appointment_id'],
                    ':patient_id' => $_SESSION['patient_id']
                ]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['status' => 'success', 'message' => 'Appointment cancelled successfully']);
                } else {
                    throw new Exception('Appointment not found or already cancelled');
                }
                exit;
            }
        }
        
        // Handle form submission (new appointment or reschedule)
        $action = $_POST['action'] ?? 'new';
        $test_type = $_POST['test-type'] ?? '';
        $appointment_date = $_POST['appointment-date'] ?? '';
        $appointment_time = $_POST['appointment-time'] ?? '';

        if (empty($test_type) || empty($appointment_date) || empty($appointment_time)) {
            throw new Exception('Please fill in all required fields');
        }

        // Validate date and time
        $current_date = date('Y-m-d');
        if ($appointment_date < $current_date) {
            throw new Exception('Please select a future date');
        }

        if ($action === 'reschedule') {
            // Update existing appointment
            $appointment_id = $_POST['appointment_id'] ?? null;
            if (!$appointment_id) {
                throw new Exception('Invalid appointment ID');
            }

            $sql = "UPDATE appointments SET 
                    test_type = :test_type,
                    doctor_id = :doctor_id,
                    appointment_date = :appointment_date,
                    appointment_time = :appointment_time,
                    status = 'scheduled'
                    WHERE id = :id AND patient_id = :patient_id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $appointment_id,
                ':patient_id' => $_SESSION['patient_id'],
                ':test_type' => $test_type,
                ':doctor_id' => $_SESSION['doctor_id'],
                ':appointment_date' => $appointment_date,
                ':appointment_time' => $appointment_time
            ]);

            if ($stmt->rowCount() > 0) {
                $response = [
                    'status' => 'success',
                    'message' => 'Appointment rescheduled successfully!'
                ];
            } else {
                throw new Exception('Failed to reschedule appointment');
            }
        } else {
            // Get selected doctor_id from form
            $doctor_id = $_POST['doctor'] ?? null;
            if (empty($doctor_id)) {
                throw new Exception('Please select a doctor');
            }

            // Verify doctor exists and is active
            $doctor_sql = "SELECT id FROM users WHERE id = :doctor_id AND role = 'doctor' AND status = 'active'";
            $doctor_stmt = $pdo->prepare($doctor_sql);
            $doctor_stmt->execute([':doctor_id' => $doctor_id]);
            $doctor_result = $doctor_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$doctor_result) {
                throw new Exception('Invalid or inactive doctor selected');
            }

            // Validate date format
            if (!strtotime($appointment_date)) {
                throw new Exception('Invalid date format');
            }

            // Validate time format
            if (!strtotime($appointment_time)) {
                throw new Exception('Invalid time format');
            }

            // Check if the appointment time is available
            $check_sql = "SELECT COUNT(*) FROM appointments 
                        WHERE doctor_id = :doctor_id 
                        AND appointment_date = :appointment_date 
                        AND appointment_time = :appointment_time 
                        AND status = 'scheduled'";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([
                ':doctor_id' => $doctor_id,
                ':appointment_date' => $appointment_date,
                ':appointment_time' => $appointment_time
            ]);
            $existing_appointments = $check_stmt->fetchColumn();

            if ($existing_appointments > 0) {
                throw new Exception('This time slot is already booked');
            }

            // Insert new appointment
            $sql = "INSERT INTO appointments (
                        patient_id, 
                        test_type, 
                        doctor_id, 
                        appointment_date, 
                        appointment_time,
                        status,
                        created_at
                    ) VALUES (
                        :patient_id,
                        :test_type,
                        :doctor_id,
                        :appointment_date,
                        :appointment_time,
                        'scheduled',
                        NOW()
                    )";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                ':patient_id' => $_SESSION['patient_id'],
                ':test_type' => $test_type,
                ':doctor_id' => $doctor_id,
                ':appointment_date' => $appointment_date,
                ':appointment_time' => $appointment_time
            ]);

            if (!$result) {
                $error = $stmt->errorInfo();
                error_log('Appointment insert error: ' . print_r($error, true));
                throw new Exception('Failed to insert appointment: ' . $error[2]);
            }

            // Get the newly inserted appointment ID
            $appointment_id = $pdo->lastInsertId();

            // Get doctor's name from users table
            $doctor_sql = "SELECT full_name as doctor_name FROM users WHERE id = :doctor_id";
            $doctor_stmt = $pdo->prepare($doctor_sql);
            $doctor_stmt->execute([':doctor_id' => $doctor_id]);
            $doctor_result = $doctor_stmt->fetch(PDO::FETCH_ASSOC);
            $doctor_name = $doctor_result ? " with Dr. " . $doctor_result['doctor_name'] : "";

            // Format the appointment date and time for the notification message
            $formatted_date = date('F j, Y', strtotime($appointment_date));
            $formatted_time = date('g:i A', strtotime($appointment_time));

            $notification_message = "Your appointment for {$test_type}{$doctor_name} has been scheduled for {$formatted_date} at {$formatted_time}.";
            
            // Insert notification
            $notification_sql = "INSERT INTO notifications (
                                patient_id,
                                doctor_id,
                                title,
                                message,
                                type,
                                created_at
                            ) VALUES (
                                :patient_id,
                                :doctor_id,
                                'New Appointment Scheduled',
                                :message,
                                'appointment',
                                NOW()
                            )";

            $notification_stmt = $pdo->prepare($notification_sql);
            $notification_result = $notification_stmt->execute([
                ':patient_id' => $_SESSION['patient_id'],
                ':doctor_id' => $doctor_id,
                ':message' => $notification_message
            ]);

            if (!$notification_result) {
                $error = $notification_stmt->errorInfo();
                error_log('Notification insert error: ' . print_r($error, true));
                // Don't throw exception here as appointment was created
            }

            $response = [
                'status' => 'success',
                'message' => 'Appointment scheduled successfully!'
            ];
        }
    } catch (Exception $e) {
        error_log('Appointment scheduling error: ' . $e->getMessage());
        $response = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Fetch doctors for dropdown
try {
    $sql = "SELECT u.id, u.full_name as doctor_name 
            FROM users u 
            WHERE u.role = 'doctor' 
            ORDER BY u.full_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $doctors = [];
}

// Fetch patient's appointments
try {
    $patient_id = $_SESSION['patient_id'];
    
    // Fetch upcoming appointments
    $sql = "SELECT a.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name 
            FROM appointments a 
            LEFT JOIN doctors d ON a.doctor_id = d.id 
            WHERE a.patient_id = :patient_id 
            AND a.appointment_date >= CURDATE() 
            ORDER BY a.appointment_date ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch past appointments
    $sql = "SELECT a.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name 
            FROM appointments a 
            LEFT JOIN doctors d ON a.doctor_id = d.id 
            WHERE a.patient_id = :patient_id 
            AND a.appointment_date < CURDATE() 
            ORDER BY a.appointment_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $past_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch cancelled appointments
    $sql = "SELECT a.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name 
            FROM appointments a 
            LEFT JOIN doctors d ON a.doctor_id = d.id 
            WHERE a.patient_id = :patient_id 
            AND a.status = 'cancelled' 
            ORDER BY a.appointment_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $cancelled_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Failed to fetch appointments: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Appointments - Smart Hospital Laboratory System</title>
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
        
        /* Appointment management section */
        .management-container {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .schedule-new, .calendar-view {
            flex: 1;
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        
        .section-heading {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .new-appointment-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background-color: #1976d2;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-bottom: 1.5rem;
            width: 100%;
        }
        
        .new-appointment-btn:hover {
            background-color: #0d47a1;
        }
        
        .popular-tests {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .test-card {
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .test-card:hover {
            border-color: #1976d2;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        
        .test-icon {
            width: 50px;
            height: 50px;
            background-color: #e3f2fd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.8rem;
        }
        
        .test-icon i {
            font-size: 1.3rem;
            color: #1976d2;
        }
        
        .test-name {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.3rem;
        }
        
        .test-duration {
            font-size: 0.8rem;
            color: #666;
        }
        
        .month-calendar {
            border: 1px solid #eee;
            border-radius: 5px;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem;
            background-color: #f5f7fa;
            border-bottom: 1px solid #eee;
        }
        
        .month-name {
            font-weight: 500;
            color: #333;
        }
        
        .calendar-nav {
            display: flex;
            gap: 0.5rem;
        }
        
        .calendar-nav-btn {
            width: 30px;
            height: 30px;
            border: none;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .calendar-nav-btn:hover {
            background-color: #e3f2fd;
        }
        
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            padding: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .day-name {
            font-size: 0.8rem;
            color: #666;
            padding: 0.5rem 0;
        }
        
        .calendar-dates {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            grid-template-rows: repeat(5, 1fr);
            text-align: center;
            padding: 0.5rem;
        }
        
        .date {
            padding: 0.5rem 0;
            position: relative;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .date:hover {
            background-color: #f5f7fa;
        }
        
        .date.today {
            background-color: #e3f2fd;
            border-radius: 50%;
            color: #1976d2;
            font-weight: 500;
        }
        
        .date.has-appointment::after {
            content: '';
            position: absolute;
            bottom: 3px;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background-color: #1976d2;
        }
        
        .date.adjacent-month {
            color: #ccc;
        }
        
        /* Appointments list section */
        .appointments-list {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
        }
        
        .list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .list-title {
            font-size: 1.2rem;
            color: #333;
        }
        
        .search-filter {
            display: flex;
            gap: 0.5rem;
        }
        
        .search-input {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .filter-dropdown {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #eee;
            margin-bottom: 1.5rem;
        }
        
        .tab {
            padding: 0.8rem 1.5rem;
            cursor: pointer;
            position: relative;
            color: #666;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .tab:hover {
            color: #1976d2;
        }
        
        .tab.active {
            color: #1976d2;
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #1976d2;
        }
        
        .appointments-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .appointments-table th {
            text-align: left;
            padding: 1rem;
            color: #666;
            font-weight: 500;
            border-bottom: 1px solid #eee;
        }
        
        .appointments-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .appointment-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-scheduled {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .status-completed {
            background-color: #e8f5e9;
            color: #43a047;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #e53935;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            padding: 0.3rem 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: background-color 0.3s;
        }
        
        .view-btn {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .view-btn:hover {
            background-color: #bbdefb;
        }
        
        .reschedule-btn {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .reschedule-btn:hover {
            background-color: #ffe0b2;
        }
        
        .cancel-btn {
            background-color: #ffebee;
            color: #e53935;
        }
        
        .cancel-btn:hover {
            background-color: #ffcdd2;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #bbdefb;
            margin-bottom: 1rem;
        }
        
        /* Modal for new appointment */
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
            max-width: 600px;
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
        
        /* Responsive design */
        @media (max-width: 992px) {
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
            
            .management-container {
                flex-direction: column;
            }
        }
        
        @media (max-width: 768px) {
            .tabs {
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .appointments-table {
                display: block;
                overflow-x: auto;
            }
            
            .popular-tests {
                grid-template-columns: 1fr;
            }
            
            .user-name {
                display: none;
            }
        }
        
        /* Menu toggle for mobile */
        .menu-toggle {
            display: none;
            cursor: pointer;
            font-size: 1.5rem;
        }
        
        @media (max-width: 992px) {
            .menu-toggle {
                display: block;
            }
        }

        /* Cancel Confirmation Modal Styles */
        .cancel-confirm-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .cancel-confirm-modal.active {
            display: flex;
        }

        .cancel-confirm-content {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .cancel-confirm-header {
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cancel-confirm-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #333;
        }

        .close-cancel-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            padding: 0;
        }

        .cancel-confirm-body {
            padding: 20px;
        }

        .cancel-confirm-body p {
            margin: 0;
            color: #333;
        }

        .cancel-warning {
            margin-top: 8px !important;
            color: #666 !important;
            font-size: 0.9rem;
        }

        .cancel-confirm-footer {
            padding: 16px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .keep-appointment-btn {
            padding: 8px 16px;
            border: none;
            background: #f5f5f5;
            color: #333;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .confirm-cancel-btn {
            padding: 8px 16px;
            border: none;
            background: #007bff;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .confirm-cancel-btn:hover {
            background: #0056b3;
        }

        .keep-appointment-btn:hover {
            background: #e9e9e9;
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
                <a href="test_appointments.php" class="menu-item active">
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
            <!-- Page Header -->
            <div class="page-header">
                <h2 class="page-title">Test Appointments</h2>
            </div>
            
            <!-- Appointment Management Section -->
            <div class="management-container">
                <!-- Schedule New Appointment -->
                <div class="schedule-new">
                    <h3 class="section-heading">Schedule New Test</h3>
                    <button class="new-appointment-btn" id="new-appointment-btn">
                        <i class="fas fa-plus"></i> New Appointment
                    </button>
                    
                    <h4 class="subsection-title">Common Tests</h4>
                    <div class="popular-tests">
                        <div class="test-card">
                            <div class="test-icon">
                                <i class="fas fa-tint"></i>
                            </div>
                            <div class="test-name">Complete Blood Count</div>
                            <div class="test-duration">Duration: 15-20 min</div>
                        </div>
                        <div class="test-card">
                            <div class="test-icon">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <div class="test-name">Lipid Profile</div>
                            <div class="test-duration">Duration: 20-30 min</div>
                        </div>
                        <div class="test-card">
                            <div class="test-icon">
                                <i class="fas fa-flask"></i>
                            </div>
                            <div class="test-name">Glucose Test</div>
                            <div class="test-duration">Duration: 10-15 min</div>
                        </div>
                        <div class="test-card">
                            <div class="test-icon">
                                <i class="fas fa-kidneys"></i>
                            </div>
                            <div class="test-name">Kidney Function Test</div>
                            <div class="test-duration">Duration: 25-30 min</div>
                        </div>
                    </div>
                </div>
                
                <!-- Calendar View -->
                <div class="calendar-view">
                    <h3 class="section-heading">Calendar</h3>
                    <div class="month-calendar">
                        <div class="calendar-header">
                            <span class="month-name">April 2025</span>
                            <div class="calendar-nav">
                                <button class="calendar-nav-btn">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button class="calendar-nav-btn">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                        <div class="calendar-days">
                            <div class="day-name">Sun</div>
                            <div class="day-name">Mon</div>
                            <div class="day-name">Tue</div>
                            <div class="day-name">Wed</div>
                            <div class="day-name">Thu</div>
                            <div class="day-name">Fri</div>
                            <div class="day-name">Sat</div>
                        </div>
                        <div class="calendar-dates">
                            <div class="date adjacent-month">30</div>
                            <div class="date adjacent-month">31</div>
                            <div class="date">1</div>
                            <div class="date">2</div>
                            <div class="date today">3</div>
                            <div class="date">4</div>
                            <div class="date">5</div>
                            <div class="date">6</div>
                            <div class="date">7</div>
                            <div class="date">8</div>
                            <div class="date">9</div>
                            <div class="date">10</div>
                            <div class="date">11</div>
                            <div class="date has-appointment">12</div>
                            <div class="date">13</div>
                            <div class="date">14</div>
                            <div class="date">15</div>
                            <div class="date">16</div>
                            <div class="date">17</div>
                            <div class="date">18</div>
                            <div class="date">19</div>
                            <div class="date">20</div>
                            <div class="date">21</div>
                            <div class="date">22</div>
                            <div class="date">23</div>
                            <div class="date">24</div>
                            <div class="date">25</div>
                            <div class="date">26</div>
                            <div class="date">27</div>
                            <div class="date">28</div>
                            <div class="date">29</div>
                            <div class="date">30</div>
                            <div class="date adjacent-month">1</div>
                            <div class="date adjacent-month">2</div>
                            <div class="date adjacent-month">3</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Appointments List Section -->
            <div class="appointments-list">
                <div class="list-header">
                    <h3 class="list-title">My Appointments</h3>
                    <div class="search-filter">
                        <input type="text" class="search-input" placeholder="Search appointments...">
                        <select class="filter-dropdown">
                            <option value="all">All</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="tabs">
                    <div class="tab active" data-tab="upcoming">Upcoming</div>
                    <div class="tab" data-tab="past">Past</div>
                    <div class="tab" data-tab="cancelled">Cancelled</div>
                </div>
                
                <div class="tab-content active" id="upcoming">
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Test Name</th>
                                <th>Date & Time</th>
                                <th>Doctor</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($upcoming_appointments)): ?>
                                <?php foreach ($upcoming_appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['test_type']); ?></td>
                                        <td><?php echo date('M j, Y, g:i A', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'])); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['lab_room']); ?></td>
                                        <td><span class="appointment-status status-<?php echo strtolower($appointment['status']); ?>"><?php echo ucfirst($appointment['status']); ?></span></td>
                                <td>
                                    <div class="action-buttons">
                                                <button class="action-btn view-btn" data-id="<?php echo $appointment['id']; ?>">View</button>
                                                <button class="action-btn reschedule-btn" data-id="<?php echo $appointment['id']; ?>">Reschedule</button>
                                                <button class="action-btn cancel-btn" data-id="<?php echo $appointment['id']; ?>">Cancel</button>
                                    </div>
                                </td>
                            </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <i class="fas fa-calendar-times"></i>
                                        <p>No upcoming appointments</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="tab-content" id="past">
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Test Name</th>
                                <th>Date & Time</th>
                                <th>Doctor</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($past_appointments)): ?>
                                <?php foreach ($past_appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['test_type']); ?></td>
                                        <td><?php echo date('M j, Y, g:i A', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'])); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['lab_room']); ?></td>
                                        <td><span class="appointment-status status-<?php echo strtolower($appointment['status']); ?>"><?php echo ucfirst($appointment['status']); ?></span></td>
                                <td>
                                    <div class="action-buttons">
                                                <button class="action-btn view-btn" data-id="<?php echo $appointment['id']; ?>">View</button>
                                    </div>
                                </td>
                            </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <i class="fas fa-history"></i>
                                        <p>No past appointments</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="tab-content" id="cancelled">
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Test Name</th>
                                <th>Date & Time</th>
                                <th>Doctor</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($cancelled_appointments)): ?>
                                <?php foreach ($cancelled_appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['test_type']); ?></td>
                                        <td><?php echo date('M j, Y, g:i A', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'])); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['lab_room']); ?></td>
                                        <td><span class="appointment-status status-<?php echo strtolower($appointment['status']); ?>"><?php echo ucfirst($appointment['status']); ?></span></td>
                                <td>
                                    <div class="action-buttons">
                                                <button class="action-btn view-btn" data-id="<?php echo $appointment['id']; ?>">View</button>
                                    </div>
                                </td>
                            </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <i class="fas fa-ban"></i>
                                        <p>No cancelled appointments</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal for New Appointment -->
    <div class="modal" id="appointment-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Schedule New Appointment</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="appointment-form" method="POST">
                    <div class="form-group">
                        <label for="test-type">Test Type*</label>
                        <select id="test-type" name="test-type" required>
                            <option value="">Select Test Type</option>
                            <option value="Complete Blood Count">Complete Blood Count (CBC)</option>
                            <option value="Lipid Profile">Lipid Profile</option>
                            <option value="Glucose Test">Glucose Test</option>
                            <option value="Kidney Function">Kidney Function Test</option>
                            <option value="Liver Function">Liver Function Test</option>
                            <option value="Thyroid Function">Thyroid Function Test</option>
                            <option value="Urinalysis">Urinalysis</option>
                            <option value="COVID-19">COVID-19 PCR Test</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="doctor">Referring Doctor</label>
                        <select id="doctor" name="doctor">
                            <option value="">Select Doctor (Optional)</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?php echo $doctor['id']; ?>">
                                    Dr. <?php echo htmlspecialchars($doctor['doctor_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="appointment-date">Preferred Date*</label>
                        <input type="date" id="appointment-date" name="appointment-date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="appointment-time">Preferred Time*</label>
                        <select id="appointment-time" name="appointment-time" required>
                            <option value="">Select Time</option>
                            <option value="09:00">09:00 AM</option>
                            <option value="09:30">09:30 AM</option>
                            <option value="10:00">10:00 AM</option>
                            <option value="10:30">10:30 AM</option>
                            <option value="11:00">11:00 AM</option>
                            <option value="11:30">11:30 AM</option>
                            <option value="14:00">02:00 PM</option>
                            <option value="14:30">02:30 PM</option>
                            <option value="15:00">03:00 PM</option>
                            <option value="15:30">03:30 PM</option>
                            <option value="16:00">04:00 PM</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="special-instructions">Special Instructions</label>
                        <textarea id="special-instructions" name="special-instructions" rows="3" placeholder="Any specific requirements or information"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="modal-btn cancel-modal-btn">Cancel</button>
                <button class="modal-btn submit-modal-btn">Schedule Appointment</button>
            </div>
        </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div class="cancel-confirm-modal" id="cancel-confirm-modal">
        <div class="cancel-confirm-content">
            <div class="cancel-confirm-header">
                <h3>Cancel Appointment</h3>
                <button class="close-cancel-modal">&times;</button>
            </div>
            <div class="cancel-confirm-body">
                <p>Are you sure you want to cancel this appointment?</p>
                <p class="cancel-warning">This action cannot be undone.</p>
            </div>
            <div class="cancel-confirm-footer">
                <button class="keep-appointment-btn">No, Keep it</button>
                <button class="confirm-cancel-btn">Yes, Cancel Appointment</button>
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
            
            // Tab navigation
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // New appointment modal
            const newAppointmentBtn = document.getElementById('new-appointment-btn');
            const appointmentModal = document.getElementById('appointment-modal');
            const closeModalBtn = document.querySelector('.close-modal');
            const cancelModalBtn = document.querySelector('.cancel-modal-btn');
            const appointmentForm = document.getElementById('appointment-form');
            const submitModalBtn = document.querySelector('.submit-modal-btn');
            
            // Open modal
            newAppointmentBtn.addEventListener('click', function() {
                appointmentModal.classList.add('active');
            });
            
            // Close modal functions
            function closeModal() {
                appointmentModal.classList.remove('active');
            }
            
            // Close modal on close button click
            closeModalBtn.addEventListener('click', closeModal);
            
            // Close modal on cancel button click
            cancelModalBtn.addEventListener('click', closeModal);
            
            // Close modal when clicking outside
            appointmentModal.addEventListener('click', function(event) {
                if (event.target === appointmentModal) {
                    closeModal();
                }
            });
            
            // Handle appointment actions (view, reschedule, cancel)
            document.addEventListener('click', function(event) {
                if (event.target.classList.contains('action-btn')) {
                    const appointmentId = event.target.getAttribute('data-id');
                    const action = event.target.classList.contains('view-btn') ? 'view' :
                                 event.target.classList.contains('reschedule-btn') ? 'reschedule' :
                                 event.target.classList.contains('cancel-btn') ? 'cancel' : null;
                    
                    if (action) {
                        handleAppointmentAction(action, appointmentId);
                    }
                }
            });
            
            function handleAppointmentAction(action, appointmentId) {
                switch(action) {
                    case 'view':
                        // Redirect to view appointment page
                        window.location.href = `view_appointment.php?id=${appointmentId}`;
                        break;
                    case 'reschedule':
                        // Open the appointment modal with pre-filled data
                        const appointmentModal = document.getElementById('appointment-modal');
                        appointmentModal.classList.add('active');
                        
                        // Add a hidden input for appointment ID
                        let appointmentIdInput = document.getElementById('appointment-id');
                        if (!appointmentIdInput) {
                            appointmentIdInput = document.createElement('input');
                            appointmentIdInput.type = 'hidden';
                            appointmentIdInput.id = 'appointment-id';
                            appointmentIdInput.name = 'appointment-id';
                            document.getElementById('appointment-form').appendChild(appointmentIdInput);
                        }
                        appointmentIdInput.value = appointmentId;
                        
                        // Change modal title and submit button text
                        document.querySelector('.modal-title').textContent = 'Reschedule Appointment';
                        document.querySelector('.submit-modal-btn').textContent = 'Update Appointment';
                        break;
                    case 'cancel':
                        // Show cancel confirmation modal
                        const cancelModal = document.getElementById('cancel-confirm-modal');
                        cancelModal.classList.add('active');

                        // Handle close button
                        const closeBtn = cancelModal.querySelector('.close-cancel-modal');
                        closeBtn.onclick = () => cancelModal.classList.remove('active');

                        // Handle keep appointment button
                        const keepBtn = cancelModal.querySelector('.keep-appointment-btn');
                        keepBtn.onclick = () => cancelModal.classList.remove('active');

                        // Handle confirm cancel button
                        const confirmBtn = cancelModal.querySelector('.confirm-cancel-btn');
                        confirmBtn.onclick = () => {
                            // Send cancel request to server
                            fetch('test_appointments.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    action: 'cancel',
                                    appointment_id: appointmentId
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                cancelModal.classList.remove('active');
                                if (data.status === 'success') {
                                    location.reload();
                                } else {
                                    alert('Failed to cancel appointment: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                cancelModal.classList.remove('active');
                                alert('Failed to cancel appointment. Please try again.');
                            });
                        };

                        // Close modal when clicking outside
                        cancelModal.onclick = (event) => {
                            if (event.target === cancelModal) {
                                cancelModal.classList.remove('active');
                            }
                        };
                        break;
                }
            }
            
            // Modify the form submission to handle both new appointments and rescheduling
            submitModalBtn.addEventListener('click', function() {
                const form = document.getElementById('appointment-form');
                const formData = new FormData(form);
                const appointmentId = document.getElementById('appointment-id')?.value;
                const isReschedule = !!appointmentId;

                // Validate form
                const testType = document.getElementById('test-type').value;
                const appointmentDate = document.getElementById('appointment-date').value;
                const appointmentTime = document.getElementById('appointment-time').value;
                
                if (!testType || !appointmentDate || !appointmentTime) {
                    alert('Please fill in all required fields.');
                    return;
                }
                
                // Add action type to form data
                formData.append('action', isReschedule ? 'reschedule' : 'new');
                if (isReschedule) {
                    formData.append('appointment_id', appointmentId);
                }
                
                // Send appointment data to server
                fetch('test_appointments.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                closeModal();
                        location.reload(); // Reload page to show updated appointments
                    } else {
                        alert('Failed to ' + (isReschedule ? 'reschedule' : 'schedule') + ' appointment: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to ' + (isReschedule ? 'reschedule' : 'schedule') + ' appointment. Please try again.');
                });
            });
            
            // Test cards open modal with pre-selected test
            const testCards = document.querySelectorAll('.test-card');
            
            testCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Open modal
                    appointmentModal.classList.add('active');
                    
                    // Pre-select the test based on the card clicked
                    const testName = this.querySelector('.test-name').textContent;
                    const testSelect = document.getElementById('test-type');
                    
                    for (let i = 0; i < testSelect.options.length; i++) {
                        if (testSelect.options[i].text.includes(testName)) {
                            testSelect.selectedIndex = i;
                            break;
                        }
                    }
                });
            });

            // Appointments filtering functionality
            const searchInput = document.querySelector('.search-input');
            const filterDropdown = document.querySelector('.filter-dropdown');
            const appointmentRows = document.querySelectorAll('.appointments-table tbody tr');

            function filterAppointments() {
                const searchTerm = searchInput.value.toLowerCase();
                const filterValue = filterDropdown.value;
                const activeTab = document.querySelector('.tab.active').getAttribute('data-tab');

                appointmentRows.forEach(row => {
                    const testName = row.querySelector('td:first-child')?.textContent.toLowerCase() || '';
                    const status = row.querySelector('.appointment-status')?.textContent.toLowerCase() || '';
                    const isInActiveTab = row.closest('.tab-content')?.id === activeTab;

                    // Check if row matches search term
                    const matchesSearch = testName.includes(searchTerm);

                    // Check if row matches filter
                    const matchesFilter = filterValue === 'all' || status.includes(filterValue.toLowerCase());

                    // Show/hide row based on search, filter, and active tab
                    if (isInActiveTab && matchesSearch && matchesFilter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Show/hide empty state message
                const activeTabContent = document.querySelector('.tab-content.active');
                const visibleRows = activeTabContent.querySelectorAll('tbody tr:not([style*="display: none"])');
                const emptyState = activeTabContent.querySelector('.empty-state');

                if (visibleRows.length === 0) {
                    if (!emptyState) {
                        // Create and insert empty state message if none exists
                        const emptyStateHtml = `
                            <tr class="empty-state">
                                <td colspan="6">
                                    <i class="fas fa-search"></i>
                                    <p>No appointments found matching your criteria</p>
                                </td>
                            </tr>`;
                        activeTabContent.querySelector('tbody').insertAdjacentHTML('beforeend', emptyStateHtml);
                    } else {
                        emptyState.style.display = '';
                    }
                } else if (emptyState) {
                    emptyState.style.display = 'none';
                }
            }

            // Add event listeners for search and filter
            searchInput.addEventListener('input', filterAppointments);
            filterDropdown.addEventListener('change', filterAppointments);

            // Update filtering when changing tabs
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Reset search and filter when changing tabs
                    searchInput.value = '';
                    filterDropdown.value = 'all';
                    filterAppointments();
                });
            });

            // Initial filter application
            filterAppointments();
        });
    </script>
</body>
</html>