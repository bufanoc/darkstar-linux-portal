#!/usr/bin/php
<?php
// Database Initialization Script
// Creates database and admin user

$dbPath = '/var/lib/darkstar/users.db';

echo "🚀 Initializing Dark Star Authentication System\n\n";

// Create database
$db = new SQLite3($dbPath);

// Create users table
echo "📊 Creating users table...\n";
$db->exec('
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT "user",
    status TEXT NOT NULL DEFAULT "pending",
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME,
    approved_by INTEGER,
    last_login DATETIME,
    FOREIGN KEY (approved_by) REFERENCES users(id)
)
');

// Create sessions table
echo "📊 Creating sessions table...\n";
$db->exec('
CREATE TABLE IF NOT EXISTS sessions (
    session_id TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address TEXT,
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id)
)
');

echo "✅ Database tables created\n\n";

// Check if admin user exists
$result = $db->query("SELECT id, username FROM users WHERE role = 'admin' LIMIT 1");
$adminExists = $result->fetchArray();

if ($adminExists) {
    echo "ℹ️  Admin user already exists: " . $adminExists['username'] . "\n";
    echo "Database initialization complete.\n";
    $db->close();
    exit(0);
}

// Create admin user with prompted credentials
echo "👤 Creating admin user...\n";
echo "Enter admin username: ";
$username = trim(fgets(STDIN));

echo "Enter admin email: ";
$email = trim(fgets(STDIN));

echo "Enter admin password: ";
// Hide password input
system('stty -echo');
$password = trim(fgets(STDIN));
system('stty echo');
echo "\n";

// Validate inputs
if (empty($username) || empty($email) || empty($password)) {
    echo "❌ Error: All fields are required\n";
    exit(1);
}

// Hash password
echo "🔒 Hashing password...\n";
$passwordHash = password_hash($password, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,
    'time_cost' => 4,
    'threads' => 1
]);

// Insert admin user
$stmt = $db->prepare('
    INSERT INTO users (username, email, password_hash, role, status, approved_at)
    VALUES (:username, :email, :password_hash, "admin", "active", CURRENT_TIMESTAMP)
');

$stmt->bindValue(':username', $username, SQLITE3_TEXT);
$stmt->bindValue(':email', $email, SQLITE3_TEXT);
$stmt->bindValue(':password_hash', $passwordHash, SQLITE3_TEXT);

if ($stmt->execute()) {
    echo "\n✅ Admin user created successfully!\n\n";
    echo "═══════════════════════════════════\n";
    echo "  Admin Credentials\n";
    echo "═══════════════════════════════════\n";
    echo "  Username: $username\n";
    echo "  Email: $email\n";
    echo "  Role: admin\n";
    echo "  Status: active\n";
    echo "═══════════════════════════════════\n\n";

    // Set proper permissions
    chmod($dbPath, 0660);
    chown($dbPath, 'www-data');
    chgrp($dbPath, 'www-data');

    echo "🔐 Database permissions set\n";
    echo "🎉 Authentication system initialized!\n";
} else {
    echo "\n❌ Error creating admin user: " . $db->lastErrorMsg() . "\n";
    exit(1);
}

$db->close();
?>
