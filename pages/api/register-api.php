<?php
session_start();
require '../../db/db.php';

if (isset($_POST['register'])) {
    $username = htmlspecialchars($_POST['username']);
    $password1 = htmlspecialchars($_POST['password1']);
    $password2 = htmlspecialchars($_POST['password2']);

    if ($password1 !== $password2) {
        echo "Kedua password tidak sama";
        exit;
    }

    $check = mysqli_query($db, "SELECT * FROM tb_user WHERE username = '$username'");
    if (mysqli_num_rows($check) > 0) {
        echo "Username sudah digunakan";
        exit;
    }

    $passwordHash = password_hash($password1, PASSWORD_DEFAULT);
    $sql = "INSERT INTO tb_user (username, password) VALUES ('$username', '$passwordHash')";
    $query = mysqli_query($db, $sql);

    if ($query) {
        header("Location: ../auth/login.php");
        exit;
    } else {
        echo "Terjadi kesalahan: " . mysqli_error($db);
    }
} else {
    header("Location: ../auth/register.php");
    exit;
}
?>
