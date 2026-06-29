<?php
require 'includes/db.php';
try {
    $stmt = $pdo->query("SELECT * FROM portfolio_items");
    $items = $stmt->fetchAll();
    echo "ITEMS COUNT: " . count($items) . "\n";
    foreach ($items as $item) {
        echo "ID: " . $item['id'] . " - TITLE: " . $item['title'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
