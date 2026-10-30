<?php
/**
 * Administrator Sidebar Navigation Component
 * Project: Student Feedback and Course Evaluation System
 */

$currentScript = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar shadow-sm">
    <div class="sidebar-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <i class="bi bi-shield-check text-info fs-3 me-2"></i>
            <div>
                <h6 class="mb-0 text-white fw-bold">Admin Console</h6>
                <small class="text-secondary" style="font-size: 0.75rem;">Course Evaluation System</small>
            </div>
        </div>
    </div>

    <div class="px-3 py-3 border-bottom border-secondary border-opacity-25 bg-black bg-opacity-20">
        <div class="d-flex align-items-center">
            <div class="bg-primary bg-opacity-25 text-info rounded-circle p-2 me-2">
                <i class="bi bi-person-gear fs-5"></i>
            </div>
            <div class="overflow-hidden">
                <div class="text-white small fw-bold text-truncate"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator'); ?></div>
                <div class="text-secondary small" style="font-size:0.75rem;"><span class="badge bg-success-subtle text-success">Super Admin</span></div>
            </div>
        </div>
    </div>

    <nav class="nav flex-column py-3 flex-grow-1">
        <div class="px-3 pb-2 text-uppercase text-secondary fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Overview</div>
        
        <a class="nav-link <?= ($currentScript === 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <div class="px-3 pt-3 pb-2 text-uppercase text-secondary fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Academic Management</div>
        
        <a class="nav-link <?= ($currentScript === 'departments.php') ? 'active' : ''; ?>" href="departments.php">
            <i class="bi bi-building"></i>
            <span>Departments</span>
        </a>

        <a class="nav-link <?= ($currentScript === 'courses.php') ? 'active' : ''; ?>" href="courses.php">
            <i class="bi bi-journal-bookmark-fill"></i>
            <span>Courses</span>
        </a>

        <a class="nav-link <?= ($currentScript === 'faculty.php') ? 'active' : ''; ?>" href="faculty.php">
            <i class="bi bi-people-fill"></i>
            <span>Faculty Members</span>
        </a>

        <a class="nav-link <?= ($currentScript === 'students.php') ? 'active' : ''; ?>" href="students.php">
            <i class="bi bi-mortarboard-fill"></i>
            <span>Students</span>
        </a>

        <div class="px-3 pt-3 pb-2 text-uppercase text-secondary fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Evaluation & Analytics</div>
        
        <a class="nav-link <?= ($currentScript === 'feedback.php') ? 'active' : ''; ?>" href="feedback.php">
            <i class="bi bi-chat-left-quote-fill"></i>
            <span>Feedback Records</span>
        </a>

        <a class="nav-link <?= ($currentScript === 'reports.php') ? 'active' : ''; ?>" href="reports.php">
            <i class="bi bi-bar-chart-line-fill"></i>
            <span>Reports & Analytics</span>
        </a>

        <div class="px-3 pt-3 pb-2 text-uppercase text-secondary fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Maintenance</div>
        
        <a class="nav-link" href="../config/setup.php">
            <i class="bi bi-database-check"></i>
            <span>Database Setup</span>
        </a>

        <a class="nav-link text-danger mt-auto pt-3 border-top border-secondary border-opacity-25" href="../auth/logout.php">
            <i class="bi bi-box-arrow-right"></i>
            <span>Sign Out</span>
        </a>
    </nav>
</aside>

