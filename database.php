<?php
/**
 * Database Configuration & Connection Handler
 * Project: Student Feedback and Course Evaluation System
 *
 * Supports both Local Development (XAMPP defaults) and Production Hosting
 * (InfinityFree, cPanel, Cloud) via environment configuration.
 */

// Load custom environment credentials if config/env.php exists
$envFile = __DIR__ . '/env.php';
$envConfig = file_exists($envFile) ? require $envFile : [];

// Determine configuration parameters (env.php -> getenv() -> default XAMPP)
$dbHost    = $envConfig['DB_HOST']    ?? getenv('DB_HOST')    ?: 'localhost';
$dbName    = $envConfig['DB_NAME']    ?? getenv('DB_NAME')    ?: 'student_feedback_system';
$dbUser    = $envConfig['DB_USER']    ?? getenv('DB_USER')    ?: 'root';
$dbPass    = $envConfig['DB_PASS']    ?? getenv('DB_PASS')    ?: '';
$dbCharset = $envConfig['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4';

// Define constants for global accessibility
if (!defined('DB_HOST'))    define('DB_HOST', $dbHost);
if (!defined('DB_NAME'))    define('DB_NAME', $dbName);
if (!defined('DB_USER'))    define('DB_USER', $dbUser);
if (!defined('DB_PASS'))    define('DB_PASS', $dbPass);
if (!defined('DB_CHARSET')) define('DB_CHARSET', $dbCharset);

/**
 * Get the PDO Database Connection instance
 *
 * @return PDO
 * @throws Exception if connection fails
 */
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Check if the error is due to database not existing yet (Error Code 1049)
            if ($e->getCode() == 1049) {
                die(
                    '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; padding: 25px; border: 1px solid #f5c2c7; background-color: #f8d7da; border-radius: 8px; color: #842029;">' .
                    '<h3 style="margin-top:0;">Database Not Found</h3>' .
                    '<p>The database <strong>' . htmlspecialchars(DB_NAME) . '</strong> has not been imported yet.</p>' .
                    '<p>Please import <code>database/database.sql</code> via phpMyAdmin on your hosting server.</p>' .
                    '<p style="font-size:0.85rem; color:#6c757d;">Or on local XAMPP, open <a href="../config/setup.php">config/setup.php</a></p>' .
                    '</div>'
                );
            }

            // Other connection errors (e.g., MySQL host or credentials mismatch)
            die(
                '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; padding: 25px; border: 1px solid #f5c2c7; background-color: #f8d7da; border-radius: 8px; color: #842029;">' .
                '<h3 style="margin-top:0;">Database Connection Error</h3>' .
                '<p>Could not connect to MySQL server. Please verify:</p>' .
                '<ul>' .
                '<li><strong>Database Host:</strong> <code>' . htmlspecialchars(DB_HOST) . '</code></li>' .
                '<li><strong>Database Name:</strong> <code>' . htmlspecialchars(DB_NAME) . '</code></li>' .
                '<li><strong>Database User:</strong> <code>' . htmlspecialchars(DB_USER) . '</code></li>' .
                '<li>Your hosting MySQL database is active and password in <code>config/env.php</code> is correct.</li>' .
                '</ul>' .
                '<p style="font-size:0.85rem; color:#6c757d;">Error detail: ' . htmlspecialchars($e->getMessage()) . '</p>' .
                '</div>'
            );
        }
    }

    return $pdo;
}

// Global connection instance for procedural use across modules
try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    // Error is handled inside getDBConnection()
}
?>
