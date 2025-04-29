<?php
session_start();
require_once 'config/db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Fetch dashboard statistics
try {
    // Total Users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Today's Test Orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM test_results WHERE DATE(order_date) = CURDATE()");
    $todaySamples = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Active Tests
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM test_results WHERE status IN ('pending', 'processing')");
    $activeSamples = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // System Alerts (Failed Login Attempts)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM activity_logs WHERE action = 'failed_login' AND DATE(created_at) = CURDATE()");
    $systemAlerts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Recent Activities
    $stmt = $pdo->query("
        SELECT al.*, u.full_name 
        FROM activity_logs al 
        JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 4
    ");
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pending Tests
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM test_results WHERE status = 'pending'");
    $pendingSamples = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total Patients
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'patient'");
    $totalPatients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

} catch(PDOException $e) {
    error_log("Database error in admin_dashboard.php: " . $e->getMessage());
    // Set default values in case of error
    $totalUsers = 0;
    $todaySamples = 0;
    $activeSamples = 0;
    $systemAlerts = 0;
    $recentActivities = [];
    $pendingSamples = 0;
    $totalPatients = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Hospital Laboratory System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #2ecc71;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #1abc9c;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }
        
        body {
            background-color: var(--light-color);
            min-height: 100vh;
            display: flex;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header i {
            font-size: 1.8rem;
            margin-right: 0.8rem;
            color: var(--secondary-color);
        }
        
        .sidebar-header h2 {
            font-size: 1.2rem;
            font-weight: 500;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .sidebar-menu h3 {
            padding: 0 1.5rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.1rem;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 0.8rem;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .menu-item.active {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .menu-item i {
            font-size: 1.1rem;
            margin-right: 0.8rem;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            color: var(--primary-color);
            font-size: 1.8rem;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
        }
        
        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 0.8rem;
            object-fit: cover;
        }
        
        .user-info p {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .user-info span {
            color: var(--dark-color);
            font-size: 0.85rem;
        }
        
        /* Dashboard Stats */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--secondary-color);
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 1rem;
            font-size: 1.5rem;
        }
        
        .stat-card:nth-child(2) .stat-icon {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--accent-color);
        }
        
        .stat-card:nth-child(3) .stat-icon {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }
        
        .stat-card:nth-child(4) .stat-icon {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }
        
        .stat-details h3 {
            color: var(--dark-color);
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        
        .stat-details p {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        /* Activity Summary */
        .activity-summary {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .card-title {
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .card-actions a {
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .card-actions a:hover {
            text-decoration: underline;
        }
        
        /* Recent Activities */
        .activity-item {
            display: flex;
            margin-bottom: 1.2rem;
            padding-bottom: 1.2rem;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .activity-icon {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--secondary-color);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 1rem;
        }
        
        .user-activity .activity-icon {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--accent-color);
        }
        
        .system-activity .activity-icon {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }
        
        .activity-details h4 {
            color: var(--dark-color);
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0.3rem;
        }
        
        .activity-details p {
            color: var(--primary-color);
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        
        .activity-time {
            color: #888;
            font-size: 0.8rem;
        }
        
        /* System Status */
        .system-status {
            margin-bottom: 1rem;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .status-label {
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        .status-value {
            background-color: var(--accent-color);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .status-value.warning {
            background-color: var(--warning-color);
        }
        
        .status-value.danger {
            background-color: var(--danger-color);
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.8rem;
        }
        
        .action-btn {
            background-color: var(--light-color);
            color: var(--primary-color);
            border: none;
            border-radius: 5px;
            padding: 0.8rem;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-btn i {
            margin-right: 0.5rem;
        }
        
        .action-btn:hover {
            background-color: #ddd;
        }
        
        @media (max-width: 991px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header h2 {
                display: none;
            }
            
            .sidebar-header {
                justify-content: center;
            }
            
            .sidebar-header i {
                margin-right: 0;
            }
            
            .sidebar-menu h3 {
                display: none;
            }
            
            .menu-item span {
                display: none;
            }
            
            .menu-item i {
                margin-right: 0;
            }
            
            .menu-item {
                justify-content: center;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .activity-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-hospital"></i>
            <h2>SHLS Admin</h2>
        </div>
        
        <div class="sidebar-menu">
            <h3>Main Menu</h3>
            <a href="admin_dashboard.php" class="menu-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="user_management.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>User Management</span>
            </a>
            <a href="system_configuration.php" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>System Configuration</span>
            </a>
            <a href="reports_analytics.php" class="menu-item">
                <i class="fas fa-chart-bar"></i>
                <span>Reports & Analytics</span>
            </a>
            <a href="system_activity_logs.php" class="menu-item">
                <i class="fas fa-history"></i>
                <span>Activity Logs</span>
            </a>
            
            <h3>Other</h3>
            <a href="#" class="menu-item">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-question-circle"></i>
                <span>Help & Support</span>
            </a>
            <a href="javascript:void(0)" onclick="confirmLogout()" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1>Admin Dashboard</h1>
            
            <div class="user-profile">
                <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="Admin User">
                <div class="user-info">
                    <p><?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
                    <span>System Administrator</span>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Stats -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details">
                    <h3>Total Users</h3>
                    <p><?php echo $totalUsers; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-flask"></i>
                </div>
                <div class="stat-details">
                    <h3>Today's Test Orders</h3>
                    <p><?php echo $todaySamples; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-details">
                    <h3>Active Tests</h3>
                    <p><?php echo $activeSamples; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-details">
                    <h3>System Alerts</h3>
                    <p><?php echo $systemAlerts; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Activity Summary -->
        <div class="activity-summary">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Activities</h2>
                    <div class="card-actions">
                        <a href="system_activity_logs.php">View All</a>
                    </div>
                </div>
                
                <div class="activity-list">
                    <?php foreach($recentActivities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-<?php echo getActivityIcon($activity['action']); ?>"></i>
                        </div>
                        <div class="activity-details">
                            <h4><?php echo htmlspecialchars($activity['action']); ?></h4>
                            <p><?php echo htmlspecialchars($activity['full_name'] . ' - ' . $activity['details']); ?></p>
                            <span class="activity-time"><?php echo timeAgo($activity['created_at']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- System Status & Quick Actions -->
            <div>
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h2 class="card-title">System Status</h2>
                    </div>
                    
                    <div class="system-status">
                        <div class="status-item">
                            <span class="status-label">Pending Tests</span>
                            <span class="status-value <?php echo $pendingSamples > 10 ? 'warning' : ''; ?>">
                                <?php echo $pendingSamples; ?>
                            </span>
                        </div>
                        
                        <div class="status-item">
                            <span class="status-label">Total Patients</span>
                            <span class="status-value"><?php echo $totalPatients; ?></span>
                        </div>
                        
                        <div class="status-item">
                            <span class="status-label">Database Status</span>
                            <span class="status-value">Operational</span>
                        </div>
                        
                        <div class="status-item">
                            <span class="status-label">Failed Logins</span>
                            <span class="status-value <?php echo $systemAlerts > 5 ? 'danger' : ''; ?>">
                                <?php echo $systemAlerts; ?> Today
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Quick Actions</h2>
                    </div>
                    
                    <div class="quick-actions">
                        <button class="action-btn" onclick="backupDatabase()">
                            <i class="fas fa-database"></i>
                            Backup Now
                        </button>
                        
                        <button class="action-btn" onclick="clearCache()">
                            <i class="fas fa-trash"></i>
                            Clear Cache
                        </button>
                        
                        <button class="action-btn" onclick="generateReport()">
                            <i class="fas fa-envelope"></i>
                            Email Report
                        </button>
                        
                        <button class="action-btn" onclick="updateSystem()">
                            <i class="fas fa-sync"></i>
                            System Update
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function performAction(action) {
            // Show loading state
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            button.disabled = true;

            // Make AJAX call
            fetch('process/quick_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=' + action
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (action === 'backup') {
                        // Show detailed success message for backup
                        Swal.fire({
                            title: 'Backup Successful!',
                            html: `
                                <div style="text-align: left;">
                                    <p>${data.message}</p>
                                    <p><strong>Backup File:</strong> ${data.backup_file}</p>
                                    <p><strong>Size:</strong> ${(data.backup_size / 1024).toFixed(2)} KB</p>
                                </div>
                            `,
                            icon: 'success',
                            confirmButtonText: 'OK',
                            timer: 5000,
                            timerProgressBar: true
                        });
                    } else {
                        // Show success message for other actions
                        Swal.fire({
                            title: 'Success',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'OK',
                            timer: 3000,
                            timerProgressBar: true
                        });
                    }
                } else {
                    // Show error message
                    Swal.fire({
                        title: 'Error',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while processing your request.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            })
            .finally(() => {
                // Reset button state
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }

        function backupDatabase() {
            // Show confirmation dialog before backup
            Swal.fire({
                title: 'Confirm Backup',
                text: 'Are you sure you want to create a database backup?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, create backup',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    performAction('backup');
                }
            });
        }

        function clearCache() {
            performAction('clear_cache');
        }

        function generateReport() {
            performAction('generate_report');
        }

        function updateSystem() {
            performAction('update_system');
        }

        // Interactive logout confirmation
        function confirmLogout() {
            Swal.fire({
                title: 'Logout Confirmation',
                text: 'Are you sure you want to logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'process/admin_logout.php';
                }
            });
        }
    </script>
</body>
</html>

<?php
// Helper functions
function getActivityIcon($action) {
    switch(strtolower($action)) {
        case 'login':
            return 'sign-in-alt';
        case 'logout':
            return 'sign-out-alt';
        case 'create':
            return 'user-plus';
        case 'update':
            return 'edit';
        case 'delete':
            return 'trash';
        default:
            return 'info-circle';
    }
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return "Just now";
    } elseif ($difference < 3600) {
        return floor($difference/60) . " minutes ago";
    } elseif ($difference < 86400) {
        return floor($difference/3600) . " hours ago";
    } else {
        return date('M j, Y', $timestamp);
    }
}
?>