<?php

/**
 * register.php
 * Handles user registration, input validation, and database insertion.
 */

// Include the database configuration file.
// It's assumed 'config.php' establishes a PDO connection named $pdo.
require_once "config.php";

// Define constants for configuration.
define('MIN_PASSWORD_LENGTH', 6);
define('LOGIN_PAGE', 'login.php');

// Initialize variables for form data and error messages.
$username = $password = $confirmPassword = '';
$usernameErr = $passwordErr = $confirmPasswordErr = '';

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize and Validate Input

    // Validate username: presence and format
    $username = trim($_POST['username'] ?? ''); // Use null coalescing for safety
    if (empty($username)) {
        $usernameErr = 'Please enter a username.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $usernameErr = 'Username can only contain letters, numbers, and underscores.';
    }

    // Validate password: presence and minimum length
    $password = trim($_POST['password'] ?? '');
    if (empty($password)) {
        $passwordErr = 'Please enter a password.';
    } elseif (strlen($password) < MIN_PASSWORD_LENGTH) {
        $passwordErr = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters.';
    }

    // Validate confirm password: presence and match with password
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    if (empty($confirmPassword)) {
        $confirmPasswordErr = 'Please confirm password.';
    } elseif (empty($passwordErr) && ($password !== $confirmPassword)) { // Only compare if password has no error
        $confirmPasswordErr = 'Password did not match.';
    }

    // 2. Check for Existing Username (Database Interaction)
    // This check runs only if the username passed initial client-side validation.
    if (empty($usernameErr)) {
        try {
            $sql = 'SELECT id FROM users WHERE username = :username';
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);

            if ($stmt->execute()) {
                if ($stmt->rowCount() === 1) {
                    $usernameErr = 'This username is already taken.';
                }
            } else {
                // Log the actual PDO error for debugging purposes.
                error_log('Database query failed during username check: ' . implode(' ', $stmt->errorInfo()));
                // Display a generic error to the user to avoid exposing sensitive info.
                // It's usually better to set a general error message variable here,
                // and display it in the HTML, similar to $login_err in your login page.
                // For simplicity in this refactor, we'll keep the echo for fatal DB errors.
                echo 'Oops! Something went wrong. Please try again later.';
            }
        } catch (PDOException $e) {
            error_log('PDO Exception during username check: ' . $e->getMessage());
            echo 'Oops! A database error occurred. Please try again later.';
        }
    }

    // 3. Insert User into Database (if all validations pass)
    if (empty($usernameErr) && empty($passwordErr) && empty($confirmPasswordErr)) {
        try {
            $sql = 'INSERT INTO users (username, password) VALUES (:username, :password)';
            $stmt = $pdo->prepare($sql);

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT); // Hash the password securely

            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);

            if ($stmt->execute()) {
                // Registration successful, redirect to login page.
                header('Location: ' . LOGIN_PAGE);
                exit(); // Always exit after a header redirect to prevent further script execution.
            } else {
                error_log('Database insert failed: ' . implode(' ', $stmt->errorInfo()));
                echo 'Oops! Something went wrong with registration. Please try again later.';
            }
        } catch (PDOException $e) {
            error_log('PDO Exception during user insertion: ' . $e->getMessage());
            echo 'Oops! A database error occurred during registration. Please try again later.';
        }
    }
}
// Note: PDO connections are typically kept open until the script finishes or explicitly closed.
// No need for a mysqli_close here since we're assuming PDO.
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font: 14px sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            /* Make body take full viewport height */
            background-color: #f8f9fa;
            /* Light background */
        }

        .wrapper {
            width: 360px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            /* Subtle shadow */
        }

        .form-group {
            margin-bottom: 1rem;
            /* Consistent spacing */
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <h2 class="mb-3">Sign Up</h2>
        <p class="mb-4">Please fill this form to create an account.</p>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" novalidate>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                    class="form-control <?php echo (!empty($usernameErr)) ? 'is-invalid' : ''; ?>"
                    value="<?php echo htmlspecialchars($username); ?>">
                <span class="invalid-feedback"><?php echo $usernameErr; ?></span>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                    class="form-control <?php echo (!empty($passwordErr)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $passwordErr; ?></span>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password"
                    class="form-control <?php echo (!empty($confirmPasswordErr)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $confirmPasswordErr; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
                <input type="reset" class="btn btn-secondary ml-2" value="Reset">
            </div>
            <p class="mt-4">Already have an account? <a href="login.php">Login here</a>.</p>
        </form>
    </div>
</body>

</html>