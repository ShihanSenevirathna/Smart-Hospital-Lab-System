<?php
session_start();
require_once '../config/db_connection.php';

$response = ['success' => false, 'error' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = $_POST['staff_id'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    try {
        // Validate input
        if (empty($staff_id) || empty($password)) {
            $response = ['success' => false, 'error' => 'Please enter both Staff ID and password'];
        } else {
            // Get user details from database
            $sql = "SELECT * FROM users 
                    WHERE username = :staff_id 
                    AND role = 'lab_staff' 
                    AND status = 'active'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':staff_id' => $staff_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['staff_id'] = $user['username'];
                $_SESSION['staff_name'] = $user['full_name'];
                $_SESSION['staff_role'] = $user['role'];
                $_SESSION['is_lab_staff'] = true;
                $_SESSION['user_id'] = $user['id'];

                // Handle remember me
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    // Store remember me token
                    $sql = "INSERT INTO auth_tokens (user_id, token, expires_at) 
                            VALUES (:user_id, :token, :expires_at)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':user_id' => $user['id'],
                        ':token' => $token,
                        ':expires_at' => $expires
                    ]);

                    // Set remember me cookie
                    setcookie('staff_remember', $token, strtotime('+30 days'), '/', '', true, true);
                }

                // Update last login
                $sql = "UPDATE users SET last_login = NOW() WHERE id = :user_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':user_id' => $user['id']]);

                $response = [
                    'success' => true,
                    'redirect' => 'lab-dashboard.php'
                ];
            } else {
                $response = ['success' => false, 'error' => 'Invalid Staff ID or password'];
            }
        }
    } catch (PDOException $e) {
        error_log("Database error in lab_staff_login.php: " . $e->getMessage());
        $response = ['success' => false, 'error' => 'Database error occurred'];
    } catch (Exception $e) {
        error_log("Error in lab_staff_login.php: " . $e->getMessage());
        $response = ['success' => false, 'error' => 'An error occurred'];
    }
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response); 