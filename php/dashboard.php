<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html'); // Redirect to login if not logged in
    exit();
}

// HTML structure for the dashboard
?>
<!doctype html>
<html lang="en">
<head>
    <title>Dashboard</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Welcome <?php echo ($_SESSION['role'] == 'admin') ? 'Admin' : 'Voter'; ?>!</h1>
        
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <!-- Admin Panel Content -->
            <div id="adminPanel">
                <h2>Admin Panel</h2>
                <p>Here you can manage elections, candidates, and more.</p>
                <!-- Add other admin functionalities here -->
            </div>
        <?php else: ?>
            <!-- Voter Panel Content -->
            <div id="voterPanel">
                <h2>Ongoing Elections</h2>
                <p>Participate in ongoing elections here.</p>
                <!-- Add voter-specific functionalities here -->
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-white text-center py-3">
        <p>&copy; 2024 Online Voting System. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
</body>
</html>
