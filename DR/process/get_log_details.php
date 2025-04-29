<?php
session_start();
require_once '../config/db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if log ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Log ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT l.*, u.full_name, u.role
        FROM activity_logs l
        JOIN users u ON l.user_id = u.id
        WHERE l.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($log) {
        echo json_encode(['success' => true, 'log' => $log]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Log not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 