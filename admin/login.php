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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Custom CSS for Professional Admin Login -->
    <style>
        :root {
            --bg: #090d16;
            --panel-bg: rgba(17, 24, 39, 0.7);
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --accent: #2563eb;
            --accent-hover: #1d4ed8;
            --border-color: rgba(255, 255, 255, 0.08);
            --font-sans: 'Plus Jakarta Sans', sans-serif;
            --font-display: 'Outfit', sans-serif;
            --danger: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(37, 99, 235, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(37, 99, 235, 0.05) 0%, transparent 40%);
            color: var(--text-primary);
            font-family: var(--font-sans);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        /* Login Card Container */
        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 45px 35px;
            background: var(--panel-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.05);
            z-index: 10;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .brand-header h1 {
            font-family: var(--font-display);
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #ffffff 0%, #d1d5db 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 6px;
        }

        .brand-header p {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 22px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 44px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-family: var(--font-sans);
            font-size: 0.9rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-control::placeholder {
            color: #4b5563;
        }

        .form-control:focus {
            background: rgba(0, 0, 0, 0.3);
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .form-control:focus + i {
            color: var(--accent);
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: var(--accent);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-family: var(--font-sans);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
            margin-top: 8px;
        }

        .btn-submit:hover {
            background: var(--accent-hover);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.35);
        }

        .btn-submit:active {
            transform: translateY(1px);
        }

        /* Error Banner */
        .error-banner {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--danger);
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-4px); }
            75% { transform: translateX(4px); }
        }

        .footer-text {
            position: absolute;
            bottom: 24px;
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand-header">
            <h1>Perumalil Creative</h1>
            <p>Admin Portal</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="error-banner">
                <i data-lucide="alert-circle" size="18"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" required autofocus>
                    <i data-lucide="user" size="16"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
                    <i data-lucide="lock" size="16"></i>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span>Sign In</span>
                <i data-lucide="arrow-right" size="16"></i>
            </button>
        </form>
    </div>

    <div class="footer-text">
        &copy; 2026 Perumalil Creative. All rights reserved.
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
