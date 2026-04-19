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
            'keywords' => ['شكرا', 'thanks', 'thank you'],
            'reply' => static function (string $label): string {
                return 'You are welcome. If you need anything else about ' . $label . ', send another message.';
            },
        ],
        [
            'keywords' => ['مرحبا', 'اهلا', 'hello', 'hi'],
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
                'keywords' => ['مدة', 'وقت', 'متى', 'days', 'when', 'time'],
                'reply' => 'Shipping usually takes from 3 to 7 business days inside Egypt, depending on the destination.',
            ],
            [
                'keywords' => ['سعر', 'تكلفة', 'cost', 'price'],
                'reply' => 'Shipping cost appears during checkout and changes based on the governorate and the order details.',
            ],
            [
                'keywords' => ['تتبع', 'tracking', 'track'],
                'reply' => 'After the order is confirmed, you can follow its status from the order details or by contacting support with the order number.',
            ],
        ],
        'payment' => [
            [
                'keywords' => ['استلام', 'cash', 'cod'],
                'reply' => 'Cash on delivery is available when that payment method appears during checkout.',
            ],
            [
                'keywords' => ['تحويل', 'bank', 'transfer'],
                'reply' => 'Bank transfer can be used when it is enabled for your order. The payment instructions are shown at checkout.',
            ],
        ],
        'returns' => [
            [
                'keywords' => ['مدة', 'يوم', 'days', 'period'],
                'reply' => 'You can request a return within 14 days as long as the product is still in its original condition.',
            ],
            [
                'keywords' => ['حالة', 'used', 'open', 'opened'],
                'reply' => 'The product should stay in its original condition and should not be used if you want the return request to be accepted.',
            ],
        ],
        'buy' => [
            [
                'keywords' => ['ازاي', 'how', 'steps', 'اشتري'],
                'reply' => 'Browse the products, add what you want to the cart, then complete login and checkout from the cart page.',
            ],
        ],
        'sell' => [
            [
                'keywords' => ['حساب', 'account', 'seller'],
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
