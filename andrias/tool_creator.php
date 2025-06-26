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

        if (empty($apiUrl)) {
            echo json_encode(['status' => 'error', 'message' => 'URL API tidak boleh kosong.']);
            exit;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // PERHATIAN: Di lingkungan produksi, aktifkan verifikasi SSL dengan sertifikat CA yang tepat.

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
                'selected_response_paths' => $selected_response_paths, 
                'params' => $api_params_for_tool, 
                'primary_input_key' => $primary_input_param_key,
                'generated_output_html' => $generated_output_html // Simpan HTML yang sudah diedit
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
                // JSON-encode string HTML untuk disisipkan ke JS, lalu tambahkan slash untuk string PHP
                // Menggunakan JSON_UNESCAPED_SLASHES untuk mencegah double backslash di URL
                $generated_output_html_js_escaped = addslashes(json_encode($generated_output_html, JSON_UNESCAPED_SLASHES));

                $final_frontend_content = $tool_frontend_template;
                $final_frontend_content = str_replace('{{page_title}}', htmlspecialchars($name), $final_frontend_content);
                $final_frontend_content = str_replace('{{tool_icon}}', htmlspecialchars($icon), $final_frontend_content);
                $final_frontend_content = str_replace('{{tool_description}}', htmlspecialchars($description), $final_frontend_content);
                $final_frontend_content = str_replace('{{primary_input_param_key}}', htmlspecialchars($primary_input_param_key), $final_frontend_content);
                $final_frontend_content = str_replace('{{generated_output_html_js_escaped}}', $generated_output_html_js_escaped, $final_frontend_content);
                
                file_put_contents($tool_dir . "/index.php", $final_frontend_content);

                // --- PROSES PEMBUATAN FILE api.php (Backend Proxy) ---
                // JSON-encode parameter dan selected paths
                $api_params_json_encoded = addslashes(json_encode($api_params_for_tool, JSON_UNESCAPED_SLASHES));
                $selected_response_paths_encoded = addslashes(json_encode($selected_response_paths, JSON_UNESCAPED_SLASHES));
                // JSON-encode HTML template untuk api.php
                // Menggunakan JSON_UNESCAPED_SLASHES untuk mencegah double backslash di URL dalam template HTML
                $api_generated_output_html_encoded = addslashes(json_encode($generated_output_html, JSON_UNESCAPED_SLASHES));

                $final_backend_api_content = $tool_backend_api_template;
                $final_backend_api_content = str_replace('{{api_url_for_tool}}', htmlspecialchars($api_url_for_tool), $final_backend_api_content);
                $final_backend_api_content = str_replace('{{api_method_for_tool}}', htmlspecialchars($api_method_for_tool), $final_backend_api_content);
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
    /* Styles for tool_creator.php */
    .tool-creator-card {
        background-color: #ffffff;
        border-radius: 0.75rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        padding: 2rem;
        margin-top: 2rem;
        margin-bottom: 2rem; /* Added margin-bottom */
    }
    .form-group label {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .input-group-param {
        margin-bottom: 0.5rem;
    }
    .json-display-box {
        background-color: #212529;
        color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.5rem;
        min-height: 100px;
        max-height: 400px;
        overflow-y: auto;
        font-family: monospace;
        font-size: 0.85rem;
        white-space: pre-wrap;
        word-break: break-all;
        position: relative; /* Untuk posisi overlay */
    }
    .json-display-box pre {
        margin: 0;
    }
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        z-index: 10;
        border-radius: 0.75rem;
    }
    .loading-overlay p {
        color: #495057;
        margin-top: 1rem;
    }
    .form-section-hidden {
        display: none;
        opacity: 0;
        height: 0;
        overflow: hidden;
        transition: opacity 0.5s ease, height 0.5s ease;
    }
    .form-section-visible {
        display: block;
        opacity: 1;
        height: auto;
    }
    .btn-outline-secondary {
        color: #6c757d;
        border-color: #6c757d;
    }
    .btn-outline-secondary:hover {
        background-color: #6c757d;
        color: white;
    }

    /* JSON display specific styles for clickability */
    .json-key, .json-value, .json-array-index {
        cursor: pointer;
        transition: background-color 0.2s ease;
        padding: 1px 3px; /* Added padding for better click area */
        border-radius: 3px;
        display: inline-block; /* To allow padding/background */
    }
    .json-key:hover, .json-value:hover, .json-array-index:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }
    .json-key { color: #8be9fd; /* light blue */ }
    .json-string { color: #f1fa8c; /* yellow */ }
    .json-number { color: #bd93f9; /* purple */ }
    .json-boolean { color: #ff79c6; /* pink */ }
    .json-null { color: #ffb86c; /* orange */ }
    .json-object, .json-array {
        margin-left: 20px;
        border-left: 1px dashed rgba(255,255,255,0.2);
        padding-left: 10px;
    }
    .json-object-bracket, .json-array-bracket {
        color: #f8f8f2; /* white */
    }
    .json-comma {
        color: #f8f8f2; /* white */
        margin-right: 5px;
    }
    .json-selected-path {
        background-color: rgba(0, 123, 255, 0.4); /* Highlight blue */
        border: 1px solid #007bff;
        color: white;
    }

    /* Styles for Selected Paths and Generated HTML */
    #selectedPathsDisplay {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        padding: 1rem;
        min-height: 80px;
        font-family: monospace;
        font-size: 0.85rem;
        word-break: break-all;
        margin-top: 1rem;
    }
    #generatedOutputHtml {
        margin-top: 1rem;
        border: 1px solid #ced4da;
        border-radius: 0.5rem;
        min-height: 150px;
        padding: 1rem;
        font-family: monospace;
        font-size: 0.85rem;
    }
    .generate-ai-html-btn {
        margin-top: 1rem;
        display: block;
        width: 100%;
    }
    .html-result-preview-box {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        padding: 1rem;
        min-height: 80px;
        word-break: break-all;
        margin-top: 1rem;
    }
</style>

<div class="container my-5">
    <div class="text-center mb-4">
        <h1 class="display-5 fw-bold"><i class="fas fa-magic me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
        <p class="lead text-muted">Uji API dan buat konfigurasi tool baru secara otomatis.</p>
    </div>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success mt-3" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger mt-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <div class="tool-creator-card position-relative">
        <div id="loadingOverlay" class="loading-overlay" style="display: none;">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p>Memproses permintaan...</p>
        </div>
        <div id="aiLoadingOverlay" class="loading-overlay" style="display: none;">
            <div class="spinner-border text-info" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading AI...</span>
            </div>
            <p>Membuat HTML dengan AI...</p>
        </div>


        <h4><i class="fas fa-vial me-2"></i>Uji API</h4>
        <p class="text-muted mb-4">Masukkan URL API, metode, dan parameter untuk menguji respons.</p>

        <form id="apiTestForm" class="mb-5">
            <div class="mb-3">
                <label for="apiUrl" class="form-label">URL API:</label>
                <input type="url" class="form-control" id="apiUrl" name="api_url" placeholder="Contoh: https://api.example.com/data" required>
            </div>
            <div class="mb-3">
                <label class="form-label d-block">Metode HTTP:</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="method" id="methodGet" value="GET" checked>
                    <label class="form-check-label" for="methodGet">GET</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="method" id="methodPost" value="POST">
                    <label class="form-check-label" for="methodPost">POST</label>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Parameter (Key-Value Pairs):</label>
                <div id="paramContainer">
                    <!-- Dynamic parameter inputs will be added here -->
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addParamBtn">
                    <i class="fas fa-plus me-2"></i>Tambah Parameter
                </button>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-play-circle me-2"></i>Uji API
            </button>
        </form>

        <h4 class="mt-5"><i class="fas fa-code me-2"></i>Respons API</h4>
        <p class="text-muted mb-2">Respons mentah dari API akan ditampilkan di sini. **Klik pada nilai untuk memilih jalurnya.**</p>
        <div class="json-display-box" id="apiResponseDisplay">
            <!-- API Response (raw clickable JSON) will be displayed here -->
            <p class="text-muted text-center mb-0">Belum ada respons. Silakan uji API terlebih dahulu.</p>
        </div>
        <div id="rawJsonToggle" style="cursor:pointer; color:#007bff; text-align: right; margin-top: 5px;">[Tampilkan Pratinjau HTML]</div>


        <div id="toolCreationSection" class="form-section-hidden mt-5">
            <h4><i class="fas fa-cogs me-2"></i>Konfigurasi Tool Baru</h4>
            <p class="text-muted mb-4">Lengkapi detail untuk membuat tool berdasarkan respons API ini.</p>
            <form id="newToolForm" method="POST" action="tool_creator.php">
                <input type="hidden" name="action" value="save_tool">
                <input type="hidden" name="api_url_for_tool" id="api_url_for_tool">
                <input type="hidden" name="api_method_for_tool" id="api_method_for_tool">
                <input type="hidden" name="api_params_for_tool" id="api_params_for_tool"> 
                <input type="hidden" name="selected_response_paths" id="selected_response_paths_input"> 
                
                <div class="mb-3">
                    <label for="tool_name" class="form-label">Nama Tool:</label>
                    <input type="text" class="form-control" id="tool_name" name="tool_name" required>
                </div>
                <div class="mb-3">
                    <label for="tool_slug" class="form-label">Slug:</label>
                    <input type="text" class="form-control" id="tool_slug" name="tool_slug" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required>
                    <small class="text-muted">Akan dibuat otomatis dari nama tool. Gunakan huruf kecil, angka, dan strip.</small>
                </div>
                <div class="mb-3">
                    <label for="tool_icon" class="form-label">Ikon (Font Awesome):</label>
                    <input type="text" class="form-control" id="tool_icon" name="tool_icon" value="fas fa-tools" placeholder="e.g., fas fa-cog" required>
                    <small class="text-muted">Contoh: <code>fas fa-star</code>. Akan muncul di daftar tools.</small>
                </div>
                <div class="mb-3">
                    <label for="tool_description" class="form-label">Deskripsi Singkat:</label>
                    <textarea class="form-control" id="tool_description" name="tool_description" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="tool_status" class="form-label">Status:</label>
                    <select class="form-select" id="tool_status" name="tool_status" required>
                        <option value="active">Aktif</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="primary_input_param_key" class="form-label">Kunci Parameter Input Utama (Opsional):</label>
                    <input type="text" class="form-control" id="primary_input_param_key" name="primary_input_param_key" placeholder="Contoh: link, query, id">
                    <small class="text-muted">Masukkan kunci parameter yang akan digunakan oleh input utama di halaman tool. Contoh: 'link' untuk video YouTube. Jika ini parameter pertama, akan terisi otomatis.</small>
                </div>
                
                <h5 class="mt-4"><i class="fas fa-bezier-curve me-2"></i>Output Tool Disesuaikan:</h5>
                <p class="text-muted mb-2">Pilih nilai-nilai dari respons API di atas. Lalu atur tampilan HTML-nya di bawah. Gunakan <code>{{path.to.value}}</code> sebagai placeholder.</p>
                <div class="mb-3">
                    <label class="form-label">Jalur Respon JSON yang Dipilih:</label>
                    <div id="selectedPathsDisplay" class="result-box mb-2">
                        <!-- Selected paths will be listed here -->
                        Tidak ada jalur yang dipilih.
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="clearSelectedPathsBtn">
                        <i class="fas fa-trash-alt me-2"></i>Bersihkan Jalur
                    </button>
                </div>
                <div class="mb-3">
                    <label for="generatedOutputHtml" class="form-label">Template Output HTML (Dapat Diedit):</label>
                    <textarea class="form-control" id="generatedOutputHtml" name="generated_output_html" rows="10" required></textarea>
                    <small class="text-muted">Desain tampilan hasil tool Anda. Gunakan placeholder seperti <code>&lt;h3&gt;Judul: {{title}}&lt;/h3&gt;&lt;p&gt;Deskripsi: {{description}}&lt;/p&gt;</code></small>
                    <button type="button" class="btn btn-info generate-ai-html-btn" id="generateHtmlWithAIBtn">
                        <i class="fas fa-robot me-2"></i>Buat HTML dengan AI
                    </button>
                </div>

                <button type="submit" class="btn btn-success w-100">
                    <i class="fas fa-plus-circle me-2"></i>Buat Tool
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiTestForm = document.getElementById('apiTestForm');
    const apiUrlInput = document.getElementById('apiUrl');
    const methodGet = document.getElementById('methodGet');
    const methodPost = document.getElementById('methodPost');
    const paramContainer = document.getElementById('paramContainer');
    const addParamBtn = document.getElementById('addParamBtn');
    const apiResponseDisplay = document.getElementById('apiResponseDisplay'); // This will show raw JSON or HTML preview
    const rawJsonToggle = document.getElementById('rawJsonToggle'); // New toggle button
    const loadingOverlay = document.getElementById('loadingOverlay');
    const aiLoadingOverlay = document.getElementById('aiLoadingOverlay'); 

    const toolCreationSection = document.getElementById('toolCreationSection');
    const newToolForm = document.getElementById('newToolForm');
    const toolNameInput = document.getElementById('tool_name');
    const toolSlugInput = document.getElementById('tool_slug');
    const api_url_for_tool = document.getElementById('api_url_for_tool');
    const api_method_for_tool = document.getElementById('api_method_for_tool');
    const apiParamsForToolInput = document.getElementById('api_params_for_tool'); 
    const primaryInputParamKeyInput = document.getElementById('primary_input_param_key'); 

    const selectedResponsePathsInput = document.getElementById('selected_response_paths_input');
    const selectedPathsDisplay = document.getElementById('selectedPathsDisplay');
    const clearSelectedPathsBtn = document.getElementById('clearSelectedPathsBtn');
    const generatedOutputHtmlTextarea = document.getElementById('generatedOutputHtml');
    const generateHtmlWithAIBtn = document.getElementById('generateHtmlWithAIBtn'); 

    let paramCounter = 0;
    let selectedJsonPaths = new Set(); 
    let fullApiResponseData = null; // Stores the raw JSON data from the API test
    let currentDisplayMode = 'html_preview'; // Default to HTML preview

    // Function to escape HTML special characters
    function htmlspecialcharsJS(str) {
        if (typeof str !== 'string') {
            str = String(str);
        }
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Function to render clickable JSON
    function renderJsonClickable(data, currentPath = '') {
        let html = '';
        if (typeof data === 'object' && data !== null) {
            if (Array.isArray(data)) {
                html += '<span class="json-array-bracket">[</span>';
                if (data.length > 0) {
                    html += '<div class="json-array">';
                    data.forEach((item, index) => {
                        const itemPath = currentPath ? `${currentPath}[${index}]` : `[${index}]`;
                        html += `<div class="json-item-wrapper"><span class="json-array-index ${selectedJsonPaths.has(itemPath) ? 'json-selected-path' : ''}" data-json-path="${itemPath}"></span> ${renderJsonClickable(item, itemPath)}`;
                        if (index < data.length - 1) html += '<span class="json-comma">,</span>';
                        html += '</div>';
                    });
                    html += '</div>';
                }
                html += '<span class="json-array-bracket">]</span>';
            } else {
                html += '<span class="json-object-bracket">{</span>';
                const keys = Object.keys(data);
                if (keys.length > 0) {
                    html += '<div class="json-object">';
                    keys.forEach((key, index) => {
                        const newPath = currentPath ? `${currentPath}.${key}` : key;
                        html += `<div class="json-key-value-pair"><span class="json-key ${selectedJsonPaths.has(newPath) ? 'json-selected-path' : ''}" data-json-path="${newPath}">"${htmlspecialcharsJS(key)}"</span>: ${renderJsonClickable(data[key], newPath)}`;
                        if (index < keys.length - 1) html += '<span class="json-comma">,</span>';
                        html += '</div>';
                    });
                    html += '</div>';
                }
                html += '<span class="json-object-bracket">}</span>';
            }
        } else {
            let displayValue = JSON.stringify(data);
            let className = '';
            if (typeof data === 'string') {
                className = 'json-string';
            } else if (typeof data === 'number') {
                className = 'json-number';
            } else if (typeof data === 'boolean') {
                className = 'json-boolean';
            } else if (data === null) {
                className = 'json-null';
            }
            html += `<span class="json-value ${className} ${selectedJsonPaths.has(currentPath) ? 'json-selected-path' : ''}" data-json-path="${currentPath}">${htmlspecialcharsJS(displayValue)}</span>`;
        }
        return html;
    }

    // Function to handle click on JSON elements for multi-selection
    function handleJsonClick(event) {
        let target = event.target;
        let path = null;

        while (target && !target.dataset.jsonPath && target !== apiResponseDisplay) {
            target = target.parentElement;
        }

        if (target && target.dataset.jsonPath) {
            path = target.dataset.jsonPath;
            
            if (selectedJsonPaths.has(path)) {
                selectedJsonPaths.delete(path);
                target.classList.remove('json-selected-path');
            } else {
                selectedJsonPaths.add(path);
                target.classList.add('json-selected-path');
            }
            updateSelectedPathsDisplay();
            generateOutputHtmlPreview(); // Update HTML preview in textarea
            // No need to call updateTestResultHtmlDisplay here directly
            // The display mode will handle the rendering based on currentDisplayMode
        }
    }

    // Function to update the display of selected paths
    function updateSelectedPathsDisplay() {
        const pathsArray = Array.from(selectedJsonPaths);
        if (pathsArray.length > 0) {
            selectedPathsDisplay.innerHTML = pathsArray.map(p => `<code>${htmlspecialcharsJS(p)}</code>`).join('<br>');
            selectedResponsePathsInput.value = JSON.stringify(pathsArray);
            clearSelectedPathsBtn.style.display = 'block';
        } else {
            selectedPathsDisplay.textContent = 'Tidak ada jalur yang dipilih.';
            selectedResponsePathsInput.value = '[]';
            clearSelectedPathsBtn.style.display = 'none';
        }
    }

    // Function to clear all selected paths
    clearSelectedPathsBtn.addEventListener('click', function() {
        selectedJsonPaths.clear();
        // Re-render raw JSON to remove highlights
        if (fullApiResponseData) {
            apiResponseDisplay.innerHTML = renderJsonClickable(fullApiResponseData);
            apiResponseDisplay.addEventListener('click', handleJsonClick); // Add click listener back
        }
        updateSelectedPathsDisplay();
        generateOutputHtmlPreview(); // Update HTML preview
        updateTestResultHtmlDisplay(); // Update the main display
    });

    // Function to extract value from JSON data based on path
    function extractValueFromPath(data, path) {
        if (!path || !data) return 'N/A';
        let pathParts = path.split('.');
        let current = data;
        let found = true;

        for (const part of pathParts) {
            const arrayMatch = part.match(/(\w+)\[(\d+)\]/);
            if (arrayMatch) {
                const arrayKey = arrayMatch[1];
                const arrayIndex = parseInt(arrayMatch[2]);
                if (current && typeof current === 'object' && current[arrayKey] && Array.isArray(current[arrayKey]) && current[arrayKey].length > arrayIndex) {
                    current = current[arrayKey][arrayIndex];
                } else {
                    found = false;
                    break;
                }
            } else if (current && typeof current === 'object' && current.hasOwnProperty(part)) {
                current = current[part];
            } else {
                found = false;
                break;
            }
        }
        return found ? (typeof current === 'object' ? JSON.stringify(current) : current) : 'N/A';
    }

    // Function to generate and update the editable HTML output preview (for textarea)
    function generateOutputHtmlPreview() {
        let htmlContent = '';
        if (selectedJsonPaths.size > 0 && fullApiResponseData) {
            Array.from(selectedJsonPaths).forEach(path => {
                const value = extractValueFromPath(fullApiResponseData, path);
                htmlContent += `<div><strong>${htmlspecialcharsJS(path)}:</strong> {{${htmlspecialcharsJS(path)}}}</div>\n`; // Use placeholder
            });
        } else if (fullApiResponseData) {
            htmlContent = `<p>Output HTML Anda akan tampil di sini. Anda bisa menggunakan <code>{{path.ke.nilai}}</code> sebagai placeholder dari data JSON yang Anda pilih.</p>\n<p>Contoh: <code>&lt;h1&gt;Judul: {{title}}&lt;/h1&gt;</code></p>`;
        } else {
            htmlContent = '<!-- Output HTML akan digenerate di sini -->';
        }
        generatedOutputHtmlTextarea.value = htmlContent;
    }

    // NEW FUNCTION: Updates the main apiResponseDisplay with rendered HTML or raw JSON
    function updateTestResultHtmlDisplay() {
        if (!fullApiResponseData) {
            apiResponseDisplay.innerHTML = '<p class="text-muted text-center mb-0">Belum ada respons. Silakan uji API terlebih dahulu.</p>';
            rawJsonToggle.style.display = 'none'; // Hide toggle if no data
            return;
        }
        
        if (currentDisplayMode === 'html_preview') {
            let renderedHtml = generatedOutputHtmlTextarea.value;
            // Replace placeholders with actual values for live preview
            Array.from(selectedJsonPaths).forEach(path => {
                const value = extractValueFromPath(fullApiResponseData, path);
                const placeholder = new RegExp(`\{\{${escapeRegExp(path)}\}\}`, 'g');
                renderedHtml = renderedHtml.replace(placeholder, htmlspecialcharsJS(value));
            });
            apiResponseDisplay.innerHTML = `<div class="html-result-preview-box">${renderedHtml}</div>`;
            apiResponseDisplay.removeEventListener('click', handleJsonClick); // Remove click listener when showing HTML preview
            rawJsonToggle.textContent = '[Tampilkan JSON Mentah]';
        } else if (currentDisplayMode === 'json') {
            apiResponseDisplay.innerHTML = renderJsonClickable(fullApiResponseData);
            apiResponseDisplay.addEventListener('click', handleJsonClick); // Add click listener back
            rawJsonToggle.textContent = '[Tampilkan Pratinjau HTML]';
        }
        rawJsonToggle.style.display = 'block'; // Ensure toggle is visible if data exists
    }

    // Toggle between raw JSON and HTML preview
    rawJsonToggle.addEventListener('click', function() {
        if (fullApiResponseData) { // Only toggle if there's data to display
            currentDisplayMode = (currentDisplayMode === 'json') ? 'html_preview' : 'json';
            updateTestResultHtmlDisplay();
        }
    });

    // Utility function for escaping regex characters in path
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); // $& means the matched substring
    }


    // Function to generate HTML using Gemini AI
    generateHtmlWithAIBtn.addEventListener('click', async function() {
        if (!fullApiResponseData) {
            alert('Harap uji API dan dapatkan respons terlebih dahulu.');
            return;
        }
        if (selectedJsonPaths.size === 0) {
            alert('Harap pilih setidaknya satu jalur JSON dari respons API.');
            return;
        }

        aiLoadingOverlay.style.display = 'flex'; // Show AI loading indicator

        const pathsForAI = Array.from(selectedJsonPaths);
        const sampleDataForAI = {};
        pathsForAI.forEach(path => {
            sampleDataForAI[path] = extractValueFromPath(fullApiResponseData, path);
        });

        const prompt = `
            Anda adalah seorang asisten yang ahli dalam membuat struktur HTML yang rapi dan estetik untuk menampilkan data.
            Buatlah struktur HTML minimalis namun menarik untuk menampilkan data dari respons API berikut.
            Gunakan placeholder dalam format {{path.ke.nilai}} untuk setiap data yang ingin ditampilkan.
            Contoh: <p>Nama: {{user.name}}</p>
            Pastikan HTML yang dihasilkan bersih, estetik, dan mudah diedit oleh admin.
            Data yang tersedia dan path yang dipilih:
            ${JSON.stringify(sampleDataForAI, null, 2)}

            Sertakan juga CSS inline minimal jika perlu untuk kerapian (misal: margin, padding, font-size).
            Desain harus selaras dengan tampilan header/footer yang umumnya menggunakan Bootstrap 5 dan Font Awesome.
            Fokus pada readability dan UX.
            Berikan hanya kode HTML, tanpa penjelasan.
        `;

        let chatHistory = [];
        chatHistory.push({ role: "user", parts: [{ text: prompt }] });

        const payload = {
            contents: chatHistory,
            generationConfig: {
                temperature: 0.7,
                topP: 0.95,
                topK: 40,
            }
        };

        const apiKey = "AIzaSyC6KEcR5VD3Xe4az2Gt8lmvHbmExhpMRcI"; // Your provided API Key
        const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${apiKey}`;

        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (result.candidates && result.candidates.length > 0 &&
                result.candidates[0].content && result.candidates[0].content.parts &&
                result.candidates[0].content.parts.length > 0) {
                const aiGeneratedHtml = result.candidates[0].content.parts[0].text;
                // Clean up markdown code block if present
                const cleanedHtml = aiGeneratedHtml.replace(/```html\n?|```/g, '').trim();
                generatedOutputHtmlTextarea.value = cleanedHtml;
                updateTestResultHtmlDisplay(); // Update the main display after AI generation
            } else {
                alert("Gagal menghasilkan HTML dari AI. Respons tidak valid.");
            }
        } catch (error) {
            console.error('Error calling Gemini API:', error);
            alert('Terjadi kesalahan saat memanggil Gemini AI: ' + error.message);
        } finally {
            aiLoadingOverlay.style.display = 'none'; // Hide AI loading indicator
        }
    });


    // Function to add a new parameter input row
    function addParameterRow(key = '', value = '') {
        const row = document.createElement('div');
        row.className = 'input-group input-group-param';
        const keyInput = document.createElement('input');
        keyInput.type = 'text';
        keyInput.className = 'form-control';
        keyInput.name = `params[${paramCounter}][key]`;
        keyInput.placeholder = 'Parameter Key';
        keyInput.value = htmlspecialcharsJS(key);

        const valueInput = document.createElement('input');
        valueInput.type = 'text';
        valueInput.className = 'form-control';
        valueInput.name = `params[${paramCounter}][value]`;
        valueInput.placeholder = 'Parameter Value';
        valueInput.value = htmlspecialcharsJS(value);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-outline-danger remove-param-btn';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.addEventListener('click', function() {
            row.remove();
            // Update primary input key if the removed one was the first or the currently active
            if (paramContainer.children.length > 0) {
                const firstExistingKeyInput = paramContainer.children[0].querySelector('input[name$="[key]"]');
                primaryInputParamKeyInput.value = firstExistingKeyInput ? firstExistingKeyInput.value : '';
            } else {
                primaryInputParamKeyInput.value = '';
            }
        });

        row.appendChild(keyInput);
        row.appendChild(valueInput);
        row.appendChild(removeBtn);
        paramContainer.appendChild(row);

        // Auto-fill primary_input_param_key if this is the first parameter
        if (paramContainer.children.length === 1) {
            primaryInputParamKeyInput.value = keyInput.value;
        }
        
        // Update primary_input_param_key when the key input changes, only if it's the first one
        keyInput.addEventListener('input', function() {
            if (paramContainer.children[0] === row) { // Check if this is still the first row
                primaryInputParamKeyInput.value = this.value;
            }
        });

        paramCounter++;
    }

    addParamBtn.addEventListener('click', () => addParameterRow());

    // Initial parameter row only if none exist (e.g., on first load)
    if (paramContainer.children.length === 0) {
        addParameterRow();
    }

    // Handle API Test Form Submission
    apiTestForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        loadingOverlay.style.display = 'flex'; // Show loading overlay
        apiResponseDisplay.innerHTML = '<p class="text-muted text-center mb-0">Memuat respons API...</p>'; // Clear previous response
        selectedJsonPaths.clear(); // Clear previous selections
        updateSelectedPathsDisplay(); // Update display
        generatedOutputHtmlTextarea.value = ''; // Clear generated HTML

        fullApiResponseData = null; // Clear stored API data
        currentDisplayMode = 'html_preview'; // Reset display mode to HTML preview initially

        const apiUrl = apiUrlInput.value;
        const method = document.querySelector('input[name="method"]:checked').value;
        const paramInputs = paramContainer.querySelectorAll('.input-group-param');
        const params = [];
        paramInputs.forEach(row => {
            const key = row.querySelector('input[name$="[key]"]').value;
            const value = row.querySelector('input[name$="[value]"]').value;
            if (key) { // Only add if key is not empty
                params.push({ key, value });
            }
        });

        const formData = new FormData();
        formData.append('action', 'test_api');
        formData.append('api_url', apiUrl);
        formData.append('method', method);
        params.forEach((param, index) => {
            formData.append(`params[${index}][key]`, param.key);
            formData.append(`params[${index}][value]`, param.value);
        });

        try {
            const response = await fetch('tool_creator.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            loadingOverlay.style.display = 'none'; // Hide loading overlay

            if (result.status === 'success') {
                if (result.is_json === false) {
                     apiResponseDisplay.innerHTML = `
                        <p class="text-warning"><strong>Respons bukan JSON:</strong></p>
                        <pre>${htmlspecialcharsJS(result.data)}</pre>
                     `;
                     rawJsonToggle.style.display = 'none'; // Hide toggle if not JSON
                } else {
                    fullApiResponseData = result.data; // Store full response
                    // Render HTML preview initially
                    updateTestResultHtmlDisplay(); // This will display HTML preview by default
                    // But also add the click listener for JSON (it will be active when toggled)
                    apiResponseDisplay.addEventListener('click', handleJsonClick); 
                    rawJsonToggle.style.display = 'block'; // Show toggle for JSON
                }
                
                // Show tool creation section and pre-fill fields
                toolCreationSection.classList.remove('form-section-hidden');
                toolCreationSection.classList.add('form-section-visible');
                api_url_for_tool.value = apiUrl;
                api_method_for_tool.value = method;
                apiParamsForToolInput.value = JSON.stringify(params); // Simpan parameter yang diuji
                
                generateOutputHtmlPreview(); // Generate initial HTML preview

            } else {
                apiResponseDisplay.innerHTML = `<div class="alert alert-danger mb-0">${htmlspecialcharsJS(result.message)}</div>`;
                toolCreationSection.classList.remove('form-section-visible');
                toolCreationSection.classList.add('form-section-hidden');
                rawJsonToggle.style.display = 'none'; // Hide toggle on error
            }
        } catch (error) {
            console.error('Error:', error);
            loadingOverlay.style.display = 'none';
            apiResponseDisplay.innerHTML = `<div class="alert alert-danger mb-0">Terjadi kesalahan jaringan: ${htmlspecialcharsJS(error.message)}</div>`;
            toolCreationSection.classList.remove('form-section-visible');
            toolCreationSection.classList.add('form-section-hidden');
            rawJsonToggle.style.display = 'none'; // Hide toggle on error
        }
    });

    // Auto-generate slug from tool name
    toolNameInput.addEventListener('keyup', function() {
        toolSlugInput.value = createSlug(this.value);
    });

    function createSlug(str) {
        if (!str) return '';
        str = str.replace(/^\s+|\s+$/g, ''); 
        str = str.toLowerCase();
        var from = "àáäâèéëêìíïîòóöôùúüûñç·/_,:;";
        var to   = "aaaaeeeeiiiioooouuuunc------";
        for (var i=0, l=from.length ; i<l ; i++) {
            str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
        }
        str = str.replace(/[^a-z0-9 -]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-');
        return str;
    }
});
</script>

<?php include $path_prefix . 'footer.php'; ?>
