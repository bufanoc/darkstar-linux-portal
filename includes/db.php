<?php
require_once 'config.php';

class Database {
    private $pdo;

    public function __construct() {
        try {
            $this->pdo = new PDO('sqlite:' . DB_PATH);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // Enable foreign keys
            $this->pdo->exec('PRAGMA foreign_keys = ON;');
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            die('Database connection failed');
        }
    }

    public function getConnection() {
        return $this->pdo;
    }

    // Signup requests
    public function createSignupRequest($name, $email, $phone) {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO signup_requests (name, email, phone, status)
                 VALUES (:name, :email, :phone, 'pending')"
            );
            return $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone
            ]);
        } catch (PDOException $e) {
            error_log('Signup request error: ' . $e->getMessage());
            return false;
        }
    }

    public function getSignupRequests($status = null) {
        try {
            if ($status) {
                $stmt = $this->pdo->prepare(
                    "SELECT * FROM signup_requests WHERE status = :status ORDER BY submitted_at DESC"
                );
                $stmt->execute([':status' => $status]);
            } else {
                $stmt = $this->pdo->query(
                    "SELECT * FROM signup_requests ORDER BY submitted_at DESC"
                );
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Get signup requests error: ' . $e->getMessage());
            return [];
        }
    }

    public function updateSignupStatus($id, $status) {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE signup_requests SET status = :status WHERE id = :id"
            );
            return $stmt->execute([':status' => $status, ':id' => $id]);
        } catch (PDOException $e) {
            error_log('Update signup status error: ' . $e->getMessage());
            return false;
        }
    }

    // Users
    public function createUser($username, $email, $password) {
        try {
            $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
            $stmt = $this->pdo->prepare(
                "INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)"
            );
            return $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $password_hash
            ]);
        } catch (PDOException $e) {
            error_log('Create user error: ' . $e->getMessage());
            return false;
        }
    }

    public function getUserByUsername($username) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = :username AND active = 1");
            $stmt->execute([':username' => $username]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Get user error: ' . $e->getMessage());
            return false;
        }
    }

    public function getUserByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email AND active = 1");
            $stmt->execute([':email' => $email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Get user by email error: ' . $e->getMessage());
            return false;
        }
    }

    public function getAllUsers() {
        try {
            $stmt = $this->pdo->query("SELECT id, username, email, created_at, active FROM users ORDER BY created_at DESC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Get all users error: ' . $e->getMessage());
            return [];
        }
    }

    public function updateUserStatus($id, $active) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET active = :active WHERE id = :id");
            return $stmt->execute([':active' => $active ? 1 : 0, ':id' => $id]);
        } catch (PDOException $e) {
            error_log('Update user status error: ' . $e->getMessage());
            return false;
        }
    }

    // Admin users
    public function createAdminUser($username, $password) {
        try {
            $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
            $stmt = $this->pdo->prepare(
                "INSERT INTO admin_users (username, password_hash) VALUES (:username, :password_hash)"
            );
            return $stmt->execute([
                ':username' => $username,
                ':password_hash' => $password_hash
            ]);
        } catch (PDOException $e) {
            error_log('Create admin user error: ' . $e->getMessage());
            return false;
        }
    }

    public function getAdminUser($username) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM admin_users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Get admin user error: ' . $e->getMessage());
            return false;
        }
    }
}
