<?php
// track_tool_usage.php

// Hanya izinkan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Metode permintaan tidak valid.']);
    exit;
}

// Ambil data JSON dari body request
$input_data = json_decode(file_get_contents('php://input'), true);

if (!isset($input_data['tool_slug']) || empty(trim($input_data['tool_slug']))) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Parameter tool_slug tidak ditemukan atau kosong.']);
    exit;
}

$tool_slug = trim($input_data['tool_slug']);
// Sanitasi dasar untuk slug (hanya alphanumeric dan strip/underscore)
$tool_slug_sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', $tool_slug);

if (empty($tool_slug_sanitized)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Format tool_slug tidak valid setelah sanitasi.']);
    exit;
}

$stats_file = __DIR__ . '/tool_usage_stats.json'; // File statistik di direktori yang sama dengan skrip ini (root)
$usage_data = [];

// Baca data statistik yang ada (jika ada)
if (file_exists($stats_file)) {
    $file_content = file_get_contents($stats_file);
    if ($file_content !== false) {
        $decoded_content = json_decode($file_content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_content)) {
            $usage_data = $decoded_content;
        } else {
            // File ada tapi formatnya salah, log error dan mulai dengan array kosong
            error_log("track_tool_usage.php: Gagal decode tool_usage_stats.json atau bukan array. Error: " . json_last_error_msg());
        }
    } else {
        error_log("track_tool_usage.php: Gagal membaca tool_usage_stats.json.");
    }
}

// Increment count untuk tool yang digunakan
$usage_data[$tool_slug_sanitized] = ($usage_data[$tool_slug_sanitized] ?? 0) + 1;

// Tulis kembali data ke file JSON dengan file locking
$fp = fopen($stats_file, 'c'); // 'c' mode: buka untuk baca/tulis; buat jika tidak ada; pointer di awal; jangan truncate
if ($fp) {
    if (flock($fp, LOCK_EX)) { // Dapatkan lock eksklusif
        ftruncate($fp, 0);      // Truncate file
        fwrite($fp, json_encode($usage_data, JSON_PRETTY_PRINT));
        fflush($fp);            // Pastikan semua output ditulis
        flock($fp, LOCK_UN);    // Lepaskan lock
        fclose($fp);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Penggunaan tool berhasil dicatat.']);
    } else {
        fclose($fp);
        error_log("track_tool_usage.php: Gagal mendapatkan file lock untuk tool_usage_stats.json.");
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal memproses permintaan (lock).']);
    }
} else {
    error_log("track_tool_usage.php: Gagal membuka file tool_usage_stats.json untuk ditulis.");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memproses permintaan (open file).']);
}

?>