<?php
// auth_check.php - Session Authentication Guard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect unauthorized users to login page
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['error'] = "Access denied. Please log in first.";
    header("Location: login.php");
    exit();
}
?>
