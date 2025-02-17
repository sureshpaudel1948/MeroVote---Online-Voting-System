<?php

include 'db_config.php';
include 'utils.php';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $password);
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
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showModal('Error', 'All fields are required.');
            });
          </script>";
        exit;
    }

    if (!preg_match('/^[0-9]{10}$/', $phoneNumber)) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showModal('Error', 'Invalid phone number format. Must be 10 digits.');
            });
          </script>";
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    try {
        if ($role === "voter") {
            $electionType = trim($_POST["election-type"]);

            // School/College Level Voter
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
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showModal('Success', 'Data updated successfully. Redirecting...');
                            setTimeout(() => { window.location.href = 'voter_login.php'; }, 3000);
                        });
                      </script>";
                } else {
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showModal('Error', 'No matching student ID found. Update failed. ');
                        });
                      </script>";
                }
            }

            // Local Level Voter
            elseif ($electionType === "local") {
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
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showModal('Success', 'Data updated successfully. Redirecting...');
                            setTimeout(() => { window.location.href = 'voter_login.php'; }, 3000);
                        });
                      </script>";
                } else {
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showModal('Error', 'No matching local voter found. Update failed.');
                        });
                      </script>";
                }
            }

            // Organizational Level Voter
            elseif ($electionType === "org") {
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
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showModal('Success', 'Data updated successfully. Redirecting...');
                            setTimeout(() => { window.location.href = 'voter_login.php'; }, 3000);
                        });
                      </script>";
                } else {
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showModal('Error', 'No matching employee found. Update failed.');
                        });
                      </script>";
                }
            }
        }

        // Admin
        elseif ($role === "admin") {
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
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showModal('Success', 'Data updated successfully. Redirecting...');
                        setTimeout(() => { window.location.href = 'admin_login.php'; }, 3000);
                    });
                  </script>";
            } else {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showModal('Error', 'No admin found with the given ID.');
                    });
                  </script>";
            }
        }
    } catch (PDOException $e) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showModal('Error', 'Error updating data: " . addslashes($e->getMessage()) . "');
            });
          </script>";
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
               <!-- Brand Logo and Name -->
            <a class="navbar-brand d-flex align-items-center" href="register.php">
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
                            <a class="nav-link active" aria-current="page" href="register.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../index.html#how">How It Works</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="all-elections.php">Elections</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="voter_login.php">Login</a>
                        </li>
                    </ul>
                </div>
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
                    <span class="input-group-text toggle-password" id="togglePasswordIcon1"
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
                        placeholder="Confirm your password" required /> <span class="input-group-text toggle-password"
                        id="togglePasswordIcon2"
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

            <!-- Modal HTML -->
            <div id="customModal" class="modal" style="display:none;">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h2 id="modalTitle"></h2>
                    <p id="modalMessage"></p>
                    <button class="modal-btn" onclick="closeModal()">OK</button>
                </div>
            </div>
        

            <button type="submit" class="btn btn-primary">Register</button>

            <div class="mt-3 text-center log-cont">
                <p>Already have an account?</p>
                <div class="login">
                    <a href="admin_login.php" class="admin-link">Admin Login</a>
                    <a href="voter_login.php" class="voter-link">Voter Login</a>
                </div>
            </div>


            <style>
                /* Modal Background */
                .modal {
                    position: fixed;
                    z-index: 1000;
                    /* Ensure it appears on top of everything */
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    display: none;
                    /* Keep it hidden initially */
                    background: rgba(0, 0, 0, 0.6);
                    backdrop-filter: blur(5px);
                    /* Smooth blur effect */
                    align-items: center;
                    justify-content: center;
                    /* Perfectly center the modal */
                }


                /* Modal Content */
                .modal-content {
                    width: 300px;
                    /* Reduced size */
                    max-width: 90%;
                    padding: 15px;
                    /* Adjusted padding */
                    border-radius: 10px;
                    /* Slightly smaller radius */
                    background: #fff;
                    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.25);
                    text-align: center;
                    animation: modalFadeIn 0.3s ease-in-out;
                }

                /* Modal Title */
                .modal-content h2 {
                    font-size: 20px;
                    /* Reduced font size */
                    margin-bottom: 10px;
                    color: #333;
                }

                /* Modal Message */
                .modal-content p {
                    font-size: 14px;
                    /* Smaller text */
                    margin-bottom: 15px;
                    /* Adjust spacing */
                    color: #555;
                    line-height: 1.5;
                }

                /* Close Button */
                .close {
                    position: absolute;
                    top: 10px;
                    right: 15px;
                    color: #888;
                    font-size: 20px;
                    /* Slightly smaller size */
                    font-weight: bold;
                    cursor: pointer;
                }

                .close:hover {
                    color: #444;
                }

                /* Modal Button */
                .modal-btn {
                    padding: 8px 16px;
                    /* Slightly smaller padding */
                    border: none;
                    border-radius: 6px;
                    /* Smaller radius */
                    background: #007bff;
                    color: #fff;
                    font-size: 14px;
                    cursor: pointer;
                    transition: 0.3s;
                }

                .modal-btn:hover {
                    background: #0056b3;
                }

                /* Animation */
                @keyframes modalFadeIn {
                    from {
                        opacity: 0;
                        transform: translateY(-20px);
                    }

                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            </style>


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
            const phoneNumber = document.getElementById("phone_number").value;
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm-password").value;

            // Validate phone number format (10 digits)
            const phoneRegex = /^[0-9]{10}$/;
            if (!phoneRegex.test(phoneNumber)) {
                showModal("Error", "Invalid phone number. Please enter a 10-digit number.");
                return false;
            }

            // Validate password match
            if (password !== confirmPassword) {
                showModal("Error", "Passwords do not match.");
                return false;
            }

            return true; // Form is valid
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


        // Modal JavaScript

        // Show Modal
        function showModal(title, message) {
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalMessage').innerText = message;
            document.getElementById('customModal').style.display = 'flex';
        }

        // Close Modal
        function closeModal() {
            document.getElementById('customModal').style.display = 'none';
        }


        // Navbar Collapsing on Small Screens
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                const navbar = document.querySelector('.navbar-collapse');
                if (navbar.classList.contains('show')) {
                    navbar.classList.remove('show');
                }
            });
        });
    </script>

    <footer class="bg-dark text-white text-center py-3">
        <div class="container">
            <p>&copy; 2024 MeroVote. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="js/script.js"></script>
</body>

</html>