<?php
session_start();
require_once 'config/db_connection.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Log the received data
        error_log("Login attempt - Email: " . $_POST['email']);
        
        // Sanitize input
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;

        // Log sanitized data
        error_log("Sanitized email: " . $email);

        // Prepare SQL statement
        $sql = "SELECT id, first_name, last_name, email, password FROM patients WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // Log query results
        error_log("Query returned " . $stmt->rowCount() . " rows");

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Log user data (excluding password)
            error_log("User found - ID: " . $user['id'] . ", Name: " . $user['first_name'] . " " . $user['last_name']);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['patient_id'] = $user['id'];
                $_SESSION['patient_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['patient_email'] = $user['email'];
                
                // Log successful login
                error_log("Password verified - Login successful for user ID: " . $user['id']);
                
                // Handle remember me
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (30 * 24 * 60 * 60); // 30 days
                    
                    // Store remember me token in database
                    $updateToken = "UPDATE patients SET remember_token = :token, token_expires = :expires WHERE id = :id";
                    $tokenStmt = $pdo->prepare($updateToken);
                    $tokenStmt->execute([
                        ':token' => $token,
                        ':expires' => date('Y-m-d H:i:s', $expires),
                        ':id' => $user['id']
                    ]);
                    
                    // Set cookie
                    setcookie('remember_token', $token, $expires, '/', '', true, true);
                }

                // Return success response
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful!',
                    'redirect' => 'patient_dashboard.php'
                ]);
                exit;
            } else {
                error_log("Password verification failed for user ID: " . $user['id']);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid email or password'
                ]);
                exit;
            }
        } else {
            error_log("No user found with email: " . $email);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid email or password'
            ]);
            exit;
        }
    } catch(PDOException $e) {
        error_log("Database error during login: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Login failed: ' . $e->getMessage()
        ]);
        exit;
    }
}
?> 