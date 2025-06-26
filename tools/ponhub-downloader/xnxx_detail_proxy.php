<?php
// File: xnxx_detail_proxy.php

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

// --- Fungsi untuk mengirim respons error ---
function send_json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['status' => 'Error', 'message' => $message]);
    exit;
}

// --- Konfigurasi ---
define('BASE_API_URL', 'https://api.ibeng.my.id/api/downloader/xnxx');
define('API_KEY', 'xsbvvf2mjb');

// --- Logika Utama ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json_error('Metode tidak diizinkan. Harap gunakan metode GET.', 405);
}

if (!isset($_GET['url']) || empty(trim($_GET['url'])) || !filter_var($_GET['url'], FILTER_VALIDATE_URL)) {
    send_json_error('Parameter "url" tidak valid atau kosong.');
}

$video_url = trim($_GET['url']);

// --- Panggil API Eksternal ---
$api_params = ['url' => $video_url, 'apikey' => API_KEY];
$api_url = BASE_API_URL . '?' . http_build_query($api_params);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) DetailProxy/1.0');

$responseBody = curl_exec($ch);
$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// --- Tangani Respons ---
if ($curlError || $httpStatusCode !== 200) {
    $errorMessage = $curlError ?: 'Server API detail mengembalikan status error: ' . $httpStatusCode;
    send_json_error($errorMessage, 502);
}

// Ganti nama author
$responseBody = str_replace('"author":"iBeng"', '"author":"Andrias"', $responseBody);

$decodedData = json_decode($responseBody);
if (json_last_error() !== JSON_ERROR_NONE) {
    send_json_error('Respons dari API detail bukan format JSON yang valid.', 502);
}

// Kirim respons ke frontend
echo $responseBody;
exit;
?>
