<?php
session_start();
require_once 'config/db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Get filter parameters
$dateRange = $_GET['dateRange'] ?? 'last7';
$logType = $_GET['logType'] ?? '';
$userFilter = $_GET['userFilter'] ?? '';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build the query
$query = "
    SELECT al.*, u.full_name, u.role
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE 1=1
";

$params = [];

// Apply date range filter
switch($dateRange) {
    case 'today':
        $query .= " AND DATE(al.created_at) = CURDATE()";
        break;
    case 'yesterday':
        $query .= " AND DATE(al.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        break;
    case 'last7':
        $query .= " AND al.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'last30':
        $query .= " AND al.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        break;
    case 'custom':
        if (!empty($_GET['startDate']) && !empty($_GET['endDate'])) {
            $query .= " AND DATE(al.created_at) BETWEEN ? AND ?";
            $params[] = $_GET['startDate'];
            $params[] = $_GET['endDate'];
        }
        break;
}

// Apply log type filter
if (!empty($logType)) {
    $query .= " AND al.action LIKE ?";
    $params[] = "%$logType%";
}

// Apply user role filter
if (!empty($userFilter)) {
    $query .= " AND u.role = ?";
    $params[] = $userFilter;
}

// Apply search filter
if (!empty($search)) {
    $query .= " AND (al.details LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get total count for pagination
$countQuery = str_replace("SELECT al.*, u.full_name, u.role", "SELECT COUNT(*)", $query);
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalLogs = $stmt->fetchColumn();
$totalPages = ceil($totalLogs / $perPage);

// Add ordering and limit directly to the query string
$query .= " ORDER BY al.created_at DESC LIMIT $perPage OFFSET $offset";

// Execute the main query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user statistics
$stmt = $pdo->query("
    SELECT role, COUNT(*) as count 
    FROM users 
    GROUP BY role
");
$userStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get log type statistics
$stmt = $pdo->query("
    SELECT action, COUNT(*) as count 
    FROM activity_logs 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY action
");
$logTypeStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Activity Logs - Smart Hospital Laboratory System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        /* Log Controls */
        .log-controls {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .filter-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .filter-group {
            flex: 1;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .filter-control {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .actions-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .log-actions {
            display: flex;
            gap: 0.8rem;
        }
        
        .btn {
            padding: 0.7rem 1.2rem;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            border: none;
            display: flex;
            align-items: center;
        }
        
        .btn i {
            margin-right: 0.5rem;
        }
        
        .btn-secondary {
            background-color: #ddd;
            color: var(--dark-color);
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .log-info {
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        /* Activity Logs Table */
        .logs-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .logs-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .logs-table th {
            background-color: var(--primary-color);
            color: white;
            text-align: left;
            padding: 1rem;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .logs-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        .logs-table tr:last-child td {
            border-bottom: none;
        }
        
        .logs-table tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .log-id {
            font-family: monospace;
            color: #666;
        }
        
        .log-time {
            white-space: nowrap;
        }
        
        .log-type {
            display: inline-block;
            padding: 0.3rem 0.7rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
            text-align: center;
            min-width: 80px;
        }
        
        .log-type.info {
            background-color: var(--info-color);
        }
        
        .log-type.success {
            background-color: var(--accent-color);
        }
        
        .log-type.warning {
            background-color: var(--warning-color);
        }
        
        .log-type.error {
            background-color: var(--danger-color);
        }
        
        .log-type.security {
            background-color: var(--dark-color);
        }
        
        .log-user {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 0.8rem;
        }
        
        .log-details {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .action-icon {
            color: var(--dark-color);
            cursor: pointer;
            margin-left: 0.5rem;
            transition: color 0.3s;
        }
        
        .action-icon:hover {
            color: var(--secondary-color);
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .page-info {
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        .page-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .page-btn {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            color: var(--dark-color);
            background-color: white;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .page-btn.active {
            background-color: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
        }
        
        .page-btn:hover:not(.active) {
            background-color: #f1f1f1;
        }
        
        /* Log Details Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .close-modal {
            background: none;
            border: none;
            color: #888;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .log-detail-row {
            margin-bottom: 1.2rem;
        }
        
        .log-detail-row:last-child {
            margin-bottom: 0;
        }
        
        .detail-label {
            color: #888;
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
        }
        
        .detail-value {
            color: var(--dark-color);
            font-size: 0.95rem;
        }
        
        .log-message {
            padding: 1rem;
            background-color: #f9f9f9;
            border-radius: 5px;
            color: var(--dark-color);
            font-family: monospace;
            overflow-x: auto;
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
            
            .filter-row {
                flex-direction: column;
            }
            
            .actions-row {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .log-actions {
                width: 100%;
            }
            
            .btn {
                flex: 1;
                justify-content: center;
            }
            
            .logs-table {
                display: block;
                overflow-x: auto;
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
            <a href="admin_dashboard.php" class="menu-item">
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
            <a href="system_activity_logs.php" class="menu-item active">
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
            <a href="admin_login.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1>System Activity Logs</h1>
        </div>
        
        <!-- Log Controls -->
        <div class="log-controls">
            <form id="filterForm" method="GET">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="dateRange">Date Range</label>
                        <select id="dateRange" name="dateRange" class="filter-control">
                            <option value="today" <?php echo $dateRange === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="yesterday" <?php echo $dateRange === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                            <option value="last7" <?php echo $dateRange === 'last7' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="last30" <?php echo $dateRange === 'last30' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="custom" <?php echo $dateRange === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="logType">Log Type</label>
                        <select id="logType" name="logType" class="filter-control">
                            <option value="">All Types</option>
                            <?php foreach($logTypeStats as $stat): ?>
                            <option value="<?php echo htmlspecialchars($stat['action']); ?>" 
                                    <?php echo $logType === $stat['action'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($stat['action'])); ?> (<?php echo $stat['count']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="userFilter">User Role</label>
                        <select id="userFilter" name="userFilter" class="filter-control">
                            <option value="">All Users</option>
                            <?php foreach($userStats as $stat): ?>
                            <option value="<?php echo htmlspecialchars($stat['role']); ?>"
                                    <?php echo $userFilter === $stat['role'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($stat['role'])); ?> (<?php echo $stat['count']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="activitySearch">Search</label>
                        <input type="text" id="activitySearch" name="search" class="filter-control" 
                               placeholder="Search logs by keyword..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                
                <div class="actions-row">
                    <div class="log-actions">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-filter"></i>
                            Apply Filters
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="exportLogs()">
                            <i class="fas fa-download"></i>
                            Export Logs
                        </button>
                        <button type="button" class="btn btn-danger" onclick="clearLogs()">
                            <i class="fas fa-trash"></i>
                            Clear Logs
                        </button>
                    </div>
                    
                    <div class="log-info">
                        Showing <strong><?php echo count($logs); ?></strong> of <strong><?php echo $totalLogs; ?></strong> logs
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Activity Logs Table -->
        <div class="logs-container">
            <table class="logs-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Timestamp</th>
                        <th>Type</th>
                        <th>User</th>
                        <th>IP Address</th>
                        <th>Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $log): ?>
                    <tr>
                        <td class="log-id">#<?php echo $log['id']; ?></td>
                        <td class="log-time"><?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?></td>
                        <td><span class="log-type <?php echo strtolower($log['action']); ?>"><?php echo ucfirst($log['action']); ?></span></td>
                        <td class="log-user">
                            <img src="https://randomuser.me/api/portraits/<?php echo $log['user_id'] % 2 ? 'men' : 'women'; ?>/<?php echo $log['user_id']; ?>.jpg" 
                                 alt="<?php echo htmlspecialchars($log['full_name']); ?>" 
                                 class="user-avatar">
                            <?php echo htmlspecialchars($log['full_name']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        <td class="log-details"><?php echo htmlspecialchars($log['details']); ?></td>
                        <td>
                            <i class="fas fa-eye action-icon view-log" data-id="<?php echo $log['id']; ?>"></i>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="pagination">
            <div class="page-info">
                Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $perPage, $totalLogs); ?> of <?php echo $totalLogs; ?> logs
            </div>
            
            <div class="page-buttons">
                <?php if($page > 1): ?>
                <a href="?page=1<?php echo !empty($dateRange) ? '&dateRange='.$dateRange : ''; ?><?php echo !empty($logType) ? '&logType='.$logType : ''; ?><?php echo !empty($userFilter) ? '&userFilter='.$userFilter : ''; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>" class="page-btn">
                    <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="?page=<?php echo ($page-1); ?><?php echo !empty($dateRange) ? '&dateRange='.$dateRange : ''; ?><?php echo !empty($logType) ? '&logType='.$logType : ''; ?><?php echo !empty($userFilter) ? '&userFilter='.$userFilter : ''; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>" class="page-btn">
                    <i class="fas fa-angle-left"></i>
                </a>
                <?php endif; ?>
                
                <?php for($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo !empty($dateRange) ? '&dateRange='.$dateRange : ''; ?><?php echo !empty($logType) ? '&logType='.$logType : ''; ?><?php echo !empty($userFilter) ? '&userFilter='.$userFilter : ''; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>" 
                   class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if($page < $totalPages): ?>
                <a href="?page=<?php echo ($page+1); ?><?php echo !empty($dateRange) ? '&dateRange='.$dateRange : ''; ?><?php echo !empty($logType) ? '&logType='.$logType : ''; ?><?php echo !empty($userFilter) ? '&userFilter='.$userFilter : ''; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>" class="page-btn">
                    <i class="fas fa-angle-right"></i>
                </a>
                <a href="?page=<?php echo $totalPages; ?><?php echo !empty($dateRange) ? '&dateRange='.$dateRange : ''; ?><?php echo !empty($logType) ? '&logType='.$logType : ''; ?><?php echo !empty($userFilter) ? '&userFilter='.$userFilter : ''; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>" class="page-btn">
                    <i class="fas fa-angle-double-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Log Details Modal -->
    <div class="modal" id="logDetailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Log Entry Details</h2>
                <button class="close-modal">&times;</button>
            </div>
            
            <div class="modal-body" id="logDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById('logDetailsModal');
        const closeBtn = document.querySelector('.close-modal');
        
        // Close modal when clicking outside
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
        
        // View log details
        document.querySelectorAll('.view-log').forEach(icon => {
            icon.addEventListener('click', async () => {
                const logId = icon.dataset.id;
                try {
                    const response = await fetch(`process/get_log_details.php?id=${logId}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        document.getElementById('logDetailsContent').innerHTML = `
                            <div class="log-detail-row">
                                <div class="detail-label">Log ID</div>
                                <div class="detail-value log-id">#${data.log.id}</div>
                            </div>
                            
                            <div class="log-detail-row">
                                <div class="detail-label">Timestamp</div>
                                <div class="detail-value">${new Date(data.log.created_at).toLocaleString()}</div>
                            </div>
                            
                            <div class="log-detail-row">
                                <div class="detail-label">Type</div>
                                <div class="detail-value"><span class="log-type ${data.log.action.toLowerCase()}">${data.log.action}</span></div>
                            </div>
                            
                            <div class="log-detail-row">
                                <div class="detail-label">User</div>
                                <div class="detail-value log-user">
                                    <img src="https://randomuser.me/api/portraits/${data.log.user_id % 2 ? 'men' : 'women'}/${data.log.user_id}.jpg" 
                                         alt="${data.log.full_name}" 
                                         class="user-avatar">
                                    ${data.log.full_name} (${data.log.role})
                                </div>
                            </div>
                            
                            <div class="log-detail-row">
                                <div class="detail-label">IP Address</div>
                                <div class="detail-value">${data.log.ip_address}</div>
                            </div>
                            
                            <div class="log-detail-row">
                                <div class="detail-label">Activity</div>
                                <div class="detail-value">${data.log.details}</div>
                            </div>
                            
                            <div class="log-detail-row">
                                <div class="detail-label">Additional Details</div>
                                <div class="detail-value log-message">
                                    ${JSON.stringify(data.log, null, 2)}
                                </div>
                            </div>
                        `;
                        modal.style.display = 'flex';
                    } else {
                        alert('Error loading log details: ' + data.message);
                    }
                } catch (error) {
                    alert('Error loading log details');
                    console.error('Error:', error);
                }
            });
        });
        
        // Export logs
        async function exportLogs() {
            try {
                const response = await fetch('process/export_logs.php');
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'activity_logs.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } catch (error) {
                alert('Error exporting logs');
                console.error('Error:', error);
            }
        }
        
        // Clear logs function
        function clearLogs() {
            // Create confirmation dialog
            const dialog = document.createElement('div');
            dialog.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
            `;

            // Create dialog content
            const content = document.createElement('div');
            content.style.cssText = `
                background: white;
                padding: 2rem;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                max-width: 400px;
                width: 90%;
                text-align: center;
            `;

            content.innerHTML = `
                <h3 style="margin-bottom: 1rem; color: var(--danger-color);">Clear All Logs</h3>
                <p style="margin-bottom: 1.5rem;">Are you sure you want to clear all activity logs? This action cannot be undone.</p>
                <div style="display: flex; justify-content: center; gap: 1rem;">
                    <button onclick="confirmClearLogs(this)" style="
                        background: var(--danger-color);
                        color: white;
                        border: none;
                        padding: 0.5rem 1.5rem;
                        border-radius: 4px;
                        cursor: pointer;
                    ">Clear Logs</button>
                    <button onclick="closeDialog(this)" style="
                        background: var(--light-color);
                        color: var(--dark-color);
                        border: none;
                        padding: 0.5rem 1.5rem;
                        border-radius: 4px;
                        cursor: pointer;
                    ">Cancel</button>
                </div>
            `;

            dialog.appendChild(content);
            document.body.appendChild(dialog);
        }

        function closeDialog(button) {
            button.closest('div[style*="position: fixed"]').remove();
        }

        async function confirmClearLogs(button) {
            // Show loading state
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing...';

            try {
                const response = await fetch('process/clear_logs.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                const data = await response.json();

                if (data.status === 'success') {
                    // Close dialog
                    button.closest('div[style*="position: fixed"]').remove();
                    
                    // Show success message
                    const successMsg = document.createElement('div');
                    successMsg.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: var(--accent-color);
                        color: white;
                        padding: 1rem;
                        border-radius: 4px;
                        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                        z-index: 1000;
                    `;
                    successMsg.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                    document.body.appendChild(successMsg);

                    // Reload the page after 2 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);

                    // Remove success message after 3 seconds
                    setTimeout(() => {
                        successMsg.remove();
                    }, 3000);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                // Show error message
                const errorMsg = document.createElement('div');
                errorMsg.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: var(--danger-color);
                    color: white;
                    padding: 1rem;
                    border-radius: 4px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    z-index: 1000;
                `;
                errorMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error: ' + error.message;
                document.body.appendChild(errorMsg);

                // Remove error message after 5 seconds
                setTimeout(() => {
                    errorMsg.remove();
                }, 5000);

                // Reset button
                button.disabled = false;
                button.innerHTML = 'Clear Logs';
            }
        }
        
        // Handle custom date range
        document.getElementById('dateRange').addEventListener('change', function() {
            if (this.value === 'custom') {
                const startDate = prompt('Enter start date (YYYY-MM-DD):');
                const endDate = prompt('Enter end date (YYYY-MM-DD):');
                if (startDate && endDate) {
                    const form = document.getElementById('filterForm');
                    const startInput = document.createElement('input');
                    startInput.type = 'hidden';
                    startInput.name = 'startDate';
                    startInput.value = startDate;
                    form.appendChild(startInput);
                    
                    const endInput = document.createElement('input');
                    endInput.type = 'hidden';
                    endInput.name = 'endDate';
                    endInput.value = endDate;
                    form.appendChild(endInput);
                    
                    form.submit();
                }
            }
        });
    </script>
</body>
</html>

    