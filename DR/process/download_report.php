<?php
session_start();
require_once '../config/db_connection.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: ../doctor-login.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];

// Check if report ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../patient-reports.php");
    exit();
}

$report_id = $_GET['id'];

// Fetch report details
$stmt = $pdo->prepare("
    SELECT tr.*, p.id as pat_id, p.first_name, p.last_name, p.email, p.phone, p.date_of_birth, p.gender,
           t.name as test_name, t.price, t.category_id, tc.name as category_name,
           tr.created_at, tr.status, tr.results
    FROM test_results tr
    JOIN patients p ON tr.patient_id = p.id
    JOIN tests t ON tr.test_id = t.id
    JOIN test_categories tc ON t.category_id = tc.id
    WHERE tr.id = ? AND tr.doctor_id = ?
");
$stmt->execute([$report_id, $doctor_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

// If report not found or not belonging to this doctor, redirect
if (!$report) {
    header("Location: ../patient-reports.php");
    exit();
}

// Parse the results JSON if available
$results = [];
if (!empty($report['results'])) {
    // Check if results is already an array
    if (is_array($report['results'])) {
        $results = $report['results'];
    } else {
        // Try to decode JSON
        $decoded_results = json_decode($report['results'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_results)) {
            $results = $decoded_results;
        } else {
            // If not valid JSON, treat as a simple string value
            $results = ['Result' => ['value' => $report['results'], 'unit' => '']];
        }
    }
}

// Calculate patient age
$dob = new DateTime($report['date_of_birth']);
$now = new DateTime();
$age = $now->diff($dob)->y;

// Check if we should generate PDF or show interactive HTML
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

if ($format === 'pdf') {
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Test_Report_' . $report['pat_id'] . '_' . date('Y-m-d') . '.pdf"');
    
    // Include TCPDF library if available, otherwise redirect to HTML version
    if (file_exists('../vendor/tecnickcom/tcpdf/tcpdf.php')) {
        require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Hospital Laboratory System');
        $pdf->SetAuthor('Dr. ' . $_SESSION['doctor_name']);
        $pdf->SetTitle('Test Report - ' . $report['test_name']);
        
        // Set default header data
        $pdf->SetHeaderData('', 0, 'Hospital Laboratory System', 'Test Report');
        
        // Set header and footer fonts
        $pdf->setHeaderFont(['helvetica', '', 12]);
        $pdf->setFooterFont(['helvetica', '', 10]);
        
        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont('courier');
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 12);
        
        // Hospital and Report Header
        $pdf->Cell(0, 10, 'HOSPITAL LABORATORY SYSTEM', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'TEST REPORT', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Patient Information
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'PATIENT INFORMATION', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        
        $pdf->Cell(50, 7, 'Patient ID:', 0, 0);
        $pdf->Cell(0, 7, 'PAT-' . sprintf('%04d', $report['pat_id']), 0, 1);
        
        $pdf->Cell(50, 7, 'Patient Name:', 0, 0);
        $pdf->Cell(0, 7, $report['first_name'] . ' ' . $report['last_name'], 0, 1);
        
        $pdf->Cell(50, 7, 'Age/Gender:', 0, 0);
        $pdf->Cell(0, 7, $age . ' Years, ' . ucfirst($report['gender']), 0, 1);
        
        $pdf->Cell(50, 7, 'Contact:', 0, 0);
        $pdf->Cell(0, 7, $report['phone'] . ' / ' . $report['email'], 0, 1);
        
        $pdf->Ln(5);
        
        // Test Information
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'TEST INFORMATION', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        
        $pdf->Cell(50, 7, 'Test Name:', 0, 0);
        $pdf->Cell(0, 7, $report['test_name'], 0, 1);
        
        $pdf->Cell(50, 7, 'Category:', 0, 0);
        $pdf->Cell(0, 7, $report['category_name'], 0, 1);
        
        $pdf->Cell(50, 7, 'Date:', 0, 0);
        $pdf->Cell(0, 7, date('F d, Y', strtotime($report['created_at'])), 0, 1);
        
        $pdf->Cell(50, 7, 'Status:', 0, 0);
        $pdf->Cell(0, 7, ucfirst($report['status']), 0, 1);
        
        $pdf->Ln(5);
        
        // Test Results
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'TEST RESULTS', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        
        if (empty($results)) {
            $pdf->Cell(0, 7, 'No detailed results available for this test.', 0, 1);
        } else {
            // Table header
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(80, 7, 'Parameter', 1, 0, 'C', true);
            $pdf->Cell(50, 7, 'Value', 1, 0, 'C', true);
            $pdf->Cell(50, 7, 'Reference', 1, 1, 'C', true);
            
            // Table data
            foreach ($results as $param => $value) {
                if (is_array($value) && isset($value['value'])) {
                    $pdf->Cell(80, 7, $param, 1, 0);
                    $pdf->Cell(50, 7, $value['value'] . ' ' . ($value['unit'] ?? ''), 1, 0);
                    $pdf->Cell(50, 7, $value['reference'] ?? 'N/A', 1, 1);
                } else {
                    $pdf->Cell(80, 7, $param, 1, 0);
                    $pdf->Cell(50, 7, is_array($value) ? json_encode($value) : $value, 1, 0);
                    $pdf->Cell(50, 7, 'N/A', 1, 1);
                }
            }
        }
        
        $pdf->Ln(10);
        
        // Doctor's Signature
        $pdf->Cell(0, 7, 'Doctor\'s Signature: _____________________', 0, 1, 'R');
        $pdf->Cell(0, 7, 'Date: _____________________', 0, 1, 'R');
        
        // Output the PDF
        $pdf->Output('Test_Report_' . $report['pat_id'] . '_' . date('Y-m-d') . '.pdf', 'D');
    } else {
        // If TCPDF is not available, redirect to HTML version
        header("Location: download_report.php?id=" . $report_id . "&format=html");
        exit();
    }
} else {
    // Interactive HTML version
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Test Report - <?php echo htmlspecialchars($report['test_name']); ?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                padding: 20px;
            }
            
            .container {
                max-width: 1000px;
                margin: 0 auto;
                background-color: var(--white-color);
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }
            
            .header {
                background-color: var(--dark-color);
                color: var(--white-color);
                padding: 20px;
                text-align: center;
            }
            
            .header h1 {
                font-size: 24px;
                margin-bottom: 5px;
            }
            
            .header h2 {
                font-size: 18px;
                font-weight: normal;
            }
            
            .report-content {
                padding: 20px;
            }
            
            .section {
                margin-bottom: 30px;
                border-bottom: 1px solid #eee;
                padding-bottom: 20px;
            }
            
            .section:last-child {
                border-bottom: none;
            }
            
            .section-title {
                font-size: 18px;
                font-weight: bold;
                color: var(--primary-color);
                margin-bottom: 15px;
                padding-bottom: 5px;
                border-bottom: 2px solid var(--light-color);
            }
            
            .info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 15px;
            }
            
            .info-item {
                display: flex;
                flex-direction: column;
            }
            
            .info-label {
                font-weight: bold;
                color: #666;
                font-size: 14px;
            }
            
            .info-value {
                font-size: 16px;
            }
            
            .results-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
            }
            
            .results-table th, .results-table td {
                padding: 12px 15px;
                text-align: left;
                border-bottom: 1px solid #eee;
            }
            
            .results-table th {
                background-color: var(--light-color);
                font-weight: bold;
                color: var(--dark-color);
            }
            
            .results-table tr:hover {
                background-color: #f9f9f9;
            }
            
            .status-badge {
                display: inline-block;
                padding: 5px 10px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: bold;
            }
            
            .status-normal {
                background-color: #e8f5e9;
                color: #388e3c;
            }
            
            .status-abnormal {
                background-color: #ffebee;
                color: #d32f2f;
            }
            
            .status-pending {
                background-color: #fff8e1;
                color: #ff8f00;
            }
            
            .signature-section {
                margin-top: 40px;
                display: flex;
                justify-content: flex-end;
            }
            
            .signature-box {
                width: 250px;
                text-align: center;
            }
            
            .signature-line {
                border-top: 1px solid #333;
                margin-top: 50px;
                margin-bottom: 5px;
            }
            
            .signature-label {
                font-size: 14px;
                color: #666;
            }
            
            .actions {
                display: flex;
                justify-content: center;
                gap: 15px;
                margin-top: 30px;
            }
            
            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 10px 20px;
                border-radius: 5px;
                font-weight: bold;
                text-decoration: none;
                cursor: pointer;
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
            
            @media print {
                body {
                    background-color: white;
                    padding: 0;
                }
                
                .container {
                    box-shadow: none;
                    border-radius: 0;
                }
                
                .actions {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>HOSPITAL LABORATORY SYSTEM</h1>
                <h2>TEST REPORT</h2>
            </div>
            
            <div class="report-content">
                <div class="section">
                    <div class="section-title">PATIENT INFORMATION</div>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Patient ID</span>
                            <span class="info-value">PAT-<?php echo sprintf('%04d', $report['pat_id']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Patient Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Age/Gender</span>
                            <span class="info-value"><?php echo $age; ?> Years, <?php echo ucfirst($report['gender']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Contact</span>
                            <span class="info-value"><?php echo htmlspecialchars($report['phone'] . ' / ' . $report['email']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">TEST INFORMATION</div>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Test Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($report['test_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Category</span>
                            <span class="info-value"><?php echo htmlspecialchars($report['category_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date</span>
                            <span class="info-value"><?php echo date('F d, Y', strtotime($report['created_at'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="info-value">
                                <span class="status-badge status-<?php echo $report['status']; ?>">
                                    <?php echo ucfirst($report['status']); ?>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">TEST RESULTS</div>
                    
                    <?php if (empty($results)): ?>
                        <p>No detailed results available for this test.</p>
                    <?php else: ?>
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Value</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $param => $value): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($param); ?></td>
                                        <td>
                                            <?php if (is_array($value) && isset($value['value'])): ?>
                                                <?php echo htmlspecialchars($value['value']); ?> 
                                                <?php if (!empty($value['unit'])): ?>
                                                    <span class="unit"><?php echo htmlspecialchars($value['unit']); ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars(is_array($value) ? json_encode($value) : $value); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (is_array($value) && isset($value['reference'])): ?>
                                                <?php echo htmlspecialchars($value['reference']); ?>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <div class="signature-section">
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div class="signature-label">Doctor's Signature</div>
                    </div>
                </div>
                
                <div class="signature-section">
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div class="signature-label">Date</div>
                    </div>
                </div>
                
                <div class="actions">
                    <a href="download_report.php?id=<?php echo $report_id; ?>&format=pdf" class="btn btn-primary">
                        <i class="fas fa-file-pdf"></i> Download PDF
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </div>
        </div>
        
        <script>
            // Add any interactive features here
            document.addEventListener('DOMContentLoaded', function() {
                // Highlight abnormal results if any
                const tableRows = document.querySelectorAll('.results-table tbody tr');
                tableRows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    if (cells.length > 0) {
                        const valueCell = cells[1];
                        if (valueCell.textContent.includes('High') || valueCell.textContent.includes('Low')) {
                            row.style.backgroundColor = '#fff3e0';
                        }
                    }
                });
            });
        </script>
    </body>
    </html>
    <?php
}
?> 