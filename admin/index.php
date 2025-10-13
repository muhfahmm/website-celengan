<?php
session_start();
require '../db/db.php';

if (!isset($_SESSION['login'])) {
    header('location: auth/login.php');
}
$admin = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <a href=""></a>
</body>
</html>