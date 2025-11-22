<?php
session_start();
header('Content-Type: application/json');
error_reporting(0);

// Database connection
$dbPath = '/var/lib/darkstar/users.db';
$db = new SQLite3($dbPath);

// Rate limiting (reuse from network-control)
define('RATE_LIMIT_MAX_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW', 60);
define('RATE_LIMIT_LOCKOUT', 300);
define('RATE_LIMIT_FILE', '/tmp/auth-rate-limit.json');

// Get client IP
function getClientIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// Rate limiting functions
function loadRateLimitData() {
    if (!file_exists(RATE_LIMIT_FILE)) {
        return [];
    }
    $data = @file_get_contents(RATE_LIMIT_FILE);
    return $data ? json_decode($data, true) : [];
}

function saveRateLimitData($data) {
    @file_put_contents(RATE_LIMIT_FILE, json_encode($data));
    @chmod(RATE_LIMIT_FILE, 0600);
}

function cleanupOldEntries($data) {
    $now = time();
    foreach ($data as $ip => $info) {
        if ($now - $info['last_attempt'] > RATE_LIMIT_LOCKOUT) {
            unset($data[$ip]);
        }
    }
    return $data;
}

function checkRateLimit($ip) {
    $data = loadRateLimitData();
    $data = cleanupOldEntries($data);
    $now = time();

    if (!isset($data[$ip])) {
        $data[$ip] = ['attempts' => 0, 'first_attempt' => $now, 'last_attempt' => $now];
    }

    $ipData = $data[$ip];

    if ($ipData['attempts'] >= RATE_LIMIT_MAX_ATTEMPTS) {
        $timeSinceLastAttempt = $now - $ipData['last_attempt'];
        if ($timeSinceLastAttempt < RATE_LIMIT_LOCKOUT) {
            $remainingTime = RATE_LIMIT_LOCKOUT - $timeSinceLastAttempt;
            return [
                'allowed' => false,
                'reason' => 'Too many failed attempts',
                'retry_after' => $remainingTime,
                'data' => $data
            ];
        } else {
            $data[$ip] = ['attempts' => 0, 'first_attempt' => $now, 'last_attempt' => $now];
        }
    }

    $windowStart = $now - RATE_LIMIT_WINDOW;
    if ($ipData['first_attempt'] < $windowStart) {
        $data[$ip] = ['attempts' => 0, 'first_attempt' => $now, 'last_attempt' => $now];
    }

    return ['allowed' => true, 'data' => $data];
}

function recordFailedAttempt($ip, $data) {
    $now = time();
    if (!isset($data[$ip])) {
        $data[$ip] = ['attempts' => 0, 'first_attempt' => $now, 'last_attempt' => $now];
    }

    $data[$ip]['attempts']++;
    $data[$ip]['last_attempt'] = $now;

    saveRateLimitData($data);

    $delay = min($data[$ip]['attempts'] * 0.5, 3);
    usleep($delay * 1000000);
}

function resetRateLimit($ip, $data) {
    if (isset($data[$ip])) {
        unset($data[$ip]);
        saveRateLimitData($data);
    }
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$clientIP = getClientIP();
$rateLimitCheck = checkRateLimit($clientIP);

if (!$rateLimitCheck['allowed']) {
    $minutes = ceil($rateLimitCheck['retry_after'] / 60);
    echo json_encode([
        'success' => false,
        'error' => 'Too many failed attempts. Please try again in ' . $minutes . ' minute' . ($minutes > 1 ? 's' : ''),
        'retry_after' => $rateLimitCheck['retry_after']
    ]);
    exit;
}

// ============================================
// SIGNUP
// ============================================
if ($action === 'signup') {
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        recordFailedAttempt($clientIP, $rateLimitCheck['data']);
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        recordFailedAttempt($clientIP, $rateLimitCheck['data']);
        echo json_encode(['success' => false, 'error' => 'Invalid email address']);
        exit;
    }

    if (strlen($username) < 3 || strlen($username) > 20) {
        recordFailedAttempt($clientIP, $rateLimitCheck['data']);
        echo json_encode(['success' => false, 'error' => 'Username must be 3-20 characters']);
        exit;
    }

    if (strlen($password) < 8) {
        recordFailedAttempt($clientIP, $rateLimitCheck['data']);
        echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters']);
        exit;
    }

    // Check if username or email already exists
    $stmt = $db->prepare('SELECT id FROM users WHERE username = :username OR email = :email');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $result = $stmt->execute();

    if ($result->fetchArray()) {
        recordFailedAttempt($clientIP, $rateLimitCheck['data']);
        echo json_encode(['success' => false, 'error' => 'Username or email already exists']);
        exit;
    }

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 1
    ]);

    // Insert user (status = pending by default)
    $stmt = $db->prepare('
        INSERT INTO users (username, email, password_hash, role, status)
        VALUES (:username, :email, :password_hash, "user", "pending")
    ');

    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':password_hash', $passwordHash, SQLITE3_TEXT);

    if ($stmt->execute()) {
        resetRateLimit($clientIP, $rateLimitCheck['data']);
        echo json_encode([
            'success' => true,
            'message' => 'Account created! Your account is pending admin approval. You will be able to login once approved.',
            'status' => 'pending'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create account']);
    }
}

// ============================================
// LOGIN
// ============================================
elseif ($action === 'login') {
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        recordFailedAttempt($clientIP, $rateLimitCheck['data']);
        echo json_encode(['success' => false, 'error' => 'Username and password required']);
        exit;
    }

    // Get user
    $stmt = $db->prepare('SELECT id, username, email, password_hash, role, status FROM users WHERE username = :username');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        recordFailedAttempt($clientIP, $rateLimitCheck['data']);
        echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
        exit;
    }

    // Check if account is pending
    if ($user['status'] === 'pending') {
        echo json_encode([
            'success' => false,
            'error' => 'Your account is pending admin approval. Please wait for approval before logging in.',
            'status' => 'pending'
        ]);
        exit;
    }

    // Check if account is suspended
    if ($user['status'] === 'suspended') {
        echo json_encode([
            'success' => false,
            'error' => 'Your account has been suspended. Please contact the administrator.',
            'status' => 'suspended'
        ]);
        exit;
    }

    // Update last login
    $updateStmt = $db->prepare('UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id');
    $updateStmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
    $updateStmt->execute();

    // Create session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;

    resetRateLimit($clientIP, $rateLimitCheck['data']);

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
}

// ============================================
// LOGOUT
// ============================================
elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
}

// ============================================
// CHECK SESSION
// ============================================
elseif ($action === 'check') {
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'logged_in' => false
        ]);
    }
}

// ============================================
// INVALID ACTION
// ============================================
else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

$db->close();
?>
