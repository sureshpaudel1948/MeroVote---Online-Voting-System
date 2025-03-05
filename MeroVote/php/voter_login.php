<?php
// Enable output buffering to prevent issues with headers
ob_start();

// Include database configuration and utility functions
include 'db_config.php';
include 'utils.php';




// Initialize variables for form input and error message
$phone_number = '';
$student_id = '';
$local_id = '';
$employee_id = '';
$error_message = '';

// Start session
session_start();
$_SESSION['phone-number'] = $phone_number; // Ensure $mobileNumber is sanitized and validated

// Handle the login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input values
    $phone_number = sanitize_input($_POST['phone-number']);
    $password = sanitize_input($_POST['password']);
    $electionType = isset($_POST['election-type']) ? sanitize_input($_POST['election-type']) : '';

    // Use a switch block based on the selected election type
    switch ($electionType) {
        case 'college':
            // Normal School/College login
            if (!empty($_POST['student_id'])) {
                $student_id = sanitize_input($_POST['student_id']);
                $stmt = $pdo->prepare("SELECT * FROM users_college WHERE phone_number = ? AND student_id = ?");
                $stmt->execute([$phone_number, $student_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = trim($user['id']);
                    $_SESSION['user_type'] = 'college';
                    $_SESSION['user_identifier'] = trim($user['student_id']);
                    $_SESSION['user_role'] = 'School/College Level Election';
                    header('Location: otp-api.php');
                    exit();
                }
            }
            break;
        case 'college-grp':
            // Group School/College login
            if (!empty($_POST['student_id'])) {
                $student_id = sanitize_input($_POST['student_id']);
                $stmt = $pdo->prepare("SELECT * FROM users_college WHERE phone_number = ? AND student_id = ?");
                $stmt->execute([$phone_number, $student_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = trim($user['id']);
                    $_SESSION['user_type'] = 'college-grp';
                    $_SESSION['user_identifier'] = trim($user['student_id']);
                    $_SESSION['user_role'] = 'School/College Level Election-Group';
                    header('Location: voter_grp_dashboard.php');
                    exit();
                }
            }
            break;
        case 'local':
            // Normal Local login
            if (!empty($_POST['local_id'])) {
                $local_id = sanitize_input($_POST['local_id']);
                $stmt = $pdo->prepare("SELECT * FROM users_local WHERE phone_number = ? AND local_id = ?");
                $stmt->execute([$phone_number, $local_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = trim($user['id']);
                    $_SESSION['user_type'] = 'local';
                    $_SESSION['user_identifier'] = trim($user['local_id']);
                    $_SESSION['user_role'] = 'Local Level Election';
                    header('Location: otp-api.php');
                    exit();
                }
            }
            break;
        case 'local-grp':
            // Group Local login
            if (!empty($_POST['local_id'])) {
                $local_id = sanitize_input($_POST['local_id']);
                $stmt = $pdo->prepare("SELECT * FROM users_local WHERE phone_number = ? AND local_id = ?");
                $stmt->execute([$phone_number, $local_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = trim($user['id']);
                    $_SESSION['user_type'] = 'local-grp';
                    $_SESSION['user_identifier'] = trim($user['local_id']);
                    $_SESSION['user_role'] = 'Local Level Election-Group';
                    header('Location: voter_grp_dashboard.php');
                    exit();
                }
            }
            break;
        case 'org':
            // Normal Organizational login
            if (!empty($_POST['employee_id'])) {
                $employee_id = sanitize_input($_POST['employee_id']);
                $stmt = $pdo->prepare("SELECT * FROM users_org WHERE phone_number = ? AND employee_id = ?");
                $stmt->execute([$phone_number, $employee_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = trim($user['id']);
                    $_SESSION['user_type'] = 'org';
                    $_SESSION['user_identifier'] = trim($user['employee_id']);
                    $_SESSION['user_role'] = 'Organizational Level Election';
                    header('Location: otp-api.php');
                    exit();
                }
            }
            break;
        case 'org-grp':
            // Group Organizational login
            if (!empty($_POST['employee_id'])) {
                $employee_id = sanitize_input($_POST['employee_id']);
                $stmt = $pdo->prepare("SELECT * FROM users_org WHERE phone_number = ? AND employee_id = ?");
                $stmt->execute([$phone_number, $employee_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = trim($user['id']);
                    $_SESSION['user_type'] = 'org-grp';
                    $_SESSION['user_identifier'] = trim($user['employee_id']);
                    $_SESSION['user_role'] = 'Organizational Level Election-Group';
                    header('Location: voter_grp_dashboard.php');
                    exit();
                }
            }
            break;
        default:
            $error_message = "Invalid election type selected.";
            break;
    }
    // If no condition matched, set error message
    if (empty($_SESSION['user_id'])) {
        $error_message = "Invalid phone number, voter ID, or password.";
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
            <a class="navbar-brand d-flex align-items-center" href="voter_login.php">
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
                        <li class = 'nav-item'>
                            <a class = 'nav-link' href = 'feedback.php'>Feedback</a>
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
        <h1 style="font-weight:bold; color: #003d80; text-align: center;">VOTER LOGIN</h1>
        <h4 style="text-align: center;">Login to Your Account</h4>

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
                    <option value="college-grp">School/College Level Election-Group</option>
                    <option value="local">Local Level Election</option>
                    <option value="local-grp">Local Level Election-Group</option>
                    <option value="org">Organizational Level Election</option>
                    <option value="org-grp">Organizational Level Election-Group</option>
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
            } else if (electionType === 'college-grp') {
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
        } else if (electionType === 'local-grp') {
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
        } else if (electionType === 'org-grp') {
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