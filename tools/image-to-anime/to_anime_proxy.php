<?php
// File: to_anime_proxy.php

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
define('BASE_API_URL', 'https://api.ibeng.my.id/api/ai/toanime');
define('API_KEY', 'xsbvvf2mjb');
// Menggunakan path absolut untuk keandalan
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('MAX_LIFETIME', 3 * 60); // 3 menit dalam detik
define('MAX_USAGE', 20); // Batas penggunaan global harian
define('LIMIT_FILE', __DIR__ . '/limit_toanime_counter.txt'); // Menggunakan path absolut

// --- Manajemen Limit Global Berbasis File ---
$today = date('Y-m-d');
$limit_data = ['date' => $today, 'count' => 0];

$file_handle = fopen(LIMIT_FILE, 'c+');
if ($file_handle) {
    if (flock($file_handle, LOCK_EX)) {
        $file_content = fread($file_handle, filesize(LIMIT_FILE) ?: 1);
        $saved_data = json_decode($file_content, true);

        if (is_array($saved_data) && isset($saved_data['date']) && $saved_data['date'] === $today) {
            $limit_data = $saved_data;
        }
        
        ftruncate($file_handle, 0);
        rewind($file_handle);
    }
} else {
    send_json_error('Tidak dapat memproses file limit di server.', 500);
}


// --- Logika Utama ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flock($file_handle, LOCK_UN);
    fclose($file_handle);
    send_json_error('Metode tidak diizinkan.', 405);
}

// --- Pemeriksaan Batas Penggunaan ---
if ($limit_data['count'] >= MAX_USAGE) {
    fwrite($file_handle, json_encode($limit_data));
    flock($file_handle, LOCK_UN);
    fclose($file_handle);
    send_json_error('Batas penggunaan global harian (' . MAX_USAGE . ') telah tercapai. Coba lagi besok.', 429);
}

// --- Validasi Input ---
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    send_json_error('Gagal mengunggah gambar. Pastikan Anda memilih file.');
}

$file = $_FILES['image'];

// Validasi file (ukuran dan tipe)
if ($file['size'] > MAX_FILE_SIZE) {
    send_json_error('Ukuran file terlalu besar. Maksimal 5 MB.');
}
$file_type = mime_content_type($file['tmp_name']);
if (!in_array($file_type, ALLOWED_TYPES)) {
    send_json_error('Format file tidak didukung. Harap unggah JPG, PNG, atau WEBP.');
}

// --- Proses Upload File ---
if (!is_dir(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0755, true)) {
        send_json_error('Gagal membuat direktori upload di server.', 500);
    }
}

if (!is_writable(UPLOAD_DIR)) {
    send_json_error('Direktori upload tidak dapat ditulis. Periksa izin folder.', 500);
}

$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$unique_filename = uniqid('img_', true) . '.' . $file_extension;
$upload_path = UPLOAD_DIR . $unique_filename;
if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    send_json_error('Gagal menyimpan file di server.', 500);
}


// --- Buat URL Server & Panggil API Eksternal ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$base_url = rtrim($protocol . $host . $script_path, '/');
$server_image_url = $base_url . '/uploads/' . $unique_filename;

$api_params = ['url' => $server_image_url, 'apikey' => API_KEY];
$api_url = BASE_API_URL . '?' . http_build_query($api_params);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120); 
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ToAnimeProxy/1.0');

$responseBody = curl_exec($ch);
$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

unlink($upload_path); // Hapus file sementara

// --- Tangani Respons dari API ---
if ($curlError || $httpStatusCode !== 200) {
    fwrite($file_handle, json_encode($limit_data));
    flock($file_handle, LOCK_UN);
    fclose($file_handle);
    $errorMessage = $curlError ?: 'Server AI mengembalikan status error: ' . $httpStatusCode;
    send_json_error($errorMessage, 502);
}

// Ganti nama author seperti yang diminta
$responseBody = str_replace('"author":"iBeng"', '"author":"Andrias"', $responseBody);
$decodedData = json_decode($responseBody, true);

// Periksa apakah JSON valid dan memiliki struktur yang diharapkan
if (json_last_error() !== JSON_ERROR_NONE || !isset($decodedData['status']) || $decodedData['status'] !== 'Success' || !isset($decodedData['data']['result'])) {
    fwrite($file_handle, json_encode($limit_data));
    flock($file_handle, LOCK_UN);
    fclose($file_handle);
    $errorMessage = isset($decodedData['message']) ? $decodedData['message'] : 'Respons API tidak valid atau gagal.';
    send_json_error($errorMessage, 502);
}

// --- Ambil Gambar dari URL Hasil ---
$imageUrl = $decodedData['data']['result'];

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $imageUrl);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 60);
$finalImageBody = curl_exec($ch2);
$finalHttpStatusCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$finalContentType = curl_getinfo($ch2, CURLINFO_CONTENT_TYPE);
$finalCurlError = curl_error($ch2);
curl_close($ch2);

// Periksa apakah pengambilan gambar kedua berhasil
if ($finalCurlError || $finalHttpStatusCode !== 200 || strpos($finalContentType, 'image/') !== 0) {
    fwrite($file_handle, json_encode($limit_data));
    flock($file_handle, LOCK_UN);
    fclose($file_handle);
    $errorMessage = $finalCurlError ?: 'Gagal mengunduh gambar dari URL hasil. Status: ' . $finalHttpStatusCode;
    send_json_error($errorMessage, 502);
}

// --- Sukses ---
$limit_data['count']++;
fwrite($file_handle, json_encode($limit_data));
flock($file_handle, LOCK_UN);
fclose($file_handle);

// Kirim hasil gambar final
header('Content-Type: ' . $finalContentType);
header('Content-Length: ' . strlen($finalImageBody));
echo $finalImageBody;
exit;
?>
