<?php
/**
 * CSRF (Cross-Site Request Forgery) Protection Helper
 * Project: Student Feedback and Course Evaluation System
 */

// Ensure PHP session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate or retrieve existing CSRF token for the session
 *
 * @return string Secure random hex token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate hidden input field containing CSRF token
 *
 * @return string HTML input tag
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate incoming token against session token using timing-safe comparison
 *
 * @param string|null $token
 * @return bool True if valid, false otherwise
 */
function verify_csrf_token($token) {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Enforce CSRF token verification for POST requests.
 * Terminates with an HTTP 403 error message if verification fails.
 */
function validate_csrf_request() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            http_response_code(403);
            die(
                '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 25px; border: 1px solid #f5c2c7; background-color: #f8d7da; border-radius: 8px; color: #842029;">' .
                '<h3 style="margin-top:0;">403 - Invalid or Expired Security Token</h3>' .
                '<p>Your form session has expired or the security token is invalid. Please refresh the page and try submitting again.</p>' .
                '<a href="javascript:history.back()" style="display:inline-block; margin-top:10px; padding:8px 16px; background:#0d6efd; color:#fff; text-decoration:none; border-radius:4px;">Go Back</a>' .
                '</div>'
            );
        }
    }
}
?>

