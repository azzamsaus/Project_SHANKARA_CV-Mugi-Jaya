<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-warning text-dark rounded-top-4 p-4">
                <h5 class="modal-title fw-bold">Ulasan Layanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formReview">
                <div class="modal-body p-4">
                    <div class="mb-3 text-center">
                        <label class="form-label fw-semibold">Beri Rating</label>
                        <select name="rating" class="form-select form-control-lg text-center fs-5" required>
                            <option value="5">⭐⭐⭐⭐⭐ Sangat Puas</option>
                            <option value="4">⭐⭐⭐⭐ Puas</option>
                            <option value="3">⭐⭐⭐ Cukup</option>
                            <option value="2">⭐⭐ Kurang</option>
                            <option value="1">⭐ Buruk</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Komentar</label>
                        <textarea name="komentar" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-warning w-100 rounded-pill py-2 fw-bold text-dark">Kirim Ulasan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
$('#formReview').on('submit', function(e) {
    e.preventDefault();
    $.post('/shankara_trackingbarang/api/buat_ulasan.php', $(this).serialize() + '&no_pengiriman=' + currentNoPengiriman, function(res) {
        if(res.success) {
            $('#reviewModal').modal('hide');
            Swal.fire('Terima Kasih!', 'Ulasan disimpan.', 'success');
        }
    }, 'json');
});
</script>