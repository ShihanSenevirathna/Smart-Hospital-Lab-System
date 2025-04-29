php<!DOCTYPE html>
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
            <a href="admin_login.html" class="menu-item">
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
        
        <!-- Log Filters and Controls -->
        <div class="log-controls">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="dateRange">Date Range</label>
                    <select id="dateRange" class="filter-control">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="last7" selected>Last 7 Days</option>
                        <option value="last30">Last 30 Days</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="logType">Log Type</label>
                    <select id="logType" class="filter-control">
                        <option value="">All Types</option>
                        <option value="info">Information</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                        <option value="error">Error</option>
                        <option value="security">Security</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="userFilter">User</label>
                    <select id="userFilter" class="filter-control">
                        <option value="">All Users</option>
                        <option value="admin">Administrators</option>
                        <option value="doctor">Doctors</option>
                        <option value="lab">Lab Technicians</option>
                        <option value="patient">Patients</option>
                    </select>
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <label for="activitySearch">Search</label>
                    <input type="text" id="activitySearch" class="filter-control" placeholder="Search logs by keyword...">
                </div>
            </div>
            
            <div class="actions-row">
                <div class="log-actions">
                    <button class="btn btn-secondary">
                        <i class="fas fa-filter"></i>
                        Apply Filters
                    </button>
                    <button class="btn btn-secondary">
                        <i class="fas fa-download"></i>
                        Export Logs
                    </button>
                    <button class="btn btn-danger">
                        <i class="fas fa-trash"></i>
                        Clear Logs
                    </button>
                </div>
                
                <div class="log-info">
                    Showing <strong>500</strong> of <strong>2,345</strong> logs
                </div>
            </div>
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
                    <tr>
                        <td class="log-id">#12345</td>
                        <td class="log-time">Apr 3, 2025 09:42:18</td>
                        <td><span class="log-type success">Success</span></td>
                        <td class="log-user">
                            <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="Dr. John Smith" class="user-avatar">
                            Dr. John Smith
                        </td>
                        <td>192.168.1.101</td>
                        <td class="log-details">User logged in successfully</td>
                        <td>
                            <i class="fas fa-eye action-icon view-log" data-id="12345"></i>
                        </td>
                    </tr>
                    <tr>
                        <td class="log-id">#12344</td>
                        <td class="log-time">Apr 3, 2025 09:40:05</td>
                        <td><span class="log-type security">Security</span></td>
                        <td class="log-user">
                            <img src="https://randomuser.me/api/portraits/men/5.jpg" alt="System" class="user-avatar">
                            System
                        </td>
                        <td>192.168.1.1</td>
                        <td class="log-details">Automatic database backup completed</td>
                        <td>
                            <i class="fas fa-eye action-icon view-log" data-id="12344"></i>
                        </td>
                    </tr>
                    <tr>
                        <td class="log-id">#12343</td>
                        <td class="log-time">Apr 3, 2025 09:35:22</td>
                        <td><span class="log-type info">Info</span></td>
                        <td class="log-user">
                            <img src="https://randomuser.me/api/portraits/women/2.jpg" alt="Dr. Sarah Johnson" class="user-avatar">
                            Dr. Sarah Johnson
                        </td>
                        <td>192.168.1.102</td>
                        <td class="log-details">New lab test request created for patient #P1045</td>
                        <td>
                            <i class="fas fa-eye action-icon view-log" data-id="12343"></i>
                        </td>
                    </tr>
                    <tr>
                        <td class="log-id">#12342</td>
                        <td class="log-time">Apr 3, 2025 09:22:10</td>
                        <td><span class="log-type error">Error</span></td>
                        <td class="log-user">
                            <img src="https://randomuser.me/api/portraits/men/3.jpg" alt="James Wilson" class="user-avatar">
                            James Wilson
                        </td>
                        <td>192.168.1.103</td>
                        <td class="log-details">Error processing sample QR code #QR982134</td>
                        <td>
                            <i class="fas fa-eye action-icon view-log" data-id="12342"></i>
                        </td>
                    </tr>
                    <tr>
                        <td class="log-id">#12341</td>
                        <td class="log-time">Apr 3, 2025 09:15:33</td>
                        <td><span class="log-type warning">Warning</span></td>
                        <td class="log-user">
                            <img src="https://randomuser.me/api/portraits/men/5.jpg" alt="System" class="user-avatar">
                            System
                        </td>
                        <td>192.168.1.1</td>
                        <td class="log-details">Server load reached 85% capacity, performance may be affected</td>
                        <td>
                            <i class="fas fa-eye action-icon view-log" data-id="12341"></i>
                        </td>
                    </tr>
                    <tr>
                        <td class="log-id">#12340</td>
                        <td class="log-time">Apr 3, 2025 09:12:40</td>
                        <td><span class="log-type success">Success</span></td>
                        <td class="log-user">
                            <img src="https://randomuser.me/api/portraits/women/4.jpg" alt="Emily Davis" class="user-avatar">
                            Emily Davis
                        </td>
                        <td>192.168.1.105</td>
                        <td class="log-details">User updated profile information</td>
                        <td>
                            <i class="fas fa-eye action-icon view-log" data-id="12340"></i>
                        </td>
                    </tr>
                    <tr>
                        <td class="log-id">#12339</td>
                        <td class="log-time">Apr 3, 2025 09:05:18</td>
                        <td><span class="log-type security">Security</span></td>
                        <td class="log-user">
                            <img src="https://randomuser.me/api/portraits/men/6.jpg" alt="Unknown" class="user-avatar">
                            Unknown
                        </td>
                        <td>203.0.113.12</td>
                        <td class="log-details">Failed login attempt for username: admin (attempt 3 of 5)</td>
                        <td>
                            <i class="fas fa-eye action-icon view-log" data-id="12339"></i>
                        </td>
                    </tr>
                    <tr>
                        <td class="log-id">#12338</td>
                        <td class="log-time">Apr 3, 2025 09:01:45</td>
                        <td><span class="log-type info">Info</span></td>
                        <td class="log-user">
                            <img src="https://randomuser.me/api/portraits/men/3.jpg" alt="James Wilson" class="user-avatar">
                            James Wilson
                        </td>
                        <td>192.168.1.103</td>
                        <td class="log-details">New sample registered in system with QR code #QR982134</td>
                        <td>
                            <i class="fas fa-eye action-icon view-log" data-id="12338"></i>
                        </td>
                    </tr>
                    <tr>
                        <td class="log-id">#12337</td>
                        <td class="log-time">Apr 3, 2025 08:55:22</td>
                        <td><span class="log-type success">Success</span></td>
                        <td class="log-user">
                            <img src="https://randomuser.me/api/portraits/men/3.jpg" alt="James Wilson" class="user-avatar">
                            James Wilson
                        </td>
                        <td>192.168.1.103</td>
                        <td class="log-details">User logged in successfully</td>
                        <td>
                            <i class="fas fa-eye action-icon view-log" data-id="12337"></i>
                        </td>
                    </tr>
                    <tr>
                        <td class="log-id">#12336</td>
                        <td class="log-time">Apr 3, 2025 08:45:10</td>
                        <td><span class="log-type success">Success</span></td>
                        <td class="log-user">
                            <img src="https://randomuser.me/api/portraits/women/2.jpg" alt="Dr. Sarah Johnson" class="user-avatar">
                            Dr. Sarah Johnson
                        </td>
                        <td>192.168.1.102</td>
                        <td class="log-details">User logged in successfully</td>
                        <td>
                            <i class="fas fa-eye action-icon view-log" data-id="12336"></i>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="pagination">
            <div class="page-info">
                Showing 1 to 10 of 2,345 logs
            </div>
            
            <div class="page-buttons">
                <button class="page-btn"><i class="fas fa-angle-double-left"></i></button>
                <button class="page-btn"><i class="fas fa-angle-left"></i></button>
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <button class="page-btn">4</button>
                <button class="page-btn">5</button>
                <button class="page-btn"><i class="fas fa-angle-right"></i></button>
                <button class="page-btn"><i class="fas fa-angle-double-right"></i></button>
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
            
            <div class="modal-body">
                <div class="log-detail-row">
                    <div class="detail-label">Log ID</div>
                    <div class="detail-value log-id">#12345</div>
                </div>
                
                <div class="log-detail-row">
                    <div class="detail-label">Timestamp</div>
                    <div class="detail-value">April 3, 2025 09:42:18 AM</div>
                </div>
                
                <div class="log-detail-row">
                    <div class="detail-label">Type</div>
                    <div class="detail-value"><span class="log-type success">Success</span></div>
                </div>
                
                <div class="log-detail-row">
                    <div class="detail-label">User</div>
                    <div class="detail-value log-user">
                        <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="Dr. John Smith" class="user-avatar">
                        Dr. John Smith (Administrator)
                    </div>
                </div>
                
                <div class="log-detail-row">
                    <div class="detail-label">IP Address</div>
                    <div class="detail-value">192.168.1.101</div>
                </div>
                
                <div class="log-detail-row">
                    <div class="detail-label">Device / Browser</div>
                    <div class="detail-value">Windows 10 / Chrome 125.0.0.0</div>
                </div>
                
                <div class="log-detail-row">
                    <div class="detail-label">Activity</div>
                    <div class="detail-value">User logged in successfully</div>
                </div>
                
                <div class="log-detail-row">
                    <div class="detail-label">Additional Details</div>
                    <div class="detail-value log-message">
                        {
                          "event": "user_login",
                          "status": "success",
                          "user_id": "admin_001",
                          "user_role": "administrator",
                          "login_method": "password",
                          "session_id": "sess_89ad71c2e4b0",
                          "previous_login": "2025-04-02T16:30:45.000Z"
                        }
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functionality
        const modal = document.getElementById('logDetailsModal');
        const closeBtn = document.querySelector('.close-modal');
        const viewLogBtns = document.querySelectorAll('.view-log');
        
        // Open modal when view icon is clicked
        viewLogBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const logId = btn.getAttribute('data-id');
                // In a real app, you would fetch the log details from the server
                // For demo purposes, we're just showing the modal with preset data
                
                // Update the modal title with the log ID
                document.querySelector('.modal-title').textContent = `Log Entry Details (ID: ${logId})`;
                
                modal.style.display = 'flex';
            });
        });
        
        // Close modal when X button is clicked
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
        
        // Close modal when clicking outside the modal content
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Filter functionality
        document.querySelector('.btn-secondary:first-child').addEventListener('click', () => {
            // In a real app, this would apply filters to the table
            alert('Filters applied!');
        });
        
        // Export functionality
        document.querySelector('.btn-secondary:nth-child(2)').addEventListener('click', () => {
            // In a real app, this would generate and download an export file
            alert('Logs exported successfully!');
        });
        
        // Clear logs functionality
        document.querySelector('.btn-danger').addEventListener('click', () => {
            if (confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
                // In a real app, this would send a request to clear logs
                alert('Logs cleared successfully!');
            }
        });
        
        // Pagination functionality
        const pageBtns = document.querySelectorAll('.page-btn');
        
        pageBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Remove active class from all buttons
                pageBtns.forEach(b => b.classList.remove('active'));
                
                // Add active class to clicked button if it's a number
                if (!btn.querySelector('i')) {
                    btn.classList.add('active');
                }
                
                // In a real app, this would fetch the data for the selected page
            });
        });
    </script>
</body>
</html>