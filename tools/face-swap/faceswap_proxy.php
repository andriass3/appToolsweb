<?php
// File: faceswap_proxy.php

// Mencegah error PHP merusak output JSON
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

// --- Konfigurasi ---
define('TARGET_API_BASE_URL', 'https://api.ibeng.my.id/api/ai/faceswap');
$apiKeys = [
    'apikey' => 'MrsNK32zbG',
];

define('LOCAL_UPLOAD_DIR', __DIR__ . '/images/'); 
define('LOCAL_UPLOAD_URL_BASE', 'https://app.andrias.web.id/tools/face-swap/images/'); 
define('TEMP_FILE_LIFETIME_SECONDS', 300); // 5 menit

// PERUBAHAN: Lokasi file untuk menyimpan riwayat
define('HISTORY_FILE', __DIR__ . '/faceswap_history.json');
define('HISTORY_MAX_ENTRIES', 10); // Simpan maksimal 50 entri terakhir

// --- Fungsi Helper ---

function cleanup_old_files($dir) {
    if (!is_dir($dir)) return;
    foreach (glob($dir . "*") as $file) {
        if (is_file($file) && time() - filemtime($file) >= TEMP_FILE_LIFETIME_SECONDS) {
            unlink($file);
        }
    }
}

function upload_file_locally_and_get_url($fileData) {
    if ($fileData['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    if (!is_dir(LOCAL_UPLOAD_DIR)) {
        if (!mkdir(LOCAL_UPLOAD_DIR, 0755, true)) {
            return null;
        }
    }

    $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $newFileName = uniqid('swap_', true) . '.' . strtolower($extension);
    $destinationPath = LOCAL_UPLOAD_DIR . $newFileName;

    if (move_uploaded_file($fileData['tmp_name'], $destinationPath)) {
        return LOCAL_UPLOAD_URL_BASE . $newFileName;
    }
    return null;
}

// PERUBAHAN: Fungsi untuk menyimpan riwayat
function save_history_entry($result_url, $user_ip) {
    $history = [];
    if (file_exists(HISTORY_FILE)) {
        $history = json_decode(file_get_contents(HISTORY_FILE), true) ?: [];
    }

    $new_entry = [
        'timestamp' => time(),
        'result_url' => $result_url,
        'ip' => $user_ip
    ];

    array_unshift($history, $new_entry); // Tambahkan entri baru di awal
    $history = array_slice($history, 0, HISTORY_MAX_ENTRIES); // Batasi jumlah entri

    file_put_contents(HISTORY_FILE, json_encode($history, JSON_PRETTY_PRINT));
}


// --- Logika Utama ---

cleanup_old_files(LOCAL_UPLOAD_DIR);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'Error', 'message' => 'Metode tidak diizinkan.']);
    exit;
}

$original_type = $_POST['original_type'] ?? 'url';
$target_type = $_POST['target_type'] ?? 'url';
$original_url_input = $_POST['original_url'] ?? '';
$target_url_input = $_POST['target_url'] ?? '';

$original_image_url = null;
$target_image_url = null;

if ($original_type === 'file' && isset($_FILES['original_file'])) {
    $original_image_url = upload_file_locally_and_get_url($_FILES['original_file']);
    if (!$original_image_url) {
        http_response_code(500);
        echo json_encode(['status' => 'Error', 'message' => 'Gagal mengunggah file gambar original.']);
        exit;
    }
} else {
    $original_image_url = filter_var($original_url_input, FILTER_VALIDATE_URL);
}

if ($target_type === 'file' && isset($_FILES['target_file'])) {
    $target_image_url = upload_file_locally_and_get_url($_FILES['target_file']);
    if (!$target_image_url) {
        http_response_code(500);
        echo json_encode(['status' => 'Error', 'message' => 'Gagal mengunggah file gambar target.']);
        exit;
    }
} else {
    $target_image_url = filter_var($target_url_input, FILTER_VALIDATE_URL);
}

if (!$original_image_url || !$target_image_url) {
    http_response_code(400);
    echo json_encode(['status' => 'Error', 'message' => 'URL gambar original atau target tidak valid.']);
    exit;
}

$queryParams = http_build_query(array_merge([
    'original' => $original_image_url,
    'target' => $target_image_url
], $apiKeys));

$final_api_url = TARGET_API_BASE_URL . '?' . $queryParams;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $final_api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 90); 
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$responseBody = curl_exec($ch);
$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['status' => 'Error', 'message' => 'Gagal menghubungi API Face Swap: ' . $curlError]);
    exit;
}

$decodedResponse = json_decode($responseBody, true);

if ($httpStatusCode === 200 && isset($decodedResponse['status']) && $decodedResponse['status'] === 'Success') {
    
    $finalImageUrl = $decodedResponse['data']['data']['url'] ?? null;
    
    if ($finalImageUrl) {
        // PERUBAHAN: Simpan ke riwayat jika sukses
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        save_history_entry($finalImageUrl, $user_ip);
        
        http_response_code(200);
        echo json_encode([
            "status" => "Success",
            "data" => ["url" => $finalImageUrl]
        ]);
    } else {
        http_response_code(502);
        echo json_encode(['status' => 'Error', 'message' => 'Format respons dari API tidak dikenali.']);
    }

} else {
    http_response_code($httpStatusCode);
    $errorMessage = $decodedResponse['message'] ?? 'Terjadi kesalahan pada API Face Swap.';
    echo json_encode(['status' => 'Error', 'message' => $errorMessage]);
}
exit;
?>
