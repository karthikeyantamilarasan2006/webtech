<?php
/**
 * Visual Analytics & Accreditation Summary Reports
 * Project: Student Feedback and Course Evaluation System
 */

$pageTitle = 'Reports & Analytics - Admin Console';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin_login();

// Filter parameters
$filterDept    = (int)($_GET['department_id'] ?? 0);
$filterCourse  = (int)($_GET['course_id'] ?? 0);
$filterFaculty = (int)($_GET['faculty_id'] ?? 0);
$filterSem     = (int)($_GET['semester'] ?? 0);

$departments = $pdo->query("SELECT department_id, department_code, department_name FROM departments ORDER BY department_name ASC")->fetchAll();
$courses     = $pdo->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_code ASC")->fetchAll();
$facultyList = $pdo->query("SELECT faculty_id, full_name, faculty_code FROM faculty ORDER BY full_name ASC")->fetchAll();

// Dynamic WHERE condition
$where = " WHERE 1=1";
$params = [];

if ($filterDept > 0) {
    $where .= " AND fb.department_id = ?";
    $params[] = $filterDept;
}
if ($filterCourse > 0) {
    $where .= " AND fb.course_id = ?";
    $params[] = $filterCourse;
}
if ($filterFaculty > 0) {
    $where .= " AND fb.faculty_id = ?";
    $params[] = $filterFaculty;
}
if ($filterSem > 0) {
    $where .= " AND fb.semester = ?";
    $params[] = $filterSem;
}

// 1. Overall Summary Stats
$summaryStmt = $pdo->prepare("
    SELECT 
        COUNT(feedback_id) AS total_feedback,
        AVG(average_rating) AS institutional_avg,
        COUNT(DISTINCT course_id) AS courses_evaluated,
        COUNT(DISTINCT faculty_id) AS faculty_evaluated
    FROM feedback fb
    {$where}
");
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch();

// 2. Course-wise Performance Breakdown
$courseSummaryStmt = $pdo->prepare("
    SELECT c.course_code, c.course_name, c.semester, d.department_code,
           COUNT(fb.feedback_id) AS eval_count,
           AVG(fb.average_rating) AS course_avg,
           AVG(fb.teaching_effectiveness) AS avg_teaching,
           AVG(fb.subject_knowledge) AS avg_knowledge,
           AVG(fb.overall_satisfaction) AS avg_satisfaction
    FROM courses c
    JOIN departments d ON c.department_id = d.department_id
    LEFT JOIN feedback fb ON c.course_id = fb.course_id " . ($filterDept > 0 ? " AND fb.department_id = {$filterDept}" : "") . "
    GROUP BY c.course_id
    ORDER BY course_avg DESC
");
$courseSummaryStmt->execute();
$courseReports = $courseSummaryStmt->fetchAll();

// 3. Faculty Performance Ranking
$facultyRankStmt = $pdo->prepare("
    SELECT f.faculty_code, f.full_name, f.designation, d.department_code,
           COUNT(fb.feedback_id) AS eval_count,
           AVG(fb.average_rating) AS faculty_avg
    FROM faculty f
    JOIN departments d ON f.department_id = d.department_id
    LEFT JOIN feedback fb ON f.faculty_id = fb.faculty_id
    GROUP BY f.faculty_id
    ORDER BY faculty_avg DESC
");
$facultyRankStmt->execute();
$facultyRankings = $facultyRankStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Include Chart.js from Official CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-content">
        <!-- Print Header (Visible only when printed) -->
        <div class="d-none d-print-block mb-4 text-center border-bottom pb-3">
            <h2 class="fw-bold text-navy mb-1">COLLEGE OF ENGINEERING & TECHNOLOGY</h2>
            <h4 class="fw-semibold text-muted mb-1">Academic Quality Appraisal & Student Feedback Report</h4>
            <div class="small text-muted">Generated on: <?= date('F d, Y h:i A'); ?> | Official Accreditation Record</div>
        </div>

        <!-- Dashboard Action Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 no-print">
            <div>
                <h3 class="fw-bold text-navy mb-1">Institutional Feedback Analytics & Reports</h3>
                <p class="text-muted small mb-0">Visual data representations, faculty appraisal indices, and downloadable summary reports.</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-navy btn-sm px-3 shadow-sm" onclick="window.print();">
                    <i class="bi bi-printer-fill me-1"></i> Print Appraisal Report
                </button>
            </div>
        </div>

        <!-- Filter Form (Hidden on Print) -->
        <div class="card card-custom border-0 p-3 mb-4 bg-white no-print">
            <form method="GET" action="reports.php" class="row g-2 align-items-end">
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
                                <?= htmlspecialchars($c['course_code']); ?>
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
                        <i class="bi bi-filter me-1"></i> Apply
                    </button>
                    <a href="reports.php" class="btn btn-outline-secondary btn-sm px-2" title="Reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- High-level Metric Highlights -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="p-3 bg-white rounded-3 border text-center shadow-none">
                    <span class="text-muted small fw-semibold text-uppercase">Total Evaluations</span>
                    <h3 class="fw-bold text-navy mb-0 mt-1"><?= number_format($summary['total_feedback'] ?? 0); ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-white rounded-3 border text-center shadow-none">
                    <span class="text-muted small fw-semibold text-uppercase">Institutional Average</span>
                    <h3 class="fw-bold text-warning mb-0 mt-1">&#9733; <?= number_format($summary['institutional_avg'] ?? 0, 2); ?> <small class="text-muted fs-6">/ 5.0</small></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-white rounded-3 border text-center shadow-none">
                    <span class="text-muted small fw-semibold text-uppercase">Courses Appraised</span>
                    <h3 class="fw-bold text-primary mb-0 mt-1"><?= number_format($summary['courses_evaluated'] ?? 0); ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-white rounded-3 border text-center shadow-none">
                    <span class="text-muted small fw-semibold text-uppercase">Faculty Evaluated</span>
                    <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($summary['faculty_evaluated'] ?? 0); ?></h3>
                </div>
            </div>
        </div>

        <!-- 4 Visual Charts Grid -->
        <div class="row g-4 mb-4">
            <!-- 1. Faculty Performance Bar Chart -->
            <div class="col-lg-8">
                <div class="card card-custom border-0 p-4 h-100 bg-white">
                    <h5 class="fw-bold text-navy mb-1"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Faculty-wise Appraisal Ratings (Out of 5.0)</h5>
                    <p class="text-muted small mb-3">Comparison of overall teaching satisfaction index across faculty instructors.</p>
                    <div style="height: 320px; position: relative;">
                        <canvas id="facultyBarChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- 2. Satisfaction Distribution Pie Chart -->
            <div class="col-lg-4">
                <div class="card card-custom border-0 p-4 h-100 bg-white">
                    <h5 class="fw-bold text-navy mb-1"><i class="bi bi-pie-chart-fill me-2 text-success"></i>Student Satisfaction Ratio</h5>
                    <p class="text-muted small mb-3">Overall breakdown of student sentiment.</p>
                    <div style="height: 320px; position: relative;">
                        <canvas id="satisfactionPieChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- 3. Category Radar Chart -->
            <div class="col-lg-6">
                <div class="card card-custom border-0 p-4 h-100 bg-white">
                    <h5 class="fw-bold text-navy mb-1"><i class="bi bi-radar me-2 text-info"></i>Category-wise Academic Evaluation</h5>
                    <p class="text-muted small mb-3">Performance scores plotted across the 7 academic parameters.</p>
                    <div style="height: 320px; position: relative;">
                        <canvas id="categoryBarChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- 4. Submissions Timeline Line Chart -->
            <div class="col-lg-6">
                <div class="card card-custom border-0 p-4 h-100 bg-white">
                    <h5 class="fw-bold text-navy mb-1"><i class="bi bi-graph-up me-2 text-warning"></i>Feedback Submission Trend</h5>
                    <p class="text-muted small mb-3">Daily response collection velocity across the academic term.</p>
                    <div style="height: 320px; position: relative;">
                        <canvas id="trendLineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabular Summary 1: Faculty Performance Appraisal -->
        <div class="card card-custom border-0 overflow-hidden mb-4 bg-white">
            <div class="p-4 border-bottom">
                <h5 class="fw-bold text-navy mb-0"><i class="bi bi-trophy-fill me-2 text-warning"></i>Faculty Performance Appraisal Index</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Rank</th>
                            <th>Faculty Name & Designation</th>
                            <th>Department</th>
                            <th class="text-center">Reviews Logged</th>
                            <th class="text-center">Mean Score</th>
                            <th class="text-end pe-4">Appraisal Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach ($facultyRankings as $fr): 
                            $mean = (float)$fr['faculty_avg'];
                        ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted">#<?= $rank++; ?></td>
                                <td>
                                    <div class="fw-bold text-navy"><?= htmlspecialchars($fr['full_name']); ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($fr['designation']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border"><?= htmlspecialchars($fr['department_code']); ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-dark rounded-pill px-3"><?= $fr['eval_count']; ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($fr['eval_count'] > 0): ?>
                                        <span class="badge bg-warning text-dark fw-bold px-3 py-1">
                                            &#9733; <?= number_format($mean, 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if ($fr['eval_count'] == 0): ?>
                                        <span class="badge bg-light text-secondary border">Awaiting Evaluation</span>
                                    <?php elseif ($mean >= 4.5): ?>
                                        <span class="badge bg-success px-3 py-1">Outstanding (A+)</span>
                                    <?php elseif ($mean >= 4.0): ?>
                                        <span class="badge bg-primary px-3 py-1">Very Good (A)</span>
                                    <?php elseif ($mean >= 3.0): ?>
                                        <span class="badge bg-info text-dark px-3 py-1">Satisfactory (B)</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger px-3 py-1">Needs Improvement</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabular Summary 2: Course-wise Average Ratings -->
        <div class="card card-custom border-0 overflow-hidden mb-4 bg-white">
            <div class="p-4 border-bottom">
                <h5 class="fw-bold text-navy mb-0"><i class="bi bi-journal-check me-2 text-primary"></i>Course-wise Quality Appraisal</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Course Code & Subject Title</th>
                            <th>Dept & Sem</th>
                            <th class="text-center">Evaluations</th>
                            <th class="text-center">Teaching</th>
                            <th class="text-center">Subject Knowledge</th>
                            <th class="text-center">Overall Satisfaction</th>
                            <th class="text-end pe-4">Cumulative Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courseReports as $cr): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-navy"><?= htmlspecialchars($cr['course_name']); ?></div>
                                    <span class="badge bg-light text-secondary border"><?= htmlspecialchars($cr['course_code']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($cr['department_code']); ?></span>
                                    <span class="badge bg-secondary-subtle text-dark ms-1">Sem <?= $cr['semester']; ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border"><?= $cr['eval_count']; ?></span>
                                </td>
                                <td class="text-center small">
                                    <?= $cr['eval_count'] > 0 ? number_format((float)$cr['avg_teaching'], 2) : '-'; ?>
                                </td>
                                <td class="text-center small">
                                    <?= $cr['eval_count'] > 0 ? number_format((float)$cr['avg_knowledge'], 2) : '-'; ?>
                                </td>
                                <td class="text-center small">
                                    <?= $cr['eval_count'] > 0 ? number_format((float)$cr['avg_satisfaction'], 2) : '-'; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if ($cr['eval_count'] > 0): ?>
                                        <span class="badge bg-warning text-dark fw-bold px-2 py-1">
                                            &#9733; <?= number_format((float)$cr['course_avg'], 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">No submissions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Load Chart.js Controller Script -->
<script src="../assets/js/admin_charts.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

