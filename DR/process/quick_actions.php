<?php
session_start();
require_once '../config/db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Get the action from POST request
$action = $_POST['action'] ?? '';

if (empty($action)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'No action specified']);
    exit();
}

try {
    switch ($action) {
        case 'backup':
            // Create backup directory if it doesn't exist
            $backupDir = '../backups/';
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0777, true);
            }
            
            // Generate backup filename with timestamp
            $backupFile = $backupDir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            
            // Get database configuration from db_connection.php
            $dbHost = 'localhost';
            $dbUser = 'root';
            $dbPass = '';
            $dbName = 'shls_db';
            
            // Create backup command
            $command = "mysqldump --host=$dbHost --user=$dbUser --password=$dbPass $dbName > $backupFile";
            
            // Execute backup
            exec($command, $output, $returnVar);
            
            if ($returnVar === 0) {
                // Log the backup action in activity_logs
                $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, 'backup', ?, NOW())");
                $stmt->execute([
                    $_SESSION['admin_id'],
                    'Database backup created: ' . basename($backupFile)
                ]);
                
                // Also log in backup_logs table
                $stmt = $pdo->prepare("INSERT INTO backup_logs (admin_id, backup_file, backup_size, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([
                    $_SESSION['admin_id'],
                    basename($backupFile),
                    filesize($backupFile)
                ]);
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Database backup created successfully!',
                    'backup_file' => basename($backupFile),
                    'backup_size' => filesize($backupFile)
                ]);
            } else {
                throw new Exception('Failed to create backup. Please check database credentials and permissions.');
            }
            break;
            
        case 'clear_cache':
            // Clear cache directory
            $cacheDir = '../cache/';
            if (file_exists($cacheDir)) {
                array_map('unlink', glob("$cacheDir*.*"));
            }
            
            // Log the cache clear action
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'clear_cache', 'System cache cleared')");
            $stmt->execute([$_SESSION['admin_id']]);
            
            echo json_encode(['status' => 'success', 'message' => 'Cache cleared successfully']);
            break;
            
        case 'generate_report':
            // Generate system report
            $reportData = [
                'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                'total_patients' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn(),
                'total_doctors' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn(),
                'total_tests' => $pdo->query("SELECT COUNT(*) FROM test_results")->fetchColumn(),
                'pending_tests' => $pdo->query("SELECT COUNT(*) FROM test_results WHERE status = 'pending'")->fetchColumn(),
                'system_uptime' => exec('uptime'),
                'last_backup' => $pdo->query("SELECT MAX(created_at) FROM activity_logs WHERE action = 'backup'")->fetchColumn()
            ];
            
            // Save report to file
            $reportDir = '../reports/';
            if (!file_exists($reportDir)) {
                mkdir($reportDir, 0777, true);
            }
            
            $reportFile = $reportDir . 'system_report_' . date('Y-m-d_H-i-s') . '.json';
            file_put_contents($reportFile, json_encode($reportData, JSON_PRETTY_PRINT));
            
            // Log the report generation
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'generate_report', 'System report generated')");
            $stmt->execute([$_SESSION['admin_id']]);
            
            echo json_encode(['status' => 'success', 'message' => 'Report generated successfully']);
            break;
            
        case 'update_system':
            // Check for system updates
            $currentVersion = '1.0.0'; // This should be stored in a config file
            $updateAvailable = false;
            
            // Log the update check
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'update_system', 'System update check performed')");
            $stmt->execute([$_SESSION['admin_id']]);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'System update check completed',
                'current_version' => $currentVersion,
                'update_available' => $updateAvailable
            ]);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} 