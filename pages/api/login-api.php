<?php
session_start();
require '../../db/db.php'; // koneksi database

if (isset($_POST['login'])) {
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];

    $result = mysqli_query($db, "SELECT * FROM tb_user WHERE username = '$username' ");

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);

        if (password_verify($password, $row['password'])) {
            $_SESSION['login'] = true;
            $_SESSION['username'] = $row['username'];
            $_SESSION['user_id'] = $row['id']; // opsional jika ingin pakai ID user

            header('Location: ../index.php');
            exit;
        } else {
            echo "Password salah";
        }
    } else {
        echo "User tidak ditemukan";
    }
} else {
    header('Location: ../auth/login.php');
    exit;
}
?>