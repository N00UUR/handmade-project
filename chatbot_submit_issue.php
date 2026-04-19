<?php
session_start();

require_once __DIR__ . '/chatbot_bootstrap.php';

chatbot_initialize($conn);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chatbot_json_response([
        'success' => false,
        'message' => 'Method not allowed.',
    ], 405);
}

chatbot_require_csrf_token();

$user_id = isset($_SESSION['is_logged_in'], $_SESSION['user_id']) && $_SESSION['is_logged_in'] === true
    ? (int) $_SESSION['user_id']
    : null;
$rate_limit_key = $user_id !== null ? 'issue_user_' . $user_id : 'issue_guest';
chatbot_enforce_rate_limit($rate_limit_key, $user_id !== null ? 6 : 3, 600);

$order_number = isset($_POST['order_number']) ? trim((string) $_POST['order_number']) : '';
$issue_message = isset($_POST['issue_message']) ? trim((string) $_POST['issue_message']) : '';

if ($order_number !== '' && mb_strlen($order_number) > 100) {
    chatbot_json_response([
        'success' => false,
        'message' => 'Please enter an order number that is 100 characters or fewer.',
    ], 422);
}

if ($order_number !== '' && !preg_match('/^[A-Za-z0-9#\-_\/ ]+$/', $order_number)) {
    chatbot_json_response([
        'success' => false,
        'message' => 'Order numbers may only contain letters, numbers, spaces, #, -, _, or /.',
    ], 422);
}

if ($issue_message === '') {
    chatbot_json_response([
        'success' => false,
        'message' => 'Please describe the issue first.',
    ], 422);
}

if (mb_strlen($issue_message) < 5) {
    chatbot_json_response([
        'success' => false,
        'message' => 'Please provide a little more detail about the issue.',
    ], 422);
}

if (mb_strlen($issue_message) > 1000) {
    chatbot_json_response([
        'success' => false,
        'message' => 'Please keep the issue description under 1000 characters.',
    ], 422);
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
    chatbot_json_response([
        'success' => false,
        'message' => 'Could not save the issue right now.',
    ], 500);
}

chatbot_json_response([
    'success' => true,
    'message' => 'Your issue has been sent successfully.',
]);
