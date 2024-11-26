<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ./login.php'); // Redirect to login if not logged in
    exit();
}

// Include database configuration
include 'db_config.php';

// Define user role from the session
$userRole = $_SESSION['role'];

// Fetch elections for admin
$elections = [];
if ($userRole === 'admin') {
    try {
        $stmt = $pdo->query("SELECT id, name, start_date, end_date FROM elections ORDER BY start_date DESC");
        $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching elections: " . $e->getMessage());
    }
}
else {
    // Fetch ongoing and expired elections for voters
    $ongoingElections = [];
    $expiredElections = [];
    $currentDate = date('Y-m-d');

    try {
        $stmt = $pdo->query("SELECT id, name, start_date, end_date FROM elections ORDER BY start_date ASC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['start_date'] <= $currentDate && $row['end_date'] >= $currentDate) {
                $ongoingElections[] = $row;
            } elseif ($row['end_date'] < $currentDate) {
                $expiredElections[] = $row;
            }
        }
    } catch (PDOException $e) {
        die("Error fetching elections: " . $e->getMessage());
    }
}
?>
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
                            <a class="nav-link active" aria-current="page" href="#">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="container mt-4">
        <h1>Welcome, <?php echo ($userRole === 'admin') ? 'Admin' : 'Voter'; ?>!</h1>

        <?php if ($userRole === 'admin'): ?>
            <!-- Admin Panel -->
            <div id="adminPanel" class="mt-4">
                <h2>Admin Panel</h2>
                <button class="btn btn-success mb-3" onclick="createElection()">Create New Election</button>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col">Election Name</th>
                            <th scope="col">Start Date</th>
                            <th scope="col">End Date</th>
                        </tr>
                    </thead>
                    <tbody id="adminElectionsTable">
                        <?php if (!empty($elections)): ?>
                            <?php foreach ($elections as $election): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($election['name']); ?></td>
                                    <td><?php echo htmlspecialchars($election['start_date']); ?></td>
                                    <td><?php echo htmlspecialchars($election['end_date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">No elections found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
             <!-- Voter Panel -->
             <div id="userPanel" class="mt-5">
                <h2>Ongoing Elections</h2>
                <div id="ongoingElections">
                    <ul class="list-group">
                        <?php if (!empty($ongoingElections)): ?>
                            <?php foreach ($ongoingElections as $election): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($election['name']); ?>
                                    <a href="vote.php?election_id=<?php echo $election['id']; ?>" class="btn btn-primary">Vote Now</a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center">No ongoing elections available.</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <h2 class="mt-5">Expired Elections</h2>
                <div id="expiredElections">
                    <ul class="list-group">
                        <?php if (!empty($expiredElections)): ?>
                            <?php foreach ($expiredElections as $election): ?>
                                <li class="list-group-item">
                                    <?php echo htmlspecialchars($election['name']); ?> (Ended on <?php echo htmlspecialchars($election['end_date']); ?>)
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center">No expired elections found.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
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
