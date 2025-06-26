<?php
// Ganti path ke header-h.php jika perlu
// session_start() sudah dipanggil di dalam header-h.php
$page_title = "SunoAI Generate Music";
$path_prefix = '../../'; 
include $path_prefix . 'header-h.php';

// --- Konfigurasi & Inisialisasi Sesi untuk Halaman Ini ---
define('MAX_USAGE', 20); // Pastikan nilai ini sama dengan di proxy

// Inisialisasi sesi jika belum ada (sebagai fallback)
if (!isset($_SESSION['suno_usage'])) {
    $_SESSION['suno_usage'] = 0;
}
if (!isset($_SESSION['suno_history'])) {
    $_SESSION['suno_history'] = [];
}

// Ambil data dari sesi untuk ditampilkan saat halaman dimuat
$remaining_limit = MAX_USAGE - $_SESSION['suno_usage'];
$suno_history = $_SESSION['suno_history'];
?>
<style>
    /* Menggunakan variabel warna dari header-h.php */
    .tool-header-suno {
        padding: 4rem 1rem;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
        color: white;
        text-align: center;
        box-shadow: var(--shadow-lg);
    }
    .tool-header-suno h1 {
        font-weight: 700;
        font-size: 2.5rem;
    }
    .tool-header-suno .lead {
        color: rgba(255, 255, 255, 0.8);
        max-width: 600px;
        margin: 0.5rem auto 0 auto;
    }
    
    .suno-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
    }

    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid var(--border-color);
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
    }

    .btn-suno-generate {
        background: linear-gradient(45deg, var(--primary-color), var(--primary-dark));
        color: white;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        transition: var(--transition);
    }
    .btn-suno-generate:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .music-player-card {
        background-color: #ffffff;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        animation: fadeIn 0.5s ease-in-out;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .music-player-card audio {
        width: 100%;
        margin-top: 1rem;
        margin-bottom: 1rem;
    }
    .music-player-card img {
        width: 100%;
        height: auto;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .song-title {
        font-weight: 700;
        font-size: 1.5rem;
        color: var(--text-primary);
    }
    .song-tags {
        margin-top: 0.5rem;
        margin-bottom: 1rem;
    }
    .song-tags .badge {
        background-color: var(--primary-color);
        color: white;
        font-weight: 500;
        margin: 0.2rem;
        padding: 0.4em 0.8em;
    }

    /* Style untuk Statistik & Riwayat */
    .limit-stats {
        text-align: center;
        margin: -1rem auto 2rem auto;
        max-width: 400px;
        font-size: 1.1rem;
        font-weight: 500;
        background-color: rgba(255, 255, 255, 0.2);
        color: #000000;
        padding: 0.75rem;
        border-radius: var(--border-radius);
        backdrop-filter: blur(5px);
    }
    .limit-stats strong {
        color: #ff0000;
        font-weight: 700;
    }
    .history-section {
        margin-top: 3rem;
    }
    .history-item {
        margin-bottom: 2rem;
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        animation: fadeIn 0.5s;
    }
    .history-item .prompt-text {
        font-style: italic;
        color: var(--text-secondary);
        border-left: 3px solid var(--primary-color);
        padding-left: 1rem;
        margin-bottom: 1.5rem;
    }
</style>

<div class="container py-5">
    <div class="tool-header-suno">
        <h1><i class="fas fa-music me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
        <p class="lead">Buat lagu orisinal dari ide Anda dengan kekuatan AI.</p>
    </div>
    
     <!-- Statistik Penggunaan -->
    <div class="limit-stats">
        Sisa kredit hari ini: <strong id="limitCount"><?php echo max(0, $remaining_limit); ?></strong> / <?php echo MAX_USAGE; ?>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card suno-card">
                <div class="card-body p-4 p-lg-5">
                    <form id="musicGeneratorForm">
                        <div class="mb-3">
                            <label for="queryInput" class="form-label fs-5">Deskripsi Musik & Lirik:</label>
                            <textarea id="queryInput" class="form-control" rows="4" placeholder="Contoh: lagu pop akustik tentang senja di pantai, dengan lirik yang puitis" maxlength="250" required></textarea>
                            <small class="form-text text-muted">Jelaskan gaya musik dan isi lirik. Maksimal 50 kata (sekitar 250 karakter).</small>
                        </div>
                        <div class="d-grid">
                            <button type="submit" id="submitBtn" class="btn btn-suno-generate btn-lg mt-3">
                                <span id="btnText">Buat Musik</span>
                                <span id="spinner" class="spinner-border spinner-border-sm" style="display: none;"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div id="loadingIndicator" class="text-center mt-4" style="display: none;">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
                <p class="mt-2 text-dark">AI sedang membuat lagu Anda... Ini bisa memakan waktu 1-2 menit.</p>
            </div>
            <div id="resultContainer" class="mt-4" style="display: none;"></div>
            <div id="errorMessage" class="alert alert-danger mt-4" style="display: none;"></div>

             <!-- Bagian Riwayat -->
            <div id="historyContainer" class="history-section <?php echo empty($suno_history) ? 'd-none' : ''; ?>">
                <h2 class="text-center mb-4">Riwayat Terakhir</h2>
                <?php foreach ($suno_history as $item): ?>
                    <div class="history-item">
                        <p class="prompt-text"><strong>Prompt:</strong> "<?php echo htmlspecialchars($item['prompt']); ?>"</p>
                        <?php foreach ($item['data'] as $song): ?>
                            <div class="music-player-card">
                                <?php
                                    // Helper variables for song data
                                    $title = $song['title'] ?? 'Tanpa Judul';
                                    $imageUrl = $song['image_url'] ?? 'https://placehold.co/512x512/e2e8f0/2d3748?text=SunoAI';
                                    $audioUrl = $song['audio_url'] ?? '';
                                    $videoUrl = $song['video_url'] ?? '';
                                    $tags = !empty($song['tags']) ? explode(',', $song['tags']) : [];
                                ?>
                                <div class="row g-4 align-items-center">
                                    <div class="col-md-4 text-center">
                                        <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Album Art" class="img-fluid rounded shadow-sm">
                                    </div>
                                    <div class="col-md-8">
                                        <h3 class="song-title text-dark"><?php echo htmlspecialchars($title); ?></h3>
                                        <?php if(!empty($tags)): ?>
                                        <div class="song-tags">
                                            <?php foreach($tags as $tag): ?>
                                            <span class="badge rounded-pill"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                        <div class="mb-3">
                                            <?php if($audioUrl): ?>
                                            <audio controls src="<?php echo htmlspecialchars($audioUrl); ?>"></audio>
                                            <?php else: ?>
                                            <p class="text-muted">Audio tidak tersedia.</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-auto">
                                             <?php if($videoUrl): ?>
                                             <a href="<?php echo htmlspecialchars($videoUrl); ?>" class="btn btn-sm btn-outline-primary" target="_blank" download><i class="fas fa-video me-1"></i> Unduh Video</a>
                                             <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('musicGeneratorForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const spinner = document.getElementById('spinner');
    const resultContainer = document.getElementById('resultContainer');
    const errorMessage = document.getElementById('errorMessage');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const limitCountEl = document.getElementById('limitCount');
    const historyContainer = document.getElementById('historyContainer');


    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        errorMessage.style.display = 'none';
        resultContainer.style.display = 'none';
        submitBtn.disabled = true;
        btnText.textContent = 'Memproses...';
        spinner.style.display = 'inline-block';
        loadingIndicator.style.display = 'block';

        const query = document.getElementById('queryInput').value;

        try {
            const response = await fetch('sunoai_proxy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query: query })
            });

            const data = await response.json();

            // Selalu update sisa kredit jika informasi tersedia di respons
            if (data.data && typeof data.data.remaining_limit !== 'undefined') {
                limitCountEl.textContent = data.data.remaining_limit;
            }

            if (!response.ok || data.status !== 'Success' || (data.data && data.data.status === 'error')) {
                const apiErrorMessage = data.message || 'Gagal membuat musik.';
                throw new Error(apiErrorMessage);
            }
            
            // Tampilkan hasil utama
            displayResult(data.data.results);
            
            // Tambahkan ke riwayat di halaman
            updateHistoryView(query, data.data.results);

        } catch (error) {
            console.error('Error:', error);
            errorMessage.textContent = 'Terjadi kesalahan: ' + error.message;
            errorMessage.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
            btnText.textContent = 'Buat Musik';
            spinner.style.display = 'none';
            loadingIndicator.style.display = 'none';
        }
    });

    function displayResult(songsArray) {
        resultContainer.innerHTML = ''; 
        
        if (Array.isArray(songsArray) && songsArray.length > 0) {
            songsArray.forEach(song => {
                const card = createSongCard(song);
                resultContainer.appendChild(card);
            });
        } else {
            errorMessage.textContent = 'Format respons tidak dikenali atau tidak ada lagu yang dihasilkan.';
            errorMessage.style.display = 'block';
        }

        resultContainer.style.display = 'block';
    }
    
    // Fungsi untuk menambahkan item baru ke tampilan riwayat
    function updateHistoryView(prompt, songsArray) {
        historyContainer.classList.remove('d-none'); // Pastikan kontainer riwayat terlihat
        
        const historyItem = document.createElement('div');
        historyItem.className = 'history-item';

        const promptEl = document.createElement('p');
        promptEl.className = 'prompt-text';
        // Gunakan innerHTML untuk merender tag strong
        promptEl.innerHTML = `<strong>Prompt:</strong> "${escapeHtml(prompt)}"`;
        historyItem.appendChild(promptEl);
        
        songsArray.forEach(song => {
            historyItem.appendChild(createSongCard(song));
        });

        // Tambahkan item riwayat baru di bawah judul "Riwayat Terakhir"
        const titleElement = historyContainer.querySelector('h2');
        titleElement.after(historyItem);
        
        // Hapus item riwayat terlama jika jumlahnya lebih dari 5
        const historyItems = historyContainer.querySelectorAll('.history-item');
        if (historyItems.length > 5) {
            historyItems[historyItems.length - 1].remove();
        }
    }

    function createSongCard(song) {
        const cardDiv = document.createElement('div');
        cardDiv.className = 'music-player-card';

        const title = song.title || 'Tanpa Judul';
        const imageUrl = song.image_url || 'https://placehold.co/512x512/e2e8f0/2d3748?text=SunoAI';
        const audioUrl = song.audio_url;
        const videoUrl = song.video_url;
        const tags = song.tags ? song.tags.split(',').map(tag => tag.trim()) : [];

        let audioPlayer = audioUrl ? `<audio controls src="${escapeHtml(audioUrl)}"></audio>` : `<p class="text-muted">Audio tidak tersedia.</p>`;
        let videoDownload = videoUrl ? `<a href="${escapeHtml(videoUrl)}" class="btn btn-sm btn-outline-primary" target="_blank" download><i class="fas fa-video me-1"></i> Unduh Video</a>` : '';

        let tagsHtml = '';
        if (tags.length > 0) {
            tagsHtml = `<div class="song-tags">
                ${tags.map(tag => `<span class="badge rounded-pill">${escapeHtml(tag)}</span>`).join(' ')}
            </div>`;
        }
        
        cardDiv.innerHTML = `
            <div class="row g-4 align-items-center">
                <div class="col-md-4 text-center">
                    <img src="${escapeHtml(imageUrl)}" alt="Album Art" class="img-fluid rounded shadow-sm">
                </div>
                <div class="col-md-8">
                    <h3 class="song-title text-dark">${escapeHtml(title)}</h3>
                    ${tagsHtml}
                    <div class="mb-3">${audioPlayer}</div>
                    <div class="mt-auto">
                         ${videoDownload}
                    </div>
                </div>
            </div>
        `;
        return cardDiv;
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
// Ganti path ke footer-h.php jika perlu
include $path_prefix . 'footer-h.php';
?>
