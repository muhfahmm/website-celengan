<?php
session_start();
require '../db/db.php';

if (!isset($_SESSION['login'])) {
    header('location: auth/login.php');
    exit;
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id']; // pastikan saat login disimpan user_id
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Celengan Digital</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Hai, <?php echo htmlspecialchars($username); ?></h2>
            <a href="auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>

        <?php
        // Hitung total tabungan
        $query = "SELECT 
                SUM(CASE WHEN jenis='pemasukan' THEN nominal ELSE 0 END) -
                SUM(CASE WHEN jenis='pengeluaran' THEN nominal ELSE 0 END) AS total 
              FROM tb_transaksi 
              WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $total_tabungan = $result['total'] ?? 0;
        ?>

        <div class="card bg-secondary mb-4">
            <div class="card-body text-center">
                <h4>Total Tabungan</h4>
                <h2>Rp<?php echo number_format($total_tabungan, 0, ',', '.'); ?></h2>
            </div>
        </div>

        <!-- Form Tambah Transaksi -->
        <div class="card bg-secondary mb-4">
            <div class="card-body">
                <form action="api/transactions/add-tabungan.php" method="POST" class="row g-2">
                    <div class="col-md-3">
                        <select name="jenis" class="form-select" required>
                            <option value="pemasukan">Pemasukan</option>
                            <option value="pengeluaran">Pengeluaran</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="nominal" class="form-control" placeholder="Nominal" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="keterangan" class="form-control" placeholder="Keterangan">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Daftar Transaksi -->
        <div class="card bg-secondary">
            <div class="card-body">
                <h5 class="mb-3">Riwayat Transaksi</h5>
                <table class="table table-dark table-striped table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Nominal</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM tb_transaksi WHERE user_id = ? ORDER BY tanggal DESC";
                        $stmt = $db->prepare($query);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                        ?>
                            <tr>
                                <td><?php echo date('d M Y H:i', strtotime($row['tanggal'])); ?></td>
                                <td><?php echo ucfirst($row['jenis']); ?></td>
                                <td>Rp<?php echo number_format($row['nominal'], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                <td>
                                    <form action="api/transactions/hapus-tabungan.php" method="POST" style="display:inline">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>

</html>