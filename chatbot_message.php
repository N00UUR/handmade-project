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

function chatbot_text_has_any(string $text, array $needles): bool
{
    foreach ($needles as $needle) {
        if ($needle !== '' && mb_stripos($text, $needle) !== false) {
            return true;
        }
    }

    return false;
}

function chatbot_build_reply(string $topic, string $label, string $default_response, string $message): string
{
    $normalized = mb_strtolower($message);

    if (chatbot_text_has_any($normalized, ['Ø´ÙƒØ±Ø§', 'thanks', 'thank you'])) {
        return 'You are welcome. If you need anything else about ' . $label . ', send another message.';
    }

    if (chatbot_text_has_any($normalized, ['Ù…Ø±Ø­Ø¨Ø§', 'Ø§Ù‡Ù„Ø§', 'hello', 'hi'])) {
        return 'Hello. Ask me anything about ' . $label . '.';
    }

    switch ($topic) {
        case 'shipping':
            if (chatbot_text_has_any($normalized, ['Ù…Ø¯Ø©', 'ÙˆÙ‚Øª', 'Ù…ØªÙ‰', 'days', 'when', 'time'])) {
                return 'Shipping usually takes from 3 to 7 business days inside Egypt, depending on the destination.';
            }
            if (chatbot_text_has_any($normalized, ['Ø³Ø¹Ø±', 'ØªÙƒÙ„ÙØ©', 'cost', 'price'])) {
                return 'Shipping cost appears during checkout and changes based on the governorate and the order details.';
            }
            if (chatbot_text_has_any($normalized, ['ØªØªØ¨Ø¹', 'tracking', 'track'])) {
                return 'After the order is confirmed, you can follow its status from the order details or by contacting support with the order number.';
            }
            break;

        case 'payment':
            if (chatbot_text_has_any($normalized, ['Ø§Ø³ØªÙ„Ø§Ù…', 'cash', 'cod'])) {
                return 'Cash on delivery is available when that payment method appears during checkout.';
            }
            if (chatbot_text_has_any($normalized, ['ØªØ­ÙˆÙŠÙ„', 'bank', 'transfer'])) {
                return 'Bank transfer can be used when it is enabled for your order. The payment instructions are shown at checkout.';
            }
            break;

        case 'returns':
            if (chatbot_text_has_any($normalized, ['Ù…Ø¯Ø©', 'ÙŠÙˆÙ…', 'days', 'period'])) {
                return 'You can request a return within 14 days as long as the product is still in its original condition.';
            }
            if (chatbot_text_has_any($normalized, ['Ø­Ø§Ù„Ø©', 'used', 'open', 'opened'])) {
                return 'The product should stay in its original condition and should not be used if you want the return request to be accepted.';
            }
            break;

        case 'buy':
            if (chatbot_text_has_any($normalized, ['Ø§Ø²Ø§ÙŠ', 'how', 'steps', 'Ø§Ø´ØªØ±ÙŠ'])) {
                return 'Browse the products, add what you want to the cart, then complete login and checkout from the cart page.';
            }
            break;

        case 'sell':
            if (chatbot_text_has_any($normalized, ['Ø­Ø³Ø§Ø¨', 'account', 'seller'])) {
                return 'Create a seller account first, then add your products from the seller dashboard with images, price, and available quantity.';
            }
            break;
    }

    return $default_response . ' If you want a more specific answer, send another short question in the same topic.';
}

$reply = chatbot_build_reply($topic, $option['option_label'], $option['option_response'], $message);

chatbot_json_response([
    'success' => true,
    'reply' => $reply,
]);
