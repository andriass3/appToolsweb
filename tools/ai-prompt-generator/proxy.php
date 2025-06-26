<?php
// gemini_proxy.php

// ** KEAMANAN API KEY **
// Cara terbaik adalah menyimpan API key di environment variable server Anda
// atau dalam file konfigurasi di luar web root.
// Hindari hardcoding langsung di sini untuk lingkungan produksi yang sebenarnya.
// Untuk contoh ini, kita akan mendefinisikannya di sini, yang sudah lebih aman daripada di client-side.
define('GEMINI_API_KEY', 'AIzaSyA3C1cufLs0xnKOE1GRYalitFSSv-6ea0k'); // API Key Anda

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Metode permintaan tidak valid. Hanya POST yang diizinkan.']);
    exit;
}

$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

if (!isset($data['metaPrompt']) || empty($data['metaPrompt'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Parameter metaPrompt tidak ada atau kosong.']);
    exit;
}

$metaPrompt = $data['metaPrompt'];

$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . GEMINI_API_KEY;

$payload = [
    'contents' => [[
        'parts' => [['text' => $metaPrompt]]
    ]],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 300
    ]
];

$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($payload),
        'ignore_errors' => true // Untuk menangkap respons error dari API
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($apiUrl, false, $context);

$http_status_line = $http_response_header[0]; // Mendapatkan baris status HTTP pertama

if (strpos($http_status_line, '200 OK') !== false) {
    // Jika sukses, langsung teruskan respons dari Gemini
    echo $result;
} else {
    // Jika ada error dari Gemini atau koneksi
    http_response_code(500); // Internal Server Error (atau bisa lebih spesifik berdasarkan $http_status_line)
    $error_details = json_decode($result, true); // Coba decode error dari Gemini
    if ($error_details && isset($error_details['error']['message'])) {
        echo json_encode(['error' => 'Gagal menghubungi API Gemini: ' . $error_details['error']['message'], 'details' => $error_details]);
    } else {
        echo json_encode(['error' => 'Gagal menghubungi API Gemini. Status: ' . $http_status_line, 'raw_response' => $result]);
    }
}
?>
