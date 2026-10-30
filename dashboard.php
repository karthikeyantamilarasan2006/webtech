<?php
/**
 * Student Dashboard
 * Project: Student Feedback and Course Evaluation System
 */

$pageTitle = 'Student Dashboard - Course Evaluation System';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_student_login();
$student = get_current_student();

// Fetch student department name
$deptStmt = $pdo->prepare("SELECT department_name, department_code FROM departments WHERE department_id = ?");
$deptStmt->execute([$student['department_id']]);
$deptInfo = $deptStmt->fetch() ?: ['department_name' => 'General Engineering', 'department_code' => 'GEN'];

// Fetch courses assigned to this student's department and semester
$coursesStmt = $pdo->prepare("
    SELECT c.course_id, c.course_code, c.course_name, c.credits, c.semester,
           f.faculty_id, f.faculty_code, f.full_name AS faculty_name, f.designation,
           fs.submission_id, fs.submitted_at, fs.is_anonymous
    FROM courses c
    LEFT JOIN course_faculty cf ON c.course_id = cf.course_id AND cf.semester = c.semester
    LEFT JOIN faculty f ON cf.faculty_id = f.faculty_id
    LEFT JOIN feedback_submissions fs ON fs.course_id = c.course_id 
                                      AND fs.faculty_id = f.faculty_id 
                                      AND fs.student_id = ? 
                                      AND fs.semester = c.semester
    WHERE c.department_id = ? AND c.semester = ?
    ORDER BY c.course_code ASC
");
$coursesStmt->execute([$student['student_id'], $student['department_id'], $student['semester']]);
$assignedCourses = $coursesStmt->fetchAll();

// Calculate Progress Metrics
$totalAssigned = count($assignedCourses);
$completedCount = 0;
foreach ($assignedCourses as $item) {
    if (!empty($item['submission_id'])) {
        $completedCount++;
    }
}
$pendingCount = $totalAssigned - $completedCount;
$progressPercentage = $totalAssigned > 0 ? round(($completedCount / $totalAssigned) * 100) : 0;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-5 bg-light">
    <div class="container">
        <!-- Welcome Banner -->
        <div class="card card-custom border-0 bg-navy-gradient text-white p-4 p-md-5 mb-4 shadow-sm">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="badge bg-info text-dark px-3 py-1 rounded-pill fw-bold mb-2">Student Portal</span>
                    <h2 class="display-6 fw-bold mb-1 text-white">Welcome, <?= htmlspecialchars($student['full_name']); ?>!</h2>
                    <p class="text-light opacity-75 mb-0 fs-6">
                        <i class="bi bi-card-text me-1"></i> Roll: <strong><?= htmlspecialchars($student['roll_number']); ?></strong> &nbsp;|&nbsp;
                        <i class="bi bi-building me-1"></i> <?= htmlspecialchars($deptInfo['department_name']); ?> (<?= htmlspecialchars($deptInfo['department_code']); ?>) &nbsp;|&nbsp;
                        <i class="bi bi-calendar3 me-1"></i> Semester <?= htmlspecialchars($student['semester']); ?> (<?= htmlspecialchars($student['academic_year']); ?>)
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="feedback_form.php" class="btn btn-warning btn-lg px-4 py-2 rounded-pill fw-semibold shadow-sm text-dark">
                        <i class="bi bi-pencil-square me-1"></i> Start Evaluation
                    </a>
                </div>
            </div>
        </div>

        <!-- Metrics Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card card-stat bg-white p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Assigned Subjects</span>
                            <h3 class="fw-bold mb-0 text-navy mt-1"><?= $totalAssigned; ?></h3>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-journal-text"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-stat bg-white p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Completed Feedback</span>
                            <h3 class="fw-bold mb-0 text-success mt-1"><?= $completedCount; ?></h3>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-stat bg-white p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Pending Evaluations</span>
                            <h3 class="fw-bold mb-0 text-warning mt-1"><?= $pendingCount; ?></h3>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Evaluation Progress Card -->
        <div class="card card-custom border-0 p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-navy mb-0"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Evaluation Progress for Current Term</h6>
                <span class="badge bg-primary px-3 py-1 rounded-pill"><?= $progressPercentage; ?>% Completed</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= $progressPercentage; ?>%" aria-valuenow="<?= $progressPercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>

        <!-- Assigned Courses Table -->
        <div class="card card-custom border-0 overflow-hidden mb-4">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-navy mb-1"><i class="bi bi-card-checklist me-2 text-primary"></i>My Enrolled Courses & Assigned Faculty</h5>
                    <p class="text-muted small mb-0">Evaluate each subject and instructor to help enhance course delivery.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Course Code & Title</th>
                            <th>Assigned Faculty</th>
                            <th class="text-center">Credits</th>
                            <th class="text-center">Evaluation Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assignedCourses)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-folder-x fs-1 d-block mb-2"></i>
                                    No courses are registered for your department and semester yet. Please check back later.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assignedCourses as $c): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-navy"><?= htmlspecialchars($c['course_name']); ?></div>
                                        <span class="badge bg-light text-primary border"><?= htmlspecialchars($c['course_code']); ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['faculty_name'])): ?>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($c['faculty_name']); ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($c['designation']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">Faculty assignment pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-dark"><?= $c['credits']; ?> Credits</span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($c['submission_id'])): ?>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
                                                <i class="bi bi-check-circle-fill me-1"></i>Submitted
                                            </span>
                                            <div class="text-muted small mt-1" style="font-size:0.75rem;">
                                                <?= date('M d, Y', strtotime($c['submitted_at'])); ?>
                                                <?= ($c['is_anonymous']) ? '<span class="badge bg-dark-subtle text-dark ms-1">Anon</span>' : ''; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2 rounded-pill">
                                                <i class="bi bi-hourglass-split me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php if (empty($c['submission_id'])): ?>
                                            <?php if (!empty($c['faculty_id'])): ?>
                                                <a href="feedback_form.php?course_id=<?= $c['course_id']; ?>&faculty_id=<?= $c['faculty_id']; ?>" class="btn btn-navy btn-sm px-3 py-1">
                                                    <i class="bi bi-pencil me-1"></i>Evaluate
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm px-3 py-1" disabled>Awaiting Faculty</button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button class="btn btn-outline-success btn-sm px-3 py-1" disabled>
                                                <i class="bi bi-check2-all me-1"></i>Completed
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

