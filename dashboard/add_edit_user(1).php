<?php
require '../db_connect.php';


$user_id = $username = $email = $role = "";
$errors = [];
$is_edit = isset($_GET['user_id']);

if ($is_edit) {
    $user_id = $_GET['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $username = $user['username'];
        $email = $user['email'];
        $role = $user['role_id'] == 1 ? "Customer" : "Seller";
    } else {
        $errors[] = "User not found.";
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];
    $role = $_POST["role"];
    $password = $_POST["password"] ?? null;

    if (empty($username)) {
        $errors[] = "Username is required";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    if (!$is_edit && (empty($password) || strlen($password) < 6)) {
        $errors[] = "Password must be at least 6 characters";
    }
    if (empty($role)) {
        $errors[] = "Role selection is required";
    }

    if (empty($errors)) {
        $role_id = ($role === 'Customer') ? 1 : 2;
        $query = "SELECT user_id FROM users WHERE (username = ? OR email = ?)";
        if ($is_edit) {
            $query .= " AND user_id != ?";
        }

        $stmt = $conn->prepare($query);

        if ($is_edit) {
            $stmt->bind_param("ssi", $username, $email, $user_id);
        } else {
            $stmt->bind_param("ss", $username, $email);
        }

        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Username or email already exists.";
        } else {
            if ($is_edit) {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, role_id = ? WHERE user_id = ?");
                    $stmt->bind_param("sssii", $username, $email, $hashed_password, $role_id, $user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role_id = ? WHERE user_id = ?");
                    $stmt->bind_param("ssii", $username, $email, $role_id, $user_id);
                }
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $username, $email, $hashed_password, $role_id);
            }

            if ($stmt->execute()) {
                header('Location: user_management.php'); 
            } else {
                $errors[] = "Error: " . $stmt->error;
            }
        }

        $stmt->close();
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit User' : 'Add User' ?></title>
    <link rel="stylesheet" href="../css/style.css">
    
</head>
<body>
<div class="wrapper">
    <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) . ($is_edit ? "?user_id=$user_id" : '') ?>" method="POST">
        <h2><?= $is_edit ? 'Edit User' : 'Add User' ?></h2>

        <div class="errors">
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="input-field">
            <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required>
            <label>Enter your username</label>
        </div>
        <div class="input-field">
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            <label>Enter your email</label>
        </div>
        <?php if (!$is_edit || !empty($password)): ?>
            <div class="input-field">
                <input type="password" name="password" <?= $is_edit ? '' : 'required' ?>>
                <label><?= $is_edit ? 'Update password (optional)' : 'Create a password' ?></label>
            </div>
        <?php endif; ?>
        <div class="input-field">
            <select class="select-option" name="role" required>
                <option value="" disabled <?= empty($role) ? 'selected' : '' ?>>Select your role</option>
                <option value="Customer" <?= $role === 'Customer' ? 'selected' : '' ?>>Customer</option>
                <option value="Seller" <?= $role === 'Seller' ? 'selected' : '' ?>>Seller</option>
            </select>
        </div>
        <button type="submit"><?= $is_edit ? 'Update' : 'Add' ?> User</button>
        <div class="back">
            <p><a href="user_management.php">Back to User List</a></p>
        </div>
    </form>
</div>
</body>
</html>
