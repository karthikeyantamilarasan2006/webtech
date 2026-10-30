<?php
/**
 * Feedback Submission Processing Controller
 * Project: Student Feedback and Course Evaluation System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_student_login();
$student = get_current_student();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

// CSRF Protection
validate_csrf_request();

$courseId     = (int)($_POST['course_id'] ?? 0);
$facultyId    = (int)($_POST['faculty_id'] ?? 0);
$departmentId = (int)($_POST['department_id'] ?? $student['department_id']);
$semester     = (int)($_POST['semester'] ?? $student['semester']);
$academicYear = trim($_POST['academic_year'] ?? $student['academic_year']);
$isAnonymous  = isset($_POST['is_anonymous']) && $_POST['is_anonymous'] == '1' ? 1 : 0;
$comments     = trim($_POST['comments'] ?? '');

// Limit comment length
if (strlen($comments) > 500) {
    $comments = substr($comments, 0, 500);
}

// 7 Criteria Ratings Validation
$ratingKeys = [
    'teaching_effectiveness',
    'subject_knowledge',
    'communication_skills',
    'course_content_quality',
    'classroom_interaction',
    'doubt_clarification',
    'overall_satisfaction'
];

$ratings = [];
$sum = 0;
$hasErrors = false;

foreach ($ratingKeys as $key) {
    $val = (int)($_POST[$key] ?? 0);
    if ($val < 1 || $val > 5) {
        $hasErrors = true;
        break;
    }
    $ratings[$key] = $val;
    $sum += $val;
}

if ($hasErrors || $courseId <= 0 || $facultyId <= 0) {
    $_SESSION['flash_error'] = "Invalid evaluation data. Please ensure all 7 criteria are given a 1-5 star rating.";
    header("Location: feedback_form.php?course_id={$courseId}&faculty_id={$facultyId}");
    exit;
}

$averageRating = round($sum / count($ratingKeys), 2);

try {
    // 1. Check for Duplicate Submission
    $checkStmt = $pdo->prepare("
        SELECT submission_id FROM feedback_submissions 
        WHERE student_id = ? AND course_id = ? AND faculty_id = ? AND academic_year = ? AND semester = ?
    ");
    $checkStmt->execute([$student['student_id'], $courseId, $facultyId, $academicYear, $semester]);
    if ($checkStmt->fetch()) {
        $_SESSION['flash_error'] = "You have already completed feedback for this course and faculty member.";
        header("Location: dashboard.php");
        exit;
    }

    // 2. Begin Atomic Database Transaction
    $pdo->beginTransaction();

    // A. Insert into Submission Tracking Receipt
    $subStmt = $pdo->prepare("
        INSERT INTO feedback_submissions 
            (student_id, course_id, faculty_id, academic_year, semester, is_anonymous, submitted_at)
        VALUES 
            (?, ?, ?, ?, ?, ?, NOW())
    ");
    $subStmt->execute([
        $student['student_id'],
        $courseId,
        $facultyId,
        $academicYear,
        $semester,
        $isAnonymous
    ]);

    // B. Insert into Evaluation Records (Decouple student_id if anonymous)
    $evalStudentId = ($isAnonymous === 1) ? null : $student['student_id'];

    $feedStmt = $pdo->prepare("
        INSERT INTO feedback (
            student_id, course_id, faculty_id, department_id, academic_year, semester, is_anonymous,
            teaching_effectiveness, subject_knowledge, communication_skills, 
            course_content_quality, classroom_interaction, doubt_clarification, 
            overall_satisfaction, average_rating, comments, submitted_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, NOW()
        )
    ");
    $feedStmt->execute([
        $evalStudentId,
        $courseId,
        $facultyId,
        $departmentId,
        $academicYear,
        $semester,
        $isAnonymous,
        $ratings['teaching_effectiveness'],
        $ratings['subject_knowledge'],
        $ratings['communication_skills'],
        $ratings['course_content_quality'],
        $ratings['classroom_interaction'],
        $ratings['doubt_clarification'],
        $ratings['overall_satisfaction'],
        $averageRating,
        $comments
    ]);

    $pdo->commit();

    // Fetch Course & Faculty name for the receipt display
    $courseInfo = $pdo->prepare("SELECT course_code, course_name FROM courses WHERE course_id = ?");
    $courseInfo->execute([$courseId]);
    $courseRow = $courseInfo->fetch();

    $facultyInfo = $pdo->prepare("SELECT full_name FROM faculty WHERE faculty_id = ?");
    $facultyInfo->execute([$facultyId]);
    $facultyRow = $facultyInfo->fetch();

    // Save receipt details in session
    $_SESSION['feedback_receipt'] = [
        'course_code'    => $courseRow['course_code'] ?? 'N/A',
        'course_name'    => $courseRow['course_name'] ?? 'N/A',
        'faculty_name'   => $facultyRow['full_name'] ?? 'N/A',
        'average_rating' => $averageRating,
        'is_anonymous'   => $isAnonymous,
        'timestamp'      => date('Y-m-d H:i:s')
    ];

    header("Location: feedback_success.php");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = "Evaluation could not be processed due to a database error: " . $e->getMessage();
    header("Location: dashboard.php");
    exit;
}

