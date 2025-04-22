<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_logged_in']);

// Optional: destroy the session completely
// session_destroy();

// Set logout message
$_SESSION['login_message'] = "You have been logged out successfully.";

// Redirect to login page
header("Location: login.php");
exit();
?>