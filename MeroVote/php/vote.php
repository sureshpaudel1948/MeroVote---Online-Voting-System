<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ./login.php'); // Redirect to login if not logged in
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
} catch (PDOException $e) {
    die("Error fetching candidates: " . $e->getMessage());
}

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidateId = $_POST['candidate'] ?? null;

    if ($candidateId) {
        try {
            $stmt = $pdo->prepare("INSERT INTO votes (user_id, candidate_id, election_id) VALUES (:user_id, :candidate_id, :election_id)");
            $stmt->execute([
                'user_id' => $_SESSION['user_id'],
                'candidate_id' => $candidateId,
                'election_id' => $electionId
            ]);

            echo "<div class='alert alert-success'>Vote successfully submitted!</div>";
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Error submitting vote: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>Please select a candidate.</div>";
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <title>Vote Now - MeroVote</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/styles.css" />
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

        .candidate-card {
            display: flex;
            align-items: center;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fefefe;
            transition: background 0.3s;
        }

        .candidate-card:hover {
            background: #f1f9ff;
        }

        .candidate-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0056b3;
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
                <a class="navbar-brand" href="#">MeroVote - Online Voting Portal</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <div class="container mt-5">
        <h1 class="text-center text-primary mb-4">Vote for Your Candidate</h1>
        <form method="post">
            <?php if (!empty($candidates)): ?>
                <?php foreach ($candidates as $candidate): ?>
                    <div class="form-check candidate-card">
                        <input class="form-check-input" type="radio" name="candidate"
                            id="candidate<?php echo $candidate['id']; ?>" value="<?php echo $candidate['id']; ?>">
                        <label class="form-check-label d-flex align-items-center"
                            for="candidate<?php echo $candidate['id']; ?>">
                            <img src="<?php echo htmlspecialchars($candidate['photo']); ?>" alt="Candidate Photo"
                                class="candidate-photo">
                            <span><?php echo htmlspecialchars($candidate['name']); ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary mt-3">Submit Vote</button>
                </div>
            <?php else: ?>
                <p class="text-danger text-center">No candidates available for this election.</p>
            <?php endif; ?>
        </form>
    </div>
    <footer class="bg-dark text-white text-center py-3">
        <div class="cont">
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