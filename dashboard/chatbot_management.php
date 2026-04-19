<?php
require '../db_connect.php';
require '../chatbot_bootstrap.php';

session_start();
if (!isset($_SESSION["is_admin_logged_in"]) || $_SESSION["is_admin_logged_in"] !== true) {
    header("Location: login.php");
    exit();
}

chatbot_initialize($conn);

function chatbot_management_build_option_map(array $options): array
{
    $map = [];

    foreach ($options as $option) {
        $map[(int) $option['id']] = $option;
    }

    return $map;
}

function chatbot_management_is_valid_key(string $key): bool
{
    return (bool) preg_match('/^[a-z_]{2,100}$/', $key);
}

$errors = [];
$success_messages = [];
$default_option_keys = chatbot_get_default_option_keys();
$options = chatbot_get_options($conn, false);
$options_by_id = chatbot_management_build_option_map($options);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $welcome_message = isset($_POST['welcome_message']) ? trim((string) $_POST['welcome_message']) : '';
    $option_ids = $_POST['option_id'] ?? [];
    $option_labels = $_POST['option_label'] ?? [];
    $option_responses = $_POST['option_response'] ?? [];
    $sort_orders = $_POST['sort_order'] ?? [];
    $active_ids = $_POST['is_active'] ?? [];
    $delete_ids = $_POST['delete_option'] ?? [];

    $new_option_key = isset($_POST['new_option_key']) ? trim((string) $_POST['new_option_key']) : '';
    $new_option_label = isset($_POST['new_option_label']) ? trim((string) $_POST['new_option_label']) : '';
    $new_option_response = isset($_POST['new_option_response']) ? trim((string) $_POST['new_option_response']) : '';
    $new_sort_order = isset($_POST['new_sort_order']) ? (int) $_POST['new_sort_order'] : count($options) + 1;
    $new_is_active = isset($_POST['new_is_active']) ? 1 : 0;

    $delete_ids = array_map('intval', $delete_ids);

    if ($welcome_message === '') {
        $errors[] = 'Welcome message is required.';
    }

    if ($new_option_key !== '' || $new_option_label !== '' || $new_option_response !== '') {
        if ($new_option_key === '' || $new_option_label === '' || $new_option_response === '') {
            $errors[] = 'New topics need a topic key, button label, and response text.';
        } elseif (!chatbot_management_is_valid_key($new_option_key)) {
            $errors[] = 'Topic keys must use lowercase letters and underscores only.';
        } elseif (in_array($new_option_key, array_column($options, 'option_key'), true)) {
            $errors[] = 'That topic key already exists.';
        }
    }

    foreach ($option_ids as $index => $option_id_raw) {
        $option_id = (int) $option_id_raw;

        if (in_array($option_id, $delete_ids, true)) {
            continue;
        }

        $label = trim((string) ($option_labels[$index] ?? ''));
        $response = trim((string) ($option_responses[$index] ?? ''));

        if ($label === '' || $response === '') {
            $errors[] = 'Existing topics must keep both a button label and a response text.';
            break;
        }
    }

    if (empty($errors)) {
        $settings_stmt = $conn->prepare("
            UPDATE chatbot_settings
            SET setting_value = ?
            WHERE setting_key = 'welcome_message'
        ");
        $settings_stmt->bind_param('s', $welcome_message);
        $settings_stmt->execute();
        $settings_stmt->close();

        $option_stmt = $conn->prepare("
            UPDATE chatbot_options
            SET option_label = ?, option_response = ?, sort_order = ?, is_active = ?
            WHERE id = ?
        ");

        foreach ($option_ids as $index => $option_id_raw) {
            $option_id = (int) $option_id_raw;

            if (!isset($options_by_id[$option_id]) || in_array($option_id, $delete_ids, true)) {
                continue;
            }

            $label = trim((string) ($option_labels[$index] ?? ''));
            $response = trim((string) ($option_responses[$index] ?? ''));
            $order = isset($sort_orders[$index]) ? max(1, (int) $sort_orders[$index]) : $index + 1;
            $is_active = in_array((string) $option_id, $active_ids, true) ? 1 : 0;

            $option_stmt->bind_param('ssiii', $label, $response, $order, $is_active, $option_id);
            $option_stmt->execute();
        }

        $option_stmt->close();

        if ($new_option_key !== '' && $new_option_label !== '' && $new_option_response !== '') {
            $insert_stmt = $conn->prepare("
                INSERT INTO chatbot_options (option_key, option_label, option_response, sort_order, is_active)
                VALUES (?, ?, ?, ?, ?)
            ");
            $new_sort_order = max(1, $new_sort_order);
            $insert_stmt->bind_param('sssii', $new_option_key, $new_option_label, $new_option_response, $new_sort_order, $new_is_active);
            $insert_stmt->execute();
            $insert_stmt->close();
            $success_messages[] = 'New chatbot topic added.';
        }

        $deactivated_defaults = 0;
        $deleted_custom_topics = 0;

        if (!empty($delete_ids)) {
            $deactivate_stmt = $conn->prepare("
                UPDATE chatbot_options
                SET is_active = 0
                WHERE id = ?
            ");
            $delete_stmt = $conn->prepare("
                DELETE FROM chatbot_options
                WHERE id = ?
            ");

            foreach ($delete_ids as $option_id) {
                if (!isset($options_by_id[$option_id])) {
                    continue;
                }

                $option_key = (string) $options_by_id[$option_id]['option_key'];

                if (in_array($option_key, $default_option_keys, true)) {
                    $deactivate_stmt->bind_param('i', $option_id);
                    $deactivate_stmt->execute();
                    $deactivated_defaults++;
                    continue;
                }

                $delete_stmt->bind_param('i', $option_id);
                $delete_stmt->execute();
                $deleted_custom_topics++;
            }

            $deactivate_stmt->close();
            $delete_stmt->close();
        }

        $success_messages[] = 'Chatbot content updated successfully.';

        if ($deactivated_defaults > 0) {
            $success_messages[] = $deactivated_defaults . ' default topic(s) were deactivated.';
        }

        if ($deleted_custom_topics > 0) {
            $success_messages[] = $deleted_custom_topics . ' custom topic(s) were deleted.';
        }

        $options = chatbot_get_options($conn, false);
        $options_by_id = chatbot_management_build_option_map($options);
    }
}

$settings = chatbot_get_settings($conn);

$issues_query = "
    SELECT ci.id, ci.order_number, ci.issue_message, ci.created_at, u.username
    FROM chatbot_issues ci
    LEFT JOIN users u ON ci.user_id = u.user_id
    ORDER BY ci.created_at DESC
    LIMIT 20
";
$issues_result = $conn->query($issues_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Management</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .panel {
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group textarea,
        .form-group input[type="text"],
        .form-group input[type="number"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-family: inherit;
        }

        .option-card {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 16px;
            background: #fafafa;
        }

        .option-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 12px;
        }

        .option-card h3,
        .option-card h4 {
            margin: 0;
            color: #4b49ac;
        }

        .key-row {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .topic-key {
            font-family: Consolas, monospace;
            background: #f0f2ff;
            color: #2b2f77;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .topic-badge {
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .topic-badge-default {
            background: #e8f0ff;
            color: #204a9a;
        }

        .topic-badge-custom {
            background: #eaf7ee;
            color: #227a45;
        }

        .status-message {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }

        .status-success {
            background: #e8f6ec;
            color: #1d6f42;
        }

        .status-error {
            background: #fdecea;
            color: #a12622;
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .muted-text {
            color: #666;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Chatbot Management</h1>
    </header>

    <div class="main-container">
        <?php include 'navigation.php'; ?>

        <div class="main">
            <?php foreach ($success_messages as $success_message): ?>
                <div class="status-message status-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endforeach; ?>

            <?php if (!empty($errors)): ?>
                <div class="status-message status-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="chatbot_management.php">
                <div class="panel">
                    <h2>Welcome Message</h2>
                    <div class="form-group">
                        <label for="welcome_message">Message shown when the chatbot opens</label>
                        <textarea id="welcome_message" name="welcome_message" rows="4" dir="rtl"><?= htmlspecialchars($settings['welcome_message'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="panel">
                    <div class="option-header">
                        <h2>Chatbot Topics</h2>
                        <div class="muted-text">The widget shows active topics only.</div>
                    </div>

                    <?php foreach ($options as $option): ?>
                        <?php $is_default_topic = in_array($option['option_key'], $default_option_keys, true); ?>
                        <div class="option-card">
                            <div class="key-row">
                                <span class="topic-key"><?= htmlspecialchars($option['option_key']) ?></span>
                                <span class="topic-badge <?= $is_default_topic ? 'topic-badge-default' : 'topic-badge-custom' ?>">
                                    <?= $is_default_topic ? 'Default topic' : 'Custom topic' ?>
                                </span>
                            </div>

                            <input type="hidden" name="option_id[]" value="<?= (int) $option['id'] ?>">

                            <div class="form-group">
                                <label>Button Label</label>
                                <input type="text" name="option_label[]" value="<?= htmlspecialchars($option['option_label']) ?>" dir="rtl">
                            </div>

                            <div class="form-group">
                                <label>Response Text</label>
                                <textarea name="option_response[]" rows="4" dir="rtl"><?= htmlspecialchars($option['option_response']) ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Sort Order</label>
                                <input type="number" name="sort_order[]" value="<?= (int) $option['sort_order'] ?>" min="1">
                            </div>

                            <label class="checkbox-row">
                                <input
                                    type="checkbox"
                                    name="is_active[]"
                                    value="<?= (int) $option['id'] ?>"
                                    <?= (int) $option['is_active'] === 1 ? 'checked' : '' ?>
                                >
                                Active
                            </label>

                            <label class="checkbox-row">
                                <input
                                    type="checkbox"
                                    name="delete_option[]"
                                    value="<?= (int) $option['id'] ?>"
                                >
                                <?= $is_default_topic ? 'Deactivate this default topic' : 'Delete this custom topic' ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="panel">
                    <h2>Add New Topic</h2>
                    <div class="form-group">
                        <label for="new_option_key">Topic Key</label>
                        <input id="new_option_key" type="text" name="new_option_key" value="" placeholder="example_topic">
                        <div class="muted-text">Use lowercase letters and underscores only.</div>
                    </div>

                    <div class="form-group">
                        <label for="new_option_label">Button Label</label>
                        <input id="new_option_label" type="text" name="new_option_label" value="" dir="rtl">
                    </div>

                    <div class="form-group">
                        <label for="new_option_response">Response Text</label>
                        <textarea id="new_option_response" name="new_option_response" rows="4" dir="rtl"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="new_sort_order">Sort Order</label>
                        <input id="new_sort_order" type="number" name="new_sort_order" value="<?= count($options) + 1 ?>" min="1">
                    </div>

                    <label class="checkbox-row">
                        <input type="checkbox" name="new_is_active" value="1" checked>
                        Active
                    </label>
                </div>

                <button type="submit" class="btn edit">Save Chatbot Settings</button>
            </form>

            <div class="panel">
                <h2>Recent Submitted Issues</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Order Number</th>
                            <th>Issue</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($issues_result && $issues_result->num_rows > 0): ?>
                            <?php while ($issue = $issues_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= (int) $issue['id'] ?></td>
                                    <td><?= htmlspecialchars($issue['username'] ?? 'Guest') ?></td>
                                    <td><?= htmlspecialchars($issue['order_number'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($issue['issue_message']) ?></td>
                                    <td><?= htmlspecialchars($issue['created_at']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No chatbot issues have been submitted yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
