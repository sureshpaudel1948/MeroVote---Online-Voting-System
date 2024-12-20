<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ./voter_login.php'); // Redirect to login if not logged in
    exit();
}

// Include database configuration
include 'db_config.php';

// Get election ID from query parameter
$electionId = $_GET['election_id'] ?? null;

if (!$electionId) {
    die("Election ID not specified.");
}

// Fetch candidates for the election
try {
    $stmt = $pdo->prepare("SELECT id, name, photo FROM candidates WHERE election_id = :election_id");
    $stmt->execute(['election_id' => $electionId]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($candidates)) {
        die("No candidates found for this election.");
    }
} catch (PDOException $e) {
    die("Error fetching candidates: " . $e->getMessage());
}

// Fetch live vote counts for the election
$voteCounts = [];
try {
    $voteStmt = $pdo->prepare("
        SELECT v.candidate_id AS candidate_name, COUNT(v.id) AS vote_count
        FROM votes v
        INNER JOIN candidates c ON v.candidate_id = c.name
        WHERE v.election_id = :election_id
        GROUP BY v.candidate_id
    ");
    $voteStmt->execute(['election_id' => $electionId]);
    $voteCounts = $voteStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching vote counts: " . $e->getMessage());
}


// Handle voting logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidateId = $_POST['candidate'] ?? null;

    if ($candidateId) {
        try {
            // Check if the user has already voted in this election
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE user_id = :user_id AND election_id = :election_id");
            $stmt->execute([
                'user_id' => $_SESSION['user_id'],
                'election_id' => $electionId
            ]);
            $voteCount = $stmt->fetchColumn();

            if ($voteCount > 0) {
                $modalType = "warning";
                $modalMessage = "You have already voted in this election.";
            } else {
                // Insert the vote
                $stmt = $pdo->prepare("INSERT INTO votes (user_id, candidate_id, election_id) VALUES (:user_id, :candidate_id, :election_id)");
                $stmt->execute([
                    'user_id' => $_SESSION['user_id'],
                    'candidate_id' => $candidateId,
                    'election_id' => $electionId
                ]);
                $modalType = "success";
                $modalMessage = "Vote successfully submitted!";
            }
        } catch (PDOException $e) {
            $modalType = "danger";
            $modalMessage = "Error submitting vote: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $modalType = "warning";
        $modalMessage = "Please select a candidate.";
    }
}
?>



<!doctype html>
<html lang="en">

<head>
    <title>Vote Now - MeroVote</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../css/styles.css" />
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border-color: #007bff;
        }

        /* Selected state */
        .candidate-card input:checked+label {
            background: linear-gradient(to bottom right, #00bbff, #007bff, #0047ab, #1e3a8a);
            /* Blue gradient */
            color: #FFFFFF;
            /* Text color */
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.4);
            transition: all 0.3s ease;
            /* Smooth transition */
        }

        /* Candidate photo style */
        .candidate-photo {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #ddd;
            transition: border-color 0.2s, box-shadow 0.3s;
        }

        /* Highlight photo border on selection */
        .candidate-card input:checked+label .candidate-photo {
            border-color: #007bff;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.5);
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
        button[type="submit"] {
            font-size: 1.1rem;
            font-weight: bold;
            padding: 10px 20px;
            background: linear-gradient(to right, #28a745, #218838);
            /* Green gradient for submit button */
            border: none;
            color: #fff;
            border-radius: 5px;
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }

        /* Button hover effect */
        button[type="submit"]:hover {
            background: linear-gradient(to right, #218838, #1e7e34);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
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
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 2px 6px rgba(0, 255, 0, 0.4);
        }

        /* Add hover effect for list items */
        .live-vote-count .list-group-item:hover {
            background-color: #eef7ff;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 123, 0.2);
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
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <!-- Brand -->
                <a class="navbar-brand" href="vote.php">MeroVote - Online
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
    <div class="container mt-5">
        <h1 class="text-center text-primary mb-4">Vote for Your Candidate</h1>
        <form method="post">
            <div class="row justify-content-center">
                <?php foreach ($candidates as $candidate): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm candidate-card">
                            <div class="card-body text-center">
                                <input class="form-check-input d-none" type="radio" name="candidate"
                                    id="candidate<?php echo $candidate['id']; ?>" value="<?php echo $candidate['id']; ?>">
                                <label class="form-check-label d-flex flex-column align-items-center"
                                    for="candidate<?php echo $candidate['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($candidate['photo']); ?>" alt="Candidate Photo"
                                        class="candidate-photo img-thumbnail mb-3">
                                    <strong
                                        class="candidate-name"><?php echo htmlspecialchars($candidate['name']); ?></strong>
                                </label>
                            </div>
                        </div>
                        <!-- Display live vote counts -->
                        <h6 class="text-primary mt-3">Live Vote Count:</h6>
                        <ul class="list-group live-vote-count mb-3">
                            <?php
                            $found = false;
                            foreach ($voteCounts as $vote) {
                                if ($vote['candidate_name'] == $candidate['name']) {
                                    $found = true;
                                    echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
                                    echo "<div class='vote-details d-flex align-items-center'>";
                                    echo "<span class='fw-bold'>" . htmlspecialchars($vote['candidate_name']) . "</span>";
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
                <button type="submit" class="btn btn-success mt-4 px-5">Submit Vote</button>
            </div>
        </form>
        <!-- Feedback Modal -->
        <div id="feedbackModal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-<?= $modalType ?>">
                        <h5 class="modal-title text-white"><?= ucfirst($modalType) ?> Message</h5>
                        <button type="button" class="btn-close text-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <p><?= $modalMessage ?></p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-<?= $modalType ?>" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>


    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($modalMessage)): ?>
                var feedbackModal = new bootstrap.Modal(document.getElementById('feedbackModal'));
                feedbackModal.show();
            <?php endif; ?>
        });
    </script>

</body>

</html>