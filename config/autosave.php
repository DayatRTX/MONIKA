<?php
// File: autosave.php
// File ini hanya untuk menerima data dari autosave, tidak untuk diakses langsung.
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // navigator.sendBeacon mengirim data sebagai blob, kita ambil dari input stream
    $postData = file_get_contents('php://input');
    parse_str($postData, $formData);

    if (isset($formData['status'])) {
        $statuses = $formData['status'];
        $today = date('Y-m-d');
        
        $conn->begin_transaction();
        try {
            foreach ($statuses as $kegiatan_id => $status) {
                $kegiatan_id = intval($kegiatan_id);

                $stmt = $conn->prepare(
                    "INSERT INTO log_kegiatan (kegiatan_id, tanggal, status) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE status = VALUES(status)"
                );
                if (!$stmt) throw new Exception($conn->error);

                $stmt->bind_param("iss", $kegiatan_id, $today, $status);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                $stmt->close();
            }
            $conn->commit();
            // Kirim respons HTTP 200 OK untuk menandakan sukses
            http_response_code(200);
        } catch (Exception $e) {
            $conn->rollback();
            // Kirim respons error jika gagal
            http_response_code(500);
            error_log("Autosave GAGAL: " . $e->getMessage()); // Log error di server
        }
    }
}
?>