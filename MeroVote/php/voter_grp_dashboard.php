<?php
session_start();
if ( !isset( $_SESSION[ 'user_id' ] ) ) {
    header( 'Location: ./voter_login.php' );
    exit();
}

include 'db_config.php';

$user_id = $_SESSION[ 'user_id' ];
$user_role = $_SESSION[ 'user_role' ];

// Role-based filtering of election types ( for group voting )
$allowedElectionType = '';
if ( $user_role == 'School/College Level Election-Group' ) {
    $allowedElectionType = 'School/College Level Election-Group';
} elseif ( $user_role == 'Local Level Election-Group' ) {
    $allowedElectionType = 'Local Level Election-Group';
} elseif ( $user_role == 'Organizational Level Election-Group' ) {
    $allowedElectionType = 'Organizational Level Election-Group';
}

if ( empty( $allowedElectionType ) ) {
    die( 'Invalid user role.' );
}

$ongoingElections = [];
$expiredElections = [];
$currentDate = date( 'Y-m-d' );

try {
    // Fetch all elections from elections_group for the given group election type
    $stmt = $pdo->prepare( "
        SELECT id, election_type, name, start_date, end_date, start_time, end_time, 
               panel1_pos1, panel1_pos2, panel1_pos3, panel1_pos4, 
               panel2_pos1, panel2_pos2, panel2_pos3, panel2_pos4
        FROM elections_group
        WHERE election_type = :election_type
        ORDER BY start_date ASC
    " );
    $stmt->execute( [ 'election_type' => $allowedElectionType ] );

    $processedExpiredElections = [];
    // To track expired election IDs
    while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
        // Build an election data array from available fields
        $electionData = [
            'id' => $row[ 'id' ],
            'election_type' => $row[ 'election_type' ],
            'name' => $row[ 'name' ],
            'start_date' => $row[ 'start_date' ],
            'end_date' => $row[ 'end_date' ],
            'start_time' => $row[ 'start_time' ],
            'end_time' => $row[ 'end_time' ],
            // Panel 1 positions
            'panel1_pos1' => $row[ 'panel1_pos1' ],
            'panel1_pos2' => $row[ 'panel1_pos2' ],
            'panel1_pos3' => $row[ 'panel1_pos3' ],
            'panel1_pos4' => $row[ 'panel1_pos4' ],
            // Panel 2 positions
            'panel2_pos1' => $row[ 'panel2_pos1' ],
            'panel2_pos2' => $row[ 'panel2_pos2' ],
            'panel2_pos3' => $row[ 'panel2_pos3' ],
            'panel2_pos4' => $row[ 'panel2_pos4' ]
        ];

        // Separate ongoing and expired elections based on the dates
        if ( $row[ 'start_date' ] <= $currentDate && $row[ 'end_date' ] >= $currentDate ) {
            $ongoingElections[] = $electionData;
        } elseif ( $row[ 'end_date' ] < $currentDate ) {
            if ( !in_array( $row[ 'id' ], $processedExpiredElections ) ) {
                $expiredElections[] = $electionData;
                $processedExpiredElections[] = $row[ 'id' ];
            }
        }
    }

    // Process winner details for expired elections
    foreach ( $expiredElections as &$election ) {
        $election[ 'winner_name' ] = 'No Winner';
        $election[ 'winner_image' ] = './candidates_photos/default.jpg';
        $election[ 'winner_votes' ] = 0;

        $electionName = $election[ 'name' ];

        $winnerStmt = $pdo->prepare( "
            SELECT 
                c.name AS candidate_name, 
                c.photo AS candidate_image,     
                COUNT(v.id) AS total_votes
            FROM candidates_group c
            LEFT JOIN votes_group v 
                ON v.candidate_id = c.id AND v.election = :election_name
            WHERE c.election_name = :election_name
            GROUP BY c.id, c.name, c.photo
            ORDER BY total_votes DESC
            LIMIT 1
        " );
        $winnerStmt->execute( [ 'election_name' => $electionName ] );
        $winner = $winnerStmt->fetch( PDO::FETCH_ASSOC );

        if ( $winner && !empty( $winner[ 'candidate_name' ] ) ) {
            $election[ 'winner_name' ] = $winner[ 'candidate_name' ];
            $election[ 'winner_image' ] = !empty( $winner[ 'candidate_image' ] ) ? $winner[ 'candidate_image' ] : './candidates_photos/default.jpg';
            $election[ 'winner_votes' ] = $winner[ 'total_votes' ] ?? 0;
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
<a class = 'navbar-brand d-flex align-items-center' href = 'voter_dashboard.php'>
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
<a class = 'nav-link' href = 'feedback.php'>Feedback</a>
</li>
<li class = 'nav-item'>
<a class = 'nav-link' href = '../index.html#how'>How It Works</a>
</li>
<li class = 'nav-item'>
<a class = 'nav-link' href = 'voter_grp_dashboard.php'>Dashboard</a>
</li>
<li class = 'nav-item'>
<a class = 'nav-link' href = 'logout.php'>Logout</a>
</li>
</ul>
</div>
</div>
</nav>
</header>

<main class = 'container mt-5'>
<h1 class = 'text-center text-primary mb-4'>
Welcome, <span class = 'fw-bold'>Voter!</span>
</h1>

<!-- Ongoing Elections Section -->
<section class = 'mt-4'>
<h2 class = 'text-success mb-3 text-center'>Ongoing Elections</h2>
<div id = 'ongoingElections' class = 'row'>
<?php if ( !empty( $ongoingElections ) ): ?>
<?php foreach ( $ongoingElections as $election ): ?>
<div class = 'col-md-4 mb-4'>
<div class = 'card shadow-sm border-success'>
<div class = 'card-header bg-success text-white'>
<strong><?php echo htmlspecialchars( $election[ 'election_type' ] );
?></strong>
</div>
<div class = 'card-body'>
<h5 class = 'card-title'><?php echo htmlspecialchars( $election[ 'name' ] );
?></h5>
<p class = 'card-text'>Participate and make your vote count!</p>
<!-- ( Optional ) Insert a note about panel details if desired -->
<a href = "group_vote.php?election_id=<?php echo urlencode($election['id']); ?>" class = 'btn btn-primary w-100'>
Vote Now
</a>
</div>
</div>
</div>
<?php endforeach;
?>
<?php else: ?>
<div class = 'col-12 text-center'>
<p class = 'alert alert-warning'>No ongoing elections available.</p>
</div>
<?php endif;
?>
</div>
</section>

<!-- Expired Elections Section -->
<section class = 'mt-5'>
<h2 class = 'text-danger mb-3 text-center'>Expired Elections</h2>
<div id = 'expiredElections' class = 'row'>
<?php if ( !empty( $expiredElections ) ): ?>
<?php $renderedElections = [];
?>
<?php foreach ( $expiredElections as $election ): ?>
<?php if ( in_array( $election[ 'id' ], $renderedElections ) ) continue;
?>
<?php $renderedElections[] = $election[ 'id' ];
?>
<div class = 'col-md-4 mb-4'>
<div class = 'card shadow-sm border-danger'>
<div class = 'card-header bg-danger text-white'>
<strong><?php echo htmlspecialchars( $election[ 'election_type' ] );
?></strong>
</div>
<div class = 'card-body'>
<h5 class = 'card-title'><?php echo htmlspecialchars( $election[ 'name' ] );
?></h5>
<p class = 'card-text'>
<small>Ended on: <?php echo htmlspecialchars( $election[ 'end_date' ] );
?></small>
</p>
<!-- Winner Section -->
<div class = 'winner-details text-center mt-3'>
<h6 class = 'text-success'><strong>Winner:</strong>
<?php echo htmlspecialchars( $election[ 'winner_name' ] );
?>
</h6>
<img src = "<?php echo htmlspecialchars($election['winner_image']); ?>" alt = 'Winner Image' class = 'rounded-circle'
style = 'width: 100px; height: 100px; object-fit: cover;'>
<p class = 'mt-2'>Votes: <strong><?php echo htmlspecialchars( $election[ 'winner_votes' ] );
?></strong></p>
</div>
</div>
</div>
</div>
<?php endforeach;
?>
<?php else: ?>
<div class = 'col-12 text-center'>
<p class = 'alert alert-secondary'>No expired elections found.</p>
</div>
<?php endif;
?>
</div>
</section>
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