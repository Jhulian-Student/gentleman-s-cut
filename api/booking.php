<?php
// api/booking.php
// Appointment booking and retrieval endpoint

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

startSession();
$method = $_SERVER['REQUEST_METHOD'];

// Handle GET: Retrieve appointments
if ($method === 'GET') {
    $role = $_SESSION['role'] ?? ($_GET['role'] ?? null);
    $userEmail = $_SESSION['email'] ?? null;
    $queryEmail = trim($_GET['email'] ?? '');

    // Synchronize role from database if logged in
    if (isset($_SESSION['user_id'])) {
        try {
            $userStmt = $pdo->prepare("SELECT role, email FROM users WHERE id = ? LIMIT 1");
            $userStmt->execute([$_SESSION['user_id']]);
            $userRow = $userStmt->fetch();
            if ($userRow) {
                $role = $userRow['role'];
                $_SESSION['role'] = $userRow['role'];
                $userEmail = $userRow['email'];
            }
        } catch (PDOException $e) {}
    }

    try {
        if ($role === 'admin' || $role === 'barber') {
            // Staff sees all appointments
            $stmt = $pdo->query("SELECT id, customer_name, email, phone, service, appointment_date, appointment_time, notes, status, created_at FROM appointments ORDER BY appointment_date DESC, appointment_time ASC");
            $appointments = $stmt->fetchAll();
        } else {
            // Customer or guest sees appointments matching their email
            $filterEmail = $userEmail ?: $queryEmail;
            if (empty($filterEmail)) {
                jsonResponse(['success' => true, 'appointments' => []]);
            }
            $stmt = $pdo->prepare("SELECT id, customer_name, email, phone, service, appointment_date, appointment_time, notes, status, created_at FROM appointments WHERE email = ? ORDER BY appointment_date DESC, appointment_time ASC");
            $stmt->execute([$filterEmail]);
            $appointments = $stmt->fetchAll();
        }

        jsonResponse([
            'success' => true,
            'appointments' => $appointments
        ]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Database query failed: ' . $e->getMessage()], 500);
    }
}

// Handle POST: Create new appointment
if ($method === 'POST') {
    $data = getRequestData();

    $customerName = trim($data['customerName'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $service = trim($data['service'] ?? '');
    $appointmentDate = trim($data['appointmentDate'] ?? '');
    $appointmentTime = trim($data['appointmentTime'] ?? '');
    $notes = trim($data['notes'] ?? '');

    if (empty($customerName) || empty($email) || empty($phone) || empty($service) || empty($appointmentDate) || empty($appointmentTime)) {
        jsonResponse(['success' => false, 'message' => 'Please fill out all required appointment fields.'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Please enter a valid email address.'], 400);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO appointments (customer_name, email, phone, service, appointment_date, appointment_time, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->execute([$customerName, $email, $phone, $service, $appointmentDate, $appointmentTime, $notes]);

        $newId = (int)$pdo->lastInsertId();

        jsonResponse([
            'success' => true,
            'message' => "Appointment successfully booked for {$customerName} on {$appointmentDate} at {$appointmentTime}!",
            'appointmentId' => $newId
        ], 201);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to save appointment: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);

