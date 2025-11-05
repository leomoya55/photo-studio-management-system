<?php
// session_manager.php - Session management with timeout
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set session timeout (30 minutes of inactivity)
$session_timeout = 30 * 60; // 30 minutes in seconds

// Check if session has expired
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $session_timeout) {
        // Session expired
        session_unset();
        session_destroy();
        session_start();
        $session_expired = true;
    }
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = '';
$userRole = '';

if ($isLoggedIn) {
    $userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $userRole = $_SESSION['role'] ?? 'customer';
}

// If session expired, you can optionally show a message
$sessionExpiredMessage = isset($session_expired) ? $session_expired : false;
?>