<?php
session_start();
// Sesuaikan path ke database.php jika letaknya berbeda
require_once '../config/database.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Tangkap data dari form login
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password_input = $_POST['password'];

    // Cari user berdasarkan username
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    // Jika username ditemukan
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        // VERIFIKASI PASSWORD ENKRIPSI
        if (password_verify($password_input, $user['password'])) {
            
            // Jika cocok, buat sesi login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            
            // Simpan data warehouse jika ada (untuk Logistik/Driver)
            if(!empty($user['warehouse_id'])) {
                $_SESSION['warehouse_id'] = $user['warehouse_id'];
            }

            // Catat ke riwayat aktivitas (Opsional, hapus jika tidak pakai tabel riwayat)
            $user_id = $user['id'];
            mysqli_query($conn, "INSERT INTO riwayat_aktivitas (user_id, aktivitas) VALUES ($user_id, 'User login ke sistem')");

            // Arahkan ke dashboard utama
            header("Location: ../dashboard/index.php");
            exit();
        } else {
            // Password salah
            header("Location: login.php?error=1");
            exit();
        }
    } else {
        // Username tidak ditemukan
        header("Location: login.php?error=1");
        exit();
    }
} else {
    // Jika diakses langsung tanpa form
    header("Location: login.php");
    exit();
}
?>