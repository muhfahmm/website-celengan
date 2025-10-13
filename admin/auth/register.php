<?php
session_start();
require '../../db/db.php';

if (!isset($_SESSION['usernamelogin'])) {
    header('login.php');
}

if (isset($_POST['register'])) {

    $username = htmlspecialchars($_POST['username']);
    $password1 = htmlspecialchars($_POST['password1']);
    $password2 = htmlspecialchars($_POST['password2']);

    // cek password
    if ($password1 !== $password2) {
        echo "Kedua password tidak sama";
    } else {
        // cek apakah username sudah dipakai
        $check = mysqli_query($db, "SELECT * FROM tb_user WHERE username = '$username'");
        if (mysqli_num_rows($check) > 0) {
            echo "Username sudah digunakan";
        } else {
            // enkripsi password
            $passwordHash = password_hash($password1, PASSWORD_DEFAULT);

            // masukkan ke database
            $sql = "INSERT INTO tb_user (username, password) VALUES ('$username', '$passwordHash')";
            $query = mysqli_query($db, $sql);

            if ($query) {
                echo "Registrasi berhasil";
                header("location: login.php");
                exit;
            } else {
                echo "Terjadi kesalahan: " . mysqli_error($db);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="" method="post">
        <input type="text" name="username" placeholder="username">
        <br>
        <input type="password" name="password1" placeholder="password 1">
        <br>
        <input type="password" name="password2" placeholder="password 2">
        <br>
        <button type="submit" name="register">register</button>
        <br>
        <a href="login.php">masuk</a>
    </form>
</body>
</html>