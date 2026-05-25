<?php
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role == 'driver') {
    $nama_user = $_SESSION['nama_lengkap'];
    $cek = fetchOne(query("SELECT * FROM sopir WHERE nama_sopir = '$nama_user'"));
    if (!$cek || empty($cek['foto_sopir']) || empty($cek['no_hp'])) {
        die("<script>alert('Harap lengkapi Foto & No HP di menu Profil Driver sebelum melanjutkan!'); window.location='../dashboard/index.php';</script>");
    }
} elseif ($role == 'logistik') {
    $cek = fetchOne(query("SELECT foto_user, no_hp, warehouse_id FROM users WHERE id = $user_id"));
    if (empty($cek['foto_user']) || empty($cek['no_hp']) || empty($cek['warehouse_id'])) {
        die("<script>alert('Harap lengkapi Foto, No HP, dan pastikan Warehouse sudah disetting Admin sebelum melanjutkan!'); window.location='../dashboard/index.php';</script>");
    }
}
?>