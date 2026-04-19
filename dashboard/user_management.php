<?php
require '../db_connect.php';
session_start();
if (!isset($_SESSION["is_admin_logged_in"]) || $_SESSION["is_admin_logged_in"] !== true) {
  header("Location: ../login.php");
  exit();
}

if (isset($_POST['delete'])) {
    $user_id = $_POST['user_id'];
    $delete_query = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    header("Location: user_management.php"); 
    exit;
}

$users_query = "
    SELECT u.user_id, u.username, u.email, r.role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.role_id
";
$users_result = $conn->query($users_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>User Management</h1>
    </header>
    <div class="main-container">
    <?php include 'navigation.php'; ?>

    <div class="main">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users_result->num_rows > 0): ?>
                    <?php while ($user = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $user['user_id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['role_name']) ?></td>
                            <td>
                                <a href="add_edit_user.php?user_id=<?= $user['user_id'] ?>" class="btn edit">Update</a>
                                <form action="user_management.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                    <button type="submit" name="delete" class="btn delete" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    </div>
</body>
</html>
