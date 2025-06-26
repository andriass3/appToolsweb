<?php
// Cek apakah ini dipanggil dari dashboard atau diakses langsung
$is_dashboard_page = isset($_GET['page']) && $_GET['page'] === 'tool_creator';

if (!$is_dashboard_page) {
    // Jika diakses langsung, gunakan logika lama
    $page_title = "Advanced Tool Creator";
    $path_prefix = '../'; 
    $tools_file = $path_prefix . 'tools.json'; 
    
    // Sertakan file autentikasi admin
    require_once 'auth.php'; 
    // Sertakan file fungsi-fungsi yang sudah dipisahkan
    require_once 'includes/tool_functions.php';
    require_once 'includes/tool_template_content.php';
    
    // Include header
    include $path_prefix . 'header.php';
} else {
    // Jika dipanggil dari dashboard, tidak perlu include header/footer
    $page_title = "Advanced Tool Creator";
    $path_prefix = '../'; 
    $tools_file = $path_prefix . 'tools.json'; 
    
    // Sertakan file fungsi-fungsi yang sudah dipisahkan
    require_once 'includes/tool_functions.php';
    require_once 'includes/tool_template_content.php';
}

// --- ENHANCED TEMPLATE CONTENT ---
$enhanced_frontend_template = <<<'EOT'
<?php 
$page_title = "{{page_title}}";
$tool_icon = "{{tool_icon}}";
$tool_description = "{{tool_description}}";
$path_prefix = '../../'; 

$api_result_display_html = '';
$error_message = null;

// Include header
if (file_exists($path_prefix . 'header.php')) {
    include $path_prefix . 'header.php';
} else {
    echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>" . htmlspecialchars($page_title) . "</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css' rel='stylesheet'>";
    echo "<style> body { padding-top: 20px; padding-bottom: 20px; background-color: #f8f9fa; } .footer { padding: 1rem 0; margin-top: 2rem; border-top: 1px solid #dee2e6; text-align: center; } </style>";
    echo "</head><body><main class='container'>";
}
?>

<style>
    .tool-page-container .card {
        background-color: #ffffff;
        border-radius: 0.75rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: none;
    }
    .tool-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 3rem 2rem;
        border-radius: 0.75rem;
        margin-bottom: 2rem;
        text-align: center;
    }
    .tool-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }
    .tool-header p {
        font-size: 1.2rem;
        opacity: 0.9;
        margin: 0;
    }
    .form-section {
        background: white;
        border-radius: 0.75rem;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    .form-section h4 {
        color: #495057;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e9ecef;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #e9ecef;
        padding: 0.75rem 1rem;
        font-size: 1rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 8px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }
    .result-display {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        padding: 1.5rem;
        min-height: 120px;
        word-break: break-all;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .loading-spinner {
        display: none; 
        text-align: center;
        margin-top: 1.5rem;
    }
    .loading-spinner .spinner-border {
        width: 3rem;
        height: 3rem;
    }
    .parameter-group {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        border-left: 4px solid #667eea;
    }
    .parameter-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    .parameter-description {
        font-size: 0.9rem;
        color: #6c757d;
        margin-bottom: 0.75rem;
    }
    .success-animation {
        animation: fadeInUp 0.5s ease-out;
    }
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class='tool-page-container container my-5'>
    <div class='tool-header'>
        <h1><i class='<?php echo htmlspecialchars($tool_icon); ?> me-3'></i><?php echo $page_title; ?></h1>
        <p><?php echo $tool_description; ?></p>
    </div>

    <div class="form-section">
        <h4><i class="fas fa-cogs me-2"></i>Konfigurasi Tool</h4>
        <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form id='toolForm' method="POST" action="api_proxy.php">
            {{form_fields}}
            
            <div class="d-grid mt-4">
                <button type='submit' class='btn btn-primary btn-lg' id="submitBtn">
                    <i class="fas fa-rocket me-2"></i>Jalankan Tool
                </button>
            </div>
        </form>

        <div id='loadingIndicator' class='loading-spinner'>
            <div class='spinner-border text-primary' role='status'></div>
            <p class='text-muted mt-3'>Memproses permintaan...</p>
        </div>

        <div id='resultDisplay' class='mt-4' style='display:none;'>
            <h4><i class="fas fa-check-circle me-2 text-success"></i>Hasil</h4>
            <div class='result-display success-animation'>
                <!-- Results will be displayed here -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toolForm = document.getElementById('toolForm');
    const submitBtn = document.getElementById('submitBtn');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const resultDisplay = document.getElementById('resultDisplay');
    const resultContent = resultDisplay.querySelector('.result-display');

    toolForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        // Show loading state
        loadingIndicator.style.display = 'block';
        resultDisplay.style.display = 'none';
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';

        const formData = new FormData(toolForm);

        try {
            const response = await fetch('api_proxy.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                let errorBody = {};
                try {
                    errorBody = await response.json();
                } catch (e) {
                    errorBody.message = `HTTP Error: ${response.status}`;
                }
                throw new Error(errorBody.message || `HTTP error! Status: ${response.status}`);
            }

            const apiResponse = await response.json();

            if (apiResponse.status === 'success') {
                resultContent.innerHTML = apiResponse.html_output;
                resultDisplay.style.display = 'block';
                resultDisplay.scrollIntoView({ behavior: 'smooth' });
            } else {
                resultContent.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>${apiResponse.message || 'Terjadi kesalahan tidak dikenal.'}</div>`;
                resultDisplay.style.display = 'block';
            }

        } catch (error) {
            console.error('Error:', error);
            resultContent.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Terjadi kesalahan: ${error.message}</div>`;
            resultDisplay.style.display = 'block';
        } finally {
            loadingIndicator.style.display = 'none';
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-rocket me-2"></i>Jalankan Tool';
        }
    });
});
</script>

<?php include $path_prefix . 'footer.php'; ?>
EOT;

$enhanced_api_proxy_template = <<<'EOT'
<?php
// Enhanced API Proxy for {{tool_name}}
// This file securely handles external API calls and protects endpoint URLs

header('Content-Type: application/json');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Rate limiting configuration
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour
define('RATE_LIMIT_FILE', __DIR__ . '/rate_limit.json');

// API Configuration (Hidden from users)
$api_config = [
    'url' => '{{api_url}}',
    'method' => '{{api_method}}',
    'timeout' => 30,
    'max_retries' => 3,
    'retry_delay' => 1000, // milliseconds
    'headers' => [
        'User-Agent' => 'ToolProxy/1.0',
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ]
];

$response_mapping = json_decode('{{response_mapping}}', true);
$parameter_config = json_decode('{{parameter_config}}', true);

// Rate limiting function
function checkRateLimit($ip) {
    $rate_file = RATE_LIMIT_FILE;
    $current_time = time();
    
    if (!file_exists($rate_file)) {
        file_put_contents($rate_file, json_encode([]));
    }
    
    $rate_data = json_decode(file_get_contents($rate_file), true) ?: [];
    
    // Clean old entries
    $rate_data = array_filter($rate_data, function($entry) use ($current_time) {
        return ($current_time - $entry['timestamp']) < RATE_LIMIT_WINDOW;
    });
    
    // Count requests from this IP
    $ip_requests = array_filter($rate_data, function($entry) use ($ip) {
        return $entry['ip'] === $ip;
    });
    
    if (count($ip_requests) >= RATE_LIMIT_REQUESTS) {
        return false;
    }
    
    // Add current request
    $rate_data[] = ['ip' => $ip, 'timestamp' => $current_time];
    file_put_contents($rate_file, json_encode($rate_data));
    
    return true;
}

// Input validation function
function validateInput($value, $config) {
    if ($config['required'] && empty($value)) {
        return ['valid' => false, 'error' => 'Field is required'];
    }
    
    if (!empty($value)) {
        // Type validation
        switch ($config['type']) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return ['valid' => false, 'error' => 'Invalid email format'];
                }
                break;
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return ['valid' => false, 'error' => 'Invalid URL format'];
                }
                break;
            case 'number':
                if (!is_numeric($value)) {
                    return ['valid' => false, 'error' => 'Must be a number'];
                }
                break;
        }
        
        // Length validation
        if (isset($config['min_length']) && strlen($value) < $config['min_length']) {
            return ['valid' => false, 'error' => "Minimum length is {$config['min_length']}"];
        }
        
        if (isset($config['max_length']) && strlen($value) > $config['max_length']) {
            return ['valid' => false, 'error' => "Maximum length is {$config['max_length']}"];
        }
        
        // Pattern validation
        if (isset($config['pattern']) && !preg_match($config['pattern'], $value)) {
            return ['valid' => false, 'error' => 'Invalid format'];
        }
    }
    
    return ['valid' => true];
}

// Extract value from JSON response using path
function extractValue($data, $path) {
    if (empty($path)) return $data;
    
    $keys = explode('.', $path);
    $current = $data;
    
    foreach ($keys as $key) {
        if (preg_match('/(\w+)\[(\d+)\]/', $key, $matches)) {
            $arrayKey = $matches[1];
            $index = (int)$matches[2];
            if (isset($current[$arrayKey][$index])) {
                $current = $current[$arrayKey][$index];
            } else {
                return null;
            }
        } elseif (isset($current[$key])) {
            $current = $current[$key];
        } else {
            return null;
        }
    }
    
    return $current;
}

// Main processing
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Rate limiting check
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!checkRateLimit($client_ip)) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Rate limit exceeded. Please try again later.']);
    exit;
}

// Validate and process input parameters
$api_params = [];
$validation_errors = [];

foreach ($parameter_config as $param) {
    $value = $_POST[$param['key']] ?? '';
    
    // Apply default value if empty and default exists
    if (empty($value) && isset($param['default_value'])) {
        $value = $param['default_value'];
    }
    
    $validation = validateInput($value, $param);
    if (!$validation['valid']) {
        $validation_errors[] = "{$param['label']}: {$validation['error']}";
        continue;
    }
    
    if (!empty($value) || $param['required']) {
        $api_params[$param['key']] = $value;
    }
}

if (!empty($validation_errors)) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Validation failed: ' . implode(', ', $validation_errors)
    ]);
    exit;
}

// Make API request with retry logic
$attempt = 0;
$response = null;
$last_error = null;

while ($attempt < $api_config['max_retries']) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_config['url'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $api_config['timeout'],
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array_map(function($key, $value) {
            return "$key: $value";
        }, array_keys($api_config['headers']), $api_config['headers'])
    ]);
    
    if ($api_config['method'] === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($api_params));
    } else {
        $url_with_params = $api_config['url'] . '?' . http_build_query($api_params);
        curl_setopt($ch, CURLOPT_URL, $url_with_params);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        $last_error = "cURL Error: $curl_error";
        $attempt++;
        if ($attempt < $api_config['max_retries']) {
            usleep($api_config['retry_delay'] * 1000);
        }
        continue;
    }
    
    if ($http_code === 200) {
        break;
    }
    
    $last_error = "HTTP Error: $http_code";
    $attempt++;
    if ($attempt < $api_config['max_retries']) {
        usleep($api_config['retry_delay'] * 1000);
    }
}

if ($attempt >= $api_config['max_retries']) {
    echo json_encode([
        'status' => 'error', 
        'message' => "API request failed after {$api_config['max_retries']} attempts. Last error: $last_error"
    ]);
    exit;
}

// Process API response
$decoded_response = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid JSON response from API'
    ]);
    exit;
}

// Extract mapped values and generate HTML output
$extracted_values = [];
foreach ($response_mapping as $mapping) {
    $value = extractValue($decoded_response, $mapping['path']);
    $extracted_values[$mapping['key']] = $value !== null ? $value : 'N/A';
}

// Generate HTML output using template
$html_template = '{{html_template}}';
$html_output = $html_template;

foreach ($extracted_values as $key => $value) {
    $placeholder = '{{' . $key . '}}';
    $safe_value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $html_output = str_replace($placeholder, $safe_value, $html_output);
}

// Return success response
echo json_encode([
    'status' => 'success',
    'data' => $extracted_values,
    'html_output' => $html_output
]);
EOT;

// --- LOGIKA UTAMA UNTUK UJI API DAN SIMPAN TOOLS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'test_api') {
        header('Content-Type: application/json');
        $apiUrl = $_POST['api_url'] ?? '';
        $method = $_POST['method'] ?? 'GET';
        $params = $_POST['params'] ?? [];
        $headers = $_POST['headers'] ?? [];

        if (empty($apiUrl)) {
            echo json_encode(['status' => 'error', 'message' => 'URL API tidak boleh kosong.']);
            exit;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // Set custom headers
        $curl_headers = ['User-Agent: ToolCreator/1.0'];
        foreach ($headers as $header) {
            if (!empty($header['key']) && !empty($header['value'])) {
                $curl_headers[] = $header['key'] . ': ' . $header['value'];
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);

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
        } else {
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
            $decodedResponse = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo json_encode([
                    'status' => 'success', 
                    'http_code' => $httpCode, 
                    'data' => $decodedResponse, 
                    'is_json' => true,
                    'response_size' => strlen($response)
                ]);
            } else {
                echo json_encode([
                    'status' => 'success', 
                    'http_code' => $httpCode, 
                    'data' => $response, 
                    'is_json' => false,
                    'response_size' => strlen($response)
                ]);
            }
        }
        exit;

    } elseif ($action === 'save_tool') {
        $name = trim($_POST['tool_name']);
        $slug_input = trim($_POST['tool_slug']);
        $icon = trim($_POST['tool_icon']);
        $description = trim($_POST['tool_description']);
        $status = $_POST['tool_status'];
        $api_url_for_tool = trim($_POST['api_url_for_tool']);
        $api_method_for_tool = trim($_POST['api_method_for_tool']);
        $parameters = json_decode($_POST['parameters'] ?? '[]', true);
        $response_mapping = json_decode($_POST['response_mapping'] ?? '[]', true);
        $html_template = $_POST['html_template'] ?? '';
        $headers = json_decode($_POST['headers'] ?? '[]', true);

        if (empty($name) || empty($slug_input) || empty($icon) || empty($description) || empty($api_url_for_tool)) {
            $redirect_url = $is_dashboard_page ? 'dashboard.php?page=tool_creator&error=' : 'tool_creator.php?error=';
            header('Location: ' . $redirect_url . urlencode('Semua field wajib diisi.'));
            exit;
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug_input)) {
            $redirect_url = $is_dashboard_page ? 'dashboard.php?page=tool_creator&error=' : 'tool_creator.php?error=';
            header('Location: ' . $redirect_url . urlencode('Format slug tidak valid. Gunakan huruf kecil, angka, dan tanda hubung.'));
            exit;
        }

        $tools = get_all_tools_admin_creator($tools_file);
        foreach ($tools as $existing_tool) {
            if ($existing_tool['slug'] === $slug_input) {
                $redirect_url = $is_dashboard_page ? 'dashboard.php?page=tool_creator&error=' : 'tool_creator.php?error=';
                header('Location: ' . $redirect_url . urlencode('Slug sudah digunakan. Harap pilih slug lain.'));
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
                'parameters' => $parameters,
                'response_mapping' => $response_mapping,
                'html_template' => $html_template,
                'headers' => $headers,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];

        $tools[] = $new_tool;
        $save_result = save_tools_admin_creator($tools, $tools_file);

        if ($save_result['status'] === 'error') {
            $redirect_url = $is_dashboard_page ? 'dashboard.php?page=tool_creator&error=' : 'tool_creator.php?error=';
            header('Location: ' . $redirect_url . urlencode($save_result['message']));
            exit;
        }

        // Create tool directory and files
        $base_tools_dir = realpath(__DIR__ . '/../tools');
        $tool_dir = $base_tools_dir . DIRECTORY_SEPARATOR . $slug_input;

        if (!file_exists($tool_dir)) {
            mkdir($tool_dir, 0755, true);
        }

        // Generate form fields HTML
        $form_fields_html = '';
        foreach ($parameters as $param) {
            $required = $param['required'] ? 'required' : '';
            $placeholder = !empty($param['placeholder']) ? $param['placeholder'] : '';
            
            $form_fields_html .= '<div class="parameter-group">';
            $form_fields_html .= '<div class="parameter-label">' . htmlspecialchars($param['label']) . '</div>';
            
            if (!empty($param['description'])) {
                $form_fields_html .= '<div class="parameter-description">' . htmlspecialchars($param['description']) . '</div>';
            }
            
            if ($param['display_type'] === 'hidden') {
                $form_fields_html .= '<input type="hidden" name="' . htmlspecialchars($param['key']) . '" value="' . htmlspecialchars($param['default_value'] ?? '') . '">';
            } elseif ($param['display_type'] === 'select') {
                $form_fields_html .= '<select class="form-select" name="' . htmlspecialchars($param['key']) . '" ' . $required . '>';
                if (!$param['required']) {
                    $form_fields_html .= '<option value="">-- Pilih --</option>';
                }
                $options = explode(',', $param['options'] ?? '');
                foreach ($options as $option) {
                    $option = trim($option);
                    $selected = ($option === ($param['default_value'] ?? '')) ? 'selected' : '';
                    $form_fields_html .= '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>' . htmlspecialchars($option) . '</option>';
                }
                $form_fields_html .= '</select>';
            } elseif ($param['display_type'] === 'textarea') {
                $form_fields_html .= '<textarea class="form-control" name="' . htmlspecialchars($param['key']) . '" placeholder="' . htmlspecialchars($placeholder) . '" rows="4" ' . $required . '>' . htmlspecialchars($param['default_value'] ?? '') . '</textarea>';
            } else {
                $input_type = $param['type'] === 'email' ? 'email' : ($param['type'] === 'url' ? 'url' : 'text');
                $form_fields_html .= '<input type="' . $input_type . '" class="form-control" name="' . htmlspecialchars($param['key']) . '" placeholder="' . htmlspecialchars($placeholder) . '" value="' . htmlspecialchars($param['default_value'] ?? '') . '" ' . $required . '>';
            }
            
            $form_fields_html .= '</div>';
        }

        // Create frontend file
        $frontend_content = $enhanced_frontend_template;
        $frontend_content = str_replace('{{page_title}}', htmlspecialchars($name), $frontend_content);
        $frontend_content = str_replace('{{tool_icon}}', htmlspecialchars($icon), $frontend_content);
        $frontend_content = str_replace('{{tool_description}}', htmlspecialchars($description), $frontend_content);
        $frontend_content = str_replace('{{form_fields}}', $form_fields_html, $frontend_content);

        file_put_contents($tool_dir . "/index.php", $frontend_content);

        // Create API proxy file
        $api_content = $enhanced_api_proxy_template;
        $api_content = str_replace('{{tool_name}}', htmlspecialchars($name), $api_content);
        $api_content = str_replace('{{api_url}}', htmlspecialchars($api_url_for_tool), $api_content);
        $api_content = str_replace('{{api_method}}', htmlspecialchars($api_method_for_tool), $api_content);
        $api_content = str_replace('{{response_mapping}}', addslashes(json_encode($response_mapping)), $api_content);
        $api_content = str_replace('{{parameter_config}}', addslashes(json_encode($parameters)), $api_content);
        $api_content = str_replace('{{html_template}}', addslashes($html_template), $api_content);

        file_put_contents($tool_dir . "/api_proxy.php", $api_content);

        $redirect_url = $is_dashboard_page ? 'dashboard.php?page=tool_creator&message=' : 'tool_creator.php?message=';
        header('Location: ' . $redirect_url . urlencode('Tool berhasil dibuat dengan konfigurasi lengkap!'));
        exit;
    }
}
?>

<style>
    .enhanced-creator {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }
    .creator-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 3rem 2rem;
        border-radius: 1rem;
        margin-bottom: 2rem;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .creator-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }
    .creator-section {
        background: white;
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        border: 1px solid #e9ecef;
    }
    .section-header {
        display: flex;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f8f9fa;
    }
    .section-header h3 {
        margin: 0;
        color: #495057;
        font-weight: 600;
    }
    .section-header .badge {
        margin-left: 1rem;
        font-size: 0.8rem;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #e9ecef;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 8px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }
    .btn-outline-primary {
        border-color: #667eea;
        color: #667eea;
        border-radius: 8px;
    }
    .btn-outline-primary:hover {
        background: #667eea;
        border-color: #667eea;
    }
    .parameter-item, .mapping-item, .header-item {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        position: relative;
    }
    .remove-btn {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }
    .json-display {
        background: #2d3748;
        color: #e2e8f0;
        border-radius: 8px;
        padding: 1rem;
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
        max-height: 400px;
        overflow-y: auto;
        white-space: pre-wrap;
        word-break: break-all;
    }
    .json-key {
        color: #63b3ed;
        cursor: pointer;
        padding: 2px 4px;
        border-radius: 3px;
        transition: background-color 0.2s;
    }
    .json-key:hover {
        background-color: rgba(99, 179, 237, 0.2);
    }
    .json-key.selected {
        background-color: rgba(99, 179, 237, 0.4);
        color: white;
    }
    .json-string { color: #68d391; }
    .json-number { color: #fbb6ce; }
    .json-boolean { color: #f6ad55; }
    .json-null { color: #a0aec0; }
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
        flex-direction: column;
        border-radius: 1rem;
        z-index: 10;
    }
    .test-results {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }
    .success-indicator {
        color: #28a745;
        font-weight: 600;
    }
    .error-indicator {
        color: #dc3545;
        font-weight: 600;
    }
    .response-stats {
        display: flex;
        gap: 1rem;
        margin-top: 0.5rem;
    }
    .response-stats .badge {
        font-size: 0.8rem;
    }
    .html-preview {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
        min-height: 100px;
    }
</style>

<div class="enhanced-creator">
    <div class="container">
        <div class="creator-header">
            <h1><i class="fas fa-magic me-3"></i><?php echo htmlspecialchars($page_title); ?></h1>
            <p class="lead mb-0">Buat tool canggih dengan konfigurasi API eksternal yang aman dan mudah digunakan</p>
        </div>

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
        <div class="creator-section" id="apiConfigSection">
            <div class="section-header">
                <h3><i class="fas fa-cog me-2"></i>1. Konfigurasi API</h3>
                <span class="badge bg-primary">Wajib</span>
            </div>
            
            <form id="apiTestForm">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">URL API Endpoint</label>
                        <input type="url" class="form-control" id="apiUrl" placeholder="https://api.example.com/endpoint" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Metode HTTP</label>
                        <select class="form-select" id="apiMethod">
                            <option value="GET">GET</option>
                            <option value="POST">POST</option>
                        </select>
                    </div>
                </div>

                <!-- Headers Configuration -->
                <div class="mt-3">
                    <label class="form-label">Headers (Opsional)</label>
                    <div id="headersContainer"></div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addHeaderBtn">
                        <i class="fas fa-plus me-1"></i>Tambah Header
                    </button>
                </div>

                <!-- Parameters Configuration -->
                <div class="mt-3">
                    <label class="form-label">Parameter API</label>
                    <div id="parametersContainer"></div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addParameterBtn">
                        <i class="fas fa-plus me-1"></i>Tambah Parameter
                    </button>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-play me-2"></i>Test API
                    </button>
                </div>
            </form>

            <div id="testResults" class="test-results" style="display: none;">
                <h5>Hasil Test API</h5>
                <div id="testResultsContent"></div>
            </div>
        </div>

        <!-- Step 2: Response Mapping -->
        <div class="creator-section" id="responseMappingSection" style="display: none;">
            <div class="section-header">
                <h3><i class="fas fa-map me-2"></i>2. Mapping Response</h3>
                <span class="badge bg-info">Pilih Data</span>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <h5>Response JSON</h5>
                    <div id="jsonDisplay" class="json-display">
                        Belum ada response. Silakan test API terlebih dahulu.
                    </div>
                </div>
                <div class="col-md-6">
                    <h5>Mapping yang Dipilih</h5>
                    <div id="selectedMappings"></div>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="clearMappingsBtn">
                        <i class="fas fa-trash me-1"></i>Bersihkan
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 3: Tool Configuration -->
        <div class="creator-section" id="toolConfigSection" style="display: none;">
            <div class="section-header">
                <h3><i class="fas fa-tools me-2"></i>3. Konfigurasi Tool</h3>
                <span class="badge bg-success">Final</span>
            </div>
            
            <form id="toolCreationForm" method="POST" action="<?php echo $is_dashboard_page ? 'dashboard.php?page=tool_creator' : 'tool_creator.php'; ?>">
                <input type="hidden" name="action" value="save_tool">
                <input type="hidden" name="api_url_for_tool" id="finalApiUrl">
                <input type="hidden" name="api_method_for_tool" id="finalApiMethod">
                <input type="hidden" name="parameters" id="finalParameters">
                <input type="hidden" name="response_mapping" id="finalResponseMapping">
                <input type="hidden" name="headers" id="finalHeaders">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Tool</label>
                        <input type="text" class="form-control" name="tool_name" id="toolName" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Slug URL</label>
                        <input type="text" class="form-control" name="tool_slug" id="toolSlug" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required>
                        <small class="text-muted">Otomatis dibuat dari nama tool</small>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Icon (Font Awesome)</label>
                        <input type="text" class="form-control" name="tool_icon" value="fas fa-tools" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="tool_status">
                            <option value="active">Aktif</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Deskripsi Tool</label>
                    <textarea class="form-control" name="tool_description" rows="3" required></textarea>
                </div>

                <div class="mt-3">
                    <label class="form-label">Template HTML Output</label>
                    <textarea class="form-control" name="html_template" id="htmlTemplate" rows="8" placeholder="Gunakan {{key}} untuk placeholder data dari API"></textarea>
                    <small class="text-muted">Contoh: &lt;h3&gt;Nama: {{name}}&lt;/h3&gt;&lt;p&gt;Email: {{email}}&lt;/p&gt;</small>
                </div>

                <div class="mt-3">
                    <h5>Preview HTML</h5>
                    <div id="htmlPreview" class="html-preview">
                        Template HTML akan ditampilkan di sini...
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-rocket me-2"></i>Buat Tool
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let apiResponse = null;
    let selectedMappings = [];
    let parameters = [];
    let headers = [];

    // DOM elements
    const apiTestForm = document.getElementById('apiTestForm');
    const parametersContainer = document.getElementById('parametersContainer');
    const headersContainer = document.getElementById('headersContainer');
    const addParameterBtn = document.getElementById('addParameterBtn');
    const addHeaderBtn = document.getElementById('addHeaderBtn');
    const testResults = document.getElementById('testResults');
    const testResultsContent = document.getElementById('testResultsContent');
    const responseMappingSection = document.getElementById('responseMappingSection');
    const toolConfigSection = document.getElementById('toolConfigSection');
    const jsonDisplay = document.getElementById('jsonDisplay');
    const selectedMappings = document.getElementById('selectedMappings');
    const htmlTemplate = document.getElementById('htmlTemplate');
    const htmlPreview = document.getElementById('htmlPreview');
    const toolName = document.getElementById('toolName');
    const toolSlug = document.getElementById('toolSlug');

    // Add parameter row
    function addParameterRow() {
        const index = parameters.length;
        const div = document.createElement('div');
        div.className = 'parameter-item';
        div.innerHTML = `
            <button type="button" class="remove-btn" onclick="removeParameter(${index})">×</button>
            <div class="row g-2">
                <div class="col-md-3">
                    <input type="text" class="form-control" placeholder="Key" data-param-key="${index}">
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" placeholder="Label" data-param-label="${index}">
                </div>
                <div class="col-md-2">
                    <select class="form-select" data-param-type="${index}">
                        <option value="text">Text</option>
                        <option value="email">Email</option>
                        <option value="url">URL</option>
                        <option value="number">Number</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" data-param-display="${index}">
                        <option value="input">Input</option>
                        <option value="textarea">Textarea</option>
                        <option value="select">Select</option>
                        <option value="hidden">Hidden</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" data-param-required="${index}">
                        <label class="form-check-label">Required</label>
                    </div>
                </div>
            </div>
            <div class="row g-2 mt-2">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Default Value" data-param-default="${index}">
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Placeholder" data-param-placeholder="${index}">
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Description" data-param-description="${index}">
                </div>
            </div>
        `;
        parametersContainer.appendChild(div);
        parameters.push({});
    }

    // Add header row
    function addHeaderRow() {
        const index = headers.length;
        const div = document.createElement('div');
        div.className = 'header-item';
        div.innerHTML = `
            <button type="button" class="remove-btn" onclick="removeHeader(${index})">×</button>
            <div class="row g-2">
                <div class="col-md-6">
                    <input type="text" class="form-control" placeholder="Header Name" data-header-key="${index}">
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" placeholder="Header Value" data-header-value="${index}">
                </div>
            </div>
        `;
        headersContainer.appendChild(div);
        headers.push({});
    }

    // Remove parameter
    window.removeParameter = function(index) {
        const items = parametersContainer.querySelectorAll('.parameter-item');
        if (items[index]) {
            items[index].remove();
            parameters.splice(index, 1);
        }
    };

    // Remove header
    window.removeHeader = function(index) {
        const items = headersContainer.querySelectorAll('.header-item');
        if (items[index]) {
            items[index].remove();
            headers.splice(index, 1);
        }
    };

    // Collect parameters data
    function collectParameters() {
        const paramData = [];
        parametersContainer.querySelectorAll('.parameter-item').forEach((item, index) => {
            const key = item.querySelector(`[data-param-key="${index}"]`).value;
            const label = item.querySelector(`[data-param-label="${index}"]`).value;
            const type = item.querySelector(`[data-param-type="${index}"]`).value;
            const display_type = item.querySelector(`[data-param-display="${index}"]`).value;
            const required = item.querySelector(`[data-param-required="${index}"]`).checked;
            const default_value = item.querySelector(`[data-param-default="${index}"]`).value;
            const placeholder = item.querySelector(`[data-param-placeholder="${index}"]`).value;
            const description = item.querySelector(`[data-param-description="${index}"]`).value;

            if (key && label) {
                paramData.push({
                    key, label, type, display_type, required,
                    default_value, placeholder, description
                });
            }
        });
        return paramData;
    }

    // Collect headers data
    function collectHeaders() {
        const headerData = [];
        headersContainer.querySelectorAll('.header-item').forEach((item, index) => {
            const key = item.querySelector(`[data-header-key="${index}"]`).value;
            const value = item.querySelector(`[data-header-value="${index}"]`).value;
            if (key && value) {
                headerData.push({ key, value });
            }
        });
        return headerData;
    }

    // Render JSON with clickable keys
    function renderJsonClickable(obj, path = '') {
        if (typeof obj !== 'object' || obj === null) {
            let className = 'json-string';
            if (typeof obj === 'number') className = 'json-number';
            else if (typeof obj === 'boolean') className = 'json-boolean';
            else if (obj === null) className = 'json-null';
            
            return `<span class="${className}">${JSON.stringify(obj)}</span>`;
        }

        if (Array.isArray(obj)) {
            let html = '[\n';
            obj.forEach((item, index) => {
                const itemPath = path ? `${path}[${index}]` : `[${index}]`;
                html += `  ${renderJsonClickable(item, itemPath)}`;
                if (index < obj.length - 1) html += ',';
                html += '\n';
            });
            html += ']';
            return html;
        }

        let html = '{\n';
        const keys = Object.keys(obj);
        keys.forEach((key, index) => {
            const keyPath = path ? `${path}.${key}` : key;
            const isSelected = selectedMappings.some(m => m.path === keyPath);
            const selectedClass = isSelected ? 'selected' : '';
            
            html += `  <span class="json-key ${selectedClass}" data-path="${keyPath}" onclick="toggleMapping('${keyPath}', '${key}')">"${key}"</span>: `;
            html += renderJsonClickable(obj[key], keyPath);
            if (index < keys.length - 1) html += ',';
            html += '\n';
        });
        html += '}';
        return html;
    }

    // Toggle mapping selection
    window.toggleMapping = function(path, key) {
        const existingIndex = selectedMappings.findIndex(m => m.path === path);
        
        if (existingIndex >= 0) {
            selectedMappings.splice(existingIndex, 1);
        } else {
            selectedMappings.push({ path, key });
        }
        
        updateMappingsDisplay();
        updateJsonDisplay();
        updateHtmlPreview();
    };

    // Update mappings display
    function updateMappingsDisplay() {
        if (selectedMappings.length === 0) {
            selectedMappings.innerHTML = '<p class="text-muted">Belum ada mapping yang dipilih. Klik pada key di JSON response.</p>';
            return;
        }

        let html = '';
        selectedMappings.forEach((mapping, index) => {
            html += `
                <div class="mapping-item">
                    <button type="button" class="remove-btn" onclick="removeMapping(${index})">×</button>
                    <strong>Key:</strong> ${mapping.key}<br>
                    <strong>Path:</strong> ${mapping.path}<br>
                    <strong>Placeholder:</strong> {{${mapping.key}}}
                </div>
            `;
        });
        selectedMappings.innerHTML = html;
    }

    // Remove mapping
    window.removeMapping = function(index) {
        selectedMappings.splice(index, 1);
        updateMappingsDisplay();
        updateJsonDisplay();
        updateHtmlPreview();
    };

    // Update JSON display
    function updateJsonDisplay() {
        if (apiResponse) {
            jsonDisplay.innerHTML = renderJsonClickable(apiResponse);
        }
    }

    // Update HTML preview
    function updateHtmlPreview() {
        let template = htmlTemplate.value;
        if (!template) {
            htmlPreview.innerHTML = 'Template HTML akan ditampilkan di sini...';
            return;
        }

        // Replace placeholders with sample data
        selectedMappings.forEach(mapping => {
            const placeholder = `{{${mapping.key}}}`;
            const sampleValue = `[Sample ${mapping.key}]`;
            template = template.replace(new RegExp(placeholder.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), sampleValue);
        });

        htmlPreview.innerHTML = template;
    }

    // Auto-generate slug from tool name
    toolName.addEventListener('input', function() {
        const slug = this.value
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s-]+/g, '-')
            .replace(/^-+|-+$/g, '');
        toolSlug.value = slug;
    });

    // HTML template change handler
    htmlTemplate.addEventListener('input', updateHtmlPreview);

    // Event listeners
    addParameterBtn.addEventListener('click', addParameterRow);
    addHeaderBtn.addEventListener('click', addHeaderRow);

    // Clear mappings
    document.getElementById('clearMappingsBtn').addEventListener('click', function() {
        selectedMappings = [];
        updateMappingsDisplay();
        updateJsonDisplay();
        updateHtmlPreview();
    });

    // API Test Form
    apiTestForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const apiUrl = document.getElementById('apiUrl').value;
        const apiMethod = document.getElementById('apiMethod').value;
        const paramData = collectParameters();
        const headerData = collectHeaders();

        testResults.style.display = 'block';
        testResultsContent.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Testing API...';

        const formData = new FormData();
        formData.append('action', 'test_api');
        formData.append('api_url', apiUrl);
        formData.append('method', apiMethod);
        
        paramData.forEach((param, index) => {
            formData.append(`params[${index}][key]`, param.key);
            formData.append(`params[${index}][value]`, param.default_value || 'test');
        });

        headerData.forEach((header, index) => {
            formData.append(`headers[${index}][key]`, header.key);
            formData.append(`headers[${index}][value]`, header.value);
        });

        try {
            const response = await fetch('<?php echo $is_dashboard_page ? "dashboard.php?page=tool_creator" : "tool_creator.php"; ?>', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                apiResponse = result.data;
                
                testResultsContent.innerHTML = `
                    <div class="success-indicator">✓ API Test Berhasil</div>
                    <div class="response-stats">
                        <span class="badge bg-success">HTTP ${result.http_code}</span>
                        <span class="badge bg-info">${result.response_size} bytes</span>
                        <span class="badge bg-primary">${result.is_json ? 'JSON' : 'Text'}</span>
                    </div>
                `;

                if (result.is_json) {
                    responseMappingSection.style.display = 'block';
                    toolConfigSection.style.display = 'block';
                    updateJsonDisplay();
                    
                    // Set final form values
                    document.getElementById('finalApiUrl').value = apiUrl;
                    document.getElementById('finalApiMethod').value = apiMethod;
                    document.getElementById('finalParameters').value = JSON.stringify(paramData);
                    document.getElementById('finalHeaders').value = JSON.stringify(headerData);
                }
            } else {
                testResultsContent.innerHTML = `
                    <div class="error-indicator">✗ API Test Gagal</div>
                    <div class="alert alert-danger mt-2">${result.message}</div>
                `;
            }
        } catch (error) {
            testResultsContent.innerHTML = `
                <div class="error-indicator">✗ Error</div>
                <div class="alert alert-danger mt-2">${error.message}</div>
            `;
        }
    });

    // Tool Creation Form
    document.getElementById('toolCreationForm').addEventListener('submit', function(e) {
        document.getElementById('finalResponseMapping').value = JSON.stringify(selectedMappings);
    });

    // Initialize with one parameter row
    addParameterRow();
});
</script>

<?php 
// Hanya include footer jika bukan dashboard page
if (!$is_dashboard_page) {
    include $path_prefix . 'footer.php'; 
}
?>