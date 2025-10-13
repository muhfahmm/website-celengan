<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header('location: ../../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$jenis = $_POST['jenis'];
$nominal = $_POST['nominal'];
$keterangan = $_POST['keterangan'] ?? '';

if ($nominal > 0) {
    $query = "INSERT INTO tb_transaksi (user_id, jenis, nominal, keterangan) VALUES (?,?,?,?)";
    $stmt = $db->prepare($query);
    $stmt->bind_param("isds", $user_id, $jenis, $nominal, $keterangan);
    $stmt->execute();
}

header('location: ../../index.php');
