<?php
/**
 * Faculty Management & Course Allocation CRUD
 * Project: Student Feedback and Course Evaluation System
 */

$pageTitle = 'Faculty Management - Admin Console';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin_login();

$errors = [];
$success = '';

// Handle Actions (Add, Edit, Delete, Assign Course, Unassign Course)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_request();

    $action = $_POST['action'] ?? '';

    // 1. ADD FACULTY
    if ($action === 'create') {
        $facultyCode   = strtoupper(trim($_POST['faculty_code'] ?? ''));
        $fullName      = trim($_POST['full_name'] ?? '');
        $email         = strtolower(trim($_POST['email'] ?? ''));
        $phone         = trim($_POST['phone'] ?? '');
        $departmentId  = (int)($_POST['department_id'] ?? 0);
        $designation   = trim($_POST['designation'] ?? '');
        $qualification = trim($_POST['qualification'] ?? '');

        if (empty($facultyCode) || empty($fullName) || empty($email) || $departmentId <= 0) {
            $errors[] = "Faculty Code, Full Name, Email, and Department are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid faculty email address.";
        } else {
            try {
                $check = $pdo->prepare("SELECT faculty_id FROM faculty WHERE faculty_code = ? OR email = ?");
                $check->execute([$facultyCode, $email]);
                if ($check->fetch()) {
                    $errors[] = "A faculty member with this Code or Email is already registered.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO faculty (faculty_code, full_name, email, phone, department_id, designation, qualification) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$facultyCode, $fullName, $email, $phone, $departmentId, $designation, $qualification]);
                    $success = "Faculty member '{$fullName}' ({$facultyCode}) was added successfully!";
                }
            } catch (Exception $e) {
                $errors[] = "Error adding faculty member: " . $e->getMessage();
            }
        }
    }

    // 2. UPDATE FACULTY
    if ($action === 'update') {
        $facultyId     = (int)($_POST['faculty_id'] ?? 0);
        $facultyCode   = strtoupper(trim($_POST['faculty_code'] ?? ''));
        $fullName      = trim($_POST['full_name'] ?? '');
        $email         = strtolower(trim($_POST['email'] ?? ''));
        $phone         = trim($_POST['phone'] ?? '');
        $departmentId  = (int)($_POST['department_id'] ?? 0);
        $designation   = trim($_POST['designation'] ?? '');
        $qualification = trim($_POST['qualification'] ?? '');

        if ($facultyId <= 0 || empty($facultyCode) || empty($fullName) || empty($email)) {
            $errors[] = "Invalid faculty update parameters.";
        } else {
            try {
                $check = $pdo->prepare("SELECT faculty_id FROM faculty WHERE (faculty_code = ? OR email = ?) AND faculty_id != ?");
                $check->execute([$facultyCode, $email, $facultyId]);
                if ($check->fetch()) {
                    $errors[] = "Faculty code or email is already in use by another instructor.";
                } else {
                    $stmt = $pdo->prepare("UPDATE faculty SET faculty_code = ?, full_name = ?, email = ?, phone = ?, department_id = ?, designation = ?, qualification = ? WHERE faculty_id = ?");
                    $stmt->execute([$facultyCode, $fullName, $email, $phone, $departmentId, $designation, $qualification, $facultyId]);
                    $success = "Faculty details updated successfully!";
                }
            } catch (Exception $e) {
                $errors[] = "Error updating faculty: " . $e->getMessage();
            }
        }
    }

    // 3. DELETE FACULTY
    if ($action === 'delete') {
        $facultyId = (int)($_POST['faculty_id'] ?? 0);
        if ($facultyId <= 0) {
            $errors[] = "Invalid faculty record selected for deletion.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM faculty WHERE faculty_id = ?");
                $stmt->execute([$facultyId]);
                $success = "Faculty member record was deleted.";
            } catch (Exception $e) {
                $errors[] = "Cannot delete faculty record: " . $e->getMessage();
            }
        }
    }

    // 4. ASSIGN COURSE TO FACULTY
    if ($action === 'assign_course') {
        $facultyId    = (int)($_POST['faculty_id'] ?? 0);
        $courseId     = (int)($_POST['course_id'] ?? 0);
        $semester     = (int)($_POST['semester'] ?? 0);
        $academicYear = trim($_POST['academic_year'] ?? '2025-2026');

        if ($facultyId <= 0 || $courseId <= 0 || $semester <= 0) {
            $errors[] = "Please select valid faculty, course, and semester for assignment.";
        } else {
            try {
                $check = $pdo->prepare("SELECT id FROM course_faculty WHERE course_id = ? AND faculty_id = ? AND semester = ? AND academic_year = ?");
                $check->execute([$courseId, $facultyId, $semester, $academicYear]);
                if ($check->fetch()) {
                    $errors[] = "This course is already assigned to this faculty member for the selected term.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO course_faculty (course_id, faculty_id, academic_year, semester) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$courseId, $facultyId, $academicYear, $semester]);
                    $success = "Course was assigned to faculty member successfully!";
                }
            } catch (Exception $e) {
                $errors[] = "Course assignment error: " . $e->getMessage();
            }
        }
    }

    // 5. UNASSIGN COURSE
    if ($action === 'unassign_course') {
        $mappingId = (int)($_POST['mapping_id'] ?? 0);
        if ($mappingId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM course_faculty WHERE id = ?");
                $stmt->execute([$mappingId]);
                $success = "Course assignment was removed.";
            } catch (Exception $e) {
                $errors[] = "Error unassigning course: " . $e->getMessage();
            }
        }
    }
}

// Fetch departments for dropdown
$departments = $pdo->query("SELECT department_id, department_code, department_name FROM departments ORDER BY department_name ASC")->fetchAll();

// Fetch all courses for assignment modal
$allCourses = $pdo->query("SELECT course_id, course_code, course_name, semester FROM courses ORDER BY course_code ASC")->fetchAll();

// Fetch faculty members with aggregate ratings & course count
$facultyList = $pdo->query("
    SELECT f.*, d.department_name, d.department_code,
           COUNT(DISTINCT cf.id) AS assigned_course_count,
           COUNT(DISTINCT fb.feedback_id) AS total_feedback,
           AVG(fb.average_rating) AS avg_rating
    FROM faculty f
    JOIN departments d ON f.department_id = d.department_id
    LEFT JOIN course_faculty cf ON f.faculty_id = cf.faculty_id
    LEFT JOIN feedback fb ON f.faculty_id = fb.faculty_id
    GROUP BY f.faculty_id
    ORDER BY f.full_name ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-navy mb-1">Faculty & Instructor Directory</h3>
                <p class="text-muted small mb-0">Manage teaching professors, designations, contact details, and course teaching allocations.</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#assignCourseModal">
                    <i class="bi bi-link-45deg me-1"></i> Allocate Course to Faculty
                </button>
                <button type="button" class="btn btn-navy btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                    <i class="bi bi-person-plus me-1"></i> Add Faculty
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

        <!-- Faculty Directory Table -->
        <div class="card card-custom border-0 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Code</th>
                            <th>Faculty Name & Designation</th>
                            <th>Department</th>
                            <th>Contact Email</th>
                            <th class="text-center">Courses</th>
                            <th class="text-center">Appraisal Score</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($facultyList)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No faculty members found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($facultyList as $fac): ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-light text-navy border fw-bold"><?= htmlspecialchars($fac['faculty_code']); ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-navy"><?= htmlspecialchars($fac['full_name']); ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($fac['designation']); ?> &bull; <?= htmlspecialchars($fac['qualification']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary fw-semibold"><?= htmlspecialchars($fac['department_code']); ?></span>
                                    </td>
                                    <td>
                                        <a href="mailto:<?= htmlspecialchars($fac['email']); ?>" class="text-decoration-none small text-dark">
                                            <i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($fac['email']); ?>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-dark rounded-pill px-3"><?= $fac['assigned_course_count']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($fac['total_feedback'] > 0): ?>
                                            <span class="badge bg-warning text-dark fw-bold px-2 py-1">
                                                &#9733; <?= number_format($fac['avg_rating'], 2); ?>
                                            </span>
                                            <small class="text-muted d-block" style="font-size:0.75rem;"><?= $fac['total_feedback']; ?> reviews</small>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">No evaluations</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <!-- Edit Modal Trigger -->
                                        <button class="btn btn-outline-primary btn-sm px-2 py-1 me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editFacultyModal<?= $fac['faculty_id']; ?>" 
                                                title="Edit Faculty">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Delete Form -->
                                        <form method="POST" action="faculty.php" class="d-inline" onsubmit="return confirm('Deleting <?= htmlspecialchars($fac['full_name']); ?> will also detach their course allocations. Continue?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="faculty_id" value="<?= $fac['faculty_id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm px-2 py-1" title="Delete Faculty">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit Faculty Modal -->
                                <div class="modal fade" id="editFacultyModal<?= $fac['faculty_id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="faculty.php">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="faculty_id" value="<?= $fac['faculty_id']; ?>">

                                                <div class="modal-header bg-navy text-white">
                                                    <h5 class="modal-title fs-6 fw-bold">Edit Faculty Information</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold">Faculty ID Code <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control text-uppercase" name="faculty_code" value="<?= htmlspecialchars($fac['faculty_code']); ?>" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                                                            <select class="form-select" name="department_id" required>
                                                                <?php foreach ($departments as $dept): ?>
                                                                    <option value="<?= $dept['department_id']; ?>" <?= ($dept['department_id'] == $fac['department_id']) ? 'selected' : ''; ?>>
                                                                        <?= htmlspecialchars($dept['department_code']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($fac['full_name']); ?>" required>
                                                    </div>

                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($fac['email']); ?>" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold">Phone Number</label>
                                                            <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($fac['phone'] ?? ''); ?>">
                                                        </div>
                                                    </div>

                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold">Designation</label>
                                                            <select class="form-select" name="designation">
                                                                <option value="Professor" <?= ($fac['designation'] === 'Professor') ? 'selected' : ''; ?>>Professor</option>
                                                                <option value="Associate Professor" <?= ($fac['designation'] === 'Associate Professor') ? 'selected' : ''; ?>>Associate Professor</option>
                                                                <option value="Assistant Professor" <?= ($fac['designation'] === 'Assistant Professor') ? 'selected' : ''; ?>>Assistant Professor</option>
                                                                <option value="Lecturer" <?= ($fac['designation'] === 'Lecturer') ? 'selected' : ''; ?>>Lecturer</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold">Qualification</label>
                                                            <input type="text" class="form-control" name="qualification" value="<?= htmlspecialchars($fac['qualification']); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-navy btn-sm px-3">Update Profile</button>
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

<!-- Add Faculty Modal -->
<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="faculty.php">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="create">

                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-person-plus me-1"></i> Add Faculty Member</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Faculty ID Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" name="faculty_code" placeholder="e.g. FAC106" required>
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
                        <input type="text" class="form-control" name="full_name" placeholder="e.g. Dr. Jane Smith" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" placeholder="faculty@college.edu" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" class="form-control" name="phone" placeholder="9876543210">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Designation</label>
                            <select class="form-select" name="designation">
                                <option value="Assistant Professor" selected>Assistant Professor</option>
                                <option value="Associate Professor">Associate Professor</option>
                                <option value="Professor">Professor</option>
                                <option value="Lecturer">Lecturer</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Qualification</label>
                            <input type="text" class="form-control" name="qualification" placeholder="e.g. Ph.D. in CSE">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy btn-sm px-3">Add Faculty</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Course Modal -->
<div class="modal fade" id="assignCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="faculty.php">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="assign_course">

                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-link-45deg me-1"></i> Allocate Course to Faculty</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Faculty Instructor <span class="text-danger">*</span></label>
                        <select class="form-select" name="faculty_id" required>
                            <option value="">-- Choose Faculty --</option>
                            <?php foreach ($facultyList as $fac): ?>
                                <option value="<?= $fac['faculty_id']; ?>">
                                    <?= htmlspecialchars($fac['full_name'] . ' (' . $fac['faculty_code'] . ' - ' . $fac['department_code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Course / Subject <span class="text-danger">*</span></label>
                        <select class="form-select" name="course_id" required>
                            <option value="">-- Choose Course --</option>
                            <?php foreach ($allCourses as $c): ?>
                                <option value="<?= $c['course_id']; ?>">
                                    <?= htmlspecialchars($c['course_code'] . ' - ' . $c['course_name'] . ' (Sem ' . $c['semester'] . ')'); ?>
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
                            <label class="form-label fw-semibold">Academic Year</label>
                            <input type="text" class="form-control" name="academic_year" value="2025-2026" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Assign Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

