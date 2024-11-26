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
    $stmt = $pdo->prepare("SELECT id, name FROM candidates WHERE election_id = :election_id");
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

            echo "Vote successfully submitted!";
        } catch (PDOException $e) {
            die("Error submitting vote: " . $e->getMessage());
        }
    } else {
        echo "Please select a candidate.";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <title>Vote Now - MeroVote</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/styles.css" />
</head>
<body>
    <div class="container mt-5">
        <h1>Vote for Your Candidate</h1>
        <form method="post">
    <?php if (!empty($candidates)): ?>
        <?php foreach ($candidates as $candidate): ?>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="candidate" id="candidate<?php echo $candidate['id']; ?>" value="<?php echo $candidate['id']; ?>">
                <label class="form-check-label" for="candidate<?php echo $candidate['id']; ?>">
                    <img src="<?php echo htmlspecialchars($candidate['photo']); ?>" alt="Candidate Photo" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 10px;">
                    <?php echo htmlspecialchars($candidate['name']); ?>
                </label>
            </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary mt-3">Submit Vote</button>
    <?php else: ?>
        <p>No candidates available for this election.</p>
    <?php endif; ?>
</form>

    </div>
</body>
</html>
