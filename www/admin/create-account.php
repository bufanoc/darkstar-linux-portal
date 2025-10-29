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
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($signupId) || empty($username) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

$db = new Database();

// Create the user
if ($db->createUser($username, $email, $password)) {
    // Update signup status to approved
    $db->updateSignupStatus($signupId, 'approved');

    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully! Password: ' . $password
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create account. Username or email may already exist.'
    ]);
}
