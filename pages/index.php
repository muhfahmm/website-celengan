<?php
session_start();
require '../db/db.php';

if (!isset($_SESSION['login'])) {
    header('location: auth/login.php');
    exit;
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// ====== TOTAL TABUNGAN ======
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

// ====== TARGET TABUNGAN ======
$query_target = "SELECT * FROM tb_target WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt_target = $db->prepare($query_target);
$stmt_target->bind_param("i", $user_id);
$stmt_target->execute();
$result_target = $stmt_target->get_result()->fetch_assoc();

$target_id = $result_target['id'] ?? null;
$target_nominal = $result_target['target_nominal'] ?? 0;
$target_keterangan = $result_target['keterangan'] ?? 'Celengan Tanpa Nama';
$persentase = ($target_nominal > 0) ? min(($total_tabungan / $target_nominal) * 100, 100) : 0;

// ====== DATA UNTUK GRAFIK BATANG (per tanggal) ======
$query_chart = "SELECT DATE(tanggal) as tgl,
                SUM(CASE WHEN jenis='pemasukan' THEN nominal ELSE 0 END) AS total_pemasukan,
                SUM(CASE WHEN jenis='pengeluaran' THEN nominal ELSE 0 END) AS total_pengeluaran
                FROM tb_transaksi 
                WHERE user_id = ?
                GROUP BY DATE(tanggal)
                ORDER BY DATE(tanggal)";
$stmt_chart = $db->prepare($query_chart);
$stmt_chart->bind_param("i", $user_id);
$stmt_chart->execute();
$result_chart = $stmt_chart->get_result();

$dates = [];
$pemasukan = [];
$pengeluaran = [];

while ($row = $result_chart->fetch_assoc()) {
    $dates[] = date('d M', strtotime($row['tgl']));
    $pemasukan[] = (int)$row['total_pemasukan'];
    $pengeluaran[] = (int)$row['total_pengeluaran'];
}

// ====== DATA UNTUK DIAGRAM TRANSAKSI (per aksi user) ======
$query_tx = "SELECT tanggal, jenis, nominal FROM tb_transaksi WHERE user_id = ? ORDER BY tanggal ASC";
$stmt_tx = $db->prepare($query_tx);
$stmt_tx->bind_param("i", $user_id);
$stmt_tx->execute();
$result_tx = $stmt_tx->get_result();

$tx_labels = [];
$tx_values = [];
$tx_colors = [];
$no = 1;

while ($row = $result_tx->fetch_assoc()) {
    $tx_labels[] = 'Tx ' . $no++;
    $tx_values[] = (int)$row['nominal'] * ($row['jenis'] == 'pengeluaran' ? -1 : 1);
    $tx_colors[] = ($row['jenis'] == 'pemasukan') ? 'rgba(75, 192, 75, 0.8)' : 'rgba(255, 99, 132, 0.8)';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Celengan Digital</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Hai, <?php echo htmlspecialchars($username); ?></h2>
            <a href="auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>

        <!-- CARD: TOTAL & TARGET -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <h4><?php echo htmlspecialchars($target_keterangan); ?></h4>
                <h5 class="text-muted mb-2">(Target Celengan)</h5>
                <h2>Rp<?php echo number_format($total_tabungan, 0, ',', '.'); ?></h2>

                <?php if ($target_nominal > 0): ?>
                    <p class="mt-2 mb-1">Target: Rp<?php echo number_format($target_nominal, 0, ',', '.'); ?></p>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $persentase; ?>%;">
                            <?php echo round($persentase, 1); ?>%
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted mt-2">Belum ada target ditetapkan.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- FORM: TAMBAH TRANSAKSI -->
        <div class="card mb-4">
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

        <!-- GRAFIK BATANG PER TANGGAL -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3 text-center">Grafik Harian</h5>
                <canvas id="barChart" height="120"></canvas>
            </div>
        </div>

        <!-- GRAFIK TRANSAKSI PER AKSI -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3 text-center">Grafik Transaksi</h5>
                <canvas id="transactionChart" height="120"></canvas>
            </div>
        </div>

        <!-- TABEL TRANSAKSI -->
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Riwayat Transaksi</h5>
                <table class="table table-striped table-sm align-middle">
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
                                    <form action="api/transactions/delete-tabungan.php" method="POST" style="display:inline">
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

    <script>
        const ctx1 = document.getElementById('barChart').getContext('2d');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                        label: 'Pemasukan',
                        data: <?php echo json_encode($pemasukan); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)'
                    },
                    {
                        label: 'Pengeluaran',
                        data: <?php echo json_encode($pengeluaran); ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.7)'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });

        // GRAFIK TRANSAKSI PER AKSI USER
        const ctx2 = document.getElementById('transactionChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($tx_labels); ?>,
                datasets: [{
                    label: 'Nominal Transaksi',
                    data: <?php echo json_encode($tx_values); ?>,
                    backgroundColor: <?php echo json_encode($tx_colors); ?>
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                return 'Rp' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>

</html>