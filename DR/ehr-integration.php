<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EHR Integration | Smart Hospital Laboratory System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1e88e5;
            --secondary-color: #26c6da;
            --dark-color: #0d47a1;
            --light-color: #e3f2fd;
            --success-color: #66bb6a;
            --warning-color: #ffb74d;
            --danger-color: #ef5350;
            --white-color: #ffffff;
            --gray-color: #f5f5f5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--gray-color);
            color: #333;
            line-height: 1.6;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--dark-color);
            color: var(--white-color);
            padding: 20px 0;
            transition: all 0.3s;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header img {
            height: 40px;
            margin-right: 10px;
        }

        .sidebar-header h3 {
            font-size: 18px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu h4 {
            padding: 0 20px 10px;
            opacity: 0.7;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            text-decoration: none;
            color: var(--white-color);
        }

        .menu-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--secondary-color);
        }

        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .menu-item i {
            margin-right: 15px;
            font-size: 18px;
        }

        .doctor-profile {
            padding: 20px;
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
        }

        .doctor-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .doctor-profile-info {
            flex: 1;
        }

        .doctor-profile-info h4 {
            font-size: 14px;
        }

        .doctor-profile-info p {
            font-size: 12px;
            opacity: 0.7;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            overflow: auto;
        }

        .topbar {
            background-color: var(--white-color);
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .toggle-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #555;
        }

        .search-box {
            position: relative;
            margin: 0 20px;
            flex: 1;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 35px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .search-box i {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            color: #999;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
        }

        .topbar-actions .action-item {
            margin-left: 20px;
            font-size: 20px;
            color: #555;
            position: relative;
            cursor: pointer;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Content Styles */
        .content {
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #777;
        }

        .card {
            background-color: var(--white-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 18px;
        }

        .card-body {
            padding: 20px;
        }

        .status-box {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
        }

        .status-connected {
            background-color: rgba(102, 187, 106, 0.1);
            color: var(--success-color);
        }

        .status-disconnected {
            background-color: rgba(239, 83, 80, 0.1);
            color: var(--danger-color);
        }

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white-color);
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--dark-color);
        }

        .btn-secondary {
            background-color: var(--white-color);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-secondary:hover {
            background-color: var(--light-color);
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
            border: none;
        }

        .btn-success:hover {
            background-color: #43a047;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
            border: none;
        }

        .btn-danger:hover {
            background-color: #d32f2f;
        }

        /* EHR Integration Specific Styles */
        .ehr-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .ehr-stat-card {
            background-color: var(--white-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .ehr-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 30px;
        }

        .ehr-icon.sync {
            background-color: rgba(30, 136, 229, 0.1);
            color: var(--primary-color);
        }

        .ehr-icon.success {
            background-color: rgba(102, 187, 106, 0.1);
            color: var(--success-color);
        }

        .ehr-icon.warning {
            background-color: rgba(255, 183, 77, 0.1);
            color: var(--warning-color);
        }

        .ehr-stat-card h4 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .ehr-stat-card p {
            font-size: 14px;
            color: #777;
            margin-bottom: 10px;
        }

        .ehr-stat-card .stat-value {
            font-size: 24px;
            font-weight: 700;
        }

        .ehr-connector {
            background-color: var(--white-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .connector-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .connector-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .connector-title img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .connector-title h3 {
            font-size: 18px;
        }

        .connector-body {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .connector-info {
            flex: 1;
        }

        .connector-info p {
            margin-bottom: 10px;
            color: #777;
        }

        .connector-status {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sync-history {
            margin-top: 15px;
        }

        .sync-history h4 {
            margin-bottom: 10px;
            font-size: 16px;
        }

        .sync-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .sync-item:last-child {
            border-bottom: none;
        }

        .sync-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 15px;
        }

        .sync-status.success {
            background-color: var(--success-color);
        }

        .sync-status.error {
            background-color: var(--danger-color);
        }

        .sync-status.warning {
            background-color: var(--warning-color);
        }

        .sync-info {
            flex: 1;
        }

        .sync-info h5 {
            font-size: 15px;
            margin-bottom: 3px;
        }

        .sync-info p {
            font-size: 13px;
            color: #777;
        }

        .sync-time {
            font-size: 13px;
            color: #777;
        }

        .integration-settings {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .setting-card {
            background-color: var(--white-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }

        .setting-card h4 {
            margin-bottom: 15px;
            font-size: 16px;
            display: flex;
            align-items: center;
        }

        .setting-card h4 i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: var(--primary-color);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .setting-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .setting-name {
            font-size: 14px;
        }

        .setting-description {
            font-size: 12px;
            color: #777;
            margin-top: 3px;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .ehr-dashboard {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -250px;
                height: 100%;
                z-index: 100;
            }

            .sidebar.open {
                left: 0;
            }

            .ehr-dashboard {
                grid-template-columns: 1fr;
            }

            .integration-settings {
                grid-template-columns: 1fr;
            }

            .connector-body {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .connector-status {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="images/lab-logo.png" alt="Logo">
                <h3>SHLS</h3>
            </div>
            <div class="sidebar-menu">
                <h4>MAIN MENU</h4>
                <a href="doctor-dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="test-request.php" class="menu-item">
                    <i class="fas fa-flask"></i>
                    <span>Test Requests</span>
                </a>
                <a href="patient-reports.php" class="menu-item">
                    <i class="fas fa-file-medical-alt"></i>
                    <span>Patient Reports</span>
                </a>
                <a href="appointment-scheduling.php" class="menu-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Appointments</span>
                </a>
                <a href="ehr-integration.php" class="menu-item active">
                    <i class="fas fa-database"></i>
                    <span>EHR Integration</span>
                </a>
                <h4>SETTINGS</h4>
                <a href="doctor-profile.php" class="menu-item">
                    <i class="fas fa-user-md"></i>
                    <span>Profile</span>
                </a>
                <a href="doctor-profile.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
            <div class="doctor-profile">
                <img src="https://via.placeholder.com/40x40?text=DR" alt="Doctor">
                <div class="doctor-profile-info">
                    <h4>Dr. Sarah Johnson</h4>
                    <p>Cardiologist</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="topbar">
                <button class="toggle-btn" id="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search patients, data...">
                </div>
                <div class="topbar-actions">
                    <div class="action-item">
                        <i class="fas fa-envelope"></i>
                        <div class="notification-badge">3</div>
                    </div>
                    <div class="action-item">
                        <i class="fas fa-bell"></i>
                        <div class="notification-badge">5</div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="page-header">
                    <h2>Electronic Health Records Integration</h2>
                    <p>Manage connections between the laboratory system and hospital EHR</p>
                </div>

                <div class="ehr-dashboard">
                    <div class="ehr-stat-card">
                        <div class="ehr-icon sync">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <h4>Last Sync Time</h4>
                        <p>Latest synchronization with EHR</p>
                        <div class="stat-value">Today, 10:45 AM</div>
                    </div>
                    <div class="ehr-stat-card">
                        <div class="ehr-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h4>Synced Records</h4>
                        <p>Total records successfully synced</p>
                        <div class="stat-value">15,842</div>
                    </div>
                    <div class="ehr-stat-card">
                        <div class="ehr-icon warning">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h4>Pending Records</h4>
                        <p>Records waiting to be synced</p>
                        <div class="stat-value">24</div>
                    </div>
                    <div class="ehr-stat-card">
                        <div class="ehr-icon sync">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Active Patients</h4>
                        <p>Patients with shared EHR data</p>
                        <div class="stat-value">3,756</div>
                    </div>
                </div>

                <div class="ehr-connector">
                    <div class="connector-header">
                        <div class="connector-title">
                            <img src="https://via.placeholder.com/40x40?text=EHR" alt="EHR System">
                            <h3>Hospital EHR System</h3>
                        </div>
                        <div class="status-box status-connected">
                            <i class="fas fa-plug"></i>
                            <span>Connected</span>
                        </div>
                    </div>
                    <div class="connector-body">
                        <div class="connector-info">
                            <p><strong>Connection Type:</strong> HL7 FHIR API</p>
                            <p><strong>Endpoint URL:</strong> https://hospital-ehr-api.example.com/fhir</p>
                            <p><strong>Last Connection Test:</strong> Today, 10:45 AM (Successful)</p>
                        </div>
                        <div class="connector-status">
                            <button class="btn btn-secondary">
                                <i class="fas fa-sync-alt"></i> Test Connection
                            </button>
                            <button class="btn btn-primary">
                                <i class="fas fa-sync"></i> Sync Now
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Synchronization History</h3>
                    </div>
                    <div class="card-body">
                        <div class="sync-history">
                            <div class="sync-item">
                                <div class="sync-status success"></div>
                                <div class="sync-info">
                                    <h5>Full Synchronization Completed</h5>
                                    <p>Successfully synced 125 lab records to EHR system</p>
                                </div>
                                <div class="sync-time">Today, 10:45 AM</div>
                            </div>
                            <div class="sync-item">
                                <div class="sync-status success"></div>
                                <div class="sync-info">
                                    <h5>New Test Results Synced</h5>
                                    <p>Pushed 12 new test results to EHR system</p>
                                </div>
                                <div class="sync-time">Today, 9:30 AM</div>
                            </div>
                            <div class="sync-item">
                                <div class="sync-status warning"></div>
                                <div class="sync-info">
                                    <h5>Partial Sync Completed</h5>
                                    <p>3 records had missing patient identifiers, rest synchronized successfully</p>
                                </div>
                                <div class="sync-time">Yesterday, 4:15 PM</div>
                            </div>
                            <div class="sync-item">
                                <div class="sync-status error"></div>
                                <div class="sync-info">
                                    <h5>Synchronization Failed</h5>
                                    <p>API connection timeout, please check network connectivity</p>
                                </div>
                                <div class="sync-time">Yesterday, 2:10 PM</div>
                            </div>
                            <div class="sync-item">
                                <div class="sync-status success"></div>
                                <div class="sync-info">
                                    <h5>Patient Records Updated</h5>
                                    <p>Successfully updated 75 patient records with new lab results</p>
                                </div>
                                <div class="sync-time">Yesterday, 11:20 AM</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Integration Settings</h3>
                    </div>
                    <div class="card-body">
                        <div class="integration-settings">
                            <div class="setting-card">
                                <h4><i class="fas fa-sync-alt"></i> Synchronization</h4>
                                <div class="setting-item">
                                    <div>
                                        <div class="setting-name">Auto-Sync</div>
                                        <div class="setting-description">Automatically sync data between systems</div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" checked>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <div>
                                        <div class="setting-name">Sync Frequency</div>
                                        <div class="setting-description">How often to automatically sync data</div>
                                    </div>
                                    <select class="form-control" style="width: 120px;">
                                        <option>15 Minutes</option>
                                        <option selected>30 Minutes</option>
                                        <option>1 Hour</option>
                                        <option>2 Hours</option>
                                        <option>4 Hours</option>
                                    </select>
                                </div>
                                <div class="setting-item">
                                    <div>
                                        <div class="setting-name">Real-time Updates</div>
                                        <div class="setting-description">Push critical results immediately</div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" checked>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="setting-card">
                                <h4><i class="fas fa-shield-alt"></i> Security & Privacy</h4>
                                <div class="setting-item">
                                    <div>
                                        <div class="setting-name">Data Encryption</div>
                                        <div class="setting-description">Encrypt all data before transmission</div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" checked>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <div>
                                        <div class="setting-name">Authentication Method</div>
                                        <div class="setting-description">How to authenticate with EHR system</div>
                                    </div>
                                    <select class="form-control" style="width: 120px;">
                                        <option>Basic Auth</option>
                                        <option selected>OAuth 2.0</option>
                                        <option>API Key</option>
                                    </select>
                                </div>
                                <div class="setting-item">
                                    <div>
                                        <div class="setting-name">Audit Logging</div>
                                        <div class="setting-description">Track all data access and changes</div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" checked>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="setting-card">
                                <h4><i class="fas fa-exchange-alt"></i> Data Exchange</h4>
                                <div class="setting-item">
                                    <div>
                                        <div class="setting-name">Two-Way Sync</div>
                                        <div class="setting-description">Sync data in both directions</div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" checked>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <div>
                                        <div class="setting-name">Include Images</div>
                                        <div class="setting-description">Include imaging data in sync</div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <div>
                                        <div class="setting-name">Historical Data</div>
                                        <div class="setting-description">Include historical records in sync</div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" checked>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggleSidebar');
            
            if (window.innerWidth <= 768 && !sidebar.contains(event.target) && event.target !== toggleBtn) {
                sidebar.classList.remove('open');
            }
        });
    </script>
</body>
</html>