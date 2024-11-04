<?php
session_start();
require_once 'handle_requests.php'; // Include the handler functions

// Enable error reporting to help catch any issues
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['domain'])) {
    header("Location: login.php");
    exit;
}

$loginDomain = $_SESSION['domain'];
$recipientUser = isset($_POST['recipient']) ? trim($_POST['recipient']) : '';
$recipient = $recipientUser !== '' ? $recipientUser . '@' . $loginDomain : $loginDomain;
$sender = isset($_POST['sender']) ? $_POST['sender'] : '';
$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : '';
$mid = isset($_POST['mid']) ? $_POST['mid'] : '';
$quarantineType = isset($_POST['quarantine_type']) ? $_POST['quarantine_type'] : '';

$emailResults = [];
$pvoResults = [];
$spamResults = [];
$releaseMessage = '';
$deleteMessage = '';

// Handle form submissions for searching, releasing, or deleting emails
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['search_email'])) {
        $emailResults = searchEmails($recipient, $sender, formatDateTime($startDate), formatDateTime($endDate));
    }

    if (isset($_POST['search_pvo'])) {
        $pvoResults = searchPVO($recipient, $sender, formatDateTime($startDate), formatDateTime($endDate));
    }

    if (isset($_POST['search_spam'])) {
        $spamResults = searchSpam($recipient, formatDateTime($startDate), formatDateTime($endDate));
    }

    if (isset($_POST['release_email'])) {
        $releaseMessage = releaseEmail($mid, $quarantineType);
    }

    if (isset($_POST['delete_email'])) {
        $deleteMessage = deleteEmail($mid, $quarantineType);
    }
}

// Function to display results in a human-readable table format
function jsonToTable($jsonData, $type = 'default') {
    if (empty($jsonData)) {
        return "<p>No data found.</p>";
    }

    // Start the table with the headers
    $html = '<table class="table table-bordered table-striped custom-table">';
    $html .= '<thead><tr>';

    if ($type === 'pvo') {
        // PVO-specific headers with action buttons
        $html .= '<th>Sender</th><th>Recipient</th><th>Subject</th><th>Status (Quarantine Reason)</th><th>Timestamp (Received)</th><th>Email Message ID (esaMid)</th><th>Actions</th>';
    } else {
        // Default headers
        $html .= '<th>Sender</th><th>Recipient</th><th>Subject</th><th>Status</th><th>Timestamp</th><th>Message ID</th><th>Actions</th>';
    }

    $html .= '</tr></thead><tbody>';

    // Loop through the JSON data to extract the specific fields
    foreach ($jsonData as $item) {
        if (isset($item['attributes'])) {
            $attributes = $item['attributes'];

            if ($type === 'pvo') {
                // Handle PVO fields and arrays
                $sender = isset($attributes['sender']) ? (is_array($attributes['sender']) ? implode(', ', $attributes['sender']) : htmlspecialchars($attributes['sender'])) : 'N/A';
                $recipient = isset($attributes['recipient']) ? (is_array($attributes['recipient']) ? implode(', ', $attributes['recipient']) : htmlspecialchars($attributes['recipient'])) : 'N/A';
                $subject = isset($attributes['subject']) ? (is_array($attributes['subject']) ? implode(', ', $attributes['subject']) : htmlspecialchars($attributes['subject'])) : 'N/A';
                $status = isset($attributes['quarantineForReason']) ? (is_array($attributes['quarantineForReason']) ? implode(', ', $attributes['quarantineForReason']) : htmlspecialchars($attributes['quarantineForReason'])) : 'Unknown Status';
                $timestamp = isset($attributes['received']) ? (is_array($attributes['received']) ? implode(', ', $attributes['received']) : htmlspecialchars($attributes['received'])) : 'N/A';
                $messageID = isset($attributes['esaMid']) ? (is_array($attributes['esaMid']) ? implode(', ', $attributes['esaMid']) : htmlspecialchars($attributes['esaMid'])) : 'N/A';

                // Replace Sender IP with action buttons
                $actions = '
                    <form method="post" action="">
                        <input type="hidden" name="mid" value="' . htmlspecialchars($messageID) . '">
                        <input type="hidden" name="quarantine_type" value="pvo">
                        <button type="submit" name="release_email" class="btn btn-success btn-sm">Release</button>
                        <button type="submit" name="delete_email" class="btn btn-danger btn-sm">Delete</button>
                    </form>';
                
                // Add a row with these fields, including the action buttons instead of Sender IP
                $html .= '<tr>';
                $html .= "<td>$sender</td><td>$recipient</td><td>$subject</td><td>$status</td><td>$timestamp</td><td>$messageID</td><td>$actions</td>";
                $html .= '</tr>';
            } else {
                // Default fields and handle arrays
                $sender = isset($attributes['sender']) ? (is_array($attributes['sender']) ? implode(', ', $attributes['sender']) : htmlspecialchars($attributes['sender'])) : 'N/A';
                $recipient = isset($attributes['recipient'][0]) ? (is_array($attributes['recipient'][0]) ? implode(', ', $attributes['recipient'][0]) : htmlspecialchars($attributes['recipient'][0])) : 'N/A';
                $mid = isset($attributes['mid'][0]) ? (is_array($attributes['mid'][0]) ? implode(', ', $attributes['mid'][0]) : htmlspecialchars($attributes['mid'][0])) : null;
                $subject = isset($attributes['finalSubject'][$mid]) 
                    ? htmlspecialchars($attributes['finalSubject'][$mid]) 
                    : (isset($attributes['subject']) ? htmlspecialchars($attributes['subject']) : 'N/A');
                $status = isset($attributes['messageStatus'][$mid]) 
                    ? htmlspecialchars($attributes['messageStatus'][$mid]) 
                    : 'Unknown Status';
                $timestamp = isset($attributes['timestamp']) ? (is_array($attributes['timestamp']) ? implode(', ', $attributes['timestamp']) : htmlspecialchars($attributes['timestamp'])) : 'N/A';
                $messageID = $mid ? htmlspecialchars($mid) : 'N/A';
                $senderIP = isset($attributes['senderIp']) ? (is_array($attributes['senderIp']) ? implode(', ', $attributes['senderIp']) : htmlspecialchars($attributes['senderIp'])) : 'N/A';

                // Add a row with these fields
                $html .= '<tr>';
                $html .= "<td>$sender</td><td>$recipient</td><td>$subject</td><td>$status</td><td>$timestamp</td><td>$messageID</td><td>$senderIP</td>";
                $html .= '</tr>';
            }
        }
    }

    $html .= '</tbody></table>';
    return $html;
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nayatel - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/dashboard_styles.css"> <!-- Separate CSS for Dashboard Page -->
</head>
<body class="bg-light">
    <!-- Top Bar -->
    <div class="container-fluid bg-white py-3 shadow-sm top-bar">
        <div class="container d-flex justify-content-between align-items-center">
            <!-- Logo -->
            <div class="logo">
                <img src="assets/logo.svg" alt="Nayatel" style="width: 150px;">
            </div>

            <!-- User Information -->
            <div class="user-info d-flex align-items-center">
                <span class="me-3">Logged in as: <?php echo htmlspecialchars($_SESSION['domain']); ?></span>
                <a href="logout.php" class="btn btn-primary" style="background-color: #1f4690; border-color: #1f4690;">
                    Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <h3>Menu</h3>
        <a href="?section=search_email">Search Email</a>
        <a href="?section=search_pvo">Search Policy Qurantine</a>
        <a href="?section=search_spam">Search Spam Quarantine</a>
        <!--<a href="?section=manage_quarantine">Manage Quarantine Emails</a> -->
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="card shadow p-4">
            <?php
            // Load content based on selected section
            $section = isset($_GET['section']) ? $_GET['section'] : 'search_email';

            if ($section === 'search_email') { ?>
                <h3 class="mb-4">Search Email</h3>
                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="recipient">Recipient</label>
                            <input type="text" class="form-control" id="recipient" name="recipient" placeholder="Leave Empty to search across domain">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sender">Sender</label>
                            <input type="text" class="form-control" id="sender" name="sender" required value="<?php echo htmlspecialchars($sender); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date">Start Date</label>
                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" required value="<?php echo htmlspecialchars(substr($startDate, 0, 16)); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date">End Date</label>
                            <input type="datetime-local" class="form-control" id="end_date" name="end_date" required value="<?php echo htmlspecialchars(substr($endDate, 0, 16)); ?>">
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="search_email" class="btn btn-primary" style="background-color: #1f4690; border-color: #1f4690;">Search Email</button>
                    </div>
                </form>

            <?php } elseif ($section === 'search_pvo') { ?>
                <h3 class="mb-4">Search Policy Quarantine</h3>
                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="recipient">Recipient</label>
                            <input type="text" class="form-control" id="recipient" name="recipient" placeholder="Leave empty to search across domain" >
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sender">Sender</label>
                            <input type="text" class="form-control" id="sender"
                            name="sender" required>
                            value="<?php echo isset($_POST['sender']) ? htmlspecialchars($_POST['sender']) : ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date">Start Date</label>
                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                            value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date">End Date</label>
                            <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                             value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>">
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="search_pvo" class="btn btn-primary" style="background-color: #1f4690; border-color: #1f4690;">Search PVO</button>
                    </div>
                </form>

            <?php } elseif ($section === 'search_spam') { ?>
                <h3 class="mb-4">Search Spam Quarantine</h3>
                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="recipient">Recipient</label>
                            <input type="text" class="form-control" id="recipient" name="recipient" placeholder="Leave empty to search across domain" >
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="start_date">Start Date</label>
                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date">End Date</label>
                            <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="search_spam" class="btn btn-primary" style="background-color: #1f4690; border-color: #1f4690;">Search Spam Quarantine</button>
                    </div>
                </form>

            <?php } else { ?>
                <h3 class="mb-4">Manage Quarantine Emails</h3>
                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="mid">Message ID (MID)</label>
                            <input type="text" class="form-control" id="mid" name="mid">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="quarantine_type">Quarantine Type</label>
                            <select id="quarantine_type" name="quarantine_type" class="form-select" required>
                                <option value="spam">Spam</option>
                                <option value="pvo">PVO</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="release_email" class="btn btn-success">Release Email</button>
                        <button type="submit" name="delete_email" class="btn btn-danger">Delete Email</button>
                    </div>
                </form>
            <?php } ?>

            <!-- Display results or messages here -->
            <div class="results mt-4">
                <h4>Results</h4>
                <?php
                if (!empty($emailResults)) {
                    echo jsonToTable($emailResults['response']['data']);
                }
                if (!empty($pvoResults)) {
                    echo jsonToTable($pvoResults['response']['data'], 'pvo');
                }
                if (!empty($spamResults)) {
                    echo jsonToTable($spamResults['response']['data']);
                }
                if (!empty($releaseMessage)) {
                    echo '<p class="alert alert-success">' . htmlspecialchars($releaseMessage) . '</p>';
                }
                if (!empty($deleteMessage)) {
                    echo '<p class="alert alert-danger">' . htmlspecialchars($deleteMessage) . '</p>';
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
