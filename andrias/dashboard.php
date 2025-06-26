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
                            
                            // Include tool management content here
                            echo '<p class="text-muted">Halaman kelola tools akan ditambahkan di sini.</p>';
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
                            
                            // Include feedback content here
                            echo '<p class="text-muted">Halaman feedback akan ditambahkan di sini.</p>';
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
                                                <a href="dashboard.php?page=tool_creator" class="btn btn-primary">
                                                    <i class="fas fa-magic me-2"></i>Buat Tool Baru
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