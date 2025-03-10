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

$electionId = $_POST['election_id'] ?? $_GET['election_id'] ?? null;
if (!$electionId) {
    die('Election ID not specified.');
}

function generateVoterHash($user_id, $election_id) {
    return hash('sha256', trim($user_id) . '_' . trim($election_id) . '_AngAd');
}

try {
    $stmt = $pdo->prepare('SELECT id, name, photo, candidate_position FROM candidates WHERE elect_no = :election_id');
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
    $stmt = $pdo->prepare("SELECT name FROM elections WHERE id = :election_id");
    $stmt->execute(['election_id' => $electionId]);
    $electionName = $stmt->fetchColumn();
    
    $voteStmt = $pdo->prepare("
        SELECT c.id AS candidate_id, c.name AS candidate_name, c.candidate_position, COUNT(v.id) AS vote_count
        FROM votes v
        INNER JOIN candidates c ON v.candidate_id = c.id
        WHERE v.election = :election_name
        GROUP BY c.id, c.name, c.candidate_position
    ");
    $voteStmt->execute(['election_name' => $electionName]);
    $voteCounts = $voteStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error fetching vote counts: ' . $e->getMessage());
}

date_default_timezone_set('Asia/Kathmandu');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidateId = $_POST['candidate'] ?? null;
    $current_time = date('H:i:s');

    $stmt = $pdo->prepare("SELECT start_time, end_time, name FROM elections WHERE id = :election_id");
    $stmt->bindParam(':election_id', $electionId, PDO::PARAM_INT);
    $stmt->execute();
    $election = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$election) {
        $_SESSION['message'] = "Election details not found.";
        $_SESSION['msg_type'] = "danger";
        $_SESSION['show_modal'] = true;
    } else {
        $start_time = (new DateTime($election['start_time']))->format('H:i:s');
        $end_time = (new DateTime($election['end_time']))->format('H:i:s');

        if ($current_time < $start_time || $current_time > $end_time) {
            $_SESSION['message'] = "Voting is allowed only between " . $start_time . " and " . $end_time;
            $_SESSION['msg_type'] = "warning";
            $_SESSION['show_modal'] = true;
        } elseif ($candidateId) {
            try {
                $stmt = $pdo->prepare("
                    SELECT id, name, candidate_position FROM candidates 
                    WHERE id = :cand_id AND elect_no = :election_id
                ");
                $stmt->execute([
                    'cand_id' => $candidateId,
                    'election_id' => $electionId
                ]);
                $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$candidate) {
                    $_SESSION['message'] = "Invalid candidate selected.";
                    $_SESSION['msg_type'] = "danger";
                    $_SESSION['show_modal'] = true;
                } else {
                    $voterHash = generateVoterHash($_SESSION['user_id'], $electionId);

                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM votes 
                        WHERE hashed_user_id = :hashed_user_id AND election = :election_name
                    ");
                    $stmt->execute([
                        'hashed_user_id' => $voterHash,
                        'election_name' => $election['name']
                    ]);
                    $voteCount = $stmt->fetchColumn();

                    if ($voteCount > 0) {
                        $_SESSION['message'] = "You have already voted in this election.";
                        $_SESSION['msg_type'] = "warning";
                        $_SESSION['show_modal'] = true;
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO votes (hashed_user_id, candidate_id, candidate_name, candidate_position, election) 
                            VALUES (:hashed_user_id, :candidate_id, :candidate_name, :candidate_position, :election_name)
                        ");
                        $stmt->execute([
                            'hashed_user_id' => $voterHash,
                            'candidate_id' => $candidate['id'],
                            'candidate_name' => $candidate['name'],
                            'candidate_position' => $candidate['candidate_position'],
                            'election_name' => $election['name']
                        ]);
                        
                        $_SESSION['message'] = "Vote successfully submitted!";
                        $_SESSION['msg_type'] = "success";
                        $_SESSION['show_modal'] = true;
                    }
                }
            } catch (PDOException $e) {
                $_SESSION['message'] = "Error submitting vote: " . htmlspecialchars($e->getMessage());
                $_SESSION['msg_type'] = "danger";
                $_SESSION['show_modal'] = true;
            }
        } else {
            $_SESSION['message'] = "Please select a candidate.";
            $_SESSION['msg_type'] = "warning";
            $_SESSION['show_modal'] = true;
        }
    }

   // After processing the vote, send a thank-you SMS to the voter
if ($_SESSION['msg_type'] === "success") {
    // Retrieve voter's phone number from the session
    $phone_number = $_SESSION['phone-number'] ?? null;
    if ($phone_number) {
        $message = "Dear voter, thanks for casting your vote through MeroVote!";
        // Use include_once so that otp-api.php is included only once
        include_once 'otp-api.php'; // Ensure the path is correct
  
        // Conditionally define logToFile if it doesn't exist
        if (!function_exists('logToFile')) {
            function logToFile($message) {
                $logFile = 'sms_api.log';
                $timestamp = date('Y-m-d H:i:s');
                $fullMessage = "[$timestamp] $message\n";
                file_put_contents($logFile, $fullMessage, FILE_APPEND);
            }
        }
  
        // Call the sendSMS function (assumed to be defined in otp-api.php)
        $smsResult = sendSMS($phone_number, $message);
        if (!$smsResult['success']) {
            logToFile("SMS Error: " . $smsResult['message']);
        }
    } else {
        logToFile("Mobile number not found in session.");
    }
  }
    
    header('Location: vote.php?election_id=' . $electionId);
    exit();
}
?>


<!doctype html>
<html lang = 'en'>

<head>
<title>Vote Now - MeroVote</title>
<link href = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel = 'stylesheet' />
<link rel = 'stylesheet' href = '../css/styles.css' />
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
</head>

<body>
<header>
<nav class = 'navbar navbar-expand-lg navbar-dark bg-dark'>
<div class = 'container-fluid'>
<!-- Brand Logo and Name -->
<a class = 'navbar-brand d-flex align-items-center' href = '../index.html'>
<img src = '../img/MeroVote-Logo.png' style = 'height: 60px; width: auto;' alt = 'Logo' class = 'logo img-fluid me-2'>
<span></span>
</a>

<!-- Toggler Button for Small Screens -->
<button class = ' navbar-toggler' type = 'button' data-bs-toggle = 'collapse'
data-bs-target = '#navbarSupportedContent' aria-controls = 'navbarSupportedContent'
aria-expanded = 'false' aria-label = 'Toggle navigation'>
<span class = 'navbar-toggler-icon'></span>
</button>

<!-- Navbar Content -->
<div class = 'collapse navbar-collapse' id = 'navbarSupportedContent'>
<!-- Navbar Items -->
<ul class = 'navbar-nav ms-auto mb-2 mb-lg-0'>
<li class = 'nav-item'>
<a class = 'nav-link' href = '../index.html#how'>How It Works</a>
</li>
<li class = 'nav-item'>
<a class = 'nav-link' href = 'feedback.php'>Feedback</a>
</li>
<li class = 'nav-item'>
<a class = 'nav-link' href = 'voter_dashboard.php'>Dashboard</a>
</li>
<li class = 'nav-item'>
<a class = 'nav-link' href = 'logout.php'>Logout</a>
</li>
</ul>
</div>
</div>
</nav>
</header>
<div class="container mt-5">
    <h1 class="text-center text-primary mb-4">Vote for Your Candidate</h1>
    <form method="post" action="vote.php?election_id=<?php echo htmlspecialchars($electionId); ?>">
        <!-- Hidden field to pass election ID in POST data -->
        <input type="hidden" name="election_id" value="<?php echo htmlspecialchars($electionId); ?>">
        <div class="row justify-content-center">
            <?php foreach ($candidates as $candidate): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm candidate-card">
                        <div class="card-body text-center">
                            <input 
                                class="form-check-input d-none" 
                                type="radio" 
                                name="candidate" 
                                id="candidate<?php echo $candidate['id']; ?>" 
                                value="<?php echo $candidate['id']; ?>" 
                                required
                            >
                            <label class="form-check-label d-flex flex-column align-items-center candidate-label" 
                                   for="candidate<?php echo $candidate['id']; ?>">
                                <img src="<?php echo htmlspecialchars($candidate['photo']); ?>" 
                                     alt="Candidate Photo" 
                                     class="candidate-photo img-thumbnail mb-3">
                                <strong class="candidate-name">
                                    <?php echo htmlspecialchars($candidate['name']); ?>
                                </strong>
                                <!-- Display candidate position -->
                                <span class="badge bg-secondary mt-2">
                                    <?php echo htmlspecialchars($candidate['candidate_position']); ?>
                                </span>
                            </label>
                        </div>
                    </div>
                    <!-- Display live vote counts -->
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
            <?php endforeach; ?>
        </div>
        <div class="text-center">
            <button type="submit" name="vote_submit" class="btn btn-success mt-4 px-5">Submit Vote</button>
        </div>
    </form>

    <!-- Feedback Modal -->
    <?php if (!empty($_SESSION['message'])): ?>
    <div id="feedbackModal" class="modal fade show" tabindex="-1" role="dialog" style="display: block; background: rgba(0, 0, 0, 0.5);">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-<?php echo $_SESSION['msg_type'] ?? 'warning'; ?>">
                    <h5 class="modal-title text-white"><?php echo ucfirst($_SESSION['msg_type'] ?? ''); ?> Message</h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <p><?php echo $_SESSION['message'] ?? ''; ?></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-<?php echo $_SESSION['msg_type'] ?? 'warning'; ?>" data-bs-dismiss="modal" onclick="hideModal()">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php 
        // Clear session message after displaying modal
        unset($_SESSION['message']);
        unset($_SESSION['msg_type']);
    endif; 
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('feedbackModal');
    if (modal) {
        var feedbackModal = new bootstrap.Modal(modal);
        feedbackModal.show();
    }
});

// Hide modal and remove overlay when closed
function hideModal() {
    var modal = document.getElementById('feedbackModal');
    if (modal) {
        modal.style.display = "none";
    }
}

// Enable label selection functionality for radio buttons
document.querySelectorAll('.candidate-label').forEach(label => {
    label.addEventListener('click', function() {
        let radioInput = this.previousElementSibling;
        if (radioInput) {
            radioInput.checked = true;
        }
    });
});
</script>
</body>

</html>