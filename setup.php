<?php
/**
 * Automated Database Setup & Health Check Tool
 * Project: Student Feedback and Course Evaluation System
 *
 * This script allows one-click installation and verification of the database,
 * seeds all tables with realistic academic data, and synchronizes bcrypt passwords.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'student_feedback_system');
define('DB_USER', 'root');
define('DB_PASS', '');

$message = '';
$status = '';
$tableStats = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    try {
        // Connect to MySQL server without selecting database first
        $pdoServer = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $sqlFile = __DIR__ . '/../database/database.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("database.sql file was not found at: " . htmlspecialchars($sqlFile));
        }

        $sqlContent = file_get_contents($sqlFile);

        // Execute schema queries
        $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdoServer->exec("USE `" . DB_NAME . "`;");

        // Execute the entire database.sql
        $pdoServer->exec($sqlContent);

        // Compute genuine bcrypt hashes for the running PHP version
        $adminPasswordHash = password_hash('Admin@123', PASSWORD_BCRYPT);
        $studentPasswordHash = password_hash('Student@123', PASSWORD_BCRYPT);

        // Update seeded admin password
        $stmtAdmin = $pdoServer->prepare("UPDATE `admins` SET `password` = ? WHERE `username` = 'admin'");
        $stmtAdmin->execute([$adminPasswordHash]);

        // Update seeded students password
        $stmtStudents = $pdoServer->prepare("UPDATE `students` SET `password` = ?");
        $stmtStudents->execute([$studentPasswordHash]);

        $message = "Database 'student_feedback_system' was successfully initialized and seeded with all tables and sample data!";
        $status = 'success';
    } catch (Exception $e) {
        $message = "Installation Error: " . $e->getMessage();
        $status = 'danger';
    }
}

// Check database status and table counts
$isInstalled = false;
try {
    $pdoTest = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $tables = ['departments', 'courses', 'faculty', 'course_faculty', 'students', 'admins', 'feedback_submissions', 'feedback'];
    foreach ($tables as $t) {
        $q = $pdoTest->query("SELECT COUNT(*) AS total FROM `" . $t . "`");
        $row = $q->fetch(PDO::FETCH_ASSOC);
        $tableStats[$t] = $row['total'];
    }
    $isInstalled = true;
} catch (Exception $e) {
    $isInstalled = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup & Diagnostics - Student Feedback System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f0f4f8;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #1e293b;
        }
        .setup-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .header-banner {
            background: linear-gradient(135deg, #0c2340 0%, #1a4a82 100%);
            color: #ffffff;
            border-radius: 12px 12px 0 0;
            padding: 30px;
        }
    </style>
</head>
<body class="py-5">
    <div class="container" style="max-width: 820px;">
        <div class="setup-card overflow-hidden">
            <div class="header-banner">
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-database-check fs-1 me-3 text-info"></i>
                    <div>
                        <h2 class="h4 mb-0 fw-bold">Database Setup & Diagnostics</h2>
                        <small class="text-light opacity-75">Student Feedback and Course Evaluation System (CSE Mini-Project)</small>
                    </div>
                </div>
            </div>

            <div class="p-4 p-md-5">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $status; ?> alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                        <i class="bi <?= ($status === 'success') ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger'; ?> fs-4 me-3"></i>
                        <div><?= htmlspecialchars($message); ?></div>
                    </div>
                <?php endif; ?>

                <div class="card mb-4 border-0 bg-light">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-hdd-network me-2 text-primary"></i>Server Connection Status</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><strong>Database Host:</strong> <code><?= htmlspecialchars(DB_HOST); ?></code></li>
                            <li class="mb-2"><strong>Database Name:</strong> <code><?= htmlspecialchars(DB_NAME); ?></code></li>
                            <li class="mb-2"><strong>Database User:</strong> <code><?= htmlspecialchars(DB_USER); ?></code></li>
                            <li>
                                <strong>Installation Status:</strong>
                                <?php if ($isInstalled): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Installed & Ready</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i>Not Installed / Missing Tables</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>

                <?php if ($isInstalled): ?>
                    <h5 class="fw-bold mb-3"><i class="bi bi-table me-2 text-primary"></i>Database Tables & Record Counts</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Table Name</th>
                                    <th>Description</th>
                                    <th class="text-center">Records</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $descriptions = [
                                    'departments' => 'Academic departments (CSE, ECE, IT, MECH)',
                                    'courses' => 'Subjects offered with credits and semester',
                                    'faculty' => 'Teaching professors and designations',
                                    'course_faculty' => 'Mapping of faculty to courses',
                                    'students' => 'Student credentials, roll numbers & department',
                                    'admins' => 'Administrative login accounts',
                                    'feedback_submissions' => 'Duplicate submission tracking receipts',
                                    'feedback' => 'Evaluation ratings and student comments'
                                ];
                                foreach ($tableStats as $table => $count): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($table); ?></strong></td>
                                        <td class="text-muted small"><?= $descriptions[$table] ?? 'Core table'; ?></td>
                                        <td class="text-center"><span class="badge bg-primary rounded-pill"><?= $count; ?></span></td>
                                        <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> OK</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="card mb-4 border-info">
                        <div class="card-header bg-info-subtle fw-bold text-dark">
                            <i class="bi bi-key-fill me-2"></i>Default Credentials for Testing
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 border rounded bg-white">
                                        <div class="fw-bold text-primary mb-1"><i class="bi bi-shield-lock me-1"></i>Administrator Login</div>
                                        <div class="small">Username: <code>admin</code></div>
                                        <div class="small">Password: <code>Admin@123</code></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded bg-white">
                                        <div class="fw-bold text-success mb-1"><i class="bi bi-person-badge me-1"></i>Sample Student Login</div>
                                        <div class="small">Roll Number: <code>2023CSE001</code></div>
                                        <div class="small">Password: <code>Student@123</code></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <form method="POST" onsubmit="return confirm('Running installation will create or re-initialize tables and seed sample data. Continue?');">
                        <input type="hidden" name="action" value="install">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-play-circle-fill me-2"></i><?= $isInstalled ? 'Re-run Setup & Reset Seed Data' : 'Run One-Click Database Setup'; ?>
                        </button>
                    </form>

                    <div class="d-flex gap-2">
                        <a href="../index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-house me-1"></i>Go to Home Page
                        </a>
                        <a href="../auth/admin_login.php" class="btn btn-dark">
                            <i class="bi bi-shield-lock me-1"></i>Admin Login
                        </a>
                        <a href="../auth/student_login.php" class="btn btn-success">
                            <i class="bi bi-person me-1"></i>Student Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

