<?php
session_start();
require_once 'config/db_connection.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header('Location: doctor-login.php');
    exit;
}

$doctor_id = $_SESSION['doctor_id'];

try {
    // Log the logout action
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details) 
        VALUES (?, 'logout', 'Doctor logged out of the system')
    ");
    $stmt->execute([$doctor_id]);

    // Update last login time
    $stmt = $pdo->prepare("
        UPDATE users 
        SET last_login = CURRENT_TIMESTAMP 
        WHERE id = ? AND role = 'doctor'
    ");
    $stmt->execute([$doctor_id]);

    // Clear all session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Redirect to login page with success message
    $_SESSION['success_message'] = "You have been successfully logged out.";
    header('Location: doctor-login.php');
    exit;

} catch (PDOException $e) {
    error_log("Error during logout: " . $e->getMessage());
    // Even if there's an error, we still want to log the user out
    $_SESSION = array();
    session_destroy();
    header('Location: doctor-login.php');
    exit;
}
?> 