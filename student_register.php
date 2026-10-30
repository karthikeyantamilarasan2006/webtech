<?php
/**
 * Student Registration Page & Processing
 * Project: Student Feedback and Course Evaluation System
 */

$pageTitle = 'Student Registration - Course Evaluation System';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Redirect if already logged in as student
if (is_student_logged_in()) {
    header("Location: ../student/dashboard.php");
    exit;
}

$errors = [];
$success = '';

// Fetch active departments for the registration dropdown
$departments = [];
try {
    $deptStmt = $pdo->query("SELECT department_id, department_code, department_name FROM departments ORDER BY department_name ASC");
    $departments = $deptStmt->fetchAll();
} catch (Exception $e) {
    $errors[] = "Failed to load department list: " . $e->getMessage();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_request();

    $fullName     = trim($_POST['full_name'] ?? '');
    $rollNumber   = strtoupper(trim($_POST['roll_number'] ?? ''));
    $email        = strtolower(trim($_POST['email'] ?? ''));
    $departmentId = (int)($_POST['department_id'] ?? 0);
    $academicYear = trim($_POST['academic_year'] ?? '2025-2026');
    $semester     = (int)($_POST['semester'] ?? 0);
    $password     = $_POST['password'] ?? '';
    $confirmPass  = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($fullName) || strlen($fullName) < 3) {
        $errors[] = "Full name must be at least 3 characters long.";
    }
    if (empty($rollNumber)) {
        $errors[] = "Roll number / Student ID is required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }
    if ($departmentId <= 0) {
        $errors[] = "Please select your academic department.";
    }
    if ($semester < 1 || $semester > 8) {
        $errors[] = "Please select a valid semester (1 to 8).";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirmPass) {
        $errors[] = "Passwords do not match.";
    }

    // Check for duplicate Roll Number or Email
    if (empty($errors)) {
        try {
            $checkStmt = $pdo->prepare("SELECT student_id, roll_number, email FROM students WHERE roll_number = ? OR email = ?");
            $checkStmt->execute([$rollNumber, $email]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                if ($existing['roll_number'] === $rollNumber) {
                    $errors[] = "A student with Roll Number '{$rollNumber}' is already registered.";
                }
                if ($existing['email'] === $email) {
                    $errors[] = "Email address '{$email}' is already in use.";
                }
            } else {
                // Hash password securely
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                // Insert student
                $insertStmt = $pdo->prepare("INSERT INTO students (roll_number, full_name, email, password, department_id, academic_year, semester, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
                $insertStmt->execute([
                    $rollNumber,
                    $fullName,
                    $email,
                    $hashedPassword,
                    $departmentId,
                    $academicYear,
                    $semester
                ]);

                $_SESSION['flash_success'] = "Registration successful! You can now log in using your Roll Number and Password.";
                header("Location: student_login.php");
                exit;
            }
        } catch (Exception $e) {
            $errors[] = "Registration database error: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-5 bg-light">
    <div class="container" style="max-width: 680px;">
        <div class="card card-custom border-0 shadow-sm overflow-hidden">
            <div class="p-4 bg-navy-gradient text-white text-center">
                <i class="bi bi-person-plus-fill fs-1 text-info mb-2"></i>
                <h3 class="fw-bold mb-1">Create Student Account</h3>
                <p class="text-light opacity-75 small mb-0">Register with your institutional roll number to evaluate your courses</p>
            </div>

            <div class="p-4 p-md-5">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Please correct the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="student_register.php" class="needs-validation" novalidate>
                    <?= csrf_field(); ?>

                    <div class="mb-3">
                        <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="full_name" name="full_name" placeholder="e.g. Rahul Sharma" value="<?= htmlspecialchars($_POST['full_name'] ?? ''); ?>" required minlength="3">
                        </div>
                        <div class="invalid-feedback">Please enter your full name (minimum 3 characters).</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="roll_number" class="form-label fw-semibold">Roll Number / Student ID <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-card-heading"></i></span>
                                <input type="text" class="form-control text-uppercase" id="roll_number" name="roll_number" placeholder="e.g. 2023CSE045" value="<?= htmlspecialchars($_POST['roll_number'] ?? ''); ?>" required>
                            </div>
                            <div class="invalid-feedback">Please provide your unique roll number.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">College Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="name@college.edu" value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            <div class="invalid-feedback">A valid email address is required.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="department_id" class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                            <select class="form-select" id="department_id" name="department_id" required>
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['department_id']; ?>" <?= (isset($_POST['department_id']) && (int)$_POST['department_id'] === (int)$dept['department_id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($dept['department_name'] . ' (' . $dept['department_code'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select your academic department.</div>
                        </div>

                        <div class="col-md-3">
                            <label for="semester" class="form-label fw-semibold">Semester <span class="text-danger">*</span></label>
                            <select class="form-select" id="semester" name="semester" required>
                                <option value="">Select</option>
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?= $s; ?>" <?= (isset($_POST['semester']) && (int)$_POST['semester'] === $s) ? 'selected' : ($s === 5 ? 'selected' : ''); ?>>
                                        Sem <?= $s; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="academic_year" class="form-label fw-semibold">Academic Year</label>
                            <select class="form-select" id="academic_year" name="academic_year">
                                <option value="2025-2026" selected>2025-2026</option>
                                <option value="2024-2025">2024-2025</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="password" class="form-label fw-semibold">Create Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="At least 6 characters" required minlength="6">
                                <button class="btn btn-outline-secondary toggle-password-visibility" type="button" data-target="password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Password must be at least 6 characters.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat password" required minlength="6">
                                <button class="btn btn-outline-secondary toggle-password-visibility" type="button" data-target="confirm_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Please repeat your chosen password.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-navy w-100 py-2 fs-6 shadow-sm mb-3">
                        <i class="bi bi-check2-circle me-1"></i> Register Student Account
                    </button>

                    <div class="text-center text-muted small">
                        Already have an account? <a href="student_login.php" class="text-primary fw-semibold">Sign in here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

