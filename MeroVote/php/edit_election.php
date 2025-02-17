<?php
// Start session and include database connection
session_start();
require_once( 'db_config.php' );

// Check if election ID is provided
if ( !isset( $_GET[ 'id' ] ) ) {
    $_SESSION[ 'message' ] = 'Election not found.';
    $_SESSION[ 'msg_type' ] = 'danger';
    header( 'Location: elections.php' );
    exit;
}

$election_id = $_GET[ 'id' ];

// Fetch election details
$query = $pdo->prepare( 'SELECT * FROM elections WHERE id = :id' );
$query->execute( [ 'id' => $election_id ] );
$election = $query->fetch( PDO::FETCH_ASSOC );

if ( !$election ) {
    $_SESSION[ 'message' ] = 'Election not found.';
    $_SESSION[ 'msg_type' ] = 'danger';
    header( 'Location: elections.php' );
    exit;
}

// Handle form submission
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $election_type = $_POST[ 'election_type' ];
    $election_name = $_POST[ 'election_name' ];
    $start_date = $_POST[ 'start_date' ];
    $end_date = $_POST[ 'end_date' ];

    try {
        $pdo->beginTransaction();

        // Step 1: Update candidates' election_name if referenced directly
        $updateCandidates = $pdo->prepare("
            UPDATE candidates 
            SET election_name = :new_name 
            WHERE election_name = (SELECT name FROM elections WHERE id = :id)
        ");
        $updateCandidates->execute([
            'new_name' => $election_name,
            'id' => $election_id
        ]);

        // Step 2: Update election record
        $updateElection = $pdo->prepare("
            UPDATE elections 
            SET election_type = :type, 
                name = :name, 
                start_date = :start, 
                end_date = :end 
            WHERE id = :id
        ");
        $updateElection->execute([
            'type' => $election_type,
            'name' => $election_name,
            'start' => $start_date,
            'end' => $end_date,
            'id' => $election_id
        ]);

        $pdo->commit();
        $_SESSION['message'] = 'Election updated successfully!';
        $_SESSION['msg_type'] = 'success';

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['message'] = 'Update failed: ' . $e->getMessage();
        $_SESSION['msg_type'] = 'danger';
    }

    header('Location: elections.php');
    exit;
}
?>


<!doctype html>
<html lang = 'en'>

<head>
<title>Edit Election - MeroVote</title>
<meta charset = 'utf-8'>
<meta name = 'viewport' content = 'width = device-width, initial-scale = 1, shrink-to-fit = no'>
<link href = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel = 'stylesheet'>
<link rel = 'stylesheet' href = '../css/styles.css'>
</head>

<body>
<header>
<nav class = 'navbar navbar-expand-lg navbar-dark bg-dark'>
<div class = 'container-fluid'>
<a class = 'navbar-brand d-flex align-items-center' href = 'elections.php'>
<img src = '../img/MeroVote-Logo.png' style = 'height: 45px;
        ' alt = 'MeroVote Logo' class = 'me-2'>
<span>MeroVote - Online Voting Portal</span>
</a>
<button class = 'navbar-toggler' type = 'button' data-bs-toggle = 'collapse' data-bs-target = '#navbarNav'>
<span class = 'navbar-toggler-icon'></span>
</button>
<div class = 'collapse navbar-collapse' id = 'navbarNav'>
<ul class = 'navbar-nav ms-auto'>
<li class = 'nav-item'><a class = 'nav-link' href = 'admin_dashboard.php'>Dashboard</a></li>
<li class = 'nav-item'><a class = 'nav-link' href = '../index.html'>Logout</a></li>
</ul>
</div>
</div>
</nav>
</header>

<main class = 'container mt-4'>
<h1>Edit Election</h1>

<form action = "edit_election.php?id=<?= htmlspecialchars($election_id) ?>" method = 'POST'>
<div class = 'mb-3'>
<label for = 'electionType' class = 'form-label'>Election Type</label>
<select name = 'election_type' id = 'electionType' class = 'form-select' required>
<option value = 'Organizational Level Election' < ?= $election[ 'election_type' ] === 'Organizational Level Election' ? 'selected' : '' ?>>Organizational Level Election</option>
<option value = 'Local Level Election' < ?= $election[ 'election_type' ] === 'Local Level Election' ? 'selected' : '' ?>>Local Level Election</option>
<option value = 'School/College Level Election' < ?= $election[ 'election_type' ] === 'School/College Level Election' ? 'selected' : '' ?>>School/College Level Election</option>
</select>
</div>
<div class = 'mb-3'>
<label for = 'electionName' class = 'form-label'>Election Name</label>
<input type = 'text' name = 'election_name' id = 'electionName' class = 'form-control' value = "<?= htmlspecialchars($election['name']) ?>" required>
</div>
<div class = 'mb-3'>
<label for = 'startDate' class = 'form-label'>Start Date</label>
<input type = 'date' name = 'start_date' id = 'startDate' class = 'form-control' value = "<?= htmlspecialchars($election['start_date']) ?>" required>
</div>
<div class = 'mb-3'>
<label for = 'endDate' class = 'form-label'>End Date</label>
<input type = 'date' name = 'end_date' id = 'endDate' class = 'form-control' value = "<?= htmlspecialchars($election['end_date']) ?>" required>
</div>
<button type = 'submit' class = 'btn btn-primary'>Update Election</button>
<a href = 'elections.php' class = 'btn btn-secondary'>Cancel</a>
</form>
</main>

<style>
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

<footer class = 'bg-dark text-white text-center py-3 mt-4'>
<div class = 'container'>
<p>&copy;
2024 Online Voting System. All rights reserved.</p>
</div>
</footer>

<script src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js'></script>
        </body>

        </html>
