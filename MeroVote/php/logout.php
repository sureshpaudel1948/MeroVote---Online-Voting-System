<?php
session_start();
// Unset all session variables
$_SESSION = array();
// If there's a session cookie, remove it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
// Destroy the session.
session_destroy();

// Redirect the user to the login page (or otp-api.php if needed)
header("Location: voter_login.php");
exit();
?>
