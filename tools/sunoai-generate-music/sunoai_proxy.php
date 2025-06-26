<?php
// File: sunoai_proxy.php

ini_set('display_errors', 0);
error_reporting(0);
// Mulai atau lanjutkan sesi yang ada
session_start(); 

header('Content-Type: application/json');

// --- Konfigurasi ---
define('BASE_API_URL', 'https://api.ibeng.my.id/api/ai/sunoai');
// Ganti dengan API Key Anda yang valid
define('API_KEY', 'xsbvvf2mjb'); 
// Tentukan batas maksimal penggunaan per sesi
define('MAX_USAGE', 20); 

// --- Inisialisasi Sesi ---
// Jika variabel sesi untuk hitungan penggunaan belum ada, inisialisasi ke 0.
if (!isset($_SESSION['suno_usage'])) {
    $_SESSION['suno_usage'] = 0;
}
// Jika variabel sesi untuk riwayat belum ada, inisialisasi sebagai array kosong.
if (!isset($_SESSION['suno_history'])) {
    $_SESSION['suno_history'] = [];
}

// --- Logika Utama ---
// Hanya izinkan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'Error', 'message' => 'Metode tidak diizinkan.']);
    exit;
}

// --- Pemeriksaan Batas Penggunaan ---
// Periksa apakah pengguna telah mencapai batas maksimal.
if ($_SESSION['suno_usage'] >= MAX_USAGE) {
    http_response_code(429); // Too Many Requests
    echo json_encode([
        'status' => 'Error', 
        'message' => 'Batas penggunaan harian Anda (' . MAX_USAGE . ') telah tercapai.',
        'data' => ['remaining_limit' => 0]
    ]);
    exit;
}

$input_data = json_decode(file_get_contents('php://input'), true);
$query = isset($input_data['query']) ? trim($input_data['query']) : '';

if (empty($query)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'Error', 'message' => 'Parameter "query" tidak boleh kosong.']);
    exit;
}

// Batasi panjang query
if (str_word_count($query) > 50) {
    http_response_code(400);
    echo json_encode(['status' => 'Error', 'message' => 'Query terlalu panjang, maksimal 50 kata.']);
    exit;
}

// Bangun URL API akhir dengan query dan apikey
$api_query = rawurlencode($query);
$api_url = BASE_API_URL . '?query=' . $api_query . '&apikey=' . API_KEY;

// Gunakan cURL untuk memanggil API eksternal
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 180); // Timeout 3 menit
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36 SunoAIProxy/1.0');

$responseBody = curl_exec($ch);
$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'Error', 'message' => 'Gagal menghubungi server AI Music: ' . $curlError]);
    exit;
}

// Proses respons jika berhasil (kode status 200)
if ($httpStatusCode === 200 && $responseBody) {
    $responseData = json_decode($responseBody, true);

    // Pastikan respons dari API eksternal valid
    if (json_last_error() === JSON_ERROR_NONE && isset($responseData['status']) && $responseData['status'] === 'Success') {
        
        // Tambah hitungan penggunaan
        $_SESSION['suno_usage']++;

        // Buat entri riwayat baru
        $history_entry = [
            'prompt' => $query,
            'data' => $responseData['data']['results']
        ];
        // Tambahkan entri baru ke awal array riwayat
        array_unshift($_SESSION['suno_history'], $history_entry);
        // Batasi riwayat hanya untuk 5 entri terakhir
        $_SESSION['suno_history'] = array_slice($_SESSION['suno_history'], 0, 5);

        // Hitung sisa kredit
        $remaining_limit = MAX_USAGE - $_SESSION['suno_usage'];
        // Tambahkan informasi sisa kredit ke dalam data respons
        $responseData['data']['remaining_limit'] = $remaining_limit;

        // Ganti nama author jika ada dan kirim respons
        $finalResponse = str_replace('iBeng', 'Andrias', json_encode($responseData));
        echo $finalResponse;
        exit;

    } else {
        // Jika API eksternal mengembalikan error atau format tidak dikenal
        http_response_code(502); // Bad Gateway
        $errorMessage = $responseData['message'] ?? 'Server AI Music mengembalikan respons yang tidak valid.';
        echo json_encode(['status' => 'Error', 'message' => $errorMessage]);
        exit;
    }
} else {
    // Tangani jika status code bukan 200
    http_response_code(502); // Bad Gateway
    echo json_encode(['status' => 'Error', 'message' => 'Server AI Music mengembalikan status error: ' . $httpStatusCode]);
    exit;
}
?>
