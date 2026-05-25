<div class="modal fade" id="pesanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-primary text-white rounded-top-4 p-4">
                <h5 class="modal-title fw-bold">Form Pemesanan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPesan">
                <div class="modal-body p-4">
                    <input type="hidden" id="pesan_barang_id" name="barang_id">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Barang</label>
                        <input type="text" class="form-control bg-light fw-bold" id="pesan_nama_barang" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Proyek / Pemesan</label>
                        <input type="text" name="nama_klien" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Jumlah</label>
                            <input type="number" name="jumlah" class="form-control" required min="1">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Tanggal Butuh</label>
                            <input type="date" name="tanggal_butuh" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2">Kirim Pesanan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
$('#formPesan').on('submit', function(e) {
    e.preventDefault();
    Swal.fire({ title: 'Memproses...', didOpen: () => Swal.showLoading() });
    $.post('/shankara_trackingbarang/api/buat_pesanan.php', $(this).serialize(), function(res) {
        if(res.success) {
            $('#pesanModal').modal('hide');
            Swal.fire('Sukses!', 'Pesanan dikirim.', 'success');
            $('#formPesan')[0].reset();
        }
    }, 'json');
});
</script>