<?php
session_start();
require_once 'config/db_connection.php';

// Check if patient is logged in
if (!isset($_SESSION['patient_id'])) {
    header('Location: patient_login.php');
    exit;
}

$patient_id = $_SESSION['patient_id'];

try {
    // Update all unread notifications for this patient to read
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE patient_id = ? AND is_read = 0
    ");
    $stmt->execute([$patient_id]);

    // Log the action
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details) 
        VALUES (?, 'mark_notifications_read', 'Patient marked all notifications as read')
    ");
    $stmt->execute([$patient_id]);

    // Redirect back to dashboard with success message
    $_SESSION['success_message'] = "All notifications have been marked as read.";
    header('Location: patient_dashboard.php');
    exit;

} catch (PDOException $e) {
    error_log("Error marking notifications as read: " . $e->getMessage());
    $_SESSION['error_message'] = "Failed to mark notifications as read. Please try again.";
    header('Location: patient_dashboard.php');
    exit;
}
?> 