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

$raw_input = file_get_contents('php://input');
$payload = json_decode($raw_input, true);

$topic = isset($payload['topic']) ? trim((string) $payload['topic']) : '';
$message = isset($payload['message']) ? trim((string) $payload['message']) : '';

if ($topic === '' || $message === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Topic and message are required.',
    ], JSON_UNESCAPED_UNICODE);
    exit();
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
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Topic not found.',
    ], JSON_UNESCAPED_UNICODE);
    exit();
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

    if (chatbot_text_has_any($normalized, ['شكرا', 'thanks', 'thank you'])) {
        return 'You are welcome. If you need anything else about ' . $label . ', send another message.';
    }

    if (chatbot_text_has_any($normalized, ['مرحبا', 'اهلا', 'hello', 'hi'])) {
        return 'Hello. Ask me anything about ' . $label . '.';
    }

    switch ($topic) {
        case 'shipping':
            if (chatbot_text_has_any($normalized, ['مدة', 'وقت', 'متى', 'days', 'when', 'time'])) {
                return 'Shipping usually takes from 3 to 7 business days inside Egypt, depending on the destination.';
            }
            if (chatbot_text_has_any($normalized, ['سعر', 'تكلفة', 'cost', 'price'])) {
                return 'Shipping cost appears during checkout and changes based on the governorate and the order details.';
            }
            if (chatbot_text_has_any($normalized, ['تتبع', 'tracking', 'track'])) {
                return 'After the order is confirmed, you can follow its status from the order details or by contacting support with the order number.';
            }
            break;

        case 'payment':
            if (chatbot_text_has_any($normalized, ['استلام', 'cash', 'cod'])) {
                return 'Cash on delivery is available when that payment method appears during checkout.';
            }
            if (chatbot_text_has_any($normalized, ['تحويل', 'bank', 'transfer'])) {
                return 'Bank transfer can be used when it is enabled for your order. The payment instructions are shown at checkout.';
            }
            break;

        case 'returns':
            if (chatbot_text_has_any($normalized, ['مدة', 'يوم', 'days', 'period'])) {
                return 'You can request a return within 14 days as long as the product is still in its original condition.';
            }
            if (chatbot_text_has_any($normalized, ['حالة', 'used', 'open', 'opened'])) {
                return 'The product should stay in its original condition and should not be used if you want the return request to be accepted.';
            }
            break;

        case 'buy':
            if (chatbot_text_has_any($normalized, ['ازاي', 'how', 'steps', 'اشتري'])) {
                return 'Browse the products, add what you want to the cart, then complete login and checkout from the cart page.';
            }
            break;

        case 'sell':
            if (chatbot_text_has_any($normalized, ['حساب', 'account', 'seller'])) {
                return 'Create a seller account first, then add your products from the seller dashboard with images, price, and available quantity.';
            }
            break;
    }

    return $default_response . ' If you want a more specific answer, send another short question in the same topic.';
}

$reply = chatbot_build_reply($topic, $option['option_label'], $option['option_response'], $message);

echo json_encode([
    'success' => true,
    'reply' => $reply,
], JSON_UNESCAPED_UNICODE);
