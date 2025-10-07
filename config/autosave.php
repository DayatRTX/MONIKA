<?php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $postData = file_get_contents('php://input');
    parse_str($postData, $formData);
    $today = date('Y-m-d');

    try {
        $conn->beginTransaction();

        if (isset($formData['status'])) {
            $statuses = $formData['status'];
            $stmt = $conn->prepare(
                "INSERT INTO log_kegiatan (kegiatan_id, tanggal, status) VALUES (:id, :tanggal, :status)
                 ON DUPLICATE KEY UPDATE status = :status"
            );
            foreach ($statuses as $kegiatan_id => $status) {
                $stmt->execute(['id' => $kegiatan_id, 'tanggal' => $today, 'status' => $status]);
            }
        } elseif (isset($formData['komentar'])) {
            $komentars = $formData['komentar'];
            $stmt = $conn->prepare(
                "INSERT INTO log_kegiatan (kegiatan_id, tanggal, status, komentar) VALUES (:id, :tanggal, 'Belum', :komentar)
                 ON DUPLICATE KEY UPDATE komentar = :komentar"
            );
            foreach ($komentars as $kegiatan_id => $komentar) {
                $stmt->execute(['id' => $kegiatan_id, 'tanggal' => $today, 'komentar' => $komentar]);
            }
        } else {
            http_response_code(400); 
            $conn->rollBack();
            exit;
        }

        $conn->commit();
        http_response_code(200);

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        http_response_code(500);
        error_log("Autosave GAGAL: " . $e->getMessage());
    }
} else {
    http_response_code(405);
}
?>