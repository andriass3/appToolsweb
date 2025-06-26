<?php
// andrias/includes/tool_functions.php

// Fungsi untuk membaca tools dari tools.json
function get_all_tools_admin_creator($tools_file_path) {
    if (!file_exists($tools_file_path)) {
        return [];
    }
    $json_data = file_get_contents($tools_file_path);
    $decoded_data = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded_data)) {
        return [];
    }
    return $decoded_data;
}

// Fungsi untuk menyimpan tools ke tools.json
function save_tools_admin_creator($tools_array, $tools_file_path) {
    // Pastikan direktori tools.json writable
    if (!is_writable(dirname($tools_file_path)) || (file_exists($tools_file_path) && !is_writable($tools_file_path))) {
         return ['status' => 'error', 'message' => 'Error: File tools.json atau direktorinya tidak writable.'];
    }
    $json_data = json_encode($tools_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($tools_file_path, $json_data) === false) {
        return ['status' => 'error', 'message' => 'Gagal menyimpan data tools.'];
    }
    return ['status' => 'success'];
}

// Fungsi untuk membuat slug dari string
function create_slug_creator($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string); 
    $string = preg_replace('/[\s-]+/', '-', $string); 
    $string = trim($string, '-'); 
    return $string;
}