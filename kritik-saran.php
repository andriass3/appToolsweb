<?php
session_start(); // Mulai session untuk CAPTCHA dan CSRF

// --- KONFIGURASI KEAMANAN DAN UMUM ---
$page_title = "Kritik dan Saran";
$path_prefix = ''; // Diasumsikan file ini dan header/footer.php ada di root. Sesuaikan jika perlu.
$feedback_file = 'feedback.json'; // Nama file untuk menyimpan feedback
$tools_json_file = 'tools.json'; // Nama file JSON untuk daftar tools

// Konfigurasi Rate Limiting
$rate_limit_submissions = 5; // Maksimal 5 pengiriman
$rate_limit_window = 3600; // Dalam 1 jam (3600 detik)
$rate_limit_log_dir = __DIR__ . '/rate_limit_feedback_logs'; // Direktori log rate limit

// Konfigurasi Waktu Minimum Pengiriman Form (anti-bot cepat)
$min_submission_time_seconds = 5; // Minimal 5 detik setelah form dimuat

$tools_list = [];
$error_loading_tools = '';
$success_message = '';
$error_message = '';
$form_data = [
    'tool_name' => '',
    'critic_name' => '',
    'suggestion' => '',
    'captcha' => '',
    'csrf_token' => '',
    'honeypot' => '' // Field honeypot
];

// --- FUNGSI KEAMANAN DAN UTILITAS ---

// Fungsi untuk memuat tools (sama seperti sebelumnya)
function load_active_tools($json_file) {
    $active_tools = [];
    if (file_exists($json_file)) {
        $json_data = file_get_contents($json_file);
        if ($json_data === false) return ['error' => "Gagal membaca file $json_file."];
        $tools_data = json_decode($json_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) return ['error' => "Format file $json_file tidak valid: " . json_last_error_msg()];
        if (is_array($tools_data)) {
            foreach ($tools_data as $tool) {
                if (isset($tool['name'], $tool['status']) && $tool['status'] === 'active') {
                    $active_tools[] = $tool['name'];
                }
            }
        } else {
            return ['error' => "Data $json_file bukan array."];
        }
    } else {
        return ['error' => "File $json_file tidak ditemukan."];
    }
    return $active_tools;
}

// Fungsi untuk sanitasi input string dasar
function sanitize_string($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Fungsi untuk validasi dan sanitasi nama
function validate_and_sanitize_name($name) {
    $name = trim($name);
    // Hapus karakter selain huruf dan spasi
    $name_sanitized = preg_replace('/[^a-zA-Z\s]/u', '', $name); // Mendukung karakter unicode untuk huruf
    // Batasi panjang setelah sanitasi dasar
    if (mb_strlen($name_sanitized) > 15) {
        $name_sanitized = mb_substr($name_sanitized, 0, 15);
    }
    return htmlspecialchars($name_sanitized, ENT_QUOTES, 'UTF-8');
}

// Fungsi untuk validasi dan sanitasi saran
function validate_and_sanitize_suggestion($suggestion) {
    $suggestion = trim($suggestion);
    // Hapus karakter yang tidak diizinkan (selain huruf, angka, spasi, dan .,!?)
    $suggestion_sanitized = preg_replace('/[^\p{L}\p{N}\s.,!?]/u', '', $suggestion); // \p{L} untuk huruf unicode, \p{N} untuk angka unicode
    // Batasi panjang (sudah ada di validasi utama, tapi bisa juga di sini jika perlu)
    // if (mb_strlen($suggestion_sanitized) > 2000) {
    //     $suggestion_sanitized = mb_substr($suggestion_sanitized, 0, 2000);
    // }
    return htmlspecialchars($suggestion_sanitized, ENT_QUOTES, 'UTF-8');
}


// Fungsi untuk generate CSRF token
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fungsi untuk validasi CSRF token
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Fungsi untuk Rate Limiting
function check_rate_limit($ip, $limit, $window, $log_dir) {
    if (!is_dir($log_dir)) {
        if (!mkdir($log_dir, 0755, true)) {
            error_log("Gagal membuat direktori rate limit: $log_dir");
            return true; 
        }
    }
    $log_file = $log_dir . '/' . md5($ip) . '.json';
    $current_time = time();
    $ip_data = ['count' => 0, 'first_request_time' => $current_time];

    if (file_exists($log_file)) {
        $data = json_decode(file_get_contents($log_file), true);
        if ($data && ($current_time - $data['first_request_time']) < $window) {
            $ip_data = $data;
        }
    }

    if ($ip_data['count'] >= $limit) {
        return false; 
    }

    $ip_data['count']++;
    file_put_contents($log_file, json_encode($ip_data));
    return true; 
}


// Memuat daftar tools aktif
$loaded_tools_result = load_active_tools($tools_json_file);
if (isset($loaded_tools_result['error'])) {
    $error_loading_tools = $loaded_tools_result['error'];
} else {
    $tools_list = $loaded_tools_result;
}

// Generate CSRF token untuk form
$csrf_token = generate_csrf_token();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['form_load_time'] = time();
}


// --- LOGIKA CAPTCHA ---
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_SESSION['captcha_answer_expected'])) {
    $captcha_num1_new = rand(1, 10);
    $captcha_num2_new = rand(1, 10);
    $_SESSION['captcha_num1_display'] = $captcha_num1_new;
    $_SESSION['captcha_num2_display'] = $captcha_num2_new;
    $_SESSION['captcha_answer_expected'] = $captcha_num1_new + $captcha_num2_new;
}
$display_num1 = $_SESSION['captcha_num1_display'] ?? rand(1,5);
$display_num2 = $_SESSION['captcha_num2_display'] ?? rand(1,5);


// --- PROSES FORM JIKA METHOD POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data mentah dulu
    $raw_tool_name = $_POST['tool_name'] ?? '';
    $raw_critic_name = $_POST['critic_name'] ?? '';
    $raw_suggestion = $_POST['suggestion'] ?? '';
    
    // Sanitasi dasar awal
    $form_data['tool_name'] = sanitize_string($raw_tool_name);
    // Untuk nama dan saran, sanitasi lebih ketat akan dilakukan setelah validasi regex
    $form_data['captcha'] = sanitize_string($_POST['captcha'] ?? '');
    $form_data['csrf_token'] = $_POST['csrf_token'] ?? '';
    $form_data['honeypot'] = $_POST['website_url_extra_field'] ?? '';

    // 1. Validasi CSRF Token
    if (!validate_csrf_token($form_data['csrf_token'])) {
        $error_message .= "Kesalahan validasi formulir (CSRF token tidak valid). Silakan coba lagi.<br>";
    }

    // 2. Validasi Honeypot
    if (!empty($form_data['honeypot'])) {
        error_log("Potensi spam (honeypot terisi) dari IP: " . $_SERVER['REMOTE_ADDR']);
        $error_message .= "Terjadi kesalahan yang tidak terduga. Silakan coba lagi nanti.<br>";
    }

    // 3. Validasi Waktu Pengiriman
    if (isset($_SESSION['form_load_time'])) {
        $time_to_submit = time() - $_SESSION['form_load_time'];
        if ($time_to_submit < $min_submission_time_seconds) {
            error_log("Potensi spam (pengiriman terlalu cepat: {$time_to_submit}s) dari IP: " . $_SERVER['REMOTE_ADDR']);
            $error_message .= "Pengiriman terlalu cepat. Mohon isi formulir dengan seksama.<br>";
        }
    } else {
        $error_message .= "Sesi formulir tidak valid. Silakan muat ulang halaman dan coba lagi.<br>";
    }

    // 4. Validasi Rate Limiting
    $ip_address = $_SERVER['REMOTE_ADDR'];
    if (!check_rate_limit($ip_address, $rate_limit_submissions, $rate_limit_window, $rate_limit_log_dir)) {
        $error_message .= "Anda telah mengirimkan terlalu banyak feedback dalam waktu singkat. Silakan coba lagi nanti.<br>";
    }

    // Lanjutkan validasi lain hanya jika tidak ada error dari pemeriksaan keamanan awal
    if (empty($error_message)) {
        if (empty($form_data['tool_name'])) {
            $error_message .= "Silakan pilih nama tool.<br>";
        }

        // Validasi Nama Pengkritik (critic_name)
        if (!empty($raw_critic_name)) { // Hanya validasi jika diisi karena opsional
            if (!preg_match('/^[\p{L}\s]+$/u', $raw_critic_name)) { // Hanya huruf (unicode) dan spasi
                $error_message .= "Nama pengkritik hanya boleh berisi huruf dan spasi.<br>";
            }
            if (mb_strlen(trim($raw_critic_name)) > 15) { // Cek panjang setelah trim
                $error_message .= "Nama pengkritik terlalu panjang (maksimal 15 karakter).<br>";
            }
            $form_data['critic_name'] = validate_and_sanitize_name($raw_critic_name); // Sanitasi final
        } else {
            $form_data['critic_name'] = ''; // Jika kosong, pastikan string kosong
        }


        // Validasi Kritik dan Saran (suggestion)
        if (empty(trim($raw_suggestion))) {
            $error_message .= "Kritik dan saran tidak boleh kosong.<br>";
        } else {
            // Hanya huruf (unicode), angka (unicode), spasi, dan karakter .,!?
            if (!preg_match('/^[\p{L}\p{N}\s.,!?]+$/u', $raw_suggestion)) {
                $error_message .= "Kritik dan saran hanya boleh berisi huruf, angka, spasi, dan tanda baca (.,!?).<br>";
            }
            if (mb_strlen(trim($raw_suggestion)) > 2000) {
                $error_message .= "Kritik dan saran terlalu panjang (maksimal 2000 karakter).<br>";
            }
            // Sanitasi final setelah validasi regex
            $form_data['suggestion'] = validate_and_sanitize_suggestion($raw_suggestion);
        }


        if (empty($form_data['captcha'])) {
            $error_message .= "CAPTCHA tidak boleh kosong.<br>";
        } elseif (!isset($_SESSION['captcha_answer_expected']) || intval($form_data['captcha']) !== $_SESSION['captcha_answer_expected']) {
            $error_message .= "Jawaban CAPTCHA salah.<br>";
        }
    }

    // Jika semua validasi lolos
    if (empty($error_message)) {
        $new_feedback = [
            'timestamp' => date('Y-m-d H:i:s T'),
            'tool_name' => $form_data['tool_name'], 
            'critic_name' => $form_data['critic_name'], 
            'suggestion' => $form_data['suggestion'], 
            'ip_address' => $ip_address
        ];

        $feedback_data = [];
        if (file_exists($feedback_file)) {
            $json_data = file_get_contents($feedback_file);
            if ($json_data !== false) {
                $feedback_data = json_decode($json_data, true);
                if (!is_array($feedback_data)) $feedback_data = [];
            }
        }
        $feedback_data[] = $new_feedback;

        if (file_put_contents($feedback_file, json_encode($feedback_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX)) {
            $success_message = "Terima kasih! Kritik dan saran Anda telah berhasil dikirim.";
            $form_data = array_fill_keys(array_keys($form_data), ''); 
            unset($_SESSION['csrf_token']); 
            unset($_SESSION['form_load_time']);
            unset($_SESSION['captcha_answer_expected']); 
            $csrf_token = generate_csrf_token(); 
            $_SESSION['form_load_time'] = time(); 
        } else {
            $error_message = "Maaf, terjadi kesalahan saat menyimpan feedback Anda. Silakan coba lagi.";
        }
    }
}

// Panggil header
if (file_exists($path_prefix . 'header.php')) {
    include $path_prefix . 'header.php';
} else {
    if (file_exists('header.php')) include 'header.php';
    else echo "<!DOCTYPE html><html><head><title>$page_title</title><link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'></head><body><main class='container mt-4'><h1>Error: header.php tidak ditemukan.</h1>";
}
?>

<div class="tool-page-container"> 
    <div class="text-center mb-4">
        <h1><i class="fas fa-comments me-2"></i><?php echo $page_title; ?></h1>
        <p class="lead text-muted">Kami menghargai masukan Anda untuk meningkatkan kualitas layanan kami.</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <strong>Terjadi Kesalahan:</strong><br>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_loading_tools)): ?>
                <div class="alert alert-warning" role="alert">
                    <strong>Peringatan:</strong> <?php echo htmlspecialchars($error_loading_tools); ?> Daftar tools mungkin tidak lengkap.
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="feedbackForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div style="position: absolute; left: -5000px;" aria-hidden="true">
                    <label for="website_url_extra_field">Jangan isi field ini</label>
                    <input type="text" name="website_url_extra_field" id="website_url_extra_field" tabindex="-1" autocomplete="off"
                           value="<?php echo htmlspecialchars($form_data['honeypot']); ?>">
                </div>

                <div class="mb-3">
                    <label for="tool_name" class="form-label fs-5">Nama Tool yang Dikomentari:</label>
                    <select class="form-select form-select-lg" id="tool_name" name="tool_name" required>
                        <option value="" disabled <?php echo empty($form_data['tool_name']) ? 'selected' : ''; ?>>-- Pilih Tool --</option>
                        <?php if (!empty($tools_list)): ?>
                            <?php foreach ($tools_list as $tool_name): ?>
                                <option value="<?php echo htmlspecialchars($tool_name); ?>" <?php echo ($form_data['tool_name'] == $tool_name) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tool_name); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Tidak ada tools aktif yang tersedia</option>
                        <?php endif; ?>
                        <option value="Lainnya/Umum" <?php echo ($form_data['tool_name'] == 'Lainnya/Umum') ? 'selected' : ''; ?>>Lainnya/Umum</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="critic_name" class="form-label fs-5">Nama Anda:</label>
                    <input type="text" class="form-control form-control-lg" id="critic_name" name="critic_name" value="<?php echo htmlspecialchars($form_data['critic_name']); ?>" placeholder="Nama Anda" maxlength="15">
                </div>

                <div class="mb-3">
                    <label for="suggestion" class="form-label fs-5">Kritik dan Saran Anda :</label>
                    <textarea class="form-control form-control-lg" id="suggestion" name="suggestion" rows="6" placeholder="Tuliskan kritik dan saran Anda di sini..." required maxlength="2000"><?php echo htmlspecialchars($form_data['suggestion']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="captcha" class="form-label fs-5">Verifikasi (CAPTCHA):</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light">
                            <?php echo $display_num1; ?> + <?php echo $display_num2; ?> = ?
                        </span>
                        <input type="number" class="form-control" id="captcha" name="captcha" placeholder="Jawaban" required autocomplete="off">
                    </div>
                    <small class="form-text text-muted">Masukkan hasil penjumlahan untuk verifikasi.</small>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-paper-plane me-2"></i>Kirim Kritik dan Saran
                </button>
            </form>
        </div>
    </div>
</div>

<?php
// Panggil footer
if (file_exists($path_prefix . 'footer.php')) {
    include $path_prefix . 'footer.php';
} else {
     if (file_exists('footer.php')) include 'footer.php';
    else echo "</main></body></html>"; 
}
?>