<?php
// Tentukan judul halaman
$page_title = "Admin Dashboard";
// Path relatif dari folder 'andrias' ke root direktori
$path_prefix = '../'; 

// Sertakan file autentikasi admin
require_once 'auth.php'; 

// Fungsi untuk mengambil data tools dari tools.json
function get_tools_data() {
    $json_file = '../tools.json';
    if (file_exists($json_file)) {
        $json_data = file_get_contents($json_file);
        $tools = json_decode($json_data, true);
        return is_array($tools) ? $tools : [];
    }
    return [];
}

// Fungsi untuk mengambil data feedback dari feedback.json
function get_feedback_data() {
    $json_file = '../feedback.json';
    if (file_exists($json_file)) {
        $json_data = file_get_contents($json_file);
        $feedback = json_decode($json_data, true);
        return is_array($feedback) ? $feedback : [];
    }
    return [];
}

// Ambil data untuk statistik
$tools = get_tools_data();
$feedback = get_feedback_data();
$total_tools = count($tools);
$active_tools = count(array_filter($tools, function($tool) {
    return isset($tool['status']) && $tool['status'] === 'active';
}));
$maintenance_tools = $total_tools - $active_tools;
$total_feedback = count($feedback);

// Tentukan halaman yang akan ditampilkan
$current_page = $_GET['page'] ?? 'dashboard';

// Sertakan file header dan footer HTML
include $path_prefix . 'header.php';
?>

<style>
/* Enhanced Admin Dashboard Styles */
body.admin-page {
    background-color: #f4f7f6;
}

.admin-dashboard-enhanced {
    padding-top: 1.5rem;
}

.admin-header {
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
}

.admin-header h1 {
    font-weight: 600;
    color: #343a40;
}

.custom-alert {
    border-left-width: 5px;
    border-radius: 0.375rem;
}

.stat-card {
    border-radius: 0.75rem;
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08) !important;
}

.stat-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
}

.stat-card .card-title {
    font-size: 0.95rem;
    color: #6c757d;
    font-weight: 500;
}

.stat-card .card-text {
    color: #343a40;
    font-size: 2rem;
    font-weight: 700;
}

/* Sidebar Styles */
.admin-sidebar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: calc(100vh - 76px);
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}

.sidebar-nav {
    padding: 1.5rem 0;
}

.sidebar-nav .nav-link {
    color: rgba(255, 255, 255, 0.8);
    padding: 0.75rem 1.5rem;
    margin: 0.25rem 1rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 500;
}

.sidebar-nav .nav-link:hover {
    color: white;
    background: rgba(255, 255, 255, 0.1);
    transform: translateX(5px);
}

.sidebar-nav .nav-link.active {
    color: white;
    background: rgba(255, 255, 255, 0.2);
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.sidebar-nav .nav-link i {
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
}

.main-content {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    padding: 2rem;
    margin: 1rem;
    min-height: calc(100vh - 120px);
}

/* Page Content Styles */
.page-header {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 1rem;
    margin-bottom: 2rem;
}

.page-header h2 {
    color: #495057;
    font-weight: 600;
}

.page-header .breadcrumb {
    background: none;
    padding: 0;
    margin: 0;
}

/* Tool Creator specific styles when embedded */
.tool-creator-embedded {
    margin-top: 0;
}

.tool-creator-embedded .container {
    padding: 0;
    max-width: none;
}

.tool-creator-embedded .text-center.mb-4 {
    margin-bottom: 1.5rem !important;
}

/* Form manage tool styles */
#manageToolFormSection {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.5s ease-in-out;
}

#manageToolFormSection.form-visible {
    max-height: 1000px; 
    overflow: visible;
}

.form-manage-tool .card-header,
.list-tools-card .card-header {
    background-color: #ffffff;
    border-bottom: 1px solid #eff2f5;
}

.form-manage-tool .card-header h5,
.list-tools-card .card-header h5 {
    font-weight: 600;
    color: #343a40;
}

.form-manage-tool .form-control-lg,
.form-manage-tool .form-select-lg {
    font-size: 1rem;
    padding: 0.65rem 1rem;
}

.form-manage-tool .input-group-text {
    font-size: 1rem;
}

.admin-tools-table th {
    font-weight: 600;
    color: #495057;
    background-color: #f8f9fa !important;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.admin-tools-table td {
    vertical-align: middle;
    font-size: 0.95rem;
}

.admin-tools-table .badge {
    font-size: 0.8rem;
    font-weight: 500;
}

.admin-tools-table .action-buttons .btn {
    padding: 0.3rem 0.6rem;
    font-size: 0.8rem;
}

.admin-tools-table .action-buttons .btn i {
    font-size: 0.9em;
}

.admin-tools-table tr:hover {
    background-color: #f1f5f9 !important;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-sidebar {
        min-height: auto;
    }
    
    .main-content {
        margin: 0.5rem;
        padding: 1rem;
    }
    
    .sidebar-nav .nav-link {
        margin: 0.25rem 0.5rem;
        padding: 0.5rem 1rem;
    }
    
    .admin-header {
        flex-direction: column;
        align-items: flex-start !important;
    }
    .admin-header h1 {
        margin-bottom: 0.5rem !important;
    }
    .stat-card .card-body {
        flex-direction: column;
        align-items: flex-start !important;
    }
    .stat-icon {
        margin-bottom: 0.75rem;
    }
}
</style>

<body class="admin-page">

<div class="admin-dashboard-enhanced">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="admin-sidebar">
                    <nav class="sidebar-nav">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'tools' ? 'active' : ''; ?>" href="dashboard.php?page=tools">
                                    <i class="fas fa-tools"></i>
                                    <span>Kelola Tools</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'tool_creator' ? 'active' : ''; ?>" href="dashboard.php?page=tool_creator">
                                    <i class="fas fa-magic"></i>
                                    <span>Tool Creator</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'advanced_tool_manager' ? 'active' : ''; ?>" href="dashboard.php?page=advanced_tool_manager">
                                    <i class="fas fa-cogs"></i>
                                    <span>Advanced Manager</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'feedback' ? 'active' : ''; ?>" href="dashboard.php?page=feedback">
                                    <i class="fas fa-comments"></i>
                                    <span>Feedback</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <?php
                    // Tampilkan alert jika ada pesan
                    if (isset($_GET['message'])): ?>
                        <div class="alert alert-success custom-alert" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['message']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger custom-alert" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Routing untuk halaman yang berbeda
                    switch ($current_page) {
                        case 'advanced_tool_manager':
                            echo '<div class="page-header">';
                            echo '<h2><i class="fas fa-cogs me-2"></i>Advanced Tool Manager</h2>';
                            echo '<nav aria-label="breadcrumb">';
                            echo '<ol class="breadcrumb">';
                            echo '<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>';
                            echo '<li class="breadcrumb-item active">Advanced Tool Manager</li>';
                            echo '</ol>';
                            echo '</nav>';
                            echo '</div>';
                            
                            echo '<div class="advanced-manager-embedded">';
                            include 'advanced_tool_manager.php';
                            echo '</div>';
                            break;
                            
                        case 'tool_creator':
                            echo '<div class="page-header">';
                            echo '<h2><i class="fas fa-magic me-2"></i>Tool Creator</h2>';
                            echo '<nav aria-label="breadcrumb">';
                            echo '<ol class="breadcrumb">';
                            echo '<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>';
                            echo '<li class="breadcrumb-item active">Tool Creator</li>';
                            echo '</ol>';
                            echo '</nav>';
                            echo '</div>';
                            
                            echo '<div class="tool-creator-embedded">';
                            include 'tool_creator.php';
                            echo '</div>';
                            break;
                            
                        case 'tools':
                            echo '<div class="page-header">';
                            echo '<h2><i class="fas fa-tools me-2"></i>Kelola Tools</h2>';
                            echo '<nav aria-label="breadcrumb">';
                            echo '<ol class="breadcrumb">';
                            echo '<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>';
                            echo '<li class="breadcrumb-item active">Kelola Tools</li>';
                            echo '</ol>';
                            echo '</nav>';
                            echo '</div>';
                            
                            // Include the complete tool management functionality
                            ?>
                            <!-- Form untuk Menambah/Edit Tool -->
                            <div class="card form-manage-tool mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Tambah Tool Baru</h5>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="toggleFormBtn">
                                        <i class="fas fa-chevron-down me-1"></i>Tampilkan Form
                                    </button>
                                </div>
                                <div class="card-body" id="manageToolFormSection">
                                    <form action="tool_actions.php" method="POST" id="toolForm">
                                        <input type="hidden" name="action" value="add_tool" id="formAction">
                                        <input type="hidden" name="tool_id" id="toolId">
                                        
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="toolName" class="form-label">Nama Tool</label>
                                                <div class="input-group input-group-lg">
                                                    <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                                    <input type="text" class="form-control" id="toolName" name="tool_name" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="toolSlug" class="form-label">Slug (URL)</label>
                                                <div class="input-group input-group-lg">
                                                    <span class="input-group-text"><i class="fas fa-link"></i></span>
                                                    <input type="text" class="form-control" id="toolSlug" name="tool_slug" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required>
                                                </div>
                                                <small class="text-muted">Hanya huruf kecil, angka, dan tanda hubung. Contoh: my-tool</small>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-3 mt-2">
                                            <div class="col-md-6">
                                                <label for="toolIcon" class="form-label">Ikon (Font Awesome)</label>
                                                <div class="input-group input-group-lg">
                                                    <span class="input-group-text"><i class="fas fa-icons"></i></span>
                                                    <input type="text" class="form-control" id="toolIcon" name="tool_icon" value="fas fa-tools" required>
                                                </div>
                                                <small class="text-muted">Contoh: fas fa-star, fab fa-github</small>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="toolStatus" class="form-label">Status</label>
                                                <select class="form-select form-select-lg" id="toolStatus" name="tool_status" required>
                                                    <option value="active">Aktif</option>
                                                    <option value="maintenance">Maintenance</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <label for="toolDescription" class="form-label">Deskripsi</label>
                                            <textarea class="form-control" id="toolDescription" name="tool_description" rows="3" required></textarea>
                                        </div>
                                        
                                        <div class="mt-4 d-flex gap-2">
                                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                                <i class="fas fa-save me-2"></i>Simpan Tool
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-lg" id="cancelBtn" style="display: none;">
                                                <i class="fas fa-times me-2"></i>Batal
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Daftar Tools -->
                            <div class="card list-tools-card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Tools (<?php echo $total_tools; ?>)</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($tools)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover admin-tools-table">
                                                <thead>
                                                    <tr>
                                                        <th>Nama Tool</th>
                                                        <th>Slug</th>
                                                        <th>Status</th>
                                                        <th>Deskripsi</th>
                                                        <th width="200">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($tools as $tool): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <i class="<?php echo htmlspecialchars($tool['icon'] ?? 'fas fa-tools'); ?> me-2 text-primary"></i>
                                                                    <strong><?php echo htmlspecialchars($tool['name'] ?? 'Nama Tool'); ?></strong>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($tool['slug'] ?? ''); ?></code>
                                                            </td>
                                                            <td>
                                                                <?php if (($tool['status'] ?? '') === 'active'): ?>
                                                                    <span class="badge bg-success">Aktif</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-warning">Maintenance</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="text-muted"><?php echo htmlspecialchars(substr($tool['description'] ?? '', 0, 50)); ?>...</span>
                                                            </td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button class="btn btn-sm btn-outline-primary edit-tool-btn" 
                                                                            data-id="<?php echo htmlspecialchars($tool['id'] ?? ''); ?>"
                                                                            data-name="<?php echo htmlspecialchars($tool['name'] ?? ''); ?>"
                                                                            data-slug="<?php echo htmlspecialchars($tool['slug'] ?? ''); ?>"
                                                                            data-icon="<?php echo htmlspecialchars($tool['icon'] ?? ''); ?>"
                                                                            data-description="<?php echo htmlspecialchars($tool['description'] ?? ''); ?>"
                                                                            data-status="<?php echo htmlspecialchars($tool['status'] ?? ''); ?>"
                                                                            title="Edit Tool">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    
                                                                    <form method="POST" action="tool_actions.php" class="d-inline">
                                                                        <input type="hidden" name="action" value="toggle_maintenance">
                                                                        <input type="hidden" name="tool_id" value="<?php echo htmlspecialchars($tool['id'] ?? ''); ?>">
                                                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Toggle Status">
                                                                            <i class="fas fa-power-off"></i>
                                                                        </button>
                                                                    </form>
                                                                    
                                                                    <form method="POST" action="tool_actions.php" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus tool ini?')">
                                                                        <input type="hidden" name="action" value="delete_tool">
                                                                        <input type="hidden" name="tool_id" value="<?php echo htmlspecialchars($tool['id'] ?? ''); ?>">
                                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Tool">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Belum ada tools</h5>
                                            <p class="text-muted">Mulai dengan menambahkan tool pertama Anda.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const toggleFormBtn = document.getElementById('toggleFormBtn');
                                const formSection = document.getElementById('manageToolFormSection');
                                const toolForm = document.getElementById('toolForm');
                                const formAction = document.getElementById('formAction');
                                const toolId = document.getElementById('toolId');
                                const submitBtn = document.getElementById('submitBtn');
                                const cancelBtn = document.getElementById('cancelBtn');
                                const toolNameInput = document.getElementById('toolName');
                                const toolSlugInput = document.getElementById('toolSlug');

                                // Toggle form visibility
                                toggleFormBtn.addEventListener('click', function() {
                                    if (formSection.classList.contains('form-visible')) {
                                        formSection.classList.remove('form-visible');
                                        toggleFormBtn.innerHTML = '<i class="fas fa-chevron-down me-1"></i>Tampilkan Form';
                                        resetForm();
                                    } else {
                                        formSection.classList.add('form-visible');
                                        toggleFormBtn.innerHTML = '<i class="fas fa-chevron-up me-1"></i>Sembunyikan Form';
                                    }
                                });

                                // Auto-generate slug from tool name
                                toolNameInput.addEventListener('keyup', function() {
                                    if (formAction.value === 'add_tool') { // Only auto-generate for new tools
                                        toolSlugInput.value = createSlug(this.value);
                                    }
                                });

                                function createSlug(str) {
                                    return str
                                        .toLowerCase()
                                        .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
                                        .replace(/[\s-]+/g, '-') // Replace spaces and multiple hyphens with single hyphen
                                        .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
                                }

                                // Edit tool functionality
                                document.querySelectorAll('.edit-tool-btn').forEach(btn => {
                                    btn.addEventListener('click', function() {
                                        const toolData = {
                                            id: this.dataset.id,
                                            name: this.dataset.name,
                                            slug: this.dataset.slug,
                                            icon: this.dataset.icon,
                                            description: this.dataset.description,
                                            status: this.dataset.status
                                        };

                                        // Fill form with tool data
                                        formAction.value = 'edit_tool';
                                        toolId.value = toolData.id;
                                        document.getElementById('toolName').value = toolData.name;
                                        document.getElementById('toolSlug').value = toolData.slug;
                                        document.getElementById('toolIcon').value = toolData.icon;
                                        document.getElementById('toolDescription').value = toolData.description;
                                        document.getElementById('toolStatus').value = toolData.status;

                                        // Update UI
                                        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Update Tool';
                                        cancelBtn.style.display = 'inline-block';
                                        
                                        // Show form
                                        formSection.classList.add('form-visible');
                                        toggleFormBtn.innerHTML = '<i class="fas fa-chevron-up me-1"></i>Sembunyikan Form';
                                        
                                        // Disable slug editing for existing tools
                                        toolSlugInput.readOnly = true;
                                        toolSlugInput.style.backgroundColor = '#f8f9fa';
                                    });
                                });

                                // Cancel edit
                                cancelBtn.addEventListener('click', function() {
                                    resetForm();
                                });

                                function resetForm() {
                                    toolForm.reset();
                                    formAction.value = 'add_tool';
                                    toolId.value = '';
                                    submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan Tool';
                                    cancelBtn.style.display = 'none';
                                    toolSlugInput.readOnly = false;
                                    toolSlugInput.style.backgroundColor = '';
                                }
                            });
                            </script>
                            <?php
                            break;
                            
                        case 'feedback':
                            echo '<div class="page-header">';
                            echo '<h2><i class="fas fa-comments me-2"></i>Feedback</h2>';
                            echo '<nav aria-label="breadcrumb">';
                            echo '<ol class="breadcrumb">';
                            echo '<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>';
                            echo '<li class="breadcrumb-item active">Feedback</li>';
                            echo '</ol>';
                            echo '</nav>';
                            echo '</div>';
                            
                            // Include feedback content
                            ?>
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Daftar Feedback (<?php echo $total_feedback; ?>)</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($feedback)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Tanggal</th>
                                                        <th>Tool</th>
                                                        <th>Nama</th>
                                                        <th>Saran</th>
                                                        <th>IP Address</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach (array_reverse($feedback) as $item): ?>
                                                        <tr>
                                                            <td>
                                                                <small class="text-muted"><?php echo htmlspecialchars($item['timestamp'] ?? ''); ?></small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-primary"><?php echo htmlspecialchars($item['tool_name'] ?? 'Unknown'); ?></span>
                                                            </td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($item['critic_name'] ?? 'Anonim'); ?></strong>
                                                            </td>
                                                            <td>
                                                                <div style="max-width: 300px;">
                                                                    <?php echo htmlspecialchars(substr($item['suggestion'] ?? '', 0, 100)); ?>
                                                                    <?php if (strlen($item['suggestion'] ?? '') > 100): ?>
                                                                        <span class="text-muted">...</span>
                                                                        <button class="btn btn-sm btn-link p-0" onclick="alert('<?php echo htmlspecialchars(addslashes($item['suggestion'] ?? '')); ?>')">Lihat Selengkapnya</button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($item['ip_address'] ?? ''); ?></code>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Belum ada feedback</h5>
                                            <p class="text-muted">Feedback dari pengguna akan muncul di sini.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php
                            break;
                            
                        default: // Dashboard
                            ?>
                            <div class="admin-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h1>Dashboard Admin</h1>
                                    <p class="text-muted mb-0">Selamat datang, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>!</p>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">Terakhir login: <?php echo date('d M Y, H:i'); ?></small>
                                </div>
                            </div>

                            <!-- Statistik Cards -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-3">
                                    <div class="card stat-card h-100">
                                        <div class="card-body d-flex align-items-center">
                                            <div class="stat-icon bg-primary text-white me-3">
                                                <i class="fas fa-tools"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-1">Total Tools</h6>
                                                <p class="card-text mb-0"><?php echo $total_tools; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stat-card h-100">
                                        <div class="card-body d-flex align-items-center">
                                            <div class="stat-icon bg-success text-white me-3">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-1">Tools Aktif</h6>
                                                <p class="card-text mb-0"><?php echo $active_tools; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stat-card h-100">
                                        <div class="card-body d-flex align-items-center">
                                            <div class="stat-icon bg-warning text-white me-3">
                                                <i class="fas fa-wrench"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-1">Maintenance</h6>
                                                <p class="card-text mb-0"><?php echo $maintenance_tools; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stat-card h-100">
                                        <div class="card-body d-flex align-items-center">
                                            <div class="stat-icon bg-info text-white me-3">
                                                <i class="fas fa-comments"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-1">Total Feedback</h6>
                                                <p class="card-text mb-0"><?php echo $total_feedback; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-grid gap-2">
                                                <a href="dashboard.php?page=advanced_tool_manager" class="btn btn-primary">
                                                    <i class="fas fa-cogs me-2"></i>Advanced Tool Manager
                                                </a>
                                                <a href="dashboard.php?page=tool_creator" class="btn btn-outline-primary">
                                                    <i class="fas fa-magic me-2"></i>Basic Tool Creator
                                                </a>
                                                <a href="dashboard.php?page=tools" class="btn btn-outline-primary">
                                                    <i class="fas fa-tools me-2"></i>Kelola Tools
                                                </a>
                                                <a href="dashboard.php?page=feedback" class="btn btn-outline-info">
                                                    <i class="fas fa-comments me-2"></i>Lihat Feedback
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>Recent Activity</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="list-group list-group-flush">
                                                <?php if (!empty($feedback)): ?>
                                                    <?php foreach (array_slice($feedback, 0, 3) as $item): ?>
                                                        <div class="list-group-item border-0 px-0">
                                                            <div class="d-flex w-100 justify-content-between">
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($item['tool_name'] ?? 'Unknown'); ?></h6>
                                                                <small><?php echo htmlspecialchars($item['timestamp'] ?? ''); ?></small>
                                                            </div>
                                                            <p class="mb-1"><?php echo htmlspecialchars(substr($item['suggestion'] ?? '', 0, 100)); ?>...</p>
                                                            <small>oleh <?php echo htmlspecialchars($item['critic_name'] ?? 'Anonim'); ?></small>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <p class="text-muted">Belum ada feedback terbaru.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                            break;
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path_prefix . 'footer.php'; ?>