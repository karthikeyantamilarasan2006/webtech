<?php
/**
 * Shared Footer & Script Inclusions
 * Project: Student Feedback and Course Evaluation System
 */

if (!isset($baseUrl)) {
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $baseUrl = (in_array(basename($scriptPath), ['auth', 'student', 'admin', 'config'])) ? '../' : './';
}
?>
    <footer class="bg-white border-top py-4 mt-auto no-print">
        <div class="container text-center">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-start mb-2 mb-md-0">
                    <p class="text-muted small mb-0">
                        &copy; <?= date('Y'); ?> <strong>Student Feedback and Course Evaluation System</strong>. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="badge bg-light text-secondary border">Academic Mini-Project (CSE)</span>
                    <a href="<?= $baseUrl; ?>config/setup.php" class="text-muted text-decoration-none small ms-3">
                        <i class="bi bi-gear-fill me-1"></i>Setup Check
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Common JavaScript -->
    <script src="<?= $baseUrl; ?>assets/js/script.js"></script>

    <!-- Theme Switcher & Color Customizer -->
    <script src="<?= $baseUrl; ?>assets/js/theme_switcher.js"></script>

    <!-- Campus Assistant AI Chatbot Widget -->
    <?php require_once __DIR__ . '/chatbot.php'; ?>
    <script src="<?= $baseUrl; ?>assets/js/chatbot.js"></script>
</body>
</html>

