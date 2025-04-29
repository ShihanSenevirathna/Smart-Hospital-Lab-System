<?php
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL without selecting a database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if database exists
    $result = $pdo->query("SHOW DATABASES LIKE 'shls_db'");
    if ($result->rowCount() > 0) {
        echo "Database 'shls_db' exists.\n";
        
        // Select the database
        $pdo->exec("USE shls_db");
        
        // Check if patients table exists
        $result = $pdo->query("SHOW TABLES LIKE 'patients'");
        if ($result->rowCount() > 0) {
            echo "Table 'patients' exists.\n";
            
            // Check if there are any patients
            $result = $pdo->query("SELECT COUNT(*) FROM patients");
            $count = $result->fetchColumn();
            echo "Number of patients in database: " . $count . "\n";
            
            // Show first patient if exists
            if ($count > 0) {
                $result = $pdo->query("SELECT id, first_name, last_name, email FROM patients LIMIT 1");
                $patient = $result->fetch(PDO::FETCH_ASSOC);
                echo "First patient:\n";
                echo "ID: " . $patient['id'] . "\n";
                echo "Name: " . $patient['first_name'] . " " . $patient['last_name'] . "\n";
                echo "Email: " . $patient['email'] . "\n";
            }
        } else {
            echo "Table 'patients' does not exist.\n";
        }
    } else {
        echo "Database 'shls_db' does not exist.\n";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 