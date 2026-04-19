<?php
session_start();

require_once __DIR__ . '/chatbot_bootstrap.php';

chatbot_initialize($conn);

header('Content-Type: application/json; charset=utf-8');

$settings = chatbot_get_settings($conn);
$options = chatbot_get_options($conn, true);

echo json_encode([
    'success' => true,
    'welcome_message' => $settings['welcome_message'] ?? '',
    'options' => $options,
], JSON_UNESCAPED_UNICODE);
