<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ./admin_login.php');
    exit();
}

include 'db_config.php';

$elections = [];

try {
    $stmt = $pdo->query("SELECT id, election_type, name, start_date, end_date FROM elections ORDER BY start_date DESC");
    $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching elections: " . $e->getMessage());
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redirect_election'])) {
    header('Location: ./elections.php');
    exit();
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
                <a class="navbar-brand" href="admin_dashboard.php">MeroVote - Online
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
                            <a class="nav-link" href="admin_login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../index.html#how">How It Works</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
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
        <h1>Welcome, Admin !</h1>
        <div id="adminPanel" class="mt-4">
            <h2>Admin Panel</h2>
            <form method="POST">
                <button type="submit" name="redirect_election" class="btn btn-success mb-3">Create New Election</button>
            </form>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">Election Type</th>
                        <th scope="col">Election Name</th>
                        <th scope="col">Start Date</th>
                        <th scope="col">End Date</th>
                    </tr>
                </thead>
                <tbody id="adminElectionsTable">
                    <?php if (!empty($elections)): ?>
                        <?php foreach ($elections as $election): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($election['election_type']); ?></td>
                                <td><?php echo htmlspecialchars($election['name']); ?></td>
                                <td><?php echo htmlspecialchars($election['start_date']); ?></td>
                                <td><?php echo htmlspecialchars($election['end_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No elections found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer class="bg-dark text-white text-center py-3">
        <div class="container">
            <p>&copy; 2024 Online Voting System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
</body>

</html>