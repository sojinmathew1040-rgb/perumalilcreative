<?php
// admin/index.php - Secure Premium Command Center
require_once __DIR__ . '/auth.php';
check_auth();
require_once __DIR__ . '/../includes/db.php';

$success_msg = '';
$error_msg = '';
$active_tab = $_GET['tab'] ?? 'global';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. UPDATE SETTINGS
    if ($action === 'update_settings') {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
            foreach ($_POST['settings'] as $key => $value) {
                $stmt->execute([$value, $key]);
            }
            $pdo->commit();
            $success_msg = 'Global settings updated successfully.';
            $active_tab = 'global';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = 'Failed to update settings: ' . $e->getMessage();
        }
    }

    // 2. SECURITY CREDENTIALS UPDATE
    elseif ($action === 'update_credentials') {
        $username = trim($_POST['username'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        
        if ($username !== '') {
            try {
                // Fetch user info
                $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
                $stmt->execute([$_SESSION['admin_user_id']]);
                $user = $stmt->fetch();

                if (password_verify($current_password, $user['password_hash'])) {
                    if ($new_password !== '') {
                        $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                        $update = $pdo->prepare("UPDATE admin_users SET username = ?, password_hash = ? WHERE id = ?");
                        $update->execute([$username, $new_hash, $_SESSION['admin_user_id']]);
                    } else {
                        $update = $pdo->prepare("UPDATE admin_users SET username = ? WHERE id = ?");
                        $update->execute([$username, $_SESSION['admin_user_id']]);
                    }
                    $_SESSION['admin_username'] = $username;
                    $success_msg = 'Credentials updated successfully.';
                } else {
                    $error_msg = 'Incorrect current password.';
                }
                $active_tab = 'security';
            } catch (Exception $e) {
                $error_msg = 'Failed to update credentials: ' . $e->getMessage();
            }
        } else {
            $error_msg = 'Username cannot be empty.';
        }
    }

    // 3. SERVICE ACTIONS
    elseif ($action === 'service_add') {
        $title = trim($_POST['title'] ?? '');
        $icon = trim($_POST['icon'] ?? 'laptop');
        $description = trim($_POST['description'] ?? '');
        $delay = trim($_POST['delay'] ?? '0.1');
        $order_index = (int)($_POST['order_index'] ?? 0);

        if ($title !== '') {
            try {
                $stmt = $pdo->prepare("INSERT INTO services (title, icon, description, delay, order_index) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $icon, $description, $delay, $order_index]);
                $success_msg = 'Service added successfully.';
                $active_tab = 'services';
            } catch (Exception $e) {
                $error_msg = 'Failed to add service: ' . $e->getMessage();
            }
        }
    }
    elseif ($action === 'service_edit') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $icon = trim($_POST['icon'] ?? 'laptop');
        $description = trim($_POST['description'] ?? '');
        $delay = trim($_POST['delay'] ?? '0.1');
        $order_index = (int)($_POST['order_index'] ?? 0);

        if ($id > 0 && $title !== '') {
            try {
                $stmt = $pdo->prepare("UPDATE services SET title = ?, icon = ?, description = ?, delay = ?, order_index = ? WHERE id = ?");
                $stmt->execute([$title, $icon, $description, $delay, $order_index, $id]);
                $success_msg = 'Service updated successfully.';
                $active_tab = 'services';
            } catch (Exception $e) {
                $error_msg = 'Failed to update service: ' . $e->getMessage();
            }
        }
    }
    elseif ($action === 'service_delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
                $stmt->execute([$id]);
                $success_msg = 'Service deleted successfully.';
                $active_tab = 'services';
            } catch (Exception $e) {
                $error_msg = 'Failed to delete service: ' . $e->getMessage();
            }
        }
    }

    // 4. PACKAGE ACTIONS
    elseif ($action === 'package_add') {
        $name = trim($_POST['name'] ?? '');
        $price_label = trim($_POST['price_label'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $features = trim($_POST['features'] ?? '');
        $delay = trim($_POST['delay'] ?? '0.1');
        $is_popular = isset($_POST['is_popular']) ? 1 : 0;
        $order_index = (int)($_POST['order_index'] ?? 0);

        if ($name !== '') {
            try {
                $stmt = $pdo->prepare("INSERT INTO pricing_packages (name, price_label, price, features, delay, is_popular, order_index) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $price_label, $price, $features, $delay, $is_popular, $order_index]);
                $success_msg = 'Pricing package added successfully.';
                $active_tab = 'services';
            } catch (Exception $e) {
                $error_msg = 'Failed to add package: ' . $e->getMessage();
            }
        }
    }
    elseif ($action === 'package_edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price_label = trim($_POST['price_label'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $features = trim($_POST['features'] ?? '');
        $delay = trim($_POST['delay'] ?? '0.1');
        $is_popular = isset($_POST['is_popular']) ? 1 : 0;
        $order_index = (int)($_POST['order_index'] ?? 0);

        if ($id > 0 && $name !== '') {
            try {
                $stmt = $pdo->prepare("UPDATE pricing_packages SET name = ?, price_label = ?, price = ?, features = ?, delay = ?, is_popular = ?, order_index = ? WHERE id = ?");
                $stmt->execute([$name, $price_label, $price, $features, $delay, $is_popular, $order_index, $id]);
                $success_msg = 'Pricing package updated successfully.';
                $active_tab = 'services';
            } catch (Exception $e) {
                $error_msg = 'Failed to update package: ' . $e->getMessage();
            }
        }
    }
    elseif ($action === 'package_delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM pricing_packages WHERE id = ?");
                $stmt->execute([$id]);
                $success_msg = 'Pricing package deleted successfully.';
                $active_tab = 'services';
            } catch (Exception $e) {
                $error_msg = 'Failed to delete package: ' . $e->getMessage();
            }
        }
    }

    // 5. PORTFOLIO ACTIONS
    elseif ($action === 'portfolio_add' || $action === 'portfolio_edit') {
        $id = (int)($_POST['id'] ?? 0);
        $category = trim($_POST['category'] ?? 'video');
        $tag = trim($_POST['tag'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $order_index = (int)($_POST['order_index'] ?? 0);
        
        $image_path = $_POST['existing_image'] ?? '';
        
        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = time() . '_' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $image_path = 'assets/images/' . $filename;
            }
        }

        if ($title !== '') {
            try {
                if ($action === 'portfolio_add') {
                    $stmt = $pdo->prepare("INSERT INTO portfolio_items (category, tag, title, description, link, image_path, order_index) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$category, $tag, $title, $description, $link, $image_path, $order_index]);
                    $success_msg = 'Portfolio item added successfully.';
                } else {
                    $stmt = $pdo->prepare("UPDATE portfolio_items SET category = ?, tag = ?, title = ?, description = ?, link = ?, image_path = ?, order_index = ? WHERE id = ?");
                    $stmt->execute([$category, $tag, $title, $description, $link, $image_path, $order_index, $id]);
                    $success_msg = 'Portfolio item updated successfully.';
                }
                $active_tab = 'portfolio';
            } catch (Exception $e) {
                $error_msg = 'Failed to save portfolio item: ' . $e->getMessage();
            }
        }
    }
    elseif ($action === 'portfolio_delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM portfolio_items WHERE id = ?");
                $stmt->execute([$id]);
                $success_msg = 'Portfolio item deleted successfully.';
                $active_tab = 'portfolio';
            } catch (Exception $e) {
                $error_msg = 'Failed to delete portfolio item: ' . $e->getMessage();
            }
        }
    }

    // 6. TEAM ACTIONS
    elseif ($action === 'team_add' || $action === 'team_edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $order_index = (int)($_POST['order_index'] ?? 0);
        
        $image_path = $_POST['existing_image'] ?? '';

        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = time() . '_' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $image_path = 'assets/images/' . $filename;
            }
        }

        if ($name !== '') {
            try {
                if ($action === 'team_add') {
                    $stmt = $pdo->prepare("INSERT INTO team_members (name, role, image_path, order_index) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $role, $image_path, $order_index]);
                    $success_msg = 'Team member added successfully.';
                } else {
                    $stmt = $pdo->prepare("UPDATE team_members SET name = ?, role = ?, image_path = ?, order_index = ? WHERE id = ?");
                    $stmt->execute([$name, $role, $image_path, $order_index, $id]);
                    $success_msg = 'Team member updated successfully.';
                }
                $active_tab = 'about';
            } catch (Exception $e) {
                $error_msg = 'Failed to save team member: ' . $e->getMessage();
            }
        }
    }
    elseif ($action === 'team_delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM team_members WHERE id = ?");
                $stmt->execute([$id]);
                $success_msg = 'Team member deleted successfully.';
                $active_tab = 'about';
            } catch (Exception $e) {
                $error_msg = 'Failed to delete team member: ' . $e->getMessage();
            }
        }
    }

    // 7. FAQ ACTIONS
    elseif ($action === 'faq_add') {
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        $order_index = (int)($_POST['order_index'] ?? 0);

        if ($question !== '') {
            try {
                $stmt = $pdo->prepare("INSERT INTO faq_items (question, answer, order_index) VALUES (?, ?, ?)");
                $stmt->execute([$question, $answer, $order_index]);
                $success_msg = 'FAQ item added successfully.';
                $active_tab = 'faq';
            } catch (Exception $e) {
                $error_msg = 'Failed to add FAQ item: ' . $e->getMessage();
            }
        }
    }
    elseif ($action === 'faq_edit') {
        $id = (int)($_POST['id'] ?? 0);
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        $order_index = (int)($_POST['order_index'] ?? 0);

        if ($id > 0 && $question !== '') {
            try {
                $stmt = $pdo->prepare("UPDATE faq_items SET question = ?, answer = ?, order_index = ? WHERE id = ?");
                $stmt->execute([$question, $answer, $order_index, $id]);
                $success_msg = 'FAQ item updated successfully.';
                $active_tab = 'faq';
            } catch (Exception $e) {
                $error_msg = 'Failed to update FAQ item: ' . $e->getMessage();
            }
        }
    }
    elseif ($action === 'faq_delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM faq_items WHERE id = ?");
                $stmt->execute([$id]);
                $success_msg = 'FAQ item deleted successfully.';
                $active_tab = 'faq';
            } catch (Exception $e) {
                $error_msg = 'Failed to delete FAQ item: ' . $e->getMessage();
            }
        }
    }
    
    // 8. COLLABORATOR ACTIONS
    elseif ($action === 'collab_add' || $action === 'collab_edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $order_index = (int)($_POST['order_index'] ?? 0);
        
        $image_path = $_POST['existing_image'] ?? '';

        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = time() . '_' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $image_path = 'assets/images/' . $filename;
            }
        }

        if ($name !== '' && $image_path !== '') {
            try {
                if ($action === 'collab_add') {
                    $stmt = $pdo->prepare("INSERT INTO collaborators (name, image_path, order_index) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $image_path, $order_index]);
                    $success_msg = 'Collaborator logo added successfully.';
                } else {
                    $stmt = $pdo->prepare("UPDATE collaborators SET name = ?, image_path = ?, order_index = ? WHERE id = ?");
                    $stmt->execute([$name, $image_path, $order_index, $id]);
                    $success_msg = 'Collaborator logo updated successfully.';
                }
                $active_tab = 'collaborators';
            } catch (Exception $e) {
                $error_msg = 'Failed to save collaborator: ' . $e->getMessage();
            }
        } else {
            $error_msg = 'Collaborator name and logo image are required.';
        }
    }
    elseif ($action === 'collab_delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM collaborators WHERE id = ?");
                $stmt->execute([$id]);
                $success_msg = 'Collaborator deleted successfully.';
                $active_tab = 'collaborators';
            } catch (Exception $e) {
                $error_msg = 'Failed to delete collaborator: ' . $e->getMessage();
            }
        }
    }
    
    // 9. INQUIRY ACTIONS
    elseif ($action === 'inquiry_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'New');
        if ($id > 0 && in_array($status, ['New', 'In Progress', 'Replied', 'Archived'])) {
            try {
                $stmt = $pdo->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
                $success_msg = 'Inquiry status updated successfully.';
                $active_tab = 'inquiries';
            } catch (Exception $e) {
                $error_msg = 'Failed to update inquiry status: ' . $e->getMessage();
            }
        }
    }
    elseif ($action === 'inquiry_delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM inquiries WHERE id = ?");
                $stmt->execute([$id]);
                $success_msg = 'Inquiry deleted successfully.';
                $active_tab = 'inquiries';
            } catch (Exception $e) {
                $error_msg = 'Failed to delete inquiry: ' . $e->getMessage();
            }
        }
    }
}

// Fetch current site settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $error_msg = 'Failed to load site settings: ' . $e->getMessage();
}

// Fetch lists
try {
    $services = $pdo->query("SELECT * FROM services ORDER BY order_index ASC")->fetchAll();
    $packages = $pdo->query("SELECT * FROM pricing_packages ORDER BY order_index ASC")->fetchAll();
    $portfolio = $pdo->query("SELECT * FROM portfolio_items ORDER BY order_index ASC")->fetchAll();
    $team = $pdo->query("SELECT * FROM team_members ORDER BY order_index ASC")->fetchAll();
    $faqs = $pdo->query("SELECT * FROM faq_items ORDER BY order_index ASC")->fetchAll();
    $collaborators_list = $pdo->query("SELECT * FROM collaborators ORDER BY order_index ASC")->fetchAll();
    
    // Auto-create table check just in case self-healing wasn't hit
    try {
        $inquiries = $pdo->query("SELECT * FROM inquiries ORDER BY created_at DESC")->fetchAll();
    } catch (PDOException $ex) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `inquiries` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `package_slug` VARCHAR(100) NOT NULL,
            `package_name` VARCHAR(255) NOT NULL,
            `price` DECIMAL(10,2) NOT NULL,
            `message` TEXT NOT NULL,
            `status` VARCHAR(50) DEFAULT 'New',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $inquiries = $pdo->query("SELECT * FROM inquiries ORDER BY created_at DESC")->fetchAll();
    }
    
    // Count new inquiries
    $new_inquiries_count = 0;
    foreach ($inquiries as $inq) {
        if ($inq['status'] === 'New') {
            $new_inquiries_count++;
        }
    }
} catch (Exception $e) {
    $error_msg = 'Failed to fetch content lists: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Command Center | Perumalil Creative</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Custom CSS for Premium Dashboard -->
    <style>
        :root {
            --bg: #080c14;
            --sidebar-bg: #0c101b;
            --card-bg: #111625;
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --accent: #2563eb;
            --accent-glow: rgba(37, 99, 235, 0.12);
            --border-color: rgba(255, 255, 255, 0.08);
            --font-heading: 'Outfit', sans-serif;
            --font-sans: 'Plus Jakarta Sans', sans-serif;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg);
            color: var(--text-primary);
            font-family: var(--font-sans);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        /* Abstract Grid Backdrop - subtle for software dashboard */
        .grid-backdrop {
            display: none; /* remove matching front side */
        }

        .radial-glow {
            position: fixed;
            top: -200px;
            right: -200px;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.05) 0%, transparent 70%);
            z-index: 1;
            pointer-events: none;
        }

        /* Admin Sidebar Layout */
        .admin-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            width: 100%;
            z-index: 2;
            position: relative;
        }

        .sidebar {
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100vh;
            position: sticky;
            top: 0;
        }

        .sidebar-brand h2 {
            font-family: var(--font-heading);
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #ffffff;
            margin-bottom: 4px;
        }

        .sidebar-brand p {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .sidebar-brand p span.dot {
            width: 6px;
            height: 6px;
            background-color: var(--success);
            border-radius: 50%;
            display: inline-block;
        }

        .sidebar-menu {
            margin-top: 40px;
            flex-grow: 1;
        }

        .menu-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-bottom: 6px;
            border: 1px solid transparent;
        }

        .menu-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.03);
        }

        .menu-link.active {
            color: #ffffff;
            background: var(--accent-glow);
            border-color: rgba(37, 99, 235, 0.2);
        }

        .sidebar-footer {
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 10px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        /* Content Area */
        .content {
            padding: 40px 40px;
            overflow-y: auto;
            max-height: 100vh;
        }

        .content-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 20px;
        }

        .content-header h1 {
            font-family: var(--font-heading);
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
        }

        .content-header p {
            color: var(--text-secondary);
            font-size: 0.88rem;
            margin-top: 4px;
        }

        .view-site-link {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.88rem;
            border: 1px solid var(--border-color);
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .view-site-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
        }

        /* Dashboard Stats Widget Panel */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-details h3 {
            font-size: 1.8rem;
            font-family: var(--font-heading);
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 4px;
        }

        .stat-details p {
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Notifications */
        .alert {
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.88rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        /* Panels */
        .glass-panel {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .glass-panel-title {
            font-family: var(--font-heading);
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
            color: #ffffff;
        }

        .panel-title-text {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Table & Inquiries view layout */
        .table-responsive {
            overflow-x: auto;
            width: 100%;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: rgba(0,0,0,0.1);
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.88rem;
        }

        .admin-table th {
            background: rgba(255, 255, 255, 0.02);
            padding: 14px 16px;
            font-weight: 600;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .admin-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            color: var(--text-primary);
        }

        .admin-table tr:last-child td {
            border-bottom: none;
        }

        .admin-table tr:hover td {
            background: rgba(255, 255, 255, 0.01);
        }

        /* Status badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-new {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .badge-progress {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .badge-replied {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .badge-archived {
            background: rgba(156, 163, 175, 0.1);
            color: var(--text-secondary);
        }

        /* Action Forms & Elements */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-full-width {
            grid-column: span 2;
        }

        .form-group-admin {
            margin-bottom: 16px;
        }

        .form-group-admin label {
            display: block;
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 6px;
            font-weight: 600;
        }

        .form-control-admin {
            width: 100%;
            padding: 10px 14px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-family: var(--font-sans);
            font-size: 0.88rem;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control-admin:focus {
            border-color: var(--accent);
            background: rgba(0, 0, 0, 0.3);
        }

        textarea.form-control-admin {
            resize: vertical;
            min-height: 90px;
        }

        .btn-save {
            padding: 10px 20px;
            background: var(--accent);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-family: var(--font-sans);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-save:hover {
            background: #1d4ed8;
        }

        /* CRUD lists */
        .crud-list {
            margin-top: 15px;
        }

        .crud-item {
            background: rgba(255, 255, 255, 0.01);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            transition: all 0.2s ease;
        }

        .crud-item:hover {
            border-color: rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.02);
        }

        .crud-info {
            flex-grow: 1;
        }

        .crud-info h4 {
            font-size: 1rem;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ffffff;
        }

        .crud-info p {
            color: var(--text-secondary);
            font-size: 0.85rem;
            line-height: 1.45;
        }

        .crud-meta-badge {
            display: inline-block;
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-top: 6px;
            font-weight: 500;
        }

        .crud-actions {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .btn-icon:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-icon.btn-delete:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-color: rgba(239, 68, 68, 0.2);
        }

        .edit-form-wrapper {
            display: none;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed var(--border-color);
            width: 100%;
        }

        .item-image-preview {
            width: 70px;
            height: 70px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid var(--border-color);
            background: #000;
        }

        .image-upload-row {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Detail Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 999;
            padding: 20px;
        }

        .modal-content {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            animation: modalFadeIn 0.3s ease;
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h3 {
            font-family: var(--font-heading);
            font-size: 1.25rem;
            color: #ffffff;
        }

        .modal-close {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            color: #ffffff;
        }

        .modal-body {
            padding: 24px;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .modal-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 20px;
        }

        .modal-meta-item label {
            display: block;
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .modal-meta-item p {
            color: #ffffff;
            font-weight: 500;
        }

        .modal-message-box {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
            padding: 15px;
            border-radius: 8px;
            color: var(--text-primary);
            white-space: pre-wrap;
            margin-top: 10px;
            max-height: 250px;
            overflow-y: auto;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Quick Status form inside table */
        .status-select-form select {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            outline: none;
            cursor: pointer;
        }

        .status-select-form select:focus {
            border-color: var(--accent);
        }

        /* Mobile styling */
        @media (max-width: 991px) {
            .admin-container {
                grid-template-columns: 1fr;
            }
            .sidebar {
                height: auto;
                position: relative;
                padding: 20px;
            }
            .sidebar-menu {
                margin-top: 20px;
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }
            .menu-link {
                margin-bottom: 0;
                padding: 8px 12px;
                font-size: 0.8rem;
            }
            .content {
                padding: 24px 20px;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-full-width {
                grid-column: span 1;
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <div class="radial-glow"></div>

    <div class="admin-container">
        
        <!-- Sidebar Navigation Drawer -->
        <div class="sidebar">
            <div>
                <div class="sidebar-brand">
                    <h2>Studio Control</h2>
                    <p><span class="dot"></span> Connected</p>
                </div>

                <div class="sidebar-menu">
                    <a href="?tab=global" class="menu-link <?php echo $active_tab === 'global' ? 'active' : ''; ?>">
                        <i data-lucide="settings" size="18"></i>
                        <span>Global Settings</span>
                    </a>
                    
                    <a href="?tab=inquiries" class="menu-link <?php echo $active_tab === 'inquiries' ? 'active' : ''; ?>" style="position: relative;">
                        <i data-lucide="mail" size="18"></i>
                        <span>Client Inquiries</span>
                        <?php if ($new_inquiries_count > 0): ?>
                            <span class="badge badge-new" style="margin-left: auto; padding: 2px 6px; border-radius: 10px; font-weight: 700; font-size: 0.72rem;"><?php echo $new_inquiries_count; ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="?tab=services" class="menu-link <?php echo $active_tab === 'services' ? 'active' : ''; ?>">
                        <i data-lucide="layout-grid" size="18"></i>
                        <span>Services &amp; Pricing</span>
                    </a>
                    <a href="?tab=portfolio" class="menu-link <?php echo $active_tab === 'portfolio' ? 'active' : ''; ?>">
                        <i data-lucide="folder-open" size="18"></i>
                        <span>Portfolio Cards</span>
                    </a>
                    <a href="?tab=about" class="menu-link <?php echo $active_tab === 'about' ? 'active' : ''; ?>">
                        <i data-lucide="users" size="18"></i>
                        <span>About &amp; Team</span>
                    </a>
                    <a href="?tab=faq" class="menu-link <?php echo $active_tab === 'faq' ? 'active' : ''; ?>">
                        <i data-lucide="help-circle" size="18"></i>
                        <span>FAQ Accordion</span>
                    </a>
                    <a href="?tab=collaborators" class="menu-link <?php echo $active_tab === 'collaborators' ? 'active' : ''; ?>">
                        <i data-lucide="handshake" size="18"></i>
                        <span>Collaborators</span>
                    </a>
                    <a href="?tab=security" class="menu-link <?php echo $active_tab === 'security' ? 'active' : ''; ?>">
                        <i data-lucide="shield-check" size="18"></i>
                        <span>Security Credentials</span>
                    </a>
                </div>
            </div>

            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i data-lucide="log-out" size="18"></i>
                    <span>Log Out</span>
                </a>
            </div>
        </div>

        <!-- Main Workspace -->
        <div class="content">
            
            <div class="content-header">
                <div>
                    <h1>Command Dashboard</h1>
                    <p>Modify and manage the digital architecture of Perumalil Creative</p>
                </div>
                <a href="../index.php" target="_blank" class="view-site-link">
                    <span>View Frontend</span>
                    <i data-lucide="external-link" size="16"></i>
                </a>
            </div>

            <!-- Alerts System -->
            <?php if ($success_msg !== ''): ?>
                <div class="alert alert-success">
                    <i data-lucide="check-circle" size="20"></i>
                    <span><?php echo htmlspecialchars($success_msg); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($error_msg !== ''): ?>
                <div class="alert alert-error">
                    <i data-lucide="alert-triangle" size="20"></i>
                    <span><?php echo htmlspecialchars($error_msg); ?></span>
                </div>
            <?php endif; ?>

            <!-- Metrics Stats Cards -->
            <?php
            $total_inquiries = count($inquiries);
            $total_services = count($services);
            $total_portfolio = count($portfolio);
            $total_collaborators = count($collaborators_list);
            ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-details">
                        <h3><?php echo $total_inquiries; ?></h3>
                        <p>Total Inquiries</p>
                    </div>
                    <div class="stat-icon">
                        <i data-lucide="mail" size="20"></i>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-details">
                        <h3><?php echo $total_services; ?></h3>
                        <p>Active Services</p>
                    </div>
                    <div class="stat-icon">
                        <i data-lucide="layout-grid" size="20"></i>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-details">
                        <h3><?php echo $total_portfolio; ?></h3>
                        <p>Portfolio Items</p>
                    </div>
                    <div class="stat-icon">
                        <i data-lucide="folder-open" size="20"></i>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-details">
                        <h3><?php echo $total_collaborators; ?></h3>
                        <p>Brand Partners</p>
                    </div>
                    <div class="stat-icon">
                        <i data-lucide="handshake" size="20"></i>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 1: GLOBAL SETTINGS ================= -->
            <?php if ($active_tab === 'global'): ?>
                <div class="glass-panel">
                    <h3 class="glass-panel-title">
                        <i data-lucide="settings" size="20"></i>
                        <span>Global Settings &amp; Local Details</span>
                    </h3>
                    
                    <form action="?tab=global" method="POST">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="form-grid">
                            <h4 class="form-full-width" style="color: var(--accent); font-family: var(--font-heading); margin-top: 10px; font-size: 0.95rem; text-transform: uppercase;">SEO META DEFINITIONS</h4>
                            
                            <div class="form-group-admin form-full-width">
                                <label>SEO Browser Title</label>
                                <input type="text" name="settings[seo_title]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['seo_title'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group-admin form-full-width">
                                <label>SEO Browser Description</label>
                                <textarea name="settings[seo_description]" class="form-control-admin"><?php echo htmlspecialchars($settings['seo_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group-admin form-full-width">
                                <label>SEO Keywords (Comma Separated)</label>
                                <input type="text" name="settings[seo_keywords]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['seo_keywords'] ?? ''); ?>">
                            </div>

                            <h4 class="form-full-width" style="color: var(--accent); font-family: var(--font-heading); margin-top: 20px; font-size: 0.95rem; text-transform: uppercase;">HERO BLOCK IDENTITY</h4>
                            
                            <div class="form-group-admin form-full-width">
                                <label>Hero Statement / Mission Text</label>
                                <textarea name="settings[hero_mission_text]" class="form-control-admin"><?php echo htmlspecialchars($settings['hero_mission_text'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group-admin">
                                <label>Hero Coordinate Text Indicator</label>
                                <input type="text" name="settings[hero_coords]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['hero_coords'] ?? ''); ?>">
                            </div>

                            <h4 class="form-full-width" style="color: var(--accent); font-family: var(--font-heading); margin-top: 20px; font-size: 0.95rem; text-transform: uppercase;">DOUBLES VALUE GRID</h4>

                            <div class="form-group-admin">
                                <label>Intro Left Title</label>
                                <input type="text" name="settings[intro_col_left_title]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['intro_col_left_title'] ?? ''); ?>">
                            </div>
                            <div class="form-group-admin">
                                <label>Intro Left Tagline</label>
                                <input type="text" name="settings[intro_col_left_tagline]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['intro_col_left_tagline'] ?? ''); ?>">
                            </div>
                            <div class="form-group-admin form-full-width">
                                <label>Intro Left Description</label>
                                <textarea name="settings[intro_col_left_desc]" class="form-control-admin"><?php echo htmlspecialchars($settings['intro_col_left_desc'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group-admin">
                                <label>Intro Right Title</label>
                                <input type="text" name="settings[intro_col_right_title]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['intro_col_right_title'] ?? ''); ?>">
                            </div>
                            <div class="form-group-admin">
                                <label>Intro Right Tagline</label>
                                <input type="text" name="settings[intro_col_right_tagline]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['intro_col_right_tagline'] ?? ''); ?>">
                            </div>
                            <div class="form-group-admin form-full-width">
                                <label>Intro Right Description</label>
                                <textarea name="settings[intro_col_right_desc]" class="form-control-admin"><?php echo htmlspecialchars($settings['intro_col_right_desc'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group-admin">
                                <label>Vision Tagline</label>
                                <input type="text" name="settings[intro_vision_tagline]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['intro_vision_tagline'] ?? ''); ?>">
                            </div>
                            <div class="form-group-admin">
                                <label>Vision Title (HTML break &lt;br&gt; tags allowed)</label>
                                <input type="text" name="settings[intro_vision_title]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['intro_vision_title'] ?? ''); ?>">
                            </div>
                            <div class="form-group-admin form-full-width">
                                <label>Vision Description Paragraph</label>
                                <textarea name="settings[intro_vision_text]" class="form-control-admin"><?php echo htmlspecialchars($settings['intro_vision_text'] ?? ''); ?></textarea>
                            </div>

                            <h4 class="form-full-width" style="color: var(--accent); font-family: var(--font-heading); margin-top: 20px; font-size: 0.95rem; text-transform: uppercase;">CONTACT PIPELINE &amp; HEADQUARTERS MAP</h4>
                            
                            <div class="form-group-admin">
                                <label>Contact Section Headline</label>
                                <input type="text" name="settings[contact_headline]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['contact_headline'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group-admin">
                                <label>Contact Section Description</label>
                                <input type="text" name="settings[contact_desc]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['contact_desc'] ?? ''); ?>">
                            </div>

                            <div class="form-group-admin">
                                <label>Direct Helpline 1</label>
                                <input type="text" name="settings[contact_phone1]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['contact_phone1'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group-admin">
                                <label>Direct Helpline 2</label>
                                <input type="text" name="settings[contact_phone2]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['contact_phone2'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group-admin">
                                <label>Email Address</label>
                                <input type="email" name="settings[contact_email]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>">
                            </div>

                            <div class="form-group-admin">
                                <label>Google Maps Directions Link</label>
                                <input type="url" name="settings[location_maps_link]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['location_maps_link'] ?? ''); ?>">
                            </div>

                            <div class="form-group-admin">
                                <label>Office Map Latitude (E.g. 9.9487)</label>
                                <input type="text" name="settings[map_latitude]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['map_latitude'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group-admin">
                                <label>Office Map Longitude (E.g. 76.6294)</label>
                                <input type="text" name="settings[map_longitude]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['map_longitude'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group-admin form-full-width">
                                <label>Office Address (HTML break &lt;br&gt; tags allowed)</label>
                                <textarea name="settings[contact_address]" class="form-control-admin"><?php echo htmlspecialchars($settings['contact_address'] ?? ''); ?></textarea>
                            </div>

                            <h4 class="form-full-width" style="color: var(--accent); font-family: var(--font-heading); margin-top: 20px; font-size: 0.95rem; text-transform: uppercase;">EDITORIAL FOOTER</h4>

                            <div class="form-group-admin form-full-width">
                                <label>Footer Copyright Block</label>
                                <input type="text" name="settings[footer_copyright]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['footer_copyright'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div style="margin-top: 30px; text-align: right;">
                            <button type="submit" class="btn-save">
                                <i data-lucide="save" size="18"></i>
                                <span>Save Settings</span>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- ================= TAB: CLIENT INQUIRIES ================= -->
            <?php if ($active_tab === 'inquiries'): ?>
                <div class="glass-panel">
                    <h3 class="glass-panel-title">
                        <span class="panel-title-text">
                            <i data-lucide="mail" size="20"></i>
                            <span>Client Inquiries &amp; Creative Blueprints</span>
                        </span>
                        <span style="font-size: 0.8rem; font-weight: normal; color: var(--text-secondary);">
                            Total: <?php echo count($inquiries); ?> // New: <?php echo $new_inquiries_count; ?>
                        </span>
                    </h3>

                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Client Name</th>
                                    <th>Email Address</th>
                                    <th>Selected Package</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($inquiries)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                            No inquiries received yet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($inquiries as $inq): ?>
                                        <tr id="inq-row-<?php echo $inq['id']; ?>">
                                            <td>
                                                <?php echo date('M d, Y H:i', strtotime($inq['created_at'])); ?>
                                            </td>
                                            <td style="font-weight: 600;">
                                                <?php echo htmlspecialchars($inq['name']); ?>
                                            </td>
                                            <td>
                                                <a href="mailto:<?php echo htmlspecialchars($inq['email']); ?>" style="color: var(--accent); text-decoration: none;">
                                                    <?php echo htmlspecialchars($inq['email']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($inq['package_name']); ?>
                                            </td>
                                            <td>
                                                &#8377;<?php echo number_format((float)$inq['price']); ?>
                                            </td>
                                            <td>
                                                <form action="?tab=inquiries" method="POST" class="status-select-form" style="display:inline;">
                                                    <input type="hidden" name="action" value="inquiry_status">
                                                    <input type="hidden" name="id" value="<?php echo $inq['id']; ?>">
                                                    <select name="status" onchange="this.form.submit()">
                                                        <option value="New" <?php echo $inq['status'] === 'New' ? 'selected' : ''; ?>>New</option>
                                                        <option value="In Progress" <?php echo $inq['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                        <option value="Replied" <?php echo $inq['status'] === 'Replied' ? 'selected' : ''; ?>>Replied</option>
                                                        <option value="Archived" <?php echo $inq['status'] === 'Archived' ? 'selected' : ''; ?>>Archived</option>
                                                    </select>
                                                </form>
                                            </td>
                                            <td>
                                                <div class="crud-actions">
                                                    <button class="btn-icon" title="Read Message" onclick='viewInquiryDetail(<?php echo json_encode($inq, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                                        <i data-lucide="eye" size="15"></i>
                                                    </button>
                                                    <form action="?tab=inquiries" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this inquiry?');">
                                                        <input type="hidden" name="action" value="inquiry_delete">
                                                        <input type="hidden" name="id" value="<?php echo $inq['id']; ?>">
                                                        <button type="submit" class="btn-icon btn-delete" title="Delete">
                                                            <i data-lucide="trash-2" size="15"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ================= TAB 2: SERVICES & PRICING ================= -->
            <?php if ($active_tab === 'services'): ?>
                <!-- Service Cards Grid management -->
                <div class="glass-panel">
                    <h3 class="glass-panel-title">
                        <i data-lucide="layout-grid" size="20"></i>
                        <span>Manage Services List</span>
                    </h3>

                    <!-- Add Service Box -->
                    <div style="margin-bottom: 30px; padding: 20px; border: 1px solid rgba(255, 255, 255, 0.05); background: rgba(255, 255, 255, 0.01); border-radius: 8px;">
                        <h4 style="margin-bottom: 15px; font-family: var(--font-heading); font-size: 1rem; text-transform: uppercase; color: var(--accent);">Add New Service</h4>
                        <form action="?tab=services" method="POST">
                            <input type="hidden" name="action" value="service_add">
                            <div class="form-grid">
                                <div class="form-group-admin">
                                    <label>Service Title</label>
                                    <input type="text" name="title" class="form-control-admin" placeholder="E.g. Video Production" required>
                                </div>
                                <div class="form-group-admin">
                                    <label>Lucide Icon ID</label>
                                    <input type="text" name="icon" class="form-control-admin" placeholder="E.g. video, search, palette, globe, laptop, megaphone" required>
                                </div>
                                <div class="form-group-admin form-full-width">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control-admin" placeholder="Enter service description..." required></textarea>
                                </div>
                                <div class="form-group-admin">
                                    <label>Reveal Delay (Seconds)</label>
                                    <input type="text" name="delay" class="form-control-admin" value="0.1">
                                </div>
                                <div class="form-group-admin">
                                    <label>Display Sorting Order</label>
                                    <input type="number" name="order_index" class="form-control-admin" value="0">
                                </div>
                            </div>
                            <div style="margin-top: 15px; text-align: right;">
                                <button type="submit" class="btn-save">
                                    <i data-lucide="plus" size="18"></i>
                                    <span>Create Service</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Current Services list -->
                    <div class="crud-list">
                        <?php foreach ($services as $srv): ?>
                            <div class="crud-item" id="service-<?php echo $srv['id']; ?>">
                                <div class="crud-info">
                                    <h4>
                                        <i data-lucide="<?php echo htmlspecialchars($srv['icon']); ?>" style="color: var(--accent);"></i>
                                        <span><?php echo htmlspecialchars($srv['title']); ?></span>
                                    </h4>
                                    <p><?php echo htmlspecialchars($srv['description']); ?></p>
                                    <span class="crud-meta-badge">Order: <?php echo $srv['order_index']; ?> // Delay: <?php echo htmlspecialchars($srv['delay']); ?>s</span>
                                    
                                    <!-- Edit Form -->
                                    <div class="edit-form-wrapper" id="edit-srv-form-<?php echo $srv['id']; ?>">
                                        <form action="?tab=services" method="POST">
                                            <input type="hidden" name="action" value="service_edit">
                                            <input type="hidden" name="id" value="<?php echo $srv['id']; ?>">
                                            <div class="form-grid">
                                                <div class="form-group-admin">
                                                    <label>Service Title</label>
                                                    <input type="text" name="title" class="form-control-admin" value="<?php echo htmlspecialchars($srv['title']); ?>" required>
                                                </div>
                                                <div class="form-group-admin">
                                                    <label>Lucide Icon ID</label>
                                                    <input type="text" name="icon" class="form-control-admin" value="<?php echo htmlspecialchars($srv['icon']); ?>" required>
                                                </div>
                                                <div class="form-group-admin form-full-width">
                                                    <label>Description</label>
                                                    <textarea name="description" class="form-control-admin" required><?php echo htmlspecialchars($srv['description']); ?></textarea>
                                                </div>
                                                <div class="form-group-admin">
                                                    <label>Reveal Delay (Seconds)</label>
                                                    <input type="text" name="delay" class="form-control-admin" value="<?php echo htmlspecialchars($srv['delay']); ?>">
                                                </div>
                                                <div class="form-group-admin">
                                                    <label>Display Sorting Order</label>
                                                    <input type="number" name="order_index" class="form-control-admin" value="<?php echo $srv['order_index']; ?>">
                                                </div>
                                            </div>
                                            <div style="margin-top: 15px; text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
                                                <button type="button" class="btn-save" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color);" onclick="toggleEditForm('srv', <?php echo $srv['id']; ?>)">Cancel</button>
                                                <button type="submit" class="btn-save">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="crud-actions">
                                    <button class="btn-icon" onclick="toggleEditForm('srv', <?php echo $srv['id']; ?>)">
                                        <i data-lucide="edit-3" size="16"></i>
                                    </button>
                                    <form action="?tab=services" method="POST" onsubmit="return confirm('Are you sure you want to delete this service?');">
                                        <input type="hidden" name="action" value="service_delete">
                                        <input type="hidden" name="id" value="<?php echo $srv['id']; ?>">
                                        <button type="submit" class="btn-icon btn-delete">
                                            <i data-lucide="trash-2" size="16"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Pricing Packages list management -->
                <div class="glass-panel">
                    <h3 class="glass-panel-title">
                        <i data-lucide="credit-card" size="20"></i>
                        <span>Manage Pricing Packages</span>
                    </h3>

                    <!-- Add Package Box -->
                    <div style="margin-bottom: 30px; padding: 20px; border: 1px solid rgba(255, 255, 255, 0.05); background: rgba(255, 255, 255, 0.01); border-radius: 8px;">
                        <h4 style="margin-bottom: 15px; font-family: var(--font-heading); font-size: 1rem; text-transform: uppercase; color: var(--accent);">Add New Pricing Package</h4>
                        <form action="?tab=services" method="POST">
                            <input type="hidden" name="action" value="package_add">
                            <div class="form-grid">
                                <div class="form-group-admin">
                                    <label>Package Name</label>
                                    <input type="text" name="name" class="form-control-admin" placeholder="E.g. Web Development" required>
                                </div>
                                <div class="form-group-admin">
                                    <label>Price Label Prefix</label>
                                    <input type="text" name="price_label" class="form-control-admin" placeholder="E.g. Starting From / Monthly" required>
                                </div>
                                <div class="form-group-admin">
                                    <label>Price Value (Without ₹, commas allowed)</label>
                                    <input type="text" name="price" class="form-control-admin" placeholder="E.g. 5,999" required>
                                </div>
                                <div class="form-group-admin">
                                    <label>Reveal Delay (Seconds)</label>
                                    <input type="text" name="delay" class="form-control-admin" value="0.1">
                                </div>
                                <div class="form-group-admin form-full-width">
                                    <label>Features Bullet List (One item per line)</label>
                                    <textarea name="features" class="form-control-admin" rows="4" placeholder="Professional Cinematic Shoot&#10;Advanced Editing Included&#10;4K High Quality Output" required></textarea>
                                </div>
                                <div class="form-group-admin" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                                    <input type="checkbox" id="is_popular_new" name="is_popular" value="1" style="width: 18px; height: 18px;">
                                    <label for="is_popular_new" style="margin-bottom: 0; cursor: pointer;">Mark as Popular Plan</label>
                                </div>
                                <div class="form-group-admin">
                                    <label>Display Sorting Order</label>
                                    <input type="number" name="order_index" class="form-control-admin" value="0">
                                </div>
                            </div>
                            <div style="margin-top: 15px; text-align: right;">
                                <button type="submit" class="btn-save">
                                    <i data-lucide="plus" size="18"></i>
                                    <span>Create Package</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Current Packages list -->
                    <div class="crud-list">
                        <?php foreach ($packages as $pkg): ?>
                            <div class="crud-item" id="package-<?php echo $pkg['id']; ?>">
                                <div class="crud-info">
                                    <h4>
                                        <span><?php echo htmlspecialchars($pkg['name']); ?></span>
                                        <?php if ($pkg['is_popular']): ?>
                                            <span style="font-size: 0.65rem; background: var(--accent); color: white; padding: 2px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase;">Popular</span>
                                        <?php endif; ?>
                                    </h4>
                                    <p style="font-weight: 600; color: #fff; margin-bottom: 8px;"><?php echo htmlspecialchars($pkg['price_label']); ?>: &#8377;<?php echo htmlspecialchars($pkg['price']); ?></p>
                                    <p style="font-size: 0.8rem; white-space: pre-line; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 6px; border: 1px solid var(--border-color);"><?php echo htmlspecialchars($pkg['features']); ?></p>
                                    <span class="crud-meta-badge">Order: <?php echo $pkg['order_index']; ?> // Delay: <?php echo htmlspecialchars($pkg['delay']); ?>s</span>
                                    
                                    <!-- Edit Form -->
                                    <div class="edit-form-wrapper" id="edit-pkg-form-<?php echo $pkg['id']; ?>">
                                        <form action="?tab=services" method="POST">
                                            <input type="hidden" name="action" value="package_edit">
                                            <input type="hidden" name="id" value="<?php echo $pkg['id']; ?>">
                                            <div class="form-grid">
                                                <div class="form-group-admin">
                                                    <label>Package Name</label>
                                                    <input type="text" name="name" class="form-control-admin" value="<?php echo htmlspecialchars($pkg['name']); ?>" required>
                                                </div>
                                                <div class="form-group-admin">
                                                    <label>Price Label Prefix</label>
                                                    <input type="text" name="price_label" class="form-control-admin" value="<?php echo htmlspecialchars($pkg['price_label']); ?>" required>
                                                </div>
                                                <div class="form-group-admin">
                                                    <label>Price Value (Without ₹)</label>
                                                    <input type="text" name="price" class="form-control-admin" value="<?php echo htmlspecialchars($pkg['price']); ?>" required>
                                                </div>
                                                <div class="form-group-admin">
                                                    <label>Reveal Delay (Seconds)</label>
                                                    <input type="text" name="delay" class="form-control-admin" value="<?php echo htmlspecialchars($pkg['delay']); ?>">
                                                </div>
                                                <div class="form-group-admin form-full-width">
                                                    <label>Features Bullet List (One item per line)</label>
                                                    <textarea name="features" class="form-control-admin" rows="4" required><?php echo htmlspecialchars($pkg['features']); ?></textarea>
                                                </div>
                                                <div class="form-group-admin" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                                                    <input type="checkbox" id="is_popular_<?php echo $pkg['id']; ?>" name="is_popular" value="1" <?php echo $pkg['is_popular'] ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                                                    <label for="is_popular_<?php echo $pkg['id']; ?>" style="margin-bottom: 0; cursor: pointer;">Mark as Popular Plan</label>
                                                </div>
                                                <div class="form-group-admin">
                                                    <label>Display Sorting Order</label>
                                                    <input type="number" name="order_index" class="form-control-admin" value="<?php echo $pkg['order_index']; ?>">
                                                </div>
                                            </div>
                                            <div style="margin-top: 15px; text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
                                                <button type="button" class="btn-save" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color);" onclick="toggleEditForm('pkg', <?php echo $pkg['id']; ?>)">Cancel</button>
                                                <button type="submit" class="btn-save">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="crud-actions">
                                    <button class="btn-icon" onclick="toggleEditForm('pkg', <?php echo $pkg['id']; ?>)">
                                        <i data-lucide="edit-3" size="16"></i>
                                    </button>
                                    <form action="?tab=services" method="POST" onsubmit="return confirm('Are you sure you want to delete this pricing package?');">
                                        <input type="hidden" name="action" value="package_delete">
                                        <input type="hidden" name="id" value="<?php echo $pkg['id']; ?>">
                                        <button type="submit" class="btn-icon btn-delete">
                                            <i data-lucide="trash-2" size="16"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ================= TAB 3: PORTFOLIO SHOWCASE ================= -->
            <?php if ($active_tab === 'portfolio'): ?>
                <div class="glass-panel">
                    <h3 class="glass-panel-title">
                        <i data-lucide="folder-open" size="20"></i>
                        <span>Manage Portfolio Cards</span>
                    </h3>

                    <!-- Add Portfolio Item Box -->
                    <div style="margin-bottom: 30px; padding: 20px; border: 1px solid rgba(255, 255, 255, 0.05); background: rgba(255, 255, 255, 0.01); border-radius: 8px;">
                        <h4 style="margin-bottom: 15px; font-family: var(--font-heading); font-size: 1rem; text-transform: uppercase; color: var(--accent);">Add Portfolio Showcase Item</h4>
                        <form action="?tab=portfolio" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="portfolio_add">
                            <div class="form-grid">
                                <div class="form-group-admin">
                                    <label>Display Category Tab</label>
                                    <select name="category" class="form-control-admin" style="background: #111;" required>
                                        <option value="video">Video Production</option>
                                        <option value="web">Web Development</option>
                                        <option value="design">Graphic Design</option>
                                    </select>
                                </div>
                                <div class="form-group-admin">
                                    <label>Display Tag Label</label>
                                    <input type="text" name="tag" class="form-control-admin" placeholder="E.g. Cinematic Event Shoots / UI/UX Design" required>
                                </div>
                                <div class="form-group-admin">
                                    <label>Showcase Title</label>
                                    <input type="text" name="title" class="form-control-admin" placeholder="E.g. Easter Celebration Film" required>
                                </div>
                                <div class="form-group-admin">
                                    <label>Showcase Action Link (URL / #)</label>
                                    <input type="text" name="link" class="form-control-admin" placeholder="E.g. https://www.youtube.com/watch?v=..." required>
                                </div>
                                <div class="form-group-admin form-full-width">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control-admin" placeholder="Enter short showcase item description..." required></textarea>
                                </div>
                                <div class="form-group-admin">
                                    <label>Card Visual Asset Image</label>
                                    <input type="file" name="image" class="form-control-admin" accept="image/*" required>
                                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 5px;">Recommended size: ~800x600px, PNG or JPG format</p>
                                </div>
                                <div class="form-group-admin">
                                    <label>Display Sorting Order</label>
                                    <input type="number" name="order_index" class="form-control-admin" value="0">
                                </div>
                            </div>
                            <div style="margin-top: 15px; text-align: right;">
                                <button type="submit" class="btn-save">
                                    <i data-lucide="plus" size="18"></i>
                                    <span>Create Showcase Item</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Current Portfolio Items list -->
                    <div class="crud-list">
                        <?php foreach ($portfolio as $item): ?>
                            <div class="crud-item" id="portfolio-<?php echo $item['id']; ?>">
                                <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="Showcase Preview" class="item-image-preview">
                                <div class="crud-info">
                                    <h4>
                                        <span><?php echo htmlspecialchars($item['title']); ?></span>
                                        <span class="crud-meta-badge" style="background: var(--accent-glow); color: #fff;"><?php echo htmlspecialchars($item['category']); ?></span>
                                    </h4>
                                    <p style="margin-bottom: 6px; font-weight: 500; font-size: 0.85rem; color: var(--accent);"><?php echo htmlspecialchars($item['tag']); ?></p>
                                    <p><?php echo htmlspecialchars($item['description']); ?></p>
                                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; word-break: break-all;">Link: <?php echo htmlspecialchars($item['link']); ?></p>
                                    <span class="crud-meta-badge">Order: <?php echo $item['order_index']; ?></span>
                                    
                                    <!-- Edit Form -->
                                    <div class="edit-form-wrapper" id="edit-port-form-<?php echo $item['id']; ?>">
                                        <form action="?tab=portfolio" method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="action" value="portfolio_edit">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($item['image_path']); ?>">
                                            
                                            <div class="form-grid">
                                                <div class="form-group-admin">
                                                    <label>Display Category Tab</label>
                                                    <select name="category" class="form-control-admin" style="background: #111;" required>
                                                        <option value="video" <?php echo $item['category'] === 'video' ? 'selected' : ''; ?>>Video Production</option>
                                                        <option value="web" <?php echo $item['category'] === 'web' ? 'selected' : ''; ?>>Web Development</option>
                                                        <option value="design" <?php echo $item['category'] === 'design' ? 'selected' : ''; ?>>Graphic Design</option>
                                                    </select>
                                                </div>
                                                <div class="form-group-admin">
                                                    <label>Display Tag Label</label>
                                                    <input type="text" name="tag" class="form-control-admin" value="<?php echo htmlspecialchars($item['tag']); ?>" required>
                                                </div>
                                                <div class="form-group-admin">
                                                    <label>Showcase Title</label>
                                                    <input type="text" name="title" class="form-control-admin" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                                                </div>
                                                <div class="form-group-admin">
                                                    <label>Showcase Action Link</label>
                                                    <input type="text" name="link" class="form-control-admin" value="<?php echo htmlspecialchars($item['link']); ?>" required>
                                                </div>
                                                <div class="form-group-admin form-full-width">
                                                    <label>Description</label>
                                                    <textarea name="description" class="form-control-admin" required><?php echo htmlspecialchars($item['description']); ?></textarea>
                                                </div>
                                                <div class="form-group-admin form-full-width">
                                                    <label>Replace Visual Image Asset</label>
                                                    <div class="image-upload-row">
                                                        <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="Current preview" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                                        <input type="file" name="image" class="form-control-admin" accept="image/*">
                                                    </div>
                                                </div>
                                                <div class="form-group-admin">
                                                    <label>Display Sorting Order</label>
                                                    <input type="number" name="order_index" class="form-control-admin" value="<?php echo $item['order_index']; ?>">
                                                </div>
                                            </div>
                                            <div style="margin-top: 15px; text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
                                                <button type="button" class="btn-save" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color);" onclick="toggleEditForm('port', <?php echo $item['id']; ?>)">Cancel</button>
                                                <button type="submit" class="btn-save">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="crud-actions">
                                    <button class="btn-icon" onclick="toggleEditForm('port', <?php echo $item['id']; ?>)">
                                        <i data-lucide="edit-3" size="16"></i>
                                    </button>
                                    <form action="?tab=portfolio" method="POST" onsubmit="return confirm('Are you sure you want to delete this showcase item?');">
                                        <input type="hidden" name="action" value="portfolio_delete">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn-icon btn-delete">
                                            <i data-lucide="trash-2" size="16"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ================= TAB 4: ABOUT & TEAM ================= -->
            <?php if ($active_tab === 'about'): ?>
                <!-- Text blocks settings -->
                <div class="glass-panel">
                    <h3 class="glass-panel-title">
                        <i data-lucide="settings" size="20"></i>
                        <span>Philosophy &amp; About Us Text</span>
                    </h3>
                    
                    <form action="?tab=about" method="POST">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="form-grid">
                            <h4 class="form-full-width" style="color: var(--accent); font-family: var(--font-heading); font-size: 0.95rem; text-transform: uppercase;">CREATIVE PHILOSOPHY BLOCK</h4>
                            
                            <div class="form-group-admin">
                                <label>Philosophy Section Tagline</label>
                                <input type="text" name="settings[about_philosophy_tagline]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['about_philosophy_tagline'] ?? ''); ?>">
                            </div>
                            <div class="form-group-admin">
                                <label>Philosophy Title (HTML break &lt;br&gt; tags allowed)</label>
                                <input type="text" name="settings[about_philosophy_title]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['about_philosophy_title'] ?? ''); ?>">
                            </div>
                            <div class="form-group-admin form-full-width">
                                <label>Philosophy Statement Description</label>
                                <textarea name="settings[about_philosophy_desc]" class="form-control-admin"><?php echo htmlspecialchars($settings['about_philosophy_desc'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group-admin">
                                <label>Detail Point 1: Title</label>
                                <input type="text" name="settings[about_detail1_title]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['about_detail1_title'] ?? ''); ?>">
                            </div>
                            <div class="form-group-admin">
                                <label>Detail Point 1: Description</label>
                                <textarea name="settings[about_detail1_desc]" class="form-control-admin"><?php echo htmlspecialchars($settings['about_detail1_desc'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group-admin">
                                <label>Detail Point 2: Title</label>
                                <input type="text" name="settings[about_detail2_title]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['about_detail2_title'] ?? ''); ?>">
                            </div>
                            <div class="form-group-admin">
                                <label>Detail Point 2: Description</label>
                                <textarea name="settings[about_detail2_desc]" class="form-control-admin"><?php echo htmlspecialchars($settings['about_detail2_desc'] ?? ''); ?></textarea>
                            </div>

                            <h4 class="form-full-width" style="color: var(--accent); font-family: var(--font-heading); margin-top: 20px; font-size: 0.95rem; text-transform: uppercase;">ABOUT COMPANY PROFILE</h4>
                            
                            <div class="form-group-admin">
                                <label>About Section Tagline</label>
                                <input type="text" name="settings[about_us_tagline]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['about_us_tagline'] ?? ''); ?>">
                            </div>
                            <div class="form-group-admin">
                                <label>About Title (HTML break &lt;br&gt; tags allowed)</label>
                                <input type="text" name="settings[about_us_title]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['about_us_title'] ?? ''); ?>">
                            </div>
                            <div class="form-group-admin form-full-width">
                                <label>About Section Subtitle / Header Statement</label>
                                <input type="text" name="settings[about_us_subtitle]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['about_us_subtitle'] ?? ''); ?>">
                            </div>
                            <div class="form-group-admin form-full-width">
                                <label>Description Paragraph 1</label>
                                <textarea name="settings[about_us_desc1]" class="form-control-admin" rows="4"><?php echo htmlspecialchars($settings['about_us_desc1'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group-admin form-full-width">
                                <label>Description Paragraph 2</label>
                                <textarea name="settings[about_us_desc2]" class="form-control-admin" rows="4"><?php echo htmlspecialchars($settings['about_us_desc2'] ?? ''); ?></textarea>
                            </div>

                            <h4 class="form-full-width" style="color: var(--accent); font-family: var(--font-heading); margin-top: 20px; font-size: 0.95rem; text-transform: uppercase;">MD LEADERSHIP TESTIMONIAL</h4>
                            
                            <div class="form-group-admin form-full-width">
                                <label>MD Quote Statement</label>
                                <textarea name="settings[testimonial_quote]" class="form-control-admin" rows="4"><?php echo htmlspecialchars($settings['testimonial_quote'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group-admin">
                                <label>MD Author Name</label>
                                <input type="text" name="settings[testimonial_author]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['testimonial_author'] ?? ''); ?>">
                            </div>
                            <div class="form-group-admin">
                                <label>MD Role / Title Designation</label>
                                <input type="text" name="settings[testimonial_role]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['testimonial_role'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div style="margin-top: 30px; text-align: right;">
                            <button type="submit" class="btn-save">
                                <i data-lucide="save" size="18"></i>
                                <span>Save About Settings</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Team Management -->
                <div class="glass-panel">
                    <h3 class="glass-panel-title">
                        <i data-lucide="users" size="20"></i>
                        <span>Manage Leadership Team</span>
                    </h3>

                    <!-- Add Team Member Box -->
                    <div style="margin-bottom: 30px; padding: 20px; border: 1px solid rgba(255, 255, 255, 0.05); background: rgba(255, 255, 255, 0.01); border-radius: 8px;">
                        <h4 style="margin-bottom: 15px; font-family: var(--font-heading); font-size: 1rem; text-transform: uppercase; color: var(--accent);">Add Team Member</h4>
                        <form action="?tab=about" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="team_add">
                            <div class="form-grid">
                                <div class="form-group-admin">
                                    <label>Full Name</label>
                                    <input type="text" name="name" class="form-control-admin" placeholder="E.g. Dijo J Perumaly" required>
                                </div>
                                <div class="form-group-admin">
                                    <label>Role / Position Title</label>
                                    <input type="text" name="role" class="form-control-admin" placeholder="E.g. Managing Director" required>
                                </div>
                                <div class="form-group-admin">
                                    <label>Member Portrait Image / GIF</label>
                                    <input type="file" name="image" class="form-control-admin" accept="image/*" required>
                                </div>
                                <div class="form-group-admin">
                                    <label>Display Sorting Order</label>
                                    <input type="number" name="order_index" class="form-control-admin" value="0">
                                </div>
                            </div>
                            <div style="margin-top: 15px; text-align: right;">
                                <button type="submit" class="btn-save">
                                    <i data-lucide="plus" size="18"></i>
                                    <span>Create Member</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Current Team list -->
                    <div class="crud-list">
                        <?php foreach ($team as $member): ?>
                            <div class="crud-item" id="team-<?php echo $member['id']; ?>">
                                <img src="../<?php echo htmlspecialchars($member['image_path']); ?>" alt="Portrait" class="item-image-preview">
                                <div class="crud-info">
                                    <h4><span><?php echo htmlspecialchars($member['name']); ?></span></h4>
                                    <p style="font-weight: 600; color: var(--accent);"><?php echo htmlspecialchars($member['role']); ?></p>
                                    <span class="crud-meta-badge">Order: <?php echo $member['order_index']; ?></span>
                                    
                                    <!-- Edit Form -->
                                    <div class="edit-form-wrapper" id="edit-team-form-<?php echo $member['id']; ?>">
                                        <form action="?tab=about" method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="action" value="team_edit">
                                            <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($member['image_path']); ?>">
                                            
                                            <div class="form-grid">
                                                <div class="form-group-admin">
                                                    <label>Full Name</label>
                                                    <input type="text" name="name" class="form-control-admin" value="<?php echo htmlspecialchars($member['name']); ?>" required>
                                                </div>
                                                <div class="form-group-admin">
                                                    <label>Role / Position Title</label>
                                                    <input type="text" name="role" class="form-control-admin" value="<?php echo htmlspecialchars($member['role']); ?>" required>
                                                </div>
                                                <div class="form-group-admin form-full-width">
                                                    <label>Replace portrait Image</label>
                                                    <div class="image-upload-row">
                                                        <img src="../<?php echo htmlspecialchars($member['image_path']); ?>" alt="Current preview" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                                        <input type="file" name="image" class="form-control-admin" accept="image/*">
                                                    </div>
                                                </div>
                                                <div class="form-group-admin">
                                                    <label>Display Sorting Order</label>
                                                    <input type="number" name="order_index" class="form-control-admin" value="<?php echo $member['order_index']; ?>">
                                                </div>
                                            </div>
                                            <div style="margin-top: 15px; text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
                                                <button type="button" class="btn-save" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color);" onclick="toggleEditForm('team', <?php echo $member['id']; ?>)">Cancel</button>
                                                <button type="submit" class="btn-save">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="crud-actions">
                                    <button class="btn-icon" onclick="toggleEditForm('team', <?php echo $member['id']; ?>)">
                                        <i data-lucide="edit-3" size="16"></i>
                                    </button>
                                    <form action="?tab=about" method="POST" onsubmit="return confirm('Are you sure you want to delete this team member?');">
                                        <input type="hidden" name="action" value="team_delete">
                                        <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                                        <button type="submit" class="btn-icon btn-delete">
                                            <i data-lucide="trash-2" size="16"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ================= TAB 5: FAQ ACCORDION ================= -->
            <?php if ($active_tab === 'faq'): ?>
                <div class="glass-panel">
                    <h3 class="glass-panel-title">
                        <i data-lucide="help-circle" size="20"></i>
                        <span>Manage FAQ Accordion</span>
                    </h3>

                    <!-- Add FAQ Box -->
                    <div style="margin-bottom: 30px; padding: 20px; border: 1px solid rgba(255, 255, 255, 0.05); background: rgba(255, 255, 255, 0.01); border-radius: 8px;">
                        <h4 style="margin-bottom: 15px; font-family: var(--font-heading); font-size: 1rem; text-transform: uppercase; color: var(--accent);">Add New Accordion FAQ</h4>
                        <form action="?tab=faq" method="POST">
                            <input type="hidden" name="action" value="faq_add">
                            <div class="form-grid">
                                <div class="form-group-admin form-full-width">
                                    <label>Question text</label>
                                    <input type="text" name="question" class="form-control-admin" placeholder="E.g. What is the standard turnaround time?" required>
                                </div>
                                <div class="form-group-admin form-full-width">
                                    <label>Detailed Answer</label>
                                    <textarea name="answer" class="form-control-admin" rows="4" placeholder="Enter accordion expansion answer details..." required></textarea>
                                </div>
                                <div class="form-group-admin">
                                    <label>Display Sorting Order</label>
                                    <input type="number" name="order_index" class="form-control-admin" value="0">
                                </div>
                            </div>
                            <div style="margin-top: 15px; text-align: right;">
                                <button type="submit" class="btn-save">
                                    <i data-lucide="plus" size="18"></i>
                                    <span>Create FAQ</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Current FAQs list -->
                    <div class="crud-list">
                        <?php foreach ($faqs as $faq): ?>
                            <div class="crud-item" id="faq-<?php echo $faq['id']; ?>">
                                <div class="crud-info">
                                    <h4 style="font-family: var(--font-sans); font-weight: 700; text-transform: none;"><?php echo htmlspecialchars($faq['question']); ?></h4>
                                    <p><?php echo htmlspecialchars($faq['answer']); ?></p>
                                    <span class="crud-meta-badge">Order: <?php echo $faq['order_index']; ?></span>
                                    
                                    <!-- Edit Form -->
                                    <div class="edit-form-wrapper" id="edit-faq-form-<?php echo $faq['id']; ?>">
                                        <form action="?tab=faq" method="POST">
                                            <input type="hidden" name="action" value="faq_edit">
                                            <input type="hidden" name="id" value="<?php echo $faq['id']; ?>">
                                            <div class="form-grid">
                                                <div class="form-group-admin form-full-width">
                                                    <label>Question Text</label>
                                                    <input type="text" name="question" class="form-control-admin" value="<?php echo htmlspecialchars($faq['question']); ?>" required>
                                                </div>
                                                <div class="form-group-admin form-full-width">
                                                    <label>Detailed Answer</label>
                                                    <textarea name="answer" class="form-control-admin" rows="4" required><?php echo htmlspecialchars($faq['answer']); ?></textarea>
                                                </div>
                                                <div class="form-group-admin">
                                                    <label>Display Sorting Order</label>
                                                    <input type="number" name="order_index" class="form-control-admin" value="<?php echo $faq['order_index']; ?>">
                                                </div>
                                            </div>
                                            <div style="margin-top: 15px; text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
                                                <button type="button" class="btn-save" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color);" onclick="toggleEditForm('faq', <?php echo $faq['id']; ?>)">Cancel</button>
                                                <button type="submit" class="btn-save">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="crud-actions">
                                    <button class="btn-icon" onclick="toggleEditForm('faq', <?php echo $faq['id']; ?>)">
                                        <i data-lucide="edit-3" size="16"></i>
                                    </button>
                                    <form action="?tab=faq" method="POST" onsubmit="return confirm('Are you sure you want to delete this FAQ item?');">
                                        <input type="hidden" name="action" value="faq_delete">
                                        <input type="hidden" name="id" value="<?php echo $faq['id']; ?>">
                                        <button type="submit" class="btn-icon btn-delete">
                                            <i data-lucide="trash-2" size="16"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ================= TAB 6: SECURITY CREDENTIALS ================= -->
            <?php if ($active_tab === 'security'): ?>
                <div class="glass-panel">
                    <h3 class="glass-panel-title">
                        <i data-lucide="shield-check" size="20"></i>
                        <span>Security Credentials Configuration</span>
                    </h3>
                    
                    <form action="?tab=security" method="POST">
                        <input type="hidden" name="action" value="update_credentials">
                        
                        <div class="form-grid">
                            <div class="form-group-admin">
                                <label>Administrator Username</label>
                                <input type="text" name="username" class="form-control-admin" value="<?php echo htmlspecialchars($_SESSION['admin_username']); ?>" required>
                            </div>
                            
                            <div class="form-group-admin">
                                <label>New Password (Leave blank to keep current)</label>
                                <input type="password" name="new_password" class="form-control-admin" placeholder="Enter new password...">
                            </div>
                            
                            <div class="form-group-admin form-full-width" style="border-top: 1px solid var(--border-color); padding-top: 20px; margin-top: 10px;">
                                <label style="color: var(--danger);">Confirm current Decryption Password to apply</label>
                                <input type="password" name="current_password" class="form-control-admin" placeholder="Enter current password to save..." required>
                            </div>
                        </div>
                        
                        <div style="margin-top: 30px; text-align: right;">
                            <button type="submit" class="btn-save" style="background-color: var(--accent);">
                                <i data-lucide="key-round" size="18"></i>
                                <span>Update Security Credentials</span>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- ================= TAB 7: COLLABORATORS ================= -->
            <?php if ($active_tab === 'collaborators'): ?>
                <div class="glass-panel">
                    <h3 class="glass-panel-title">
                        <i data-lucide="handshake" size="20"></i>
                        <span>Manage Collaborators / Brand Logos</span>
                    </h3>

                    <!-- Add Collaborator Box -->
                    <div style="margin-bottom: 30px; padding: 20px; border: 1px solid rgba(255, 255, 255, 0.05); background: rgba(255, 255, 255, 0.01); border-radius: 8px;">
                        <h4 style="margin-bottom: 15px; font-family: var(--font-heading); font-size: 1rem; text-transform: uppercase; color: var(--accent);">Add New Collaborator Logo</h4>
                        <form action="?tab=collaborators" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="collab_add">
                            <div class="form-grid">
                                <div class="form-group-admin">
                                    <label>Partner / Client Name</label>
                                    <input type="text" name="name" class="form-control-admin" placeholder="E.g. Viswajyothy College" required>
                                </div>
                                <div class="form-group-admin">
                                    <label>Display Sorting Order</label>
                                    <input type="number" name="order_index" class="form-control-admin" value="0">
                                </div>
                                <div class="form-group-admin form-full-width">
                                    <label>Brand Logo Image (Transparent PNG recommended)</label>
                                    <input type="file" name="image" class="form-control-admin" accept="image/*" required>
                                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 5px;">Preferably white/grey monochromatic transparent PNG logos, ~200x80px.</p>
                                </div>
                            </div>
                            <div style="margin-top: 15px; text-align: right;">
                                <button type="submit" class="btn-save">
                                    <i data-lucide="plus" size="18"></i>
                                    <span>Create Collaborator</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Current Collaborators list -->
                    <div class="crud-list">
                        <?php foreach ($collaborators_list as $collab): ?>
                            <div class="crud-item" id="collab-<?php echo $collab['id']; ?>">
                                <img src="../<?php echo htmlspecialchars($collab['image_path']); ?>" alt="Logo Preview" class="item-image-preview" style="object-fit: contain; background: rgba(255,255,255,0.05); padding: 10px;">
                                <div class="crud-info">
                                    <h4><span><?php echo htmlspecialchars($collab['name']); ?></span></h4>
                                    <span class="crud-meta-badge">Order: <?php echo $collab['order_index']; ?></span>
                                    
                                    <!-- Edit Form -->
                                    <div class="edit-form-wrapper" id="edit-collab-form-<?php echo $collab['id']; ?>">
                                        <form action="?tab=collaborators" method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="action" value="collab_edit">
                                            <input type="hidden" name="id" value="<?php echo $collab['id']; ?>">
                                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($collab['image_path']); ?>">
                                            
                                            <div class="form-grid">
                                                <div class="form-group-admin">
                                                    <label>Partner / Client Name</label>
                                                    <input type="text" name="name" class="form-control-admin" value="<?php echo htmlspecialchars($collab['name']); ?>" required>
                                                </div>
                                                <div class="form-group-admin">
                                                    <label>Display Sorting Order</label>
                                                    <input type="number" name="order_index" class="form-control-admin" value="<?php echo $collab['order_index']; ?>">
                                                </div>
                                                <div class="form-group-admin form-full-width">
                                                    <label>Replace Brand Logo Image</label>
                                                    <div class="image-upload-row">
                                                        <img src="../<?php echo htmlspecialchars($collab['image_path']); ?>" alt="Current preview" style="width: 50px; height: 50px; object-fit: contain; background: rgba(255,255,255,0.05); padding: 5px; border-radius: 4px;">
                                                        <input type="file" name="image" class="form-control-admin" accept="image/*">
                                                    </div>
                                                </div>
                                            </div>
                                            <div style="margin-top: 15px; text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
                                                <button type="button" class="btn-save" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color);" onclick="toggleEditForm('collab', <?php echo $collab['id']; ?>)">Cancel</button>
                                                <button type="submit" class="btn-save">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="crud-actions">
                                    <button class="btn-icon" onclick="toggleEditForm('collab', <?php echo $collab['id']; ?>)">
                                        <i data-lucide="edit-3" size="16"></i>
                                    </button>
                                    <form action="?tab=collaborators" method="POST" onsubmit="return confirm('Are you sure you want to delete this collaborator?');">
                                        <input type="hidden" name="action" value="collab_delete">
                                        <input type="hidden" name="id" value="<?php echo $collab['id']; ?>">
                                        <button type="submit" class="btn-icon btn-delete">
                                            <i data-lucide="trash-2" size="16"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>

    </div>

    <!-- Inquiry Details Modal -->
    <div id="inquiryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Creative Blueprint Details</h3>
                <button class="modal-close" onclick="closeInquiryModal()">
                    <i data-lucide="x" size="20"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-meta-grid">
                    <div class="modal-meta-item">
                        <label>Client Name</label>
                        <p id="modalClientName"></p>
                    </div>
                    <div class="modal-meta-item">
                        <label>Email Address</label>
                        <p><a id="modalClientEmail" href="" style="color: var(--accent); text-decoration: none;"></a></p>
                    </div>
                    <div class="modal-meta-item">
                        <label>Selected Package</label>
                        <p id="modalPackage"></p>
                    </div>
                    <div class="modal-meta-item">
                        <label>Price Blueprint</label>
                        <p id="modalPrice"></p>
                    </div>
                    <div class="modal-meta-item">
                        <label>Received Date</label>
                        <p id="modalDate"></p>
                    </div>
                    <div class="modal-meta-item">
                        <label>Status</label>
                        <p id="modalStatus"></p>
                    </div>
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 5px;">Goal Blueprint &amp; Message</label>
                    <div id="modalMessage" class="modal-message-box"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script triggers -->
    <script>
        // Init Lucide Icons
        lucide.createIcons();

        // Toggle Edit Form accordions
        function toggleEditForm(type, id) {
            const form = document.getElementById(`edit-${type}-form-${id}`);
            if (form) {
                if (form.style.display === 'block') {
                    form.style.display = 'none';
                } else {
                    form.style.display = 'block';
                }
            }
        }

        // Inquiry Modal functions
        function viewInquiryDetail(inq) {
            document.getElementById('modalClientName').textContent = inq.name;
            const emailLink = document.getElementById('modalClientEmail');
            emailLink.textContent = inq.email;
            emailLink.href = 'mailto:' + inq.email;
            
            document.getElementById('modalPackage').textContent = inq.package_name;
            document.getElementById('modalPrice').textContent = '₹' + parseFloat(inq.price).toLocaleString('en-IN');
            
            // Format Date
            const d = new Date(inq.created_at);
            const formattedDate = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' ' + 
                                  d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            document.getElementById('modalDate').textContent = formattedDate;
            
            document.getElementById('modalStatus').textContent = inq.status;
            document.getElementById('modalMessage').textContent = inq.message;
            
            // Show modal
            const modal = document.getElementById('inquiryModal');
            modal.style.display = 'flex';
            
            // Refresh Lucide icons inside modal if needed
            lucide.createIcons();
        }

        function closeInquiryModal() {
            document.getElementById('inquiryModal').style.display = 'none';
        }

        // Close modal when clicking outside content card
        window.onclick = function(event) {
            const modal = document.getElementById('inquiryModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
