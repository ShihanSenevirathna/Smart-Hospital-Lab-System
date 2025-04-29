<?php
session_start();
require_once '../config/db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../admin_login.php");
    exit();
}

// Function to log activity
function logActivity($pdo, $userId, $action, $details) {
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR']]);
}

// Handle different CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_user':
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, email) VALUES (?, ?, ?, ?, ?)");
                $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt->execute([
                    $_POST['username'],
                    $hashedPassword,
                    $_POST['full_name'],
                    $_POST['role'],
                    $_POST['email']
                ]);
                
                logActivity($pdo, $_SESSION['admin_id'], 'create', "Created new user: {$_POST['username']}");
                echo json_encode(['success' => true, 'message' => 'User created successfully']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error creating user: ' . $e->getMessage()]);
            }
            break;
            
        case 'update_user':
            try {
                $sql = "UPDATE users SET full_name = ?, role = ?, email = ?";
                $params = [$_POST['full_name'], $_POST['role'], $_POST['email']];
                
                if (!empty($_POST['password'])) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $_POST['user_id'];
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                logActivity($pdo, $_SESSION['admin_id'], 'update', "Updated user ID: {$_POST['user_id']}");
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error updating user: ' . $e->getMessage()]);
            }
            break;
            
        case 'delete_user':
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$_POST['user_id']]);
                
                logActivity($pdo, $_SESSION['admin_id'], 'delete', "Deleted user ID: {$_POST['user_id']}");
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()]);
            }
            break;
            
        case 'create_test':
            try {
                $stmt = $pdo->prepare("INSERT INTO tests (test_name, description, price) VALUES (?, ?, ?)");
                $stmt->execute([
                    $_POST['test_name'],
                    $_POST['description'],
                    $_POST['price']
                ]);
                
                logActivity($pdo, $_SESSION['admin_id'], 'create', "Created new test: {$_POST['test_name']}");
                echo json_encode(['success' => true, 'message' => 'Test created successfully']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error creating test: ' . $e->getMessage()]);
            }
            break;
            
        case 'update_test':
            try {
                $stmt = $pdo->prepare("UPDATE tests SET test_name = ?, description = ?, price = ?, status = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['test_name'],
                    $_POST['description'],
                    $_POST['price'],
                    $_POST['status'],
                    $_POST['test_id']
                ]);
                
                logActivity($pdo, $_SESSION['admin_id'], 'update', "Updated test ID: {$_POST['test_id']}");
                echo json_encode(['success' => true, 'message' => 'Test updated successfully']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error updating test: ' . $e->getMessage()]);
            }
            break;
            
        case 'delete_test':
            try {
                $stmt = $pdo->prepare("DELETE FROM tests WHERE id = ?");
                $stmt->execute([$_POST['test_id']]);
                
                logActivity($pdo, $_SESSION['admin_id'], 'delete', "Deleted test ID: {$_POST['test_id']}");
                echo json_encode(['success' => true, 'message' => 'Test deleted successfully']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error deleting test: ' . $e->getMessage()]);
            }
            break;
            
        case 'update_sample_status':
            try {
                $stmt = $pdo->prepare("UPDATE test_orders SET status = ?, results = ? WHERE id = ?");
                $stmt->execute([$_POST['status'], $_POST['results'], $_POST['sample_id']]);
                
                logActivity($pdo, $_SESSION['admin_id'], 'update', "Updated sample status ID: {$_POST['sample_id']}");
                echo json_encode(['success' => true, 'message' => 'Sample status updated successfully']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error updating sample status: ' . $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    // Handle GET requests for fetching data
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_users':
            try {
                $stmt = $pdo->query("SELECT id, username, full_name, role, email, created_at FROM users");
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error fetching users: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_tests':
            try {
                $stmt = $pdo->query("SELECT * FROM tests");
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error fetching tests: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_samples':
            try {
                $stmt = $pdo->query("
                    SELECT t.*, 
                           u1.full_name as patient_name,
                           u2.full_name as doctor_name,
                           ts.name as test_name
                    FROM test_orders t
                    JOIN users u1 ON t.patient_id = u1.id
                    JOIN users u2 ON t.doctor_id = u2.id
                    JOIN tests ts ON t.test_id = ts.id
                    ORDER BY t.order_date DESC
                ");
                $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'samples' => $samples]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error fetching samples: ' . $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?> 