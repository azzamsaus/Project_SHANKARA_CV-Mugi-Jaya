<div class="modal fade" id="komplainModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-danger text-white rounded-top-4 p-4">
                <h5 class="modal-title fw-bold">Pengaduan Komplain</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formKomplain" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Masalah</label>
                        <select name="jenis_masalah" class="form-select" required>
                            <option value="barang_rusak">Barang Rusak/Cacat</option>
                            <option value="jumlah_kurang">Jumlah Kurang</option>
                            <option value="keterlambatan">Keterlambatan Ekspedisi</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Penjelasan Lengkap</label>
                        <textarea name="deskripsi" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Bukti Foto</label>
                        <input type="file" name="foto_komplain" class="form-control" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-danger w-100 rounded-pill py-2 fw-bold">Ajukan Komplain</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
$('#formKomplain').on('submit', function(e) {
    e.preventDefault();
    let fd = new FormData(this);
    fd.append('no_pengiriman', currentNoPengiriman);
    Swal.fire({ title: 'Mengirim...', didOpen: () => Swal.showLoading() });
    $.ajax({
        url: '/shankara_trackingbarang/api/buat_komplain.php', type: 'POST',
        data: fd, processData: false, contentType: false, dataType: 'json',
        success: function(res) {
            Swal.close();
            if(res.success) {
                $('#komplainModal').modal('hide');
                Swal.fire('Terkirim', 'Komplain Anda diteruskan ke Admin.', 'info');
                $('#formKomplain')[0].reset();
            }
        }
    });
});
</script>