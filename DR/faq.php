<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ & Help Center - Smart Hospital Laboratory System</title>
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
        .page-header {
            background-color: #e3f2fd;
            padding: 3rem 0;
            text-align: center;
        }
        
        .page-header h2 {
            font-size: 2.5rem;
            color: #0d47a1;
            margin-bottom: 1rem;
        }
        
        .page-header p {
            max-width: 800px;
            margin: 0 auto;
            color: #555;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 0;
        }
        
        /* Help categories */
        .help-categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .category-box {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .category-box:hover {
            transform: translateY(-5px);
        }
        
        .category-icon {
            width: 60px;
            height: 60px;
            background-color: #e3f2fd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        .category-icon i {
            font-size: 24px;
            color: #1976d2;
        }
        
        .category-box h3 {
            font-size: 1.3rem;
            margin-bottom: 0.8rem;
            color: #333;
        }
        
        .category-box p {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }
        
        .category-box a {
            color: #1976d2;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
            transition: color 0.3s;
        }
        
        .category-box a:hover {
            color: #0d47a1;
        }
        
        /* FAQ section */
        .faq-section {
            margin-bottom: 3rem;
        }
        
        .section-title {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e3f2fd;
        }
        
        .faq-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 2rem;
        }
        
        .tab-btn {
            padding: 0.8rem 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            color: #666;
            position: relative;
            transition: color 0.3s;
        }
        
        .tab-btn.active {
            color: #1976d2;
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #1976d2;
        }
        
        .faq-content {
            margin-top: 2rem;
        }
        
        .faq-item {
            margin-bottom: 1.5rem;
            border-radius: 8px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .faq-question {
            padding: 1.2rem 1.5rem;
            background-color: #f8f9fa;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .faq-question:hover {
            background-color: #e9ecef;
        }
        
        .faq-question i {
            font-size: 1.2rem;
            color: #666;
            transition: transform 0.3s;
        }
        
        .faq-answer {
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out, padding 0.3s ease;
        }
        
        .faq-answer-content {
            padding: 0 1.5rem;
            color: #555;
        }
        
        .faq-item.active .faq-question {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .faq-item.active .faq-question i {
            transform: rotate(180deg);
            color: #1976d2;
        }
        
        .faq-item.active .faq-answer {
            max-height: 500px;
            padding: 1.5rem;
        }
        
        /* Contact section */
        .contact-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .contact-info {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .contact-form {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .contact-info h3, .contact-form h3 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #333;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 1.2rem;
        }
        
        .info-icon {
            margin-right: 1rem;
            width: 40px;
            height: 40px;
            background-color: #e3f2fd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .info-icon i {
            font-size: 1rem;
            color: #1976d2;
        }
        
        .info-details h4 {
            font-size: 1rem;
            margin-bottom: 0.3rem;
            color: #333;
        }
        
        .info-details p, .info-details a {
            color: #666;
            font-size: 0.95rem;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .info-details a:hover {
            color: #1976d2;
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
        
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            color: #333;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            border-color: #1976d2;
            outline: none;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .btn {
            display: inline-block;
            background-color: #1976d2;
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #0d47a1;
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
            
            .contact-section {
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

    <!-- Page Header -->
    <section class="page-header">
        <h2>FAQ & Help Center</h2>
        <p>Find answers to common questions and learn how to get the most out of our Smart Hospital Laboratory System.</p>
    </section>

    <!-- Main Content -->
    <div class="container">
        <!-- Help Categories -->
        <div class="help-categories">
            <div class="category-box">
                <div class="category-icon">
                    <i class="fas fa-user"></i>
                </div>
                <h3>For Patients</h3>
                <p>Get help with scheduling appointments, accessing test results, and managing your profile.</p>
                <a href="#patient-faq">Learn More <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="category-box">
                <div class="category-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <h3>For Doctors</h3>
                <p>Learn about requesting lab tests, viewing patient reports, and managing appointments.</p>
                <a href="#doctor-faq">Learn More <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="category-box">
                <div class="category-icon">
                    <i class="fas fa-flask"></i>
                </div>
                <h3>For Lab Staff</h3>
                <p>Understand sample tracking, test processing, and interdepartmental communication.</p>
                <a href="#lab-faq">Learn More <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="category-box">
                <div class="category-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Security & Privacy</h3>
                <p>Information about data protection, privacy policies, and secure access.</p>
                <a href="#security-faq">Learn More <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <!-- FAQ Section -->
        <section class="faq-section">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <div class="faq-tabs">
                <button class="tab-btn active" data-target="general">General</button>
                <button class="tab-btn" data-target="patient-faq">Patient</button>
                <button class="tab-btn" data-target="doctor-faq">Doctor</button>
                <button class="tab-btn" data-target="lab-faq">Lab Staff</button>
                <button class="tab-btn" data-target="security-faq">Security</button>
            </div>
            <div class="faq-content">
                <!-- General FAQ -->
                <div id="general" class="tab-content active">
                    <div class="faq-item active">
                        <div class="faq-question">
                            What is the Smart Hospital Laboratory System?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <div class="faq-answer-content">
                                <p>The Smart Hospital Laboratory System (SHLS) is a comprehensive digital solution for hospital laboratories that streamlines sample tracking, test processing, result reporting, and interdepartmental communication. It uses QR-based sample tracking to ensure accuracy and efficiency throughout the laboratory workflow.</p>
                            </div>
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">
                            How do I create an account on the system?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <div class="faq-answer-content">
                                <p>Account creation depends on your role in the hospital:</p>
                                <ul>
                                    <li><strong>Patients:</strong> Can self-register on the patient registration page with their personal and contact information.</li>
                                    <li><strong>Doctors, Lab Staff, and Administrators:</strong> Accounts are typically created by system administrators. Please contact your hospital's IT department or system administrator for account setup.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">
                            Is the system accessible on mobile devices?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <div class="faq-answer-content">
                                <p>Yes, the Smart Hospital Laboratory System is designed with a responsive interface that works on desktop computers, tablets, and smartphones. This allows users to access the system from anywhere with an internet connection.</p>
                            </div>
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">
                            How do I reset my password if I forget it?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <div class="faq-answer-content">
                                <p>To reset your password:</p>
                                <ol>
                                    <li>Click on the "Forgot Password" link on the login page</li>
                                    <li>Enter your registered email address</li>
                                    <li>Check your email for a password reset link</li>
                                    <li>Follow the link to create a new password</li>
                                </ol>
                                <p>If you don't receive the reset email, check your spam folder or contact system support.</p>
                            </div>
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">
                            What browsers are supported by the system?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <div class="faq-answer-content">
                                <p>The Smart Hospital Laboratory System works best with the latest versions of:</p>
                                <ul>
                                    <li>Google Chrome</li>
                                    <li>Mozilla Firefox</li>
                                    <li>Microsoft Edge</li>
                                    <li>Safari</li>
                                </ul>
                                <p>For optimal performance and security, please keep your browser updated to the latest version.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Other FAQ categories would be here -->
            </div>
        </section>

        <!-- Contact Support Section -->
        <section class="contact-section">
            <div class="contact-info">
                <h3>Contact Support</h3>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="info-details">
                        <h4>Phone Support</h4>
                        <p>+1 (555) 123-4567</p>
                        <p>Monday - Friday, 8am - 6pm</p>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-details">
                        <h4>Email Support</h4>
                        <a href="mailto:support@shls.example.com">support@shls.example.com</a>
                        <p>We'll respond within 24 hours</p>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-comment-alt"></i>
                    </div>
                    <div class="info-details">
                        <h4>Live Chat</h4>
                        <p>Available 24/7</p>
                        <a href="#" class="chat-link">Start Chat Now</a>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="info-details">
                        <h4>User Manuals</h4>
                        <p>Download detailed user guides</p>
                        <a href="user_guidesphp">View Guides</a>
                    </div>
                </div>
            </div>
            <div class="contact-form">
                <h3>Send Us a Message</h3>
                <form id="support-form">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="user-type">User Type</label>
                        <select id="user-type" name="user-type">
                            <option value="patient">Patient</option>
                            <option value="doctor">Doctor</option>
                            <option value="lab-staff">Laboratory Staff</option>
                            <option value="admin">Administrator</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required></textarea>
                    </div>
                    <button type="submit" class="btn">Submit Request</button>
                </form>
            </div>
        </section>
    </div>

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
        // JavaScript for FAQ accordion functionality
        document.addEventListener('DOMContentLoaded', function() {
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                
                question.addEventListener('click', () => {
                    // Toggle current item
                    item.classList.toggle('active');
                    
                    // Close other items (optional)
                    faqItems.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('active');
                        }
                    });
                });
            });
            
            // Tab functionality
            const tabButtons = document.querySelectorAll('.tab-btn');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Remove active class from all buttons
                    tabButtons.forEach(btn => {
                        btn.classList.remove('active');
                    });
                    
                    // Add active class to clicked button
                    button.classList.add('active');
                    
                    // Show corresponding content
                    const target = button.dataset.target;
                    const tabContents = document.querySelectorAll('.tab-content');
                    
                    tabContents.forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    document.getElementById(target).classList.add('active');
                });
            });
            
            // Form submission
            const supportForm = document.getElementById('support-form');
            
            supportForm.addEventListener('submit', function(event) {
                event.preventDefault();
                alert('Thank you for your message. Our support team will contact you soon!');
                supportForm.reset();
            });
        });
    </script>
</body>
</html>