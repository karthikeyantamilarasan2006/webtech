<?php
/**
 * Public Landing Page
 * Project: Student Feedback and Course Evaluation System
 */

$pageTitle = 'Home - Student Feedback & Course Evaluation System';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

// Fetch quick live summary metrics for the landing hero
$stats = [
    'students' => 0,
    'courses'  => 0,
    'faculty'  => 0,
    'feedback' => 0
];

try {
    if (isset($pdo)) {
        $stats['students'] = $pdo->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn() ?: 0;
        $stats['courses']  = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn() ?: 0;
        $stats['faculty']  = $pdo->query("SELECT COUNT(*) FROM faculty")->fetchColumn() ?: 0;
        $stats['feedback'] = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn() ?: 0;
    }
} catch (Exception $e) {
    // If tables are not yet initialized, defaults remain 0
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main>
    <!-- Hero Banner -->
    <section class="bg-navy-gradient text-white py-5 position-relative overflow-hidden">
        <div class="container py-lg-4">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="badge bg-info text-dark px-3 py-2 rounded-pill fw-bold mb-3">
                        <i class="bi bi-mortarboard-fill me-1"></i> Quality Assurance & Academic Appraisal
                    </span>
                    <h1 class="display-4 fw-extrabold text-white mb-3">
                        Student Feedback & <br class="d-none d-md-inline">Course Evaluation System
                    </h1>
                    <p class="lead text-light opacity-90 mb-4 fs-5" style="max-width: 620px;">
                        A centralized, secure digital platform designed to collect constructive student evaluations, analyze teaching performance metrics, and foster continuous institutional improvement.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <?php if (is_student_logged_in()): ?>
                            <a href="student/dashboard.php" class="btn btn-primary btn-lg px-4 py-2 rounded-pill fw-semibold shadow">
                                <i class="bi bi-speedometer2 me-2"></i>Go to Student Dashboard
                            </a>
                            <a href="student/feedback_form.php" class="btn btn-outline-light btn-lg px-4 py-2 rounded-pill fw-semibold">
                                <i class="bi bi-pencil-square me-2"></i>Submit Feedback
                            </a>
                        <?php elseif (is_admin_logged_in()): ?>
                            <a href="admin/dashboard.php" class="btn btn-warning btn-lg px-4 py-2 rounded-pill fw-semibold shadow">
                                <i class="bi bi-speedometer2 me-2"></i>Open Admin Console
                            </a>
                        <?php else: ?>
                            <a href="auth/student_login.php" class="btn btn-primary btn-lg px-4 py-2 rounded-pill fw-semibold shadow">
                                <i class="bi bi-person-fill me-2"></i>Student Portal
                            </a>
                            <a href="auth/admin_login.php" class="btn btn-outline-light btn-lg px-4 py-2 rounded-pill fw-semibold">
                                <i class="bi bi-shield-lock-fill me-2"></i>Admin Console
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card bg-white text-dark shadow-lg border-0 rounded-4 p-4">
                        <h4 class="fw-bold mb-3 text-navy d-flex align-items-center">
                            <i class="bi bi-activity me-2 text-primary"></i>Live Platform Statistics
                        </h4>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3 text-center border">
                                    <h2 class="text-primary fw-bold mb-0"><?= number_format($stats['students']); ?></h2>
                                    <small class="text-muted fw-semibold">Active Students</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3 text-center border">
                                    <h2 class="text-info fw-bold mb-0"><?= number_format($stats['faculty']); ?></h2>
                                    <small class="text-muted fw-semibold">Faculty Evaluated</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3 text-center border">
                                    <h2 class="text-success fw-bold mb-0"><?= number_format($stats['courses']); ?></h2>
                                    <small class="text-muted fw-semibold">Courses Offered</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3 text-center border">
                                    <h2 class="text-warning fw-bold mb-0"><?= number_format($stats['feedback']); ?></h2>
                                    <small class="text-muted fw-semibold">Responses Logged</small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-primary mb-0 mt-3 small py-2 d-flex align-items-center">
                            <i class="bi bi-shield-check fs-5 me-2 text-primary"></i>
                            <div><strong>100% Privacy Protected:</strong> Anonymous feedback prevents identity tracking in administrative reports.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Pillars / Feature Cards -->
    <section class="py-5 bg-white">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="text-primary fw-bold text-uppercase small" style="letter-spacing: 0.1em;">Engineered For Academic Excellence</span>
                <h2 class="fw-bold text-navy mt-1">Key System Capabilities</h2>
                <p class="text-muted" style="max-width: 600px; margin: auto;">Designed according to university evaluation guidelines to guarantee objective appraisal, data integrity, and actionable metrics.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 card-custom border-0 p-4">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary mb-3">
                            <i class="bi bi-stars"></i>
                        </div>
                        <h5 class="fw-bold text-navy">7-Metric Evaluation Rubric</h5>
                        <p class="text-muted small">
                            Rate teaching effectiveness, subject knowledge, communication, course content, classroom interaction, doubt clarification, and overall satisfaction on an interactive 5-star scale.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 card-custom border-0 p-4">
                        <div class="stat-icon bg-success bg-opacity-10 text-success mb-3">
                            <i class="bi bi-incognito"></i>
                        </div>
                        <h5 class="fw-bold text-navy">Verified Confidentiality</h5>
                        <p class="text-muted small">
                            Students can choose anonymous submission with complete confidence. An identity-decoupled verification receipt prevents duplicate submissions while keeping evaluations private.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 card-custom border-0 p-4">
                        <div class="stat-icon bg-info bg-opacity-10 text-info mb-3">
                            <i class="bi bi-bar-chart-fill"></i>
                        </div>
                        <h5 class="fw-bold text-navy">Comprehensive Analytics</h5>
                        <p class="text-muted small">
                            Administrators get real-time visual charts (Chart.js), faculty performance rankings, department breakdowns, and print-ready summary reports for accreditation.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Role Gateways / Quick Access -->
    <section class="py-5 bg-light">
        <div class="container py-3">
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-6">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white border-start border-primary border-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded-3 p-3 me-3">
                                <i class="bi bi-person-badge fs-3"></i>
                            </div>
                            <div>
                                <h4 class="fw-bold mb-0 text-navy">Student Portal</h4>
                                <small class="text-muted">For enrolled undergraduate & postgraduate students</small>
                            </div>
                        </div>
                        <p class="text-muted">
                            Access assigned course evaluations, rate your professors across academic criteria, submit constructive comments, and review your completion receipts.
                        </p>
                        <div class="mt-auto d-flex gap-2">
                            <a href="auth/student_login.php" class="btn btn-navy px-4">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Student Sign In
                            </a>
                            <a href="auth/student_register.php" class="btn btn-outline-primary px-3">
                                <i class="bi bi-person-plus me-1"></i> Create Account
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white border-start border-dark border-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-dark text-white rounded-3 p-3 me-3">
                                <i class="bi bi-shield-lock-fill fs-3"></i>
                            </div>
                            <div>
                                <h4 class="fw-bold mb-0 text-navy">Administrative Portal</h4>
                                <small class="text-muted">For academic heads, coordinators & system admins</small>
                            </div>
                        </div>
                        <p class="text-muted">
                            Manage department curricula, assign faculty to course batches, inspect evaluated scores, analyze visual satisfaction charts, and generate institutional appraisal reports.
                        </p>
                        <div class="mt-auto">
                            <a href="auth/admin_login.php" class="btn btn-dark px-4">
                                <i class="bi bi-shield-check me-1"></i> Admin Sign In
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

