<?php
session_start();
require_once '../config/db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Clear all logs from activity_logs table
    $stmt = $pdo->prepare("TRUNCATE TABLE activity_logs");
    $stmt->execute();
    
    // Log this action
    $admin_username = $_SESSION['admin_username'];
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND role = 'admin' LIMIT 1");
    $stmt->execute([$admin_username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $user['id'],
            'clear_logs',
            'All activity logs cleared by admin',
            $_SERVER['REMOTE_ADDR']
        ]);
    }
    
    echo json_encode(['status' => 'success', 'message' => 'All logs have been cleared successfully']);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 