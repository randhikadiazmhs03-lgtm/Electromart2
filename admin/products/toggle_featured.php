<?php
/**
 * ELECTROMART - Admin: Toggle Featured (AJAX)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id         = (int)($input['id']          ?? 0);
$is_featured = (int)($input['is_featured'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$stmt = $conn->prepare("UPDATE products SET is_featured = ? WHERE id = ?");
$stmt->bind_param('ii', $is_featured, $id);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok]);
