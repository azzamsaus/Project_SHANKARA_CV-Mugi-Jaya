<?php
require_once dirname(__DIR__) . '/config/database.php';
if(!isLoggedIn()) redirect('../auth/login.php');

function uploadFile($file, $folder) {
    if (!isset($file) || $file['error'] != 0 || $file['size'] == 0) {
        return null;
    }
    
    $target_dir = $_SERVER['DOCUMENT_ROOT'] . '/shankara_trackingbarang/uploads/' . $folder . '/';
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed)) {
        return null;
    }
    
    $new_filename = date('YmdHis') . '_' . uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return '/shankara_trackingbarang/uploads/' . $folder . '/' . $new_filename;
    }
    return null;
}

$id = $_POST['id'];
$sopir = $_POST['sopir'];
$no_kendaraan = $_POST['no_kendaraan'];
$lokasi_awal = $_POST['lokasi_awal'];
$lokasi_tujuan = $_POST['lokasi_tujuan'];

// Upload foto
$foto_lokasi_awal = uploadFile($_FILES['foto_lokasi_awal'] ?? null, 'lokasi_awal');
$foto_sopir = uploadFile($_FILES['foto_sopir'] ?? null, 'sopir');

$sql = "UPDATE pengiriman SET 
        sopir = '$sopir',
        no_kendaraan = '$no_kendaraan',
        lokasi_awal = '$lokasi_awal',
        lokasi_tujuan = '$lokasi_tujuan'";

if($foto_lokasi_awal) $sql .= ", foto_lokasi_awal = '$foto_lokasi_awal'";
if($foto_sopir) $sql .= ", foto_sopir = '$foto_sopir'";

$sql .= ", status = 'pending' WHERE id = $id";

if(query($sql)) {
    $pengiriman = fetchOne(query("SELECT * FROM pengiriman WHERE id = $id"));
    query("INSERT INTO notifikasi (user_id, judul, pesan, link) 
           SELECT id, 'Pengiriman Siap Dikirim', 
           'Pengiriman {$pengiriman['no_pengiriman']} siap untuk diantar. Silakan update status perjalanan.', 
           '/shankara_trackingbarang/pengiriman/index.php' 
           FROM users WHERE role = 'mandor'");
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan']);
}
?>