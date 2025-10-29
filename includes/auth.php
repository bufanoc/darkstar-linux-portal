<?php
require_once 'config.php';
require_once 'db.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // User authentication
    public function loginUser($username_or_email, $password) {
        // Check if it's an email or username
        $user = strpos($username_or_email, '@') !== false
            ? $this->db->getUserByEmail($username_or_email)
            : $this->db->getUserByUsername($username_or_email);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION[USER_SESSION_KEY] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'login_time' => time()
            ];
            return true;
        }
        return false;
    }

    public function isUserLoggedIn() {
        if (!isset($_SESSION[USER_SESSION_KEY])) {
            return false;
        }

        // Check session timeout
        if (time() - $_SESSION[USER_SESSION_KEY]['login_time'] > SESSION_TIMEOUT) {
            $this->logoutUser();
            return false;
        }

        return true;
    }

    public function logoutUser() {
        unset($_SESSION[USER_SESSION_KEY]);
    }

    public function getCurrentUser() {
        return $_SESSION[USER_SESSION_KEY] ?? null;
    }

    // Admin authentication
    public function loginAdmin($username, $password) {
        $admin = $this->db->getAdminUser($username);

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION[ADMIN_SESSION_KEY] = [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'login_time' => time()
            ];
            return true;
        }
        return false;
    }

    public function isAdminLoggedIn() {
        if (!isset($_SESSION[ADMIN_SESSION_KEY])) {
            return false;
        }

        // Check session timeout
        if (time() - $_SESSION[ADMIN_SESSION_KEY]['login_time'] > SESSION_TIMEOUT) {
            $this->logoutAdmin();
            return false;
        }

        return true;
    }

    public function logoutAdmin() {
        unset($_SESSION[ADMIN_SESSION_KEY]);
    }

    public function getCurrentAdmin() {
        return $_SESSION[ADMIN_SESSION_KEY] ?? null;
    }

    // Require authentication
    public function requireUserAuth() {
        if (!$this->isUserLoggedIn()) {
            header('Location: /index.html');
            exit;
        }
    }

    public function requireAdminAuth() {
        if (!$this->isAdminLoggedIn()) {
            header('Location: /admin/login.php');
            exit;
        }
    }
}
