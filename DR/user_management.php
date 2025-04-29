<?php
session_start();
require_once 'config/db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Get search parameters
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build the query
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (full_name LIKE ? OR email LIKE ? OR username LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($roleFilter)) {
    $query .= " AND role = ?";
    $params[] = $roleFilter;
}

if (!empty($statusFilter)) {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY created_at DESC";

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            try {
                // Start transaction
                $pdo->beginTransaction();

                // First create the user in users table
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, email, status) VALUES (?, ?, ?, ?, ?, ?)");
                $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $fullName = $_POST['firstName'] . ' ' . $_POST['lastName'];
                $stmt->execute([
                    $_POST['email'], // Using email as username
                    $hashedPassword,
                    $fullName,
                    $_POST['role'],
                    $_POST['email'],
                    $_POST['status']
                ]);
                
                $userId = $pdo->lastInsertId();

                // If role is doctor, insert into doctors table
                if ($_POST['role'] === 'doctor') {
                    $stmt = $pdo->prepare("INSERT INTO doctors (first_name, last_name, email, phone, specialization, department) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['firstName'],
                        $_POST['lastName'],
                        $_POST['email'],
                        $_POST['phone'] ?? null,
                        $_POST['specialization'] ?? null,
                        $_POST['department'] ?? null
                    ]);
                }
                // If role is patient, insert into patients table
                else if ($_POST['role'] === 'patient') {
                    $stmt = $pdo->prepare("INSERT INTO patients (
                        first_name, last_name, email, phone, username, password,
                        date_of_birth, gender, national_id, address, city, state,
                        zip_code, country, blood_type, allergies, medications,
                        medical_conditions, emergency_contact, emergency_phone
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->execute([
                        $_POST['firstName'],
                        $_POST['lastName'],
                        $_POST['email'],
                        $_POST['phone'] ?? null,
                        $_POST['email'], // Using email as username
                        $hashedPassword,
                        $_POST['dateOfBirth'] ?? null,
                        $_POST['gender'] ?? null,
                        $_POST['nationalId'] ?? null,
                        $_POST['address'] ?? null,
                        $_POST['city'] ?? null,
                        $_POST['state'] ?? null,
                        $_POST['zipCode'] ?? null,
                        $_POST['country'] ?? null,
                        $_POST['bloodType'] ?? null,
                        $_POST['allergies'] ?? null,
                        $_POST['medications'] ?? null,
                        $_POST['medicalConditions'] ?? null,
                        $_POST['emergencyContact'] ?? null,
                        $_POST['emergencyPhone'] ?? null
                    ]);
                }

                // Commit transaction
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'User created successfully']);
            } catch (PDOException $e) {
                // Rollback transaction on error
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Error creating user: ' . $e->getMessage()]);
            }
            break;
            
        case 'update':
            try {
                $sql = "UPDATE users SET full_name = ?, role = ?, email = ?, status = ?";
                $params = [
                    $_POST['firstName'] . ' ' . $_POST['lastName'],
                    $_POST['role'],
                    $_POST['email'],
                    $_POST['status']
                ];
                
                if (!empty($_POST['password'])) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $_POST['user_id'];
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error updating user: ' . $e->getMessage()]);
            }
            break;
            
        case 'delete':
            try {
                // Start transaction
                $pdo->beginTransaction();

                // First delete related activity logs
                $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE user_id = ?");
                $stmt->execute([$_POST['user_id']]);

                // Then delete the user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$_POST['user_id']]);

                // Commit transaction
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } catch (PDOException $e) {
                // Rollback transaction on error
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()]);
            }
            break;
    }
    exit();
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    error_log("Error fetching users: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Smart Hospital Laboratory System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        
        /* User Management Styles */
        .controls-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .search-bar {
            flex: 1;
            max-width: 400px;
            position: relative;
        }
        
        .search-bar input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .search-bar i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }
        
        .filter-options {
            display: flex;
            gap: 1rem;
        }
        
        .filter-select {
            padding: 0.7rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        .action-btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 0.7rem 1.2rem;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: background-color 0.3s;
        }
        
        .action-btn i {
            margin-right: 0.5rem;
        }
        
        .action-btn:hover {
            background-color: var(--primary-color);
        }
        
        /* User Table */
        .users-table {
            width: 100%;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .users-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .users-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        .users-table tr:last-child td {
            border-bottom: none;
        }
        
        .users-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 0.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .role-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            color: white;
            background-color: var(--secondary-color);
        }
        
        .role-badge.doctor {
            background-color: var(--info-color);
        }
        
        .role-badge.lab {
            background-color: var(--accent-color);
        }
        
        .role-badge.patient {
            background-color: var(--warning-color);
        }
        
        .role-badge.admin {
            background-color: var(--dark-color);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            color: white;
        }
        
        .status-badge.active {
            background-color: var(--accent-color);
        }
        
        .status-badge.inactive {
            background-color: var(--danger-color);
        }
        
        .status-badge.pending {
            background-color: var(--warning-color);
        }
        
        .action-icons {
            display: flex;
            gap: 0.8rem;
        }
        
        .action-icons a {
            color: var(--dark-color);
            transition: color 0.3s;
        }
        
        .action-icons a:hover {
            color: var(--secondary-color);
        }
        
        .action-icons a.delete:hover {
            color: var(--danger-color);
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        /* Modal styles for Add/Edit User */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            display: flex;
            flex-direction: column;
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
        
        .close-btn {
            background: none;
            border: none;
            color: #888;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .modal-body {
            padding: 1.5rem;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 1.2rem;
        }
        
        .form-group label {
            display: block;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            position: sticky;
            bottom: 0;
            background-color: white;
            z-index: 1;
        }
        
        .btn-cancel {
            background-color: #ddd;
            color: var(--dark-color);
            border: none;
            border-radius: 5px;
            padding: 0.7rem 1.2rem;
            font-size: 0.9rem;
            cursor: pointer;
            min-width: 100px;
        }
        
        .btn-save {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 0.7rem 1.2rem;
            font-size: 0.9rem;
            cursor: pointer;
            min-width: 100px;
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
            
            .controls-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .search-bar {
                max-width: 100%;
                width: 100%;
            }
            
            .filter-options {
                width: 100%;
                justify-content: space-between;
            }
        }
        
        @media (max-width: 767px) {
            .users-table {
                overflow-x: auto;
            }
            
            .filter-options {
                flex-wrap: wrap;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
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
            <a href="user_management.php" class="menu-item active">
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
            <a href="admin_login.html" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1>User Management</h1>
        </div>
        
        <!-- Controls -->
        <div class="controls-row">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search users by name, email, or role...">
            </div>
            
            <div class="filter-options">
                <select class="filter-select">
                    <option value="">All Roles</option>
                    <option value="admin">Administrator</option>
                    <option value="doctor">Doctor</option>
                    <option value="lab_staff">Lab Staff</option>
                    <option value="patient">Patient</option>
                </select>
                
                <select class="filter-select">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
            
            <button class="action-btn" id="addUserBtn">
                <i class="fas fa-plus"></i>
                Add New User
            </button>
        </div>
        
        <!-- Users Table -->
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <img src="https://randomuser.me/api/portraits/<?php echo $user['id'] % 2 ? 'men' : 'women'; ?>/<?php echo $user['id']; ?>.jpg" 
                                     alt="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                     class="user-avatar">
                                <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="role-badge <?php echo strtolower($user['role']); ?>"><?php echo ucfirst($user['role']); ?></span></td>
                        <td><span class="status-badge <?php echo strtolower($user['status']); ?>"><?php echo ucfirst($user['status']); ?></span></td>
                        <td>
                            <?php 
                            if (!empty($user['last_login'])) {
                                $lastLogin = new DateTime($user['last_login']);
                                echo $lastLogin->format('M j, Y g:i A');
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </td>
                        <td>
                            <div class="action-icons">
                                <a href="#" title="Edit User" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="#" title="Reset Password" onclick="resetPassword(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-key"></i>
                                </a>
                                <a href="#" class="delete" title="Delete User" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="pagination">
            <div class="page-info">
                Showing 1 to 6 of 24 users
            </div>
            
            <div class="page-buttons">
                <button class="page-btn"><i class="fas fa-angle-left"></i></button>
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <button class="page-btn">4</button>
                <button class="page-btn"><i class="fas fa-angle-right"></i></button>
            </div>
        </div>
    </main>
    
    <!-- Add/Edit User Modal -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New User</h2>
                <button class="close-btn">&times;</button>
            </div>
            
            <div class="modal-body">
                <form id="userForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <input type="text" id="firstName" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" id="lastName" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" class="form-control" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" class="form-control" required onchange="toggleRoleFields()">
                                <option value="">Select Role</option>
                                <option value="admin">Administrator</option>
                                <option value="doctor">Doctor</option>
                                <option value="lab_staff">Lab Technician</option>
                                <option value="patient">Patient</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" class="form-control" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" class="form-control" required>
                    </div>

                    <!-- Role-specific fields container -->
                    <div id="roleFieldsContainer" style="display: none; max-height: 300px; overflow-y: auto; margin-top: 1rem; padding: 1rem; border: 1px solid #ddd; border-radius: 5px;">
                        <!-- Doctor Specific Fields -->
                        <div id="doctorFields" style="display: none;">
                            <h4 class="form-subtitle">Doctor Information</h4>
                            <div class="form-group">
                                <label for="doctorPhone">Phone Number</label>
                                <input type="tel" id="doctorPhone" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="specialization">Specialization</label>
                                <input type="text" id="specialization" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="department">Department</label>
                                <input type="text" id="department" class="form-control">
                            </div>
                        </div>

                        <!-- Patient Specific Fields -->
                        <div id="patientFields" style="display: none;">
                            <h4 class="form-subtitle">Patient Information</h4>
                            <div class="form-group">
                                <label for="patientPhone">Phone Number</label>
                                <input type="tel" id="patientPhone" class="form-control">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="dateOfBirth">Date of Birth</label>
                                    <input type="date" id="dateOfBirth" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender" class="form-control">
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="nationalId">National ID</label>
                                <input type="text" id="nationalId" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <input type="text" id="address" class="form-control">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" id="city" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="state">State</label>
                                    <input type="text" id="state" class="form-control">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="zipCode">ZIP Code</label>
                                    <input type="text" id="zipCode" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="country">Country</label>
                                    <input type="text" id="country" class="form-control">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="bloodType">Blood Type</label>
                                <select id="bloodType" class="form-control">
                                    <option value="">Select Blood Type</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="allergies">Allergies</label>
                                <textarea id="allergies" class="form-control" rows="2"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="medications">Current Medications</label>
                                <textarea id="medications" class="form-control" rows="2"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="medicalConditions">Medical Conditions</label>
                                <textarea id="medicalConditions" class="form-control" rows="2"></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="emergencyContact">Emergency Contact Name</label>
                                    <input type="text" id="emergencyContact" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="emergencyPhone">Emergency Contact Phone</label>
                                    <input type="tel" id="emergencyPhone" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button class="btn-cancel" id="cancelBtn">Cancel</button>
                <button class="btn-save" id="saveBtn">Save User</button>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        const addUserBtn = document.getElementById('addUserBtn');
        const userModal = document.getElementById('userModal');
        const closeBtn = document.querySelector('.close-btn');
        const cancelBtn = document.getElementById('cancelBtn');
        const userForm = document.getElementById('userForm');
        const saveBtn = document.getElementById('saveBtn');
        
        // Reset form when opening modal
        function resetForm() {
            userForm.reset();
            document.querySelector('.modal-title').textContent = 'Add New User';
            saveBtn.textContent = 'Save User';
            saveBtn.dataset.action = 'create';
            delete saveBtn.dataset.userId;
        }
        
        addUserBtn.addEventListener('click', () => {
            resetForm();
            userModal.style.display = 'flex';
        });
        
        closeBtn.addEventListener('click', () => {
            userModal.style.display = 'none';
            resetForm();
        });
        
        cancelBtn.addEventListener('click', () => {
            userModal.style.display = 'none';
            resetForm();
        });
        
        // Close modal when clicking outside the modal content
        window.addEventListener('click', (event) => {
            if (event.target === userModal) {
                userModal.style.display = 'none';
                resetForm();
            }
        });
        
        // Function to toggle role-specific fields
        function toggleRoleFields() {
            const role = document.getElementById('role').value;
            const roleFieldsContainer = document.getElementById('roleFieldsContainer');
            const doctorFields = document.getElementById('doctorFields');
            const patientFields = document.getElementById('patientFields');
            
            // Hide all role-specific fields first
            roleFieldsContainer.style.display = 'none';
            doctorFields.style.display = 'none';
            patientFields.style.display = 'none';
            
            // Show fields based on selected role
            if (role === 'doctor' || role === 'patient') {
                roleFieldsContainer.style.display = 'block';
                if (role === 'doctor') {
                    doctorFields.style.display = 'block';
                } else if (role === 'patient') {
                    patientFields.style.display = 'block';
                }
                
                // Smooth scroll to the role-specific fields
                setTimeout(() => {
                    roleFieldsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            }
        }
        
        // Handle form submission
        saveBtn.addEventListener('click', async () => {
            // Form validation
            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const email = document.getElementById('email').value.trim();
            const role = document.getElementById('role').value;
            const status = document.getElementById('status').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            // Validate required fields
            if (!firstName || !lastName || !email || !role || !status || !password) {
                Swal.fire({
                    title: 'Error',
                    text: 'Please fill in all required fields',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Validate password match
            if (password !== confirmPassword) {
                Swal.fire({
                    title: 'Error',
                    text: 'Passwords do not match',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            const formData = new FormData();
            formData.append('action', saveBtn.dataset.action || 'create');
            
            if (saveBtn.dataset.action === 'update') {
                formData.append('user_id', saveBtn.dataset.userId);
            }
            
            // Basic user information
            formData.append('firstName', firstName);
            formData.append('lastName', lastName);
            formData.append('email', email);
            formData.append('role', role);
            formData.append('status', status);
            formData.append('password', password);
            
            // Role-specific fields
            if (role === 'doctor') {
                formData.append('phone', document.getElementById('doctorPhone').value.trim());
                formData.append('specialization', document.getElementById('specialization').value.trim());
                formData.append('department', document.getElementById('department').value.trim());
            } else if (role === 'patient') {
                formData.append('phone', document.getElementById('patientPhone').value.trim());
                formData.append('dateOfBirth', document.getElementById('dateOfBirth').value);
                formData.append('gender', document.getElementById('gender').value);
                formData.append('nationalId', document.getElementById('nationalId').value.trim());
                formData.append('address', document.getElementById('address').value.trim());
                formData.append('city', document.getElementById('city').value.trim());
                formData.append('state', document.getElementById('state').value.trim());
                formData.append('zipCode', document.getElementById('zipCode').value.trim());
                formData.append('country', document.getElementById('country').value.trim());
                formData.append('bloodType', document.getElementById('bloodType').value);
                formData.append('allergies', document.getElementById('allergies').value.trim());
                formData.append('medications', document.getElementById('medications').value.trim());
                formData.append('medicalConditions', document.getElementById('medicalConditions').value.trim());
                formData.append('emergencyContact', document.getElementById('emergencyContact').value.trim());
                formData.append('emergencyPhone', document.getElementById('emergencyPhone').value.trim());
            }
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                
                const result = await response.json();
                
                if (result.success) {
                    const role = document.getElementById('role').value;
                    const roleMessage = role === 'doctor' ? 'Doctor' : role === 'patient' ? 'Patient' : 'User';
                    
                    Swal.fire({
                        title: 'Success',
                        text: `${roleMessage} created successfully!`,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: result.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while saving the user. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
        
        // Handle edit user
        function editUser(user) {
            document.querySelector('.modal-title').textContent = 'Edit User';
            saveBtn.textContent = 'Update User';
            saveBtn.dataset.action = 'update';
            saveBtn.dataset.userId = user.id;
            
            document.getElementById('firstName').value = user.full_name.split(' ')[0];
            document.getElementById('lastName').value = user.full_name.split(' ').slice(1).join(' ');
            document.getElementById('email').value = user.email;
            document.getElementById('role').value = user.role;
            document.getElementById('status').value = user.status;
            document.getElementById('password').required = false;
            document.getElementById('confirmPassword').required = false;
            
            userModal.style.display = 'flex';
        }
        
        // Handle delete user
        async function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('user_id', userId);
                
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert(result.message);
                        window.location.reload(); // Refresh the page to show updated data
                    } else {
                        alert(result.message);
                    }
                } catch (error) {
                    alert('An error occurred while deleting the user.');
                    console.error('Error:', error);
                }
            }
        }
        
        // Handle reset password
        async function resetPassword(userId) {
            if (confirm('Are you sure you want to reset this user\'s password?')) {
                const newPassword = prompt('Enter new password:');
                if (newPassword) {
                    const formData = new FormData();
                    formData.append('action', 'update');
                    formData.append('user_id', userId);
                    formData.append('password', newPassword);
                    
                    try {
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            alert('Password reset successfully');
                        } else {
                            alert(result.message);
                        }
                    } catch (error) {
                        alert('An error occurred while resetting the password.');
                        console.error('Error:', error);
                    }
                }
            }
        }

        // Search functionality
        const searchInput = document.querySelector('.search-bar input');
        const roleFilter = document.querySelector('.filter-select:first-child');
        const statusFilter = document.querySelector('.filter-select:last-child');
        let searchTimeout;

        function performSearch() {
            const searchValue = searchInput.value.trim();
            const roleValue = roleFilter.value;
            const statusValue = statusFilter.value;

            // Build URL with search parameters
            const params = new URLSearchParams();
            if (searchValue) params.append('search', searchValue);
            if (roleValue) params.append('role', roleValue);
            if (statusValue) params.append('status', statusValue);

            // Update URL without reloading the page
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.pushState({}, '', newUrl);

            // Show loading state
            const tableBody = document.querySelector('.users-table tbody');
            tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center;"><i class="fas fa-spinner fa-spin"></i> Searching...</td></tr>';

            // Fetch filtered results
            fetch(newUrl)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTableBody = doc.querySelector('.users-table tbody');
                    tableBody.innerHTML = newTableBody.innerHTML;
                })
                .catch(error => {
                    console.error('Error:', error);
                    tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--danger-color);">Error loading results</td></tr>';
                });
        }

        // Add event listeners
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 500);
        });

        roleFilter.addEventListener('change', performSearch);
        statusFilter.addEventListener('change', performSearch);

        // Handle browser back/forward buttons
        window.addEventListener('popstate', () => {
            performSearch();
        });
    </script>
</body>
</html>