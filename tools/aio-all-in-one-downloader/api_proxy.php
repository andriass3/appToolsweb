<?php
// File: api_proxy.php

// session_start(); // Dihapus
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

// --- Konfigurasi Keamanan & Umum ---
define('BASE_API_URL', 'https://api.ferdev.my.id');
define('RATE_LIMIT_DIR', __DIR__ . '/rate_limit_logs/');
define('RATE_LIMIT_COUNT', 20);
define('RATE_LIMIT_WINDOW_SECONDS', 3600);

function check_rate_limit($ip) {
    if (!is_dir(RATE_LIMIT_DIR)) {
        if (!mkdir(RATE_LIMIT_DIR, 0755, true)) {
            return true;
        }
    }
    $log_file = RATE_LIMIT_DIR . md5($ip) . '.json';
    $current_time = time();
    $ip_data = ['count' => 0, 'first_request_time' => $current_time];

    if (file_exists($log_file)) {
        $data = json_decode(file_get_contents($log_file), true);
        if ($data && ($current_time - $data['first_request_time']) < RATE_LIMIT_WINDOW_SECONDS) {
            $ip_data = $data;
        }
    }
    if ($ip_data['count'] >= RATE_LIMIT_COUNT) {
        return false;
    }
    $ip_data['count']++;
    file_put_contents($log_file, json_encode($ip_data));
    return true;
}

// --- Logika Utama ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit;
}

// --- BLOK VALIDASI CSRF DIHAPUS ---

// PERUBAHAN: Baca data dari raw input JSON, bukan dari $_POST
$input_data = json_decode(file_get_contents('php://input'), true);

// Validasi Rate Limiting
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!check_rate_limit($ip_address)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Anda telah mencapai batas penggunaan. Silakan coba lagi nanti.']);
    exit;
}

// Ambil data dari $input_data
$service = $input_data['service'] ?? '';
$link = $input_data['link'] ?? '';

if (empty($service) || empty($link)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap.']);
    exit;
}

$endpoint_map = [
    'capcut' => '/downloader/capcut',
    'douyin' => '/downloader/douyin',
    'facebook' => '/downloader/facebook',
    'instagram' => '/downloader/instagram',
    'xnxx' => '/downloader/xnxx',
    'ytmp3' => '/downloader/ytmp3'
];

if (!array_key_exists($service, $endpoint_map)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Platform yang dipilih tidak valid.']);
    exit;
}

$endpoint = $endpoint_map[$service];
$api_url = BASE_API_URL . $endpoint . '?link=' . urlencode($link);

// Panggilan cURL ke API eksternal
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 AIOProxy/1.0');
$responseBody = curl_exec($ch);
$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menghubungi server downloader: ' . $curlError]);
    exit;
}

// PERBAIKAN: Penanganan error yang lebih baik jika status bukan 200
if ($httpStatusCode !== 200) {
    http_response_code(502); // Bad Gateway, karena masalah ada di server upstream

    // Coba decode respons error dari API eksternal untuk mendapatkan pesan yang lebih spesifik
    $errorData = json_decode($responseBody, true);
    $errorMessage = 'Server downloader mengembalikan status error: ' . $httpStatusCode;

    // Jika ada pesan error spesifik dari API, gunakan itu
    if ($errorData && isset($errorData['message'])) {
        $errorMessage = $errorData['message'];
    } elseif ($errorData && isset($errorData['msg'])) { // Alternatif kunci pesan error
        $errorMessage = $errorData['msg'];
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $errorMessage
    ]);
    exit;
}

echo $responseBody;
exit;

?>
