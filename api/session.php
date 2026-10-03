<?php
// api/session.php
// Returns the currently authenticated user's session, synchronized with live database state

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

startSession();

if (isset($_SESSION['user_id'])) {
    try {
        // Query live role and profile from database
        $stmt = $pdo->prepare("SELECT id, full_name, email, phone, role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user) {
            // Synchronize session values with live database state
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phone'] = $user['phone'];

            jsonResponse([
                'authenticated' => true,
                'user' => [
                    'id' => (int)$user['id'],
                    'fullName' => $user['full_name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            // User was removed from database
            session_destroy();
            jsonResponse([
                'authenticated' => false,
                'user' => null
            ]);
        }
    } catch (PDOException $e) {
        // Fallback to existing session values if DB error occurs
        jsonResponse([
            'authenticated' => true,
            'user' => [
                'id' => (int)$_SESSION['user_id'],
                'fullName' => $_SESSION['full_name'] ?? '',
                'email' => $_SESSION['email'] ?? '',
                'phone' => $_SESSION['phone'] ?? '',
                'role' => $_SESSION['role'] ?? 'customer'
            ]
        ]);
    }
} else {
    jsonResponse([
        'authenticated' => false,
        'user' => null
    ]);
}
