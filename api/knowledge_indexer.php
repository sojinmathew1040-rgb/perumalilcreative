<?php
// api/knowledge_indexer.php - Universal Database Harvester & Semantic Knowledge Engine
// Dynamically discovers and indexes ALL tables, schemas, and records from MySQL for the AI Assistant

if (php_sapi_name() !== 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'knowledge_indexer.php') {
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/../includes/db.php';

/**
 * Calculate dynamic real-time fingerprint of the database state
 */
function getDatabaseFingerprint($pdo) {
    if (!$pdo) return '';
    try {
        $stmt = $pdo->query("SELECT 
            (SELECT COUNT(*) FROM site_settings),
            (SELECT COUNT(*) FROM portfolio_items),
            (SELECT COALESCE(MAX(id), 0) FROM portfolio_items),
            (SELECT COUNT(*) FROM services),
            (SELECT COALESCE(MAX(id), 0) FROM services),
            (SELECT COUNT(*) FROM pricing_packages),
            (SELECT COALESCE(MAX(id), 0) FROM pricing_packages),
            (SELECT COUNT(*) FROM team_members),
            (SELECT COALESCE(MAX(id), 0) FROM team_members),
            (SELECT COUNT(*) FROM faq_items),
            (SELECT COALESCE(MAX(id), 0) FROM faq_items),
            (SELECT COUNT(*) FROM intro_pillars),
            (SELECT COUNT(*) FROM collaborators)
        ");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return $row ? implode(':', $row) : '';
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Tokenize and normalize text into clean keywords with Malayalam & Manglish stemming
 */
function extractKeywords($text) {
    $text = mb_strtolower(strip_tags((string)$text));
    
    // Stopwords in English, Malayalam, and Manglish
    $stopWords = [
        'the', 'is', 'at', 'which', 'on', 'a', 'an', 'and', 'or', 'in', 'for', 'to', 
        'of', 'with', 'by', 'as', 'it', 'our', 'we', 'you', 'your', 'from', 'this',
        'that', 'are', 'was', 'were', 'will', 'have', 'has', 'had', 'been', 'about',
        'hi', 'hello', 'hey', 'namaskaram', 'who', 'whom', 'whose', 'what', 'where',
        'how', 'tell', 'me', 'give', 'show', 'i', 'my', 'please',
        // Malayalam & Manglish question words and filler words
        'eth', 'ethanu', 'ethaanu', 'enth', 'enthanu', 'enthaanu', 'enthellam', 'enthokke', 'enthokkeyanu',
        'para', 'parayu', 'parayamo', 'parayaamo', 'choykkyam', 'chothikkam', 'ariyikanam', 'ariyamo', 'kittumo',
        'evide', 'evideyaanu', 'evideanu', 'evideya', 'aaranu', 'aara', 'aarkkanu', 'aaroke',
        'und', 'undo', 'undoo', 'aanu', 'anu', 'aano', 'cheytha', 'cheythathe', 'cheythathu', 'cheyyunne',
        'njan', 'njammal', 'ningal', 'nammal', 'nammude', 'karyam', 'karyangal'
    ];
    
    $tokens = preg_split('/[\s,\.\?\!\;\:\-\(\)\[\]\/\'\"\`\&\#\•\+\*\|\>\<\~]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $cleanTokens = [];
    
    // Synonym mapping dictionary (maps Malayalam/Manglish and related terms to core searchable concepts)
    $synonyms = [
        'stalam' => ['location', 'address', 'office', 'vazhakulam'],
        'sthalam' => ['location', 'address', 'office', 'vazhakulam'],
        'sthalathil' => ['location', 'address', 'office'],
        'sthalamevida' => ['location', 'address'],
        'kaashu' => ['price', 'pricing', 'cost', 'package', 'rate'],
        'paisa' => ['price', 'pricing', 'cost', 'package', 'rate'],
        'vila' => ['price', 'pricing', 'cost', 'package', 'rate'],
        'ethraya' => ['price', 'cost', 'rate', 'how much'],
        'pani' => ['portfolio', 'work', 'project'],
        'aalu' => ['team', 'member', 'leadership'],
        'staff' => ['team', 'member', 'leadership'],
        'mudhalali' => ['director', 'dijo', 'founder', 'owner', 'md'],
        'vilikkan' => ['contact', 'phone', 'call'],
        'vilikkyan' => ['contact', 'phone', 'call'],
        'insta' => ['instagram', 'social'],
        'fb' => ['facebook', 'social'],
        'yt' => ['youtube', 'social'],
        'devolper' => ['developer'],
        'devloper' => ['developer'],
        'developr' => ['developer'],
        'desiner' => ['designer'],
        'directer' => ['director']
    ];
    
    foreach ($tokens as $t) {
        $t = trim($t);
        if (mb_strlen($t) < 2) continue;
        
        // Normalize common Manglish suffixes:
        // -il, -nte, -ude, -um, -inu, -nu, -ya, -a
        if (preg_match('/^(.*)(il|nte|ude|um|inu|nu|ya|a)$/u', $t, $m) && mb_strlen($m[1]) >= 3) {
            $candidateStem = $m[1];
            if (in_array($candidateStem, ['section', 'office', 'site', 'portfolio', 'link', 'details', 'reel', 'reels', 'package', 'service', 'client', 'work', 'project', 'team', 'member', 'video', 'design', 'location'])) {
                $cleanTokens[] = $candidateStem;
            }
        }

        if (!in_array($t, $stopWords)) {
            $cleanTokens[] = $t;
            if (isset($synonyms[$t])) {
                foreach ($synonyms[$t] as $syn) {
                    $cleanTokens[] = $syn;
                }
            }
        }
    }
    
    return array_values(array_unique($cleanTokens));
}

/**
 * Universal Harvester: inspects entire MySQL database schema and indexes ALL tables
 */
function refreshSiteKnowledge($pdo) {
    $knowledgeDir = __DIR__ . '/../data';
    if (!is_dir($knowledgeDir)) {
        @mkdir($knowledgeDir, 0777, true);
    }

    // 1. Discover all tables in the database
    $tablesStmt = $pdo->query("SHOW TABLES");
    $allTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    $tableSchemas = [];
    $tableStats = [];
    $searchEntities = [];
    $customTablesData = [];

    // Inspect columns & row counts for EVERY table
    foreach ($allTables as $tbl) {
        try {
            $colStmt = $pdo->query("SHOW COLUMNS FROM `{$tbl}`");
            $cols = $colStmt->fetchAll(PDO::FETCH_ASSOC);
            $tableSchemas[$tbl] = array_map(function($c) {
                return [
                    'field' => $c['Field'],
                    'type' => $c['Type'],
                    'null' => $c['Null'],
                    'key' => $c['Key'],
                    'default' => $c['Default']
                ];
            }, $cols);

            $cntStmt = $pdo->query("SELECT COUNT(*) FROM `{$tbl}`");
            $tableStats[$tbl] = (int)$cntStmt->fetchColumn();
        } catch (Exception $eSchema) {
            $tableSchemas[$tbl] = [];
            $tableStats[$tbl] = 0;
        }
    }

    // =========================================================================
    // 2. Process Core Studio Tables
    // =========================================================================

    // A. site_settings
    $settings = [];
    if (in_array('site_settings', $allTables)) {
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $k = $row['setting_key'];
                $v = (string)$row['setting_value'];
                $settings[$k] = $v;

                // Only index meaningful public informational settings for search; exclude internal technical keys and flags
                $allowedPublicSettings = [
                    'contact_address', 'contact_email', 'contact_phone1', 'contact_phone2',
                    'about_us_desc1', 'about_us_desc2', 'about_philosophy_desc', 'hero_mission_text',
                    'testimonial_quote'
                ];
                if (!in_array($k, $allowedPublicSettings)) {
                    continue;
                }

                $label = ucwords(str_replace('_', ' ', $k));
                $searchEntities[] = [
                    'type' => 'setting',
                    'id' => $k,
                    'title' => $label,
                    'content' => $v,
                    'keywords' => extractKeywords($label . ' ' . $v),
                    'meta' => ['key' => $k, 'value' => $v]
                ];
            }
        } catch (Exception $eSettings) {}
    }

    // B. services
    $services = [];
    if (in_array('services', $allTables)) {
        try {
            $stmt = $pdo->query("SELECT * FROM services ORDER BY order_index ASC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $services[] = $row;
                $searchEntities[] = [
                    'type' => 'service',
                    'id' => $row['id'] ?? $row['title'],
                    'title' => $row['title'],
                    'content' => $row['description'],
                    'keywords' => extractKeywords($row['title'] . ' ' . $row['description'] . ' service services creative agency deliverable'),
                    'meta' => $row
                ];
            }
        } catch (Exception $eServices) {}
    }

    // C. pricing_packages
    $packages = [];
    if (in_array('pricing_packages', $allTables)) {
        try {
            $stmt = $pdo->query("SELECT * FROM pricing_packages ORDER BY order_index ASC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $featuresList = array_values(array_filter(array_map(function($item) {
                    return trim(html_entity_decode($item, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                }, explode("\n", str_replace("\r", "", $row['features'])))));
                $cleanPrice = str_ireplace(['&#8377;', '₹', 'rs.', 'inr'], '', $row['price']);
                $pkgData = [
                    'id' => $row['id'] ?? 0,
                    'name' => $row['name'],
                    'price_label' => $row['price_label'],
                    'price' => trim($cleanPrice),
                    'raw_price' => $row['price'],
                    'features' => $featuresList,
                    'is_popular' => (bool)($row['is_popular'] ?? 0),
                    'order_index' => (int)($row['order_index'] ?? 0)
                ];
                $packages[] = $pkgData;

                $searchEntities[] = [
                    'type' => 'pricing_package',
                    'id' => $row['id'] ?? $row['name'],
                    'title' => $row['name'] . ' Package',
                    'content' => $row['price_label'] . ' ₹' . trim($cleanPrice) . ' - Features: ' . implode(', ', $featuresList),
                    'keywords' => extractKeywords($row['name'] . ' ' . $row['price'] . ' ' . implode(' ', $featuresList) . ' price pricing package cost rate card quotation'),
                    'meta' => $pkgData
                ];
            }
        } catch (Exception $ePackages) {}
    }

    // D. portfolio_categories
    $categories = [];
    if (in_array('portfolio_categories', $allTables)) {
        try {
            $catStmt = $pdo->query("SELECT * FROM portfolio_categories ORDER BY order_index ASC, id ASC");
            while ($cRow = $catStmt->fetch(PDO::FETCH_ASSOC)) {
                $categories[] = $cRow;
                $searchEntities[] = [
                    'type' => 'portfolio_category',
                    'id' => $cRow['slug'] ?? $cRow['name'],
                    'title' => $cRow['name'] . ' Category',
                    'content' => 'Portfolio showcase category for ' . $cRow['name'] . ' projects.',
                    'keywords' => extractKeywords($cRow['name'] . ' ' . $cRow['slug'] . ' category portfolio showcase works gallery'),
                    'meta' => $cRow
                ];
            }
        } catch (Exception $eCat) {}
    }

    // E. portfolio_items
    $portfolio = [];
    $portfolioFlat = [];
    $latestPortfolio = [];
    if (in_array('portfolio_items', $allTables)) {
        try {
            $stmt = $pdo->query("SELECT * FROM portfolio_items ORDER BY category ASC, order_index ASC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cat = $row['category'] ?: 'general';
                if (!isset($portfolio[$cat])) {
                    $portfolio[$cat] = [];
                }
                $itemData = [
                    'id' => $row['id'] ?? 0,
                    'category' => $cat,
                    'tag' => $row['tag'] ?? '',
                    'title' => $row['title'] ?? '',
                    'description' => $row['description'] ?? '',
                    'link' => $row['link'] ?? '',
                    'image_path' => $row['image_path'] ?? '',
                    'is_launching_soon' => !empty($row['is_launching_soon'])
                ];
                $portfolio[$cat][] = $itemData;
                $portfolioFlat[] = $itemData;

                $searchEntities[] = [
                    'type' => 'portfolio',
                    'id' => $row['id'] ?? $row['title'],
                    'title' => $row['title'],
                    'content' => ($row['tag'] ? $row['tag'] . ' — ' : '') . $row['description'] . ($row['link'] ? ' (Link: ' . $row['link'] . ')' : '') . (!empty($row['is_launching_soon']) ? ' [Launching Soon]' : ''),
                    'keywords' => extractKeywords($row['title'] . ' ' . $row['tag'] . ' ' . $row['description'] . ' ' . $cat . ' project portfolio case study website video commercial latest recent work'),
                    'meta' => $itemData
                ];
            }

            // Fetch latest portfolio items ordered by ID DESC
            $lStmt = $pdo->query("SELECT * FROM portfolio_items ORDER BY id DESC LIMIT 5");
            $latestPortfolio = $lStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ePort) {}
    }

    // F. team_members
    $team = [];
    if (in_array('team_members', $allTables)) {
        try {
            $stmt = $pdo->query("SELECT * FROM team_members ORDER BY order_index ASC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $team[] = $row;
                $roleLower = strtolower($row['role'] ?? '');
                $extraKeywords = 'team leadership staff head member who aaranu aara';
                $roleTokens = preg_split('/[\s\-\_\/]+/', $roleLower);
                foreach ($roleTokens as $rt) {
                    if (strlen($rt) >= 3) {
                        $extraKeywords .= ' ' . $rt;
                    }
                }
                if (stripos($roleLower, 'developer') !== false || stripos($roleLower, 'programm') !== false || stripos($roleLower, 'coder') !== false || stripos($roleLower, 'engineer') !== false) {
                    $extraKeywords .= ' developer devolper devloper developr web developer web devolper frontend backend programmer coder software engineer architectures app';
                }
                if (stripos($roleLower, 'design') !== false || stripos($roleLower, 'artist') !== false) {
                    $extraKeywords .= ' designer desiner designr graphic designer branding typography visual artist creator ui ux';
                }
                if (stripos($roleLower, 'director') !== false || stripos($roleLower, 'lead') !== false || stripos($roleLower, 'head') !== false || stripos($roleLower, 'manager') !== false) {
                    $extraKeywords .= ' director managing director tech director founder ceo owner executive leadership head manager';
                }
                if (stripos($roleLower, 'video') !== false || stripos($roleLower, 'animat') !== false || stripos($roleLower, 'motion') !== false || stripos($roleLower, 'cinemat') !== false || stripos($roleLower, 'edit') !== false) {
                    $extraKeywords .= ' video animator animation 3d motion cinematic editor cinematography shoot camera';
                }
                if (stripos($roleLower, 'content') !== false || stripos($roleLower, 'copy') !== false || stripos($roleLower, 'writ') !== false) {
                    $extraKeywords .= ' content writer copywriter author strategist articles scripts copywriting';
                }
                if (stripos($roleLower, 'market') !== false || stripos($roleLower, 'seo') !== false || stripos($roleLower, 'ad') !== false || stripos($roleLower, 'social') !== false) {
                    $extraKeywords .= ' marketing marketer seo ads campaigns meta google social media growth outreach';
                }

                $searchEntities[] = [
                    'type' => 'team',
                    'id' => $row['id'] ?? $row['name'],
                    'title' => $row['name'],
                    'content' => $row['role'] . ' at Perumalil Creative studio.',
                    'keywords' => extractKeywords($row['name'] . ' ' . $row['role'] . ' ' . $extraKeywords),
                    'meta' => array_merge($row, ['role' => $row['role'] ?? ''])
                ];
            }
        } catch (Exception $eTeam) {}
    }

    // G. faq_items
    $faqs = [];
    if (in_array('faq_items', $allTables)) {
        try {
            $stmt = $pdo->query("SELECT * FROM faq_items ORDER BY order_index ASC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $faqs[] = $row;
                $searchEntities[] = [
                    'type' => 'faq',
                    'id' => $row['id'] ?? $row['question'],
                    'title' => $row['question'],
                    'content' => $row['answer'],
                    'keywords' => extractKeywords($row['question'] . ' ' . $row['answer'] . ' faq question help answer workflow revision turnaround'),
                    'meta' => $row
                ];
            }
        } catch (Exception $eFaq) {}
    }

    // H. intro_pillars
    $pillars = [];
    if (in_array('intro_pillars', $allTables)) {
        try {
            $stmt = $pdo->query("SELECT * FROM intro_pillars ORDER BY order_index ASC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $pillars[] = $row;
                $searchEntities[] = [
                    'type' => 'pillar',
                    'id' => $row['id'] ?? $row['title'],
                    'title' => $row['title'],
                    'content' => $row['description'],
                    'keywords' => extractKeywords($row['title'] . ' ' . $row['description'] . ' pillar standard strength foundation core value'),
                    'meta' => $row
                ];
            }
        } catch (Exception $ePillars) {}
    }

    // I. collaborators
    $collaborators = [];
    if (in_array('collaborators', $allTables)) {
        try {
            $stmt = $pdo->query("SELECT * FROM collaborators ORDER BY order_index ASC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $collaborators[] = $row;
                $searchEntities[] = [
                    'type' => 'collaborator',
                    'id' => $row['id'] ?? $row['name'],
                    'title' => $row['name'],
                    'content' => 'Trusted Studio Partner / Brand Collaborator of Perumalil Creative.',
                    'keywords' => extractKeywords($row['name'] . ' collaborator partner client brand institution trusted'),
                    'meta' => $row
                ];
            }
        } catch (Exception $eCollab) {}
    }

    // J. inquiries (Safe anonymized insights & telemetry)
    $inquiryStats = [
        'total' => 0,
        'by_status' => [],
        'popular_packages' => [],
        'recent_count_7d' => 0
    ];
    if (in_array('inquiries', $allTables)) {
        try {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM inquiries");
            $inquiryStats['total'] = (int)$countStmt->fetchColumn();

            // Status breakdown
            $statusStmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM inquiries GROUP BY status");
            $inquiryStats['by_status'] = $statusStmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // Popular packages
            $popStmt = $pdo->query("SELECT package_name, COUNT(*) as cnt FROM inquiries WHERE package_name != '' GROUP BY package_name ORDER BY cnt DESC LIMIT 5");
            $inquiryStats['popular_packages'] = $popStmt->fetchAll(PDO::FETCH_ASSOC);

            // Inquiries created in last 7 days
            try {
                $sevenDays = $pdo->query("SELECT COUNT(*) FROM inquiries WHERE created_at >= NOW() - INTERVAL 7 DAY");
                $inquiryStats['recent_count_7d'] = (int)$sevenDays->fetchColumn();
            } catch (Exception $eDate) {}

            $searchEntities[] = [
                'type' => 'inquiries_telemetry',
                'id' => 'inquiries_overview',
                'title' => 'Client Inquiries & Lead Pipeline Telemetry',
                'content' => 'Total visitor inquiries captured: ' . $inquiryStats['total'] . '. Active lead reception status for web architecture and video production.',
                'keywords' => extractKeywords('inquiries leads clients quotes orders inquiries telemetry pipeline'),
                'meta' => $inquiryStats
            ];
        } catch (Exception $eInq) {}
    }

    // K. admin_users (Safe schema inspection - excludes passwords)
    $adminUsers = [];
    if (in_array('admin_users', $allTables)) {
        try {
            // Find existing columns in admin_users
            $adminColNames = array_column($tableSchemas['admin_users'] ?? [], 'field');
            $fieldsToSelect = ['id', 'username'];
            if (in_array('email', $adminColNames)) $fieldsToSelect[] = 'email';
            if (in_array('created_at', $adminColNames)) $fieldsToSelect[] = 'created_at';

            $admQuery = "SELECT " . implode(', ', $fieldsToSelect) . " FROM admin_users";
            $admStmt = $pdo->query($admQuery);
            while ($admRow = $admStmt->fetch(PDO::FETCH_ASSOC)) {
                $adminUsers[] = [
                    'id' => $admRow['id'],
                    'username' => $admRow['username'],
                    'created_at' => $admRow['created_at'] ?? null
                ];
                $searchEntities[] = [
                    'type' => 'admin_user',
                    'id' => $admRow['username'],
                    'title' => 'Portal Administrator ' . $admRow['username'],
                    'content' => 'Authorized Studio Portal Administrator (' . $admRow['username'] . ') for Perumalil Creative.',
                    'keywords' => extractKeywords($admRow['username'] . ' admin administrator portal credentials user management'),
                    'meta' => ['username' => $admRow['username']]
                ];
            }
        } catch (Exception $eAdm) {}
    }

    // =========================================================================
    // 3. Universal Indexing for Any Other / Future Custom Database Tables
    // =========================================================================
    $knownCoreTables = [
        'site_settings', 'services', 'pricing_packages', 'portfolio_categories',
        'portfolio_items', 'team_members', 'faq_items', 'intro_pillars',
        'collaborators', 'inquiries', 'admin_users'
    ];

    foreach ($allTables as $tbl) {
        if (in_array($tbl, $knownCoreTables)) continue;

        try {
            $colNames = array_column($tableSchemas[$tbl] ?? [], 'field');
            // Select non-sensitive columns (exclude passwords/tokens)
            $safeCols = array_filter($colNames, function($c) {
                return !preg_match('/(pass|token|secret|salt|hash|auth)/i', $c);
            });
            if (empty($safeCols)) continue;

            $cQuery = "SELECT " . implode(', ', array_map(function($c) { return "`$c`"; }, $safeCols)) . " FROM `{$tbl}` LIMIT 500";
            $cStmt = $pdo->query($cQuery);
            $customRows = $cStmt->fetchAll(PDO::FETCH_ASSOC);
            $customTablesData[$tbl] = $customRows;

            // Index each row
            foreach ($customRows as $rIdx => $cRow) {
                // Find a good title field
                $rowTitle = '';
                foreach (['title', 'name', 'label', 'subject', 'heading', 'slug', 'id'] as $titleCandidate) {
                    if (isset($cRow[$titleCandidate]) && !empty($cRow[$titleCandidate])) {
                        $rowTitle = (string)$cRow[$titleCandidate];
                        break;
                    }
                }
                if (empty($rowTitle)) {
                    $rowTitle = ucwords(str_replace('_', ' ', $tbl)) . ' #' . ($rIdx + 1);
                }

                // Compose content from all text fields
                $rowContentParts = [];
                foreach ($cRow as $k => $v) {
                    if (is_string($v) && strlen($v) > 0) {
                        $rowContentParts[] = ucwords(str_replace('_', ' ', $k)) . ': ' . $v;
                    }
                }
                $rowContent = implode(' | ', $rowContentParts);

                $searchEntities[] = [
                    'type' => 'custom_table_row',
                    'id' => $tbl . '_' . ($cRow['id'] ?? $rIdx),
                    'title' => $rowTitle,
                    'content' => $rowContent,
                    'keywords' => extractKeywords($tbl . ' ' . $rowTitle . ' ' . $rowContent),
                    'meta' => [
                        'table' => $tbl,
                        'data' => $cRow
                    ]
                ];
            }
        } catch (Exception $eCustom) {}
    }

    // =========================================================================
    // 3.5. Universal Static Website Content & Social Channels Scanner
    // =========================================================================
    // A. Official Social Media Channels
    $searchEntities[] = [
        'type' => 'social_media',
        'id' => 'social_channels',
        'title' => 'Official Social Media Channels & Handles',
        'content' => 'Instagram: https://www.instagram.com/perumalil_creative?igsh=eDQyc3d3eGo2eGRi (@perumalil_creative) | WhatsApp Support: https://wa.me/919946020716 (+91 99460 20716) | Facebook: https://www.facebook.com/share/1CRr4Cunuz/ | YouTube: https://youtube.com/@perumalilcreative?si=yepc18uv3rxStF7j',
        'keywords' => extractKeywords('instagram insta ig facebook fb youtube yt social media accounts channel page handle follow link whatsapp online connect profile'),
        'meta' => [
            'instagram' => 'https://www.instagram.com/perumalil_creative?igsh=eDQyc3d3eGo2eGRi',
            'whatsapp' => 'https://wa.me/919946020716',
            'facebook' => 'https://www.facebook.com/share/1CRr4Cunuz/',
            'youtube' => 'https://youtube.com/@perumalilcreative?si=yepc18uv3rxStF7j'
        ]
    ];

    // B. Studio Track Record, Metrics & Badges
    $searchEntities[] = [
        'type' => 'site_content',
        'id' => 'studio_metrics',
        'title' => 'Studio Track Record & Metrics',
        'content' => '25+ Client Partners, 98% Visual Impact Rating, 50+ Cinematic Releases. Engineered in Vazhakulam, Kerala with international creative standards.',
        'keywords' => extractKeywords('metrics stats rating visual impact 98% 25+ client partners 50+ releases cinematic numbers performance credibility track record'),
        'meta' => ['section' => 'intro_metrics']
    ];

    // C. Studio Landmark & Proximity
    $searchEntities[] = [
        'type' => 'site_content',
        'id' => 'studio_landmark',
        'title' => 'Headquarters Location & Landmark',
        'content' => 'First Floor, Kaimalayil Plaza, Opposite Viswajyothy College of Engineering and Technology (VJCET), Avoly P.O., Vazhakulam, Kerala - 686670. GPS: 9.9487° N, 76.6294° E.',
        'keywords' => extractKeywords('viswajyothy college vjcet opposite kaimalayil plaza avoly vazhakulam muvattupuzha ernakulam kerala office landmark where route directions location'),
        'meta' => ['section' => 'location']
    ];

    // D. Scan Component Files for any other textual content
    $compDir = __DIR__ . '/../components';
    $siteFiles = glob($compDir . '/*.php') ?: [];
    $siteFiles[] = __DIR__ . '/../index.php';

    foreach ($siteFiles as $filePath) {
        if (!is_file($filePath)) continue;
        $baseName = basename($filePath, '.php');
        if (in_array($baseName, ['ai_assistant', 'floating_actions', 'launching_soon_modal'])) continue;

        $rawCode = @file_get_contents($filePath);
        if (!$rawCode) continue;

        // Strip PHP code
        $noPhp = preg_replace('/<\?php.*?\?>/s', '', $rawCode);
        // Strip scripts & styles
        $cleanHtml = preg_replace('/<(script|style)\b[^>]*>(.*?)<\/(script|style)>/is', '', $noPhp);
        // Strip tags
        $plainText = strip_tags($cleanHtml);
        $plainText = html_entity_decode($plainText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Extract discrete meaningful sentences / headings
        $lines = preg_split('/[\r\n]+/', $plainText);
        $meaningfulChunks = [];
        foreach ($lines as $ln) {
            $ln = trim(preg_replace('/\s+/', ' ', $ln));
            if (mb_strlen($ln) >= 20 && !preg_match('/^[\d\s\W]+$/u', $ln)) {
                $meaningfulChunks[] = $ln;
            }
        }

        if (!empty($meaningfulChunks)) {
            $chunkText = implode(' ', array_slice($meaningfulChunks, 0, 15));
            $searchEntities[] = [
                'type' => 'site_content',
                'id' => 'component_' . $baseName,
                'title' => ucwords(str_replace('_', ' ', $baseName)) . ' Section Content',
                'content' => $chunkText,
                'keywords' => extractKeywords($baseName . ' ' . $chunkText),
                'meta' => ['component' => $baseName, 'file' => basename($filePath)]
            ];
        }
    }

    // =========================================================================
    // 4. Studio Global Narrative Synthesis
    // =========================================================================
    $studioNarrative = [
        'name' => 'Perumalil Creative',
        'tagline' => 'Design Your Every Occasion.',
        'mission' => $settings['hero_mission_text'] ?? 'We design premium creative identities, cinematic brand reels, and luxury digital architectures built on strict mathematical grid parameters.',
        'address' => strip_tags(str_replace('<br>', ', ', $settings['contact_address'] ?? 'First Floor, Kaimalayil Plaza, Opposite Viswajyothy College, Avoly P.O., Vazhakulam, Kerala - 686670')),
        'coordinates' => $settings['hero_coords'] ?? '9.9487° N, 76.6294° E',
        'map_latitude' => $settings['map_latitude'] ?? '9.9487',
        'map_longitude' => $settings['map_longitude'] ?? '76.6294',
        'primary_phone' => $settings['contact_phone1'] ?? '+91 99460 20716',
        'secondary_phone' => $settings['contact_phone2'] ?? '+91 99460 20724',
        'email' => $settings['contact_email'] ?? 'hello@perumalilcreative.com',
        'google_maps' => $settings['location_maps_link'] ?? 'https://maps.app.goo.gl/sVnxVtQPHHKY5nWz8',
        'leadership' => [
            'md_name' => $settings['testimonial_author'] ?? 'Dijo J Perumaly',
            'md_role' => $settings['testimonial_role'] ?? 'Managing Director',
            'quote' => $settings['testimonial_quote'] ?? 'Small businesses are the heart of our communities. We craft marketing strategies that tell your story and help your dreams grow.',
            'team_members' => array_map(function($m) { return ['name' => $m['name'], 'role' => $m['role']]; }, $team)
        ],
        'about_narrative' => ($settings['about_us_desc1'] ?? '') . ' ' . ($settings['about_us_desc2'] ?? ''),
        'philosophy' => $settings['about_philosophy_desc'] ?? 'We believe local businesses in Vazhakulam, Kerala deserve digital architectures and marketing campaigns that command respect on a global stage.',
        'vision' => $settings['intro_vision_text'] ?? 'We specialize in high-quality video production for your brand stories and advertising campaigns.',
        'details' => [
            'meticulous_detail' => ($settings['about_detail1_title'] ?? '') . ': ' . ($settings['about_detail1_desc'] ?? ''),
            'strategic_growth' => ($settings['about_detail2_title'] ?? '') . ': ' . ($settings['about_detail2_desc'] ?? '')
        ]
    ];

    $dbFingerprint = getDatabaseFingerprint($pdo);

    // Build Master Knowledge Structure
    $masterKnowledge = [
        'generated_at' => date('c'),
        'db_fingerprint' => $dbFingerprint,
        'total_tables_indexed' => count($allTables),
        'tables_list' => $allTables,
        'table_schemas' => $tableSchemas,
        'table_stats' => $tableStats,
        'entities_count' => count($searchEntities),
        'studio' => $studioNarrative,
        'site_settings' => $settings,
        'services' => $services,
        'pricing_packages' => $packages,
        'portfolio' => $portfolio,
        'portfolio_flat' => $portfolioFlat,
        'latest_portfolio' => $latestPortfolio,
        'portfolio_categories' => $categories,
        'team' => $team,
        'faq' => $faqs,
        'pillars' => $pillars,
        'collaborators' => $collaborators,
        'inquiry_stats' => $inquiryStats,
        'admin_users' => $adminUsers,
        'custom_tables_data' => $customTablesData
    ];

    // Save full knowledge graph
    file_put_contents($knowledgeDir . '/site_knowledge.json', json_encode($masterKnowledge, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    // Save full search index
    file_put_contents($knowledgeDir . '/database_search_index.json', json_encode($searchEntities, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    return [
        'knowledge' => $masterKnowledge,
        'search_index' => $searchEntities
    ];
}

// Direct invocation (via HTTP GET/POST or CLI)
if (php_sapi_name() === 'cli' || basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'knowledge_indexer.php') {
    try {
        $result = refreshSiteKnowledge($pdo);
        echo json_encode([
            'success' => true,
            'message' => 'Complete MySQL database studied and indexed successfully!',
            'generated_at' => $result['knowledge']['generated_at'],
            'stats' => [
                'tables_indexed' => $result['knowledge']['total_tables_indexed'],
                'tables' => $result['knowledge']['tables_list'],
                'table_stats' => $result['knowledge']['table_stats'],
                'searchable_entities' => count($result['search_index']),
                'services_count' => count($result['knowledge']['services']),
                'packages_count' => count($result['knowledge']['pricing_packages']),
                'portfolio_items_count' => count($result['knowledge']['portfolio_flat']),
                'categories_count' => count($result['knowledge']['portfolio_categories']),
                'team_members_count' => count($result['knowledge']['team']),
                'faq_items_count' => count($result['knowledge']['faq']),
                'pillars_count' => count($result['knowledge']['pillars']),
                'collaborators_count' => count($result['knowledge']['collaborators']),
                'admin_users_count' => count($result['knowledge']['admin_users'])
            ]
        ], JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
