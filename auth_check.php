<?php
/**
 * Role-Based Access Control & Session Guard
 * Project: Student Feedback and Course Evaluation System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the currently authenticated user is a student
 *
 * @return bool
 */
function is_student_logged_in() {
    return isset($_SESSION['student_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student';
}

/**
 * Check if the currently authenticated user is an administrator
 *
 * @return bool
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'superadmin', 'coordinator']);
}

/**
 * Guard for Student Pages: Redirect to student login if not authenticated
 */
function require_student_login() {
    if (!is_student_logged_in()) {
        $_SESSION['flash_error'] = "Please log in with your student credentials to access this page.";
        
        // Determine proper relative redirect path based on directory depth
        $isSubdir = strpos($_SERVER['SCRIPT_NAME'], '/student/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
        $redirectUrl = ($isSubdir ? '../' : '') . 'auth/student_login.php';
        
        header("Location: " . $redirectUrl);
        exit;
    }
}

/**
 * Guard for Administrator Pages: Redirect to admin login if not authenticated
 */
function require_admin_login() {
    if (!is_admin_logged_in()) {
        $_SESSION['flash_error'] = "Administrative authorization required. Please log in.";
        
        $isSubdir = strpos($_SERVER['SCRIPT_NAME'], '/student/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
        $redirectUrl = ($isSubdir ? '../' : '') . 'auth/admin_login.php';
        
        header("Location: " . $redirectUrl);
        exit;
    }
}

/**
 * Get logged in student details from session
 *
 * @return array
 */
function get_current_student() {
    return [
        'student_id'   => $_SESSION['student_id'] ?? null,
        'roll_number'  => $_SESSION['roll_number'] ?? '',
        'full_name'    => $_SESSION['full_name'] ?? '',
        'email'        => $_SESSION['email'] ?? '',
        'department_id'=> $_SESSION['department_id'] ?? null,
        'semester'     => $_SESSION['semester'] ?? null,
        'academic_year'=> $_SESSION['academic_year'] ?? '2025-2026'
    ];
}

/**
 * Get logged in admin details from session
 *
 * @return array
 */
function get_current_admin() {
    return [
        'admin_id'  => $_SESSION['admin_id'] ?? null,
        'username'  => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['admin_name'] ?? 'Administrator',
        'role'      => $_SESSION['admin_role'] ?? 'superadmin'
    ];
}
?>

