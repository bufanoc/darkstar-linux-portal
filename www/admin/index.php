<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

$auth = new Auth();
$auth->requireAdminAuth();

$db = new Database();
$signupRequests = $db->getSignupRequests('pending');
$users = $db->getAllUsers();

$admin = $auth->getCurrentAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Terminal Portal</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="admin-style.css">
</head>
<body class="admin-body">
    <nav class="admin-nav">
        <div class="admin-nav-container">
            <h1>Terminal Portal Admin</h1>
            <div class="admin-nav-right">
                <span>Welcome, <?= htmlspecialchars($admin['username']) ?></span>
                <a href="logout.php" class="admin-logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <div class="admin-tabs">
            <button class="admin-tab active" onclick="showTab('signups')">Pending Signups (<?= count($signupRequests) ?>)</button>
            <button class="admin-tab" onclick="showTab('users')">Manage Users (<?= count($users) ?>)</button>
        </div>

        <!-- Signup Requests Tab -->
        <div id="signups-tab" class="admin-tab-content active">
            <h2>Pending Signup Requests</h2>

            <?php if (empty($signupRequests)): ?>
                <p class="admin-empty">No pending signup requests</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($signupRequests as $request): ?>
                        <tr>
                            <td><?= htmlspecialchars($request['name']) ?></td>
                            <td><?= htmlspecialchars($request['email']) ?></td>
                            <td><?= htmlspecialchars($request['phone'] ?? 'N/A') ?></td>
                            <td><?= date('M j, Y g:ia', strtotime($request['submitted_at'])) ?></td>
                            <td>
                                <button class="admin-btn-small admin-btn-success" onclick="approveSignup(<?= $request['id'] ?>, '<?= htmlspecialchars($request['email']) ?>')">
                                    Create Account
                                </button>
                                <button class="admin-btn-small admin-btn-danger" onclick="rejectSignup(<?= $request['id'] ?>)">
                                    Reject
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Users Management Tab -->
        <div id="users-tab" class="admin-tab-content">
            <h2>Manage Users</h2>

            <?php if (empty($users)): ?>
                <p class="admin-empty">No users yet</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Created</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <span class="status-badge <?= $user['active'] ? 'status-active' : 'status-inactive' ?>">
                                    <?= $user['active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['active']): ?>
                                    <button class="admin-btn-small admin-btn-warning" onclick="toggleUserStatus(<?= $user['id'] ?>, 0)">
                                        Deactivate
                                    </button>
                                <?php else: ?>
                                    <button class="admin-btn-small admin-btn-success" onclick="toggleUserStatus(<?= $user['id'] ?>, 1)">
                                        Activate
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Account Modal -->
    <div id="createAccountModal" class="admin-modal">
        <div class="admin-modal-content">
            <span class="admin-modal-close" onclick="closeCreateAccountModal()">&times;</span>
            <h2>Create User Account</h2>
            <form id="createAccountForm">
                <input type="hidden" id="signupId" name="signup_id">
                <div class="admin-form-group">
                    <label for="newUsername">Username</label>
                    <input type="text" id="newUsername" name="username" required>
                </div>
                <div class="admin-form-group">
                    <label for="newEmail">Email (auto-filled)</label>
                    <input type="email" id="newEmail" name="email" readonly>
                </div>
                <div class="admin-form-group">
                    <label for="newPassword">Temporary Password</label>
                    <input type="text" id="newPassword" name="password" required>
                    <button type="button" class="admin-btn-link" onclick="generatePassword()">Generate Random</button>
                </div>
                <div class="admin-form-message" id="createAccountMessage"></div>
                <button type="submit" class="admin-btn">Create Account</button>
            </form>
        </div>
    </div>

    <script>
        // Tab switching
        function showTab(tabName) {
            document.querySelectorAll('.admin-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.admin-tab-content').forEach(content => content.classList.remove('active'));

            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }

        // Approve signup
        function approveSignup(id, email) {
            document.getElementById('signupId').value = id;
            document.getElementById('newEmail').value = email;
            document.getElementById('newUsername').value = email.split('@')[0];
            generatePassword();
            document.getElementById('createAccountModal').style.display = 'flex';
        }

        // Close modal
        function closeCreateAccountModal() {
            document.getElementById('createAccountModal').style.display = 'none';
            document.getElementById('createAccountForm').reset();
            document.getElementById('createAccountMessage').className = 'admin-form-message';
        }

        // Generate random password
        function generatePassword() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%^&*';
            let password = '';
            for (let i = 0; i < 12; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('newPassword').value = password;
        }

        // Create account form submission
        document.getElementById('createAccountForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = {
                signup_id: document.getElementById('signupId').value,
                username: document.getElementById('newUsername').value,
                email: document.getElementById('newEmail').value,
                password: document.getElementById('newPassword').value
            };

            const messageEl = document.getElementById('createAccountMessage');

            try {
                const response = await fetch('create-account.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    messageEl.textContent = data.message;
                    messageEl.className = 'admin-form-message success';

                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    messageEl.textContent = data.message;
                    messageEl.className = 'admin-form-message error';
                }
            } catch (error) {
                messageEl.textContent = 'An error occurred';
                messageEl.className = 'admin-form-message error';
            }
        });

        // Reject signup
        async function rejectSignup(id) {
            if (!confirm('Are you sure you want to reject this signup request?')) return;

            try {
                const response = await fetch('reject-signup.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ signup_id: id })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Failed to reject signup');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }

        // Toggle user status
        async function toggleUserStatus(userId, active) {
            const action = active ? 'activate' : 'deactivate';
            if (!confirm(`Are you sure you want to ${action} this user?`)) return;

            try {
                const response = await fetch('toggle-user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, active: active })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Failed to update user status');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }
    </script>
</body>
</html>
