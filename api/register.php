<?php
// api/register.php
// User registration handler

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = getRequestData();

$fullName = trim($data['fullName'] ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$password = $data['password'] ?? '';
$confirmPassword = $data['confirmPassword'] ?? null;
$role = trim($data['registerRole'] ?? ($data['role'] ?? 'customer'));

// Validation
if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
    jsonResponse(['success' => false, 'message' => 'Please fill out all required fields.'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Please enter a valid email address.'], 400);
}

if ($confirmPassword !== null && $password !== $confirmPassword) {
    jsonResponse(['success' => false, 'message' => 'Passwords do not match. Please try again.'], 400);
}

if (strlen($password) < 6) {
    jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters long.'], 400);
}

// Validate role (Only customer or barber allowed for self-registration)
$allowedRoles = ['customer', 'barber'];
if (!in_array($role, $allowedRoles, true)) {
    $role = 'customer';
}

try {
    // Check if email already taken
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'An account with this email already exists.'], 409);
    }

    // Hash password securely
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $insertStmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
    $insertStmt->execute([$fullName, $email, $phone, $hashedPassword, $role]);

    $newUserId = (int)$pdo->lastInsertId();

    // Set session
    startSession();
    $_SESSION['user_id'] = $newUserId;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['email'] = $email;
    $_SESSION['phone'] = $phone;
    $_SESSION['role'] = $role;

    jsonResponse([
        'success' => true,
        'message' => "Registration successful! Welcome, {$fullName}.",
        'user' => [
            'id' => $newUserId,
            'fullName' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'role' => $role
        ]
    ], 201);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}

