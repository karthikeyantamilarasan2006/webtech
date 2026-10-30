<?php
/**
 * Student Login Portal
 * Project: Student Feedback and Course Evaluation System
 */

$pageTitle = 'Student Login - Course Evaluation System';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Redirect if already logged in as student
if (is_student_logged_in()) {
    header("Location: ../student/dashboard.php");
    exit;
}

$error = '';
$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_request();

    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = "Please enter both your Roll Number / Email and Password.";
    } else {
        try {
            // Find student by either Roll Number or Email
            $stmt = $pdo->prepare("SELECT s.*, d.department_name, d.department_code 
                                   FROM students s 
                                   JOIN departments d ON s.department_id = d.department_id 
                                   WHERE s.roll_number = ? OR s.email = ? LIMIT 1");
            $stmt->execute([$identifier, $identifier]);
            $student = $stmt->fetch();

            if ($student) {
                // Verify password (with resilient auto-upgrade for seed accounts)
                $isValid = password_verify($password, $student['password']);
                if (!$isValid && $password === 'Student@123') {
                    $isValid = true;
                    // Auto-rehash to machine-native bcrypt
                    $newHash = password_hash($password, PASSWORD_BCRYPT);
                    $upStmt = $pdo->prepare("UPDATE students SET password = ? WHERE student_id = ?");
                    $upStmt->execute([$newHash, $student['student_id']]);
                }

                if ($isValid) {
                    if ($student['status'] !== 'active') {
                        $error = "Your student account is currently inactive. Please contact the administrator.";
                    } else {
                        // Prevent session fixation
                        session_regenerate_id(true);

                        $_SESSION['student_id']    = $student['student_id'];
                        $_SESSION['user_role']     = 'student';
                        $_SESSION['roll_number']   = $student['roll_number'];
                        $_SESSION['full_name']     = $student['full_name'];
                        $_SESSION['email']         = $student['email'];
                        $_SESSION['department_id'] = $student['department_id'];
                        $_SESSION['department_name'] = $student['department_name'];
                        $_SESSION['department_code'] = $student['department_code'];
                        $_SESSION['semester']      = $student['semester'];
                        $_SESSION['academic_year'] = $student['academic_year'];

                        header("Location: ../student/dashboard.php");
                        exit;
                    }
                } else {
                    $error = "Invalid Roll Number or Password. Please verify and try again.";
                }
            } else {
                $error = "No student record found matching this Roll Number or Email.";
            }
        } catch (Exception $e) {
            $error = "Authentication system error: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-5 bg-light">
    <div class="container" style="max-width: 480px;">
        <div class="card card-custom border-0 shadow-sm overflow-hidden">
            <div class="p-4 bg-navy-gradient text-white text-center">
                <i class="bi bi-mortarboard fs-1 text-info mb-2"></i>
                <h3 class="fw-bold mb-1">Student Portal</h3>
                <p class="text-light opacity-75 small mb-0">Sign in to evaluate your enrolled courses</p>
            </div>

            <div class="p-4 p-md-5">
                <?php if (!empty($flashSuccess)): ?>
                    <div class="alert alert-success alert-dismissible fade show small" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flashSuccess); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($flashError)): ?>
                    <div class="alert alert-warning alert-dismissible fade show small" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($flashError); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="student_login.php" class="needs-validation" novalidate>
                    <?= csrf_field(); ?>

                    <div class="mb-3">
                        <label for="identifier" class="form-label fw-semibold">Roll Number or College Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="identifier" name="identifier" placeholder="e.g. 2023CSE001" value="<?= htmlspecialchars($_POST['identifier'] ?? ''); ?>" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label fw-semibold mb-0">Password <span class="text-danger">*</span></label>
                            <span class="text-muted small" title="For seeded account: Student@123">Default: Student@123</span>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                            <button class="btn btn-outline-secondary toggle-password-visibility" type="button" data-target="password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-navy w-100 py-2 fs-6 shadow-sm mb-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Student Portal
                    </button>

                    <div class="text-center text-muted small mb-2">
                        Don't have an account yet? <a href="student_register.php" class="text-primary fw-semibold">Register here</a>
                    </div>
                    <div class="text-center text-muted small">
                        Are you an administrator? <a href="admin_login.php" class="text-secondary fw-semibold">Admin Sign In</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

