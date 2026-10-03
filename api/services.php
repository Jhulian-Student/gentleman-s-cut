<?php
// api/services.php
// Services management and query endpoint

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET: Fetch all active services
if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT id, name, price, duration, description FROM services ORDER BY id ASC");
        $services = $stmt->fetchAll();
        jsonResponse(['success' => true, 'services' => $services]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Error loading services: ' . $e->getMessage()], 500);
    }
}

// POST: Add a new service (Admin only)
if ($method === 'POST') {
    requireAuth(['admin']);
    $data = getRequestData();

    $name = trim($data['name'] ?? '');
    $price = floatval($data['price'] ?? 0);
    $duration = trim($data['duration'] ?? '');
    $description = trim($data['description'] ?? '');

    if (empty($name) || $price <= 0 || empty($duration)) {
        jsonResponse(['success' => false, 'message' => 'Valid service name, price, and duration are required.'], 400);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO services (name, price, duration, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $price, $duration, $description]);
        $newId = (int)$pdo->lastInsertId();

        jsonResponse([
            'success' => true,
            'message' => "Service '{$name}' added successfully.",
            'service' => [
                'id' => $newId,
                'name' => $name,
                'price' => $price,
                'duration' => $duration,
                'description' => $description
            ]
        ], 201);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to add service: ' . $e->getMessage()], 500);
    }
}

// DELETE: Remove a service (Admin only)
if ($method === 'DELETE') {
    requireAuth(['admin']);
    $data = getRequestData();
    $serviceId = intval($data['id'] ?? ($_GET['id'] ?? 0));

    if ($serviceId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid service ID.'], 400);
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$serviceId]);
        jsonResponse(['success' => true, 'message' => 'Service deleted successfully.']);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to delete service: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);

