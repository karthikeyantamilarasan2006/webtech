<?php
/**
 * Shared Public & Student Navigation Bar
 * Project: Student Feedback and Course Evaluation System
 */

// If header.php was not included, compute $baseUrl safely
if (!isset($baseUrl)) {
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $baseUrl = (in_array(basename($scriptPath), ['auth', 'student', 'admin', 'config'])) ? '../' : './';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand d-flex align-items-center" href="<?= $baseUrl; ?>index.php">
            <i class="bi bi-mortarboard-fill me-2 fs-3 text-info"></i>
            <span>EduFeedback <span class="badge bg-light text-primary fs-7 fw-normal ms-1">v1.0</span></span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : ''; ?>" href="<?= $baseUrl; ?>index.php">
                        <i class="bi bi-house-door me-1"></i> Home
                    </a>
                </li>

                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student'): ?>
                    <!-- Student Nav Options -->
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'active' : ''; ?>" href="<?= $baseUrl; ?>student/dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i> My Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'feedback_form.php') ? 'active' : ''; ?>" href="<?= $baseUrl; ?>student/feedback_form.php">
                            <i class="bi bi-pencil-square me-1"></i> Submit Evaluation
                        </a>
                    </li>
                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link dropdown-toggle btn btn-sm btn-outline-light px-3 py-1 text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['full_name'] ?? 'Student'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><span class="dropdown-item-text text-muted small"><i class="bi bi-card-text me-1"></i>Roll: <?= htmlspecialchars($_SESSION['roll_number'] ?? ''); ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= $baseUrl; ?>auth/logout.php">
                                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>

                <?php elseif (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'superadmin', 'coordinator'])): ?>
                    <!-- Admin Quick Nav Option -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $baseUrl; ?>admin/dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i> Admin Console
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-warning" href="<?= $baseUrl; ?>auth/logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                        </a>
                    </li>

                <?php else: ?>
                    <!-- Guest Options -->
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'student_login.php') ? 'active' : ''; ?>" href="<?= $baseUrl; ?>auth/student_login.php">
                            <i class="bi bi-person-fill me-1"></i> Student Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'student_register.php') ? 'active' : ''; ?>" href="<?= $baseUrl; ?>auth/student_register.php">
                            <i class="bi bi-person-plus-fill me-1"></i> Register
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-outline-light btn-sm px-3 py-1 rounded-pill" href="<?= $baseUrl; ?>auth/admin_login.php">
                            <i class="bi bi-shield-lock me-1"></i> Admin Portal
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Theme Switcher & Color Customizer Dropdown -->
                <li class="nav-item dropdown ms-lg-1">
                    <button class="btn btn-sm btn-outline-light d-flex align-items-center gap-1 rounded-pill px-3 py-1 shadow-none" type="button" id="themeDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Customize Colors & Theme">
                        <i class="bi bi-palette-fill"></i>
                        <span class="d-none d-md-inline small fw-semibold">Theme</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end theme-dropdown-menu" aria-labelledby="themeDropdown">
                        <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                            <span class="fw-bold fs-7 text-uppercase" style="letter-spacing: 0.05em;"><i class="bi bi-paint-bucket me-1"></i> Color Themes</span>
                            <span class="badge bg-primary-subtle text-primary small">SaaS Palettes</span>
                        </div>
                        
                        <!-- Preset Theme 1: Ocean Blue -->
                        <button type="button" class="theme-option-btn" data-theme="ocean-blue">
                            <div class="d-flex align-items-center gap-2">
                                <div class="theme-swatch-group">
                                    <span class="theme-swatch-dot" style="background:#0a2240;"></span>
                                    <span class="theme-swatch-dot" style="background:#0284c7;"></span>
                                    <span class="theme-swatch-dot" style="background:#f4f7fb;"></span>
                                </div>
                                <span>Ocean Blue</span>
                            </div>
                        </button>

                        <!-- Preset Theme 2: Dark Mode -->
                        <button type="button" class="theme-option-btn" data-theme="dark-mode">
                            <div class="d-flex align-items-center gap-2">
                                <div class="theme-swatch-group">
                                    <span class="theme-swatch-dot" style="background:#0f172a;"></span>
                                    <span class="theme-swatch-dot" style="background:#3b82f6;"></span>
                                    <span class="theme-swatch-dot" style="background:#1e293b;"></span>
                                </div>
                                <span>Dark Mode</span>
                            </div>
                        </button>

                        <!-- Preset Theme 3: Emerald Green -->
                        <button type="button" class="theme-option-btn" data-theme="emerald-green">
                            <div class="d-flex align-items-center gap-2">
                                <div class="theme-swatch-group">
                                    <span class="theme-swatch-dot" style="background:#064e3b;"></span>
                                    <span class="theme-swatch-dot" style="background:#10b981;"></span>
                                    <span class="theme-swatch-dot" style="background:#f0fdf4;"></span>
                                </div>
                                <span>Emerald Green</span>
                            </div>
                        </button>

                        <!-- Preset Theme 4: Purple Modern -->
                        <button type="button" class="theme-option-btn" data-theme="purple-modern">
                            <div class="d-flex align-items-center gap-2">
                                <div class="theme-swatch-group">
                                    <span class="theme-swatch-dot" style="background:#4c1d95;"></span>
                                    <span class="theme-swatch-dot" style="background:#8b5cf6;"></span>
                                    <span class="theme-swatch-dot" style="background:#faf5ff;"></span>
                                </div>
                                <span>Purple Modern</span>
                            </div>
                        </button>

                        <!-- Preset Theme 5: Rose Pink -->
                        <button type="button" class="theme-option-btn" data-theme="rose-pink">
                            <div class="d-flex align-items-center gap-2">
                                <div class="theme-swatch-group">
                                    <span class="theme-swatch-dot" style="background:#881337;"></span>
                                    <span class="theme-swatch-dot" style="background:#e11d48;"></span>
                                    <span class="theme-swatch-dot" style="background:#fff1f2;"></span>
                                </div>
                                <span>Rose Pink</span>
                            </div>
                        </button>

                        <!-- Preset Theme 6: Midnight -->
                        <button type="button" class="theme-option-btn" data-theme="midnight">
                            <div class="d-flex align-items-center gap-2">
                                <div class="theme-swatch-group">
                                    <span class="theme-swatch-dot" style="background:#030712;"></span>
                                    <span class="theme-swatch-dot" style="background:#38bdf8;"></span>
                                    <span class="theme-swatch-dot" style="background:#0b1329;"></span>
                                </div>
                                <span>Midnight</span>
                            </div>
                        </button>

                        <!-- Custom Studio Box -->
                        <div class="custom-color-studio">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <small class="fw-bold text-uppercase" style="font-size:0.7rem;"><i class="bi bi-sliders me-1"></i>Custom Color Studio</small>
                            </div>
                            <div class="color-input-row">
                                <label for="customPrimaryColor">Primary:</label>
                                <input type="color" id="customPrimaryColor" value="#0a2240">
                            </div>
                            <div class="color-input-row">
                                <label for="customSecondaryColor">Secondary:</label>
                                <input type="color" id="customSecondaryColor" value="#1a4a82">
                            </div>
                            <div class="color-input-row">
                                <label for="customAccentColor">Accent:</label>
                                <input type="color" id="customAccentColor" value="#0284c7">
                            </div>
                            <div class="color-input-row">
                                <label for="customBgColor">Background:</label>
                                <input type="color" id="customBgColor" value="#f4f7fb">
                            </div>
                            <div class="d-flex gap-1 mt-2">
                                <button type="button" id="applyCustomThemeBtn" class="btn btn-navy btn-sm flex-grow-1" style="font-size:0.75rem; padding: 4px 8px;">
                                    Apply Custom
                                </button>
                                <button type="button" id="resetDefaultThemeBtn" class="btn btn-outline-secondary btn-sm" style="font-size:0.75rem; padding: 4px 8px;" title="Reset to Default">
                                    Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
