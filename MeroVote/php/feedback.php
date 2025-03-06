<?php
session_start();
if ( !isset( $_SESSION[ 'user_id' ] ) ) {
    header( 'Location: ./voter_login.php' );
    exit();
}

include 'db_config.php';

// Handle Feedback Submission
if ( $_SERVER[ 'REQUEST_METHOD' ] == 'POST' && isset( $_POST[ 'submit_feedback' ] ) ) {
    $name = htmlspecialchars( trim( $_POST[ 'name' ] ) );
    $feedback = htmlspecialchars( trim( $_POST[ 'feedback' ] ) );
    $address = htmlspecialchars( trim( $_POST[ 'address' ] ) );

    if ( !empty( $name ) && !empty( $feedback ) && !empty( $address ) ) {
        try {
            $stmt = $pdo->prepare( 'INSERT INTO feedback (name, feedback, address) VALUES (:name, :feedback, :address)' );
            $stmt->execute( [
                ':name' => $name,
                ':feedback' => $feedback,
                ':address' => $address
            ] );
            $feedbackSuccess = 'Thank you for your feedback!';
        } catch ( PDOException $e ) {
            $feedbackError = 'Error submitting feedback: ' . $e->getMessage();
        }
    } else {
        $feedbackError = 'All fields are required!';
    }
}

$user_id = $_SESSION[ 'user_id' ];
$user_role = $_SESSION[ 'user_role' ];

// Role-based filtering of election types
$allowedElectionType = '';
if ( $user_role == 'School/College Level Election' ) {
    $allowedElectionType = 'School/College Level Election';
} elseif ( $user_role == 'Local Level Election' ) {
    $allowedElectionType = 'Local Level Election';
} elseif ( $user_role == 'Organizational Level Election' ) {
    $allowedElectionType = 'Organizational Level Election';
}

if ( empty( $allowedElectionType ) ) {
    die( 'Invalid user role.' );
}

$ongoingElections = [];
$expiredElections = [];
$currentDate = date( 'Y-m-d' );

try {
    // Fetch all elections based on election type
    $stmt = $pdo->prepare( "
        SELECT id, election_type, name, start_date, end_date
        FROM elections
        WHERE election_type = :election_type
        ORDER BY start_date ASC
    " );
    $stmt->execute( [ 'election_type' => $allowedElectionType ] );

    $processedExpiredElections = [];
    // Array to track processed election IDs

    while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
        // Separate ongoing and expired elections
        if ( $row[ 'start_date' ] <= $currentDate && $row[ 'end_date' ] >= $currentDate ) {
            $ongoingElections[] = $row;
            // Ongoing elections
        } elseif ( $row[ 'end_date' ] < $currentDate ) {
            // Check if this expired election has already been processed
            if ( !in_array( $row[ 'id' ], $processedExpiredElections ) ) {
                $expiredElections[] = $row;
                // Add to expired elections
                $processedExpiredElections[] = $row[ 'id' ];
                // Track processed election ID
            }
        }
    }

    // Process winner details for expired elections
    foreach ( $expiredElections as &$election ) {
        $election[ 'winner_name' ] = 'No Winner';
        $election[ 'winner_image' ] = 'default.jpg';
        $election[ 'winner_votes' ] = 0;

        $electionName = $election[ 'name' ];
        // Fetch using election name

        // Fetch winner based on votes count for each expired election
        $winnerStmt = $pdo->prepare( "
        SELECT 
            c.name AS candidate_name, 
            c.photo AS candidate_image, 
            COUNT(v.id) AS total_votes
        FROM candidates c
        LEFT JOIN votes v 
            ON v.candidate_id = c.id AND v.election_id = :election_name
        WHERE c.election_id = :election_name
        GROUP BY c.id, c.name, c.photo
        ORDER BY total_votes DESC
        LIMIT 1
    " );
        $winnerStmt->execute( [ 'election_name' => $electionName ] );
        $winner = $winnerStmt->fetch( PDO::FETCH_ASSOC );

        // Update winner details if votes exist
        if ( $winner && $winner[ 'total_votes' ] > 0 ) {
            $election[ 'winner_name' ] = $winner[ 'candidate_name' ];
            $election[ 'winner_image' ] = $winner[ 'candidate_image' ];
            $election[ 'winner_votes' ] = $winner[ 'total_votes' ];
        } else {
            // **Ensure the default image is assigned if no votes exist**
            $election[ 'winner_name' ] = 'No Winner';
            $election[ 'winner_image' ] = './candidates_photos/default.jpg';
            $election[ 'winner_votes' ] = 0;
        }
    }

} catch ( PDOException $e ) {
    die( 'Error fetching elections: ' . $e->getMessage() );
}
?>

<!doctype html>
<html lang = 'en'>

<head>
<title>Dashboard - MeroVote</title>
<meta charset = 'utf-8' />
<meta name = 'viewport' content = 'width=device-width, initial-scale=1, shrink-to-fit=no' />
<link href = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel = 'stylesheet' />
<link rel = 'stylesheet' href = '../css/styles.css' />
</head>

<body>
<header>
<nav class = 'navbar navbar-expand-lg navbar-dark bg-dark'>
<div class = 'container-fluid'>
<!-- Brand Logo and Name -->
<a class = 'navbar-brand d-flex align-items-center' href = '../index.html'>
<img src = '../img/MeroVote-Logo.png' style = 'height: 60px; width: auto;' alt = 'MeroVote Logo' class = 'logo img-fluid me-2'>
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
<a class = 'nav-link' href = 'voter_login.php'>Login</a>
</li>
<li class = 'nav-item'>
<a class = 'nav-link' href = '../index.html#how'>How It Works</a>
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

<main class = 'container mt-4'>
<h1 class = 'text-center text-primary mb-4'>

</h1>
<div class = 'extra'>
<!-- Feedback Form -->
<div class = 'feedback mt-5'>
<h2 class = 'text-center text-info mb-4'>We Value Your Feedback!</h2>

<!-- Success/Error Messages -->
<?php if ( isset( $feedbackSuccess ) ): ?>
<div class = 'alert alert-success text-center'><?php echo $feedbackSuccess;
?></div>
<?php elseif ( isset( $feedbackError ) ): ?>
<div class = 'alert alert-danger text-center'><?php echo $feedbackError;
?></div>
<?php endif;
?>

<form method = 'POST' action = '' class = 'shadow p-4 rounded bg-light'>
<div class = 'form-group mb-3'>
<label for = 'name' class = 'form-label'>Your Name</label>
<input type = 'text' name = 'name' id = 'name' class = 'form-control' placeholder = 'Enter your name' required>
</div>

<div class = 'form-group mb-3'>
<label for = 'feedback' class = 'form-label'>Your Feedback</label>
<textarea name = 'feedback' id = 'feedback' class = 'form-control' rows = '4' placeholder = 'Share your thoughts here...' required></textarea>
</div>

<div class = 'form-group mb-4'>
<label for = 'address' class = 'form-label'>Your Address</label>
<input type = 'text' name = 'address' id = 'address' class = 'form-control' placeholder = 'Enter your address' required>
</div>

<button type = 'submit' name = 'submit_feedback' class = 'btn btn-primary w-100'>Submit Feedback</button>
</form>
</div>

</div>

</main>

<footer class = 'bg-dark text-white text-center py-3'>
<div class = 'container'>
<p>&copy;
2024 Online Voting System. All rights reserved.</p>
</div>
</footer>

<!-- Feedback Modal -->
<div id = 'feedbackModal' class = 'modal fade' tabindex = '-1' role = 'dialog'>
<div class = 'modal-dialog modal-dialog-centered' role = 'document'>
<div class = 'modal-content'>
<div class = "modal-header bg-<?= $modalType ?>">
<h5 class = 'modal-title text-white'>< ?= ucfirst( $modalType ) ?> Message</h5>
<button type = 'button' class = 'btn-close text-white' data-bs-dismiss = 'modal' aria-label = 'Close'></button>
</div>
<div class = 'modal-body text-center'>
<p>< ?= $modalMessage ?></p>
</div>
<div class = 'modal-footer justify-content-center'>
<button type = 'button' class = "btn btn-<?= $modalType ?>" data-bs-dismiss = 'modal'>Close</button>
</div>
</div>
</div>
</div>

<script src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js'></script>
<script>
// Redirect to create election page

function createElection() {
    window.location.href = 'elections.php';
}
</script>
</body>

</html>