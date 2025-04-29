<?php
session_start();
require_once '../config/db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Unauthorized access');
}

try {
    // Get filter parameters
    $dateRange = $_GET['dateRange'] ?? '';
    $logType = $_GET['logType'] ?? '';
    $userFilter = $_GET['userFilter'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // Build query
    $query = "
        SELECT l.*, u.full_name, u.role
        FROM activity_logs l
        JOIN users u ON l.user_id = u.id
        WHERE 1=1
    ";
    $params = [];
    
    // Apply date range filter
    switch ($dateRange) {
        case 'today':
            $query .= " AND DATE(l.created_at) = CURDATE()";
            break;
        case 'yesterday':
            $query .= " AND DATE(l.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'last7':
            $query .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'last30':
            $query .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
        case 'custom':
            if (!empty($_GET['startDate']) && !empty($_GET['endDate'])) {
                $query .= " AND DATE(l.created_at) BETWEEN ? AND ?";
                $params[] = $_GET['startDate'];
                $params[] = $_GET['endDate'];
            }
            break;
    }
    
    // Apply log type filter
    if (!empty($logType)) {
        $query .= " AND l.action = ?";
        $params[] = $logType;
    }
    
    // Apply user role filter
    if (!empty($userFilter)) {
        $query .= " AND u.role = ?";
        $params[] = $userFilter;
    }
    
    // Apply search filter
    if (!empty($search)) {
        $query .= " AND (l.details LIKE ? OR u.full_name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $query .= " ORDER BY l.created_at DESC";
    
    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="activity_logs_' . date('Y-m-d') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, [
        'ID',
        'Timestamp',
        'Type',
        'User',
        'Role',
        'IP Address',
        'Activity Details'
    ]);
    
    // Add data rows
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['id'],
            $log['created_at'],
            $log['action'],
            $log['full_name'],
            $log['role'],
            $log['ip_address'],
            $log['details']
        ]);
    }
    
    fclose($output);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Error exporting logs: ' . $e->getMessage());
} 