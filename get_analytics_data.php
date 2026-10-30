<?php
/**
 * AJAX Analytics Data Provider for Chart.js
 * Project: Student Feedback and Course Evaluation System
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin_login();

$filterDept    = (int)($_GET['department_id'] ?? 0);
$filterCourse  = (int)($_GET['course_id'] ?? 0);
$filterFaculty = (int)($_GET['faculty_id'] ?? 0);
$filterSem     = (int)($_GET['semester'] ?? 0);

// Base condition & parameter array
$where = " WHERE 1=1";
$params = [];

if ($filterDept > 0) {
    $where .= " AND fb.department_id = ?";
    $params[] = $filterDept;
}
if ($filterCourse > 0) {
    $where .= " AND fb.course_id = ?";
    $params[] = $filterCourse;
}
if ($filterFaculty > 0) {
    $where .= " AND fb.faculty_id = ?";
    $params[] = $filterFaculty;
}
if ($filterSem > 0) {
    $where .= " AND fb.semester = ?";
    $params[] = $filterSem;
}

try {
    // 1. Faculty Performance Ratings (Bar Chart)
    $facultySql = "
        SELECT f.full_name, AVG(fb.average_rating) as avg_score, COUNT(fb.feedback_id) as total_reviews
        FROM feedback fb
        JOIN faculty f ON fb.faculty_id = f.faculty_id
        {$where}
        GROUP BY f.faculty_id
        ORDER BY avg_score DESC
        LIMIT 10
    ";
    $stmt = $pdo->prepare($facultySql);
    $stmt->execute($params);
    $facultyRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $facultyLabels = [];
    $facultyScores = [];
    foreach ($facultyRows as $row) {
        $facultyLabels[] = $row['full_name'];
        $facultyScores[] = round((float)$row['avg_score'], 2);
    }

    // 2. Student Satisfaction Distribution (Pie Chart)
    $distSql = "
        SELECT 
            SUM(CASE WHEN average_rating >= 4.5 THEN 1 ELSE 0 END) AS excellent_count,
            SUM(CASE WHEN average_rating >= 3.5 AND average_rating < 4.5 THEN 1 ELSE 0 END) AS good_count,
            SUM(CASE WHEN average_rating >= 2.5 AND average_rating < 3.5 THEN 1 ELSE 0 END) AS average_count,
            SUM(CASE WHEN average_rating < 2.5 THEN 1 ELSE 0 END) AS poor_count
        FROM feedback fb
        {$where}
    ";
    $stmt = $pdo->prepare($distSql);
    $stmt->execute($params);
    $distRow = $stmt->fetch(PDO::FETCH_ASSOC);

    $satisfactionData = [
        'labels' => ['Excellent (4.5 - 5.0)', 'Good (3.5 - 4.4)', 'Average (2.5 - 3.4)', 'Needs Review (< 2.5)'],
        'counts' => [
            (int)($distRow['excellent_count'] ?? 0),
            (int)($distRow['good_count'] ?? 0),
            (int)($distRow['average_count'] ?? 0),
            (int)($distRow['poor_count'] ?? 0)
        ]
    ];

    // 3. Category-wise Criteria Averages (Doughnut Chart)
    $catSql = "
        SELECT 
            AVG(teaching_effectiveness) AS avg_te,
            AVG(subject_knowledge) AS avg_sk,
            AVG(communication_skills) AS avg_cs,
            AVG(course_content_quality) AS avg_cc,
            AVG(classroom_interaction) AS avg_ci,
            AVG(doubt_clarification) AS avg_dc,
            AVG(overall_satisfaction) AS avg_os
        FROM feedback fb
        {$where}
    ";
    $stmt = $pdo->prepare($catSql);
    $stmt->execute($params);
    $catRow = $stmt->fetch(PDO::FETCH_ASSOC);

    $categoryData = [
        'labels' => [
            'Teaching Effectiveness',
            'Subject Knowledge',
            'Communication Skills',
            'Course Content',
            'Classroom Interaction',
            'Doubt Clarification',
            'Overall Satisfaction'
        ],
        'scores' => [
            round((float)($catRow['avg_te'] ?? 0), 2),
            round((float)($catRow['avg_sk'] ?? 0), 2),
            round((float)($catRow['avg_cs'] ?? 0), 2),
            round((float)($catRow['avg_cc'] ?? 0), 2),
            round((float)($catRow['avg_ci'] ?? 0), 2),
            round((float)($catRow['avg_dc'] ?? 0), 2),
            round((float)($catRow['avg_os'] ?? 0), 2)
        ]
    ];

    // 4. Feedback Submission Timeline Trend (Line Chart)
    $trendSql = "
        SELECT DATE_FORMAT(fb.submitted_at, '%Y-%m-%d') AS submit_date, COUNT(fb.feedback_id) AS total_daily
        FROM feedback fb
        {$where}
        GROUP BY DATE_FORMAT(fb.submitted_at, '%Y-%m-%d')
        ORDER BY submit_date ASC
        LIMIT 14
    ";
    $stmt = $pdo->prepare($trendSql);
    $stmt->execute($params);
    $trendRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $trendLabels = [];
    $trendCounts = [];
    foreach ($trendRows as $tr) {
        $trendLabels[] = date('M d', strtotime($tr['submit_date']));
        $trendCounts[] = (int)$tr['total_daily'];
    }

    echo json_encode([
        'success' => true,
        'faculty' => [
            'labels' => $facultyLabels,
            'scores' => $facultyScores
        ],
        'satisfaction' => $satisfactionData,
        'categories'   => $categoryData,
        'trend'        => [
            'labels' => $trendLabels,
            'counts' => $trendCounts
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

