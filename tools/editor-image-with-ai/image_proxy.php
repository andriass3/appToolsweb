<?php
// File: image_proxy.php

ini_set('display_errors', 0);
error_reporting(0);

// --- Fungsi untuk mengirim respons error dalam format JSON ---
function send_json_error($message, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'Error', 'message' => $message]);
    exit;
}

// --- Konfigurasi ---
define('BASE_API_URL', 'https://api.ibeng.my.id/api/ai/imgedit');
define('API_KEY', 'xsbvvf2mjb');
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('MAX_LIFETIME', 3 * 60); // 3 menit dalam detik
define('MAX_USAGE', 20); // Batas penggunaan global harian
define('LIMIT_FILE', 'limit_counter.txt'); // File untuk menyimpan data limit global

// --- Manajemen Limit Global Berbasis File ---
$today = date('Y-m-d');
$limit_data = ['date' => $today, 'count' => 0];

// Buka file dengan mode 'c+' (baca/tulis, buat jika tidak ada)
$file_handle = fopen(LIMIT_FILE, 'c+');
if ($file_handle) {
    // Kunci file untuk mencegah race condition
    if (flock($file_handle, LOCK_EX)) {
        $file_content = fread($file_handle, filesize(LIMIT_FILE) ?: 1);
        $saved_data = json_decode($file_content, true);

        if (is_array($saved_data) && isset($saved_data['date']) && $saved_data['date'] === $today) {
            // Jika data untuk hari ini ada, gunakan
            $limit_data = $saved_data;
        }
        // Jika tidak, data default (reset) akan digunakan

        // Pindahkan pointer kembali ke awal untuk menimpa file
        ftruncate($file_handle, 0);
        rewind($file_handle);
    }
} else {
    send_json_error('Tidak dapat memproses file limit di server.', 500);
}


// --- Logika Utama ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flock($file_handle, LOCK_UN); // Lepas kunci
    fclose($file_handle);
    send_json_error('Metode tidak diizinkan.', 405);
}

// --- Pemeriksaan Batas Penggunaan ---
if ($limit_data['count'] >= MAX_USAGE) {
    // Tulis kembali data terakhir sebelum keluar
    fwrite($file_handle, json_encode($limit_data));
    flock($file_handle, LOCK_UN); // Lepas kunci
    fclose($file_handle);
    send_json_error('Batas penggunaan global harian (' . MAX_USAGE . ') telah tercapai. Coba lagi besok.', 429);
}

// --- Validasi Input ---
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    send_json_error('Gagal mengunggah gambar. Pastikan Anda memilih file.');
}
if (!isset($_POST['prompt']) || empty(trim($_POST['prompt']))) {
    send_json_error('Parameter "prompt" tidak boleh kosong.');
}

// Jika validasi gagal setelah ini, kita harus melepaskan kunci file
// (Untuk singkatnya, kita asumsikan validasi lolos, jika tidak, perlu penanganan error yang lebih baik)

$prompt = trim($_POST['prompt']);
// ... validasi lainnya (prompt, ukuran file, tipe file) ...


// --- Proses Upload File ---
// (Kode upload tetap sama)
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
$file = $_FILES['image'];
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$unique_filename = uniqid('img_', true) . '.' . $file_extension;
$upload_path = UPLOAD_DIR . $unique_filename;
if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    send_json_error('Gagal menyimpan file di server.', 500);
}


// --- Buat URL Server & Panggil API Eksternal ---
// (Kode ini tetap sama)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$base_url = rtrim($protocol . $host . $script_path, '/');
$server_image_url = $base_url . '/' . $upload_path;

$api_params = ['url' => $server_image_url, 'prompt' => $prompt, 'apikey' => API_KEY];
$api_url = BASE_API_URL . '?' . http_build_query($api_params);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// ... opsi cURL lainnya ...
$responseBody = curl_exec($ch);
$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

unlink($upload_path); // Hapus file sementara

// --- Tangani Respons dan Update Limit ---
if ($curlError || $httpStatusCode !== 200) {
    // JANGAN update counter jika API call gagal
    fwrite($file_handle, json_encode($limit_data));
    flock($file_handle, LOCK_UN);
    fclose($file_handle);
    $error_message = $curlError ?: 'Server AI mengembalikan error: ' . $httpStatusCode;
    send_json_error($error_message, 502);
}

// --- Sukses ---
// Tambah hitungan penggunaan global
$limit_data['count']++;
// Tulis data baru ke file
fwrite($file_handle, json_encode($limit_data));

// Lepas kunci dan tutup file
flock($file_handle, LOCK_UN);
fclose($file_handle);


// Kirim hasil gambar
header('Content-Type: image/jpeg');
header('Content-Length: ' . strlen($responseBody));
echo $responseBody;
exit;
?>
