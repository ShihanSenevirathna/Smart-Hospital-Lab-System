<?php
session_start();
require_once 'config/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['patient_id'])) {
    header('Location: patient_login.php');
    exit;
}

// Function to update account settings
function updateAccountSettings($pdo, $patient_id, $data) {
    try {
        $sql = "UPDATE patients SET 
                email = :email,
                language_preference = :language,
                timezone = :timezone
                WHERE id = :patient_id";
                
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':email' => $data['email'],
            ':language' => $data['language'],
            ':timezone' => $data['timezone'],
            ':patient_id' => $patient_id
        ]);
    } catch(PDOException $e) {
        error_log("Error updating account settings: " . $e->getMessage());
        return false;
    }
}

// Function to update notification preferences
function updateNotificationPreferences($pdo, $patient_id, $data) {
    try {
        $sql = "INSERT INTO notification_settings (
                patient_id,
                appointment_reminders,
                test_results_notifications,
                doctor_messages_notifications,
                medication_reminders,
                health_tips_notifications
            ) VALUES (
                :patient_id,
                :appointment_reminders,
                :test_results_notifications,
                :doctor_messages_notifications,
                :medication_reminders,
                :health_tips_notifications
            ) ON DUPLICATE KEY UPDATE
                appointment_reminders = VALUES(appointment_reminders),
                test_results_notifications = VALUES(test_results_notifications),
                doctor_messages_notifications = VALUES(doctor_messages_notifications),
                medication_reminders = VALUES(medication_reminders),
                health_tips_notifications = VALUES(health_tips_notifications)";
                
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':patient_id' => $patient_id,
            ':appointment_reminders' => $data['appointment_reminders'],
            ':test_results_notifications' => $data['test_result_notifications'],
            ':doctor_messages_notifications' => $data['doctor_messages'],
            ':medication_reminders' => $data['medication_reminders'],
            ':health_tips_notifications' => $data['health_tips']
        ]);
    } catch(PDOException $e) {
        error_log("Error updating notification preferences: " . $e->getMessage());
        return false;
    }
}

// Function to update security settings
function updateSecuritySettings($pdo, $patient_id, $data) {
    try {
        $sql = "UPDATE patients SET 
                two_factor_auth = :two_factor_auth,
                last_password_change = NOW()
                WHERE id = :patient_id";
                
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':two_factor_auth' => $data['two_factor_auth'],
            ':patient_id' => $patient_id
        ]);
    } catch(PDOException $e) {
        error_log("Error updating security settings: " . $e->getMessage());
        return false;
    }
}

// Function to update privacy settings
function updatePrivacySettings($pdo, $patient_id, $data) {
    try {
        $sql = "UPDATE patients SET 
                share_medical_records = :share_medical_records,
                allow_email_communications = :allow_email_communications,
                allow_sms_communications = :allow_sms_communications
                WHERE id = :patient_id";
                
        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            ':share_medical_records' => $data['share_medical_records'],
            ':allow_email_communications' => $data['allow_email_communications'],
            ':allow_sms_communications' => $data['allow_sms_communications'],
            ':patient_id' => $patient_id
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_SESSION['patient_id'];
    $success_message = '';
    $error_message = '';
    
    if (isset($_POST['update_account'])) {
        $data = [
            'email' => $_POST['email'],
            'language' => $_POST['language'],
            'timezone' => $_POST['timezone']
        ];
        
        if (updateAccountSettings($conn, $patient_id, $data)) {
            $success_message = "Account settings updated successfully!";
        } else {
            $error_message = "Failed to update account settings.";
        }
    }
    
    if (isset($_POST['update_notifications'])) {
        $data = [
            'appointment_reminders' => isset($_POST['appointment_reminders']) ? 1 : 0,
            'test_result_notifications' => isset($_POST['test_result_notifications']) ? 1 : 0,
            'doctor_messages' => isset($_POST['doctor_messages']) ? 1 : 0,
            'medication_reminders' => isset($_POST['medication_reminders']) ? 1 : 0,
            'health_tips' => isset($_POST['health_tips']) ? 1 : 0,
            'data_usage_research' => isset($_POST['data_usage_research']) ? 1 : 0,
            'profile_visibility' => isset($_POST['profile_visibility']) ? 1 : 0
        ];
        
        if (updateNotificationPreferences($conn, $patient_id, $data)) {
            $success_message = "Notification preferences updated successfully!";
        } else {
            $error_message = "Failed to update notification preferences.";
        }
    }
    
    if (isset($_POST['update_security'])) {
        $data = [
            'two_factor_auth' => isset($_POST['two_factor_auth']) ? 1 : 0
        ];
        
        if (updateSecuritySettings($conn, $patient_id, $data)) {
            $success_message = "Security settings updated successfully!";
        } else {
            $error_message = "Failed to update security settings.";
        }
    }
    
    if (isset($_POST['update_privacy'])) {
        $data = [
            'share_medical_records' => isset($_POST['share_medical_records']) ? 1 : 0,
            'allow_email_communications' => isset($_POST['allow_email_communications']) ? 1 : 0,
            'allow_sms_communications' => isset($_POST['allow_sms_communications']) ? 1 : 0
        ];
        
        if (updatePrivacySettings($conn, $patient_id, $data)) {
            $success_message = "Privacy settings updated successfully!";
        } else {
            $error_message = "Failed to update privacy settings.";
        }
    }

    if (isset($_POST['update_personal_info'])) {
        try {
            $sql = "UPDATE patients SET 
                    first_name = :first_name,
                    last_name = :last_name,
                    date_of_birth = :date_of_birth,
                    gender = :gender,
                    blood_type = :blood_type,
                    national_id = :national_id
                    WHERE id = :patient_id";
                    
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                ':first_name' => $_POST['first_name'],
                ':last_name' => $_POST['last_name'],
                ':date_of_birth' => $_POST['date_of_birth'],
                ':gender' => $_POST['gender'],
                ':blood_type' => $_POST['blood_type'],
                ':national_id' => $_POST['national_id'],
                ':patient_id' => $patient_id
            ]);
            
            if ($success) {
                $_SESSION['success_message'] = "Personal information updated successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to update personal information. Please try again.";
            }
        } catch(PDOException $e) {
            error_log("Error updating personal information: " . $e->getMessage());
            $_SESSION['error_message'] = "Failed to update personal information. Please try again.";
        }
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['update_contact_info'])) {
        try {
            $sql = "UPDATE patients SET 
                    email = :email,
                    phone = :phone,
                    address = :address,
                    city = :city,
                    state = :state,
                    zip_code = :zip_code
                    WHERE id = :patient_id";
                    
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                ':email' => $_POST['email'],
                ':phone' => $_POST['phone'],
                ':address' => $_POST['address'],
                ':city' => $_POST['city'],
                ':state' => $_POST['state'],
                ':zip_code' => $_POST['zip_code'],
                ':patient_id' => $patient_id
            ]);
            
            if ($success) {
                $_SESSION['success_message'] = "Contact information updated successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to update contact information. Please try again.";
            }
        } catch(PDOException $e) {
            error_log("Error updating contact information: " . $e->getMessage());
            $_SESSION['error_message'] = "Failed to update contact information. Please try again.";
        }
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['update_emergency_info'])) {
        try {
            $sql = "UPDATE patients SET 
                    emergency_contact = :emergency_contact,
                    emergency_phone = :emergency_phone
                    WHERE id = :patient_id";
                    
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                ':emergency_contact' => $_POST['emergency_contact'],
                ':emergency_phone' => $_POST['emergency_phone'],
                ':patient_id' => $patient_id
            ]);
            
            if ($success) {
                $_SESSION['success_message'] = "Emergency contact information updated successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to update emergency contact information. Please try again.";
            }
        } catch(PDOException $e) {
            error_log("Error updating emergency contact information: " . $e->getMessage());
            $_SESSION['error_message'] = "Failed to update emergency contact information. Please try again.";
        }
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

try {
    $patient_id = $_SESSION['patient_id'];
    
    // Fetch patient data
    $sql = "SELECT p.*, 
            COUNT(DISTINCT a.id) as appointment_count,
            COUNT(DISTINCT tr.id) as test_count,
            COUNT(DISTINCT n.id) as notification_count
            FROM patients p
            LEFT JOIN appointments a ON p.id = a.patient_id
            LEFT JOIN test_results tr ON p.id = tr.patient_id
            LEFT JOIN notifications n ON p.id = n.patient_id AND n.is_read = 0
            WHERE p.id = :id
            GROUP BY p.id";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        throw new Exception("Patient not found");
    }
    
    // Format date of birth
    $dob = new DateTime($patient['date_of_birth']);
    $formatted_dob = $dob->format('F j, Y');
    
    // Get initials for avatar
    $initials = strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1));
    
    // Fetch recent test results
    $sql = "SELECT * FROM test_results 
            WHERE patient_id = :patient_id 
            ORDER BY test_date DESC 
            LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $test_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch upcoming appointments
    $sql = "SELECT a.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name 
            FROM appointments a 
            LEFT JOIN doctors d ON a.doctor_id = d.id 
            WHERE a.patient_id = :patient_id 
            AND a.appointment_date >= CURDATE() 
            ORDER BY a.appointment_date ASC 
            LIMIT 2";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch health metrics for the chart
    $sql = "SELECT * FROM health_metrics 
            WHERE patient_id = :patient_id 
            AND metric_type = 'blood_glucose' 
            ORDER BY test_date DESC 
            LIMIT 7";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    $health_metrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
} catch(Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Smart Hospital Laboratory System</title>
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
        
        /* Profile tabs */
        .profile-tabs {
            display: flex;
            border-bottom: 1px solid #eee;
            margin-bottom: 2rem;
        }
        
        .profile-tab {
            padding: 1rem 1.5rem;
            font-weight: 500;
            cursor: pointer;
            color: #666;
            transition: all 0.3s;
            position: relative;
        }
        
        .profile-tab:hover {
            color: #1976d2;
        }
        
        .profile-tab.active {
            color: #1976d2;
        }
        
        .profile-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #1976d2;
        }
        
        /* Profile content */
        .profile-content {
            display: none;
        }
        
        .profile-content.active {
            display: block;
        }
        
        /* Personal Info Tab */
        .profile-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .profile-header {
            background-color: #e3f2fd;
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background-color: #bbdefb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 500;
            color: #0d47a1;
            border: 4px solid white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-name {
            font-size: 1.8rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .profile-id {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }
        
        .profile-quick-info {
            display: flex;
            gap: 1.5rem;
        }
        
        .quick-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quick-info-item i {
            color: #1976d2;
        }
        
        .profile-edit-btn {
            background-color: #1976d2;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: auto;
        }
        
        .profile-edit-btn:hover {
            background-color: #0d47a1;
        }
        
        .profile-body {
            padding: 2rem;
        }
        
        .info-section {
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
        
        .section-edit-link {
            color: #1976d2;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .section-edit-link:hover {
            text-decoration: underline;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.3rem;
        }
        
        .info-value {
            font-weight: 500;
            color: #333;
        }
        
        .medical-info {
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 1.5rem;
        }
        
        .medical-list {
            list-style: none;
        }
        
        .medical-item {
            display: flex;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .medical-item:last-child {
            border-bottom: none;
        }
        
        .medical-item i {
            color: #1976d2;
            margin-right: 1rem;
            width: 20px;
            text-align: center;
        }
        
        /* Medical Records Tab */
        .records-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .records-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .records-title {
            font-size: 1.2rem;
            color: #333;
        }
        
        .records-filter {
            display: flex;
            gap: 1rem;
        }
        
        .records-filter select {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
        }
        
        .upload-btn {
            background-color: #1976d2;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .upload-btn:hover {
            background-color: #0d47a1;
        }
        
        .records-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .records-table th {
            text-align: left;
            padding: 1rem;
            background-color: #f5f7fa;
            color: #555;
            font-weight: 500;
            border-bottom: 1px solid #eee;
        }
        
        .records-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .file-icon {
            width: 36px;
            height: 36px;
            background-color: #f5f5f5;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1976d2;
            font-size: 1.2rem;
        }
        
        .record-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .record-btn {
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
        
        .delete-btn {
            background-color: #ffebee;
            color: #e53935;
        }
        
        .delete-btn:hover {
            background-color: #ffcdd2;
        }
        
        /* Settings Tab */
        .settings-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .settings-nav {
            display: flex;
            flex-direction: column;
            width: 250px;
            border-right: 1px solid #eee;
            padding-right: 1.5rem;
        }
        
        .settings-link {
            padding: 0.8rem 1rem;
            color: #555;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .settings-link:hover {
            background-color: #f5f7fa;
            color: #1976d2;
        }
        
        .settings-link.active {
            background-color: #e3f2fd;
            color: #1976d2;
            font-weight: 500;
        }
        
        .settings-link i {
            width: 20px;
            text-align: center;
        }
        
        .settings-content {
            flex: 1;
            padding-left: 2rem;
        }
        
        .settings-panel {
            display: none;
        }
        
        .settings-panel.active {
            display: block;
        }
        
        .settings-title {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 1.5rem;
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
        
        .form-row {
            display: flex;
            gap: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
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
        
        /* Security Settings */
        .security-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .security-info {
            flex: 1;
        }
        
        .security-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .security-description {
            font-size: 0.9rem;
            color: #666;
        }
        
        .security-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .security-enabled {
            color: #43a047;
            font-weight: 500;
        }
        
        .security-disabled {
            color: #e53935;
            font-weight: 500;
        }
        
        /* Responsive menu toggle */
        .menu-toggle {
            display: none;
            cursor: pointer;
            font-size: 1.5rem;
        }
        
        /* Responsive settings layout */
        .settings-layout {
            display: flex;
        }
        
        .settings-mobile-nav {
            display: none;
            margin-bottom: 1.5rem;
        }
        
        .settings-dropdown {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
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
            
            .settings-layout {
                flex-direction: column;
            }
            
            .settings-nav {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #eee;
                padding-right: 0;
                padding-bottom: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .settings-content {
                padding-left: 0;
            }
            
            .settings-mobile-nav {
                display: block;
            }
            
            .settings-nav {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }
            
            .profile-quick-info {
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
            }
            
            .profile-edit-btn {
                margin-left: 0;
                margin-top: 1rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .profile-tabs {
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .records-table {
                display: block;
                overflow-x: auto;
            }
            
            .user-name {
                display: none;
            }
        }
        
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-badge.completed {
            background-color: #e8f5e9;
            color: #43a047;
        }
        
        .status-badge.processing {
            background-color: #fff3e0;
            color: #f57c00;
        }
        
        .status-badge.pending {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .status-badge.cancelled {
            background-color: #ffebee;
            color: #e53935;
        }
        
        .status-badge.scheduled {
            background-color: #e8eaf6;
            color: #3f51b5;
        }

        /* Modal styles */
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
            background-color: white;
            margin: 5% auto;
            padding: 0;
            width: 90%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
        }

        .close {
            font-size: 1.5rem;
            font-weight: bold;
            color: #666;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover {
            color: #333;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .cancel-btn {
            padding: 0.8rem 1.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
            color: #666;
            cursor: pointer;
            transition: all 0.3s;
        }

        .cancel-btn:hover {
            background-color: #f5f5f5;
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
                    <span class="notification-count"><?php echo $patient['notification_count']; ?></span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo htmlspecialchars($initials); ?>
                    </div>
                    <span class="user-name"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></span>
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
                <a href="patient_profile.php" class="menu-item active">
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
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Page Header -->
            <div class="page-header">
                <h2 class="page-title">My Profile</h2>
                <p class="page-subtitle">Manage your personal information and account settings</p>
            </div>
            
            <!-- Profile Tabs -->
            <div class="profile-tabs">
                <div class="profile-tab active" data-tab="personal-info">Personal Info</div>
                <div class="profile-tab" data-tab="medical-records">Medical Records</div>
                <div class="profile-tab" data-tab="settings">Settings</div>
            </div>
            
            <!-- Profile Content - Personal Info Tab -->
            <div class="profile-content active" id="personal-info">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php echo htmlspecialchars($initials); ?>
                        </div>
                        <div class="profile-info">
                            <h3 class="profile-name"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h3>
                            <p class="profile-id">Patient ID: SHLS-<?php echo str_pad($patient['id'], 5, '0', STR_PAD_LEFT); ?></p>
                            <div class="profile-quick-info">
                                <div class="quick-info-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($patient['email']); ?></span>
                                </div>
                                <div class="quick-info-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($patient['phone']); ?></span>
                                </div>
                                <div class="quick-info-item">
                                    <i class="fas fa-birthday-cake"></i>
                                    <span><?php echo htmlspecialchars($formatted_dob); ?></span>
                                </div>
                            </div>
                        </div>
                        <button class="profile-edit-btn">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                    </div>
                    <div class="profile-body">
                        <!-- Personal Information Section -->
                        <div class="info-section">
                            <h3 class="section-title">
                                Personal Information
                                <a href="#" class="section-edit-link">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </a>
                            </h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Full Name</span>
                                    <span class="info-value"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Date of Birth</span>
                                    <span class="info-value"><?php echo htmlspecialchars($formatted_dob); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Gender</span>
                                    <span class="info-value"><?php echo htmlspecialchars($patient['gender']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Blood Type</span>
                                    <span class="info-value"><?php echo htmlspecialchars($patient['blood_type'] ?? 'Not specified'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">National ID</span>
                                    <span class="info-value"><?php echo htmlspecialchars($patient['national_id']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contact Information Section -->
                        <div class="info-section">
                            <h3 class="section-title">
                                Contact Information
                                <a href="#" class="section-edit-link">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </a>
                            </h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Email Address</span>
                                    <span class="info-value"><?php echo htmlspecialchars($patient['email']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Phone Number</span>
                                    <span class="info-value"><?php echo htmlspecialchars($patient['phone']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Address</span>
                                    <span class="info-value"><?php echo htmlspecialchars($patient['address']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">City</span>
                                    <span class="info-value"><?php echo htmlspecialchars($patient['city']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">State</span>
                                    <span class="info-value"><?php echo htmlspecialchars($patient['state']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">ZIP Code</span>
                                    <span class="info-value"><?php echo htmlspecialchars($patient['zip_code']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Emergency Contact Section -->
                        <div class="info-section">
                            <h3 class="section-title">
                                Emergency Contact
                                <a href="#" class="section-edit-link">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </a>
                            </h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Contact Name</span>
                                    <span class="info-value"><?php echo htmlspecialchars($patient['emergency_contact'] ?? 'Not specified'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Phone Number</span>
                                    <span class="info-value"><?php echo htmlspecialchars($patient['emergency_phone'] ?? 'Not specified'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Medical Information Section -->
                        <div class="info-section">
                            <h3 class="section-title">
                                Medical Information
                                <a href="#" class="section-edit-link">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </a>
                            </h3>
                            <div class="medical-info">
                                <ul class="medical-list">
                                    <?php if (!empty($patient['allergies'])): ?>
                                    <li class="medical-item">
                                        <i class="fas fa-pills"></i>
                                        <div>
                                            <strong>Allergies:</strong> <?php echo htmlspecialchars($patient['allergies']); ?>
                                        </div>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($patient['medical_conditions'])): ?>
                                    <li class="medical-item">
                                        <i class="fas fa-heartbeat"></i>
                                        <div>
                                            <strong>Medical Conditions:</strong> <?php echo htmlspecialchars($patient['medical_conditions']); ?>
                                        </div>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($patient['medications'])): ?>
                                    <li class="medical-item">
                                        <i class="fas fa-syringe"></i>
                                        <div>
                                            <strong>Current Medications:</strong> <?php echo htmlspecialchars($patient['medications']); ?>
                                        </div>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Content - Medical Records Tab -->
            <div class="profile-content" id="medical-records">
                <div class="records-container">
                    <div class="records-header">
                        <h3 class="records-title">My Medical Records</h3>
                        <div class="records-filter">
                            <select class="filter-dropdown">
                                <option value="all">All Records</option>
                                <option value="test_results">Test Results</option>
                                <option value="appointments">Appointments</option>
                                <option value="health_metrics">Health Metrics</option>
                            </select>
                            <button class="upload-btn">
                                <i class="fas fa-upload"></i> Upload Record
                            </button>
                        </div>
                    </div>
                    
                    <table class="records-table">
                        <thead>
                            <tr>
                                <th>Record Type</th>
                                <th>Details</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch and display test results
                            $sql = "SELECT * FROM test_results 
                                    WHERE patient_id = :patient_id 
                                    ORDER BY test_date DESC";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([':patient_id' => $patient_id]);
                            $test_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($test_results as $result) {
                                $status_class = $result['status'] === 'completed' ? 'completed' : 
                                              ($result['status'] === 'processing' ? 'processing' : 'pending');
                                $status_text = ucfirst($result['status']);
                                ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                                        <div class="file-icon">
                                                <i class="fas fa-file-medical-alt"></i>
                                        </div>
                                            <span>Test Result</span>
                                    </div>
                                </td>
                                    <td><?php echo htmlspecialchars($result['test_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($result['test_date'])); ?></td>
                                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                <td>
                                    <div class="record-actions">
                                            <?php if ($result['status'] === 'completed'): ?>
                                                <button class="record-btn view-btn" onclick="viewTestResult(<?php echo $result['id']; ?>)">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <button class="record-btn download-btn" onclick="downloadTestResult(<?php echo $result['id']; ?>)">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                            <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>

                            <?php
                            // Fetch and display appointments
                            $sql = "SELECT a.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name 
                                    FROM appointments a 
                                    LEFT JOIN doctors d ON a.doctor_id = d.id 
                                    WHERE a.patient_id = :patient_id 
                                    ORDER BY a.appointment_date DESC";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([':patient_id' => $patient_id]);
                            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($appointments as $appointment) {
                                $status_class = $appointment['status'] === 'completed' ? 'completed' : 
                                              ($appointment['status'] === 'cancelled' ? 'cancelled' : 'scheduled');
                                $status_text = ucfirst($appointment['status']);
                                ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                                        <div class="file-icon">
                                                <i class="fas fa-calendar-check"></i>
                                        </div>
                                            <span>Appointment</span>
                                    </div>
                                </td>
                                    <td>
                                        <?php echo htmlspecialchars($appointment['test_type']); ?>
                                        <br>
                                        <small>Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                <td>
                                    <div class="record-actions">
                                            <button class="record-btn view-btn" onclick="viewAppointment(<?php echo $appointment['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <?php if ($appointment['status'] === 'scheduled'): ?>
                                                <button class="record-btn cancel-btn" onclick="cancelAppointment(<?php echo $appointment['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>

                            <?php
                            // Fetch and display health metrics
                            $sql = "SELECT * FROM health_metrics 
                                    WHERE patient_id = :patient_id 
                                    ORDER BY test_date DESC";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([':patient_id' => $patient_id]);
                            $health_metrics = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($health_metrics as $metric) {
                                ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                                        <div class="file-icon">
                                                <i class="fas fa-heartbeat"></i>
                                        </div>
                                            <span>Health Metric</span>
                                    </div>
                                </td>
                                    <td>
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $metric['metric_type']))); ?>
                                        <br>
                                        <small>Value: <?php echo htmlspecialchars($metric['result_value']); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($metric['test_date'])); ?></td>
                                    <td><span class="status-badge completed">Recorded</span></td>
                                <td>
                                    <div class="record-actions">
                                            <button class="record-btn view-btn" onclick="viewHealthMetric(<?php echo $metric['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Profile Content - Settings Tab -->
            <div class="profile-content" id="settings">
                <div class="settings-container">
                    <!-- Mobile dropdown for settings navigation -->
                    <div class="settings-mobile-nav">
                        <select class="settings-dropdown">
                            <option value="account">Account Settings</option>
                            <option value="notifications">Notification Preferences</option>
                            <option value="security">Security Settings</option>
                            <option value="privacy">Privacy Settings</option>
                        </select>
                    </div>
                    
                    <!-- Settings layout with sidebar and content -->
                    <div class="settings-layout">
                        <nav class="settings-nav">
                            <a href="#" class="settings-link active" data-panel="account">
                                <i class="fas fa-user-cog"></i> Account Settings
                            </a>
                            <a href="#" class="settings-link" data-panel="notifications">
                                <i class="fas fa-bell"></i> Notification Preferences
                            </a>
                            <a href="#" class="settings-link" data-panel="security">
                                <i class="fas fa-shield-alt"></i> Security Settings
                            </a>
                            <a href="#" class="settings-link" data-panel="privacy">
                                <i class="fas fa-user-shield"></i> Privacy Settings
                            </a>
                        </nav>
                        
                        <div class="settings-content">
                            <!-- Account Settings Panel -->
                            <div class="settings-panel active" id="account-panel">
                                <h3 class="settings-title">Account Settings</h3>
                                <?php if (isset($success_message)): ?>
                                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                                <?php endif; ?>
                                <?php if (isset($error_message)): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                                <?php endif; ?>
                                <form id="account-form" method="POST">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="username">Username</label>
                                            <input type="text" id="username" value="<?php echo htmlspecialchars($patient['username']); ?>" disabled>
                                        </div>
                                        <div class="form-group">
                                            <label for="email">Email Address</label>
                                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="language">Language Preference</label>
                                        <select id="language" name="language">
                                            <option value="en" <?php echo ($patient['language_preference'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                                            <option value="es" <?php echo ($patient['language_preference'] ?? 'en') === 'es' ? 'selected' : ''; ?>>Spanish</option>
                                            <option value="fr" <?php echo ($patient['language_preference'] ?? 'en') === 'fr' ? 'selected' : ''; ?>>French</option>
                                            <option value="de" <?php echo ($patient['language_preference'] ?? 'en') === 'de' ? 'selected' : ''; ?>>German</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="timezone">Time Zone</label>
                                        <select id="timezone" name="timezone">
                                            <option value="et" <?php echo ($patient['timezone'] ?? 'et') === 'et' ? 'selected' : ''; ?>>Eastern Time (ET)</option>
                                            <option value="ct" <?php echo ($patient['timezone'] ?? 'et') === 'ct' ? 'selected' : ''; ?>>Central Time (CT)</option>
                                            <option value="mt" <?php echo ($patient['timezone'] ?? 'et') === 'mt' ? 'selected' : ''; ?>>Mountain Time (MT)</option>
                                            <option value="pt" <?php echo ($patient['timezone'] ?? 'et') === 'pt' ? 'selected' : ''; ?>>Pacific Time (PT)</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="update_account" class="save-btn">Save Changes</button>
                                </form>
                            </div>
                            
                            <!-- Notification Settings Panel -->
                            <div class="settings-panel" id="notifications-panel">
                                <h3 class="settings-title">Notification Preferences</h3>
                                <?php if (isset($success_message)): ?>
                                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                                <?php endif; ?>
                                <?php if (isset($error_message)): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                                <?php endif; ?>
                                <form method="POST">
                                <div class="notification-preferences">
                                    <div class="notification-option">
                                        <div class="notification-info">
                                            <div class="notification-title">Appointment Reminders</div>
                                            <div class="notification-description">Receive notifications about upcoming appointments</div>
                                        </div>
                                        <label class="toggle-switch">
                                                <input type="checkbox" name="appointment_reminders" <?php echo ($patient['appointment_reminders'] ?? true) ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                
                                <div class="notification-option">
                                    <div class="notification-info">
                                                <div class="notification-title">Test Results</div>
                                                <div class="notification-description">Get notified when new test results are available</div>
                                    </div>
                                    <label class="toggle-switch">
                                                <input type="checkbox" name="test_result_notifications" <?php echo ($patient['test_result_notifications'] ?? true) ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                
                                <div class="notification-option">
                                    <div class="notification-info">
                                                <div class="notification-title">Doctor Messages</div>
                                                <div class="notification-description">Receive notifications when doctors send you messages</div>
                                    </div>
                                    <label class="toggle-switch">
                                                <input type="checkbox" name="doctor_messages" <?php echo ($patient['doctor_messages'] ?? true) ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                        
                                    <div class="notification-option">
                                        <div class="notification-info">
                                                <div class="notification-title">Medication Reminders</div>
                                                <div class="notification-description">Get reminded to take your medications</div>
                                        </div>
                                        <label class="toggle-switch">
                                                <input type="checkbox" name="medication_reminders" <?php echo ($patient['medication_reminders'] ?? true) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                        
                                    <div class="notification-option">
                                        <div class="notification-info">
                                                <div class="notification-title">Health Tips & Updates</div>
                                                <div class="notification-description">Receive health tips and hospital updates</div>
                                        </div>
                                        <label class="toggle-switch">
                                                <input type="checkbox" name="health_tips" <?php echo ($patient['health_tips'] ?? true) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                        
                                    <div class="notification-option">
                                        <div class="notification-info">
                                                <div class="notification-title">Data Usage for Research</div>
                                                <div class="notification-description">Allow anonymized data to be used for medical research</div>
                                        </div>
                                        <label class="toggle-switch">
                                                <input type="checkbox" name="data_usage_research" <?php echo ($patient['data_usage_research'] ?? false) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                        
                                    <div class="notification-option">
                                        <div class="notification-info">
                                                <div class="notification-title">Profile Visibility</div>
                                                <div class="notification-description">Show your profile to other hospital staff</div>
                                        </div>
                                        <label class="toggle-switch">
                                                <input type="checkbox" name="profile_visibility" <?php echo ($patient['profile_visibility'] ?? true) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                    <button type="submit" name="update_notifications" class="save-btn" style="margin-top: 1.5rem;">Save Preferences</button>
                                </form>
                            </div>
                            
                            <!-- Security Settings Panel -->
                            <div class="settings-panel" id="security-panel">
                                <h3 class="settings-title">Security Settings</h3>
                                <?php if (isset($success_message)): ?>
                                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                                <?php endif; ?>
                                <?php if (isset($error_message)): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                                <?php endif; ?>
                                <form method="POST">
                                <div class="security-card">
                                    <div class="security-info">
                                        <div class="security-title">Two-Factor Authentication</div>
                                        <div class="security-description">Add an extra layer of security to your account</div>
                                    </div>
                                    <div class="security-status">
                                            <span class="<?php echo ($patient['two_factor_auth'] ?? false) ? 'security-enabled' : 'security-disabled'; ?>">
                                                <?php echo ($patient['two_factor_auth'] ?? false) ? 'Enabled' : 'Disabled'; ?>
                                            </span>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="two_factor_auth" <?php echo ($patient['two_factor_auth'] ?? false) ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                    </div>
                                </div>
                                    <button type="submit" name="update_security" class="save-btn" style="margin-top: 1.5rem;">Save Security Settings</button>
                                </form>
                            </div>
                            
                            <!-- Privacy Settings Panel -->
                            <div class="settings-panel" id="privacy-panel">
                                <h3 class="settings-title">Privacy Settings</h3>
                                <?php if (isset($success_message)): ?>
                                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                                <?php endif; ?>
                                <?php if (isset($error_message)): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                                <?php endif; ?>
                                <form method="POST">
                                <div class="notification-option">
                                    <div class="notification-info">
                                        <div class="notification-title">Share Medical Records with Doctors</div>
                                        <div class="notification-description">Allow doctors to access your medical records</div>
                                    </div>
                                    <label class="toggle-switch">
                                            <input type="checkbox" name="share_medical_records" <?php echo ($patient['share_medical_records'] ?? true) ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                
                                <div class="notification-option">
                                    <div class="notification-info">
                                        <div class="notification-title">Allow Email Communications</div>
                                        <div class="notification-description">Receive emails about your health and appointments</div>
                                    </div>
                                    <label class="toggle-switch">
                                            <input type="checkbox" name="allow_email_communications" <?php echo ($patient['allow_email_communications'] ?? true) ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                
                                <div class="notification-option">
                                    <div class="notification-info">
                                        <div class="notification-title">Allow SMS Communications</div>
                                        <div class="notification-description">Receive text messages about your health and appointments</div>
                                    </div>
                                    <label class="toggle-switch">
                                            <input type="checkbox" name="allow_sms_communications" <?php echo ($patient['allow_sms_communications'] ?? true) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                </div>
                                
                                    <button type="submit" name="update_privacy" class="save-btn" style="margin-top: 1.5rem;">Save Privacy Settings</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add this modal HTML before the closing body tag -->
    <div id="editPersonalInfoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Personal Information</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editPersonalInfoForm" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_first_name">First Name</label>
                            <input type="text" id="edit_first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_last_name">Last Name</label>
                            <input type="text" id="edit_last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_date_of_birth">Date of Birth</label>
                            <input type="date" id="edit_date_of_birth" name="date_of_birth" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_gender">Gender</label>
                            <select id="edit_gender" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_blood_type">Blood Type</label>
                            <select id="edit_blood_type" name="blood_type">
                                <option value="">Select Blood Type</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_national_id">National ID</label>
                            <input type="text" id="edit_national_id" name="national_id" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn">Cancel</button>
                        <button type="submit" class="save-btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add this modal HTML before the closing body tag, after the personal info modal -->
    <div id="editContactInfoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Contact Information</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editContactInfoForm" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_email">Email Address</label>
                            <input type="email" id="edit_email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_phone">Phone Number</label>
                            <input type="tel" id="edit_phone" name="phone" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_address">Address</label>
                        <input type="text" id="edit_address" name="address" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_city">City</label>
                            <input type="text" id="edit_city" name="city" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_state">State</label>
                            <input type="text" id="edit_state" name="state" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_zip_code">ZIP Code</label>
                        <input type="text" id="edit_zip_code" name="zip_code" required>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn">Cancel</button>
                        <button type="submit" class="save-btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add this modal HTML before the closing body tag, after the contact info modal -->
    <div id="editEmergencyInfoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Emergency Contact Information</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editEmergencyInfoForm" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_emergency_contact">Emergency Contact Name</label>
                            <input type="text" id="edit_emergency_contact" name="emergency_contact" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_emergency_phone">Emergency Contact Phone</label>
                            <input type="tel" id="edit_emergency_phone" name="emergency_phone" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn">Cancel</button>
                        <button type="submit" class="save-btn">Save Changes</button>
                    </div>
                </form>
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
            
            // Profile tabs functionality
            const profileTabs = document.querySelectorAll('.profile-tab');
            const profileContents = document.querySelectorAll('.profile-content');
            
            profileTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs and contents
                    profileTabs.forEach(t => t.classList.remove('active'));
                    profileContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // Settings navigation functionality
            const settingsLinks = document.querySelectorAll('.settings-link');
            const settingsPanels = document.querySelectorAll('.settings-panel');
            const settingsDropdown = document.querySelector('.settings-dropdown');
            
            settingsLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all links and panels
                    settingsLinks.forEach(l => l.classList.remove('active'));
                    settingsPanels.forEach(panel => panel.classList.remove('active'));
                    
                    // Add active class to clicked link and corresponding panel
                    this.classList.add('active');
                    const panelId = this.getAttribute('data-panel') + '-panel';
                    document.getElementById(panelId).classList.add('active');
                    
                    // Update mobile dropdown
                    if (settingsDropdown) {
                        const panelName = this.getAttribute('data-panel');
                        for (let i = 0; i < settingsDropdown.options.length; i++) {
                            if (settingsDropdown.options[i].value === panelName) {
                                settingsDropdown.selectedIndex = i;
                                break;
                            }
                        }
                    }
                });
            });
            
            // Mobile settings dropdown functionality
            if (settingsDropdown) {
                settingsDropdown.addEventListener('change', function() {
                    const selectedValue = this.value;
                    
                    // Find and click the corresponding link
                    settingsLinks.forEach(link => {
                        if (link.getAttribute('data-panel') === selectedValue) {
                            link.click();
                        }
                    });
                });
            }
            
            // Handle form submissions
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const formData = new FormData(this);
                    const submitButton = form.querySelector('button[type="submit"]');
                    const originalText = submitButton.textContent;
                    
                    // Disable submit button and show loading state
                    submitButton.disabled = true;
                    submitButton.textContent = 'Saving...';
                    
                    // Submit form using fetch
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(html => {
                        // Re-enable submit button and restore original text
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                        
                        // Show success message
                        const successMessage = document.createElement('div');
                        successMessage.className = 'alert alert-success';
                        successMessage.textContent = 'Settings updated successfully!';
                        form.insertBefore(successMessage, form.firstChild);
                        
                        // Remove success message after 3 seconds
                        setTimeout(() => {
                            successMessage.remove();
                        }, 3000);
                    })
                    .catch(error => {
                        // Re-enable submit button and restore original text
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                        
                        // Show error message
                        const errorMessage = document.createElement('div');
                        errorMessage.className = 'alert alert-danger';
                        errorMessage.textContent = 'Failed to update settings. Please try again.';
                        form.insertBefore(errorMessage, form.firstChild);
                        
                        // Remove error message after 3 seconds
                        setTimeout(() => {
                            errorMessage.remove();
                        }, 3000);
                    });
                    
                    e.preventDefault();
                });
            });
            
            // Record action buttons handler
            const recordButtons = document.querySelectorAll('.record-btn');
            recordButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const actionType = this.classList.contains('view-btn') ? 'view' : 
                                      this.classList.contains('download-btn') ? 'download' : 
                                      this.classList.contains('delete-btn') ? 'delete' : 'action';
                    
                    // Handle different button types in security panel
                    if (this.closest('#security-panel')) {
                        const title = this.closest('.security-card').querySelector('.security-title').textContent;
                        alert(`${title} ${actionType} functionality will be implemented in a future update.`);
                        return;
                    }
                    
                    // Handle record action buttons
                    if (this.closest('tr')) {
                        const fileName = this.closest('tr').querySelector('td:first-child span').textContent;
                        
                        if (actionType === 'view') {
                            alert(`Viewing ${fileName}`);
                        } else if (actionType === 'download') {
                            alert(`Downloading ${fileName}`);
                        } else if (actionType === 'delete') {
                            if (confirm(`Are you sure you want to delete ${fileName}?`)) {
                                this.closest('tr').remove();
                                alert(`${fileName} deleted successfully.`);
                            }
                        }
                    }
                });
            });
            
            // Upload button handler
            const uploadBtn = document.querySelector('.upload-btn');
            if (uploadBtn) {
                uploadBtn.addEventListener('click', function() {
                    alert('Record upload functionality will be implemented in a future update.');
                });
            }
            
            // Add these functions to handle record actions
            function viewTestResult(id) {
                alert('Viewing test result ' + id);
            }
            
            function downloadTestResult(id) {
                alert('Downloading test result ' + id);
            }
            
            function viewAppointment(id) {
                alert('Viewing appointment ' + id);
            }
            
            function cancelAppointment(id) {
                if (confirm('Are you sure you want to cancel this appointment?')) {
                    alert('Appointment ' + id + ' cancelled successfully.');
                }
            }
            
            function viewHealthMetric(id) {
                alert('Viewing health metric ' + id);
            }

            // Add this to your existing JavaScript
            function openEditPersonalInfoModal() {
                const modal = document.getElementById('editPersonalInfoModal');
                const form = document.getElementById('editPersonalInfoForm');
                
                // Populate form with current values
                form.querySelector('#edit_first_name').value = '<?php echo htmlspecialchars($patient['first_name']); ?>';
                form.querySelector('#edit_last_name').value = '<?php echo htmlspecialchars($patient['last_name']); ?>';
                form.querySelector('#edit_date_of_birth').value = '<?php echo $patient['date_of_birth']; ?>';
                form.querySelector('#edit_gender').value = '<?php echo htmlspecialchars($patient['gender']); ?>';
                form.querySelector('#edit_blood_type').value = '<?php echo htmlspecialchars($patient['blood_type'] ?? ''); ?>';
                form.querySelector('#edit_national_id').value = '<?php echo htmlspecialchars($patient['national_id']); ?>';
                
                modal.style.display = 'block';
            }

            function closeModal() {
                const modal = document.getElementById('editPersonalInfoModal');
                modal.style.display = 'none';
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('editPersonalInfoModal');
                if (event.target === modal) {
                    closeModal();
                }
            }

            // Handle form submission
            document.getElementById('editPersonalInfoForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('update_personal_info', '1');
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    // Reload the page to show updated information
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update personal information. Please try again.');
                });
            });

            // Update the edit button click handler
            document.querySelector('.section-edit-link').addEventListener('click', function(e) {
                e.preventDefault();
                openEditPersonalInfoModal();
            });

            // Add event listeners for modal close buttons
            document.querySelector('.close').addEventListener('click', closeModal);
            document.querySelector('.cancel-btn').addEventListener('click', closeModal);

            // Add these functions to your existing JavaScript
            function openEditContactInfoModal() {
                const modal = document.getElementById('editContactInfoModal');
                const form = document.getElementById('editContactInfoForm');
                
                // Populate form with current values
                form.querySelector('#edit_email').value = '<?php echo htmlspecialchars($patient['email']); ?>';
                form.querySelector('#edit_phone').value = '<?php echo htmlspecialchars($patient['phone']); ?>';
                form.querySelector('#edit_address').value = '<?php echo htmlspecialchars($patient['address']); ?>';
                form.querySelector('#edit_city').value = '<?php echo htmlspecialchars($patient['city']); ?>';
                form.querySelector('#edit_state').value = '<?php echo htmlspecialchars($patient['state']); ?>';
                form.querySelector('#edit_zip_code').value = '<?php echo htmlspecialchars($patient['zip_code']); ?>';
                
                modal.style.display = 'block';
            }

            function closeContactModal() {
                const modal = document.getElementById('editContactInfoModal');
                modal.style.display = 'none';
            }

            // Update the window.onclick function to handle both modals
            window.onclick = function(event) {
                const personalInfoModal = document.getElementById('editPersonalInfoModal');
                const contactInfoModal = document.getElementById('editContactInfoModal');
                if (event.target === personalInfoModal) {
                    closeModal();
                }
                if (event.target === contactInfoModal) {
                    closeContactModal();
                }
            }

            // Handle contact info form submission
            document.getElementById('editContactInfoForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('update_contact_info', '1');
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    // Reload the page to show updated information
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update contact information. Please try again.');
                });
            });

            // Add event listeners for contact info modal close buttons
            document.querySelectorAll('.close').forEach(closeBtn => {
                closeBtn.addEventListener('click', function() {
                    if (this.closest('#editContactInfoModal')) {
                        closeContactModal();
                    } else {
                        closeModal();
                    }
                });
            });

            // Update the contact info edit button click handler
            document.querySelectorAll('.section-edit-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (this.closest('.info-section').querySelector('.info-label').textContent === 'Email Address') {
                        openEditContactInfoModal();
                    }
                });
            });

            // Add these functions to your existing JavaScript, inside the DOMContentLoaded event listener
            function openEditEmergencyInfoModal() {
                const modal = document.getElementById('editEmergencyInfoModal');
                const form = document.getElementById('editEmergencyInfoForm');
                
                // Populate form with current values
                form.querySelector('#edit_emergency_contact').value = '<?php echo htmlspecialchars($patient['emergency_contact'] ?? ''); ?>';
                form.querySelector('#edit_emergency_phone').value = '<?php echo htmlspecialchars($patient['emergency_phone'] ?? ''); ?>';
                
                modal.style.display = 'block';
            }

            function closeEmergencyModal() {
                const modal = document.getElementById('editEmergencyInfoModal');
                modal.style.display = 'none';
            }

            // Update the window.onclick function to handle all modals
            window.onclick = function(event) {
                const personalInfoModal = document.getElementById('editPersonalInfoModal');
                const contactInfoModal = document.getElementById('editContactInfoModal');
                const emergencyInfoModal = document.getElementById('editEmergencyInfoModal');
                if (event.target === personalInfoModal) {
                    closeModal();
                }
                if (event.target === contactInfoModal) {
                    closeContactModal();
                }
                if (event.target === emergencyInfoModal) {
                    closeEmergencyModal();
                }
            }

            // Handle emergency info form submission
            document.getElementById('editEmergencyInfoForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('update_emergency_info', '1');
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    // Reload the page to show updated information
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update emergency contact information. Please try again.');
                });
            });

            // Update the emergency info edit button click handler
            document.querySelectorAll('.section-edit-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (this.closest('.info-section').querySelector('.info-label').textContent === 'Contact Name') {
                        openEditEmergencyInfoModal();
                    }
                });
            });

            // Add event listeners for all cancel buttons
            document.querySelectorAll('.cancel-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const modal = this.closest('.modal');
                    if (modal) {
                        modal.style.display = 'none';
                    }
                });
            });

            // Medical Records Filtering
            const filterDropdown = document.querySelector('.filter-dropdown');
            const recordsTable = document.querySelector('.records-table tbody');
            const allRows = Array.from(recordsTable.querySelectorAll('tr'));

            if (filterDropdown) {
                filterDropdown.addEventListener('change', function() {
                    const selectedValue = this.value;
                    
                    // Show/hide rows based on selected filter
                    allRows.forEach(row => {
                        const recordType = row.querySelector('td:first-child span').textContent.toLowerCase();
                        const isVisible = selectedValue === 'all' || 
                                        (selectedValue === 'test_results' && recordType.includes('test result')) ||
                                        (selectedValue === 'appointments' && recordType.includes('appointment')) ||
                                        (selectedValue === 'health_metrics' && recordType.includes('health metric'));
                        
                        row.style.display = isVisible ? '' : 'none';
                    });

                    // Update table message if no records are visible
                    const visibleRows = allRows.filter(row => row.style.display !== 'none');
                    if (visibleRows.length === 0) {
                        const noRecordsRow = document.createElement('tr');
                        noRecordsRow.innerHTML = `
                            <td colspan="5" style="text-align: center; padding: 2rem;">
                                <div style="color: #666; font-style: italic;">
                                    No records found for the selected filter.
                                </div>
                            </td>
                        `;
                        recordsTable.appendChild(noRecordsRow);
                    } else {
                        // Remove the "no records" message if it exists
                        const noRecordsMessage = recordsTable.querySelector('tr:last-child td[colspan="5"]');
                        if (noRecordsMessage) {
                            noRecordsMessage.closest('tr').remove();
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
                                        