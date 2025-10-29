<?php
header('Content-Type: application/json');

require_once '../../includes/config.php';
require_once '../../includes/auth.php';

$auth = new Auth();

if ($auth->isUserLoggedIn()) {
    $user = $auth->getCurrentUser();
    echo json_encode([
        'authenticated' => true,
        'user' => [
            'username' => $user['username'],
            'email' => $user['email']
        ]
    ]);
} else {
    echo json_encode([
        'authenticated' => false
    ]);
}
