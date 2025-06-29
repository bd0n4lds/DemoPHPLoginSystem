<?php

/**
 * login.php
 * Handles user login, session management, and form validation.
 */

// Start the session
session_start();

// Redirect logged-in users to the welcome page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: welcome.php");
    exit;
}

// Include database configuration
require_once "config.php";

// Initialize variables for form data and error messages
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize and validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter your username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Sanitize and validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Attempt to validate credentials if no input errors
    if (empty($username_err) && empty($password_err)) {
        // Prepare a SELECT statement to fetch user data
        $sql = "SELECT id, username, password FROM users WHERE username = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind parameters to the prepared statement
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;

            // Execute the statement
            if (mysqli_stmt_execute($stmt)) {
                // Store result and check for existing username
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) === 1) {
                    // Bind result variables and fetch data
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);

                    if (mysqli_stmt_fetch($stmt)) {
                        // Verify password
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct; start a new session (redundant if already started, but good for clarity)
                            // session_start(); // Already called at the top of the file

                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;

                            // Redirect to welcome page
                            header("location: welcome.php");
                            exit; // Always exit after a header redirect
                        } else {
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    $login_err = "Invalid username or password.";
                }
            } else {
                // Handle statement execution error
                error_log("Database error during login: " . mysqli_error($link));
                $login_err = "Oops! Something went wrong. Please try again later.";
            }

            // Close the statement
            mysqli_stmt_close($stmt);
        } else {
            // Handle prepare statement error
            error_log("Database error preparing statement: " . mysqli_error($link));
            $login_err = "Oops! Something went wrong. Please try again later.";
        }
    }

    // Close database connection after all operations
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font: 14px sans-serif;
        }

        .wrapper {
            width: 360px;
            padding: 20px;
            margin: 50px auto;
            /* Center the wrapper */
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <h2>Login</h2>
        <p>Please fill in your credentials to login.</p>

        <?php if (!empty($login_err)): ?>
            <div class="alert alert-danger">
                <?php echo $login_err; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username"
                    class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>"
                    value="<?php echo htmlspecialchars($username); ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password"
                    class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login">
            </div>
            <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
        </form>
    </div>
</body>

</html>