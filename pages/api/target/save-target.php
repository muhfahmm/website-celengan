<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header('location: ../../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$target_nominal = $_POST['target_nominal'];
$keterangan = $_POST['keterangan'] ?? null;

$query = "INSERT INTO tb_target (user_id, target_nominal, keterangan) VALUES (?, ?, ?)";
$stmt = $db->prepare($query);
$stmt->bind_param("ids", $user_id, $target_nominal, $keterangan);
$stmt->execute();

header('location: ../../index.php');
exit;
