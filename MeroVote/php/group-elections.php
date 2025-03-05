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

    // Default required positions for Panel 1 and Panel 2
    $panel1_pos1 = $_POST['panel1_pos1'];
    $panel1_pos2 = $_POST['panel1_pos2'];
    $panel2_pos1 = $_POST['panel2_pos1'];
    $panel2_pos2 = $_POST['panel2_pos2'];
   

    // Extra positions for Panel 1 and Panel 2 (if provided)
    $panel1_pos3 = $_POST['panel1_pos3'] ?? '';
    $panel1_pos4 = $_POST['panel1_pos4'] ?? '';
    $panel1_pos5 = $_POST['panel1_pos5'] ?? '';
    $panel1_pos6 = $_POST['panel1_pos6'] ?? '';
    $panel1_pos7 = $_POST['panel1_pos7'] ?? '';
    $panel1_pos8 = $_POST['panel1_pos8'] ?? '';
    $panel2_pos3 = $_POST['panel2_pos3'] ?? '';
    $panel2_pos4 = $_POST['panel2_pos4'] ?? '';
    $panel2_pos5 = $_POST['panel2_pos5'] ?? '';
    $panel2_pos6 = $_POST['panel2_pos6'] ?? '';
    $panel2_pos7 = $_POST['panel2_pos7'] ?? '';
    $panel2_pos8 = $_POST['panel2_pos8'] ?? '';

    // Extra panels (Panel 3 and Panel 4 positions) – default to empty strings if not provided
    $panel3_pos1 = $_POST['panel3_pos1'] ?? '';
    $panel3_pos2 = $_POST['panel3_pos2'] ?? '';
    $panel3_pos3 = $_POST['panel3_pos3'] ?? '';
    $panel3_pos4 = $_POST['panel3_pos4'] ?? '';
    $panel3_pos5 = $_POST['panel3_pos5'] ?? '';
    $panel3_pos6 = $_POST['panel3_pos6'] ?? '';
    $panel3_pos7 = $_POST['panel3_pos7'] ?? '';
    $panel3_pos8 = $_POST['panel3_pos8'] ?? '';

    $panel4_pos1 = $_POST['panel4_pos1'] ?? '';
    $panel4_pos2 = $_POST['panel4_pos2'] ?? '';
    $panel4_pos3 = $_POST['panel4_pos3'] ?? '';
    $panel4_pos4 = $_POST['panel4_pos4'] ?? '';
    $panel4_pos5 = $_POST['panel4_pos5'] ?? '';
    $panel4_pos6 = $_POST['panel4_pos6'] ?? '';
    $panel4_pos7 = $_POST['panel4_pos7'] ?? '';
    $panel4_pos8 = $_POST['panel4_pos8'] ?? '';

    // Ensure start time is before end time
    if (strtotime($start_time) >= strtotime($end_time)) {
        $_SESSION['message'] = "Start time must be before end time.";
        $_SESSION['msg_type'] = "danger";
        header('Location: group-elections.php');
        exit();
    }

    try {
        // Insert election into elections_group table with all panels and positions
        $stmt = $pdo->prepare("INSERT INTO elections_group (
            election_type, name, start_date, end_date, start_time, end_time,  
            panel1_pos1, panel1_pos2, panel1_pos3, panel1_pos4, panel1_pos5, panel1_pos6, panel1_pos7, panel1_pos8, 
            panel2_pos1, panel2_pos2, panel2_pos3, panel2_pos4, panel2_pos5, panel2_pos6, panel2_pos7, panel2_pos8, 
            panel3_pos1, panel3_pos2, panel3_pos3, panel3_pos4, panel3_pos5, panel3_pos6, panel3_pos7, panel3_pos8, 
            panel4_pos1, panel4_pos2, panel4_pos3, panel4_pos4, panel4_pos5, panel4_pos6, panel4_pos7, panel4_pos8
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $values = [
            $election_type, $election_name, $start_date, $end_date, $start_time, $end_time,  
            $panel1_pos1, $panel1_pos2, $panel1_pos3, $panel1_pos4, $panel1_pos5, $panel1_pos6, $panel1_pos7, $panel1_pos8, 
            $panel2_pos1, $panel2_pos2, $panel2_pos3, $panel2_pos4, $panel2_pos5, $panel2_pos6, $panel2_pos7, $panel2_pos8, 
            $panel3_pos1, $panel3_pos2, $panel3_pos3, $panel3_pos4, $panel3_pos5, $panel3_pos6, $panel3_pos7, $panel3_pos8, 
            $panel4_pos1, $panel4_pos2, $panel4_pos3, $panel4_pos4, $panel4_pos5, $panel4_pos6, $panel4_pos7, $panel4_pos8
        ];
        
        if ($stmt->execute($values)) {
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

    header('Location: group-elections.php');
    exit();
}

// Handle candidate addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_name'])) {
    $candidate_name = htmlspecialchars($_POST['candidate_name']);
    // Retrieve the posted election ID (should be named "election_id")
    $rawElectionId = $_POST['election_id'] ?? '';
    $election_id = (int) trim($rawElectionId);
    $panel = htmlspecialchars($_POST['panel']); 
    $candidate_position = htmlspecialchars($_POST['candidate_position']); 
    $photo = $_FILES['photo'];

    if (!$election_id) {
        $_SESSION['message'] = "Election ID is missing.";
        $_SESSION['msg_type'] = "danger";
        header('Location: group-elections.php');
        exit();
    }

    if ($photo['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Error uploading photo. Error code: " . $photo['error'];
        $_SESSION['msg_type'] = "danger";
        header('Location: group-elections.php');
        exit();
    }

    try {
        // Fetch the election details using the provided election ID
        $stmt = $pdo->prepare("SELECT id, name FROM elections_group WHERE id = ?");
        $stmt->execute([$election_id]);
        $election = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$election) {
            $_SESSION['message'] = "Invalid election ID.";
            $_SESSION['msg_type'] = "danger";
            header('Location: group-elections.php');
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
                // Insert the candidate into the database.
                // Note: Order of parameters: name, photo, election_name, panel, candidate_position, elect_no.
                $stmt = $pdo->prepare("INSERT INTO candidates_group (name, photo, election_name, panel, candidate_position, elect_no, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                if ($stmt->execute([$candidate_name, $targetFile, $election_name, $panel, $candidate_position, $election_id])) {
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

    header('Location: group-elections.php');
    exit();
}

// Fetch all elections for the dropdown
$elections = $pdo->query("SELECT id, election_type, name, start_date, end_date, start_time, end_time, 
               panel1_pos1, panel1_pos2, panel1_pos3, panel1_pos4, 
               panel2_pos1, panel2_pos2, panel2_pos3, panel2_pos4, 
               panel1_pos5, panel1_pos6, panel1_pos7, panel1_pos8, 
               panel2_pos5, panel2_pos6, panel2_pos7, panel2_pos8, 
               panel3_pos1, panel3_pos2, panel3_pos3, panel3_pos4, panel3_pos5, panel3_pos6, panel3_pos7, panel3_pos8, 
               panel4_pos1, panel4_pos2, panel4_pos3, panel4_pos4, panel4_pos5, panel4_pos6, panel4_pos7, panel4_pos8
        FROM elections_group ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all candidates for group elections to enforce position limits
$existingCandidates = $pdo->query("SELECT election_name, panel, candidate_position FROM candidates_group")->fetchAll(PDO::FETCH_ASSOC);


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
            <a class="navbar-brand d-flex align-items-center" href="elections.php">
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
    <!-- Create Group Election Form -->
    <div class="col-md-6 mb-4">
      <div class="card shadow">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">Create New Group Election</h5>
        </div>
        <div class="card-body">
          <form action="group-elections.php" method="POST" id="electionForm">
            <div class="mb-3">
              <label for="electionType" class="form-label">Election Type</label>
              <select name="election_type" id="electionType" class="form-select" required>
                <option value="">Select Election Type</option>
                <option value="Organizational Level Election-Group">Organizational Level Election-Group</option>
                <option value="Local Level Election-Group">Local Level Election-Group</option>
                <option value="School/College Level Election-Group">School/College Level Election-Group</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="electionName" class="form-label">Election Name</label>
              <input type="text" name="election_name" id="electionName" class="form-control" placeholder="e.g. Nepal Group Election 2081" required>
            </div>
            <!-- Panel 1 Positions (Default 2 positions) -->
            <h5 class="text-center">Panel 1 Positions</h5>
            <div id="panel1Positions">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="panel1_pos1" class="form-label">Position 1</label>
                  <input type="text" name="panel1_pos1" id="panel1_pos1" class="form-control" placeholder="e.g. Mayor" required>
                </div>
                <div class="col-md-6">
                  <label for="panel1_pos2" class="form-label">Position 2</label>
                  <input type="text" name="panel1_pos2" id="panel1_pos2" class="form-control" placeholder="e.g. Sub-Mayor" required>
                </div>
              </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" id="addPanel1PosBtn">Add More Positions (Panel 1 &amp; 2)</button>
            <hr>
            <!-- Panel 2 Positions (Default 2 positions) -->
            <h5 class="text-center">Panel 2 Positions</h5>
            <div id="panel2Positions">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="panel2_pos1" class="form-label">Position 1</label>
                  <input type="text" name="panel2_pos1" id="panel2_pos1" class="form-control" placeholder="e.g. Mayor" required>
                </div>
                <div class="col-md-6">
                  <label for="panel2_pos2" class="form-label">Position 2</label>
                  <input type="text" name="panel2_pos2" id="panel2_pos2" class="form-control" placeholder="e.g. Sub-Mayor" required>
                </div>
              </div>
            </div>
            <hr>
            <!-- Extra Panels (Panel 3 and Panel 4) -->
            <div id="extraPanels" style="display:none;">
              <!-- Panel 3 Positions -->
              <h5 class="text-center">Panel 3 Positions</h5>
              <div id="panel3Positions">
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label for="panel3_pos1" class="form-label">Position 1</label>
                    <input type="text" name="panel3_pos1" id="panel3_pos1" class="form-control" placeholder="e.g. Mayor">
                  </div>
                  <div class="col-md-6">
                    <label for="panel3_pos2" class="form-label">Position 2</label>
                    <input type="text" name="panel3_pos2" id="panel3_pos2" class="form-control" placeholder="e.g. Sub-Mayor">
                  </div>
                </div>
              </div>
              <!-- Panel 4 Positions -->
              <h5 class="text-center">Panel 4 Positions</h5>
              <div id="panel4Positions">
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label for="panel4_pos1" class="form-label">Position 1</label>
                    <input type="text" name="panel4_pos1" id="panel4_pos1" class="form-control" placeholder="e.g. Mayor">
                  </div>
                  <div class="col-md-6">
                    <label for="panel4_pos2" class="form-label">Position 2</label>
                    <input type="text" name="panel4_pos2" id="panel4_pos2" class="form-control" placeholder="e.g. Sub-Mayor">
                  </div>
                </div>
              </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm mt-2" id="addPanelsBtn">Add More Panels</button>
            <hr>
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
            <hr>
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
      <form id="candidateForm" action="group-elections.php" method="POST" enctype="multipart/form-data">
        <!-- Candidate Name -->
        <div class="mb-3">
          <label for="candidateName" class="form-label">Candidate Name</label>
          <input type="text" name="candidate_name" id="candidateName" class="form-control" placeholder="e.g. Hari Sharma" required>
        </div>
        <!-- Election Dropdown -->
        <div class="mb-3">
          <label for="electionId" class="form-label">Select Election</label>
          <select name="election_id" id="electionId" class="form-select" required>
            <option value="">Select Election</option>
            <?php foreach ($elections as $election): ?>
              <option value="<?php echo $election['id']; ?>" data-ename="<?php echo htmlspecialchars($election['name']); ?>">
                <?php echo htmlspecialchars($election['election_type'] . " - " . $election['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Panel Dropdown (Now with 4 options) -->
        <div class="mb-3">
          <label for="panel" class="form-label">Select Panel</label>
          <select name="panel" id="panel" class="form-select" required>
            <option value="">Select Panel</option>
            <option value="Panel 1">Panel 1</option>
            <option value="Panel 2">Panel 2</option>
            <option value="Panel 3">Panel 3</option>
            <option value="Panel 4">Panel 4</option>
          </select>
        </div>
        <!-- Candidate Position Dropdown (Populated dynamically) -->
        <div class="mb-3">
          <label for="candidatePosition" class="form-label">Candidate Position</label>
          <select name="candidate_position" id="candidatePosition" class="form-select" required>
            <option value="">Select Candidate Position</option>
          </select>
        </div>
        <!-- Photo Upload -->
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


<!-- JavaScript to update candidate positions and enforce candidate count limits -->
<script>
document.addEventListener("DOMContentLoaded", function(){
  // Convert PHP arrays into JavaScript objects
  var electionsData = <?php echo json_encode($elections); ?>;
  var existingCandidates = <?php echo json_encode($existingCandidates); ?>;
  
  // Get references to the dropdowns in candidate form
  var electionSelect = document.getElementById("electionId");
  var panelSelect = document.getElementById("panel");
  var candidatePositionSelect = document.getElementById("candidatePosition");
  
  function updateCandidatePositions() {
    var electionId = electionSelect.value;
    var selectedPanel = panelSelect.value;
    candidatePositionSelect.innerHTML = '<option value="">Select Candidate Position</option>';
    
    if (electionId && selectedPanel) {
      // Find the selected election from the elections data
      var selectedElection = electionsData.find(function(election) {
        return election.id == electionId;
      });
      
      if (selectedElection) {
        var positions = [];
        // Determine key prefix based on selected panel (e.g., "panel1_pos" for "Panel 1")
        var panelNumber = selectedPanel.split(" ")[1];
        for (var i = 1; i <= 8; i++) {
          var key = "panel" + panelNumber + "_pos" + i;
          var pos = selectedElection[key];
          if (pos && pos.trim() !== "") {
            positions.push(pos);
          }
        }
        positions.forEach(function(pos) {
          var opt = document.createElement("option");
          opt.value = pos;
          opt.text = pos;
          candidatePositionSelect.appendChild(opt);
        });
      }
    }
  }
      electionSelect.addEventListener("change", updateCandidatePositions);
      panelSelect.addEventListener("change", updateCandidatePositions);
    
 // Function to show a modal with a given message
 function showModalMessage(message) {
    var messageEl = document.getElementById("candidateAlertMessage");
    messageEl.innerText = message;
    var modalEl = document.getElementById("candidateAlertModal");
    var modal = new bootstrap.Modal(modalEl);
    modal.show();
  }

      // Before form submission, check if a candidate already exists for the chosen election, panel, and position.
  document.getElementById("candidateForm").addEventListener("submit", function(e) {
    var selectedElectionOption = electionSelect.options[electionSelect.selectedIndex];
    var electionName = selectedElectionOption.getAttribute("data-ename");
    var panel = panelSelect.value;
    var candidatePos = candidatePositionSelect.value;
    
    // Count how many candidates already exist for this election, panel, and position.
    var count = existingCandidates.filter(function(item) {
      return item.election_name === electionName &&
             item.panel === panel &&
             item.candidate_position === candidatePos;
    }).length;
    
    // For each position, only one candidate is allowed. You can adjust the condition if multiple candidates per position are allowed.
    if (count >= 1) {
      e.preventDefault();
      showModalMessage("Candidates cannot be created more than the specified Election Positions for this slot.");
    }
  });
});

    // Functions to add more positions and panels
    document.addEventListener("DOMContentLoaded", function(){
      // Button to add one extra position to Panel 1 and Panel 2
      var addPanelPositionsBtn = document.getElementById("addPanel1PosBtn");
      addPanelPositionsBtn.addEventListener("click", function(){
        // Maximum allowed positions per panel is 8
        var panel1Container = document.getElementById("panel1Positions");
        var panel2Container = document.getElementById("panel2Positions");

        // Count current positions for Panel 1 (we assume each row has two inputs)
        var currentPanel1Inputs = panel1Container.querySelectorAll("input[type='text']").length;
        if (currentPanel1Inputs < 8) {
          // Add a new row with two extra positions (one for Panel 1 and one for Panel 2)
          var newRow1 = document.createElement("div");
          newRow1.className = "row mb-3";
          newRow1.innerHTML = `
            <div class="col-md-6">
              <label class="form-label">Position ${currentPanel1Inputs + 1}</label>
              <input type="text" name="panel1_pos${currentPanel1Inputs + 1}" class="form-control" placeholder="Extra Position">
            </div>
          `;
          panel1Container.appendChild(newRow1);

          var currentPanel2Inputs = document.getElementById("panel2Positions").querySelectorAll("input[type='text']").length;
          var newRow2 = document.createElement("div");
          newRow2.className = "row mb-3";
          newRow2.innerHTML = `
            <div class="col-md-6">
              <label class="form-label">Position ${currentPanel2Inputs + 1}</label>
              <input type="text" name="panel2_pos${currentPanel2Inputs + 1}" class="form-control" placeholder="Extra Position">
            </div>
          `;
          document.getElementById("panel2Positions").appendChild(newRow2);
        } else {
          alert("Maximum positions reached for Panel 1 and Panel 2.");
        }
      });
      
      // Button to add extra panels (Panel 3 and Panel 4) if not already shown
      var addPanelsBtn = document.getElementById("addPanelsBtn");
      addPanelsBtn.addEventListener("click", function(){
        var extraPanelsDiv = document.getElementById("extraPanels");
        if (extraPanelsDiv.style.display === "none") {
          extraPanelsDiv.style.display = "block";
        } else {
          alert("Extra panels already added.");
        }
      });
    });
  </script>
  
  <!-- Feedback Modal -->
  <div id="feedbackModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header bg-<?php echo $modalType ?>">
          <h5 class="modal-title text-white">
            <?php echo ucfirst($modalType) ?> Message
          </h5>
          <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <p><?php echo $modalMessage ?></p>
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-<?php echo $modalType ?>" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

 <!-- Candidate Alert Modal -->
<div class="modal fade" id="candidateAlertModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning<?php echo $modalType ?>">
        <h5 class="modal-title text-white">Alert</h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center" id="candidateAlertMessage">
        <!-- Message will be inserted here -->
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-<?php echo $modalType ?>" data-bs-dismiss="modal">OK</button>
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