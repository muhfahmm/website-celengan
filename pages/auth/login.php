<?php
session_start();
if (isset($_SESSION['login'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <form action="../api/login-api.php" method="post">
        <input type="text" name="username" placeholder="username" required>
        <br>
        <input type="password" name="password" placeholder="password" required>
        <br>
        <button name="login" type="submit">Login</button>
        <br>
        <a href="register.php">Daftar</a>
    </form>
</body>
</html>