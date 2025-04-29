<?php
session_start();

// Log the logout activity
if (isset($_SESSION['doctor_id'])) {
    require_once '../config/db_connection.php';
    
    try {
        // Log the logout activity in activity_logs table
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs 
            (user_id, activity_type, description, user_role) 
            VALUES (?, 'logout', 'Doctor logged out', 'doctor')
        ");
        $stmt->execute([$_SESSION['doctor_id']]);
    } catch (PDOException $e) {
        error_log("Error logging logout activity: " . $e->getMessage());
    }
}

// Destroy all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page with a logout message
header("Location: ../doctor-login.php?logout=success");
exit();
?> 