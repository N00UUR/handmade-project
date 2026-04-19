<?php
session_start();

require 'db_connect.php';

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
        $stmt = $conn->prepare("SELECT user_id, password, role_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $hashed_password, $role_id);
            $stmt->fetch();
        
            if (password_verify($password, $hashed_password)) {
                $_SESSION["user_id"] = $id;
                $_SESSION["role_id"] = $role_id;
                $_SESSION["is_logged_in"] = true;  

                if ($role_id == 1) {
                    header("Location: index.php"); 
                }
                
                 else {
                    header("Location: seller.php"); 
                }
                exit();
            } else {
                $errors[] = "Invalid email or password.";
            }
        }
        else {
            $errors[] = "Invalid email or password.";
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
  <title>Login Page</title>
  <link rel="stylesheet" href="css/style.css">
  <script>
    function validateLoginForm() {
      const email = document.forms["loginForm"]["email"].value;
      const password = document.forms["loginForm"]["password"].value;
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      let errors = [];

      if (!email || !emailPattern.test(email)) {
        errors.push("Please enter a valid email address.");
      }

      if (!password) {
        errors.push("Please enter your password.");
      }

      if (errors.length > 0) {
        document.getElementById("errors").innerHTML = errors.map(error => `<p>${error}</p>`).join('');
        return false; 
      }

      return true; 
    }

  </script>
</head>
<body>
  <div class="wrapper">
    <form name="loginForm" action="login.php" method="POST" onsubmit="return validateLoginForm();">
      <h2>Login</h2>

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
      <div class="register">
        <p>Don't have an account? <a href="register.php">Register</a></p>
      </div>
    </form>
  </div>
</body>
</html>
