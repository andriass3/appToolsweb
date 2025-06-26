<?php
// Tentukan judul halaman
$page_title = "Buat Tool Baru (API Explorer)";
// Path relatif dari folder 'andrias' ke root direktori
$path_prefix = '../'; 

// Asumsi tools.json, feedback.json, tool_usage_stats.json berada di root.
$tools_file = $path_prefix . 'tools.json'; 

// Sertakan file autentikasi admin
require_once 'auth.php'; 
// Sertakan file fungsi-fungsi yang sudah dipisahkan
require_once 'includes/tool_functions.php';
require_once 'includes/tool_template_content.php'; // Sertakan file template baru

// --- LOGIKA UTAMA UNTUK UJI API DAN SIMPAN TOOLS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'test_api') {
        header('Content-Type: application/json');
        $apiUrl = $_POST['api_url'] ?? '';
        $method = $_POST['method'] ?? 'GET';
        $params = $_POST['params'] ?? []; // Array of {key, value}
        $headers = $_POST['headers'] ?? []; // Array of {key, value}

        if (empty($apiUrl)) {
            echo json_encode(['status' => 'error', 'message' => 'URL API tidak boleh kosong.']);
            exit;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Set custom headers
        $curlHeaders = ['User-Agent: Mozilla/5.0 (compatible; ToolCreator/1.0)'];
        foreach ($headers as $header) {
            if (!empty($header['key']) && !empty($header['value'])) {
                $curlHeaders[] = $header['key'] . ': ' . $header['value'];
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            $postFields = [];
            foreach ($params as $param) {
                if (!empty($param['key'])) {
                    $postFields[$param['key']] = $param['value'];
                }
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
            curl_setopt($ch, CURLOPT_URL, $apiUrl); 
        } else { // GET
            $queryString = '';
            if (!empty($params)) {
                $getParams = [];
                foreach ($params as $param) {
                    if (!empty($param['key'])) {
                        $getParams[$param['key']] = $param['value'];
                    }
                }
                $queryString = '?' . http_build_query($getParams);
            }
            curl_setopt($ch, CURLOPT_URL, $apiUrl . $queryString);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            echo json_encode(['status' => 'error', 'message' => 'Kesalahan cURL: ' . $curlError]);
        } else {
            // Coba decode JSON, jika gagal, kembalikan sebagai teks biasa
            $decodedResponse = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo json_encode(['status' => 'success', 'http_code' => $httpCode, 'data' => $decodedResponse, 'is_json' => true]);
            } else {
                echo json_encode(['status' => 'success', 'http_code' => $httpCode, 'data' => $response, 'is_json' => false]);
            }
        }
        exit;

    } elseif ($action === 'save_tool') {
        global $tool_frontend_template; // Akses variabel template frontend
        global $tool_backend_api_template; // Akses variabel template backend

        $name = trim($_POST['tool_name']);
        $slug_input = trim($_POST['tool_slug']);
        $icon = trim($_POST['tool_icon']);
        $description = trim($_POST['tool_description']);
        $status = $_POST['tool_status'];
        $api_url_for_tool = trim($_POST['api_url_for_tool']);
        $selected_response_paths = json_decode($_POST['selected_response_paths'] ?? '[]', true); 
        $api_method_for_tool = trim($_POST['api_method_for_tool']);
        $api_params_for_tool = json_decode($_POST['api_params_for_tool'] ?? '[]', true);
        $api_headers_for_tool = json_decode($_POST['api_headers_for_tool'] ?? '[]', true);
        $primary_input_param_key = trim($_POST['primary_input_param_key'] ?? '');
        $generated_output_html = $_POST['generated_output_html'] ?? ''; 

        if (empty($name) || empty($slug_input) || empty($icon) || empty($description) || empty($api_url_for_tool)) {
            header('Location: tool_creator.php?error=' . urlencode('Semua field wajib diisi.'));
            exit;
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug_input)) {
            header('Location: tool_creator.php?error=' . urlencode('Format slug tidak valid. Gunakan huruf kecil, angka, dan tanda hubung.'));
            exit;
        }

        $tools = get_all_tools_admin_creator($tools_file); 
        foreach ($tools as $existing_tool) {
            if ($existing_tool['slug'] === $slug_input) {
                header('Location: tool_creator.php?error=' . urlencode('Slug sudah digunakan. Harap pilih slug lain.'));
                exit;
            }
        }

        $new_tool = [
            'id' => uniqid('tool_'),
            'name' => $name,
            'slug' => $slug_input,
            'icon' => $icon,
            'description' => $description,
            'status' => $status,
            'api_config' => [
                'url' => $api_url_for_tool,
                'method' => $api_method_for_tool,
                'headers' => $api_headers_for_tool,
                'selected_response_paths' => $selected_response_paths, 
                'params' => $api_params_for_tool, 
                'primary_input_key' => $primary_input_param_key,
                'generated_output_html' => $generated_output_html
            ]
        ];
        $tools[] = $new_tool;
        $save_result = save_tools_admin_creator($tools, $tools_file); 

        if ($save_result['status'] === 'error') {
            header('Location: tool_creator.php?error=' . urlencode($save_result['message']));
            exit;
        }

        // Buat folder untuk tool baru
        $base_tools_dir = realpath(__DIR__ . '/../tools'); 
        $tool_dir = $base_tools_dir . DIRECTORY_SEPARATOR . $slug_input;

        $message_appendix = '';
        if (!file_exists($tool_dir)) {
            if (mkdir($tool_dir, 0755, true)) {
                // --- PROSES PEMBUATAN FILE index.php (Frontend Tool) ---
                $generated_output_html_js_escaped = addslashes(json_encode($generated_output_html, JSON_UNESCAPED_SLASHES));

                $final_frontend_content = $tool_frontend_template;
                $final_frontend_content = str_replace('{{page_title}}', htmlspecialchars($name), $final_frontend_content);
                $final_frontend_content = str_replace('{{tool_icon}}', htmlspecialchars($icon), $final_frontend_content);
                $final_frontend_content = str_replace('{{tool_description}}', htmlspecialchars($description), $final_frontend_content);
                $final_frontend_content = str_replace('{{primary_input_param_key}}', htmlspecialchars($primary_input_param_key), $final_frontend_content);
                $final_frontend_content = str_replace('{{generated_output_html_js_escaped}}', $generated_output_html_js_escaped, $final_frontend_content);
                
                file_put_contents($tool_dir . "/index.php", $final_frontend_content);

                // --- PROSES PEMBUATAN FILE api.php (Backend Proxy) ---
                $api_params_json_encoded = addslashes(json_encode($api_params_for_tool, JSON_UNESCAPED_SLASHES));
                $api_headers_json_encoded = addslashes(json_encode($api_headers_for_tool, JSON_UNESCAPED_SLASHES));
                $selected_response_paths_encoded = addslashes(json_encode($selected_response_paths, JSON_UNESCAPED_SLASHES));
                $api_generated_output_html_encoded = addslashes(json_encode($generated_output_html, JSON_UNESCAPED_SLASHES));

                $final_backend_api_content = $tool_backend_api_template;
                $final_backend_api_content = str_replace('{{api_url_for_tool}}', htmlspecialchars($api_url_for_tool), $final_backend_api_content);
                $final_backend_api_content = str_replace('{{api_method_for_tool}}', htmlspecialchars($api_method_for_tool), $final_backend_api_content);
                $final_backend_api_content = str_replace('{{api_headers_for_tool_json}}', $api_headers_json_encoded, $final_backend_api_content);
                $final_backend_api_content = str_replace('{{selected_response_paths_json}}', $selected_response_paths_encoded, $final_backend_api_content);
                $final_backend_api_content = str_replace('{{api_params_for_tool_json}}', $api_params_json_encoded, $final_backend_api_content);
                $final_backend_api_content = str_replace('{{primary_input_param_key}}', htmlspecialchars($primary_input_param_key), $final_backend_api_content);
                $final_backend_api_content = str_replace('{{generated_output_html_escaped}}', $api_generated_output_html_encoded, $final_backend_api_content);

                file_put_contents($tool_dir . "/api.php", $final_backend_api_content);

                $message_appendix = ' dan folder tool dibuat dengan file index.php & api.php.';
            } else {
                $message_appendix = ', namun gagal membuat folder tool otomatis. Buat manual: ' . htmlspecialchars($tool_dir);
            }
        } else {
             $message_appendix = '. Folder tool sudah ada. File index.php & api.php diperbarui.';
        }
        header('Location: tool_creator.php?message=' . urlencode('Tool berhasil ditambahkan' . $message_appendix));
        exit;
    }
}

// Sertakan file header dan footer HTML
include $path_prefix . 'header.php';
?>

<style>
    /* Enhanced Tool Creator Styles */
    .tool-creator-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 1rem;
        padding: 3rem 2rem;
        margin-bottom: 2rem;
        color: white;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .tool-creator-container h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }
    
    .tool-creator-container .lead {
        font-size: 1.2rem;
        opacity: 0.9;
    }

    .step-container {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    .step-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #dee2e6;
    }

    .step-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-block;
        margin-bottom: 0.5rem;
    }

    .step-title {
        font-size: 1.4rem;
        font-weight: 600;
        color: #2d3748;
        margin: 0;
    }

    .step-body {
        padding: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-control, .form-select {
        border: 2px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 0.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }

    .btn-outline-primary {
        border: 2px solid #667eea;
        color: #667eea;
        background: transparent;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-outline-primary:hover {
        background: #667eea;
        color: white;
        transform: translateY(-1px);
    }

    .btn-outline-danger {
        border: 2px solid #e53e3e;
        color: #e53e3e;
        background: transparent;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-outline-danger:hover {
        background: #e53e3e;
        color: white;
        transform: translateY(-1px);
    }

    .parameter-row, .header-row {
        background: #f8f9fa;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
        position: relative;
    }

    .parameter-row .row, .header-row .row {
        align-items: end;
    }

    .remove-btn {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }

    .json-display {
        background: #1a202c;
        color: #e2e8f0;
        border-radius: 0.5rem;
        padding: 1.5rem;
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        font-size: 0.9rem;
        line-height: 1.5;
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #2d3748;
    }

    .json-key {
        color: #63b3ed;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .json-key:hover {
        background: rgba(99, 179, 237, 0.2);
        border-radius: 3px;
    }

    .json-key.selected {
        background: rgba(72, 187, 120, 0.3);
        border-radius: 3px;
        color: #68d391;
    }

    .json-string { color: #f6e05e; }
    .json-number { color: #fc8181; }
    .json-boolean { color: #9f7aea; }
    .json-null { color: #a0aec0; }

    .selected-paths {
        background: #f0fff4;
        border: 1px solid #9ae6b4;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-top: 1rem;
    }

    .path-tag {
        background: #667eea;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.8rem;
        margin: 0.25rem;
        display: inline-block;
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        z-index: 10;
    }

    .hidden-section {
        display: none;
        opacity: 0;
        transition: opacity 0.5s ease;
    }

    .visible-section {
        display: block;
        opacity: 1;
    }

    .form-text {
        font-size: 0.875rem;
        color: #718096;
        margin-top: 0.25rem;
    }

    .alert {
        border-radius: 0.5rem;
        border: none;
        padding: 1rem 1.5rem;
    }

    .alert-success {
        background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
        color: #22543d;
        border-left: 4px solid #38a169;
    }

    .alert-danger {
        background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
        color: #742a2a;
        border-left: 4px solid #e53e3e;
    }

    .spinner-border {
        width: 2rem;
        height: 2rem;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .tool-creator-container {
            padding: 2rem 1rem;
        }
        
        .tool-creator-container h1 {
            font-size: 2rem;
        }
        
        .step-body {
            padding: 1rem;
        }
        
        .parameter-row, .header-row {
            padding: 0.75rem;
        }
        
        .remove-btn {
            position: static;
            margin-top: 0.5rem;
            width: auto;
            height: auto;
            border-radius: 0.25rem;
        }
    }
</style>

<div class="container my-5">
    <!-- Header Section -->
    <div class="tool-creator-container">
        <h1><i class="fas fa-magic me-3"></i><?php echo htmlspecialchars($page_title); ?></h1>
        <p class="lead">Buat tool baru dengan mudah menggunakan API eksternal. Sistem akan menghasilkan interface yang aman dan user-friendly.</p>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Step 1: API Configuration -->
    <div class="step-container">
        <div class="step-header">
            <div class="step-badge">Step 1</div>
            <h3 class="step-title">Konfigurasi API</h3>
        </div>
        <div class="step-body">
            <form id="apiTestForm">
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="apiUrl" class="form-label">URL API Endpoint</label>
                            <input type="url" class="form-control" id="apiUrl" name="api_url" 
                                   placeholder="https://api.example.com/endpoint" required>
                            <div class="form-text">Masukkan URL lengkap endpoint API yang akan digunakan</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="apiMethod" class="form-label">HTTP Method</label>
                            <select class="form-select" id="apiMethod" name="method" required>
                                <option value="GET">GET</option>
                                <option value="POST">POST</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Headers Section -->
                <div class="form-group">
                    <label class="form-label">Headers (Opsional)</label>
                    <div id="headersContainer">
                        <!-- Headers akan ditambahkan di sini -->
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addHeaderBtn">
                        <i class="fas fa-plus me-1"></i>Tambah Header
                    </button>
                    <div class="form-text">Tambahkan header seperti Authorization, Content-Type, API-Key, dll.</div>
                </div>

                <!-- Parameters Section -->
                <div class="form-group">
                    <label class="form-label">Parameter API</label>
                    <div id="parametersContainer">
                        <!-- Parameters akan ditambahkan di sini -->
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addParameterBtn">
                        <i class="fas fa-plus me-1"></i>Tambah Parameter
                    </button>
                    <div class="form-text">Definisikan parameter yang diperlukan oleh API</div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-play me-2"></i>Test API
                </button>
            </form>
        </div>
    </div>

    <!-- Step 2: Response Analysis (Hidden initially) -->
    <div class="step-container hidden-section" id="responseSection">
        <div class="step-header">
            <div class="step-badge">Step 2</div>
            <h3 class="step-title">Analisis Response API</h3>
        </div>
        <div class="step-body position-relative">
            <div id="loadingIndicator" class="loading-overlay" style="display: none;">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 mb-0">Menguji API...</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Response JSON</label>
                <div class="form-text mb-2">Klik pada key JSON untuk memilih data yang akan ditampilkan di tool</div>
                <div id="jsonDisplay" class="json-display">
                    <p class="text-center text-muted mb-0">Response API akan ditampilkan di sini setelah testing</p>
                </div>
            </div>

            <div id="selectedPathsSection" class="selected-paths" style="display: none;">
                <label class="form-label">Data yang Dipilih</label>
                <div id="selectedPathsList"></div>
                <button type="button" class="btn btn-outline-danger btn-sm mt-2" id="clearSelectionsBtn">
                    <i class="fas fa-trash me-1"></i>Hapus Semua Pilihan
                </button>
            </div>
        </div>
    </div>

    <!-- Step 3: Tool Configuration (Hidden initially) -->
    <div class="step-container hidden-section" id="toolConfigSection">
        <div class="step-header">
            <div class="step-badge">Step 3</div>
            <h3 class="step-title">Konfigurasi Tool</h3>
        </div>
        <div class="step-body">
            <form id="toolConfigForm" method="POST">
                <input type="hidden" name="action" value="save_tool">
                <input type="hidden" name="api_url_for_tool" id="hiddenApiUrl">
                <input type="hidden" name="api_method_for_tool" id="hiddenApiMethod">
                <input type="hidden" name="api_headers_for_tool" id="hiddenApiHeaders">
                <input type="hidden" name="api_params_for_tool" id="hiddenApiParams">
                <input type="hidden" name="selected_response_paths" id="hiddenSelectedPaths">

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="toolName" class="form-label">Nama Tool</label>
                            <input type="text" class="form-control" id="toolName" name="tool_name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="toolSlug" class="form-label">Slug URL</label>
                            <input type="text" class="form-control" id="toolSlug" name="tool_slug" 
                                   pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required>
                            <div class="form-text">Akan dibuat otomatis dari nama tool</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="toolIcon" class="form-label">Icon (Font Awesome)</label>
                            <input type="text" class="form-control" id="toolIcon" name="tool_icon" 
                                   value="fas fa-tools" required>
                            <div class="form-text">Contoh: fas fa-star, fab fa-github</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="toolStatus" class="form-label">Status</label>
                            <select class="form-select" id="toolStatus" name="tool_status" required>
                                <option value="active">Aktif</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="toolDescription" class="form-label">Deskripsi Tool</label>
                    <textarea class="form-control" id="toolDescription" name="tool_description" 
                              rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="primaryInputKey" class="form-label">Parameter Input Utama (Opsional)</label>
                    <select class="form-select" id="primaryInputKey" name="primary_input_param_key">
                        <option value="">Pilih parameter utama...</option>
                    </select>
                    <div class="form-text">Parameter yang akan menjadi input utama di form tool</div>
                </div>

                <div class="form-group">
                    <label for="outputTemplate" class="form-label">Template Output HTML</label>
                    <textarea class="form-control" id="outputTemplate" name="generated_output_html" 
                              rows="8" required></textarea>
                    <div class="form-text">Gunakan placeholder seperti {{key.path}} untuk menampilkan data dari API</div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>Buat Tool
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let headerCounter = 0;
    let parameterCounter = 0;
    let selectedPaths = new Set();
    let apiResponseData = null;

    // DOM elements
    const apiTestForm = document.getElementById('apiTestForm');
    const addHeaderBtn = document.getElementById('addHeaderBtn');
    const addParameterBtn = document.getElementById('addParameterBtn');
    const headersContainer = document.getElementById('headersContainer');
    const parametersContainer = document.getElementById('parametersContainer');
    const responseSection = document.getElementById('responseSection');
    const toolConfigSection = document.getElementById('toolConfigSection');
    const jsonDisplay = document.getElementById('jsonDisplay');
    const selectedPathsSection = document.getElementById('selectedPathsSection');
    const selectedPathsList = document.getElementById('selectedPathsList');
    const clearSelectionsBtn = document.getElementById('clearSelectionsBtn');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const toolNameInput = document.getElementById('toolName');
    const toolSlugInput = document.getElementById('toolSlug');
    const primaryInputKey = document.getElementById('primaryInputKey');
    const outputTemplate = document.getElementById('outputTemplate');

    // Add Header functionality
    addHeaderBtn.addEventListener('click', function() {
        console.log('Add header clicked'); // Debug log
        addHeaderRow();
    });

    // Add Parameter functionality  
    addParameterBtn.addEventListener('click', function() {
        console.log('Add parameter clicked'); // Debug log
        addParameterRow();
    });

    function addHeaderRow(key = '', value = '') {
        const headerRow = document.createElement('div');
        headerRow.className = 'header-row';
        headerRow.innerHTML = `
            <div class="row g-2">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="headers[${headerCounter}][key]" 
                           placeholder="Header Key (e.g., Authorization)" value="${escapeHtml(key)}">
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control" name="headers[${headerCounter}][value]" 
                           placeholder="Header Value (e.g., Bearer token)" value="${escapeHtml(value)}">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger remove-btn" onclick="this.closest('.header-row').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        headersContainer.appendChild(headerRow);
        headerCounter++;
    }

    function addParameterRow(key = '', value = '', type = 'text', display = 'input', required = false, description = '') {
        const parameterRow = document.createElement('div');
        parameterRow.className = 'parameter-row';
        parameterRow.innerHTML = `
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Parameter Key</label>
                    <input type="text" class="form-control parameter-key" name="params[${parameterCounter}][key]" 
                           placeholder="Parameter name" value="${escapeHtml(key)}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Default Value</label>
                    <input type="text" class="form-control" name="params[${parameterCounter}][value]" 
                           placeholder="Default value" value="${escapeHtml(value)}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="params[${parameterCounter}][type]">
                        <option value="text" ${type === 'text' ? 'selected' : ''}>Text</option>
                        <option value="email" ${type === 'email' ? 'selected' : ''}>Email</option>
                        <option value="url" ${type === 'url' ? 'selected' : ''}>URL</option>
                        <option value="number" ${type === 'number' ? 'selected' : ''}>Number</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Display As</label>
                    <select class="form-select" name="params[${parameterCounter}][display]">
                        <option value="input" ${display === 'input' ? 'selected' : ''}>Input</option>
                        <option value="textarea" ${display === 'textarea' ? 'selected' : ''}>Textarea</option>
                        <option value="select" ${display === 'select' ? 'selected' : ''}>Select</option>
                        <option value="hidden" ${display === 'hidden' ? 'selected' : ''}>Hidden</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Required</label>
                    <select class="form-select" name="params[${parameterCounter}][required]">
                        <option value="false" ${!required ? 'selected' : ''}>No</option>
                        <option value="true" ${required ? 'selected' : ''}>Yes</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-outline-danger remove-btn" onclick="this.closest('.parameter-row').remove(); updatePrimaryInputOptions();">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="row g-2 mt-2">
                <div class="col-12">
                    <input type="text" class="form-control" name="params[${parameterCounter}][description]" 
                           placeholder="Parameter description (optional)" value="${escapeHtml(description)}">
                </div>
            </div>
        `;
        parametersContainer.appendChild(parameterRow);
        
        // Add event listener for parameter key changes
        const keyInput = parameterRow.querySelector('.parameter-key');
        keyInput.addEventListener('input', updatePrimaryInputOptions);
        
        parameterCounter++;
        updatePrimaryInputOptions();
    }

    function updatePrimaryInputOptions() {
        const paramKeys = Array.from(document.querySelectorAll('.parameter-key'))
            .map(input => input.value.trim())
            .filter(key => key !== '');
        
        primaryInputKey.innerHTML = '<option value="">Pilih parameter utama...</option>';
        paramKeys.forEach(key => {
            const option = document.createElement('option');
            option.value = key;
            option.textContent = key;
            primaryInputKey.appendChild(option);
        });
    }

    // Auto-generate slug from tool name
    toolNameInput.addEventListener('input', function() {
        const slug = this.value
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s-]+/g, '-')
            .replace(/^-+|-+$/g, '');
        toolSlugInput.value = slug;
    });

    // API Test Form submission
    apiTestForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        loadingIndicator.style.display = 'flex';
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('tool_creator.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                apiResponseData = result.data;
                displayJsonResponse(result.data);
                showSection(responseSection);
                
                // Store API configuration for later use
                document.getElementById('hiddenApiUrl').value = document.getElementById('apiUrl').value;
                document.getElementById('hiddenApiMethod').value = document.getElementById('apiMethod').value;
                
                // Store headers and parameters
                const headers = collectFormData('headers');
                const params = collectFormData('params');
                document.getElementById('hiddenApiHeaders').value = JSON.stringify(headers);
                document.getElementById('hiddenApiParams').value = JSON.stringify(params);
                
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            alert('Network error: ' + error.message);
        } finally {
            loadingIndicator.style.display = 'none';
        }
    });

    function collectFormData(type) {
        const containers = type === 'headers' ? headersContainer : parametersContainer;
        const inputs = containers.querySelectorAll(`input[name^="${type}"]`);
        const data = [];
        const items = {};
        
        inputs.forEach(input => {
            const match = input.name.match(new RegExp(`${type}\\[(\\d+)\\]\\[(.+)\\]`));
            if (match) {
                const index = match[1];
                const field = match[2];
                if (!items[index]) items[index] = {};
                items[index][field] = input.value;
            }
        });
        
        Object.values(items).forEach(item => {
            if (item.key && item.key.trim()) {
                data.push(item);
            }
        });
        
        return data;
    }

    function displayJsonResponse(data) {
        jsonDisplay.innerHTML = '';
        const pre = document.createElement('pre');
        pre.innerHTML = syntaxHighlight(JSON.stringify(data, null, 2));
        jsonDisplay.appendChild(pre);
        
        // Add click listeners to JSON keys
        jsonDisplay.addEventListener('click', handleJsonClick);
    }

    function syntaxHighlight(json) {
        json = json.replace(/&/g, '&').replace(/</g, '<').replace(/>/g, '>');
        return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
            let cls = 'json-number';
            if (/^"/.test(match)) {
                if (/:$/.test(match)) {
                    cls = 'json-key';
                } else {
                    cls = 'json-string';
                }
            } else if (/true|false/.test(match)) {
                cls = 'json-boolean';
            } else if (/null/.test(match)) {
                cls = 'json-null';
            }
            return '<span class="' + cls + '">' + match + '</span>';
        });
    }

    function handleJsonClick(e) {
        if (e.target.classList.contains('json-key')) {
            const keyText = e.target.textContent.replace(/[":]/g, '');
            const path = getJsonPath(e.target);
            
            if (selectedPaths.has(path)) {
                selectedPaths.delete(path);
                e.target.classList.remove('selected');
            } else {
                selectedPaths.add(path);
                e.target.classList.add('selected');
            }
            
            updateSelectedPathsDisplay();
        }
    }

    function getJsonPath(element) {
        // Simple path extraction - in a real implementation, you'd want more sophisticated path tracking
        const keyText = element.textContent.replace(/[":]/g, '');
        return keyText;
    }

    function updateSelectedPathsDisplay() {
        if (selectedPaths.size > 0) {
            selectedPathsSection.style.display = 'block';
            selectedPathsList.innerHTML = Array.from(selectedPaths)
                .map(path => `<span class="path-tag">${escapeHtml(path)}</span>`)
                .join('');
            
            // Update hidden field
            document.getElementById('hiddenSelectedPaths').value = JSON.stringify(Array.from(selectedPaths));
            
            // Generate HTML template
            generateHtmlTemplate();
            
            // Show tool configuration section
            showSection(toolConfigSection);
        } else {
            selectedPathsSection.style.display = 'none';
            toolConfigSection.classList.add('hidden-section');
            toolConfigSection.classList.remove('visible-section');
        }
    }

    function generateHtmlTemplate() {
        let template = '<div class="api-result">\n';
        selectedPaths.forEach(path => {
            template += `  <div class="result-item">\n`;
            template += `    <strong>${escapeHtml(path)}:</strong> {{${escapeHtml(path)}}}\n`;
            template += `  </div>\n`;
        });
        template += '</div>';
        
        outputTemplate.value = template;
    }

    // Clear selections
    clearSelectionsBtn.addEventListener('click', function() {
        selectedPaths.clear();
        document.querySelectorAll('.json-key.selected').forEach(el => {
            el.classList.remove('selected');
        });
        updateSelectedPathsDisplay();
    });

    function showSection(section) {
        section.classList.remove('hidden-section');
        section.classList.add('visible-section');
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize with one parameter row
    addParameterRow();
});
</script>

<?php include $path_prefix . 'footer.php'; ?>