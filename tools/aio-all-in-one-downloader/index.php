<?php
// session_start(); // Mulai session untuk CSRF

// Fungsi untuk membuat token CSRF
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token_aio'])) {
        $_SESSION['csrf_token_aio'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token_aio'];
}

$csrf_token = generate_csrf_token();
$page_title = "AIO (All-in-One) Downloader";
$path_prefix = '../../'; 
 include $path_prefix . 'header.php';
?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .card { border: none; }
        .result-card {
            background-color: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
            animation: fadeIn 0.5s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .result-thumbnail {
            width: 100%;
            height: auto;
            max-height: 400px;
            object-fit: cover;
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
        }
        .result-title {
            font-weight: 600;
            font-size: 1.2rem;
        }
        .download-buttons .btn {
            margin: 0.25rem;
        }
    </style>
</head>
<body>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="text-center mb-4">
                <h1><i class="fas fa-download me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <p class="lead text-muted">Unduh video dan audio dari berbagai platform dengan mudah.</p>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form id="downloaderForm">
                        <!-- Input CSRF Token Tersembunyi Dihapus -->
                        
                        <div class="mb-3">
                            <label for="serviceType" class="form-label fs-5">Pilih Platform:</label>
                            <select id="serviceType" name="service" class="form-select form-select-lg">
                                <option value="capcut">CapCut (TikTok Template)</option>
                                <option value="douyin">Douyin / TikTok</option>
                                <option value="facebook">Facebook</option>
                                <option value="instagram">Instagram</option>
                                <option value="xnxx">XNXX</option>
                                <option value="ytmp3">YouTube (MP3 Audio)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                             <label for="urlInput" class="form-label fs-5">Masukkan URL:</label>
                             <input type="url" id="urlInput" name="link" class="form-control form-control-lg" placeholder="Tempel URL di sini..." required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" id="submitBtn" class="btn btn-primary btn-lg">
                                <span id="btnText">Unduh</span>
                                <span id="spinner" class="spinner-border spinner-border-sm" style="display: none;"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div id="resultContainer" class="mt-4" style="display: none;"></div>
            <div id="errorMessage" class="alert alert-danger mt-4" style="display: none;"></div>

        </div>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('downloaderForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const spinner = document.getElementById('spinner');
    const resultContainer = document.getElementById('resultContainer');
    const errorMessage = document.getElementById('errorMessage');

    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        // UI feedback
        errorMessage.style.display = 'none';
        resultContainer.style.display = 'none';
        submitBtn.disabled = true;
        btnText.textContent = 'Memproses...';
        spinner.style.display = 'inline-block';

        const service = document.getElementById('serviceType').value;
        const link = document.getElementById('urlInput').value;

        try {
            // PERUBAHAN: Kirim data sebagai JSON, bukan FormData
            const response = await fetch('api_proxy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ service: service, link: link })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || data.msg || 'Gagal mengunduh konten.');
            }

            displayResult(data, service);

        } catch (error) {
            console.error('Error:', error);
            errorMessage.textContent = 'Terjadi kesalahan: ' + error.message;
            errorMessage.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
            btnText.textContent = 'Unduh';
            spinner.style.display = 'none';
        }
    });

    function displayResult(data, service) {
        let resultHtml = '';
        switch(service) {
            case 'capcut':
                resultHtml = `
                    <div class="card result-card">
                        <img src="${escapeHtml(data.data.posterUrl)}" class="result-thumbnail" alt="Poster" onerror="this.style.display='none'">
                        <div class="card-body">
                            <h5 class="result-title">${escapeHtml(data.data.title)}</h5>
                            <p class="card-text text-muted">${escapeHtml(data.data.pengguna)} | ${escapeHtml(data.data.likes)}</p>
                            <div class="download-buttons mt-3 text-center">
                                <a href="${escapeHtml(data.data.videoUrl)}" class="btn btn-danger" target="_blank" download>
                                    <i class="fas fa-video me-2"></i>Unduh Video
                                </a>
                            </div>
                        </div>
                    </div>`;
                break;
            case 'douyin':
                resultHtml = `
                    <div class="card result-card">
                        <img src="${escapeHtml(data.result.thumbnail)}" class="result-thumbnail" alt="Thumbnail" onerror="this.style.display='none'">
                        <div class="card-body">
                            <h5 class="result-title">${escapeHtml(data.result.title)}</h5>
                            <div class="download-buttons mt-3 text-center">
                                <a href="${escapeHtml(data.result.download.no_watermark)}" class="btn btn-primary" target="_blank" download><i class="fas fa-video me-2"></i>Video (Tanpa Watermark)</a>
                                <a href="${escapeHtml(data.result.download.with_watermark)}" class="btn btn-secondary" target="_blank" download><i class="fas fa-video me-2"></i>Video (Dengan Watermark)</a>
                                <a href="${escapeHtml(data.result.download.mp3)}" class="btn btn-success" target="_blank" download><i class="fas fa-music me-2"></i>Audio (MP3)</a>
                            </div>
                        </div>
                    </div>`;
                break;
            case 'facebook':
                resultHtml = `
                    <div class="card result-card">
                        <div class="card-body">
                            <h5 class="result-title">${escapeHtml(data.data.title)}</h5>
                            <div class="download-buttons mt-3 text-center">
                                <a href="${escapeHtml(data.data.hd)}" class="btn btn-primary" target="_blank" download><i class="fas fa-video me-2"></i>Unduh HD</a>
                                <a href="${escapeHtml(data.data.sd)}" class="btn btn-secondary" target="_blank" download><i class="fas fa-video me-2"></i>Unduh SD</a>
                            </div>
                        </div>
                    </div>`;
                break;
            case 'instagram':
                resultHtml = `
                    <div class="card result-card">
                        <img src="${escapeHtml(data.data.thumbnailUrl)}" class="result-thumbnail" alt="Thumbnail" onerror="this.style.display='none'">
                        <div class="card-body">
                             <h5 class="result-title">Konten oleh @${escapeHtml(data.data.username)}</h5>
                             <div class="download-buttons mt-3 text-center">
                                ${data.data.videoUrls.map(video => `<a href="${escapeHtml(video.url)}" class="btn btn-primary" target="_blank" download><i class="fas fa-video me-2"></i>Unduh Video (${video.name})</a>`).join('')}
                            </div>
                        </div>
                    </div>`;
                break;
            case 'xnxx':
                 resultHtml = `
                    <div class="card result-card">
                        <img src="${escapeHtml(data.result.image)}" class="result-thumbnail" alt="Thumbnail" onerror="this.style.display='none'">
                        <div class="card-body">
                            <h5 class="result-title">${escapeHtml(data.result.title)}</h5>
                            <p class="card-text text-muted">Durasi: ${new Date(data.result.duration * 1000).toISOString().substr(11, 8)}</p>
                            <div class="download-buttons mt-3 text-center">
                                <a href="${escapeHtml(data.result.files.high)}" class="btn btn-primary" target="_blank" download><i class="fas fa-video me-2"></i>Kualitas Tinggi</a>
                                <a href="${escapeHtml(data.result.files.low)}" class="btn btn-secondary" target="_blank" download><i class="fas fa-video me-2"></i>Kualitas Rendah</a>
                            </div>
                        </div>
                    </div>`;
                break;
            case 'ytmp3':
                resultHtml = `
                    <div class="card result-card">
                        <img src="${escapeHtml(data.data.thumbnail)}" class="result-thumbnail" alt="Thumbnail" onerror="this.style.display='none'">
                        <div class="card-body">
                            <h5 class="result-title">${escapeHtml(data.data.title)}</h5>
                            <p class="card-text text-muted">Ukuran: ${(data.data.size / (1024*1024)).toFixed(2)} MB</p>
                             <div class="download-buttons mt-3 text-center">
                                <a href="${escapeHtml(data.data.dlink)}" class="btn btn-success" target="_blank" download><i class="fas fa-music me-2"></i>Unduh MP3</a>
                            </div>
                        </div>
                    </div>`;
                break;
            default:
                resultHtml = '<div class="alert alert-warning">Tipe layanan tidak didukung untuk tampilan hasil.</div>';
        }
        resultContainer.innerHTML = resultHtml;
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
});
</script>
<?php
// Footer fallback jika tidak ada footer.php
if (file_exists($path_prefix . 'footer.php')) {
    include $path_prefix . 'footer.php';
} else {
    echo "</main><footer class='footer'><p>&copy; " . date("Y") . " My Web Tool. All rights reserved.</p></footer></body></html>";
}
?>