<?php
session_start();
header('Content-Type: application/json');
error_reporting(0);

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Access denied. Admin only.']);
    exit;
}

// Database connection
$dbPath = '/var/lib/darkstar/users.db';
$db = new SQLite3($dbPath);

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// ============================================
// LIST USERS
// ============================================
if ($action === 'list-users') {
    $status = $input['status'] ?? 'all'; // all, pending, active, suspended

    $query = 'SELECT id, username, email, role, status, created_at, approved_at, last_login FROM users';

    if ($status !== 'all') {
        $query .= ' WHERE status = :status';
    }

    $query .= ' ORDER BY created_at DESC';

    $stmt = $db->prepare($query);

    if ($status !== 'all') {
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
    }

    $result = $stmt->execute();
    $users = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $users[] = $row;
    }

    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
}

// ============================================
// APPROVE USER
// ============================================
elseif ($action === 'approve-user') {
    $userId = $input['user_id'] ?? 0;

    if (empty($userId)) {
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit;
    }

    $stmt = $db->prepare('
        UPDATE users
        SET status = "active", approved_at = CURRENT_TIMESTAMP, approved_by = :admin_id
        WHERE id = :user_id
    ');

    $stmt->bindValue(':admin_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'User approved successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to approve user']);
    }
}

// ============================================
// REJECT USER (DELETE)
// ============================================
elseif ($action === 'reject-user') {
    $userId = $input['user_id'] ?? 0;

    if (empty($userId)) {
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit;
    }

    // Don't allow deleting admin users
    $checkStmt = $db->prepare('SELECT role FROM users WHERE id = :user_id');
    $checkStmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $checkResult = $checkStmt->execute();
    $checkUser = $checkResult->fetchArray(SQLITE3_ASSOC);

    if ($checkUser && $checkUser['role'] === 'admin') {
        echo json_encode(['success' => false, 'error' => 'Cannot delete admin users']);
        exit;
    }

    $stmt = $db->prepare('DELETE FROM users WHERE id = :user_id');
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'User rejected and deleted successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to reject user']);
    }
}

// ============================================
// SUSPEND USER
// ============================================
elseif ($action === 'suspend-user') {
    $userId = $input['user_id'] ?? 0;

    if (empty($userId)) {
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit;
    }

    // Don't allow suspending admin users
    $checkStmt = $db->prepare('SELECT role FROM users WHERE id = :user_id');
    $checkStmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $checkResult = $checkStmt->execute();
    $checkUser = $checkResult->fetchArray(SQLITE3_ASSOC);

    if ($checkUser && $checkUser['role'] === 'admin') {
        echo json_encode(['success' => false, 'error' => 'Cannot suspend admin users']);
        exit;
    }

    $stmt = $db->prepare('UPDATE users SET status = "suspended" WHERE id = :user_id');
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'User suspended successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to suspend user']);
    }
}

// ============================================
// ACTIVATE USER (UNSUSPEND)
// ============================================
elseif ($action === 'activate-user') {
    $userId = $input['user_id'] ?? 0;

    if (empty($userId)) {
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit;
    }

    $stmt = $db->prepare('
        UPDATE users
        SET status = "active", approved_at = CURRENT_TIMESTAMP, approved_by = :admin_id
        WHERE id = :user_id
    ');

    $stmt->bindValue(':admin_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'User activated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to activate user']);
    }
}

// ============================================
// GET STATS
// ============================================
elseif ($action === 'stats') {
    $stats = [
        'total' => 0,
        'pending' => 0,
        'active' => 0,
        'suspended' => 0
    ];

    $result = $db->query('SELECT status, COUNT(*) as count FROM users GROUP BY status');

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $stats[$row['status']] = $row['count'];
    }

    $totalResult = $db->query('SELECT COUNT(*) as total FROM users');
    $totalRow = $totalResult->fetchArray(SQLITE3_ASSOC);
    $stats['total'] = $totalRow['total'];

    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
}

// ============================================
// INVALID ACTION
// ============================================
else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

$db->close();
?>
