<?php
// db.php
// Central Database Connection & Helper Functions for The Gentleman's Cut

// Enable CORS for local development
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 86400");
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}

// Database configuration: supports Railway native variables, standard cloud variables, and local defaults
$host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: '127.0.0.1');
$port = (int)(getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: 3306));
$dbname = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: 'gentlemans_cut_db');
$username = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root');
$password = getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// Also support cloud DATABASE_URL / MYSQL_URL (Railway, Render, Heroku)
$dbUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');
if ($dbUrl) {
    $parsed = parse_url($dbUrl);
    if (!empty($parsed['host'])) {
        $host = $parsed['host'];
        $port = !empty($parsed['port']) ? (int)$parsed['port'] : 3306;
        $username = $parsed['user'] ?? $username;
        $password = $parsed['pass'] ?? $password;
        $dbname = ltrim($parsed['path'] ?? '', '/') ?: $dbname;
    }
}

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    // Auto-create database tables on first launch if they do not exist
    static $dbInitialized = false;
    if (!$dbInitialized) {
        $dbInitialized = true;
        try {
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
            if (!$tableCheck) {
                $sqlPath = __DIR__ . '/../database/database.sql';
                if (file_exists($sqlPath)) {
                    $initSql = file_get_contents($sqlPath);
                    if ($dbname !== 'gentlemans_cut_db') {
                        $initSql = preg_replace('/CREATE DATABASE IF NOT EXISTS `gentlemans_cut_db`[^;]*;/i', '', $initSql);
                        $initSql = preg_replace('/USE `gentlemans_cut_db`;/i', "USE `{$dbname}`;", $initSql);
                    }
                    $pdo->exec($initSql);
                }
            }
        } catch (Exception $initError) {
            // Non-blocking: continue normal execution
        }
    }
} catch (PDOException $e) {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (strpos($uri, 'api/') !== false || strpos($accept, 'application/json') !== false) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection error: ' . $e->getMessage()]);
        exit;
    }
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}

/**
 * Start session safely if not already active
 */
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Return a standardized JSON response and exit
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get request data whether sent via form-data (POST) or JSON body
 */
function getRequestData(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
    return $_POST;
}

/**
 * Require active session with optional role authorization
 */
function requireAuth(?array $allowedRoles = null): array {
    startSession();
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
    }
    if ($allowedRoles && !in_array($_SESSION['role'] ?? '', $allowedRoles, true)) {
        jsonResponse(['success' => false, 'message' => 'Permission denied.'], 403);
    }
    return $_SESSION;
}

