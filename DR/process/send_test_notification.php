<?php
session_start();
require_once '../config/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['patient_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$response = ['success' => false, 'error' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_SESSION['patient_id'];
    $type = $_POST['type'] ?? '';
    $value = $_POST['value'] ?? '';

    try {
        switch ($type) {
            case 'email':
                // Send test email
                // In a real application, you would send an actual email here
                // For now, we'll just simulate success
                $response = [
                    'success' => true,
                    'message' => 'Test email sent successfully to ' . $value
                ];
                break;

            case 'sms':
                // Send test SMS
                // In a real application, you would send an actual SMS here
                // For now, we'll just simulate success
                $response = [
                    'success' => true,
                    'message' => 'Test SMS sent successfully to ' . $value
                ];
                break;

            default:
                $response = ['success' => false, 'error' => 'Invalid notification type'];
                break;
        }
    } catch (Exception $e) {
        error_log("Error in send_test_notification.php: " . $e->getMessage());
        $response = ['success' => false, 'error' => 'Failed to send test notification'];
    }
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response); 