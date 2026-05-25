<?php
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$nama_user = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : '';

// 1. AMBIL ID GUDANG (WAREHOUSE) JIKA YANG LOGIN ADALAH LOGISTIK
$my_warehouse_id = 0;
if($user_role == 'logistik') {
    $user_data = fetchOne(query("SELECT warehouse_id FROM users WHERE id = $user_id"));
    $my_warehouse_id = $user_data['warehouse_id'] ? $user_data['warehouse_id'] : 0;
}

// 2. MENGAMBIL DATA PENGIRIMAN BERDASARKAN ROLE
if($user_role == 'admin' || $user_role == 'owner') {
    $pengiriman = fetchAll(query("
        SELECT p.*, pr.nama_proyek, w.nama_warehouse
        FROM pengiriman p 
        JOIN proyek pr ON p.proyek_id = pr.id 
        JOIN warehouse w ON p.warehouse_id = w.id 
        WHERE p.status_admin = 'pending'
        ORDER BY p.created_at DESC
    "));
    
    $riwayat_pengiriman = fetchAll(query("
        SELECT p.*, pr.nama_proyek, w.nama_warehouse
        FROM pengiriman p 
        JOIN proyek pr ON p.proyek_id = pr.id 
        JOIN warehouse w ON p.warehouse_id = w.id 
        WHERE p.status_admin != 'pending'
        ORDER BY p.created_at DESC
    "));
} elseif($user_role == 'logistik') {
    // Filter HANYA pengiriman yang berasal dari gudang milik staf ini
    $pengiriman = fetchAll(query("
        SELECT p.*, pr.nama_proyek, w.nama_warehouse
        FROM pengiriman p 
        JOIN proyek pr ON p.proyek_id = pr.id 
        JOIN warehouse w ON p.warehouse_id = w.id 
        WHERE p.status_admin = 'approved' 
        AND (p.sopir IS NULL OR p.sopir = '')
        AND p.warehouse_id = $my_warehouse_id
        ORDER BY p.created_at DESC
    "));
} elseif($user_role == 'mandor' || $user_role == 'driver') {
    // Filter HANYA pengiriman yang ditugaskan kepada nama Supir/Driver ini
    $pengiriman = fetchAll(query("
        SELECT p.*, pr.nama_proyek, w.nama_warehouse
        FROM pengiriman p 
        JOIN proyek pr ON p.proyek_id = pr.id 
        JOIN warehouse w ON p.warehouse_id = w.id 
        WHERE p.sopir = '$nama_user'
        ORDER BY p.created_at DESC
    "));
} else {
    $pengiriman = fetchAll(query("
        SELECT p.*, pr.nama_proyek, w.nama_warehouse
        FROM pengiriman p 
        JOIN proyek pr ON p.proyek_id = pr.id 
        JOIN warehouse w ON p.warehouse_id = w.id 
        ORDER BY p.created_at DESC
    "));
}

$proyek = fetchAll(query("SELECT * FROM proyek WHERE status != 'completed'"));
$warehouse = fetchAll(query("SELECT * FROM warehouse"));

$stok_warehouse = fetchAll(query("
    SELECT s.warehouse_id, s.barang_id, s.jumlah, b.nama_barang, b.satuan, b.harga 
    FROM stok s 
    JOIN barang b ON s.barang_id = b.id 
    WHERE s.jumlah > 0
"));

// 3. MENGAMBIL DAFTAR SUPIR YANG AKTIF UNTUK DROPDOWN (DENGAN FILTER WAREHOUSE)
if($user_role == 'logistik' && $my_warehouse_id > 0) {
    // Hanya ambil supir yang bertugas di gudang yang sama dengan staf logistik
    $daftar_sopir = fetchAll(query("SELECT * FROM sopir WHERE status_aktif = 'Aktif' AND warehouse_id = $my_warehouse_id ORDER BY nama_sopir ASC"));
} else {
    // Admin/Owner bisa melihat semua supir
    $daftar_sopir = fetchAll(query("SELECT * FROM sopir WHERE status_aktif = 'Aktif' ORDER BY nama_sopir ASC"));
}
?>

<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: transparent; }
        
        .page-title { font-size: 24px; font-weight: 700; color: #2c3e50; margin-bottom: 5px; }
        .page-subtitle { color: #7f8c8d; font-size: 14px; margin-bottom: 20px; }
        
        .btn-tambah { background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 600; }
        .btn-approve { background: #27ae60; color: white; border: none; padding: 5px 15px; border-radius: 8px; font-size: 12px; }
        .btn-edit-logistik { background: #f39c12; color: white; border: none; padding: 5px 15px; border-radius: 8px; font-size: 12px; }
        .btn-track { background: #3498db; color: white; border: none; padding: 5px 12px; border-radius: 8px; font-size: 12px; cursor: pointer; }
        .btn-upload-tiba { background: #27ae60; color: white; border: none; padding: 5px 12px; border-radius: 8px; font-size: 12px; cursor: pointer; }
        .btn-validate { background: #4361ee; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 12px; cursor: pointer; }
        
        .card-custom { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); overflow: hidden; }
        .card-header-custom { padding: 16px 20px; background: white; border-bottom: 2px solid #e9ecef; }
        .card-header-custom h5 { margin: 0; font-weight: 700; color: #2c3e50; }
        .card-body-custom { padding: 20px; }
        
        .table-custom { width: 100%; border-collapse: collapse; }
        .table-custom th { background: #f8f9fa; padding: 12px; font-size: 13px; font-weight: 700; color: #2c3e50; border-bottom: 2px solid #e9ecef; text-align: left; }
        .table-custom td { padding: 12px; font-size: 13px; color: #34495e; border-bottom: 1px solid #e9ecef; vertical-align: middle; }
        .table-custom tr:hover { background: #f8f9fa; }
        
        .badge-pending-adm { background: #f39c12; color: white; padding: 4px 12px; border-radius: 30px; font-size: 11px; }
        .badge-status { display: inline-block; padding: 4px 12px; border-radius: 30px; font-size: 11px; font-weight: 600; }
        .badge-dikirim { background: #2980b9; color: white; }
        .badge-dalam_perjalanan { background: #f39c12; color: white; }
        .badge-hampir_sampai { background: #e67e22; color: white; }
        .badge-sampai { background: #27ae60; color: white; }
        .badge-pending { background: #95a5a6; color: white; }
        
        .foto-thumb { width: 45px; height: 45px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 1px solid #ddd; }
        .camera-icon { color: #27ae60; cursor: pointer; margin-left: 5px; font-size: 14px; }
        
        .modal-header-custom { background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 15px 20px; }
        .form-label-custom { font-weight: 600; font-size: 13px; margin-bottom: 5px; color: #2c3e50; }
        .form-control-custom, .form-select-custom { border-radius: 10px; border: 1px solid #ddd; padding: 8px 12px; width: 100%; }
        
        .tab-btn-custom {
            color: #495057 !important;
            background-color: #f8f9fa !important;
            border: 1px solid #dee2e6 !important;
            transition: all 0.3s;
        }
        .tab-btn-custom.active {
            background-color: #3498db !important;
            color: #ffffff !important;
            border-color: #3498db !important;
            box-shadow: 0 4px 6px rgba(52, 152, 219, 0.3) !important;
        }

        @media (max-width: 768px) {
            .table-custom { font-size: 11px; }
            .table-custom th, .table-custom td { padding: 8px; }
            .foto-thumb { width: 35px; height: 35px; }
        }
    </style>
</head>
<body>

<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="page-title"><i class="bi bi-truck"></i> Manajemen Pengiriman</h3>
            <p class="page-subtitle">
                <?php 
                if(in_array($_SESSION['role'], ['admin', 'owner'])) echo "📋 Buat pengiriman dan alokasikan stok barang ke Proyek";
                elseif($_SESSION['role'] == 'logistik') echo "📝 Isi detail pengiriman (sopir, no kendaraan, foto lokasi awal) untuk gudang Anda.";
                elseif(in_array($_SESSION['role'], ['mandor', 'driver'])) echo "🚚 Data pengiriman yang ditugaskan khusus kepada Anda. Klik Validasi untuk update status.";
                ?>
            </p>
        </div>
        <?php if(in_array($_SESSION['role'], ['admin', 'owner'])): ?>
        <button class="btn-tambah shadow-sm" data-bs-toggle="modal" data-bs-target="#tambahPengirimanModal">
            <i class="bi bi-plus-circle"></i> Buat Pengiriman
        </button>
        <?php endif; ?>
    </div>
    
    <!-- KHUSUS ADMIN & OWNER: TAMPILAN TABS (PENDING & RIWAYAT) -->
    <?php if(in_array($_SESSION['role'], ['admin', 'owner'])): ?>
        <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active px-4 fw-bold tab-btn-custom" id="pills-pending-tab" data-bs-toggle="pill" data-bs-target="#pills-pending" type="button" role="tab" style="border-radius: 10px;">Perlu Diproses</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link px-4 fw-bold ms-2 tab-btn-custom" id="pills-history-tab" data-bs-toggle="pill" data-bs-target="#pills-history" type="button" role="tab" style="border-radius: 10px;">Riwayat Pengiriman</button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            <!-- TAB 1: PENDING PENGIRIMAN -->
            <div class="tab-pane fade show active" id="pills-pending" role="tabpanel">
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5><i class="bi bi-hourglass-split text-warning"></i> Menunggu Logistik</h5>
                    </div>
                    <div class="card-body-custom">
                        <div style="overflow-x: auto;">
                            <table class="table-custom">
                                <thead>
                                    <tr>
                                        <th>No. Pengiriman</th>
                                        <th>Proyek</th>
                                        <th>Warehouse</th>
                                        <th>Tanggal</th>
                                        <th>Status Admin</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($pengiriman)): ?>
                                        <tr>
                                            <td colspan="6" style="text-align: center; padding: 40px;">
                                                <i class="bi bi-check-circle text-success" style="font-size: 48px;"></i><br>
                                                Semua pengiriman telah diproses.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($pengiriman as $p): ?>
                                        <tr>
                                            <td><strong><?php echo $p['no_pengiriman']; ?></strong></td>
                                            <td><?php echo $p['nama_proyek']; ?></td>
                                            <td><?php echo $p['nama_warehouse']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($p['tanggal_pengiriman'])); ?></td>
                                            <td><span class="badge-pending-adm">Menunggu Logistik</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info rounded-3 mb-1" onclick="lihatDetail(<?php echo $p['id']; ?>)"><i class="bi bi-eye"></i> Detail</button>
                                                <button class="btn-approve rounded-3 mb-1" onclick="approveToLogistik(<?php echo $p['id']; ?>)">
                                                    <i class="bi bi-send"></i> Kirim ke Logistik
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: RIWAYAT PENGIRIMAN -->
            <div class="tab-pane fade" id="pills-history" role="tabpanel">
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5><i class="bi bi-clock-history text-primary"></i> Riwayat Seluruh Pengiriman</h5>
                    </div>
                    <div class="card-body-custom">
                        <div style="overflow-x: auto;">
                            <table class="table-custom">
                                <thead>
                                    <tr>
                                        <th>No. Pengiriman</th>
                                        <th>Proyek Tujuan</th>
                                        <th>Sopir</th>
                                        <th>Status Ekspedisi</th>
                                        <th>Aksi Lengkap</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($riwayat_pengiriman)): ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center; padding: 40px;">
                                                <i class="bi bi-inbox text-muted" style="font-size: 48px;"></i><br>
                                                Belum ada riwayat pengiriman.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($riwayat_pengiriman as $r): ?>
                                        <tr>
                                            <td><strong><?php echo $r['no_pengiriman']; ?></strong><br><small class="text-muted"><?php echo date('d/m/Y', strtotime($r['tanggal_pengiriman'])); ?></small></td>
                                            <td><?php echo $r['nama_proyek']; ?></td>
                                            <td>
                                                <?php echo $r['sopir'] ?: '<span class="text-muted fst-italic">Belum diisi</span>'; ?>
                                                <?php if($r['no_kendaraan']) echo "<br><small class='text-primary fw-bold'>".$r['no_kendaraan']."</small>"; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status = $r['status'];
                                                if($status == 'dikirim') echo '<span class="badge-status badge-dikirim">📦 Dikirim</span>';
                                                elseif($status == 'dalam_perjalanan') echo '<span class="badge-status badge-dalam_perjalanan">🚚 Dalam Perjalanan</span>';
                                                elseif($status == 'hampir_sampai') echo '<span class="badge-status badge-hampir_sampai">📍 Hampir Sampai</span>';
                                                elseif($status == 'sampai') echo '<span class="badge-status badge-sampai">✅ Sampai</span>';
                                                else echo '<span class="badge-status badge-pending">⏳ Pending</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info rounded-3" onclick="lihatDetail(<?php echo $r['id']; ?>)"><i class="bi bi-file-text"></i> Detail Info</button>
                                                <button class="btn-track rounded-3" onclick="showTrackingDetail(<?php echo $r['id']; ?>)"><i class="bi bi-geo-alt"></i> Peta</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <!-- TAMPILAN NON-ADMIN (Normal Table) -->
    <?php else: ?>
        <div class="card-custom">
            <div class="card-header-custom">
                <h5><i class="bi bi-list-ul"></i> Daftar Pengiriman</h5>
            </div>
            <div class="card-body-custom">
                <div style="overflow-x: auto;">
                    <table class="table-custom">
                        <thead>
                            <?php if($_SESSION['role'] == 'logistik'): ?>
                            <tr>
                                <th>No. Pengiriman</th>
                                <th>Proyek</th>
                                <th>Warehouse</th>
                                <th>Tanggal</th>
                                <th>Foto Awal</th>
                                <th>Aksi</th>
                            </tr>
                            <?php elseif(in_array($_SESSION['role'], ['mandor', 'driver'])): ?>
                            <tr>
                                <th>No. Pengiriman</th>
                                <th>Proyek</th>
                                <th>Sopir</th>
                                <th>No. Kendaraan</th>
                                <th>Status</th>
                                <th>Foto Awal</th>
                                <th>Bukti Tiba</th>
                                <th>Aksi</th>
                            </tr>
                            <?php endif; ?>
                        </thead>
                        <tbody>
                            <?php if(empty($pengiriman)): ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 40px;">
                                        <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i><br>
                                        Belum ada data pengiriman untuk Anda.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($pengiriman as $p): ?>
                                <tr>
                                    <!-- LOGISTIK -->
                                    <?php if($_SESSION['role'] == 'logistik'): ?>
                                    <td><strong><?php echo $p['no_pengiriman']; ?></strong></td>
                                    <td><?php echo $p['nama_proyek']; ?></td>
                                    <td><span class="badge bg-primary px-3 py-2 rounded-pill"><?php echo $p['nama_warehouse']; ?></span></td>
                                    <td><?php echo date('d/m/Y', strtotime($p['tanggal_pengiriman'])); ?></td>
                                    <td>
                                        <?php if(!empty($p['foto_lokasi_awal'])): ?>
                                            <img src="<?php echo $p['foto_lokasi_awal']; ?>" class="foto-thumb" onclick="showFoto('<?php echo $p['foto_lokasi_awal']; ?>', 'Foto Lokasi Awal')">
                                        <?php else: ?> - <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info rounded-3 mb-1" onclick="lihatDetail(<?php echo $p['id']; ?>)"><i class="bi bi-eye"></i> Detail</button>
                                        <!-- MENGIRIMKAN NAMA WAREHOUSE OTOMATIS KE MODAL -->
                                        <button class="btn-edit-logistik rounded-3 mb-1" onclick="openEditLogistik(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['nama_warehouse'], ENT_QUOTES); ?>')">
                                            <i class="bi bi-pencil"></i> Isi Detail Supir
                                        </button>
                                    </td>
                                    
                                    <!-- MANDOR/DRIVER -->
                                    <?php elseif(in_array($_SESSION['role'], ['mandor', 'driver'])): ?>
                                    <td><strong><?php echo $p['no_pengiriman']; ?></strong></td>
                                    <td><?php echo $p['nama_proyek']; ?></td>
                                    <td><?php echo $p['sopir'] ?: '-'; ?> <?php if(!empty($p['foto_sopir'])): ?><i class="bi bi-camera-fill camera-icon" onclick="showFoto('<?php echo $p['foto_sopir']; ?>', 'Foto Sopir')"></i><?php endif; ?></td>
                                    <td><?php echo $p['no_kendaraan'] ?: '-'; ?></td>
                                    <td>
                                        <?php
                                        $status = $p['status'];
                                        if($status == 'dikirim') echo '<span class="badge-status badge-dikirim">📦 Dikirim</span>';
                                        elseif($status == 'dalam_perjalanan') echo '<span class="badge-status badge-dalam_perjalanan">🚚 Di Jalan</span>';
                                        elseif($status == 'hampir_sampai') echo '<span class="badge-status badge-hampir_sampai">📍 Hampir Tiba</span>';
                                        elseif($status == 'sampai') echo '<span class="badge-status badge-sampai">✅ Sampai</span>';
                                        else echo '<span class="badge-status badge-pending">⏳ Pending</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($p['foto_lokasi_awal'])): ?>
                                            <img src="<?php echo $p['foto_lokasi_awal']; ?>" class="foto-thumb" onclick="showFoto('<?php echo $p['foto_lokasi_awal']; ?>', 'Foto Lokasi Awal')">
                                        <?php else: ?> - <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($p['foto_lokasi_tujuan'])): ?>
                                            <img src="<?php echo $p['foto_lokasi_tujuan']; ?>" class="foto-thumb" onclick="showFoto('<?php echo $p['foto_lokasi_tujuan']; ?>', 'Foto Bukti Tiba')">
                                        <?php elseif($status == 'hampir_sampai'): ?>
                                            <button class="btn-upload-tiba btn-sm" onclick="uploadBuktiTiba(<?php echo $p['id']; ?>)">
                                                <i class="bi bi-camera"></i> Upload
                                            </button>
                                        <?php else: ?> - <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info rounded-3 mb-1" onclick="lihatDetail(<?php echo $p['id']; ?>)"><i class="bi bi-eye"></i> Detail</button>
                                        <?php if($status == 'pending' || $status == 'dikirim'): ?>
                                            <button class="btn-validate rounded-3 mb-1" onclick="validateDelivery(<?php echo $p['id']; ?>)">
                                                <i class="bi bi-check-circle"></i> Validasi Jalan
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-track rounded-3 mb-1" onclick="showTrackingDetail(<?php echo $p['id']; ?>)">
                                                <i class="bi bi-geo-alt"></i> Map
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ========================================================================= -->
<!-- MODAL INFO DETAIL LENGKAP PENGIRIMAN                                      -->
<!-- ========================================================================= -->
<div class="modal fade" id="detailModalLengkap" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header bg-dark text-white pt-4 px-4 pb-3 border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-text me-2"></i> Detail Informasi Pengiriman</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="detailModalBody">
                <!-- Ajax Content Dimuat Disini -->
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL TAMBAH PENGIRIMAN (ADMIN) - DENGAN FITUR HARGA & SUBTOTAL           -->
<!-- ========================================================================= -->
<div class="modal fade" id="tambahPengirimanModal" tabindex="-1">
    <div class="modal-dialog modal-xl"> 
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header-custom pt-4 px-4 pb-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i> Buat Pengiriman Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="tambah.php" method="POST">
                <div class="modal-body bg-light" style="padding: 25px;">
                    <div class="row g-4">
                        <div class="col-md-4 border-end border-2 border-white pe-md-4">
                            <h6 class="fw-bold text-secondary border-bottom pb-2 mb-3"><i class="bi bi-info-circle me-1"></i> Data Pengiriman</h6>
                            <div class="mb-3">
                                <label class="form-label-custom text-muted">No. Pengiriman</label>
                                <input type="text" name="no_pengiriman" class="form-control form-control-sm bg-white" value="TRK<?php echo date('YmdHis'); ?>" required readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label-custom text-muted">Tanggal Pengiriman</label>
                                <input type="date" name="tanggal_pengiriman" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label-custom fw-bold text-dark">Proyek Tujuan</label>
                                <select name="proyek_id" class="form-select form-select-sm border-primary" required>
                                    <option value="" selected disabled>-- Pilih Proyek Tujuan --</option>
                                    <?php foreach($proyek as $pr): ?>
                                    <option value="<?php echo $pr['id']; ?>"><?php echo $pr['nama_proyek']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3 mt-4 pt-3 border-top">
                                <label class="form-label-custom fw-bold text-danger"><i class="bi bi-building me-1"></i> Warehouse Asal</label>
                                <select name="warehouse_id" id="warehouseSelect" class="form-select border-danger shadow-sm" required>
                                    <option value="" selected disabled>Pilih Warehouse (Gudang)</option>
                                    <?php foreach($warehouse as $w): ?>
                                    <option value="<?php echo $w['id']; ?>"><?php echo $w['nama_warehouse']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted" style="font-size: 0.75rem;">Pilih gudang untuk memunculkan daftar stok barang.</small>
                            </div>
                        </div>

                        <div class="col-md-8 ps-md-4">
                            <h6 class="fw-bold text-secondary border-bottom pb-2 mb-3"><i class="bi bi-box-seam me-1"></i> Detail Muatan & Harga Barang</h6>
                            <div class="table-responsive bg-white rounded shadow-sm border mb-3" style="min-height: 220px; max-height: 350px;">
                                <table class="table table-hover table-sm mb-0" id="tableBarang">
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th class="ps-3 text-secondary py-2">Pilih Item & Stok Sisa</th>
                                            <th width="100" class="text-secondary text-center py-2">Jumlah</th>
                                            <th width="140" class="text-secondary text-end pe-3 py-2">Subtotal</th>
                                            <th width="50"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyBarang">
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-5">
                                                <i class="bi bi-arrow-left-circle fs-3 d-block text-light mb-2"></i>
                                                Silakan pilih <strong class="text-danger">Warehouse Asal</strong> terlebih dahulu.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="bg-dark text-white p-3 rounded-3 shadow-sm d-flex justify-content-between align-items-center mt-3 mb-3">
                                <span class="fw-bold small text-uppercase opacity-75">Total Nilai Muatan</span>
                                <h4 class="mb-0 fw-bold text-warning" id="grandTotalLabel">Rp 0</h4>
                            </div>

                            <button type="button" class="btn btn-outline-primary btn-sm w-100 fw-bold" id="btnTambahBarang" disabled>
                                <i class="bi bi-plus-lg"></i> Tambah Baris Barang
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0 px-4 py-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm"><i class="bi bi-save me-1"></i> Simpan & Buat Pengiriman</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL EDIT LOGISTIK (TAMPILAN BARU UNTUK SUPIR & LOKASI GUDANG)           -->
<!-- ========================================================================= -->
<div class="modal fade" id="editLogistikModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white;">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Isi Detail Pengiriman</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <!-- ACTION AJAX DITANGKAP OLEH JQUERY -->
            <form id="formEditLogistik" method="POST" enctype="multipart/form-data" action="/shankara_trackingbarang/pengiriman/update_logistik.php">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body" style="padding: 20px;">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Dropdown Sopir -->
                            <div class="mb-3">
                                <label class="form-label-custom">Nama Sopir <span class="text-danger">*</span></label>
                                <select name="sopir" id="edit_sopir" class="form-select form-control-custom border-warning" required>
                                    <option value="" disabled selected>-- Pilih Supir yang Tersedia --</option>
                                    <?php if(empty($daftar_sopir)): ?>
                                        <option value="" disabled>Belum ada supir di Warehouse ini</option>
                                    <?php else: ?>
                                        <?php foreach($daftar_sopir as $supir): ?>
                                        <option value="<?php echo htmlspecialchars($supir['nama_sopir'], ENT_QUOTES); ?>">
                                            <?php echo $supir['nama_sopir']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label-custom">No. Kendaraan <span class="text-danger">*</span></label>
                                <input type="text" name="no_kendaraan" id="edit_no_kendaraan" class="form-control-custom" placeholder="B 1234 XYZ" required>
                            </div>
                            
                            <!-- Lokasi Awal Otomatis Menggunakan Nama Gudang -->
                            <div class="mb-3">
                                <label class="form-label-custom">Lokasi Awal (Gudang Asal) <span class="text-danger">*</span></label>
                                <input type="text" name="lokasi_awal" id="edit_lokasi_awal" class="form-control-custom bg-light fw-bold text-primary" readonly required>
                                <small class="text-muted" style="font-size:0.7rem;">Lokasi otomatis menyesuaikan dengan data Warehouse.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label-custom">Lokasi Tujuan </label>
                                <input type="text" name="lokasi_tujuan" id="edit_lokasi_tujuan" class="form-control-custom" placeholder="Bekasi">
                            </div>
                            <div class="mb-3">
                                <label class="form-label-custom">Foto Sopir (Opsional)</label>
                                <input type="file" name="foto_sopir" class="form-control-custom" accept="image/*">
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <div class="alert alert-warning">
                        <i class="bi bi-camera"></i> <strong>Foto Lokasi Awal (Wajib)</strong><br>
                        Upload foto saat barang DIKIRIM DARI WAREHOUSE.
                    </div>
                    <div class="mb-3">
                        <label class="form-label-custom">Unggah Foto Lokasi Awal <span class="text-danger">*</span></label>
                        <input type="file" name="foto_lokasi_awal" class="form-control-custom" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold text-dark shadow-sm">Simpan & Kirim ke Driver</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// =====================================================
// SCRIPT: MENCEGAH FORM REDIRECT KE JSON PAGE (AJAX SUBMIT)
// =====================================================
$(document).ready(function() {
    $('#formEditLogistik').on('submit', function(e) {
        e.preventDefault(); // Menahan agar tidak pindah ke halaman putih JSON
        
        let submitBtn = $(this).find('button[type="submit"]');
        let originalText = submitBtn.html();
        submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...').prop('disabled', true);
        
        let formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                submitBtn.html(originalText).prop('disabled', false);
                
                if(response.success) {
                    $('#editLogistikModal').modal('hide');
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Data detail pengiriman berhasil disimpan.',
                        icon: 'success',
                        confirmButtonColor: '#3498db'
                    }).then(() => {
                        location.reload(); // Refresh halaman agar tabel terupdate
                    });
                } else {
                    Swal.fire('Gagal!', response.message || 'Terjadi kesalahan saat menyimpan data.', 'error');
                }
            },
            error: function() {
                submitBtn.html(originalText).prop('disabled', false);
                Swal.fire('Error!', 'Gagal terhubung ke server. Periksa koneksi Anda.', 'error');
            }
        });
    });
});

// =====================================================
// SCRIPT: MODAL INFO DETAIL LENGKAP PENGIRIMAN
// =====================================================
function lihatDetail(id) {
    $('#detailModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Sinkronisasi data...</p></div>');
    
    // Gunakan Vanilla JS agar modal tampil sempurna dan tidak bentrok
    var detailModal = new bootstrap.Modal(document.getElementById('detailModalLengkap'));
    detailModal.show();
    
    $.ajax({
        url: '/shankara_trackingbarang/api/get_tracking.php?id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if(res.success && res.pengiriman) {
                let p = res.pengiriman;
                let barang = res.detail_barang || [];
                let statusLabel = p.status.toUpperCase();
                
                let barangHtml = '';
                barang.forEach(item => {
                    barangHtml += `
                        <div class="d-flex justify-content-between align-items-center p-2 mb-2 bg-white rounded border">
                            <span class="fw-bold text-dark small">${item.nama_barang}</span>
                            <span class="badge bg-primary rounded-pill px-3">${item.jumlah} ${item.satuan}</span>
                        </div>`;
                });

                let fotoSopir = p.foto_sopir ? `<img src="${p.foto_sopir}" class="img-fluid rounded border shadow-sm" style="height:100px; object-fit:cover; cursor:pointer" onclick="showFoto('${p.foto_sopir}','Foto Sopir')">` : '<div class="p-3 border rounded bg-white text-muted small">N/A</div>';
                let fotoAwal = p.foto_lokasi_awal ? `<img src="${p.foto_lokasi_awal}" class="img-fluid rounded border shadow-sm" style="height:100px; object-fit:cover; cursor:pointer" onclick="showFoto('${p.foto_lokasi_awal}','Lokasi Awal')">` : '<div class="p-3 border rounded bg-white text-muted small">N/A</div>';
                let fotoTiba = p.foto_lokasi_tujuan ? `<img src="${p.foto_lokasi_tujuan}" class="img-fluid rounded border shadow-sm" style="height:100px; object-fit:cover; cursor:pointer" onclick="showFoto('${p.foto_lokasi_tujuan}','Bukti Tiba')">` : '<div class="p-3 border rounded bg-white text-muted small">N/A</div>';

                let html = `
                    <div class="p-4 bg-light">
                        <div class="row g-4">
                            <div class="col-md-6 border-end">
                                <h6 class="text-uppercase small fw-bold text-muted mb-3"><i class="bi bi-info-circle me-1"></i> Data Operasional</h6>
                                <table class="table table-sm table-borderless small">
                                    <tr><td width="35%" class="text-muted">No. Resi</td><td>: <strong>${p.no_pengiriman}</strong></td></tr>
                                    <tr><td class="text-muted">Proyek</td><td>: ${p.nama_proyek}</td></tr>
                                    <tr><td class="text-muted">Gudang</td><td>: ${p.nama_warehouse}</td></tr>
                                    <tr><td class="text-muted">Tgl Kirim</td><td>: ${p.tanggal_pengiriman}</td></tr>
                                    <tr><td class="text-muted">Sopir</td><td>: ${p.sopir || '-'} <span class="text-primary fw-bold">(${p.no_kendaraan || '-'})</span></td></tr>
                                    <tr><td class="text-muted">Status Info</td><td>: <span class="badge bg-warning text-dark">${statusLabel}</span></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-uppercase small fw-bold text-muted mb-3"><i class="bi bi-box-seam me-1"></i> Daftar Muatan</h6>
                                <div style="max-height: 180px; overflow-y: auto;" class="pe-2">
                                    ${barangHtml || '<div class="alert alert-light text-center small border">Data barang tidak ada.</div>'}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-4 border-top">
                        <h6 class="text-uppercase small fw-bold text-muted mb-3 text-center"><i class="bi bi-camera me-1"></i> Dokumentasi Lapangan</h6>
                        <div class="row text-center g-3">
                            <div class="col-4">
                                <div class="small fw-bold text-muted mb-2">Kurir</div>
                                ${fotoSopir}
                            </div>
                            <div class="col-4">
                                <div class="small fw-bold text-muted mb-2">Lokasi Awal</div>
                                ${fotoAwal}
                            </div>
                            <div class="col-4">
                                <div class="small fw-bold text-muted mb-2">Bukti Tiba</div>
                                ${fotoTiba}
                            </div>
                        </div>
                    </div>`;
                $('#detailModalBody').html(html);
            }
        },
        error: function() {
            $('#detailModalBody').html('<div class="alert alert-danger m-4">Gagal menghubungkan ke database server.</div>');
        }
    });
}

// =====================================================
// SCRIPT: LOGIKA STOK BARANG DAN HARGA DINAMIS
// =====================================================
const stokData = <?php echo json_encode($stok_warehouse); ?>;

$('#warehouseSelect').on('change', function() {
    let wid = $(this).val();
    $('#tbodyBarang').empty(); 
    updateGrandTotal(); 
    
    if(wid) {
        $('#btnTambahBarang').prop('disabled', false);
        tambahBarisBarang(wid); 
    } else {
        $('#btnTambahBarang').prop('disabled', true);
        $('#tbodyBarang').html('<tr><td colspan="4" class="text-center text-muted py-4">Pilih Warehouse terlebih dahulu</td></tr>');
    }
});

$('#btnTambahBarang').on('click', function() {
    let wid = $('#warehouseSelect').val();
    tambahBarisBarang(wid);
});

function tambahBarisBarang(warehouse_id) {
    let options = '<option value="" selected disabled>-- Pilih Item --</option>';
    let availableItems = stokData.filter(item => item.warehouse_id == warehouse_id);
    
    if(availableItems.length === 0) {
        options = '<option value="" disabled>Stok Habis / Kosong di Gudang ini</option>';
    } else {
        availableItems.forEach(item => {
            let formattedHarga = parseFloat(item.harga).toLocaleString('id-ID');
            options += `<option value="${item.barang_id}" data-max="${item.jumlah}" data-harga="${item.harga}">
                ${item.nama_barang} (Stok: ${item.jumlah} ${item.satuan} - Rp ${formattedHarga})
            </option>`;
        });
    }

    let row = `
        <tr class="align-middle border-bottom">
            <td class="ps-3 pt-3 pb-2">
                <select name="barang_id[]" class="form-select form-select-sm border-primary select-barang" required>
                    ${options}
                </select>
                <small class="text-muted error-stok d-none mt-1 d-block" style="font-size: 0.65rem;">
                    Sisa Stok: <span class="max-val fw-bold text-dark">0</span> | @ Harga: Rp <span class="price-val fw-bold text-success">0</span>
                </small>
            </td>
            <td class="pt-3 pb-2 text-center">
                <input type="number" name="jumlah[]" class="form-control form-control-sm text-center input-jumlah fw-bold" min="1" required placeholder="0" readonly>
            </td>
            <td class="pt-3 pb-2 text-end pe-3">
                <span class="small fw-bold subtotal-label text-primary">Rp 0</span>
            </td>
            <td class="pt-3 pb-2 text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-barang" title="Hapus Item"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
    `;
    $('#tbodyBarang').append(row);
}

$(document).on('click', '.btn-hapus-barang', function() {
    $(this).closest('tr').remove();
    updateGrandTotal(); 
    if($('#tbodyBarang tr').length === 0) {
        $('#tbodyBarang').html('<tr><td colspan="4" class="text-center text-muted py-4"><i class="bi bi-exclamation-circle text-warning mb-2 d-block"></i> Belum ada item yang ditambahkan.</td></tr>');
    }
});

$(document).on('change', '.select-barang', function() {
    let selected = $(this).find(':selected');
    let maxStok = selected.data('max');
    let harga = selected.data('harga') || 0;
    
    let row = $(this).closest('tr');
    let inputJumlah = row.find('.input-jumlah');
    let errorMsg = row.find('.error-stok');
    
    if(maxStok !== undefined) {
        inputJumlah.prop('readonly', false); 
        inputJumlah.attr('max', maxStok);
        inputJumlah.val(''); 
        
        errorMsg.removeClass('d-none');
        errorMsg.find('.max-val').text(maxStok);
        errorMsg.find('.price-val').text(parseFloat(harga).toLocaleString('id-ID'));
        
        row.find('.subtotal-label').text('Rp 0'); 
        updateGrandTotal();
    } else {
        inputJumlah.prop('readonly', true);
    }
});

$(document).on('input', '.input-jumlah', function() {
    let row = $(this).closest('tr');
    let harga = parseFloat(row.find('.select-barang :selected').data('harga')) || 0;
    let jumlah = parseInt($(this).val()) || 0;
    let max = parseInt($(this).attr('max'));
    
    if (jumlah > max) {
        jumlah = max;
        $(this).val(max);
        Swal.fire({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            icon: 'warning', title: `Stok tidak cukup! Maksimal ${max}`
        });
    }
    
    let subtotal = harga * jumlah;
    row.find('.subtotal-label').text('Rp ' + subtotal.toLocaleString('id-ID'));
    updateGrandTotal();
});

function updateGrandTotal() {
    let grandTotal = 0;
    $('.input-jumlah').each(function() {
        let row = $(this).closest('tr');
        let harga = parseFloat(row.find('.select-barang :selected').data('harga')) || 0;
        let jumlah = parseInt($(this).val()) || 0;
        grandTotal += (harga * jumlah);
    });
    $('#grandTotalLabel').text('Rp ' + grandTotal.toLocaleString('id-ID'));
}

// =====================================================
// SCRIPT: ADMIN & LOGISTIK ACTION
// =====================================================
function approveToLogistik(id) {
    Swal.fire({
        title: 'Kirim ke Logistik?',
        text: 'Pengiriman akan dikirim ke Logistik untuk diisi detailnya',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Kirim',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/shankara_trackingbarang/api/approve_pengiriman.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id=${id}`
            }).then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Berhasil', 'Pengiriman telah dikirim ke Logistik', 'success').then(()=>location.reload());
                }
            });
        }
    });
}

function openEditLogistik(id, namaWarehouse) {
    $.ajax({
        url: '/shankara_trackingbarang/api/get_pengiriman.php?id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if(data.success) {
                $('#edit_id').val(data.pengiriman.id);
                // Reset select dan input
                $('#edit_sopir').val(data.pengiriman.sopir || '');
                $('#edit_no_kendaraan').val(data.pengiriman.no_kendaraan || '');
                // OTOMATIS MENGISI NAMA GUDANG KE DALAM INPUT LOKASI AWAL
                $('#edit_lokasi_awal').val(namaWarehouse); 
                $('#edit_lokasi_tujuan').val(data.pengiriman.lokasi_tujuan || '');
                
                $('#editLogistikModal').modal('show');
            }
        }
    });
}

function validateDelivery(id) {
    Swal.fire({
        title: 'Validasi Pengiriman',
        text: 'Apakah Anda siap memproses pengiriman ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Validasi',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({title: 'Memproses...', text: 'Harap tunggu', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
            let now = new Date();
            let formattedDate = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0') + ' ' + String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ':' + String(now.getSeconds()).padStart(2, '0');
            
            fetch('/shankara_trackingbarang/api/update_lokasi.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `pengiriman_id=${id}&status=dikirim&last_status_update=${formattedDate}`
            }).then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Berhasil!', 'Status pengiriman berubah', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal', 'Terjadi kesalahan', 'error');
                }
            });
        }
    });
}

function uploadBuktiTiba(id) { window.location.href = '/shankara_trackingbarang/pengiriman/upload_foto_tiba.php?id=' + id; }
function showFoto(url, title) { Swal.fire({ title: title, imageUrl: url, imageWidth: 600, imageHeight: 'auto', confirmButtonText: 'Tutup' }); }
function showTrackingDetail(id) { window.location.href = '/shankara_trackingbarang/api/get_tracking_detail.php?id=' + id; }
</script>
</body>
</html>