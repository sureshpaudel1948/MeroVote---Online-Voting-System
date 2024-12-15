<?php

include 'db_config.php';
include 'utils.php';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $password); // Corrected $db usage
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["full_name"]);
    $phoneNumber = trim($_POST["phone_number"]);
    $password = trim($_POST["password"]);
    $role = trim($_POST["role"]);

    // Input validation
    if (empty($name) || empty($phoneNumber) || empty($password) || empty($role)) {
        echo "All fields are required.";
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    try {
        if ($role === "voter") {
            $electionType = trim($_POST["election-type"]);

            if ($electionType === "college") {
                $studentId = trim($_POST["student_id"]);
                $gender = trim($_POST["gender"]);

                $sql = "UPDATE users_college
                        SET phone_number = :phoneNumber, password = :password
                        WHERE student_id = :studentId";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':phoneNumber' => $phoneNumber,
                    ':password' => $hashedPassword,
                    ':studentId' => $studentId
                ]);

                if ($stmt->rowCount() > 0) {
                    echo "Data updated successfully.";

                    // Redirect to Admin login page
                    header("Location: voter_login.php");
                    exit;
                }

                // Check if any rows were affected
                if ($stmt->rowCount() == 0) {
                    echo "No student found with the given Student ID.";
                }
            } elseif ($electionType === "local") {
                $localId = trim($_POST["local_id"]);
                $gender = trim($_POST["gender"]);
                $address = trim($_POST["address"]);

                $sql = "UPDATE users_local
                        SET phone_number = :phoneNumber, password = :password
                        WHERE local_id = :localId AND address = :address";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':phoneNumber' => $phoneNumber,
                    ':password' => $hashedPassword,
                    ':localId' => $localId,
                    ':address' => $address
                ]);

                if ($stmt->rowCount() > 0) {
                    echo "Data updated successfully.";

                    // Redirect to Admin login page
                    header("Location: voter_login.php");
                    exit;
                }

                // Check if any rows were affected
                if ($stmt->rowCount() == 0) {
                    echo "No local voter found with the given Local ID.";
                }
            } elseif ($electionType === "org") {
                $employeeId = trim($_POST["employee_id"]);
                $gender = trim($_POST["gender"]);
                $address = trim($_POST["address"]);

                $sql = "UPDATE users_org
                        SET phone_number = :phoneNumber, password = :password
                        WHERE employee_id = :employeeId AND address = :address";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':phoneNumber' => $phoneNumber,
                    ':password' => $hashedPassword,
                    ':employeeId' => $employeeId,
                    ':address' => $address
                ]);
                if ($stmt->rowCount() > 0) {
                    echo "Data updated successfully.";

                    // Redirect to Admin login page
                    header("Location: voter_login.php");
                    exit;
                }

                // Check if any rows were affected
                if ($stmt->rowCount() == 0) {
                    echo "No employee found with the given employee ID.";
                }
            }
        } elseif ($role === "admin") {
            $adminId = trim($_POST["admin_id"]);

            $sql = "UPDATE admins
                    SET password = :password
                    WHERE admin_id = :adminId";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':password' => $hashedPassword,
                ':adminId' => $adminId
            ]);

            if ($stmt->rowCount() > 0) {
                echo "Data updated successfully.";

                // Redirect to Admin login page
                header("Location: admin_login.php");
                exit;
            }
            // Check if any rows were affected
            if ($stmt->rowCount() == 0) {
                echo "No admin found with the given ID.";
            }
        }
    } catch (PDOException $e) {
        echo "Error updating data: " . $e->getMessage();
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
        <form id="registerForm" action="register.php" method="POST" onsubmit="return validateForm()">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="full_name" name="full_name"
                    placeholder="Enter your full name" required />
            </div>

            <div class="mb-3">
                <label for="phone-number" class="form-label">Phone Number</label>
                <input type="text" class="form-control" id="phone_number" name="phone_number"
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
                <select class="form-control" id="role" name="role" onchange="toggleRoleFields()" required>
                    <option value="" disabled selected>Select your role</option>
                    <option value="voter">Voter</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <!-- Admin ID Field -->
            <div id="admin-id-container" style="display: none;">
                <label for="admin-id" class="form-label">Admin ID</label>
                <input type="text" id="admin_id" name="admin_id" class="form-control" placeholder="Enter Admin ID">
            </div>

            <!-- Election Type (Visible only for Voters) -->
            <div id="election-type-container" style="display: none;">
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

            <div class="mt-3 text-center log-cont">
                <p>Already have an account?</p>
                <div class="login">
                    <a href="admin_login.php" class="admin-link">Admin Login</a>
                    <a href="voter_login.php" class="voter-link">Voter Login</a>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Register</button>
        </form>
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

        function toggleRoleFields() {
            const role = document.getElementById('role').value;
            const adminIdContainer = document.getElementById('admin-id-container');
            const electionTypeContainer = document.getElementById('election-type-container');
            const additionalFields = document.getElementById('additional-fields');

            adminIdContainer.style.display = role === 'admin' ? 'block' : 'none';
            electionTypeContainer.style.display = role === 'voter' ? 'block' : 'none';
            additionalFields.innerHTML = ''; // Clear additional fields
        }

        function toggleElectionFields() {
            const electionType = document.getElementById('election-type').value;
            const additionalFields = document.getElementById('additional-fields');

            additionalFields.innerHTML = ''; // Clear previous fields

            if (electionType === 'college') {
                additionalFields.innerHTML = `
            <div class="mb-3">
                <label for="student-id" class="form-label">Student ID</label>
                <input type="text" class="form-control" id="student_id" name="student_id" placeholder="Enter your Student ID" required />
            </div>
            <div class="mb-3">
                <label for="gender" class="form-label">Gender</label>
                <select class="form-control" id="gender" name="gender" required>
                    <option value="" disabled selected>Select Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>`;
            } else if (electionType === 'local') {
                additionalFields.innerHTML = `
            <div class="mb-3">
                <label for="local-id" class="form-label">Local ID</label>
                <input type="text" class="form-control" id="local_id" name="local_id" placeholder="Enter your Local ID" required />
            </div>
            <div class="mb-3">
                <label for="gender" class="form-label">Gender</label>
                <select class="form-control" id="gender" name="gender" required>
                    <option value="" disabled selected>Select Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" class="form-control" id="address" name="address" placeholder="Enter your Address" required />
            </div>`;
            } else if (electionType === 'org') {
                additionalFields.innerHTML = `
            <div class="mb-3">
                <label for="employee-id" class="form-label">Employee ID</label>
                <input type="text" class="form-control" id="employee_id" name="employee_id" placeholder="Enter your Employee ID" required />
            </div>
            <div class="mb-3">
                <label for="gender" class="form-label">Gender</label>
                <select class="form-control" id="gender" name="gender" required>
                    <option value="" disabled selected>Select Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" class="form-control" id="address" name="address" placeholder="Enter your Address" required />
            </div>`;
            }
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