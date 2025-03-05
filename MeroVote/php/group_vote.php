<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ./voter_login.php');
    exit();
}

include 'db_config.php';

// Get election ID from POST if available, else from GET
$electionId = $_POST['election_id'] ?? $_GET['election_id'] ?? null;
if (!$electionId) {
    die('Election ID not specified.');
}

function generateVoterHash($user_id, $election_id) {
    return hash('sha256', trim($user_id) . '_' . trim($election_id) . '_AngAd');
}

try {
    // Fetch all candidates for this group election
    $stmt = $pdo->prepare('SELECT id, name, photo, panel, candidate_position FROM candidates_group WHERE elect_no = :election_id');
    $stmt->execute(['election_id' => $electionId]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($candidates)) {
        die('No candidates found for this election.');
    }
} catch (PDOException $e) {
    die('Error fetching candidates: ' . $e->getMessage());
}

$voteCounts = [];
try {
    $stmt = $pdo->prepare("SELECT name FROM elections_group WHERE id = :election_id");
    $stmt->execute(['election_id' => $electionId]);
    $electionName = $stmt->fetchColumn();
    
    $voteStmt = $pdo->prepare("SELECT c.id AS candidate_id, c.name AS candidate_name, c.candidate_position, COUNT(v.id) AS vote_count 
                               FROM votes_group v 
                               INNER JOIN candidates_group c ON v.candidate_id = c.id 
                               WHERE v.election = :election_name 
                               GROUP BY c.id, c.name, c.candidate_position");
    $voteStmt->execute(['election_name' => $electionName]);
    $voteCounts = $voteStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error fetching vote counts: ' . $e->getMessage());
}

date_default_timezone_set('Asia/Kathmandu');

// For GET requests (and in case POST hasn't computed totalPositions), compute it from candidates_group:
$stmt = $pdo->prepare("SELECT DISTINCT candidate_position FROM candidates_group WHERE elect_no = :election_id");
$stmt->execute(['election_id' => $electionId]);
$uniquePositionsArray = $stmt->fetchAll(PDO::FETCH_COLUMN);
$totalPositions = count($uniquePositionsArray);
if ($totalPositions === 0) {
    die('No candidate positions found for this election.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidateIds = $_POST['candidate'] ?? [];
    if (!is_array($candidateIds)) {
        $_SESSION['message'] = "Please select candidates properly.";
        $_SESSION['msg_type'] = "warning";
        header('Location: group_vote.php?election_id=' . $electionId);
        exit();
    }
    
    $current_time = date('H:i:s');
    
    // Fetch election details
    $stmt = $pdo->prepare("SELECT start_time, end_time, name FROM elections_group WHERE id = :election_id");
    $stmt->execute(['election_id' => $electionId]);
    $election = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$election) {
        $_SESSION['message'] = "Election details not found.";
        $_SESSION['msg_type'] = "danger";
        header('Location: group_vote.php?election_id=' . $electionId);
        exit();
    }
    
    $start_time = (new DateTime($election['start_time']))->format('H:i:s');
    $end_time = (new DateTime($election['end_time']))->format('H:i:s');
    
    if ($current_time < $start_time || $current_time > $end_time) {
        $_SESSION['message'] = "Voting is allowed only between " . $start_time . " and " . $end_time;
        $_SESSION['msg_type'] = "warning";
        header('Location: group_vote.php?election_id=' . $electionId);
        exit();
    }
    
    $voterHash = generateVoterHash($_SESSION['user_id'], $electionId);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM votes_group WHERE hashed_user_id = :hashed_user_id AND election = :election_name");
    $stmt->execute(['hashed_user_id' => $voterHash, 'election_name' => $election['name']]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['message'] = "You have already voted in this election.";
        $_SESSION['msg_type'] = "warning";
        header('Location: group_vote.php?election_id=' . $electionId);
        exit();
    }
    
    // Validation: The voter must select exactly $totalPositions candidates.
    if (count($candidateIds) !== $totalPositions) {
        $_SESSION['message'] = "Please select exactly " . $totalPositions . " candidates.";
        $_SESSION['msg_type'] = "warning";
        header('Location: group_vote.php?election_id=' . $electionId);
        exit();
    }

    $selectedPositions = [];
    $candidateDetails = [];
    
    // Fetch details for each selected candidate
    foreach ($candidateIds as $candId) {
        $stmt = $pdo->prepare("SELECT id, name, candidate_position, panel FROM candidates_group WHERE id = :cand_id AND elect_no = :election_id");
        $stmt->execute(['cand_id' => $candId, 'election_id' => $electionId]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$candidate) {
            continue;
        }
    
        // Track selected candidate positions
        $selectedPositions[] = $candidate['candidate_position'];
        $candidateDetails[] = $candidate;
    }
    
    // Ensure no duplicate positions are selected
    if (count(array_unique($selectedPositions)) !== count($selectedPositions)) {
        $_SESSION['message'] = "You cannot select multiple candidates for the same position, even across different panels.";
        $_SESSION['msg_type'] = "warning";
        header('Location: group_vote.php?election_id=' . $electionId);
        exit();
    }
    
    // Ensure the voter selects exactly the number of unique positions required
    if (count(array_unique($selectedPositions)) !== $totalPositions) {
        $_SESSION['message'] = 'You must select ' . $totalPositions . ' unique positions.';
        $_SESSION['msg_type'] = 'warning';
        header('Location: group_vote.php?election_id=' . $electionId);
        exit();
    }
    
    // Process vote submission
    try {
        foreach ($candidateDetails as $candidate) {
            $stmtInsert = $pdo->prepare("INSERT INTO votes_group (hashed_user_id, candidate_id, candidate_name, candidate_position, election) 
                                         VALUES (:hashed_user_id, :candidate_id, :candidate_name, :candidate_position, :election_name)");
            $stmtInsert->execute([ 
                'hashed_user_id' => $voterHash,
                'candidate_id' => $candidate['id'],
                'candidate_name' => $candidate['name'],
                'candidate_position' => $candidate['candidate_position'],
                'election_name' => $election['name']
            ]);
        }
    
        $_SESSION['message'] = "Vote successfully submitted!";
        $_SESSION['msg_type'] = "success";
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error submitting vote: " . htmlspecialchars($e->getMessage());
        $_SESSION['msg_type'] = "danger";
    }

    if ($_SESSION['msg_type'] === "success") {
      $phone_number = $_SESSION['phone-number'] ?? null;
      if ($phone_number) {
          $message = "Dear voter, thanks for casting your vote through MeroVote!";
          include 'otp-api.php'; // Ensure this path is correct
          $smsResult = sendSMS($phone_number, $message);
          if (!$smsResult['success']) {
              // Log the error or handle it as needed
              logToFile("SMS Error: " . $smsResult['message']);
          }
      } else {
          logToFile("Mobile number not found in session.");
      }
  }
  
    
    header('Location: group_vote.php?election_id=' . $electionId);
    exit();
}
?>


<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Vote Now - MeroVote</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../css/styles.css" />
</head>
<body>
<header>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <!-- Brand Logo and Name -->
      <a class="navbar-brand d-flex align-items-center" href="voter_dashboard.php">
        <img src="../img/MeroVote-Logo.png" style="height: 60px; width: auto;" alt="Logo" class="logo img-fluid me-2">
        <span></span>
      </a>
      <!-- Toggler Button for Small Screens -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
              data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
              aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <!-- Navbar Content -->
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <!-- Navbar Items -->
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link" href="../index.html#how">How It Works</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="feedback.php">Feedback</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="voter_grp_dashboard.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">Logout</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</header>
<style>
body {
    background-color: #f8f9fa;
}

.container {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba( 0, 0, 0, 0.1 );
    padding: 20px;
}

.panel-title {
      margin-top: 20px;
      margin-bottom: 10px;
      font-weight: bold;
      text-align: center;
    }

/* Candidate card base style */
.candidate-card {
    border: 2px solid transparent;
    border-radius: 10px;
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
    cursor: pointer;
    overflow: hidden;
}

/* Hover effect for candidate card */
.candidate-card:hover {
    transform: scale( 1.05 );
    box-shadow: 0 5px 15px rgba( 0, 0, 0, 0.2 );
    border-color: #007bff;
}

/* Selected state */
.candidate-card input:checked+label {
    background: linear-gradient( to bottom right, #00bbff, #007bff, #0047ab, #1e3a8a );
    /* Blue gradient */
    color: #FFFFFF;
    /* Text color */
    border-radius: 10px;
    box-shadow: 0 8px 20px rgba( 0, 123, 255, 0.4 );
    transition: all 0.3s ease;
    /* Smooth transition */
}

/* Candidate photo style */
.candidate-photo {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #007bff;
    transition: border-color 0.2s, box-shadow 0.3s;
}

/* Highlight photo border on selection */
.candidate-card input:checked+label .candidate-photo {
    border-color: #007bff;
    box-shadow: 0 4px 15px rgba( 0, 123, 255, 0.5 );
    /* Lightened blue shadow */
}

/* Candidate name styling */
.candidate-name {
    font-size: 1.3rem;
    font-weight: 600;
    margin-top: 10px;
    transition: color 0.3s ease;
}

/* Button styling */
button[ type = 'submit' ] {
    font-size: 1.1rem;
    font-weight: bold;
    padding: 10px 20px;
    background: linear-gradient( to right, #28a745, #218838 );
    /* Green gradient for submit button */
    border: none;
    color: #fff;
    border-radius: 5px;
    transition: background 0.3s ease, box-shadow 0.3s ease;
}

/* Button hover effect */
button[ type = 'submit' ]:hover {
    background: linear-gradient( to right, #218838, #1e7e34 );
    box-shadow: 0 5px 15px rgba( 40, 167, 69, 0.3 );
}

.modal-header.bg-success {
    background-color: #28a745 !important;
}

.modal-header.bg-danger {
    background-color: #dc3545 !important;
}

.modal-header.bg-warning {
    background-color: #ffc107 !important;
    color: #212529;
}

.modal-content {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba( 0, 0, 0, 0.3 );
}

.modal-body {
    font-size: 1.1rem;
}

.modal-footer .btn {
    padding: 0.5rem 2rem;
    font-size: 1rem;
    font-weight: bold;
}

/* Style for the live vote count list */
.live-vote-count {
    border: 2px solid #007bff;
    border-radius: 10px;
    padding: 10px;
    background-color: #f9f9ff;
    box-shadow: 0 4px 8px rgba( 0, 0, 0, 0.1 );
}

/* Style for individual vote details */
.vote-details {
    font-size: 1.1rem;
    color: #343a40;
}

/* Badge styling for the vote count */
.vote-badge {
    font-size: 1rem;
    font-weight: bold;
    padding: 10px;
    border-radius: 20px;
    box-shadow: 0 2px 6px rgba( 0, 255, 0, 0.4 );
}

/* Add hover effect for list items */
.live-vote-count .list-group-item:hover {
    background-color: #eef7ff;
    transform: translateY( -2px );
    box-shadow: 0 2px 8px rgba( 0, 0, 123, 0.2 );
    transition: all 0.3s ease-in-out;
}

/* Styling for the no votes yet message */
.live-vote-count .text-muted {
    font-style: italic;
    color: #6c757d !important;
    font-size: 0.95rem;
}

footer {
    background-color: #343a40;
    color: #ffffff;
    padding: 1.5rem 0;
    margin-top: 10rem;
}

footer a {
    color: #17a2b8;
    text-decoration: none;
}

footer a:hover {
    text-decoration: underline;
}
</style>
<div class="container mt-5">
  <h1 class="text-center text-primary mb-4">Vote for Your Candidate</h1>
  <p class="text-center text-muted">
    Please select candidates (each from a different position) from any panel.
  </p>
  
  <form id="voteForm" method="post" action="group_vote.php?election_id=<?php echo htmlspecialchars($electionId); ?>">
    <input type="hidden" name="election_id" value="<?php echo htmlspecialchars($electionId); ?>">
    <!-- Use computed totalPositions -->
    <input type="hidden" id="total_positions" value="<?php echo (int)$totalPositions; ?>">

    <?php 
      // Group candidates by panel
      $panels = array_unique(array_column($candidates, 'panel'));
      foreach ($panels as $panel):
    ?>
      <h2 class="panel-title"><?php echo htmlspecialchars($panel); ?></h2>
      <div class="row">
        <?php 
          $panelCandidates = array_filter($candidates, function($c) use ($panel) {
            return strtolower(trim($c['panel'])) === strtolower(trim($panel));
          });
          
          if (!empty($panelCandidates)):
            foreach ($panelCandidates as $candidate):
        ?>
          <div class="col-md-4 mb-4">
            <div class="card shadow-sm candidate-card" style="cursor: pointer;">
              <div class="card-body text-center">
                <input class="form-check-input candidate-checkbox" type="checkbox" name="candidate[]" 
                       id="candidate<?php echo $candidate['id']; ?>" 
                       value="<?php echo $candidate['id']; ?>" 
                       data-position="<?php echo htmlspecialchars($candidate['candidate_position']); ?>"
                       data-panel="<?php echo htmlspecialchars($candidate['panel']); ?>">
                <label class="form-check-label d-flex flex-column align-items-center candidate-label" 
                       for="candidate<?php echo $candidate['id']; ?>">
                  <img src="<?php echo htmlspecialchars($candidate['photo']); ?>" 
                       alt="Candidate Photo" class="candidate-photo img-thumbnail mb-3">
                  <strong class="candidate-name"><?php echo htmlspecialchars($candidate['name']); ?></strong>
                  <span class="badge bg-secondary mt-2"><?php echo htmlspecialchars($candidate['candidate_position']); ?></span>
                </label>
              </div>
            </div>

            <h6 class="text-primary mt-3">Live Vote Count:</h6>
            <ul class="list-group live-vote-count mb-3">
              <?php
              $found = false;
              foreach ($voteCounts as $vote) {
                  if (isset($vote['candidate_id']) && $vote['candidate_id'] == $candidate['id']) {
                      $found = true;
                      echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
                      echo "<div class='vote-details d-flex align-items-center'>";
                      echo "<span class='fw-bold'>" . htmlspecialchars($candidate['name']) . "</span>";
                      echo "</div>";
                      echo "<span class='badge bg-success vote-badge'>" . htmlspecialchars($vote['vote_count']) . "</span>";
                      echo "</li>";
                  }
              }
              if (!$found) {
                  echo "<li class='list-group-item text-muted text-center'>No votes yet.</li>";
              }
              ?>
            </ul>
          </div>
        <?php endforeach; else: ?>
          <div class="col-12 text-center">
            <p class="alert alert-warning">No candidates available in <?php echo htmlspecialchars($panel); ?>.</p>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <div class="text-center">
      <button type="submit" id="submitVoteBtn" class="btn btn-success mt-4 px-5">Submit Vote</button>
    </div>
  </form>
</div>

<!-- Feedback Modal (always in DOM) -->
<div id="feedbackModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-show-modal="<?php echo !empty($_SESSION['message']) ? 'true' : 'false'; ?>">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-<?php echo $_SESSION['msg_type'] ?? 'warning'; ?>">
        <h5 class="modal-title text-white">
          <?php echo isset($_SESSION['msg_type']) ? ucfirst($_SESSION['msg_type']) : 'Message'; ?>
        </h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p><?php echo $_SESSION['message'] ?? ''; ?></p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-<?php echo $_SESSION['msg_type'] ?? 'warning'; ?>" data-bs-dismiss="modal">
          Close
        </button>
      </div>
    </div>
  </div>
</div>
<?php 
// Clear session message after displaying modal
if(isset($_SESSION['message'])) {
    unset($_SESSION['message']);
    unset($_SESSION['msg_type']);
}
?>

<!-- Load Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Only auto-show the modal if data-show-modal is "true"
    var modal = document.getElementById('feedbackModal');
    if (modal && modal.getAttribute('data-show-modal') === 'true') {
        var feedbackModal = new bootstrap.Modal(modal);
        feedbackModal.show();
    }
    
    // Make candidate cards clickable
    document.querySelectorAll('.candidate-card').forEach(card => {
        card.addEventListener('click', function (e) {
            if (e.target.tagName.toLowerCase() === 'input' || e.target.tagName.toLowerCase() === 'label') return;
            const checkbox = card.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                card.classList.toggle('selected', checkbox.checked);
            }
        });
    });
});

// Validate unique candidate positions on form submission
document.getElementById('voteForm').addEventListener('submit', function(event) {
    const selectedCandidates = document.querySelectorAll('.candidate-checkbox:checked');
    const selectedPositions = [];
    
    selectedCandidates.forEach(function(candidate) {
        const position = candidate.getAttribute('data-position');
        selectedPositions.push(position);
    });

    const uniquePositions = [...new Set(selectedPositions)];
    const totalPositions = document.getElementById('total_positions').value;

    if (uniquePositions.length !== parseInt(totalPositions)) {
        event.preventDefault();
        showModal('Warning', 'You must select ' + totalPositions + ' unique positions.');
    }
});

// Function to display the modal with given title and message
function showModal(title, message) {
    var modalElement = document.getElementById('feedbackModal');
    if (modalElement) {
        var modalInstance = new bootstrap.Modal(modalElement);
        document.querySelector('.modal-title').innerText = title;
        document.querySelector('.modal-body p').innerText = message;
        modalInstance.show();
    } else {
        console.error('Modal element not found.');
    }
}
</script>
</body>
</html>