<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ./voter_login.php');
    exit();
}

include 'db_config.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Role-based filtering of election types
$allowedElectionType = '';
if ($user_role == "School/College Level Election") {
    $allowedElectionType = 'School/College Level Election';
} elseif ($user_role == "Local Level Election") {
    $allowedElectionType = 'Local Level Election';
} elseif ($user_role == "Organizational Level Election") {
    $allowedElectionType = 'Organizational Level Election';
}

if (empty($allowedElectionType)) {
    die("Invalid user role.");
}

$ongoingElections = [];
$expiredElections = [];
$currentDate = date('Y-m-d');

try {
    // Fetch all elections based on election type
    $stmt = $pdo->prepare("
        SELECT id, election_type, name, start_date, end_date
        FROM elections
        WHERE election_type = :election_type
        ORDER BY start_date ASC
    ");
    $stmt->execute(['election_type' => $allowedElectionType]);

    $processedExpiredElections = []; // Array to track processed election IDs

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Separate ongoing and expired elections
        if ($row['start_date'] <= $currentDate && $row['end_date'] >= $currentDate) {
            $ongoingElections[] = $row; // Ongoing elections
        } elseif ($row['end_date'] < $currentDate) {
            // Check if this expired election has already been processed
            if (!in_array($row['id'], $processedExpiredElections)) {
                $expiredElections[] = $row; // Add to expired elections
                $processedExpiredElections[] = $row['id']; // Track processed election ID
            }
        }
    }

   // Process winner details for expired elections
foreach ($expiredElections as &$election) {
    $election['winner_name'] = "No Winner";
    $election['winner_image'] = "default.jpg";
    $election['winner_votes'] = 0;

    $electionName = $election['name']; // Fetch using election name

    // Fetch winner based on votes count for each expired election
    $winnerStmt = $pdo->prepare("
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
    ");
    $winnerStmt->execute(['election_name' => $electionName]);
    $winner = $winnerStmt->fetch(PDO::FETCH_ASSOC);

    // Update winner details if votes exist
    if ($winner && $winner['total_votes'] > 0) {
        $election['winner_name'] = $winner['candidate_name'];
        $election['winner_image'] = $winner['candidate_image'];
        $election['winner_votes'] = $winner['total_votes'];
    }
}

} catch (PDOException $e) {
    die("Error fetching elections: " . $e->getMessage());
}
?>



<!doctype html>
<html lang="en">

<head>
    <title>Dashboard - MeroVote</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../css/styles.css" />
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <!-- Brand -->
                <a class="navbar-brand" href="voter_dashboard.php">MeroVote - Online
                    Voting Portal</a>

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
                            <a class="nav-link" href="voter_login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../index.html#how">How It Works</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="voter_dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../index.html">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="container mt-4">
        <h1 class="text-center text-primary mb-4">
            Welcome, Voter!
        </h1>

        <!-- Voter Panel -->
        <div id="userPanel" class="mt-5">
            <!-- Ongoing Elections -->
            <h2 class="text-success mb-3">Ongoing Elections</h2>
            <div id="ongoingElections" class="row">
                <?php if (!empty($ongoingElections)): ?>
                    <?php foreach ($ongoingElections as $election): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card shadow-sm border-success">
                                <div class="card-header bg-success text-white">
                                    <strong><?php echo htmlspecialchars($election['election_type']); ?></strong>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($election['name']); ?></h5>
                                    <p class="card-text">Participate in this election and make your vote count!</p>

                                    <a href="vote.php?election_id=<?php echo urlencode($election['name']); ?>"
                                        class="btn btn-primary w-100">Vote Now</a>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="alert alert-warning">No ongoing elections available.</p>
                    </div>
                <?php endif; ?>
            </div>

          <!-- Expired Elections -->
<h2 class="text-danger mt-5 mb-3">Expired Elections</h2>
<div id="expiredElections" class="row">
    <?php if (!empty($expiredElections)): ?>
        <?php 
        // Initialize a tracking array to ensure no duplicate elections are displayed
        $renderedElections = [];
        ?>
        <?php foreach ($expiredElections as $election): ?>
            <?php 
            // Check if this election has already been rendered
            if (in_array($election['id'], $renderedElections)) {
                continue; // Skip duplicate elections
            }
            $renderedElections[] = $election['id']; // Mark election as rendered
            ?>
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-danger">
                    <div class="card-header bg-danger text-white">
                        <strong><?php echo htmlspecialchars($election['election_type']); ?></strong>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($election['name']); ?></h5>
                        <p class="card-text">
                            <small>Ended on: <?php echo htmlspecialchars($election['end_date']); ?></small>
                        </p>

                        <!-- Winner Details -->
                        <div class="winner-details text-center mt-3">
                            <h6 class="text-success"><strong>Winner:</strong>
                                <?php echo htmlspecialchars($election['winner_name']); ?></h6>
                            <img src="./candidates_photos/<?php echo htmlspecialchars($election['winner_image']); ?>"
                                alt="Winner Image" class="rounded-circle"
                                style="width: 100px; height: 100px; object-fit: cover; border: 2px solid #28a745;">
                            <p class="mt-2">Votes:
                                <strong><?php echo htmlspecialchars($election['winner_votes']); ?></strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center">
            <p class="alert alert-secondary">No expired elections found.</p>
        </div>
    <?php endif; ?>
</div>

        </div>
    </main>


    <footer class="bg-dark text-white text-center py-3">
        <div class="container">
            <p>&copy; 2024 Online Voting System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
    <script>
        // Redirect to create election page
        function createElection() {
            window.location.href = "elections.php";
        }
    </script>
</body>

</html>