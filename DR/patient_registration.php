<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - Smart Hospital Laboratory System</title>
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
        
        /* Registration section */
        .registration-section {
            padding: 3rem 0;
        }
        
        .registration-container {
            width: 90%;
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .registration-header {
            background-color: #e3f2fd;
            padding: 2rem;
            text-align: center;
        }
        
        .registration-header h2 {
            font-size: 2rem;
            color: #0d47a1;
            margin-bottom: 0.5rem;
        }
        
        .registration-header p {
            color: #555;
        }
        
        .registration-form {
            padding: 2rem;
        }
        
        .form-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .form-steps::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #e0e0e0;
            transform: translateY(-50%);
            z-index: 1;
        }
        
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            color: #666;
            position: relative;
            z-index: 2;
        }
        
        .step.active {
            background-color: #1976d2;
            color: white;
        }
        
        .step-label {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 5px;
            font-size: 0.8rem;
            color: #666;
            white-space: nowrap;
        }
        
        .step.active .step-label {
            color: #1976d2;
            font-weight: 500;
        }
        
        .form-page {
            display: none;
        }
        
        .form-page.active {
            display: block;
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
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            color: #333;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #1976d2;
            outline: none;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-prev {
            background-color: #e0e0e0;
            color: #333;
        }
        
        .btn-prev:hover {
            background-color: #d0d0d0;
        }
        
        .btn-next, .btn-submit {
            background-color: #1976d2;
            color: white;
        }
        
        .btn-next:hover, .btn-submit:hover {
            background-color: #0d47a1;
        }
        
        .agreement {
            margin-top: 1.5rem;
        }
        
        .agreement input {
            margin-right: 0.5rem;
        }
        
        .agreement label {
            font-size: 0.9rem;
            color: #555;
        }
        
        .agreement label a {
            color: #1976d2;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .agreement label a:hover {
            color: #0d47a1;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #555;
        }
        
        .login-link a {
            color: #1976d2;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .login-link a:hover {
            color: #0d47a1;
        }
        
        .form-subtitle {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        /* Footer */
        footer {
            background-color: #333;
            color: #fff;
            padding: 2rem 0 1rem;
            margin-top: 3rem;
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
        
        /* Success message */
        .success-message {
            text-align: center;
            padding: 2rem;
            display: none;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background-color: #4caf50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .success-icon i {
            font-size: 40px;
            color: white;
        }
        
        .success-message h3 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1rem;
        }
        
        .success-message p {
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
            }
            
            nav ul {
                margin-top: 1rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .step-label {
                font-size: 0.7rem;
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

    <!-- Registration Section -->
    <section class="registration-section">
        <div class="registration-container">
            <div class="registration-header">
                <h2>Patient Registration</h2>
                <p>Create your account to access lab tests, reports, and appointments</p>
            </div>
            
            <!-- Multi-step form with progress indicator -->
            <div class="registration-form">
                <div class="form-steps">
                    <div class="step active" data-step="1">
                        1
                        <span class="step-label">Personal Info</span>
                    </div>
                    <div class="step" data-step="2">
                        2
                        <span class="step-label">Contact Details</span>
                    </div>
                    <div class="step" data-step="3">
                        3
                        <span class="step-label">Medical Info</span>
                    </div>
                    <div class="step" data-step="4">
                        4
                        <span class="step-label">Account Setup</span>
                    </div>
                </div>
                
                <form id="registration-form" action="process_patient_registration.php" method="POST">
                    <!-- Step 1: Personal Information -->
                    <div class="form-page active" data-page="1">
                        <h3 class="form-subtitle">Personal Information</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first-name">First Name*</label>
                                <input type="text" id="first-name" name="first-name" required>
                            </div>
                            <div class="form-group">
                                <label for="last-name">Last Name*</label>
                                <input type="text" id="last-name" name="last-name" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="dob">Date of Birth*</label>
                                <input type="date" id="dob" name="dob" required>
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender*</label>
                                <select id="gender" name="gender" required>
                                    <option value="">Select gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                    <option value="prefer-not-to-say">Prefer not to say</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="national-id">National ID / Passport Number*</label>
                            <input type="text" id="national-id" name="national-id" required>
                        </div>
                        
                        <div class="btn-container">
                            <div></div> <!-- Empty div for flex alignment -->
                            <button type="button" class="btn btn-next" data-next="2">Next Step</button>
                        </div>
                    </div>
                    
                    <!-- Step 2: Contact Details -->
                    <div class="form-page" data-page="2">
                        <h3 class="form-subtitle">Contact Details</h3>
                        
                        <div class="form-group">
                            <label for="email">Email Address*</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number*</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address*</label>
                            <input type="text" id="address" name="address" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City*</label>
                                <input type="text" id="city" name="city" required>
                            </div>
                            <div class="form-group">
                                <label for="state">State/Province*</label>
                                <input type="text" id="state" name="state" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="zip">ZIP / Postal Code*</label>
                                <input type="text" id="zip" name="zip" required>
                            </div>
                            <div class="form-group">
                                <label for="country">Country*</label>
                                <select id="country" name="country" required>
                                    <option value="">Select country</option>
                                    <option value="us">United States</option>
                                    <option value="ca">Canada</option>
                                    <option value="uk">United Kingdom</option>
                                    <option value="au">Australia</option>
                                    <!-- More countries would be added here -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="btn-container">
                            <button type="button" class="btn btn-prev" data-prev="1">Previous</button>
                            <button type="button" class="btn btn-next" data-next="3">Next Step</button>
                        </div>
                    </div>
                    
                    <!-- Step 3: Medical Information -->
                    <div class="form-page" data-page="3">
                        <h3 class="form-subtitle">Medical Information</h3>
                        
                        <div class="form-group">
                            <label for="blood-type">Blood Type</label>
                            <select id="blood-type" name="blood-type">
                                <option value="">Select blood type (if known)</option>
                                <option value="a-positive">A+</option>
                                <option value="a-negative">A-</option>
                                <option value="b-positive">B+</option>
                                <option value="b-negative">B-</option>
                                <option value="ab-positive">AB+</option>
                                <option value="ab-negative">AB-</option>
                                <option value="o-positive">O+</option>
                                <option value="o-negative">O-</option>
                                <option value="unknown">Unknown</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="allergies">Known Allergies</label>
                            <textarea id="allergies" name="allergies" rows="3" placeholder="Enter any known allergies (medication, food, etc.)"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="medications">Current Medications</label>
                            <textarea id="medications" name="medications" rows="3" placeholder="List any medications you are currently taking"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="medical-conditions">Pre-existing Medical Conditions</label>
                            <textarea id="medical-conditions" name="medical-conditions" rows="3" placeholder="Enter any pre-existing medical conditions"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="emergency-contact">Emergency Contact Name</label>
                            <input type="text" id="emergency-contact" name="emergency-contact">
                        </div>
                        
                        <div class="form-group">
                            <label for="emergency-phone">Emergency Contact Phone</label>
                            <input type="tel" id="emergency-phone" name="emergency-phone">
                        </div>
                        
                        <div class="btn-container">
                            <button type="button" class="btn btn-prev" data-prev="2">Previous</button>
                            <button type="button" class="btn btn-next" data-next="4">Next Step</button>
                        </div>
                    </div>
                    
                    <!-- Step 4: Account Setup -->
                    <div class="form-page" data-page="4">
                        <h3 class="form-subtitle">Account Setup</h3>
                        
                        <div class="form-group">
                            <label for="username">Username*</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password*</label>
                            <input type="password" id="password" name="password" required>
                            <small>Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm-password">Confirm Password*</label>
                            <input type="password" id="confirm-password" name="confirm-password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="security-question">Security Question*</label>
                            <select id="security-question" name="security-question" required>
                                <option value="">Select a security question</option>
                                <option value="first-pet">What was the name of your first pet?</option>
                                <option value="mother-maiden">What is your mother's maiden name?</option>
                                <option value="birth-city">In what city were you born?</option>
                                <option value="first-school">What was the name of your first school?</option>
                                <option value="favorite-food">What is your favorite food?</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="security-answer">Security Answer*</label>
                            <input type="text" id="security-answer" name="security-answer" required>
                        </div>
                        
                        <div class="agreement">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a>*</label>
                        </div>
                        
                        <div class="btn-container">
                            <button type="button" class="btn btn-prev" data-prev="3">Previous</button>
                            <button type="submit" class="btn btn-submit">Create Account</button>
                        </div>
                    </div>
                </form>
                
                <!-- Success Message (Initially Hidden) -->
                <div class="success-message" id="success-message">
                    <div class="success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h3>Registration Successful!</h3>
                    <p>Your account has been created successfully. You can now log in to access your account.</p>
                    <a href="patient_login.php" class="btn btn-next">Go to Login</a>
                </div>
                
                <div class="login-link">
                    <p>Already have an account? <a href="patient_login.php">Login here</a></p>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Multi-step form navigation
            const nextButtons = document.querySelectorAll('.btn-next');
            const prevButtons = document.querySelectorAll('.btn-prev');
            const formPages = document.querySelectorAll('.form-page');
            const steps = document.querySelectorAll('.step');
            const registrationForm = document.getElementById('registration-form');
            const successMessage = document.getElementById('success-message');
            
            // Next button click event
            nextButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const currentPage = parseInt(this.getAttribute('data-next')) - 1;
                    const nextPage = parseInt(this.getAttribute('data-next'));
                    
                    // Validate current page fields
                    const currentFormPage = document.querySelector(`.form-page[data-page="${currentPage}"]`);
                    const requiredFields = currentFormPage.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value) {
                            isValid = false;
                            field.style.borderColor = '#ff3860';
                        } else {
                            field.style.borderColor = '#ddd';
                        }
                    });
                    
                    if (!isValid) {
                        alert('Please fill in all required fields.');
                        return;
                    }
                    
                    // If validations pass, move to next step
                    formPages.forEach(page => {
                        page.classList.remove('active');
                    });
                    
                    document.querySelector(`.form-page[data-page="${nextPage}"]`).classList.add('active');
                    
                    // Update step indicators
                    steps.forEach(step => {
                        if (parseInt(step.getAttribute('data-step')) <= nextPage) {
                            step.classList.add('active');
                        } else {
                            step.classList.remove('active');
                        }
                    });
                });
            });
            
            // Previous button click event
            prevButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const prevPage = parseInt(this.getAttribute('data-prev'));
                    
                    formPages.forEach(page => {
                        page.classList.remove('active');
                    });
                    
                    document.querySelector(`.form-page[data-page="${prevPage}"]`).classList.add('active');
                    
                    // Update step indicators
                    steps.forEach(step => {
                        if (parseInt(step.getAttribute('data-step')) <= prevPage) {
                            step.classList.add('active');
                        } else {
                            step.classList.remove('active');
                        }
                    });
                });
            });
            
            // Form submission
            registrationForm.addEventListener('submit', function(event) {
                event.preventDefault();
                
                // Validate password matching
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm-password').value;
                
                if (password !== confirmPassword) {
                    alert('Passwords do not match. Please try again.');
                    return;
                }
                
                // Validate terms agreement
                const termsChecked = document.getElementById('terms').checked;
                
                if (!termsChecked) {
                    alert('Please agree to the Terms of Service and Privacy Policy to continue.');
                    return;
                }

                // Create FormData object
                const formData = new FormData(this);

                // Submit form using fetch
                fetch('process_patient_registration.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Show success message
                        registrationForm.style.display = 'none';
                        successMessage.style.display = 'block';
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred during registration. Please try again.');
                });
            });
            
            // Field validation on blur
            const requiredFields = document.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                field.addEventListener('blur', function() {
                    if (!this.value) {
                        this.style.borderColor = '#ff3860';
                    } else {
                        this.style.borderColor = '#ddd';
                    }
                });
            });
            
            // Password strength validation
            const passwordField = document.getElementById('password');
            
            passwordField.addEventListener('input', function() {
                const password = this.value;
                const hasUpperCase = /[A-Z]/.test(password);
                const hasLowerCase = /[a-z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                const isLongEnough = password.length >= 8;
                
                if (isLongEnough && hasUpperCase && hasLowerCase && hasNumber && hasSpecial) {
                    this.style.borderColor = '#4caf50';
                } else {
                    this.style.borderColor = '#ddd';
                }
            });
        });
    </script>
</body>
</html>