<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/database.php';

$id = $_GET['id'] ?? null;

if($id) {
    // Ambil data pengiriman
    $sql = "SELECT p.*, pr.nama_proyek, w.nama_warehouse 
            FROM pengiriman p 
            LEFT JOIN proyek pr ON p.proyek_id = pr.id 
            LEFT JOIN warehouse w ON p.warehouse_id = w.id 
            WHERE p.id = $id";
    $result = query($sql);
    $pengiriman = fetchOne($result);
    
    // Ambil riwayat tracking
    $sql_track = "SELECT * FROM tracking_lokasi WHERE pengiriman_id = $id ORDER BY waktu DESC";
    $result_track = query($sql_track);
    $tracking = fetchAll($result_track);
    
    // Ambil detail barang
    $sql_detail = "SELECT dp.*, b.nama_barang, b.satuan 
                   FROM detail_pengiriman dp 
                   JOIN barang b ON dp.barang_id = b.id 
                   WHERE dp.pengiriman_id = $id";
    $result_detail = query($sql_detail);
    $detail_barang = fetchAll($result_detail);
    
    echo json_encode([
        'success' => true,
        'pengiriman' => $pengiriman,
        'tracking' => $tracking,
        'detail_barang' => $detail_barang
    ]);
} else {
    // Jika tidak ada id, ambil semua yang aktif
    $sql = "SELECT p.*, pr.nama_proyek 
            FROM pengiriman p 
            LEFT JOIN proyek pr ON p.proyek_id = pr.id 
            WHERE p.status IN ('dikirim', 'dalam_perjalanan')
            ORDER BY p.created_at DESC";
    $result = query($sql);
    $pengiriman = fetchAll($result);
    
    echo json_encode([
        'success' => true,
        'data' => $pengiriman
    ]);
}
?>