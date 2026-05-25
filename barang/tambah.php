<?php
require_once '../config/database.php';
if(!isLoggedIn()) redirect('../auth/login.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_barang = $_POST['kode_barang'];
    $nama_barang = $_POST['nama_barang'];
    $spesifikasi = $_POST['spesifikasi'];
    $satuan = $_POST['satuan'];
    $harga = $_POST['harga'];
    
    $sql = "INSERT INTO barang (kode_barang, nama_barang, spesifikasi, satuan, harga) 
            VALUES ('$kode_barang', '$nama_barang', '$spesifikasi', '$satuan', $harga)";
    
    if(query($sql)) {
        $barang_id = mysqli_insert_id($conn);
        
        // Log aktivitas
        query("INSERT INTO riwayat_aktivitas (user_id, aktivitas, tabel_terkait, record_id) 
               VALUES ({$_SESSION['user_id']}, 'Tambah barang: $nama_barang', 'barang', $barang_id)");
        
        header("Location: index.php?success=1");
    } else {
        header("Location: index.php?error=1");
    }
}
?>