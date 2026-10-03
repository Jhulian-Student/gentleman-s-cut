<?php
// api/admin.php
// Admin management endpoint for appointment status updates and deletions

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$session = requireAuth(['admin', 'barber']);
$method = $_SERVER['REQUEST_METHOD'];
$data = getRequestData();

// POST: Actions like updating status or deleting
if ($method === 'POST') {
    $action = $data['action'] ?? '';

    // Update appointment status
    if ($action === 'update_status') {
        $appointmentId = intval($data['id'] ?? 0);
        $newStatus = trim($data['status'] ?? '');

        $allowedStatuses = ['Pending', 'Confirmed', 'Cancelled'];
        if ($appointmentId <= 0 || !in_array($newStatus, $allowedStatuses, true)) {
            jsonResponse(['success' => false, 'message' => 'Invalid appointment ID or status.'], 400);
        }

        try {
            $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $appointmentId]);
            jsonResponse(['success' => true, 'message' => "Appointment #{$appointmentId} status updated to '{$newStatus}'."]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    // Delete appointment (Admin only)
    if ($action === 'delete_appointment') {
        if ($session['role'] !== 'admin') {
            jsonResponse(['success' => false, 'message' => 'Only administrators can delete appointments.'], 403);
        }

        $appointmentId = intval($data['id'] ?? 0);
        if ($appointmentId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Invalid appointment ID.'], 400);
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
            $stmt->execute([$appointmentId]);
            jsonResponse(['success' => true, 'message' => "Appointment #{$appointmentId} deleted successfully."]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }

    jsonResponse(['success' => false, 'message' => 'Unknown admin action.'], 400);
}

// GET: Summary stats for admin dashboard
if ($method === 'GET') {
    try {
        $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $appointmentCount = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
        $pendingCount = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'")->fetchColumn();
        $serviceCount = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();

        jsonResponse([
            'success' => true,
            'stats' => [
                'users' => (int)$userCount,
                'appointments' => (int)$appointmentCount,
                'pending' => (int)$pendingCount,
                'services' => (int)$serviceCount
            ]
        ]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Stats query failed: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);

