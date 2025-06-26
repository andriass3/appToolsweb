<?php
// session_start();

// --- LOGIKA UNTUK TAMPILAN HALAMAN (Tidak ada pemrosesan AJAX di sini) ---
$page_title = "Cek Validasi Rekening Bank";
$path_prefix = '../../'; // Sesuaikan jika perlu

// Header fallback jika tidak ada header.php
if (file_exists($path_prefix . 'header.php')) {
    include $path_prefix . 'header.php';
} else {
    echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>" . htmlspecialchars($page_title) . "</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css' rel='stylesheet'>";
    echo "<style> body { padding-top: 20px; padding-bottom: 20px; background-color: #f8f9fa; } .footer { padding: 1rem 0; margin-top: 2rem; border-top: 1px solid #dee2e6; text-align: center; } </style>";
    echo "</head><body><main class='container'>";
}
?>

<style>
    .result-card {
        margin-top: 1.5rem;
    }
    .progress-bar-label {
        font-weight: 500;
        margin-bottom: 0.25rem;
    }
    .badge-score {
        font-size: 0.9em;
    }
    .json-result {
        background-color: #212529;
        color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.25rem;
        max-height: 400px;
        overflow-y: auto;
        white-space: pre-wrap;
        font-family: monospace;
        font-size: 0.85rem;
    }
    /* CSS untuk animasi progress bar */
    @keyframes progress-bar-stripes-animation {
        from { background-position-x: 1rem; }
        to { background-position-x: 0; }
    }
    .progress-bar-animated {
        animation: progress-bar-stripes-animation 1s linear infinite;
    }
    .progress-bar {
        transition: width 0.6s ease;
    }
    .analysis-summary-card {
        text-align: center;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        color: white;
    }
    .analysis-summary-card h4 {
        margin: 0;
        font-size: 1.5rem;
    }
    /* Gaya untuk spinner */
    .spinner-border {
        display: inline-block;
        width: 2rem;
        height: 2rem;
        vertical-align: text-bottom;
        border: 0.25em solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: .75s linear infinite spinner-border;
    }
    @keyframes spinner-border {
        to { transform: rotate(360deg); }
    }
</style>

<div class="tool-page-container">
    <div class="text-center mb-4">
        <h1><i class="fas fa-university me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
        <p class="lead text-muted">Validasi cepat status dan nama pemilik rekening bank atau e-wallet Anda.</p>
        <!-- Tombol untuk memicu modal tutorial -->
        <button type="button" class="btn btn-outline-info btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#tutorialModal">
            <i class="fas fa-book me-2"></i>Tutorial Penggunaan API
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <!-- API Key Input -->
            <div class="mb-3">
                <label for="apiKeyInput" class="form-label">API Key (Opsional):</label>
                <input type="text" class="form-control" id="apiKeyInput" name="apikey" placeholder="Masukkan API Key Anda jika ada">
                <small class="form-text text-muted">Jika kosong, akan menggunakan batasan global.</small>
            </div>
            <!-- Limit section -->
            <div class="limit-wrap mb-4">
                <label class="form-label">Penggunaan Harian:</label>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-info" id="usageProgressBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="limit-text mt-2 text-end">
                    <span id="remainingUsage">0</span> / <span id="totalLimit">0</span> tersisa hari ini
                </div>
            </div>
<hr>Limit habis? Hub <a href="https://wa.me/6282279698099" >admin</a><hr>
            <form id="bankValidationForm" method="POST">
                <div class="mb-3">
                    <label for="accountType" class="form-label">Pilih Bank / E-Wallet:</label>
                    <select id="accountType" name="account_type" class="form-select form-select-lg" required>
                        <option disabled selected value="">— Memuat opsi bank... —</option>
                        <!-- Opsi akan dimuat di sini oleh JavaScript -->
                    </select>
                </div>
                <div class="mb-3">
                    <label for="accountNumber" class="form-label">Nomor Rekening / E-Wallet:</label>
                    <input type="text" class="form-control" id="accountNumber" name="account_number" placeholder="Masukkan nomor rekening atau e-wallet" required pattern="\d+">
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-2" id="submitBtn">
                    Cek Validasi
                </button>
            </form>
        </div>
    </div>

    <div id="loadingIndicator" class="text-center mt-4" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Memeriksa rekening, mohon tunggu...</p>
    </div>

    <div id="resultContainer" class="result-card" style="display:none;">
        <div class="card-body">
            <h5 class="card-title"><i class="fas fa-info-circle me-2"></i>Hasil Validasi</h5>
            <div id="validationResultText" class="mt-3 fs-5"></div>
        </div>
    </div>

</div>

<!-- Modal Tutorial API -->
<div class="modal fade" id="tutorialModal" tabindex="-1" aria-labelledby="tutorialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tutorialModalLabel">Tutorial Penggunaan API Validasi Rekening</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>1. Endpoint API:</h6>
                <p>Gunakan endpoint berikut untuk melakukan validasi rekening:</p>
                <pre><code class="text-dark">https://app.andrias.web.id/tools/cek-validasi-rekening/api.php</code></pre>

                <h6>2. Metode Permintaan (Request Method):</h6>
                <p>Gunakan metode <code>GET</code> untuk mengambil opsi bank/e-wallet dan <code>GET</code> atau <code>POST</code> untuk validasi akun.</p>

                <h6>3. Aksi yang Tersedia:</h6>
                <ul>
                    <li>
                        <strong>Mendapatkan Daftar Bank/E-Wallet (<code>action=get_options</code>):</strong>
                        <p>Untuk mendapatkan daftar bank dan e-wallet yang didukung, lakukan permintaan <code>GET</code> ke API dengan parameter <code>action=get_options</code>.</p>
                        <pre><code class="text-dark">GET https://app.andrias.web.id/tools/cek-validasi-rekening/api.php?action=get_options&apikey=YOUR_API_KEY</code></pre>
                        <p><strong>Contoh Respons:</strong></p>
                        <pre><code class="json bg-light text-dark">{
    "ok": true,
    "options": [
        { "code": "10001", "label": "BANK BRI" },
        { "code": "10002", "label": "BANK MANDIRI" },
        // ... dan lainnya
    ],
    "remain": 49,
    "limit": 50
}</code></pre>
                    </li>
                    <li>
                        <strong>Validasi Akun (<code>action=validate_account</code>):</strong>
                        <p>Untuk memvalidasi nomor rekening/e-wallet, lakukan permintaan <code>GET</code> atau <code>POST</code> ke API dengan parameter <code>action=validate_account</code>, <code>account_type</code> (<strong>kode unik bank/e-wallet</strong>), <code>account_number</code>, dan <code>apikey</code>.</p>
                        <pre><code class="text-dark">GET https://app.andrias.web.id/tools/cek-validasi-rekening/api.php?action=validate_account&account_type=10001&account_number=1234567890&apikey=YOUR_API_KEY</code></pre>
                        <pre><code class="text-dark">POST https://app.andrias.web.id/tools/cek-validasi-rekening/api.php
Content-Type: application/x-www-form-urlencoded

action=validate_account&account_type=10001&account_number=1234567890&apikey=YOUR_API_KEY</code></pre>
                        <p><strong>Contoh Respons Sukses:</strong></p>
                        <pre><code class="json bg-light text-dark">{
    "ok": true,
    "account_name": "Nama Pemilik Rekening",
    "account_number": "1234567890",
    "bank_label": "BANK BRI",
    "source": "by app.andrias.web.id",
    "remain": 48,
    "limit": 50
}</code></pre>
                        <p><strong>Contoh Respons Gagal:</strong></p>
                        <pre><code class="json bg-light text-dark">{
    "ok": false,
    "msg": "Pesan kesalahan, misalnya 'Nomor rekening tidak ditemukan'.",
    "remain": 48,
    "limit": 50
}</code></pre>
                    </li>
                </ul>

                <h6>4. Batas Penggunaan (Limit):</h6>
                <p>API ini memiliki batas penggunaan harian yang dapat bervariasi tergantung pada API Key yang digunakan. Anda dapat melihat sisa penggunaan melalui properti <code>remain</code> dalam respons API.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bankValidationForm = document.getElementById('bankValidationForm');
    const accountTypeSelect = document.getElementById('accountType');
    const submitBtn = document.getElementById('submitBtn');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const resultContainer = document.getElementById('resultContainer');
    const validationResultText = document.getElementById('validationResultText');
    const remainingUsageSpan = document.getElementById('remainingUsage');
    const totalLimitSpan = document.getElementById('totalLimit');
    const usageProgressBar = document.getElementById('usageProgressBar');
    const accountNumberInput = document.getElementById('accountNumber');
    const apiKeyInput = document.getElementById('apiKeyInput'); // Input API Key

    // Fungsi untuk memperbarui tampilan penggunaan limit
    function updateUsageDisplay(remaining, limit) {
        remainingUsageSpan.textContent = remaining !== undefined ? remaining : 'N/A';
        totalLimitSpan.textContent = limit !== undefined ? limit : 'N/A';
        if (limit && limit > 0) {
            const usedPercentage = ((limit - remaining) / limit) * 100;
            usageProgressBar.style.width = `${usedPercentage}%`;
            usageProgressBar.setAttribute('aria-valuenow', usedPercentage);
        } else {
            usageProgressBar.style.width = `0%`;
            usageProgressBar.setAttribute('aria-valuenow', 0);
        }
    }

    // Event listener untuk perubahan pada input API Key
    apiKeyInput.addEventListener('input', function() {
        // Saat API Key berubah, muat ulang opsi bank untuk mendapatkan batasan yang relevan
        // dan perbarui tampilan penggunaan.
        loadBankOptions();
    });

    // Fungsi untuk memuat opsi bank secara dinamis dari API
    async function loadBankOptions() {
        accountTypeSelect.innerHTML = '<option disabled selected value="">— Memuat opsi bank... —</option>';
        const currentApiKey = apiKeyInput.value.trim(); // Ambil API Key saat ini

        let url = 'api.php?action=get_options'; // Targetkan api.php
        if (currentApiKey) {
            url += `&apikey=${encodeURIComponent(currentApiKey)}`;
        }

        try {
            const response = await fetch(url);
            const data = await response.json();

            if (data.ok && data.options) {
                accountTypeSelect.innerHTML = '<option disabled selected value="">— Pilih Bank —</option>';
                data.options.forEach(option => {
                    const optElement = document.createElement('option');
                    optElement.value = option.code;
                    optElement.textContent = option.label;
                    accountTypeSelect.appendChild(optElement);
                });
                updateUsageDisplay(data.remain, data.limit);
            } else {
                console.error('Gagal memuat opsi bank:', data.error || data.msg);
                accountTypeSelect.innerHTML = '<option disabled selected value="">— Gagal memuat opsi —</option>';
                updateUsageDisplay(0, 0); // Atur ke 0 jika gagal memuat
            }
        } catch (error) {
            console.error('Error fetching bank options:', error);
            accountTypeSelect.innerHTML = '<option disabled selected value="">— Error memuat opsi —</option>';
            updateUsageDisplay(0, 0); // Atur ke 0 jika ada error
        }
    }

    // Panggil fungsi untuk memuat opsi bank saat halaman dimuat pertama kali
    loadBankOptions();

    // Handler saat form disubmit
    bankValidationForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        const accountTypeCode = accountTypeSelect.value;
        const accountNumber = accountNumberInput.value;
        const currentApiKey = apiKeyInput.value.trim();

        if (!accountTypeCode || accountTypeCode === '' || !accountNumber || accountNumber.trim() === '' || !/^\d+$/.test(accountNumber)) {
            validationResultText.innerHTML = `
                <div class="alert alert-danger mt-3 shadow-sm border-0 animate__animated animate__fadeInUp">
                    <h4 class="alert-heading text-danger mb-3">
                        <i class="fas fa-exclamation-triangle me-2 animate__animated animate__shakeX"></i> Validasi Gagal!
                    </h4>
                    <hr>
                    <p class="mb-0"><strong>Pesan:</strong> Mohon lengkapi semua field dengan benar.</p>
                </div>
            `;
            resultContainer.style.display = 'block';
            return;
        }

        loadingIndicator.style.display = 'block';
        resultContainer.style.display = 'none';
        validationResultText.innerHTML = '';

        submitBtn.disabled = true;
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memeriksa...';

        const formData = new FormData();
        formData.append('action', 'validate_account');
        formData.append('account_type', accountTypeCode);
        formData.append('account_number', accountNumber);
        if (currentApiKey) {
            formData.append('apikey', currentApiKey);
        }

        try {
            const response = await fetch('api.php', { // Targetkan api.php
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.ok) {
                validationResultText.innerHTML = `
                    <div class="alert alert-success mt-3 shadow-sm border-0 animate__animated animate__fadeInUp">
                        <h4 class="alert-heading text-success mb-3">
                            <i class="fas fa-check-circle me-2 animate__animated animate__bounceIn"></i> Validasi Berhasil!
                        </h4>
                        <hr>
                        <div class="row g-2 align-items-center">
                            <div class="col-auto text-primary">
                                <i class="fas fa-university fa-lg"></i>
                            </div>
                            <div class="col">
                                <p class="mb-1"><strong>Bank/E-Wallet:</strong> <span class="fw-bold">${data.bank_label}</span></p>
                            </div>
                        </div>
                        <div class="row g-2 align-items-center mt-2">
                            <div class="col-auto text-primary">
                                <i class="fas fa-credit-card fa-lg"></i>
                            </div>
                            <div class="col">
                                <p class="mb-1"><strong>Nomor Rekening/E-Wallet:</strong> <span class="fw-bold">${data.account_number}</span></p>
                            </div>
                        </div>
                        <div class="row g-2 align-items-center mt-2">
                            <div class="col-auto text-primary">
                                <i class="fas fa-user-check fa-lg"></i>
                            </div>
                            <div class="col">
                                <p class="mb-0"><strong>Nama Pemilik:</strong> <span class="fw-bold text-success">${data.account_name}</span></p>
                            </div>
                        </div>
                        <div class="row g-2 align-items-center mt-2">
                            <div class="col-auto text-primary">
                                <i class="fas fa-link fa-lg"></i>
                            </div>
                            <div class="col">
                                <p class="mb-0"><strong>Sumber:</strong> <span class="fw-bold">${data.source}</span></p>
                            </div>
                        </div>
                    </div>
                `;
                updateUsageDisplay(data.remain, data.limit);
            } else {
                const selectedBankLabel = accountTypeSelect.options[accountTypeSelect.selectedIndex].textContent;

                validationResultText.innerHTML = `
                    <div class="alert alert-danger mt-3 shadow-sm border-0 animate__animated animate__fadeInUp">
                        <h4 class="alert-heading text-danger mb-3">
                            <i class="fas fa-exclamation-triangle me-2 animate__animated animate__shakeX"></i> Validasi Gagal!
                        </h4>
                        <hr>
                        <div class="row g-2 align-items-center">
                            <div class="col-auto text-danger">
                                <i class="fas fa-university fa-lg"></i>
                            </div>
                            <div class="col">
                                <p class="mb-1"><strong>Bank/E-Wallet:</strong> <span class="fw-bold">${selectedBankLabel}</span></p>
                            </div>
                        </div>
                        <div class="row g-2 align-items-center mt-2">
                            <div class="col-auto text-danger">
                                <i class="fas fa-credit-card fa-lg"></i>
                            </div>
                            <div class="col">
                                <p class="mb-1"><strong>Nomor Rekening/E-Wallet:</strong> <span class="fw-bold">${accountNumber}</span></p>
                            </div>
                        </div>
                        <div class="row g-2 align-items-center mt-2">
                            <div class="col-auto text-danger">
                                <i class="fas fa-info-circle fa-lg"></i>
                            </div>
                            <div class="col">
                                <p class="mb-0"><strong>Pesan:</strong> ${data.msg}</p>
                            </div>
                        </div>
                    </div>
                `;
                updateUsageDisplay(data.remain, data.limit);
            }

        } catch (error) {
            console.error('Error:', error);
            validationResultText.innerHTML = `
                <div class="alert alert-danger mt-3 shadow-sm border-0 animate__animated animate__fadeInUp">
                    <h4 class="alert-heading text-danger mb-3">
                        <i class="fas fa-times-circle me-2 animate__animated animate__wobble"></i> Terjadi Kesalahan!
                    </h4>
                    <hr>
                    <p class="mb-0"><strong>Pesan Error:</strong> ${error.message}. Mohon coba lagi.</p>
                </div>
            `;
            updateUsageDisplay(0, 0);
        } finally {
            loadingIndicator.style.display = 'none';
            resultContainer.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });
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
