<?php
session_start();

// Hardcoded admin credentials
$admin_username = 'admin';
$admin_password = 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Check for admin/admin credentials
    if ($username === $admin_username && $password === $admin_password) {
        // Login successful
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        
        // Redirect to admin dashboard
        header("Location: admin_dashboard.php");
        exit();
    } else {
        // Login failed
        header("Location: admin_login.php?error=1");
        exit();
    }
} else {
    // If someone tries to access this file directly without POST data
    header("Location: admin_login.php");
    exit();
}
?> 