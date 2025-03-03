<?php
session_start();
include 'db_config.php';

// Ensure the user is logged in
if ( !isset( $_SESSION[ 'user_id' ] ) ) {
    die( 'Error: User session not set. Verify login flow in admin_dashboard.php.' );
}

// Check if election ID is provided in the URL
if ( !isset( $_GET[ 'id' ] ) || empty( $_GET[ 'id' ] ) ) {
    $_SESSION[ 'message' ] = 'Invalid election ID.';
    $_SESSION[ 'msg_type' ] = 'danger';
    // Redirect to the appropriate dashboard page ( adjust if needed )
    header( 'Location: elections.php' );
    exit();
}

$election_id = intval( $_GET[ 'id' ] );
// Determine election type: default to individual if not specified.
$type = isset( $_GET[ 'type' ] ) && $_GET[ 'type' ] === 'group' ? 'group' : 'individual';

// Fetch the election details from the correct table
if ( $type === 'group' ) {
    $stmt = $pdo->prepare( 'SELECT * FROM elections_group WHERE id = ?' );
    $stmt->execute( [ $election_id ] );
    $election = $stmt->fetch( PDO::FETCH_ASSOC );
    if ( !$election ) {
        $_SESSION[ 'message' ] = 'Group election not found.';
        $_SESSION[ 'msg_type' ] = 'danger';
        header( 'Location: group-elections.php' );
        exit();
    }
} else {
    $stmt = $pdo->prepare( 'SELECT * FROM elections WHERE id = ?' );
    $stmt->execute( [ $election_id ] );
    $election = $stmt->fetch( PDO::FETCH_ASSOC );
    if ( !$election ) {
        $_SESSION[ 'message' ] = 'Election not found.';
        $_SESSION[ 'msg_type' ] = 'danger';
        header( 'Location: elections.php' );
        exit();
    }
}

// Handle election update
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' && isset( $_POST[ 'update_election' ] ) ) {
    $election_type = $_POST[ 'election_type' ];
    $election_name = htmlspecialchars( $_POST[ 'election_name' ] );
    $start_date = $_POST[ 'start_date' ];
    $end_date = $_POST[ 'end_date' ];
    $start_time = $_POST[ 'start_time' ];
    $end_time = $_POST[ 'end_time' ];

    // Ensure start time is before end time
    if ( strtotime( $start_time ) >= strtotime( $end_time ) ) {
        $_SESSION[ 'message' ] = 'Start time must be before end time.';
        $_SESSION[ 'msg_type' ] = 'danger';
        header( "Location: edit_election.php?id=$election_id&type=$type" );
        exit();
    }

    try {
        if ( $type === 'group' ) {
            // For group elections, include the panel positions.
            $panel1_pos1 = $_POST[ 'panel1_pos1' ];
            $panel1_pos2 = $_POST[ 'panel1_pos2' ];
            $panel1_pos3 = $_POST[ 'panel1_pos3' ];
            $panel1_pos4 = $_POST[ 'panel1_pos4' ];
            $panel2_pos1 = $_POST[ 'panel2_pos1' ];
            $panel2_pos2 = $_POST[ 'panel2_pos2' ];
            $panel2_pos3 = $_POST[ 'panel2_pos3' ];
            $panel2_pos4 = $_POST[ 'panel2_pos4' ];

            $stmt = $pdo->prepare( "UPDATE elections_group SET 
                election_type = ?, 
                name = ?, 
                start_date = ?, 
                end_date = ?, 
                start_time = ?, 
                end_time = ?, 
                panel1_pos1 = ?, 
                panel1_pos2 = ?, 
                panel1_pos3 = ?, 
                panel1_pos4 = ?, 
                panel2_pos1 = ?, 
                panel2_pos2 = ?, 
                panel2_pos3 = ?, 
                panel2_pos4 = ? 
                WHERE id = ?" );
            $stmt->execute( [
                $election_type,
                $election_name,
                $start_date,
                $end_date,
                $start_time,
                $end_time,
                $panel1_pos1,
                $panel1_pos2,
                $panel1_pos3,
                $panel1_pos4,
                $panel2_pos1,
                $panel2_pos2,
                $panel2_pos3,
                $panel2_pos4,
                $election_id
            ] );
        } else {
            // For individual elections, update election_position.
            $election_position = $_POST[ 'election_position' ];
            $stmt = $pdo->prepare( "UPDATE elections SET 
                election_type = ?, 
                name = ?, 
                start_date = ?, 
                end_date = ?, 
                start_time = ?, 
                end_time = ?, 
                election_position = ? 
                WHERE id = ?" );
            $stmt->execute( [
                $election_type,
                $election_name,
                $start_date,
                $end_date,
                $start_time,
                $end_time,
                $election_position,
                $election_id
            ] );
        }
        $_SESSION[ 'message' ] = 'Election updated successfully!';
        $_SESSION[ 'msg_type' ] = 'success';
    } catch( PDOException $e ) {
        $_SESSION[ 'message' ] = 'Database error: ' . $e->getMessage();
        $_SESSION[ 'msg_type' ] = 'danger';
    }

    header( 'Location: ' . ( $type === 'group' ? 'group-elections.php' : 'elections.php' ) );
    exit();
}
?>

<!DOCTYPE html>
<html lang = 'en'>
<head>
<meta charset = 'UTF-8'>
<meta name = 'viewport' content = 'width=device-width, initial-scale=1.0'>
<title>Edit Election</title>
<link href = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel = 'stylesheet'>
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
<div class = 'container mt-5'>
<h2 class = 'text-center text-primary'>Edit Election</h2>
<form method = 'post'>
<div class = 'mb-3'>
<label class = 'form-label'>Election Type</label>
<input type = 'text' class = 'form-control' name = 'election_type' value = "<?php echo htmlspecialchars($election['election_type']); ?>" required>
</div>
<div class = 'mb-3'>
<label class = 'form-label'>Election Name</label>
<input type = 'text' class = 'form-control' name = 'election_name' value = "<?php echo htmlspecialchars($election['name']); ?>" required>
</div>
<?php if ( stripos( $election[ 'election_type' ], 'group' ) !== false ): ?>
<!-- Group Election: display panel positions -->
<?php for ( $panel = 1; $panel <= 2; $panel++ ): ?>
<?php for ( $position = 1; $position <= 4; $position++ ): ?>
<div class = 'mb-3'>
<label class = 'form-label'>Panel <?php echo $panel;
?> Position <?php echo $position;
?></label>
<input type = 'text' class = 'form-control' name = 'panel<?php echo $panel; ?>_pos<?php echo $position; ?>' value = "<?php echo htmlspecialchars($election['panel' . $panel . '_pos' . $position]); ?>" required>
</div>
<?php endfor;
?>
<?php endfor;
?>
<?php else: ?>
<!-- Individual Election: display election_position -->
<div class = 'mb-3'>
<label for = 'electionPosition' class = 'form-label'>Election Position</label>
<input type = 'text' class = 'form-control' name = 'election_position' value = "<?php echo htmlspecialchars($election['election_position']); ?>" required>
</div>
<?php endif;
?>
<div class = 'mb-3'>
<label for = 'startDate' class = 'form-label'>Start Date</label>
<input type = 'date' class = 'form-control' name = 'start_date' value = "<?php echo htmlspecialchars($election['start_date']); ?>" required>
</div>
<div class = 'mb-3'>
<label for = 'endDate' class = 'form-label'>End Date</label>
<input type = 'date' class = 'form-control' name = 'end_date' value = "<?php echo htmlspecialchars($election['end_date']); ?>" required>
</div>
<div class = 'mb-3'>
<label for = 'startTime' class = 'form-label'>Start Time</label>
<input type = 'time' class = 'form-control' name = 'start_time' value = "<?php echo htmlspecialchars($election['start_time']); ?>" required>
</div>
<div class = 'mb-3'>
<label for = 'endTime' class = 'form-label'>End Time</label>
<input type = 'time' class = 'form-control' name = 'end_time' value = "<?php echo htmlspecialchars($election['end_time']); ?>" required>
</div>
<div class = 'text-center'>
<button type = 'submit' name = 'update_election' class = 'btn btn-success'>Update Election</button>
<a href = "<?php echo (stripos($election['election_type'], 'group') !== false) ? 'group-elections.php' : 'elections.php'; ?>" class = 'btn btn-secondary'>Cancel</a>
</div>
</form>
</div>

<script src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>
