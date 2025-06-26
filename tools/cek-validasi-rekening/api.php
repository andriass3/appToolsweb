<?php
header('Content-Type: application/json');

// --- Configuration ---
const GLOBAL_DAILY_LIMIT = 0; // Batasan default jika tidak ada API key atau API key tidak valid
const API_KEY_USAGE_FILE_PREFIX = __DIR__ . '/usage_count_'; // Prefix untuk file penggunaan API key spesifik

// Konfigurasi API Keys dan Limitnya
// Format: 'API_KEY_ANDA' => ['limit' => JUMLAH_LIMIT_HARIAN]
$api_keys_config = [
    'SGB' => ['limit' => 0], // Contoh API Key 1 dengan limit 100 penggunaan/hari
    '50002' => ['limit' => 0],  // Contoh API Key 2 dengan limit 25 penggunaan/hari
    // Tambahkan API Key lainnya di sini sesuai kebutuhan Anda
];

/**
 * Mendapatkan jalur file penggunaan berdasarkan API Key.
 * Jika API Key kosong atau tidak terdaftar dalam konfigurasi, gunakan file global.
 * @param string|null $apiKey API Key yang digunakan.
 * @return string Jalur lengkap ke file penggunaan.
 */
function getUsageFilePath(?string $apiKey): string {
    global $api_keys_config;
    // Gunakan hash API key untuk nama file agar tidak mudah ditebak dan unik
    if ($apiKey && isset($api_keys_config[$apiKey])) {
        return API_KEY_USAGE_FILE_PREFIX . md5($apiKey) . '.txt';
    }
    // Fallback ke file penggunaan global jika tidak ada API key atau API key tidak terdaftar
    return __DIR__ . '/usage_count_bank_validation.txt';
}

/**
 * Mendapatkan limit penggunaan harian berdasarkan API Key.
 * @param string|null $apiKey API Key yang digunakan.
 * @return int Limit penggunaan harian.
 */
function getDailyLimit(?string $apiKey): int {
    global $api_keys_config;
    // Mengembalikan limit spesifik jika API key terdaftar, jika tidak, kembalikan limit global
    if ($apiKey && isset($api_keys_config[$apiKey])) {
        return $api_keys_config[$apiKey]['limit'];
    }
    return GLOBAL_DAILY_LIMIT;
}

/**
 * Mengambil jumlah penggunaan hari ini secara spesifik per API Key atau global.
 * @param string|null $apiKey API Key yang digunakan (opsional).
 * @return int Jumlah penggunaan hari ini.
 */
function getUsageToday(?string $apiKey = null): int {
    $filePath = getUsageFilePath($apiKey);
    if (!file_exists($filePath)) {
        return 0; // Jika file belum ada, berarti penggunaan masih 0
    }
    $file_content = file_get_contents($filePath);
    $data = json_decode($file_content, true);

    if (!is_array($data)) {
        $data = []; // Pastikan $data adalah array jika file rusak/kosong
    }

    $today = date('Y-m-d');

    // Bersihkan entri lama (tanggal sebelumnya) untuk menjaga ukuran file
    foreach ($data as $date_key => $daily_count) {
        if ($date_key !== $today) {
            unset($data[$date_key]);
        }
    }

    return $data[$today] ?? 0; // Kembalikan jumlah penggunaan hari ini, atau 0 jika belum ada
}

/**
 * Menambah hitungan penggunaan hari ini secara spesifik per API Key atau global.
 * @param string|null $apiKey API Key yang digunakan (opsional).
 */
function incrementUsage(?string $apiKey = null): void {
    $filePath = getUsageFilePath($apiKey);
    // Baca konten file, atau buat objek JSON kosong jika file belum ada
    $file_content = file_exists($filePath) ? file_get_contents($filePath) : '{}';
    $data = json_decode($file_content, true);

    if (!is_array($data)) {
        $data = []; // Pastikan $data adalah array
    }

    $today = date('Y-m-d');

    // Inisialisasi entri hari ini jika belum ada
    if (!isset($data[$today])) {
        $data[$today] = 0;
    }

    $data[$today]++; // Tambah hitungan penggunaan
    // Simpan data kembali ke file, menggunakan JSON_PRETTY_PRINT untuk keterbacaan
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
}

// --- FUNGSI BANTUAN API EKSTERNAL ---

/**
 * Mengambil daftar opsi bank/e-wallet dari sumber eksternal dan menambahkan kode unik.
 * @return array Array asosiatif, setiap itemnya memiliki 'value' dari API eksternal, 'label' (nama bank), dan 'code' (kode unik 5 digit).
 */
function getBankOptions(): array {
    $html = @file_get_contents('https://www.wisnucekrekening.xyz/');
    if (!$html) return [];

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html, LIBXML_NOWARNING);
    $xp  = new DOMXPath($dom);

    $sel = $xp->query("//select[@name='account_type']")->item(0);
    $ops = [];
    $code_prefix = 10000; // Prefix untuk kode unik 5 digit
    if ($sel) {
        foreach ($sel->getElementsByTagName('option') as $o) {
            $code_prefix++;
            $ops[] = [
                'value' => $o->getAttribute('value'), // Nilai asli dari API eksternal (untuk cURL)
                'label' => trim($o->textContent),
                'code'  => (string)$code_prefix // Kode unik 5 digit
            ];
        }
    }
    return $ops;
}

/**
 * Memvalidasi nomor rekening/e-wallet melalui API eksternal.
 * @param string $typeValue Nilai tipe akun yang akan dikirim ke API eksternal.
 * @param string $num Nomor rekening/e-wallet.
 * @return array Respons JSON dari API eksternal yang di-decode menjadi array.
 */
function validateAccount(string $typeValue, string $num): array {
    $ch = curl_init('https://kedaimutasi.com/cekrekening/home/validate_account');
    curl_setopt_array($ch, [
        CURLOPT_POST           => 1,
        CURLOPT_POSTFIELDS     => http_build_query(['account_type' => $typeValue, 'account_number' => $num]),
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: Mozilla/5.0'
        ],
        CURLOPT_TIMEOUT => 15 // Timeout untuk permintaan cURL
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        // Log kesalahan cURL untuk debugging
        error_log("cURL Error in validateAccount: " . $err);
        return ['error' => 'Gagal menghubungi server eksternal: ' . $err];
    }
    $json = json_decode($res, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Log kesalahan parsing JSON
        error_log("JSON Decode Error: " . json_last_error_msg() . " for response: " . $res);
        return ['error' => 'Respons tidak valid dari server eksternal.'];
    }
    return $json;
}

// --- LOGIKA UTAMA APLIKASI ---
$action = $_REQUEST['action'] ?? ''; // Action bisa dari GET atau POST
$apiKey = $_REQUEST['apikey'] ?? null; // Ambil API Key dari request (GET atau POST)

// Dapatkan limit dan sisa penggunaan untuk API Key yang sedang digunakan (atau global)
$current_limit = getDailyLimit($apiKey);
$current_remain = $current_limit - getUsageToday($apiKey);

// Inisialisasi array hasil yang akan dikembalikan sebagai JSON
$result = ['ok' => false, 'remain' => $current_remain, 'limit' => $current_limit];

switch ($action) {
    case 'get_options':
        // Aksi ini bisa diakses dengan GET atau POST, tapi umumnya GET
        // Tidak perlu cek metode karena $_REQUEST menangani keduanya
        $options = getBankOptions();
        // Mengembalikan 'code' dan 'label' untuk setiap opsi, bersama dengan limit dan sisa penggunaan
        $result = [
            'ok' => true,
            'options' => array_map(function($opt) {
                return ['code' => $opt['code'], 'label' => $opt['label']];
            }, $options),
            'remain' => $current_remain,
            'limit' => $current_limit
        ];
        break;

    case 'validate_account':
        // Aksi ini dapat diakses dengan GET atau POST
        // Parameter diakses via $_REQUEST untuk kompatibilitas
        $code = $_REQUEST['account_type'] ?? ''; // Menerima kode unik bank/e-wallet
        $num = $_REQUEST['account_number'] ?? ''; // Menerima nomor rekening/e-wallet

        // Validasi parameter input
        // Memastikan kode adalah 5 digit angka dan nomor rekening adalah angka
        if (!ctype_digit($code) || strlen($code) !== 5 || !$num || !ctype_digit($num)) {
            echo json_encode(['ok' => false, 'msg' => 'Mohon lengkapi semua field dengan benar (kode bank 5 digit dan nomor rekening harus angka).', 'remain' => $current_remain, 'limit' => $current_limit]);
            exit;
        }

        // Periksa apakah limit penggunaan sudah tercapai untuk API Key ini
        if (getUsageToday($apiKey) >= $current_limit) {
            echo json_encode(['ok' => false, 'msg' => 'Limit penggunaan harian sudah tercapai untuk API Key ini atau global. Silakan coba lagi besok.', 'remain' => $current_remain, 'limit' => $current_limit]);
            exit;
        }


        $all_options = getBankOptions(); // Dapatkan semua opsi bank dengan value dan label asli
        $typeValueForCurl = '';
        $bankLabel = '';

        // Cari `value` (yang akan dikirim ke API eksternal) dan `label` (nama bank)
        // berdasarkan kode unik yang diterima dari request.
        foreach ($all_options as $opt) {
            if ($opt['code'] === $code) {
                $typeValueForCurl = $opt['value'];
                $bankLabel = $opt['label'];
                break;
            }
        }
        
        // Jika kode bank/e-wallet tidak ditemukan
        if (!$typeValueForCurl) {
            echo json_encode(['ok' => false, 'msg' => 'Kode bank/e-wallet tidak valid.', 'remain' => $current_remain, 'limit' => $current_limit]);
            exit;
        }

        // Panggil API eksternal dengan nilai `typeValueForCurl` yang sesuai
        $apiRes = validateAccount($typeValueForCurl, $num);
        
        // Tangani respons dari API eksternal
        if (isset($apiRes['error']) || isset($apiRes['error_message'])) {
            $result = [
                'ok' => false,
                'msg' => $apiRes['error_message'] ?? $apiRes['error'],
                'remain' => $current_limit - getUsageToday($apiKey), // Hitung ulang sisa setelah penggunaan
                'limit' => $current_limit
            ];
        } elseif (!empty($apiRes['account_name'])) {
            incrementUsage($apiKey); // Tambah hitungan penggunaan untuk API Key ini
            $result = [
                'ok' => true,
                'account_name' => $apiRes['account_name'],
                'account_number' => $num,
                'bank_label' => $bankLabel, // Label bank yang mudah dibaca
                'source' => 'by app.andrias.web.id',
                'remain' => $current_limit - getUsageToday($apiKey), // Hitung ulang sisa setelah penggunaan
                'limit' => $current_limit
            ];
        } else {
            // Jika nama akun tidak ditemukan atau respons tidak terduga
            $result = [
                'ok' => false,
                'msg' => 'Nama pemilik rekening tidak dapat ditemukan atau respons tidak terduga.',
                'remain' => $current_limit - getUsageToday($apiKey),
                'limit' => $current_limit
            ];
        }
        break;

    default:
        // Aksi tidak valid
        http_response_code(400);
        $result = ['ok' => false, 'msg' => 'Aksi tidak valid.', 'remain' => $current_remain, 'limit' => $current_limit];
        break;
}

echo json_encode($result, JSON_PRETTY_PRINT);
