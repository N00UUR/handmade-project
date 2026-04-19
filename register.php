<?php
require 'db_connect.php';
$username = $email = $password = $confirm_password = $role = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = $_POST["username"];
  $email = $_POST["email"];
  $password = $_POST["password"];
  $confirm_password = $_POST["confirm_password"];
  $role = $_POST["role"]; 

  if (empty($username)) {
    $errors[] = "Username is required";
  }
  if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required";
  }
  if (empty($password) || strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters";
  }
  if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match";
  }
  if (empty($role)) {
    $errors[] = "Role selection is required";
  }

  if (empty($errors)) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      $errors[] = "Username or email already exists";
    }

    $stmt->close();
  }

  if (empty($errors)) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $role_id = ($role === 'Customer') ? 1 : 2;

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $username, $email, $hashed_password, $role_id);

    if ($stmt->execute()) {
      header('Location: login.php');
    } else {
      $errors[] = "Error: " . $stmt->error;
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
  <title>Register Page</title>
  <link rel="stylesheet" href="css/style.css">
  <script>
    function validateForm() {
      const username = document.forms["registerForm"]["username"].value;
      const email = document.forms["registerForm"]["email"].value;
      const password = document.forms["registerForm"]["password"].value;
      const confirmPassword = document.forms["registerForm"]["confirm_password"].value;
      const role = document.forms["registerForm"]["role"].value;
      let errors = [];

      if (username.trim() === "") {
        errors.push("Username is required");
      }

      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailPattern.test(email)) {
        errors.push("Please enter a valid email address");
      }

      if (password.length < 6) {
        errors.push("Password must be at least 6 characters long");
      }

      if (password !== confirmPassword) {
        errors.push("Passwords do not match");
      }

      if (role === "") {
        errors.push("Please select a role");
      }

      if (errors.length > 0) {
        const errorDiv = document.getElementById("errors");
        errorDiv.innerHTML = errors.map(error => `<p>${error}</p>`).join('');
        return false; 
      }

      return true; 
    }
  </script>
</head>

<body>
  <div class="wrapper">
    <form name="registerForm" action="register.php" method="POST" onsubmit="return validateForm();">
      <h2>Register</h2>

      <div id="errors" class="errors">
        <?php if (!empty($errors)): ?>
          <?php foreach ($errors as $error): ?>
            <p><?php echo $error; ?></p>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="input-field">
        <input type="text" name="username" required>
        <label>Enter your username</label>
      </div>
      <div class="input-field">
        <input type="email" name="email" required>
        <label>Enter your email</label>
      </div>
      <div class="input-field">
        <input type="password" name="password" required>
        <label>Create a password</label>
      </div>
      <div class="input-field">
        <input type="password" name="confirm_password" required>
        <label>Confirm your password</label>
      </div>
      <div class="input-field">
        <select class="select-option" name="role" required>
          <option value="" disabled selected>Select your role</option>
          <option value="Customer">Customer</option>
          <option value="Seller">Seller</option>
        </select>
      </div>
      <button type="submit">Register</button>
      <div class="register">
        <p>Already have an account? <a href="login.php">Login</a></p>
      </div>
    </form>
  </div>
  <?php include 'chatbot_widget.php'; ?>
</body>

</html>
