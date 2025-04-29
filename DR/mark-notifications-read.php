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
    // Update all unread notifications for this doctor to read
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE doctor_id = ? AND is_read = 0
    ");
    $stmt->execute([$doctor_id]);

    // Log the action
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details) 
        VALUES (?, 'mark_notifications_read', 'Marked all notifications as read')
    ");
    $stmt->execute([$doctor_id]);

    // Redirect back to dashboard with success message
    $_SESSION['success_message'] = "All notifications have been marked as read.";
    header('Location: doctor-dashboard.php');
    exit;

} catch (PDOException $e) {
    error_log("Error marking notifications as read: " . $e->getMessage());
    $_SESSION['error_message'] = "Failed to mark notifications as read. Please try again.";
    header('Location: doctor-dashboard.php');
    exit;
}
?> 