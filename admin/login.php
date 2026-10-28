<?php
// admin/login.php - Professional Admin Authentication Page
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username !== '' && $password !== '') {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'Authentication system error: ' . $e->getMessage();
        }
    } else {
        $error = 'Please fill in all security fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Login | Perumalil Creative</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Central Stylesheet with Cache Buster -->
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo @filemtime(dirname(__DIR__) . '/assets/css/main.css') ?: time(); ?>">
</head>
<body class="admin-login-body">

    <!-- Ambient Glowing Orbs -->
    <div class="login-ambient-orb orb-1"></div>
    <div class="login-ambient-orb orb-2"></div>

    <div class="login-container">
        <!-- Return Link -->
        <a href="../index.php" class="back-to-site-top">
            <i data-lucide="arrow-left" size="14"></i>
            <span>Return to Perumalil Creative</span>
        </a>

        <!-- Main Glass Card -->
        <div class="login-card">
            <div class="brand-header">
                <div class="brand-badge-icon">
                    <i data-lucide="shield-check" size="24"></i>
                </div>
                <h1>Perumalil Creative</h1>
                <div class="portal-status-tag">
                    <span class="status-pulse-dot"></span>
                    <span>Admin Security Gateway</span>
                </div>
            </div>

            <?php if ($error !== ''): ?>
                <div class="error-banner">
                    <i data-lucide="alert-circle" size="18"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" autocomplete="on">
                <div class="form-group">
                    <label for="username" class="form-label">Username / Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon-left">
                            <i data-lucide="user" size="18"></i>
                        </span>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-control" 
                            placeholder="admin@perumalilcreative.com" 
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            autocomplete="username" 
                            required 
                            autofocus
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon-left">
                            <i data-lucide="lock" size="18"></i>
                        </span>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="••••••••••••" 
                            autocomplete="current-password" 
                            required
                        >
                        <button type="button" class="btn-toggle-password" id="togglePasswordBtn" aria-label="Toggle password visibility" tabindex="-1">
                            <i data-lucide="eye" id="togglePasswordIcon" size="18"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="submitLoginBtn">
                    <span>Authenticate & Access</span>
                    <i data-lucide="arrow-right" size="18"></i>
                </button>
            </form>

            <div class="security-meta">
                <i data-lucide="lock" size="12"></i>
                <span>256-Bit SSL Encrypted Admin Control Pipeline</span>
            </div>
        </div>

        <div class="login-footer-copy">
            &copy; 2026 Perumalil Creative. All rights reserved. Authorised personnel only.
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Interactive Password Toggle
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('togglePasswordIcon');

        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const isPassword = passwordInput.getAttribute('type') === 'password';
                if (isPassword) {
                    passwordInput.setAttribute('type', 'text');
                    toggleBtn.innerHTML = '<i data-lucide="eye-off" size="18"></i>';
                } else {
                    passwordInput.setAttribute('type', 'password');
                    toggleBtn.innerHTML = '<i data-lucide="eye" size="18"></i>';
                }
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        }
    </script>
</body>
</html>
