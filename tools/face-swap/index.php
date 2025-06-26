<?php
$page_title = "AI Face Swap";
$path_prefix = '../../'; 
include $path_prefix . 'header.php';
?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .card { border: none; }
        .image-input-card { background-color: #fff; border: 1px solid #e0e0e0; border-radius: 0.75rem; padding: 1.5rem; height: 100%; }
        .preview-box { width: 100%; height: 250px; border: 2px dashed #ccc; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; position: relative; overflow: hidden; cursor: pointer; transition: border-color 0.3s ease; }
        .preview-box:hover, .preview-box.dragover { border-color: #0d6efd; }
        .preview-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .preview-box .placeholder { color: #6c757d; text-align: center; }
        .input-method-toggle .nav-link { cursor: pointer; }
        #result-image-container { max-width: 500px; margin: 1.5rem auto 0; animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .processing-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); color: white; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 1056; text-align: center; }
        .processing-overlay .spinner-border { width: 4rem; height: 4rem; }
        
        .history-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        .history-card {
            border-radius: 0.5rem;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: block;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .history-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .history-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background-color: #f0f0f0;
        }
    </style>
<body>
<main class="container py-5">
    <div class="text-center mb-4">
        <h1><i class="fas fa-people-arrows me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
        <p class="lead text-muted">Tukar wajah antara dua gambar dengan mudah menggunakan AI.</p>
    </div>

    <form id="faceSwapForm">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="image-input-card">
                    <h5 class="mb-3"><i class="fas fa-user-circle me-2 text-primary"></i>Gambar Original</h5>
                    <ul class="nav nav-pills nav-fill mb-3" data-target-prefix="original">
                        <li class="nav-item"><a class="nav-link active" data-method="file">Unggah</a></li>
                        <li class="nav-item"><a class="nav-link" data-method="url">URL</a></li>
                    </ul>
                    <div id="original_file_input">
                        <div class="preview-box" id="original_preview_box">
                            <img id="original_preview" src="#" alt="Pratinjau Original" style="display:none;">
                            <p id="original_placeholder" class="placeholder">Klik/Jatuhkan Gambar</p>
                        </div>
                        <input type="file" name="original_file" id="original_file" style="display:none;" accept="image/*">
                    </div>
                    <div id="original_url_input" style="display:none;">
                        <input type="url" name="original_url" class="form-control" placeholder="https://...">
                    </div>
                    <input type="hidden" name="original_type" id="original_type" value="file">
                </div>
            </div>
            <div class="col-md-6">
                <div class="image-input-card">
                    <h5 class="mb-3"><i class="fas fa-user-friends me-2 text-info"></i>Gambar Target</h5>
                     <ul class="nav nav-pills nav-fill mb-3" data-target-prefix="target">
                        <li class="nav-item"><a class="nav-link active" data-method="file">Unggah</a></li>
                        <li class="nav-item"><a class="nav-link" data-method="url">URL</a></li>
                    </ul>
                     <div id="target_file_input">
                        <div class="preview-box" id="target_preview_box">
                            <img id="target_preview" src="#" alt="Pratinjau Target" style="display:none;">
                            <p id="target_placeholder" class="placeholder">Klik/Jatuhkan Gambar</p>
                        </div>
                        <input type="file" name="target_file" id="target_file" style="display:none;" accept="image/*">
                    </div>
                    <div id="target_url_input" style="display:none;">
                        <input type="url" name="target_url" class="form-control" placeholder="https://...">
                    </div>
                    <input type="hidden" name="target_type" id="target_type" value="file">
                </div>
            </div>
        </div>
        <div class="d-grid mt-4">
            <button type="submit" id="submitBtn" class="btn btn-primary btn-lg">Tukar Wajah</button>
        </div>
    </form>
    
    <div id="processingOverlay" class="processing-overlay" style="display: none;">
        <div class="spinner-border text-light" role="status"></div>
        <h4 class="mt-3">Memproses Gambar...</h4>
        <p>Ini mungkin memakan waktu beberapa saat.</p>
    </div>
    
    <div id="resultContainer" style="display:none;"></div>
    <div id="errorMessage" class="alert alert-danger mt-4" style="display:none;"></div>

    <div class="mt-5 pt-4 border-top">
        <h3 class="text-center mb-4">Riwayat Terakhir</h3>
        <div id="historyGrid" class="history-grid">
            <!-- Riwayat akan dimuat di sini oleh JavaScript -->
        </div>
        <div id="historyLoader" class="text-center p-4" style="display:none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('faceSwapForm');
    const submitBtn = form.querySelector('button[type="submit"]');
    const processingOverlay = document.getElementById('processingOverlay');
    const resultContainer = document.getElementById('resultContainer');
    const errorMessage = document.getElementById('errorMessage');
    const historyGrid = document.getElementById('historyGrid');
    const historyLoader = document.getElementById('historyLoader');

    // Setup input method toggles (Upload/URL)
    document.querySelectorAll('.input-method-toggle').forEach(toggle => {
        const prefix = toggle.dataset.targetPrefix;
        toggle.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                if (this.classList.contains('active')) return;

                toggle.querySelector('.active').classList.remove('active');
                this.classList.add('active');
                
                const method = this.dataset.method;
                document.getElementById(`${prefix}_type`).value = method;

                document.getElementById(`${prefix}_file_input`).style.display = (method === 'file') ? 'block' : 'none';
                document.getElementById(`${prefix}_url_input`).style.display = (method === 'url') ? 'block' : 'none';
            });
        });
    });

    // Fungsi untuk setup preview gambar
    function setupPreview(fileInputId, previewBoxId) {
        const fileInput = document.getElementById(fileInputId);
        const previewBox = document.getElementById(previewBoxId);
        const previewImg = previewBox.querySelector('img');
        const placeholder = previewBox.querySelector('p');

        const displayImage = (file) => {
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    if (previewImg && placeholder) {
                        previewImg.src = e.target.result;
                        previewImg.style.display = 'block';
                        placeholder.style.display = 'none';
                    }
                };
                reader.readAsDataURL(file);
            }
        };

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                displayImage(fileInput.files[0]);
            }
        });

        previewBox.addEventListener('click', () => fileInput.click());
        previewBox.addEventListener('dragover', e => { e.preventDefault(); previewBox.classList.add('dragover'); });
        previewBox.addEventListener('dragleave', () => previewBox.classList.remove('dragover'));
        previewBox.addEventListener('drop', e => {
            e.preventDefault();
            previewBox.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                displayImage(fileInput.files[0]);
            }
        });
    }

    setupPreview('original_file', 'original_preview_box');
    setupPreview('target_file', 'target_preview_box');

    // Fungsi untuk memuat riwayat
    async function loadHistory() {
        historyLoader.style.display = 'block';
        historyGrid.innerHTML = '';
        try {
            const response = await fetch('history_loader.php');
            const historyData = await response.json();

            if (historyData && historyData.length > 0) {
                historyData.forEach(item => {
                    const card = `
                        <a href="${escapeHtml(item.result_url)}" class="history-card" target="_blank" title="Lihat gambar">
                            <img src="${escapeHtml(item.result_url)}" loading="lazy" alt="Hasil Face Swap">
                        </a>
                    `;
                    historyGrid.innerHTML += card;
                });
            } else {
                historyGrid.innerHTML = '<p class="text-muted text-center col-12">Belum ada riwayat.</p>';
            }
        } catch (error) {
            console.error('Gagal memuat riwayat:', error);
            historyGrid.innerHTML = '<p class="text-danger text-center col-12">Gagal memuat riwayat.</p>';
        } finally {
            historyLoader.style.display = 'none';
        }
    }
    
    // Handle form submission
    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        processingOverlay.style.display = 'flex';
        errorMessage.style.display = 'none';
        resultContainer.style.display = 'none';
        submitBtn.disabled = true;

        const formData = new FormData(form);

        try {
            const response = await fetch('faceswap_proxy.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!response.ok || data.status !== 'Success') {
                throw new Error(data.message || 'Gagal menukar wajah.');
            }

            displayResult(data.data.url);
            loadHistory(); // Muat ulang riwayat setelah berhasil

        } catch (error) {
            console.error('Error:', error);
            errorMessage.textContent = 'Terjadi kesalahan: ' + error.message;
            errorMessage.style.display = 'block';
        } finally {
            processingOverlay.style.display = 'none';
            submitBtn.disabled = false;
        }
    });

    function displayResult(imageUrl) {
        resultContainer.innerHTML = `
            <div id="result-image-container">
                <div class="card shadow-sm">
                    <div class="card-header text-center">
                        <h5>Hasil Face Swap</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="${escapeHtml(imageUrl)}" class="img-fluid rounded" alt="Hasil Face Swap">
                        <div class="d-grid mt-3">
                            <a href="${escapeHtml(imageUrl)}" class="btn btn-success btn-lg" download="faceswap-result.png">
                                <i class="fas fa-download me-2"></i>Unduh Gambar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;
        resultContainer.style.display = 'block';
    }

    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    // Panggil fungsi untuk memuat riwayat saat halaman pertama kali dibuka
    loadHistory();
});
</script>
</body>
</html>
