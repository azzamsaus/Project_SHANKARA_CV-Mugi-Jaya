<?php
$katalog_barang = fetchAll(query("SELECT * FROM barang ORDER BY nama_barang ASC"));
?>
<div class="hero-section" style="padding-bottom: 90px;">
    <div class="container">
        <h1>Katalog Material</h1>
        <p class="opacity-75 fs-5">Pilih dan pesan material logistik langsung ke sistem admin.</p>
    </div>
</div>

<div class="container" style="margin-top: -40px; margin-bottom: 60px;">
    <div class="row g-4">
        <?php if(empty($katalog_barang)): ?>
            <div class="col-12 text-center py-5 bg-white rounded-4 shadow-sm">
                <i class="bi bi-box-seam text-muted" style="font-size: 4rem;"></i>
                <p class="text-muted mt-3 fw-semibold">Katalog masih kosong.</p>
            </div>
        <?php else: ?>
            <?php foreach($katalog_barang as $brg): ?>
            <div class="col-md-4 col-lg-3">
                <div class="card h-100 border-0 rounded-4 shadow-sm text-center p-4 transition-hover" style="transition: transform 0.3s;">
                    <div class="mb-3">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="bi bi-bricks text-primary fs-1"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($brg['nama_barang']); ?></h5>
                    <p class="text-muted small mb-3" style="height: 40px; overflow: hidden;"><?php echo htmlspecialchars($brg['spesifikasi']); ?></p>
                    <div class="mt-auto">
                        <h4 class="fw-bold text-success mb-3">Rp <?php echo number_format($brg['harga'], 0, ',', '.'); ?> <span class="fs-6 text-muted fw-normal">/ <?php echo htmlspecialchars($brg['satuan']); ?></span></h4>
                        <button class="btn btn-primary w-100 rounded-pill" onclick="openPesanModal(<?php echo $brg['id']; ?>, '<?php echo htmlspecialchars($brg['nama_barang']); ?>')">
                            Pesan
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style> .transition-hover:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.1) !important; } </style>