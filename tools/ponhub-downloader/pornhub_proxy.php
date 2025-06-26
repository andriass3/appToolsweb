<?php
// File: pornhub_proxy.php

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

// --- Fungsi untuk mengirim respons error dalam format JSON ---
function send_json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['status' => 'Error', 'message' => $message]);
    exit;
}

// --- Konfigurasi ---
define('BASE_API_URL', 'https://api.ibeng.my.id/api/search/xnxx'); // URL Diperbarui
define('API_KEY', 'xsbvvf2mjb');

// --- Logika Utama ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json_error('Metode tidak diizinkan. Harap gunakan metode GET.', 405);
}

if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    send_json_error('Parameter "q" (query) tidak boleh kosong.');
}

$query = trim($_GET['q']);

// --- Panggil API Eksternal ---
$api_params = ['q' => $query, 'apikey' => API_KEY];
$api_url = BASE_API_URL . '?' . http_build_query($api_params);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SearchProxy/1.0');

$responseBody = curl_exec($ch);
$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// --- Tangani Respons dari API ---
if ($curlError || $httpStatusCode !== 200) {
    $errorMessage = $curlError ?: 'Server API eksternal mengembalikan status error: ' . $httpStatusCode;
    send_json_error($errorMessage, 502);
}

// Ganti nama author seperti yang diminta
$responseBody = str_replace('"author":"iBeng"', '"author":"Andrias"', $responseBody);

// Cek apakah responsnya valid JSON
$decodedData = json_decode($responseBody);
if (json_last_error() !== JSON_ERROR_NONE) {
    send_json_error('Respons dari API eksternal bukan format JSON yang valid.', 502);
}

// Kirim respons yang sudah dimodifikasi ke frontend
echo $responseBody;
exit;
?>
