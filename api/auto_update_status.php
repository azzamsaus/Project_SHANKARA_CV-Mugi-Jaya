<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');

// 1. SET TIMEZONE KE WIB AGAR SINKRON DENGAN WAKTU LOKAL
date_default_timezone_set('Asia/Jakarta'); 

require_once dirname(__DIR__) . '/config/database.php';

$sql = "SELECT p.* FROM pengiriman p 
        WHERE p.status IN ('dikirim', 'dalam_perjalanan', 'hampir_sampai')
        ORDER BY p.id ASC";
$result = query($sql);
$deliveries = fetchAll($result);

$updated = [];

foreach($deliveries as $d) {
    // Ambil waktu update terakhir
    $last_time = $d['last_status_update'];
    if(empty($last_time) || $last_time == '0000-00-00 00:00:00') {
        $last_time = $d['created_at'];
    }
    
    $last = strtotime($last_time);
    $now = time();
    $diff = $now - $last; // hitung selisih dalam detik
    
    // 2. CEGAH SELISIH MINUS:
    // Jika selisih minus akibat bentrok waktu HP dan Server, kita mutlakkan saja (positif)
    if($diff < 0) {
        $diff = abs($diff); 
    }
    // Jika selisih masih 0 (karena waktunya benar-benar persis), kita set 1 detik agar tetap memicu update
    if ($diff == 0) {
        $diff = 1;
    }
    
    $new_status = null;
    $message = '';
    
    // 3. LOGIKA UPDATE
    if($d['status'] == 'dikirim' && $diff >= 5) {
        $new_status = 'dalam_perjalanan';
        $message = "Kendaraan dalam perjalanan";
    } 
    elseif($d['status'] == 'dalam_perjalanan' && $diff >= 10) {
        $new_status = 'hampir_sampai';
        $message = "Pesanan hampir sampai";
    } 
    
    if($new_status) {
        $waktu_sekarang = date('Y-m-d H:i:s');
        $update_sql = "UPDATE pengiriman SET status = '$new_status', last_status_update = '$waktu_sekarang' WHERE id = {$d['id']}";
        
        if(query($update_sql)) {
            $track_sql = "INSERT INTO tracking_lokasi (pengiriman_id, latitude, longitude, lokasi_text, waktu) 
                          VALUES ({$d['id']}, -6.2, 106.8, '$message', '$waktu_sekarang')";
            query($track_sql);
            
            $updated[] = [
                'id' => $d['id'],
                'no_pengiriman' => $d['no_pengiriman'],
                'old_status' => $d['status'],
                'new_status' => $new_status,
                'diff_detik' => $diff
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'updated' => $updated,
    'total_checked' => count($deliveries),
    'server_time' => date('Y-m-d H:i:s')
]);
?>