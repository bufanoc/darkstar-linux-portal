<?php
/**
 * Dark Star Portal - Network Control API
 * Password-protected internet enable/disable for webtop container
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['password']) || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing password or action']);
    exit;
}

$password = $input['password'];
$action = $input['action']; // 'enable' or 'disable'

// Password hash - CHANGE THIS!
// Generate with: php -r "echo password_hash('your-password-here', PASSWORD_ARGON2ID);"
$password_hash = '$argon2id$v=19$m=65536,t=4,p=1$RjNHc1ZteWZjZW1OZWVqSg$vYPUZEm3oeJHhj3TqKlVvN3K+HdCpXYXzTLphPJqn6E'; // Default: "darkstar2025"

// Verify password
if (!password_verify($password, $password_hash)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid password']);
    exit;
}

// Container and network names
$container = 'darkstar-webtop';
$network = 'darkstar-linux-portal_internet';

// Execute docker command based on action
if ($action === 'enable') {
    // Check if already connected
    $check = shell_exec("sudo /usr/bin/docker network inspect $network --format='{{range .Containers}}{{.Name}}{{end}}' 2>&1");
    if (strpos($check, $container) !== false) {
        echo json_encode(['success' => true, 'message' => 'Internet already enabled', 'status' => 'enabled']);
        exit;
    }

    // Connect to internet network
    $exit_code = 0;
    exec("sudo /usr/bin/docker network connect $network $container 2>&1", $output_lines, $exit_code);

    if ($exit_code === 0) {
        echo json_encode(['success' => true, 'message' => 'Internet access enabled', 'status' => 'enabled']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to enable internet', 'details' => implode("\n", $output_lines)]);
    }

} elseif ($action === 'disable') {
    // Check if already disconnected
    $check = shell_exec("sudo /usr/bin/docker network inspect $network --format='{{range .Containers}}{{.Name}}{{end}}' 2>&1");
    if (strpos($check, $container) === false) {
        echo json_encode(['success' => true, 'message' => 'Internet already disabled', 'status' => 'disabled']);
        exit;
    }

    // Disconnect from internet network
    $exit_code = 0;
    exec("sudo /usr/bin/docker network disconnect $network $container 2>&1", $output_lines, $exit_code);

    if ($exit_code === 0) {
        echo json_encode(['success' => true, 'message' => 'Internet access disabled', 'status' => 'disabled']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to disable internet', 'details' => implode("\n", $output_lines)]);
    }

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action. Use "enable" or "disable"']);
}
?>
