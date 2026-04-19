<?php
session_start();

require_once __DIR__ . '/chatbot_bootstrap.php';

chatbot_initialize($conn);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.',
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$order_number = isset($_POST['order_number']) ? trim($_POST['order_number']) : '';
$issue_message = isset($_POST['issue_message']) ? trim($_POST['issue_message']) : '';
$user_id = isset($_SESSION['is_logged_in'], $_SESSION['user_id']) && $_SESSION['is_logged_in'] === true
    ? (int) $_SESSION['user_id']
    : null;

if ($order_number !== '' && mb_strlen($order_number) > 100) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Please enter an order number that is 100 characters or fewer.',
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($issue_message === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'ÙŠØ±Ø¬Ù‰ ÙƒØªØ§Ø¨Ø© Ø§Ù„Ù…Ø´ÙƒÙ„Ø© Ø£ÙˆÙ„Ø§Ù‹.',
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (mb_strlen($issue_message) > 1000) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Ù†Øµ Ø§Ù„Ù…Ø´ÙƒÙ„Ø© Ø·ÙˆÙŠÙ„ Ø¬Ø¯Ù‹Ø§. Ø­Ø§ÙˆÙ„ Ø£Ù† ÙŠÙƒÙˆÙ† Ø£Ù‚Ù„ Ù…Ù† 1000 Ø­Ø±Ù.',
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$order_number = $order_number !== '' ? $order_number : null;

$stmt = $conn->prepare("
    INSERT INTO chatbot_issues (user_id, order_number, issue_message)
    VALUES (?, ?, ?)
");
$stmt->bind_param('iss', $user_id, $order_number, $issue_message);
$success = $stmt->execute();
$stmt->close();

if (!$success) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'ØªØ¹Ø°Ø± Ø­ÙØ¸ Ø§Ù„Ù…Ø´ÙƒÙ„Ø© Ø­Ø§Ù„ÙŠÙ‹Ø§.',
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

echo json_encode([
    'success' => true,
    'message' => 'ØªÙ… Ø¥Ø±Ø³Ø§Ù„ Ø§Ù„Ù…Ø´ÙƒÙ„Ø© Ø¨Ù†Ø¬Ø§Ø­.',
], JSON_UNESCAPED_UNICODE);
