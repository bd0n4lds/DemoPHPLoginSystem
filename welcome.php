<?php
// Logic (Controller-like part)
session_start();

const LOGIN_PAGE = 'login.php';
const RESET_PASSWORD_PAGE = 'reset-password.php';
const LOGOUT_PAGE = 'logout.php';

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ' . LOGIN_PAGE);
    exit();
}

// Get the username for display, safely
$loggedInUsername = htmlspecialchars($_SESSION['username'] ?? 'Guest');

// Data to be passed to the view
$templateData = [
    'username' => $loggedInUsername,
    'resetPasswordUrl' => RESET_PASSWORD_PAGE,
    'logoutUrl' => LOGOUT_PAGE,
];

// Include the HTML template (View part)
require_once 'welcome_template.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - <?php echo $templateData['username']; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font: 14px sans-serif;
            text-align: center;
            padding-top: 50px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="my-5">Hi, <b><?php echo $templateData['username']; ?></b>. Welcome To Our Demo Login System.</h1>
        <p>
            <a href="<?php echo $templateData['resetPasswordUrl']; ?>" class="btn btn-warning">Reset Your Password</a>
            <a href="<?php echo $templateData['logoutUrl']; ?>" class="btn btn-danger ml-3">Sign Out of Your Account</a>
        </p>
    </div>
</body>

</html>