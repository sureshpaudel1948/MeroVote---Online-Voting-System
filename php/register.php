<?php
// Include database configuration and utility functions
include 'db_config.php';
include 'utils.php';

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and hash the input values
    $full_name = sanitize_input($_POST['name']);
    $phone_number = sanitize_input($_POST['phone-number']);
    $password = hash_password($_POST['password']);
    $role = sanitize_input($_POST['role']);  // Ensure 'role' is selected in the form

    try {
        // Prepare the SQL statement for inserting a new user
        $stmt = $pdo->prepare("INSERT INTO users (full_name, phone_number, password, role) VALUES (?, ?, ?, ?)");
        
        // Execute the statement with the provided data
        if ($stmt->execute([$full_name, $phone_number, $password, $role])) {
            // Redirect to login page upon successful registration
            redirect('../login.html');
        } else {
            // Output an error if the registration fails
            $errorInfo = $stmt->errorInfo();
            echo "Registration failed! Error: " . $errorInfo[2];
        }
    } catch (Exception $e) {
        // Catch any exceptions and output an error message
        echo "Error during registration: " . $e->getMessage();
    }
}
?>
