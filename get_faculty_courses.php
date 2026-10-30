<?php
/**
 * AJAX Endpoint: Dynamic Course & Faculty Fetcher
 * Project: Student Feedback and Course Evaluation System
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Allow access to authenticated students and admins
if (!is_student_logged_in() && !is_admin_logged_in()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    // 1. Get faculty assigned to a specific course
    if ($action === 'get_faculty_by_course') {
        $courseId = (int)($_GET['course_id'] ?? 0);
        if ($courseId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid Course ID']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT f.faculty_id, f.faculty_code, f.full_name, f.designation
            FROM faculty f
            JOIN course_faculty cf ON f.faculty_id = cf.faculty_id
            WHERE cf.course_id = ?
        ");
        $stmt->execute([$courseId]);
        $facultyList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'faculty' => $facultyList]);
        exit;
    }

    // 2. Get courses by department and semester
    if ($action === 'get_courses_by_dept_sem') {
        $deptId = (int)($_GET['department_id'] ?? 0);
        $semester = (int)($_GET['semester'] ?? 0);

        if ($deptId <= 0 || $semester <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT course_id, course_code, course_name, credits
            FROM courses
            WHERE department_id = ? AND semester = ?
            ORDER BY course_code ASC
        ");
        $stmt->execute([$deptId, $semester]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'courses' => $courses]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database query error: ' . $e->getMessage()]);
}

