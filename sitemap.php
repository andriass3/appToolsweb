<?php
// Set header ke XML
header('Content-Type: application/xml; charset=utf-8');

// --- KONFIGURASI ---
// Ganti dengan URL domain utama website Anda.
// Pastikan diakhiri dengan garis miring (/).
$base_url = 'https://app.andrias.web.id/'; 
// --------------------


// Fungsi untuk membaca data tools dari tools.json
function get_active_tools() {
    $json_file = 'tools.json';
    if (file_exists($json_file)) {
        $json_data = file_get_contents($json_file);
        $tools = json_decode($json_data, true);
        if (is_array($tools)) {
            // Filter hanya tool yang statusnya 'active'
            return array_filter($tools, function($tool) {
                return isset($tool['status']) && $tool['status'] === 'active';
            });
        }
    }
    return [];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// 1. URL Halaman Utama (Homepage)
echo '  <url>' . "\n";
echo '    <loc>' . htmlspecialchars($base_url) . '</loc>' . "\n";
// Gunakan tanggal modifikasi file tools.json sebagai lastmod
echo '    <lastmod>' . date('Y-m-d', filemtime('tools.json')) . '</lastmod>' . "\n";
echo '    <changefreq>weekly</changefreq>' . "\n";
echo '    <priority>1.0</priority>' . "\n";
echo '  </url>' . "\n";

// 2. URL untuk setiap tool yang aktif
$tools = get_active_tools();
if (!empty($tools)) {
    foreach ($tools as $tool) {
        if (isset($tool['slug'])) {
            $tool_url = $base_url . 'tools/' . htmlspecialchars($tool['slug']) . '/';
            echo '  <url>' . "\n";
            echo '    <loc>' . $tool_url . '</loc>' . "\n";
            // Anda bisa menambahkan tanggal modifikasi spesifik per tool jika ada datanya
            // Untuk sekarang, kita gunakan tanggal modifikasi file tools.json
            echo '    <lastmod>' . date('Y-m-d', filemtime('tools.json')) . '</lastmod>' . "\n";
            echo '    <changefreq>monthly</changefreq>' . "\n";
            echo '    <priority>0.8</priority>' . "\n";
            echo '  </url>' . "\n";
        }
    }
}

echo '</urlset>' . "\n";
?>