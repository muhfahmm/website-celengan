<?php
session_start();
if (isset($_SESSION['usernamelogin'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
    <form action="../api/register-api.php" method="post">
        <input type="text" name="username" placeholder="username" required>
        <br>
        <input type="password" name="password1" placeholder="password 1" required>
        <br>
        <input type="password" name="password2" placeholder="password 2" required>
        <br>
        <button type="submit" name="register">Register</button>
        <br>
        <a href="login.php">Masuk</a>
    </form>
</body>
</html>
