<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        if ($auth->loginAdmin($username, $password)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid credentials';
        }
    } else {
        $error = 'Please fill in all fields';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Terminal Portal</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        .admin-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, var(--bg-darker) 0%, var(--bg-dark) 100%);
        }

        .admin-login-box {
            background: var(--bg-dark);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 12px;
            padding: 3rem;
            max-width: 400px;
            width: 90%;
        }

        .admin-login-title {
            text-align: center;
            color: var(--purple-light);
            margin-bottom: 2rem;
            font-size: 2rem;
        }

        .admin-error {
            background: rgba(255, 95, 86, 0.2);
            border: 1px solid rgba(255, 95, 86, 0.5);
            color: #ff5f56;
            padding: 0.8rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .admin-form-group {
            margin-bottom: 1.5rem;
        }

        .admin-form-group label {
            display: block;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .admin-form-group input {
            width: 100%;
            padding: 0.8rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .admin-form-group input:focus {
            outline: none;
            border-color: var(--purple-primary);
            background: rgba(255, 255, 255, 0.08);
        }

        .admin-btn {
            width: 100%;
            padding: 1rem;
            background: var(--purple-primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .admin-btn:hover {
            background: var(--purple-light);
            transform: translateY(-2px);
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: var(--purple-primary);
            text-decoration: none;
        }

        .back-link a:hover {
            color: var(--purple-light);
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-box">
            <h1 class="admin-login-title">Admin Login</h1>

            <?php if ($error): ?>
                <div class="admin-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="admin-form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="admin-form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="admin-btn">Login</button>
            </form>

            <div class="back-link">
                <a href="/">← Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>
