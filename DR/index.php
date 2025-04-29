<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Hospital Laboratory System</title>
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
        
        /* Main content */
        .hero {
            background: url('images/lab-hero.jpg') no-repeat center center;
            background-size: cover;
            height: 500px;
            display: flex;
            align-items: center;
            position: relative;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .hero-content {
            max-width: 1200px;
            width: 90%;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            color: white;
        }
        
        .hero-content h2 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .hero-content p {
            font-size: 1.2rem;
            max-width: 600px;
            margin-bottom: 2rem;
        }
        
        .btn {
            display: inline-block;
            background-color: #2196f3;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #0d8aee;
        }
        
        /* User category section */
        .user-categories {
            padding: 4rem 0;
            max-width: 1200px;
            width: 90%;
            margin: 0 auto;
        }
        
        .section-heading {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .section-heading h2 {
            font-size: 2.2rem;
            color: #333;
            margin-bottom: 1rem;
        }
        
        .section-heading p {
            color: #666;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .category-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .category-card:hover {
            transform: translateY(-10px);
        }
        
        .category-img {
            height: 180px;
            background-size: cover;
            background-position: center;
        }
        
        .patient-img {
            background-image: url('images/patient.jpg');
        }
        
        .doctor-img {
            background-image: url('images/doctor.jpg');
        }
        
        .lab-img {
            background-image: url('images/lab-staff.jpg');
        }
        
        .admin-img {
            background-image: url('images/admin.jpg');
        }
        
        .category-content {
            padding: 1.5rem;
        }
        
        .category-content h3 {
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
            color: #1976d2;
        }
        
        .category-content p {
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        /* Features section */
        .features {
            background-color: #e8f5fe;
            padding: 4rem 0;
        }
        
        .features-container {
            max-width: 1200px;
            width: 90%;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .feature-item {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            background-color: #e3f2fd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .feature-icon i {
            font-size: 30px;
            color: #1976d2;
        }
        
        .feature-item h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .feature-item p {
            color: #666;
        }
        
        /* Footer */
        footer {
            background-color: #333;
            color: #fff;
            padding: 3rem 0 2rem;
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
            padding-top: 2rem;
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
            
            .hero-content h2 {
                font-size: 2rem;
            }
            
            .categories-grid, .features-grid {
                grid-template-columns: 1fr;
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h2>Modern Healthcare Laboratory Management</h2>
            <p>Streamline your laboratory operations with our QR-based sample tracking system. Enhance accuracy, improve efficiency, and provide better patient care.</p>
            <a href="patient_registration.php" class="btn">Get Started</a>
        </div>
    </section>

    <!-- User Categories Section -->
    <section class="user-categories">
        <div class="section-heading">
            <h2>Choose Your Role</h2>
            <p>Our system is designed for different user roles within the hospital ecosystem, each with tailored features and dashboards.</p>
        </div>
        <div class="categories-grid">
            <div class="category-card">
                <div class="category-img patient-img"></div>
                <div class="category-content">
                    <h3>Patients</h3>
                    <p>Schedule appointments, access test results, and manage your medical information securely.</p>
                    <a href="patient_login.php" class="btn">Patient Portal</a>
                </div>
            </div>
            <div class="category-card">
                <div class="category-img doctor-img"></div>
                <div class="category-content">
                    <h3>Doctors</h3>
                    <p>Request lab tests, view patient reports, and schedule appointments efficiently.</p>
                    <a href="doctor-login.php" class="btn">Doctor Portal</a>
                </div>
            </div>
            <div class="category-card">
                <div class="category-img lab-img"></div>
                <div class="category-content">
                    <h3>Laboratory Staff</h3>
                    <p>Track samples, process tests, and communicate with doctors and patients.</p>
                    <a href="lab-staff-login.php" class="btn">Lab Portal</a>
                </div>
            </div>
            <div class="category-card">
                <div class="category-img admin-img"></div>
                <div class="category-content">
                    <h3>Administrators</h3>
                    <p>Manage system settings, user roles, and monitor performance analytics.</p>
                    <a href="admin_login.php" class="btn">Admin Portal</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="features-container">
            <div class="section-heading">
                <h2>Key Features</h2>
                <p>Our Smart Hospital Laboratory System provides innovative solutions for modern healthcare challenges.</p>
            </div>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <h3>QR-Based Sample Tracking</h3>
                    <p>Eliminate errors with unique QR codes for each sample, ensuring accurate tracking throughout processing.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>Appointment Management</h3>
                    <p>Schedule and manage lab test appointments with automated reminders for patients.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-file-medical-alt"></i>
                    </div>
                    <h3>Digital Reports</h3>
                    <p>Access test results digitally with secure download options and historical data tracking.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <h3>EHR Integration</h3>
                    <p>Seamless connection with Electronic Health Records for comprehensive patient care.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3>Interdepartmental Communication</h3>
                    <p>Facilitate secure messaging between laboratory staff, doctors, and administrators.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Secure & Compliant</h3>
                    <p>Built with data protection and healthcare compliance regulations in mind.</p>
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

    <script>
        // JavaScript could be added here for additional functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Check if the browser supports service workers for offline functionality
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('service-worker.js').then(function() {
                    console.log('Service Worker registered successfully');
                }).catch(function(error) {
                    console.log('Service Worker registration failed:', error);
                });
            }
        });
    </script>
</body>
</html>