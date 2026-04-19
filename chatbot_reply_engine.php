<?php

function chatbot_text_has_any(string $text, array $needles): bool
{
    foreach ($needles as $needle) {
        if ($needle !== '' && mb_stripos($text, $needle) !== false) {
            return true;
        }
    }

    return false;
}

function chatbot_get_common_reply_rules(): array
{
    return [
        [
            'keywords' => ['Ã˜Â´Ã™Æ’Ã˜Â±Ã˜Â§', 'thanks', 'thank you'],
            'reply' => static function (string $label): string {
                return 'You are welcome. If you need anything else about ' . $label . ', send another message.';
            },
        ],
        [
            'keywords' => ['Ã™â€¦Ã˜Â±Ã˜Â­Ã˜Â¨Ã˜Â§', 'Ã˜Â§Ã™â€¡Ã™â€žÃ˜Â§', 'hello', 'hi'],
            'reply' => static function (string $label): string {
                return 'Hello. Ask me anything about ' . $label . '.';
            },
        ],
    ];
}

function chatbot_get_topic_reply_rules(): array
{
    return [
        'shipping' => [
            [
                'keywords' => ['Ã™â€¦Ã˜Â¯Ã˜Â©', 'Ã™Ë†Ã™â€šÃ˜Âª', 'Ã™â€¦Ã˜ÂªÃ™â€°', 'days', 'when', 'time'],
                'reply' => 'Shipping usually takes from 3 to 7 business days inside Egypt, depending on the destination.',
            ],
            [
                'keywords' => ['Ã˜Â³Ã˜Â¹Ã˜Â±', 'Ã˜ÂªÃ™Æ’Ã™â€žÃ™ÂÃ˜Â©', 'cost', 'price'],
                'reply' => 'Shipping cost appears during checkout and changes based on the governorate and the order details.',
            ],
            [
                'keywords' => ['Ã˜ÂªÃ˜ÂªÃ˜Â¨Ã˜Â¹', 'tracking', 'track'],
                'reply' => 'After the order is confirmed, you can follow its status from the order details or by contacting support with the order number.',
            ],
        ],
        'payment' => [
            [
                'keywords' => ['Ã˜Â§Ã˜Â³Ã˜ÂªÃ™â€žÃ˜Â§Ã™â€¦', 'cash', 'cod'],
                'reply' => 'Cash on delivery is available when that payment method appears during checkout.',
            ],
            [
                'keywords' => ['Ã˜ÂªÃ˜Â­Ã™Ë†Ã™Å Ã™â€ž', 'bank', 'transfer'],
                'reply' => 'Bank transfer can be used when it is enabled for your order. The payment instructions are shown at checkout.',
            ],
        ],
        'returns' => [
            [
                'keywords' => ['Ã™â€¦Ã˜Â¯Ã˜Â©', 'Ã™Å Ã™Ë†Ã™â€¦', 'days', 'period'],
                'reply' => 'You can request a return within 14 days as long as the product is still in its original condition.',
            ],
            [
                'keywords' => ['Ã˜Â­Ã˜Â§Ã™â€žÃ˜Â©', 'used', 'open', 'opened'],
                'reply' => 'The product should stay in its original condition and should not be used if you want the return request to be accepted.',
            ],
        ],
        'buy' => [
            [
                'keywords' => ['Ã˜Â§Ã˜Â²Ã˜Â§Ã™Å ', 'how', 'steps', 'Ã˜Â§Ã˜Â´Ã˜ÂªÃ˜Â±Ã™Å '],
                'reply' => 'Browse the products, add what you want to the cart, then complete login and checkout from the cart page.',
            ],
        ],
        'sell' => [
            [
                'keywords' => ['Ã˜Â­Ã˜Â³Ã˜Â§Ã˜Â¨', 'account', 'seller'],
                'reply' => 'Create a seller account first, then add your products from the seller dashboard with images, price, and available quantity.',
            ],
        ],
    ];
}

function chatbot_resolve_rule_reply($reply, string $label): string
{
    if (is_callable($reply)) {
        return (string) $reply($label);
    }

    return (string) $reply;
}

function chatbot_find_matching_reply(string $normalized_message, array $rules, string $label): ?string
{
    foreach ($rules as $rule) {
        $keywords = $rule['keywords'] ?? [];

        if (!is_array($keywords) || !chatbot_text_has_any($normalized_message, $keywords)) {
            continue;
        }

        return chatbot_resolve_rule_reply($rule['reply'] ?? '', $label);
    }

    return null;
}

function chatbot_build_reply(string $topic, string $label, string $default_response, string $message): string
{
    $normalized = mb_strtolower($message);

    $common_reply = chatbot_find_matching_reply($normalized, chatbot_get_common_reply_rules(), $label);
    if ($common_reply !== null) {
        return $common_reply;
    }

    $topic_rules = chatbot_get_topic_reply_rules();
    $topic_reply = chatbot_find_matching_reply($normalized, $topic_rules[$topic] ?? [], $label);
    if ($topic_reply !== null) {
        return $topic_reply;
    }

    return $default_response . ' If you want a more specific answer, send another short question in the same topic.';
}
