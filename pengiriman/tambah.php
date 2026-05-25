<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $no_pengiriman = $_POST['no_pengiriman'];
    $proyek_id = $_POST['proyek_id'];
    $warehouse_id = $_POST['warehouse_id'];
    $tanggal_pengiriman = $_POST['tanggal_pengiriman'];
    $user_id = $_SESSION['user_id'];

    // 1. Simpan Data Pengiriman Utama (Masih Pending / Draft)
    $sql_pengiriman = "INSERT INTO pengiriman 
                       (no_pengiriman, proyek_id, warehouse_id, tanggal_pengiriman, status_admin, created_by) 
                       VALUES 
                       ('$no_pengiriman', $proyek_id, $warehouse_id, '$tanggal_pengiriman', 'pending', $user_id)";
    
    if (query($sql_pengiriman)) {
        $pengiriman_id = mysqli_insert_id($conn);

        // 2. Simpan Detail Barang (Hanya mencatat, BELUM memotong stok)
        if(isset($_POST['barang_id']) && isset($_POST['jumlah'])) {
            $barang_id_arr = $_POST['barang_id'];
            $jumlah_arr = $_POST['jumlah'];

            for($i = 0; $i < count($barang_id_arr); $i++) {
                $b_id = $barang_id_arr[$i];
                $jml = $jumlah_arr[$i];
                
                if(!empty($b_id) && !empty($jml)) {
                    $sql_detail = "INSERT INTO detail_pengiriman 
                                   (pengiriman_id, barang_id, jumlah, status_validasi) 
                                   VALUES 
                                   ($pengiriman_id, $b_id, $jml, 'pending')";
                    query($sql_detail);
                    // Kode pemotong stok di sini SUDAH DIHAPUS
                }
            }
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        query("INSERT INTO riwayat_aktivitas (user_id, aktivitas, tabel_terkait, record_id, ip_address) 
               VALUES ($user_id, 'Buat draft pengiriman: $no_pengiriman', 'pengiriman', $pengiriman_id, '$ip')");

        header("Location: /shankara_trackingbarang/pengiriman/index.php?status=success");
        exit;
    } else {
        echo "Gagal menyimpan pengiriman.";
    }
}
?>