<?php
session_start();
if ( !isset( $_SESSION[ 'admin_id' ] ) ) {
    header( 'Location: ./otp-api.php' );
    exit();
}

include 'db_config.php';
$election_type = isset($_GET['type']) ? $_GET['type'] : 'Individual'; // Default to 'Individual' if not set

// Fetch individual elections
$individualElections = [];
try {
    $stmt = $pdo->query( 'SELECT id, election_type, name, start_date, end_date FROM elections ORDER BY start_date DESC' );
    $individualElections = $stmt->fetchAll( PDO::FETCH_ASSOC );
} catch ( PDOException $e ) {
    die( 'Error fetching individual elections: ' . $e->getMessage() );
}

// Fetch group elections
$groupElections = [];
try {
    $stmt = $pdo->query( 'SELECT id, election_type, name, start_date, end_date, start_time, end_time, panel1_pos1, panel1_pos2, panel1_pos3, panel1_pos4, 
               panel2_pos1, panel2_pos2, panel2_pos3, panel2_pos4, 
               panel1_pos5, panel1_pos6, panel1_pos7, panel1_pos8, 
               panel2_pos5, panel2_pos6, panel2_pos7, panel2_pos8, 
               panel3_pos1, panel3_pos2, panel3_pos3, panel3_pos4, panel3_pos5, panel3_pos6, panel3_pos7, panel3_pos8, 
               panel4_pos1, panel4_pos2, panel4_pos3, panel4_pos4, panel4_pos5, panel4_pos6, panel4_pos7, panel4_pos8
        FROM elections_group ORDER BY start_date DESC' );
    $groupElections = $stmt->fetchAll( PDO::FETCH_ASSOC );
} catch ( PDOException $e ) {
    die( 'Error fetching group elections: ' . $e->getMessage() );
}


// (Optional) Sort by start_date descending - Group Elections
usort($groupElections, function($a, $b) {
    return strtotime($b['start_date']) - strtotime($a['start_date']);
});

// (Optional) Sort by start_date descending - Individual Elections
usort($individualElections, function($a, $b) {
    return strtotime($b['start_date']) - strtotime($a['start_date']);
});

// Handle Delete for individual elections
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' && isset( $_POST[ 'delete_election' ] ) && isset( $_POST[ 'election_table' ] ) && $_POST[ 'election_table' ] === 'individual' ) {
    $deleteId = $_POST[ 'election_id' ];
    try {
        $deleteStmt = $pdo->prepare( 'DELETE FROM elections WHERE id = ?' );
        $deleteStmt->execute( [ $deleteId ] );
        header( 'Location: admin_dashboard.php' );
        exit();
    } catch ( PDOException $e ) {
        die( 'Error deleting election: ' . $e->getMessage() );
    }
}

// Handle Delete for group elections
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' && isset( $_POST[ 'delete_election' ] ) && isset( $_POST[ 'election_table' ] ) && $_POST[ 'election_table' ] === 'group' ) {
    $deleteId = $_POST[ 'election_id' ];
    try {
        $deleteStmt = $pdo->prepare( 'DELETE FROM elections_group WHERE id = ?' );
        $deleteStmt->execute( [ $deleteId ] );
        header( 'Location: admin_dashboard.php' );                                  
        exit();
    } catch ( PDOException $e ) {
        die( 'Error deleting group election: ' . $e->getMessage() );
    }
}

// Handle Redirect to Create New Election based on category selection
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' && isset( $_POST[ 'redirect_election' ] ) ) {
    $election_category = isset( $_POST[ 'election_category' ] ) ? $_POST[ 'election_category' ] : 'individual';
    if ( $election_category === 'group' ) {
        header( 'Location: ./group-elections.php' );
    } else {
        header( 'Location: ./elections.php' );
    }
    exit();
}
?>

<!doctype html>
<html lang = 'en'>
<head>
<title>Admin Dashboard - MeroVote</title>
<meta charset = 'utf-8' />
<meta name = 'viewport' content = 'width=device-width, initial-scale=1, shrink-to-fit=no' />
<link href = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel = 'stylesheet' />
<link rel = 'stylesheet' href = '../css/styles.css' />
</head>
<body>

<header>
<nav class = 'navbar navbar-expand-lg navbar-dark bg-dark'>
<div class = 'container-fluid'>
<a class = 'navbar-brand d-flex align-items-center' href = '../index.html'>
<img src = '../img/MeroVote-Logo.png' style = 'height: 60px;' alt = 'MeroVote Logo' class = 'me-2'>
<span class = 'fw-bold'> </span>
</a>
<button class = 'navbar-toggler' type = 'button' data-bs-toggle = 'collapse'
data-bs-target = '#navbarContent' aria-controls = 'navbarContent'
aria-expanded = 'false' aria-label = 'Toggle navigation'>
<span class = 'navbar-toggler-icon'></span>
</button>

<div class = 'collapse navbar-collapse' id = 'navbarContent'>
<ul class = 'navbar-nav ms-auto'>
<li class = 'nav-item'>
<a class = 'nav-link' href = 'admin_login.php'>Login</a>
</li>
<li class = 'nav-item'>
<a class = 'nav-link' href = '../index.html#how'>How It Works</a>
</li>
<li class = 'nav-item'>
<a class = 'nav-link active' href = 'admin_dashboard.php'>Dashboard</a>
</li>
<li class = 'nav-item'>
<a class = 'nav-link text-danger' href = 'logout.php'>Logout</a>
</li>
</ul>
</div>
</div>
</nav>
</header>

<main class="container my-4">
    <h1 class="text-primary mb-4">Welcome, Admin!</h1>

    <div class="d-flex justify-content-between mb-3">
        <h2 class="text-secondary">📋 Manage Elections</h2>
        <form method="POST">
            <!-- New dropdown for election category -->
            <select name="election_category" class="form-select d-inline-block w-auto me-2">
                <option value="individual" selected>Individual</option>
                <option value="group">Group</option>
            </select>
            <button type="submit" name="redirect_election" class="btn btn-success">➕ Create New Election</button>
        </form>
    </div>

    <!-- Individual Elections Table -->
    <h4 class="text-primary mb-4" style="text-align:center; font-weight:500;  ">Individual Elections</h4>
    <table class="table table-hover table-bordered">
        <thead>
            <tr>
                <th scope="col">Election Type</th>
                <th scope="col">Election Name</th>
                <th scope="col">Start Date</th>
                <th scope="col">End Date</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($individualElections)): ?>
                <?php foreach ($individualElections as $election): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($election['election_type']); ?></td>
                        <td><?php echo htmlspecialchars($election['name']); ?></td>
                        <td><?php echo htmlspecialchars($election['start_date']); ?></td>
                        <td><?php echo htmlspecialchars($election['end_date']); ?></td>
                        <td class="action-btns">
                            <!-- Use a conditional if needed to redirect to the proper edit page -->
                            <a href="edit_election.php?id=<?php echo $election['id']; ?>&type=<?php echo urlencode($election['election_type']); ?>" class="btn btn-warning btn-sm">✏️ Edit</a>

                            <form method="POST" class="d-inline-block">
    <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
    <input type="hidden" name="election_table" value="individual">
    <button type="submit" name="delete_election" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this election?')">🗑️ Delete</button>
</form>

                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">No elections found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

      <!-- Group Elections Table -->
      <h4 class="text-primary mb-4" style="text-align:center; font-weight:500;">Group Elections</h4>
      <table class="table table-hover table-bordered">
        <thead>
            <tr>
                <th scope="col">Election Type</th>
                <th scope="col">Election Name</th>
                <th scope="col">Start Date</th>
                <th scope="col">End Date</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($groupElections)): ?>
                <?php foreach ($groupElections as $election): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($election['election_type']); ?></td>
                        <td><?php echo htmlspecialchars($election['name']); ?></td>
                        <td><?php echo htmlspecialchars($election['start_date']); ?></td>
                        <td><?php echo htmlspecialchars($election['end_date']); ?></td>
                        <td class="action-btns">
                            <!-- Use a conditional if needed to redirect to the proper edit page -->
                            <a href="edit_group-election.php?id=<?php echo $election['id']; ?>&type=<?php echo urlencode($election['election_type']); ?>" class="btn btn-warning btn-sm">✏️ Edit</a>

                            <form method="POST" class="d-inline-block">
    <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
    <input type="hidden" name="election_table" value="group">
    <button type="submit" name="delete_election" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this election?')">🗑️ Delete</button>
</form>

                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">No elections found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>


<style>
/* Admin Dashboard Enhancements */
h1, h2 {
    font-family: 'Arial', sans-serif;
    font-weight: bold;
    color: #333;
}

.table th {
    background-color: #343a40;
    color: white;
    text-align: center;
}

.table td {
    text-align: center;
    vertical-align: middle;
}

.action-btns .btn {
    margin: 0 5px;
    padding: 5px 10px;
}

.table-hover tbody tr:hover {
    background-color: #f0f8ff;
}

.navbar {
    box-shadow: 0px 3px 5px rgba( 0, 0, 0, 0.2 );
}

.navbar-brand img {
    border-radius: 8px;
}

footer p {
    margin: 0;
    font-size: 14px;
}

</style>

<footer class = 'bg-dark text-white text-center py-3'>
<div class = 'container'>
<p>&copy;
2024 Online Voting System. All rights reserved.</p>
</div>
</footer>

<script src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script>

</body>
</html>

