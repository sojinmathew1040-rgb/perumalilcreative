<?php
// api/chat.php - Universal Hybrid AI Knowledge Engine for Nova
// Studies ALL tables and records from the MySQL database with zero external API dependencies
// Supports optional Google Gemini API acceleration when configured in site_settings

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/knowledge_indexer.php';

// 1. Parse Request (Supports JSON POST, form POST, or GET for diagnostics)
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

$userMessage = '';
$chatHistory = [];

if (is_array($input) && isset($input['message'])) {
    $userMessage = trim($input['message']);
    $chatHistory = $input['history'] ?? [];
} elseif (isset($_POST['message'])) {
    $userMessage = trim($_POST['message']);
    $chatHistory = isset($_POST['history']) && is_array($_POST['history']) ? $_POST['history'] : [];
} elseif (isset($_GET['message']) || isset($_GET['q'])) {
    $userMessage = trim($_GET['message'] ?? $_GET['q'] ?? '');
}

if (empty($userMessage)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Empty message prompt. Provide a "message" parameter.']);
    exit;
}

// 2. Load or refresh site knowledge & database search index with zero-latency live sync
$knowledgePath = __DIR__ . '/../data/site_knowledge.json';
$indexPath = __DIR__ . '/../data/database_search_index.json';

$currentDbFingerprint = getDatabaseFingerprint($pdo);
$cacheExpired = (!file_exists($knowledgePath) || !file_exists($indexPath) || (time() - filemtime($knowledgePath)) > 120);

$knowledge = file_exists($knowledgePath) ? json_decode(file_get_contents($knowledgePath), true) : null;
$searchIndex = file_exists($indexPath) ? json_decode(file_get_contents($indexPath), true) : null;

// Re-harvest if cache expired, files missing, or database changed
if ($cacheExpired || !$knowledge || !$searchIndex || !isset($knowledge['db_fingerprint']) || $knowledge['db_fingerprint'] !== $currentDbFingerprint) {
    $harvest = refreshSiteKnowledge($pdo);
    $knowledge = $harvest['knowledge'];
    $searchIndex = $harvest['search_index'];
}

// Check if AI Assistant is globally enabled by administrator
$aiEnabledSetting = $knowledge['site_settings']['ai_enabled'] ?? '1';
if ($aiEnabledSetting === '0' || $aiEnabledSetting === 'false') {
    echo json_encode([
        'success' => false,
        'disabled' => true,
        'reply' => 'The AI Assistant is currently offline or disabled by the administrator.',
        'engine' => 'disabled'
    ]);
    exit;
}

// 3. Lead Capture Detection (Phone or Email in user message)
$detectedPhone = '';
$detectedEmail = '';

if (preg_match('/(\+?91[\-\s]?)?[6-9]\d{9}/', $userMessage, $phoneMatches)) {
    $detectedPhone = $phoneMatches[0];
}

if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $userMessage, $emailMatches)) {
    $detectedEmail = $emailMatches[0];
}

$leadCaptured = false;
if (!empty($detectedPhone) || !empty($detectedEmail)) {
    try {
        $leadContact = $detectedEmail ?: $detectedPhone;
        $inqStmt = $pdo->prepare("INSERT INTO inquiries (name, email, package_slug, package_name, price, message, status) VALUES (?, ?, 'nova-lead', 'AI Assistant Inquiry', 0, ?, 'New')");
        $inqStmt->execute(['Visitor via Nova AI', $leadContact, "Captured via Nova Assistant: " . $userMessage]);
        $leadCaptured = true;
    } catch (Exception $eLead) {}
}

// 4. Fuzzy Matching & Typo Tolerance Helper
function hasFuzzyMatch($query, $targets, $maxDist = 1) {
    $qLower = mb_strtolower(trim($query));
    $tokens = preg_split('/[\s,\.\?\!\;\:\-]+/u', $qLower, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($targets as $t) {
        $t = mb_strtolower($t);
        if (strpos($t, ' ') !== false) {
            if (strpos($qLower, $t) !== false) return true;
        } else {
            if (in_array($t, $tokens)) return true;
        }
    }
    if ($maxDist > 0) {
        foreach ($tokens as $token) {
            if (mb_strlen($token) < 4) continue;
            foreach ($targets as $t) {
                $t = mb_strtolower($t);
                if (strpos($t, ' ') === false && mb_strlen($t) >= 4 && abs(mb_strlen($token) - mb_strlen($t)) <= 1) {
                    if (levenshtein($token, $t) <= $maxDist) return true;
                }
            }
        }
    }
    return false;
}

// 5. Advanced Database BM25 Search Engine
function searchAllDatabaseEntities($query, $searchIndex) {
    $qLower = mb_strtolower(trim($query));
    $queryTokens = extractKeywords($qLower);
    if (empty($queryTokens)) {
        return [];
    }

    $scoredResults = [];

    foreach ($searchIndex as $entity) {
        $score = 0;
        $titleLower = mb_strtolower($entity['title']);
        $contentLower = mb_strtolower($entity['content']);
        $roleLower = mb_strtolower($entity['meta']['role'] ?? '');
        $roleWords = !empty($roleLower) ? preg_split('/[\s,\.\?\!\;\:\-]+/u', $roleLower, -1, PREG_SPLIT_NO_EMPTY) : [];

        // A. Direct full-title or full-role containment
        if (strpos($qLower, $titleLower) !== false) {
            $score += 40;
        }
        if (!empty($roleLower) && strpos($qLower, $roleLower) !== false) {
            $score += 45;
        }

        // B. Query is contained in title or role
        if (strpos($titleLower, $qLower) !== false && mb_strlen($qLower) >= 4) {
            $score += 25;
        }
        if (!empty($roleLower) && strpos($roleLower, $qLower) !== false && mb_strlen($qLower) >= 4) {
            $score += 30;
        }

        // C. Token-level matching
        $matchCount = 0;
        foreach ($queryTokens as $qToken) {
            $tokenMatched = false;

            // Title token match
            if (strpos($titleLower, $qToken) !== false) {
                $score += 15;
                $matchCount++;
                $tokenMatched = true;
            } elseif (mb_strlen($qToken) >= 4) {
                // Check fuzzy match against title words
                $titleWords = preg_split('/[\s,\.\?\!\;\:\-]+/u', $titleLower, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($titleWords as $tw) {
                    if (abs(mb_strlen($qToken) - mb_strlen($tw)) <= 2 && levenshtein($qToken, $tw) <= 1) {
                        $score += 12;
                        $matchCount++;
                        $tokenMatched = true;
                        break;
                    }
                }
            }

            // Role match (for team members)
            if (!empty($roleLower)) {
                if (strpos($roleLower, $qToken) !== false) {
                    $score += 25;
                    $matchCount++;
                    $tokenMatched = true;
                } elseif (mb_strlen($qToken) >= 4) {
                    foreach ($roleWords as $rw) {
                        if (abs(mb_strlen($qToken) - mb_strlen($rw)) <= 2 && levenshtein($qToken, $rw) <= 1) {
                            $score += 20;
                            $matchCount++;
                            $tokenMatched = true;
                            break;
                        }
                    }
                }
            }

            // Keyword match (exact or fuzzy if length >= 4)
            if (in_array($qToken, $entity['keywords'])) {
                $score += 8;
                $matchCount++;
                $tokenMatched = true;
            } elseif (mb_strlen($qToken) >= 4) {
                foreach ($entity['keywords'] as $kw) {
                    if (abs(mb_strlen($qToken) - mb_strlen($kw)) <= 2 && levenshtein($qToken, $kw) <= 1) {
                        $score += 6;
                        $matchCount++;
                        $tokenMatched = true;
                        break;
                    }
                }
            }

            // Content match
            if (strpos($contentLower, $qToken) !== false) {
                $score += 4;
            }
        }

        // Boost for multiple token matches
        if ($matchCount > 1) {
            $score += ($matchCount * 5);
        }

        // Boost high-value entity types
        if ($score > 0) {
            if ($entity['type'] === 'service') $score += 20;
            if ($entity['type'] === 'pricing_package') $score += 20;
            if ($entity['type'] === 'portfolio') $score += 15;
            if ($entity['type'] === 'team') $score += 10;
            if ($entity['type'] === 'faq') $score += 2;

            $scoredResults[] = [
                'entity' => $entity,
                'score' => $score
            ];
        }
    }

    usort($scoredResults, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    return $scoredResults;
}

// 6. Optional Gemini LLM Dispatcher
function tryGeminiAI($userMessage, $chatHistory, $knowledge, $apiKey) {
    if (empty($apiKey) || strlen($apiKey) < 15) {
        return null;
    }

    $systemContext = "You are Nova, the intelligent AI Assistant for Perumalil Creative, a premier digital agency in Vazhakulam, Kerala. "
        . "You have studied and memorized the entire MySQL database. Here is the verified database knowledge:\n"
        . "- Managing Director: " . ($knowledge['studio']['leadership']['md_name'] ?? 'Dijo J Perumaly') . "\n"
        . "- Team: " . json_encode($knowledge['studio']['leadership']['team_members'] ?? []) . "\n"
        . "- Headquarters: " . ($knowledge['studio']['address'] ?? '') . " (Coordinates: " . ($knowledge['studio']['coordinates'] ?? '') . ")\n"
        . "- Contact: Primary Phone: " . ($knowledge['studio']['primary_phone'] ?? '') . ", Secondary: " . ($knowledge['studio']['secondary_phone'] ?? '') . ", Email: " . ($knowledge['studio']['email'] ?? '') . "\n"
        . "- Services (6): " . json_encode(array_column($knowledge['services'] ?? [], 'title')) . "\n"
        . "- Pricing Packages (4): " . json_encode(array_map(function($p) { return $p['name'] . ' (' . $p['price_label'] . ' Rs.' . $p['price'] . ')'; }, $knowledge['pricing_packages'] ?? [])) . "\n"
        . "- Portfolio Showcase (" . count($knowledge['portfolio_flat'] ?? []) . " projects): " . json_encode(array_column($knowledge['portfolio_flat'] ?? [], 'title')) . "\n"
        . "- Database Tables: " . implode(', ', $knowledge['tables_list'] ?? []) . "\n"
        . "Guidelines:\n"
        . "1. Answer directly and concisely in 1 to 3 short sentences. Never write long essays or repetitive marketing fluff.\n"
        . "2. Use clean HTML formatting (<strong>, <br>, <em>, <a>).\n"
        . "3. Never invent facts outside this database.\n"
        . "4. If asked about the latest work added in portfolio, reference the most recent project and its link directly.";

    $contents = [];
    $contents[] = [
        'role' => 'user',
        'parts' => [['text' => "System Instructions: " . $systemContext]]
    ];
    $contents[] = [
        'role' => 'model',
        'parts' => [['text' => "Understood. I am Nova, fully grounded in the Perumalil Creative database."]]
    ];

    if (is_array($chatHistory)) {
        foreach (array_slice($chatHistory, -4) as $h) {
            $role = ($h['sender'] ?? 'user') === 'bot' ? 'model' : 'user';
            $text = strip_tags($h['text'] ?? '');
            if (!empty($text)) {
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $text]]
                ];
            }
        }
    }

    $contents[] = [
        'role' => 'user',
        'parts' => [['text' => $userMessage]]
    ];

    $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($apiKey);

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.4,
            'maxOutputTokens' => 600
        ]
    ]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $resJson = json_decode($response, true);
        $candidateText = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (!empty($candidateText)) {
            return $candidateText;
        }
    }

    return null;
}

// =========================================================================
// 7. Master Database Reasoning & Q&A Engine
// =========================================================================

// Helpers to dynamically resolve packages and team members from database knowledge
function getPackageByKeywords($packages, $keywords) {
    if (empty($packages)) return null;
    foreach ($packages as $pkg) {
        $nameLower = mb_strtolower($pkg['name']);
        foreach ($keywords as $kw) {
            if (strpos($nameLower, mb_strtolower($kw)) !== false) {
                return $pkg;
            }
        }
    }
    return null;
}

function getTeamMemberByKeywords($team, $keywords) {
    if (empty($team)) return null;
    foreach ($team as $m) {
        $roleLower = mb_strtolower($m['role'] ?? '');
        $nameLower = mb_strtolower($m['name'] ?? '');
        foreach ($keywords as $kw) {
            $kwLower = mb_strtolower($kw);
            if (strpos($roleLower, $kwLower) !== false || strpos($nameLower, $kwLower) !== false) {
                return $m;
            }
        }
    }
    return null;
}

// Cleanly format package pricing phrases without repeating "starts from Starting From"
function formatCleanPackageStartingPhrase($pkg) {
    $pName = htmlspecialchars($pkg['name'] ?? '');
    $pPrice = htmlspecialchars($pkg['price'] ?? '');
    $pLabel = mb_strtolower($pkg['price_label'] ?? '');
    if (strpos($pLabel, 'month') !== false) {
        return "Our <strong>{$pName}</strong> campaign management starts from <strong>₹{$pPrice}/month</strong>";
    }
    return "Our <strong>{$pName}</strong> package starts from <strong>₹{$pPrice}</strong>";
}

// Helper to fetch the latest portfolio items (live from MySQL or fallback)
function getLatestPortfolioItems($pdo, $portfolioFlat, $category = null, $limit = 1) {
    if ($pdo) {
        try {
            if ($category) {
                $stmt = $pdo->prepare("SELECT * FROM portfolio_items WHERE category = ? ORDER BY id DESC LIMIT " . (int)$limit);
                $stmt->execute([$category]);
            } else {
                $stmt = $pdo->query("SELECT * FROM portfolio_items ORDER BY id DESC LIMIT " . (int)$limit);
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                return $limit === 1 ? $rows[0] : $rows;
            }
        } catch (Exception $e) {}
    }

    $filtered = $portfolioFlat ?? [];
    if ($category) {
        $filtered = array_values(array_filter($filtered, function($item) use ($category) {
            return ($item['category'] ?? '') === $category;
        }));
    }
    if (empty($filtered)) return $limit === 1 ? null : [];
    usort($filtered, function($a, $b) {
        return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
    });
    return $limit === 1 ? $filtered[0] : array_slice($filtered, 0, $limit);
}

// 1. Universal Dynamic Team Member Matcher:
// Automatically matches ANY team member in MySQL by their name OR their role (handles any newly added post/designation!)
function matchDynamicTeamMember($q, $teamList) {
    if (empty($teamList)) return null;

    $bestMatch = null;
    $bestScore = 0;
    $qTokens = preg_split('/[\s\?\!\,\.\:\;]+/', $q);

    foreach ($teamList as $m) {
        $nameLower = mb_strtolower(trim($m['name'] ?? ''));
        $roleLower = mb_strtolower(trim($m['role'] ?? ''));
        if (empty($nameLower) && empty($roleLower)) continue;

        $score = 0;

        // Direct name match (full or individual name parts of 3+ letters)
        if (strpos($q, $nameLower) !== false) {
            $score += 70;
        } else {
            $nameParts = preg_split('/\s+/', $nameLower);
            foreach ($nameParts as $np) {
                if (mb_strlen($np) >= 3 && strpos($q, $np) !== false) {
                    $score += 45;
                    break;
                }
            }
        }

        // Full role match in query
        if (!empty($roleLower) && strpos($q, $roleLower) !== false) {
            $score += 65;
        } else {
            // Check individual role keywords (e.g. "animator", "developer", "strategist", "editor", "writer", "ceo", "cto", "md")
            $roleParts = preg_split('/[\s\-\_\/\&\,]+/', $roleLower);
            $matchedRoleParts = 0;
            foreach ($roleParts as $rp) {
                if (in_array($rp, ['and', 'the', 'for', 'of', 'in', 'our', 'all', 'creative', 'perumalil', 'studio', 'agency'])) continue;
                if (empty($rp)) continue;

                // Exact word token match (supports 3+ letter designations like 'ceo', 'cto', 'art', 'dev')
                if (mb_strlen($rp) >= 3 && in_array($rp, $qTokens)) {
                    $matchedRoleParts++;
                    continue;
                }

                // Substring for longer role words (4+ letters)
                if (mb_strlen($rp) >= 4 && strpos($q, $rp) !== false) {
                    $matchedRoleParts++;
                    continue;
                }

                // Levenshtein typo tolerance strictly for 5+ letter words to avoid false positive matches like ceo/seo
                if (mb_strlen($rp) >= 5) {
                    foreach ($qTokens as $qt) {
                        if (abs(mb_strlen($qt) - mb_strlen($rp)) <= 1 && levenshtein($qt, $rp) <= 1) {
                            $matchedRoleParts++;
                            break;
                        }
                    }
                }
            }
            if ($matchedRoleParts > 0) {
                $score += ($matchedRoleParts * 25);
            }
        }

        // Boost for role inquiry intent words (who, aaranu, aara, lead, head, person, member)
        $isAskingPerson = (
            strpos($q, 'who') !== false ||
            strpos($q, 'aaranu') !== false ||
            strpos($q, 'aaraanu') !== false ||
            strpos($q, 'aara') !== false ||
            strpos($q, 'lead') !== false ||
            strpos($q, 'head') !== false ||
            strpos($q, 'specialist') !== false ||
            strpos($q, 'person') !== false ||
            strpos($q, 'member') !== false
        );

        if ($score > 0 && $isAskingPerson) {
            $score += 20;
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestMatch = $m;
        }
    }

    $isAskingPersonQuery = (
        strpos($q, 'who') !== false || strpos($q, 'aaranu') !== false || 
        strpos($q, 'lead') !== false || strpos($q, 'head') !== false || 
        strpos($q, 'person') !== false || strpos($q, 'member') !== false
    );
    $hasDirectName = $bestMatch && !empty($bestMatch['name']) && (strpos($q, mb_strtolower($bestMatch['name'])) !== false);

    if ($bestScore >= 35 && ($isAskingPersonQuery || $hasDirectName)) {
        return ['member' => $bestMatch, 'score' => $bestScore];
    }
    return null;
}

// 2. Universal Dynamic Pricing Package Matcher:
// Matches ANY pricing package in MySQL (for any newly added pricing card)
function matchDynamicPricingPackage($q, $packages) {
    if (empty($packages)) return null;

    $isAskingPrice = (
        strpos($q, 'price') !== false ||
        strpos($q, 'cost') !== false ||
        strpos($q, 'rate') !== false ||
        strpos($q, 'rates') !== false ||
        strpos($q, 'package') !== false ||
        strpos($q, 'packages') !== false ||
        strpos($q, 'pack') !== false ||
        strpos($q, 'plan') !== false ||
        strpos($q, 'plans') !== false ||
        strpos($q, 'how much') !== false ||
        strpos($q, 'kaashu') !== false ||
        strpos($q, 'ethraya') !== false ||
        strpos($q, 'vila') !== false ||
        strpos($q, 'charges') !== false ||
        strpos($q, 'charge') !== false ||
        strpos($q, 'pricing') !== false ||
        strpos($q, 'budget') !== false ||
        strpos($q, 'quotation') !== false ||
        strpos($q, 'quote') !== false
    );

    // If query has no pricing/cost/package intent keywords, DO NOT match a pricing package card!
    if (!$isAskingPrice) {
        return null;
    }

    $bestMatch = null;
    $bestScore = 0;
    $qTokens = preg_split('/[\s\?\!\,\.\:\;]+/', $q);

    foreach ($packages as $pkg) {
        $pNameLower = mb_strtolower(trim($pkg['name'] ?? ''));
        if (empty($pNameLower)) continue;

        $score = 0;

        // Full package name match
        if (strpos($q, $pNameLower) !== false) {
            $score += 85;
        } else {
            // Significant tokens from package name
            $pTokens = preg_split('/[\s\-\_\/\&]+/', $pNameLower);
            $sigTokens = array_values(array_filter($pTokens, function($t) {
                return !in_array($t, ['and', 'for', 'the', 'with', 'our', 'all', 'per', 'package', 'packages', 'card', 'cards', 'plan', 'plans']) && mb_strlen($t) >= 3;
            }));
            $totalSig = count($sigTokens);
            $matchedTokens = 0;
            $hasDistinctToken = false;

            foreach ($sigTokens as $pt) {
                $isGeneric = in_array($pt, ['design', 'creative', 'development', 'production', 'services', 'service', 'studio']);
                if (strpos($q, $pt) !== false) {
                    $matchedTokens++;
                    if (!$isGeneric) {
                        $hasDistinctToken = true;
                    }
                } elseif (mb_strlen($pt) >= 5) {
                    foreach ($qTokens as $qt) {
                        if (abs(mb_strlen($qt) - mb_strlen($pt)) <= 1 && levenshtein($qt, $pt) <= 1) {
                            $matchedTokens++;
                            if (!$isGeneric) {
                                $hasDistinctToken = true;
                            }
                            break;
                        }
                    }
                }
            }

            if ($matchedTokens > 0) {
                // If package name has multiple tokens and ONLY generic terms matched (like "design"), don't treat it as a decisive match
                if ($totalSig > 1 && !$hasDistinctToken) {
                    $score += 15;
                } else {
                    $ratio = $totalSig > 0 ? ($matchedTokens / $totalSig) : 1;
                    $score += (int)($matchedTokens * 35 * $ratio);
                }
            }
        }

        // Distinct feature keywords (do not duplicate stopwords or repeat points)
        if (!empty($pkg['features'])) {
            $matchedFeatWords = [];
            foreach ($pkg['features'] as $feat) {
                $fLower = mb_strtolower($feat);
                $fTokens = preg_split('/[\s\-\_\/\&]+/', $fLower);
                foreach ($fTokens as $ft) {
                    if (in_array($ft, ['design', 'creative', 'service', 'services', 'work', 'custom', 'included', 'high', 'quality', 'with', 'page', 'site', 'from', 'days'])) continue;
                    if (mb_strlen($ft) >= 4 && strpos($q, $ft) !== false && !in_array($ft, $matchedFeatWords)) {
                        $matchedFeatWords[] = $ft;
                        $score += 15;
                    }
                }
            }
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestMatch = $pkg;
        }
    }

    if ($bestScore >= 35) {
        return ['package' => $bestMatch, 'score' => $bestScore];
    }
    return null;
}

// 3. Universal Dynamic Service Matcher:
// Matches ANY service in MySQL with concept expansion (e.g. logo, brochure, poster -> Graphic Design; website, ecommerce -> Website Design)
function matchDynamicService($q, $services) {
    if (empty($services)) return null;

    $bestMatch = null;
    $bestScore = 0;
    $qTokens = preg_split('/[\s\?\!\,\.\:\;]+/', $q);

    // Concept anchor map for high-accuracy intent matching
    $serviceConceptMap = [
        'Website Design' => ['website', 'web site', 'websites', 'web page', 'web design', 'web development', 'ecommerce', 'online store', 'web portal'],
        'SEO Services' => ['seo', 'search engine', 'ranking', 'rank', 'google ranking', 'organic traffic'],
        'Digital Marketing' => ['digital marketing', 'marketing', 'social media', 'social ads', 'instagram ads', 'facebook ads', 'campaign'],
        'Advertising' => ['advertising', 'advertisement', 'advertise', 'commercials', 'commercial'],
        'ID Card Design' => ['id card', 'id cards', 'identity card', 'idcard', 'student id', 'employee id'],
        'Graphic Design' => ['graphic design', 'graphics', 'logo', 'logos', 'poster', 'posters', 'brochure', 'brochures', 'banner', 'banners', 'branding', 'flyer', 'flyers', 'visiting card']
    ];

    foreach ($services as $srv) {
        $sTitle = $srv['title'] ?? '';
        $sTitleLower = mb_strtolower(trim($sTitle));
        if (empty($sTitleLower)) continue;

        $score = 0;

        // Check concept anchor map
        if (isset($serviceConceptMap[$sTitle])) {
            foreach ($serviceConceptMap[$sTitle] as $conceptTerm) {
                if (strpos($q, $conceptTerm) !== false) {
                    $score += 75;
                    break;
                }
            }
        }

        // Exact full title match (e.g. "id card design", "seo services", "website design")
        if (strpos($q, $sTitleLower) !== false) {
            $score += 90;
        } else {
            $sTokens = preg_split('/[\s\-\_\/\&]+/', $sTitleLower);
            $matchedTokens = 0;
            $totalSigTokens = 0;
            foreach ($sTokens as $st) {
                if (in_array($st, ['services', 'service', 'and', 'the', 'for', 'our', 'all'])) continue;
                $totalSigTokens++;
                if (mb_strlen($st) >= 3) {
                    if (strpos($q, $st) !== false) {
                        $matchedTokens++;
                    } elseif (mb_strlen($st) >= 5) {
                        foreach ($qTokens as $qt) {
                            if (abs(mb_strlen($qt) - mb_strlen($st)) <= 1 && levenshtein($qt, $st) <= 1) {
                                $matchedTokens++;
                                break;
                            }
                        }
                    }
                }
            }
            if ($matchedTokens > 0) {
                $ratio = $totalSigTokens > 0 ? ($matchedTokens / $totalSigTokens) : 1;
                $score += (int)($matchedTokens * 35 * $ratio);
            }
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestMatch = $srv;
        }
    }

    if ($bestScore >= 30) {
        return ['service' => $bestMatch, 'score' => $bestScore];
    }
    return null;
}

function runMasterDatabaseEngine($query, &$k, &$searchIndex, $leadCaptured, $pdo = null) {
    $q = mb_strtolower(trim($query));
    $waPhone = preg_replace('/[^0-9]/', '', $k['studio']['primary_phone'] ?? '919946020716');

    // If visitor provided contact details
    if ($leadCaptured) {
        return [
            'reply' => "<strong>Thank you! Your contact details have been recorded.</strong><br>Our team will reach out to you shortly. You can also connect directly on WhatsApp!",
            'chips' => [
                ['label' => '💬 Direct WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone],
                ['label' => '📞 Call Studio', 'action' => 'call', 'target' => $k['studio']['primary_phone']],
                ['label' => '🎬 Explore Portfolio', 'action' => 'scroll', 'target' => '#portfolio']
            ]
        ];
    }

    // 0. Comprehensive Database Learning, Audit, Sync, and Table Inspection
    if (
        hasFuzzyMatch($q, [
            'study database', 'study the database', 'study all the database', 'study all database',
            'study all', 'studied database', 'learn database', 'learn all database', 'learn everything',
            'learn everything on database', 'learn everything on the database', 'what is in database',
            'what do you know', 'everything in database', 'all database', 'all the database',
            'sync database', 'refresh database', 'database tables', 'database table', 'show database',
            'about database', 'database info', 'database summary', 'database audit', 'list tables',
            'how many tables', 'how many records'
        ], 1) ||
        (strpos($q, 'database') !== false && (
            strpos($q, 'study') !== false || strpos($q, 'learn') !== false || strpos($q, 'table') !== false ||
            strpos($q, 'what') !== false || strpos($q, 'how many') !== false || strpos($q, 'list') !== false ||
            strpos($q, 'show') !== false || strpos($q, 'all') !== false || strpos($q, 'schema') !== false
        ))
    ) {
        // Trigger live refresh
        if ($pdo) {
            $fresh = refreshSiteKnowledge($pdo);
            $k = $fresh['knowledge'];
            $searchIndex = $fresh['search_index'];
        }

        $tablesList = $k['tables_list'] ?? [];
        $tStats = $k['table_stats'] ?? [];
        $totalEntities = count($searchIndex);
        $genTime = date('M j, Y - g:i A', strtotime($k['generated_at'] ?? 'now'));

        // Dynamically format database audit summary from live MySQL records
        $teamSummaryParts = [];
        foreach (($k['team'] ?? []) as $tm) {
            $teamSummaryParts[] = htmlspecialchars($tm['role']) . ' ' . htmlspecialchars($tm['name']);
        }
        $teamSummary = !empty($teamSummaryParts) ? implode(', ', $teamSummaryParts) : 'Core leadership team';
        $srvSummary = !empty($k['services']) ? implode(', ', array_column($k['services'], 'title')) : 'Digital Services';

        $reply = "✅ <strong>Live Database Audit (Synced: {$genTime}):</strong><br>";
        $reply .= "• 👥 <strong>Team:</strong> {$teamSummary}<br>";
        $reply .= "• 🎨 <strong>Services:</strong> {$srvSummary}<br>";
        $reply .= "• 💰 <strong>Pricing:</strong> " . count($k['pricing_packages']) . " transparent packages<br>";
        $reply .= "• 🎬 <strong>Portfolio:</strong> " . count($k['portfolio_flat']) . " showcase projects<br>";
        $reply .= "• 📍 <strong>Location:</strong> Vazhakulam, Kerala";

        return [
            'reply' => $reply,
            'chips' => [
                ['label' => '✨ Latest Work Added', 'action' => 'chat', 'target' => 'What is the latest work added in portfolio?'],
                ['label' => '👥 Who is the Director?', 'action' => 'chat', 'target' => 'Who is the director of the company?'],
                ['label' => "🎨 Studio Services", 'action' => 'chat', 'target' => 'What services do you offer?'],
                ['label' => '💰 Rate Card Packages', 'action' => 'chat', 'target' => 'What are your pricing packages?']
            ]
        ];
    }

    // 1. Direct Table Inquiries (e.g. "show table services", "what is in table team_members")
    if (preg_match('/(?:table|show table|what is in table)\s+([a-zA-Z0-9_]+)/i', $q, $tblMatch)) {
        $targetTable = $tblMatch[1];
        if (isset($k['table_schemas'][$targetTable])) {
            $cols = $k['table_schemas'][$targetTable];
            $rowCount = $k['table_stats'][$targetTable] ?? 0;
            $colNames = array_column($cols, 'field');

            $reply = "📊 <strong>Table: <code>{$targetTable}</code></strong> (" . $rowCount . " rows)<br>";
            $reply .= "<strong>Schema Columns:</strong> " . implode(', ', array_map(function($c) { return "<code>$c</code>"; }, $colNames)) . "<br><br>";

            if ($targetTable === 'team_members') {
                $reply .= "<strong>Recorded Members:</strong><br>";
                foreach ($k['team'] as $tm) {
                    $reply .= "• 👤 <strong>" . htmlspecialchars($tm['name']) . "</strong> — " . htmlspecialchars($tm['role']) . "<br>";
                }
            } elseif ($targetTable === 'services') {
                $reply .= "<strong>Recorded Services:</strong><br>";
                foreach ($k['services'] as $srv) {
                    $reply .= "• 🎨 <strong>" . htmlspecialchars($srv['title']) . "</strong>: " . htmlspecialchars($srv['description']) . "<br>";
                }
            } elseif ($targetTable === 'pricing_packages') {
                $reply .= "<strong>Recorded Pricing Packages:</strong><br>";
                foreach ($k['pricing_packages'] as $pkg) {
                    $reply .= "• 💰 <strong>" . htmlspecialchars($pkg['name']) . "</strong> (" . htmlspecialchars($pkg['price_label']) . " ₹" . htmlspecialchars($pkg['price']) . ")<br>";
                }
            } elseif ($targetTable === 'portfolio_items') {
                $reply .= "<strong>Sample Recorded Projects:</strong><br>";
                foreach (array_slice($k['portfolio_flat'], 0, 6) as $pi) {
                    $reply .= "• 🎬 <strong>" . htmlspecialchars($pi['title']) . "</strong> (" . htmlspecialchars($pi['category']) . ")<br>";
                }
                $reply .= "...and " . (count($k['portfolio_flat']) - 6) . " more projects recorded.";
            } elseif ($targetTable === 'faq_items') {
                $reply .= "<strong>Recorded FAQs:</strong><br>";
                foreach ($k['faq'] as $f) {
                    $reply .= "• ❓ <strong>" . htmlspecialchars($f['question']) . "</strong><br>";
                }
            } elseif ($targetTable === 'intro_pillars') {
                $reply .= "<strong>Recorded Intro Pillars:</strong><br>";
                foreach ($k['pillars'] as $pil) {
                    $reply .= "• ⚡ <strong>" . htmlspecialchars($pil['title']) . "</strong>: " . htmlspecialchars($pil['description']) . "<br>";
                }
            } else {
                $reply .= "This table is actively monitored and indexed by Nova's Knowledge Engine.";
            }

            return [
                'reply' => $reply,
                'chips' => [
                    ['label' => '📊 All Database Tables', 'action' => 'chat', 'target' => 'Study all the database'],
                    ['label' => '🎨 Explore Services', 'action' => 'scroll', 'target' => '#services'],
                    ['label' => '💰 Pricing Packages', 'action' => 'chat', 'target' => 'What are your pricing packages?']
                ]
            ];
        }
    }

    // =========================================================================
    // 2. Exact Question-Targeted Handlers (Precise, ChatGPT / Gemini style)
    // =========================================================================

    // A0. Company Overview & About Inquiries (Priority handler so company questions never match "Creative Developer")
    if (
        strpos($q, 'what is perumalil') !== false ||
        strpos($q, 'who is perumalil') !== false ||
        strpos($q, 'about perumalil') !== false ||
        strpos($q, 'tell me about perumalil') !== false ||
        strpos($q, 'tell me about your company') !== false ||
        strpos($q, 'about the company') !== false ||
        strpos($q, 'about your company') !== false ||
        strpos($q, 'what company') !== false ||
        strpos($q, 'what does perumalil') !== false ||
        $q === 'about us' || $q === 'about' || $q === 'perumalil creative' || $q === 'perumalil'
    ) {
        return [
            'reply' => "<strong>Perumalil Creative</strong> is a premier creative advertising, digital marketing, and web design agency based in Vazhakulam, Kerala.<br><br>We specialize in website development, 4K video production, brand identity, graphic design, and digital marketing.",
            'chips' => [
                ['label' => '🎨 Explore Services', 'action' => 'scroll', 'target' => '#services'],
                ['label' => '🎬 View Portfolio', 'action' => 'scroll', 'target' => '#portfolio'],
                ['label' => '👥 Meet the Team', 'action' => 'chat', 'target' => 'Who are all the team members?'],
                ['label' => '💬 Discuss on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // A. Specific Team Member / Role Queries
    // 1. Creative Developer (e.g. "hi the creative devolper", "who is the creative developer", "sojin", "web developer")
    if (
        (
            strpos($q, 'sojin') !== false ||
            strpos($q, 'creative developer') !== false ||
            strpos($q, 'creative devolper') !== false ||
            strpos($q, 'creative devloper') !== false ||
            strpos($q, 'creative developr') !== false ||
            strpos($q, 'web developer') !== false ||
            strpos($q, 'web devolper') !== false ||
            strpos($q, 'web devloper') !== false ||
            strpos($q, 'programmer') !== false ||
            strpos($q, 'coder') !== false ||
            hasFuzzyMatch($q, ['developer', 'devloper', 'devolper', 'developr', 'programmer', 'coder'], 1)
        ) &&
        strpos($q, 'package') === false && strpos($q, 'price') === false && 
        strpos($q, 'cost') === false && strpos($q, 'rate') === false && 
        strpos($q, 'pricing') === false && strpos($q, 'how much') === false && 
        strpos($q, 'kaashu') === false && strpos($q, 'ethraya') === false &&
        strpos($q, 'all') === false && strpos($q, 'members') === false && strpos($q, 'everyone') === false
    ) {
        $dev = getTeamMemberByKeywords($k['team'], ['creative developer', 'web developer', 'developer', 'programmer', 'coder', 'sojin']);
        $devName = htmlspecialchars($dev['name'] ?? 'Sojin');
        $devRole = htmlspecialchars($dev['role'] ?? 'Creative Developer');
        return [
            'reply' => "The <strong>{$devRole}</strong> at Perumalil Creative is <strong>{$devName}</strong>.<br>Specializes in web architectures and interactive web platforms.",
            'chips' => [
                ['label' => '🌐 View Web Architectures', 'action' => 'scroll', 'target' => '#portfolio'],
                ['label' => '👥 Meet All Team Members', 'action' => 'chat', 'target' => 'Who are all the team members?'],
                ['label' => '💬 Discuss a Project', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // 2. Creative Designer (e.g. "who is the creative designer", "creative designer", "sam", "graphic designer")
    if (
        (
            strpos($q, 'sam') !== false ||
            strpos($q, 'creative designer') !== false ||
            strpos($q, 'creative desiner') !== false ||
            strpos($q, 'graphic designer') !== false ||
            strpos($q, 'graphic desiner') !== false ||
            (
                hasFuzzyMatch($q, ['designer', 'desiner'], 1) &&
                (strpos($q, 'graphic design') === false || strpos($q, 'graphic designer') !== false)
            )
        ) &&
        (strpos($q, 'graphic design') === false || strpos($q, 'graphic designer') !== false) &&
        strpos($q, 'service') === false && strpos($q, 'services') === false &&
        strpos($q, 'id card') === false && strpos($q, 'website design') === false &&
        strpos($q, 'package') === false && strpos($q, 'price') === false && 
        strpos($q, 'cost') === false && strpos($q, 'rate') === false && 
        strpos($q, 'pricing') === false && strpos($q, 'how much') === false && 
        strpos($q, 'kaashu') === false && strpos($q, 'ethraya') === false &&
        strpos($q, 'all') === false && strpos($q, 'members') === false && strpos($q, 'everyone') === false
    ) {
        $des = getTeamMemberByKeywords($k['team'], ['creative designer', 'graphic designer', 'designer', 'sam']);
        $desName = htmlspecialchars($des['name'] ?? 'Sam');
        $desRole = htmlspecialchars($des['role'] ?? 'Creative Designer');
        return [
            'reply' => "The <strong>{$desRole}</strong> at Perumalil Creative is <strong>{$desName}</strong>.<br>Specializes in visual branding, graphic design, and brand identity.",
            'chips' => [
                ['label' => '🎨 View Graphic Works', 'action' => 'scroll', 'target' => '#portfolio'],
                ['label' => '👥 Meet All Team Members', 'action' => 'chat', 'target' => 'Who are all the team members?'],
                ['label' => '💬 Chat on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // 3. Technical Director (e.g. "who is the technical director", "tech director", "manoj")
    if (
        (
            strpos($q, 'manoj') !== false ||
            strpos($q, 'technical director') !== false ||
            strpos($q, 'tech director') !== false ||
            strpos($q, 'technical directer') !== false ||
            strpos($q, 'tech directer') !== false ||
            hasFuzzyMatch($q, ['technical director', 'tech director'], 1)
        ) &&
        strpos($q, 'all') === false && strpos($q, 'team') === false && strpos($q, 'members') === false && strpos($q, 'everyone') === false
    ) {
        $td = getTeamMemberByKeywords($k['team'], ['technical director', 'tech director', 'technical', 'manoj']);
        $tdName = htmlspecialchars($td['name'] ?? 'Manoj');
        $tdRole = htmlspecialchars($td['role'] ?? 'Technical Director');
        return [
            'reply' => "The <strong>{$tdRole}</strong> at Perumalil Creative is <strong>{$tdName}</strong>.<br>Oversees technical architecture and engineering execution.",
            'chips' => [
                ['label' => '🌐 View Web Projects', 'action' => 'scroll', 'target' => '#portfolio'],
                ['label' => '👥 Meet All Team Members', 'action' => 'chat', 'target' => 'Who are all the team members?'],
                ['label' => '💬 Connect on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // 4. Managing Director / Founder / CEO / Owner / Dijo (Exact single answer)
    if (
        (
            strpos($q, 'dijo') !== false ||
            strpos($q, 'perumaly') !== false ||
            hasFuzzyMatch($q, ['managing director', 'director', 'founder', 'owner', 'ceo', 'head of company'], 1) ||
            strpos($q, 'who runs') !== false || strpos($q, 'who leads') !== false || 
            strpos($q, 'who owns') !== false || strpos($q, 'who started') !== false ||
            strpos($q, 'aaraanu director') !== false || strpos($q, 'aaraanu owner') !== false ||
            strpos($q, 'director aaranu') !== false || strpos($q, 'director aara') !== false
        ) &&
        strpos($q, 'technical') === false && strpos($q, 'creative') === false &&
        strpos($q, 'service') === false && strpos($q, 'services') === false &&
        strpos($q, 'package') === false && strpos($q, 'price') === false && strpos($q, 'cost') === false &&
        strpos($q, 'seo') === false &&
        strpos($q, 'all') === false && strpos($q, 'team') === false && strpos($q, 'members') === false && 
        strpos($q, 'staff') === false && strpos($q, 'employees') === false
    ) {
        $md = getTeamMemberByKeywords($k['team'], ['managing director', 'director', 'dijo']);
        $mdName = htmlspecialchars($md['name'] ?? ($k['studio']['leadership']['md_name'] ?? 'Dijo J Perumaly'));
        $mdRole = htmlspecialchars($md['role'] ?? ($k['studio']['leadership']['md_role'] ?? 'Managing Director'));
        return [
            'reply' => "The <strong>{$mdRole}</strong> of Perumalil Creative is <strong>{$mdName}</strong>.<br>Leads creative direction, agency vision, and strategic partnerships.",
            'chips' => [
                ['label' => '💬 Connect with Director', 'action' => 'whatsapp', 'target' => '+' . $waPhone],
                ['label' => '👥 Meet All Team Members', 'action' => 'chat', 'target' => 'Who are all the team members?'],
                ['label' => '📞 Call Studio', 'action' => 'call', 'target' => $k['studio']['primary_phone']]
            ]
        ];
    }

    // 5. Plural/Full Team Query (ONLY when explicitly asking for all members or team overview)
    if (
        (
            hasFuzzyMatch($q, [
                'team members', 'who are the team members', 'who are all the team members', 'all team members',
                'who works here', 'full team', 'all team', 'employees list', 'staff list', 'members list',
                'who are the members', 'meet the team', 'leadership and team', 'leadership team'
            ], 1) ||
            strpos($q, 'who works') !== false || strpos($q, 'who work') !== false || strpos($q, 'who is working') !== false ||
            $q === 'team' || $q === 'team members' || $q === 'leadership' ||
            (strpos($q, 'team') !== false && (strpos($q, 'who') !== false || strpos($q, 'all') !== false || strpos($q, 'list') !== false || strpos($q, 'members') !== false))
        ) &&
        strpos($q, 'how many') === false && strpos($q, 'ethra') === false
    ) {
        $teamRows = [];
        if (!empty($k['team'])) {
            foreach ($k['team'] as $tm) {
                $icon = (stripos($tm['role'], 'director') !== false || stripos($tm['role'], 'managing') !== false) ? '👤' : ((stripos($tm['role'], 'developer') !== false || stripos($tm['role'], 'tech') !== false) ? '💻' : '🎨');
                $teamRows[] = "• {$icon} <strong>" . htmlspecialchars($tm['name']) . "</strong> — " . htmlspecialchars($tm['role']);
            }
        }

        return [
            'reply' => "Our core studio team:<br>" . implode("<br>", $teamRows),
            'chips' => [
                ['label' => '💬 Chat with Director', 'action' => 'whatsapp', 'target' => '+' . $waPhone],
                ['label' => '🎨 Explore Services', 'action' => 'scroll', 'target' => '#services'],
                ['label' => '🎬 View Portfolio', 'action' => 'scroll', 'target' => '#portfolio']
            ]
        ];
    }

    // 6. Universal Dynamic Team Member Matcher:
    // Matches ANY newly added team member or designation from MySQL (e.g. 3D Animator, App Developer, Content Strategist)!
    if (
        strpos($q, 'package') === false && strpos($q, 'price') === false && 
        strpos($q, 'cost') === false && strpos($q, 'rate') === false && 
        strpos($q, 'pricing') === false && strpos($q, 'how much') === false && 
        strpos($q, 'kaashu') === false && strpos($q, 'ethraya') === false &&
        strpos($q, 'all') === false && strpos($q, 'everyone') === false &&
        strpos($q, 'what is perumalil') === false && strpos($q, 'about perumalil') === false
    ) {
        $dynamicMemberMatch = matchDynamicTeamMember($q, $k['team'] ?? []);
        if ($dynamicMemberMatch) {
            $tm = $dynamicMemberMatch['member'];
            $tmName = htmlspecialchars($tm['name']);
            $tmRole = htmlspecialchars($tm['role']);
            return [
                'reply' => "The <strong>{$tmRole}</strong> at Perumalil Creative is <strong>{$tmName}</strong>.",
                'chips' => [
                    ['label' => '👥 Meet All Team Members', 'action' => 'chat', 'target' => 'Who are all the team members?'],
                    ['label' => '🎬 Explore Portfolio', 'action' => 'scroll', 'target' => '#portfolio'],
                    ['label' => '💬 Connect on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
                ]
            ];
        }
    }

    // B. Exact Specific Contact Fields
    // 1. Exact Email Query
    if (
        hasFuzzyMatch($q, ['what is your email', 'email address', 'official email', 'studio email', 'email id', 'contact email', 'what is the email'], 1) ||
        $q === 'email' || $q === 'email address'
    ) {
        $email = htmlspecialchars($k['studio']['email'] ?? 'hello@perumalilcreative.com');
        return [
            'reply' => "Our official email address is <strong>{$email}</strong>.",
            'chips' => [
                ['label' => '✉️ Send Email', 'action' => 'whatsapp', 'target' => '+' . $waPhone],
                ['label' => '📞 Call Studio', 'action' => 'call', 'target' => $k['studio']['primary_phone']],
                ['label' => '💬 Chat on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // 2. Exact Phone Query
    if (
        hasFuzzyMatch($q, ['what is your phone', 'what is your phone number', 'phone number', 'contact number', 'mobile number', 'call number', 'telephone', 'what is the phone number'], 1) ||
        $q === 'phone' || $q === 'phone number' || $q === 'number'
    ) {
        $p1 = htmlspecialchars($k['studio']['primary_phone'] ?? '+91 99460 20716');
        $p2 = htmlspecialchars($k['studio']['secondary_phone'] ?? '+91 99460 20724');
        return [
            'reply' => "Our studio contact numbers are:<br>• Primary: <strong>{$p1}</strong><br>• Secondary: <strong>{$p2}</strong>",
            'chips' => [
                ['label' => '📞 Call Primary', 'action' => 'call', 'target' => $p1],
                ['label' => '💬 Chat on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // 3. Exact Address / Studio Location Query
    if (
        hasFuzzyMatch($q, ['what is your address', 'where is your office', 'office location', 'studio address', 'physical location', 'office address', 'where is your studio located', 'what is the address'], 1)
    ) {
        $addr = htmlspecialchars($k['studio']['address']);
        $coords = htmlspecialchars($k['studio']['coordinates']);
        return [
            'reply' => "Our physical creative studio is located at:<br>📍 <strong>{$addr}</strong><br>(Coordinates: {$coords})",
            'chips' => [
                ['label' => '📍 Open Interactive Map', 'action' => 'scroll', 'target' => '#location'],
                ['label' => '🗺️ Google Maps Directions', 'action' => 'whatsapp', 'target' => '+' . $waPhone],
                ['label' => '📞 Call Studio', 'action' => 'call', 'target' => $k['studio']['primary_phone']]
            ]
        ];
    }

    // 4. Exact Tagline / Slogan / Motto
    if (
        hasFuzzyMatch($q, ['tagline', 'motto', 'slogan', 'what is your tagline', 'what is your motto', 'studio tagline'], 1)
    ) {
        return [
            'reply' => "Our motto is <em>\"Design Your Every Occasion.\"</em>",
            'chips' => [
                ['label' => '🎨 Explore Services', 'action' => 'scroll', 'target' => '#services'],
                ['label' => '🎬 View Portfolio', 'action' => 'scroll', 'target' => '#portfolio']
            ]
        ];
    }

    // C. Exact Specific Package Pricing (When user asks about ONE package)
    // 1. Video Production Price
    if (
        (strpos($q, 'video') !== false || strpos($q, 'cinematic') !== false || strpos($q, 'shoot') !== false || strpos($q, 'reel') !== false) &&
        (strpos($q, 'price') !== false || strpos($q, 'cost') !== false || strpos($q, 'rate') !== false || strpos($q, 'package') !== false || strpos($q, 'how much') !== false || strpos($q, 'kaashu') !== false || strpos($q, 'ethraya') !== false) &&
        strpos($q, 'all') === false && strpos($q, 'compare') === false
    ) {
        $pkg = getPackageByKeywords($k['pricing_packages'], ['video', 'reel', 'shoot', 'cinematic']);
        if ($pkg) {
            $featHtml = !empty($pkg['features']) ? "<br><br><strong>Includes:</strong><br>• " . implode("<br>• ", array_map(function($f) { return htmlspecialchars(html_entity_decode($f, ENT_QUOTES | ENT_HTML5, 'UTF-8')); }, $pkg['features'])) : "";
            return [
                'reply' => formatCleanPackageStartingPhrase($pkg) . ".{$featHtml}",
                'chips' => [
                    ['label' => '💰 Open Cost Calculator', 'action' => 'scroll', 'target' => '#contact'],
                    ['label' => '🎬 View Video Showcase', 'action' => 'scroll', 'target' => '#portfolio'],
                    ['label' => '💬 Discuss on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
                ]
            ];
        }
    }

    // 2. Web Development Price
    if (
        (strpos($q, 'web') !== false || strpos($q, 'website') !== false || strpos($q, 'static') !== false || strpos($q, 'dynamic') !== false) &&
        (strpos($q, 'price') !== false || strpos($q, 'cost') !== false || strpos($q, 'rate') !== false || strpos($q, 'package') !== false || strpos($q, 'how much') !== false || strpos($q, 'kaashu') !== false || strpos($q, 'ethraya') !== false) &&
        strpos($q, 'all') === false && strpos($q, 'compare') === false
    ) {
        $pkg = getPackageByKeywords($k['pricing_packages'], ['web', 'website']);
        if ($pkg) {
            $featHtml = !empty($pkg['features']) ? "<br><br><strong>Includes:</strong><br>• " . implode("<br>• ", array_map(function($f) { return htmlspecialchars(html_entity_decode($f, ENT_QUOTES | ENT_HTML5, 'UTF-8')); }, $pkg['features'])) : "";
            return [
                'reply' => formatCleanPackageStartingPhrase($pkg) . ".{$featHtml}",
                'chips' => [
                    ['label' => '💰 Open Cost Calculator', 'action' => 'scroll', 'target' => '#contact'],
                    ['label' => '🌐 View Web Portfolios', 'action' => 'scroll', 'target' => '#portfolio'],
                    ['label' => '💬 Discuss on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
                ]
            ];
        }
    }

    // 3. Graphic Design Price
    if (
        (strpos($q, 'graphic') !== false || strpos($q, 'logo') !== false || strpos($q, 'poster') !== false || strpos($q, 'brochure') !== false) &&
        (strpos($q, 'price') !== false || strpos($q, 'cost') !== false || strpos($q, 'rate') !== false || strpos($q, 'package') !== false || strpos($q, 'how much') !== false || strpos($q, 'kaashu') !== false || strpos($q, 'ethraya') !== false) &&
        strpos($q, 'all') === false && strpos($q, 'compare') === false
    ) {
        $pkg = getPackageByKeywords($k['pricing_packages'], ['graphic', 'design', 'logo']);
        if ($pkg) {
            $featHtml = !empty($pkg['features']) ? " (includes " . implode(", ", array_map(function($f) { return htmlspecialchars(html_entity_decode($f, ENT_QUOTES | ENT_HTML5, 'UTF-8')); }, $pkg['features'])) . ")" : "";
            return [
                'reply' => formatCleanPackageStartingPhrase($pkg) . "{$featHtml}.",
                'chips' => [
                    ['label' => '🎨 View Graphic Works', 'action' => 'scroll', 'target' => '#portfolio'],
                    ['label' => '💰 Price Calculator', 'action' => 'scroll', 'target' => '#contact'],
                    ['label' => '💬 Inquire on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
                ]
            ];
        }
    }

    // 4. Meta & Google Ads Price
    if (
        (strpos($q, 'ads') !== false || strpos($q, 'meta') !== false || strpos($q, 'google ad') !== false || strpos($q, 'campaign') !== false) &&
        (strpos($q, 'price') !== false || strpos($q, 'cost') !== false || strpos($q, 'rate') !== false || strpos($q, 'package') !== false || strpos($q, 'how much') !== false || strpos($q, 'kaashu') !== false || strpos($q, 'ethraya') !== false) &&
        strpos($q, 'all') === false && strpos($q, 'compare') === false
    ) {
        $pkg = getPackageByKeywords($k['pricing_packages'], ['ads', 'meta', 'google', 'campaign', 'marketing']);
        if ($pkg) {
            $featHtml = !empty($pkg['features']) ? " (includes " . implode(", ", array_map(function($f) { return htmlspecialchars(html_entity_decode($f, ENT_QUOTES | ENT_HTML5, 'UTF-8')); }, $pkg['features'])) . ")" : "";
            return [
                'reply' => formatCleanPackageStartingPhrase($pkg) . "{$featHtml}.",
                'chips' => [
                    ['label' => '💰 Open Cost Calculator', 'action' => 'scroll', 'target' => '#contact'],
                    ['label' => '💬 Start Ads on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
                ]
            ];
        }
    }

    // 5. Universal Dynamic Matcher for ANY pricing package card in MySQL
    $dynPkgMatch = matchDynamicPricingPackage($q, $k['pricing_packages'] ?? []);
    if ($dynPkgMatch) {
        $customPkg = $dynPkgMatch['package'];
        $featHtml = !empty($customPkg['features']) ? "<br><br><strong>Includes:</strong><br>• " . implode("<br>• ", array_map(function($f) { return htmlspecialchars(html_entity_decode($f, ENT_QUOTES | ENT_HTML5, 'UTF-8')); }, $customPkg['features'])) : "";
        return [
            'reply' => formatCleanPackageStartingPhrase($customPkg) . ".{$featHtml}",
            'chips' => [
                ['label' => '💰 Open Cost Calculator', 'action' => 'scroll', 'target' => '#contact'],
                ['label' => '📦 Compare All Packages', 'action' => 'scroll', 'target' => '#services'],
                ['label' => '💬 Discuss on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // 6. Service Price Inquiry (For services without a standalone package card in MySQL)
    if (
        (
            strpos($q, 'price') !== false || strpos($q, 'cost') !== false ||
            strpos($q, 'rate') !== false || strpos($q, 'package') !== false ||
            strpos($q, 'how much') !== false || strpos($q, 'kaashu') !== false ||
            strpos($q, 'ethraya') !== false || strpos($q, 'charges') !== false
        ) &&
        strpos($q, 'all') === false && strpos($q, 'compare') === false
    ) {
        $matchedSrvPrice = matchDynamicService($q, $k['services'] ?? []);
        if ($matchedSrvPrice) {
            $sTitle = htmlspecialchars($matchedSrvPrice['service']['title']);
            return [
                'reply' => "For <strong>{$sTitle}</strong>, our pricing is customized based on your specific requirements and scope of work.<br><br>You can use our interactive cost calculator on this page or connect directly with our studio team on WhatsApp for an instant custom quote!",
                'chips' => [
                    ['label' => '💰 Open Cost Calculator', 'action' => 'scroll', 'target' => '#contact'],
                    ['label' => '💬 Get Quote on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone],
                    ['label' => '🎨 View Services Grid', 'action' => 'scroll', 'target' => '#services']
                ]
            ];
        }
    }

    // D. Exact Counts / Number Questions (Dynamic from database)
    // 1. How many services
    if (strpos($q, 'how many') !== false && strpos($q, 'service') !== false) {
        $sCount = count($k['services'] ?? []);
        $sList = implode(', ', array_column($k['services'] ?? [], 'title'));
        return [
            'reply' => "We offer <strong>{$sCount} core services</strong>: {$sList}.",
            'chips' => [
                ['label' => '🎨 View Services Grid', 'action' => 'scroll', 'target' => '#services'],
                ['label' => '💰 Price Calculator', 'action' => 'scroll', 'target' => '#contact']
            ]
        ];
    }

    // 2. How many team members
    if (strpos($q, 'how many') !== false && (strpos($q, 'team') !== false || strpos($q, 'member') !== false || strpos($q, 'people') !== false || strpos($q, 'staff') !== false)) {
        $tCount = count($k['team'] ?? []);
        $tList = implode(', ', array_map(function($m) { return htmlspecialchars($m['name']) . ' (' . htmlspecialchars($m['role']) . ')'; }, $k['team'] ?? []));
        return [
            'reply' => "Our core team consists of <strong>{$tCount} members</strong>: {$tList}.",
            'chips' => [
                ['label' => '👥 Meet All Team Members', 'action' => 'chat', 'target' => 'Who are all the team members?'],
                ['label' => '💬 Chat on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // 3. How many pricing packages
    if (strpos($q, 'how many') !== false && (strpos($q, 'package') !== false || strpos($q, 'pricing') !== false || strpos($q, 'rate') !== false)) {
        $pCount = count($k['pricing_packages'] ?? []);
        $pList = implode(', ', array_map(function($p) { return htmlspecialchars($p['name']) . ' (₹' . htmlspecialchars($p['price']) . ')'; }, $k['pricing_packages'] ?? []));
        return [
            'reply' => "We offer <strong>{$pCount} transparent packages</strong>: {$pList}.",
            'chips' => [
                ['label' => '💰 Open Pricing Calculator', 'action' => 'scroll', 'target' => '#contact'],
                ['label' => '📦 Compare All Packages', 'action' => 'scroll', 'target' => '#services'],
                ['label' => '💬 Discuss on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // 4. How many portfolio projects
    if (strpos($q, 'how many') !== false && (strpos($q, 'project') !== false || strpos($q, 'portfolio') !== false || strpos($q, 'works') !== false)) {
        $pCount = count($k['portfolio_flat'] ?? []);
        $catParts = [];
        foreach (($k['portfolio'] ?? []) as $cat => $items) {
            $catParts[] = ucfirst($cat) . ' (' . count($items) . ')';
        }
        $breakdownStr = !empty($catParts) ? " across " . implode(', ', $catParts) : "";
        return [
            'reply' => "We have <strong>{$pCount} featured projects</strong> in our portfolio{$breakdownStr}.",
            'chips' => [
                ['label' => '🎬 Explore Portfolio Grid', 'action' => 'scroll', 'target' => '#portfolio'],
                ['label' => '💰 Estimate Similar Project', 'action' => 'scroll', 'target' => '#contact']
            ]
        ];
    }

    // 3. Broad Category Synthesizers (Prioritized for comprehensive multi-item questions)

    // A. Pricing / Rates / Packages (Rate card)
    if (
        hasFuzzyMatch($q, ['pricing packages', 'pricing package', 'rate card', 'rates', 'all packages', 'all pricing', 'what are your packages', 'what are your pricing', 'what packages', 'show packages', 'packages list', 'pricing', 'price list', 'prices', 'rate list', 'kaashu', 'ethraya', 'budget'], 1) ||
        (strpos($q, 'package') !== false && (strpos($q, 'all') !== false || strpos($q, 'what') !== false || strpos($q, 'show') !== false || strpos($q, 'pricing') !== false || strpos($q, 'list') !== false)) ||
        (strpos($q, 'how much') !== false && strpos($q, 'cost') === false && strpos($q, 'website') === false && strpos($q, 'static') === false)
    ) {
        $lines = [];
        foreach ($k['pricing_packages'] as $p) {
            $lines[] = "• 💰 <strong>" . htmlspecialchars($p['name']) . ":</strong> " . htmlspecialchars($p['price_label']) . " ₹" . htmlspecialchars($p['price']);
        }
        $reply = "Here is our studio-direct transparent rate card from the database:<br>" . implode("<br>", $lines) . "<br><br>You can also use our interactive cost calculator on this page to configure exact deliverables!";
        return [
            'reply' => $reply,
            'chips' => [
                ['label' => '💰 Open Pricing Calculator', 'action' => 'scroll', 'target' => '#contact'],
                ['label' => '📦 Compare Package Features', 'action' => 'scroll', 'target' => '#services'],
                ['label' => '💬 WhatsApp Custom Quote', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // B. Services / Offerings
    // 1. Specific Individual Service Query (Automatically matches ANY existing or newly added service in MySQL!)
    if (
        strpos($q, 'price') === false && strpos($q, 'cost') === false && strpos($q, 'rate') === false &&
        strpos($q, 'package') === false && strpos($q, 'how much') === false && strpos($q, 'kaashu') === false &&
        strpos($q, 'ethraya') === false && strpos($q, 'all') === false && strpos($q, 'list') === false &&
        $q !== 'services' && $q !== 'service'
    ) {
        $matchedSrvInfo = matchDynamicService($q, $k['services'] ?? []);
        if ($matchedSrvInfo) {
            $srv = $matchedSrvInfo['service'];
            $sTitle = htmlspecialchars($srv['title']);
            $sDesc = htmlspecialchars($srv['description']);
            $hasActionIntent = (
                strpos($q, 'can you') !== false ||
                strpos($q, 'do you') !== false ||
                strpos($q, 'need') !== false ||
                strpos($q, 'want') !== false ||
                strpos($q, 'looking for') !== false ||
                strpos($q, 'make') !== false ||
                strpos($q, 'build') !== false ||
                strpos($q, 'create') !== false
            );
            $intro = $hasActionIntent ? "Yes, absolutely! We provide professional <strong>{$sTitle}</strong>.<br>" : "🎨 <strong>{$sTitle}:</strong> ";
            return [
                'reply' => $intro . $sDesc,
                'chips' => [
                    ['label' => '🎨 View Services Grid', 'action' => 'scroll', 'target' => '#services'],
                    ['label' => '🎬 Check Past Works', 'action' => 'scroll', 'target' => '#portfolio'],
                    ['label' => '💬 Discuss on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
                ]
            ];
        }

        // Dedicated match for Video Production (packaged in pricing_packages table)
        if (
            (strpos($q, 'video') !== false || strpos($q, 'cinematic') !== false || strpos($q, 'shoot') !== false || strpos($q, 'film') !== false || strpos($q, 'reel') !== false || strpos($q, 'editing') !== false) &&
            strpos($q, 'price') === false && strpos($q, 'cost') === false && strpos($q, 'rate') === false
        ) {
            return [
                'reply' => "Yes, absolutely! We specialize in high-end <strong>Video Production</strong>, cinematic brand commercials, 4K event coverage, and viral Instagram Reels (packages starting from ₹5,999).",
                'chips' => [
                    ['label' => '🎬 View Video Showcase', 'action' => 'scroll', 'target' => '#portfolio'],
                    ['label' => '💰 Video Pricing', 'action' => 'chat', 'target' => 'What is the video package price?'],
                    ['label' => '💬 Discuss on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
                ]
            ];
        }
    }

    // 2. All Services List
    if (
        hasFuzzyMatch($q, ['what services do you offer', 'what services', 'services list', 'all services', 'our services', 'what do you do', 'solutions list', 'services grid'], 1) ||
        $q === 'services' || $q === 'service' || (strpos($q, 'service') !== false && (strpos($q, 'what') !== false || strpos($q, 'all') !== false || strpos($q, 'list') !== false || strpos($q, 'offer') !== false))
    ) {
        $srvLines = [];
        foreach ($k['services'] as $s) {
            $srvLines[] = "• 🎨 <strong>" . htmlspecialchars($s['title']) . ":</strong> " . htmlspecialchars($s['description']);
        }
        $sCount = count($k['services'] ?? []);
        return [
            'reply' => "We provide {$sCount} core creative & digital services:<br>" . implode("<br>", $srvLines),
            'chips' => [
                ['label' => '🎨 View Services Grid', 'action' => 'scroll', 'target' => '#services'],
                ['label' => '🎬 Check Past Works', 'action' => 'scroll', 'target' => '#portfolio'],
                ['label' => '💰 Get Price Estimate', 'action' => 'scroll', 'target' => '#contact']
            ]
        ];
    }

    // =========================================================================
    // C0. Dedicated Latest / Last Added Portfolio Work Handler
    // =========================================================================
    $isAskingLatest = (
        strpos($q, 'latest') !== false ||
        strpos($q, 'last') !== false ||
        strpos($q, 'recent') !== false ||
        strpos($q, 'newest') !== false ||
        strpos($q, 'new work') !== false ||
        strpos($q, 'new project') !== false ||
        strpos($q, 'newly') !== false ||
        strpos($q, 'puthiya') !== false ||
        strpos($q, 'ettavum') !== false ||
        strpos($q, 'add cheytha') !== false
    );

    $isWorkContext = (
        strpos($q, 'work') !== false ||
        strpos($q, 'project') !== false ||
        strpos($q, 'portfolio') !== false ||
        strpos($q, 'item') !== false ||
        strpos($q, 'site') !== false ||
        strpos($q, 'website') !== false ||
        strpos($q, 'video') !== false ||
        strpos($q, 'design') !== false ||
        strpos($q, 'added') !== false ||
        strpos($q, 'add cheytha') !== false
    );

    if ($isAskingLatest && $isWorkContext) {
        $catFilter = null;
        if (strpos($q, 'web') !== false || strpos($q, 'site') !== false) {
            $catFilter = 'web';
        } elseif (strpos($q, 'video') !== false || strpos($q, 'film') !== false || strpos($q, 'reel') !== false || strpos($q, 'shoot') !== false) {
            $catFilter = 'video';
        } elseif (strpos($q, 'design') !== false || strpos($q, 'graphic') !== false || strpos($q, 'poster') !== false || strpos($q, 'logo') !== false || strpos($q, 'branding') !== false) {
            $catFilter = 'design';
        }

        $isPlural = (
            strpos($q, 'works') !== false ||
            strpos($q, 'projects') !== false ||
            strpos($q, 'items') !== false ||
            strpos($q, 'all') !== false ||
            strpos($q, 'list') !== false ||
            strpos($q, 'top') !== false ||
            strpos($q, '3') !== false
        ) && (
            strpos($q, 'last work') === false &&
            strpos($q, 'last project') === false &&
            strpos($q, 'last added work') === false &&
            strpos($q, 'last added project') === false &&
            strpos($q, 'latest work') === false &&
            strpos($q, 'latest project') === false &&
            strpos($q, 'which is the') === false &&
            strpos($q, 'what is the') === false &&
            strpos($q, 'eth aanu') === false &&
            strpos($q, 'ethanu') === false &&
            strpos($q, 'eth') === false
        );

        if ($isPlural) {
            $recentItems = getLatestPortfolioItems($pdo, $k['portfolio_flat'], $catFilter, 3);
            if (!empty($recentItems)) {
                $rows = [];
                foreach ($recentItems as $it) {
                    $t = htmlspecialchars($it['title']);
                    $c = ucfirst(htmlspecialchars($it['category']));
                    $l = $it['link'] ?? '';
                    $linkHtml = (!empty($l) && $l !== '#' && filter_var($l, FILTER_VALIDATE_URL))
                        ? " — <a href=\"" . htmlspecialchars($l) . "\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color: #38bdf8; text-decoration: underline;\">Live Site ↗</a>"
                        : "";
                    $rows[] = "• 🎬 <strong>{$t}</strong> ({$c}){$linkHtml}";
                }
                return [
                    'reply' => "Here are the latest projects added to our portfolio:<br>" . implode("<br>", $rows),
                    'chips' => [
                        ['label' => '🎬 Explore Portfolio Grid', 'action' => 'scroll', 'target' => '#portfolio'],
                        ['label' => '💬 Inquire on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
                    ]
                ];
            }
        }

        // Exact singular last added work
        $latestItem = getLatestPortfolioItems($pdo, $k['portfolio_flat'], $catFilter, 1);
        if ($latestItem) {
            $title = htmlspecialchars($latestItem['title'] ?? '');
            $cat = ucfirst(htmlspecialchars($latestItem['category'] ?? 'Work'));
            $tag = htmlspecialchars($latestItem['tag'] ?? '');
            $desc = htmlspecialchars($latestItem['description'] ?? '');
            $link = $latestItem['link'] ?? '';
            $isLaunching = !empty($latestItem['is_launching_soon']);

            $catLabel = $tag ? "{$cat} &bull; {$tag}" : $cat;
            $reply = "The latest project added to our portfolio is <strong>{$title}</strong> ({$catLabel}).<br>{$desc}";

            if ($isLaunching) {
                $reply .= "<br>🚀 <em>Currently in active development.</em>";
            }

            if (!empty($link) && $link !== '#' && filter_var($link, FILTER_VALIDATE_URL)) {
                $cleanLink = htmlspecialchars($link);
                $reply .= "<br><br>🔗 <strong>Live Platform:</strong> <a href=\"{$cleanLink}\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color: #38bdf8; text-decoration: underline; font-weight: 600;\">{$cleanLink} ↗</a>";
            }

            $chips = [];
            if (!empty($link) && $link !== '#' && filter_var($link, FILTER_VALIDATE_URL)) {
                $chips[] = ['label' => '🌐 Open Live Site', 'action' => 'url', 'target' => $link];
            }
            $chips[] = ['label' => '🎬 View in Portfolio', 'action' => 'scroll', 'target' => '#portfolio'];
            $chips[] = ['label' => '💬 Discuss on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone];

            return [
                'reply' => $reply,
                'chips' => $chips
            ];
        }
    }

    // C. Portfolio / Showcase / Projects (Broad list)
    if (
        hasFuzzyMatch($q, ['show portfolio', 'portfolio projects', 'all projects', 'all portfolio', 'past works', 'showcase works', 'what have you done', 'what have you made', 'show websites'], 1) ||
        $q === 'portfolio' || $q === 'projects' || (strpos($q, 'portfolio') !== false && (strpos($q, 'all') !== false || strpos($q, 'show') !== false || strpos($q, 'list') !== false))
    ) {
        $pCount = count($k['portfolio_flat'] ?? []);
        return [
            'reply' => "Our portfolio features <strong>{$pCount} projects</strong> across Web Architecture, 4K Video Production, and Graphic Branding.<br>Explore our showcase gallery below, or ask me for our latest works!",
            'chips' => [
                ['label' => '🎬 View Portfolio Gallery', 'action' => 'scroll', 'target' => '#portfolio'],
                ['label' => '✨ Latest Work Added', 'action' => 'chat', 'target' => 'What is the latest work added in portfolio?'],
                ['label' => '💬 Discuss on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // D. Launching Soon Projects
    if (hasFuzzyMatch($q, ['launching soon', 'upcoming', 'coming soon', 'under development', 'in development'], 1)) {
        $launching = array_filter($k['portfolio_flat'] ?? [], function($p) { return !empty($p['is_launching_soon']); });
        $lLines = [];
        foreach ($launching as $lp) {
            $lLines[] = "• 🚀 <strong>" . htmlspecialchars($lp['title']) . "</strong> (" . htmlspecialchars($lp['tag']) . ") — " . htmlspecialchars($lp['description']);
        }
        $reply = "Here are our exclusive projects currently in active development by our engineering team:<br>" . implode("<br><br>", $lLines);
        return [
            'reply' => $reply,
            'chips' => [
                ['label' => '🎬 View in Portfolio', 'action' => 'scroll', 'target' => '#portfolio'],
                ['label' => '💬 Inquire on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // E. Location, Headquarters, Address & Coordinates
    if (hasFuzzyMatch($q, ['location', 'locaton', 'address', 'office', 'studio', 'vazhakulam', 'kerala', 'evideya', 'sthalamevida', 'map', 'coordinates', 'where'], 1)) {
        return [
            'reply' => "📍 <strong>Studio Address:</strong> " . htmlspecialchars($k['studio']['address']) . "<br>• <strong>Coordinates:</strong> " . htmlspecialchars($k['studio']['coordinates']) . "<br>• Located opposite Viswajyothy College in Vazhakulam, Kerala.",
            'chips' => [
                ['label' => '📍 Open Interactive Map', 'action' => 'scroll', 'target' => '#location'],
                ['label' => '🗺️ Google Maps Directions', 'action' => 'whatsapp', 'target' => '+' . $waPhone],
                ['label' => '📞 Call Studio', 'action' => 'call', 'target' => $k['studio']['primary_phone']]
            ]
        ];
    }

    // F. Contact, Phone, Email & WhatsApp
    if (hasFuzzyMatch($q, ['contact', 'contct', 'phone', 'phne', 'number', 'email', 'whatsapp', 'call', 'reach', 'talk', 'vilikkaan'], 1)) {
        return [
            'reply' => "📞 <strong>Primary Phone:</strong> " . htmlspecialchars($k['studio']['primary_phone']) . "<br>• <strong>Secondary:</strong> " . htmlspecialchars($k['studio']['secondary_phone']) . "<br>• ✉️ <strong>Email:</strong> " . htmlspecialchars($k['studio']['email']) . "<br>• 👤 <strong>Director:</strong> " . htmlspecialchars($k['studio']['leadership']['md_name']),
            'chips' => [
                ['label' => '💬 Chat on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone],
                ['label' => '📞 Call Studio Directly', 'action' => 'call', 'target' => $k['studio']['primary_phone']],
                ['label' => '📝 Fill Inquiry Form', 'action' => 'scroll', 'target' => '#contact']
            ]
        ];
    }

    // G. Collaborators & Partners
    if (hasFuzzyMatch($q, ['collaborator', 'collaborators', 'partner', 'partners', 'brands', 'clients list', 'who do you work with'], 1)) {
        $cCount = count($k['collaborators'] ?? []);
        $sampleCollabs = array_slice($k['collaborators'] ?? [], 0, 8);
        $cNames = array_map(function($c) { return htmlspecialchars($c['name']); }, $sampleCollabs);
        $reply = "🤝 <strong>Perumalil Creative</strong> proudly collaborates with <strong>{$cCount}+ institutions & brands</strong> across Kerala and beyond, including:<br>• " . implode("<br>• ", $cNames) . "<br>...and many more!";
        return [
            'reply' => $reply,
            'chips' => [
                ['label' => '🎬 View Our Works', 'action' => 'scroll', 'target' => '#portfolio'],
                ['label' => '💬 Partner With Us', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // H. FAQs (Broad)
    if (hasFuzzyMatch($q, ['faqs', 'frequently asked questions', 'turnaround', 'revisions', 'workflow'], 1) || $q === 'faq') {
        $faqLines = [];
        foreach (array_slice($k['faq'] ?? [], 0, 3) as $f) {
            $faqLines[] = "• ❓ <strong>" . htmlspecialchars($f['question']) . "</strong><br>" . htmlspecialchars($f['answer']);
        }
        return [
            'reply' => "Here are answers to some of our most frequently asked questions:<br><br>" . implode("<br><br>", $faqLines),
            'chips' => [
                ['label' => '🎨 Explore Services', 'action' => 'scroll', 'target' => '#services'],
                ['label' => '💰 Rate Calculator', 'action' => 'scroll', 'target' => '#contact'],
                ['label' => '💬 Inquire on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // I. Intro Pillars & Guarantees
    if (hasFuzzyMatch($q, ['pillar', 'pillars', 'values', 'guarantees', 'standards', 'strengths'], 1)) {
        $pilLines = [];
        foreach ($k['pillars'] as $p) {
            $pilLines[] = "• ⚡ <strong>" . htmlspecialchars($p['title']) . ":</strong> " . htmlspecialchars($p['description']);
        }
        return [
            'reply' => "Here are the foundational pillars of <strong>Perumalil Creative</strong>:<br><br>" . implode("<br>", $pilLines),
            'chips' => [
                ['label' => '🎨 Explore Services', 'action' => 'scroll', 'target' => '#services'],
                ['label' => '💬 Talk to Us on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // J. Company Philosophy & About
    if (hasFuzzyMatch($q, ['philosophy', 'about us', 'about perumalil', 'mission', 'vision', 'who is perumalil creative'], 1)) {
        $narrative = htmlspecialchars($k['studio']['about_narrative'] ?? '');
        $phil = htmlspecialchars($k['studio']['philosophy'] ?? '');
        return [
            'reply' => "<strong>About Perumalil Creative:</strong><br>{$narrative}<br><br><strong>Our Philosophy:</strong><br><em>\"{$phil}\"</em>",
            'chips' => [
                ['label' => '👥 Meet the Team', 'action' => 'chat', 'target' => 'Who are the team members?'],
                ['label' => '🎨 Explore Services', 'action' => 'scroll', 'target' => '#services'],
                ['label' => '🎬 View Portfolio', 'action' => 'scroll', 'target' => '#portfolio']
            ]
        ];
    }

    // K. Greetings & Bot Identity (Only when message is primarily a greeting without a specific topic)
    if (
        hasFuzzyMatch($q, ['hello', 'hi', 'hey', 'namaskaram', 'good morning', 'good evening', 'who are you', 'what are you', 'your name'], 0) &&
        strpos($q, 'service') === false && strpos($q, 'price') === false && strpos($q, 'package') === false &&
        strpos($q, 'developer') === false && strpos($q, 'devolper') === false && strpos($q, 'devloper') === false &&
        strpos($q, 'designer') === false && strpos($q, 'director') === false
    ) {
        return [
            'reply' => "Hi, I'm <strong>Nova</strong>! 👋 How can I help you today?",
            'chips' => []
        ];
    }

    // L. Social Media Handles & Online Channels
    if (
        hasFuzzyMatch($q, ['instagram', 'insta', 'facebook', 'youtube', 'social media', 'social', 'channel', 'channels', 'follow', 'handles'], 1) ||
        strpos($q, 'instagram') !== false || strpos($q, 'youtube') !== false || strpos($q, 'facebook') !== false
    ) {
        return [
            'reply' => "Follow <strong>Perumalil Creative</strong> on our official channels:<br>• 📷 <strong>Instagram:</strong> <a href=\"https://www.instagram.com/perumalil_creative?igsh=eDQyc3d3eGo2eGRi\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color:#38bdf8; font-weight:600;\">@perumalil_creative ↗</a><br>• 💬 <strong>WhatsApp:</strong> <a href=\"https://wa.me/919946020716\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color:#38bdf8; font-weight:600;\">+91 99460 20716 ↗</a><br>• 🎬 <strong>YouTube:</strong> <a href=\"https://youtube.com/@perumalilcreative?si=yepc18uv3rxStF7j\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color:#38bdf8; font-weight:600;\">@perumalilcreative ↗</a><br>• 🌐 <strong>Facebook:</strong> <a href=\"https://www.facebook.com/share/1CRr4Cunuz/\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color:#38bdf8; font-weight:600;\">Perumalil Creative Page ↗</a>",
            'chips' => [
                ['label' => '📷 Instagram Profile', 'action' => 'url', 'target' => 'https://www.instagram.com/perumalil_creative?igsh=eDQyc3d3eGo2eGRi'],
                ['label' => '💬 Chat on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone],
                ['label' => '🎬 YouTube Channel', 'action' => 'url', 'target' => 'https://youtube.com/@perumalilcreative?si=yepc18uv3rxStF7j']
            ]
        ];
    }

    // M. Tagline, Slogan & Motto
    if (hasFuzzyMatch($q, ['tagline', 'slogan', 'motto'], 1) || strpos($q, 'slogan') !== false || strpos($q, 'tagline') !== false) {
        $tagline = htmlspecialchars($k['studio']['tagline'] ?? 'Design Your Every Occasion.');
        $mission = htmlspecialchars($k['studio']['mission'] ?? '');
        return [
            'reply' => "Our studio motto is <strong>\"{$tagline}\"</strong><br>{$mission}",
            'chips' => [
                ['label' => '🎨 Explore Services', 'action' => 'scroll', 'target' => '#services'],
                ['label' => '🎬 View Portfolio', 'action' => 'scroll', 'target' => '#portfolio'],
                ['label' => '💬 Connect on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // 4. Search all Database & Website Content Entities with Intelligent Multi-Tier Scoring
    $matches = searchAllDatabaseEntities($query, $searchIndex);

    if (!empty($matches) && $matches[0]['score'] >= 10) {
        $topMatch = $matches[0];
        $entity = $topMatch['entity'];
        $type = $entity['type'];

        // A. Match: TEAM MEMBER
        if ($type === 'team') {
            $name = htmlspecialchars($entity['title']);
            $role = htmlspecialchars($entity['meta']['role'] ?? 'Leader');
            return [
                'reply' => "The <strong>{$role}</strong> at Perumalil Creative is <strong>{$name}</strong>.",
                'chips' => [
                    ['label' => '💬 Chat on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone],
                    ['label' => '👥 Meet All Team Members', 'action' => 'chat', 'target' => 'Who are all the team members?'],
                    ['label' => '🎬 Explore Our Work', 'action' => 'scroll', 'target' => '#portfolio']
                ]
            ];
        }

        // B. Match: PORTFOLIO ITEM
        if ($type === 'portfolio') {
            $title = htmlspecialchars($entity['title']);
            $tag = htmlspecialchars($entity['meta']['tag'] ?? '');
            $desc = htmlspecialchars($entity['meta']['description'] ?? '');
            $link = $entity['meta']['link'] ?? '';
            $cat = ucfirst(htmlspecialchars($entity['meta']['category'] ?? 'Showcase'));
            $isLaunching = !empty($entity['meta']['is_launching_soon']);

            $catLabel = $tag ? "{$cat} &bull; {$tag}" : $cat;
            $reply = "🎬 <strong>{$title}</strong> ({$catLabel})<br>{$desc}";
            if ($isLaunching) {
                $reply .= "<br>🚀 <em>Currently in active development.</em>";
            }
            if (!empty($link) && $link !== '#' && filter_var($link, FILTER_VALIDATE_URL)) {
                $cleanLink = htmlspecialchars($link);
                $reply .= "<br><br>🔗 <strong>Live Platform:</strong> <a href=\"{$cleanLink}\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color: #38bdf8; text-decoration: underline; font-weight: 600;\">{$cleanLink} ↗</a>";
            }

            $chips = [];
            if (!empty($link) && $link !== '#' && filter_var($link, FILTER_VALIDATE_URL)) {
                $chips[] = ['label' => '🌐 Open Live Site', 'action' => 'url', 'target' => $link];
            }
            $chips[] = ['label' => '🎬 View in Portfolio', 'action' => 'scroll', 'target' => '#portfolio'];
            $chips[] = ['label' => '💬 Discuss on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone];

            return [
                'reply' => $reply,
                'chips' => $chips
            ];
        }

        // C. Match: PRICING PACKAGE
        if ($type === 'pricing_package') {
            $name = htmlspecialchars($entity['meta']['name'] ?? $entity['title']);
            $priceLabel = htmlspecialchars($entity['meta']['price_label'] ?? 'Starts from');
            $price = htmlspecialchars($entity['meta']['price'] ?? '');
            $features = $entity['meta']['features'] ?? [];

            $featList = [];
            foreach ($features as $f) {
                $featList[] = "• " . htmlspecialchars($f);
            }

            $reply = "💰 <strong>{$name}</strong><br><strong>{$priceLabel}:</strong> ₹{$price}";
            if (!empty($featList)) {
                $reply .= "<br><br><strong>Includes:</strong><br>" . implode("<br>", $featList);
            }

            return [
                'reply' => $reply,
                'chips' => [
                    ['label' => '💰 Open Cost Calculator', 'action' => 'scroll', 'target' => '#contact'],
                    ['label' => '📦 Compare All Packages', 'action' => 'scroll', 'target' => '#services'],
                    ['label' => '💬 Book on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
                ]
            ];
        }

        // D. Match: SERVICE
        if ($type === 'service') {
            $title = htmlspecialchars($entity['title']);
            $desc = htmlspecialchars($entity['content']);

            return [
                'reply' => "🎨 <strong>{$title}:</strong> {$desc}",
                'chips' => [
                    ['label' => '🎨 View Services Grid', 'action' => 'scroll', 'target' => '#services'],
                    ['label' => '🎬 Check Past Works', 'action' => 'scroll', 'target' => '#portfolio'],
                    ['label' => '💰 Cost Calculator', 'action' => 'scroll', 'target' => '#contact']
                ]
            ];
        }

        // E. Match: FAQ ITEM
        if ($type === 'faq') {
            $faqA = htmlspecialchars($entity['content']);

            return [
                'reply' => $faqA,
                'chips' => [
                    ['label' => '🎨 Explore Services', 'action' => 'scroll', 'target' => '#services'],
                    ['label' => '💰 Pricing Calculator', 'action' => 'scroll', 'target' => '#contact'],
                    ['label' => '💬 Inquire on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
                ]
            ];
        }

        // F. Match: INTRO PILLAR
        if ($type === 'pillar') {
            $pTitle = htmlspecialchars($entity['title']);
            $pDesc = htmlspecialchars($entity['content']);

            return [
                'reply' => "⚡ <strong>{$pTitle}:</strong> {$pDesc}",
                'chips' => [
                    ['label' => '🎨 Explore Services', 'action' => 'scroll', 'target' => '#services'],
                    ['label' => '💬 Talk on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
                ]
            ];
        }

        // G. Match: COLLABORATOR / PARTNER
        if ($type === 'collaborator') {
            $partnerName = htmlspecialchars($entity['title']);
            return [
                'reply' => "🤝 <strong>{$partnerName}</strong> is one of our trusted studio partners & collaborators.",
                'chips' => [
                    ['label' => '🎬 Explore Portfolio', 'action' => 'scroll', 'target' => '#portfolio'],
                    ['label' => '💬 Partner with Us', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
                ]
            ];
        }

        // H. Match: SITE SETTING
        if ($type === 'setting') {
            $val = htmlspecialchars($entity['content']);
            $sKey = $entity['id'] ?? '';
            $heading = (strpos($sKey, 'contact') !== false) ? 'Studio Contact' : ((strpos($sKey, 'about') !== false || strpos($sKey, 'mission') !== false) ? 'About Perumalil Creative' : 'Perumalil Creative');
            return [
                'reply' => "<strong>{$heading}:</strong><br>{$val}",
                'chips' => [
                    ['label' => '📍 Open Map', 'action' => 'scroll', 'target' => '#location'],
                    ['label' => '💬 Chat on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
                ]
            ];
        }

        // I. Match: CUSTOM TABLE ROW
        if ($type === 'custom_table_row') {
            $title = htmlspecialchars($entity['title']);
            $content = htmlspecialchars($entity['content']);
            $tbl = htmlspecialchars($entity['meta']['table'] ?? 'Database Record');
            return [
                'reply' => "📄 <strong>{$title}</strong> (from table <code>{$tbl}</code>):<br>{$content}",
                'chips' => [
                    ['label' => '📊 Database Audit', 'action' => 'chat', 'target' => 'Study all the database'],
                    ['label' => '💬 Chat on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
                ]
            ];
        }

        // J. Match: SOCIAL MEDIA
        if ($type === 'social_media') {
            return [
                'reply' => "Follow <strong>Perumalil Creative</strong> on our official channels:<br>• 📷 <strong>Instagram:</strong> <a href=\"https://www.instagram.com/perumalil_creative?igsh=eDQyc3d3eGo2eGRi\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color:#38bdf8; font-weight:600;\">@perumalil_creative ↗</a><br>• 💬 <strong>WhatsApp:</strong> <a href=\"https://wa.me/919946020716\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color:#38bdf8; font-weight:600;\">+91 99460 20716 ↗</a><br>• 🎬 <strong>YouTube:</strong> <a href=\"https://youtube.com/@perumalilcreative?si=yepc18uv3rxStF7j\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color:#38bdf8; font-weight:600;\">@perumalilcreative ↗</a><br>• 🌐 <strong>Facebook:</strong> <a href=\"https://www.facebook.com/share/1CRr4Cunuz/\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color:#38bdf8; font-weight:600;\">Perumalil Creative Page ↗</a>",
                'chips' => [
                    ['label' => '📷 Instagram Profile', 'action' => 'url', 'target' => 'https://www.instagram.com/perumalil_creative?igsh=eDQyc3d3eGo2eGRi'],
                    ['label' => '💬 Chat on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone],
                    ['label' => '🎬 YouTube Channel', 'action' => 'url', 'target' => 'https://youtube.com/@perumalilcreative?si=yepc18uv3rxStF7j']
                ]
            ];
        }

        // K. Match: STATIC SITE CONTENT
        if ($type === 'site_content') {
            $title = htmlspecialchars($entity['title']);
            $content = htmlspecialchars($entity['content']);
            return [
                'reply' => "📌 <strong>{$title}:</strong><br>{$content}",
                'chips' => [
                    ['label' => '🎨 Explore Services', 'action' => 'scroll', 'target' => '#services'],
                    ['label' => '🎬 View Portfolio', 'action' => 'scroll', 'target' => '#portfolio'],
                    ['label' => '💬 Chat on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
                ]
            ];
        }
    }

    // Fallback: Semantic synthesis from top search match (if any)
    if (!empty($matches) && $matches[0]['score'] >= 15) {
        $top = $matches[0]['entity'];
        return [
            'reply' => "<strong>" . htmlspecialchars($top['title']) . ":</strong><br>" . htmlspecialchars($top['content']),
            'chips' => [
                ['label' => '✨ Latest Work Added', 'action' => 'chat', 'target' => 'What is the latest work added in portfolio?'],
                ['label' => '🎨 Explore Services', 'action' => 'scroll', 'target' => '#services'],
                ['label' => '💬 Chat on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone]
            ]
        ];
    }

    // Default Fallback
    return [
        'reply' => "I couldn't find an exact match for that, but I can help you with our <strong>services</strong>, <strong>latest portfolio works</strong>, <strong>package pricing</strong>, <strong>team</strong>, or <strong>studio location</strong> in Vazhakulam. You can also chat directly on WhatsApp!",
        'chips' => [
            ['label' => '💬 Chat on WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone],
            ['label' => '✨ Latest Portfolio Work', 'action' => 'chat', 'target' => 'What is the latest work added in portfolio?'],
            ['label' => '🎨 Explore Services', 'action' => 'scroll', 'target' => '#services'],
            ['label' => '💰 Pricing Calculator', 'action' => 'scroll', 'target' => '#contact']
        ]
    ];
}

// 8. Execute Reasoning Engine (with optional Gemini LLM acceleration)
$geminiApiKey = $knowledge['site_settings']['gemini_api_key'] ?? '';
$aiReply = null;
$engineUsed = 'master-database-engine';

if (!empty($geminiApiKey)) {
    $aiReply = tryGeminiAI($userMessage, $chatHistory, $knowledge, $geminiApiKey);
    if ($aiReply) {
        $engineUsed = 'gemini-1.5-flash+database';
    }
}

if (!$aiReply) {
    $localResult = runMasterDatabaseEngine($userMessage, $knowledge, $searchIndex, $leadCaptured, $pdo);
    $reply = $localResult['reply'];
    $chips = $localResult['chips'];
} else {
    $reply = $aiReply;
    $waPhone = preg_replace('/[^0-9]/', '', $knowledge['studio']['primary_phone'] ?? '919946020716');
    $chips = [
        ['label' => '💬 Direct WhatsApp', 'action' => 'whatsapp', 'target' => '+' . $waPhone],
        ['label' => '🎬 Explore Portfolio', 'action' => 'scroll', 'target' => '#portfolio'],
        ['label' => '💰 Cost Calculator', 'action' => 'scroll', 'target' => '#contact']
    ];
}

echo json_encode([
    'success' => true,
    'reply' => $reply,
    'chips' => $chips,
    'engine' => $engineUsed,
    'lead_captured' => $leadCaptured,
    'timestamp' => date('c')
]);
