<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

$auth = new Auth();
$auth->requireUserAuth();

$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Terminal Portal</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        .dashboard-container {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--bg-darker) 0%, var(--bg-dark) 100%);
            padding-top: 80px;
        }

        .dashboard-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(10, 10, 15, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(168, 85, 247, 0.2);
            z-index: 1000;
            padding: 1rem 0;
        }

        .dashboard-nav-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-logo {
            font-size: 1.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--purple-light), var(--purple-primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 2px;
        }

        .dashboard-user {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .dashboard-user span {
            color: var(--purple-light);
        }

        .dashboard-logout-btn {
            padding: 0.5rem 1.2rem;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dashboard-logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .dashboard-content {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 2rem;
        }

        .dashboard-welcome {
            text-align: center;
            margin-bottom: 3rem;
        }

        .dashboard-welcome h1 {
            font-size: 3rem;
            background: linear-gradient(135deg, var(--purple-light), var(--purple-primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .dashboard-welcome p {
            color: var(--text-secondary);
            font-size: 1.2rem;
        }

        .dashboard-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(168, 85, 247, 0.2);
            border-radius: 12px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            border-color: var(--purple-primary);
            box-shadow: 0 10px 40px rgba(168, 85, 247, 0.3);
        }

        .dashboard-card-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .dashboard-card h3 {
            color: var(--purple-light);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .dashboard-card p {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .dashboard-terminal-section {
            margin-top: 3rem;
            background: var(--terminal-bg);
            border: 1px solid rgba(168, 85, 247, 0.2);
            border-radius: 12px;
            overflow: hidden;
        }

        .dashboard-terminal-header {
            background: #16162a;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid rgba(168, 85, 247, 0.2);
        }

        .terminal-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .terminal-dot.red { background: #ff5f56; }
        .terminal-dot.yellow { background: #ffbd2e; }
        .terminal-dot.green { background: #27c93f; }

        .dashboard-terminal-embed {
            width: 100%;
            height: 600px;
        }

        .dashboard-terminal-embed iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>
    <nav class="dashboard-nav">
        <div class="dashboard-nav-content">
            <div class="dashboard-logo">TERMINAL PORTAL</div>
            <div class="dashboard-user">
                <span>Welcome, <?= htmlspecialchars($user['username']) ?></span>
                <form action="/api/logout.php" method="POST" style="margin: 0;">
                    <button type="submit" class="dashboard-logout-btn">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-content">
            <div class="dashboard-welcome">
                <h1>Members Dashboard</h1>
                <p>Welcome to your secure portal, <?= htmlspecialchars($user['username']) ?></p>
            </div>

            <div class="dashboard-features">
                <div class="dashboard-card">
                    <div class="dashboard-card-icon">🚀</div>
                    <h3>Enhanced Terminal</h3>
                    <p>Access to the full Ubuntu terminal with extended session times and additional features.</p>
                </div>

                <div class="dashboard-card">
                    <div class="dashboard-card-icon">📊</div>
                    <h3>Activity History</h3>
                    <p>View your command history and session analytics (coming soon).</p>
                </div>

                <div class="dashboard-card">
                    <div class="dashboard-card-icon">🔒</div>
                    <h3>Private Workspace</h3>
                    <p>Your own isolated environment with persistent storage options.</p>
                </div>
            </div>

            <div class="dashboard-terminal-section">
                <div class="dashboard-terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span style="color: var(--text-secondary); font-family: monospace; margin-left: auto;">
                        <?= htmlspecialchars($user['username']) ?>@terminal-portal
                    </span>
                </div>
                <div class="dashboard-terminal-embed">
                    <iframe src="/terminal/"></iframe>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
