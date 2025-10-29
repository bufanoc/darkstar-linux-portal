<?php
header('Content-Type: application/json');

require_once '../../includes/config.php';

echo json_encode([
    'csrf_token' => get_csrf_token()
]);
