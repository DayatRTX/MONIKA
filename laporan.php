<?php
require_once 'config/db.php';

$tanggal_laporan = $_GET['tanggal'] ?? date('Y-m-d');
$laporan_data = [];
$is_data_otomatis = false;

$sql = "SELECT km.nama_kegiatan, a.nama_alat, km.waktu_standar, lk.status, lk.timestamp_update, lk.komentar
        FROM log_kegiatan lk
        JOIN kegiatan_master km ON lk.kegiatan_id = km.kegiatan_id
        LEFT JOIN alat a ON km.alat_id = a.alat_id
        WHERE lk.tanggal = ?
        ORDER BY km.urutan ASC";

$stmt = $conn->prepare($sql);
$stmt->execute([$tanggal_laporan]);
$laporan_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($laporan_data) && $tanggal_laporan < date('Y-m-d')) {
    $is_data_otomatis = true;
    $result_kegiatan_master = $conn->query(
        "SELECT km.nama_kegiatan, km.waktu_standar, a.nama_alat 
         FROM kegiatan_master km
         LEFT JOIN alat a ON km.alat_id = a.alat_id
         ORDER BY km.urutan ASC"
    );
    if ($result_kegiatan_master) {
        $rows = $result_kegiatan_master->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $laporan_data[] = [
                'nama_kegiatan' => $row['nama_kegiatan'],
                'nama_alat' => $row['nama_alat'],
                'waktu_standar' => $row['waktu_standar'],
                'status' => 'Lewat',
                'komentar' => null,
                'timestamp_update' => $tanggal_laporan . ' 23:59:59'
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kegiatan Operasional - EVADOS</title>
    <link rel="icon" href="logo.png" type="image/png" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-top-section">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar"><i class="fas fa-bars"></i></button>
                <div class="sidebar-header"><h3 class="logo-text">MONITORING</h3></div>
            </div>
            <nav class="sidebar-menu">
                <ul>
                    <li><a href="index.php"><i class="fas fa-tasks"></i> <span class="menu-text">Kegiatan Hari Ini</span></a></li>
                    <li><a href="laporan.php" class="active"><i class="fas fa-history"></i> <span class="menu-text">History Laporan</span></a></li>
                </ul>
            </nav>
        </aside>
        <main class="main-content" id="mainContent">
            <header class="header"><h1>Laporan Histori Kegiatan Operasional</h1></header>
            <section class="content-card">
                <h2><i class="fas fa-search"></i> Lihat Laporan Berdasarkan Tanggal</h2>
                <form action="laporan.php" method="GET" class="filter-form">
                    <div class="input-group">
                        <label for="tanggal">Pilih Tanggal Laporan</label>
                        <input type="date" name="tanggal" id="tanggal" value="<?php echo htmlspecialchars($tanggal_laporan); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <button type="submit" class="submit btn-filter-action"><i class="fas fa-eye"></i> Tampilkan Laporan</button>
                    <a href="laporan.php" class="btn-filter-action btn-reset-filter"><i class="fas fa-calendar-day"></i> Laporan Hari Ini</a>
                </form>
                <?php if($is_data_otomatis): ?>
                <div class="info-box warning">
                    <h4><i class="fas fa-exclamation-triangle"></i> Catatan</h4>
                    <p>Tidak ada data yang disimpan pada tanggal ini. Sistem secara otomatis menampilkan semua status sebagai <strong>"Lewat"</strong>.</p>
                </div>
                <?php endif; ?>
                <h2 class="mt-30"><i class="fas fa-file-alt"></i> Hasil Laporan untuk Tanggal: <?php echo date('d F Y', strtotime($tanggal_laporan)); ?></h2>
                <div style="overflow-x: auto;">
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th class="col-nomor">No.</th>
                                <th>Nama Kegiatan</th>
                                <th>Alat Terkait</th>
                                <th>Waktu Standar</th>
                                <th>Status</th>
                                <th>Komentar</th>
                                <th>Terakhir Diperbarui</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($laporan_data)): $nomor = 1; ?>
                                <?php foreach ($laporan_data as $laporan): ?>
                                     <?php
                                        $status_class = '';
                                        switch ($laporan['status']) {
                                            case 'Selesai': $status_class = 'status-selesai'; break;
                                            case 'Belum': $status_class = 'status-belum'; break;
                                            case 'Terlambat': $status_class = 'status-terlambat'; break;
                                            case 'Lewat': $status_class = 'status-tidak-dilaksanakan'; break;
                                            case 'Rusak': $status_class = 'status-tidak-dilaksanakan'; break;
                                            case 'Libur': $status_class = 'status-belum'; break;
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo $nomor++; ?></td>
                                        <td><?php echo htmlspecialchars($laporan['nama_kegiatan']); ?></td>
                                        <td><?php echo $laporan['nama_alat'] ? htmlspecialchars($laporan['nama_alat']) : '-'; ?></td>
                                        <td><?php echo date('H:i', strtotime($laporan['waktu_standar'])); ?></td>
                                        <td class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($laporan['status']); ?></td>
                                        <td><?php echo htmlspecialchars($laporan['komentar'] ?? '-'); ?></td>
                                        <td>
                                            <?php
                                                if ($is_data_otomatis) {
                                                    echo '(Data Otomatis)';
                                                } else {
                                                    echo date('d M Y, H:i:s', strtotime($laporan['timestamp_update']));
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center">Tidak ada data kegiatan untuk tanggal yang dipilih.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
    <script src="js/script.js"></script>
</body>
</html>