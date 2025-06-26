<?php
session_start();

// Base URL untuk path aset yang konsisten.
$base_url = '/';

// --- LOGIKA MENGAMBIL SLUG DARI URL ---
$page_slug = ''; 
$request_uri = strtok($_SERVER['REQUEST_URI'], '?');
if (preg_match('/\/tools\/([a-zA-Z0-9_-]+)\/?$/', $request_uri, $matches)) {
    $page_slug = $matches[1];
}

/**
 * Fungsi untuk mengambil data definisi tools dari tools.json.
 * @return array Data tools atau array kosong jika gagal.
 */
function get_tools_data() {
    $json_file = 'tools.json';
    if (file_exists($json_file)) {
        $json_data = file_get_contents($json_file);
        $tools = json_decode($json_data, true);
        return is_array($tools) ? $tools : [];
    }
    return [];
}

/**
 * Fungsi untuk mengambil data statistik penggunaan dari tool_usage_stats.json.
 * @return array Data statistik atau array kosong jika gagal.
 */
function get_usage_stats() {
    $stats_file = 'tool_usage_stats.json';
    if (file_exists($stats_file)) {
        $stats_data = file_get_contents($stats_file);
        $stats = json_decode($stats_data, true);
        return is_array($stats) ? $stats : [];
    }
    return [];
}

$tools = get_tools_data();
$usage_stats = get_usage_stats();

// Gabungkan statistik ke dalam data tools untuk pengurutan
foreach ($tools as $index => $tool) {
    $slug = $tool['slug'] ?? 'unknown-' . $index;
    $tools[$index]['usage_count'] = $usage_stats[$slug] ?? 0;
}

// Urutkan tools berdasarkan usage_count (paling populer di atas)
usort($tools, function($a, $b) {
    return ($b['usage_count'] ?? 0) - ($a['usage_count'] ?? 0);
});

// Kategorisasi tools
$categories = [
    'AI Tools' => ['ai-prompt-generator', 'ai-image-generator', 'ai-detection', 'editor-image-with-ai', 'image-to-anime'],
    'Download Tools' => ['aio-all-in-one-downloader', 'ponhub-downloader', 'youtube-transcript-generator'],
    'Image Tools' => ['image-optimizer', 'face-swap'],
    'Text Tools' => ['text-analyzer', 'kamus-kbbi'],
    'Generator Tools' => ['password-generator', 'kartu-undian-generator', 'generate-nama-bayi', 'sunoai-generate-music'],
    'Utility Tools' => ['cek-validasi-rekening']
];

// --- PERSIAPAN DATA UNTUK CHART ---
$top_tools = array_slice($tools, 0, 5);
$top_tool_names = json_encode(array_column($top_tools, 'name'));
$top_tool_usage = json_encode(array_column($top_tools, 'usage_count'));

$page_title = "Webtools Directory - Platform Tools Digital Terlengkap";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <?php if (!empty($page_slug)): ?>
    <meta name="tool-slug-stats" content="<?php echo htmlspecialchars($page_slug); ?>">
    <?php endif; ?>

    <!-- Meta Tags -->
    <meta name="description" content="Platform tools digital terlengkap dengan lebih dari 15+ tools AI, download, generator, dan utility tools lainnya">
    <meta name="keywords" content="webtools, ai tools, online tools, digital tools, generator, downloader">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /*
        ==================================================
        MODERN SOPHISTICATED WEBTOOLS DIRECTORY
        ==================================================
        */
        
        :root {
            /* Modern Color Palette */
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --accent-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            
            /* Neutral Colors */
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            
            /* Glassmorphism */
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --backdrop-blur: blur(20px);
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -2px rgb(0 0 0 / 0.05);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --shadow-glow: 0 0 50px rgba(102, 126, 234, 0.3);
            
            /* Animations */
            --transition-fast: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-normal: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--gray-800);
            overflow-x: hidden;
            position: relative;
        }

        /* Background Effects */
        .bg-decoration {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 226, 0.3) 0%, transparent 50%);
        }

        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-blur);
            border: 1px solid var(--glass-border);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }

        .shape:nth-child(1) { width: 80px; height: 80px; top: 20%; left: 10%; animation-delay: 0s; }
        .shape:nth-child(2) { width: 60px; height: 60px; top: 60%; left: 80%; animation-delay: -5s; }
        .shape:nth-child(3) { width: 100px; height: 100px; top: 80%; left: 20%; animation-delay: -10s; }
        .shape:nth-child(4) { width: 40px; height: 40px; top: 30%; left: 70%; animation-delay: -15s; }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(120deg); }
            66% { transform: translateY(20px) rotate(240deg); }
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        .header {
            padding: 2rem 0;
            text-align: center;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            text-decoration: none;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-gradient);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            box-shadow: var(--shadow-lg);
            transition: var(--transition-normal);
        }

        .logo:hover .logo-icon {
            transform: scale(1.05) rotate(5deg);
            box-shadow: var(--shadow-glow);
        }

        .logo-text {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-title {
            font-family: 'Poppins', sans-serif;
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            color: white;
            margin-bottom: 1rem;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        /* Search Section */
        .search-section {
            margin: 3rem 0;
            display: flex;
            justify-content: center;
        }

        .search-container {
            position: relative;
            width: 100%;
            max-width: 500px;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3.5rem;
            font-size: 1.1rem;
            border: none;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: var(--backdrop-blur);
            box-shadow: var(--shadow-xl);
            color: var(--gray-800);
            transition: var(--transition-normal);
            outline: none;
        }

        .search-input:focus {
            transform: translateY(-2px);
            box-shadow: var(--shadow-glow);
            background: rgba(255, 255, 255, 1);
        }

        .search-input::placeholder {
            color: var(--gray-500);
        }

        .search-icon {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            font-size: 1.2rem;
            transition: var(--transition-normal);
        }

        .search-input:focus + .search-icon {
            color: #667eea;
        }

        /* Category Filter */
        .category-filter {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin: 2rem 0;
        }

        .category-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: var(--backdrop-blur);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-normal);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .category-btn:hover,
        .category-btn.active {
            background: rgba(255, 255, 255, 0.9);
            color: var(--gray-800);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Main Content */
        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: var(--backdrop-blur);
            border-radius: 25px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Analytics Section */
        .analytics-section {
            margin-bottom: 3rem;
        }

        .section-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .section-subtitle {
            color: var(--gray-600);
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
            background: var(--gray-50);
            border-radius: 15px;
            padding: 1rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            transition: var(--transition-normal);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-glow);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 500;
        }

        /* Tools Grid */
        .tools-section {
            margin-top: 3rem;
        }

        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .tool-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
            border: 1px solid var(--gray-200);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
        }

        .tool-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: var(--transition-normal);
        }

        .tool-card:hover::before {
            transform: scaleX(1);
        }

        .tool-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: #667eea;
        }

        .tool-card.maintenance {
            opacity: 0.7;
            background: var(--gray-100);
            cursor: not-allowed;
        }

        .tool-card.maintenance:hover {
            transform: none;
        }

        .tool-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .tool-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-gradient);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .tool-info {
            flex: 1;
        }

        .tool-name {
            font-family: 'Poppins', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }

        .tool-category {
            font-size: 0.85rem;
            color: var(--gray-500);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tool-description {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .tool-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .tool-usage {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-500);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .tool-arrow {
            color: var(--gray-400);
            transition: var(--transition-normal);
        }

        .tool-card:hover .tool-arrow {
            color: #667eea;
            transform: translateX(5px);
        }

        .maintenance-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--secondary-gradient);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 3rem;
            color: var(--gray-500);
            display: none;
        }

        .no-results-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 2rem 0;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 3rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .main-content {
                padding: 1.5rem;
                border-radius: 20px;
            }

            .tools-grid {
                grid-template-columns: 1fr;
            }

            .tool-card {
                padding: 1.5rem;
            }

            .category-filter {
                gap: 0.5rem;
            }

            .category-btn {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .tool-header {
                flex-direction: column;
                text-align: center;
            }

            .tool-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: center;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        .stagger-animation {
            animation-delay: calc(var(--index) * 0.1s);
        }

        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body>
    <!-- Background Effects -->
    <div class="bg-decoration"></div>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container">
        <!-- Header -->
        <header class="header">
            <a href="<?php echo $base_url; ?>" class="logo">
                <div class="logo-icon">
                    <i class="material-icons-round">hub</i>
                </div>
                <div class="logo-text">Webtools</div>
            </a>
            
            <h1 class="hero-title">Platform Tools Digital Terlengkap</h1>
            <p class="hero-subtitle">Koleksi 15+ tools canggih untuk mempermudah pekerjaan Anda. Dari AI tools, generator, hingga utility tools - semua dalam satu platform.</p>
        </header>

        <!-- Search Section -->
        <section class="search-section">
            <div class="search-container">
                <input type="text" id="search-input" class="search-input" placeholder="Cari tools yang Anda butuhkan...">
                <i class="material-icons-round search-icon">search</i>
            </div>
        </section>

        <!-- Category Filter -->
        <section class="category-filter">
            <button class="category-btn active" data-category="all">Semua Tools</button>
            <button class="category-btn" data-category="AI Tools">AI Tools</button>
            <button class="category-btn" data-category="Download Tools">Download</button>
            <button class="category-btn" data-category="Generator Tools">Generator</button>
            <button class="category-btn" data-category="Image Tools">Image</button>
            <button class="category-btn" data-category="Utility Tools">Utility</button>
        </section>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Analytics Section -->
            <section class="analytics-section">
                <h2 class="section-title">Statistik Platform</h2>
                <p class="section-subtitle">Lihat performa dan popularitas tools di platform kami</p>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($tools); ?></div>
                        <div class="stat-label">Total Tools</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo array_sum($usage_stats); ?></div>
                        <div class="stat-label">Total Penggunaan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($tools, function($tool) { return ($tool['status'] ?? 'active') === 'active'; })); ?></div>
                        <div class="stat-label">Tools Aktif</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($categories); ?></div>
                        <div class="stat-label">Kategori</div>
                    </div>
                </div>

                <div class="chart-container">
                    <canvas id="usage-chart"></canvas>
                </div>
            </section>

            <!-- Tools Section -->
            <section class="tools-section">
                <h2 class="section-title">Jelajahi Tools</h2>
                <p class="section-subtitle">Pilih tools yang sesuai dengan kebutuhan Anda</p>

                <div class="tools-grid" id="tools-grid">
                    <?php if (!empty($tools)): ?>
                        <?php foreach ($tools as $index => $tool): ?>
                            <?php
                                $is_maintenance = isset($tool['status']) && $tool['status'] === 'maintenance';
                                $tool_url = $is_maintenance ? '#' : ($base_url ?? '') . 'tools/' . htmlspecialchars($tool['slug'] ?? '') . '/';
                                $tool_name = htmlspecialchars($tool['name'] ?? 'Nama Tool');
                                $tool_description = htmlspecialchars($tool['description'] ?? 'Deskripsi tidak tersedia.');
                                $usage_count = $tool['usage_count'] ?? 0;
                                $tool_slug = $tool['slug'] ?? '';
                                
                                // Determine category
                                $tool_category = 'Other';
                                foreach ($categories as $category => $slugs) {
                                    if (in_array($tool_slug, $slugs)) {
                                        $tool_category = $category;
                                        break;
                                    }
                                }
                                
                                $icon_class = $tool['icon'] ?? 'fas fa-tools';
                            ?>

                            <?php if ($is_maintenance): ?>
                                <div class="tool-card maintenance fade-in-up stagger-animation" 
                                     style="--index: <?php echo $index; ?>"
                                     data-name="<?php echo strtolower($tool_name); ?>"
                                     data-description="<?php echo strtolower($tool_description); ?>"
                                     data-category="<?php echo $tool_category; ?>">
                                    <div class="maintenance-badge">Maintenance</div>
                                    <div class="tool-header">
                                        <div class="tool-icon">
                                            <i class="<?php echo $icon_class; ?>"></i>
                                        </div>
                                        <div class="tool-info">
                                            <h3 class="tool-name"><?php echo $tool_name; ?></h3>
                                            <div class="tool-category"><?php echo $tool_category; ?></div>
                                        </div>
                                    </div>
                                    <p class="tool-description"><?php echo $tool_description; ?></p>
                                    <div class="tool-footer">
                                        <div class="tool-usage">
                                            <i class="material-icons-round">trending_up</i>
                                            <span><?php echo number_format($usage_count); ?> pengguna</span>
                                        </div>
                                        <div class="tool-arrow">
                                            <i class="material-icons-round">build</i>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <a href="<?php echo $tool_url; ?>" class="tool-card fade-in-up stagger-animation" 
                                   style="--index: <?php echo $index; ?>"
                                   data-name="<?php echo strtolower($tool_name); ?>"
                                   data-description="<?php echo strtolower($tool_description); ?>"
                                   data-category="<?php echo $tool_category; ?>">
                                    <div class="tool-header">
                                        <div class="tool-icon">
                                            <i class="<?php echo $icon_class; ?>"></i>
                                        </div>
                                        <div class="tool-info">
                                            <h3 class="tool-name"><?php echo $tool_name; ?></h3>
                                            <div class="tool-category"><?php echo $tool_category; ?></div>
                                        </div>
                                    </div>
                                    <p class="tool-description"><?php echo $tool_description; ?></p>
                                    <div class="tool-footer">
                                        <div class="tool-usage">
                                            <i class="material-icons-round">trending_up</i>
                                            <span><?php echo number_format($usage_count); ?> pengguna</span>
                                        </div>
                                        <div class="tool-arrow">
                                            <i class="material-icons-round">arrow_forward</i>
                                        </div>
                                    </div>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- No Results -->
                <div class="no-results" id="no-results">
                    <div class="no-results-icon">
                        <i class="material-icons-round">search_off</i>
                    </div>
                    <h3>Tidak ada tools yang ditemukan</h3>
                    <p>Coba ubah kata kunci pencarian atau pilih kategori lain</p>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; <?php echo date("Y"); ?> Webtools Directory. Dibuat dengan ❤️ untuk produktivitas Anda.</p>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart initialization
            const chartCanvas = document.getElementById('usage-chart');
            if (chartCanvas) {
                const ctx = chartCanvas.getContext('2d');
                const toolNames = <?php echo $top_tool_names; ?>;
                const toolUsage = <?php echo $top_tool_usage; ?>;

                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(102, 126, 234, 0.8)');
                gradient.addColorStop(1, 'rgba(118, 75, 162, 0.8)');

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: toolNames,
                        datasets: [{
                            label: 'Jumlah Penggunaan',
                            data: toolUsage,
                            backgroundColor: gradient,
                            borderColor: '#667eea',
                            borderWidth: 2,
                            borderRadius: 10,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: 'white',
                                bodyColor: 'white',
                                borderColor: '#667eea',
                                borderWidth: 1,
                                cornerRadius: 10,
                                padding: 12
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0, 0, 0, 0.1)' },
                                ticks: { color: '#64748b', font: { family: 'Inter' } }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: '#64748b', font: { family: 'Inter' } }
                            }
                        }
                    }
                });
            }

            // Search functionality
            const searchInput = document.getElementById('search-input');
            const toolsGrid = document.getElementById('tools-grid');
            const noResults = document.getElementById('no-results');
            const toolCards = document.querySelectorAll('.tool-card');

            function performSearch() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const activeCategory = document.querySelector('.category-btn.active').dataset.category;
                let visibleCount = 0;

                toolCards.forEach(card => {
                    const name = card.dataset.name || '';
                    const description = card.dataset.description || '';
                    const category = card.dataset.category || '';
                    
                    const matchesSearch = name.includes(searchTerm) || description.includes(searchTerm);
                    const matchesCategory = activeCategory === 'all' || category === activeCategory;
                    
                    if (matchesSearch && matchesCategory) {
                        card.style.display = 'block';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (visibleCount === 0) {
                    noResults.style.display = 'block';
                } else {
                    noResults.style.display = 'none';
                }
            }

            searchInput.addEventListener('input', performSearch);

            // Category filter
            const categoryBtns = document.querySelectorAll('.category-btn');
            categoryBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    categoryBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    performSearch();
                });
            });

            // Tool usage tracking
            const toolSlugMeta = document.querySelector('meta[name="tool-slug-stats"]');
            if (toolSlugMeta) {
                const currentToolSlug = toolSlugMeta.getAttribute('content');
                if (currentToolSlug) {
                    fetch('/track_tool_usage.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ tool_slug: currentToolSlug })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            console.log('Tool usage tracked:', currentToolSlug);
                        }
                    })
                    .catch(error => {
                        console.error('Error tracking tool usage:', error);
                    });
                }
            }

            // Smooth animations for tool cards
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });

            toolCards.forEach(card => {
                observer.observe(card);
            });

            // Loading state for tool links
            const toolLinks = document.querySelectorAll('a.tool-card');
            toolLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (!this.classList.contains('maintenance')) {
                        this.classList.add('loading');
                        
                        // Remove loading class after navigation
                        setTimeout(() => {
                            this.classList.remove('loading');
                        }, 2000);
                    }
                });
            });
        });
    </script>
</body>
</html>