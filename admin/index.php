<?php
// admin/index.php - Secure Premium Command Center
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/auth.php';
check_auth();
require_once __DIR__ . '/../includes/db.php';

$success_msg = '';
$error_msg = '';
$active_tab = $_GET['tab'] ?? 'global';
$subtab = $_GET['sub'] ?? '';

// Helper: Sequential gap-free reindexing for portfolio items within a category
if (!function_exists('reindexPortfolioCategory')) {
    function reindexPortfolioCategory($pdo, $category) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM portfolio_items WHERE category = ? ORDER BY order_index ASC, id ASC");
            $stmt->execute([$category]);
            $itemIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $upd = $pdo->prepare("UPDATE portfolio_items SET order_index = ? WHERE id = ?");
            foreach ($itemIds as $idx => $itemId) {
                $upd->execute([$idx + 1, $itemId]);
            }
        } catch (Exception $e) {}
    }
}

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. UPDATE SETTINGS
    if ($action === 'update_settings') {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            foreach ($_POST['settings'] as $key => $value) {
                $stmt->execute([$key, $value]);
            }
            $pdo->commit();

            // Auto-refresh site knowledge for Nova AI Assistant
            try {
                require_once __DIR__ . '/../api/knowledge_indexer.php';
                refreshSiteKnowledge($pdo);
            } catch (Exception $eIdx) {}

            $success_msg = 'Settings updated successfully & AI Knowledge Base refreshed.';
            $active_tab = $_POST['active_tab'] ?? ($_GET['tab'] ?? 'global');
            if (!empty($_POST['subtab'])) {
                $subtab = $_POST['subtab'];
            }
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
        $new_category_name = trim($_POST['new_category_name'] ?? '');

        // Handle dynamic new category creation on the fly
        if ($category === '__new__' || $new_category_name !== '') {
            if ($new_category_name !== '') {
                $category = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $new_category_name), '-'));
                if ($category === '') {
                    $category = 'cat-' . time();
                }
                try {
                    $maxOrder = (int)$pdo->query("SELECT COALESCE(MAX(order_index), 0) FROM portfolio_categories")->fetchColumn();
                    $stmtCat = $pdo->prepare("INSERT IGNORE INTO portfolio_categories (slug, name, order_index) VALUES (?, ?, ?)");
                    $stmtCat->execute([$category, $new_category_name, $maxOrder + 1]);
                } catch (Exception $e) {
                    // Category table might not exist yet or duplicate slug
                }
            } else {
                $category = 'video';
            }
        }

        $tag = trim($_POST['tag'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $order_index = (int)($_POST['order_index'] ?? 0);
        $is_launching_soon = isset($_POST['is_launching_soon']) ? 1 : 0;
        
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

        // When launching soon is disabled, clean any leftover prefixes and assign dedicated image if needed
        if ($is_launching_soon === 0) {
            $tag = trim(preg_replace('/^Launching Soon\s*[\x{2022}\-\:\•\|]?\s*/iu', '', $tag));
            if (empty($tag)) {
                $tag = ($category === 'web') ? 'Web Architecture' : (($category === 'video') ? 'Cinematic Video' : 'Visual Design');
            }
            $title = trim(preg_replace('/^Launching Soon\s*[\x{2022}\-\:\•\|]?\s*/iu', '', $title));

            if (!empty($link) && !preg_match('#^https?://#i', $link) && $link !== '#' && strpos($link, 'javascript:') !== 0) {
                $link = 'https://' . ltrim($link, '/');
            }

            if (empty($image_path) || strpos($image_path, 'portfolio_launching_soon') !== false || strpos($image_path, 'launching_soon_template') !== false) {
                $cleanSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $title));
                $candidates = [
                    'assets/images/portfolio_' . $cleanSlug . '.jpg',
                    'assets/images/portfolio_' . $cleanSlug . '.png',
                    'assets/images/portfolio_' . $cleanSlug . '_com.jpg',
                    'assets/images/portfolio_' . $cleanSlug . '_in.jpg'
                ];
                foreach ($candidates as $cand) {
                    if (file_exists(dirname(__DIR__) . '/' . $cand)) {
                        $image_path = $cand;
                        break;
                    }
                }
            }
        }

        if ($title !== '') {
            try {
                if ($action === 'portfolio_add') {
                    // Shift existing items in this category at or above $order_index up by 1
                    $shiftStmt = $pdo->prepare("UPDATE portfolio_items SET order_index = order_index + 1 WHERE category = ? AND order_index >= ?");
                    $shiftStmt->execute([$category, $order_index]);

                    $stmt = $pdo->prepare("INSERT INTO portfolio_items (category, tag, title, description, link, image_path, order_index, is_launching_soon) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$category, $tag, $title, $description, $link, $image_path, $order_index, $is_launching_soon]);
                    
                    // Reindex category to ensure strictly sequential ordering
                    reindexPortfolioCategory($pdo, $category);
                    $success_msg = 'Portfolio item added successfully.';
                } else {
                    // Fetch existing category and order_index
                    $curStmt = $pdo->prepare("SELECT category, order_index FROM portfolio_items WHERE id = ?");
                    $curStmt->execute([$id]);
                    $curRow = $curStmt->fetch(PDO::FETCH_ASSOC);

                    if ($curRow) {
                        $old_category = $curRow['category'];
                        $old_order = (int)$curRow['order_index'];

                        if ($old_category === $category) {
                            if ($order_index < $old_order) {
                                // Moving UP in list (e.g. 15 -> 10): items from 10 to 14 shift down to 11 to 15
                                $shiftStmt = $pdo->prepare("UPDATE portfolio_items SET order_index = order_index + 1 WHERE category = ? AND order_index >= ? AND order_index < ? AND id != ?");
                                $shiftStmt->execute([$category, $order_index, $old_order, $id]);
                            } elseif ($order_index > $old_order) {
                                // Moving DOWN in list (e.g. 5 -> 10): items from 6 to 10 shift up to 5 to 9
                                $shiftStmt = $pdo->prepare("UPDATE portfolio_items SET order_index = order_index - 1 WHERE category = ? AND order_index > ? AND order_index <= ? AND id != ?");
                                $shiftStmt->execute([$category, $old_order, $order_index, $id]);
                            }
                        } else {
                            // Category changed: close gap in old category, make room in new category
                            $shiftOld = $pdo->prepare("UPDATE portfolio_items SET order_index = order_index - 1 WHERE category = ? AND order_index > ? AND id != ?");
                            $shiftOld->execute([$old_category, $old_order, $id]);

                            $shiftNew = $pdo->prepare("UPDATE portfolio_items SET order_index = order_index + 1 WHERE category = ? AND order_index >= ? AND id != ?");
                            $shiftNew->execute([$category, $order_index, $id]);
                        }
                    }

                    $stmt = $pdo->prepare("UPDATE portfolio_items SET category = ?, tag = ?, title = ?, description = ?, link = ?, image_path = ?, order_index = ?, is_launching_soon = ? WHERE id = ?");
                    $stmt->execute([$category, $tag, $title, $description, $link, $image_path, $order_index, $is_launching_soon, $id]);

                    reindexPortfolioCategory($pdo, $category);
                    if (isset($old_category) && $old_category !== $category) {
                        reindexPortfolioCategory($pdo, $old_category);
                    }

                    $success_msg = 'Portfolio item updated successfully with automated position shifting.';
                }
                $active_tab = 'portfolio';
            } catch (Exception $e) {
                $error_msg = 'Failed to save portfolio item: ' . $e->getMessage();
            }
        }
    }
    // Action: Update Global Launching Soon Poster Template Image
    elseif ($action === 'launching_soon_template_save') {
        if (isset($_FILES['template_image']) && $_FILES['template_image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['template_image']['name'], PATHINFO_EXTENSION));
            $filename = 'launching_soon_template_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['template_image']['tmp_name'], $upload_dir . $filename)) {
                $image_path = 'assets/images/' . $filename;
                // Update default files as well
                @copy($upload_dir . $filename, $upload_dir . 'launching_soon_template.jpg');
                @copy($upload_dir . $filename, $upload_dir . 'portfolio_launching_soon.jpg');

                try {
                    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('launching_soon_template_image', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$image_path, $image_path]);
                    $success_msg = 'Launching Soon template poster updated successfully! All in-construction previews will now use this new artwork.';
                } catch (Exception $e) {
                    $error_msg = 'Database error: ' . $e->getMessage();
                }
            } else {
                $error_msg = 'Failed to upload image file. Please check directory permissions.';
            }
        } else {
            $error_msg = 'Please select a valid image file to upload.';
        }
        $active_tab = 'portfolio';
    }
    elseif ($action === 'portfolio_categories_reorder') {
        if (isset($_POST['category_orders']) && is_array($_POST['category_orders'])) {
            try {
                $stmt = $pdo->prepare("UPDATE portfolio_categories SET order_index = ? WHERE id = ?");
                foreach ($_POST['category_orders'] as $catId => $ord) {
                    $stmt->execute([(int)$ord, (int)$catId]);
                }
                $success_msg = 'Category display order updated successfully! The category with the lowest order number will appear as the default active tab.';
                $active_tab = 'portfolio';
            } catch (Exception $e) {
                $error_msg = 'Failed to update category order: ' . $e->getMessage();
            }
        }
    }
    elseif ($action === 'portfolio_category_move') {
        $cat_id = (int)($_POST['category_id'] ?? 0);
        $direction = $_POST['direction'] ?? 'up';
        if ($cat_id > 0) {
            try {
                $allCats = $pdo->query("SELECT id, order_index FROM portfolio_categories ORDER BY order_index ASC, id ASC")->fetchAll();
                $curIdx = -1;
                foreach ($allCats as $k => $c) {
                    if ((int)$c['id'] === $cat_id) {
                        $curIdx = $k;
                        break;
                    }
                }
                if ($curIdx !== -1) {
                    $swapIdx = ($direction === 'up') ? $curIdx - 1 : $curIdx + 1;
                    if (isset($allCats[$swapIdx])) {
                        $targetCat = $allCats[$swapIdx];
                        $currentCat = $allCats[$curIdx];

                        $currOrder = (int)$currentCat['order_index'];
                        $targetOrder = (int)$targetCat['order_index'];
                        if ($currOrder === $targetOrder) {
                            $currOrder = $curIdx;
                            $targetOrder = $swapIdx;
                        }

                        $stmt = $pdo->prepare("UPDATE portfolio_categories SET order_index = ? WHERE id = ?");
                        $stmt->execute([$targetOrder, (int)$currentCat['id']]);
                        $stmt->execute([$currOrder, (int)$targetCat['id']]);

                        $success_msg = 'Category order updated successfully.';
                    }
                }
                $active_tab = 'portfolio';
            } catch (Exception $e) {
                $error_msg = 'Failed to reorder category: ' . $e->getMessage();
            }
        }
    }
    elseif ($action === 'portfolio_category_edit') {
        $cat_id = (int)($_POST['category_id'] ?? 0);
        $cat_name = trim($_POST['category_name'] ?? '');
        $order_index = (int)($_POST['order_index'] ?? 0);
        if ($cat_id > 0 && $cat_name !== '') {
            try {
                $stmt = $pdo->prepare("UPDATE portfolio_categories SET name = ?, order_index = ? WHERE id = ?");
                $stmt->execute([$cat_name, $order_index, $cat_id]);
                $success_msg = "Category '{$cat_name}' updated successfully.";
                $active_tab = 'portfolio';
            } catch (Exception $e) {
                $error_msg = 'Failed to update category: ' . $e->getMessage();
            }
        }
    }
    elseif ($action === 'portfolio_category_add') {
        $cat_name = trim($_POST['category_name'] ?? '');
        $custom_order = isset($_POST['order_index']) && $_POST['order_index'] !== '' ? (int)$_POST['order_index'] : null;
        if ($cat_name !== '') {
            $cat_slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $cat_name), '-'));
            if ($cat_slug === '') {
                $cat_slug = 'cat-' . time();
            }
            try {
                if ($custom_order !== null) {
                    $orderVal = $custom_order;
                } else {
                    $maxOrder = (int)$pdo->query("SELECT COALESCE(MAX(order_index), 0) FROM portfolio_categories")->fetchColumn();
                    $orderVal = $maxOrder + 1;
                }
                $stmt = $pdo->prepare("INSERT INTO portfolio_categories (slug, name, order_index) VALUES (?, ?, ?)");
                $stmt->execute([$cat_slug, $cat_name, $orderVal]);
                $success_msg = "Category '{$cat_name}' added successfully.";
            } catch (Exception $e) {
                $error_msg = 'Failed to add category: ' . $e->getMessage();
            }
        } else {
            $error_msg = 'Category name cannot be empty.';
        }
        $active_tab = 'portfolio';
    }
    elseif ($action === 'portfolio_category_delete') {
        $cat_id = (int)($_POST['category_id'] ?? 0);
        if ($cat_id > 0) {
            try {
                $catStmt = $pdo->prepare("SELECT slug, name FROM portfolio_categories WHERE id = ?");
                $catStmt->execute([$cat_id]);
                $catData = $catStmt->fetch();
                if ($catData) {
                    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM portfolio_items WHERE category = ?");
                    $countStmt->execute([$catData['slug']]);
                    if ($countStmt->fetchColumn() > 0) {
                        $error_msg = 'Cannot delete category "' . htmlspecialchars($catData['name']) . '" because portfolio cards are currently assigned to it.';
                    } else {
                        $del = $pdo->prepare("DELETE FROM portfolio_categories WHERE id = ?");
                        $del->execute([$cat_id]);
                        $success_msg = 'Category "' . htmlspecialchars($catData['name']) . '" deleted successfully.';
                    }
                }
            } catch (Exception $e) {
                $error_msg = 'Failed to delete category: ' . $e->getMessage();
            }
        }
        $active_tab = 'portfolio';
    }
    elseif ($action === 'portfolio_delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $catStmt = $pdo->prepare("SELECT category FROM portfolio_items WHERE id = ?");
                $catStmt->execute([$id]);
                $delCategory = $catStmt->fetchColumn();

                $stmt = $pdo->prepare("DELETE FROM portfolio_items WHERE id = ?");
                $stmt->execute([$id]);

                if ($delCategory) {
                    reindexPortfolioCategory($pdo, $delCategory);
                }

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

    // Auto-refresh site knowledge and search index for Nova AI Assistant on ANY successful update
    if (!empty($success_msg) && $action !== 'update_credentials') {
        try {
            require_once __DIR__ . '/../api/knowledge_indexer.php';
            refreshSiteKnowledge($pdo);
        } catch (Exception $eIdx) {}
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

    // Dynamic Portfolio Categories initialization & synchronization
    try {
        $portfolio_categories = $pdo->query("SELECT * FROM portfolio_categories ORDER BY order_index ASC, id ASC")->fetchAll();
    } catch (PDOException $ex) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `portfolio_categories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `slug` VARCHAR(50) NOT NULL UNIQUE,
            `name` VARCHAR(100) NOT NULL,
            `order_index` INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("INSERT IGNORE INTO `portfolio_categories` (`slug`, `name`, `order_index`) VALUES 
            ('web', 'Web Development', 0),
            ('video', 'Video Production', 1),
            ('design', 'Graphic Design', 2)
        ");
        $portfolio_categories = $pdo->query("SELECT * FROM portfolio_categories ORDER BY order_index ASC, id ASC")->fetchAll();
    }

    // Ensure any categories already present in portfolio items are registered
    if (!empty($portfolio_categories)) {
        $existingSlugs = array_column($portfolio_categories, 'slug');
        foreach ($portfolio as $item) {
            $catSlug = trim($item['category'] ?? '');
            if ($catSlug !== '' && !in_array($catSlug, $existingSlugs)) {
                $catName = ucwords(str_replace(['_', '-'], ' ', $catSlug));
                try {
                    $stmtIns = $pdo->prepare("INSERT IGNORE INTO portfolio_categories (slug, name, order_index) VALUES (?, ?, 10)");
                    $stmtIns->execute([$catSlug, $catName]);
                    $existingSlugs[] = $catSlug;
                } catch (Exception $e) {}
            }
        }
        $portfolio_categories = $pdo->query("SELECT * FROM portfolio_categories ORDER BY order_index ASC, id ASC")->fetchAll();
    }
    $categoryMap = array_column($portfolio_categories, 'name', 'slug');
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
    
    <!-- Google Fonts: Luxury Marquee Typography Suite -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Cinzel:wght@700;800;900&family=Italiana&family=Montserrat:wght@800;900&family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Righteous&family=Space+Grotesk:wght@600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Central Stylesheet -->
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo @filemtime(dirname(__DIR__) . '/assets/css/main.css') ?: time(); ?>">
</head>
<body class="admin-body">

    <div class="radial-glow"></div>
    <div id="adminToastContainer" class="admin-toast-container"></div>

    <div class="admin-container preview-hidden">
        
        <!-- Sidebar Navigation Drawer -->
        <div class="sidebar" id="adminSidebar">
            <div>
                <div class="sidebar-brand">
                    <div class="brand-full">
                        <h2>Studio Control</h2>
                        <p><span class="dot"></span> Connected</p>
                    </div>
                    <div class="brand-mini" title="Perumalil Creative - Connected">
                        <span class="brand-mini-badge">PC</span>
                        <span class="dot"></span>
                    </div>
                </div>

                <div class="sidebar-menu">
                    <a href="?tab=global" class="menu-link <?php echo ($active_tab === 'global') ? 'active' : ''; ?>" data-tooltip="Global Settings" title="Global Settings">
                        <i data-lucide="settings" size="18"></i>
                        <span>Global Settings</span>
                    </a>
                    
                    <a href="?tab=typography" class="menu-link <?php echo ($active_tab === 'typography') ? 'active' : ''; ?>" data-tooltip="Typography &amp; Colors Studio" title="Typography &amp; Colors Studio">
                        <i data-lucide="palette" size="18"></i>
                        <span>Typography &amp; Colors</span>
                    </a>

                    <a href="?tab=ai" class="menu-link <?php echo ($active_tab === 'ai') ? 'active' : ''; ?>" data-tooltip="AI Assistant (Nova)" title="AI Assistant (Nova)">
                        <i data-lucide="bot" size="18"></i>
                        <span>AI Assistant</span>
                    </a>
                    
                    <a href="?tab=inquiries" class="menu-link inquiries-link <?php echo $active_tab === 'inquiries' ? 'active' : ''; ?>" data-tooltip="Client Inquiries" title="Client Inquiries">
                        <i data-lucide="mail" size="18"></i>
                        <span>Client Inquiries</span>
                        <?php if ($new_inquiries_count > 0): ?>
                            <span class="badge badge-new menu-inquiries-badge"><?php echo $new_inquiries_count; ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="?tab=services" class="menu-link <?php echo $active_tab === 'services' ? 'active' : ''; ?>" data-tooltip="Services &amp; Pricing" title="Services &amp; Pricing">
                        <i data-lucide="layout-grid" size="18"></i>
                        <span>Services &amp; Pricing</span>
                    </a>
                    <a href="?tab=portfolio" class="menu-link <?php echo $active_tab === 'portfolio' ? 'active' : ''; ?>" data-tooltip="Portfolio Cards" title="Portfolio Cards">
                        <i data-lucide="folder-open" size="18"></i>
                        <span>Portfolio Cards</span>
                    </a>
                    <a href="?tab=about" class="menu-link <?php echo $active_tab === 'about' ? 'active' : ''; ?>" data-tooltip="About &amp; Team" title="About &amp; Team">
                        <i data-lucide="users" size="18"></i>
                        <span>About &amp; Team</span>
                    </a>
                    <a href="?tab=faq" class="menu-link <?php echo $active_tab === 'faq' ? 'active' : ''; ?>" data-tooltip="FAQ Accordion" title="FAQ Accordion">
                        <i data-lucide="help-circle" size="18"></i>
                        <span>FAQ Accordion</span>
                    </a>
                    <a href="?tab=collaborators" class="menu-link <?php echo $active_tab === 'collaborators' ? 'active' : ''; ?>" data-tooltip="Collaborators" title="Collaborators">
                        <i data-lucide="handshake" size="18"></i>
                        <span>Collaborators</span>
                    </a>
                    <a href="?tab=security" class="menu-link <?php echo $active_tab === 'security' ? 'active' : ''; ?>" data-tooltip="Security Credentials" title="Security Credentials">
                        <i data-lucide="shield-check" size="18"></i>
                        <span>Security Credentials</span>
                    </a>
                </div>
            </div>

            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn" data-tooltip="Log Out" title="Log Out">
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
                <div class="header-action-group">
                    <button type="button" class="preview-toggle-top-btn" id="dbBackupTopBtn" onclick="runDatabaseBackup()" title="Save current database state to db/perumalil_creative.sql">
                        <i data-lucide="database" size="15"></i>
                        <span id="dbBackupBtnText">Backup DB</span>
                    </button>
                    <button type="button" class="preview-toggle-top-btn" id="previewToggleTopBtn" onclick="toggleAdminPreview()" title="Toggle live frontend preview panel">
                        <i data-lucide="split" size="15"></i>
                        <span id="previewToggleBtnText">Show Live Preview</span>
                    </button>
                    <a href="../index.php" target="_blank" class="view-site-link">
                        <span>Open Site</span>
                        <i data-lucide="external-link" size="15"></i>
                    </a>
                </div>
            </div>

            <!-- Alerts System -->
            <?php if (isset($_GET['backup']) && $_GET['backup'] === 'success'): ?>
                <div class="alert alert-success">
                    <i data-lucide="check-circle" size="20"></i>
                    <span>Database backup updated successfully to <strong>db/perumalil_creative.sql</strong>!</span>
                </div>
            <?php endif; ?>
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

                    <!-- Compact Sub-Tabs -->
                    <div class="admin-subnav" id="globalSubnav" data-group="global">
                        <button type="button" class="admin-subnav-btn <?php echo ($subtab === 'seo' || $subtab === '' || !in_array($subtab, ['hero', 'intro', 'contact'])) ? 'active' : ''; ?>" onclick="switchAdminSubtab('globalSubnav', 'global_sec_seo', this)">
                            <i data-lucide="search" size="15"></i>
                            <span>SEO &amp; Meta</span>
                        </button>
                        <button type="button" class="admin-subnav-btn <?php echo ($subtab === 'hero') ? 'active' : ''; ?>" onclick="switchAdminSubtab('globalSubnav', 'global_sec_hero', this)">
                            <i data-lucide="sparkles" size="15"></i>
                            <span>Hero Identity</span>
                        </button>
                        <button type="button" class="admin-subnav-btn <?php echo ($subtab === 'intro') ? 'active' : ''; ?>" onclick="switchAdminSubtab('globalSubnav', 'global_sec_intro', this)">
                            <i data-lucide="layout" size="15"></i>
                            <span>Intro &amp; Vision</span>
                        </button>
                        <button type="button" class="admin-subnav-btn <?php echo ($subtab === 'contact') ? 'active' : ''; ?>" onclick="switchAdminSubtab('globalSubnav', 'global_sec_contact', this)">
                            <i data-lucide="map-pin" size="15"></i>
                            <span>Contact &amp; Map</span>
                        </button>
                    </div>
                    
                    <form action="?tab=global" method="POST">
                        <input type="hidden" name="action" value="update_settings">
                        <input type="hidden" name="active_tab" value="global">
                        <input type="hidden" name="subtab" id="globalCurrentSubtab" value="<?php echo htmlspecialchars($subtab ?: 'seo'); ?>">

                        <!-- SECTION 1: SEO -->
                        <div id="global_sec_seo" class="subtab-section <?php echo ($subtab === 'seo' || $subtab === '' || !in_array($subtab, ['hero', 'intro', 'contact'])) ? 'active' : ''; ?>" data-subgroup="global" style="<?php echo ($subtab === 'seo' || $subtab === '' || !in_array($subtab, ['hero', 'intro', 'contact'])) ? 'display: block;' : 'display: none;'; ?>">
                            <div class="form-grid">
                                <div class="form-group-admin form-full-width">
                                    <label>SEO Browser Title</label>
                                    <input type="text" name="settings[seo_title]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['seo_title'] ?? ''); ?>">
                                </div>
                                <div class="form-group-admin form-full-width">
                                    <label>SEO Browser Description</label>
                                    <textarea name="settings[seo_description]" class="form-control-admin" rows="3"><?php echo htmlspecialchars($settings['seo_description'] ?? ''); ?></textarea>
                                </div>
                                <div class="form-group-admin form-full-width">
                                    <label>SEO Keywords (Comma Separated)</label>
                                    <input type="text" name="settings[seo_keywords]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['seo_keywords'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="admin-actions-right">
                                <button type="submit" class="btn-save"><i data-lucide="save" size="18"></i><span>Save Settings</span></button>
                            </div>
                        </div>

                        <!-- SECTION 2: HERO -->
                        <div id="global_sec_hero" class="subtab-section <?php echo ($subtab === 'hero') ? 'active' : ''; ?>" data-subgroup="global" style="<?php echo ($subtab === 'hero') ? 'display: block;' : 'display: none;'; ?>">
                            <div class="form-grid">
                                <div class="form-group-admin">
                                    <label>Hero Headline Part 1 (Prefix)</label>
                                    <input type="text" name="settings[hero_headline_part1]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['hero_headline_part1'] ?? 'Design Your'); ?>">
                                </div>
                                <div class="form-group-admin">
                                    <label>Hero Headline Part 2 (Glow Accent Text)</label>
                                    <input type="text" name="settings[hero_headline_part2]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['hero_headline_part2'] ?? 'Every Occasion.'); ?>">
                                </div>
                                <div class="form-group-admin form-full-width">
                                    <label>Hero Statement / Mission Text</label>
                                    <textarea name="settings[hero_mission_text]" class="form-control-admin" rows="3"><?php echo htmlspecialchars($settings['hero_mission_text'] ?? ''); ?></textarea>
                                </div>
                                <div class="form-group-admin form-full-width">
                                    <label>Hero Coordinate Text Indicator</label>
                                    <input type="text" name="settings[hero_coords]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['hero_coords'] ?? ''); ?>">
                                </div>

                                <!-- Dynamic Marquee Studio -->
                                <div class="admin-divider-header">
                                    <i data-lucide="palette" size="18"></i>
                                    <h4>Colossal Marquee Typography &amp; Dynamic Color Studio</h4>
                                </div>
                                <p class="admin-help-text">
                                    Control the colors and animation of the massive marquee text scrolling in the background of the Hero section. You can choose alternating colors per word, rainbow spectrum per letter, continuous flowing gradients, or custom brand palettes.
                                </p>

                                <!-- Live Interactive Preview -->
                                <div class="marquee-live-preview-box">
                                    <div class="marquee-preview-label">
                                        <span><i data-lucide="eye" size="14"></i> Real-time Live Color &amp; Font Preview</span>
                                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                            <span class="preview-mode-tag preview-font-tag" id="previewFontTag" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3);">Font: <?php echo htmlspecialchars($settings['hero_marquee_font'] ?? 'Syne'); ?></span>
                                            <span class="preview-mode-tag" id="previewModeTag">Word-by-Word Multi-Color</span>
                                        </div>
                                    </div>
                                    <div class="marquee-preview-canvas" id="marqueePreviewCanvas">
                                        <div class="preview-marquee-row row-top">
                                            <span class="preview-marquee-text" id="previewRow1Text">PERUMALIL CREATIVE &bull; PERUMALIL CREATIVE</span>
                                        </div>
                                        <div class="preview-marquee-row row-bottom">
                                            <span class="preview-marquee-text" id="previewRow2Text">CREATIVE DIRECTION &bull; CINEMATIC PRODUCTION</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Font Family Typography Selector & Visual Chips -->
                                <div class="form-group-admin form-full-width">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <label style="margin-bottom: 0;"><i data-lucide="type" size="16" style="vertical-align: middle; margin-right: 4px;"></i> Marquee Display Font Family</label>
                                        <span style="font-size: 0.74rem; color: #94a3b8;">10 Curated Luxury &amp; Display Fonts</span>
                                    </div>
                                    <select name="settings[hero_marquee_font]" id="heroMarqueeFont" class="form-control-admin" onchange="onMarqueeFontChange()">
                                        <option value="Syne" <?php echo ($settings['hero_marquee_font'] ?? 'Syne') === 'Syne' ? 'selected' : ''; ?>>⚡ Syne (Futuristic Avant-Garde Ultra Bold) [Default]</option>
                                        <option value="Space Grotesk" <?php echo ($settings['hero_marquee_font'] ?? '') === 'Space Grotesk' ? 'selected' : ''; ?>>🚀 Space Grotesk (Tech Neo-Grotesque Heavy)</option>
                                        <option value="Cinzel" <?php echo ($settings['hero_marquee_font'] ?? '') === 'Cinzel' ? 'selected' : ''; ?>>🏛️ Cinzel (Cinematic Imperial Luxury Serif)</option>
                                        <option value="Bebas Neue" <?php echo ($settings['hero_marquee_font'] ?? '') === 'Bebas Neue' ? 'selected' : ''; ?>>🎬 Bebas Neue (Condensed High-Impact Headline)</option>
                                        <option value="Outfit" <?php echo ($settings['hero_marquee_font'] ?? '') === 'Outfit' ? 'selected' : ''; ?>>✨ Outfit (Ultra-Clean Modern Geometric Sans)</option>
                                        <option value="Playfair Display" <?php echo ($settings['hero_marquee_font'] ?? '') === 'Playfair Display' ? 'selected' : ''; ?>>💎 Playfair Display (Editorial High-Fashion Luxury Serif)</option>
                                        <option value="Righteous" <?php echo ($settings['hero_marquee_font'] ?? '') === 'Righteous' ? 'selected' : ''; ?>>⚡ Righteous (Retro-Futuristic Cyber Accent)</option>
                                        <option value="Montserrat" <?php echo ($settings['hero_marquee_font'] ?? '') === 'Montserrat' ? 'selected' : ''; ?>>🔥 Montserrat (Heavyweight Minimalist Architecture)</option>
                                        <option value="Italiana" <?php echo ($settings['hero_marquee_font'] ?? '') === 'Italiana' ? 'selected' : ''; ?>>🍷 Italiana (Italian Haute Couture Elegance)</option>
                                        <option value="Plus Jakarta Sans" <?php echo ($settings['hero_marquee_font'] ?? '') === 'Plus Jakarta Sans' ? 'selected' : ''; ?>>🌐 Plus Jakarta Sans (Contemporary Precision Sans)</option>
                                    </select>
                                    
                                    <!-- Interactive Font Chips Preview -->
                                    <div class="font-chips-container">
                                        <?php
                                        $fontsList = [
                                            'Syne' => ['name' => 'Syne', 'css' => "'Syne', sans-serif", 'badge' => 'Avant-Garde'],
                                            'Space Grotesk' => ['name' => 'Space Grotesk', 'css' => "'Space Grotesk', sans-serif", 'badge' => 'Neo-Tech'],
                                            'Cinzel' => ['name' => 'Cinzel', 'css' => "'Cinzel', serif", 'badge' => 'Cinema Serif'],
                                            'Bebas Neue' => ['name' => 'Bebas Neue', 'css' => "'Bebas Neue', sans-serif", 'badge' => 'Poster'],
                                            'Outfit' => ['name' => 'Outfit', 'css' => "'Outfit', sans-serif", 'badge' => 'Clean Sans'],
                                            'Playfair Display' => ['name' => 'Playfair Display', 'css' => "'Playfair Display', serif", 'badge' => 'Editorial'],
                                            'Righteous' => ['name' => 'Righteous', 'css' => "'Righteous', cursive", 'badge' => 'Cyber'],
                                            'Montserrat' => ['name' => 'Montserrat', 'css' => "'Montserrat', sans-serif", 'badge' => 'Bold Block'],
                                            'Italiana' => ['name' => 'Italiana', 'css' => "'Italiana', serif", 'badge' => 'Couture'],
                                            'Plus Jakarta Sans' => ['name' => 'Plus Jakarta Sans', 'css' => "'Plus Jakarta Sans', sans-serif", 'badge' => 'Precision']
                                        ];
                                        $currentFont = $settings['hero_marquee_font'] ?? 'Syne';
                                        foreach ($fontsList as $fKey => $fData):
                                            $isActive = ($currentFont === $fKey);
                                        ?>
                                            <button type="button" 
                                                    class="font-chip-btn <?php echo $isActive ? 'active' : ''; ?>" 
                                                    data-font-name="<?php echo htmlspecialchars($fKey); ?>"
                                                    onclick="selectMarqueeFont('<?php echo htmlspecialchars($fKey); ?>')"
                                                    style="font-family: <?php echo $fData['css']; ?>;">
                                                <span class="font-chip-title"><?php echo htmlspecialchars($fData['name']); ?></span>
                                                <span class="font-chip-sub"><?php echo htmlspecialchars($fData['badge']); ?></span>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Color Mode Selector -->
                                <div class="form-group-admin form-full-width">
                                    <label>Marquee Color Engine Mode</label>
                                    <select name="settings[hero_marquee_mode]" id="heroMarqueeMode" class="form-control-admin" onchange="updateMarqueeLivePreview()">
                                        <option value="words" <?php echo ($settings['hero_marquee_mode'] ?? 'words') === 'words' ? 'selected' : ''; ?>>🎨 Word-by-Word Multi-Color (Each word cycles through palette)</option>
                                        <option value="letters" <?php echo ($settings['hero_marquee_mode'] ?? '') === 'letters' ? 'selected' : ''; ?>>✨ Letter-by-Letter Rainbow Spectrum (Each character gets a color)</option>
                                        <option value="gradient" <?php echo ($settings['hero_marquee_mode'] ?? '') === 'gradient' ? 'selected' : ''; ?>>🌈 Continuous Flowing Multi-Color Gradient</option>
                                        <option value="solid" <?php echo ($settings['hero_marquee_mode'] ?? '') === 'solid' ? 'selected' : ''; ?>>💎 Dual-Tone Solid &amp; Accent</option>
                                    </select>
                                </div>

                                <!-- Quick Presets -->
                                <div class="form-group-admin form-full-width">
                                    <label>Quick Curated Luxury Presets</label>
                                    <div class="preset-chips-container">
                                        <button type="button" class="preset-chip-btn" onclick="applyMarqueePreset(['#0066ff', '#38bdf8', '#ffffff', '#60a5fa'], 'words')">
                                            <span class="preset-dots-preview">
                                                <span style="background: #0066ff"></span>
                                                <span style="background: #38bdf8"></span>
                                                <span style="background: #ffffff"></span>
                                                <span style="background: #60a5fa"></span>
                                            </span>
                                            <span>Electric Blue &amp; Cyan</span>
                                        </button>
                                        <button type="button" class="preset-chip-btn" onclick="applyMarqueePreset(['#00ffaa', '#38bdf8', '#ff007f', '#a855f7'], 'words')">
                                            <span class="preset-dots-preview">
                                                <span style="background: #00ffaa"></span>
                                                <span style="background: #38bdf8"></span>
                                                <span style="background: #ff007f"></span>
                                                <span style="background: #a855f7"></span>
                                            </span>
                                            <span>Cyber Neon Spectrum</span>
                                        </button>
                                        <button type="button" class="preset-chip-btn" onclick="applyMarqueePreset(['#f59e0b', '#ef4444', '#ffffff', '#fbbf24'], 'words')">
                                            <span class="preset-dots-preview">
                                                <span style="background: #f59e0b"></span>
                                                <span style="background: #ef4444"></span>
                                                <span style="background: #ffffff"></span>
                                                <span style="background: #fbbf24"></span>
                                            </span>
                                            <span>Sunset Gold &amp; Amber</span>
                                        </button>
                                        <button type="button" class="preset-chip-btn" onclick="applyMarqueePreset(['#ffffff', '#cbd5e1', '#94a3b8', '#64748b'], 'words')">
                                            <span class="preset-dots-preview">
                                                <span style="background: #ffffff"></span>
                                                <span style="background: #cbd5e1"></span>
                                                <span style="background: #94a3b8"></span>
                                                <span style="background: #64748b"></span>
                                            </span>
                                            <span>Minimalist Monochrome</span>
                                        </button>
                                        <button type="button" class="preset-chip-btn" onclick="applyMarqueePreset(['#10b981', '#84cc16', '#ffffff', '#06b6d4'], 'words')">
                                            <span class="preset-dots-preview">
                                                <span style="background: #10b981"></span>
                                                <span style="background: #84cc16"></span>
                                                <span style="background: #ffffff"></span>
                                                <span style="background: #06b6d4"></span>
                                            </span>
                                            <span>Emerald &amp; Mint Luxury</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Interactive Draggable Color Studio -->
                                <div class="form-group-admin form-full-width">
                                    <div class="color-studio-header">
                                        <div>
                                            <label class="color-studio-label">
                                                <i data-lucide="palette" size="16"></i>
                                                Interactive Palette Color Studio (Drag &amp; Pick)
                                            </label>
                                            <p class="color-studio-sub">
                                                Click any of the 4 palette slots below, then drag across the 2D spectrum or slide the rainbow hue bar to visually choose colors in real time.
                                            </p>
                                        </div>
                                        <div class="active-slot-pill">
                                            <span>Active Slot: </span>
                                            <strong id="activeSlotIndicator">Color 1 (Primary)</strong>
                                        </div>
                                    </div>

                                    <!-- 4 Selectable Color Slots -->
                                    <div class="color-slots-grid">
                                        <div class="color-slot-card active" id="slotCard1" onclick="selectColorSlot(1)">
                                            <div class="color-slot-swatch" id="slotSwatch1" style="background: <?php echo htmlspecialchars($settings['hero_marquee_color1'] ?? '#0066ff'); ?>;"></div>
                                            <div class="color-slot-meta">
                                                <span class="color-slot-tag">Palette 1</span>
                                                <span class="color-slot-title">Primary / Word 1</span>
                                                <span class="color-slot-hex" id="slotHexLabel1"><?php echo htmlspecialchars($settings['hero_marquee_color1'] ?? '#0066ff'); ?></span>
                                            </div>
                                            <input type="hidden" name="settings[hero_marquee_color1]" id="heroColorText1" value="<?php echo htmlspecialchars($settings['hero_marquee_color1'] ?? '#0066ff'); ?>">
                                        </div>

                                        <div class="color-slot-card" id="slotCard2" onclick="selectColorSlot(2)">
                                            <div class="color-slot-swatch" id="slotSwatch2" style="background: <?php echo htmlspecialchars($settings['hero_marquee_color2'] ?? '#38bdf8'); ?>;"></div>
                                            <div class="color-slot-meta">
                                                <span class="color-slot-tag">Palette 2</span>
                                                <span class="color-slot-title">Secondary / Word 2</span>
                                                <span class="color-slot-hex" id="slotHexLabel2"><?php echo htmlspecialchars($settings['hero_marquee_color2'] ?? '#38bdf8'); ?></span>
                                            </div>
                                            <input type="hidden" name="settings[hero_marquee_color2]" id="heroColorText2" value="<?php echo htmlspecialchars($settings['hero_marquee_color2'] ?? '#38bdf8'); ?>">
                                        </div>

                                        <div class="color-slot-card" id="slotCard3" onclick="selectColorSlot(3)">
                                            <div class="color-slot-swatch" id="slotSwatch3" style="background: <?php echo htmlspecialchars($settings['hero_marquee_color3'] ?? '#ffffff'); ?>;"></div>
                                            <div class="color-slot-meta">
                                                <span class="color-slot-tag">Palette 3</span>
                                                <span class="color-slot-title">Tertiary / Word 3</span>
                                                <span class="color-slot-hex" id="slotHexLabel3"><?php echo htmlspecialchars($settings['hero_marquee_color3'] ?? '#ffffff'); ?></span>
                                            </div>
                                            <input type="hidden" name="settings[hero_marquee_color3]" id="heroColorText3" value="<?php echo htmlspecialchars($settings['hero_marquee_color3'] ?? '#ffffff'); ?>">
                                        </div>

                                        <div class="color-slot-card" id="slotCard4" onclick="selectColorSlot(4)">
                                            <div class="color-slot-swatch" id="slotSwatch4" style="background: <?php echo htmlspecialchars($settings['hero_marquee_color4'] ?? '#a855f7'); ?>;"></div>
                                            <div class="color-slot-meta">
                                                <span class="color-slot-tag">Palette 4</span>
                                                <span class="color-slot-title">Accent / Word 4</span>
                                                <span class="color-slot-hex" id="slotHexLabel4"><?php echo htmlspecialchars($settings['hero_marquee_color4'] ?? '#a855f7'); ?></span>
                                            </div>
                                            <input type="hidden" name="settings[hero_marquee_color4]" id="heroColorText4" value="<?php echo htmlspecialchars($settings['hero_marquee_color4'] ?? '#a855f7'); ?>">
                                        </div>
                                    </div>

                                    <!-- Main Draggable Studio Console -->
                                    <div class="draggable-color-studio-box">
                                        <!-- 2D Saturation / Brightness Drag Area -->
                                        <div class="studio-spectrum-area" id="studioSpectrumArea">
                                            <div class="spectrum-gradient-white"></div>
                                            <div class="spectrum-gradient-black"></div>
                                            <div class="spectrum-handle" id="spectrumHandle"></div>
                                        </div>

                                        <!-- Rainbow Hue Drag Slider -->
                                        <div class="studio-hue-wrap">
                                            <div class="studio-hue-track" id="studioHueTrack">
                                                <div class="hue-slider-thumb" id="hueSliderThumb"></div>
                                            </div>
                                        </div>

                                        <!-- Color Controls & Details Row -->
                                        <div class="studio-controls-bar">
                                            <div class="studio-active-swatch-box">
                                                <div class="studio-active-swatch" id="studioActiveSwatch"></div>
                                                <div class="studio-swatch-info">
                                                    <span class="studio-swatch-title">Selected Tone</span>
                                                    <span class="studio-swatch-rgb" id="studioActiveRgb">rgb(0, 102, 255)</span>
                                                </div>
                                            </div>

                                            <div class="studio-hex-input-wrap">
                                                <span class="hex-hash-prefix">#</span>
                                                <input type="text" id="studioHexInput" class="studio-hex-field" value="0066ff" maxlength="6" oninput="onStudioHexTyped(this.value)">
                                                <button type="button" class="btn-copy-hex" onclick="copyStudioHex()" title="Copy Hex">
                                                    <i data-lucide="copy" size="14"></i>
                                                </button>
                                                <button type="button" class="btn-eyedropper" id="btnEyedropper" onclick="pickScreenColor()" title="Pick color from screen">
                                                    <i data-lucide="pipette" size="14"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Curated Quick Palette Swatches -->
                                        <div class="studio-palette-chips-wrap">
                                            <span class="palette-chips-label">Quick Brand Swatches:</span>
                                            <div class="studio-chips-grid">
                                                <button type="button" class="studio-chip" style="background: #0066ff" onclick="applyStudioColor('#0066ff')" title="Electric Blue"></button>
                                                <button type="button" class="studio-chip" style="background: #38bdf8" onclick="applyStudioColor('#38bdf8')" title="Cyan Sky"></button>
                                                <button type="button" class="studio-chip" style="background: #0284c7" onclick="applyStudioColor('#0284c7')" title="Ocean Blue"></button>
                                                <button type="button" class="studio-chip" style="background: #00f2fe" onclick="applyStudioColor('#00f2fe')" title="Aquamarine"></button>
                                                <button type="button" class="studio-chip" style="background: #10b981" onclick="applyStudioColor('#10b981')" title="Emerald"></button>
                                                <button type="button" class="studio-chip" style="background: #00ffaa" onclick="applyStudioColor('#00ffaa')" title="Neon Mint"></button>
                                                <button type="button" class="studio-chip" style="background: #84cc16" onclick="applyStudioColor('#84cc16')" title="Lime"></button>
                                                <button type="button" class="studio-chip" style="background: #f59e0b" onclick="applyStudioColor('#f59e0b')" title="Gold Amber"></button>
                                                <button type="button" class="studio-chip" style="background: #f97316" onclick="applyStudioColor('#f97316')" title="Tangerine"></button>
                                                <button type="button" class="studio-chip" style="background: #ef4444" onclick="applyStudioColor('#ef4444')" title="Hot Crimson"></button>
                                                <button type="button" class="studio-chip" style="background: #ec4899" onclick="applyStudioColor('#ec4899')" title="Neon Pink"></button>
                                                <button type="button" class="studio-chip" style="background: #a855f7" onclick="applyStudioColor('#a855f7')" title="Cyber Purple"></button>
                                                <button type="button" class="studio-chip" style="background: #8b5cf6" onclick="applyStudioColor('#8b5cf6')" title="Royal Violet"></button>
                                                <button type="button" class="studio-chip" style="background: #ffffff" onclick="applyStudioColor('#ffffff')" title="Pure White"></button>
                                                <button type="button" class="studio-chip" style="background: #cbd5e1" onclick="applyStudioColor('#cbd5e1')" title="Silver Platinum"></button>
                                                <button type="button" class="studio-chip" style="background: #64748b" onclick="applyStudioColor('#64748b')" title="Slate Gray"></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Text Customization & Styles -->
                                <div class="form-group-admin">
                                    <label>Row 1 Marquee Text (Top Row)</label>
                                    <input type="text" name="settings[hero_marquee_row1_text]" id="heroRow1Text" class="form-control-admin" value="<?php echo htmlspecialchars($settings['hero_marquee_row1_text'] ?? 'PERUMALIL CREATIVE • PERUMALIL CREATIVE • PERUMALIL CREATIVE • '); ?>" oninput="updateMarqueeLivePreview()">
                                </div>

                                <div class="form-group-admin">
                                    <label>Row 1 Typography Style</label>
                                    <select name="settings[hero_marquee_row1_style]" id="heroRow1Style" class="form-control-admin" onchange="updateMarqueeLivePreview()">
                                        <option value="filled" <?php echo ($settings['hero_marquee_row1_style'] ?? 'filled') === 'filled' ? 'selected' : ''; ?>>Solid Filled Text</option>
                                        <option value="outline" <?php echo ($settings['hero_marquee_row1_style'] ?? '') === 'outline' ? 'selected' : ''; ?>>Outlined Wireframe Text</option>
                                    </select>
                                </div>

                                <div class="form-group-admin">
                                    <label>Row 2 Marquee Text (Bottom Row)</label>
                                    <input type="text" name="settings[hero_marquee_row2_text]" id="heroRow2Text" class="form-control-admin" value="<?php echo htmlspecialchars($settings['hero_marquee_row2_text'] ?? 'CREATIVE DIRECTION • CINEMATIC PRODUCTION • BRAND ARCHITECTURE • DIGITAL EXPERIENCES • '); ?>" oninput="updateMarqueeLivePreview()">
                                </div>

                                <div class="form-group-admin">
                                    <label>Row 2 Typography Style</label>
                                    <select name="settings[hero_marquee_row2_style]" id="heroRow2Style" class="form-control-admin" onchange="updateMarqueeLivePreview()">
                                        <option value="outline" <?php echo ($settings['hero_marquee_row2_style'] ?? 'outline') === 'outline' ? 'selected' : ''; ?>>Outlined Wireframe Text</option>
                                        <option value="filled" <?php echo ($settings['hero_marquee_row2_style'] ?? '') === 'filled' ? 'selected' : ''; ?>>Solid Filled Text</option>
                                    </select>
                                </div>

                                <!-- Opacity & Speed Sliders -->
                                <div class="form-group-admin">
                                    <?php $currentOpacity = !empty($settings['hero_marquee_opacity']) && $settings['hero_marquee_opacity'] !== '0.35' ? $settings['hero_marquee_opacity'] : '0.85'; ?>
                                    <label>Marquee Visual Opacity: <span id="opacityDisplay"><?php echo round(((float)$currentOpacity) * 100); ?>%</span></label>
                                    <input type="range" name="settings[hero_marquee_opacity]" id="heroMarqueeOpacity" min="0.1" max="1" step="0.05" class="form-control-admin" value="<?php echo htmlspecialchars($currentOpacity); ?>" oninput="document.getElementById('opacityDisplay').textContent = Math.round(this.value * 100) + '%'; updateMarqueeLivePreview();" style="padding: 4px 0; cursor: pointer;">
                                </div>

                                <div class="form-group-admin">
                                    <label>Marquee Scroll Speed: <span id="speedDisplay"><?php echo (int)($settings['hero_marquee_speed'] ?? 55); ?>s</span></label>
                                    <input type="range" name="settings[hero_marquee_speed]" id="heroMarqueeSpeed" min="20" max="90" step="5" class="form-control-admin" value="<?php echo htmlspecialchars($settings['hero_marquee_speed'] ?? '55'); ?>" oninput="document.getElementById('speedDisplay').textContent = this.value + 's';" style="padding: 4px 0; cursor: pointer;">
                                </div>
                            </div>
                            <div class="admin-actions-right">
                                <button type="submit" class="btn-save"><i data-lucide="save" size="18"></i><span>Save Settings</span></button>
                            </div>
                        </div>

                        <!-- SECTION 3: INTRO & VISION -->
                        <div id="global_sec_intro" class="subtab-section <?php echo ($subtab === 'intro') ? 'active' : ''; ?>" data-subgroup="global" style="<?php echo ($subtab === 'intro') ? 'display: block;' : 'display: none;'; ?>">
                            <div class="form-grid">
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
                                    <textarea name="settings[intro_col_left_desc]" class="form-control-admin" rows="2"><?php echo htmlspecialchars($settings['intro_col_left_desc'] ?? ''); ?></textarea>
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
                                    <textarea name="settings[intro_col_right_desc]" class="form-control-admin" rows="2"><?php echo htmlspecialchars($settings['intro_col_right_desc'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group-admin">
                                    <label>Vision Tagline</label>
                                    <input type="text" name="settings[intro_vision_tagline]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['intro_vision_tagline'] ?? ''); ?>">
                                </div>
                                <div class="form-group-admin">
                                    <label>Vision Title (&lt;br&gt; tags allowed)</label>
                                    <input type="text" name="settings[intro_vision_title]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['intro_vision_title'] ?? ''); ?>">
                                </div>
                                <div class="form-group-admin form-full-width">
                                    <label>Vision Description Paragraph</label>
                                    <textarea name="settings[intro_vision_text]" class="form-control-admin" rows="2"><?php echo htmlspecialchars($settings['intro_vision_text'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="admin-actions-right">
                                <button type="submit" class="btn-save"><i data-lucide="save" size="18"></i><span>Save Settings</span></button>
                            </div>
                        </div>

                        <!-- SECTION 4: CONTACT & MAP -->
                        <div id="global_sec_contact" class="subtab-section <?php echo ($subtab === 'contact') ? 'active' : ''; ?>" data-subgroup="global" style="<?php echo ($subtab === 'contact') ? 'display: block;' : 'display: none;'; ?>">
                            <div class="form-grid">
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
                                    <label>Office Latitude (E.g. 9.9487)</label>
                                    <input type="text" name="settings[map_latitude]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['map_latitude'] ?? ''); ?>">
                                </div>
                                <div class="form-group-admin">
                                    <label>Office Longitude (E.g. 76.6294)</label>
                                    <input type="text" name="settings[map_longitude]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['map_longitude'] ?? ''); ?>">
                                </div>

                                <div class="form-group-admin form-full-width">
                                    <label>Office Address (&lt;br&gt; tags allowed)</label>
                                    <textarea name="settings[contact_address]" class="form-control-admin" rows="2"><?php echo htmlspecialchars($settings['contact_address'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group-admin form-full-width">
                                    <label>Footer Copyright Block</label>
                                    <input type="text" name="settings[footer_copyright]" class="form-control-admin" value="<?php echo htmlspecialchars($settings['footer_copyright'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="admin-actions-right">
                                <button type="submit" class="btn-save"><i data-lucide="save" size="18"></i><span>Save Settings</span></button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- ================= TAB: TYPOGRAPHY & COLORS STUDIO ================= -->
            <?php if ($active_tab === 'typography'): ?>
                <div class="glass-panel">
                    <div class="admin-panel-header-bar">
                        <div>
                            <h3 class="glass-panel-title title-mb-5">
                                <span class="panel-title-text">
                                    <i data-lucide="palette" size="20" class="icon-accent"></i>
                                    <span>Universal Website Typography &amp; Dynamic Color Architecture Studio</span>
                                </span>
                            </h3>
                            <p class="admin-header-subtitle">
                                Master control center for all fonts, headings, text styles, and the full site background &amp; card color architecture across Perumalil Creative.
                            </p>
                        </div>
                    </div>

                    <form action="?tab=typography" method="POST">
                        <input type="hidden" name="action" value="update_settings">
                        <input type="hidden" name="active_tab" value="typography">

                            <!-- Real-time Live Interactive Preview Box -->
                            <div class="site-typography-live-preview-box" id="siteTypoPreviewBox">
                                <div class="marquee-preview-label">
                                    <span><i data-lucide="eye" size="14"></i> Real-time Live Website Typography &amp; Color Preview</span>
                                    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                        <span class="preview-mode-tag preview-font-tag" id="previewSiteHeadingFontTag">Heading: <?php echo htmlspecialchars($settings['site_font_heading'] ?? 'Syne'); ?></span>
                                        <span class="preview-mode-tag preview-font-tag" id="previewSiteEditorialFontTag" style="background: rgba(168, 85, 247, 0.15); color: #c084fc; border-color: rgba(168, 85, 247, 0.3);">Subtext: <?php echo htmlspecialchars($settings['site_font_editorial'] ?? 'Outfit'); ?></span>
                                        <span class="preview-mode-tag preview-font-tag" id="previewSiteBodyFontTag" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border-color: rgba(16, 185, 129, 0.3);">Body: <?php echo htmlspecialchars($settings['site_font_body'] ?? 'Plus Jakarta Sans'); ?></span>
                                        <span class="preview-mode-tag" id="previewSitePresetTag" style="background: rgba(0, 102, 255, 0.2); color: #93c5fd; border-color: rgba(0, 102, 255, 0.4);">Theme: Live Studio</span>
                                    </div>
                                </div>

                                <!-- Preview Canvas -->
                                <div class="site-preview-canvas" id="sitePreviewCanvas">
                                    <div class="preview-tagline-row">
                                        <span class="preview-tagline-chip" id="previewTaglineText">✦ LUXURY DIGITAL ARCHITECTURE &bull; VAZHAKULAM, KERALA</span>
                                    </div>

                                    <div class="preview-headline-row">
                                        <h1 class="preview-main-headline" id="previewMainHeadline">
                                            DESIGN YOUR <br>
                                            <span class="preview-glow-text" id="previewGlowText">EVERY OCCASION.</span>
                                        </h1>
                                    </div>

                                    <div class="preview-subheadline-row">
                                        <h3 class="preview-subheadline-text" id="previewSubheadlineText">Pioneering Visual Identities &amp; High-Performance Experiences</h3>
                                    </div>

                                    <div class="preview-body-row">
                                        <p class="preview-body-text" id="previewBodyText">
                                            We design premium creative identities, cinematic brand reels, and luxury digital architectures built on strict mathematical grid parameters.
                                        </p>
                                    </div>

                                    <div class="preview-footer-row">
                                        <div class="preview-buttons-wrap">
                                            <button type="button" class="preview-cta-btn active-accent" id="previewAccentBtn">
                                                <span>Explore Services</span>
                                                <i data-lucide="arrow-right" size="14"></i>
                                            </button>
                                            <button type="button" class="preview-cta-btn outline" id="previewOutlineBtn">
                                                <span>Initiate Blueprint</span>
                                            </button>
                                        </div>
                                        <div class="preview-coords-meta" id="previewCoordsMeta">
                                            <span>9.9487° N, 76.6294° E &bull; SURGICAL ACCURACY</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Curated Luxury Presets -->
                            <div class="form-group-admin form-full-width">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <label style="margin-bottom: 0;"><i data-lucide="sparkles" size="16" style="vertical-align: middle; margin-right: 4px;"></i> Quick Curated Luxury Presets</label>
                                    <span style="font-size: 0.74rem; color: #94a3b8;">1-Click Harmonious Typography &amp; Color Palettes</span>
                                </div>
                                <div class="preset-chips-container">
                                    <button type="button" class="preset-chip-btn" onclick="applySitePreset('electric_cobalt')">
                                        <span class="preset-dots-preview">
                                            <span style="background: #ffffff"></span>
                                            <span style="background: #38bdf8"></span>
                                            <span style="background: #a0a0ab"></span>
                                            <span style="background: #0066ff"></span>
                                        </span>
                                        <span>⚡ Electric Cobalt &amp; Cyan</span>
                                    </button>
                                    <button type="button" class="preset-chip-btn" onclick="applySitePreset('cyber_neon')">
                                        <span class="preset-dots-preview">
                                            <span style="background: #ffffff"></span>
                                            <span style="background: #00ffaa"></span>
                                            <span style="background: #cbd5e1"></span>
                                            <span style="background: #a855f7"></span>
                                        </span>
                                        <span>🚀 Cyber Neon Spectrum</span>
                                    </button>
                                    <button type="button" class="preset-chip-btn" onclick="applySitePreset('sunset_gold')">
                                        <span class="preset-dots-preview">
                                            <span style="background: #fef3c7"></span>
                                            <span style="background: #fbbf24"></span>
                                            <span style="background: #d1d5db"></span>
                                            <span style="background: #f59e0b"></span>
                                        </span>
                                        <span>🏛️ Sunset Gold &amp; Amber</span>
                                    </button>
                                    <button type="button" class="preset-chip-btn" onclick="applySitePreset('emerald_mint')">
                                        <span class="preset-dots-preview">
                                            <span style="background: #f0fdf4"></span>
                                            <span style="background: #34d399"></span>
                                            <span style="background: #94a3b8"></span>
                                            <span style="background: #10b981"></span>
                                        </span>
                                        <span>💎 Emerald &amp; Mint Luxury</span>
                                    </button>
                                    <button type="button" class="preset-chip-btn" onclick="applySitePreset('haute_couture')">
                                        <span class="preset-dots-preview">
                                            <span style="background: #ffffff"></span>
                                            <span style="background: #fb7185"></span>
                                            <span style="background: #cbd5e1"></span>
                                            <span style="background: #e11d48"></span>
                                        </span>
                                        <span>🍷 Haute Couture Elegance</span>
                                    </button>
                                    <button type="button" class="preset-chip-btn" onclick="applySitePreset('monochrome')">
                                        <span class="preset-dots-preview">
                                            <span style="background: #ffffff"></span>
                                            <span style="background: #cbd5e1"></span>
                                            <span style="background: #94a3b8"></span>
                                            <span style="background: #64748b"></span>
                                        </span>
                                        <span>🎬 Minimalist Monochrome</span>
                                    </button>
                                    <button type="button" class="preset-chip-btn" onclick="applySitePreset('royal_violet')">
                                        <span class="preset-dots-preview">
                                            <span style="background: #faf5ff"></span>
                                            <span style="background: #c084fc"></span>
                                            <span style="background: #cbd5e1"></span>
                                            <span style="background: #7c3aed"></span>
                                        </span>
                                        <span>🌌 Royal Violet Prestige</span>
                                    </button>
                                </div>
                            </div>

                            <!-- TYPOGRAPHY SELECTORS: 3 CORE ROLES -->
                            <?php
                            $curatedFontsList = [
                                'Syne' => ['name' => 'Syne', 'css' => "'Syne', sans-serif", 'badge' => 'Avant-Garde'],
                                'Space Grotesk' => ['name' => 'Space Grotesk', 'css' => "'Space Grotesk', sans-serif", 'badge' => 'Neo-Tech'],
                                'Cinzel' => ['name' => 'Cinzel', 'css' => "'Cinzel', serif", 'badge' => 'Cinema Serif'],
                                'Bebas Neue' => ['name' => 'Bebas Neue', 'css' => "'Bebas Neue', sans-serif", 'badge' => 'Poster'],
                                'Outfit' => ['name' => 'Outfit', 'css' => "'Outfit', sans-serif", 'badge' => 'Clean Sans'],
                                'Playfair Display' => ['name' => 'Playfair Display', 'css' => "'Playfair Display', serif", 'badge' => 'Editorial'],
                                'Righteous' => ['name' => 'Righteous', 'css' => "'Righteous', cursive", 'badge' => 'Cyber'],
                                'Montserrat' => ['name' => 'Montserrat', 'css' => "'Montserrat', sans-serif", 'badge' => 'Bold Block'],
                                'Italiana' => ['name' => 'Italiana', 'css' => "'Italiana', serif", 'badge' => 'Couture'],
                                'Plus Jakarta Sans' => ['name' => 'Plus Jakarta Sans', 'css' => "'Plus Jakarta Sans', sans-serif", 'badge' => 'Precision']
                            ];
                            $activeHeadingFont = $settings['site_font_heading'] ?? 'Syne';
                            $activeEditorialFont = $settings['site_font_editorial'] ?? 'Outfit';
                            $activeBodyFont = $settings['site_font_body'] ?? 'Plus Jakarta Sans';
                            $activeHeroFont = $settings['hero_headline_font'] ?? $activeHeadingFont;
                            ?>

                            <!-- 1. Main Headings Typography -->
                            <div class="site-typography-group-card form-full-width">
                                <div class="site-typography-group-header">
                                    <span class="site-typography-group-title"><i data-lucide="heading-1" size="18" style="color: #38bdf8;"></i> 1. Main Headings Font Family (H1, H2, H3, Section Titles, Brand Headlines)</span>
                                    <span class="site-typography-group-sub">Governs all major headlines and titles across every section</span>
                                </div>
                                <select name="settings[site_font_heading]" id="siteFontHeading" class="form-control-admin" onchange="onSiteFontDropdownChange('heading')">
                                    <?php foreach ($curatedFontsList as $fKey => $fData): ?>
                                        <option value="<?php echo htmlspecialchars($fKey); ?>" <?php echo ($activeHeadingFont === $fKey) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($fData['name']); ?> (<?php echo htmlspecialchars($fData['badge']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="font-chips-container" id="headingFontChipsContainer">
                                    <?php foreach ($curatedFontsList as $fKey => $fData): ?>
                                        <button type="button" 
                                                class="font-chip-btn site-font-chip <?php echo ($activeHeadingFont === $fKey) ? 'active' : ''; ?>" 
                                                data-font-cat="heading"
                                                data-font-name="<?php echo htmlspecialchars($fKey); ?>"
                                                onclick="selectSiteFont('heading', '<?php echo htmlspecialchars($fKey); ?>')"
                                                style="font-family: <?php echo $fData['css']; ?>;">
                                            <span class="font-chip-title"><?php echo htmlspecialchars($fData['name']); ?></span>
                                            <span class="font-chip-sub"><?php echo htmlspecialchars($fData['badge']); ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- 2. Sub-headings & Taglines Typography -->
                            <div class="site-typography-group-card form-full-width">
                                <div class="site-typography-group-header">
                                    <span class="site-typography-group-title"><i data-lucide="tag" size="18" style="color: #c084fc;"></i> 2. Sub-headings, Taglines, Badges &amp; Accents Font Family</span>
                                    <span class="site-typography-group-sub">Governs section labels, category badges, telemetry badges, and buttons</span>
                                </div>
                                <select name="settings[site_font_editorial]" id="siteFontEditorial" class="form-control-admin" onchange="onSiteFontDropdownChange('editorial')">
                                    <?php foreach ($curatedFontsList as $fKey => $fData): ?>
                                        <option value="<?php echo htmlspecialchars($fKey); ?>" <?php echo ($activeEditorialFont === $fKey) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($fData['name']); ?> (<?php echo htmlspecialchars($fData['badge']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="font-chips-container" id="editorialFontChipsContainer">
                                    <?php foreach ($curatedFontsList as $fKey => $fData): ?>
                                        <button type="button" 
                                                class="font-chip-btn site-font-chip <?php echo ($activeEditorialFont === $fKey) ? 'active' : ''; ?>" 
                                                data-font-cat="editorial"
                                                data-font-name="<?php echo htmlspecialchars($fKey); ?>"
                                                onclick="selectSiteFont('editorial', '<?php echo htmlspecialchars($fKey); ?>')"
                                                style="font-family: <?php echo $fData['css']; ?>;">
                                            <span class="font-chip-title"><?php echo htmlspecialchars($fData['name']); ?></span>
                                            <span class="font-chip-sub"><?php echo htmlspecialchars($fData['badge']); ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- 3. Body Text & Descriptions Typography -->
                            <div class="site-typography-group-card form-full-width">
                                <div class="site-typography-group-header">
                                    <span class="site-typography-group-title"><i data-lucide="align-left" size="18" style="color: #34d399;"></i> 3. Body Text, Descriptions &amp; Paragraphs Font Family</span>
                                    <span class="site-typography-group-sub">Governs editorial statements, service cards, portfolio details, and FAQs</span>
                                </div>
                                <select name="settings[site_font_body]" id="siteFontBody" class="form-control-admin" onchange="onSiteFontDropdownChange('body')">
                                    <?php foreach ($curatedFontsList as $fKey => $fData): ?>
                                        <option value="<?php echo htmlspecialchars($fKey); ?>" <?php echo ($activeBodyFont === $fKey) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($fData['name']); ?> (<?php echo htmlspecialchars($fData['badge']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="font-chips-container" id="bodyFontChipsContainer">
                                    <?php foreach ($curatedFontsList as $fKey => $fData): ?>
                                        <button type="button" 
                                                class="font-chip-btn site-font-chip <?php echo ($activeBodyFont === $fKey) ? 'active' : ''; ?>" 
                                                data-font-cat="body"
                                                data-font-name="<?php echo htmlspecialchars($fKey); ?>"
                                                onclick="selectSiteFont('body', '<?php echo htmlspecialchars($fKey); ?>')"
                                                style="font-family: <?php echo $fData['css']; ?>;">
                                            <span class="font-chip-title"><?php echo htmlspecialchars($fData['name']); ?></span>
                                            <span class="font-chip-sub"><?php echo htmlspecialchars($fData['badge']); ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- 4. Hero Headline Specific Typography -->
                            <div class="site-typography-group-card form-full-width">
                                <div class="site-typography-group-header">
                                    <span class="site-typography-group-title"><i data-lucide="sparkles" size="18" style="color: #fbbf24;"></i> 4. Hero Brand Headline Specific Typography ("Design Your Every Occasion")</span>
                                    <span class="site-typography-group-sub">Customize the hero headline font independently or link it to main headings</span>
                                </div>
                                <select name="settings[hero_headline_font]" id="heroHeadlineFont" class="form-control-admin" onchange="onSiteFontDropdownChange('hero')">
                                    <?php foreach ($curatedFontsList as $fKey => $fData): ?>
                                        <option value="<?php echo htmlspecialchars($fKey); ?>" <?php echo ($activeHeroFont === $fKey) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($fData['name']); ?> (<?php echo htmlspecialchars($fData['badge']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="font-chips-container" id="heroFontChipsContainer">
                                    <?php foreach ($curatedFontsList as $fKey => $fData): ?>
                                        <button type="button" 
                                                class="font-chip-btn site-font-chip <?php echo ($activeHeroFont === $fKey) ? 'active' : ''; ?>" 
                                                data-font-cat="hero"
                                                data-font-name="<?php echo htmlspecialchars($fKey); ?>"
                                                onclick="selectSiteFont('hero', '<?php echo htmlspecialchars($fKey); ?>')"
                                                style="font-family: <?php echo $fData['css']; ?>;">
                                            <span class="font-chip-title"><?php echo htmlspecialchars($fData['name']); ?></span>
                                            <span class="font-chip-sub"><?php echo htmlspecialchars($fData['badge']); ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- INTERACTIVE DRAGGABLE UNIVERSAL COLOR STUDIO (6 SLOTS) -->
                            <div class="form-group-admin form-full-width">
                                <div class="color-studio-header">
                                    <div>
                                        <label class="color-studio-label">
                                            <i data-lucide="palette" size="16"></i>
                                            Universal Color Architecture Studio (Drag &amp; Pick)
                                        </label>
                                        <p class="color-studio-sub">
                                            Click any color role slot below, then drag across the 2D saturation/brightness spectrum or slide the rainbow hue bar to dial in colors in real time.
                                        </p>
                                    </div>
                                    <div class="active-slot-pill">
                                        <span>Active Slot: </span>
                                        <strong id="siteActiveSlotIndicator">1. Main Headings Color</strong>
                                    </div>
                                </div>

                                <!-- 6 Selectable Color Slots -->
                                <div class="site-color-slots-grid">
                                    <div class="color-slot-card active" id="siteSlotCard1" onclick="selectSiteColorSlot(1)">
                                        <div class="color-slot-swatch" id="siteSlotSwatch1" style="background: <?php echo htmlspecialchars($settings['site_color_heading'] ?? '#ffffff'); ?>;"></div>
                                        <div class="color-slot-meta">
                                            <span class="color-slot-tag">Slot 1</span>
                                            <span class="color-slot-title">Main Headings</span>
                                            <span class="color-slot-hex" id="siteSlotHexLabel1"><?php echo htmlspecialchars($settings['site_color_heading'] ?? '#ffffff'); ?></span>
                                        </div>
                                        <input type="hidden" name="settings[site_color_heading]" id="siteColorText1" value="<?php echo htmlspecialchars($settings['site_color_heading'] ?? '#ffffff'); ?>">
                                    </div>

                                    <div class="color-slot-card" id="siteSlotCard2" onclick="selectSiteColorSlot(2)">
                                        <div class="color-slot-swatch" id="siteSlotSwatch2" style="background: <?php echo htmlspecialchars($settings['site_color_subheading'] ?? '#38bdf8'); ?>;"></div>
                                        <div class="color-slot-meta">
                                            <span class="color-slot-tag">Slot 2</span>
                                            <span class="color-slot-title">Sub-headings</span>
                                            <span class="color-slot-hex" id="siteSlotHexLabel2"><?php echo htmlspecialchars($settings['site_color_subheading'] ?? '#38bdf8'); ?></span>
                                        </div>
                                        <input type="hidden" name="settings[site_color_subheading]" id="siteColorText2" value="<?php echo htmlspecialchars($settings['site_color_subheading'] ?? '#38bdf8'); ?>">
                                    </div>

                                    <div class="color-slot-card" id="siteSlotCard3" onclick="selectSiteColorSlot(3)">
                                        <div class="color-slot-swatch" id="siteSlotSwatch3" style="background: <?php echo htmlspecialchars($settings['site_color_body'] ?? '#a0a0ab'); ?>;"></div>
                                        <div class="color-slot-meta">
                                            <span class="color-slot-tag">Slot 3</span>
                                            <span class="color-slot-title">Body Paragraphs</span>
                                            <span class="color-slot-hex" id="siteSlotHexLabel3"><?php echo htmlspecialchars($settings['site_color_body'] ?? '#a0a0ab'); ?></span>
                                        </div>
                                        <input type="hidden" name="settings[site_color_body]" id="siteColorText3" value="<?php echo htmlspecialchars($settings['site_color_body'] ?? '#a0a0ab'); ?>">
                                    </div>

                                    <div class="color-slot-card" id="siteSlotCard4" onclick="selectSiteColorSlot(4)">
                                        <div class="color-slot-swatch" id="siteSlotSwatch4" style="background: <?php echo htmlspecialchars($settings['site_color_accent'] ?? '#0066ff'); ?>;"></div>
                                        <div class="color-slot-meta">
                                            <span class="color-slot-tag">Slot 4</span>
                                            <span class="color-slot-title">Brand Accent</span>
                                            <span class="color-slot-hex" id="siteSlotHexLabel4"><?php echo htmlspecialchars($settings['site_color_accent'] ?? '#0066ff'); ?></span>
                                        </div>
                                        <input type="hidden" name="settings[site_color_accent]" id="siteColorText4" value="<?php echo htmlspecialchars($settings['site_color_accent'] ?? '#0066ff'); ?>">
                                    </div>

                                    <div class="color-slot-card" id="siteSlotCard5" onclick="selectSiteColorSlot(5)">
                                        <div class="color-slot-swatch" id="siteSlotSwatch5" style="background: <?php echo htmlspecialchars($settings['hero_color_glow'] ?? '#38bdf8'); ?>;"></div>
                                        <div class="color-slot-meta">
                                            <span class="color-slot-tag">Slot 5</span>
                                            <span class="color-slot-title">Hero Glow Text</span>
                                            <span class="color-slot-hex" id="siteSlotHexLabel5"><?php echo htmlspecialchars($settings['hero_color_glow'] ?? '#38bdf8'); ?></span>
                                        </div>
                                        <input type="hidden" name="settings[hero_color_glow]" id="siteColorText5" value="<?php echo htmlspecialchars($settings['hero_color_glow'] ?? '#38bdf8'); ?>">
                                    </div>

                                    <div class="color-slot-card" id="siteSlotCard6" onclick="selectSiteColorSlot(6)">
                                        <div class="color-slot-swatch" id="siteSlotSwatch6" style="background: <?php echo htmlspecialchars($settings['site_color_muted'] ?? '#64748b'); ?>;"></div>
                                        <div class="color-slot-meta">
                                            <span class="color-slot-tag">Slot 6</span>
                                            <span class="color-slot-title">Muted Labels</span>
                                            <span class="color-slot-hex" id="siteSlotHexLabel6"><?php echo htmlspecialchars($settings['site_color_muted'] ?? '#64748b'); ?></span>
                                        </div>
                                        <input type="hidden" name="settings[site_color_muted]" id="siteColorText6" value="<?php echo htmlspecialchars($settings['site_color_muted'] ?? '#64748b'); ?>">
                                    </div>

                                    <div class="color-slot-card" id="siteSlotCard7" onclick="selectSiteColorSlot(7)" style="border-left: 3px solid #38bdf8;">
                                        <div class="color-slot-swatch" id="siteSlotSwatch7" style="background: <?php echo htmlspecialchars($settings['site_color_bg'] ?? '#050505'); ?>;"></div>
                                        <div class="color-slot-meta">
                                            <span class="color-slot-tag" style="color: #38bdf8;">Slot 7 &bull; Site BG</span>
                                            <span class="color-slot-title">Main Background</span>
                                            <span class="color-slot-hex" id="siteSlotHexLabel7"><?php echo htmlspecialchars($settings['site_color_bg'] ?? '#050505'); ?></span>
                                        </div>
                                        <input type="hidden" name="settings[site_color_bg]" id="siteColorText7" value="<?php echo htmlspecialchars($settings['site_color_bg'] ?? '#050505'); ?>">
                                    </div>

                                    <div class="color-slot-card" id="siteSlotCard8" onclick="selectSiteColorSlot(8)" style="border-left: 3px solid #a855f7;">
                                        <div class="color-slot-swatch" id="siteSlotSwatch8" style="background: <?php echo htmlspecialchars($settings['site_color_card'] ?? '#0c0d12'); ?>;"></div>
                                        <div class="color-slot-meta">
                                            <span class="color-slot-tag" style="color: #c084fc;">Slot 8 &bull; Surfaces</span>
                                            <span class="color-slot-title">Cards &amp; Panels</span>
                                            <span class="color-slot-hex" id="siteSlotHexLabel8"><?php echo htmlspecialchars($settings['site_color_card'] ?? '#0c0d12'); ?></span>
                                        </div>
                                        <input type="hidden" name="settings[site_color_card]" id="siteColorText8" value="<?php echo htmlspecialchars($settings['site_color_card'] ?? '#0c0d12'); ?>">
                                    </div>
                                </div>

                                <!-- Main Draggable Studio Console -->
                                <div class="draggable-color-studio-box">
                                    <div class="studio-spectrum-area" id="siteSpectrumArea">
                                        <div class="spectrum-gradient-white"></div>
                                        <div class="spectrum-gradient-black"></div>
                                        <div class="spectrum-handle" id="siteSpectrumHandle"></div>
                                    </div>

                                    <div class="studio-hue-wrap">
                                        <div class="studio-hue-track" id="siteHueTrack">
                                            <div class="hue-slider-thumb" id="siteHueSliderThumb"></div>
                                        </div>
                                    </div>

                                    <div class="studio-controls-bar">
                                        <div class="studio-active-swatch-box">
                                            <div class="studio-active-swatch" id="siteStudioActiveSwatch"></div>
                                            <div class="studio-swatch-info">
                                                <span class="studio-swatch-title">Active Color</span>
                                                <span class="studio-swatch-rgb" id="siteStudioActiveRgb">rgb(255, 255, 255)</span>
                                            </div>
                                        </div>

                                        <div class="studio-hex-input-wrap">
                                            <span class="hex-hash-prefix">#</span>
                                            <input type="text" id="siteStudioHexInput" class="studio-hex-field" value="ffffff" maxlength="6" oninput="onSiteStudioHexTyped(this.value)">
                                            <button type="button" class="btn-copy-hex" onclick="copySiteStudioHex()" title="Copy Hex">
                                                <i data-lucide="copy" size="14"></i>
                                            </button>
                                            <button type="button" class="btn-eyedropper" onclick="pickSiteScreenColor()" title="Pick color from screen">
                                                <i data-lucide="pipette" size="14"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="studio-palette-chips-wrap">
                                        <span class="palette-chips-label">Quick Luxury Tones:</span>
                                        <div class="studio-chips-grid">
                                            <button type="button" class="studio-chip" style="background: #ffffff;" onclick="applySiteStudioColor('#ffffff')" title="Pure White"></button>
                                            <button type="button" class="studio-chip" style="background: #0066ff;" onclick="applySiteStudioColor('#0066ff')" title="Electric Cobalt"></button>
                                            <button type="button" class="studio-chip" style="background: #38bdf8;" onclick="applySiteStudioColor('#38bdf8')" title="Cyan Glow"></button>
                                            <button type="button" class="studio-chip" style="background: #00ffaa;" onclick="applySiteStudioColor('#00ffaa')" title="Neon Mint"></button>
                                            <button type="button" class="studio-chip" style="background: #a855f7;" onclick="applySiteStudioColor('#a855f7')" title="Electric Purple"></button>
                                            <button type="button" class="studio-chip" style="background: #fbbf24;" onclick="applySiteStudioColor('#fbbf24')" title="Imperial Amber"></button>
                                            <button type="button" class="studio-chip" style="background: #ef4444;" onclick="applySiteStudioColor('#ef4444')" title="Vivid Crimson"></button>
                                            <button type="button" class="studio-chip" style="background: #fb7185;" onclick="applySiteStudioColor('#fb7185')" title="Rose Gold"></button>
                                            <button type="button" class="studio-chip" style="background: #a0a0ab;" onclick="applySiteStudioColor('#a0a0ab')" title="Muted Silver"></button>
                                            <button type="button" class="studio-chip" style="background: #64748b;" onclick="applySiteStudioColor('#64748b')" title="Slate Gray"></button>
                                            <button type="button" class="studio-chip" style="background: #050505; border: 1px solid rgba(255,255,255,0.3);" onclick="applySiteStudioColor('#050505')" title="Space Black"></button>
                                            <button type="button" class="studio-chip" style="background: #080e1a; border: 1px solid #38bdf8;" onclick="applySiteStudioColor('#080e1a')" title="Midnight Navy"></button>
                                            <button type="button" class="studio-chip" style="background: #04140c; border: 1px solid #34d399;" onclick="applySiteStudioColor('#04140c')" title="Deep Emerald"></button>
                                            <button type="button" class="studio-chip" style="background: #0e061a; border: 1px solid #c084fc;" onclick="applySiteStudioColor('#0e061a')" title="Royal Violet"></button>
                                            <button type="button" class="studio-chip" style="background: #111116; border: 1px solid rgba(255,255,255,0.25);" onclick="applySiteStudioColor('#111116')" title="Luxury Charcoal"></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- DIRECT COLOR FINE-TUNING & SECTION REFINEMENTS -->
                            <div class="admin-divider-header" style="margin-top: 24px;">
                                <i data-lucide="sliders" size="18"></i>
                                <h4>Direct Color Swatches &amp; Fine-Tuning</h4>
                            </div>
                            <div class="form-grid">
                                <div class="form-group-admin" style="grid-column: span 2;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                        <label style="margin: 0; font-weight: 700; color: #38bdf8;">
                                            <i data-lucide="layers" size="14" style="vertical-align: middle; margin-right: 4px;"></i>
                                            Main Site Background Color (Full Website Background)
                                        </label>
                                        <span style="font-size: 0.74rem; color: #94a3b8;">Controls the entire site's space black or custom background</span>
                                    </div>
                                    <div class="color-picker-input-group">
                                        <input type="color" class="color-swatch-picker" value="<?php echo htmlspecialchars($settings['site_color_bg'] ?? '#050505'); ?>" onchange="applyDirectColor(7, this.value)">
                                        <input type="text" class="form-control-admin" id="directColorInput7" value="<?php echo htmlspecialchars($settings['site_color_bg'] ?? '#050505'); ?>" oninput="applyDirectColor(7, this.value)">
                                    </div>
                                    <!-- Quick Luxury Background Presets -->
                                    <div class="quick-bg-tones" style="display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap;">
                                        <button type="button" class="btn-quick-bg-tone" onclick="applyDirectColor(7, '#050505'); applyDirectColor(8, '#0c0d12');" style="background: #050505; border: 1px solid rgba(255,255,255,0.2); color: #e2e8f0; font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; cursor: pointer;">Space Black (#050505)</button>
                                        <button type="button" class="btn-quick-bg-tone" onclick="applyDirectColor(7, '#080e1a'); applyDirectColor(8, '#0f172a');" style="background: #080e1a; border: 1px solid rgba(56,189,248,0.3); color: #38bdf8; font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; cursor: pointer;">Midnight Navy (#080e1a)</button>
                                        <button type="button" class="btn-quick-bg-tone" onclick="applyDirectColor(7, '#04140c'); applyDirectColor(8, '#082316');" style="background: #04140c; border: 1px solid rgba(52,211,153,0.3); color: #34d399; font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; cursor: pointer;">Deep Emerald (#04140c)</button>
                                        <button type="button" class="btn-quick-bg-tone" onclick="applyDirectColor(7, '#0e061a'); applyDirectColor(8, '#1b0e2f');" style="background: #0e061a; border: 1px solid rgba(192,132,252,0.3); color: #c084fc; font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; cursor: pointer;">Royal Violet (#0e061a)</button>
                                        <button type="button" class="btn-quick-bg-tone" onclick="applyDirectColor(7, '#111116'); applyDirectColor(8, '#18181f');" style="background: #111116; border: 1px solid rgba(255,255,255,0.15); color: #cbd5e1; font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; cursor: pointer;">Luxury Charcoal (#111116)</button>
                                        <button type="button" class="btn-quick-bg-tone" onclick="applyDirectColor(7, '#160808'); applyDirectColor(8, '#240f0f');" style="background: #160808; border: 1px solid rgba(244,63,94,0.3); color: #fb7185; font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; cursor: pointer;">Velvet Crimson (#160808)</button>
                                    </div>
                                </div>

                                <div class="form-group-admin" style="grid-column: span 2;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                        <label style="margin: 0; font-weight: 700; color: #c084fc;">
                                            <i data-lucide="box" size="14" style="vertical-align: middle; margin-right: 4px;"></i>
                                            Cards &amp; Container Surfaces Background
                                        </label>
                                        <span style="font-size: 0.74rem; color: #94a3b8;">Controls elevated cards, boxes, and modal panels</span>
                                    </div>
                                    <div class="color-picker-input-group">
                                        <input type="color" class="color-swatch-picker" value="<?php echo htmlspecialchars($settings['site_color_card'] ?? '#0c0d12'); ?>" onchange="applyDirectColor(8, this.value)">
                                        <input type="text" class="form-control-admin" id="directColorInput8" value="<?php echo htmlspecialchars($settings['site_color_card'] ?? '#0c0d12'); ?>" oninput="applyDirectColor(8, this.value)">
                                    </div>
                                </div>

                                <div class="form-group-admin">
                                    <label>Main Headings Color</label>
                                    <div class="color-picker-input-group">
                                        <input type="color" class="color-swatch-picker" value="<?php echo htmlspecialchars($settings['site_color_heading'] ?? '#ffffff'); ?>" onchange="applyDirectColor(1, this.value)">
                                        <input type="text" class="form-control-admin" id="directColorInput1" value="<?php echo htmlspecialchars($settings['site_color_heading'] ?? '#ffffff'); ?>" oninput="applyDirectColor(1, this.value)">
                                    </div>
                                </div>

                                <div class="form-group-admin">
                                    <label>Sub-headings &amp; Taglines Color</label>
                                    <div class="color-picker-input-group">
                                        <input type="color" class="color-swatch-picker" value="<?php echo htmlspecialchars($settings['site_color_subheading'] ?? '#38bdf8'); ?>" onchange="applyDirectColor(2, this.value)">
                                        <input type="text" class="form-control-admin" id="directColorInput2" value="<?php echo htmlspecialchars($settings['site_color_subheading'] ?? '#38bdf8'); ?>" oninput="applyDirectColor(2, this.value)">
                                    </div>
                                </div>

                                <div class="form-group-admin">
                                    <label>Body Text &amp; Paragraphs Color</label>
                                    <div class="color-picker-input-group">
                                        <input type="color" class="color-swatch-picker" value="<?php echo htmlspecialchars($settings['site_color_body'] ?? '#a0a0ab'); ?>" onchange="applyDirectColor(3, this.value)">
                                        <input type="text" class="form-control-admin" id="directColorInput3" value="<?php echo htmlspecialchars($settings['site_color_body'] ?? '#a0a0ab'); ?>" oninput="applyDirectColor(3, this.value)">
                                    </div>
                                </div>

                                <div class="form-group-admin">
                                    <label>Brand Primary Accent Color</label>
                                    <div class="color-picker-input-group">
                                        <input type="color" class="color-swatch-picker" value="<?php echo htmlspecialchars($settings['site_color_accent'] ?? '#0066ff'); ?>" onchange="applyDirectColor(4, this.value)">
                                        <input type="text" class="form-control-admin" id="directColorInput4" value="<?php echo htmlspecialchars($settings['site_color_accent'] ?? '#0066ff'); ?>" oninput="applyDirectColor(4, this.value)">
                                    </div>
                                </div>

                                <div class="form-group-admin">
                                    <label>Hero Headline Glow Accent Color</label>
                                    <div class="color-picker-input-group">
                                        <input type="color" class="color-swatch-picker" value="<?php echo htmlspecialchars($settings['hero_color_glow'] ?? '#38bdf8'); ?>" onchange="applyDirectColor(5, this.value)">
                                        <input type="text" class="form-control-admin" id="directColorInput5" value="<?php echo htmlspecialchars($settings['hero_color_glow'] ?? '#38bdf8'); ?>" oninput="applyDirectColor(5, this.value)">
                                    </div>
                                </div>

                                <div class="form-group-admin">
                                    <label>Hero Mission Text Color</label>
                                    <div class="color-picker-input-group">
                                        <input type="color" class="color-swatch-picker" value="<?php echo htmlspecialchars($settings['hero_color_mission'] ?? '#cbd5e1'); ?>" onchange="document.getElementById('siteColorMission').value = this.value; document.getElementById('previewBodyText').style.color = this.value;">
                                        <input type="text" name="settings[hero_color_mission]" id="siteColorMission" class="form-control-admin" value="<?php echo htmlspecialchars($settings['hero_color_mission'] ?? '#cbd5e1'); ?>" oninput="document.getElementById('previewBodyText').style.color = this.value;">
                                    </div>
                                </div>
                            </div>

                            <div class="admin-actions-right">
                                <button type="submit" class="btn-save"><i data-lucide="save" size="18"></i><span>Save Whole-Website Typography &amp; Colors</span></button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- ================= TAB: AI ASSISTANT (NOVA) CONFIGURATION ================= -->
            <?php if ($active_tab === 'ai'): ?>
                <?php
                $isAiEnabled = !isset($settings['ai_enabled']) || $settings['ai_enabled'] === '1' || $settings['ai_enabled'] === 'true' || $settings['ai_enabled'] === 1;
                $aiBotName = !empty($settings['ai_bot_name']) ? $settings['ai_bot_name'] : 'Nova';
                $aiGreetingTitle = !empty($settings['ai_greeting_title']) ? $settings['ai_greeting_title'] : 'Hi! I am Nova 👋';
                $aiGreetingDesc = !empty($settings['ai_greeting_desc']) ? $settings['ai_greeting_desc'] : 'How can I help you today?';
                
                // Quick Database Entity Counts for Nova Knowledge Stats
                $cServices = (int)$pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
                $cPackages = (int)$pdo->query("SELECT COUNT(*) FROM pricing_packages")->fetchColumn();
                $cPortfolio = (int)$pdo->query("SELECT COUNT(*) FROM portfolio_items")->fetchColumn();
                $cTeam = (int)$pdo->query("SELECT COUNT(*) FROM team_members")->fetchColumn();
                $cFaq = (int)$pdo->query("SELECT COUNT(*) FROM faq_items")->fetchColumn();
                $cCollab = (int)$pdo->query("SELECT COUNT(*) FROM collaborators")->fetchColumn();
                $totalKnowledgeEntities = $cServices + $cPackages + $cPortfolio + $cTeam + $cFaq + $cCollab + 28;
                ?>

                <div class="glass-panel">
                    <div class="admin-panel-header-bar">
                        <div>
                            <h3 class="glass-panel-title title-mb-5">
                                <span class="panel-title-text">
                                    <i data-lucide="bot" size="22" class="icon-accent" style="color: #38bdf8;"></i>
                                    <span>AI Assistant (Nova) Cognitive Knowledge Control</span>
                                </span>
                            </h3>
                            <p class="admin-header-subtitle">
                                Master control center for the website conversational chatbot, master toggle switch, real-time database knowledge indexing, and Google Gemini LLM engine.
                            </p>
                        </div>
                        <div class="ai-status-pill-wrap">
                            <span class="ai-status-pill <?php echo $isAiEnabled ? 'status-active' : 'status-disabled'; ?>" id="aiGlobalStatusPill">
                                <span class="pulse-indicator"></span>
                                <span id="aiGlobalStatusText"><?php echo $isAiEnabled ? 'LIVE &amp; ACTIVE ON WEBSITE' : 'OFFLINE / DISABLED'; ?></span>
                            </span>
                        </div>
                    </div>

                    <form method="POST" action="index.php" id="aiConfigForm">
                        <input type="hidden" name="action" value="update_settings">
                        <input type="hidden" name="active_tab" value="ai">

                        <!-- 1. MASTER TOGGLE SWITCH CARD -->
                        <div class="ai-toggle-master-card <?php echo $isAiEnabled ? 'active' : 'disabled'; ?>" id="aiMasterCard">
                            <div class="ai-toggle-info">
                                <div class="ai-toggle-title-row">
                                    <i data-lucide="<?php echo $isAiEnabled ? 'power' : 'power-off'; ?>" size="22" id="aiPowerIcon" style="color: <?php echo $isAiEnabled ? '#10b981' : '#94a3b8'; ?>;"></i>
                                    <h4 id="aiToggleHeadlineText"><?php echo $isAiEnabled ? 'AI Assistant is Currently Active on Live Website' : 'AI Assistant is Disabled (Hidden from Visitors)'; ?></h4>
                                </div>
                                <p class="ai-toggle-subtext" id="aiToggleExplainer">
                                    <?php echo $isAiEnabled 
                                        ? 'The animated Nova robot widget and "Chat with Nova" Hero badges are currently visible and serving client inquiries on perumalilcreative.com.'
                                        : 'The AI companion is completely hidden and disabled from the live website. Visitors cannot see or trigger the chatbot.'; ?>
                                </p>
                            </div>
                            <div class="ai-switch-control">
                                <input type="hidden" name="settings[ai_enabled]" value="0">
                                <label class="cyber-switch-label" title="Toggle AI Assistant On/Off">
                                    <input type="checkbox" name="settings[ai_enabled]" value="1" id="aiEnabledCheckbox" <?php echo $isAiEnabled ? 'checked' : ''; ?> onchange="onAiToggleChanged(this)">
                                    <span class="cyber-switch-slider"></span>
                                </label>
                            </div>
                        </div>

                        <!-- 2. REAL-TIME DATABASE KNOWLEDGE BASE & RE-INDEX CARD -->
                        <div class="ai-knowledge-metrics-card">
                            <div class="ai-knowledge-card-header">
                                <div class="knowledge-header-left">
                                    <div class="knowledge-badge-pill">
                                        <i data-lucide="database" size="14"></i>
                                        <span>STUDIED DATABASE METRICS</span>
                                    </div>
                                    <h4>Automatic Real-Time Website Knowledge Engine</h4>
                                    <p>Nova studies and memorizes all records in your MySQL database so it can answer visitor questions about services, pricing, projects, and office location with 100% accuracy.</p>
                                </div>
                                <div class="knowledge-header-right">
                                    <button type="button" class="btn-reindex-knowledge" id="btnReindexKnowledgeTab" onclick="triggerReindexKnowledge(this)" title="Re-sync all database records into Nova AI Assistant">
                                        <i data-lucide="refresh-cw" size="14"></i>
                                        <span>Re-Index Site Knowledge</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Entity Count Badges -->
                            <div class="ai-entity-metrics-grid">
                                <div class="ai-metric-item">
                                    <span class="metric-num"><?php echo $cServices; ?></span>
                                    <span class="metric-lbl"><i data-lucide="layout-grid" size="13"></i> Services</span>
                                </div>
                                <div class="ai-metric-item">
                                    <span class="metric-num"><?php echo $cPackages; ?></span>
                                    <span class="metric-lbl"><i data-lucide="tag" size="13"></i> Pricing Packages</span>
                                </div>
                                <div class="ai-metric-item">
                                    <span class="metric-num"><?php echo $cPortfolio; ?></span>
                                    <span class="metric-lbl"><i data-lucide="folder-open" size="13"></i> Portfolio Works</span>
                                </div>
                                <div class="ai-metric-item">
                                    <span class="metric-num"><?php echo $cTeam; ?></span>
                                    <span class="metric-lbl"><i data-lucide="users" size="13"></i> Team Members</span>
                                </div>
                                <div class="ai-metric-item">
                                    <span class="metric-num"><?php echo $cFaq; ?></span>
                                    <span class="metric-lbl"><i data-lucide="help-circle" size="13"></i> FAQ Accordions</span>
                                </div>
                                <div class="ai-metric-item">
                                    <span class="metric-num"><?php echo $cCollab; ?></span>
                                    <span class="metric-lbl"><i data-lucide="handshake" size="13"></i> Collaborators</span>
                                </div>
                                <div class="ai-metric-item highlight-metric">
                                    <span class="metric-num"><?php echo $totalKnowledgeEntities; ?>+</span>
                                    <span class="metric-lbl"><i data-lucide="cpu" size="13"></i> Total Knowledge Nodes</span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. BOT PERSONA & GREETING SETTINGS -->
                        <div class="form-grid" style="margin-top: 24px;">
                            <div class="form-group-admin">
                                <label>AI Assistant Display Name</label>
                                <input type="text" name="settings[ai_bot_name]" class="form-control-admin" value="<?php echo htmlspecialchars($aiBotName); ?>" placeholder="E.g., Nova">
                                <small style="color: #64748b; font-size: 0.73rem;">The persona name displayed on the live chatbot bubble and header tag.</small>
                            </div>

                            <div class="form-group-admin">
                                <label>Popup Bubble Title / Headline</label>
                                <input type="text" name="settings[ai_greeting_title]" class="form-control-admin" value="<?php echo htmlspecialchars($aiGreetingTitle); ?>" placeholder="E.g., Hi! I am Nova 👋">
                                <small style="color: #64748b; font-size: 0.73rem;">Greeting headline shown inside the floating animated character speech bubble.</small>
                            </div>

                            <div class="form-group-admin form-full-width">
                                <label>Popup Bubble Subtitle / Prompt Question</label>
                                <input type="text" name="settings[ai_greeting_desc]" class="form-control-admin" value="<?php echo htmlspecialchars($aiGreetingDesc); ?>" placeholder="How can I help you today?">
                                <small style="color: #64748b; font-size: 0.73rem;">Introductory question enticing visitors to click and interact with the AI assistant.</small>
                            </div>
                        </div>

                        <!-- 4. GOOGLE GEMINI LLM API KEY (OPTIONAL ACCELERATION) -->
                        <div class="gemini-integration-card" style="margin-top: 24px;">
                            <div class="gemini-card-header">
                                <div class="gemini-title-wrap">
                                    <span class="gemini-sparkle-badge">✦ LLM ACCELERATOR</span>
                                    <h4>Google Gemini 1.5 Flash Integration (Optional)</h4>
                                </div>
                                <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener" class="gemini-get-key-link">
                                    <span>Get Free Gemini Key</span>
                                    <i data-lucide="external-link" size="13"></i>
                                </a>
                            </div>
                            <p class="gemini-card-desc">
                                By default, Nova uses your high-speed, zero-cost PHP Server Knowledge Engine. You can optionally connect Google Gemini 1.5 Flash to enable hyper-natural, human-like conversations in Malayalam, English, and Manglish.
                            </p>

                            <div class="form-group-admin">
                                <label>Google Gemini API Key</label>
                                <div class="gemini-key-input-wrap">
                                    <input type="password" name="settings[gemini_api_key]" id="geminiApiKeyInput" class="form-control-admin" placeholder="AIzaSy... (Leave empty to use built-in free Server Engine)" value="<?php echo htmlspecialchars($settings['gemini_api_key'] ?? ''); ?>">
                                    <button type="button" class="btn-toggle-key-visibility" onclick="toggleApiKeyMask()" title="Show/Hide API Key">
                                        <i data-lucide="eye" size="16" id="eyeIconApiKey"></i>
                                    </button>
                                </div>
                                <small style="color: #64748b; font-size: 0.73rem; display: block; margin-top: 6px;">
                                    Status: <?php echo !empty($settings['gemini_api_key']) ? '<span style="color: #10b981; font-weight: 700;">● Gemini 1.5 Flash Connected</span>' : '<span style="color: #38bdf8; font-weight: 700;">● Built-in Server Knowledge Engine Active (Free)</span>'; ?>
                                </small>
                            </div>
                        </div>

                        <!-- 5. INTERACTIVE IN-ADMIN TEST PLAYGROUND -->
                        <div class="ai-test-playground-card" style="margin-top: 24px;">
                            <div class="playground-header">
                                <div class="playground-title-group">
                                    <i data-lucide="message-square-code" size="18" style="color: #38bdf8;"></i>
                                    <h4>Interactive Studio AI Test Console</h4>
                                </div>
                                <span class="playground-mode-tag">Direct Database Diagnostic</span>
                            </div>
                            <p style="font-size: 0.78rem; color: #94a3b8; margin-bottom: 14px;">
                                Test how Nova answers visitor queries before saving or publishing changes. Type any question in English, Malayalam, or Manglish:
                            </p>

                            <div class="playground-chat-box" id="playgroundChatBox">
                                <div class="playground-msg bot-msg">
                                    <div class="msg-avatar"><i data-lucide="bot" size="14"></i></div>
                                    <div class="msg-body">
                                        <strong>Nova AI:</strong> Hello! Ask me about our services, pricing, portfolio works, team, or studio location.
                                    </div>
                                </div>
                            </div>

                            <div class="playground-input-row">
                                <input type="text" id="playgroundInput" class="form-control-admin" placeholder="Type a test question (e.g., 'What are your video pricing packages?', 'വാഴക്കുളം എവിടെയാണ്?')" onkeydown="if(event.key==='Enter'){event.preventDefault();sendPlaygroundMsg();}">
                                <button type="button" class="btn-playground-send" onclick="sendPlaygroundMsg()" id="btnPlaygroundSend">
                                    <i data-lucide="send" size="15"></i>
                                    <span>Ask Nova</span>
                                </button>
                            </div>
                        </div>

                        <!-- Sticky Bottom Form Actions -->
                        <div class="admin-actions-bar" style="margin-top: 28px;">
                            <div class="admin-actions-left">
                                <span style="font-size: 0.8rem; color: #64748b;">Changes take effect immediately on perumalilcreative.com</span>
                            </div>
                            <div class="admin-actions-right">
                                <button type="submit" class="btn-save"><i data-lucide="save" size="18"></i><span>Save AI Settings</span></button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- ================= TAB: CLIENT INQUIRIES ================= -->
            <?php if ($active_tab === 'inquiries'): ?>
                <div class="glass-panel">
                    <div class="admin-panel-header-bar">
                        <div>
                            <h3 class="glass-panel-title title-mb-5">
                                <span class="panel-title-text">
                                    <i data-lucide="mail" size="20" class="icon-accent"></i>
                                    <span>Client Inquiries &amp; Creative Blueprints</span>
                                </span>
                            </h3>
                            <p class="admin-header-subtitle">
                                Total: <strong class="text-white"><?php echo count($inquiries); ?></strong> inquiries &bull; New: <strong class="text-highlight-blue"><?php echo $new_inquiries_count; ?></strong> unread
                            </p>
                        </div>
                        <div class="admin-search-wrap search-max-280">
                            <i data-lucide="search" size="15" class="admin-search-icon"></i>
                            <input type="text" placeholder="Search inquiries..." oninput="filterInquiriesBySearch(this.value)" class="admin-search-input">
                        </div>
                    </div>

                    <!-- Status Quick Filter Pills -->
                    <div class="category-filter-bar mb-18">
                        <button type="button" class="cat-filter-btn active" onclick="filterInquiriesByStatus('all', this)">
                            <span>All Inquiries</span>
                            <span class="cat-count-pill"><?php echo count($inquiries); ?></span>
                        </button>
                        <button type="button" class="cat-filter-btn" onclick="filterInquiriesByStatus('new', this)">
                            <span>🟡 New</span>
                            <span class="cat-count-pill pill-blue"><?php echo $new_inquiries_count; ?></span>
                        </button>
                        <button type="button" class="cat-filter-btn" onclick="filterInquiriesByStatus('in progress', this)">
                            <span>🔵 In Progress</span>
                        </button>
                        <button type="button" class="cat-filter-btn" onclick="filterInquiriesByStatus('replied', this)">
                            <span>🟢 Replied</span>
                        </button>
                        <button type="button" class="cat-filter-btn" onclick="filterInquiriesByStatus('archived', this)">
                            <span>⚪ Archived</span>
                        </button>
                    </div>

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
                                        <td colspan="7" class="table-empty-td">
                                            No inquiries received yet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($inquiries as $inq): ?>
                                        <tr id="inq-row-<?php echo $inq['id']; ?>" class="inquiry-row" data-status="<?php echo htmlspecialchars($inq['status']); ?>">
                                            <td class="td-date">
                                                <?php echo date('M d, Y H:i', strtotime($inq['created_at'])); ?>
                                            </td>
                                            <td class="td-client-name">
                                                <?php echo htmlspecialchars($inq['name']); ?>
                                            </td>
                                            <td>
                                                <a href="mailto:<?php echo htmlspecialchars($inq['email']); ?>" class="table-mail-link">
                                                    <?php echo htmlspecialchars($inq['email']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($inq['package_name']); ?>
                                            </td>
                                            <td class="td-price">
                                                &#8377;<?php echo number_format((float)$inq['price']); ?>
                                            </td>
                                            <td>
                                                <form action="?tab=inquiries" method="POST" class="status-select-form d-inline">
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
                                                    <form action="?tab=inquiries" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this inquiry?');">
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
                <div class="glass-panel">
                    <!-- Tab Header with Segmented Navigation -->
                    <div class="admin-panel-header-bar mb-22">
                        <div>
                            <h3 class="glass-panel-title title-mb-5">
                                <i data-lucide="layers" size="20" class="icon-accent"></i>
                                <span>Services &amp; Pricing Management</span>
                            </h3>
                            <p class="admin-header-subtitle">Switch between agency service offerings and pricing tiers without endless scrolling.</p>
                        </div>
                        <div class="admin-subnav" data-group="servicesGroup">
                            <button type="button" class="admin-subnav-btn active" onclick="switchAdminSubtab('subtab-services-list', this)">
                                <i data-lucide="layout-grid" size="15"></i>
                                <span>Agency Services (<?php echo count($services); ?>)</span>
                            </button>
                            <button type="button" class="admin-subnav-btn" onclick="switchAdminSubtab('subtab-pricing-list', this)">
                                <i data-lucide="credit-card" size="15"></i>
                                <span>Pricing Packages (<?php echo count($packages); ?>)</span>
                            </button>
                        </div>
                    </div>

                    <!-- SUB-TAB 1: AGENCY SERVICES -->
                    <div id="subtab-services-list" class="subtab-section active" data-subgroup="servicesGroup">
                        <div class="admin-panel-header-bar mb-18">
                            <div class="admin-search-wrap">
                                <i data-lucide="search" size="15" class="admin-search-icon"></i>
                                <input type="text" placeholder="Search services..." oninput="filterAdminCards('servicesCrudList', this.value)" class="admin-search-input">
                            </div>
                            <button type="button" class="btn-add-toggle" onclick="toggleAddDrawer('addServiceDrawer', this)">
                                <i data-lucide="plus" size="15"></i>
                                <span>Add New Service</span>
                            </button>
                        </div>

                        <!-- Collapsible Add Service Drawer -->
                        <div id="addServiceDrawer" class="collapsible-drawer-box">
                            <h4 class="drawer-title">
                                <i data-lucide="sparkles" size="16"></i>
                                <span>Create New Agency Service</span>
                            </h4>
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
                                <div class="drawer-actions">
                                    <button type="button" class="btn-save btn-cancel" onclick="toggleAddDrawer('addServiceDrawer')">Cancel</button>
                                    <button type="submit" class="btn-save">
                                        <i data-lucide="plus" size="16"></i>
                                        <span>Create Service</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Current Services list -->
                        <div class="crud-list" id="servicesCrudList">
                            <?php foreach ($services as $srv): ?>
                                <div class="crud-item" id="service-<?php echo $srv['id']; ?>">
                                    <div class="crud-info">
                                        <h4>
                                            <i data-lucide="<?php echo htmlspecialchars($srv['icon']); ?>" class="icon-accent"></i>
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
                                                <div class="drawer-actions">
                                                    <button type="button" class="btn-save btn-cancel" onclick="toggleEditForm('srv', <?php echo $srv['id']; ?>)">Cancel</button>
                                                    <button type="submit" class="btn-save">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="crud-actions">
                                        <button class="btn-icon" onclick="toggleEditForm('srv', <?php echo $srv['id']; ?>)" title="Edit Service">
                                            <i data-lucide="edit-3" size="16"></i>
                                        </button>
                                        <form action="?tab=services" method="POST" onsubmit="return confirm('Are you sure you want to delete this service?');">
                                            <input type="hidden" name="action" value="service_delete">
                                            <input type="hidden" name="id" value="<?php echo $srv['id']; ?>">
                                            <button type="submit" class="btn-icon btn-delete" title="Delete Service">
                                                <i data-lucide="trash-2" size="16"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- SUB-TAB 2: PRICING PACKAGES -->
                    <div id="subtab-pricing-list" class="subtab-section" data-subgroup="servicesGroup">
                        <div class="admin-panel-header-bar mb-18">
                            <div class="admin-search-wrap">
                                <i data-lucide="search" size="15" class="admin-search-icon"></i>
                                <input type="text" placeholder="Search pricing packages..." oninput="filterAdminCards('packagesCrudList', this.value)" class="admin-search-input">
                            </div>
                            <button type="button" class="btn-add-toggle" onclick="toggleAddDrawer('addPackageDrawer', this)">
                                <i data-lucide="plus" size="15"></i>
                                <span>Add New Pricing Package</span>
                            </button>
                        </div>

                        <!-- Collapsible Add Package Drawer -->
                        <div id="addPackageDrawer" class="collapsible-drawer-box">
                            <h4 class="drawer-title">
                                <i data-lucide="credit-card" size="16"></i>
                                <span>Create New Pricing Package</span>
                            </h4>
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
                                    <div class="form-group-admin checkbox-group-row">
                                        <input type="checkbox" id="is_popular_new" name="is_popular" value="1" class="checkbox-input">
                                        <label for="is_popular_new" class="checkbox-label">Mark as Popular Plan</label>
                                    </div>
                                    <div class="form-group-admin">
                                        <label>Display Sorting Order</label>
                                        <input type="number" name="order_index" class="form-control-admin" value="0">
                                    </div>
                                </div>
                                <div class="drawer-actions">
                                    <button type="button" class="btn-save btn-cancel" onclick="toggleAddDrawer('addPackageDrawer')">Cancel</button>
                                    <button type="submit" class="btn-save">
                                        <i data-lucide="plus" size="16"></i>
                                        <span>Create Package</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Current Packages list -->
                        <div class="crud-list" id="packagesCrudList">
                            <?php foreach ($packages as $pkg): ?>
                                <div class="crud-item" id="package-<?php echo $pkg['id']; ?>">
                                    <div class="crud-info">
                                        <h4>
                                            <span><?php echo htmlspecialchars($pkg['name']); ?></span>
                                            <?php if ($pkg['is_popular']): ?>
                                                <span class="badge-popular">Popular</span>
                                            <?php endif; ?>
                                        </h4>
                                        <p class="pkg-price-text"><?php echo htmlspecialchars($pkg['price_label']); ?>: &#8377;<?php echo htmlspecialchars($pkg['price']); ?></p>
                                        <p class="pkg-features-preview"><?php echo htmlspecialchars($pkg['features']); ?></p>
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
                                                        <label>Price Value (Without ₹, commas allowed)</label>
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
                                                    <div class="form-group-admin checkbox-group-row">
                                                        <input type="checkbox" id="is_popular_<?php echo $pkg['id']; ?>" name="is_popular" value="1" <?php echo $pkg['is_popular'] ? 'checked' : ''; ?> class="checkbox-input">
                                                        <label for="is_popular_<?php echo $pkg['id']; ?>" class="checkbox-label">Mark as Popular Plan</label>
                                                    </div>
                                                    <div class="form-group-admin">
                                                        <label>Display Sorting Order</label>
                                                        <input type="number" name="order_index" class="form-control-admin" value="<?php echo $pkg['order_index']; ?>">
                                                    </div>
                                                </div>
                                                <div class="drawer-actions">
                                                    <button type="button" class="btn-save btn-cancel" onclick="toggleEditForm('pkg', <?php echo $pkg['id']; ?>)">Cancel</button>
                                                    <button type="submit" class="btn-save">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="crud-actions">
                                        <button class="btn-icon" onclick="toggleEditForm('pkg', <?php echo $pkg['id']; ?>)" title="Edit Package">
                                            <i data-lucide="edit-3" size="16"></i>
                                        </button>
                                        <form action="?tab=services" method="POST" onsubmit="return confirm('Are you sure you want to delete this pricing package?');">
                                            <input type="hidden" name="action" value="package_delete">
                                            <input type="hidden" name="id" value="<?php echo $pkg['id']; ?>">
                                            <button type="submit" class="btn-icon btn-delete" title="Delete Package">
                                                <i data-lucide="trash-2" size="16"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ================= TAB 3: PORTFOLIO SHOWCASE ================= -->
            <?php if ($active_tab === 'portfolio'): ?>
                <div class="glass-panel">
                    <div class="admin-panel-header-bar mb-22">
                        <div>
                            <h3 class="glass-panel-title title-mb-5">
                                <i data-lucide="folder-open" size="20" class="icon-accent"></i>
                                <span>Portfolio Showcase Management</span>
                            </h3>
                            <p class="admin-header-subtitle">Organize project cards, category display order, and Launching Soon poster without scroll fatigue.</p>
                        </div>
                        <div class="admin-subnav" data-group="portfolioGroup">
                            <button type="button" class="admin-subnav-btn active" onclick="switchAdminSubtab('subtab-portfolio-items', this)">
                                <i data-lucide="grid" size="15"></i>
                                <span>Showcase Items (<?php echo count($portfolio); ?>)</span>
                            </button>
                            <button type="button" class="admin-subnav-btn" onclick="switchAdminSubtab('subtab-portfolio-template', this)">
                                <i data-lucide="sparkles" size="15"></i>
                                <span>Launching Soon Poster</span>
                            </button>
                            <button type="button" class="admin-subnav-btn" onclick="switchAdminSubtab('subtab-portfolio-categories', this)">
                                <i data-lucide="layers" size="15"></i>
                                <span>Category Tabs &amp; Order (<?php echo count($portfolio_categories); ?>)</span>
                            </button>
                        </div>
                    </div>

                    <!-- SUB-TAB 1: SHOWCASE ITEMS (DEFAULT ACTIVE) -->
                    <div id="subtab-portfolio-items" class="subtab-section active" data-subgroup="portfolioGroup">
                        <!-- Top Controls: Live Search & Add Drawer Toggle -->
                        <div class="admin-panel-header-bar mb-16">
                            <div class="admin-search-wrap">
                                <i data-lucide="search" size="15" class="admin-search-icon"></i>
                                <input type="text" placeholder="Search projects by title, tag, link..." oninput="filterAdminCards('portfolioCrudList', this.value)" class="admin-search-input">
                            </div>
                            <button type="button" class="btn-add-toggle" onclick="toggleAddDrawer('addPortfolioDrawer', this)">
                                <i data-lucide="plus" size="15"></i>
                                <span>Add Showcase Item</span>
                            </button>
                        </div>

                        <!-- Category Filter Pills Bar -->
                        <div class="category-filter-bar mb-20">
                            <button type="button" class="cat-filter-btn active" onclick="filterPortfolioByCategory('all', this)">
                                <span>All Projects</span>
                                <span class="cat-count-pill"><?php echo count($portfolio); ?></span>
                            </button>
                            <?php foreach ($portfolio_categories as $pCat): ?>
                                <?php
                                $cCount = 0;
                                foreach ($portfolio as $pItem) {
                                    if ($pItem['category'] === $pCat['slug']) $cCount++;
                                }
                                ?>
                                <button type="button" class="cat-filter-btn" onclick="filterPortfolioByCategory('<?php echo htmlspecialchars($pCat['slug']); ?>', this)">
                                    <span><?php echo htmlspecialchars($pCat['name']); ?></span>
                                    <span class="cat-count-pill"><?php echo $cCount; ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Collapsible Add Portfolio Item Drawer -->
                        <div id="addPortfolioDrawer" class="collapsible-drawer-box">
                            <h4 class="drawer-title">
                                <i data-lucide="sparkles" size="16"></i>
                                <span>Create Portfolio Showcase Item</span>
                            </h4>
                            <form action="?tab=portfolio" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="portfolio_add">
                                <div class="form-grid">
                                    <div class="form-group-admin">
                                        <div class="cat-select-header">
                                            <label class="cat-select-label">Display Category Tab</label>
                                            <button type="button" onclick="showNewCategoryInput('add')" class="btn-add-cat-link">+ Add New Category</button>
                                        </div>
                                        <select name="category" id="portfolioAddCategorySelect" class="form-control-admin bg-dark-select" onchange="handleCategorySelectChange(this, 'add')" required>
                                            <?php foreach ($portfolio_categories as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                            <option value="__new__">+ Add New Category...</option>
                                        </select>
                                        <div id="newCategoryInput_add" class="new-cat-input-wrap">
                                            <input type="text" name="new_category_name" class="form-control-admin new-cat-input" placeholder="Enter new category name (e.g. Photography, 3D Art)">
                                        </div>
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
                                        <p class="field-hint">Recommended size: ~800x600px, PNG or JPG format</p>
                                    </div>
                                    <div class="form-group-admin">
                                        <label>Display Sorting Order</label>
                                        <input type="number" name="order_index" class="form-control-admin" value="1" min="1">
                                        <p class="field-hint">Auto-shifts existing cards down when inserting into an existing position.</p>
                                    </div>
                                    <div class="form-group-admin form-full-width launching-soon-config-box">
                                        <label class="launching-soon-label">
                                            <input type="checkbox" name="is_launching_soon" value="1" class="launching-soon-checkbox">
                                            <div>
                                                <div class="launching-soon-title-wrap">
                                                    <span>Mark as "Launching Soon" (Under Active Construction)</span>
                                                    <span class="launching-soon-modal-tag">In-Page 80% Screen Modal</span>
                                                </div>
                                                <p class="launching-soon-hint">
                                                    When enabled, clicking this card on the website opens a luxury in-page Launching Soon preview modal instead of navigating to an external link.
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="drawer-actions">
                                    <button type="button" class="btn-save btn-cancel" onclick="toggleAddDrawer('addPortfolioDrawer')">Cancel</button>
                                    <button type="submit" class="btn-save">
                                        <i data-lucide="plus" size="16"></i>
                                        <span>Create Showcase Item</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Current Portfolio Items list -->
                        <div class="crud-list" id="portfolioCrudList">
                            <?php foreach ($portfolio as $item): ?>
                                <div class="crud-item portfolio-crud-card" id="portfolio-<?php echo $item['id']; ?>" data-category="<?php echo htmlspecialchars($item['category']); ?>">
                                    <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="Showcase Preview" class="item-image-preview">
                                    <div class="crud-info">
                                        <h4>
                                            <span><?php echo htmlspecialchars($item['title']); ?></span>
                                            <span class="crud-meta-badge badge-cat-glow"><?php echo htmlspecialchars($categoryMap[$item['category']] ?? $item['category']); ?></span>
                                        </h4>
                                        <p class="port-item-tag"><?php echo htmlspecialchars($item['tag']); ?></p>
                                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                                        <p class="port-item-link">Link: <?php echo htmlspecialchars($item['link']); ?></p>
                                        <div class="port-item-meta-row">
                                            <span class="crud-meta-badge">Order: <?php echo $item['order_index']; ?></span>
                                            <?php if (!empty($item['is_launching_soon'])): ?>
                                                <span class="crud-meta-badge badge-launching-soon">🚀 Launching Soon Modal</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Edit Form -->
                                        <div class="edit-form-wrapper" id="edit-port-form-<?php echo $item['id']; ?>">
                                            <form action="?tab=portfolio" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="action" value="portfolio_edit">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($item['image_path']); ?>">
                                                
                                                <div class="form-grid">
                                                    <div class="form-group-admin">
                                                        <div class="cat-select-header">
                                                            <label class="cat-select-label">Display Category Tab</label>
                                                            <button type="button" onclick="showNewCategoryInput('<?php echo $item['id']; ?>')" class="btn-add-cat-link">+ Add New Category</button>
                                                        </div>
                                                        <select name="category" id="portfolioCategorySelect_<?php echo $item['id']; ?>" class="form-control-admin bg-dark-select" onchange="handleCategorySelectChange(this, '<?php echo $item['id']; ?>')" required>
                                                            <?php foreach ($portfolio_categories as $cat): ?>
                                                                <option value="<?php echo htmlspecialchars($cat['slug']); ?>" <?php echo ($item['category'] === $cat['slug']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                            <option value="__new__">+ Add New Category...</option>
                                                        </select>
                                                        <div id="newCategoryInput_<?php echo $item['id']; ?>" class="new-cat-input-wrap">
                                                            <input type="text" name="new_category_name" class="form-control-admin new-cat-input" placeholder="Enter new category name (e.g. Photography, 3D Art)">
                                                        </div>
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
                                                            <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="Current preview" class="img-thumb-50">
                                                            <input type="file" name="image" class="form-control-admin" accept="image/*">
                                                        </div>
                                                    </div>
                                                    <div class="form-group-admin">
                                                        <label>Display Sorting Order</label>
                                                        <input type="number" name="order_index" class="form-control-admin" value="<?php echo $item['order_index']; ?>" min="1">
                                                        <p class="field-hint">E.g., changing from 15 to 10 will auto-shift cards from 10 onward down to 11, 12, etc.</p>
                                                    </div>
                                                    <div class="form-group-admin form-full-width launching-soon-config-box">
                                                        <label class="launching-soon-label">
                                                            <input type="checkbox" name="is_launching_soon" value="1" <?php echo !empty($item['is_launching_soon']) ? 'checked' : ''; ?> class="launching-soon-checkbox">
                                                            <div>
                                                                <div class="launching-soon-title-wrap">
                                                                    <span>Mark as "Launching Soon" (Under Active Construction)</span>
                                                                    <span class="launching-soon-modal-tag">In-Page 80% Screen Modal</span>
                                                                </div>
                                                                <p class="launching-soon-hint">
                                                                    When enabled, clicking this card on the website opens a luxury in-page Launching Soon preview modal instead of navigating to an external link.
                                                                </p>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="drawer-actions">
                                                    <button type="button" class="btn-save btn-cancel" onclick="toggleEditForm('port', <?php echo $item['id']; ?>)">Cancel</button>
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

                    <!-- SUB-TAB 2: LAUNCHING SOON POSTER TEMPLATE -->
                    <div id="subtab-portfolio-template" class="subtab-section" data-subgroup="portfolioGroup">
                        <?php
                        $currentTemplateImg = $settings['launching_soon_template_image'] ?? 'assets/images/portfolio_launching_soon.jpg';
                        if (!file_exists('../' . $currentTemplateImg)) {
                            $currentTemplateImg = 'assets/images/portfolio_launching_soon.jpg';
                        }
                        ?>
                        <div class="template-banner-box">
                            <div class="template-banner-header">
                                <div class="cat-left-wrap">
                                    <div class="template-icon-wrap">
                                        <i data-lucide="sparkles" size="20"></i>
                                    </div>
                                    <div>
                                        <h4 class="template-title">
                                            Global "Launching Soon" Poster Template (പോസ്റ്റർ ടെംപ്ലേറ്റ്)
                                        </h4>
                                        <p class="template-desc">
                                            This template photo is displayed in the 80% screen preview modal for all projects marked as "Launching Soon". Upload a new custom edited photo here anytime.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="template-preview-grid">
                                <!-- Current Template Preview -->
                                <div class="template-thumbnail-wrap">
                                    <img src="../<?php echo htmlspecialchars($currentTemplateImg); ?>?v=<?php echo time(); ?>" alt="Active Template Preview" class="template-thumb-img">
                                    <span class="template-tag-badge">Active Poster</span>
                                </div>

                                <!-- Upload Form -->
                                <form action="?tab=portfolio" method="POST" enctype="multipart/form-data" class="template-upload-form">
                                    <input type="hidden" name="action" value="launching_soon_template_save">
                                    <div class="template-form-row">
                                        <input type="file" name="template_image" class="form-control-admin template-file-input" accept="image/*" required>
                                        <button type="submit" class="btn-save btn-template-upload">
                                            <i data-lucide="upload-cloud" size="16"></i>
                                            <span>Upload &amp; Apply Template</span>
                                        </button>
                                    </div>
                                    <span class="template-hint-text">
                                        Recommended: JPG, PNG, or WebP. Aspect ratio ~4:3 or ~16:9.
                                    </span>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- SUB-TAB 3: CATEGORY TABS & ORDER -->
                    <div id="subtab-portfolio-categories" class="subtab-section" data-subgroup="portfolioGroup">
                        <div class="cat-management-box">
                            <div class="cat-management-header">
                                <div>
                                    <div class="cat-title-wrap">
                                        <i data-lucide="layers" size="18" class="icon-accent"></i>
                                        <h4 class="cat-management-title">
                                            Portfolio Category Tabs &amp; Display Order
                                        </h4>
                                    </div>
                                    <p class="cat-management-subtitle">
                                        Set the order of tabs (e.g. 1 for Web Development, 2 for Video Production, 3 for Graphic Design). The category with the lowest order number is automatically displayed first as the default active tab.
                                    </p>
                                </div>
                                <button type="button" class="btn-save btn-add-cat-tab" onclick="toggleAddCategoryModal()">
                                    <i data-lucide="plus-circle" size="15"></i>
                                    <span>Add New Category Tab</span>
                                </button>
                            </div>

                            <!-- Category Tabs Reordering Form -->
                            <form action="?tab=portfolio" method="POST">
                                <input type="hidden" name="action" value="portfolio_categories_reorder">
                                <div class="cat-reorder-grid">
                                    <?php foreach ($portfolio_categories as $cIdx => $pCat): ?>
                                        <?php
                                        $itemCount = 0;
                                        foreach ($portfolio as $pItem) {
                                            if ($pItem['category'] === $pCat['slug']) $itemCount++;
                                        }
                                        $isFirst = ($cIdx === 0);
                                        ?>
                                        <div class="cat-reorder-card <?php echo $isFirst ? 'is-first' : ''; ?>">
                                            <div class="cat-left-wrap">
                                                <!-- Order Input -->
                                                <div class="cat-order-col">
                                                    <label class="cat-order-label">Order</label>
                                                    <input type="number" name="category_orders[<?php echo $pCat['id']; ?>]" value="<?php echo $pCat['order_index']; ?>" class="form-control-admin cat-order-input <?php echo $isFirst ? 'is-first' : ''; ?>" title="Set display order (e.g. 1 for Web Development)">
                                                </div>

                                                <!-- Up / Down Quick Buttons -->
                                                <div class="cat-arrow-col">
                                                    <button type="submit" formaction="?tab=portfolio" name="action" value="portfolio_category_move" onclick="document.getElementById('moveCatId').value='<?php echo $pCat['id']; ?>'; document.getElementById('moveCatDir').value='up';" class="btn-arrow-move" title="Move Up">
                                                        <i data-lucide="chevron-up" size="12"></i>
                                                    </button>
                                                    <button type="submit" formaction="?tab=portfolio" name="action" value="portfolio_category_move" onclick="document.getElementById('moveCatId').value='<?php echo $pCat['id']; ?>'; document.getElementById('moveCatDir').value='down';" class="btn-arrow-move" title="Move Down">
                                                        <i data-lucide="chevron-down" size="12"></i>
                                                    </button>
                                                </div>

                                                <!-- Name & Stats -->
                                                <div>
                                                    <div class="cat-info-row">
                                                        <span class="cat-name-text"><?php echo htmlspecialchars($pCat['name']); ?></span>
                                                        <?php if ($isFirst): ?>
                                                            <span class="badge-active-tab-1st">1st Active Tab</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="cat-meta-sub">
                                                        <span>slug: <code><?php echo htmlspecialchars($pCat['slug']); ?></code></span>
                                                        <span>&bull;</span>
                                                        <span class="text-accent"><?php echo $itemCount; ?> projects</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Category Actions -->
                                            <div class="cat-actions-row">
                                                <button type="button" onclick="openEditCategoryModal(<?php echo $pCat['id']; ?>, '<?php echo htmlspecialchars(addslashes($pCat['name'])); ?>', <?php echo (int)$pCat['order_index']; ?>)" class="cat-action-btn" title="Edit Name/Order">
                                                    <i data-lucide="edit-2" size="12"></i>
                                                </button>
                                                <?php if ($itemCount === 0 && !in_array($pCat['slug'], ['video', 'web', 'design'])): ?>
                                                    <button type="submit" formaction="?tab=portfolio" name="action" value="portfolio_category_delete" onclick="document.getElementById('deleteCatId').value='<?php echo $pCat['id']; ?>'; return confirm('Delete category <?php echo htmlspecialchars($pCat['name']); ?>?');" class="cat-delete-btn" title="Delete category">
                                                        <i data-lucide="trash-2" size="12"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <input type="hidden" name="category_id" id="moveCatId" value="">
                                <input type="hidden" name="direction" id="moveCatDir" value="up">
                                <input type="hidden" name="category_id" id="deleteCatId" value="">

                                <div class="cat-save-order-bar">
                                    <span class="cat-save-hint">Change numbers above and click to update live order:</span>
                                    <button type="submit" class="btn-save btn-save-order">
                                        <i data-lucide="check" size="16"></i>
                                        <span>Save Tab Display Order</span>
                                    </button>
                                </div>
                            </form>

                            <!-- Collapsible Quick Add Category Form -->
                            <div id="quickAddCategoryBox" class="cat-quick-box">
                                <h5 class="cat-quick-title">Create New Category Tab:</h5>
                                <form action="?tab=portfolio" method="POST" class="cat-quick-form">
                                    <input type="hidden" name="action" value="portfolio_category_add">
                                    <input type="text" name="category_name" class="form-control-admin cat-quick-input-name" placeholder="Category Name (e.g. Photography, Mobile Apps)" required>
                                    <input type="number" name="order_index" class="form-control-admin cat-quick-input-order" placeholder="Order (e.g. 4)" value="<?php echo count($portfolio_categories) + 1; ?>">
                                    <button type="submit" class="btn-save btn-quick-cat">Create Tab</button>
                                    <button type="button" class="btn-save btn-quick-cat-cancel" onclick="toggleAddCategoryModal()">Cancel</button>
                                </form>
                            </div>

                            <!-- Edit Category Modal / Box -->
                            <div id="editCategoryModalBox" class="cat-quick-box">
                                <h5 class="cat-quick-title">Edit Category Tab Details:</h5>
                                <form action="?tab=portfolio" method="POST" class="cat-quick-form">
                                    <input type="hidden" name="action" value="portfolio_category_edit">
                                    <input type="hidden" name="category_id" id="editCatIdInput" value="">
                                    <input type="text" name="category_name" id="editCatNameInput" class="form-control-admin cat-quick-input-name" placeholder="Category Name" required>
                                    <input type="number" name="order_index" id="editCatOrderInput" class="form-control-admin cat-quick-input-order" placeholder="Order Index" required>
                                    <button type="submit" class="btn-save btn-quick-cat">Update Category</button>
                                    <button type="button" class="btn-save btn-quick-cat-cancel" onclick="document.getElementById('editCategoryModalBox').style.display='none'">Cancel</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ================= TAB 4: ABOUT & TEAM ================= -->
            <?php if ($active_tab === 'about'): ?>
                <div class="glass-panel">
                    <div class="admin-panel-header-bar mb-22">
                        <div>
                            <h3 class="glass-panel-title title-mb-5">
                                <i data-lucide="users" size="20" class="icon-accent"></i>
                                <span>About Us &amp; Leadership Team</span>
                            </h3>
                            <p class="admin-header-subtitle">Manage your agency leadership profiles and brand story text without lengthy vertical scrolling.</p>
                        </div>
                        <div class="admin-subnav" data-group="aboutGroup">
                            <button type="button" class="admin-subnav-btn active" onclick="switchAdminSubtab('subtab-about-team', this)">
                                <i data-lucide="users" size="15"></i>
                                <span>Leadership Team (<?php echo count($team); ?>)</span>
                            </button>
                            <button type="button" class="admin-subnav-btn" onclick="switchAdminSubtab('subtab-about-text', this)">
                                <i data-lucide="file-text" size="15"></i>
                                <span>Philosophy &amp; Story Text</span>
                            </button>
                        </div>
                    </div>

                    <!-- SUB-TAB 1: LEADERSHIP TEAM -->
                    <div id="subtab-about-team" class="subtab-section active" data-subgroup="aboutGroup">
                        <div class="admin-panel-header-bar mb-18">
                            <div class="admin-search-wrap">
                                <i data-lucide="search" size="15" class="admin-search-icon"></i>
                                <input type="text" placeholder="Search team members..." oninput="filterAdminCards('teamCrudList', this.value)" class="admin-search-input">
                            </div>
                            <button type="button" class="btn-add-toggle" onclick="toggleAddDrawer('addTeamDrawer', this)">
                                <i data-lucide="plus" size="15"></i>
                                <span>Add Team Member</span>
                            </button>
                        </div>

                        <!-- Collapsible Add Team Member Drawer -->
                        <div id="addTeamDrawer" class="collapsible-drawer-box">
                            <h4 class="drawer-title">
                                <i data-lucide="user-plus" size="16"></i>
                                <span>Create New Team Member</span>
                            </h4>
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
                                <div class="drawer-actions">
                                    <button type="button" class="btn-save btn-cancel" onclick="toggleAddDrawer('addTeamDrawer')">Cancel</button>
                                    <button type="submit" class="btn-save">
                                        <i data-lucide="plus" size="16"></i>
                                        <span>Create Member</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Current Team list -->
                        <div class="crud-list" id="teamCrudList">
                            <?php foreach ($team as $member): ?>
                                <div class="crud-item" id="team-<?php echo $member['id']; ?>">
                                    <img src="../<?php echo htmlspecialchars($member['image_path']); ?>" alt="Portrait" class="item-image-preview">
                                    <div class="crud-info">
                                        <h4><span><?php echo htmlspecialchars($member['name']); ?></span></h4>
                                        <p class="team-member-role"><?php echo htmlspecialchars($member['role']); ?></p>
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
                                                            <img src="../<?php echo htmlspecialchars($member['image_path']); ?>" alt="Current preview" class="img-thumb-50">
                                                            <input type="file" name="image" class="form-control-admin" accept="image/*">
                                                        </div>
                                                    </div>
                                                    <div class="form-group-admin">
                                                        <label>Display Sorting Order</label>
                                                        <input type="number" name="order_index" class="form-control-admin" value="<?php echo $member['order_index']; ?>">
                                                    </div>
                                                </div>
                                                <div class="drawer-actions">
                                                    <button type="button" class="btn-save btn-cancel" onclick="toggleEditForm('team', <?php echo $member['id']; ?>)">Cancel</button>
                                                    <button type="submit" class="btn-save">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="crud-actions">
                                        <button class="btn-icon" onclick="toggleEditForm('team', <?php echo $member['id']; ?>)" title="Edit Member">
                                            <i data-lucide="edit-3" size="16"></i>
                                        </button>
                                        <form action="?tab=about" method="POST" onsubmit="return confirm('Are you sure you want to delete this team member?');">
                                            <input type="hidden" name="action" value="team_delete">
                                            <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                                            <button type="submit" class="btn-icon btn-delete" title="Delete Member">
                                                <i data-lucide="trash-2" size="16"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- SUB-TAB 2: PHILOSOPHY & ABOUT TEXT -->
                    <div id="subtab-about-text" class="subtab-section" data-subgroup="aboutGroup">
                        <form action="?tab=about" method="POST">
                            <input type="hidden" name="action" value="update_settings">
                            
                            <div class="form-grid">
                                <h4 class="form-full-width section-heading-accent">CREATIVE PHILOSOPHY BLOCK</h4>
                                
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

                                <h4 class="form-full-width section-heading-accent mt-20">ABOUT COMPANY PROFILE</h4>
                                
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

                                <h4 class="form-full-width section-heading-accent mt-20">MD LEADERSHIP TESTIMONIAL</h4>
                                
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
                            
                            <div class="admin-actions-right">
                                <button type="submit" class="btn-save">
                                    <i data-lucide="save" size="18"></i>
                                    <span>Save About Settings</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ================= TAB 5: FAQ ACCORDION ================= -->
            <?php if ($active_tab === 'faq'): ?>
                <div class="glass-panel">
                    <div class="admin-panel-header-bar mb-20">
                        <div>
                            <h3 class="glass-panel-title title-mb-5">
                                <i data-lucide="help-circle" size="20" class="icon-accent"></i>
                                <span>Manage FAQ Accordion</span>
                            </h3>
                            <p class="admin-header-subtitle">Total FAQs: <strong class="text-white"><?php echo count($faqs); ?></strong></p>
                        </div>
                        <div class="admin-actions-flex">
                            <div class="admin-search-wrap search-w-250">
                                <i data-lucide="search" size="15" class="admin-search-icon"></i>
                                <input type="text" placeholder="Search FAQs..." oninput="filterAdminCards('faqCrudList', this.value)" class="admin-search-input">
                            </div>
                            <button type="button" class="btn-add-toggle" onclick="toggleAddDrawer('addFaqDrawer', this)">
                                <i data-lucide="plus" size="15"></i>
                                <span>Add New FAQ</span>
                            </button>
                        </div>
                    </div>

                    <!-- Collapsible Add FAQ Drawer -->
                    <div id="addFaqDrawer" class="collapsible-drawer-box">
                        <h4 class="drawer-title">
                            <i data-lucide="plus-circle" size="16"></i>
                            <span>Add New Accordion FAQ</span>
                        </h4>
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
                            <div class="drawer-actions">
                                <button type="button" class="btn-save btn-cancel" onclick="toggleAddDrawer('addFaqDrawer')">Cancel</button>
                                <button type="submit" class="btn-save">
                                    <i data-lucide="plus" size="16"></i>
                                    <span>Create FAQ</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Current FAQs list -->
                    <div class="crud-list" id="faqCrudList">
                        <?php foreach ($faqs as $faq): ?>
                            <div class="crud-item" id="faq-<?php echo $faq['id']; ?>">
                                <div class="crud-info">
                                    <h4 class="faq-question-title"><?php echo htmlspecialchars($faq['question']); ?></h4>
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
                                            <div class="drawer-actions">
                                                <button type="button" class="btn-save btn-cancel" onclick="toggleEditForm('faq', <?php echo $faq['id']; ?>)">Cancel</button>
                                                <button type="submit" class="btn-save">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="crud-actions">
                                    <button class="btn-icon" onclick="toggleEditForm('faq', <?php echo $faq['id']; ?>)" title="Edit FAQ">
                                        <i data-lucide="edit-3" size="16"></i>
                                    </button>
                                    <form action="?tab=faq" method="POST" onsubmit="return confirm('Are you sure you want to delete this FAQ item?');">
                                        <input type="hidden" name="action" value="faq_delete">
                                        <input type="hidden" name="id" value="<?php echo $faq['id']; ?>">
                                        <button type="submit" class="btn-icon btn-delete" title="Delete FAQ">
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
                            
                            <div class="form-group-admin form-full-width security-confirm-group">
                                <label class="label-danger">Confirm current Decryption Password to apply</label>
                                <input type="password" name="current_password" class="form-control-admin" placeholder="Enter current password to save..." required>
                            </div>
                        </div>
                        
                        <div class="admin-actions-right">
                            <button type="submit" class="btn-save btn-accent-solid">
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
                    <div class="admin-panel-header-bar mb-20">
                        <div>
                            <h3 class="glass-panel-title title-mb-5">
                                <i data-lucide="handshake" size="20" class="icon-accent"></i>
                                <span>Manage Collaborators &amp; Brand Logos</span>
                            </h3>
                            <p class="admin-header-subtitle">Total Brands: <strong class="text-white"><?php echo count($collaborators_list); ?></strong></p>
                        </div>
                        <div class="admin-actions-flex">
                            <div class="admin-search-wrap search-w-250">
                                <i data-lucide="search" size="15" class="admin-search-icon"></i>
                                <input type="text" placeholder="Search brands..." oninput="filterAdminCards('collabCrudList', this.value)" class="admin-search-input">
                            </div>
                            <button type="button" class="btn-add-toggle" onclick="toggleAddDrawer('addCollabDrawer', this)">
                                <i data-lucide="plus" size="15"></i>
                                <span>Add Collaborator Logo</span>
                            </button>
                        </div>
                    </div>

                    <!-- Collapsible Add Collaborator Box -->
                    <div id="addCollabDrawer" class="collapsible-drawer-box">
                        <h4 class="drawer-title">
                            <i data-lucide="plus-circle" size="16"></i>
                            <span>Add New Collaborator Brand Logo</span>
                        </h4>
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
                                    <p class="field-hint">Preferably white/grey monochromatic transparent PNG logos, ~200x80px.</p>
                                </div>
                            </div>
                            <div class="drawer-actions">
                                <button type="button" class="btn-save btn-cancel" onclick="toggleAddDrawer('addCollabDrawer')">Cancel</button>
                                <button type="submit" class="btn-save">
                                    <i data-lucide="plus" size="16"></i>
                                    <span>Create Collaborator</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Current Collaborators list -->
                    <div class="crud-list" id="collabCrudList">
                        <?php foreach ($collaborators_list as $collab): ?>
                            <div class="crud-item" id="collab-<?php echo $collab['id']; ?>">
                                <img src="../<?php echo htmlspecialchars($collab['image_path']); ?>" alt="Logo Preview" class="item-image-preview collab-logo-preview">
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
                                                        <img src="../<?php echo htmlspecialchars($collab['image_path']); ?>" alt="Current preview" class="collab-logo-edit-preview">
                                                        <input type="file" name="image" class="form-control-admin" accept="image/*">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="drawer-actions">
                                                <button type="button" class="btn-save btn-cancel" onclick="toggleEditForm('collab', <?php echo $collab['id']; ?>)">Cancel</button>
                                                <button type="submit" class="btn-save">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="crud-actions">
                                    <button class="btn-icon" onclick="toggleEditForm('collab', <?php echo $collab['id']; ?>)" title="Edit Collaborator">
                                        <i data-lucide="edit-3" size="16"></i>
                                    </button>
                                    <form action="?tab=collaborators" method="POST" onsubmit="return confirm('Are you sure you want to delete this collaborator?');">
                                        <input type="hidden" name="action" value="collab_delete">
                                        <input type="hidden" name="id" value="<?php echo $collab['id']; ?>">
                                        <button type="submit" class="btn-icon btn-delete" title="Delete Collaborator">
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

        <!-- Live Front-End Preview Split Pane (Requested by User) -->
        <aside class="admin-preview-pane" id="adminPreviewPane">
            <!-- Header Toolbar -->
            <div class="preview-pane-header">
                <div class="preview-pane-title">
                    <span class="preview-live-pulse"></span>
                    <span>Live Preview</span>
                </div>

                <div class="preview-pane-controls">
                    <!-- Prominent Refresh Button as requested -->
                    <button type="button" class="preview-control-btn btn-refresh" id="previewRefreshBtn" onclick="refreshAdminPreview()" title="Refresh frontend preview to see saved changes">
                        <i data-lucide="rotate-cw" size="13"></i>
                        <span>Refresh</span>
                    </button>

                    <!-- Device Switcher: Desktop/PC vs Mobile ONLY -->
                    <div class="preview-device-segmented">
                        <button type="button" class="preview-device-btn active" id="pdevDesktop" onclick="setPreviewDevice('desktop', this)" title="PC / Desktop View">
                            <i data-lucide="monitor" size="13"></i>
                            <span>Desktop</span>
                        </button>
                        <button type="button" class="preview-device-btn" id="pdevMobile" onclick="setPreviewDevice('mobile', this)" title="Mobile View">
                            <i data-lucide="smartphone" size="13"></i>
                            <span>Mobile</span>
                        </button>
                    </div>

                    <!-- Open in New Tab -->
                    <a href="../index.php" target="_blank" class="preview-control-btn" title="Open full site in new tab">
                        <i data-lucide="external-link" size="14"></i>
                    </a>

                    <!-- Close / Collapse Preview Pane -->
                    <button type="button" class="preview-control-btn btn-close-preview" onclick="closeAdminPreview()" title="Close Live Preview">
                        <i data-lucide="x" size="14"></i>
                    </button>
                </div>
            </div>

            <!-- Jump to Section Quick Bar -->
            <div class="preview-quick-bar">
                <span class="preview-jump-tag"><i data-lucide="compass" size="12"></i> Jump:</span>
                <button type="button" class="preview-jump-btn <?php echo empty($active_tab) || $active_tab === 'global' ? 'active' : ''; ?>" onclick="jumpPreviewTo('', this)">Top</button>
                <button type="button" class="preview-jump-btn <?php echo $active_tab === 'services' ? 'active' : ''; ?>" onclick="jumpPreviewTo('services', this)">Services</button>
                <button type="button" class="preview-jump-btn <?php echo $active_tab === 'portfolio' ? 'active' : ''; ?>" onclick="jumpPreviewTo('portfolio', this)">Portfolio</button>
                <button type="button" class="preview-jump-btn <?php echo $active_tab === 'about' ? 'active' : ''; ?>" onclick="jumpPreviewTo('about', this)">About</button>
                <button type="button" class="preview-jump-btn <?php echo $active_tab === 'faq' ? 'active' : ''; ?>" onclick="jumpPreviewTo('faq', this)">FAQ</button>
                <button type="button" class="preview-jump-btn" onclick="jumpPreviewTo('contact', this)">Pricing &amp; Contact</button>
            </div>

            <!-- Studio Canvas with Browser Window Mockup Setup -->
            <div class="preview-frame-wrapper" id="previewFrameWrapper">
                <div class="preview-loading-overlay" id="previewLoadingOverlay">
                    <div class="preview-spinner"></div>
                    <span class="preview-spinner-text">Updating Live Site...</span>
                </div>

                <div class="preview-mockup-frame mode-desktop" id="previewMockupFrame">
                    <!-- Browser Window Top Chrome -->
                    <div class="mockup-window-bar">
                        <div class="mockup-traffic-dots">
                            <span class="traffic-dot red"></span>
                            <span class="traffic-dot yellow"></span>
                            <span class="traffic-dot green"></span>
                        </div>
                        <div class="mockup-mobile-speaker"></div>
                        <div class="mockup-url-pill">
                            <i data-lucide="lock" size="11" class="icon-emerald"></i>
                            <span>perumalilcreative.com/<?php echo htmlspecialchars($active_tab); ?></span>
                        </div>
                    </div>

                    <!-- Browser Mockup Viewport -->
                    <div class="mockup-viewport" id="mockupViewport">
                        <iframe id="livePreviewIframe" class="preview-iframe" src="../index.php<?php 
                            if ($active_tab === 'services') echo '#services';
                            elseif ($active_tab === 'portfolio') echo '#portfolio';
                            elseif ($active_tab === 'about') echo '#about';
                            elseif ($active_tab === 'faq') echo '#faq';
                        ?>" title="Live Perumalil Creative Preview"></iframe>
                    </div>

                    <!-- Mobile Bottom Home Indicator Bar -->
                    <div class="mockup-mobile-homebar"></div>
                </div>
            </div>
        </aside>

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
                        <p><a id="modalClientEmail" href="" class="modal-accent-link"></a></p>
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
                    <label class="modal-message-label">Goal Blueprint &amp; Message</label>
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

        // Portfolio Category Dynamic Selection Helpers
        function toggleAddCategoryModal() {
            const box = document.getElementById('quickAddCategoryBox');
            if (box) {
                const isHidden = (box.style.display === 'none' || box.style.display === '');
                box.style.display = isHidden ? 'block' : 'none';
                if (isHidden) {
                    const input = box.querySelector('input[name="category_name"]');
                    if (input) input.focus();
                }
            }
        }

        function handleCategorySelectChange(selectElem, contextId) {
            const newCatWrapper = document.getElementById('newCategoryInput_' + contextId);
            if (!newCatWrapper) return;
            if (selectElem.value === '__new__') {
                newCatWrapper.style.display = 'block';
                const input = newCatWrapper.querySelector('input');
                if (input) {
                    input.required = true;
                    input.focus();
                }
            } else {
                newCatWrapper.style.display = 'none';
                const input = newCatWrapper.querySelector('input');
                if (input) {
                    input.required = false;
                }
            }
        }

        function showNewCategoryInput(contextId) {
            let selectElem;
            if (contextId === 'add') {
                selectElem = document.getElementById('portfolioAddCategorySelect');
            } else {
                selectElem = document.getElementById('portfolioCategorySelect_' + contextId.replace('edit_', ''));
            }
            if (selectElem) {
                selectElem.value = '__new__';
                handleCategorySelectChange(selectElem, contextId);
            }
        }

        function openEditCategoryModal(id, name, order) {
            const box = document.getElementById('editCategoryModalBox');
            if (box) {
                document.getElementById('editCatIdInput').value = id;
                document.getElementById('editCatNameInput').value = name;
                document.getElementById('editCatOrderInput').value = order;
                box.style.display = 'block';
                box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                document.getElementById('editCatNameInput').focus();
            }
        }

        // Sub-tabs switcher helpers
        function switchAdminSubtab(arg1, arg2, arg3) {
            let nav, targetSectionId, activeBtn;
            if (typeof arg2 === 'string') {
                // Called as switchAdminSubtab(navId, targetSectionId, activeBtn)
                nav = document.getElementById(arg1);
                targetSectionId = arg2;
                activeBtn = arg3;
            } else {
                // Called as switchAdminSubtab(targetSectionId, activeBtn)
                targetSectionId = arg1;
                activeBtn = arg2;
                if (activeBtn) {
                    nav = activeBtn.closest('.admin-subnav');
                }
            }

            if (nav && activeBtn) {
                nav.querySelectorAll('.admin-subnav-btn').forEach(btn => btn.classList.remove('active'));
                activeBtn.classList.add('active');
            } else if (activeBtn) {
                const parent = activeBtn.parentElement;
                if (parent) {
                    parent.querySelectorAll('.admin-subnav-btn').forEach(btn => btn.classList.remove('active'));
                }
                activeBtn.classList.add('active');
            }

            const targetSec = document.getElementById(targetSectionId);
            let groupName = nav ? nav.getAttribute('data-group') : '';
            if (!groupName && targetSec) {
                groupName = targetSec.getAttribute('data-subgroup');
            }

            if (groupName) {
                document.querySelectorAll(`.subtab-section[data-subgroup="${groupName}"]`).forEach(sec => {
                    sec.style.display = 'none';
                    sec.classList.remove('active');
                });
            } else if (targetSec && targetSec.parentElement) {
                targetSec.parentElement.querySelectorAll('.subtab-section').forEach(sec => {
                    sec.style.display = 'none';
                    sec.classList.remove('active');
                });
            }

            if (targetSec) {
                targetSec.style.display = 'block';
                targetSec.classList.add('active');
                if (targetSectionId && targetSectionId.startsWith('global_sec_')) {
                    const subVal = targetSectionId.replace('global_sec_', '');
                    const subInput = document.getElementById('globalCurrentSubtab');
                    if (subInput) subInput.value = subVal;
                }
            }
            if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
        }

        // Drawer toggle helper
        function toggleAddDrawer(drawerId, btn) {
            const drawer = document.getElementById(drawerId);
            if (!drawer) return;
            const isHidden = (drawer.style.display === 'none' || drawer.style.display === '');
            drawer.style.display = isHidden ? 'block' : 'none';
            if (btn) {
                const span = btn.querySelector('span');
                if (isHidden) {
                    if (span && !span.getAttribute('data-orig-text')) {
                        span.setAttribute('data-orig-text', span.textContent);
                    }
                    if (span) span.textContent = 'Close Form';
                } else {
                    if (span && span.getAttribute('data-orig-text')) {
                        span.textContent = span.getAttribute('data-orig-text');
                    }
                }
            }
            if (isHidden) {
                drawer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                const firstInput = drawer.querySelector('input:not([type="hidden"]), textarea, select');
                if (firstInput) firstInput.focus();
            }
            if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
        }

        // Live Search / Filter Cards Helper
        function filterAdminCards(containerId, query) {
            const container = document.getElementById(containerId);
            if (!container) return;
            const q = query.toLowerCase().trim();
            const cards = container.querySelectorAll('.crud-item, .compact-card');
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = (q === '' || text.includes(q)) ? '' : 'none';
            });
        }

        // Filter Portfolio Cards by Category Tab
        function filterPortfolioByCategory(categorySlug, activeBtn) {
            const bar = activeBtn ? activeBtn.closest('.category-filter-bar') : null;
            if (bar) {
                bar.querySelectorAll('.cat-filter-btn').forEach(btn => btn.classList.remove('active'));
            }
            if (activeBtn) activeBtn.classList.add('active');

            const cards = document.querySelectorAll('.portfolio-crud-card');
            cards.forEach(card => {
                const cardCat = card.getAttribute('data-category');
                if (categorySlug === 'all' || cardCat === categorySlug) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Inquiries Table Filtering Helper
        let currentInquiryStatusFilter = 'all';
        let currentInquirySearchQuery = '';

        function filterInquiriesByStatus(status, btn) {
            currentInquiryStatusFilter = status;
            if (btn) {
                const bar = btn.closest('.category-filter-bar');
                if (bar) bar.querySelectorAll('.cat-filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            }
            applyInquiriesFilter();
        }

        function filterInquiriesBySearch(q) {
            currentInquirySearchQuery = q.toLowerCase().trim();
            applyInquiriesFilter();
        }

        function applyInquiriesFilter() {
            const rows = document.querySelectorAll('.inquiry-row');
            rows.forEach(r => {
                const status = (r.getAttribute('data-status') || '').toLowerCase();
                const text = r.textContent.toLowerCase();
                const matchesStatus = (currentInquiryStatusFilter === 'all' || status === currentInquiryStatusFilter.toLowerCase());
                const matchesSearch = (currentInquirySearchQuery === '' || text.includes(currentInquirySearchQuery));
                r.style.display = (matchesStatus && matchesSearch) ? '' : 'none';
            });
        }

        // ================= Live Frontend Preview Logic =================
        function toggleAdminPreview() {
            const container = document.querySelector('.admin-container');
            const btnText = document.getElementById('previewToggleBtnText');
            if (!container) return;
            const isNowHidden = container.classList.toggle('preview-hidden');
            const isVisible = !isNowHidden;
            localStorage.setItem('admin_preview_visible', isVisible ? 'true' : 'false');
            if (btnText) {
                btnText.textContent = isVisible ? 'Hide Live Preview' : 'Show Live Preview';
            }
            if (window.lucide) {
                lucide.createIcons();
            }
        }

        function closeAdminPreview() {
            const container = document.querySelector('.admin-container');
            const btnText = document.getElementById('previewToggleBtnText');
            if (container) {
                container.classList.add('preview-hidden');
            }
            localStorage.setItem('admin_preview_visible', 'false');
            if (btnText) {
                btnText.textContent = 'Show Live Preview';
            }
            if (window.lucide) {
                lucide.createIcons();
            }
        }

        // ================= Knowledge Base In-Page Re-Indexing & Toasts =================
        function showKnowledgeSyncToast(type, title, message) {
            const container = document.getElementById('adminToastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = 'admin-toast toast-' + (type || 'success');

            const iconName = type === 'error' ? 'alert-circle' : 'check-circle';
            toast.innerHTML = `
                <div class="admin-toast-icon">
                    <i data-lucide="${iconName}" size="20"></i>
                </div>
                <div class="admin-toast-content">
                    <div class="admin-toast-title">${title}</div>
                    <div class="admin-toast-desc">${message}</div>
                </div>
                <button type="button" class="admin-toast-close" onclick="this.closest('.admin-toast').remove()" title="Close">
                    <i data-lucide="x" size="14"></i>
                </button>
            `;

            container.appendChild(toast);
            if (window.lucide) lucide.createIcons();

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
                setTimeout(() => toast.remove(), 300);
            }, 4500);
        }

        function triggerReindexKnowledge(btn) {
            if (!btn) return;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="rotate-cw" size="14" class="spinning"></i> <span>Studying Database...</span>';
            if (window.lucide) lucide.createIcons();

            fetch('../api/knowledge_indexer.php?_t=' + Date.now())
                .then(r => r.json())
                .then(data => {
                    if (data && data.success) {
                        const total = data.stats ? data.stats.searchable_entities : 88;
                        btn.style.background = 'rgba(16, 185, 129, 0.2)';
                        btn.style.borderColor = 'rgba(16, 185, 129, 0.6)';
                        btn.style.color = '#10b981';
                        btn.innerHTML = '<i data-lucide="check" size="14"></i> <span>Indexed ' + total + ' Items!</span>';
                        if (window.lucide) lucide.createIcons();

                        showKnowledgeSyncToast('success', 'Database Synchronized!', 'Nova AI has memorized all ' + total + ' records across services, pricing, projects, and studio details.');
                    } else {
                        throw new Error((data && data.message) || 'Indexing failed');
                    }
                })
                .catch(err => {
                    btn.style.background = 'rgba(239, 68, 68, 0.2)';
                    btn.style.borderColor = 'rgba(239, 68, 68, 0.6)';
                    btn.style.color = '#ef4444';
                    btn.innerHTML = '<i data-lucide="alert-circle" size="14"></i> <span>Sync Failed</span>';
                    if (window.lucide) lucide.createIcons();
                    showKnowledgeSyncToast('error', 'Sync Failed', err.message || 'Could not index database');
                })
                .finally(() => {
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.style.background = '';
                        btn.style.borderColor = '';
                        btn.style.color = '';
                        btn.innerHTML = originalHtml;
                        if (window.lucide) lucide.createIcons();
                    }, 3500);
                });
        }

        // ================= AI Assistant (Nova) Tab Handlers =================
        function onAiToggleChanged(checkbox) {
            const isChecked = checkbox.checked;
            const card = document.getElementById('aiMasterCard');
            const icon = document.getElementById('aiPowerIcon');
            const pill = document.getElementById('aiGlobalStatusPill');
            const pillText = document.getElementById('aiGlobalStatusText');
            const headline = document.getElementById('aiToggleHeadlineText');
            const explainer = document.getElementById('aiToggleExplainer');

            if (isChecked) {
                if (card) {
                    card.classList.remove('disabled');
                    card.classList.add('active');
                }
                if (icon) {
                    icon.setAttribute('data-lucide', 'power');
                    icon.style.color = '#10b981';
                }
                if (pill) {
                    pill.className = 'ai-status-pill status-active';
                }
                if (pillText) {
                    pillText.textContent = 'LIVE & ACTIVE ON WEBSITE';
                }
                if (headline) {
                    headline.textContent = 'AI Assistant is Currently Active on Live Website';
                }
                if (explainer) {
                    explainer.textContent = 'The animated Nova robot widget and "Chat with Nova" Hero badges are currently visible and serving client inquiries on perumalilcreative.com.';
                }
            } else {
                if (card) {
                    card.classList.remove('active');
                    card.classList.add('disabled');
                }
                if (icon) {
                    icon.setAttribute('data-lucide', 'power-off');
                    icon.style.color = '#ef4444';
                }
                if (pill) {
                    pill.className = 'ai-status-pill status-disabled';
                }
                if (pillText) {
                    pillText.textContent = 'OFFLINE / DISABLED';
                }
                if (headline) {
                    headline.textContent = 'AI Assistant is Disabled (Hidden from Visitors)';
                }
                if (explainer) {
                    explainer.textContent = 'The AI companion is completely hidden and disabled from the live website. Visitors cannot see or trigger the chatbot.';
                }
            }

            if (window.lucide) lucide.createIcons();

            // Real-time update live preview iframe if open
            try {
                const iframe = document.getElementById('livePreviewIframe');
                if (iframe && iframe.contentDocument) {
                    const aiWidget = iframe.contentDocument.getElementById('aiAssistantWidget');
                    if (aiWidget) {
                        aiWidget.style.display = isChecked ? '' : 'none';
                    }
                    const heroBadge = iframe.contentDocument.getElementById('heroAiTriggerBtn');
                    if (heroBadge) {
                        heroBadge.style.display = isChecked ? '' : 'none';
                    }
                }
            } catch (eIframe) {}
        }

        function toggleApiKeyMask() {
            const input = document.getElementById('geminiApiKeyInput');
            const icon = document.getElementById('eyeIconApiKey');
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                if (icon) icon.setAttribute('data-lucide', 'eye');
            }
            if (window.lucide) lucide.createIcons();
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        function sendPlaygroundMsg() {
            const input = document.getElementById('playgroundInput');
            const chatBox = document.getElementById('playgroundChatBox');
            const btn = document.getElementById('btnPlaygroundSend');
            if (!input || !chatBox) return;

            const query = input.value.trim();
            if (!query) return;

            // Append user message
            const userMsgDiv = document.createElement('div');
            userMsgDiv.className = 'playground-msg user-msg';
            userMsgDiv.innerHTML = `
                <div class="msg-avatar"><i data-lucide="user" size="14"></i></div>
                <div class="msg-body">${escapeHtml(query)}</div>
            `;
            chatBox.appendChild(userMsgDiv);
            input.value = '';

            // Typing indicator
            const typingDiv = document.createElement('div');
            typingDiv.className = 'playground-msg bot-msg';
            typingDiv.id = 'playgroundTyping';
            typingDiv.innerHTML = `
                <div class="msg-avatar"><i data-lucide="bot" size="14"></i></div>
                <div class="msg-body" style="color: #94a3b8; font-style: italic;">
                    Nova is thinking...
                </div>
            `;
            chatBox.appendChild(typingDiv);
            chatBox.scrollTop = chatBox.scrollHeight;
            if (window.lucide) lucide.createIcons();

            if (btn) btn.disabled = true;

            fetch('../api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: query })
            })
            .then(res => res.json())
            .then(data => {
                const typing = document.getElementById('playgroundTyping');
                if (typing) typing.remove();

                const botReply = (data && (data.reply || data.response || data.message)) || 'No response generated.';
                const engine = (data && data.engine) ? `<span style="font-size: 0.68rem; color: #38bdf8; display: block; margin-top: 4px;">[Engine: ${data.engine}]</span>` : '';

                const botMsgDiv = document.createElement('div');
                botMsgDiv.className = 'playground-msg bot-msg';
                botMsgDiv.innerHTML = `
                    <div class="msg-avatar"><i data-lucide="bot" size="14"></i></div>
                    <div class="msg-body">
                        <strong>Nova:</strong> ${botReply}
                        ${engine}
                    </div>
                `;
                chatBox.appendChild(botMsgDiv);
                chatBox.scrollTop = chatBox.scrollHeight;
                if (window.lucide) lucide.createIcons();
            })
            .catch(err => {
                const typing = document.getElementById('playgroundTyping');
                if (typing) typing.remove();

                const botMsgDiv = document.createElement('div');
                botMsgDiv.className = 'playground-msg bot-msg';
                botMsgDiv.innerHTML = `
                    <div class="msg-avatar"><i data-lucide="alert-circle" size="14" style="color:#ef4444;"></i></div>
                    <div class="msg-body" style="color:#ef4444;">
                        Error connecting to AI Engine: ${err.message}
                    </div>
                `;
                chatBox.appendChild(botMsgDiv);
                chatBox.scrollTop = chatBox.scrollHeight;
                if (window.lucide) lucide.createIcons();
            })
            .finally(() => {
                if (btn) btn.disabled = false;
            });
        }

        function setPreviewDevice(device, btn) {
            const frame = document.getElementById('previewMockupFrame');
            if (!frame) return;
            
            frame.className = 'preview-mockup-frame mode-' + device;
            
            document.querySelectorAll('#pdevDesktop, #pdevMobile').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            localStorage.setItem('admin_preview_device', device);

            if (window.lucide) {
                lucide.createIcons();
            }
        }

        function refreshAdminPreview() {
            const iframe = document.getElementById('livePreviewIframe');
            const overlay = document.getElementById('previewLoadingOverlay');
            const refreshBtn = document.getElementById('previewRefreshBtn');
            
            if (refreshBtn) refreshBtn.classList.add('spinning');
            if (overlay) overlay.classList.add('loading');
            
            if (iframe) {
                try {
                    let currentSrc = iframe.src;
                    const hashIndex = currentSrc.indexOf('#');
                    const hash = hashIndex !== -1 ? currentSrc.substring(hashIndex) : '';
                    const base = currentSrc.split('?')[0].split('#')[0];
                    iframe.src = base + '?_t=' + Date.now() + hash;
                } catch (e) {
                    iframe.src = '../index.php?_t=' + Date.now();
                }
            }
            
            setTimeout(() => {
                if (overlay) overlay.classList.remove('loading');
                if (refreshBtn) refreshBtn.classList.remove('spinning');
            }, 700);
        }

        function jumpPreviewTo(sectionId, btn) {
            const iframe = document.getElementById('livePreviewIframe');
            if (!iframe) return;
            
            document.querySelectorAll('.preview-jump-btn').forEach(b => b.classList.remove('active'));
            if (btn) {
                btn.classList.add('active');
            } else if (window.event && window.event.target && window.event.target.classList.contains('preview-jump-btn')) {
                window.event.target.classList.add('active');
            }

            try {
                // If same origin and iframe loaded, attempt smooth in-page scroll
                const doc = iframe.contentDocument || iframe.contentWindow?.document;
                if (doc) {
                    if (!sectionId || sectionId === 'top') {
                        iframe.contentWindow.scrollTo({ top: 0, behavior: 'smooth' });
                        return;
                    }
                    const targetEl = doc.getElementById(sectionId) || doc.querySelector('[name="' + sectionId + '"]');
                    if (targetEl) {
                        targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        return;
                    }
                }
            } catch (e) {
                // Ignore cross-origin error and use src hash update
            }

            const base = '../index.php';
            const targetHash = sectionId ? '#' + sectionId : '';
            iframe.src = base + '?_t=' + Date.now() + targetHash;
        }

        // =========================================================================
        // INTERACTIVE DRAGGABLE PALETTE COLOR STUDIO ENGINE
        // =========================================================================
        let activeColorSlot = 1;
        let currentHue = 215; // 0 to 360
        let currentSat = 100; // 0 to 100
        let currentVal = 100; // 0 to 100
        let isDraggingSpectrum = false;
        let isDraggingHue = false;

        const slotRoles = {
            1: 'Color 1 (Primary / Word 1)',
            2: 'Color 2 (Secondary / Word 2)',
            3: 'Color 3 (Tertiary / Word 3)',
            4: 'Color 4 (Accent / Word 4)'
        };

        // Utility: Convert Hex to RGB
        function hexToRgb(hex) {
            hex = (hex || '#0066ff').replace(/^#/, '');
            if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
            const num = parseInt(hex, 16);
            if (isNaN(num)) return { r: 0, g: 102, b: 255 };
            return {
                r: (num >> 16) & 255,
                g: (num >> 8) & 255,
                b: num & 255
            };
        }

        // Utility: Convert RGB to Hex
        function rgbToHex(r, g, b) {
            return '#' + [r, g, b].map(x => {
                const h = Math.max(0, Math.min(255, Math.round(x))).toString(16);
                return h.length === 1 ? '0' + h : h;
            }).join('');
        }

        // Utility: Convert RGB to HSV
        function rgbToHsv(r, g, b) {
            r /= 255; g /= 255; b /= 255;
            const max = Math.max(r, g, b), min = Math.min(r, g, b);
            let h = 0, s = 0, v = max;
            const d = max - min;
            s = max === 0 ? 0 : d / max;
            if (max !== min) {
                switch (max) {
                    case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                    case g: h = (b - r) / d + 2; break;
                    case b: h = (r - g) / d + 4; break;
                }
                h /= 6;
            }
            return { h: h * 360, s: s * 100, v: v * 100 };
        }

        // Utility: Convert HSV to RGB
        function hsvToRgb(h, s, v) {
            h = (h % 360 + 360) % 360 / 360;
            s = Math.max(0, Math.min(100, s)) / 100;
            v = Math.max(0, Math.min(100, v)) / 100;
            let r = 0, g = 0, b = 0;
            const i = Math.floor(h * 6);
            const f = h * 6 - i;
            const p = v * (1 - s);
            const q = v * (1 - f * s);
            const t = v * (1 - (1 - f) * s);
            switch (i % 6) {
                case 0: r = v; g = t; b = p; break;
                case 1: r = q; g = v; b = p; break;
                case 2: r = p; g = v; b = t; break;
                case 3: r = p; g = v; b = t; break;
                case 4: r = t; g = p; b = v; break;
                case 5: r = v; g = p; b = q; break;
            }
            return {
                r: Math.round(r * 255),
                g: Math.round(g * 255),
                b: Math.round(b * 255)
            };
        }

        // Select active slot (1, 2, 3, or 4)
        function selectColorSlot(num) {
            activeColorSlot = num;

            // Highlight active slot card
            for (let i = 1; i <= 4; i++) {
                const card = document.getElementById('slotCard' + i);
                if (card) {
                    if (i === num) card.classList.add('active');
                    else card.classList.remove('active');
                }
            }

            // Update badge indicator
            const ind = document.getElementById('activeSlotIndicator');
            if (ind && slotRoles[num]) ind.textContent = slotRoles[num];

            // Load current hex of this slot
            const input = document.getElementById('heroColorText' + num);
            const hex = (input ? input.value : '#0066ff') || '#0066ff';

            syncStudioFromHex(hex);
        }

        // Synchronize studio handles and UI from a hex string
        function syncStudioFromHex(hex) {
            const rgb = hexToRgb(hex);
            const hsv = rgbToHsv(rgb.r, rgb.g, rgb.b);
            currentHue = hsv.h;
            currentSat = hsv.s;
            currentVal = hsv.v;

            renderStudioUI(hex, rgb);
        }

        // Render Studio visuals (Spectrum area background, handle position, hue thumb position, active swatch)
        function renderStudioUI(hex, rgb) {
            if (!rgb) rgb = hexToRgb(hex);

            const spectrum = document.getElementById('studioSpectrumArea');
            if (spectrum) {
                spectrum.style.backgroundColor = `hsl(${currentHue}, 100%, 50%)`;
            }

            const handle = document.getElementById('spectrumHandle');
            if (handle) {
                handle.style.left = currentSat + '%';
                handle.style.top = (100 - currentVal) + '%';
                handle.style.backgroundColor = hex;
            }

            const hueThumb = document.getElementById('hueSliderThumb');
            if (hueThumb) {
                const percent = (currentHue / 360) * 100;
                hueThumb.style.left = percent + '%';
                hueThumb.style.backgroundColor = `hsl(${currentHue}, 100%, 50%)`;
            }

            const activeSwatch = document.getElementById('studioActiveSwatch');
            if (activeSwatch) activeSwatch.style.background = hex;

            const rgbLabel = document.getElementById('studioActiveRgb');
            if (rgbLabel) rgbLabel.textContent = `rgb(${rgb.r}, ${rgb.g}, ${rgb.b})`;

            const hexInput = document.getElementById('studioHexInput');
            if (hexInput && document.activeElement !== hexInput) {
                hexInput.value = hex.replace('#', '').toLowerCase();
            }

            // Update active slot card in the grid
            const slotSwatch = document.getElementById('slotSwatch' + activeColorSlot);
            if (slotSwatch) slotSwatch.style.background = hex;

            const slotHex = document.getElementById('slotHexLabel' + activeColorSlot);
            if (slotHex) slotHex.textContent = hex;

            const hiddenInput = document.getElementById('heroColorText' + activeColorSlot);
            if (hiddenInput) hiddenInput.value = hex;

            // Trigger Marquee Live Preview
            updateMarqueeLivePreview();
        }

        // Recalculate hex from current HSV and update UI
        function updateActiveColorFromHsv() {
            const rgb = hsvToRgb(currentHue, currentSat, currentVal);
            const hex = rgbToHex(rgb.r, rgb.g, rgb.b);
            renderStudioUI(hex, rgb);
        }

        // Handle dragging on 2D Spectrum Area
        function handleSpectrumPointer(e) {
            const spectrum = document.getElementById('studioSpectrumArea');
            if (!spectrum) return;
            const rect = spectrum.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;

            let x = (clientX - rect.left) / rect.width;
            let y = (clientY - rect.top) / rect.height;

            x = Math.max(0, Math.min(1, x));
            y = Math.max(0, Math.min(1, y));

            currentSat = x * 100;
            currentVal = (1 - y) * 100;

            updateActiveColorFromHsv();
        }

        // Handle dragging on Rainbow Hue Slider Track
        function handleHuePointer(e) {
            const track = document.getElementById('studioHueTrack');
            if (!track) return;
            const rect = track.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;

            let x = (clientX - rect.left) / rect.width;
            x = Math.max(0, Math.min(1, x));

            currentHue = x * 360;

            updateActiveColorFromHsv();
        }

        // Initialize drag event listeners on spectrum and hue slider
        function initStudioDragListeners() {
            const spectrum = document.getElementById('studioSpectrumArea');
            if (spectrum) {
                spectrum.addEventListener('mousedown', (e) => {
                    isDraggingSpectrum = true;
                    handleSpectrumPointer(e);
                });
                spectrum.addEventListener('touchstart', (e) => {
                    isDraggingSpectrum = true;
                    handleSpectrumPointer(e);
                }, { passive: true });
            }

            const hueTrack = document.getElementById('studioHueTrack');
            if (hueTrack) {
                hueTrack.addEventListener('mousedown', (e) => {
                    isDraggingHue = true;
                    handleHuePointer(e);
                });
                hueTrack.addEventListener('touchstart', (e) => {
                    isDraggingHue = true;
                    handleHuePointer(e);
                }, { passive: true });
            }

            document.addEventListener('mousemove', (e) => {
                if (isDraggingSpectrum) handleSpectrumPointer(e);
                if (isDraggingHue) handleHuePointer(e);
            });

            document.addEventListener('touchmove', (e) => {
                if (isDraggingSpectrum) handleSpectrumPointer(e);
                if (isDraggingHue) handleHuePointer(e);
            }, { passive: true });

            document.addEventListener('mouseup', () => {
                isDraggingSpectrum = false;
                isDraggingHue = false;
            });

            document.addEventListener('touchend', () => {
                isDraggingSpectrum = false;
                isDraggingHue = false;
            });
        }

        // Direct apply from quick chip swatch
        function applyStudioColor(hex) {
            syncStudioFromHex(hex);
        }

        // When user types in hex input
        function onStudioHexTyped(val) {
            val = val.trim().replace(/^#/, '');
            if (/^[0-9A-Fa-f]{3}$/.test(val) || /^[0-9A-Fa-f]{6}$/.test(val)) {
                syncStudioFromHex('#' + val);
            }
        }

        // Copy Hex to clipboard
        function copyStudioHex() {
            const hex = document.getElementById('heroColorText' + activeColorSlot)?.value || '#0066ff';
            if (navigator.clipboard) {
                navigator.clipboard.writeText(hex).then(() => {
                    alert('Copied ' + hex + ' to clipboard!');
                });
            }
        }

        // Pick Screen Color with native EyeDropper API
        async function pickScreenColor() {
            if (window.EyeDropper) {
                try {
                    const eyeDropper = new EyeDropper();
                    const result = await eyeDropper.open();
                    if (result && result.sRGBHex) {
                        syncStudioFromHex(result.sRGBHex);
                    }
                } catch (e) {
                    // Canceled or not allowed
                }
            } else {
                alert('EyeDropper is not supported by your browser. Please drag on the visual spectrum pad to choose colors.');
            }
        }

        // Presets: Update all 4 slots and refresh active studio
        function applyMarqueePreset(colors, mode) {
            if (Array.isArray(colors)) {
                for (let i = 0; i < 4; i++) {
                    const col = colors[i] || '#ffffff';
                    const hidden = document.getElementById('heroColorText' + (i + 1));
                    const swatch = document.getElementById('slotSwatch' + (i + 1));
                    const hexLbl = document.getElementById('slotHexLabel' + (i + 1));
                    if (hidden) hidden.value = col;
                    if (swatch) swatch.style.background = col;
                    if (hexLbl) hexLbl.textContent = col;
                }
            }
            if (mode) {
                const modeSelect = document.getElementById('heroMarqueeMode');
                if (modeSelect) modeSelect.value = mode;
            }

            // Sync currently active slot in studio
            selectColorSlot(activeColorSlot);
            updateMarqueeLivePreview();
        }

        const MARQUEE_FONTS_MAP = {
            'Syne': { family: "'Syne', sans-serif", weight: '800' },
            'Space Grotesk': { family: "'Space Grotesk', sans-serif", weight: '700' },
            'Cinzel': { family: "'Cinzel', serif", weight: '800' },
            'Bebas Neue': { family: "'Bebas Neue', sans-serif", weight: '400' },
            'Outfit': { family: "'Outfit', sans-serif", weight: '800' },
            'Playfair Display': { family: "'Playfair Display', serif", weight: '900' },
            'Righteous': { family: "'Righteous', cursive", weight: '400' },
            'Montserrat': { family: "'Montserrat', sans-serif", weight: '900' },
            'Italiana': { family: "'Italiana', serif", weight: '400' },
            'Plus Jakarta Sans': { family: "'Plus Jakarta Sans', sans-serif", weight: '800' }
        };

        function selectMarqueeFont(fontName) {
            const fontSelect = document.getElementById('heroMarqueeFont');
            if (fontSelect) {
                fontSelect.value = fontName;
            }
            syncFontChipsUI(fontName);
            updateMarqueeLivePreview();
        }

        function onMarqueeFontChange() {
            const fontSelect = document.getElementById('heroMarqueeFont');
            const fontName = fontSelect ? fontSelect.value : 'Syne';
            syncFontChipsUI(fontName);
            updateMarqueeLivePreview();
        }

        function syncFontChipsUI(activeFont) {
            document.querySelectorAll('.font-chip-btn').forEach(btn => {
                if (btn.getAttribute('data-font-name') === activeFont) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            const fontTag = document.getElementById('previewFontTag');
            if (fontTag) {
                fontTag.textContent = 'Font: ' + activeFont;
            }
        }

        function updateMarqueeLivePreview() {
            const fontEl = document.getElementById('heroMarqueeFont');
            const chosenFont = fontEl ? fontEl.value : 'Syne';
            const fontObj = MARQUEE_FONTS_MAP[chosenFont] || MARQUEE_FONTS_MAP['Syne'];

            const modeEl = document.getElementById('heroMarqueeMode');
            if (!modeEl) return;
            const mode = modeEl.value || 'words';
            const tag = document.getElementById('previewModeTag');
            if (tag) {
                if (mode === 'words') tag.textContent = 'Word-by-Word Multi-Color';
                else if (mode === 'letters') tag.textContent = 'Letter-by-Letter Rainbow Spectrum';
                else if (mode === 'gradient') tag.textContent = 'Continuous Flowing Gradient';
                else tag.textContent = 'Dual-Tone Solid';
            }

            const fontTag = document.getElementById('previewFontTag');
            if (fontTag) {
                fontTag.textContent = 'Font: ' + chosenFont;
            }

            const c1 = document.getElementById('heroColorText1')?.value || '#0066ff';
            const c2 = document.getElementById('heroColorText2')?.value || '#38bdf8';
            const c3 = document.getElementById('heroColorText3')?.value || '#ffffff';
            const c4 = document.getElementById('heroColorText4')?.value || '#a855f7';
            const palette = [c1, c2, c3, c4].filter(Boolean);

            const row1Style = document.getElementById('heroRow1Style')?.value || 'filled';
            const row2Style = document.getElementById('heroRow2Style')?.value || 'outline';

            const row1Input = document.getElementById('heroRow1Text')?.value || 'PERUMALIL CREATIVE • PERUMALIL CREATIVE';
            const row2Input = document.getElementById('heroRow2Text')?.value || 'CREATIVE DIRECTION • CINEMATIC PRODUCTION';

            const row1Preview = document.getElementById('previewRow1Text');
            const row2Preview = document.getElementById('previewRow2Text');

            const opacityEl = document.getElementById('heroMarqueeOpacity');
            const opacity = opacityEl ? parseFloat(opacityEl.value) : 0.85;
            const canvas = document.getElementById('marqueePreviewCanvas');
            if (canvas) canvas.style.opacity = Math.max(0.25, opacity);

            function formatTextForPreview(text, style) {
                const clean = text.replace(/&bull;|&#8226;/g, '•').trim();
                const pLen = palette.length || 1;

                if (mode === 'solid') {
                    const col = palette[0] || '#ffffff';
                    if (style === 'outline') {
                        return `<span style="-webkit-text-stroke: 1.5px ${col}; color: transparent;">${clean}</span>`;
                    } else {
                        return `<span style="color: ${col};">${clean}</span>`;
                    }
                }

                if (mode === 'gradient') {
                    const grad = `linear-gradient(90deg, ${palette.join(', ')})`;
                    if (style === 'outline') {
                        return `<span style="-webkit-text-stroke: 1.5px ${palette[0]}; color: transparent;">${clean}</span>`;
                    } else {
                        return `<span style="background: ${grad}; -webkit-background-clip: text; -webkit-text-fill-color: transparent;">${clean}</span>`;
                    }
                }

                if (mode === 'letters') {
                    let out = '';
                    let charIdx = 0;
                    for (let i = 0; i < clean.length; i++) {
                        const ch = clean[i];
                        if (ch === '•') {
                            out += '<span style="color: rgba(255,255,255,0.4); padding: 0 8px;">&bull;</span>';
                        } else if (ch === ' ' || ch === '\t') {
                            out += ' ';
                        } else {
                            const col = palette[charIdx % pLen];
                            charIdx++;
                            if (style === 'outline') {
                                out += `<span style="-webkit-text-stroke: 1.5px ${col}; color: transparent;">${ch}</span>`;
                            } else {
                                out += `<span style="color: ${col};">${ch}</span>`;
                            }
                        }
                    }
                    return out;
                }

                // Default: words
                const parts = clean.split(/(\s+|•)/);
                let out = '';
                let wordIdx = 0;
                parts.forEach(p => {
                    if (p === '•') {
                        out += '<span style="color: rgba(255,255,255,0.4); padding: 0 8px;">&bull;</span>';
                    } else if (!p.trim()) {
                        out += p;
                    } else {
                        const col = palette[wordIdx % pLen];
                        wordIdx++;
                        if (style === 'outline') {
                            out += `<span style="-webkit-text-stroke: 1.5px ${col}; color: transparent;">${p}</span>`;
                        } else {
                            out += `<span style="color: ${col};">${p}</span>`;
                        }
                    }
                });
                return out;
            }

            if (row1Preview) {
                row1Preview.style.fontFamily = fontObj.family;
                row1Preview.style.fontWeight = fontObj.weight;
                row1Preview.innerHTML = formatTextForPreview(row1Input, row1Style);
            }
            if (row2Preview) {
                row2Preview.style.fontFamily = fontObj.family;
                row2Preview.style.fontWeight = fontObj.weight;
                row2Preview.innerHTML = formatTextForPreview(row2Input, row2Style);
            }
        }

        // =========================================================================
        // UNIVERSAL WEBSITE TYPOGRAPHY & DYNAMIC COLOR STUDIO ENGINE
        // =========================================================================
        let activeSiteColorSlot = 1;
        let currentSiteHue = 215;
        let currentSiteSat = 100;
        let currentSiteVal = 100;
        let isDraggingSiteSpectrum = false;
        let isDraggingSiteHue = false;

        const siteSlotRoles = {
            1: '1. Main Headings Color',
            2: '2. Sub-headings & Taglines Color',
            3: '3. Body Text & Paragraphs Color',
            4: '4. Brand Primary Accent Color',
            5: '5. Hero Headline Glow Accent Color',
            6: '6. Muted Labels Color',
            7: '7. Main Site Background Color (Full Website Background)',
            8: '8. Cards & Container Surfaces Background'
        };

        const SITE_PRESETS = {
            'electric_cobalt': {
                name: 'Electric Cobalt & Cyan',
                fonts: { heading: 'Syne', editorial: 'Outfit', body: 'Plus Jakarta Sans', hero: 'Syne' },
                colors: { 1: '#ffffff', 2: '#38bdf8', 3: '#a0a0ab', 4: '#0066ff', 5: '#38bdf8', 6: '#64748b', 7: '#050505', 8: '#0c0d12' }
            },
            'cyber_neon': {
                name: 'Cyber Neon Spectrum',
                fonts: { heading: 'Righteous', editorial: 'Space Grotesk', body: 'Outfit', hero: 'Righteous' },
                colors: { 1: '#ffffff', 2: '#00ffaa', 3: '#cbd5e1', 4: '#a855f7', 5: '#00ffaa', 6: '#64748b', 7: '#030712', 8: '#0b1329' }
            },
            'sunset_gold': {
                name: 'Sunset Gold & Amber',
                fonts: { heading: 'Cinzel', editorial: 'Montserrat', body: 'Outfit', hero: 'Cinzel' },
                colors: { 1: '#fef3c7', 2: '#fbbf24', 3: '#d1d5db', 4: '#f59e0b', 5: '#fbbf24', 6: '#78716c', 7: '#0b0907', 8: '#17130e' }
            },
            'emerald_mint': {
                name: 'Emerald & Mint Luxury',
                fonts: { heading: 'Outfit', editorial: 'Plus Jakarta Sans', body: 'Plus Jakarta Sans', hero: 'Space Grotesk' },
                colors: { 1: '#f0fdf4', 2: '#34d399', 3: '#94a3b8', 4: '#10b981', 5: '#34d399', 6: '#475569', 7: '#030d08', 8: '#081c12' }
            },
            'haute_couture': {
                name: 'Haute Couture Elegance',
                fonts: { heading: 'Italiana', editorial: 'Playfair Display', body: 'Outfit', hero: 'Italiana' },
                colors: { 1: '#ffffff', 2: '#fb7185', 3: '#cbd5e1', 4: '#e11d48', 5: '#fb7185', 6: '#9ca3af', 7: '#0e0508', 8: '#1a0c11' }
            },
            'monochrome': {
                name: 'Minimalist Monochrome',
                fonts: { heading: 'Bebas Neue', editorial: 'Montserrat', body: 'Plus Jakarta Sans', hero: 'Bebas Neue' },
                colors: { 1: '#ffffff', 2: '#cbd5e1', 3: '#94a3b8', 4: '#64748b', 5: '#ffffff', 6: '#475569', 7: '#0a0a0a', 8: '#141414' }
            },
            'royal_violet': {
                name: 'Royal Violet Prestige',
                fonts: { heading: 'Syne', editorial: 'Outfit', body: 'Plus Jakarta Sans', hero: 'Syne' },
                colors: { 1: '#faf5ff', 2: '#c084fc', 3: '#cbd5e1', 4: '#7c3aed', 5: '#c084fc', 6: '#6b7280', 7: '#090414', 8: '#130924' }
            }
        };

        // Select active slot (1 to 8)
        function selectSiteColorSlot(num) {
            activeSiteColorSlot = num;

            // Highlight active slot card
            for (let i = 1; i <= 8; i++) {
                const card = document.getElementById('siteSlotCard' + i);
                if (card) {
                    if (i === num) card.classList.add('active');
                    else card.classList.remove('active');
                }
            }

            // Update badge indicator
            const ind = document.getElementById('siteActiveSlotIndicator');
            if (ind && siteSlotRoles[num]) ind.textContent = siteSlotRoles[num];

            // Load current hex of this slot
            const input = document.getElementById('siteColorText' + num);
            const hex = (input ? input.value : '#ffffff') || '#ffffff';

            syncSiteStudioFromHex(hex);
        }

        // Synchronize studio handles and UI from a hex string
        function syncSiteStudioFromHex(hex) {
            const rgb = hexToRgb(hex);
            const hsv = rgbToHsv(rgb.r, rgb.g, rgb.b);
            currentSiteHue = hsv.h;
            currentSiteSat = hsv.s;
            currentSiteVal = hsv.v;

            renderSiteStudioUI(hex, rgb);
        }

        // Render Studio visuals
        function renderSiteStudioUI(hex, rgb) {
            if (!rgb) rgb = hexToRgb(hex);

            const spectrum = document.getElementById('siteSpectrumArea');
            if (spectrum) {
                spectrum.style.backgroundColor = `hsl(${currentSiteHue}, 100%, 50%)`;
            }

            const handle = document.getElementById('siteSpectrumHandle');
            if (handle) {
                handle.style.left = currentSiteSat + '%';
                handle.style.top = (100 - currentSiteVal) + '%';
                handle.style.backgroundColor = hex;
            }

            const hueThumb = document.getElementById('siteHueSliderThumb');
            if (hueThumb) {
                const percent = (currentSiteHue / 360) * 100;
                hueThumb.style.left = percent + '%';
                hueThumb.style.backgroundColor = `hsl(${currentSiteHue}, 100%, 50%)`;
            }

            const activeSwatch = document.getElementById('siteStudioActiveSwatch');
            if (activeSwatch) activeSwatch.style.background = hex;

            const rgbLabel = document.getElementById('siteStudioActiveRgb');
            if (rgbLabel) rgbLabel.textContent = `rgb(${rgb.r}, ${rgb.g}, ${rgb.b})`;

            const hexInput = document.getElementById('siteStudioHexInput');
            if (hexInput && document.activeElement !== hexInput) {
                hexInput.value = hex.replace('#', '').toLowerCase();
            }

            // Update active slot card in the grid
            const slotSwatch = document.getElementById('siteSlotSwatch' + activeSiteColorSlot);
            if (slotSwatch) slotSwatch.style.background = hex;

            const slotHex = document.getElementById('siteSlotHexLabel' + activeSiteColorSlot);
            if (slotHex) slotHex.textContent = hex;

            const hiddenInput = document.getElementById('siteColorText' + activeSiteColorSlot);
            if (hiddenInput) hiddenInput.value = hex;

            // Direct input mirror
            const directInput = document.getElementById('directColorInput' + activeSiteColorSlot);
            if (directInput && document.activeElement !== directInput) {
                directInput.value = hex;
                const picker = directInput.previousElementSibling;
                if (picker && picker.type === 'color') picker.value = hex;
            }

            // Trigger Real-time live preview
            updateSiteLivePreview();
        }

        function updateActiveSiteColorFromHsv() {
            const rgb = hsvToRgb(currentSiteHue, currentSiteSat, currentSiteVal);
            const hex = rgbToHex(rgb.r, rgb.g, rgb.b);
            renderSiteStudioUI(hex, rgb);
        }

        // Pointer handlers for Spectrum area
        function handleSiteSpectrumPointer(e) {
            const spectrum = document.getElementById('siteSpectrumArea');
            if (!spectrum) return;
            const rect = spectrum.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;

            let x = (clientX - rect.left) / rect.width;
            let y = (clientY - rect.top) / rect.height;

            x = Math.max(0, Math.min(1, x));
            y = Math.max(0, Math.min(1, y));

            currentSiteSat = x * 100;
            currentSiteVal = (1 - y) * 100;

            updateActiveSiteColorFromHsv();
        }

        // Pointer handlers for Hue slider track
        function handleSiteHuePointer(e) {
            const track = document.getElementById('siteHueTrack');
            if (!track) return;
            const rect = track.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;

            let x = (clientX - rect.left) / rect.width;
            x = Math.max(0, Math.min(1, x));

            currentSiteHue = x * 360;

            updateActiveSiteColorFromHsv();
        }

        // Initialize drag event listeners on site spectrum and hue slider
        function initSiteStudioDragListeners() {
            const spectrum = document.getElementById('siteSpectrumArea');
            if (spectrum) {
                spectrum.addEventListener('mousedown', (e) => {
                    isDraggingSiteSpectrum = true;
                    handleSiteSpectrumPointer(e);
                });
                spectrum.addEventListener('touchstart', (e) => {
                    isDraggingSiteSpectrum = true;
                    handleSiteSpectrumPointer(e);
                }, { passive: true });
            }

            const hueTrack = document.getElementById('siteHueTrack');
            if (hueTrack) {
                hueTrack.addEventListener('mousedown', (e) => {
                    isDraggingSiteHue = true;
                    handleSiteHuePointer(e);
                });
                hueTrack.addEventListener('touchstart', (e) => {
                    isDraggingSiteHue = true;
                    handleSiteHuePointer(e);
                }, { passive: true });
            }

            document.addEventListener('mousemove', (e) => {
                if (isDraggingSiteSpectrum) handleSiteSpectrumPointer(e);
                if (isDraggingSiteHue) handleSiteHuePointer(e);
            });

            document.addEventListener('touchmove', (e) => {
                if (isDraggingSiteSpectrum) handleSiteSpectrumPointer(e);
                if (isDraggingSiteHue) handleSiteHuePointer(e);
            }, { passive: true });

            document.addEventListener('mouseup', () => {
                isDraggingSiteSpectrum = false;
                isDraggingSiteHue = false;
            });

            document.addEventListener('touchend', () => {
                isDraggingSiteSpectrum = false;
                isDraggingSiteHue = false;
            });
        }

        // Direct apply from quick chip swatch
        function applySiteStudioColor(hex) {
            syncSiteStudioFromHex(hex);
        }

        // When user types in hex input
        function onSiteStudioHexTyped(val) {
            val = val.trim().replace(/^#/, '');
            if (/^[0-9A-Fa-f]{3}$/.test(val) || /^[0-9A-Fa-f]{6}$/.test(val)) {
                syncSiteStudioFromHex('#' + val);
            }
        }

        // Copy Hex to clipboard
        function copySiteStudioHex() {
            const hex = document.getElementById('siteColorText' + activeSiteColorSlot)?.value || '#ffffff';
            if (navigator.clipboard) {
                navigator.clipboard.writeText(hex).then(() => {
                    alert('Copied ' + hex + ' to clipboard!');
                });
            }
        }

        // Pick Screen Color with native EyeDropper API
        async function pickSiteScreenColor() {
            if (window.EyeDropper) {
                try {
                    const eyeDropper = new EyeDropper();
                    const result = await eyeDropper.open();
                    if (result && result.sRGBHex) {
                        syncSiteStudioFromHex(result.sRGBHex);
                    }
                } catch (e) {
                    // Canceled or not allowed
                }
            } else {
                alert('EyeDropper is not supported by your browser. Please drag on the visual spectrum pad to choose colors.');
            }
        }

        // Direct Color inputs
        function applyDirectColor(slotNum, hex) {
            if (!hex) return;
            if (!hex.startsWith('#')) hex = '#' + hex;

            const hidden = document.getElementById('siteColorText' + slotNum);
            const swatch = document.getElementById('siteSlotSwatch' + slotNum);
            const hexLbl = document.getElementById('siteSlotHexLabel' + slotNum);
            const direct = document.getElementById('directColorInput' + slotNum);

            if (hidden) hidden.value = hex;
            if (swatch) swatch.style.background = hex;
            if (hexLbl) hexLbl.textContent = hex;
            if (direct && direct.value !== hex) {
                direct.value = hex;
                const picker = direct.previousElementSibling;
                if (picker && picker.type === 'color') picker.value = hex;
            }

            if (slotNum === activeSiteColorSlot) {
                syncSiteStudioFromHex(hex);
            } else {
                updateSiteLivePreview();
            }
        }

        // Preset Application
        function applySitePreset(presetKey) {
            const preset = SITE_PRESETS[presetKey];
            if (!preset) return;

            // Apply Fonts
            if (preset.fonts) {
                if (preset.fonts.heading) selectSiteFont('heading', preset.fonts.heading);
                if (preset.fonts.editorial) selectSiteFont('editorial', preset.fonts.editorial);
                if (preset.fonts.body) selectSiteFont('body', preset.fonts.body);
                if (preset.fonts.hero) selectSiteFont('hero', preset.fonts.hero);
            }

            // Apply Colors
            if (preset.colors) {
                for (let i = 1; i <= 8; i++) {
                    const col = preset.colors[i];
                    if (col) {
                        const hidden = document.getElementById('siteColorText' + i);
                        const swatch = document.getElementById('siteSlotSwatch' + i);
                        const hexLbl = document.getElementById('siteSlotHexLabel' + i);
                        const direct = document.getElementById('directColorInput' + i);

                        if (hidden) hidden.value = col;
                        if (swatch) swatch.style.background = col;
                        if (hexLbl) hexLbl.textContent = col;
                        if (direct) {
                            direct.value = col;
                            const picker = direct.previousElementSibling;
                            if (picker && picker.type === 'color') picker.value = col;
                        }
                    }
                }
            }

            const presetTag = document.getElementById('previewSitePresetTag');
            if (presetTag) presetTag.textContent = 'Preset: ' + preset.name;

            // Sync currently active slot in studio
            selectSiteColorSlot(activeSiteColorSlot);
            updateSiteLivePreview();
        }

        // Font Selection
        function selectSiteFont(category, fontName) {
            let selectId = 'siteFontHeading';
            if (category === 'editorial') selectId = 'siteFontEditorial';
            else if (category === 'body') selectId = 'siteFontBody';
            else if (category === 'hero') selectId = 'heroHeadlineFont';

            const sel = document.getElementById(selectId);
            if (sel) sel.value = fontName;

            syncSiteFontChipsUI(category, fontName);
            updateSiteLivePreview();
        }

        function onSiteFontDropdownChange(category) {
            let selectId = 'siteFontHeading';
            if (category === 'editorial') selectId = 'siteFontEditorial';
            else if (category === 'body') selectId = 'siteFontBody';
            else if (category === 'hero') selectId = 'heroHeadlineFont';

            const sel = document.getElementById(selectId);
            const fontName = sel ? sel.value : 'Syne';

            syncSiteFontChipsUI(category, fontName);
            updateSiteLivePreview();
        }

        function syncSiteFontChipsUI(category, fontName) {
            document.querySelectorAll(`.site-font-chip[data-font-cat="${category}"]`).forEach(btn => {
                if (btn.getAttribute('data-font-name') === fontName) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        // Universal Real-Time Live Preview & CSS Injection
        function updateSiteLivePreview() {
            const hFont = document.getElementById('siteFontHeading')?.value || 'Syne';
            const eFont = document.getElementById('siteFontEditorial')?.value || 'Outfit';
            const bFont = document.getElementById('siteFontBody')?.value || 'Plus Jakarta Sans';
            const heroFont = document.getElementById('heroHeadlineFont')?.value || hFont;

            const hFontObj = MARQUEE_FONTS_MAP[hFont] || MARQUEE_FONTS_MAP['Syne'];
            const eFontObj = MARQUEE_FONTS_MAP[eFont] || MARQUEE_FONTS_MAP['Outfit'];
            const bFontObj = MARQUEE_FONTS_MAP[bFont] || MARQUEE_FONTS_MAP['Plus Jakarta Sans'];
            const heroFontObj = MARQUEE_FONTS_MAP[heroFont] || hFontObj;

            // Update Preview Header Tags
            const hTag = document.getElementById('previewSiteHeadingFontTag');
            if (hTag) hTag.textContent = 'Heading: ' + hFont;
            const eTag = document.getElementById('previewSiteEditorialFontTag');
            if (eTag) eTag.textContent = 'Subtext: ' + eFont;
            const bTag = document.getElementById('previewSiteBodyFontTag');
            if (bTag) bTag.textContent = 'Body: ' + bFont;

            // Get Colors
            const colHeading = document.getElementById('siteColorText1')?.value || '#ffffff';
            const colSubheading = document.getElementById('siteColorText2')?.value || '#38bdf8';
            const colBody = document.getElementById('siteColorText3')?.value || '#a0a0ab';
            const colAccent = document.getElementById('siteColorText4')?.value || '#0066ff';
            const colHeroGlow = document.getElementById('siteColorText5')?.value || '#38bdf8';
            const colMuted = document.getElementById('siteColorText6')?.value || '#64748b';
            const colBg = document.getElementById('siteColorText7')?.value || '#050505';
            const colCard = document.getElementById('siteColorText8')?.value || '#0c0d12';

            // 1. Update In-page Preview Canvas
            const canvas = document.getElementById('sitePreviewCanvas');
            if (canvas) {
                canvas.style.backgroundColor = colBg;
                canvas.style.background = `radial-gradient(circle at 20% 20%, ${colCard} 0%, ${colBg} 100%)`;

                const tagline = document.getElementById('previewTaglineText');
                if (tagline) {
                    tagline.style.fontFamily = eFontObj.family;
                    tagline.style.color = colSubheading;
                    tagline.style.borderColor = colAccent + '40';
                    tagline.style.backgroundColor = colAccent + '15';
                }

                const headline = document.getElementById('previewMainHeadline');
                if (headline) {
                    headline.style.fontFamily = heroFontObj.family;
                    headline.style.fontWeight = heroFontObj.weight;
                    headline.style.color = colHeading;
                }

                const glow = document.getElementById('previewGlowText');
                if (glow) {
                    glow.style.color = colHeroGlow;
                    glow.style.textShadow = `0 0 25px ${colHeroGlow}99, 0 0 45px ${colHeroGlow}44`;
                }

                const subhead = document.getElementById('previewSubheadlineText');
                if (subhead) {
                    subhead.style.fontFamily = eFontObj.family;
                    subhead.style.color = colSubheading;
                }

                const body = document.getElementById('previewBodyText');
                if (body) {
                    body.style.fontFamily = bFontObj.family;
                    body.style.color = colBody;
                }

                const accentBtn = document.getElementById('previewAccentBtn');
                if (accentBtn) {
                    accentBtn.style.fontFamily = eFontObj.family;
                    accentBtn.style.backgroundColor = colAccent;
                    accentBtn.style.boxShadow = `0 4px 20px ${colAccent}55`;
                }

                const outlineBtn = document.getElementById('previewOutlineBtn');
                if (outlineBtn) {
                    outlineBtn.style.fontFamily = eFontObj.family;
                    outlineBtn.style.borderColor = colAccent + '66';
                    outlineBtn.style.color = colHeading;
                }

                const coords = document.getElementById('previewCoordsMeta');
                if (coords) {
                    coords.style.fontFamily = eFontObj.family;
                    coords.style.color = colMuted;
                }
            }

            // 2. Real-time Live Injection into livePreviewIframe
            try {
                const iframe = document.getElementById('livePreviewIframe');
                if (iframe && iframe.contentDocument) {
                    const iDoc = iframe.contentDocument;
                    let dynStyle = iDoc.getElementById('admin-live-injected-typo');
                    if (!dynStyle) {
                        dynStyle = iDoc.createElement('style');
                        dynStyle.id = 'admin-live-injected-typo';
                        iDoc.head.appendChild(dynStyle);
                    }
                    dynStyle.textContent = `
                        :root {
                            --bg-dark: ${colBg} !important;
                            --bg-card: ${colCard} !important;
                            --font-heading: ${hFontObj.family} !important;
                            --font-editorial: ${eFontObj.family} !important;
                            --font-body: ${bFontObj.family} !important;
                            --hero-headline-font: ${heroFontObj.family} !important;
                            --text-primary: ${colHeading} !important;
                            --text-secondary: ${colBody} !important;
                            --text-subheading: ${colSubheading} !important;
                            --text-muted: ${colMuted} !important;
                            --accent: ${colAccent} !important;
                            --accent-blue: ${colAccent} !important;
                            --hero-headline-color: ${colHeading} !important;
                            --hero-glow-color: ${colHeroGlow} !important;
                        }
                        html, body {
                            background-color: var(--bg-dark) !important;
                        }
                        .webgl-background {
                            background: radial-gradient(circle at 50% 50%, ${colCard} 0%, ${colBg} 100%) !important;
                        }
                        .hero, .hero-immersive-canvas {
                            background: transparent !important;
                            background-color: transparent !important;
                        }
                        .preloader {
                            background-color: var(--bg-dark) !important;
                        }
                        .intro, .services-section, .portfolio-section, .pricing-section, .location-section, .contact-section, .footer {
                            background-color: var(--bg-dark) !important;
                        }
                        .intro-logos-container {
                            background: radial-gradient(ellipse at 50% -20%, ${colAccent}15 0%, transparent 65%), var(--bg-dark) !important;
                        }
                        .marquee-fade-left {
                            background: linear-gradient(to right, var(--bg-dark) 0%, transparent 100%) !important;
                        }
                        .marquee-fade-right {
                            background: linear-gradient(to left, var(--bg-dark) 0%, transparent 100%) !important;
                        }
                        .navbar.scrolled {
                            background: ${colBg}cc !important;
                        }
                        h1, h2, h3, h4, .section-title, .brand-headline, .font-heading {
                            font-family: var(--font-heading) !important;
                            color: var(--text-primary) !important;
                        }
                        .hero-title {
                            font-family: var(--hero-headline-font) !important;
                            color: var(--hero-headline-color) !important;
                        }
                        .text-glow, .hero-glow {
                            color: var(--hero-glow-color) !important;
                            text-shadow: 0 0 25px ${colHeroGlow}80 !important;
                        }
                        .section-tag, .tagline-chip, .badge, .telemetry-val {
                            font-family: var(--font-editorial) !important;
                            color: var(--text-subheading) !important;
                        }
                        p, .description, .lead-para {
                            font-family: var(--font-body) !important;
                            color: var(--text-secondary) !important;
                        }
                    `;

                    try {
                        if (iframe.contentWindow && iframe.contentWindow.webGLBackgroundInstance && typeof iframe.contentWindow.webGLBackgroundInstance.updateColors === 'function') {
                            iframe.contentWindow.webGLBackgroundInstance.updateColors(colBg, colAccent);
                        }
                    } catch (eWebGL) {}
                }
            } catch (err) {
                // Ignore cross-origin security errors if any
            }
        }

        // Initialize Live Preview Listeners & Settings
        document.addEventListener('DOMContentLoaded', function() {
            const savedVisible = localStorage.getItem('admin_preview_visible');
            const savedDevice = localStorage.getItem('admin_preview_device');
            const container = document.querySelector('.admin-container');
            const btnText = document.getElementById('previewToggleBtnText');
            
            // Set balanced preview width variable
            document.documentElement.style.setProperty('--admin-preview-width', '48%');
            
            // By default Live Preview is HIDDEN; only open if user previously explicitly opened it
            if (savedVisible === 'true') {
                if (container) container.classList.remove('preview-hidden');
                if (btnText) btnText.textContent = 'Hide Live Preview';
            } else {
                if (container) container.classList.add('preview-hidden');
                if (btnText) btnText.textContent = 'Show Live Preview';
            }

            if (savedDevice === 'mobile') {
                const mobBtn = document.getElementById('pdevMobile');
                if (mobBtn) setPreviewDevice('mobile', mobBtn);
            }
            
            const iframe = document.getElementById('livePreviewIframe');
            if (iframe) {
                iframe.addEventListener('load', function() {
                    const overlay = document.getElementById('previewLoadingOverlay');
                    const refreshBtn = document.getElementById('previewRefreshBtn');
                    if (overlay) overlay.classList.remove('loading');
                    if (refreshBtn) refreshBtn.classList.remove('spinning');
                    updateSiteLivePreview();
                });
            }

            if (window.lucide) {
                lucide.createIcons();
            }

            // Initialize Interactive Draggable Color Studio (Marquee)
            initStudioDragListeners();
            selectColorSlot(1);

            // Initialize Universal Website Typography & Dynamic Color Studio
            initSiteStudioDragListeners();
            selectSiteColorSlot(1);
            updateSiteLivePreview();

            // Initialize Marquee Preview on load
            updateMarqueeLivePreview();

            <?php if ($success_msg !== ''): ?>
            // Automatically refresh live preview on successful data mutation!
            setTimeout(refreshAdminPreview, 350);
            <?php endif; ?>
        });

        // Trigger manual database backup to db/perumalil_creative.sql
        function runDatabaseBackup() {
            const btn = document.getElementById('dbBackupTopBtn');
            const textSpan = document.getElementById('dbBackupBtnText');
            if (!btn) return;

            const originalText = textSpan ? textSpan.textContent : 'Backup DB';
            if (textSpan) textSpan.textContent = 'Backing up...';
            btn.disabled = true;

            fetch('../export_db_backup.php')
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    if (textSpan) textSpan.textContent = originalText;
                    if (data && data.success) {
                        alert('✅ Database backup completed successfully!\n\nSaved to: db/perumalil_creative.sql\nTables backed up: ' + data.tables_count + '\nBackup Time: ' + data.backup_time);
                    } else {
                        alert('❌ Backup failed: ' + (data && data.error ? data.error : 'Unknown error'));
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    if (textSpan) textSpan.textContent = originalText;
                    alert('❌ Backup request error: ' + err.message);
                });
        }
    </script>
</body>
</html>
