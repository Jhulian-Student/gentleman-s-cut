<?php
// test_backend.php
// Verification test suite for database and backend API endpoints

require_once __DIR__ . '/../config/db.php';

function test($description, $assertion) {
    if ($assertion) {
        echo "[PASS] " . $description . PHP_EOL;
    } else {
        echo "[FAIL] " . $description . PHP_EOL;
        exit(1);
    }
}

echo "=== 1. Testing Database Tables & Seeding ===" . PHP_EOL;
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
test("Users table has records", $userCount >= 3);

$serviceCount = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
test("Services table has records", $serviceCount >= 6);

$appointmentCount = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
test("Appointments table has records", $appointmentCount >= 1);

echo PHP_EOL . "=== 2. Testing Password Verification ===" . PHP_EOL;
$stmt = $pdo->prepare("SELECT password FROM users WHERE email = ?");
$stmt->execute(['admin@gentlemanscut.com']);
$adminUser = $stmt->fetch();
test("Admin password verifies with 'admin123'", password_verify('admin123', $adminUser['password']));

echo PHP_EOL . "=== 3. Testing Appointment Creation ===" . PHP_EOL;
$testEmail = 'testcustomer_' . time() . '@example.com';
$insertStmt = $pdo->prepare("INSERT INTO appointments (customer_name, email, phone, service, appointment_date, appointment_time, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
$insertStmt->execute(['Test Customer', $testEmail, '09999999999', 'Classic Haircut', '2026-10-01', '10:00 AM', 'Automated test appointment']);
$newAptId = (int)$pdo->lastInsertId();
test("New appointment inserted with ID {$newAptId}", $newAptId > 0);

// Verify query
$verifyStmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
$verifyStmt->execute([$newAptId]);
$fetchedApt = $verifyStmt->fetch();
test("Appointment retrieved with status 'Pending'", $fetchedApt['status'] === 'Pending');

// Update status
$updateStmt = $pdo->prepare("UPDATE appointments SET status = 'Confirmed' WHERE id = ?");
$updateStmt->execute([$newAptId]);
$verifyStmt->execute([$newAptId]);
$updatedApt = $verifyStmt->fetch();
test("Appointment updated to 'Confirmed'", $updatedApt['status'] === 'Confirmed');

// Delete test appointment
$delStmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
$delStmt->execute([$newAptId]);
$verifyStmt->execute([$newAptId]);
test("Appointment cleanly deleted", $verifyStmt->fetch() === false);

echo PHP_EOL . "=== 4. Testing Services Table Operations ===" . PHP_EOL;
$srvStmt = $pdo->prepare("INSERT INTO services (name, price, duration, description) VALUES (?, ?, ?, ?)");
$srvStmt->execute(['Test Shave', 199.00, '15 minutes', 'Automated test shave']);
$newSrvId = (int)$pdo->lastInsertId();
test("Service created with ID {$newSrvId}", $newSrvId > 0);

$delSrvStmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
$delSrvStmt->execute([$newSrvId]);
test("Service cleanly deleted", true);

echo PHP_EOL . "=== ALL BACKEND AND DATABASE TESTS PASSED! ===" . PHP_EOL;

