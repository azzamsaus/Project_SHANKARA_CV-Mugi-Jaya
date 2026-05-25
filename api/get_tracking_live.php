<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
require_once dirname(__DIR__) . '/config/database.php';

// Ambil semua pengiriman dengan status aktif
$sql = "SELECT p.*, pr.nama_proyek,
               (SELECT latitude FROM tracking_lokasi WHERE pengiriman_id = p.id ORDER BY waktu DESC LIMIT 1) as last_lat,
               (SELECT longitude FROM tracking_lokasi WHERE pengiriman_id = p.id ORDER BY waktu DESC LIMIT 1) as last_lng,
               (SELECT waktu FROM tracking_lokasi WHERE pengiriman_id = p.id ORDER BY waktu DESC LIMIT 1) as last_update
        FROM pengiriman p 
        LEFT JOIN proyek pr ON p.proyek_id = pr.id 
        WHERE p.status IN ('dikirim', 'dalam_perjalanan', 'hampir_sampai', 'sampai')
        ORDER BY p.created_at DESC";

$result = query($sql);
$deliveries = fetchAll($result);

$data = [];
foreach($deliveries as $d) {
    $lat = $d['last_lat'];
    $lng = $d['last_lng'];
    
    if(!$lat && $d['lokasi_awal']) {
        $coords = explode(',', $d['lokasi_awal']);
        $lat = trim($coords[0] ?? -6.2);
        $lng = trim($coords[1] ?? 106.8);
    }
    
    if(!$lat) {
        $lat = -6.2;
        $lng = 106.8;
    }
    
    // Label status untuk ditampilkan
    $status_label = $d['status'];
    if($status_label == 'hampir_sampai') $status_label = 'Hampir Sampai';
    elseif($status_label == 'dalam_perjalanan') $status_label = 'Dalam Perjalanan';
    elseif($status_label == 'dikirim') $status_label = 'Dikirim';
    elseif($status_label == 'sampai') $status_label = 'Sampai';
    
    $data[] = [
        'id' => $d['id'],
        'no_pengiriman' => $d['no_pengiriman'],
        'sopir' => $d['sopir'],
        'no_kendaraan' => $d['no_kendaraan'],
        'status' => $d['status'],
        'status_label' => $status_label,
        'latitude' => floatval($lat),
        'longitude' => floatval($lng),
        'lokasi_tujuan' => $d['lokasi_tujuan'],
        'last_update' => $d['last_update'],
        'foto_sopir' => $d['foto_sopir'],
        'foto_lokasi_tujuan' => $d['foto_lokasi_tujuan']
    ];
}

echo json_encode([
    'success' => true,
    'data' => $data,
    'total' => count($data),
    'timestamp' => date('Y-m-d H:i:s')
]);
?>