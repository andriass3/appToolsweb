<?php
// andrias/includes/tool_template_content.php
// FILE INI BERISI TEMPLATE HTML DASAR UNTUK TOOL BARU YANG DIBUAT OLEH TOOL CREATOR.
// JANGAN MENGEDIT LANGSUNG DI SINI KECUALI ANDA INGIN MENGUBAH SEMUA TEMPLATE TOOL BARU.

// Template untuk file frontend tool (misal: tools/slug/index.php)
$tool_frontend_template = <<<'EOT'
<?php 
// Ini adalah template dasar untuk tool baru Anda.
// Sesuaikan variabel di bawah ini berdasarkan konfigurasi yang Anda simpan.
$page_title = "{{page_title}}";
$tool_icon = "{{tool_icon}}";
$tool_description = "{{tool_description}}";
$primary_input_param_key = "{{primary_input_param_key}}"; 
// Template HTML output disisipkan sebagai string JSON yang akan di-decode oleh JavaScript
$generated_output_html_template_js = '{{generated_output_html_js_escaped}}'; 

$path_prefix = '../../'; 

$api_result_display_html = '';
$error_message = null;

// Sertakan file header dari root direktori
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
    }
    .result-display {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        padding: 1rem;
        min-height: 80px;
        word-break: break-all;
        font-family: monospace;
    }
    /* Styles for contenteditable div */
    .contenteditable-div {
        border: 1px solid #ced4da;
        padding: 10px;
        min-height: 100px;
        background-color: #fff;
        cursor: text;
        font-family: Arial, sans-serif; /* Default font for editable text */
    }
    .contenteditable-div:focus {
        outline: none;
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    .loading-spinner {
        display: none; 
        text-align: center;
        margin-top: 1rem;
    }
</style>

<div class='tool-page-container container my-5'>
    <div class='text-center mb-4'>
        <h1 class='display-5 fw-bold'><i class='<?php echo htmlspecialchars($tool_icon); ?> me-2'></i><?php echo $page_title; ?></h1>
        <p class='lead text-muted'><?php echo $tool_description; ?></p>
    </div>

    <div class='card shadow-sm p-4'>
        <h4 class='mb-3'>Uji API Tool Ini:</h4>
        <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        <form id='toolApiTestForm'>
            <input type='hidden' name='action' value='run_tool_api_test'>
            <div class='mb-3'>
                <label for='mainInput' class='form-label'>Masukkan <?php echo !empty($primary_input_param_key) ? htmlspecialchars(ucfirst($primary_input_param_key)) : 'Input'; ?>:</label>
                <!-- Nama input disesuaikan dengan primary_input_param_key -->
                <input type='text' class='form-control' id='mainInput' name='<?php echo !empty($primary_input_param_key) ? htmlspecialchars($primary_input_param_key) : 'main_input'; ?>' placeholder='Masukkan nilai <?php echo !empty($primary_input_param_key) ? htmlspecialchars($primary_input_param_key) : 'input'; ?>' required>
                <small class='form-text text-muted'>Input ini akan dipetakan ke parameter '<?php echo !empty($primary_input_param_key) ? htmlspecialchars($primary_input_param_key) : 'input pertama atau generik'; ?>'.</small>
            </div>
            <button type='submit' class='btn btn-primary'>Submit</button>
        </form>

        <div id='loadingIndicator' class='loading-spinner'>
            <div class='spinner-border text-primary' role='status'></div>
            <p class='text-muted'>Mengambil data...</p>
        </div>

        <div id='resultDisplay' class='mt-4' style='display:none;'>
            <div class='result-display'>
                </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toolApiTestForm = document.getElementById('toolApiTestForm');
    const mainInput = document.getElementById('mainInput');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const resultDisplay = document.getElementById('resultDisplay');
    const resultContent = resultDisplay.querySelector('.result-display');

    // Dapatkan string template HTML dari PHP (diasumsikan sudah di-encode JSON)
    const outputHtmlTemplate = JSON.parse('{{generated_output_html_js_escaped}}');

    // Helper function for HTML escaping (for error messages and final display)
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

    // Function to replace placeholders in the HTML template
    function renderHtmlWithData(template, data) {
        let renderedHtml = template;
        // Iterate over the extracted_values to replace placeholders
        for (const path in data) { 
            let value = data[path]; // This is the value from extracted_values

            // Special handling for 'full_response' placeholder: render it as prettified JSON in a <pre> tag
            if (path === 'full_response') {
                try {
                    // Check if value is already an object (meaning get_value_from_json_path returned an object)
                    // or if it's a JSON string that needs parsing
                    const parsedJson = typeof value === 'object' && value !== null ? value : JSON.parse(value); 
                    value = `<pre>${htmlspecialcharsJS(JSON.stringify(parsedJson, null, 2))}</pre>`;
                } catch (e) {
                    // If it's not valid JSON (unexpected, but robust), just treat as plain text in pre
                    value = `<pre>${htmlspecialcharsJS(value)}</pre>`;
                }
            } else {
                // For other values, simply HTML escape them
                value = htmlspecialcharsJS(value);
            }
            
            // Escape path for regex to handle special characters like '.' or '[]'
            const escapedPath = path.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const placeholder = new RegExp(`\\{\\{${escapedPath}\\}\\}`, 'g');
            renderedHtml = renderedHtml.replace(placeholder, value); // Use the processed 'value'
        }
        return renderedHtml;
    }

    // Initial display state
    if (resultContent.innerHTML.trim() === '') {
        resultDisplay.style.display = 'none';
    } else {
        resultDisplay.style.display = 'block';
    }

    toolApiTestForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        loadingIndicator.style.display = 'block';
        resultDisplay.style.display = 'none';
        resultContent.innerHTML = ''; // Clear previous results

        const inputValue = mainInput.value.trim();
        const primaryInputName = mainInput.name; 
        const formData = new FormData();
        formData.append('action', 'run_tool_api_test');
        
        formData.append(primaryInputName, inputValue);

        try {
            const response = await fetch('api.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                // If response is not ok, try to parse JSON error body
                let errorBody = {};
                try {
                    errorBody = await response.json();
                } catch (e) {
                    errorBody.message = `Respons non-JSON atau kosong. Status: ${response.status}`;
                }
                throw new Error(errorBody.message || `HTTP error! Status: ${response.status}`);
            }

            const apiResponse = await response.json(); // Expecting JSON from api.php

            if (apiResponse.status === 'success') {
                const renderedOutput = renderHtmlWithData(outputHtmlTemplate, apiResponse.extracted_values);
                resultContent.innerHTML = renderedOutput;
                resultDisplay.style.display = 'block';
            } else {
                // Handle error message from api.php
                resultContent.innerHTML = `<div class=\"alert alert-danger\">Terjadi kesalahan: ${htmlspecialcharsJS(apiResponse.message)}.</div>`;
                resultDisplay.style.display = 'block';
            }

        } catch (error) {
            console.error('Error fetching API:', error);
            resultContent.innerHTML = `<div class=\"alert alert-danger\">Terjadi kesalahan jaringan: ${htmlspecialcharsJS(error.message)}. Coba lagi nanti.</div>`;
            resultDisplay.style.display = 'block';
        } finally {
            loadingIndicator.style.display = 'none';
        }
    });
});
</script>

<?php include $path_prefix . 'footer.php'; ?>
EOT;


// Template untuk file backend tool (misal: tools/slug/api.php)
$tool_backend_api_template = <<<'EOT'
<?php
// tools/slug/api.php
// File ini berfungsi sebagai proxy backend untuk tool Anda.
// Logika cURL dan pemrosesan API dilakukan di sini, tidak terlihat oleh klien.

// Variabel konfigurasi yang disisipkan dari tool_creator.php
$api_url_for_tool = "{{api_url_for_tool}}";
$api_method_for_tool = "{{api_method_for_tool}}";
$selected_response_paths_json = '{{selected_response_paths_json}}'; 
$api_params_for_tool_json = '{{api_params_for_tool_json}}'; 
$primary_input_param_key = "{{primary_input_param_key}}";
// String HTML template yang di-encode JSON
$generated_output_html_escaped = '{{generated_output_html_escaped}}'; 

$response_data = [];
$error_message = null;

// Fungsi untuk mendapatkan nilai dari path JSON
function get_value_from_json_path($data, $path) {
    if (empty($path) || empty($data)) return 'N/A';
    $path_parts = explode('.', $path);
    $current = $data;
    foreach ($path_parts as $part) {
        $array_match = [];
        if (preg_match('/(\w+)\[(\d+)\]/', $part, $array_match)) {
            $array_key = $array_match[1];
            $array_index = (int)$array_match[2];
            if (isset($current[$array_key]) && is_array($current[$array_key]) && isset($current[$array_key][$array_index])) {
                $current = $current[$array_key][$array_index];
            } else {
                return 'N/A';
            }
        } elseif (is_array($current) && isset($current[$part])) {
            $current = $current[$part];
        } else {
            return 'N/A';
        }
    }
    // Return the value directly if it's not an array/object, otherwise JSON encode it
    return is_array($current) || is_object($current) ? json_encode($current, JSON_PRETTY_PRINT) : $current;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'run_tool_api_test') {
    header('Content-Type: application/json'); // API ini sekarang selalu merespons JSON

    // Dapatkan nilai input dari form berdasarkan primary_input_param_key yang telah dikonfigurasi
    // 'main_input' adalah name default jika primary_input_param_key kosong di frontend
    $input_name_for_form = !empty($primary_input_param_key) ? $primary_input_param_key : 'main_input';
    $test_input_value = $_POST[$input_name_for_form] ?? ''; 
    
    $stored_params = json_decode($api_params_for_tool_json, true);
    $selected_paths = json_decode($selected_response_paths_json, true);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $request_url = $api_url_for_tool;
    $final_params = [];

    // Prioritaskan parameter yang disimpan dari tool_creator.php
    if (!empty($stored_params)) {
        foreach ($stored_params as $param) {
            $final_params[$param['key']] = $param['value'];
        }
    }
    
    // Jika ada nilai input dari form dan kunci input utama ditentukan,
    // timpa nilai parameter yang sesuai di $final_params
    if (!empty($test_input_value) && !empty($primary_input_param_key)) {
        $final_params[$primary_input_param_key] = $test_input_value;
    } 
    // Fallback: Jika tidak ada primary_input_param_key TAPI ada test_input_value
    // dan stored_params kosong atau tidak ada parameter utama yang cocok,
    // gunakan kunci 'input' sebagai parameter.
    else if (!empty($test_input_value) && empty($primary_input_param_key) && empty($final_params)) {
        $final_params['input'] = $test_input_value;
    }


    if ($api_method_for_tool === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($final_params));
        curl_setopt($ch, CURLOPT_URL, $request_url); 
    } else { // GET
        $queryString = '';
        if (!empty($final_params)) {
            $queryString = '?' . http_build_query($final_params);
        }
        curl_setopt($ch, CURLOPT_URL, $request_url . $queryString);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch); // curl_close should be called after curl_error

    if ($curl_error) {
        echo json_encode(['status' => 'error', 'message' => 'Kesalahan cURL: ' . $curl_error]);
    } else {
        $decoded_response = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $extracted_values = [];

            if (!empty($selected_paths)) {
                foreach ($selected_paths as $path) {
                    $extracted_values[$path] = get_value_from_json_path($decoded_response, $path);
                }
            } else {
                // Jika tidak ada path yang dipilih, sertakan full_response agar bisa digunakan jika template memintanya
                $extracted_values['full_response'] = $decoded_response; // Simpan sebagai objek/array, bukan string
            }
            
            // Kirim JSON yang berisi extracted_values kembali ke frontend
            echo json_encode(['status' => 'success', 'extracted_values' => $extracted_values]);

        } else {
            // Bukan JSON atau error parse dari API eksternal
            echo json_encode(['status' => 'error', 'message' => 'Respons API eksternal bukan JSON yang valid atau ada kesalahan parsing. Respons mentah: ' . $response]); 
        }
    }
    exit;
} else {
    // Jika diakses langsung tanpa POST request dari tool, tampilkan pesan error
    http_response_code(405); // Method Not Allowed
    echo 'Akses tidak langsung ke API tool ini.';
    exit;
}
EOT;
