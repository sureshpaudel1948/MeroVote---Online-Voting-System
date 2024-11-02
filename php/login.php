<?php
// Include database configuration and utility functions
include 'db_config.php';
include 'utils.php';

// Start session
session_start();

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input values
    $phone_number = sanitize_input($_POST['phone-number']);
    $password = sanitize_input($_POST['password']);

    // Prepare the SQL statement to fetch the user by phone number
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
    $stmt->execute([$phone_number]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify if the user exists and the password matches
    if ($user && verify_password($password, $user['password'])) {
        // Set session variables for user ID and role
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on the user's role (admin or voter)
        if (is_admin()) {
            redirect('../admin_dashboard.html'); // Assuming admin has a separate dashboard
        } else {
            redirect('../dashboard.html'); // Redirect normal users to the dashboard
        }
    } else {
        // Show an error message if credentials are invalid
        echo "Invalid phone number or password!";
    }
}
?>
