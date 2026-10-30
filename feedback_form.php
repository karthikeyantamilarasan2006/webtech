<?php
/**
 * Student Feedback Evaluation Form
 * Project: Student Feedback and Course Evaluation System
 */

$pageTitle = 'Submit Course Evaluation - Student Portal';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_student_login();
$student = get_current_student();

$preselectedCourseId = (int)($_GET['course_id'] ?? 0);
$preselectedFacultyId = (int)($_GET['faculty_id'] ?? 0);

// Fetch department details
$deptStmt = $pdo->prepare("SELECT department_name, department_code FROM departments WHERE department_id = ?");
$deptStmt->execute([$student['department_id']]);
$deptInfo = $deptStmt->fetch() ?: ['department_name' => 'General', 'department_code' => 'GEN'];

// Fetch enrolled courses for this student's department and semester
$coursesStmt = $pdo->prepare("
    SELECT c.course_id, c.course_code, c.course_name, c.credits
    FROM courses c
    WHERE c.department_id = ? AND c.semester = ?
    ORDER BY c.course_code ASC
");
$coursesStmt->execute([$student['department_id'], $student['semester']]);
$courses = $coursesStmt->fetchAll();

// Check if preselected course + faculty is already submitted by this student
$alreadySubmitted = false;
$submissionDetails = null;

if ($preselectedCourseId > 0 && $preselectedFacultyId > 0) {
    $checkStmt = $pdo->prepare("
        SELECT fs.*, c.course_name, c.course_code, f.full_name AS faculty_name
        FROM feedback_submissions fs
        JOIN courses c ON fs.course_id = c.course_id
        JOIN faculty f ON fs.faculty_id = f.faculty_id
        WHERE fs.student_id = ? AND fs.course_id = ? AND fs.faculty_id = ? AND fs.semester = ?
    ");
    $checkStmt->execute([$student['student_id'], $preselectedCourseId, $preselectedFacultyId, $student['semester']]);
    $submissionDetails = $checkStmt->fetch();
    if ($submissionDetails) {
        $alreadySubmitted = true;
    }
}

// 7 Evaluation Criteria configuration
$criteria = [
    [
        'id' => 'teaching_effectiveness',
        'title' => '1. Teaching Effectiveness & Methodology',
        'desc' => 'Instructor explains difficult concepts clearly, uses effective teaching methods, and organizes lectures logically.'
    ],
    [
        'id' => 'subject_knowledge',
        'title' => '2. Subject Knowledge & Expertise',
        'desc' => 'Instructor demonstrates comprehensive mastery of the subject matter and provides insightful examples.'
    ],
    [
        'id' => 'communication_skills',
        'title' => '3. Communication Skills & Clarity',
        'desc' => 'Instructor speaks clearly, maintains an audible tone, and communicates complex ideas effectively.'
    ],
    [
        'id' => 'course_content_quality',
        'title' => '4. Course Content & Syllabus Coverage',
        'desc' => 'The syllabus is covered thoroughly, pacing is appropriate, and practical applications are addressed.'
    ],
    [
        'id' => 'classroom_interaction',
        'title' => '5. Classroom Interaction & Engagement',
        'desc' => 'Instructor encourages student participation, questions, and active discussions during class.'
    ],
    [
        'id' => 'doubt_clarification',
        'title' => '6. Doubt Clarification & Helpfulness',
        'desc' => 'Instructor is accessible for doubts, patient in providing explanations, and receptive to questions.'
    ],
    [
        'id' => 'overall_satisfaction',
        'title' => '7. Overall Teaching & Course Satisfaction',
        'desc' => 'Overall rating of the learning experience and the educational value gained from this course and faculty.'
    ]
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-5 bg-light">
    <div class="container" style="max-width: 900px;">
        <!-- Page Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Course Evaluation</li>
                    </ol>
                </nav>
                <h3 class="fw-bold text-navy mb-0">Course & Faculty Evaluation Form</h3>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if ($alreadySubmitted && $submissionDetails): ?>
            <!-- Already Submitted Notice -->
            <div class="card card-custom border-0 shadow-sm p-4 p-md-5 text-center bg-white">
                <div class="mb-3">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3.5rem;"></i>
                </div>
                <h4 class="fw-bold text-navy mb-2">Evaluation Already Completed</h4>
                <p class="text-muted mb-4" style="max-width: 600px; margin: auto;">
                    You have already submitted an evaluation for <strong><?= htmlspecialchars($submissionDetails['course_code'] . ' - ' . $submissionDetails['course_name']); ?></strong> taught by <strong><?= htmlspecialchars($submissionDetails['faculty_name']); ?></strong>.
                </p>
                <div class="p-3 bg-light rounded-3 d-inline-block text-start mb-4 border" style="max-width: 450px; margin: auto;">
                    <div class="small mb-1"><strong>Submission Date:</strong> <?= date('F d, Y h:i A', strtotime($submissionDetails['submitted_at'])); ?></div>
                    <div class="small mb-1"><strong>Privacy Setting:</strong> <?= $submissionDetails['is_anonymous'] ? '<span class="badge bg-dark">Anonymous Feedback</span>' : '<span class="badge bg-primary">Identified Feedback</span>'; ?></div>
                    <div class="small text-muted fst-italic">Duplicate submissions are prevented to ensure objective appraisal integrity.</div>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-navy px-4">
                        <i class="bi bi-speedometer2 me-1"></i> Return to Student Dashboard
                    </a>
                </div>
            </div>
        <?php else: ?>

            <!-- Real-time Sticky Score Card -->
            <div class="card border-0 shadow-sm mb-4 bg-white sticky-top py-3 px-4 rounded-3" style="top: 75px; z-index: 1020;">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div class="d-flex align-items-center mb-2 mb-md-0">
                        <i class="bi bi-speedometer text-primary fs-3 me-3"></i>
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Live Average Score</span>
                            <div class="d-flex align-items-baseline">
                                <h3 class="fw-extrabold text-navy mb-0 me-2" id="liveAverageScore">0.00</h3>
                                <span class="text-muted small">/ 5.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-md-end">
                        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold" id="liveEvaluationProgress">
                            0 of <?= count($criteria); ?> criteria rated
                        </span>
                    </div>
                </div>
            </div>

            <!-- Form Container -->
            <div class="card card-custom border-0 shadow-sm overflow-hidden mb-4">
                <div class="p-4 bg-navy-gradient text-white">
                    <h5 class="fw-bold mb-1"><i class="bi bi-mortarboard me-2"></i>Academic Information</h5>
                    <p class="text-light opacity-75 small mb-0">Select the course subject and faculty member you are evaluating.</p>
                </div>

                <div class="p-4 p-md-4">
                    <form id="feedbackEvaluationForm" method="POST" action="submit_feedback.php">
                        <?= csrf_field(); ?>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="department_name_display" class="form-label fw-semibold">Department</label>
                                <input type="text" class="form-control bg-light" id="department_name_display" value="<?= htmlspecialchars($deptInfo['department_name'] . ' (' . $deptInfo['department_code'] . ')'); ?>" readonly>
                                <input type="hidden" name="department_id" value="<?= $student['department_id']; ?>">
                            </div>

                            <div class="col-md-3">
                                <label for="semester_display" class="form-label fw-semibold">Semester</label>
                                <input type="text" class="form-control bg-light" id="semester_display" value="Semester <?= $student['semester']; ?>" readonly>
                                <input type="hidden" name="semester" value="<?= $student['semester']; ?>">
                            </div>

                            <div class="col-md-3">
                                <label for="academic_year_display" class="form-label fw-semibold">Academic Year</label>
                                <input type="text" class="form-control bg-light" id="academic_year_display" value="<?= htmlspecialchars($student['academic_year']); ?>" readonly>
                                <input type="hidden" name="academic_year" value="<?= htmlspecialchars($student['academic_year']); ?>">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="course_id" class="form-label fw-semibold">Select Course / Subject <span class="text-danger">*</span></label>
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="">-- Choose Course --</option>
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?= $c['course_id']; ?>" <?= ($preselectedCourseId === (int)$c['course_id']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($c['course_code'] . ' - ' . $c['course_name'] . ' (' . $c['credits'] . ' Credits)'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="faculty_id" class="form-label fw-semibold">Assigned Faculty Member <span class="text-danger">*</span></label>
                                <select class="form-select" id="faculty_id" name="faculty_id" data-selected="<?= $preselectedFacultyId; ?>" required>
                                    <option value="">-- First Select Course --</option>
                                </select>
                            </div>
                        </div>

                        <!-- 7 Evaluation Criteria Cards -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-star-fill text-warning fs-5 me-2"></i>
                                <h5 class="fw-bold text-navy mb-0">Teaching Performance Parameters (1–5 Star Rating)</h5>
                            </div>
                            <p class="text-muted small mb-4">
                                <strong>Scale:</strong> 1 = Poor &nbsp;|&nbsp; 2 = Fair &nbsp;|&nbsp; 3 = Average &nbsp;|&nbsp; 4 = Good &nbsp;|&nbsp; 5 = Excellent
                            </p>

                            <?php foreach ($criteria as $index => $item): ?>
                                <div class="card border mb-3 rounded-3 shadow-none p-3">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                                        <div>
                                            <h6 class="fw-bold text-navy mb-1"><?= htmlspecialchars($item['title']); ?></h6>
                                            <p class="text-muted small mb-0"><?= htmlspecialchars($item['desc']); ?></p>
                                        </div>
                                        <div class="mt-2 mt-md-0">
                                            <span id="badge_<?= $item['id']; ?>" class="badge rating-badge bg-secondary-subtle text-secondary">Not Rated</span>
                                        </div>
                                    </div>

                                    <!-- 5-Star Radio Component -->
                                    <div class="star-rating-group">
                                        <input type="radio" id="<?= $item['id']; ?>_5" name="<?= $item['id']; ?>" value="5" required>
                                        <label for="<?= $item['id']; ?>_5" title="5 - Excellent">&#9733;</label>

                                        <input type="radio" id="<?= $item['id']; ?>_4" name="<?= $item['id']; ?>" value="4">
                                        <label for="<?= $item['id']; ?>_4" title="4 - Good">&#9733;</label>

                                        <input type="radio" id="<?= $item['id']; ?>_3" name="<?= $item['id']; ?>" value="3">
                                        <label for="<?= $item['id']; ?>_3" title="3 - Average">&#9733;</label>

                                        <input type="radio" id="<?= $item['id']; ?>_2" name="<?= $item['id']; ?>" value="2">
                                        <label for="<?= $item['id']; ?>_2" title="2 - Fair">&#9733;</label>

                                        <input type="radio" id="<?= $item['id']; ?>_1" name="<?= $item['id']; ?>" value="1">
                                        <label for="<?= $item['id']; ?>_1" title="1 - Poor">&#9733;</label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Constructive Comments -->
                        <div class="mb-4">
                            <label for="comments" class="form-label fw-semibold">
                                <i class="bi bi-chat-square-text me-1 text-primary"></i> Constructive Suggestions & Feedback (Optional)
                            </label>
                            <textarea class="form-control" id="comments" name="comments" rows="4" maxlength="500" data-counter="commentsCount" placeholder="Mention specific teaching strengths or areas where syllabus delivery, lab sessions, or doubt solving could be enhanced..."></textarea>
                            <div class="d-flex justify-content-end mt-1">
                                <span id="commentsCount" class="text-muted small">0 / 500 characters</span>
                            </div>
                        </div>

                        <!-- Anonymous Feedback Toggle -->
                        <div class="card bg-light border-primary border-opacity-25 p-3 mb-4 rounded-3">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_anonymous" name="is_anonymous" value="1" checked>
                                <label class="form-check-label fw-bold text-navy" for="is_anonymous">
                                    <i class="bi bi-incognito text-dark me-1"></i> Submit as Anonymous Feedback
                                </label>
                            </div>
                            <small class="text-muted d-block ps-4">
                                When enabled, your identity (Name and Roll Number) is permanently detached from this evaluation in the database and reports. A secure receipt is retained solely to confirm completion and prevent duplicate entries.
                            </small>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                            <a href="dashboard.php" class="btn btn-outline-secondary px-4">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-navy px-5 py-2 fs-6 shadow-sm">
                                <i class="bi bi-send-check-fill me-2"></i> Submit Course Evaluation
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        <?php endif; ?>
    </div>
</main>

<!-- Inclusions for Form Interactivity -->
<script src="../assets/js/student_feedback.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

