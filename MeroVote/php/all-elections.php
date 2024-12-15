<?php

// Include database configuration
include 'db_config.php';

// Fetch all the elections
$ongoingElections = [];
$expiredElections = [];
$currentDate = date('Y-m-d');

// Store vote counts for ongoing elections
$electionVoteCounts = [];

try {
    // Fetch ongoing and expired elections for voters
    $stmt = $pdo->prepare("SELECT id, election_type, name, start_date, end_date FROM elections WHERE election_type = :election_type ORDER BY start_date ASC");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Categorize elections as ongoing or expired
        if ($row['start_date'] <= $currentDate && $row['end_date'] >= $currentDate) {
            $ongoingElections[] = $row;

            // Fetch vote counts for the candidates in the ongoing election
            $voteStmt = $pdo->prepare("SELECT candidate_id, COUNT(*) as vote_count FROM votes WHERE election_id = :election_id GROUP BY candidate_id");
            $voteStmt->execute(['election_id' => $row['name']]);

            // Store vote counts for the current election
            $electionVoteCounts[$row['name']] = $voteStmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($row['end_date'] < $currentDate) {
            $expiredElections[] = $row;
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
    <link rel="stylesheet" href="css/styles.css" />
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">MeroVote - Online Voting Portal</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="voter_login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="all-elections.php">Elections</a>
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

                                    <!-- Display live vote counts -->
                                    <h6 class="text-primary">Live Vote Count:</h6>
                                    <ul class="list-group mb-3">
                                        <?php if (isset($electionVoteCounts[$election['name']]) && !empty($electionVoteCounts[$election['name']])): ?>
                                            <?php foreach ($electionVoteCounts[$election['name']] as $vote): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <?php echo htmlspecialchars($vote['candidate_id']); ?>
                                                    <span class="badge bg-primary rounded-pill">
                                                        <?php echo htmlspecialchars($vote['vote_count']); ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="list-group-item">No votes yet.</li>
                                        <?php endif; ?>
                                    </ul>

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
                    <?php foreach ($expiredElections as $election): ?>
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