<?php
// Include database configuration
include 'db_config.php';

// Fetch all the elections
$ongoingElections = [];
$expiredElections = [];
$currentDate = date( 'Y-m-d' );

// Store vote counts for ongoing elections
$electionVoteCounts = [];

try {
    // Fetch elections for voters
    $stmt = $pdo->query( 'SELECT id, election_type, name, start_date, end_date FROM elections ORDER BY start_date ASC' );

    while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
        // Categorize elections
        if ( $row[ 'start_date' ] <= $currentDate && $row[ 'end_date' ] >= $currentDate ) {
            $ongoingElections[] = $row;
        } elseif ( $row[ 'end_date' ] < $currentDate ) {
            $expiredElections[] = $row;
        }
    }
} catch ( PDOException $e ) {
    die( 'Error fetching elections: ' . $e->getMessage() );
}
?>

<!doctype html>
<html lang = 'en'>

<head>
<title>All Elections - MeroVote</title>
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
<img src = '../img/MeroVote-Logo.png' style = 'height: 60px; width: auto;' alt = 'MeroVote Logo'

class = 'logo img-fluid me-2'>
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
<a class = 'nav-link active' aria-current = 'page' href = '../index.html'>Home</a>
</li>
<li class = 'nav-item'>
<a class = 'nav-link' href = '../index.html#how'>How It Works</a>
</li>
<li class = 'nav-item'>
<a class = 'nav-link' href = 'all-elections.php'>Elections</a>
</li>
<li class = 'nav-item'>
<a class = 'nav-link' href = 'voter_login.php'> Voter Login</a>
</li>
<li class="nav-item">
                <a class="nav-link" href="admin_login.php">Admin Login</a>
              </li>
<li class = 'nav-item'>
<a class = 'nav-link' href = 'register.php'>Register</a>
</li>
</ul>
</div>
</div>
</nav>
</header>

<main class = 'container mt-4'>
<h1 class = 'text-center text-primary mb-4'>Explore All Elections</h1>

<!-- Instructions for voters -->
<div class = 'alert alert-info text-center'>
To vote, please <a href = 'register.php' class = 'text-decoration-underline'>Register</a> first.
<strong>Already registered?</strong> <a href = 'voter_login.php' class = 'text-decoration-underline'>Log
in</a> to cast your vote.
</div>

<!-- Ongoing Elections -->
<h2 class = 'text-success'>Ongoing Elections</h2>
<div id = 'ongoingElections' class = 'row'>
<?php if ( !empty( $ongoingElections ) ): ?>
<?php foreach ( $ongoingElections as $election ): ?>
<div class = 'col-md-4 mb-4'>
<div class = 'card shadow-sm border-success'>
<div class = 'card-header bg-success text-white d-flex justify-content-between'>
<!-- <span><?php echo htmlspecialchars( $election[ 'election_type' ] );
?></span> -->
<span

class = 'badge bg-light text-success'><?php echo htmlspecialchars( $election[ 'election_type' ] );
?></span>
</div>
<div class = 'card-body'>
<h5 class = 'card-title'><?php echo htmlspecialchars( $election[ 'name' ] );
?></h5>
<p class = 'card-text'>Make your voice heard. Register to participate in this election.</p>

<a href = 'register.php' class = 'btn btn-primary btn-sm'>Register to Vote</a>
</div>
</div>
</div>
<?php endforeach;
?>
<?php else: ?>
<p class = 'alert alert-warning text-center'>No ongoing elections available at the moment.</p>
<?php endif;
?>
</div>

<!-- Expired Elections -->
<h2 class = 'text-danger mt-5'>Expired Elections</h2>
<div id = 'expiredElections' class = 'row'>
<?php if ( !empty( $expiredElections ) ): ?>
<?php foreach ( $expiredElections as $election ): ?>
<div class = 'col-md-4 mb-4'>
<div class = 'card shadow-sm border-danger'>
<div class = 'card-header bg-danger text-white'>
<?php echo htmlspecialchars( $election[ 'election_type' ] );
?>
</div>
<div class = 'card-body'>
<h5 class = 'card-title'><?php echo htmlspecialchars( $election[ 'name' ] );
?></h5>
<p class = 'card-text'>
<small>Ended on: <?php echo htmlspecialchars( $election[ 'end_date' ] );
?></small>
</p>
</div>
</div>
</div>
<?php endforeach;
?>
<?php else: ?>
<p class = 'alert alert-secondary text-center'>No expired elections found.</p>
<?php endif;
?>
</div>
</main>

<footer class = 'bg-dark text-white text-center py-3'>
<p>&copy;
2024 MeroVote - All rights reserved.</p>
</footer>

<script src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js'></script>
</body>

</html>