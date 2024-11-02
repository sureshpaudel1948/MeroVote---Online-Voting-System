<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html'); // Redirect to login if not logged in
    exit();
}

// If admin, show admin panel
if ($_SESSION['role'] == 'admin') {
    echo "Welcome Admin!"; // You can add the admin dashboard content here
} else {
    echo "Welcome Voter!"; // You can add the voter dashboard content here
}
?>
