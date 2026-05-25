<?php
require_once '../config/database.php';
if(!isLoggedIn()) redirect('../auth/login.php');

// 1. Amankan input ID menjadi format angka (integer)
$id = intval($_GET['id']);

// 2. CEK REFERENSI DI TABEL DETAIL_PENGIRIMAN
// Mencegah penghapusan jika barang sudah pernah masuk riwayat pengiriman
$cek_pengiriman = fetchOne(query("SELECT COUNT(*) as total FROM detail_pengiriman WHERE barang_id = $id"));

if($cek_pengiriman['total'] > 0) {
    // Redirect dengan status error khusus jika barang sedang/pernah dikirim
    header("Location: index.php?error=in_use");
    exit();
}

// 3. HAPUS DATA DI TABEL STOK TERLEBIH DAHULU (Child Table)
// Jika barang belum pernah dikirim, kita harus menghapus rincian stoknya dulu
query("DELETE FROM stok WHERE barang_id = $id");

// 4. HAPUS DATA DI TABEL BARANG (Parent Table)
$sql = "DELETE FROM barang WHERE id = $id";

if(query($sql)) {
    // Catat log aktivitas jika berhasil
    query("INSERT INTO riwayat_aktivitas (user_id, aktivitas, tabel_terkait, record_id) 
           VALUES ({$_SESSION['user_id']}, 'Hapus barang ID: $id', 'barang', $id)");
           
    header("Location: index.php?deleted=1");
} else {
    header("Location: index.php?error=1");
}
?> 