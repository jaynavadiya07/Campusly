<?php
/**
 * Vercel Serverless Front Controller for Campusly
 */

// Set working directory to project root
chdir(dirname(__DIR__));

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$file = ltrim($uri, '/');

// Handle root path or index
if (empty($file) || $file === 'index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

$rootDir = realpath(__DIR__ . '/..');
$target = realpath(__DIR__ . '/../' . $file);

// Ensure target exists, is inside project root, and is a PHP file
if ($target && strpos($target, $rootDir) === 0 && is_file($target)) {
    $ext = pathinfo($target, PATHINFO_EXTENSION);
    if ($ext === 'php') {
        require $target;
        exit;
    }
}

// Fallback to index.php
require __DIR__ . '/../index.php';
