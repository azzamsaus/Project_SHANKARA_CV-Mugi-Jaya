<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/database.php';

$pengiriman_id = $_POST['pengiriman_id'] ?? null;
$status = $_POST['status'] ?? null;
$last_status_update = $_POST['last_status_update'] ?? date('Y-m-d H:i:s');

if($status && $pengiriman_id) {
    $sql = "UPDATE pengiriman SET status = '$status', last_status_update = '$last_status_update' WHERE id = $pengiriman_id";
    
    if(query($sql)) {
        $pengiriman = fetchOne(query("SELECT * FROM pengiriman WHERE id = $pengiriman_id"));
        
        // Kirim notifikasi
        $judul = '✅ Pengiriman Divalidasi';
        $pesan = "Pengiriman {$pengiriman['no_pengiriman']} telah divalidasi oleh driver.";
        
        query("INSERT INTO notifikasi (user_id, judul, pesan, link) 
               SELECT id, '$judul', '$pesan', '/shankara_trackingbarang/pengiriman/index.php' 
               FROM users WHERE role IN ('admin', 'owner')");
        
        // Simpan tracking awal
        query("INSERT INTO tracking_lokasi (pengiriman_id, latitude, longitude, lokasi_text, waktu) 
               VALUES ($pengiriman_id, -6.2, 106.8, 'Driver memulai perjalanan', NOW())");
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
}
?>