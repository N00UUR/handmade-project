<?php
session_start();

require_once __DIR__ . '/chatbot_bootstrap.php';
require_once __DIR__ . '/chatbot_reply_engine.php';

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
chatbot_enforce_rate_limit('message', 20, 60);

$raw_input = file_get_contents('php://input');
$payload = json_decode($raw_input, true);

if (!is_array($payload)) {
    chatbot_json_response([
        'success' => false,
        'message' => 'Invalid request payload.',
    ], 400);
}

$topic = isset($payload['topic']) ? trim((string) $payload['topic']) : '';
$message = isset($payload['message']) ? trim((string) $payload['message']) : '';

if ($topic === '' || $message === '') {
    chatbot_json_response([
        'success' => false,
        'message' => 'Topic and message are required.',
    ], 422);
}

if (!preg_match('/^[a-z_]{2,100}$/', $topic)) {
    chatbot_json_response([
        'success' => false,
        'message' => 'Invalid topic format.',
    ], 422);
}

if (mb_strlen($message) > 500) {
    chatbot_json_response([
        'success' => false,
        'message' => 'Please keep your message under 500 characters.',
    ], 422);
}

$stmt = $conn->prepare("
    SELECT option_label, option_response
    FROM chatbot_options
    WHERE option_key = ? AND is_active = 1
    LIMIT 1
");
$stmt->bind_param('s', $topic);
$stmt->execute();
$result = $stmt->get_result();
$option = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$option) {
    chatbot_json_response([
        'success' => false,
        'message' => 'Topic not found.',
    ], 404);
}

$reply = chatbot_build_reply($topic, $option['option_label'], $option['option_response'], $message);

chatbot_json_response([
    'success' => true,
    'reply' => $reply,
]);
