<?php
// 1. Pastikan koneksi database tersedia
require_once '../config/database.php';

// 2. LOGIKA PROTEKSI: KELENGKAPAN DATA & STATUS AKUN
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$nama_user = $_SESSION['nama_lengkap'];

// Variabel Penanda Status
$akun_nonaktif = false;
$data_belum_lengkap = false;
$pesan_error_gembok = "Harap lengkapi Foto & No HP di menu Profil terlebih dahulu!";

if ($role == 'driver') {
    // Cek data supir
    $cek = fetchOne(query("SELECT * FROM sopir WHERE nama_sopir = '$nama_user'"));
    
    // Cek apakah akun dinonaktifkan
    if ($cek && isset($cek['status_aktif']) && $cek['status_aktif'] == 'Nonaktif') {
        $akun_nonaktif = true;
        $pesan_error_gembok = "AKSES DIBLOKIR: Akun Anda saat ini DINONAKTIFKAN. Silakan hubungi Admin!";
    } 
    // Cek jika akun aktif tapi data belum lengkap
    elseif (!$cek || empty($cek['foto_sopir']) || empty($cek['no_hp'])) {
        $data_belum_lengkap = true;
    }
} elseif ($role == 'logistik') {
    // Cek data logistik
    $cek = fetchOne(query("SELECT foto_user, no_hp, warehouse_id FROM users WHERE id = $user_id"));
    if (empty($cek['foto_user']) || empty($cek['no_hp']) || empty($cek['warehouse_id'])) {
        $data_belum_lengkap = true;
    }
}

// Global akses boolean
$bisa_akses = (!$akun_nonaktif && !$data_belum_lengkap);

// 3. --- FITUR PENGUNCIAN HALAMAN PAKSA (HARD REDIRECT) ---
if (!$bisa_akses && !in_array($role, ['admin', 'owner'])) {
    $current_url = $_SERVER['REQUEST_URI'];
    $is_allowed = false;
    
    // Daftar halaman yang BOLEH diakses
    if ($akun_nonaktif) {
        // JIKA NONAKTIF: Hanya boleh ke Dashboard dan Logout. Profil diblokir!
        $whitelist = [
            'dashboard/index.php',
            'auth/logout.php',
            'api/'
        ];
    } else {
        // JIKA HANYA KURANG DATA: Boleh akses form Profil
        $whitelist = [
            'dashboard/index.php',
            'sopir/profile.php', 
            'users/profile.php',
            'auth/logout.php',
            'api/'
        ];
    }
    
    foreach ($whitelist as $w) {
        if (strpos($current_url, $w) !== false) {
            $is_allowed = true;
            break;
        }
    }
    
    // Tembak kembali ke dashboard jika mencoba memaksa masuk
    if (!$is_allowed) {
        echo "<script>
            alert('🔒 $pesan_error_gembok');
            window.location.href = '/shankara_trackingbarang/dashboard/index.php';
        </script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Sistem Tracking Barang & Proyek - SHANKARA</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/shankara_trackingbarang/assets/css/style.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        :root { --brand-color: #3498db; --brand-hover: #2980b9; --sidebar-bg: #1e1e2d; --sidebar-bg-hover: rgba(255, 255, 255, 0.05); --body-bg: #f5f7fb; --text-gray: #6c757d; }
        body { font-family: 'Nunito', sans-serif; background-color: var(--body-bg); overflow-x: hidden; }
        #wrapper { display: flex; width: 100vw; align-items: stretch; transition: all 0.3s ease-in-out; }
        #sidebar-wrapper { min-width: 260px; max-width: 260px; min-height: 100vh; background: linear-gradient(180deg, #1a1a27 0%, #1e1e2d 100%); color: #fff; transition: all 0.3s ease-in-out; box-shadow: 2px 0 10px rgba(0,0,0,0.1); z-index: 1000; }
        #wrapper.toggled #sidebar-wrapper { margin-left: -260px; }
        .sidebar-heading { padding: 1.2rem 1.5rem; font-size: 1.25rem; font-weight: 800; letter-spacing: 1px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; }
        #sidebar-wrapper .list-group-item { background-color: transparent !important; color: #a2a3b7 !important; border: none !important; padding: 12px 20px; font-weight: 600; font-size: 0.95rem; transition: all 0.2s ease; display: flex; align-items: center; border-left: 4px solid transparent !important; }
        #sidebar-wrapper .list-group-item i { font-size: 1.2rem; margin-right: 12px; width: 20px; text-align: center; color: #8c8ea7 !important; transition: all 0.2s ease; }
        #sidebar-wrapper .list-group-item:hover, #sidebar-wrapper .list-group-item.active { color: #ffffff !important; background-color: var(--sidebar-bg-hover) !important; border-left: 4px solid var(--brand-color) !important; }
        #sidebar-wrapper .list-group-item:hover i, #sidebar-wrapper .list-group-item.active i { color: var(--brand-color) !important; }
        #page-content-wrapper { min-width: 0; width: 100%; display: flex; flex-direction: column; transition: all 0.3s ease-in-out; }
        .top-navbar { height: 70px; background-color: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; align-items: center; justify-content: space-between; padding: 0 25px; z-index: 999; }
        #menu-toggle { background: none; border: none; color: var(--text-gray); font-size: 1.8rem; cursor: pointer; padding: 0; transition: color 0.2s; }
        #menu-toggle:hover { color: var(--brand-color); }
        .user-profile { display: flex; align-items: center; gap: 15px; }
        .user-badge { background-color: rgba(52, 152, 219, 0.1); color: var(--brand-color); font-weight: 700; padding: 5px 12px; border-radius: 8px; font-size: 0.8rem; }
        .btn-logout { border-radius: 8px; font-weight: 600; padding: 6px 15px; }
        .content-area { padding: 30px; flex-grow: 1; }
        @media (max-width: 768px) { #sidebar-wrapper { margin-left: -260px; position: fixed; } #wrapper.toggled #sidebar-wrapper { margin-left: 0; } .content-area { padding: 15px; } }
    </style>
</head>
<body>

    <div id="wrapper">
        <!-- SIDEBAR -->
        <div id="sidebar-wrapper">
            <div class="sidebar-heading">
                <i class="bi bi-box-seam text-primary me-2"></i> 
                <span class="text-white">SHANKARA</span>
            </div>
            
            <div class="list-group list-group-flush mt-3">
                <a href="/shankara_trackingbarang/dashboard/index.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>

                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'owner'): ?>
                <a href="/shankara_trackingbarang/dashboard/owner_dashboard.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-eye"></i> Dashboard Owner
                </a>
                <?php endif; ?>

                <?php if(in_array(isset($_SESSION['role']) ? $_SESSION['role'] : '', ['admin', 'owner', 'engineering'])): ?>
                <a href="/shankara_trackingbarang/proyek/index.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-building"></i> Manajemen Proyek
                </a>
                <?php endif; ?>

                <?php if(in_array(isset($_SESSION['role']) ? $_SESSION['role'] : '', ['admin', 'owner'])): ?>
                <a href="/shankara_trackingbarang/barang/index.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-box"></i> Manajemen Barang
                </a>
                <?php endif; ?>

                <!-- Akses Pengiriman -->
                <?php if(in_array(isset($_SESSION['role']) ? $_SESSION['role'] : '', ['admin', 'logistik', 'driver'])): ?>
                    <?php if($bisa_akses || in_array($role, ['admin', 'owner'])): ?>
                        <a href="/shankara_trackingbarang/pengiriman/index.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-truck"></i> Pengiriman
                        </a>
                    <?php else: ?>
                        <a href="#" class="list-group-item list-group-item-action opacity-50" onclick="Swal.fire('Akses Terkunci','<?= $pesan_error_gembok ?>','error')">
                            <i class="bi bi-lock-fill"></i> Pengiriman (Terkunci)
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if(in_array(isset($_SESSION['role']) ? $_SESSION['role'] : '', ['admin', 'owner'])): ?>
                <a href="#" id="trackingMenuLink" class="list-group-item list-group-item-action">
                    <i class="bi bi-geo-alt"></i> Tracking Kendaraan
                </a>
                <?php endif; ?>

                <!-- BLOK MASTER DATA (KHUSUS ADMIN & OWNER) -->
                <?php if(in_array(isset($_SESSION['role']) ? $_SESSION['role'] : '', ['admin', 'owner'])): ?>
                <li class="list-unstyled mt-3 mb-1 px-4 text-muted fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Master Data</li>
                <a href="/shankara_trackingbarang/warehouse/index.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-building"></i> Manajemen Gudang
                </a>
                <a href="/shankara_trackingbarang/sopir/index.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-person-badge"></i> Manajemen Supir
                </a>
                <a href="/shankara_trackingbarang/users/index.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-people"></i> Manajemen Pengguna
                </a>
                <?php endif; ?>

                <!-- PROFIL SAYA (KHUSUS DRIVER & LOGISTIK) -->
                <?php if($role == 'driver'): ?>
                    <li class="list-unstyled mt-3 mb-1 px-4 text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Profil Driver</li>
                    <?php if($akun_nonaktif): ?>
                        <!-- JIKA AKUN NONAKTIF, MENU PROFIL IKUT DIGEMBOK -->
                        <a href="#" class="list-group-item list-group-item-action opacity-50" onclick="Swal.fire('Akses Terkunci','<?= $pesan_error_gembok ?>','error')">
                            <i class="bi bi-lock-fill text-danger"></i> Profil (Terkunci)
                        </a>
                    <?php else: ?>
                        <!-- JIKA AKUN AKTIF, BISA BUKA PROFIL -->
                        <a href="/shankara_trackingbarang/sopir/profile.php" class="list-group-item list-group-item-action <?= $data_belum_lengkap ? 'text-warning' : '' ?>">
                            <i class="bi bi-person-bounding-box <?= $data_belum_lengkap ? 'text-warning' : '' ?>"></i> Lengkapi Data Driver
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if($role == 'logistik'): ?>
                    <li class="list-unstyled mt-3 mb-1 px-4 text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Profil Logistik</li>
                    <a href="/shankara_trackingbarang/users/profile.php" class="list-group-item list-group-item-action <?= $data_belum_lengkap ? 'text-warning' : '' ?>">
                        <i class="bi bi-person-circle <?= $data_belum_lengkap ? 'text-warning' : '' ?>"></i> Lengkapi Data Logistik
                    </a>
                <?php endif; ?>

                <!-- KOMUNIKASI & ABSENSI -->
                <li class="list-unstyled mt-3 mb-1 px-4 text-muted fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Operasional</li>
                <a href="#" id="komunikasiMenuLink" class="list-group-item list-group-item-action">
                    <i class="bi bi-chat-dots"></i> Komunikasi Divisi
                </a>

                <?php if($bisa_akses || in_array($role, ['admin', 'owner'])): ?>
                    <a href="/shankara_trackingbarang/absensi/index.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-calendar-check"></i> Absensi Pegawai
                    </a>
                <?php else: ?>
                    <a href="#" class="list-group-item list-group-item-action opacity-50" onclick="Swal.fire('Akses Terkunci','<?= $pesan_error_gembok ?>','error')">
                        <i class="bi bi-lock-fill"></i> Absensi (Terkunci)
                    </a>
                <?php endif; ?>

                <!-- BLOK LAPORAN (KHUSUS ADMIN & OWNER) -->
                <?php if(in_array(isset($_SESSION['role']) ? $_SESSION['role'] : '', ['admin', 'owner'])): ?>
                <li class="list-unstyled mt-3 mb-1 px-4 text-muted fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Laporan & Rekap</li>
                <a href="/shankara_trackingbarang/laporan/index.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-journal-check"></i> Laporan Transaksi
                </a>
                <a href="/shankara_trackingbarang/laporan_keuangan/index.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-cash-coin"></i> Laporan Keuangan Aset
                </a>
                <a href="/shankara_trackingbarang/dashboard/laporan.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-file-earmark-text"></i> Laporan Sistem
                </a>
                <?php endif; ?>
            </div>
        </div>
        <!-- END SIDEBAR -->

        <!-- MAIN CONTENT -->
        <div id="page-content-wrapper">
            <nav class="top-navbar">
                <div class="d-flex align-items-center">
                    <button id="menu-toggle"><i class="bi bi-list"></i></button>
                    <h5 class="mb-0 ms-3 fw-bold text-secondary d-none d-md-block">Sistem Manajemen Logistik</h5>
                </div>
                <div class="user-profile">
                    <div class="d-none d-md-flex flex-column align-items-end text-end me-2">
                        <span class="fw-bold text-dark lh-1 mb-1"><?php echo isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'User'; ?></span>
                        <span class="user-badge lh-1"><?php echo isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Guest'; ?></span>
                    </div>
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-size: 1.2rem;"><i class="bi bi-person"></i></div>
                    <a href="/shankara_trackingbarang/auth/logout.php" class="btn btn-outline-danger btn-sm btn-logout ms-2" onclick="return confirm('Yakin ingin logout dari sistem?')">
                        <i class="bi bi-box-arrow-right d-md-none"></i><span class="d-none d-md-inline"><i class="bi bi-power me-1"></i> Logout</span>
                    </a>
                </div>
            </nav>

            <div class="content-area">
                <?php 
                if(isset($content) && file_exists($content)) { include $content; } else {
                    echo '<div class="d-flex align-items-center justify-content-center" style="min-height: 60vh;"><div class="text-center text-muted"><i class="bi bi-cone-striped display-1 text-warning mb-3"></i><h3 class="fw-bold">Konten Tidak Ditemukan</h3><p>Modul yang Anda cari sedang dalam pengembangan atau file hilang.</p></div></div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- MODAL AREA (TRACKING & KOMUNIKASI) -->
    <div class="modal fade" id="trackingModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
                <div class="modal-header border-0 bg-white pt-4 px-4 pb-0">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-geo-alt text-primary me-2"></i> Live Tracking Kendaraan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-white p-4">
                    <div class="mb-4">
                        <div class="btn-group w-100 flex-wrap shadow-sm rounded-3" role="group">
                            <button class="btn btn-outline-primary filter-btn active" data-filter="all">Semua</button>
                            <button class="btn btn-outline-info filter-btn" data-filter="dikirim">Dikirim</button>
                            <button class="btn btn-outline-warning filter-btn" data-filter="dalam_perjalanan">Dalam Perjalanan</button>
                            <button class="btn btn-outline-secondary filter-btn" data-filter="hampir_sampai">Hampir Sampai</button>
                            <button class="btn btn-outline-success filter-btn" data-filter="sampai">Sampai</button>
                        </div>
                    </div>
                    <div id="trackingList" style="max-height: 60vh; overflow-y: auto; overflow-x: hidden;" class="pe-2">
                        <div class="text-center text-muted py-5">
                            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                            <p class="mt-3">Memuat sistem radar kendaraan...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3">
                    <button type="button" class="btn btn-light fw-bold text-secondary" data-bs-dismiss="modal">Tutup Peta</button>
                    <button type="button" class="btn btn-primary fw-bold" id="refreshTrackingBtn"><i class="bi bi-arrow-repeat me-1"></i> Segarkan Data</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="komunikasiModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
                <div class="modal-header bg-primary text-white border-0 px-4 py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-chat-left-dots me-2"></i> Hubungi Divisi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <form id="formKomunikasi" class="bg-white p-4 rounded-3 shadow-sm mb-4 border">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">Kirim Pesan Kepada</label>
                            <select class="form-select form-select-lg" name="untuk_divisi" required style="font-size:0.95rem;">
                                <option value="produksi">⚙️ Divisi Produksi</option>
                                <option value="engineering">🛠️ Divisi Engineering</option>
                                <option value="semua" selected>📢 Semua Divisi (Broadcast)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">Isi Pesan</label>
                            <textarea class="form-control" name="pesan" rows="3" required placeholder="Ketik pesan koordinasi di sini..." style="resize:none; font-size:0.95rem;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2"><i class="bi bi-send-fill me-1"></i> Kirim Pesan Sekarang</button>
                    </form>
                    <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-inbox-fill text-primary me-2"></i> Pesan Masuk Terbaru</h6>
                    <div id="daftarPesan" style="max-height: 250px; overflow-y: auto;" class="pe-2">
                        <div class="text-muted text-center py-3">Memuat riwayat obrolan...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPT AREA -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/shankara_trackingbarang/assets/js/script.js"></script>
    
    <script>
    document.getElementById("menu-toggle").addEventListener("click", function(e) { e.preventDefault(); document.getElementById("wrapper").classList.toggle("toggled"); });
    $(document).ready(function() { var currentUrl = window.location.pathname; $('.list-group-item').each(function() { var href = $(this).attr('href'); if (currentUrl.includes(href) && href !== '#') { $(this).addClass('active'); } }); });

    let currentFilter = 'all'; let trackingRefreshInterval = null;
    function loadTrackingData() {
        $('#trackingList').html(`<div class="text-center text-muted py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3 fw-bold">Sinkronisasi GPS Kendaraan...</p></div>`);
        $.ajax({ url: '/shankara_trackingbarang/api/get_tracking_live.php', type: 'GET', dataType: 'json', timeout: 10000,
            success: function(response) {
                if (response.success && response.data && response.data.length > 0) {
                    let vehicles = response.data; if (currentFilter !== 'all') { vehicles = vehicles.filter(v => v.status === currentFilter); }
                    if (vehicles.length === 0) { $('#trackingList').html(`<div class="alert alert-light text-center border text-muted py-4"><i class="bi bi-truck fs-1 d-block mb-2"></i>Tidak ada armada dengan status <strong>${currentFilter.replace('_', ' ')}</strong></div>`); return; }
                    updateCardList(vehicles);
                } else { $('#trackingList').html(`<div class="alert alert-light text-center border text-muted py-4"><i class="bi bi-check-circle fs-1 d-block mb-2 text-success"></i>Semua armada telah sampai atau tidak ada yang beroperasi.</div>`); }
            }, error: function() { $('#trackingList').html(`<div class="alert alert-danger text-center shadow-sm">Gagal terhubung ke satelit/server.</div>`); }
        });
    }

    function updateCardList(vehicles) {
        let html = '<div class="row g-3 px-1 pb-2">';
        vehicles.forEach(function(v) {
            let sBadge = '', hBg = '', pPct = 0;
            if (v.status === 'dalam_perjalanan') { sBadge = '<span class="badge bg-warning text-dark">Perjalanan</span>'; hBg = 'border-warning'; pPct = 50; }
            else if (v.status === 'dikirim') { sBadge = '<span class="badge bg-primary">Dikirim</span>'; hBg = 'border-primary'; pPct = 25; }
            else if (v.status === 'hampir_sampai') { sBadge = '<span class="badge bg-info text-dark">Hampir Sampai</span>'; hBg = 'border-info'; pPct = 85; }
            else if (v.status === 'sampai') { sBadge = '<span class="badge bg-success">Selesai</span>'; hBg = 'border-success'; pPct = 100; }
            else { sBadge = '<span class="badge bg-secondary">' + v.status + '</span>'; hBg = 'border-secondary'; pPct = 0; }
            let lUpd = v.last_update ? new Date(v.last_update).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}) : '--:--';
            html += `<div class="col-md-6 col-lg-6"><div class="card h-100 shadow-sm border-0" style="border-radius:12px;"><div class="card-header bg-white border-bottom ${hBg}" style="border-left: 5px solid; padding: 15px;"><div class="d-flex justify-content-between align-items-center"><h6 class="mb-0 text-dark fw-bold"><i class="bi bi-upc-scan text-primary me-1"></i> ${v.no_pengiriman}</h6>${sBadge}</div></div><div class="card-body p-3"><div class="row mb-3 g-2"><div class="col-6"><div class="p-2 bg-light rounded h-100"><small class="text-muted d-block mb-1" style="font-size:0.7rem;">Sopir</small><strong class="text-dark d-block text-truncate"><i class="bi bi-person text-secondary"></i> ${v.sopir || '-'}</strong></div></div><div class="col-6"><div class="p-2 bg-light rounded h-100"><small class="text-muted d-block mb-1" style="font-size:0.7rem;">No. Polisi</small><strong class="text-dark d-block text-truncate"><i class="bi bi-card-heading text-secondary"></i> ${v.no_kendaraan || '-'}</strong></div></div></div><div class="mb-3 p-2 border rounded border-light"><small class="text-muted d-block mb-1 fw-bold"><i class="bi bi-geo-alt text-danger"></i> Lokasi Tujuan</small><span class="text-dark">${v.lokasi_tujuan || 'Belum diisi'}</span></div><div class="mb-1"><div class="d-flex justify-content-between mb-1"><small class="text-muted fw-bold" style="font-size:0.7rem;">PROGRESS</small><small class="text-primary fw-bold" style="font-size:0.7rem;">${pPct}%</small></div><div class="progress" style="height: 6px; border-radius: 10px;"><div class="progress-bar progress-bar-striped progress-bar-animated ${v.status === 'dalam_perjalanan' ? 'bg-warning' : (v.status === 'sampai' ? 'bg-success' : 'bg-primary')}" style="width: ${pPct}%;"></div></div></div></div><div class="card-footer bg-white border-top-0 pt-0 pb-3 px-3 d-flex justify-content-between align-items-center"><small class="text-muted fw-bold" style="font-size: 0.75rem;"><i class="bi bi-clock-history text-warning"></i> Update: ${lUpd}</small><button class="btn btn-primary btn-sm fw-bold px-3" style="border-radius:8px;" onclick="showTrackingDetail(${v.id})">Detail</button></div></div></div>`;
        });
        html += '</div>'; $('#trackingList').html(html);
    }

    function showTrackingDetail(id) { Swal.fire({title:'Loading...', allowOutsideClick:false, didOpen:()=>{Swal.showLoading();}}); $.ajax({url:'/shankara_trackingbarang/api/get_tracking.php?id='+id, type:'GET', dataType:'json', success:function(r){Swal.close(); if(r.success&&r.pengiriman){let p=r.pengiriman; let sL=(p.status=='dikirim')?'<span class="badge bg-primary">Dikirim</span>':(p.status=='dalam_perjalanan')?'<span class="badge bg-warning text-dark">Di Jalan</span>':(p.status=='hampir_sampai')?'<span class="badge bg-info">Hampir Tiba</span>':(p.status=='sampai')?'<span class="badge bg-success">Terkirim</span>':`<span class="badge bg-dark">${p.status}</span>`; Swal.fire({title:'📍 Detail Armada', html:`<div class="text-start p-3 bg-light rounded border mt-3"><table class="table table-borderless table-sm mb-0"><tr><td class="text-muted" width="35%">No Resi</td><td><strong>${p.no_pengiriman}</strong></td></tr><tr><td class="text-muted">Kurir</td><td><strong>${p.sopir}</strong></td></tr><tr><td class="text-muted">Plat No</td><td><strong>${p.no_kendaraan}</strong></td></tr><tr><td class="text-muted">Tujuan</td><td><strong>${p.lokasi_tujuan||'-'}</strong></td></tr><tr><td class="text-muted">Status</td><td>${sL}</td></tr></table></div>`, icon:'info', confirmButtonText:'Tutup', confirmButtonColor:'#3498db'});}}});}
    function showFoto(u, t) { Swal.fire({title:t, imageUrl:u, imageWidth:600, imageHeight:'auto', confirmButtonText:'Tutup', confirmButtonColor:'#3498db'}); }
    function loadMessages() { $.ajax({url:'/shankara_trackingbarang/api/get_komunikasi.php', type:'GET', dataType:'json', success:function(d){let c=$('#daftarPesan'); if(c&&d.messages&&d.messages.length>0){let h=''; d.messages.forEach(function(m){h+=`<div class="card border-0 shadow-sm mb-2 bg-white"><div class="card-body p-3"><div class="d-flex justify-content-between align-items-center mb-2"><strong class="text-dark"><i class="bi bi-person-circle text-primary me-1"></i> ${m.dari_user}</strong><span class="badge bg-light text-secondary border">Ke: ${m.untuk_divisi}</span></div><p class="mb-2 text-dark" style="font-size:0.95rem;">${m.pesan}</p><small class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-clock me-1"></i> ${m.created_at}</small></div></div>`;}); c.html(h);}else{c.html('<div class="text-muted text-center py-5"><i class="bi bi-envelope-paper fs-1 d-block mb-3 text-light"></i>Belum ada komunikasi</div>');}}});}
    function autoUpdateStatus() { $.ajax({url:'/shankara_trackingbarang/api/auto_update_status.php', type:'GET', dataType:'json', timeout:5000, success:function(r){if(r.updated&&r.updated.length>0){if($('#trackingModal').hasClass('show')){loadTrackingData();} if(window.location.pathname.includes('pengiriman')){location.reload();}}}}); }
    function startAutoRefresh() { if(trackingRefreshInterval) clearInterval(trackingRefreshInterval); trackingRefreshInterval=setInterval(function(){if($('#trackingModal').hasClass('show')){loadTrackingData();}}, 10000); }

    $(document).ready(function() {
        $('#trackingMenuLink').on('click', function(e) { e.preventDefault(); loadTrackingData(); startAutoRefresh(); $('#trackingModal').modal('show'); });
        $('#refreshTrackingBtn').on('click', function() { loadTrackingData(); });
        $(document).on('click', '.filter-btn', function() { $('.filter-btn').removeClass('active btn-primary text-white').addClass('btn-outline-primary'); $(this).removeClass('btn-outline-primary').addClass('active btn-primary text-white'); currentFilter = $(this).data('filter'); loadTrackingData(); });
        $('#trackingModal').on('hidden.bs.modal', function() { if (trackingRefreshInterval) { clearInterval(trackingRefreshInterval); trackingRefreshInterval = null; } });
        $('#komunikasiMenuLink').on('click', function(e) { e.preventDefault(); loadMessages(); $('#komunikasiModal').modal('show'); });
        $('#formKomunikasi').on('submit', function(e) { e.preventDefault(); let sBtn=$(this).find('button[type="submit"]'); let oTxt=sBtn.html(); sBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengirim...').prop('disabled',true); $.ajax({url:'/shankara_trackingbarang/api/send_komunikasi.php', type:'POST', data:$(this).serialize(), dataType:'json', success:function(d){sBtn.html(oTxt).prop('disabled',false); if(d.success){$('#formKomunikasi')[0].reset(); loadMessages();}else{Swal.fire('Gagal','Pesan gagal dikirim','error');}}, error:function(){sBtn.html(oTxt).prop('disabled',false);}}); });
    });
    
    setInterval(autoUpdateStatus, 10000);
    window.showTrackingDetail = showTrackingDetail;
    window.showFoto = showFoto;
    </script>
</body>
</html>