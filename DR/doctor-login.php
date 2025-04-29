<?php
session_start();
require_once 'config/db_connection.php';

// Check if already logged in
if (isset($_SESSION['doctor_id'])) {
    header("Location: doctor-dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            // Prepare the SQL statement
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'doctor'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['doctor_id'] = $user['id'];
                $_SESSION['doctor_name'] = $user['full_name'];
                $_SESSION['doctor_role'] = $user['role'];
                
                // Log the login activity
                $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'login', 'Doctor logged in successfully')");
                $stmt->execute([$user['id']]);
                
                // Redirect to dashboard
                header("Location: doctor-dashboard.php");
                exit();
            } else {
                $error = 'Invalid username or password';
                
                // Log failed login attempt
                if ($user) {
                    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'failed_login', 'Failed login attempt')");
                    $stmt->execute([$user['id']]);
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Login | Smart Hospital Laboratory System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <style>
        :root {
            --primary-color: #1e88e5;
            --secondary-color: #26c6da;
            --dark-color: #0d47a1;
            --light-color: #e3f2fd;
            --success-color: #66bb6a;
            --danger-color: #ef5350;
            --white-color: #ffffff;
            --gray-color: #f5f5f5;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--gray-color);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-container {
            background-color: var(--white-color);
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 850px;
            display: flex;
        }

        .login-image {
            flex: 1;
            background-image: url('https://via.placeholder.com/500x600?text=Doctor+Login');
            background-size: cover;
            background-position: center;
        }

        .login-form {
            flex: 1;
            padding: 40px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: var(--dark-color);
            margin-bottom: 10px;
            font-size: 26px;
        }

        .login-header p {
            color: #777;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
        }

        .checkbox-container input {
            margin-right: 8px;
        }

        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background-color: var(--primary-color);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: var(--dark-color);
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            color: #777;
            font-size: 14px;
        }

        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .login-logo img {
            height: 50px;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                width: 90%;
            }
            
            .login-image {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-image"></div>
        <div class="login-form">
            <div class="login-header">
                <div class="login-logo">
                    <img src="https://via.placeholder.com/200x50?text=SHLS+Logo" alt="Smart Hospital Laboratory System">
                </div>
                <h1>Doctor Login</h1>
                <p>Enter your credentials to access the doctor dashboard</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form id="loginForm" method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                </div>
                <div class="remember-forgot">
                    <div class="checkbox-container">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
            <div class="login-footer">
                <p>Hospital staff? <a href="lab-staff-login.php">Laboratory Staff Login</a> | <a href="index.php">Back to Home</a></p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                Swal.fire({
                    title: 'Error',
                    text: 'Please enter both username and password',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            // Show loading state
            Swal.fire({
                title: 'Logging in...',
                text: 'Please wait while we verify your credentials',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Submit the form
            this.submit();
        });

        // Handle forgot password
        document.querySelector('.forgot-password').addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Reset Password',
                html: `
                    <div class="form-group">
                        <label for="email">Enter your email address</label>
                        <input type="email" id="email" class="form-control" placeholder="Enter your email">
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Send Reset Link',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const email = document.getElementById('email').value;
                    if (!email) {
                        Swal.showValidationMessage('Please enter your email address');
                    }
                    return email;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Here you would typically send a password reset email
                    Swal.fire({
                        title: 'Success',
                        text: 'If an account exists with this email, you will receive a password reset link.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });
    </script>
</body>
</html>