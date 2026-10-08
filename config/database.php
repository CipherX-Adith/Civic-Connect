<?php
// config/database.php - CivicConnect Database Handler with Auto-Setup

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3307';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$dbName = getenv('DB_NAME') ?: 'civicconnect';

define('DB_HOST', $dbHost);
define('DB_PORT', $dbPort);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_NAME', $dbName);

// App Global Constants
define('APP_NAME', 'CivicConnect');
define('APP_TAGLINE', 'Smart Public Issue Reporting Portal');

// Auto-detect base URL (works for both http://localhost/CivicConnect and http://localhost:8000/)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$isSubdir = strpos($scriptName, '/CivicConnect') === 0;
define('APP_URL', $isSubdir ? '/CivicConnect' : '');
define('UPLOAD_DIR', __DIR__ . '/../uploads/complaints/');
define('UPLOAD_URL', APP_URL . '/uploads/complaints/');

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                // Connect to MySQL server
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];

                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                
                // Ensure Database exists
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                $pdo->exec("USE `" . DB_NAME . "`;");

                // Check if tables exist; if not, auto-run schema.sql
                $check = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
                if (!$check) {
                    $schemaFile = __DIR__ . '/../schema.sql';
                    if (file_exists($schemaFile)) {
                        $sql = file_get_contents($schemaFile);
                        $pdo->exec($sql);
                    }
                }

                self::$instance = $pdo;
            } catch (PDOException $e) {
                // Friendly setup guidance page
                die("<div style='font-family:Segoe UI, sans-serif; max-width:680px; margin:50px auto; padding:30px; border-radius:14px; background:#fff1f2; border:1px solid #fecdd3; color:#9f1239; box-shadow:0 12px 30px rgba(0,0,0,0.08); line-height:1.6;'>
                    <h2 style='margin-top:0; color:#881337; font-size:1.5rem;'>⚠️ MySQL Database Configuration Required</h2>
                    <p>Unable to connect to MySQL on <code>" . htmlspecialchars(DB_HOST) . ":" . htmlspecialchars(DB_PORT) . "</code> with user <code>" . htmlspecialchars(DB_USER) . "</code>.</p>
                    
                    <div style='background:#ffffff; padding:18px; border-radius:10px; border:1px solid #fecdd3; margin:18px 0; color:#1e293b; font-size:0.95rem;'>
                        <strong>Steps to resolve in XAMPP:</strong>
                        <ol style='margin-top:8px; padding-left:20px;'>
                            <li>Open <strong>XAMPP Control Panel</strong>.</li>
                            <li>Ensure <strong>Apache</strong> and <strong>MySQL</strong> are started (green lights).</li>
                            <li>If MySQL on your machine uses a password, update <code>config/database.php</code> with your password:
                                <pre style='background:#f1f5f9; padding:10px; border-radius:6px; margin-top:8px;'>define('DB_PASS', 'your_mysql_password');</pre>
                            </li>
                            <li>Or open <strong>phpMyAdmin</strong> (<a href='http://localhost/phpmyadmin/' target='_blank' style='color:#2563eb;'>http://localhost/phpmyadmin/</a>) and import <a href='#' style='color:#2563eb;'>schema.sql</a>.</li>
                        </ol>
                    </div>
                    
                    <small style='color:#be123c; display:block; word-break:break-all;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</small>
                </div>");
            }
        }
        return self::$instance;
    }
}

function getDB(): PDO {
    return Database::getConnection();
}
