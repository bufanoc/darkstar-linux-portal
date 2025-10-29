<?php
header('Content-Type: application/json');

require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

$auth = new Auth();
$auth->requireAdminAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['user_id'] ?? null;
$active = isset($input['active']) ? (int)$input['active'] : null;

if (empty($userId) || $active === null) {
    echo json_encode(['success' => false, 'message' => 'User ID and status required']);
    exit;
}

$db = new Database();

if ($db->updateUserStatus($userId, $active)) {
    echo json_encode(['success' => true, 'message' => 'User status updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
}
