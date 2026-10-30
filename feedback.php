<?php
/**
 * Feedback Records & Appraisal Inspection
 * Project: Student Feedback and Course Evaluation System
 */

$pageTitle = 'Feedback Inspection - Admin Console';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin_login();

$errors = [];
$success = '';

// Handle Controlled Deletion of Feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_feedback') {
    validate_csrf_request();

    $feedbackId = (int)($_POST['feedback_id'] ?? 0);
    if ($feedbackId > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM feedback WHERE feedback_id = ?");
            $stmt->execute([$feedbackId]);
            $success = "Evaluation record #{$feedbackId} was removed.";
        } catch (Exception $e) {
            $errors[] = "Could not delete feedback: " . $e->getMessage();
        }
    }
}

// Filters
$filterDept    = (int)($_GET['department_id'] ?? 0);
$filterCourse  = (int)($_GET['course_id'] ?? 0);
$filterFaculty = (int)($_GET['faculty_id'] ?? 0);
$filterSem     = (int)($_GET['semester'] ?? 0);
$filterAnon    = $_GET['anonymity'] ?? 'all';

// Fetch filter options
$departments = $pdo->query("SELECT department_id, department_code, department_name FROM departments ORDER BY department_name ASC")->fetchAll();
$courses     = $pdo->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_code ASC")->fetchAll();
$facultyList = $pdo->query("SELECT faculty_id, full_name, faculty_code FROM faculty ORDER BY full_name ASC")->fetchAll();

// Build query
$sql = "
    SELECT fb.*,
           c.course_code, c.course_name,
           f.full_name AS faculty_name, f.faculty_code,
           d.department_code, d.department_name,
           s.full_name AS student_name, s.roll_number
    FROM feedback fb
    JOIN courses c ON fb.course_id = c.course_id
    JOIN faculty f ON fb.faculty_id = f.faculty_id
    JOIN departments d ON fb.department_id = d.department_id
    LEFT JOIN students s ON fb.student_id = s.student_id
    WHERE 1=1
";
$params = [];

if ($filterDept > 0) {
    $sql .= " AND fb.department_id = ?";
    $params[] = $filterDept;
}
if ($filterCourse > 0) {
    $sql .= " AND fb.course_id = ?";
    $params[] = $filterCourse;
}
if ($filterFaculty > 0) {
    $sql .= " AND fb.faculty_id = ?";
    $params[] = $filterFaculty;
}
if ($filterSem > 0) {
    $sql .= " AND fb.semester = ?";
    $params[] = $filterSem;
}
if ($filterAnon === 'anon') {
    $sql .= " AND fb.is_anonymous = 1";
} elseif ($filterAnon === 'identified') {
    $sql .= " AND fb.is_anonymous = 0";
}

$sql .= " ORDER BY fb.submitted_at DESC";
$feedbackStmt = $pdo->prepare($sql);
$feedbackStmt->execute($params);
$feedbackRecords = $feedbackStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-navy mb-1">Feedback & Course Evaluation Records</h3>
                <p class="text-muted small mb-0">Review student appraisals, examine 7-criteria breakdowns, and analyze qualitative commentary.</p>
            </div>
            <div>
                <a href="reports.php" class="btn btn-navy btn-sm px-3 shadow-sm">
                    <i class="bi bi-bar-chart-fill me-1"></i> View Visual Analytics
                </a>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Multi-parameter Filter Form -->
        <div class="card card-custom border-0 p-3 mb-4 bg-white">
            <form method="GET" action="feedback.php" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Department</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="0">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['department_id']; ?>" <?= ($filterDept === (int)$dept['department_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($dept['department_code']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Course</label>
                    <select name="course_id" class="form-select form-select-sm">
                        <option value="0">All Courses</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['course_id']; ?>" <?= ($filterCourse === (int)$c['course_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Faculty</label>
                    <select name="faculty_id" class="form-select form-select-sm">
                        <option value="0">All Faculty</option>
                        <?php foreach ($facultyList as $fac): ?>
                            <option value="<?= $fac['faculty_id']; ?>" <?= ($filterFaculty === (int)$fac['faculty_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($fac['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-1">
                    <label class="form-label small fw-semibold text-muted mb-1">Semester</label>
                    <select name="semester" class="form-select form-select-sm">
                        <option value="0">All</option>
                        <?php for ($s = 1; $s <= 8; $s++): ?>
                            <option value="<?= $s; ?>" <?= ($filterSem === $s) ? 'selected' : ''; ?>>Sem <?= $s; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-navy btn-sm px-3 flex-grow-1">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    <a href="feedback.php" class="btn btn-outline-secondary btn-sm px-2" title="Reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Feedback Entries Table -->
        <div class="card card-custom border-0 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Course Evaluated</th>
                            <th>Faculty Member</th>
                            <th class="text-center">Score</th>
                            <th>Student Evaluator</th>
                            <th>Submission Date</th>
                            <th class="text-end pe-4">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($feedbackRecords)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No evaluation records match your filter criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($feedbackRecords as $fb): ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="text-muted small">#<?= $fb['feedback_id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-navy"><?= htmlspecialchars($fb['course_code']); ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($fb['course_name']); ?> (Sem <?= $fb['semester']; ?>)</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($fb['faculty_name']); ?></div>
                                        <span class="badge bg-light text-primary border"><?= htmlspecialchars($fb['department_code']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark fw-bold px-2 py-1">
                                            &#9733; <?= number_format($fb['average_rating'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($fb['is_anonymous']): ?>
                                            <span class="badge bg-dark px-2 py-1"><i class="bi bi-incognito me-1"></i>Anonymous</span>
                                        <?php else: ?>
                                            <div class="fw-semibold text-dark small"><?= htmlspecialchars($fb['student_name'] ?? 'Student'); ?></div>
                                            <span class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($fb['roll_number'] ?? ''); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?= date('M d, Y h:i A', strtotime($fb['submitted_at'])); ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-outline-primary btn-sm px-2 py-1" data-bs-toggle="modal" data-bs-target="#viewFeedbackModal<?= $fb['feedback_id']; ?>">
                                            <i class="bi bi-eye me-1"></i> View Rubric
                                        </button>

                                        <!-- Controlled Delete -->
                                        <form method="POST" action="feedback.php" class="d-inline" onsubmit="return confirm('Delete evaluation record #<?= $fb['feedback_id']; ?>? This action should only be taken for invalid test records.');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_feedback">
                                            <input type="hidden" name="feedback_id" value="<?= $fb['feedback_id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm px-2 py-1" title="Controlled Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- View Feedback Details Modal -->
                                <div class="modal fade" id="viewFeedbackModal<?= $fb['feedback_id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-navy text-white">
                                                <div>
                                                    <h5 class="modal-title fs-6 fw-bold mb-0">Evaluation Rubric Breakdown #<?= $fb['feedback_id']; ?></h5>
                                                    <small class="text-light opacity-75"><?= htmlspecialchars($fb['course_code'] . ' - ' . $fb['course_name']); ?> &bull; <?= htmlspecialchars($fb['faculty_name']); ?></small>
                                                </div>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded-3">
                                                    <div>
                                                        <span class="text-muted small">Calculated Overall Score:</span>
                                                        <h4 class="fw-bold text-navy mb-0">&#9733; <?= number_format($fb['average_rating'], 2); ?> / 5.00</h4>
                                                    </div>
                                                    <div>
                                                        <?php if ($fb['is_anonymous']): ?>
                                                            <span class="badge bg-dark px-3 py-2"><i class="bi bi-incognito me-1"></i>Anonymous Submission</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-primary px-3 py-2"><i class="bi bi-person-check me-1"></i><?= htmlspecialchars($fb['student_name']); ?> (<?= htmlspecialchars($fb['roll_number']); ?>)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <h6 class="fw-bold text-navy mb-3">7-Criteria Performance Ratings</h6>
                                                <div class="table-responsive mb-4">
                                                    <table class="table table-bordered table-sm align-middle">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Academic Criterion</th>
                                                                <th class="text-center" style="width: 140px;">Rating (1-5)</th>
                                                                <th class="text-center" style="width: 120px;">Performance</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $criteriaBreakdown = [
                                                                'Teaching Effectiveness & Methodology' => $fb['teaching_effectiveness'],
                                                                'Subject Knowledge & Expertise'        => $fb['subject_knowledge'],
                                                                'Communication Skills & Clarity'       => $fb['communication_skills'],
                                                                'Course Content & Syllabus Coverage'   => $fb['course_content_quality'],
                                                                'Classroom Interaction & Engagement'   => $fb['classroom_interaction'],
                                                                'Doubt Clarification & Helpfulness'    => $fb['doubt_clarification'],
                                                                'Overall Teaching Satisfaction'        => $fb['overall_satisfaction']
                                                            ];
                                                            foreach ($criteriaBreakdown as $label => $val):
                                                            ?>
                                                                <tr>
                                                                    <td><?= $label; ?></td>
                                                                    <td class="text-center text-warning fw-bold">
                                                                        <?= str_repeat('&#9733;', $val) . str_repeat('&#9734;', 5 - $val); ?>
                                                                        <span class="text-dark small ms-1">(<?= $val; ?>/5)</span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <?php if ($val >= 4): ?>
                                                                            <span class="badge bg-success-subtle text-success">Good / Excellent</span>
                                                                        <?php elseif ($val == 3): ?>
                                                                            <span class="badge bg-info-subtle text-dark">Average</span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-warning-subtle text-warning-emphasis">Needs Review</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <h6 class="fw-bold text-navy mb-2">Student Comments & Constructive Suggestions</h6>
                                                <div class="p-3 bg-light rounded-3 border">
                                                    <?php if (!empty($fb['comments'])): ?>
                                                        <p class="mb-0 text-dark fst-italic">"<?= nl2br(htmlspecialchars($fb['comments'])); ?>"</p>
                                                    <?php else: ?>
                                                        <span class="text-muted small fst-italic">No additional qualitative comments provided with this evaluation.</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

