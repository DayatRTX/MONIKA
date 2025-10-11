<?php
require_once 'config/db.php';

try {
    $stmt_alat_rusak = $conn->query(
        "SELECT km.alat_id, a.nama_alat, COUNT(lk.log_id) as rusak_count
         FROM log_kegiatan lk
         JOIN kegiatan_master km ON lk.kegiatan_id = km.kegiatan_id
         JOIN alat a ON km.alat_id = a.alat_id
         WHERE lk.status = 'Rusak'
         GROUP BY km.alat_id, a.nama_alat
         HAVING rusak_count >= 3"
    );

    $alat_rusak_list = $stmt_alat_rusak->fetchAll(PDO::FETCH_ASSOC);

    foreach ($alat_rusak_list as $alat) {
        $alat_id = $alat['alat_id'];
        $current_rusak_count = $alat['rusak_count'];
        $nama_alat = $alat['nama_alat'];

        $stmt_cek_aktif = $conn->prepare("SELECT notifikasi_id FROM notifikasi_alat_rusak WHERE alat_id = ? AND status_notifikasi = 'Aktif'");
        $stmt_cek_aktif->execute([$alat_id]);
        if ($stmt_cek_aktif->rowCount() > 0) {
            continue;
        }

        $stmt_get_last_notif = $conn->prepare("SELECT pesan FROM notifikasi_alat_rusak WHERE alat_id = ? ORDER BY notifikasi_id DESC LIMIT 1");
        $stmt_get_last_notif->execute([$alat_id]);
        $last_pesan = $stmt_get_last_notif->fetchColumn();

        $last_notif_rusak_count = 0;
        if ($last_pesan && preg_match('/sebanyak (\d+) kali/', $last_pesan, $matches)) {
            $last_notif_rusak_count = (int)$matches[1];
        }

        if ($current_rusak_count > $last_notif_rusak_count) {
            $pesan = "Peringatan: Alat '$nama_alat' telah dilaporkan rusak sebanyak $current_rusak_count kali. Mohon segera diperiksa dan konfirmasi statusnya.";
            $stmt_insert_notif = $conn->prepare("INSERT INTO notifikasi_alat_rusak (alat_id, pesan) VALUES (?, ?)");
            $stmt_insert_notif->execute([$alat_id, $pesan]);
        }
    }
} catch (PDOException $e) {
    // Abaikan error jika terjadi pada blok pengecekan notifikasi otomatis
}


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
                    } else if ($log_count == 0) {
                        foreach ($kegiatan_master_ids as $kegiatan_id) {
                            $stmt_fill->execute([$kegiatan_id, $date_str]);
                        }
                    }
                }
            }
        }
    }
} catch (PDOException $e) {}

$today = date('Y-m-d');
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_alat_status'])) {
    $alat_id = $_POST['alat_id'];
    $status_alat = $_POST['update_alat_status'];
    $notifikasi_id = $_POST['notifikasi_id'];
    try {
        $conn->beginTransaction();
        
        $stmt_alat = $conn->prepare("UPDATE alat SET status_alat = ? WHERE alat_id = ?");
        $stmt_alat->execute([$status_alat, $alat_id]);
        
        $stmt_notif = $conn->prepare("UPDATE notifikasi_alat_rusak SET status_notifikasi = 'Selesai' WHERE notifikasi_id = ?");
        $stmt_notif->execute([$notifikasi_id]);

        $conn->commit();
        header("Location: index.php?update=success");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: index.php?update=error");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_status'])) {
    if (isset($_POST['status'])) {
        $statuses = $_POST['status'];
        $komentars = $_POST['komentar'] ?? [];
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare(
                "INSERT INTO log_kegiatan (kegiatan_id, tanggal, status, komentar) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), komentar = VALUES(komentar)"
            );
            foreach ($statuses as $kegiatan_id => $status) {
                $komentar = $komentars[$kegiatan_id] ?? null;
                $stmt->execute([intval($kegiatan_id), $today, $status, $komentar]);
            }
            $conn->commit();
            header("Location: index.php?save=success");
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            header("Location: index.php?save=error");
            exit();
        }
    }
}

if(isset($_GET['save']) && $_GET['save'] == 'success') {
    $success_message = "Status kegiatan berhasil disimpan!";
}
if(isset($_GET['update']) && $_GET['update'] == 'success') {
    $success_message = "Status alat berhasil diperbarui!";
}
if(isset($_GET['save']) && $_GET['save'] == 'error' || isset($_GET['update']) && $_GET['update'] == 'error') {
    $error_message = "Terjadi kesalahan saat menyimpan data.";
}

$stmt_kegiatan_master = $conn->query(
    "SELECT km.kegiatan_id, km.nama_kegiatan, km.waktu_standar, km.alat_id, a.nama_alat, a.status_alat 
     FROM kegiatan_master km 
     LEFT JOIN alat a ON km.alat_id = a.alat_id 
     ORDER BY km.urutan ASC"
);
$kegiatan_list_raw = $stmt_kegiatan_master->fetchAll(PDO::FETCH_ASSOC);
$kegiatan_list = [];
foreach ($kegiatan_list_raw as $row) {
    $kegiatan_list[$row['kegiatan_id']] = $row;
}

$log_hari_ini = [];
$stmt_log = $conn->prepare("SELECT kegiatan_id, status, komentar FROM log_kegiatan WHERE tanggal = ?");
$stmt_log->execute([$today]);
$log_rows = $stmt_log->fetchAll(PDO::FETCH_ASSOC);
foreach ($log_rows as $row) {
    $log_hari_ini[$row['kegiatan_id']] = ['status' => $row['status'], 'komentar' => $row['komentar']];
}

$status_options = ['Belum', 'Selesai', 'Terlambat', 'Lewat', 'Libur', 'Lainnya'];
$status_options_with_alat = ['Belum', 'Selesai', 'Terlambat', 'Lewat', 'Libur', 'Rusak', 'Lainnya'];
$stmt_notifikasi = $conn->query("SELECT n.notifikasi_id, n.alat_id, n.pesan, a.nama_alat FROM notifikasi_alat_rusak n JOIN alat a ON n.alat_id = a.alat_id WHERE n.status_notifikasi = 'Aktif'");
$notifikasi_list = $stmt_notifikasi->fetchAll(PDO::FETCH_ASSOC);
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
</head>
<body>
    
    <?php if (!empty($notifikasi_list)): ?>
    <div class="modal-overlay">
        <div class="modal-content">
            <h4><i class="fas fa-exclamation-triangle"></i> Peringatan Kerusakan Alat</h4>
            <p><?php echo htmlspecialchars($notifikasi_list[0]['pesan']); ?></p>
            <div class="actions">
                <form action="index.php" method="POST">
                    <input type="hidden" name="alat_id" value="<?php echo $notifikasi_list[0]['alat_id']; ?>">
                    <input type="hidden" name="notifikasi_id" value="<?php echo $notifikasi_list[0]['notifikasi_id']; ?>">
                    <button type="submit" name="update_alat_status" value="Rusak Total" class="btn-rusak-total">Alat Rusak Total</button>
                    <button type="submit" name="update_alat_status" value="Perbaikan" class="btn-perbaikan">Alat Belum Diperbaiki</button>
                    <button type="submit" name="update_alat_status" value="Baik" class="btn-diperbaiki">Alat Sudah Diperbaiki</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-top-section">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar"><i class="fas fa-bars"></i></button>
                <div class="sidebar-header"><h3 class="logo-text">MONPERA</h3></div>
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
                <p>Anda dapat menambahkan catatan atau komentar untuk setiap kegiatan, terlepas dari status yang dipilih.</p>
                <p>Perubahan status dan catatan akan <strong>tersimpan otomatis</strong>.</p>
            </div>
            <section class="content-card">
                <h2><i class="fas fa-calendar-check"></i> Daftar Kegiatan - <?php echo date('d F Y'); ?></h2>
                <form id="monitoringForm" action="index.php" method="POST">
                    <div style="overflow-x: auto;">
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th class="col-nomor">No.</th>
                                    <th>Nama Kegiatan</th>
                                    <th>Alat Terkait</th>
                                    <th class="col-waktu">Waktu Standar</th>
                                    <th class="col-status">Status & Komentar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($kegiatan_list)): $nomor = 1; ?>
                                    <?php foreach ($kegiatan_list as $id => $kegiatan): ?>
                                        <?php
                                            $current_status = $log_hari_ini[$id]['status'] ?? null;
                                            
                                            if ($kegiatan['status_alat'] == 'Rusak Total' && $current_status != 'Rusak') {
                                                try {
                                                    $stmt_fix = $conn->prepare(
                                                        "INSERT INTO log_kegiatan (kegiatan_id, tanggal, status) VALUES (?, ?, 'Rusak')
                                                         ON DUPLICATE KEY UPDATE status = 'Rusak'"
                                                    );
                                                    $stmt_fix->execute([$id, $today]);
                                                    $current_status = 'Rusak';
                                                } catch (Exception $e) {
                                                }
                                            }

                                            $current_komentar = $log_hari_ini[$id]['komentar'] ?? '';
                                            $options = $kegiatan['alat_id'] ? $status_options_with_alat : $status_options;
                                            
                                            $row_class = '';
                                            if (!empty($kegiatan['status_alat'])) {
                                                $status_class = str_replace(' ', '-', strtolower($kegiatan['status_alat']));
                                                $row_class = 'alat-' . $status_class;
                                            }
                                        ?>
                                        <tr class="<?php echo $row_class; ?>" data-alat-id="<?php echo $kegiatan['alat_id']; ?>">
                                            <td><?php echo $nomor++; ?></td>
                                            <td><?php echo htmlspecialchars($kegiatan['nama_kegiatan']); ?></td>
                                            <td><?php echo $kegiatan['nama_alat'] ? htmlspecialchars($kegiatan['nama_alat']) : '-'; ?></td>
                                            <td><?php echo date('H:i', strtotime($kegiatan['waktu_standar'])); ?></td>
                                            <td>
                                                <div class="radio-group">
                                                    <?php foreach ($options as $status_option): ?>
                                                        <input type="radio"
                                                               id="status_<?php echo $id; ?>_<?php echo str_replace(' ', '_', $status_option); ?>"
                                                               name="status[<?php echo $id; ?>]"
                                                               value="<?php echo $status_option; ?>"
                                                               class="status-radio"
                                                               data-kegiatan-id="<?php echo $id; ?>"
                                                               <?php if ($status_option == 'Selesai') echo 'data-waktu-standar="' . $kegiatan['waktu_standar'] . '"'; ?>
                                                               <?php if ($current_status == $status_option) echo 'checked'; ?>>
                                                        <label for="status_<?php echo $id; ?>_<?php echo str_replace(' ', '_', $status_option); ?>">
                                                            <?php echo $status_option; ?>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                                <textarea name="komentar[<?php echo $id; ?>]" 
                                                          id="komentar_<?php echo $id; ?>"
                                                          class="komentar-input"
                                                          data-kegiatan-id="<?php echo $id; ?>"
                                                          placeholder="Tambahkan catatan..."><?php echo htmlspecialchars($current_komentar); ?></textarea>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center">Belum ada data master kegiatan.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($kegiatan_list)): ?>
                    <div class="form-submit-button">
                         <button type="submit" name="simpan_status" class="submit submit-button-auto">
                             <i class="fas fa-save"></i> Simpan Semua Perubahan
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