<?php
// File: index.php (Final Stabil)
require_once 'config/db.php';

// --- LOGIKA PENGISIAN OTOMATIS (Lazy Cron) ---
try {
    $stmt_first_date = $conn->query("SELECT MIN(tanggal) as first_date FROM log_kegiatan");
    $first_log_date_str = $stmt_first_date->fetchColumn();

    if ($first_log_date_str) {
        $start_date = new DateTime($first_log_date_str);
        $yesterday = new DateTime('yesterday');

        if ($start_date <= $yesterday) {
            $date_range = new DatePeriod($start_date, new DateInterval('P1D'), $yesterday->modify('+1 day'));
            
            $stmt_master = $conn->query("SELECT kegiatan_id FROM kegiatan_master");
            $kegiatan_master_ids = $stmt_master->fetchAll(PDO::FETCH_COLUMN);
            $total_kegiatan_master = count($kegiatan_master_ids);

            if ($total_kegiatan_master > 0) {
                $stmt_check = $conn->prepare("SELECT kegiatan_id FROM log_kegiatan WHERE tanggal = ?");
                $stmt_fill = $conn->prepare("INSERT INTO log_kegiatan (kegiatan_id, tanggal, status) VALUES (?, ?, 'Lewat') ON DUPLICATE KEY UPDATE status=status");

                foreach ($date_range as $date) {
                    $date_str = $date->format('Y-m-d');
                    
                    $stmt_check->execute([$date_str]);
                    $logged_ids = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
                    $log_count = count($logged_ids);
                    
                    if ($log_count > 0 && $log_count < $total_kegiatan_master) {
                        $unfilled_ids = array_diff($kegiatan_master_ids, $logged_ids);
                        foreach ($unfilled_ids as $kegiatan_id) {
                            $stmt_fill->execute([$kegiatan_id, $date_str]);
                        }
                    } 
                    else if ($log_count == 0) {
                        foreach ($kegiatan_master_ids as $kegiatan_id) {
                            $stmt_fill->execute([$kegiatan_id, $date_str]);
                        }
                    }
                }
            }
        }
    }
} catch (PDOException $e) { /* Abaikan */ }
// --- AKHIR LOGIKA PENGISIAN OTOMATIS ---


$today = date('Y-m-d');
$success_message = '';
$error_message = '';

// Handle form submission "Simpan Status"
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_status'])) {
    if (isset($_POST['status'])) {
        $statuses = $_POST['status'];
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare(
                "INSERT INTO log_kegiatan (kegiatan_id, tanggal, status) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status)"
            );
            foreach ($statuses as $kegiatan_id => $status) {
                $stmt->execute([intval($kegiatan_id), $today, $status]);
            }
            $conn->commit();
            $success_message = "Status kegiatan berhasil disimpan!";
        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = "Terjadi kesalahan saat menyimpan data: " . $e->getMessage();
        }
    } else {
        $error_message = "Tidak ada status yang dipilih untuk disimpan.";
    }
}

// Ambil data kegiatan
$stmt_kegiatan_master = $conn->query("SELECT kegiatan_id, nama_kegiatan, waktu_standar FROM kegiatan_master ORDER BY urutan ASC");
$kegiatan_list_raw = $stmt_kegiatan_master->fetchAll(PDO::FETCH_ASSOC);
$kegiatan_list = [];
foreach ($kegiatan_list_raw as $row) {
    $kegiatan_list[$row['kegiatan_id']] = $row;
}

// Ambil log hari ini
$log_hari_ini = [];
$stmt_log = $conn->prepare("SELECT kegiatan_id, status FROM log_kegiatan WHERE tanggal = ?");
$stmt_log->execute([$today]);
$log_rows = $stmt_log->fetchAll(PDO::FETCH_ASSOC);
foreach ($log_rows as $row) {
    $log_hari_ini[$row['kegiatan_id']] = $row['status'];
}

$status_options = ['Belum', 'Selesai', 'Terlambat', 'Lewat', 'Libur'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Operasional Harian - <?php echo date('d F Y'); ?></title>
    <link rel="icon" href="logo.png" type="image/png" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .radio-group label { margin-right: 15px; cursor: pointer; transition: color 0.2s; }
        .radio-group input[type="radio"] { margin-right: 5px; }
        .radio-group input[type="radio"]:disabled + label { cursor: not-allowed; color: #aaa; }
    </style>
</head>
<body>
    <div class="mahasiswa-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-top-section">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar"><i class="fas fa-bars"></i></button>
                <div class="sidebar-header"><h3 class="logo-text">MONITORING</h3></div>
            </div>
            <nav class="sidebar-menu">
                <ul>
                    <li><a href="index.php" class="active"><i class="fas fa-tasks"></i> <span class="menu-text">Kegiatan Hari Ini</span></a></li>
                    <li><a href="laporan.php"><i class="fas fa-history"></i> <span class="menu-text">History Laporan</span></a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content" id="mainContent">
            <header class="header"><h1>Monitoring Kegiatan Operasional</h1></header>

            <?php if ($success_message): ?><div class="success-msg"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
            <?php if ($error_message): ?><div class="error-msg"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>

            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> Selamat Datang di Sistem Monitoring</h4>
                <p>Status kegiatan dapat diubah sepanjang hari. Jika Anda lupa menyimpan, sistem akan **menyimpan otomatis** saat Anda menutup halaman ini.</p>
                <p>Opsi **"Selesai"** akan dinonaktifkan **1 jam** setelah waktu standar kegiatan terlewat.</p>
            </div>

            <section class="content-card">
                <h2><i class="fas fa-calendar-check"></i> Daftar Kegiatan - <?php echo date('d F Y'); ?></h2>
                <form id="monitoringForm" action="index.php" method="POST">
                    <div style="overflow-x: auto;">
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">No.</th>
                                    <th>Nama Kegiatan</th>
                                    <th style="width: 150px;">Waktu Standar</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($kegiatan_list)): $nomor = 1; ?>
                                    <?php foreach ($kegiatan_list as $id => $kegiatan): ?>
                                        <?php
                                            $current_status = $log_hari_ini[$id] ?? null;
                                        ?>
                                        <tr>
                                            <td><?php echo $nomor++; ?></td>
                                            <td><?php echo htmlspecialchars($kegiatan['nama_kegiatan']); ?></td>
                                            <td><?php echo date('H:i', strtotime($kegiatan['waktu_standar'])); ?></td>
                                            <td>
                                                <div class="radio-group">
                                                    <?php foreach ($status_options as $status_option): ?>
                                                        <input type="radio"
                                                               id="status_<?php echo $id; ?>_<?php echo str_replace(' ', '_', $status_option); ?>"
                                                               name="status[<?php echo $id; ?>]"
                                                               value="<?php echo $status_option; ?>"
                                                               <?php if ($status_option == 'Selesai') echo 'data-waktu-standar="' . $kegiatan['waktu_standar'] . '"'; ?>
                                                               <?php if ($current_status !== null && $current_status == $status_option) echo 'checked'; ?>>
                                                        <label for="status_<?php echo $id; ?>_<?php echo str_replace(' ', '_', $status_option); ?>">
                                                            <?php echo $status_option; ?>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="text-align:center;">Belum ada data master kegiatan.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($kegiatan_list)): ?>
                    <div style="text-align: right; margin-top: 20px;">
                         <button type="submit" name="simpan_status" class="submit" style="width:auto; padding: 10px 20px;">
                             <i class="fas fa-save"></i> Simpan Status
                         </button>
                    </div>
                    <?php endif; ?>
                </form>
            </section>
        </main>
    </div>
    <script src="js/script.js"></script>
</body>
</html>