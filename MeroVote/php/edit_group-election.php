<?php
session_start();
include 'db_config.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Error: User session not set. Verify login flow in admin_dashboard.php.');
}

// Check if election ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = 'Invalid election ID.';
    $_SESSION['msg_type'] = 'danger';
    header('Location: group-elections.php');
    exit();
}

$election_id = intval($_GET['id']);

// Fetch the election details
$stmt = $pdo->prepare('SELECT * FROM elections_group WHERE id = ?');
$stmt->execute([$election_id]);
$election = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$election) {
    $_SESSION['message'] = 'Group election not found.';
    $_SESSION['msg_type'] = 'danger';
    header('Location: group-elections.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_election'])) {
    // Fetch values from POST request and sanitize them
    $election_type = htmlspecialchars($_POST['election_type'] ?? '');
    $election_name = htmlspecialchars($_POST['name'] ?? '');
    $start_date = htmlspecialchars($_POST['start_date'] ?? '');
    $end_date = htmlspecialchars($_POST['end_date'] ?? '');
    $start_time = htmlspecialchars($_POST['start_time'] ?? '');
    $end_time = htmlspecialchars($_POST['end_time'] ?? '');

    // Panels data
    $panels = [];
    for ($i = 1; $i <= 4; $i++) {
        for ($j = 1; $j <= 8; $j++) {
            $position_value = $_POST["panel{$i}_pos{$j}"] ?? null;
            $panels[] = $position_value;
        }
    }

    // Prepare the update statement dynamically
    $fields_to_update = [];
    $params = [];

    // Add fields dynamically to update
    if (!empty($election_type)) {
        $fields_to_update[] = 'election_type = ?';
        $params[] = $election_type;
    }
    if (!empty($election_name)) {
        $fields_to_update[] = 'name = ?';
        $params[] = $election_name;
    }
    if (!empty($start_date)) {
        $fields_to_update[] = 'start_date = ?';
        $params[] = $start_date;
    }
    if (!empty($end_date)) {
        $fields_to_update[] = 'end_date = ?';
        $params[] = $end_date;
    }
    if (!empty($start_time)) {
        $fields_to_update[] = 'start_time = ?';
        $params[] = $start_time;
    }
    if (!empty($end_time)) {
        $fields_to_update[] = 'end_time = ?';
        $params[] = $end_time;
    }

    // Add panel positions if they're not empty
    for ($i = 1; $i <= 4; $i++) {
        for ($j = 1; $j <= 8; $j++) {
            $position_value = $_POST["panel{$i}_pos{$j}"] ?? null;
            if (!empty($position_value)) {
                $fields_to_update[] = "panel{$i}_pos{$j} = ?";
                $params[] = $position_value;
            }
        }
    }

    // Validate the required fields to ensure they're not empty
    if (empty($election_type) || empty($election_name) || empty($start_date) || empty($end_date) || empty($start_time) || empty($end_time)) {
        $_SESSION['message'] = 'Please fill all the required fields.';
        $_SESSION['msg_type'] = 'danger';
        header('Location: edit_group-election.php?id=' . $election_id);
        exit();
    }

    // Update only if there are fields to update
    if (count($fields_to_update) > 0) {
        // Prepare the final SQL query
        $query = "UPDATE elections_group SET " . implode(', ', $fields_to_update) . " WHERE id = ?";
        $params[] = $election_id;

        // Debugging: check the structure of the $params array
        var_dump($params); // This will show you the final array being passed to execute()

        // Execute the statement with array_merge
        try {
            $stmt = $pdo->prepare($query);
            if ($stmt->execute($params)) {
                if ($stmt->rowCount() > 0) {
                    $_SESSION['message'] = 'Election updated successfully!';
                    $_SESSION['msg_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'No changes were made.';
                    $_SESSION['msg_type'] = 'warning';
                }
            } else {
                $_SESSION['message'] = 'Error updating election. Please try again.';
                $_SESSION['msg_type'] = 'danger';
            }
        } catch (Exception $e) {
            $_SESSION['message'] = 'Error: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
        }
    } else {
        $_SESSION['message'] = 'No fields to update.';
        $_SESSION['msg_type'] = 'warning';
    }

    // Redirect after update
    header('Location: group-elections.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Election</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="../index.html">
                <img src="../img/MeroVote-Logo.png" style="height: 60px; width: auto;" alt="MeroVote Logo" class="logo img-fluid me-2">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" 
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="voter_login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="feedback.php">Feedback</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.html#how">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<div class="container mt-5">
    <h2 class="text-center text-primary">Edit Group Election</h2>

    <form method="post">
        <!-- Election Type -->
        <div class="mb-3">
            <label class="form-label">Election Type</label>
            <input type="text" class="form-control" name="election_type" 
                   value="<?php echo htmlspecialchars($election['election_type']); ?>" required>
        </div>

        <!-- Election Name -->
        <div class="mb-3">
            <label class="form-label">Election Name</label>
            <input type="text" class="form-control" name="name" 
                   value="<?php echo htmlspecialchars($election['name']); ?>" required>
        </div>

        <!-- Panel and Position Fields (Dynamic Generation) -->
        <?php
        for ($panel = 1; $panel <= 4; $panel++) {
            $has_positions = false;
            for ($pos = 1; $pos <= 8; $pos++) {
                if (!empty($election["panel{$panel}_pos{$pos}"])) {
                    $has_positions = true;
                    break;
                }
            }
            if ($has_positions) {
                echo "<h4 class='mt-3 text-primary'>Panel $panel</h4>";
                for ($position = 1; $position <= 8; $position++) {
                    if (!empty($election["panel{$panel}_pos{$position}"])) {
                        echo "
                        <div class='mb-3'>
                            <label class='form-label'>Panel $panel - Position $position</label>
                            <input type='text' class='form-control' name='panel{$panel}_pos{$position}' 
                                   value='" . htmlspecialchars($election["panel{$panel}_pos{$position}"]) . "' required>
                        </div>";
                    }
                }
            }
        }
        ?>

        <!-- Start Date -->
        <div class="mb-3">
            <label for="startDate" class="form-label">Start Date</label>
            <input type="date" class="form-control" name="start_date" 
                   value="<?php echo htmlspecialchars($election['start_date']); ?>" required>
        </div>

        <!-- End Date -->
        <div class="mb-3">
            <label for="endDate" class="form-label">End Date</label>
            <input type="date" class="form-control" name="end_date" 
                   value="<?php echo htmlspecialchars($election['end_date']); ?>" required>
        </div>

        <!-- Start Time -->
        <div class="mb-3">
            <label for="startTime" class="form-label">Start Time</label>
            <input type="time" class="form-control" name="start_time" 
                   value="<?php echo htmlspecialchars($election['start_time']); ?>" required>
        </div>

        <!-- End Time -->
        <div class="mb-3">
            <label for="endTime" class="form-label">End Time</label>
            <input type="time" class="form-control" name="end_time" 
                   value="<?php echo htmlspecialchars($election['end_time']); ?>" required>
        </div>

        <div class="text-center">
            <button type="submit" name="update_election" class="btn btn-success">Update Election</button>
            <a href="group-elections.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
