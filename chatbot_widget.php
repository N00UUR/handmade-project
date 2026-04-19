<?php
require_once __DIR__ . '/chatbot_bootstrap.php';

chatbot_ensure_session_started();
chatbot_initialize($conn);

$chatbot_settings = chatbot_get_settings($conn);
$chatbot_options = chatbot_get_options($conn, true);
$chatbot_csrf_token = chatbot_get_csrf_token();
$chatbot_config_endpoint = 'chatbot_config.php';
$chatbot_issue_endpoint = 'chatbot_submit_issue.php';
$chatbot_message_endpoint = 'chatbot_message.php';
$chatbot_product_search_endpoint = 'chatbot_product_search.php';
$chatbot_css_version = file_exists(__DIR__ . '/css/chatbot.css') ? (string) filemtime(__DIR__ . '/css/chatbot.css') : '1';
$chatbot_js_version = file_exists(__DIR__ . '/js/chatbot.js') ? (string) filemtime(__DIR__ . '/js/chatbot.js') : '1';
?>
<link rel="stylesheet" href="css/chatbot.css?v=<?php echo urlencode($chatbot_css_version); ?>">

<div
    class="chatbot-widget"
    dir="rtl"
    data-chatbot-widget
    data-config-url="<?php echo htmlspecialchars($chatbot_config_endpoint, ENT_QUOTES, 'UTF-8'); ?>"
    data-issue-url="<?php echo htmlspecialchars($chatbot_issue_endpoint, ENT_QUOTES, 'UTF-8'); ?>"
    data-message-url="<?php echo htmlspecialchars($chatbot_message_endpoint, ENT_QUOTES, 'UTF-8'); ?>"
    data-product-search-url="<?php echo htmlspecialchars($chatbot_product_search_endpoint, ENT_QUOTES, 'UTF-8'); ?>"
    data-csrf-token="<?php echo htmlspecialchars($chatbot_csrf_token, ENT_QUOTES, 'UTF-8'); ?>"
    data-welcome-message="<?php echo htmlspecialchars($chatbot_settings['welcome_message'] ?? 'Welcome. Choose a topic to start.', ENT_QUOTES, 'UTF-8'); ?>"
>
    <button type="button" class="chatbot-toggle" data-chatbot-toggle aria-expanded="false" aria-controls="chatbot-panel">
       <img src="img/chat-icon.svg" alt="chat">
    </button>

    <section class="chatbot-panel" id="chatbot-panel" data-chatbot-panel hidden>
        <header class="chatbot-header">
            <div>
                <h2 class="chatbot-title">Store Assistant</h2>
                <p class="chatbot-subtitle">Quick help in chat format</p>
            </div>
            <button type="button" class="chatbot-close" data-chatbot-close aria-label="Close">x</button>
        </header>

        <div class="chatbot-body">
            <div class="chatbot-content" data-chatbot-content>
                <div class="chatbot-home" data-chatbot-home>
                    <div class="chatbot-options" data-chatbot-options>
                        <?php foreach ($chatbot_options as $chatbot_option): ?>
                            <button
                                type="button"
                                class="chatbot-option"
                                data-option-key="<?php echo htmlspecialchars($chatbot_option['option_key'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-option-label="<?php echo htmlspecialchars($chatbot_option['option_label'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-option-response="<?php echo htmlspecialchars($chatbot_option['option_response'], ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <?php echo htmlspecialchars($chatbot_option['option_label'], ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="chatbot-messages chatbot-welcome-messages" data-chatbot-welcome-messages>
                        <div class="chatbot-bubble chatbot-bubble-system" data-chatbot-status><?php echo htmlspecialchars($chatbot_settings['welcome_message'] ?? 'Welcome. Choose a topic to start.', ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>

                <div class="chatbot-conversation" data-chatbot-conversation hidden>
                    <div class="chatbot-topic-bar">
                        <div class="chatbot-topic-chip" data-chatbot-topic-label></div>
                        <button type="button" class="chatbot-secondary chatbot-secondary-small" data-chatbot-reset>Back</button>
                    </div>
                    <div class="chatbot-messages" data-chatbot-messages></div>
                    <div class="chatbot-search-results" data-chatbot-search-results hidden></div>
                </div>
            </div>

            <div class="chatbot-footer">
                <form class="chatbot-chat-form" data-chatbot-chat-form>
                    <div class="chatbot-inline-form">
                        <input
                            type="text"
                            id="chatbot-order-number"
                            name="order_number"
                            class="chatbot-input chatbot-inline-input"
                            placeholder="Order number (optional)"
                            autocomplete="off"
                            hidden
                        >
                        <input
                            type="text"
                            id="chatbot-message"
                            name="message"
                            class="chatbot-input chatbot-inline-input"
                            placeholder="Choose, write your question"
                            autocomplete="off"
                        >
                        <button type="submit" class="chatbot-send-button" aria-label="Send">↑</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<script src="js/chatbot.js?v=<?php echo urlencode($chatbot_js_version); ?>" defer></script>
