<?php
// Enable output buffering to prevent issues with headers
ob_start();

// Include database configuration and utility functions
include 'db_config.php';
include 'utils.php';

// Start session
session_start();

// Initialize variables for form input and error message
$phone_number = '';
$admin_id = '';
$error_message = '';

// Handle the login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input values
    $phone_number = sanitize_input($_POST['phone-number']);
    $password = sanitize_input($_POST['password']);
    $admin_id = sanitize_input($_POST['admin_id']);

    // Prepare SQL statement to fetch user by phone number and admin ID
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE phone_number = ? AND admin_id = ?");
    $stmt->execute([$phone_number, $admin_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify user exists and password matches
    if ($user && password_verify($password, $user['password'])) {
        // Set session variables for password and admin ID
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['admin_id'] = $user['admin_id'];

        // Redirect to the admin dashboard
        header('Location: otp-api.php');
        exit();
    } else {
        // Set error message for invalid login attempt
        $error_message = "Invalid phone number, admin ID, or password.";
    }
}

// Flush output buffer
ob_end_flush();
?>

<!doctype html>
<html lang="en">

<head>
    <title>Login - MeroVote</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../css/styles.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
               <!-- Brand Logo and Name -->
            <a class="navbar-brand d-flex align-items-center" href="admin_dashboard.php">
                <img src="../img/MeroVote-Logo.png" style="height: 60px; width: auto;" alt="MeroVote Logo" class="logo img-fluid me-2">
                <span></span>
            </a>

                <!-- Toggler Button for Small Screens -->
                <button class=" navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navbar Content -->
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Navbar Items -->
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="../index.html">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../index.html#how">How It Works</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="all-elections.php">Elections</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="login-container">
    <h1 style="font-weight:bold; color: #003d80; text-align: center;">ADMIN LOGIN</h1>
        <h4 style="text-align: center;">Login to Your Account</h4>

        <!-- Display error message if login failed -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="./admin_login.php">
            <div class="mb-3">
                <label for="phone-number" class="form-label">Phone Number</label>
                <input type="text" class="form-control" id="phone-number" name="phone-number"
                    placeholder="Enter your phone number" required value="<?= htmlspecialchars($phone_number) ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password"
                        placeholder="Enter your password" required>
                    <span class="input-group-text toggle-password" id="togglePasswordIcon"
                        onclick="togglePasswordVisibility('password')">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>
            <div class="mb-3">
                <label for=" admin-id" class="form-label">Admin ID</label>
                <input type="text" class="form-control" id="admin_id" name="admin_id" placeholder="Enter Admin ID"
                    required value="<?= htmlspecialchars($admin_id) ?>">
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                <label class="form-check-label" for="rememberMe">Remember Me</label>
            </div>
            <button type="submit" class="btn btn-primary btn-login">Login</button>
        </form>

        <div class="mt-3 text-center">
            <p>Don't have an account? <a href="./register.php">Register here</a></p>
        </div>
    </main>

    <footer class="bg-dark text-white text-center py-3">
        <div class="container">
            <p>&copy; 2024 MeroVote. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
    <script>
        function togglePasswordVisibility(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const icon = document.querySelector('#togglePasswordIcon i');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>

</html>