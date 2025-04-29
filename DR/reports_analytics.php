php<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Smart Hospital Laboratory System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #2ecc71;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #1abc9c;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }
        
        body {
            background-color: var(--light-color);
            min-height: 100vh;
            display: flex;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header i {
            font-size: 1.8rem;
            margin-right: 0.8rem;
            color: var(--secondary-color);
        }
        
        .sidebar-header h2 {
            font-size: 1.2rem;
            font-weight: 500;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .sidebar-menu h3 {
            padding: 0 1.5rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.1rem;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 0.8rem;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .menu-item.active {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .menu-item i {
            font-size: 1.1rem;
            margin-right: 0.8rem;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            color: var(--primary-color);
            font-size: 1.8rem;
        }
        
        /* Report Controls */
        .report-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.2rem;
            margin-bottom: 1.5rem;
        }
        
        .date-range {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .date-range-label {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .date-input {
            padding: 0.6rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .report-actions {
            display: flex;
            gap: 0.8rem;
        }
        
        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            border: none;
            display: flex;
            align-items: center;
        }
        
        .btn i {
            margin-right: 0.5rem;
        }
        
        .btn-secondary {
            background-color: #ddd;
            color: var(--dark-color);
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        /* Dashboard Stats */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--secondary-color);
            width: 45px;
            height: 45px;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 1rem;
            font-size: 1.3rem;
        }
        
        .stat-card:nth-child(2) .stat-icon {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--accent-color);
        }
        
        .stat-card:nth-child(3) .stat-icon {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }
        
        .stat-card:nth-child(4) .stat-icon {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }
        
        .stat-details h3 {
            color: var(--dark-color);
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        
        .stat-details p {
            color: var(--primary-color);
            font-size: 1.3rem;
            font-weight: 700;
        }
        
        .stat-trend {
            font-size: 0.8rem;
            margin-top: 0.3rem;
        }
        
        .trend-up {
            color: var(--accent-color);
        }
        
        .trend-down {
            color: var(--danger-color);
        }
        
        /* Chart Containers */
        .chart-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
        }
        
        .chart-title {
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: 500;
        }
        
        .chart-options select {
            padding: 0.5rem 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
            color: var(--dark-color);
        }
        
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }
        
        .chart-canvas {
            width: 100%;
            height: 350px;
        }
        
        /* Table Styles */
        .report-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .report-table th {
            text-align: left;
            padding: 1rem;
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }
        
        .report-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            color: var(--dark-color);
        }
        
        .report-table tr:last-child td {
            border-bottom: none;
        }
        
        .report-table tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        /* Responsive Styles */
        @media (max-width: 1200px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 991px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header h2 {
                display: none;
            }
            
            .sidebar-header {
                justify-content: center;
            }
            
            .sidebar-header i {
                margin-right: 0;
            }
            
            .sidebar-menu h3 {
                display: none;
            }
            
            .menu-item span {
                display: none;
            }
            
            .menu-item i {
                margin-right: 0;
            }
            
            .menu-item {
                justify-content: center;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .report-controls {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .date-range {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
            }
            
            .date-input {
                width: 100%;
            }
            
            .report-actions {
                width: 100%;
            }
            
            .btn {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-hospital"></i>
            <h2>SHLS Admin</h2>
        </div>
        
        <div class="sidebar-menu">
            <h3>Main Menu</h3>
            <a href="admin_dashboard.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="user_management.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>User Management</span>
            </a>
            <a href="system_configuration.php" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>System Configuration</span>
            </a>
            <a href="reports_analytics.php" class="menu-item active">
                <i class="fas fa-chart-bar"></i>
                <span>Reports & Analytics</span>
            </a>
            <a href="system_activity_logs.php" class="menu-item">
                <i class="fas fa-history"></i>
                <span>Activity Logs</span>
            </a>
            
            <h3>Other</h3>
            <a href="#" class="menu-item">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-question-circle"></i>
                <span>Help & Support</span>
            </a>
            <a href="admin_login.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1>Reports & Analytics</h1>
        </div>
        
        <!-- Report Controls -->
        <div class="report-controls">
            <div class="date-range">
                <span class="date-range-label">Date Range:</span>
                <input type="date" class="date-input" value="2025-03-01">
                <span>to</span>
                <input type="date" class="date-input" value="2025-04-03">
            </div>
            
            <div class="report-actions">
                <button class="btn btn-secondary">
                    <i class="fas fa-filter"></i>
                    Filter
                </button>
                <button class="btn btn-secondary">
                    <i class="fas fa-download"></i>
                    Export
                </button>
                <button class="btn btn-primary">
                    <i class="fas fa-print"></i>
                    Print Report
                </button>
            </div>
        </div>
        
        <!-- Dashboard Stats -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-flask"></i>
                </div>
                <div class="stat-details">
                    <h3>Total Tests</h3>
                    <p>8,742</p>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i> 15% from last month
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-file-medical-alt"></i>
                </div>
                <div class="stat-details">
                    <h3>Pending Results</h3>
                    <p>156</p>
                    <div class="stat-trend trend-down">
                        <i class="fas fa-arrow-down"></i> 8% from last month
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="stat-details">
                    <h3>Doctor Requests</h3>
                    <p>1,832</p>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i> 12% from last month
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-details">
                    <h3>Avg. Processing Time</h3>
                    <p>3.2 hours</p>
                    <div class="stat-trend trend-down">
                        <i class="fas fa-arrow-down"></i> 5% from last month
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Chart Container -->
        <div class="chart-container">
            <div class="chart-header">
                <h2 class="chart-title">Test Volume Analysis</h2>
                <div class="chart-options">
                    <select id="chartViewOption">
                        <option value="daily">Daily</option>
                        <option value="weekly" selected>Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
            </div>
            
            <canvas id="testsChart" class="chart-canvas"></canvas>
        </div>
        
        <!-- Multiple Charts Row -->
        <div class="charts-row">
            <div class="chart-container">
                <div class="chart-header">
                    <h2 class="chart-title">Test Types Distribution</h2>
                </div>
                
                <canvas id="testTypesChart" class="chart-canvas"></canvas>
            </div>
            
            <div class="chart-container">
                <div class="chart-header">
                    <h2 class="chart-title">Department Breakdown</h2>
                </div>
                
                <canvas id="departmentChart" class="chart-canvas"></canvas>
            </div>
        </div>
        
        <!-- Top Tests Table -->
        <div class="chart-container">
            <div class="chart-header">
                <h2 class="chart-title">Top Requested Tests</h2>
            </div>
            
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Test Name</th>
                        <th>Department</th>
                        <th>Count</th>
                        <th>Avg. Time</th>
                        <th>Change</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Complete Blood Count (CBC)</td>
                        <td>Hematology</td>
                        <td>1,245</td>
                        <td>2.1 hours</td>
                        <td class="trend-up">+18%</td>
                    </tr>
                    <tr>
                        <td>Basic Metabolic Panel</td>
                        <td>Chemistry</td>
                        <td>978</td>
                        <td>2.8 hours</td>
                        <td class="trend-up">+12%</td>
                    </tr>
                    <tr>
                        <td>Lipid Panel</td>
                        <td>Chemistry</td>
                        <td>864</td>
                        <td>3.2 hours</td>
                        <td class="trend-up">+5%</td>
                    </tr>
                    <tr>
                        <td>COVID-19 PCR Test</td>
                        <td>Microbiology</td>
                        <td>784</td>
                        <td>4.5 hours</td>
                        <td class="trend-down">-22%</td>
                    </tr>
                    <tr>
                        <td>Urinalysis</td>
                        <td>Urinalysis</td>
                        <td>752</td>
                        <td>1.8 hours</td>
                        <td class="trend-up">+8%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Tests Over Time Chart
            const testsCtx = document.getElementById('testsChart').getContext('2d');
            const testsChart = new Chart(testsCtx, {
                type: 'line',
                data: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'],
                    datasets: [
                        {
                            label: 'Completed Tests',
                            data: [1850, 1920, 2100, 2300, 2550],
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Pending Tests',
                            data: [220, 180, 195, 162, 156],
                            borderColor: '#f39c12',
                            backgroundColor: 'rgba(243, 156, 18, 0.1)',
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            
            // Test Types Distribution Chart
            const typesCtx = document.getElementById('testTypesChart').getContext('2d');
            const typesChart = new Chart(typesCtx, {
                type: 'bar',
                data: {
                    labels: ['Hematology', 'Chemistry', 'Microbiology', 'Urinalysis', 'Immunology', 'Other'],
                    datasets: [
                        {
                            label: 'Test Count',
                            data: [2450, 2180, 1520, 1300, 980, 312],
                            backgroundColor: [
                                'rgba(52, 152, 219, 0.7)',
                                'rgba(46, 204, 113, 0.7)',
                                'rgba(155, 89, 182, 0.7)',
                                'rgba(52, 73, 94, 0.7)',
                                'rgba(243, 156, 18, 0.7)',
                                'rgba(149, 165, 166, 0.7)'
                            ]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            
            // Department Breakdown Chart
            const deptCtx = document.getElementById('departmentChart').getContext('2d');
            const deptChart = new Chart(deptCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Cardiology', 'Pediatrics', 'Emergency', 'Internal Med', 'Other'],
                    datasets: [
                        {
                            data: [30, 25, 20, 15, 10],
                            backgroundColor: [
                                'rgba(231, 76, 60, 0.7)',
                                'rgba(52, 152, 219, 0.7)',
                                'rgba(241, 196, 15, 0.7)',
                                'rgba(46, 204, 113, 0.7)',
                                'rgba(149, 165, 166, 0.7)'
                            ],
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // Chart view option change event
            document.getElementById('chartViewOption').addEventListener('change', function(e) {
                const value = e.target.value;
                let labels, data1, data2;
                
                // This is simulated data - in a real app, you would fetch this from the server
                if (value === 'daily') {
                    labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                    data1 = [320, 380, 420, 390, 450, 280, 310];
                    data2 = [40, 35, 38, 30, 25, 15, 18];
                } else if (value === 'monthly') {
                    labels = ['Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr'];
                    data1 = [5600, 6200, 7100, 7800, 8200, 8742];
                    data2 = [650, 580, 520, 430, 280, 156];
                } else {
                    // Weekly is default
                    labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'];
                    data1 = [1850, 1920, 2100, 2300, 2550];
                    data2 = [220, 180, 195, 162, 156];
                }
                
                // Update chart data
                testsChart.data.labels = labels;
                testsChart.data.datasets[0].data = data1;
                testsChart.data.datasets[1].data = data2;
                testsChart.update();
            });
        });
    </script>
</body>
</html>