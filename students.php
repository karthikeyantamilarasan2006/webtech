<?php
/**
 * Student Records Management & Status Control
 * Project: Student Feedback and Course Evaluation System
 */

$pageTitle = 'Student Management - Admin Console';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin_login();

$errors = [];
$success = '';

// Handle Actions (Add, Edit, Toggle Status, Reset Password, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_request();

    $action = $_POST['action'] ?? '';

    // 1. ADD STUDENT MANUALLY
    if ($action === 'create') {
        $rollNumber   = strtoupper(trim($_POST['roll_number'] ?? ''));
        $fullName     = trim($_POST['full_name'] ?? '');
        $email        = strtolower(trim($_POST['email'] ?? ''));
        $departmentId = (int)($_POST['department_id'] ?? 0);
        $semester     = (int)($_POST['semester'] ?? 1);
        $academicYear = trim($_POST['academic_year'] ?? '2025-2026');

        if (empty($rollNumber) || empty($fullName) || empty($email) || $departmentId <= 0) {
            $errors[] = "Roll number, full name, email, and department are required.";
        } else {
            try {
                $check = $pdo->prepare("SELECT student_id FROM students WHERE roll_number = ? OR email = ?");
                $check->execute([$rollNumber, $email]);
                if ($check->fetch()) {
                    $errors[] = "A student with this Roll Number or Email is already registered.";
                } else {
                    $hash = password_hash('Student@123', PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO students (roll_number, full_name, email, password, department_id, semester, academic_year, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
                    $stmt->execute([$rollNumber, $fullName, $email, $hash, $departmentId, $semester, $academicYear]);
                    $success = "Student '{$fullName}' ({$rollNumber}) created. Default password: Student@123";
                }
            } catch (Exception $e) {
                $errors[] = "Error adding student: " . $e->getMessage();
            }
        }
    }

    // 2. UPDATE STUDENT
    if ($action === 'update') {
        $studentId    = (int)($_POST['student_id'] ?? 0);
        $rollNumber   = strtoupper(trim($_POST['roll_number'] ?? ''));
        $fullName     = trim($_POST['full_name'] ?? '');
        $email        = strtolower(trim($_POST['email'] ?? ''));
        $departmentId = (int)($_POST['department_id'] ?? 0);
        $semester     = (int)($_POST['semester'] ?? 1);
        $academicYear = trim($_POST['academic_year'] ?? '2025-2026');

        if ($studentId <= 0 || empty($rollNumber) || empty($fullName) || empty($email)) {
            $errors[] = "Invalid student update data.";
        } else {
            try {
                $check = $pdo->prepare("SELECT student_id FROM students WHERE (roll_number = ? OR email = ?) AND student_id != ?");
                $check->execute([$rollNumber, $email, $studentId]);
                if ($check->fetch()) {
                    $errors[] = "Roll number or email already belongs to another student.";
                } else {
                    $stmt = $pdo->prepare("UPDATE students SET roll_number = ?, full_name = ?, email = ?, department_id = ?, semester = ?, academic_year = ? WHERE student_id = ?");
                    $stmt->execute([$rollNumber, $fullName, $email, $departmentId, $semester, $academicYear, $studentId]);
                    $success = "Student details updated successfully!";
                }
            } catch (Exception $e) {
                $errors[] = "Error updating student: " . $e->getMessage();
            }
        }
    }

    // 3. TOGGLE STATUS (Active / Inactive)
    if ($action === 'toggle_status') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        if ($studentId > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE students SET status = IF(status = 'active', 'inactive', 'active') WHERE student_id = ?");
                $stmt->execute([$studentId]);
                $success = "Student status updated.";
            } catch (Exception $e) {
                $errors[] = "Could not toggle status: " . $e->getMessage();
            }
        }
    }

    // 4. RESET PASSWORD TO Student@123
    if ($action === 'reset_password') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        if ($studentId > 0) {
            try {
                $newHash = password_hash('Student@123', PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE student_id = ?");
                $stmt->execute([$newHash, $studentId]);
                $success = "Student password was reset to default: Student@123";
            } catch (Exception $e) {
                $errors[] = "Could not reset password: " . $e->getMessage();
            }
        }
    }

    // 5. DELETE STUDENT
    if ($action === 'delete') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        if ($studentId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
                $stmt->execute([$studentId]);
                $success = "Student record was removed.";
            } catch (Exception $e) {
                $errors[] = "Error deleting student: " . $e->getMessage();
            }
        }
    }
}

// Filters
$filterDept = (int)($_GET['department_id'] ?? 0);
$filterSem  = (int)($_GET['semester'] ?? 0);
$search     = trim($_GET['search'] ?? '');

$departments = $pdo->query("SELECT department_id, department_code, department_name FROM departments ORDER BY department_name ASC")->fetchAll();

// Build query
$sql = "
    SELECT s.*, d.department_name, d.department_code,
           COUNT(DISTINCT fs.submission_id) AS total_submissions
    FROM students s
    JOIN departments d ON s.department_id = d.department_id
    LEFT JOIN feedback_submissions fs ON s.student_id = fs.student_id
    WHERE 1=1
";
$params = [];

if ($filterDept > 0) {
    $sql .= " AND s.department_id = ?";
    $params[] = $filterDept;
}
if ($filterSem > 0) {
    $sql .= " AND s.semester = ?";
    $params[] = $filterSem;
}
if (!empty($search)) {
    $sql .= " AND (s.roll_number LIKE ? OR s.full_name LIKE ? OR s.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$sql .= " GROUP BY s.student_id ORDER BY s.roll_number ASC";
$studentsStmt = $pdo->prepare($sql);
$studentsStmt->execute($params);
$students = $studentsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-navy mb-1">Student Enrollment Directory</h3>
                <p class="text-muted small mb-0">Manage registered students, verify feedback receipts, and control account access.</p>
            </div>
            <div>
                <button type="button" class="btn btn-navy btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="bi bi-person-plus me-1"></i> Register Student Manually
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

        <!-- Search & Filter Card -->
        <div class="card card-custom border-0 p-3 mb-4 bg-white">
            <form method="GET" action="students.php" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Search by Name / Roll / Email</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="e.g. 2023CSE001 or Rahul" value="<?= htmlspecialchars($search); ?>">
                </div>

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

                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Semester</label>
                    <select name="semester" class="form-select form-select-sm">
                        <option value="0">All Semesters</option>
                        <?php for ($s = 1; $s <= 8; $s++): ?>
                            <option value="<?= $s; ?>" <?= ($filterSem === $s) ? 'selected' : ''; ?>>Sem <?= $s; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-navy btn-sm px-3 flex-grow-1">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                    <a href="students.php" class="btn btn-outline-secondary btn-sm px-2" title="Reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Student List Table -->
        <div class="card card-custom border-0 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Roll Number</th>
                            <th>Student Name</th>
                            <th>Department & Sem</th>
                            <th>Email Address</th>
                            <th class="text-center">Evaluations</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No student records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $st): ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-light text-navy border fw-bold"><?= htmlspecialchars($st['roll_number']); ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-navy"><?= htmlspecialchars($st['full_name']); ?></div>
                                        <small class="text-muted">Enrolled: <?= htmlspecialchars($st['academic_year']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary fw-semibold"><?= htmlspecialchars($st['department_code']); ?></span>
                                        <span class="badge bg-secondary-subtle text-dark ms-1">Sem <?= $st['semester']; ?></span>
                                    </td>
                                    <td>
                                        <a href="mailto:<?= htmlspecialchars($st['email']); ?>" class="text-decoration-none small text-dark">
                                            <?= htmlspecialchars($st['email']); ?>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success rounded-pill px-3"><?= $st['total_submissions']; ?> submitted</span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($st['status'] === 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <!-- Edit Modal Trigger -->
                                        <button class="btn btn-outline-primary btn-sm px-2 py-1 me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editStudentModal<?= $st['student_id']; ?>" 
                                                title="Edit Student Details">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Toggle Status Form -->
                                        <form method="POST" action="students.php" class="d-inline" onsubmit="return confirm('Toggle account status for <?= htmlspecialchars($st['full_name']); ?>?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="student_id" value="<?= $st['student_id']; ?>">
                                            <button type="submit" class="btn btn-outline-warning btn-sm px-2 py-1 me-1" title="Toggle Active/Inactive">
                                                <i class="bi bi-power"></i>
                                            </button>
                                        </form>

                                        <!-- Reset Password Form -->
                                        <form method="POST" action="students.php" class="d-inline" onsubmit="return confirm('Reset password for <?= htmlspecialchars($st['full_name']); ?> to Student@123?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="reset_password">
                                            <input type="hidden" name="student_id" value="<?= $st['student_id']; ?>">
                                            <button type="submit" class="btn btn-outline-secondary btn-sm px-2 py-1 me-1" title="Reset Password to Default">
                                                <i class="bi bi-key"></i>
                                            </button>
                                        </form>

                                        <!-- Delete Form -->
                                        <form method="POST" action="students.php" class="d-inline" onsubmit="return confirm('Deleting <?= htmlspecialchars($st['full_name']); ?> will remove their evaluation receipts. Proceed?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="student_id" value="<?= $st['student_id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm px-2 py-1" title="Delete Student">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit Student Modal -->
                                <div class="modal fade" id="editStudentModal<?= $st['student_id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="students.php">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="student_id" value="<?= $st['student_id']; ?>">

                                                <div class="modal-header bg-navy text-white">
                                                    <h5 class="modal-title fs-6 fw-bold">Edit Student Information</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold">Roll Number <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control text-uppercase" name="roll_number" value="<?= htmlspecialchars($st['roll_number']); ?>" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                                                            <select class="form-select" name="department_id" required>
                                                                <?php foreach ($departments as $dept): ?>
                                                                    <option value="<?= $dept['department_id']; ?>" <?= ($dept['department_id'] == $st['department_id']) ? 'selected' : ''; ?>>
                                                                        <?= htmlspecialchars($dept['department_code']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($st['full_name']); ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">College Email <span class="text-danger">*</span></label>
                                                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($st['email']); ?>" required>
                                                    </div>

                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold">Semester (1-8) <span class="text-danger">*</span></label>
                                                            <select class="form-select" name="semester" required>
                                                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                                                    <option value="<?= $s; ?>" <?= ($s == $st['semester']) ? 'selected' : ''; ?>>Semester <?= $s; ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold">Academic Year</label>
                                                            <input type="text" class="form-control" name="academic_year" value="<?= htmlspecialchars($st['academic_year']); ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-navy btn-sm px-3">Update Student</button>
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

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="students.php">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="create">

                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-person-plus me-1"></i> Register Student Manually</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Roll Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" name="roll_number" placeholder="e.g. 2023CSE010" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                            <select class="form-select" name="department_id" required>
                                <option value="">Select Dept</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['department_id']; ?>">
                                        <?= htmlspecialchars($dept['department_code']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="full_name" placeholder="e.g. Ananya Sen" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">College Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" placeholder="ananya.cse@college.edu" required>
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
                            <label class="form-label fw-semibold">Academic Year</label>
                            <input type="text" class="form-control" name="academic_year" value="2025-2026" required>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 small mb-0">
                        <i class="bi bi-info-circle me-1"></i> Default initial password will be set to: <code>Student@123</code>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy btn-sm px-3">Register Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

