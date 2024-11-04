<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ./login.php'); // Redirect to login if not logged in
    exit();
}

// Define user role from the session
$userRole = $_SESSION['role'];
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
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody id="adminElectionsTable">
                        <!-- Dynamically populated -->
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <!-- User Panel -->
            <div id="userPanel" class="mt-5">
                <h2>Ongoing Elections</h2>
                <div id="ongoingElections">
                    <!-- Dynamically populated -->
                </div>

                <h2 class="mt-5">Expired Elections</h2>
                <div id="expiredElections">
                    <!-- Dynamically populated -->
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
    <script src="js/script.js"></script>
    <script>
        // Display Admin or Voter panel based on the user's role
        const userRole = "<?php echo $userRole; ?>";
        
        if (userRole === "admin") {
            document.getElementById("adminPanel").style.display = "block";
        } else {
            document.getElementById("userPanel").style.display = "block";
        }

        // Function to redirect to elections.php to create a new election
        function createElection() {
            window.location.href = "elections.php";
        }
    </script>
</body>
</html>
