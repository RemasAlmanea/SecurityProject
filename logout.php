<?php
session_start();

//  Clear all session variables
$_SESSION = [];

// Delete session cookie from browser (for extra security)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

//  Destroy the session on the server
session_destroy();

//  Redirect to login page
header("Location: login.php");
exit();
?>

