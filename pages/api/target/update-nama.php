<?php
session_start();
require '../../db/db.php';

if (!isset($_SESSION['login'])) {
    header('location: ../../auth/login.php');
    exit;
}

$target_id = $_POST['target_id'];
$nama_baru = trim($_POST['nama_baru']);

if ($target_id && $nama_baru !== '') {
    $query = "UPDATE tb_target SET keterangan = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("si", $nama_baru, $target_id);
    $stmt->execute();
}

header('location: ../../index.php');
exit;
