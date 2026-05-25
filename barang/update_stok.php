<?php
require_once '../config/database.php';
if(!isLoggedIn()) redirect('../auth/login.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $barang_id = $_POST['barang_id'];
    $warehouse_id = $_POST['warehouse_id'];
    $jumlah = $_POST['jumlah'];
    $tipe = $_POST['tipe'];
    
    // Check existing stock
    $check = fetchOne(query("SELECT * FROM stok WHERE barang_id=$barang_id AND warehouse_id=$warehouse_id"));
    
    if($check) {
        if($tipe == 'tambah') {
            $new_jumlah = $check['jumlah'] + $jumlah;
        } elseif($tipe == 'kurang') {
            $new_jumlah = $check['jumlah'] - $jumlah;
        } else {
            $new_jumlah = $jumlah;
        }
        $sql = "UPDATE stok SET jumlah=$new_jumlah 
                WHERE barang_id=$barang_id AND warehouse_id=$warehouse_id";
    } else {
        $sql = "INSERT INTO stok (barang_id, warehouse_id, jumlah) 
                VALUES ($barang_id, $warehouse_id, $jumlah)";
    }
    
    if(query($sql)) {
        header("Location: index.php?stok_updated=1");
    } else {
        header("Location: index.php?error=1");
    }
}
?>