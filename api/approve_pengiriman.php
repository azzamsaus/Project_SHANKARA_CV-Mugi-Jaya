<?php
session_start();
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/database.php';

$id = $_POST['id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if($id && $user_id) {
    $now = date('Y-m-d H:i:s');
    
    // 1. Ubah status pengiriman menjadi Approved
    $sql = "UPDATE pengiriman SET 
            status_admin = 'approved', 
            approved_by_admin = $user_id, 
            approved_at_admin = '$now' 
            WHERE id = $id";
            
    if(query($sql)) {
        // 2. Ambil informasi Warehouse ID dari pengiriman ini
        $pengiriman = fetchOne(query("SELECT warehouse_id, no_pengiriman FROM pengiriman WHERE id = $id"));
        $warehouse_id = $pengiriman['warehouse_id'];
        $no_resi = $pengiriman['no_pengiriman'];
        
        // 3. Ambil daftar barang dari detail pengiriman
        $detail_barang = fetchAll(query("SELECT barang_id, jumlah FROM detail_pengiriman WHERE pengiriman_id = $id"));
        
        // 4. POTONG STOK SEKARANG (Karena sudah dikirim ke Logistik)
        foreach($detail_barang as $d) {
            $b_id = $d['barang_id'];
            $jml = $d['jumlah'];
            query("UPDATE stok SET jumlah = jumlah - $jml WHERE barang_id = $b_id AND warehouse_id = $warehouse_id");
        }
        
        // 5. Kirim Notifikasi ke User Logistik
        query("INSERT INTO notifikasi (user_id, judul, pesan, link) 
               SELECT id, 'Pengiriman Baru Masuk', 'Pengiriman $no_resi perlu diisi detail sopir dan kendaraannya', '/shankara_trackingbarang/pengiriman/index.php' 
               FROM users WHERE role = 'logistik'");

        echo json_encode(['success' => true, 'message' => 'Berhasil dikirim ke logistik dan stok telah dipotong.']);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid atau sesi habis.']);
}
?>