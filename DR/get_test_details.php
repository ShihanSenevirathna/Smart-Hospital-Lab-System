<?php
session_start();
require_once 'config/db_connection.php';

// Check if user is logged in and is lab staff
if (!isset($_SESSION['is_lab_staff']) || $_SESSION['is_lab_staff'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if test ID is provided
if (!isset($_GET['test_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Test ID is required']);
    exit;
}

try {
    // Fetch test details
    $sql = "SELECT tr.*, 
            p.first_name, p.last_name, p.id as patient_id,
            d.first_name as doctor_first_name, d.last_name as doctor_last_name,
            u.full_name as technician_name
            FROM test_results tr
            LEFT JOIN patients p ON tr.patient_id = p.id
            LEFT JOIN doctors d ON tr.doctor_id = d.id
            LEFT JOIN users u ON tr.lab_staff_id = u.id
            WHERE tr.id = :test_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':test_id' => $_GET['test_id']]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$test) {
        http_response_code(404);
        echo json_encode(['error' => 'Test not found']);
        exit;
    }
    
    // Return test details as JSON
    header('Content-Type: application/json');
    echo json_encode($test);
    
} catch (PDOException $e) {
    error_log("Error in get_test_details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
}
?> 