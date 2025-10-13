<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header('location: ../../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$id = $_POST['id'];

$query = "DELETE FROM tb_transaksi WHERE id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();

header('location: ../../index.php');
