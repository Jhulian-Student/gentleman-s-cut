<?php
// api/login.php
// User login authentication handler

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = getRequestData();

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    jsonResponse(['success' => false, 'message' => 'Please enter both email and password.'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT id, full_name, email, phone, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid email address or password.'], 401);
    }

    // Set session
    startSession();
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['phone'] = $user['phone'];
    $_SESSION['role'] = $user['role'];

    $redirectMap = [
        'customer' => 'dashboard.html',
        'barber' => 'dashboard.html',
        'admin' => 'admin.html'
    ];

    jsonResponse([
        'success' => true,
        'message' => "Welcome back, {$user['full_name']}!",
        'user' => [
            'id' => (int)$user['id'],
            'fullName' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role']
        ],
        'redirect' => $redirectMap[$user['role']] ?? 'dashboard.html'
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}

