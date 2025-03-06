<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: User session not set. Verify login flow in admin_dashboard.php.");
}

include 'db_config.php';

// Check if there's a message to display in the modal
$modalMessage = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$modalType = isset($_SESSION['msg_type']) ? $_SESSION['msg_type'] : '';

unset($_SESSION['message']); // Remove the message after displaying it
unset($_SESSION['msg_type']); // Remove the type after displaying it

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle election creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['election_name'])) {
    $election_type = $_POST['election_type']; // Fetch election type from the dropdown
    $election_name = htmlspecialchars($_POST['election_name']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $election_position = $_POST['election_position'];

    // Ensure start time is before end time
    if (strtotime($start_time) >= strtotime($end_time)) {
        $_SESSION['message'] = "Start time must be before end time.";
        $_SESSION['msg_type'] = "danger";
        header('Location: elections.php');
        exit();
    }

    try {
        // Debug: Check received POST data
        // echo '<pre>'; print_r($_POST); echo '</pre>'; exit();
        $stmt = $pdo->prepare("INSERT INTO elections (election_type, name, start_date, end_date, start_time, end_time, election_position) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$election_type, $election_name, $start_date, $end_date, $start_time, $end_time, $election_position])) {
            $_SESSION['message'] = "Election created successfully!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "Error creating election. Please try again.";
            $_SESSION['msg_type'] = "danger";
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Database error: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }

    header('Location: elections.php');
    exit();
}

// Handle candidate addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_name'])) {
    $candidate_name = htmlspecialchars($_POST['candidate_name']);
    // Cast the posted election ID to an integer
    $election_id = (int) trim($_POST['election_id']);
    $candidate_position = htmlspecialchars($_POST['candidate_position']); // New field for candidate position
    $photo = $_FILES['photo'];

    if (!$election_id) {
        $_SESSION['message'] = "Election ID is missing.";
        $_SESSION['msg_type'] = "danger";
        header('Location: elections.php');
        exit();
    }

    if ($photo['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Error uploading photo. Error code: " . $photo['error'];
        $_SESSION['msg_type'] = "danger";
        header('Location: elections.php');
        exit();
    }

    try {
        // Fetch the election details using the provided election ID
        $stmt = $pdo->prepare("SELECT id, name FROM elections WHERE id = ?");
        $stmt->execute([$election_id]);
        $election = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$election) {
            $_SESSION['message'] = "Invalid election ID.";
            $_SESSION['msg_type'] = "danger";
            header('Location: elections.php');
            exit();
        }

        // Set variables to use when inserting candidate
        $election_id = $election['id']; // This is now an integer
        $election_name = $election['name'];

        // Handle photo upload
        $photoName = time() . '_' . basename($photo['name']);
        $targetDir = 'candidates_photos/';
        $targetFile = $targetDir . $photoName;

        // Ensure the upload directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png'];
        if (in_array($photo['type'], $allowedTypes)) {
            if (move_uploaded_file($photo['tmp_name'], $targetFile)) {
                // Insert the candidate into the database with the new candidate_position field
                $stmt = $pdo->prepare("INSERT INTO candidates (name, photo, election_name, candidate_position, elect_no, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                if ($stmt->execute([$candidate_name, $targetFile, $election_name, $candidate_position, $election_id])) {
                    $_SESSION['message'] = "Candidate added successfully!";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error adding candidate. Please try again.";
                    $_SESSION['msg_type'] = "danger";
                }
            } else {
                $_SESSION['message'] = "Failed to upload photo.";
                $_SESSION['msg_type'] = "danger";
            }
        } else {
            $_SESSION['message'] = "Invalid file type. Only JPEG and PNG are allowed.";
            $_SESSION['msg_type'] = "danger";
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Database error: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }

    header('Location: elections.php');
    exit();
}

// Fetch all elections for the dropdown
$elections = $pdo->query("SELECT id, election_type, election_position, name FROM elections ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

?>



<!doctype html>
<html lang="en">

<head>
    <title>Create Election - MeroVote</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <!-- Brand Logo and Name -->
            <a class="navbar-brand d-flex align-items-center" href="../index.html">
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
                            <a class="nav-link" href="admin_login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../index.html#how">How It Works</a>
                        </li>
                        <li class = 'nav-item'>
                            <a class = 'nav-link' href = 'feedback.php'>Feedback</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href ="logout.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="container my-5">
    <div class="row">
        <!-- Create Election Form -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Create New Election</h5>
                </div>
                <div class="card-body">
                    <form action="elections.php" method="POST">
                        <div class="mb-3">
                            <label for="electionType" class="form-label">Election Type</label>
                            <select name="election_type" id="electionType" class="form-select" required>
                                <option value="">Select Election Type</option>
                                <option value="Organizational Level Election">Organizational Level Election</option>
                                <option value="Local Level Election">Local Level Election</option>
                                <option value="School/College Level Election">School/College Level Election</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="electionName" class="form-label">Election Name</label>
                            <input type="text" name="election_name" id="electionName" class="form-control" placeholder="e.g. Nepal Local Election 2081" required>
                        </div>
                        <div class="mb-3">
                            <label for="electionPosition" class="form-label">Election Position</label>
                            <input type="text" name="election_position" id="electionPosition" class="form-control" placeholder="e.g. Secretary" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" name="start_date" id="startDate" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="endDate" class="form-label">End Date</label>
                                <input type="date" name="end_date" id="endDate" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
    <div class="col-md-6 mb-3">
        <label for="startTime" class="form-label">Start Time</label>
        <input type="time" name="start_time" id="startTime" class="form-control" required>
    </div>
    <div class="col-md-6 mb-3">
        <label for="endTime" class="form-label">End Time</label>
        <input type="time" name="end_time" id="endTime" class="form-control" required>
    </div>
</div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle"></i> Create Election
                        </button>
                    </form>
                </div>
            </div>
        </div>

       <!-- Add Candidate Form -->
<div class="col-md-6 mb-4">
  <div class="card shadow">
    <div class="card-header bg-success text-white">
      <h5 class="mb-0">Add New Candidate</h5>
    </div>
    <div class="card-body">
      <form action="elections.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label for="candidateName" class="form-label">Candidate Name</label>
          <input type="text" name="candidate_name" id="candidateName" class="form-control" placeholder="e.g. Hari Sharma" required>
        </div>
        <div class="mb-3">
          <label for="electionId" class="form-label">Select Election</label>
          <select name="election_id" id="electionId" class="form-select" required>
            <option value="">Select Election</option>
            <?php foreach ($elections as $election): ?>
              <option value="<?php echo $election['id']; ?>">
                <?php echo htmlspecialchars($election['election_type'] . " - " . $election['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label for="candidatePosition" class="form-label">Candidate Position</label>
          <input type="text" name="candidate_position" id="candidatePosition" class="form-control" placeholder="Candidate Position" readonly required>
        </div>
        <div class="mb-3">
          <label><code class="note">Please, note that the photo must be authentic.</code></label><br>
          <label for="photo" class="form-label">Upload Candidate Photo</label>
          <input type="file" name="photo" id="photo" class="form-control" accept="image/jpeg, image/png" required>
        </div>
        <button type="submit" class="btn btn-success w-100">
          <i class="bi bi-plus-circle"></i> Add Candidate
        </button>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
  // Convert PHP $elections array into a JavaScript object
  var electionsData = <?php echo json_encode($elections); ?>;
  
  // Get references to the election dropdown and candidate position input field
  var electionSelect = document.getElementById("electionId");
  var candidatePositionInput = document.getElementById("candidatePosition");
  
  // When the election dropdown value changes...
  electionSelect.addEventListener("change", function() {
    var selectedId = this.value;
    // Find the election object with a matching id
    var selectedElection = electionsData.find(function(election) {
      return election.id == selectedId;
    });
    // If found and election_position exists, update the candidate position field; otherwise clear it.
    if (selectedElection && selectedElection.election_position) {
      candidatePositionInput.value = selectedElection.election_position;
    } else {
      candidatePositionInput.value = "";
    }
  });
});
</script>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-<?= $modalType ?>">
                    <h5 class="modal-title text-white">
                        <?= ucfirst($modalType) ?> Message
                    </h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <p><?= $modalMessage ?></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-<?= $modalType ?>" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.row .card-body {
    background-color: transparent !important;
}

.card-header {
    font-size: 1.2rem;
    font-weight: bold;
}
input::placeholder {
    font-style: italic;
    color: #aaa;
}
.btn-primary, .btn-success {
    transition: background-color 0.3s ease-in-out;
}
.btn-primary:hover {
    background-color: #004085;
}
.btn-success:hover {
    background-color: #155724;
}

.note {
    font-size: 0.65em;
    color: grey;
    padding: 10px;
}

    </style>


    <footer class="bg-dark text-white text-center py-3 mt-4">
        <div class="container">
            <p>&copy; 2024 Online Voting System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
    <script>
document.addEventListener("DOMContentLoaded", function () {
    var modalMessage = "<?= $modalMessage ?>";
    if (modalMessage.trim() !== "") {
        var feedbackModal = new bootstrap.Modal(document.getElementById("feedbackModal"));
        feedbackModal.show();
    }
});

        </script>
</body>

</html>