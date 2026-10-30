<?php
/**
 * Course Management & Curriculum CRUD
 * Project: Student Feedback and Course Evaluation System
 */

$pageTitle = 'Course Management - Admin Console';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin_login();

$errors = [];
$success = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_request();

    $action = $_POST['action'] ?? '';

    // 1. ADD COURSE
    if ($action === 'create') {
        $courseCode   = strtoupper(trim($_POST['course_code'] ?? ''));
        $courseName   = trim($_POST['course_name'] ?? '');
        $departmentId = (int)($_POST['department_id'] ?? 0);
        $semester     = (int)($_POST['semester'] ?? 0);
        $academicYear = trim($_POST['academic_year'] ?? '2025-2026');
        $credits      = (int)($_POST['credits'] ?? 3);

        if (empty($courseCode) || empty($courseName) || $departmentId <= 0 || $semester < 1 || $semester > 8) {
            $errors[] = "Please provide valid course code, course title, department, and semester (1-8).";
        } else {
            try {
                $check = $pdo->prepare("SELECT course_id FROM courses WHERE course_code = ?");
                $check->execute([$courseCode]);
                if ($check->fetch()) {
                    $errors[] = "Course Code '{$courseCode}' is already registered.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO courses (course_code, course_name, department_id, semester, academic_year, credits) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$courseCode, $courseName, $departmentId, $semester, $academicYear, $credits]);
                    $success = "Course '{$courseCode} - {$courseName}' was successfully created!";
                }
            } catch (Exception $e) {
                $errors[] = "Error creating course: " . $e->getMessage();
            }
        }
    }

    // 2. UPDATE COURSE
    if ($action === 'update') {
        $courseId     = (int)($_POST['course_id'] ?? 0);
        $courseCode   = strtoupper(trim($_POST['course_code'] ?? ''));
        $courseName   = trim($_POST['course_name'] ?? '');
        $departmentId = (int)($_POST['department_id'] ?? 0);
        $semester     = (int)($_POST['semester'] ?? 0);
        $academicYear = trim($_POST['academic_year'] ?? '2025-2026');
        $credits      = (int)($_POST['credits'] ?? 3);

        if ($courseId <= 0 || empty($courseCode) || empty($courseName) || $departmentId <= 0) {
            $errors[] = "Invalid course update parameters.";
        } else {
            try {
                $check = $pdo->prepare("SELECT course_id FROM courses WHERE course_code = ? AND course_id != ?");
                $check->execute([$courseCode, $courseId]);
                if ($check->fetch()) {
                    $errors[] = "Course code '{$courseCode}' is already used by another subject.";
                } else {
                    $stmt = $pdo->prepare("UPDATE courses SET course_code = ?, course_name = ?, department_id = ?, semester = ?, academic_year = ?, credits = ? WHERE course_id = ?");
                    $stmt->execute([$courseCode, $courseName, $departmentId, $semester, $academicYear, $credits, $courseId]);
                    $success = "Course details updated successfully!";
                }
            } catch (Exception $e) {
                $errors[] = "Error updating course: " . $e->getMessage();
            }
        }
    }

    // 3. DELETE COURSE
    if ($action === 'delete') {
        $courseId = (int)($_POST['course_id'] ?? 0);
        if ($courseId <= 0) {
            $errors[] = "Invalid course specified for deletion.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ?");
                $stmt->execute([$courseId]);
                $success = "Course was deleted from the curriculum.";
            } catch (Exception $e) {
                $errors[] = "Cannot delete course: " . $e->getMessage();
            }
        }
    }
}

// Filter parameters
$filterDept = (int)($_GET['department_id'] ?? 0);
$filterSem  = (int)($_GET['semester'] ?? 0);

// Fetch departments for filter & modals
$departments = $pdo->query("SELECT department_id, department_code, department_name FROM departments ORDER BY department_name ASC")->fetchAll();

// Build query for courses with assigned faculty info
$sql = "
    SELECT c.*, d.department_name, d.department_code,
           GROUP_CONCAT(DISTINCT f.full_name SEPARATOR ', ') AS assigned_faculty,
           COUNT(DISTINCT fb.feedback_id) AS total_feedback
    FROM courses c
    JOIN departments d ON c.department_id = d.department_id
    LEFT JOIN course_faculty cf ON c.course_id = cf.course_id
    LEFT JOIN faculty f ON cf.faculty_id = f.faculty_id
    LEFT JOIN feedback fb ON c.course_id = fb.course_id
    WHERE 1=1
";
$params = [];

if ($filterDept > 0) {
    $sql .= " AND c.department_id = ?";
    $params[] = $filterDept;
}
if ($filterSem > 0) {
    $sql .= " AND c.semester = ?";
    $params[] = $filterSem;
}

$sql .= " GROUP BY c.course_id ORDER BY c.semester ASC, c.course_code ASC";
$coursesStmt = $pdo->prepare($sql);
$coursesStmt->execute($params);
$courses = $coursesStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-navy mb-1">Course & Curriculum Management</h3>
                <p class="text-muted small mb-0">Configure academic courses, credits, semester allocation, and syllabus details.</p>
            </div>
            <div>
                <button type="button" class="btn btn-navy btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                    <i class="bi bi-plus-circle me-1"></i> Add New Course
                </button>
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

        <!-- Filter Card -->
        <div class="card card-custom border-0 p-3 mb-4 bg-white">
            <form method="GET" action="courses.php" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted mb-1">Filter by Department</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="0">-- All Departments --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['department_id']; ?>" <?= ($filterDept === (int)$dept['department_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($dept['department_name'] . ' (' . $dept['department_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Filter by Semester</label>
                    <select name="semester" class="form-select form-select-sm">
                        <option value="0">-- All Semesters --</option>
                        <?php for ($s = 1; $s <= 8; $s++): ?>
                            <option value="<?= $s; ?>" <?= ($filterSem === $s) ? 'selected' : ''; ?>>Semester <?= $s; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-navy btn-sm px-3 flex-grow-1">
                        <i class="bi bi-filter me-1"></i> Apply Filter
                    </button>
                    <a href="courses.php" class="btn btn-outline-secondary btn-sm px-2" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Courses Table -->
        <div class="card card-custom border-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Code & Title</th>
                            <th>Department</th>
                            <th class="text-center">Semester</th>
                            <th class="text-center">Credits</th>
                            <th>Assigned Faculty</th>
                            <th class="text-center">Feedback</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No courses found matching the criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($courses as $c): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-navy"><?= htmlspecialchars($c['course_name']); ?></div>
                                        <span class="badge bg-light text-primary border"><?= htmlspecialchars($c['course_code']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary fw-semibold"><?= htmlspecialchars($c['department_code']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-dark">Sem <?= $c['semester']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?= $c['credits']; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['assigned_faculty'])): ?>
                                            <div class="small fw-semibold text-dark"><?= htmlspecialchars($c['assigned_faculty']); ?></div>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">None assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= $c['total_feedback']; ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <!-- Edit Trigger -->
                                        <button class="btn btn-outline-primary btn-sm px-2 py-1 me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editCourseModal<?= $c['course_id']; ?>" 
                                                title="Edit Course">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Delete Form -->
                                        <form method="POST" action="courses.php" class="d-inline" onsubmit="return confirm('Deleting <?= htmlspecialchars($c['course_code']); ?> will delete related faculty assignments. Continue?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="course_id" value="<?= $c['course_id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm px-2 py-1" title="Delete Course">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit Course Modal -->
                                <div class="modal fade" id="editCourseModal<?= $c['course_id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="courses.php">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="course_id" value="<?= $c['course_id']; ?>">

                                                <div class="modal-header bg-navy text-white">
                                                    <h5 class="modal-title fs-6 fw-bold">Edit Course Details</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Course Code <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control text-uppercase" name="course_code" value="<?= htmlspecialchars($c['course_code']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Course Name / Subject Title <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="course_name" value="<?= htmlspecialchars($c['course_name']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                                                        <select class="form-select" name="department_id" required>
                                                            <?php foreach ($departments as $dept): ?>
                                                                <option value="<?= $dept['department_id']; ?>" <?= ($dept['department_id'] == $c['department_id']) ? 'selected' : ''; ?>>
                                                                    <?= htmlspecialchars($dept['department_name'] . ' (' . $dept['department_code'] . ')'); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold">Semester (1-8) <span class="text-danger">*</span></label>
                                                            <select class="form-select" name="semester" required>
                                                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                                                    <option value="<?= $s; ?>" <?= ($s == $c['semester']) ? 'selected' : ''; ?>>Semester <?= $s; ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold">Credits <span class="text-danger">*</span></label>
                                                            <input type="number" class="form-control" name="credits" min="1" max="6" value="<?= $c['credits']; ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Academic Year</label>
                                                        <input type="text" class="form-control" name="academic_year" value="<?= htmlspecialchars($c['academic_year']); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-navy btn-sm px-3">Save Changes</button>
                                                </div>
                                            </form>
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

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="courses.php">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="create">

                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-plus-circle me-1"></i> Add New Course</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Course Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" name="course_code" placeholder="e.g. CS501" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Course Title / Subject Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="course_name" placeholder="e.g. Database Management Systems" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                        <select class="form-select" name="department_id" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['department_id']; ?>">
                                    <?= htmlspecialchars($dept['department_name'] . ' (' . $dept['department_code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Semester (1-8) <span class="text-danger">*</span></label>
                            <select class="form-select" name="semester" required>
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?= $s; ?>" <?= ($s === 5) ? 'selected' : ''; ?>>Semester <?= $s; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Credits <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="credits" min="1" max="6" value="3" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Academic Year</label>
                        <input type="text" class="form-control" name="academic_year" value="2025-2026" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy btn-sm px-3">Create Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

