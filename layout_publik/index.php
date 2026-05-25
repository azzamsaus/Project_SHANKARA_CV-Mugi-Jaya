<?php
require_once dirname(__DIR__) . '/config/database.php';
$page = isset($_GET['page']) ? $_GET['page'] : 'tracking';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Publik - SHANKARA</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }

        /* Navbar Premium */
        .navbar-custom {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 15px 0;
        }
        .navbar-brand { font-weight: 800; color: #0f172a !important; letter-spacing: -0.5px; }
        .nav-link { color: #64748b !important; font-weight: 600; margin: 0 10px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { color: #3b82f6 !important; }

        /* Hero Modern */
        .hero-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 80px 0 120px 0;
            color: white;
            text-align: center;
            border-bottom-left-radius: 60px;
            border-bottom-right-radius: 60px;
        }
        .hero-section h1 { font-weight: 800; font-size: 2.8rem; letter-spacing: -1px; }

        /* Glass / Soft Card */
        .glass-card {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.5);
            margin-top: -70px;
            margin-bottom: 50px;
        }

        /* Buttons & Inputs */
        .form-control { border-radius: 12px; padding: 15px 20px; border: 2px solid #e2e8f0; background: #f8fafc; }
        .form-control:focus { border-color: #3b82f6; box-shadow: none; background: white; }
        .btn-primary { background: #3b82f6; border: none; border-radius: 12px; padding: 15px; font-weight: 700; transition: 0.3s; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3); }

        .footer-public { margin-top: 80px; padding: 40px 0; background: white; border-top: 1px solid #f1f5f9; text-align: center; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="bi bi-box-seam text-primary me-2"></i>SHANKARA</a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="publicNavbar">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page == 'tracking' ? 'active' : ''; ?>" href="index.php?page=tracking">Lacak Pengiriman</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page == 'katalog' ? 'active' : ''; ?>" href="index.php?page=katalog">Katalog & Pesan</a>
                    </li>
                </ul>
                <a href="/shankara_trackingbarang/auth/login.php" class="btn btn-dark rounded-pill px-4 fw-bold">Internal Login</a>
            </div>
        </div>
    </nav>

    <?php 
        if($page == 'katalog') {
            include 'katalog.php';
        } else {
            include 'tracking.php';
        }
    ?>

    <?php 
        include 'pesan.php';
        include 'review.php';
        include 'komplain.php';
    ?>

    <footer class="footer-public">
        <div class="container">
            <h6 class="fw-bold text-dark mb-1">CV Mugi Jaya - Divisi Logistik</h6>
            <p class="text-muted small mb-0">&copy; 2026 SHANKARA Ecosystem. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let currentNoPengiriman = '';

        // Form Tracking Submit
        $('#publicTrackForm').on('submit', function(e) {
            e.preventDefault();
            const noResi = $('#no_pengiriman').val().trim();
            $('#trackResult, #trackError, #bukti_sampai_area').addClass('d-none');
            $('#trackLoader').removeClass('d-none');

            $.ajax({
                url: '/shankara_trackingbarang/api/public_get_tracking.php', 
                type: 'GET',
                data: { no: noResi },
                dataType: 'json',
                success: function(res) {
                    $('#trackLoader').addClass('d-none');
                    if(res.success) {
                        currentNoPengiriman = res.data.no_pengiriman; 
                        $('#res_no').text(res.data.no_pengiriman);
                        $('#res_status').text(res.data.status.replace('_', ' ').toUpperCase());
                        
                        let timelineHtml = '';
                        if(res.history && res.history.length > 0) {
                            res.history.forEach(h => {
                                timelineHtml += `
                                    <div class="position-relative pb-4" style="border-left: 2px dashed #cbd5e1; margin-left: 10px; padding-left: 25px;">
                                        <div class="position-absolute bg-primary rounded-circle border border-3 border-white" style="width: 16px; height: 16px; left: -9px; top: 0;"></div>
                                        <div class="fw-bold text-dark">${h.lokasi_text}</div>
                                        <small class="text-muted"><i class="bi bi-clock me-1"></i>${h.waktu}</small>
                                    </div>`;
                            });
                        }
                        $('#timeline_list').html(timelineHtml);
                        
                        if(res.data.status === 'sampai' && res.data.foto_lokasi_tujuan) {
                            $('#res_foto_bukti').attr('src', res.data.foto_lokasi_tujuan);
                            $('#bukti_sampai_area').removeClass('d-none');
                        }
                        $('#trackResult').removeClass('d-none');
                    } else {
                        $('#trackError').removeClass('d-none');
                    }
                },
                error: function() {
                    $('#trackLoader').addClass('d-none');
                    Swal.fire('Koneksi Gagal', 'Server tidak merespon.', 'error');
                }
            });
        });

        function openPesanModal(id, nama) {
            $('#pesan_barang_id').val(id);
            $('#pesan_nama_barang').val(nama);
            $('#pesanModal').modal('show');
        }

        // Lihat file pesan.php, review.php, dan komplain.php untuk AJAX action-nya
    </script>
</body>
</html>