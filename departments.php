<?php
/**
 * Department CRUD Management
 * Project: Student Feedback and Course Evaluation System
 */

$pageTitle = 'Department Management - Admin Console';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin_login();

$errors = [];
$success = '';

// Handle Form Submissions (Create, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_request();

    $action = $_POST['action'] ?? '';

    // 1. ADD DEPARTMENT
    if ($action === 'create') {
        $code = strtoupper(trim($_POST['department_code'] ?? ''));
        $name = trim($_POST['department_name'] ?? '');

        if (empty($code) || empty($name)) {
            $errors[] = "Both Department Code (e.g. CSE) and Department Name are required.";
        } else {
            try {
                $check = $pdo->prepare("SELECT department_id FROM departments WHERE department_code = ?");
                $check->execute([$code]);
                if ($check->fetch()) {
                    $errors[] = "A department with code '{$code}' already exists.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO departments (department_code, department_name) VALUES (?, ?)");
                    $stmt->execute([$code, $name]);
                    $success = "Department '{$name}' ({$code}) was successfully created!";
                }
            } catch (Exception $e) {
                $errors[] = "Error adding department: " . $e->getMessage();
            }
        }
    }

    // 2. UPDATE DEPARTMENT
    if ($action === 'update') {
        $deptId = (int)($_POST['department_id'] ?? 0);
        $code   = strtoupper(trim($_POST['department_code'] ?? ''));
        $name   = trim($_POST['department_name'] ?? '');

        if ($deptId <= 0 || empty($code) || empty($name)) {
            $errors[] = "Invalid update parameters.";
        } else {
            try {
                $check = $pdo->prepare("SELECT department_id FROM departments WHERE department_code = ? AND department_id != ?");
                $check->execute([$code, $deptId]);
                if ($check->fetch()) {
                    $errors[] = "Department code '{$code}' is already used by another department.";
                } else {
                    $stmt = $pdo->prepare("UPDATE departments SET department_code = ?, department_name = ? WHERE department_id = ?");
                    $stmt->execute([$code, $name, $deptId]);
                    $success = "Department updated successfully!";
                }
            } catch (Exception $e) {
                $errors[] = "Error updating department: " . $e->getMessage();
            }
        }
    }

    // 3. DELETE DEPARTMENT
    if ($action === 'delete') {
        $deptId = (int)($_POST['department_id'] ?? 0);
        if ($deptId <= 0) {
            $errors[] = "Invalid department specified for deletion.";
        } else {
            try {
                // Delete department (cascades or cleans related courses according to constraints)
                $stmt = $pdo->prepare("DELETE FROM departments WHERE department_id = ?");
                $stmt->execute([$deptId]);
                $success = "Department and associated curricula records were removed.";
            } catch (Exception $e) {
                $errors[] = "Cannot delete department: " . $e->getMessage();
            }
        }
    }
}

// Fetch all departments with course, faculty & student counts
$departments = [];
try {
    $query = "
        SELECT d.department_id, d.department_code, d.department_name, d.created_at,
               COUNT(DISTINCT c.course_id) AS course_count,
               COUNT(DISTINCT f.faculty_id) AS faculty_count,
               COUNT(DISTINCT s.student_id) AS student_count
        FROM departments d
        LEFT JOIN courses c ON d.department_id = c.department_id
        LEFT JOIN faculty f ON d.department_id = f.department_id
        LEFT JOIN students s ON d.department_id = s.department_id
        GROUP BY d.department_id
        ORDER BY d.department_name ASC
    ";
    $departments = $pdo->query($query)->fetchAll();
} catch (Exception $e) {
    $errors[] = "Failed to load departments: " . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-navy mb-1">Department Management</h3>
                <p class="text-muted small mb-0">Manage institutional academic departments, branches, and faculties.</p>
            </div>
            <div>
                <button type="button" class="btn btn-navy btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addDeptModal">
                    <i class="bi bi-plus-circle me-1"></i> Add New Department
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

        <!-- Departments Table -->
        <div class="card card-custom border-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Code</th>
                            <th>Department Full Name</th>
                            <th class="text-center">Courses</th>
                            <th class="text-center">Faculty</th>
                            <th class="text-center">Students</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($departments)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No academic departments defined yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($departments as $d): ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-primary px-3 py-2 fs-7 fw-bold"><?= htmlspecialchars($d['department_code']); ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-navy"><?= htmlspecialchars($d['department_name']); ?></div>
                                        <small class="text-muted">Registered <?= date('M d, Y', strtotime($d['created_at'])); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info-emphasis rounded-pill px-3"><?= $d['course_count']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success-emphasis rounded-pill px-3"><?= $d['faculty_count']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-dark rounded-pill px-3"><?= $d['student_count']; ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <!-- Edit Button -->
                                        <button class="btn btn-outline-primary btn-sm px-2 py-1 me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editDeptModal<?= $d['department_id']; ?>" 
                                                title="Edit Department">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Delete Button Form -->
                                        <form method="POST" action="departments.php" class="d-inline" onsubmit="return confirm('Deleting <?= htmlspecialchars($d['department_name']); ?> will also remove associated courses. Are you sure?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="department_id" value="<?= $d['department_id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm px-2 py-1" title="Delete Department">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit Department Modal -->
                                <div class="modal fade" id="editDeptModal<?= $d['department_id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="departments.php">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="department_id" value="<?= $d['department_id']; ?>">

                                                <div class="modal-header bg-navy text-white">
                                                    <h5 class="modal-title fs-6 fw-bold">Edit Department</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Department Code <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control text-uppercase" name="department_code" value="<?= htmlspecialchars($d['department_code']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Department Name <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="department_name" value="<?= htmlspecialchars($d['department_name']); ?>" required>
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

<!-- Add Department Modal -->
<div class="modal fade" id="addDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="departments.php">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="create">

                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-plus-circle me-1"></i> Add New Department</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" name="department_code" placeholder="e.g. CSE, ECE, MECH" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="department_name" placeholder="e.g. Computer Science and Engineering" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy btn-sm px-3">Create Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

