<?php
// ====================================================================
// Database Configuration
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

// Prevent direct script execution if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials (Standard XAMPP default)
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'industrial_maintenance');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Dynamic Base URL Auto-Detection
if (!defined('BASE_URL')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $pos = stripos($scriptName, '/Web_project');
    if ($pos !== false) {
        // Standard XAMPP htdocs/Web_project setup
        $base = substr($scriptName, 0, $pos + strlen('/Web_project'));
        define('BASE_URL', rtrim($base, '/'));
    } else {
        // Fallback or custom virtual host / root directory
        $rootDir = str_replace('\\', '/', realpath(__DIR__ . '/..'));
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
        if ($docRoot && strpos($rootDir, $docRoot) === 0) {
            $base = substr($rootDir, strlen($docRoot));
            define('BASE_URL', rtrim(str_replace('\\', '/', $base), '/'));
        } else {
            define('BASE_URL', '/Web_project');
        }
    }
}

// Application Settings
define('APP_NAME', 'EquipTrack Pro');
define('APP_FULL_NAME', 'Industrial Equipment Maintenance & Tracking System');
define('APP_VERSION', '1.0.0');

/**
 * Returns the singleton PDO Database Connection instance
 * 
 * @return PDO
 */
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Friendly error screen for database connection failure (e.g. before importing database.sql)
            $dbName = DB_NAME;
            $errorMsg = htmlspecialchars($e->getMessage());
            die("
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Database Connection Error - " . APP_NAME . "</title>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
                    .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; max-width: 650px; width: 100%; padding: 32px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); }
                    .badge { display: inline-block; background: #ef4444; color: white; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 16px; }
                    h2 { margin: 0 0 12px 0; color: #f1f5f9; font-size: 24px; }
                    p { color: #94a3b8; font-size: 15px; line-height: 1.6; margin-bottom: 16px; }
                    .code-box { background: #090d16; border: 1px solid #1e293b; padding: 14px; border-radius: 8px; color: #f87171; font-family: monospace; font-size: 13px; overflow-x: auto; margin-bottom: 24px; word-break: break-all; }
                    .steps { background: #0f172a; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 6px; margin-bottom: 24px; }
                    .steps ol { margin: 0; padding-left: 20px; color: #cbd5e1; font-size: 14px; }
                    .steps li { margin-bottom: 8px; }
                    .btn { display: inline-block; background: #2563eb; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: background 0.2s; }
                    .btn:hover { background: #1d4ed8; }
                </style>
            </head>
            <body>
                <div class='card'>
                    <span class='badge'>MySQL Connection Error</span>
                    <h2>Unable to Connect to MySQL Database</h2>
                    <p>The application could not connect to MySQL database <strong>{$dbName}</strong>.</p>
                    <div class='code-box'>{$errorMsg}</div>
                    <div class='steps'>
                        <h4 style='margin:0 0 10px 0; color:#60a5fa;'>Quick Troubleshooting Steps:</h4>
                        <ol>
                            <li>Ensure <strong>XAMPP Control Panel</strong> is open and both <strong>Apache</strong> and <strong>MySQL</strong> are started (showing green).</li>
                            <li>Open <a href='http://localhost/phpmyadmin' target='_blank' style='color:#38bdf8;'>http://localhost/phpmyadmin</a> in your browser.</li>
                            <li>Import the <code>database.sql</code> file provided inside the <code>Web_project</code> directory.</li>
                            <li>Verify <code>config/database.php</code> credentials match your local MySQL configuration (Default: Host=localhost, User=root, Pass=blank).</li>
                        </ol>
                    </div>
                    <a href='javascript:location.reload();' class='btn'>Retry Connection</a>
                </div>
            </body>
            </html>
            ");
        }
    }

    return $pdo;
}
