<?php
$page_title = "XNXX Search"; // Judul diperbarui
$path_prefix = '../../'; 
include $path_prefix . 'header-h.php';
?>
<style>
    :root {
        --ph-orange: #ff9900;
        --ph-dark: #222222;
        --ph-light-gray: #313131;
    }
    .tool-header-ph {
        padding: 4rem 1rem;
        background-color: var(--ph-dark);
        background-image: linear-gradient(135deg, var(--ph-dark) 0%, #000000 100%);
        border-radius: var(--border-radius);
        margin-bottom: 2.5rem;
        color: white;
        text-align: center;
        box-shadow: var(--shadow-lg);
    }
    .tool-header-ph h1 {
        font-weight: 700;
        font-size: 2.5rem;
        color: var(--ph-orange);
    }
    .search-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        padding: 2rem;
    }
    .form-control-ph {
        border-radius: 8px;
        border: 2px solid var(--border-color);
        padding: 0.75rem 1.25rem;
        font-size: 1.1rem;
    }
    .form-control-ph:focus {
        border-color: var(--ph-orange);
        box-shadow: 0 0 0 0.25rem rgba(255, 153, 0, 0.25);
    }
    .btn-ph-search {
        background-color: var(--ph-orange);
        color: white;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        transition: var(--transition);
    }
    .btn-ph-search:hover {
        background-color: #e68a00;
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    #resultsGrid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }
    .result-item {
        background: #fff;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        flex-direction: column;
        cursor: pointer; /* Menambahkan cursor pointer */
    }
    .result-item:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }
    .result-item-content {
        padding: 1rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .result-item-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        text-decoration: none;
        margin-bottom: 0.75rem;
    }
    .result-item-info {
        font-size: 0.9rem;
        color: var(--text-secondary);
        white-space: pre-wrap; /* Agar baris baru di info tampil */
        margin-top: auto;
    }
    /* Style untuk Modal */
    .modal-header-ph {
        background-color: var(--ph-dark);
        color: var(--ph-orange);
    }
    .modal-body video, .modal-body img {
        max-width: 100%;
        border-radius: 8px;
    }
    .download-links a {
        margin: 0.5rem;
    }
</style>

<div class="container py-5">
    <div class="tool-header-ph">
        <h1><i class="fas fa-search me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
        <p class="lead">Cari video berdasarkan kata kunci.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Peringatan Pengguna -->
            <div class="alert alert-danger text-center" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Peringatan:</strong> Pastikan umur Anda 18+, dan VPN harus aktif agar bisa download atau streaming.
            </div>

            <div class="search-card mt-4">
                <form id="phSearchForm">
                    <div class="input-group">
                        <input type="text" id="searchInput" class="form-control form-control-ph" placeholder="Masukkan kata kunci..." required>
                        <button type="submit" id="submitBtn" class="btn btn-ph-search">
                            <span id="btnText">Cari</span>
                            <span id="spinner" class="spinner-border spinner-border-sm" style="display: none;"></span>
                        </button>
                    </div>
                </form>
            </div>
            
            <div id="loadingIndicator" class="text-center mt-4" style="display: none;">
                 <div class="spinner-border text-warning" role="status"></div>
                 <p class="mt-2 text-dark">Sedang mencari...</p>
            </div>
            
            <div id="resultsGrid">
                 <!-- Hasil pencarian akan ditampilkan di sini -->
            </div>

            <div id="errorMessage" class="alert alert-danger mt-4" style="display: none;"></div>
        </div>
    </div>
</div>

<!-- Modal untuk Detail Video -->
<div class="modal fade" id="videoDetailModal" tabindex="-1" aria-labelledby="videoDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header modal-header-ph">
        <h5 class="modal-title" id="videoDetailModalLabel">Detail Video</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalBodyContent">
        <!-- Konten detail akan dimuat di sini -->
        <div class="text-center">
            <div class="spinner-border text-warning" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('phSearchForm');
    const searchInput = document.getElementById('searchInput');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const spinner = document.getElementById('spinner');
    const resultsGrid = document.getElementById('resultsGrid');
    const errorMessage = document.getElementById('errorMessage');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const videoDetailModalEl = document.getElementById('videoDetailModal');
    const videoDetailModal = new bootstrap.Modal(videoDetailModalEl);
    const modalBodyContent = document.getElementById('modalBodyContent');
    const modalTitle = document.getElementById('videoDetailModalLabel');

    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        errorMessage.style.display = 'none';
        resultsGrid.innerHTML = '';
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        spinner.style.display = 'inline-block';
        loadingIndicator.style.display = 'block';

        const query = searchInput.value;

        try {
            const response = await fetch(`pornhub_proxy.php?q=${encodeURIComponent(query)}`);
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `Terjadi error dengan kode: ${response.status}`);
            }
            const data = await response.json();
            
            if (data.status === 'Success' && data.data.status === true && data.data.result.length > 0) {
                displayResults(data.data.result);
            } else {
                errorMessage.textContent = 'Tidak ada hasil ditemukan atau terjadi kesalahan pada API.';
                errorMessage.style.display = 'block';
            }

        } catch (error) {
            console.error('Error:', error);
            errorMessage.textContent = 'Terjadi kesalahan: ' + error.message;
            errorMessage.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
            btnText.style.display = 'inline-block';
            spinner.style.display = 'none';
            loadingIndicator.style.display = 'none';
        }
    });

    function displayResults(results) {
        resultsGrid.innerHTML = '';
        results.forEach(video => {
            const card = document.createElement('div');
            card.className = 'result-item';
            card.setAttribute('data-url', video.link); // Simpan URL di atribut data
            
            const infoText = video.info.trim();

            card.innerHTML = `
                <div class="result-item-content">
                    <span class="result-item-title">${video.title}</span>
                    <div class="result-item-info">${infoText}</div>
                </div>
            `;
            // Tambahkan event listener untuk membuka modal
            card.addEventListener('click', () => {
                showVideoDetails(video.link);
            });
            resultsGrid.appendChild(card);
        });
    }

    async function showVideoDetails(url) {
        videoDetailModal.show();
        // Tampilkan spinner loading di modal
        modalBodyContent.innerHTML = `<div class="text-center"><div class="spinner-border text-warning" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
        modalTitle.textContent = 'Memuat Detail...';

        try {
            const response = await fetch(`xnxx_detail_proxy.php?url=${encodeURIComponent(url)}`);
            if(!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Gagal memuat detail video');
            }
            const data = await response.json();

            if (data.status === 'Success' && data.data.status === true) {
                renderModalContent(data.data);
            } else {
                throw new Error(data.message || 'Gagal memuat detail dari API.');
            }

        } catch (error) {
            modalBodyContent.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    function renderModalContent(details) {
        modalTitle.textContent = details.title;

        let videoPlayer = '';
        // Cek jika URL video kualitas rendah ada
        if (details.files.low) {
            videoPlayer = `
                <video width="100%" controls autoplay class="mb-3">
                    <source src="${details.files.low}" type="video/mp4">
                    Browser Anda tidak mendukung tag video.
                </video>
            `;
        } else {
            // Jika tidak ada video, tampilkan gambar sebagai fallback
            videoPlayer = `<img src="${details.image}" alt="${details.title}" class="img-fluid mb-3">`;
        }

        let downloadButtons = '';
        if (details.files.low) {
            downloadButtons += `<a href="${details.files.low}" class="btn btn-outline-secondary" download>Download Kualitas Rendah</a>`;
        }
        if (details.files.high) {
            downloadButtons += `<a href="${details.files.high}" class="btn btn-warning" download>Download Kualitas Tinggi</a>`;
        }
        if (details.files.HLS) {
            // HLS biasanya untuk streaming, bisa ditambahkan logika lain jika perlu
        }

        modalBodyContent.innerHTML = `
            ${videoPlayer}
            <p><strong>Durasi:</strong> ${Math.floor(details.duration / 60)} menit ${details.duration % 60} detik</p>
            <p><strong>Info:</strong><br>${details.info.replace(/\t/g, ' ')}</p>
            <hr>
            <div class="text-center download-links">
                ${downloadButtons}
            </div>
        `;
    }

    // Listener untuk menghentikan video saat modal ditutup
    videoDetailModalEl.addEventListener('hidden.bs.modal', function () {
        const video = modalBodyContent.querySelector('video');
        if (video) {
            video.pause();
            video.src = ''; // Mengosongkan sumber untuk menghentikan buffering
        }
    });
});
</script>
<?php 
include $path_prefix . 'footer-h.php';
?>
