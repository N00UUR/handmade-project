<?php
session_start();

require_once __DIR__ . '/chatbot_bootstrap.php';

chatbot_initialize($conn);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    chatbot_json_response([
        'success' => false,
        'message' => 'Method not allowed.',
        'products' => [],
    ], 405);
}

chatbot_require_csrf_token();
chatbot_enforce_rate_limit('product_search', 30, 60);

$query = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

if ($query === '') {
    chatbot_json_response([
        'success' => false,
        'message' => 'Please enter a product name or keyword.',
        'products' => [],
    ], 422);
}

if (mb_strlen($query) > 100) {
    chatbot_json_response([
        'success' => false,
        'message' => 'Please keep the search keyword under 100 characters.',
        'products' => [],
    ], 422);
}

if (preg_match('/[\x00-\x1F\x7F]/u', $query)) {
    chatbot_json_response([
        'success' => false,
        'message' => 'The search keyword contains invalid characters.',
        'products' => [],
    ], 422);
}

$search_term = '%' . $query . '%';
$stmt = $conn->prepare("
    SELECT id, name, description, category, price, available_count, image_path
    FROM products
    WHERE name LIKE ? OR category LIKE ? OR description LIKE ?
    ORDER BY created_at DESC, id DESC
    LIMIT 5
");
$stmt->bind_param('sss', $search_term, $search_term, $search_term);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'description' => $row['description'],
        'category' => $row['category'],
        'price' => (float) $row['price'],
        'available_count' => (int) $row['available_count'],
        'image_path' => $row['image_path'],
        'product_url' => 'product_details.php?id=' . (int) $row['id'],
    ];
}

$stmt->close();

if (empty($products)) {
    chatbot_json_response([
        'success' => true,
        'message' => 'No matching products were found right now. Try another keyword.',
        'products' => [],
    ]);
}

chatbot_json_response([
    'success' => true,
    'message' => 'Matching products found.',
    'products' => $products,
]);
