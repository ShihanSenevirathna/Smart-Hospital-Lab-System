<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Login - Smart Hospital Laboratory System</title>
    <style>
        /* Global styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        /* Header styling */
        header {
            background: linear-gradient(135deg, #1976d2, #0d47a1);
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 50px;
            margin-right: 15px;
        }
        
        .logo h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 25px;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        nav ul li a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        /* Login section */
        .login-section {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 180px);
            padding: 3rem 0;
        }
        
        .login-container {
            display: flex;
            width: 90%;
            max-width: 1000px;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .login-image {
            flex: 1;
            background-image: url('images/patient-login.jpg');
            background-size: cover;
            background-position: center;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(25, 118, 210, 0.6);
        }
        
        .image-text {
            position: relative;
            z-index: 1;
            color: white;
            text-align: center;
            padding: 2rem;
        }
        
        .image-text h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .image-text p {
            font-size: 1.1rem;
        }
        
        .login-form-container {
            flex: 1;
            padding: 3rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h2 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            color: #333;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #1976d2;
            outline: none;
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
        }
        
        .remember-me input {
            margin-right: 0.5rem;
        }
        
        .forgot-password a {
            color: #1976d2;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .forgot-password a:hover {
            color: #0d47a1;
        }
        
        .btn {
            display: inline-block;
            background-color: #1976d2;
            color: white;
            width: 100%;
            padding: 0.8rem 1rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
        }
        
        .btn:hover {
            background-color: #0d47a1;
        }
        
        .alternate-login {
            margin-top: 2rem;
            text-align: center;
        }
        
        .alternate-login p {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .social-login {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f5f5f5;
            color: #333;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .social-btn.google {
            color: #DB4437;
        }
        
        .social-btn.facebook {
            color: #4267B2;
        }
        
        .social-btn.apple {
            color: #000;
        }
        
        .social-btn:hover {
            background-color: #e0e0e0;
        }
        
        .register-link {
            margin-top: 2rem;
            text-align: center;
        }
        
        .register-link p {
            color: #666;
        }
        
        .register-link a {
            color: #1976d2;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .register-link a:hover {
            color: #0d47a1;
        }
        
        .login-options {
            margin-top: 2rem;
            border-top: 1px solid #eee;
            padding-top: 2rem;
        }
        
        .user-types {
            text-align: center;
        }
        
        .user-types p {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .user-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        .user-link {
            color: #1976d2;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 1px solid #1976d2;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .user-link:hover {
            background-color: #1976d2;
            color: white;
        }
        
        /* Footer */
        footer {
            background-color: #333;
            color: #fff;
            padding: 2rem 0 1rem;
        }
        
        .footer-container {
            max-width: 1200px;
            width: 90%;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }
        
        .footer-col h4 {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-col h4::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 40px;
            height: 2px;
            background-color: #2196f3;
        }
        
        .footer-col ul {
            list-style: none;
        }
        
        .footer-col ul li {
            margin-bottom: 10px;
        }
        
        .footer-col ul li a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-col ul li a:hover {
            color: #2196f3;
        }
        
        .copyright {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #444;
            font-size: 0.9rem;
            color: #aaa;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
            }
            
            nav ul {
                margin-top: 1rem;
            }
            
            .login-container {
                flex-direction: column;
            }
            
            .login-image {
                display: none;
            }
            
            .user-links {
                flex-direction: column;
            }
        }
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="header-container">
            <div class="logo">
                <img src="images/lab-logo.png" alt="SHLS Logo">
                <h1>Smart Hospital Laboratory System</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="patient_login.php">Login</a></li>
                    <li><a href="patient_registration.php">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Login Section -->
    <section class="login-section">
        <div class="login-container">
            <div class="login-image">
                <div class="image-text">
                    <h2>Welcome Back</h2>
                    <p>Access your lab reports, appointments, and medical information securely.</p>
                </div>
            </div>
            <div class="login-form-container">
                <div class="login-header">
                    <h2>Patient Login</h2>
                    <p>Enter your credentials to access your account</p>
                </div>
                <form id="login-form" action="process_patient_login.php" method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    <div class="remember-forgot">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <div class="forgot-password">
                            <a href="forgot_password.html">Forgot password?</a>
                        </div>
                    </div>
                    <button type="submit" class="btn">Log In</button>
                </form>
                <div class="alternate-login">
                    <p>Or login with</p>
                    <div class="social-login">
                        <a href="#" class="social-btn google"><i class="fab fa-google"></i></a>
                        <a href="#" class="social-btn facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-btn apple"><i class="fab fa-apple"></i></a>
                    </div>
                </div>
                <div class="register-link">
                    <p>Don't have an account? <a href="patient_registration.php">Register Now</a></p>
                </div>
                <div class="login-options">
                    <div class="user-types">
                        <p>Login as:</p>
                        <div class="user-links">
                            <a href="patient_login.php" class="user-link">Patient</a>
                            <a href="doctor-login.php" class="user-link">Doctor</a>
                            <a href="lab-staff-login.php" class="user-link">Lab Staff</a>
                            <a href="admin_login.php" class="user-link">Admin</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-col">
                <h4>Smart Hospital Lab</h4>
                <ul>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="services.php">Our Services</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Get Help</h4>
                <ul>
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="support.php">Support Center</a></li>
                    <li><a href="user_guides.php">User Guides</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>User Portals</h4>
                <ul>
                    <li><a href="patient_login.php">Patient Login</a></li>
                    <li><a href="doctor_login.php">Doctor Login</a></li>
                    <li><a href="lab_login.php">Lab Staff Login</a></li>
                    <li><a href="admin_login.php">Admin Login</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Connect With Us</h4>
                <ul>
                    <li><a href="#"><i class="fab fa-facebook-f"></i> Facebook</a></li>
                    <li><a href="#"><i class="fab fa-twitter"></i> Twitter</a></li>
                    <li><a href="#"><i class="fab fa-linkedin-in"></i> LinkedIn</a></li>
                    <li><a href="#"><i class="fab fa-instagram"></i> Instagram</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2023 Smart Hospital Laboratory System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('login-form');
            
            loginForm.addEventListener('submit', function(event) {
                event.preventDefault();
                
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                const remember = document.getElementById('remember').checked;
                
                // Create FormData object
                const formData = new FormData();
                formData.append('email', email);
                formData.append('password', password);
                formData.append('remember', remember);
                
                // Show loading state
                Swal.fire({
                    title: 'Logging in...',
                    text: 'Please wait while we verify your credentials',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Submit form using fetch
                fetch('process_patient_login.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Show success message and redirect
                        Swal.fire({
                            title: 'Success!',
                            text: data.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = data.redirect;
                        });
                    } else {
                        // Show error message
                        Swal.fire({
                            title: 'Error!',
                            text: data.message,
                            icon: 'error',
                            confirmButtonText: 'Try Again'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred during login. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'Try Again'
                    });
                });
            });
        });
    </script>
</body>
</html>