// SHANKARA Tracking System - Main JavaScript

// Format currency
function formatRupiah(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

// Show notification
function showNotification(title, message, type = 'info') {
    Swal.fire({
        title: title,
        text: message,
        icon: type,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });
}

// Real-time tracking simulation
let trackingInterval = null;

function startTracking(pengirimanId) {
    if(trackingInterval) clearInterval(trackingInterval);
    
    trackingInterval = setInterval(() => {
        // Simulate GPS update
        const lat = -6.2 + (Math.random() - 0.5) * 0.1;
        const lng = 106.8 + (Math.random() - 0.5) * 0.1;
        
        fetch('../api/update_lokasi.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `pengiriman_id=${pengirimanId}&latitude=${lat}&longitude=${lng}`
        });
    }, 30000); // Update every 30 seconds
}

// Load communication messages
function loadMessages() {
    fetch('../api/get_komunikasi.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('daftarPesan');
            if(container) {
                container.innerHTML = data.messages.map(msg => `
                    <div class="border-bottom mb-2 pb-2">
                        <strong>${msg.dari_user}</strong> ke <em>${msg.untuk_divisi}</em><br>
                        <small>${msg.pesan}</small><br>
                        <small class="text-muted">${msg.created_at}</small>
                    </div>
                `).join('');
            }
        });
}

// Send communication message
document.getElementById('formKomunikasi')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('../api/send_komunikasi.php', {
        method: 'POST',
        body: formData
    }).then(response => response.json())
    .then(data => {
        if(data.success) {
            showNotification('Berhasil', 'Pesan terkirim', 'success');
            this.reset();
            loadMessages();
        }
    });
});

// Export report
function exportReport(type) {
    window.location.href = `../dashboard/export_report.php?type=${type}`;
}

// Initialize maps if Leaflet is loaded
if(typeof L !== 'undefined') {
    const map = L.map('map').setView([-6.2, 106.8], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    let marker = null;
    
    function updateMap(lat, lng) {
        if(marker) marker.remove();
        marker = L.marker([lat, lng]).addTo(map);
        map.setView([lat, lng], 12);
    }
}

// Status badge colors
const statusColors = {
    'pending': 'secondary',
    'validasi': 'info',
    'dikirim': 'primary',
    'dalam_perjalanan': 'warning',
    'sampai': 'success',
    'batal': 'danger',
    'planning': 'secondary',
    'ongoing': 'primary',
    'completed': 'success',
    'delayed': 'danger'
};

// Auto refresh for dashboard
let autoRefreshEnabled = true;

function toggleAutoRefresh() {
    autoRefreshEnabled = !autoRefreshEnabled;
    if(autoRefreshEnabled) {
        setInterval(() => {
            if(window.location.pathname.includes('dashboard')) {
                location.reload();
            }
        }, 60000);
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Load messages if on dashboard
    if(document.getElementById('daftarPesan')) {
        loadMessages();
        setInterval(loadMessages, 30000);
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// SHANKARA Tracking System - Main JavaScript

function showNotification(title, message, type = 'info') {
    Swal.fire({
        title: title,
        text: message,
        icon: type,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });
}

function loadMessages() {
    $.ajax({
        url: '/shankara_trackingbarang/api/get_komunikasi.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            const container = $('#daftarPesan');
            if (container && data.messages && data.messages.length > 0) {
                let html = '';
                data.messages.forEach(function(msg) {
                    html += `
                        <div class="border-bottom mb-2 pb-2">
                            <strong><i class="bi bi-person-circle"></i> ${msg.dari_user}</strong> 
                            ke <em>${msg.untuk_divisi}</em><br>
                            <small>${msg.pesan}</small><br>
                            <small class="text-muted">${msg.created_at}</small>
                        </div>
                    `;
                });
                container.html(html);
            } else {
                container.html('<div class="text-muted text-center">Belum ada pesan</div>');
            }
        }
    });
}

$(document).ready(function() {
    // Form komunikasi handler
    $('#formKomunikasi').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '/shankara_trackingbarang/api/send_komunikasi.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showNotification('Berhasil', 'Pesan terkirim', 'success');
                    $('#formKomunikasi')[0].reset();
                    loadMessages();
                } else {
                    showNotification('Gagal', 'Pesan gagal dikirim', 'error');
                }
            }
        });
    });
    
    // Load messages if on dashboard
    if ($('#daftarPesan').length) {
        loadMessages();
        setInterval(loadMessages, 30000);
    }
});

// Global function untuk tracking detail
function showTrackingDetail(pengirimanId) {
    $.ajax({
        url: '/shankara_trackingbarang/api/get_tracking.php?id=' + pengirimanId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.pengiriman) {
                let trackingHistory = '';
                if (response.tracking && response.tracking.length > 0) {
                    trackingHistory = '<hr><strong>Riwayat Lokasi:</strong><ul class="list-unstyled">';
                    response.tracking.forEach(function(t) {
                        trackingHistory += `<li><i class="bi bi-geo-alt"></i> ${t.lokasi_text} - <small>${t.waktu}</small></li>`;
                    });
                    trackingHistory += '</ul>';
                } else {
                    trackingHistory = '<hr><div class="alert alert-info">Belum ada update lokasi. Sistem akan update otomatis setiap 30 detik.</div>';
                }
                
                Swal.fire({
                    title: 'Detail Tracking Pengiriman',
                    html: `
                        <div class="text-start">
                            <table class="table table-sm table-borderless">
                                <tr><td><strong>No Pengiriman</strong></td><td>: ${response.pengiriman.no_pengiriman}</td></tr>
                                <tr><td><strong>Sopir</strong></td><td>: ${response.pengiriman.sopir}</td></tr>
                                <tr><td><strong>No Kendaraan</strong></td><td>: ${response.pengiriman.no_kendaraan}</td></tr>
                                <tr><td><strong>Status</strong></td><td>: ${response.pengiriman.status}</td></tr>
                                <tr><td><strong>Lokasi Awal</strong></td><td>: ${response.pengiriman.lokasi_awal || '-'}</td></tr>
                                <tr><td><strong>Lokasi Tujuan</strong></td><td>: ${response.pengiriman.lokasi_tujuan || '-'}</td></tr>
                            </table>
                            ${trackingHistory}
                        </div>
                    `,
                    icon: 'info',
                    width: 600,
                    confirmButtonText: 'Tutup'
                });
            } else {
                Swal.fire('Error', 'Data pengiriman tidak ditemukan', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            Swal.fire('Error', 'Gagal memuat data tracking: ' + error, 'error');
        }
    });
}