<?php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $postData = file_get_contents('php://input');
    parse_str($postData, $formData);

    if (isset($formData['status'])) {
        $statuses = $formData['status'];
        $today = date('Y-m-d');

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
            http_response_code(200);

        } catch (Exception $e) {
            $conn->rollBack();
            http_response_code(500);
            error_log("Autosave GAGAL: " . $e->getMessage());
        }
    } else {
        http_response_code(400); 
    }
} else {
    http_response_code(405);
}
?>