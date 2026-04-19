<?php
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/db_connect.php';
}

function chatbot_ensure_session_started(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function chatbot_json_response(array $payload, int $status_code = 200): void
{
    http_response_code($status_code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit();
}

function chatbot_get_csrf_token(): string
{
    chatbot_ensure_session_started();

    if (empty($_SESSION['chatbot_csrf_token'])) {
        $_SESSION['chatbot_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['chatbot_csrf_token'];
}

function chatbot_require_csrf_token(): void
{
    chatbot_ensure_session_started();

    $token = isset($_SERVER['HTTP_X_CHATBOT_CSRF']) ? trim((string) $_SERVER['HTTP_X_CHATBOT_CSRF']) : '';
    $session_token = isset($_SESSION['chatbot_csrf_token']) ? (string) $_SESSION['chatbot_csrf_token'] : '';

    if ($token === '' || $session_token === '' || !hash_equals($session_token, $token)) {
        chatbot_json_response([
            'success' => false,
            'message' => 'Invalid chatbot security token. Refresh the page and try again.',
        ], 403);
    }
}

function chatbot_enforce_rate_limit(string $key, int $max_attempts, int $window_seconds): void
{
    chatbot_ensure_session_started();

    $now = time();

    if (!isset($_SESSION['chatbot_rate_limits']) || !is_array($_SESSION['chatbot_rate_limits'])) {
        $_SESSION['chatbot_rate_limits'] = [];
    }

    $entries = $_SESSION['chatbot_rate_limits'][$key] ?? [];
    $entries = array_values(array_filter($entries, static function ($timestamp) use ($now, $window_seconds) {
        return is_int($timestamp) && ($now - $timestamp) < $window_seconds;
    }));

    if (count($entries) >= $max_attempts) {
        $oldest_entry = $entries[0];
        $retry_after = max(1, $window_seconds - ($now - $oldest_entry));

        header('Retry-After: ' . $retry_after);
        chatbot_json_response([
            'success' => false,
            'message' => 'Too many chatbot requests. Please wait a moment and try again.',
        ], 429);
    }

    $entries[] = $now;
    $_SESSION['chatbot_rate_limits'][$key] = $entries;
}

function chatbot_ensure_tables(mysqli $conn): void
{
    $queries = [
        "CREATE TABLE IF NOT EXISTS chatbot_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NOT NULL
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS chatbot_options (
            id INT AUTO_INCREMENT PRIMARY KEY,
            option_key VARCHAR(100) NOT NULL UNIQUE,
            option_label VARCHAR(255) NOT NULL,
            option_response TEXT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS chatbot_issues (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            order_number VARCHAR(100) NULL,
            issue_message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_chatbot_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    foreach ($queries as $query) {
        $conn->query($query);
    }
}

function chatbot_seed_defaults(mysqli $conn): void
{
    $settings = [
        'welcome_message' => 'أهلاً بك في متجر المنتجات اليدوية. اختر من القائمة التالية وسأساعدك فورًا.',
    ];

    $setting_stmt = $conn->prepare("
        INSERT INTO chatbot_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_key = setting_key
    ");

    foreach ($settings as $key => $value) {
        $setting_stmt->bind_param('ss', $key, $value);
        $setting_stmt->execute();
    }
    $setting_stmt->close();

    $options = [
        ['shipping', 'الاستفسار عن الشحن', 'مدة الشحن من 3 إلى 7 أيام عمل داخل مصر، وتكلفة الشحن تظهر عند تأكيد الطلب حسب المحافظة.', 1, 1],
        ['payment', 'طرق الدفع', 'يمكنك الدفع عند الاستلام أو باستخدام التحويل البنكي حسب توفر الطريقة في طلبك.', 2, 1],
        ['returns', 'سياسة الاسترجاع', 'يمكنك طلب الاسترجاع خلال 14 يومًا إذا كان المنتج بحالته الأصلية ولم يتم استخدامه.', 3, 1],
        ['buy', 'عايز أشتري', 'تصفح المنتجات، أضف ما يناسبك إلى السلة، ثم أكمل تسجيل الدخول وإتمام الطلب من صفحة السلة.', 4, 1],
        ['sell', 'عايز أبيع على الموقع', 'سجل حسابًا كبائع ثم قم بإضافة منتجاتك من لوحة البائع مع الصور والسعر والكمية المتاحة.', 5, 1],
        ['issue', 'عندي مشكلة في طلب', 'اكتب رقم الطلب إن وجد ثم اشرح المشكلة، وسيتم حفظها لمراجعتها من الإدارة.', 6, 1],
        ['product_search', 'اسأل عن منتج', 'اكتب اسم المنتج أو جزءًا منه وسأعرض لك النتائج المتاحة.', 7, 1],
    ];

    $option_stmt = $conn->prepare("
        INSERT INTO chatbot_options (option_key, option_label, option_response, sort_order, is_active)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            option_key = option_key
    ");

    foreach ($options as $option) {
        [$key, $label, $response, $order, $active] = $option;
        $option_stmt->bind_param('sssii', $key, $label, $response, $order, $active);
        $option_stmt->execute();
    }
    $option_stmt->close();
}

function chatbot_initialize(mysqli $conn): void
{
    chatbot_ensure_tables($conn);
    chatbot_seed_defaults($conn);
}

function chatbot_get_settings(mysqli $conn): array
{
    $settings = [];
    $result = $conn->query("SELECT setting_key, setting_value FROM chatbot_settings");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    return $settings;
}

function chatbot_get_options(mysqli $conn, bool $active_only = true): array
{
    $options = [];
    $sql = "SELECT id, option_key, option_label, option_response, sort_order, is_active
            FROM chatbot_options";

    if ($active_only) {
        $sql .= " WHERE is_active = 1";
    }

    $sql .= " ORDER BY sort_order ASC, id ASC";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $options[] = $row;
        }
    }

    return $options;
}
