<?php
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$today = date('Y-m-d');

// Cek apakah sudah absen hari ini
$sudahAbsen = fetchOne(query("SELECT * FROM absensi WHERE user_id = $user_id AND tanggal = '$today'"));

// Proses absensi masuk
if(isset($_POST['absen_masuk'])) {
    $jam_masuk = date('H:i:s');
    $status = $_POST['status'];
    $keterangan = $_POST['keterangan'] ?? '';
    
    $sql = "INSERT INTO absensi (user_id, tanggal, jam_masuk, status, keterangan) 
            VALUES ($user_id, '$today', '$jam_masuk', '$status', '$keterangan')";
    
    if(query($sql)) {
        query("INSERT INTO riwayat_aktivitas (user_id, aktivitas, tabel_terkait, record_id) 
               VALUES ($user_id, 'Melakukan absensi masuk', 'absensi', " . getLastId() . ")");
        echo "<script>alert('Absensi masuk berhasil!'); window.location.href='index.php';</script>";
    }
}

// Proses absensi keluar
if(isset($_POST['absen_keluar'])) {
    $jam_keluar = date('H:i:s');
    $sql = "UPDATE absensi SET jam_keluar = '$jam_keluar' WHERE user_id = $user_id AND tanggal = '$today'";
    
    if(query($sql)) {
        query("INSERT INTO riwayat_aktivitas (user_id, aktivitas, tabel_terkait, record_id) 
               VALUES ($user_id, 'Melakukan absensi keluar', 'absensi', 0)");
        echo "<script>alert('Absensi keluar berhasil!'); window.location.href='index.php';</script>";
    }
}

// Ambil data absensi untuk ditampilkan
if($user_role == 'admin' || $user_role == 'owner') {
    // Admin dan owner bisa lihat semua absensi
    $absensiHarian = fetchAll(query("
        SELECT a.*, u.nama_lengkap, u.role 
        FROM absensi a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.tanggal = '$today' 
        ORDER BY a.jam_masuk DESC
    "));
    
    $riwayatAbsensi = fetchAll(query("
        SELECT a.*, u.nama_lengkap, u.role 
        FROM absensi a 
        JOIN users u ON a.user_id = u.id 
        ORDER BY a.tanggal DESC, a.jam_masuk DESC 
        LIMIT 50
    "));
} else {
    // Karyawan biasa hanya lihat absensinya sendiri
    $absensiHarian = fetchAll(query("
        SELECT a.*, u.nama_lengkap, u.role 
        FROM absensi a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.tanggal = '$today' AND a.user_id = $user_id
    "));
    
    $riwayatAbsensi = fetchAll(query("
        SELECT * FROM absensi 
        WHERE user_id = $user_id 
        ORDER BY tanggal DESC, jam_masuk DESC 
        LIMIT 30
    "));
}

// Statistik absensi bulan ini
$bulanIni = date('Y-m-01');
$statistik = fetchOne(query("
    SELECT 
        COUNT(*) as total_hadir,
        SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha
    FROM absensi 
    WHERE user_id = $user_id AND tanggal >= '$bulanIni'
"));
?>

<style>
.absensi-card {
    background: linear-gradient(135deg, #4361ee, #7209b7);
    border-radius: 20px;
    padding: 25px;
    color: white;
    margin-bottom: 20px;
}

.absensi-card h3 {
    font-size: 2rem;
    font-weight: 800;
    margin: 10px 0 0;
}

.status-badge {
    padding: 8px 16px;
    border-radius: 30px;
    font-weight: 600;
    font-size: 0.85rem;
}

.status-hadir { background: #06d6a0; color: white; }
.status-izin { background: #ffd166; color: #1a1a2e; }
.status-sakit { background: #4cc9f0; color: white; }
.status-alpha { background: #ef476f; color: white; }

.btn-absen {
    padding: 15px 30px;
    font-size: 1.1rem;
    font-weight: 700;
    border-radius: 15px;
    transition: all 0.3s ease;
}

.btn-absen-masuk {
    background: linear-gradient(135deg, #06d6a0, #05b386);
    color: white;
}

.btn-absen-keluar {
    background: linear-gradient(135deg, #ef476f, #d93c62);
    color: white;
}

.btn-absen:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

.stat-box {
    background: white;
    border-radius: 15px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.stat-box h4 {
    font-size: 1.8rem;
    font-weight: 800;
    margin: 0;
    color: #1a1a2e;
}

.stat-box p {
    margin: 5px 0 0;
    color: #6c757d;
    font-size: 0.8rem;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-calendar-check"></i> Absensi Karyawan</h2>
    <span class="badge bg-primary"><?php echo date('l, d F Y'); ?></span>
</div>

<!-- Status Absensi Hari Ini -->
<div class="absensi-card">
    <div class="row align-items-center">
        <div class="col-md-6">
            <i class="bi bi-person-circle" style="font-size: 48px;"></i>
            <h3><?php echo $_SESSION['nama_lengkap']; ?></h3>
            <p class="mb-0"><?php echo ucfirst($_SESSION['role']); ?></p>
        </div>
        <div class="col-md-6 text-end">
            <?php if($sudahAbsen && $sudahAbsen['jam_masuk'] && !$sudahAbsen['jam_keluar']): ?>
                <div class="mb-3">
                    <span class="status-badge status-<?php echo $sudahAbsen['status']; ?>">
                        <i class="bi bi-check-circle"></i> Sudah Absen Masuk: <?php echo $sudahAbsen['jam_masuk']; ?>
                    </span>
                    <br>
                    <small>Status: <?php echo ucfirst($sudahAbsen['status']); ?></small>
                </div>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="absen_keluar" class="btn btn-absen btn-absen-keluar" onclick="return confirm('Absen keluar sekarang?')">
                        <i class="bi bi-box-arrow-right"></i> Absen Keluar
                    </button>
                </form>
            <?php elseif($sudahAbsen && $sudahAbsen['jam_masuk'] && $sudahAbsen['jam_keluar']): ?>
                <div class="mb-3">
                    <span class="status-badge status-<?php echo $sudahAbsen['status']; ?>">
                        <i class="bi bi-check-circle-fill"></i> Selesai Bekerja
                    </span>
                    <br>
                    <small>Masuk: <?php echo $sudahAbsen['jam_masuk']; ?> | Keluar: <?php echo $sudahAbsen['jam_keluar']; ?></small>
                </div>
                <button class="btn btn-secondary" disabled>
                    <i class="bi bi-check2-circle"></i> Sudah Absen Hari Ini
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-absen btn-absen-masuk" data-bs-toggle="modal" data-bs-target="#absenModal">
                    <i class="bi bi-fingerprint"></i> Absen Masuk
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Statistik Bulanan -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-box">
            <h4><?php echo $statistik['total_hadir'] ?? 0; ?></h4>
            <p>Total Hari</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-box">
            <h4 class="text-success"><?php echo $statistik['hadir'] ?? 0; ?></h4>
            <p>Hadir</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-box">
            <h4 class="text-warning"><?php echo $statistik['izin'] ?? 0; ?></h4>
            <p>Izin</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-box">
            <h4 class="text-danger"><?php echo $statistik['alpha'] ?? 0; ?></h4>
            <p>Alpha</p>
        </div>
    </div>
</div>

<!-- Daftar Absensi Hari Ini -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="bi bi-people"></i> Absensi Hari Ini</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Role</th>
                        <th>Jam Masuk</th>
                        <th>Jam Keluar</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($absensiHarian)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Belum ada absensi hari ini</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($absensiHarian as $a): ?>
                        <tr>
                            <td><?php echo $a['nama_lengkap']; ?></td>
                            <td><?php echo ucfirst($a['role']); ?></td>
                            <td><?php echo $a['jam_masuk'] ?? '-'; ?></td>
                            <td><?php echo $a['jam_keluar'] ?? '-'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $a['status']; ?>" style="padding: 4px 10px; font-size: 0.7rem;">
                                    <?php echo ucfirst($a['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $a['keterangan'] ?? '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Riwayat Absensi -->
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-clock-history"></i> Riwayat Absensi</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <?php if($user_role == 'admin' || $user_role == 'owner'): ?>
                        <th>Nama</th>
                        <?php endif; ?>
                        <th>Jam Masuk</th>
                        <th>Jam Keluar</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($riwayatAbsensi)): ?>
                        <tr>
                            <td colspan="<?php echo ($user_role == 'admin' || $user_role == 'owner') ? '6' : '5'; ?>" class="text-center">Belum ada riwayat absensi</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($riwayatAbsensi as $r): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($r['tanggal'])); ?></td>
                            <?php if($user_role == 'admin' || $user_role == 'owner'): ?>
                            <td><?php echo $r['nama_lengkap']; ?></td>
                            <?php endif; ?>
                            <td><?php echo $r['jam_masuk'] ?? '-'; ?></td>
                            <td><?php echo $r['jam_keluar'] ?? '-'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $r['status']; ?>" style="padding: 4px 10px; font-size: 0.7rem;">
                                    <?php echo ucfirst($r['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $r['keterangan'] ?? '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Absen Masuk -->
<div class="modal fade" id="absenModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-fingerprint"></i> Absen Masuk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status Kehadiran</label>
                        <select name="status" class="form-control" required>
                            <option value="hadir">Hadir</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan (opsional)</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Isi keterangan jika izin atau sakit..."></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Waktu absen: <?php echo date('H:i:s'); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="absen_masuk" class="btn btn-primary">Absen Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>