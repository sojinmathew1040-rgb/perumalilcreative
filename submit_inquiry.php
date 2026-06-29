<?php
// submit_inquiry.php - Process and save interactive agency inquiries
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

require_once __DIR__ . '/includes/db.php';

// Decode JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Fallback to standard POST parameters if JSON decoding is empty
if (!$input) {
    $input = $_POST;
}

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$package_slug = trim($input['package_slug'] ?? '');
$package_name = trim($input['package_name'] ?? '');
$price = isset($input['price']) ? (float)$input['price'] : 0.0;
$message = trim($input['message'] ?? '');

// Validation
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please enter your name.']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please provide a valid email address.']);
    exit;
}

if (empty($package_slug) || empty($package_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please select a specialized package.']);
    exit;
}

if ($price < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid package pricing.']);
    exit;
}

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please describe your creative goals.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO inquiries (name, email, package_slug, package_name, price, message, status) VALUES (?, ?, ?, ?, ?, ?, 'New')");
    $stmt->execute([$name, $email, $package_slug, $package_name, $price, $message]);

    echo json_encode(['success' => true, 'message' => 'Blueprint initiated successfully.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
