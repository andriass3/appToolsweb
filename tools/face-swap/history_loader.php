<?php
// File: history_loader.php

header('Content-Type: application/json');

define('HISTORY_FILE', __DIR__ . '/faceswap_history.json');
define('HISTORY_DISPLAY_LIMIT', 10); // Jumlah riwayat yang ditampilkan di halaman

$history = [];
if (file_exists(HISTORY_FILE)) {
    $history = json_decode(file_get_contents(HISTORY_FILE), true) ?: [];
}

// Ambil hanya beberapa entri terakhir untuk ditampilkan
$display_history = array_slice($history, 0, HISTORY_DISPLAY_LIMIT);

echo json_encode($display_history);
?>
