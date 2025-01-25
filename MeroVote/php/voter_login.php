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
$student_id = '';
$local_id = '';
$employee_id = '';
$error_message = '';

// Handle the login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input values
    $phone_number = sanitize_input($_POST['phone-number']);
    $password = sanitize_input($_POST['password']);

    // Check if `student_id` is provided for college users
    if (!empty($_POST['student_id'])) {
        $student_id = sanitize_input($_POST['student_id']);
        $stmt = $pdo->prepare("SELECT * FROM users_college WHERE phone_number = ? AND student_id = ?");
        $stmt->execute([$phone_number, $student_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = 'college';
            $_SESSION['user_identifier'] = $user['student_id'];  // Set session data to identify the user
            $_SESSION['user_role'] = 'School/College Level Election'; // Set user role for elections
            header('Location: voter_dashboard.php'); // Redirect to dashboard
            exit();
        }
    }

    // Check if `local_id` is provided for local users
    if (!empty($_POST['local_id'])) {
        $local_id = sanitize_input($_POST['local_id']);
        $stmt = $pdo->prepare("SELECT * FROM users_local WHERE phone_number = ? AND local_id = ?");
        $stmt->execute([$phone_number, $local_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = 'local';
            $_SESSION['user_identifier'] = $user['local_id'];  // Set session data to identify the user
            $_SESSION['user_role'] = 'Local Level Election'; // Set user role for elections
            header('Location: voter_dashboard.php'); // Redirect to dashboard
            exit();
        }
    }

    // Check if `employee_id` is provided for organizational users
    if (!empty($_POST['employee_id'])) {
        $employee_id = sanitize_input($_POST['employee_id']);
        $stmt = $pdo->prepare("SELECT * FROM users_org WHERE phone_number = ? AND employee_id = ?");
        $stmt->execute([$phone_number, $employee_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = 'org';
            $_SESSION['user_identifier'] = $user['employee_id'];  // Set session data to identify the user
            $_SESSION['user_role'] = 'Organizational Level Election'; // Set user role for elections
            header('Location: voter_dashboard.php'); // Redirect to dashboard
            exit();
        }
    }

    // If none of the above conditions match, set error message
    $error_message = "Invalid phone number, voter ID, or password.";
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
                <img src="../img/MeroVote-Logo.png" style="height: 45px; width: auto;" alt="MeroVote Logo" class="logo img-fluid me-2">
                <span>MeroVote - Online Voting Portal</span>
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
                            <a class="nav-link active" aria-current="page" href="voter_login.php">Home</a>
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
        <h2>Login to Your Account</h2>

        <!-- Display error message if login failed -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="./voter_login.php">
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

            <div id="election-type-container">
                <label for="election-type" class="form-label">Election Type</label>
                <select class="form-control" id="election-type" name="election-type" onchange="toggleElectionFields()">
                    <option value="" disabled selected>Select Election Type</option>
                    <option value="college">School/College Level Election</option>
                    <option value="local">Local Level Election</option>
                    <option value="org">Organizational Level Election</option>
                </select>
            </div>

            <!-- Additional Fields Based on Election Type -->
            <div id="additional-fields">
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

        function toggleElectionFields() {
            const electionType = document.getElementById('election-type').value;
            const additionalFields = document.getElementById('additional-fields');

            additionalFields.innerHTML = ''; // Clear previous fields

            if (electionType === 'college') {
                additionalFields.innerHTML = `
                <div class="mb-3">
                    <label for="student-id" class="form-label">Student ID</label>
                    <input type="text" id="student_id" name="student_id" class="form-control" placeholder="Enter Student ID" required>
                </div>
                
            `;
            } else if (electionType === 'local') {
                additionalFields.innerHTML = `
                <div class="mb-3">
                    <label for="local-id" class="form-label">Local ID</label>
                    <input type="text" id="local_id" name="local_id" class="form-control" placeholder="Enter Local ID" required>
                </div>
            `;
            } else if (electionType === 'org') {
                additionalFields.innerHTML = `
                <div class="mb-3">
                    <label for="employee-id" class="form-label">Employee ID</label>
                    <input type="text" id="employee_id" name="employee_id" class="form-control" placeholder="Enter Employee ID" required>
                </div>
            `;
            }
        }
    </script>
</body>

</html>