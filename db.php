<?php
/**
 * Campusly Database Connection Handler (db.php)
 * System: Campusly Academic Portal
 */

// Disable default fatal exception throwing for mysqli in PHP 8.1+ so connection failures can be handled gracefully
mysqli_report(MYSQLI_REPORT_OFF);

// Load credentials with cloud environment variable support (Vercel, Railway, Render, etc.)
$host     = getenv('DB_HOST') ?: '127.0.0.1';
$user     = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
$database = getenv('DB_NAME') ?: 'studenthub';
$port     = (int)(getenv('DB_PORT') ?: 3306);

$conn = null;
$pdo  = null;
$db_connected = false;

// 1. Resilient MySQLi Connection
try {
    $conn = @new mysqli($host, $user, $password, $database, $port);
    if ($conn && !$conn->connect_error) {
        $conn->set_charset("utf8mb4");
        $db_connected = true;
    } else if (empty(getenv('DB_NAME')) && empty(getenv('DB_HOST'))) {
        // Fallback to campusly if studenthub is not present locally
        $database = 'campusly';
        $conn = @new mysqli($host, $user, $password, $database, $port);
        if ($conn && !$conn->connect_error) {
            $conn->set_charset("utf8mb4");
            $db_connected = true;
        } else {
            $conn = null;
            $db_connected = false;
        }
    } else {
        $conn = null;
        $db_connected = false;
    }
} catch (Throwable $e) {
    $conn = null;
    $db_connected = false;
}

if (!$db_connected) {
    $conn = null;
}

// 2. PDO Instance (for prepared statements and relational queries)
if ($db_connected && $conn) {
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (Throwable $e) {
        $pdo = null;
    }
} else {
    $pdo = null;
}

// Diagnostic output if db.php is run directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    echo "<!DOCTYPE html><html><head><title>Database Connection - Campusly</title>";
    echo "<style>body{font-family: Arial, sans-serif; padding: 24px; background: #ffffff; color: #1e293b;}</style></head><body>";
    if ($db_connected) {
        echo "<h2 style='color: #16a34a;'>✅ MySQL Database Connected Successfully</h2>";
        echo "<p>Database: <strong>" . htmlspecialchars($database) . "</strong> | Host: <strong>" . htmlspecialchars($host) . "</strong></p>";
    } else {
        echo "<h2 style='color: #dc2626;'>⚠️ Database Connection Offline</h2>";
        echo "<p>Could not connect to MySQL at <code>" . htmlspecialchars($host) . ":" . htmlspecialchars((string)$port) . "</code>.</p>";
        echo "<p>If on Vercel, configure your cloud database environment variables in Project Settings (<code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASSWORD</code>, <code>DB_NAME</code>, <code>DB_PORT</code>).</p>";
    }
    echo "</body></html>";
}
