<?php
session_start();
require '../../db/db.php';

if (isset($_SESSION['login'])) {
    header('location: ../index.php');
    exit;
}

if (isset($_POST['login'])) {
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];

    $result = mysqli_query($db, "SELECT * FROM tb_user WHERE username = '$username' ");

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);

        if (password_verify($password, $row['password'])) {
            $_SESSION['login'] = true;
            $_SESSION['username'] = $row['username'];

            header('location" ../index.php');
            exit;
        } else {
            echo "password salah";
        }
    } else {
        echo "user tidak ditemukan";
    }
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
    <form action="" method="post">
        <input type="text" name="username" placeholder="username">
        <br>
        <input type="password" name="password" placeholder="password">
        <br>
        <button name="login" type="submit">login</button>
        <br>
        <a href="register.php">daftar</a>
    </form>
</body>
</html>