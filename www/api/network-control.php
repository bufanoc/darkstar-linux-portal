<?php
session_start();
header('Content-Type: application/json');
error_reporting(0);

// ============================================
// AUTHENTICATION CHECK
// ============================================
// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Authentication required. Please login.']);
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Access denied. Admin privileges required.']);
    exit;
}

// ============================================
// RATE LIMITING CONFIGURATION
// ============================================
define('RATE_LIMIT_MAX_ATTEMPTS', 5);      // Max attempts per time window
define('RATE_LIMIT_WINDOW', 60);           // Time window in seconds (1 minute)
define('RATE_LIMIT_LOCKOUT', 300);         // Lockout duration in seconds (5 minutes)
define('RATE_LIMIT_FILE', '/tmp/network-control-rate-limit.json');

// ============================================
// GET REAL CLIENT IP (Cloudflare support)
// ============================================
function getClientIP() {
    // Check for Cloudflare IP header first (production)
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }

    // Fallback to standard headers
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }

    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// ============================================
// RATE LIMITING FUNCTIONS
// ============================================
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
        // Remove entries older than lockout period
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

    // Check if IP is in lockout period
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
            // Lockout expired, reset
            $data[$ip] = ['attempts' => 0, 'first_attempt' => $now, 'last_attempt' => $now];
        }
    }

    // Check attempts within time window
    $windowStart = $now - RATE_LIMIT_WINDOW;
    if ($ipData['first_attempt'] < $windowStart) {
        // Reset window
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

    // Calculate delay (progressive delay based on attempts)
    $delay = min($data[$ip]['attempts'] * 0.5, 3); // Max 3 second delay
    usleep($delay * 1000000);
}

function resetRateLimit($ip, $data) {
    if (isset($data[$ip])) {
        unset($data[$ip]);
        saveRateLimitData($data);
    }
}

// ============================================
// MAIN EXECUTION
// ============================================

// Get client IP
$clientIP = getClientIP();

// Check rate limit
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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['action'])) {
    echo json_encode(['success' => false, 'error' => 'Missing action']);
    exit;
}

// Execute network control command
$container = 'darkstar-webtop';
$network = 'darkstar-linux-portal_internet';

if ($input['action'] === 'enable') {
    exec("sudo /usr/bin/docker network connect $network $container 2>&1", $output, $result);
    if ($result === 0 || strpos(implode($output), 'already attached') !== false) {
        echo json_encode(['success' => true, 'message' => 'Internet access enabled', 'status' => 'enabled']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to enable internet', 'output' => $output]);
    }
} elseif ($input['action'] === 'disable') {
    exec("sudo /usr/bin/docker network disconnect $network $container 2>&1", $output, $result);
    if ($result === 0 || strpos(implode($output), 'is not connected') !== false) {
        echo json_encode(['success' => true, 'message' => 'Internet access disabled', 'status' => 'disabled']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to disable internet', 'output' => $output]);
    }
} elseif ($input['action'] === 'status') {
    exec("sudo /usr/bin/docker network inspect $network --format '{{range .Containers}}{{.Name}} {{end}}' 2>&1", $output, $result);
    $connected = strpos(implode($output), $container) !== false;
    echo json_encode(['success' => true, 'status' => $connected ? 'enabled' : 'disabled']);
} elseif ($input['action'] === 'cron-status') {
    $pauseFlag = '/var/lib/darkstar/cron-paused';
    $isPaused = file_exists($pauseFlag);
    echo json_encode(['success' => true, 'status' => $isPaused ? 'paused' : 'active']);
} elseif ($input['action'] === 'cron-pause') {
    $pauseFlag = '/var/lib/darkstar/cron-paused';
    if (file_put_contents($pauseFlag, date('Y-m-d H:i:s') . " - Paused by admin\n")) {
        chmod($pauseFlag, 0644);
        echo json_encode(['success' => true, 'message' => 'Auto-restart paused', 'status' => 'paused']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to pause auto-restart']);
    }
} elseif ($input['action'] === 'cron-resume') {
    $pauseFlag = '/var/lib/darkstar/cron-paused';
    if (file_exists($pauseFlag) && unlink($pauseFlag)) {
        echo json_encode(['success' => true, 'message' => 'Auto-restart resumed', 'status' => 'active']);
    } elseif (!file_exists($pauseFlag)) {
        echo json_encode(['success' => true, 'message' => 'Auto-restart already active', 'status' => 'active']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to resume auto-restart']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>
