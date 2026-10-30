<?php
/**
 * Environment Credentials Configuration Template
 * Project: Student Feedback and Course Evaluation System
 *
 * INSTRUCTIONS FOR LIVE HOSTING (InfinityFree / cPanel):
 * 1. Copy this file and rename it to: config/env.php
 * 2. Fill in your live MySQL database credentials provided by your hosting control panel.
 * 3. Never commit config/env.php to GitHub (it is already included in .gitignore).
 */

return [
    // Database Configuration
    'DB_HOST' => 'localhost',              // e.g. 'sql200.epizy.com' on InfinityFree
    'DB_NAME' => 'student_feedback_system',// e.g. 'if0_38123456_feedback'
    'DB_USER' => 'root',                   // e.g. 'if0_38123456'
    'DB_PASS' => '',                       // Your hosting vPanel / MySQL password
    'DB_CHARSET' => 'utf8mb4',

    // Optional: OpenAI API Key for Campus Assistant Chatbot
    // If left empty, the built-in offline Academic Knowledge Engine will answer student questions automatically
    'OPENAI_API_KEY' => '',
    'OPENAI_MODEL'   => 'gpt-4o-mini'
];

