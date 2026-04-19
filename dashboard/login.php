<?php
session_start();


define("ADMIN_EMAIL", "admin@gmail.com");
define("ADMIN_PASSWORD", "12345678");

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    if (empty($password)) {
        $errors[] = "Please enter your password.";
    }

    if (empty($errors)) {
        if ($email == ADMIN_EMAIL && $password == ADMIN_PASSWORD) {
            $_SESSION["is_admin_logged_in"] = true;
            header("Location: index.php"); 
            exit();
        } else {
            $errors[] = "Invalid admin email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<style>
body::before {
  content: "";
  position: absolute;
  width: 100%;
  height: 100%;
  background: #000 url("img/background.jpg") no-repeat center center;
  background-size: cover
}

</style>
  <div class="wrapper">
    <form action="login.php" method="POST">
      <h2>Admin Login</h2>

      <div id="errors" class="errors">
        <?php if (!empty($errors)): ?>
          <?php foreach ($errors as $error): ?>
            <p><?php echo $error; ?></p>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="input-field">
        <input type="text" name="email" required>
        <label>Enter your email</label>
      </div>
      <div class="input-field">
        <input type="password" name="password" required>
        <label>Enter your password</label>
      </div>
   
      <button type="submit">Log In</button>
    </form>
  </div>
</body>
</html>
