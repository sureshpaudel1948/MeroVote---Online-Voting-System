<?php
session_start();
if ( !isset( $_SESSION[ 'user_id' ] ) ) {
    header( 'Location: ./otp-api.php' );
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
        SELECT id, election_type, name, start_date, end_date, start_time, end_time, panel1_pos1, panel1_pos2, panel1_pos3, panel1_pos4, 
               panel2_pos1, panel2_pos2, panel2_pos3, panel2_pos4, 
               panel1_pos5, panel1_pos6, panel1_pos7, panel1_pos8, 
               panel2_pos5, panel2_pos6, panel2_pos7, panel2_pos8, 
               panel3_pos1, panel3_pos2, panel3_pos3, panel3_pos4, panel3_pos5, panel3_pos6, panel3_pos7, panel3_pos8, 
               panel4_pos1, panel4_pos2, panel4_pos3, panel4_pos4, panel4_pos5, panel4_pos6, panel4_pos7, panel4_pos8
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
            'panel1_pos5' => $row[ 'panel1_pos5' ],
            'panel1_pos6' => $row[ 'panel1_pos6' ],
            'panel1_pos7' => $row[ 'panel1_pos7' ],
            'panel1_pos8' => $row[ 'panel1_pos8' ],
            // Panel 2 positions
            'panel2_pos1' => $row[ 'panel2_pos1' ],
            'panel2_pos2' => $row[ 'panel2_pos2' ],
            'panel2_pos3' => $row[ 'panel2_pos3' ],
            'panel2_pos4' => $row[ 'panel2_pos4' ],
            'panel2_pos5' => $row[ 'panel2_pos5' ],
            'panel2_pos6' => $row[ 'panel2_pos6' ],
            'panel2_pos7' => $row[ 'panel2_pos7' ],
            'panel2_pos8' => $row[ 'panel2_pos8' ],
            // Panel 3 positions
            'panel3_pos1' => $row[ 'panel3_pos1' ],
            'panel3_pos2' => $row[ 'panel3_pos2' ],
            'panel3_pos3' => $row[ 'panel3_pos3' ],
            'panel3_pos4' => $row[ 'panel3_pos4' ],
            'panel3_pos5' => $row[ 'panel3_pos5' ],
            'panel3_pos6' => $row[ 'panel3_pos6' ],
            'panel3_pos7' => $row[ 'panel3_pos7' ],
            'panel3_pos8' => $row[ 'panel3_pos8' ],
            // Panel 4 positions
            'panel4_pos1' => $row[ 'panel4_pos1' ],
            'panel4_pos2' => $row[ 'panel4_pos2' ],
            'panel4_pos3' => $row[ 'panel4_pos3' ],
            'panel4_pos4' => $row[ 'panel4_pos4' ],
            'panel4_pos5' => $row[ 'panel4_pos5' ],
            'panel4_pos6' => $row[ 'panel4_pos6' ],
            'panel4_pos7' => $row[ 'panel4_pos7' ],
            'panel4_pos8' => $row[ 'panel4_pos8' ],
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

 // Process winner details for expired elections (Group Elections)
foreach ($expiredElections as &$election) {
  $electionName = $election['name'];

  // Query to get vote totals for each candidate by their position for this election
  $winnerStmt = $pdo->prepare("
      SELECT 
          c.candidate_position,
          c.name AS candidate_name, 
          c.photo AS candidate_image,
          c.panel AS candidate_panel,
          COUNT(v.id) AS total_votes
      FROM candidates_group c
      LEFT JOIN votes_group v 
          ON v.candidate_id = c.id AND v.election = :election_name
      WHERE c.election_name = :election_name
      GROUP BY c.candidate_position, c.id, c.name, c.photo, c.panel
      ORDER BY c.candidate_position, total_votes DESC
  ");
  $winnerStmt->execute(['election_name' => $electionName]);
  $winnersData = $winnerStmt->fetchAll(PDO::FETCH_ASSOC);

  // Build an array of winners: one per candidate_position (the one with the highest votes)
  $winners = [];
  foreach ($winnersData as $row) {
      $position = $row['candidate_position'];
      // Use fallback of 0 for total_votes if not set
      $votes = isset($row['total_votes']) ? $row['total_votes'] : 0;
      // If not set or this candidate has higher votes, store this candidate as winner for that position.
      if (!isset($winners[$position]) || $votes > $winners[$position]['winner_votes']) {
          $winners[$position] = [
              'winner_name'  => $row['candidate_name'],
              'winner_image' => !empty($row['candidate_image']) ? $row['candidate_image'] : './candidates_photos/default.jpg',
              'winner_votes' => $votes,
              'winner_panel' => $row['candidate_panel']
          ];
      }
  }
  // Save winners array in the election data
  $election['winners'] = $winners;
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
<section class='mt-5'>
  <h2 class='text-danger mb-3 text-center'>Expired Elections</h2>
  <!-- Inline CSS for dynamic hover effect and improved styling -->
  <style>
    .winner-position {
      transition: transform 0.3s, background-color 0.3s, box-shadow 0.3s;
      padding: 45px;
      border-radius: 8px;
      margin: 40px;
      background-color: #ffffff;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      border: 1px solid #ddd;
    }
    .winner-position:hover {
      background-color: #e9f5ff;
      transform: translateY(-5px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    .winner-title {
      font-size: 1rem;
      font-weight: bold;
      margin-bottom: 8px;
    }
    .winner-text {
      font-size: 0.9rem;
      margin: 0;
    }
    .winner-text span.fw-bold {
      color: #007bff;
    }
  </style>
  <div id='expiredElections' class='row'>
    <?php if (!empty($expiredElections)): ?>
      <?php $renderedElections = []; ?>
      <?php $currentDate = date('Y-m-d'); // Current date for comparison ?>
      <?php foreach ($expiredElections as $election): ?>
        <?php 
          // Skip if already rendered and only show elections that have truly ended
          if (in_array($election['id'], $renderedElections)) continue;
          if ($election['end_date'] >= $currentDate) continue;
          $renderedElections[] = $election['id'];
        ?>
        <div class='col-12 mb-4'>
          <div class='card shadow-sm border-danger'>
            <div class='card-header bg-danger text-white'>
              <strong><?php echo htmlspecialchars($election['election_type']); ?></strong>
            </div>
            <div class='card-body'>
              <h5 class='card-title'><?php echo htmlspecialchars($election['name']); ?></h5>
              <p class='card-text'>
                <small>Ended on: <?php echo htmlspecialchars($election['end_date']); ?></small>
              </p>
              <!-- Winner Section: display winners in a horizontal row -->
              <div class='winner-details d-flex flex-wrap justify-content-center mt-3'>
                <?php if (isset($election['winners']) && !empty($election['winners'])): ?>
                  <?php foreach ($election['winners'] as $position => $winner): ?>
                    <div class="winner-position text-center">
                      <h6 class="winner-title text-success"><?php echo htmlspecialchars($position); ?></h6>
                      <img src="<?php echo htmlspecialchars($winner['winner_image'] ?? './candidates_photos/default.jpg'); ?>" 
                           alt="Winner Image" class="rounded-circle" 
                           style="width: 100px; height: 100px; object-fit: cover;">
                      <p class="winner-text mt-2">
                        <strong><?php echo htmlspecialchars($winner['winner_name'] ?? 'No Winner'); ?></strong>
                      </p>
                      <p class="winner-text">
                        <span class="fw-bold">Votes:</span> <?php echo htmlspecialchars($winner['winner_votes'] ?? 0); ?>
                      </p>
                      <p class="winner-text">
                        <span class="fw-bold">Panel:</span> <?php echo htmlspecialchars($winner['winner_panel'] ?? 'N/A'); ?>
                      </p>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p>No winner details available.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class='col-12 text-center'>
        <p class='alert alert-secondary'>No expired elections found.</p>
      </div>
    <?php endif; ?>
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