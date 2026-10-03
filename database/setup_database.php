<?php
// database/setup_database.php
// Setup script to initialize database tables and seed data for local or cloud deployment (Railway)

// Determine environment
$host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: '127.0.0.1');
$port = (int)(getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: 3306));
$dbname = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: 'gentlemans_cut_db');
$username = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root');
$password = getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

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

$isCli = (php_sapi_name() === 'cli');

function output($message, $type = 'info') {
    global $isCli;
    if ($isCli) {
        $prefix = $type === 'error' ? '[ERROR] ' : ($type === 'success' ? '[SUCCESS] ' : '[INFO] ');
        echo $prefix . $message . PHP_EOL;
    } else {
        $color = $type === 'error' ? '#e74c3c' : ($type === 'success' ? '#2ecc71' : '#3498db');
        echo "<div style='font-family:sans-serif; margin:8px 0; color:{$color};'><strong>" . htmlspecialchars($message) . "</strong></div>";
    }
}

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Database Setup</title><link rel='stylesheet' href='../css/style.css'></head><body style='padding:40px; max-width:700px; margin:0 auto; background:#181818; color:#eee; font-family:sans-serif;'>";
    echo "<h2 style='color:#c9a227;'>The Gentleman's Cut — Database Initializer</h2>";
}

try {
    // 1. Try connecting with database specified first
    try {
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        output("Connected to database '{$dbname}' on {$host}:{$port}.", 'success');
    } catch (PDOException $ex) {
        // If database does not exist, connect without db and create it
        $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbname}`");
        output("Database '{$dbname}' created successfully.", 'success');
    }

    // 2. Read database.sql
    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("database.sql file not found at: {$sqlFile}");
    }

    $sql = file_get_contents($sqlFile);

    // Replace default database name with target dbname if different
    if ($dbname !== 'gentlemans_cut_db') {
        $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS `gentlemans_cut_db`[^;]*;/i', '', $sql);
        $sql = preg_replace('/USE `gentlemans_cut_db`;/i', "USE `{$dbname}`;", $sql);
    }

    output("Executing database table creations and initial seed records...", 'info');
    $pdo->exec($sql);
    output("Tables and initial data initialized successfully!", 'success');

    // 3. Verify counts
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $serviceCount = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
    $appointmentCount = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();

    output("Database Verification:", 'info');
    output("- Users: {$userCount} records (Admin, Barber, Customer seeded)", 'success');
    output("- Services: {$serviceCount} records", 'success');
    output("- Appointments: {$appointmentCount} records", 'success');

    if (!$isCli) {
        echo "<p style='margin-top:20px;'><a href='../login.html' style='color:#c9a227; font-weight:bold; text-decoration:none; padding:10px 16px; background:#222; border-radius:6px; border:1px solid #c9a227;'>Go to Login Page</a> &nbsp; <a href='../index.html' style='color:#fff; text-decoration:none;'>Go to Home</a></p>";
        echo "</body></html>";
    }
} catch (PDOException $e) {
    output("Database setup failed: " . $e->getMessage(), 'error');
    if (!$isCli) {
        echo "<p>Please ensure MySQL is running or check your environment variables.</p>";
        echo "</body></html>";
    }
} catch (Exception $e) {
    output("Error: " . $e->getMessage(), 'error');
    if (!$isCli) {
        echo "</body></html>";
    }
}
