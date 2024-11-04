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

    // Server-side validation to ensure role is either 'voter' or 'admin'
    if (!in_array($role, ['voter', 'admin'])) {
        echo "Invalid role selected!";
        exit;
    }

    try {
        // Prepare the SQL statement for inserting a new user
        $stmt = $pdo->prepare("INSERT INTO users (full_name, phone_number, password, role) VALUES (?, ?, ?, ?)");
        
        // Execute the statement with the provided data
        if ($stmt->execute([$full_name, $phone_number, $password, $role])) {
            // Redirect to login page upon successful registration
            header("Location: ../login.php");
            exit;
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

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Register - MeroVote</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../css/styles.css" />
    <!-- Include FontAwesome for the eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.html">MeroVote - Online Voting Portal</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
        </nav>
    </header>

    <main class="login-container">
        <h2>Create Your Account</h2>
        <form id="registerForm" action="php/register.php" method="POST" onsubmit="return validateForm()">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name"
                    required />
            </div>

            <div class="mb-3">
                <label for="phone-number" class="form-label">Phone Number</label>
                <input type="text" class="form-control" id="phone-number" name="phone-number"
                    placeholder="Enter your phone number" required />
            </div>

            <!-- Password Field -->
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password"
                        placeholder="Enter your password" required>
                    <span class="input-group-text" id="togglePasswordIcon1"
                        onclick="togglePasswordVisibility('password', 'togglePasswordIcon1')">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>

            <!-- Confirm Password Field -->
            <div class="mb-3">
                <label for="confirm-password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="confirm-password" name="confirm-password"
                        placeholder="Confirm your password" required />
                    <span class="input-group-text" id="togglePasswordIcon2"
                        onclick="togglePasswordVisibility('confirm-password', 'togglePasswordIcon2')">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="voter">Voter</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Register</button>
        </form>

        <div class="mt-3 text-center">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </main>

    <!-- Add JavaScript for password matching and visibility -->
    <script>
        // Toggle password visibility for both password fields
        function togglePasswordVisibility(fieldId, iconId) {
            const passwordField = document.getElementById(fieldId);
            const icon = document.querySelector(`#${iconId} i`);

            if (passwordField.type === "password") {
                passwordField.type = "text";
                icon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                passwordField.type = "password";
                icon.classList.replace("fa-eye-slash", "fa-eye");
            }
        }

        // Validate form: Check if passwords match
        function validateForm() {
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm-password").value;

            if (password !== confirmPassword) {
                alert("Passwords do not match!");
                return false; // Prevent form submission
            }
            return true; // Allow form submission if passwords match
        }
    </script>

    <footer class="bg-dark text-white text-center py-3">
        <div class="container">
            <p>&copy; 2024 MeroVote. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
    <script src="js/script.js"></script>
</body>

</html>
