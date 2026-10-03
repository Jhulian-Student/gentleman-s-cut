<?php
// api/init_db.php
// Cloud Database Initializer & Health Check
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

try {
    // 1. Force initialize if tables not yet present
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('users', $tables)) {
        $sqlPath = __DIR__ . '/../database/database.sql';
        if (file_exists($sqlPath)) {
            $initSql = file_get_contents($sqlPath);
            if ($dbname !== 'gentlemans_cut_db') {
                $initSql = preg_replace('/CREATE DATABASE IF NOT EXISTS `gentlemans_cut_db`[^;]*;/i', '', $initSql);
                $initSql = preg_replace('/USE `gentlemans_cut_db`;/i', "USE `{$dbname}`;", $initSql);
            }
            $pdo->exec($initSql);
        }
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    }

    $userCount = in_array('users', $tables) ? (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() : 0;
    $serviceCount = in_array('services', $tables) ? (int)$pdo->query("SELECT COUNT(*) FROM services")->fetchColumn() : 0;
    $appointmentCount = in_array('appointments', $tables) ? (int)$pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn() : 0;

    echo json_encode([
        'success' => true,
        'message' => 'Database connected and initialized successfully!',
        'database' => $dbname,
        'tables' => $tables,
        'records' => [
            'users' => $userCount,
            'services' => $serviceCount,
            'appointments' => $appointmentCount
        ]
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
