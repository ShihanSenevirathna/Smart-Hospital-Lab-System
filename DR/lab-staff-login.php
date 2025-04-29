<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if already logged in
if (isset($_SESSION['is_lab_staff']) && $_SESSION['is_lab_staff'] === true) {
    header('Location: lab-dashboard.php');
    exit;
}

// Check for remember me cookie
if (isset($_COOKIE['staff_remember'])) {
    require_once 'config/db_connection.php';
    
    try {
        $token = $_COOKIE['staff_remember'];
        
        // Get valid token
        $sql = "SELECT user_id FROM auth_tokens 
                WHERE token = :token 
                AND expires_at > NOW() 
                AND is_valid = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':token' => $token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Get user details
            $sql = "SELECT * FROM users 
                    WHERE id = :user_id 
                    AND role = 'lab_staff' 
                    AND status = 'active'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':user_id' => $result['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Auto login
                $_SESSION['staff_id'] = $user['username'];
                $_SESSION['staff_name'] = $user['full_name'];
                $_SESSION['staff_role'] = $user['role'];
                $_SESSION['is_lab_staff'] = true;
                $_SESSION['user_id'] = $user['id'];
                
                // Update last login
                $sql = "UPDATE users SET last_login = NOW() WHERE id = :user_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':user_id' => $user['id']]);
                
                header('Location: lab-dashboard.php');
                exit;
            }
        }
    } catch (PDOException $e) {
        error_log("Database error in lab-staff-login.php: " . $e->getMessage());
    }
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/db_connection.php';
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    try {
        // Get user details
        $sql = "SELECT * FROM users WHERE username = :username AND role = 'lab_staff' AND status = 'active'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify password
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['staff_id'] = $user['username'];
            $_SESSION['staff_name'] = $user['full_name'];
            $_SESSION['staff_role'] = $user['role'];
            $_SESSION['is_lab_staff'] = true;
            
            // Debug session
            error_log("Session variables set: " . print_r($_SESSION, true));
            
            // Handle remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $sql = "INSERT INTO auth_tokens (user_id, token, expires_at) 
                        VALUES (:user_id, :token, :expires)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':user_id' => $user['id'],
                    ':token' => $token,
                    ':expires' => $expires
                ]);
                
                setcookie('staff_remember', $token, time() + (86400 * 30), '/');
            }
            
            // Update last login
            $sql = "UPDATE users SET last_login = NOW() WHERE id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':user_id' => $user['id']]);
            
            header('Location: lab-dashboard.php');
            exit;
        } else {
            $error = "Invalid username or password";
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "An error occurred during login. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Staff Login | Smart Hospital Laboratory System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2C3E50;
            --secondary-color: #3498DB;
            --accent-color: #2ECC71;
            --light-color: #ECF0F1;
            --dark-color: #34495E;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .login-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .image-container {
            flex: 1;
            background: linear-gradient(135deg, #3498db, #2c3e50);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .image-container img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .login-box {
            width: 100%;
            max-width: 450px;
            background-color: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .hospital-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .hospital-logo img {
            height: 60px;
        }
        
        h1 {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .input-group input:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .input-group .icon {
            position: absolute;
            right: 15px;
            top: 39px;
            color: #95a5a6;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .remember-me input {
            margin-right: 10px;
        }
        
        .forgot-password {
            display: block;
            text-align: right;
            color: var(--secondary-color);
            text-decoration: none;
            margin-bottom: 20px;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background-color: #2980b9;
        }
        
        .login-footer {
            margin-top: 20px;
            text-align: center;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .login-footer a {
            color: var(--secondary-color);
            text-decoration: none;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .image-container {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .login-box {
                padding: 30px 20px;
            }
        }
        
        /* Add styles for error message */
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }
        
        .error-message.show {
            display: block;
        }
        
        /* Add loading state for button */
        .btn-login.loading {
            background-color: #bdc3c7;
            cursor: not-allowed;
            position: relative;
        }
        
        .btn-login.loading:after {
            content: '';
            width: 20px;
            height: 20px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: translateY(-50%) rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-box">
                <div class="hospital-logo">
                    <img src="images/logo.png" alt="Smart Hospital Laboratory System">
                </div>
                <h1>Laboratory Staff Login</h1>
                <div id="error-message" class="error-message"></div>
                <form id="login-form" action="process/lab_staff_login.php" method="post">
                    <div class="input-group">
                        <label for="staff-id">Staff ID</label>
                        <input type="text" id="staff-id" name="staff_id" placeholder="Enter your Staff ID" required>
                        <i class="icon fas fa-user"></i>
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <i class="icon fas fa-lock"></i>
                    </div>
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                    <button type="submit" class="btn-login">Login</button>
                </form>
                <div class="login-footer">
                    <p>Not a laboratory staff? <a href="index.php">Go to home</a></p>
                </div>
            </div>
        </div>
        <div class="image-container">
            <img src="images/lab-equipment.jpg" alt="Laboratory Equipment">
        </div>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = form.querySelector('.btn-login');
            const errorDiv = document.getElementById('error-message');
            
            // Disable button and show loading state
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            submitBtn.textContent = 'Logging in...';
            errorDiv.classList.remove('show');
            
            // Send form data
            fetch(form.action, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    errorDiv.textContent = data.error;
                    errorDiv.classList.add('show');
                    
                    // Reset button state
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');
                    submitBtn.textContent = 'Login';
                }
            })
            .catch(error => {
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.classList.add('show');
                
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
                submitBtn.textContent = 'Login';
            });
        });
    </script>
</body>
</html>