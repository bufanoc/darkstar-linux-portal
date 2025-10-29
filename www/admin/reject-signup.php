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
$signupId = $input['signup_id'] ?? null;

if (empty($signupId)) {
    echo json_encode(['success' => false, 'message' => 'Signup ID required']);
    exit;
}

$db = new Database();

if ($db->updateSignupStatus($signupId, 'rejected')) {
    echo json_encode(['success' => true, 'message' => 'Signup request rejected']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to reject signup']);
}
