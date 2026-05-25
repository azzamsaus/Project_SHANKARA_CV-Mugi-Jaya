<?php
session_start();
require_once '../config/database.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

$id = $_GET['id'] ?? ($_POST['id'] ?? null);

if (!$id) {
    die("ID Pengiriman tidak ditemukan.");
}

// Ambil data pengiriman berdasarkan ID
$sql = "SELECT * FROM pengiriman WHERE id = $id";
$pengiriman = fetchOne(query($sql));

if (!$pengiriman) {
    die("Data pengiriman tidak ditemukan.");
}

$pesan_error = '';
$pesan_sukses = '';

// PROSES UPLOAD FOTO SAAT FORM DI-SUBMIT
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto_tiba'])) {
    // Tentukan folder tujuan (pastikan folder ini ada atau akan dibuat otomatis)
    $target_dir = "../uploads/lokasi_tujuan/";
    
    // Buat folder jika belum ada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($_FILES["foto_tiba"]["name"], PATHINFO_EXTENSION));
    $new_filename = date('YmdHis') . '_' . uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Path yang akan disimpan ke database
    $db_filepath = "/shankara_trackingbarang/uploads/lokasi_tujuan/" . $new_filename;

    // Validasi ekstensi gambar
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($file_extension, $allowed_types)) {
        // Pindahkan file yang di-upload ke folder tujuan
        if (move_uploaded_file($_FILES["foto_tiba"]["tmp_name"], $target_file)) {
            
            // Waktu saat ini
            $now = date('Y-m-d H:i:s');
            $today = date('Y-m-d');
            
            // 1. UPDATE TABEL PENGIRIMAN
            $sql_update = "UPDATE pengiriman SET 
                            status = 'sampai',
                            foto_lokasi_tujuan = '$db_filepath',
                            tiba_tanggal = '$today',
                            tiba_jam = '$now',
                            last_status_update = '$now'
                           WHERE id = $id";
                           
            if (query($sql_update)) {
                
                // 2. INSERT RIWAYAT TRACKING LOKASI
                $sql_track = "INSERT INTO tracking_lokasi (pengiriman_id, latitude, longitude, lokasi_text, waktu) 
                              VALUES ($id, -6.2, 106.8, 'Pesanan telah sampai di tujuan beserta bukti foto', '$now')";
                query($sql_track);

                // 3. KIRIM NOTIFIKASI KE ADMIN / OWNER
                $no_resi = $pengiriman['no_pengiriman'];
                $pesan_notif = "Pengiriman $no_resi telah sampai di tujuan.";
                query("INSERT INTO notifikasi (user_id, judul, pesan, link) 
                       SELECT id, '✅ Pesanan Sampai', '$pesan_notif', '/shankara_trackingbarang/pengiriman/index.php' 
                       FROM users WHERE role IN ('admin', 'owner')");

                $pesan_sukses = "Foto bukti tiba berhasil diunggah. Status pesanan sekarang 'Sampai'.";
            } else {
                $pesan_error = "Gagal mengupdate database.";
            }
        } else {
            $pesan_error = "Maaf, terjadi kesalahan saat menyimpan file foto.";
        }
    } else {
        $pesan_error = "Hanya file berekstensi JPG, JPEG, PNG, dan GIF yang diperbolehkan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Bukti Tiba - SHANKARA</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f5f7fa; }
        .card-custom { border-radius: 15px; border: none; }
        .header-custom { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; border-radius: 15px 15px 0 0; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow card-custom">
                    <div class="card-header header-custom py-3 text-center">
                        <h5 class="mb-0"><i class="bi bi-camera-fill"></i> Upload Bukti Barang Tiba</h5>
                    </div>
                    <div class="card-body p-4">
                        
                        <!-- Notifikasi Sukses dengan SweetAlert -->
                        <?php if ($pesan_sukses): ?>
                            <script>
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: "<?php echo $pesan_sukses; ?>",
                                    icon: 'success',
                                    confirmButtonText: 'Kembali ke Daftar',
                                    allowOutsideClick: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = 'index.php'; // Kembali ke halaman utama pengiriman
                                    }
                                });
                            </script>
                        <?php endif; ?>

                        <!-- Notifikasi Error -->
                        <?php if ($pesan_error): ?>
                            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?php echo $pesan_error; ?></div>
                        <?php endif; ?>

                        <div class="alert alert-info rounded-3">
                            <small class="text-muted d-block">No. Pengiriman</small>
                            <strong><?php echo $pengiriman['no_pengiriman']; ?></strong><br>
                            <small class="text-muted d-block mt-2">Lokasi Tujuan</small>
                            <strong><?php echo $pengiriman['lokasi_tujuan'] ?: 'Belum ditentukan'; ?></strong>
                        </div>

                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Ambil / Pilih Foto <span class="text-danger">*</span></label>
                                <!-- Parameter capture="environment" akan membuka kamera belakang di HP -->
                                <input type="file" name="foto_tiba" id="fotoInput" class="form-control form-control-lg" accept="image/*" capture="environment" required>
                                
                                <!-- Tempat menampilkan preview foto -->
                                <div class="mt-3 text-center">
                                    <img id="preview" src="#" alt="Preview Foto" style="max-width: 100%; max-height: 250px; display: none; border-radius: 10px; border: 2px solid #ddd;">
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-cloud-arrow-up-fill"></i> Upload Foto & Selesaikan
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Script untuk menampilkan preview gambar saat foto dipilih/diambil dari kamera
        document.getElementById('fotoInput').onchange = function (evt) {
            var file = evt.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('preview').src = e.target.result;
                    document.getElementById('preview').style.display = 'inline-block';
                }
                reader.readAsDataURL(file);
            }
        };
    </script>
</body>
</html>