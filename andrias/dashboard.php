<?php
session_start(); 

// --- KONFIGURASI DASAR ---
$page_title = "Admin Dashboard";
$path_prefix = '../'; // Path dari folder admin ke root
include 'auth.php'; // Memastikan admin sudah login

$tools_file = $path_prefix . 'tools.json';
$feedback_file_path = $path_prefix . 'feedback.json'; 
$tool_usage_stats_file = $path_prefix . 'tool_usage_stats.json';

$current_admin_page = $_GET['page'] ?? 'tools'; 

// --- FUNGSI-FUNGSI DARI VERSI SEBELUMNYA ---
function get_tools_data_admin() {
    global $tools_file;
    if (!file_exists($tools_file)) return [];
    $json_data = file_get_contents($tools_file);
    $decoded_data = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded_data)) return [];
    return $decoded_data;
}

function get_all_feedback($file_path) {
    if (!file_exists($file_path)) return [];
    $json_data = file_get_contents($file_path);
    if ($json_data === false) return [];
    $feedback_array = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($feedback_array)) return [];
    usort($feedback_array, function($a, $b) {
        return strtotime($b['timestamp'] ?? 0) - strtotime($a['timestamp'] ?? 0);
    });
    return $feedback_array;
}

$tools = get_tools_data_admin();
$total_tools = count($tools);
$active_tools_count = 0;
$maintenance_tools_count = 0;
if(is_array($tools)){
    foreach ($tools as $tool) {
        if (isset($tool['status']) && $tool['status'] === 'active') {
            $active_tools_count++;
        } else {
            $maintenance_tools_count++;
        }
    }
}

$editing_tool = null;
$form_title = 'Tambah Tool Baru';
$form_action = 'add_tool';
$submit_button_text = '<i class="fas fa-plus me-2"></i>Tambah Tool';
$form_visible_class = '';
if ($current_admin_page === 'tools' && isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    foreach ($tools as $tool_item) {
        if (isset($tool_item['id']) && $tool_item['id'] === $_GET['id']) {
            $editing_tool = $tool_item;
            $form_title = 'Edit Tool: ' . htmlspecialchars($editing_tool['name']);
            $form_action = 'edit_tool';
            $submit_button_text = '<i class="fas fa-save me-2"></i>Simpan Perubahan';
            $form_visible_class = 'form-visible';
            break;
        }
    }
}

// === LOGIKA UNTUK FILE MANAGER ===
$file_manager_message = '';
$file_manager_error = '';
$base_tools_path = realpath($path_prefix . 'tools');

function sanitize_name($name) {
    return preg_replace('/[^a-zA-Z0-9\-\._]/', '', $name);
}

function is_path_safe($path, $base_path) {
    $real_path = realpath($path);
    if (!$base_path) return false;
    return $real_path && strpos($real_path, $base_path) === 0;
}

if ($current_admin_page === 'file_manager' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'save_file':
            if (isset($_POST['file_path'], $_POST['file_content'])) {
                $file_to_save_path = $_POST['file_path'];
                if (is_path_safe($file_to_save_path, $base_tools_path)) {
                    if (is_writable($file_to_save_path)) {
                        file_put_contents($file_to_save_path, $_POST['file_content']) !== false
                            ? $file_manager_message = "File '" . htmlspecialchars(basename($file_to_save_path)) . "' berhasil disimpan."
                            : $file_manager_error = "Gagal menulis ke file '" . htmlspecialchars(basename($file_to_save_path)) . "'.";
                    } else {
                        $file_manager_error = "File tidak dapat ditulis. Periksa izin.";
                    }
                } else {
                    $file_manager_error = "Akses ditolak: path file tidak valid.";
                }
            } else {
                $file_manager_error = "Data tidak lengkap untuk menyimpan file.";
            }
            break;
        case 'create_folder':
            if (isset($_POST['new_folder_name'])) {
                $new_folder_name = sanitize_name($_POST['new_folder_name']);
                if (!empty($new_folder_name)) {
                    $new_folder_path = $base_tools_path . DIRECTORY_SEPARATOR . $new_folder_name;
                    if (!file_exists($new_folder_path)) {
                        mkdir($new_folder_path, 0755, true)
                            ? $file_manager_message = "Folder '" . htmlspecialchars($new_folder_name) . "' berhasil dibuat."
                            : $file_manager_error = "Gagal membuat folder. Periksa izin direktori 'tools'.";
                    } else {
                        $file_manager_error = "Folder dengan nama '" . htmlspecialchars($new_folder_name) . "' sudah ada.";
                    }
                } else {
                    $file_manager_error = "Nama folder tidak valid.";
                }
            }
            break;
        case 'create_file':
            if (isset($_POST['new_file_name'], $_POST['current_dir'])) {
                $new_file_name = sanitize_name($_POST['new_file_name']);
                $current_dir = basename($_POST['current_dir']);
                $current_dir_path = $base_tools_path . DIRECTORY_SEPARATOR . $current_dir;
                if (is_path_safe($current_dir_path, $base_tools_path) && !empty($new_file_name)) {
                    $new_file_path = $current_dir_path . DIRECTORY_SEPARATOR . $new_file_name;
                    if (!file_exists($new_file_path)) {
                        touch($new_file_path)
                            ? $file_manager_message = "File '" . htmlspecialchars($new_file_name) . "' berhasil dibuat."
                            : $file_manager_error = "Gagal membuat file. Periksa izin folder.";
                    } else {
                        $file_manager_error = "File dengan nama '" . htmlspecialchars($new_file_name) . "' sudah ada.";
                    }
                } else {
                    $file_manager_error = "Nama file atau direktori tidak valid.";
                }
            }
            break;
        case 'delete_file':
            if (isset($_POST['file_to_delete'])) {
                $file_to_delete_path = $_POST['file_to_delete'];
                if (is_path_safe($file_to_delete_path, $base_tools_path)) {
                    unlink($file_to_delete_path)
                        ? $file_manager_message = "File '" . htmlspecialchars(basename($file_to_delete_path)) . "' berhasil dihapus."
                        : $file_manager_error = "Gagal menghapus file.";
                } else {
                    $file_manager_error = "Akses ditolak: path file tidak valid.";
                }
            }
            break;
        case 'delete_folder':
            if (isset($_POST['dir_to_delete'])) {
                $dir_to_delete_path = $_POST['dir_to_delete'];
                if (is_path_safe($dir_to_delete_path, $base_tools_path)) {
                    if (count(scandir($dir_to_delete_path)) == 2) {
                        rmdir($dir_to_delete_path)
                            ? $file_manager_message = "Folder '" . htmlspecialchars(basename($dir_to_delete_path)) . "' berhasil dihapus."
                            : $file_manager_error = "Gagal menghapus folder.";
                    } else {
                        $file_manager_error = "Gagal menghapus. Folder '" . htmlspecialchars(basename($dir_to_delete_path)) . "' tidak kosong.";
                    }
                } else {
                     $file_manager_error = "Akses ditolak: path direktori tidak valid.";
                }
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-bg: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --sidebar-width: 280px;
            --header-height: 70px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* Layout Structure */
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--card-bg);
            border-right: 1px solid var(--border-color);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .sidebar.collapsed {
            transform: translateX(-100%);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .sidebar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 1rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .nav-link:hover {
            background-color: var(--light-bg);
            color: var(--primary-color);
            transform: translateX(4px);
        }

        .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        /* Header */
        .main-header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .header-content {
            display: flex;
            justify-content: between;
            align-items: center;
            gap: 1rem;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
        }

        .sidebar-toggle:hover {
            background-color: var(--light-bg);
            color: var(--primary-color);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-left: auto;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.75rem;
        }

        /* Content Area */
        .content-area {
            padding: 2rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
        }

        .stat-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }

        .stat-icon.primary { background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); }
        .stat-icon.success { background: linear-gradient(135deg, var(--success-color), #059669); }
        .stat-icon.warning { background: linear-gradient(135deg, var(--warning-color), #d97706); }
        .stat-icon.danger { background: linear-gradient(135deg, var(--danger-color), #dc2626); }

        .stat-details h3 {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }

        /* Cards */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .card-header {
            background: var(--light-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: var(--radius-md);
            border: 1px solid transparent;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1.5;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
            border-color: var(--success-color);
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
            border-color: var(--warning-color);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
            border-color: var(--danger-color);
        }

        .btn-outline-secondary {
            color: var(--text-secondary);
            border-color: var(--border-color);
            background: transparent;
        }

        .btn-outline-secondary:hover {
            background: var(--light-bg);
            color: var(--text-primary);
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
        }

        .btn-lg {
            padding: 0.875rem 1.5rem;
            font-size: 1rem;
        }

        /* Forms */
        .form-control {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--card-bg);
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 7 7 7-7'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            padding-right: 2.5rem;
        }

        /* Tables */
        .table-container {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .table {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
        }

        .table th {
            background: var(--light-bg);
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .table tbody tr:hover {
            background: var(--light-bg);
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-success {
            background: rgb(16 185 129 / 0.1);
            color: var(--success-color);
        }

        .badge-warning {
            background: rgb(245 158 11 / 0.1);
            color: var(--warning-color);
        }

        .badge-primary {
            background: rgb(37 99 235 / 0.1);
            color: var(--primary-color);
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            border: 1px solid transparent;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: rgb(16 185 129 / 0.1);
            border-color: rgb(16 185 129 / 0.2);
            color: #065f46;
        }

        .alert-danger {
            background: rgb(239 68 68 / 0.1);
            border-color: rgb(239 68 68 / 0.2);
            color: #991b1b;
        }

        .alert-warning {
            background: rgb(245 158 11 / 0.1);
            border-color: rgb(245 158 11 / 0.2);
            color: #92400e;
        }

        .alert-info {
            background: rgb(37 99 235 / 0.1);
            border-color: rgb(37 99 235 / 0.2);
            color: #1e40af;
        }

        /* Form Animation */
        .form-section {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-in-out, opacity 0.3s ease;
            opacity: 0;
        }

        .form-section.visible {
            max-height: 1000px;
            opacity: 1;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        /* File Manager Styles */
        .file-manager-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 1.5rem;
        }

        .file-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }

        .file-list-item:hover {
            background: var(--light-bg);
        }

        .file-list-item.active {
            background: var(--primary-color);
            color: white;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Code Editor */
        .code-editor {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.875rem;
            line-height: 1.5;
            background: #1e293b;
            color: #e2e8f0;
            border: none;
            border-radius: var(--radius-md);
            padding: 1rem;
            resize: vertical;
        }

        .code-editor:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: block;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }

            .file-manager-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .content-area {
                padding: 1rem;
            }

            .main-header {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stat-card {
                padding: 1rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.25rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Utilities */
        .text-muted { color: var(--text-secondary) !important; }
        .text-primary { color: var(--primary-color) !important; }
        .text-success { color: var(--success-color) !important; }
        .text-warning { color: var(--warning-color) !important; }
        .text-danger { color: var(--danger-color) !important; }

        .mb-0 { margin-bottom: 0 !important; }
        .mb-1 { margin-bottom: 0.5rem !important; }
        .mb-2 { margin-bottom: 1rem !important; }
        .mb-3 { margin-bottom: 1.5rem !important; }
        .mb-4 { margin-bottom: 2rem !important; }

        .d-flex { display: flex !important; }
        .align-items-center { align-items: center !important; }
        .justify-content-between { justify-content: space-between !important; }
        .justify-content-end { justify-content: flex-end !important; }
        .gap-2 { gap: 1rem !important; }

        .w-100 { width: 100% !important; }
        .text-center { text-align: center !important; }
        .text-end { text-align: right !important; }

        .visually-hidden {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <i class="fas fa-cogs"></i>
                    <span>Admin Panel</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="dashboard.php?page=tools" class="nav-link <?php echo ($current_admin_page === 'tools') ? 'active' : ''; ?>">
                        <i class="fas fa-tools"></i>
                        <span>Manajemen Tools</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="dashboard.php?page=feedback" class="nav-link <?php echo ($current_admin_page === 'feedback') ? 'active' : ''; ?>">
                        <i class="fas fa-comments"></i>
                        <span>Kritik dan Saran</span>
                    </a>
                </div>
                <!-- Nav item baru untuk Tool Creator -->
                <div class="nav-item">
                    <a href="tool_creator.php" class="nav-link <?php echo ($current_admin_page === 'tool_creator') ? 'active' : ''; ?>">
                        <i class="fas fa-plus-square"></i>
                        <span>Buat Tool Baru</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="dashboard.php?page=stats" class="nav-link <?php echo ($current_admin_page === 'stats') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Statistik</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="dashboard.php?page=file_manager" class="nav-link <?php echo ($current_admin_page === 'file_manager') ? 'active' : ''; ?>">
                        <i class="fas fa-folder-open"></i>
                        <span>File Manager</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Header -->
            <header class="main-header">
                <div class="header-content">
                    <div class="header-left">
                        <button class="sidebar-toggle" id="sidebarToggle">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h1 class="page-title">
                            <?php
                            $page_titles = [
                                'tools' => 'Manajemen Tools',
                                'feedback' => 'Kritik dan Saran',
                                'stats' => 'Statistik',
                                'file_manager' => 'File Manager'
                            ];
                            echo $page_titles[$current_admin_page] ?? 'Dashboard';
                            ?>
                        </h1>
                    </div>
                    <div class="header-right">
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)); ?>
                            </div>
                            <span>Selamat datang, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>!</span>
                        </div>
                        <a href="logout.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area">
                <?php if ($current_admin_page === 'tools'): ?>
                    <!-- Stats Cards -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-icon primary">
                                    <i class="fas fa-tools"></i>
                                </div>
                                <div class="stat-details">
                                    <h3>Total Tools</h3>
                                    <div class="stat-value"><?php echo $total_tools; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-icon success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-details">
                                    <h3>Tools Aktif</h3>
                                    <div class="stat-value"><?php echo $active_tools_count; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-icon warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="stat-details">
                                    <h3>Tools Maintenance</h3>
                                    <div class="stat-value"><?php echo $maintenance_tools_count; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Tool Button -->
                    <div class="d-flex justify-content-end mb-3" id="addToolButtonContainer">
                        <button id="showAddToolFormBtn" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus-circle"></i>
                            <span>Tambah Tool Baru</span>
                        </button>
                    </div>

                    <!-- Tool Form -->
                    <div class="form-section <?php echo $form_visible_class; ?>" id="manageToolFormSection">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <i id="formIcon" class="<?php echo $editing_tool ? 'fas fa-edit' : 'fas fa-plus-circle'; ?>"></i>
                                    <span id="formTitleText"><?php echo $form_title; ?></span>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="hideFormBtn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <form action="tool_actions.php" method="POST" id="toolForm">
                                    <input type="hidden" name="action" id="formAction" value="<?php echo htmlspecialchars($form_action); ?>">
                                    <input type="hidden" name="tool_id" id="toolId" value="<?php echo htmlspecialchars($editing_tool['id'] ?? ''); ?>">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="tool_name" class="form-label">
                                                Nama Tool <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="tool_name" name="tool_name" 
                                                   value="<?php echo htmlspecialchars($editing_tool['name'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tool_slug" class="form-label">
                                                Slug <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="tool_slug" name="tool_slug" 
                                                   value="<?php echo htmlspecialchars($editing_tool['slug'] ?? ''); ?>" 
                                                   <?php echo $editing_tool ? 'readonly' : 'required'; ?> 
                                                   pattern="[a-z0-9]+(?:-[a-z0-9]+)*">
                                            <small class="text-muted" id="slugHelpText">
                                                <?php echo $editing_tool ? 'Slug tidak dapat diubah.' : 'Akan dibuat otomatis jika dikosongkan, atau isi manual (huruf kecil, angka, strip).'; ?>
                                            </small>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="tool_icon" class="form-label">
                                                Ikon (Font Awesome) <span class="text-danger">*</span>
                                            </label>
                                            <div class="d-flex gap-2">
                                                <div class="d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: var(--light-bg); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                                                    <i id="iconPreview" class="<?php echo htmlspecialchars($editing_tool['icon'] ?? 'fas fa-tools'); ?> text-primary"></i>
                                                </div>
                                                <input type="text" class="form-control" id="tool_icon" name="tool_icon" 
                                                       value="<?php echo htmlspecialchars($editing_tool['icon'] ?? 'fas fa-tools'); ?>" 
                                                       required placeholder="e.g., fas fa-cog">
                                            </div>
                                            <small class="text-muted">
                                                Lihat ikon di <a href="https://fontawesome.com/icons" target="_blank" rel="noopener noreferrer">Font Awesome</a>. Contoh: <code>fas fa-star</code>
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tool_status" class="form-label">
                                                Status <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="tool_status" name="tool_status" required>
                                                <option value="active" <?php echo (isset($editing_tool['status']) && $editing_tool['status'] === 'active') ? 'selected' : ''; ?>>Aktif</option>
                                                <option value="maintenance" <?php echo (isset($editing_tool['status']) && $editing_tool['status'] === 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tool_description" class="form-label">
                                            Deskripsi Singkat <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="tool_description" name="tool_description" 
                                                  rows="3" required><?php echo htmlspecialchars($editing_tool['description'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" id="cancelFormBtn" class="btn btn-outline-secondary" 
                                                style="display: <?php echo $editing_tool ? 'inline-flex' : 'none'; ?>;">
                                            <i class="fas fa-times"></i>
                                            <span>Batal</span>
                                        </button>
                                        <button type="submit" class="btn btn-primary" id="submitFormBtn">
                                            <?php echo $submit_button_text; ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Tools Table -->
                    <div class="table-container">
                        <div class="card-header">
                            <i class="fas fa-list-alt me-2"></i>
                            Daftar Tools Tersedia
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">No.</th>
                                        <th style="width: 25%;">Nama Tool</th>
                                        <th style="width: 20%;">Slug</th>
                                        <th style="width: 15%;">Status</th>
                                        <th style="width: 15%;">Folder Tool</th>
                                        <th style="width: 20%;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tools)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center" style="padding: 3rem;">
                                                <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                                                <br>Belum ada tools yang ditambahkan.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($tools as $index => $tool): ?>
                                            <tr id="tool-row-<?php echo htmlspecialchars($tool['id'] ?? ''); ?>">
                                                <td><?php echo $index + 1; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="<?php echo htmlspecialchars($tool['icon'] ?? ''); ?> text-primary"></i>
                                                        <?php echo htmlspecialchars($tool['name'] ?? ''); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <code style="background: var(--light-bg); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8125rem;">
                                                        <?php echo htmlspecialchars($tool['slug'] ?? ''); ?>
                                                    </code>
                                                </td>
                                                <td>
                                                    <?php if (isset($tool['status']) && $tool['status'] === 'active'): ?>
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-check-circle"></i>
                                                            Aktif
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                            Maintenance
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $tool_folder_path = $path_prefix . 'tools/' . ($tool['slug'] ?? ''); 
                                                    if (!empty($tool['slug']) && is_dir($tool_folder_path)): 
                                                    ?>
                                                        <span class="text-success">
                                                            <i class="fas fa-folder-open me-1"></i>Ada
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-danger">
                                                            <i class="fas fa-folder-minus me-1"></i>Tidak Ada
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="action-buttons">
                                                        <a href="dashboard.php?page=tools&action=edit&id=<?php echo htmlspecialchars($tool['id'] ?? ''); ?>#manageToolFormSection" 
                                                           class="btn btn-outline-primary btn-sm edit-tool-btn" 
                                                           data-tool-id="<?php echo htmlspecialchars($tool['id'] ?? ''); ?>" 
                                                           title="Edit Tool">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="tool_actions.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="toggle_maintenance">
                                                            <input type="hidden" name="tool_id" value="<?php echo htmlspecialchars($tool['id'] ?? ''); ?>">
                                                            <button type="submit" 
                                                                    class="btn btn-outline-<?php echo (isset($tool['status']) && $tool['status'] === 'active') ? 'warning' : 'success'; ?> btn-sm" 
                                                                    title="<?php echo (isset($tool['status']) && $tool['status'] === 'active') ? 'Set ke Maintenance' : 'Set ke Aktif'; ?>">
                                                                <i class="fas <?php echo (isset($tool['status']) && $tool['status'] === 'active') ? 'fa-tools' : 'fa-play-circle'; ?>"></i>
                                                            </button>
                                                        </form>
                                                        <form action="tool_actions.php" method="POST" class="d-inline" 
                                                              onsubmit="return confirmDeleteTool('<?php echo htmlspecialchars(addslashes($tool['name'] ?? '')); ?>');">
                                                            <input type="hidden" name="action" value="delete_tool">
                                                            <input type="hidden" name="tool_id" value="<?php echo htmlspecialchars($tool['id'] ?? ''); ?>">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus Tool">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($current_admin_page === 'feedback'): ?>
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-comments me-2"></i>
                            Daftar Kritik dan Saran
                        </div>
                        <div class="card-body">
                            <?php $feedback_list = get_all_feedback($feedback_file_path); ?>
                            <?php if (empty($feedback_list)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Belum ada kritik dan saran yang diterima.
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <span class="badge badge-primary">Total Feedback: <?php echo count($feedback_list); ?></span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%;">No.</th>
                                                <th style="width: 15%;">Timestamp</th>
                                                <th style="width: 20%;">Tool</th>
                                                <th style="width: 15%;">Pengkritik</th>
                                                <th style="width: 30%;">Saran</th>
                                                <th style="width: 10%;">IP</th>
                                                <th style="width: 5%;" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($feedback_list as $index => $feedback): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($feedback['timestamp'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($feedback['tool_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($feedback['critic_name'] ?? 'Anonim'); ?></td>
                                                    <td>
                                                        <small><?php echo nl2br(htmlspecialchars($feedback['suggestion'] ?? 'N/A')); ?></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($feedback['ip_address'] ?? 'N/A'); ?></td>
                                                    <td class="text-center">
                                                        <a href="dashboard.php?page=feedback&action=delete_feedback&id=<?php echo urlencode($feedback['timestamp'] ?? ''); ?>" 
                                                           class="btn btn-outline-danger btn-sm" 
                                                           onclick="return confirm('Anda yakin ingin menghapus feedback ini?');" 
                                                           title="Hapus Feedback">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($current_admin_page === 'stats'): ?>
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-line me-2"></i>
                            Statistik Penggunaan Tools
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Catatan:</strong> Statistik berikut diambil dari file <code>tool_usage_stats.json</code> di server.
                            </div>
                            
                            <?php 
                            $tool_usage_stats_display_data = [];
                            $error_loading_usage_stats = '';
                            if (file_exists($tool_usage_stats_file)) {
                                $stats_content = file_get_contents($tool_usage_stats_file);
                                $decoded_stats = json_decode($stats_content, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_stats)) {
                                    $tool_name_map = [];
                                    foreach ($tools as $tool_info) {
                                        $tool_name_map[$tool_info['slug']] = $tool_info['name'];
                                    }
                                    foreach ($decoded_stats as $slug => $count) {
                                        $tool_usage_stats_display_data[] = [
                                            'name' => $tool_name_map[$slug] ?? "Tool (slug: " . htmlspecialchars($slug) . ")", 
                                            'slug' => htmlspecialchars($slug), 
                                            'count' => intval($count)
                                        ];
                                    }
                                    usort($tool_usage_stats_display_data, function($a, $b) { 
                                        return $b['count'] - $a['count']; 
                                    });
                                } else { 
                                    $error_loading_usage_stats = "Gagal membaca format file statistik."; 
                                }
                            } else { 
                                $error_loading_usage_stats = "File statistik penggunaan tidak ditemukan."; 
                            }
                            ?>
                            
                            <?php if (!empty($error_loading_usage_stats)): ?>
                                <div class="alert alert-danger"><?php echo $error_loading_usage_stats; ?></div>
                            <?php endif; ?>
                            
                            <?php if (empty($tool_usage_stats_display_data)): ?>
                                <p class="text-muted">Belum ada data penggunaan tools yang tercatat.</p>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($tool_usage_stats_display_data as $stat_item): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($stat_item['name']); ?></h6>
                                                            <small class="text-muted"><?php echo $stat_item['slug']; ?></small>
                                                        </div>
                                                        <span class="badge badge-primary"><?php echo $stat_item['count']; ?> kali</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($current_admin_page === 'file_manager'): ?>
                    <?php if (!empty($file_manager_message)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $file_manager_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($file_manager_error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $file_manager_error; ?>
                        </div>
                    <?php endif; ?>

                    <div class="file-manager-grid">
                        <!-- Folder List -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Folder Tools</span>
                                <button class="btn btn-outline-primary btn-sm" id="addFolderBtn" title="Tambah Folder Baru">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <?php 
                                $dirs = array_filter(glob($base_tools_path . '/*'), 'is_dir'); 
                                if (empty($dirs)): 
                                ?>
                                    <div class="p-3 text-muted text-center">
                                        <i class="fas fa-folder-open fa-2x mb-2"></i>
                                        <br>Tidak ada folder ditemukan.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($dirs as $dir): ?>
                                        <?php 
                                        $dir_name = basename($dir); 
                                        $is_active_dir = (isset($_GET['dir']) && $_GET['dir'] === $dir_name); 
                                        ?>
                                        <div class="file-list-item <?php echo $is_active_dir ? 'active' : ''; ?>">
                                            <a href="dashboard.php?page=file_manager&dir=<?php echo urlencode($dir_name); ?>" 
                                               class="file-info text-decoration-none <?php echo $is_active_dir ? 'text-white' : 'text-body'; ?>">
                                                <i class="fas fa-folder me-2"></i>
                                                <?php echo htmlspecialchars($dir_name); ?>
                                            </a>
                                            <form method="POST" action="dashboard.php?page=file_manager" class="d-inline" 
                                                  onsubmit="return confirm('Anda yakin ingin menghapus folder ini? HANYA BISA JIKA FOLDER KOSONG!');">
                                                <input type="hidden" name="action" value="delete_folder">
                                                <input type="hidden" name="dir_to_delete" value="<?php echo htmlspecialchars($dir); ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus Folder">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- File Content -->
                        <div>
                            <?php if (isset($_GET['dir'])): ?>
                                <?php 
                                $selected_dir_name = basename($_GET['dir']); 
                                $current_dir_path = $base_tools_path . DIRECTORY_SEPARATOR . $selected_dir_name; 
                                if (is_path_safe($current_dir_path, $base_tools_path) && is_dir($current_dir_path)): 
                                ?>
                                    <!-- File List -->
                                    <div class="card mb-4">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <span>File di /tools/<?php echo htmlspecialchars($selected_dir_name); ?></span>
                                            <button class="btn btn-outline-primary btn-sm" id="addFileBtn" 
                                                    data-current-dir="<?php echo htmlspecialchars($selected_dir_name); ?>" 
                                                    title="Tambah File Baru">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <div class="card-body p-0">
                                            <?php 
                                            $files = new DirectoryIterator($current_dir_path); 
                                            $file_found = false; 
                                            foreach ($files as $fileinfo): 
                                                if (!$fileinfo->isDot() && $fileinfo->isFile()): 
                                                    $file_found = true; 
                                                    $file_name = $fileinfo->getFilename(); 
                                                    $is_active_file = (isset($_GET['file']) && $_GET['file'] === $file_name); 
                                                    $file_path_full = $fileinfo->getPathname(); 
                                            ?>
                                                <div class="file-list-item <?php echo $is_active_file ? 'active' : ''; ?>">
                                                    <a href="dashboard.php?page=file_manager&dir=<?php echo urlencode($selected_dir_name); ?>&file=<?php echo urlencode($file_name); ?>" 
                                                       class="file-info text-decoration-none <?php echo $is_active_file ? 'text-white' : 'text-body'; ?>">
                                                        <?php 
                                                        $icon = 'fa-file-alt'; 
                                                        $icon_prefix = 'fas'; 
                                                        $ext = strtolower($fileinfo->getExtension()); 
                                                        if (in_array($ext, ['php', 'html'])) $icon = 'fa-code'; 
                                                        if (in_array($ext, ['js'])) {$icon = 'fa-js-square'; $icon_prefix = 'fab';} 
                                                        if (in_array($ext, ['css'])) {$icon = 'fa-css3-alt'; $icon_prefix = 'fab';} 
                                                        if (in_array($ext, ['json'])) $icon = 'fa-file-code'; 
                                                        ?>
                                                        <i class="<?php echo $icon_prefix . ' ' . $icon; ?> me-2"></i>
                                                        <?php echo htmlspecialchars($file_name); ?>
                                                    </a>
                                                    <form method="POST" action="dashboard.php?page=file_manager&dir=<?php echo urlencode($selected_dir_name); ?>" 
                                                          class="d-inline" onsubmit="return confirm('Anda yakin ingin menghapus file ini?');">
                                                        <input type="hidden" name="action" value="delete_file">
                                                        <input type="hidden" name="file_to_delete" value="<?php echo htmlspecialchars($file_path_full); ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus File">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php 
                                                endif; 
                                            endforeach; 
                                            if (!$file_found): 
                                            ?>
                                                <div class="p-3 text-muted text-center">
                                                    <i class="fas fa-file fa-2x mb-2"></i>
                                                    <br>Tidak ada file ditemukan.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- File Editor -->
                            <?php if (isset($_GET['dir']) && isset($_GET['file'])): ?>
                                <?php 
                                $selected_dir_name = basename($_GET['dir']); 
                                $selected_file_name = basename($_GET['file']); 
                                $file_to_edit_path = $base_tools_path . DIRECTORY_SEPARATOR . $selected_dir_name . DIRECTORY_SEPARATOR . $selected_file_name; 
                                if (is_path_safe($file_to_edit_path, $base_tools_path) && file_exists($file_to_edit_path) && is_readable($file_to_edit_path)): 
                                    $file_content = file_get_contents($file_to_edit_path); 
                                ?>
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <span>Mengedit: <?php echo htmlspecialchars($selected_file_name); ?></span>
                                            <span class="badge badge-primary"><?php echo round(filesize($file_to_edit_path) / 1024, 2); ?> KB</span>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST" action="dashboard.php?page=file_manager&dir=<?php echo urlencode($selected_dir_name); ?>&file=<?php echo urlencode($selected_file_name); ?>">
                                                <input type="hidden" name="action" value="save_file">
                                                <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($file_to_edit_path); ?>">
                                                <div class="mb-3">
                                                    <textarea name="file_content" class="form-control code-editor" rows="20"><?php echo htmlspecialchars($file_content); ?></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        File tidak ditemukan atau tidak dapat diakses.
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Halaman tidak ditemukan. Silakan pilih menu di sidebar.
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const currentAdminPage = '<?php echo $current_admin_page; ?>';
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mainContent = document.getElementById('mainContent');

            // Sidebar toggle functionality
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 1024) {
                    if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                        sidebar.classList.remove('show');
                    }
                }
            });

            // Tools page functionality
            if (currentAdminPage === 'tools') {
                const toolNameInput = document.getElementById('tool_name');
                const toolSlugInput = document.getElementById('tool_slug');
                const toolIconInput = document.getElementById('tool_icon');
                const iconPreview = document.getElementById('iconPreview');
                const manageToolFormSection = document.getElementById('manageToolFormSection');
                const showAddToolFormBtn = document.getElementById('showAddToolFormBtn');
                const hideFormBtn = document.getElementById('hideFormBtn');
                const cancelFormBtn = document.getElementById('cancelFormBtn');
                const toolForm = document.getElementById('toolForm');
                const formTitleText = document.getElementById('formTitleText');
                const formIcon = document.getElementById('formIcon');
                const formActionInput = document.getElementById('formAction');
                const toolIdInput = document.getElementById('toolId');
                const submitFormBtn = document.getElementById('submitFormBtn');
                const slugHelpText = document.getElementById('slugHelpText');
                const addToolButtonContainer = document.getElementById('addToolButtonContainer');

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

                if (toolNameInput && toolSlugInput && !toolSlugInput.hasAttribute('readonly')) {
                    toolNameInput.addEventListener('keyup', function() {
                        if (!toolSlugInput.hasAttribute('readonly')) {
                             toolSlugInput.value = createSlug(this.value);
                        }
                    });
                }

                if (toolIconInput && iconPreview) {
                    toolIconInput.addEventListener('keyup', function() {
                        iconPreview.className = this.value || 'fas fa-tools';
                    });
                    if(iconPreview) iconPreview.className = toolIconInput.value || 'fas fa-tools';
                }

                function resetFormToAddMode() {
                    if(toolForm) toolForm.reset();
                    if(iconPreview) iconPreview.className = 'fas fa-tools';
                    if(formTitleText) formTitleText.textContent = 'Tambah Tool Baru';
                    if(formIcon) formIcon.className = 'fas fa-plus-circle';
                    if(formActionInput) formActionInput.value = 'add_tool';
                    if(toolIdInput) toolIdInput.value = '';
                    if(submitFormBtn) submitFormBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Tambah Tool';
                    if(toolSlugInput) toolSlugInput.removeAttribute('readonly');
                    if(slugHelpText) slugHelpText.textContent = 'Akan dibuat otomatis jika dikosongkan, atau isi manual (huruf kecil, angka, strip).';
                    if(cancelFormBtn) cancelFormBtn.style.display = 'none';
                    const url = new URL(window.location);
                    url.searchParams.delete('action');
                    url.searchParams.delete('id');
                    window.history.pushState({}, '', url.pathname + '?page=tools');
                }

                function showForm() {
                    if(manageToolFormSection) {
                        manageToolFormSection.classList.add('visible');
                        manageToolFormSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                    if(addToolButtonContainer) addToolButtonContainer.style.display = 'none';
                }

                function hideForm() {
                    if(manageToolFormSection) {
                        manageToolFormSection.classList.remove('visible');
                    }
                    if(addToolButtonContainer) addToolButtonContainer.style.display = 'flex';
                    resetFormToAddMode();
                }
                
                if (showAddToolFormBtn) {
                    showAddToolFormBtn.addEventListener('click', function() {
                        resetFormToAddMode();
                        showForm();
                    });
                }

                if (hideFormBtn) hideFormBtn.addEventListener('click', hideForm);
                if (cancelFormBtn) cancelFormBtn.addEventListener('click', hideForm);

                if (manageToolFormSection && manageToolFormSection.classList.contains('form-visible')) {
                    if(addToolButtonContainer) addToolButtonContainer.style.display = 'none';
                    if(cancelFormBtn) cancelFormBtn.style.display = 'inline-flex';
                    manageToolFormSection.classList.add('visible');
                }

                window.confirmDeleteTool = function(toolName) {
                    return confirm(`Apakah Anda yakin ingin menghapus tool "${toolName}"? Folder tool terkait juga perlu dihapus manual jika ada.`);
                }
            } 

            // File manager functionality
            if (currentAdminPage === 'file_manager') {
                function postForm(action, data) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'dashboard.php?page=file_manager' + (data.current_dir ? '&dir=' + encodeURIComponent(data.current_dir) : '');
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = action;
                    form.appendChild(actionInput);
                    for (const key in data) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = data[key];
                        form.appendChild(input);
                    }
                    document.body.appendChild(form);
                    form.submit();
                }

                const addFolderBtn = document.getElementById('addFolderBtn');
                if (addFolderBtn) {
                    addFolderBtn.addEventListener('click', function() {
                        const newFolderName = prompt('Masukkan nama folder baru (hanya huruf, angka, strip, garis bawah):');
                        if (newFolderName) {
                            postForm('create_folder', { new_folder_name: newFolderName });
                        }
                    });
                }
                
                const addFileBtn = document.getElementById('addFileBtn');
                if (addFileBtn) {
                    addFileBtn.addEventListener('click', function() {
                        const newFileName = prompt('Masukkan nama file baru (termasuk ekstensi, misal: index.php):');
                        if (newFileName) {
                            postForm('create_file', { 
                                new_file_name: newFileName,
                                current_dir: this.getAttribute('data-current-dir')
                            });
                        }
                    });
                }
            }

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1024) {
                    sidebar.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>