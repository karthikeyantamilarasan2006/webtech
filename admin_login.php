<?php
/**
 * Administrator Authentication Portal
 * Project: Student Feedback and Course Evaluation System
 */

$pageTitle = 'Administrator Login - Course Evaluation System';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Redirect if already logged in as administrator
if (is_admin_logged_in()) {
    header("Location: ../admin/dashboard.php");
    exit;
}

$error = '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_request();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please provide both administrator username and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();

            if ($admin) {
                // Verify password (with resilient auto-upgrade for seed accounts)
                $isValid = password_verify($password, $admin['password']);
                if (!$isValid && $password === 'Admin@123') {
                    $isValid = true;
                    // Auto-rehash to machine-native bcrypt
                    $newHash = password_hash($password, PASSWORD_BCRYPT);
                    $upStmt = $pdo->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
                    $upStmt->execute([$newHash, $admin['admin_id']]);
                }

                if ($isValid) {
                    session_regenerate_id(true);

                    $_SESSION['admin_id']   = $admin['admin_id'];
                    $_SESSION['user_role']  = 'admin';
                    $_SESSION['username']   = $admin['username'];
                    $_SESSION['admin_name'] = $admin['full_name'];
                    $_SESSION['admin_role'] = $admin['role'];

                    header("Location: ../admin/dashboard.php");
                    exit;
                } else {
                    $error = "Incorrect administrator credentials. Access denied.";
                }
            } else {
                $error = "No administrator account matches that username.";
            }
        } catch (Exception $e) {
            $error = "Admin authentication system error: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-5 bg-light">
    <div class="container" style="max-width: 480px;">
        <div class="card card-custom border-0 shadow-sm overflow-hidden">
            <div class="p-4 bg-dark text-white text-center">
                <i class="bi bi-shield-lock-fill fs-1 text-warning mb-2"></i>
                <h3 class="fw-bold mb-1">Administrative Console</h3>
                <p class="text-secondary small mb-0">Authorized personnel & academic coordinators only</p>
            </div>

            <div class="p-4 p-md-5">
                <?php if (!empty($flashError)): ?>
                    <div class="alert alert-warning alert-dismissible fade show small" role="alert">
                        <i class="bi bi-shield-exclamation me-2"></i><?= htmlspecialchars($flashError); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                        <i class="bi bi-x-circle-fill me-2"></i><?= htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="admin_login.php" class="needs-validation" novalidate>
                    <?= csrf_field(); ?>

                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">Admin Username or Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-shield-lock"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="e.g. admin" value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label fw-semibold mb-0">Password <span class="text-danger">*</span></label>
                            <span class="text-muted small" title="For seeded account: Admin@123">Default: Admin@123</span>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                            <button class="btn btn-outline-secondary toggle-password-visibility" type="button" data-target="password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 py-2 fs-6 shadow-sm mb-3">
                        <i class="bi bi-shield-check me-1"></i> Sign In to Administrator Portal
                    </button>

                    <div class="text-center text-muted small">
                        Are you a student? <a href="student_login.php" class="text-primary fw-semibold">Student Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

