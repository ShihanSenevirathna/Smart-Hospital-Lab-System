<?php
require_once 'config/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Sanitize and validate input data
        $first_name = filter_input(INPUT_POST, 'first-name', FILTER_SANITIZE_STRING);
        $last_name = filter_input(INPUT_POST, 'last-name', FILTER_SANITIZE_STRING);
        $date_of_birth = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING);
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
        $national_id = filter_input(INPUT_POST, 'national-id', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
        $state = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING);
        $zip_code = filter_input(INPUT_POST, 'zip', FILTER_SANITIZE_STRING);
        $country = filter_input(INPUT_POST, 'country', FILTER_SANITIZE_STRING);
        $blood_type = filter_input(INPUT_POST, 'blood-type', FILTER_SANITIZE_STRING);
        $allergies = filter_input(INPUT_POST, 'allergies', FILTER_SANITIZE_STRING);
        $medications = filter_input(INPUT_POST, 'medications', FILTER_SANITIZE_STRING);
        $medical_conditions = filter_input(INPUT_POST, 'medical-conditions', FILTER_SANITIZE_STRING);
        $emergency_contact = filter_input(INPUT_POST, 'emergency-contact', FILTER_SANITIZE_STRING);
        $emergency_phone = filter_input(INPUT_POST, 'emergency-phone', FILTER_SANITIZE_STRING);
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
        $security_question = filter_input(INPUT_POST, 'security-question', FILTER_SANITIZE_STRING);
        $security_answer = filter_input(INPUT_POST, 'security-answer', FILTER_SANITIZE_STRING);
        
        // Create full name for users table
        $full_name = $first_name . ' ' . $last_name;

        // Start transaction
        $pdo->beginTransaction();

        // First, insert into users table
        $sql_users = "INSERT INTO users (
            username, password, full_name, role, email, status
        ) VALUES (
            :username, :password, :full_name, 'patient', :email, 'active'
        )";
        
        $stmt_users = $pdo->prepare($sql_users);
        
        // Bind parameters for users table
        $stmt_users->bindParam(':username', $username);
        $stmt_users->bindParam(':password', $password);
        $stmt_users->bindParam(':full_name', $full_name);
        $stmt_users->bindParam(':email', $email);
        
        // Execute the users statement
        $stmt_users->execute();
        
        // Get the user ID
        $user_id = $pdo->lastInsertId();

        // Check if user_id column exists in patients table
        $check_column = $pdo->query("SHOW COLUMNS FROM patients LIKE 'user_id'");
        $column_exists = $check_column->rowCount() > 0;

        // Now insert into patients table
        if ($column_exists) {
            // If user_id column exists, include it in the insert
            $sql_patients = "INSERT INTO patients (
                user_id, first_name, last_name, date_of_birth, gender, national_id, email, phone,
                address, city, state, zip_code, country, blood_type, allergies,
                medications, medical_conditions, emergency_contact, emergency_phone,
                username, password, security_question, security_answer
            ) VALUES (
                :user_id, :first_name, :last_name, :date_of_birth, :gender, :national_id, :email, :phone,
                :address, :city, :state, :zip_code, :country, :blood_type, :allergies,
                :medications, :medical_conditions, :emergency_contact, :emergency_phone,
                :username, :password, :security_question, :security_answer
            )";
        } else {
            // If user_id column doesn't exist, don't include it in the insert
            $sql_patients = "INSERT INTO patients (
                first_name, last_name, date_of_birth, gender, national_id, email, phone,
                address, city, state, zip_code, country, blood_type, allergies,
                medications, medical_conditions, emergency_contact, emergency_phone,
                username, password, security_question, security_answer
            ) VALUES (
                :first_name, :last_name, :date_of_birth, :gender, :national_id, :email, :phone,
                :address, :city, :state, :zip_code, :country, :blood_type, :allergies,
                :medications, :medical_conditions, :emergency_contact, :emergency_phone,
                :username, :password, :security_question, :security_answer
            )";
        }

        $stmt_patients = $pdo->prepare($sql_patients);

        // Bind parameters for patients table
        if ($column_exists) {
            $stmt_patients->bindParam(':user_id', $user_id);
        }
        $stmt_patients->bindParam(':first_name', $first_name);
        $stmt_patients->bindParam(':last_name', $last_name);
        $stmt_patients->bindParam(':date_of_birth', $date_of_birth);
        $stmt_patients->bindParam(':gender', $gender);
        $stmt_patients->bindParam(':national_id', $national_id);
        $stmt_patients->bindParam(':email', $email);
        $stmt_patients->bindParam(':phone', $phone);
        $stmt_patients->bindParam(':address', $address);
        $stmt_patients->bindParam(':city', $city);
        $stmt_patients->bindParam(':state', $state);
        $stmt_patients->bindParam(':zip_code', $zip_code);
        $stmt_patients->bindParam(':country', $country);
        $stmt_patients->bindParam(':blood_type', $blood_type);
        $stmt_patients->bindParam(':allergies', $allergies);
        $stmt_patients->bindParam(':medications', $medications);
        $stmt_patients->bindParam(':medical_conditions', $medical_conditions);
        $stmt_patients->bindParam(':emergency_contact', $emergency_contact);
        $stmt_patients->bindParam(':emergency_phone', $emergency_phone);
        $stmt_patients->bindParam(':username', $username);
        $stmt_patients->bindParam(':password', $password);
        $stmt_patients->bindParam(':security_question', $security_question);
        $stmt_patients->bindParam(':security_answer', $security_answer);

        // Execute the patients statement
        $stmt_patients->execute();
        
        // Commit transaction
        $pdo->commit();

        // Return success response
        echo json_encode(['status' => 'success', 'message' => 'Registration successful!']);
        exit;

    } catch(PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Return error response
        echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage()]);
        exit;
    }
}
?> 