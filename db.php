<?php
/**
 * Campusly Database Connection Handler (db.php)
 * System: Campusly Academic Portal
 */

// Load credentials with cloud environment variable support (Vercel, Railway, Render, etc.)
$host     = getenv('DB_HOST') ?: '127.0.0.1';
$user     = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
$database = getenv('DB_NAME') ?: 'studenthub';
$port     = (int)(getenv('DB_PORT') ?: 3306);

// 1. MySQLi Object-Oriented Connection (Supporting studenthub and campusly)
$conn = @new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error && empty(getenv('DB_NAME'))) {
    // Fallback to campusly if studenthub is not present locally
    $database = 'campusly';
    $conn = @new mysqli($host, $user, $password, $database, $port);
}

if ($conn->connect_error) {
    $isCloud = !empty(getenv('VERCEL')) || !empty(getenv('AWS_LAMBDA_FUNCTION_NAME'));
    $errMsg = "MySQL Database Connection Error: " . $conn->connect_error;
    if ($isCloud) {
        $errMsg .= " (Please verify your Vercel Environment Variables: DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT).";
    }
    die($errMsg);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// 2. Backward compatibility PDO instance for shared project utilities
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Graceful fallback if PDO is optional
}

// Diagnostic output if db.php is run directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    echo "<!DOCTYPE html><html><head><title>Database Connection - Campusly</title>";
    echo "<style>body{font-family: Arial, sans-serif; padding: 24px; background: #ffffff; color: #1e293b;}</style></head><body>";
    echo "<h2 style='color: #16a34a;'>✅ MySQLi Database Connected Successfully</h2>";
    echo "<p>Database: <strong>" . htmlspecialchars($database) . "</strong> | Host: <strong>" . htmlspecialchars($host) . "</strong></p>";
    echo "</body></html>";
}
