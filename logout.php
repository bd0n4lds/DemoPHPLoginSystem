<?php
// Ensure the session is started before trying to manipulate it.
// This is crucial even for destroying a session.
session_start();

// Define a constant for the login page URL.
// This makes it easy to change the redirect location if your login page path ever changes.
const LOGIN_PAGE_URL = 'login.php';

// Unset all of the session variables.
// This clears the data in the current session array.
$_SESSION = []; // Using [] is a more modern and slightly cleaner way to create an empty array than array().

// Destroy the session on the server.
// This removes the session file or entry from the session storage.
session_destroy();

// Clear the session cookie from the client's browser.
// This is a crucial step for a complete logout, as the browser might still hold the old session ID.
// It ensures that even if a new session starts, it won't mistakenly pick up the old session ID.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), // The name of the session cookie (e.g., PHPSESSID)
        '',             // Set its value to empty
        time() - 42000, // Set its expiration to a time in the past
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Redirect the user to the login page after a successful logout.
header('Location: ' . LOGIN_PAGE_URL);
exit(); // Always call exit after a header redirect to ensure no further code is executed.
?>