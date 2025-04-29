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
                // Update email in notification_channels table
                $sql = "INSERT INTO notification_channels (patient_id, channel_type, channel_value, is_active) 
                        VALUES (:patient_id, 'email', :value, 1) 
                        ON DUPLICATE KEY UPDATE 
                        channel_value = :value, 
                        is_active = 1";
                $stmt = $pdo->prepare($sql);
                $success = $stmt->execute([
                    ':patient_id' => $patient_id,
                    ':value' => $value
                ]);
                
                if ($success) {
                    $response = ['success' => true];
                } else {
                    $response = ['success' => false, 'error' => 'Failed to update email'];
                }
                break;

            case 'sms':
                // Update phone number in notification_channels table
                $sql = "INSERT INTO notification_channels (patient_id, channel_type, channel_value, is_active) 
                        VALUES (:patient_id, 'sms', :value, 1) 
                        ON DUPLICATE KEY UPDATE 
                        channel_value = :value, 
                        is_active = 1";
                $stmt = $pdo->prepare($sql);
                $success = $stmt->execute([
                    ':patient_id' => $patient_id,
                    ':value' => $value
                ]);
                
                if ($success) {
                    $response = ['success' => true];
                } else {
                    $response = ['success' => false, 'error' => 'Failed to update phone number'];
                }
                break;

            case 'push':
                // Add new device for push notifications
                $device_name = $_POST['device_name'] ?? '';
                $device_type = $_POST['device_type'] ?? '';
                
                $sql = "INSERT INTO notification_channels (patient_id, channel_type, channel_value, is_active, device_info) 
                        VALUES (:patient_id, 'push', :device_name, 1, :device_info)";
                $stmt = $pdo->prepare($sql);
                $success = $stmt->execute([
                    ':patient_id' => $patient_id,
                    ':device_name' => $device_name,
                    ':device_info' => json_encode(['type' => $device_type])
                ]);
                
                if ($success) {
                    $response = ['success' => true];
                } else {
                    $response = ['success' => false, 'error' => 'Failed to add device'];
                }
                break;

            default:
                $response = ['success' => false, 'error' => 'Invalid notification type'];
                break;
        }
    } catch (PDOException $e) {
        error_log("Database error in update_notification_settings.php: " . $e->getMessage());
        $response = ['success' => false, 'error' => 'Database error occurred'];
    }
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response); 