<?php
/**
 * Feedback Submission Confirmation Receipt
 * Project: Student Feedback and Course Evaluation System
 */

$pageTitle = 'Submission Receipt - Course Evaluation System';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_student_login();
$student = get_current_student();

$receipt = $_SESSION['feedback_receipt'] ?? null;
if (!$receipt) {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-5 bg-light">
    <div class="container" style="max-width: 650px;">
        <div class="card card-custom border-0 shadow-sm overflow-hidden text-center">
            <div class="p-4 bg-success text-white">
                <i class="bi bi-patch-check-fill display-3 mb-2"></i>
                <h3 class="fw-bold mb-1">Feedback Submitted Successfully!</h3>
                <p class="text-white opacity-90 small mb-0">Your constructive evaluation has been safely recorded.</p>
            </div>

            <div class="p-4 p-md-5">
                <div class="card bg-light border-0 p-4 mb-4 text-start">
                    <h6 class="fw-bold text-navy mb-3 text-uppercase small border-bottom pb-2">
                        <i class="bi bi-receipt me-1"></i> Official Evaluation Receipt
                    </h6>

                    <div class="row g-2 mb-2">
                        <div class="col-sm-5 text-muted small">Course Evaluated:</div>
                        <div class="col-sm-7 fw-bold text-navy">
                            <?= htmlspecialchars($receipt['course_code']); ?> - <?= htmlspecialchars($receipt['course_name']); ?>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-sm-5 text-muted small">Faculty Member:</div>
                        <div class="col-sm-7 fw-semibold text-dark">
                            <?= htmlspecialchars($receipt['faculty_name']); ?>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-sm-5 text-muted small">Calculated Score:</div>
                        <div class="col-sm-7">
                            <span class="badge bg-warning text-dark px-3 py-1 fw-bold fs-7">
                                &#9733; <?= number_format($receipt['average_rating'], 2); ?> / 5.00
                            </span>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-sm-5 text-muted small">Confidentiality Status:</div>
                        <div class="col-sm-7">
                            <?php if ($receipt['is_anonymous']): ?>
                                <span class="badge bg-dark px-2 py-1"><i class="bi bi-incognito me-1"></i>Anonymous Submission</span>
                                <small class="text-muted d-block mt-1">Identity detached from database evaluation records.</small>
                            <?php else: ?>
                                <span class="badge bg-primary px-2 py-1"><i class="bi bi-person-check me-1"></i>Identified Submission</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-sm-5 text-muted small">Logged Timestamp:</div>
                        <div class="col-sm-7 small text-muted">
                            <?= date('F d, Y - h:i:s A', strtotime($receipt['timestamp'])); ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="dashboard.php" class="btn btn-navy px-4 py-2">
                        <i class="bi bi-speedometer2 me-1"></i> Return to Dashboard
                    </a>
                    <a href="feedback_form.php" class="btn btn-outline-primary px-4 py-2">
                        <i class="bi bi-pencil-square me-1"></i> Evaluate Another Course
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php 
// Clean up receipt session variable
unset($_SESSION['feedback_receipt']);
require_once __DIR__ . '/../includes/footer.php'; 
?>

