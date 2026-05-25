<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$no_resi = $_GET['no'] ?? null;

if($no_resi) {
    // Cari data pengiriman berdasarkan No Pengiriman
    $sql = "SELECT id, no_pengiriman, status FROM pengiriman WHERE no_pengiriman = '$no_resi'";
    $pengiriman = fetchOne(query($sql));

    if($pengiriman) {
        // Ambil riwayat lokasi
        $id = $pengiriman['id'];
        $sql_history = "SELECT lokasi_text, waktu FROM tracking_lokasi WHERE pengiriman_id = $id ORDER BY waktu DESC";
        $history = fetchAll(query($sql_history));

        echo json_encode([
            'success' => true,
            'data' => $pengiriman,
            'history' => $history
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Resi tidak ditemukan']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Input tidak valid']);
}