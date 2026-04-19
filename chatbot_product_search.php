<?php
session_start();

require_once __DIR__ . '/chatbot_bootstrap.php';

chatbot_initialize($conn);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.',
        'products' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($query === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'يرجى كتابة اسم المنتج أو كلمة للبحث.',
        'products' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (mb_strlen($query) > 100) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'كلمة البحث طويلة جدًا.',
        'products' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit();
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
    echo json_encode([
        'success' => true,
        'message' => 'لم أجد منتجات مطابقة حاليًا. جرّب اسمًا آخر أو كلمة أقرب لوصف المنتج.',
        'products' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

echo json_encode([
    'success' => true,
    'message' => 'تم العثور على منتجات مناسبة.',
    'products' => $products,
], JSON_UNESCAPED_UNICODE);
