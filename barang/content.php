<?php
// Get all barang dengan perhitungan nilai stok dan rincian per warehouse (Format RAW untuk JS)
$barang = fetchAll(query("
    SELECT b.*, 
           COALESCE((SELECT SUM(jumlah) FROM stok WHERE barang_id = b.id), 0) as total_stok,
           (b.harga * COALESCE((SELECT SUM(jumlah) FROM stok WHERE barang_id = b.id), 0)) as total_nilai_item,
           (
               SELECT GROUP_CONCAT(CONCAT(w.nama_warehouse, ':', s.jumlah, ' ', b.satuan) SEPARATOR '|')
               FROM stok s
               JOIN warehouse w ON s.warehouse_id = w.id
               WHERE s.barang_id = b.id AND s.jumlah > 0
           ) as rincian_stok_raw
    FROM barang b 
    ORDER BY b.created_at DESC
"));

// Menghitung total nilai seluruh aset di gudang
$totalAsetSeluruhnya = 0;
foreach($barang as $item) {
    $totalAsetSeluruhnya += $item['total_nilai_item'];
}

// Get warehouses for stock management
$warehouse = fetchAll(query("SELECT * FROM warehouse"));
$stokData = fetchAll(query("
    SELECT s.*, b.nama_barang, b.satuan, w.nama_warehouse 
    FROM stok s 
    JOIN barang b ON s.barang_id = b.id 
    JOIN warehouse w ON s.warehouse_id = w.id
    ORDER BY w.nama_warehouse ASC
"));

// Fetch ALL Stok mentah untuk ditarik ke JavaScript (Validasi Modal Update)
$all_stok_js = fetchAll(query("SELECT barang_id, warehouse_id, jumlah FROM stok"));
?>

<!-- Ringkasan Statistik Nilai Aset -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="border-radius: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase small fw-bold opacity-75">Total Nilai Aset Seluruhnya</h6>
                        <h3 class="fw-bold mb-0">Rp <?php echo number_format($totalAsetSeluruhnya, 0, ',', '.'); ?></h3>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between mb-3 align-items-center">
    <h2 class="fw-bold text-dark">Manajemen Barang</h2>
    <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#tambahBarangModal" style="border-radius: 10px;">
        <i class="bi bi-plus-circle me-2"></i> Tambah Barang Baru
    </button>
</div>

<ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
    <li class="nav-item">
        <a class="nav-link active px-4 fw-bold" data-bs-toggle="pill" href="#daftarBarang">Daftar Barang</a>
    </li>
    <li class="nav-item">
        <a class="nav-link px-4 fw-bold" data-bs-toggle="pill" href="#stokBarang">Detail Stok Gudang</a>
    </li>
</ul>

<div class="tab-content">
    <!-- TAB 1: DAFTAR BARANG -->
    <div class="tab-pane fade show active" id="daftarBarang">
        <div class="card border-0 shadow-sm" style="border-radius: 15px;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">KODE</th>
                                <th>NAMA BARANG</th>
                                <th>HARGA SATUAN</th>
                                <th>TOTAL STOK</th>
                                <th>RINCIAN WAREHOUSE</th>
                                <th>TOTAL NILAI</th>
                                <th class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($barang as $b): ?>
                            <tr>
                                <td class="ps-4"><strong><?php echo $b['kode_barang']; ?></strong></td>
                                <td>
                                    <div class="fw-bold"><?php echo $b['nama_barang']; ?></div>
                                    <small class="text-muted"><?php echo $b['satuan']; ?></small>
                                </td>
                                <td>Rp <?php echo number_format($b['harga'], 0, ',', '.'); ?></td>
                                <td class="fw-bold fs-6 text-success"><?php echo number_format($b['total_stok'], 0, ',', '.'); ?></td>
                                
                                <!-- TAMPILAN TOMBOL MODAL RINCIAN (PENGGANTI DROPDOWN YANG BOCOR) -->
                                <td>
                                    <?php if(!empty($b['rincian_stok_raw'])): ?>
                                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm fw-bold" 
                                                onclick="bukaRincianGudang('<?php echo htmlspecialchars($b['nama_barang'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($b['rincian_stok_raw'], ENT_QUOTES); ?>')">
                                            <i class="bi bi-box-seam me-1"></i> Cek Gudang
                                        </button>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-3 py-2 rounded-pill"><i class="bi bi-x-circle me-1"></i>Stok Kosong</span>
                                    <?php endif; ?>
                                </td>
                                <!-- END TAMPILAN TOMBOL MODAL -->

                                <td class="text-primary fw-bold">
                                    Rp <?php echo number_format($b['total_nilai_item'], 0, ',', '.'); ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-danger" onclick="hapusBarang(<?php echo $b['id']; ?>)" style="border-radius: 8px;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                 </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- TAB 2: STOK BARANG PER WAREHOUSE -->
    <div class="tab-pane fade" id="stokBarang">
        <div class="card border-0 shadow-sm" style="border-radius: 15px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between mb-4">
                    <h5 class="fw-bold">Rincian Stok Tiap Gudang</h5>
                    <button class="btn btn-success px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#updateStokModal" style="border-radius: 10px;">
                        <i class="bi bi-pencil-square me-2"></i> Update Stok
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>GUDANG / WAREHOUSE</th>
                                <th>NAMA BARANG</th>
                                <th class="text-center">JUMLAH STOK</th>
                                <th>TERAKHIR DIPERBARUI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($stokData as $s): ?>
                            <tr>
                                <td class="fw-bold text-primary"><i class="bi bi-building me-2"></i><?php echo $s['nama_warehouse']; ?></td>
                                <td><?php echo $s['nama_barang']; ?></td>
                                <td class="text-center fw-bold text-success">
                                    <?php echo number_format($s['jumlah'], 0, ',', '.'); ?> <?php echo $s['satuan']; ?>
                                </td>
                                <td><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($s['updated_at'])); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL RINCIAN GUDANG (PENGGANTI DROPDOWN YANG BOCOR)    -->
<!-- ======================================================= -->
<div class="modal fade" id="rincianGudangModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h6 class="modal-title fw-bold" id="rincianGudangTitle">Rincian Stok</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="rincianGudangBody">
                <!-- Konten disisipkan via JS -->
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-sm btn-secondary w-100 rounded-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Barang -->
<div class="modal fade" id="tambahBarangModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius: 15px;">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold">Tambah Data Barang</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="tambah.php" method="POST">
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Kode Barang</label>
                            <input type="text" name="kode_barang" class="form-control" placeholder="Contoh: K03" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Satuan</label>
                            <input type="text" name="satuan" class="form-control" placeholder="kg, m3, unit" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Barang</label>
                        <input type="text" name="nama_barang" class="form-control" placeholder="Nama lengkap barang" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Harga Satuan (Rp)</label>
                        <input type="number" name="harga" class="form-control" placeholder="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Spesifikasi</label>
                        <textarea name="spesifikasi" class="form-control" rows="3" placeholder="Keterangan detail barang..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan Barang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Update Stok (DENGAN LOGIKA VALIDASI STOK KOSONG) -->
<div class="modal fade" id="updateStokModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius: 15px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                <h5 class="modal-title fw-bold"><i class="bi bi-box-seam me-2"></i>Update Stok Inventaris</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="update_stok.php" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Barang</label>
                        <select name="barang_id" id="selectBarangUpdate" class="form-select shadow-sm" required>
                            <option value="" selected disabled>-- Pilih Barang --</option>
                            <?php foreach($barang as $b): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo $b['nama_barang']; ?> (<?php echo $b['kode_barang']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Lokasi Gudang</label>
                        <select name="warehouse_id" id="selectWarehouseUpdate" class="form-select shadow-sm" required>
                            <option value="" selected disabled>-- Pilih Warehouse --</option>
                            <?php foreach($warehouse as $w): ?>
                            <option value="<?php echo $w['id']; ?>"><?php echo $w['nama_warehouse']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Indikator Stok Saat ini -->
                    <div class="p-3 mb-3 rounded shadow-sm border" style="background-color: #f8f9fa; border-left: 4px solid #10b981 !important;">
                        <span id="currentStokLabel" class="text-dark small">Pilih barang dan warehouse untuk melacak stok.</span>
                    </div>

                    <!-- Peringatan Jika Stok Kosong -->
                    <div id="alertStokKosong" class="alert alert-warning small d-none py-2 mb-3 shadow-sm border-warning">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Stok tidak tersedia di gudang ini. Anda harus menginputkan <strong>Stok Baru</strong>.
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Jumlah Input</label>
                            <input type="number" name="jumlah" id="inputJumlahUpdate" class="form-control shadow-sm fw-bold text-center fs-5" required min="1" placeholder="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Tipe Aksi</label>
                            <select name="tipe" id="selectAksiUpdate" class="form-select shadow-sm fw-bold">
                                <option value="">-- Menunggu Pilihan --</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm">Simpan Update Stok</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ========================================================
// FUNGSI MODAL RINCIAN GUDANG (PENGGANTI DROPDOWN BOCOR)
// ========================================================
function bukaRincianGudang(namaBarang, rincianRaw) {
    // Set Judul Modal
    $('#rincianGudangTitle').html(`<i class="bi bi-box-seam me-2"></i>Stok: ${namaBarang}`);
    
    // Proses Data Raw (Warehouse1:100 kg|Warehouse2:50 kg)
    let htmlContent = '';
    if(rincianRaw) {
        let items = rincianRaw.split('|');
        items.forEach(item => {
            let parts = item.split(':');
            if(parts.length === 2) {
                htmlContent += `
                <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-white">
                    <span class="fw-bold text-secondary small"><i class="bi bi-building me-2"></i>${parts[0]}</span>
                    <span class="badge bg-primary rounded-pill fs-6 px-3 py-2 shadow-sm">${parts[1]}</span>
                </div>`;
            }
        });
    }
    
    $('#rincianGudangBody').html(htmlContent);
    
    // Tampilkan Modal via Bootstrap 5
    var rincianModal = new bootstrap.Modal(document.getElementById('rincianGudangModal'));
    rincianModal.show();
}

// ========================================================
// FUNGSI UPDATE STOK (VALIDASI STOK KOSONG)
// ========================================================
const allStok = <?php echo json_encode($all_stok_js); ?>;

function checkStok() {
    let bId = $('#selectBarangUpdate').val();
    let wId = $('#selectWarehouseUpdate').val();
    
    if(bId && wId) {
        let match = allStok.find(s => s.barang_id == bId && s.warehouse_id == wId);
        let current = match ? parseInt(match.jumlah) : 0;
        
        $('#currentStokLabel').html(`Sisa stok saat ini di gudang terpilih: <strong class="fs-5 text-primary ms-2">${current}</strong>`);
        
        if(current === 0) {
            $('#selectAksiUpdate').html('<option value="set">Tambah Stok Baru (Awal)</option>');
            $('#alertStokKosong').removeClass('d-none');
        } else {
            $('#selectAksiUpdate').html(`
                <option value="tambah">Tambah Jumlah Stok (+)</option>
                <option value="kurang">Kurangi Jumlah Stok (-)</option>
                <option value="set">Ubah Jadi Jumlah Mutlak (=)</option>
            `);
            $('#alertStokKosong').addClass('d-none');
        }
        
        updateMaxLimit(current);
    } else {
        $('#currentStokLabel').html(`Pilih barang dan warehouse untuk melacak stok.`);
        $('#alertStokKosong').addClass('d-none');
        $('#selectAksiUpdate').html('<option value="">-- Menunggu Pilihan --</option>');
    }
}

function updateMaxLimit(currentStok) {
    let aksi = $('#selectAksiUpdate').val();
    if(aksi === 'kurang') {
        $('#inputJumlahUpdate').attr('max', currentStok);
    } else {
        $('#inputJumlahUpdate').removeAttr('max');
    }
}

$('#selectBarangUpdate, #selectWarehouseUpdate').on('change', checkStok);

$('#selectAksiUpdate').on('change', function() {
    let bId = $('#selectBarangUpdate').val();
    let wId = $('#selectWarehouseUpdate').val();
    let match = allStok.find(s => s.barang_id == bId && s.warehouse_id == wId);
    let current = match ? parseInt(match.jumlah) : 0;
    updateMaxLimit(current);
});

$(document).on('input', '#inputJumlahUpdate', function() {
    let aksi = $('#selectAksiUpdate').val();
    let max = parseInt($(this).attr('max'));
    let val = parseInt($(this).val());
    
    if (aksi === 'kurang' && val > max) {
        $(this).val(max);
        Swal.fire({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            icon: 'error', title: `Gagal! Sisa stok hanya ${max}`
        });
    }
});

// ========================================================
// FUNGSI HAPUS BARANG
// ========================================================
function hapusBarang(id) {
    Swal.fire({
        title: 'Hapus Barang?',
        text: "Pastikan barang ini tidak pernah digunakan dalam riwayat pengiriman!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus Permanen',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'hapus.php?id=' + id;
        }
    });
}

// Tangkap URL parameter jika gagal dihapus
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('error') && urlParams.get('error') === 'in_use') {
    Swal.fire({
        icon: 'error',
        title: 'Penghapusan Ditolak!',
        text: 'Barang tidak dapat dihapus karena sudah tercatat dalam riwayat pengiriman. Hal ini untuk menjaga validitas data laporan.',
        confirmButtonColor: '#0f172a'
    });
}
</script>