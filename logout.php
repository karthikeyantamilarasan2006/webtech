<?php
/**
 * Session Termination & Logout Handler
 * Project: Student Feedback and Course Evaluation System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$wasAdmin = isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'superadmin', 'coordinator']);

// Unset all session variables
$_SESSION = [];

// Delete the session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start new session to deliver friendly flash message
session_start();
$_SESSION['flash_success'] = "You have been logged out securely. Thank you!";

if ($wasAdmin) {
    header("Location: admin_login.php");
} else {
    header("Location: student_login.php");
}
exit;

