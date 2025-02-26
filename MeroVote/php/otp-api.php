<?php
session_start();
include 'db_config.php';

$error_message = "";

// Enhanced logging function
function logToFile($message) {
    $logFile = 'sms_api.log';
    $timestamp = date('Y-m-d H:i:s');
    $fullMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $fullMessage, FILE_APPEND);
}

// Send OTP Code with validation
function sendOTP($mobile) {
    $apiToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIyIiwianRpIjoiOTc5NjE3MmY4MmI5NzZkMjdlYzU2YzNmYTc2OTRlMjAxM2EyMWNjYTQ5MWIzZjE3M2I1NmU0Y2IxY2MwMjIyNTQxZTU0ZjIwNmUwNWRhN2YiLCJpYXQiOjE2NjAxMDM4OTEuMDk0NjE5LCJuYmYiOjE2NjAxMDM4OTEuMDk0NjIyLCJleHAiOjE2OTE2Mzk4OTEuMDkwNjI5LCJzdWIiOiI1MDEiLCJzY29wZXMiOltdfQ.VLE4mCchmKLKDhreGaE-FLGSLebmBGdP67Jm1jbYu26G_k3HQkyE1ahXh8cFrGWcXyipb9YtP406WhHANm51BQ';
    $otp = rand(100000, 999999); // Generate a 6-digit OTP

    // Store OTP in session immediately (as a string for consistent comparison)
    $_SESSION['otp'] = (string)$otp;
    $_SESSION['otp_attempts'] = 0; // Initialize attempt counter
    logToFile("Generated OTP: $otp for mobile: $mobile");

    $message = "Use this OTP code to access MeroVote Online Voting System: $otp";

    $payload = json_encode([
        'message' => $message,
        'mobile'  => $mobile
    ]);

    $ch = curl_init('https://sms.sociair.com/api/sms');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    logToFile("Request Payload: $payload");
    logToFile("Response Code: $http_code, Response: $response");
    if ($curl_error) {
        logToFile("CURL Error: $curl_error");
    }

    $responseData = json_decode($response, true);

    // Check if the response indicates success even if the HTTP code isn't 200.
    if (($http_code == 200 || $http_code == 400) && isset($responseData['message']) && strpos($responseData['message'], 'Success') !== false) {
        return ['success' => true, 'message' => 'OTP sent successfully.'];
    } else {
        unset($_SESSION['otp']); // Clear OTP on failure
        $errorDetail = isset($responseData['message']) ? $responseData['message'] : 'Service unavailable';
        return ['success' => false, 'message' => "Failed to send OTP. HTTP Error: $http_code - $errorDetail"];
    }
}

// Enhanced OTP verification
function verifyOTP($inputOtp) {
    if (($_SESSION['otp_attempts'] ?? 0) >= 3) {
        return ['success' => false, 'message' => 'Maximum attempts exceeded.'];
    }

    $cleanOtp = preg_replace('/\D/', '', $inputOtp);
    logToFile("Verifying OTP. Input: $cleanOtp, Stored: " . $_SESSION['otp']);

    if (isset($_SESSION['otp']) && hash_equals($_SESSION['otp'], (string)$cleanOtp)) {
        unset($_SESSION['otp'], $_SESSION['otp_attempts']);
        logToFile("OTP verification successful.");
        return ['success' => true, 'message' => 'OTP verified successfully.'];
    } else {
        $_SESSION['otp_attempts'] = ($_SESSION['otp_attempts'] ?? 0) + 1;
        logToFile("OTP verification failed. Attempt: " . $_SESSION['otp_attempts']);
        return ['success' => false, 'message' => 'Invalid OTP.'];
    }
}

// Handle OTP Request
if (isset($_POST['send_otp'])) {
    $mobile = preg_replace('/\D/', '', $_POST['mobile']);
    if (strlen($mobile) == 10 && substr($mobile, 0, 2) == '98') {
        $result = sendOTP($mobile);
        if ($result['success']) {
            $_SESSION['otp_mobile'] = $mobile;
            $_SESSION['otp_sent'] = true;
            $error_message = "<span style='color:green'>{$result['message']}</span>";
        } else {
            $error_message = "<span style='color:red'>Error: {$result['message']}</span>";
        }
    } else {
        $error_message = "<span style='color:red'>Invalid mobile number. Use 98XXXXXXXX.</span>";
    }
}

// Handle OTP Verification
if (isset($_POST['verify_otp'])) {
    $otp = $_POST['otp'] ?? '';
    if (strlen($otp) == 6) {
        $result = verifyOTP($otp);
        if ($result['success']) {
            $_SESSION['loggedin'] = true;
            header('Location: voter_dashboard.php');
            exit();
        } else {
            $error_message = "<span style='color:red'>{$result['message']}</span>";
        }
    } else {
        $error_message = "<span style='color:red'>Enter a 6-digit OTP.</span>";
    }
}

logToFile("Session Status: " . print_r($_SESSION, true));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MeroVote OTP Verification</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .otp-container { max-width: 400px; margin: 50px auto; padding: 20px; }
        input, button { width: 100%; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
<div class="otp-container">
    <h2>OTP Verification</h2>
    <?php if (!isset($_SESSION["otp_sent"])) { ?>
        <form method="post">
            <label>Enter Mobile Number:</label>
            <input type="text" name="mobile" pattern="98\d{8}" placeholder="98XXXXXXXX" required>
            <button type="submit" name="send_otp">Send OTP</button>
        </form>
    <?php } else { ?>
        <form method="post">
            <label>Enter OTP (6 digits):</label>
            <input type="text" name="otp" pattern="\d{6}" placeholder="123456" required>
            <button type="submit" name="verify_otp">Verify OTP</button>
        </form>
        <p>Attempts remaining: <?= 3 - ($_SESSION['otp_attempts'] ?? 0) ?></p>
    <?php } ?>
    <?php if ($error_message) echo "<p>$error_message</p>"; ?>
</div>
</body>
</html>
