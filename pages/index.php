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

// ====== DATA UNTUK CHART ======
$query_chart = "SELECT DATE(tanggal) as tgl, 
                SUM(CASE WHEN jenis='pemasukan' THEN nominal ELSE -nominal END) AS perubahan
                FROM tb_transaksi 
                WHERE user_id = ?
                GROUP BY DATE(tanggal)
                ORDER BY DATE(tanggal)";
$stmt_chart = $db->prepare($query_chart);
$stmt_chart->bind_param("i", $user_id);
$stmt_chart->execute();
$result_chart = $stmt_chart->get_result();

// ====== HITUNG TOTAL KUMULATIF HARIAN ======
$dates = [];
$balances = [];
$total = 0;
while ($row = $result_chart->fetch_assoc()) {
    $total += $row['perubahan'];
    $dates[] = date('d M', strtotime($row['tgl']));
    $balances[] = $total;
}

// Tambahkan titik awal (Rp0)
if (!empty($dates) && !empty($balances)) {
    array_unshift($dates, 'Awal');
    array_unshift($balances, 0);
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
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@3.0.1"></script>
</head>

<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Hai, <?php echo htmlspecialchars($username); ?></h2>
            <a href="auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>

        <!-- CARD: TOTAL & TARGET TABUNGAN -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <h4><?php echo htmlspecialchars($target_keterangan); ?></h4>
                <h5 class="text-muted mb-2">(Target Celengan)</h5>
                <h2>Rp<?php echo number_format($total_tabungan, 0, ',', '.'); ?></h2>

                <?php if ($target_nominal > 0): ?>
                    <p class="mt-2 mb-1">
                        Target: Rp<?php echo number_format($target_nominal, 0, ',', '.'); ?>
                    </p>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-success" 
                             role="progressbar" 
                             style="width: <?php echo $persentase; ?>%;" 
                             aria-valuenow="<?php echo $persentase; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <?php echo round($persentase, 1); ?>%
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted mt-2">Belum ada target ditetapkan.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- FORM: TAMBAH / UBAH TARGET -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Atur Target & Nama Celengan</h5>
                <form action="api/target/save-target.php" method="POST" class="row g-2 mb-2">
                    <div class="col-md-4">
                        <input type="number" name="target_nominal" class="form-control" placeholder="Nominal Target" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="keterangan" class="form-control" placeholder="Nama atau Tujuan Celengan">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success w-100">Simpan Target</button>
                    </div>
                </form>

                <?php if ($target_id): ?>
                <form action="api/target/update-nama.php" method="POST" class="row g-2">
                    <input type="hidden" name="target_id" value="<?php echo $target_id; ?>">
                    <div class="col-md-10">
                        <input type="text" name="nama_baru" class="form-control" placeholder="Ubah Nama Celengan" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-warning w-100">Ubah Nama</button>
                    </div>
                </form>
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

        <!-- CHART: PROGRESS TABUNGAN -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3 text-center">Progress Celengan</h5>
                <canvas id="progressChart" height="120"></canvas>
            </div>
        </div>

        <!-- TABEL: RIWAYAT TRANSAKSI -->
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

    <!-- SCRIPT: CHART.JS -->
    <script>
        const ctx = document.getElementById('progressChart').getContext('2d');
        const progressChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Total Tabungan (Rp)',
                    data: <?php echo json_encode($balances); ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
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
                    annotation: {
                        annotations: {
                            targetLine: {
                                type: 'line',
                                yMin: <?php echo $target_nominal; ?>,
                                yMax: <?php echo $target_nominal; ?>,
                                borderColor: 'rgba(255, 99, 132, 0.8)',
                                borderWidth: 2,
                                label: {
                                    enabled: <?php echo ($target_nominal > 0) ? 'true' : 'false'; ?>,
                                    content: 'Target: Rp<?php echo number_format($target_nominal, 0, ',', '.'); ?>',
                                    position: 'start'
                                }
                            }
                        }
                    },
                    legend: { display: false }
                }
            }
        });
    </script>

</body>
</html>
