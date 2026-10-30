<?php
/**
 * Shared HTML Head & Asset Loader
 * Project: Student Feedback and Course Evaluation System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Compute base URL path dynamically for nested directories
$scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
// Determine depth relative to project root
if (basename($scriptPath) === 'auth' || basename($scriptPath) === 'student' || basename($scriptPath) === 'admin' || basename($scriptPath) === 'config') {
    $baseUrl = '../';
} else {
    $baseUrl = './';
}

$pageTitle = $pageTitle ?? 'Student Feedback & Course Evaluation System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= htmlspecialchars($pageTitle); ?> | Academic Portal</title>

    <!-- Early Theme Initialization Script (Prevents FOUT) -->
    <script>
        (function() {
            try {
                const savedTheme = localStorage.getItem('app_theme') || 'ocean-blue';
                document.documentElement.setAttribute('data-theme', savedTheme);
                if (savedTheme === 'custom') {
                    const custom = JSON.parse(localStorage.getItem('app_custom_theme') || '{}');
                    if (custom.primary) document.documentElement.style.setProperty('--theme-primary', custom.primary);
                    if (custom.secondary) document.documentElement.style.setProperty('--theme-secondary', custom.secondary);
                    if (custom.accent) document.documentElement.style.setProperty('--theme-accent', custom.accent);
                    if (custom.bg) document.documentElement.style.setProperty('--theme-bg', custom.bg);
                }
            } catch (e) {}
        })();
    </script>
    
    <!-- Google Fonts: Inter / Roboto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Custom Academic Stylesheet -->
    <link rel="stylesheet" href="<?= $baseUrl; ?>assets/css/style.css">
    
    <!-- Campus Assistant Chatbot Stylesheet -->
    <link rel="stylesheet" href="<?= $baseUrl; ?>assets/css/chatbot.css">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body>

