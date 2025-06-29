<?php

/**
 * reset_password.php
 * Allows a logged-in user to change their password.
 */

// Initialize the session
session_start();

// Check if the user is logged in; if not, redirect to the login page.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit(); // Always exit after a header redirect
}

// Include database configuration file.
// It's assumed 'config.php' establishes a database connection, typically $link for mysqli or $pdo for PDO.
require_once "config.php";

// Define constants for password policy and redirection.
define('MIN_PASSWORD_LENGTH', 6);
define('LOGIN_PAGE', 'login.php');
define('WELCOME_PAGE', 'welcome.php');

// Initialize variables for form data and error messages.
$newPassword = $confirmPassword = '';
$newPasswordErr = $confirmPasswordErr = '';
$generalError = ''; // To capture general errors not tied to specific fields

// --- Process Form Submission ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. Validate New Password
    $newPassword = trim($_POST["new_password"] ?? '');
    if (empty($newPassword)) {
        $newPasswordErr = "Please enter your new password.";
    } elseif (strlen($newPassword) < MIN_PASSWORD_LENGTH) {
        $newPasswordErr = "Password must be at least " . MIN_PASSWORD_LENGTH . " characters.";
    }

    // 2. Validate Confirm Password
    $confirmPassword = trim($_POST["confirm_password"] ?? '');
    if (empty($confirmPassword)) {
        $confirmPasswordErr = "Please confirm your new password.";
    } elseif (empty($newPasswordErr) && ($newPassword !== $confirmPassword)) {
        $confirmPasswordErr = "Passwords do not match.";
    }

    // 3. Update Password in Database (if no validation errors)
    if (empty($newPasswordErr) && empty($confirmPasswordErr)) {
        // Prepare an update statement
        $sql = "UPDATE users SET password = ? WHERE id = ?";

        // Assuming $link is a mysqli connection object from config.php
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $paramId = $_SESSION["id"];

            mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $paramId);

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Password updated successfully. Destroy the session and redirect to login page.
                // This forces the user to log in again with their new password.
                session_destroy();
                // Clear session variables
                $_SESSION = [];
                // Destroy the session cookie
                setcookie(session_name(), '', time() - 3600, '/');

                header("location: " . LOGIN_PAGE);
                exit();
            } else {
                // Log the actual database error for debugging.
                error_log("Database error during password update for user ID " . $_SESSION["id"] . ": " . mysqli_error($link));
                $generalError = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        } else {
            // Log the error if the statement preparation fails.
            error_log("Failed to prepare update statement: " . mysqli_error($link));
            $generalError = "Oops! An internal error occurred. Please try again later.";
        }
    }

    // Close the database connection after all operations are complete for the request.
    // This is important if $link is a persistent connection from config.php.
    if (isset($link) && is_object($link)) { // Ensure $link is set and is an object before closing
        mysqli_close($link);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font: 14px sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        .wrapper {
            width: 360px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <h2 class="mb-3">Reset Password</h2>
        <p class="mb-4">Please fill out this form to reset your password.</p>

        <?php if (!empty($generalError)): ?>
            <div class="alert alert-danger">
                <?php echo $generalError; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password"
                    class="form-control <?php echo (!empty($newPasswordErr)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $newPasswordErr; ?></span>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password"
                    class="form-control <?php echo (!empty($confirmPasswordErr)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $confirmPasswordErr; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
                <a class="btn btn-link ml-2" href="<?php echo WELCOME_PAGE; ?>">Cancel</a>
            </div>
        </form>
    </div>
</body>

</html>