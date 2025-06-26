<?php
// Cek apakah ini dipanggil dari dashboard atau diakses langsung
$is_dashboard_page = isset($_GET['page']) && $_GET['page'] === 'advanced_tool_manager';

if (!$is_dashboard_page) {
    $page_title = "Advanced Tool Management System";
    $path_prefix = '../'; 
    require_once 'auth.php'; 
    include $path_prefix . 'header.php';
} else {
    $page_title = "Advanced Tool Management System";
    $path_prefix = '../'; 
}

// Include required files
require_once 'includes/tool_functions.php';
require_once 'includes/tool_template_content.php';

$tools_file = $path_prefix . 'tools.json';
$tool_configs_file = $path_prefix . 'tool_configurations.json';

// Advanced tool configuration functions
function get_tool_configurations() {
    global $tool_configs_file;
    if (file_exists($tool_configs_file)) {
        $json_data = file_get_contents($tool_configs_file);
        $configs = json_decode($json_data, true);
        return is_array($configs) ? $configs : [];
    }
    return [];
}

function save_tool_configuration($config) {
    global $tool_configs_file;
    $configs = get_tool_configurations();
    $configs[$config['id']] = $config;
    
    $json_data = json_encode($configs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($tool_configs_file, $json_data, LOCK_EX) === false) {
        return ['status' => 'error', 'message' => 'Failed to save tool configuration.'];
    }
    return ['status' => 'success'];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'test_advanced_api') {
        header('Content-Type: application/json');
        
        $config = json_decode($_POST['config'], true);
        if (!$config) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid configuration data.']);
            exit;
        }
        
        // Perform advanced API test
        $result = test_advanced_api_endpoint($config);
        echo json_encode($result);
        exit;
    }
    
    if ($action === 'save_advanced_tool') {
        header('Content-Type: application/json');
        
        $config = json_decode($_POST['config'], true);
        if (!$config) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid configuration data.']);
            exit;
        }
        
        // Validate and save configuration
        $result = save_tool_configuration($config);
        echo json_encode($result);
        exit;
    }
    
    if ($action === 'validate_parameters') {
        header('Content-Type: application/json');
        
        $parameters = json_decode($_POST['parameters'], true);
        $result = validate_tool_parameters($parameters);
        echo json_encode($result);
        exit;
    }
}

function test_advanced_api_endpoint($config) {
    $ch = curl_init();
    
    // Set basic cURL options
    curl_setopt_array($ch, [
        CURLOPT_URL => $config['endpoint_url'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $config['timeout'] ?? 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => $config['ssl_verify'] ?? true,
        CURLOPT_USERAGENT => $config['user_agent'] ?? 'AdvancedToolManager/1.0'
    ]);
    
    // Set HTTP method
    switch (strtoupper($config['http_method'])) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($config['request_body'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $config['request_body']);
            }
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if (!empty($config['request_body'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $config['request_body']);
            }
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
        default: // GET
            if (!empty($config['query_params'])) {
                $url_parts = parse_url($config['endpoint_url']);
                $query = http_build_query($config['query_params']);
                $config['endpoint_url'] .= (isset($url_parts['query']) ? '&' : '?') . $query;
                curl_setopt($ch, CURLOPT_URL, $config['endpoint_url']);
            }
    }
    
    // Set headers
    $headers = [];
    if (!empty($config['headers'])) {
        foreach ($config['headers'] as $header) {
            if (!empty($header['key']) && !empty($header['value'])) {
                $headers[] = $header['key'] . ': ' . $header['value'];
            }
        }
    }
    
    // Set authentication
    if (!empty($config['auth_type'])) {
        switch ($config['auth_type']) {
            case 'api_key':
                if (!empty($config['auth_config']['api_key'])) {
                    $headers[] = ($config['auth_config']['api_key_header'] ?? 'X-API-Key') . ': ' . $config['auth_config']['api_key'];
                }
                break;
            case 'bearer':
                if (!empty($config['auth_config']['bearer_token'])) {
                    $headers[] = 'Authorization: Bearer ' . $config['auth_config']['bearer_token'];
                }
                break;
            case 'basic':
                if (!empty($config['auth_config']['username']) && !empty($config['auth_config']['password'])) {
                    curl_setopt($ch, CURLOPT_USERPWD, $config['auth_config']['username'] . ':' . $config['auth_config']['password']);
                }
                break;
        }
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $response_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
    curl_close($ch);
    
    if ($curl_error) {
        return [
            'status' => 'error',
            'message' => 'cURL Error: ' . $curl_error,
            'response_time' => $response_time
        ];
    }
    
    $decoded_response = json_decode($response, true);
    return [
        'status' => 'success',
        'http_code' => $http_code,
        'response_time' => $response_time,
        'raw_response' => $response,
        'parsed_response' => $decoded_response,
        'is_json' => json_last_error() === JSON_ERROR_NONE
    ];
}

function validate_tool_parameters($parameters) {
    $errors = [];
    
    foreach ($parameters as $param) {
        if (empty($param['name'])) {
            $errors[] = 'Parameter name is required';
            continue;
        }
        
        if (!empty($param['validation_rules'])) {
            foreach ($param['validation_rules'] as $rule) {
                switch ($rule['type']) {
                    case 'regex':
                        if (!empty($param['test_value']) && !preg_match($rule['pattern'], $param['test_value'])) {
                            $errors[] = "Parameter '{$param['name']}' failed regex validation";
                        }
                        break;
                    case 'length':
                        if (!empty($param['test_value'])) {
                            $len = strlen($param['test_value']);
                            if (isset($rule['min']) && $len < $rule['min']) {
                                $errors[] = "Parameter '{$param['name']}' is too short";
                            }
                            if (isset($rule['max']) && $len > $rule['max']) {
                                $errors[] = "Parameter '{$param['name']}' is too long";
                            }
                        }
                        break;
                }
            }
        }
    }
    
    return [
        'status' => empty($errors) ? 'success' : 'error',
        'errors' => $errors
    ];
}
?>

<style>
.advanced-tool-manager {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 2rem 0;
}

.manager-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: none;
    margin-bottom: 2rem;
}

.manager-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 1rem 1rem 0 0;
    text-align: center;
}

.manager-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.manager-header p {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

.config-section {
    padding: 2rem;
    border-bottom: 1px solid #e9ecef;
}

.config-section:last-child {
    border-bottom: none;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-title i {
    color: #667eea;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border-radius: 0.5rem;
    border: 1px solid #ced4da;
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
    border-radius: 0.5rem;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.parameter-row {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid #e9ecef;
}

.validation-rule {
    background: #e3f2fd;
    border-radius: 0.25rem;
    padding: 0.5rem;
    margin-top: 0.5rem;
    border-left: 3px solid #2196f3;
}

.test-results {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-top: 1rem;
    border: 1px solid #e9ecef;
}

.response-preview {
    background: #212529;
    color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    max-height: 400px;
    overflow-y: auto;
    white-space: pre-wrap;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-success {
    background: #d4edda;
    color: #155724;
}

.status-error {
    background: #f8d7da;
    color: #721c24;
}

.accordion-button {
    background: #f8f9fa;
    border: none;
    font-weight: 600;
}

.accordion-button:not(.collapsed) {
    background: #667eea;
    color: white;
}

.tab-content {
    padding: 2rem 0;
}

.nav-tabs .nav-link {
    border: none;
    border-radius: 0.5rem 0.5rem 0 0;
    margin-right: 0.25rem;
    color: #6c757d;
    font-weight: 500;
}

.nav-tabs .nav-link.active {
    background: #667eea;
    color: white;
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
    z-index: 1000;
}

.metric-card {
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid #e9ecef;
}

.metric-value {
    font-size: 2rem;
    font-weight: 700;
    color: #667eea;
}

.metric-label {
    color: #6c757d;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

@media (max-width: 768px) {
    .manager-header h1 {
        font-size: 2rem;
    }
    
    .config-section {
        padding: 1rem;
    }
    
    .parameter-row {
        padding: 0.75rem;
    }
}
</style>

<div class="advanced-tool-manager">
    <div class="container">
        <div class="manager-card">
            <div class="manager-header">
                <h1><i class="fas fa-cogs me-3"></i><?php echo htmlspecialchars($page_title); ?></h1>
                <p>Enterprise-grade tool integration with advanced API management capabilities</p>
            </div>

            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs" id="managerTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="config-tab" data-bs-toggle="tab" data-bs-target="#config" type="button" role="tab">
                        <i class="fas fa-sliders-h me-2"></i>Configuration
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="testing-tab" data-bs-toggle="tab" data-bs-target="#testing" type="button" role="tab">
                        <i class="fas fa-flask me-2"></i>API Testing
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="monitoring-tab" data-bs-toggle="tab" data-bs-target="#monitoring" type="button" role="tab">
                        <i class="fas fa-chart-line me-2"></i>Monitoring
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="deployment-tab" data-bs-toggle="tab" data-bs-target="#deployment" type="button" role="tab">
                        <i class="fas fa-rocket me-2"></i>Deployment
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="managerTabContent">
                <!-- Configuration Tab -->
                <div class="tab-pane fade show active" id="config" role="tabpanel">
                    <form id="advancedToolForm">
                        <!-- Basic Configuration -->
                        <div class="config-section">
                            <h3 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Basic Configuration
                            </h3>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Tool Name</label>
                                        <input type="text" class="form-control" id="toolName" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Tool Slug</label>
                                        <input type="text" class="form-control" id="toolSlug" pattern="[a-z0-9-]+" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" id="toolDescription" rows="3" required></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">Category</label>
                                        <select class="form-select" id="toolCategory">
                                            <option value="utility">Utility</option>
                                            <option value="converter">Converter</option>
                                            <option value="generator">Generator</option>
                                            <option value="analyzer">Analyzer</option>
                                            <option value="api">API Tool</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">Icon (Font Awesome)</label>
                                        <input type="text" class="form-control" id="toolIcon" value="fas fa-tools">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" id="toolStatus">
                                            <option value="active">Active</option>
                                            <option value="maintenance">Maintenance</option>
                                            <option value="beta">Beta</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- API Configuration -->
                        <div class="config-section">
                            <h3 class="section-title">
                                <i class="fas fa-plug"></i>
                                API Configuration
                            </h3>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label">Endpoint URL</label>
                                        <input type="url" class="form-control" id="endpointUrl" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">HTTP Method</label>
                                        <select class="form-select" id="httpMethod">
                                            <option value="GET">GET</option>
                                            <option value="POST">POST</option>
                                            <option value="PUT">PUT</option>
                                            <option value="DELETE">DELETE</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">Timeout (seconds)</label>
                                        <input type="number" class="form-control" id="timeout" value="30" min="1" max="300">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">Rate Limit (requests/minute)</label>
                                        <input type="number" class="form-control" id="rateLimit" value="60" min="1">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">Cache TTL (seconds)</label>
                                        <input type="number" class="form-control" id="cacheTtl" value="300" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Authentication -->
                        <div class="config-section">
                            <h3 class="section-title">
                                <i class="fas fa-shield-alt"></i>
                                Authentication
                            </h3>
                            
                            <div class="form-group">
                                <label class="form-label">Authentication Type</label>
                                <select class="form-select" id="authType">
                                    <option value="">None</option>
                                    <option value="api_key">API Key</option>
                                    <option value="bearer">Bearer Token</option>
                                    <option value="basic">Basic Auth</option>
                                    <option value="oauth2">OAuth 2.0</option>
                                </select>
                            </div>
                            
                            <div id="authConfig" style="display: none;">
                                <!-- Dynamic auth configuration will be inserted here -->
                            </div>
                        </div>

                        <!-- Headers Configuration -->
                        <div class="config-section">
                            <h3 class="section-title">
                                <i class="fas fa-list"></i>
                                Request Headers
                            </h3>
                            
                            <div id="headersContainer">
                                <!-- Dynamic headers will be added here -->
                            </div>
                            
                            <button type="button" class="btn btn-outline-primary" id="addHeaderBtn">
                                <i class="fas fa-plus me-2"></i>Add Header
                            </button>
                        </div>

                        <!-- Parameters Configuration -->
                        <div class="config-section">
                            <h3 class="section-title">
                                <i class="fas fa-cog"></i>
                                Parameters Configuration
                            </h3>
                            
                            <div id="parametersContainer">
                                <!-- Dynamic parameters will be added here -->
                            </div>
                            
                            <button type="button" class="btn btn-outline-primary" id="addParameterBtn">
                                <i class="fas fa-plus me-2"></i>Add Parameter
                            </button>
                        </div>

                        <!-- Response Mapping -->
                        <div class="config-section">
                            <h3 class="section-title">
                                <i class="fas fa-map"></i>
                                Response Mapping
                            </h3>
                            
                            <div class="form-group">
                                <label class="form-label">Response Schema (JSON)</label>
                                <textarea class="form-control" id="responseSchema" rows="8" placeholder='{"data": {"result": "string", "status": "string"}}'></textarea>
                                <small class="form-text text-muted">Define the expected response structure</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Success Response Path</label>
                                <input type="text" class="form-control" id="successPath" placeholder="data.result">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Error Response Path</label>
                                <input type="text" class="form-control" id="errorPath" placeholder="error.message">
                            </div>
                        </div>

                        <!-- Error Handling -->
                        <div class="config-section">
                            <h3 class="section-title">
                                <i class="fas fa-exclamation-triangle"></i>
                                Error Handling & Fallbacks
                            </h3>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Retry Attempts</label>
                                        <input type="number" class="form-control" id="retryAttempts" value="3" min="0" max="10">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Retry Delay (seconds)</label>
                                        <input type="number" class="form-control" id="retryDelay" value="1" min="0" max="60">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Fallback Response</label>
                                <textarea class="form-control" id="fallbackResponse" rows="4" placeholder='{"error": "Service temporarily unavailable"}'></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Error Message Template</label>
                                <input type="text" class="form-control" id="errorTemplate" value="An error occurred: {{error}}">
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Testing Tab -->
                <div class="tab-pane fade" id="testing" role="tabpanel">
                    <div class="config-section">
                        <h3 class="section-title">
                            <i class="fas fa-flask"></i>
                            API Testing & Validation
                        </h3>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-primary w-100" id="testConnectionBtn">
                                    <i class="fas fa-play me-2"></i>Test API Connection
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-outline-primary w-100" id="validateParametersBtn">
                                    <i class="fas fa-check me-2"></i>Validate Parameters
                                </button>
                            </div>
                        </div>
                        
                        <div id="testResults" class="test-results" style="display: none;">
                            <h5>Test Results</h5>
                            <div id="testMetrics" class="row mb-3">
                                <!-- Metrics will be inserted here -->
                            </div>
                            <div id="testResponse" class="response-preview">
                                <!-- Response will be shown here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monitoring Tab -->
                <div class="tab-pane fade" id="monitoring" role="tabpanel">
                    <div class="config-section">
                        <h3 class="section-title">
                            <i class="fas fa-chart-line"></i>
                            Usage Monitoring & Analytics
                        </h3>
                        
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="metric-card">
                                    <div class="metric-value" id="totalRequests">0</div>
                                    <div class="metric-label">Total Requests</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="metric-card">
                                    <div class="metric-value" id="successRate">0%</div>
                                    <div class="metric-label">Success Rate</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="metric-card">
                                    <div class="metric-value" id="avgResponseTime">0ms</div>
                                    <div class="metric-label">Avg Response Time</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="metric-card">
                                    <div class="metric-value" id="errorCount">0</div>
                                    <div class="metric-label">Errors (24h)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Monitoring Configuration</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enableLogging" checked>
                                <label class="form-check-label" for="enableLogging">
                                    Enable request/response logging
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enableMetrics" checked>
                                <label class="form-check-label" for="enableMetrics">
                                    Enable performance metrics
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enableAlerts">
                                <label class="form-check-label" for="enableAlerts">
                                    Enable error rate alerts
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Deployment Tab -->
                <div class="tab-pane fade" id="deployment" role="tabpanel">
                    <div class="config-section">
                        <h3 class="section-title">
                            <i class="fas fa-rocket"></i>
                            Tool Deployment & Version Control
                        </h3>
                        
                        <div class="form-group">
                            <label class="form-label">Version</label>
                            <input type="text" class="form-control" id="toolVersion" value="1.0.0" pattern="\d+\.\d+\.\d+">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Deployment Environment</label>
                            <select class="form-select" id="deploymentEnv">
                                <option value="development">Development</option>
                                <option value="staging">Staging</option>
                                <option value="production">Production</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Release Notes</label>
                            <textarea class="form-control" id="releaseNotes" rows="4" placeholder="Describe the changes in this version..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-success w-100" id="deployToolBtn">
                                    <i class="fas fa-rocket me-2"></i>Deploy Tool
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-outline-secondary w-100" id="saveConfigBtn">
                                    <i class="fas fa-save me-2"></i>Save Configuration
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the advanced tool manager
    const manager = new AdvancedToolManager();
    manager.init();
});

class AdvancedToolManager {
    constructor() {
        this.config = {
            basic: {},
            api: {},
            auth: {},
            headers: [],
            parameters: [],
            response: {},
            errorHandling: {},
            monitoring: {},
            deployment: {}
        };
        
        this.headerCounter = 0;
        this.parameterCounter = 0;
    }
    
    init() {
        this.bindEvents();
        this.initializeForm();
    }
    
    bindEvents() {
        // Basic configuration
        document.getElementById('toolName').addEventListener('input', (e) => {
            document.getElementById('toolSlug').value = this.createSlug(e.target.value);
        });
        
        // Authentication type change
        document.getElementById('authType').addEventListener('change', (e) => {
            this.updateAuthConfig(e.target.value);
        });
        
        // Add header button
        document.getElementById('addHeaderBtn').addEventListener('click', () => {
            this.addHeader();
        });
        
        // Add parameter button
        document.getElementById('addParameterBtn').addEventListener('click', () => {
            this.addParameter();
        });
        
        // Test connection button
        document.getElementById('testConnectionBtn').addEventListener('click', () => {
            this.testApiConnection();
        });
        
        // Validate parameters button
        document.getElementById('validateParametersBtn').addEventListener('click', () => {
            this.validateParameters();
        });
        
        // Deploy tool button
        document.getElementById('deployToolBtn').addEventListener('click', () => {
            this.deployTool();
        });
        
        // Save configuration button
        document.getElementById('saveConfigBtn').addEventListener('click', () => {
            this.saveConfiguration();
        });
    }
    
    initializeForm() {
        // Add initial header and parameter
        this.addHeader();
        this.addParameter();
    }
    
    createSlug(str) {
        return str.toLowerCase()
                  .replace(/[^a-z0-9\s-]/g, '')
                  .replace(/[\s-]+/g, '-')
                  .replace(/^-+|-+$/g, '');
    }
    
    updateAuthConfig(authType) {
        const authConfig = document.getElementById('authConfig');
        authConfig.innerHTML = '';
        
        if (!authType) {
            authConfig.style.display = 'none';
            return;
        }
        
        authConfig.style.display = 'block';
        
        switch (authType) {
            case 'api_key':
                authConfig.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">API Key</label>
                                <input type="password" class="form-control" id="apiKey" placeholder="Enter API key">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Header Name</label>
                                <input type="text" class="form-control" id="apiKeyHeader" value="X-API-Key">
                            </div>
                        </div>
                    </div>
                `;
                break;
                
            case 'bearer':
                authConfig.innerHTML = `
                    <div class="form-group">
                        <label class="form-label">Bearer Token</label>
                        <input type="password" class="form-control" id="bearerToken" placeholder="Enter bearer token">
                    </div>
                `;
                break;
                
            case 'basic':
                authConfig.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" id="basicUsername">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" id="basicPassword">
                            </div>
                        </div>
                    </div>
                `;
                break;
                
            case 'oauth2':
                authConfig.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Client ID</label>
                                <input type="text" class="form-control" id="clientId">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Client Secret</label>
                                <input type="password" class="form-control" id="clientSecret">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Token URL</label>
                        <input type="url" class="form-control" id="tokenUrl">
                    </div>
                `;
                break;
        }
    }
    
    addHeader(key = '', value = '') {
        const container = document.getElementById('headersContainer');
        const headerDiv = document.createElement('div');
        headerDiv.className = 'parameter-row';
        headerDiv.innerHTML = `
            <div class="row align-items-center">
                <div class="col-md-5">
                    <input type="text" class="form-control" placeholder="Header name" value="${key}">
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control" placeholder="Header value" value="${value}">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.parameter-row').remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(headerDiv);
        this.headerCounter++;
    }
    
    addParameter(name = '', type = 'string', required = false, defaultValue = '') {
        const container = document.getElementById('parametersContainer');
        const paramDiv = document.createElement('div');
        paramDiv.className = 'parameter-row';
        paramDiv.innerHTML = `
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Parameter Name</label>
                    <input type="text" class="form-control" placeholder="Parameter name" value="${name}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select class="form-select">
                        <option value="string" ${type === 'string' ? 'selected' : ''}>String</option>
                        <option value="number" ${type === 'number' ? 'selected' : ''}>Number</option>
                        <option value="boolean" ${type === 'boolean' ? 'selected' : ''}>Boolean</option>
                        <option value="array" ${type === 'array' ? 'selected' : ''}>Array</option>
                        <option value="object" ${type === 'object' ? 'selected' : ''}>Object</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Required</label>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" ${required ? 'checked' : ''}>
                        <label class="form-check-label">Required</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Default Value</label>
                    <input type="text" class="form-control" placeholder="Default value" value="${defaultValue}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Actions</label>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="this.closest('.parameter-row').querySelector('.validation-rules').style.display = this.closest('.parameter-row').querySelector('.validation-rules').style.display === 'none' ? 'block' : 'none'">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.parameter-row').remove()">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="validation-rules" style="display: none;">
                <h6 class="mt-3">Validation Rules</h6>
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Min Length</label>
                        <input type="number" class="form-control" placeholder="Min length">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Max Length</label>
                        <input type="number" class="form-control" placeholder="Max length">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Regex Pattern</label>
                        <input type="text" class="form-control" placeholder="^[a-zA-Z0-9]+$">
                    </div>
                </div>
            </div>
        `;
        container.appendChild(paramDiv);
        this.parameterCounter++;
    }
    
    async testApiConnection() {
        const config = this.gatherConfiguration();
        const testBtn = document.getElementById('testConnectionBtn');
        const originalText = testBtn.innerHTML;
        
        testBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';
        testBtn.disabled = true;
        
        try {
            const response = await fetch('<?php echo $is_dashboard_page ? "dashboard.php?page=advanced_tool_manager" : "advanced_tool_manager.php"; ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'test_advanced_api',
                    config: JSON.stringify(config)
                })
            });
            
            const result = await response.json();
            this.displayTestResults(result);
            
        } catch (error) {
            this.displayTestResults({
                status: 'error',
                message: 'Network error: ' + error.message
            });
        } finally {
            testBtn.innerHTML = originalText;
            testBtn.disabled = false;
        }
    }
    
    displayTestResults(result) {
        const resultsDiv = document.getElementById('testResults');
        const metricsDiv = document.getElementById('testMetrics');
        const responseDiv = document.getElementById('testResponse');
        
        resultsDiv.style.display = 'block';
        
        // Display metrics
        if (result.status === 'success') {
            metricsDiv.innerHTML = `
                <div class="col-md-3">
                    <div class="metric-card">
                        <div class="metric-value status-success">${result.http_code}</div>
                        <div class="metric-label">HTTP Status</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card">
                        <div class="metric-value">${(result.response_time * 1000).toFixed(0)}ms</div>
                        <div class="metric-label">Response Time</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card">
                        <div class="metric-value">${result.is_json ? 'JSON' : 'Text'}</div>
                        <div class="metric-label">Response Type</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card">
                        <div class="metric-value status-success">Success</div>
                        <div class="metric-label">Status</div>
                    </div>
                </div>
            `;
            
            responseDiv.textContent = result.is_json ? 
                JSON.stringify(result.parsed_response, null, 2) : 
                result.raw_response;
        } else {
            metricsDiv.innerHTML = `
                <div class="col-md-12">
                    <div class="metric-card">
                        <div class="metric-value status-error">Error</div>
                        <div class="metric-label">${result.message}</div>
                    </div>
                </div>
            `;
            responseDiv.textContent = result.message;
        }
    }
    
    async validateParameters() {
        const parameters = this.gatherParameters();
        
        try {
            const response = await fetch('<?php echo $is_dashboard_page ? "dashboard.php?page=advanced_tool_manager" : "advanced_tool_manager.php"; ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'validate_parameters',
                    parameters: JSON.stringify(parameters)
                })
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                alert('All parameters are valid!');
            } else {
                alert('Validation errors:\n' + result.errors.join('\n'));
            }
            
        } catch (error) {
            alert('Validation error: ' + error.message);
        }
    }
    
    gatherConfiguration() {
        return {
            endpoint_url: document.getElementById('endpointUrl').value,
            http_method: document.getElementById('httpMethod').value,
            timeout: parseInt(document.getElementById('timeout').value),
            auth_type: document.getElementById('authType').value,
            auth_config: this.gatherAuthConfig(),
            headers: this.gatherHeaders(),
            query_params: this.gatherQueryParams(),
            request_body: this.gatherRequestBody()
        };
    }
    
    gatherAuthConfig() {
        const authType = document.getElementById('authType').value;
        const config = {};
        
        switch (authType) {
            case 'api_key':
                config.api_key = document.getElementById('apiKey')?.value;
                config.api_key_header = document.getElementById('apiKeyHeader')?.value;
                break;
            case 'bearer':
                config.bearer_token = document.getElementById('bearerToken')?.value;
                break;
            case 'basic':
                config.username = document.getElementById('basicUsername')?.value;
                config.password = document.getElementById('basicPassword')?.value;
                break;
        }
        
        return config;
    }
    
    gatherHeaders() {
        const headers = [];
        const headerRows = document.querySelectorAll('#headersContainer .parameter-row');
        
        headerRows.forEach(row => {
            const inputs = row.querySelectorAll('input');
            if (inputs[0].value && inputs[1].value) {
                headers.push({
                    key: inputs[0].value,
                    value: inputs[1].value
                });
            }
        });
        
        return headers;
    }
    
    gatherParameters() {
        const parameters = [];
        const paramRows = document.querySelectorAll('#parametersContainer .parameter-row');
        
        paramRows.forEach(row => {
            const inputs = row.querySelectorAll('input');
            const select = row.querySelector('select');
            const checkbox = row.querySelector('input[type="checkbox"]');
            
            if (inputs[0].value) {
                parameters.push({
                    name: inputs[0].value,
                    type: select.value,
                    required: checkbox.checked,
                    default_value: inputs[1].value,
                    test_value: inputs[1].value
                });
            }
        });
        
        return parameters;
    }
    
    gatherQueryParams() {
        // For testing purposes, gather some sample query params
        return {};
    }
    
    gatherRequestBody() {
        // For testing purposes, return empty body
        return '';
    }
    
    async deployTool() {
        const config = this.gatherFullConfiguration();
        
        try {
            const response = await fetch('<?php echo $is_dashboard_page ? "dashboard.php?page=advanced_tool_manager" : "advanced_tool_manager.php"; ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'save_advanced_tool',
                    config: JSON.stringify(config)
                })
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                alert('Tool deployed successfully!');
            } else {
                alert('Deployment failed: ' + result.message);
            }
            
        } catch (error) {
            alert('Deployment error: ' + error.message);
        }
    }
    
    async saveConfiguration() {
        const config = this.gatherFullConfiguration();
        
        // Save to localStorage as backup
        localStorage.setItem('advancedToolConfig', JSON.stringify(config));
        alert('Configuration saved locally!');
    }
    
    gatherFullConfiguration() {
        return {
            id: Date.now().toString(),
            basic: {
                name: document.getElementById('toolName').value,
                slug: document.getElementById('toolSlug').value,
                description: document.getElementById('toolDescription').value,
                category: document.getElementById('toolCategory').value,
                icon: document.getElementById('toolIcon').value,
                status: document.getElementById('toolStatus').value
            },
            api: {
                endpoint_url: document.getElementById('endpointUrl').value,
                http_method: document.getElementById('httpMethod').value,
                timeout: parseInt(document.getElementById('timeout').value),
                rate_limit: parseInt(document.getElementById('rateLimit').value),
                cache_ttl: parseInt(document.getElementById('cacheTtl').value)
            },
            auth: {
                type: document.getElementById('authType').value,
                config: this.gatherAuthConfig()
            },
            headers: this.gatherHeaders(),
            parameters: this.gatherParameters(),
            response: {
                schema: document.getElementById('responseSchema').value,
                success_path: document.getElementById('successPath').value,
                error_path: document.getElementById('errorPath').value
            },
            error_handling: {
                retry_attempts: parseInt(document.getElementById('retryAttempts').value),
                retry_delay: parseInt(document.getElementById('retryDelay').value),
                fallback_response: document.getElementById('fallbackResponse').value,
                error_template: document.getElementById('errorTemplate').value
            },
            monitoring: {
                enable_logging: document.getElementById('enableLogging').checked,
                enable_metrics: document.getElementById('enableMetrics').checked,
                enable_alerts: document.getElementById('enableAlerts').checked
            },
            deployment: {
                version: document.getElementById('toolVersion').value,
                environment: document.getElementById('deploymentEnv').value,
                release_notes: document.getElementById('releaseNotes').value,
                created_at: new Date().toISOString()
            }
        };
    }
}
</script>

<?php 
// Hanya include footer jika bukan dashboard page
if (!$is_dashboard_page) {
    include $path_prefix . 'footer.php'; 
}
?>