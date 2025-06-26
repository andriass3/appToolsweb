<?php
$page_title = "Edit Image with AI";
$path_prefix = '../../'; 
include $path_prefix . 'header-h.php';

// --- Konfigurasi & Inisialisasi Limit Global ---
define('MAX_USAGE_IMAGE', 20);
define('LIMIT_FILE', 'limit_counter.txt'); // Pastikan path ini sama dengan di proxy

$usage_count = 0;
$today = date('Y-m-d');

if (file_exists(LIMIT_FILE)) {
    $file_content = file_get_contents(LIMIT_FILE);
    $limit_data = json_decode($file_content, true);
    // Periksa apakah data valid dan untuk hari ini
    if (is_array($limit_data) && isset($limit_data['date']) && $limit_data['date'] === $today) {
        $usage_count = $limit_data['count'];
    }
}

$remaining_limit = max(0, MAX_USAGE_IMAGE - $usage_count);
$usage_percentage = ($remaining_limit / MAX_USAGE_IMAGE) * 100;
$is_limit_reached = $remaining_limit <= 0;
?>
<style>
    .tool-header-editor {
        padding: 4rem 1rem;
        background: linear-gradient(135deg, #4facfe, #00f2fe);
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
        color: white;
        text-align: center;
        box-shadow: var(--shadow-lg);
    }
    .tool-header-editor h1 {
        font-weight: 700;
        font-size: 2.5rem;
    }
    .editor-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        padding: 2rem;
    }
    .upload-area {
        border: 2px dashed var(--border-color);
        border-radius: var(--border-radius);
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: var(--transition);
    }
    .upload-area:hover {
        border-color: var(--primary-color);
        background-color: #f7fafc;
    }
    #imagePreview {
        max-width: 100%;
        max-height: 400px;
        border-radius: var(--border-radius);
        margin-top: 1rem;
        border: 1px solid var(--border-color);
    }
    
    /* UI Keren untuk Statistik Penggunaan */
    .usage-stats {
        margin: -1.5rem auto 2.5rem auto;
        padding: 0.5rem;
        max-width: 550px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 50px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .usage-stats .progress {
        height: 30px;
        border-radius: 50px;
        background-color: transparent;
        padding: 0;
        overflow: visible;
    }
    .usage-stats .progress-bar {
        background: linear-gradient(90deg, #fff, #b2f0ff);
        border-radius: 50px;
        color: #0056b3;
        font-weight: 700;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 15px;
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2);
        transition: width 0.6s ease-in-out;
        position: relative;
    }
    .usage-stats .progress-bar-text {
        position: absolute;
        width: 100%;
        text-align: center;
        color: #0056b3;
        font-weight: bold;
    }
    .usage-stats .usage-label {
        color: white;
        font-weight: 500;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        position: absolute;
        left: 25px;
        line-height: 30px;
    }
    .btn:disabled {
        background: #6c757d;
        cursor: not-allowed;
        border-color: #6c757d;
    }

    /* === Style Baru untuk Hasil Gambar === */
    #resultImageContainer {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.5s ease, transform 0.5s ease;
    }
    #resultImageContainer.show {
        opacity: 1;
        transform: translateY(0);
    }
    .result-card {
        background: #ffffff;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-lg);
        margin-top: 2rem;
        overflow: hidden;
        border: 1px solid var(--border-color);
    }
    .result-header {
        padding: 1rem 1.5rem;
        background: #f7fafc;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-color);
    }
    .result-header h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: #28a745;
    }
    .result-header h3 i {
        margin-right: 0.5rem;
    }
    .processing-time {
        font-size: 0.9rem;
        color: var(--text-secondary);
        font-weight: 500;
    }
    .result-body {
        padding: 1rem;
        text-align: center;
    }
    .result-body img {
        max-width: 100%;
        border-radius: 8px;
    }
    .result-footer {
        padding: 1rem;
        background: #f7fafc;
        text-align: center;
        border-top: 1px solid var(--border-color);
    }
</style>

<div class="container py-5">
    <div class="tool-header-editor">
        <h1><i class="fas fa-magic-wand-sparkles me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
        <p class="lead">Edit gambar Anda menggunakan instruksi teks sederhana.</p>
    </div>

    <!-- UI Statistik Penggunaan -->
    <div class="usage-stats">
        <div class="progress">
            <div id="usageProgressBar" class="progress-bar" role="progressbar" 
                 style="width: <?php echo $usage_percentage; ?>%;" 
                 aria-valuenow="<?php echo $remaining_limit; ?>" 
                 aria-valuemin="0" 
                 aria-valuemax="<?php echo MAX_USAGE_IMAGE; ?>">
                 <span class="usage-label">Sisa Kredit Global</span>
                 <span class="progress-bar-text" id="limitCountText"><?php echo $remaining_limit; ?> / <?php echo MAX_USAGE_IMAGE; ?></span>
            </div>
        </div>
    </div>


    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="editor-card">
                <form id="imageEditForm" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label fs-5">1. Unggah Gambar</label>
                        <div class="upload-area" id="uploadArea">
                            <i class="fas fa-cloud-arrow-up upload-icon"></i>
                            <p class="upload-text mt-2">Klik atau seret gambar ke sini</p>
                        </div>
                        <input type="file" id="imageUpload" name="image" class="d-none" accept="image/*" required>
                        <div class="text-center mt-3">
                            <img id="imagePreview" src="" alt="Pratinjau Gambar" class="d-none">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="promptInput" class="form-label fs-5">2. Masukkan Instruksi</label>
                        <textarea id="promptInput" name="prompt" class="form-control" rows="3" placeholder="Contoh: jadikan pria ini memiliki otot sixpack" maxlength="250" required></textarea>
                        <small class="form-text text-muted">Jelaskan perubahan yang Anda inginkan. Maksimal 50 kata.<b>NOT SUPPORT NSFW</b></small>
                    </div>

                    <div class="d-grid">
                        <button type="submit" id="submitBtn" class="btn btn-primary btn-lg mt-3" <?php if ($is_limit_reached) echo 'disabled'; ?>>
                            <span id="btnText">
                                <?php echo $is_limit_reached ? 'Kredit Global Habis' : 'Edit Gambar'; ?>
                            </span>
                            <span id="spinner" class="spinner-border spinner-border-sm" style="display: none;"></span>
                        </button>
                    </div>
                </form>
            </div>
            
            <div id="loadingIndicator" class="text-center mt-4" style="display: none;">
                 <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
                 <p class="mt-3 fs-5">AI sedang bekerja, mohon tunggu...</p>
            </div>
            
            <div id="resultImageContainer" class="text-center mt-4" style="display: none;">
                 <!-- Hasil gambar akan dirender di sini oleh JavaScript -->
            </div>

            <div id="errorMessage" class="alert alert-danger mt-4" style="display: none;"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('imageEditForm');
    const uploadArea = document.getElementById('uploadArea');
    const imageUpload = document.getElementById('imageUpload');
    const imagePreview = document.getElementById('imagePreview');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const spinner = document.getElementById('spinner');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const resultContainer = document.getElementById('resultImageContainer');
    const errorMessage = document.getElementById('errorMessage');

    const progressBar = document.getElementById('usageProgressBar');
    const limitCountText = document.getElementById('limitCountText');
    const maxUsage = <?php echo MAX_USAGE_IMAGE; ?>;
    
    let startTime = 0;

    uploadArea.addEventListener('click', () => imageUpload.click());
    uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.style.borderColor = 'var(--primary-color)'; });
    uploadArea.addEventListener('dragleave', (e) => { e.preventDefault(); uploadArea.style.borderColor = 'var(--border-color)'; });
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = 'var(--border-color)';
        if (e.dataTransfer.files.length > 0) {
            imageUpload.files = e.dataTransfer.files;
            imageUpload.dispatchEvent(new Event('change'));
        }
    });

    imageUpload.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.classList.remove('d-none');
            }
            reader.readAsDataURL(file);
        }
    });

    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        errorMessage.style.display = 'none';
        resultContainer.style.display = 'none';
        resultContainer.classList.remove('show');
        submitBtn.disabled = true;
        btnText.textContent = 'Memproses...';
        spinner.style.display = 'inline-block';
        loadingIndicator.style.display = 'block';

        startTime = performance.now(); // Catat waktu mulai
        const formData = new FormData(form);

        try {
            const response = await fetch('image_proxy.php', {
                method: 'POST',
                body: formData 
            });
            
            const endTime = performance.now(); // Catat waktu selesai
            const duration = ((endTime - startTime) / 1000).toFixed(2); // Hitung durasi dalam detik

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `Terjadi error dengan kode: ${response.status}`);
            }

            const imageBlob = await response.blob();
            const imageUrl = URL.createObjectURL(imageBlob);
            
            displayResult(imageUrl, duration);
            updateUsageStats();

        } catch (error) {
            console.error('Error:', error);
            errorMessage.textContent = 'Terjadi kesalahan: ' + error.message;
            errorMessage.style.display = 'block';
        } finally {
            if (parseInt(progressBar.getAttribute('aria-valuenow')) > 0) {
                submitBtn.disabled = false;
                btnText.textContent = 'Edit Gambar';
            } else {
                 btnText.textContent = 'Kredit Global Habis';
            }
            spinner.style.display = 'none';
            loadingIndicator.style.display = 'none';
        }
    });

    function displayResult(imageUrl, duration) {
        resultContainer.innerHTML = `
            <div class="result-card">
                <div class="result-header">
                    <h3><i class="fas fa-check-circle"></i> Berhasil!</h3>
                    <span class="processing-time">Selesai dalam ${duration} detik</span>
                </div>
                <div class="result-body">
                    <img src="${imageUrl}" alt="Hasil Gambar yang Diedit" class="img-fluid">
                </div>
                <div class="result-footer">
                    <a href="${imageUrl}" download="edited-image-${Date.now()}.jpg" class="btn btn-success">
                        <i class="fas fa-download me-2"></i>Unduh Gambar
                    </a>
                </div>
            </div>
        `;
        resultContainer.style.display = 'block';
        // Tambahkan sedikit delay untuk memicu animasi CSS
        setTimeout(() => {
            resultContainer.classList.add('show');
        }, 10);
    }
    
    function updateUsageStats() {
        let currentLimit = parseInt(progressBar.getAttribute('aria-valuenow'));
        if (currentLimit > 0) {
            currentLimit--;
            const newPercentage = (currentLimit / maxUsage) * 100;
            progressBar.style.width = newPercentage + '%';
            progressBar.setAttribute('aria-valuenow', currentLimit);
            limitCountText.textContent = `${currentLimit} / ${maxUsage}`;

            if (currentLimit <= 0) {
                submitBtn.disabled = true;
                btnText.textContent = 'Kredit Global Habis';
            }
        }
    }
});
</script>
<?php 
include $path_prefix . 'footer-h.php';
?>
