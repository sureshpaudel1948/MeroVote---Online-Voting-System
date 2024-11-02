<?php
// utils.php

// Sanitize input to prevent SQL injection or XSS attacks
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Redirect users to a specific location
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

// Check if a user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if the logged-in user is an admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if the logged-in user is a voter
function is_voter() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'voter';
}

// Hash password (in case you want to centralize password hashing)
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Verify password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Fetch user by phone number
function get_user_by_phone($pdo, $phone_number) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
    $stmt->execute([$phone_number]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
