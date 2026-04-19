<?php
require '../db_connect.php';
require '../chatbot_bootstrap.php';

session_start();
if (!isset($_SESSION["is_admin_logged_in"]) || $_SESSION["is_admin_logged_in"] !== true) {
    header("Location: login.php");
    exit();
}

chatbot_initialize($conn);

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $welcome_message = isset($_POST['welcome_message']) ? trim($_POST['welcome_message']) : '';
    $option_ids = $_POST['option_id'] ?? [];
    $option_labels = $_POST['option_label'] ?? [];
    $option_responses = $_POST['option_response'] ?? [];
    $sort_orders = $_POST['sort_order'] ?? [];
    $active_ids = $_POST['is_active'] ?? [];

    if ($welcome_message === '') {
        $errors[] = 'Welcome message is required.';
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

        foreach ($option_ids as $index => $option_id) {
            $option_id = (int) $option_id;
            $label = trim($option_labels[$index] ?? '');
            $response = trim($option_responses[$index] ?? '');
            $order = isset($sort_orders[$index]) ? (int) $sort_orders[$index] : $index + 1;
            $is_active = in_array((string) $option_id, $active_ids, true) ? 1 : 0;

            if ($label === '' || $response === '') {
                continue;
            }

            $option_stmt->bind_param('ssiii', $label, $response, $order, $is_active, $option_id);
            $option_stmt->execute();
        }

        $option_stmt->close();
        $success_message = 'Chatbot content updated successfully.';
    }
}

$settings = chatbot_get_settings($conn);
$options = chatbot_get_options($conn, false);

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

        .option-card h3 {
            margin-top: 0;
            color: #4b49ac;
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
    </style>
</head>
<body>
    <header>
        <h1>Chatbot Management</h1>
    </header>

    <div class="main-container">
        <?php include 'navigation.php'; ?>

        <div class="main">
            <?php if (!empty($success_message)): ?>
                <div class="status-message status-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>

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
                    <h2>Chatbot Options</h2>
                    <?php foreach ($options as $option): ?>
                        <div class="option-card">
                            <h3><?= htmlspecialchars($option['option_key']) ?></h3>
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
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="btn edit">Save Chatbot Settings</button>
                </div>
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
