<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: dashboard.php'); // Redirect if not logged in or not an admin
    exit();
}

include 'db_config.php';

// Handle election creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['election_name'])) {
    $election_name = htmlspecialchars($_POST['election_name']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    try {
        $stmt = $pdo->prepare("INSERT INTO elections (name, start_date, end_date) VALUES (?, ?, ?)");
        if ($stmt->execute([$election_name, $start_date, $end_date])) {
            $_SESSION['message'] = "Election created successfully!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "Error creating election. Please try again.";
            $_SESSION['msg_type'] = "danger";
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Database error: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }

    header('Location: elections.php');
    exit();
}

// Handle candidate addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_name'])) {
    $candidate_name = htmlspecialchars($_POST['candidate_name']);
    $election_id = $_POST['election_id'];
    $photo = $_FILES['photo'];

    // Debugging: Check if photo is present
    if ($photo['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Error uploading photo. Error code: " . $photo['error'];
        $_SESSION['msg_type'] = "danger";
        header('Location: elections.php');
        exit();
    }

    try {
        // Handle photo upload
        if ($photo['error'] === UPLOAD_ERR_OK) {
            $photoName = time() . '_' . basename($photo['name']);
            $targetDir = 'candidates_photos/';
            $targetFile = $targetDir . $photoName;

            // Ensure target directory exists
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            // Allowed file types (jpeg, png)
            $allowedTypes = ['image/jpeg', 'image/png'];
            if (in_array($photo['type'], $allowedTypes)) {
                // Check if file upload is successful
                if (move_uploaded_file($photo['tmp_name'], $targetFile)) {
                    // Insert candidate into the database
                    $stmt = $pdo->prepare("INSERT INTO candidates (name, photo, election_id, created_at) VALUES (?, ?, ?, NOW())");
                    if ($stmt->execute([$candidate_name, $targetFile, $election_id])) {
                        $_SESSION['message'] = "Candidate added successfully!";
                        $_SESSION['msg_type'] = "success";
                    } else {
                        $_SESSION['message'] = "Error adding candidate. Please try again.";
                        $_SESSION['msg_type'] = "danger";
                    }
                } else {
                    $_SESSION['message'] = "Failed to upload photo.";
                    $_SESSION['msg_type'] = "danger";
                }
            } else {
                $_SESSION['message'] = "Invalid file type. Only JPEG and PNG are allowed.";
                $_SESSION['msg_type'] = "danger";
            }
        } else {
            $_SESSION['message'] = "Error uploading photo.";
            $_SESSION['msg_type'] = "danger";
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Database error: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }

    header('Location: elections.php');
    exit();
}

// Fetch all elections for the dropdown
$elections = $pdo->query("SELECT id, name FROM elections ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">

<head>
    <title>Create Election - MeroVote</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
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
                            <a class="nav-link" href="./dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./logout.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="container mt-4">
        <h1>Create New Election</h1>

        <!-- Form for creating a new election -->
        <form action="elections.php" method="POST">
            <div class="mb-3">
                <label for="electionName" class="form-label">Election Name</label>
                <input type="text" name="election_name" id="electionName" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="startDate" class="form-label">Start Date</label>
                <input type="date" name="start_date" id="startDate" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="endDate" class="form-label">End Date</label>
                <input type="date" name="end_date" id="endDate" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Create Election</button>
        </form>

        <hr>

        <h1>Add New Candidate</h1>

        <!-- Form for adding a candidate -->
        <form action="elections.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="candidateName" class="form-label">Candidate Name</label>
                <input type="text" name="candidate_name" id="candidateName" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="electionId" class="form-label">Election</label>
                <select name="election_id" id="electionId" class="form-control" required>
                    <option value="">Select Election</option>
                    <?php foreach ($elections as $election): ?>
                        <option value="<?php echo $election['id']; ?>"><?php echo htmlspecialchars($election['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="photo" class="form-label">Upload Photo</label>
                <input type="file" name="photo" id="photo" class="form-control" accept="image/jpeg, image/png" required>
            </div>
            <button type="submit" class="btn btn-success">Add Candidate</button>
        </form>
    </main>

    <footer class="bg-dark text-white text-center py-3 mt-4">
        <div class="container">
            <p>&copy; 2024 Online Voting System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
</body>

</html>