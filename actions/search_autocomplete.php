<?php
/**
 * search_autocomplete.php
 * Returns JSON suggestions for live search typeahead.
 * Query param: q (string)
 */

header('Content-Type: application/json; charset=utf-8');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$q = trim($_GET['q'] ?? '');

// Return empty if query is too short
if (mb_strlen($q) < 2) {
    echo json_encode(['foods' => [], 'restaurants' => [], 'categories' => []]);
    exit;
}

require_once __DIR__ . '/../core/db.php';

$like = "%$q%";
$results = ['foods' => [], 'restaurants' => [], 'categories' => []];

// ── Food items ──────────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT id, name, category, price, emoji, image_path, badge
     FROM foods
     WHERE name LIKE ? OR description LIKE ? OR category LIKE ?
     ORDER BY is_featured DESC, name ASC
     LIMIT 6"
);
$stmt->execute([$like, $like, $like]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $results['foods'][] = [
        'id'         => (int) $row['id'],
        'name'       => $row['name'],
        'category'   => $row['category'],
        'price'      => 'Rs. ' . number_format((float) $row['price'], 0),
        'emoji'      => $row['emoji'] ?? '🍴',
        'image_path' => $row['image_path'] ?? '',
        'badge'      => $row['badge'] ?? '',
        'url'        => 'food_detail.php?id=' . (int) $row['id'],
    ];
}

// ── Restaurants ─────────────────────────────────────────────
try {
    $stmt2 = $pdo->prepare(
        "SELECT id, name, city, cuisine, logo_url
         FROM restaurants
         WHERE name LIKE ? OR city LIKE ? OR cuisine LIKE ?
         ORDER BY name ASC
         LIMIT 4"
    );
    $stmt2->execute([$like, $like, $like]);
    foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results['restaurants'][] = [
            'id'       => (int) $row['id'],
            'name'     => $row['name'],
            'city'     => $row['city'] ?? '',
            'cuisine'  => $row['cuisine'] ?? '',
            'logo_url' => $row['logo_url'] ?? '',
            'url'      => 'restaurant.php?id=' . (int) $row['id'],
        ];
    }
} catch (PDOException $e) {
    // restaurants table may not exist — ignore silently
}

// ── Categories ───────────────────────────────────────────────
$stmt3 = $pdo->prepare(
    "SELECT category, COUNT(*) as count
     FROM foods
     WHERE category LIKE ?
     GROUP BY category
     ORDER BY category ASC
     LIMIT 4"
);
$stmt3->execute([$like]);
foreach ($stmt3->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $results['categories'][] = [
        'name'  => $row['category'],
        'count' => (int) $row['count'],
        'url'   => 'menu.php?category=' . urlencode($row['category']),
    ];
}

echo json_encode($results, JSON_UNESCAPED_UNICODE);

