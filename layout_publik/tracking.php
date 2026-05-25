<div class="hero-section">
    <div class="container">
        <h1>Lacak Pengiriman Anda</h1>
        <p class="opacity-75 fs-5">Pantau progres pengiriman material proyek secara *real-time*.</p>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="glass-card">
                <form id="publicTrackForm" class="row g-3 align-items-center">
                    <div class="col-md-9">
                        <div class="position-relative">
                            <i class="bi bi-search position-absolute top-50 translate-middle-y text-muted ms-3 fs-5"></i>
                            <input type="text" class="form-control" id="no_pengiriman" placeholder="Masukkan Nomor Resi (Cth: TRK2026...)" required style="padding-left: 45px;">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">LACAK</button>
                    </div>
                </form>

                <div id="trackLoader" class="text-center py-5 d-none">
                    <div class="spinner-grow text-primary" role="status"></div>
                    <p class="mt-3 text-muted fw-semibold">Memuat data dari server...</p>
                </div>

                <div id="trackResult" class="mt-5 d-none">
                    <div class="d-flex justify-content-between align-items-center bg-light rounded-4 p-4 mb-4 border">
                        <div>
                            <small class="text-muted text-uppercase fw-bold">Nomor Resi</small>
                            <h3 class="fw-bold text-dark mb-0" id="res_no">---</h3>
                        </div>
                        <div class="text-end">
                            <small class="text-muted text-uppercase fw-bold">Status</small><br>
                            <span class="badge bg-primary px-4 py-2 rounded-pill fs-6 mt-1 shadow-sm" id="res_status">---</span>
                        </div>
                    </div>

                    <div class="row g-5">
                        <div class="col-md-7">
                            <h5 class="fw-bold mb-4">Timeline Perjalanan</h5>
                            <div id="timeline_list"></div>
                        </div>
                        
                        <div class="col-md-5">
                            <div id="bukti_sampai_area" class="d-none text-center bg-white border rounded-4 p-4 shadow-sm">
                                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-check-circle-fill text-success"></i> Pesanan Tiba</h6>
                                <img src="" id="res_foto_bukti" class="img-fluid rounded-3 mb-4" style="max-height: 200px; object-fit: cover;">
                                <p class="text-muted small mb-4">Barang telah diserahterimakan. Berikan masukan Anda untuk kualitas layanan kami.</p>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-warning fw-bold text-dark rounded-3" onclick="$('#reviewModal').modal('show')">⭐ Beri Ulasan</button>
                                    <button class="btn btn-outline-danger fw-bold rounded-3" onclick="$('#komplainModal').modal('show')">Ajukan Komplain</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="trackError" class="alert alert-danger mt-4 border-0 rounded-3 d-none text-center">
                    <i class="bi bi-x-circle-fill fs-4 d-block mb-2"></i> Nomor resi tidak ditemukan.
                </div>
            </div>
        </div>
    </div>
</div>