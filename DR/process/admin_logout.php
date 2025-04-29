<?php
session_start();
require_once '../config/db_connection.php';

// Check if admin is logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // Get admin username for logging
    $admin_username = $_SESSION['admin_username'] ?? '';
    
    // Log the logout action
    if (!empty($admin_username)) {
        try {
            // Get admin user ID
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND role = 'admin' LIMIT 1");
            $stmt->execute([$admin_username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Record logout in activity logs
                $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $user['id'],
                    'logout',
                    'Admin user logged out from the system',
                    $_SERVER['REMOTE_ADDR']
                ]);
            }
        } catch (PDOException $e) {
            // Log error but continue with logout
            error_log("Error logging admin logout: " . $e->getMessage());
        }
    }

    // Unset all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy the session
    session_destroy();
}

// Redirect to admin login page
header("Location: ../admin_login.php");
exit();
?> 