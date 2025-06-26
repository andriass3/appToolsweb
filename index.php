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

// --- PERSIAPAN DATA UNTUK CHART ---
$top_tools = array_slice($tools, 0, 5);
$top_tool_names = json_encode(array_column($top_tools, 'name'));
$top_tool_usage = json_encode(array_column($top_tools, 'usage_count'));

$page_title = "Webtools Directory";
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

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Bootstrap Grid & Modal -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-utilities.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /*
        ==================================================
        UX OVERHAUL: SUPER SUPER CANGGIH - TEMA TERANG AI
        ==================================================
        */
        
        :root {
            /* Palet Warna Tema Terang AI */
            --ai-blue: #2563EB;
            --ai-purple: #7C3AED;
            --bg-primary: #F9FAFB;
            --bg-secondary: #FFFFFF;
            --text-dark: #1F2937;
            --text-gray: #6B7280;
            --border-color: #E5E7EB;
            
            /* Konfigurasi Universal */
            --border-radius: 12px;
            --transition: all 0.3s ease;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        /* --- Global & Typography --- */
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            color: var(--text-dark);
        }

        .section-title {
            font-size: clamp(2rem, 5vw, 2.75rem); margin-bottom: 1rem;
            text-align: center;
        }

        .section-subtitle {
            font-size: clamp(1rem, 2.5vw, 1.15rem); color: var(--text-gray);
            max-width: 650px; margin: 0 auto 3rem auto;
            text-align: center;
        }

        #background-canvas {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: -1;
        }
        
        /* --- Navbar --- */
        .navbar {
            padding: 1rem 0; background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px); border-bottom: 1px solid var(--border-color);
            position: sticky; top: 0; z-index: 1020;
        }
        
        .navbar-brand {
            display: flex; align-items: center; gap: 0.75rem;
            font-family: 'Space Grotesk', sans-serif; font-size: 1.5rem; font-weight: 700;
            color: var(--text-dark); text-decoration: none;
        }

        .brand-icon {
            display: flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; color: white;
            background: linear-gradient(135deg, var(--ai-blue), var(--ai-purple));
            border-radius: 8px;
        }
        
        /* --- Hero Section --- */
        .hero-section {
            padding: 8rem 0; text-align: center;
            position: relative; overflow: hidden;
        }

        .hero-title {
            font-size: clamp(3.2rem, 7vw, 5rem); margin-bottom: 1.5rem;
            color: var(--text-dark); position: relative;
        }
        .hero-title .highlight {
            background: linear-gradient(90deg, var(--ai-blue), var(--ai-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* --- Search Section --- */
        .search-input-wrapper {
            position: relative; max-width: 700px; margin: auto;
        }
        .search-input {
            width: 100%; padding: 1.25rem 1.5rem; font-size: 1.1rem;
            border: 1px solid var(--border-color); border-radius: var(--border-radius);
            background-color: var(--bg-secondary); color: var(--text-dark);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }
        .search-input::placeholder { color: var(--text-gray); }
        .search-input:focus {
            outline: none; border-color: var(--ai-blue);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
        }

        /* --- UI Elements --- */
        .ui-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 2.5rem;
            margin-bottom: 4rem;
            box-shadow: var(--shadow-md);
        }
        .chart-wrapper {
            max-width: 800px; margin: 2rem auto 0 auto;
        }
        .chart-container {
            position: relative; height: 350px; width: 100%;
        }

        /* --- Tools List Layout --- */
        .tools-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem; /* Jarak vertikal antar item */
        }

        .tool-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            border: 1px solid var(--border-color);
            background-color: var(--bg-secondary);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        a.tool-item:hover { 
            transform: translateY(-4px); 
            border-color: var(--ai-blue);
            box-shadow: var(--shadow-md);
        }
        .tool-item.maintenance { 
            opacity: 0.7; 
            cursor: not-allowed;
            background-color: var(--bg-primary);
        }

        .tool-info { flex-grow: 1; z-index: 1; }
        .tool-name { font-size: 1.2rem; margin: 0 0 0.25rem 0; color: var(--text-dark); }
        .tool-description { font-size: 0.95rem; color: var(--text-gray); margin: 0; max-width: 90%; }
        
        .tool-stats {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-shrink: 0;
            padding-left: 1.5rem;
        }
        .tool-usage { font-size: 0.9rem; font-weight: 500; color: var(--text-gray); }
        .maintenance-badge { font-size: 0.9rem; font-weight: 500; color: #D97706; }
        .launch-indicator { color: var(--text-gray); opacity: 0; transition: var(--transition); }
        a.tool-item:hover .launch-indicator { opacity: 1; color: var(--ai-blue); }
        
        /* --- Footer --- */
        .site-footer {
            padding: 4rem 0; margin-top: 4rem;
            background-color: var(--bg-secondary);
            text-align: center; color: var(--text-gray);
            border-top: 1px solid var(--border-color);
        }
        
        .fade-in-up { opacity: 0; transform: translateY(20px); transition: opacity 0.6s ease-out, transform 0.6s ease-out; }
        .fade-in-up.visible { opacity: 1; transform: translateY(0); }

    </style>
</head>
<body>
<canvas id="background-canvas"></canvas>

<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $base_url; ?>">
            <div class="brand-icon"><i class="material-icons">hub</i></div>
            <span>Webtools Directory</span>
        </a>
    </div>
</nav>

<main>
    <section class="hero-section">
        <div class="container position-relative">
            <h1 class="hero-title">Akselerasi Alur Kerja dengan <span class="highlight">Intelijen Artifisial</span></h1>
            <p class="section-subtitle">Koleksi tool presisi yang dirancang untuk efisiensi. Temukan, gunakan, dan tingkatkan produktivitas Anda.</p>
            <div class="search-section">
                <div class="search-input-wrapper">
                    <input type="text" id="tool-search-input" class="search-input" placeholder="Cari tool, misalnya: Image Optimizer">
                </div>
            </div>
        </div>
    </section>

    <div class="container py-5 mt-5">
        <div class="ui-container fade-in-up">
            <h2 class="section-title">Analitik Platform</h2>
            <p class="section-subtitle">Visualisasi 5 tools terpopuler yang menjadi andalan para pengguna kami.</p>
            <div class="chart-wrapper">
                <div class="chart-container">
                    <canvas id="platformStatsChart"></canvas>
                </div>
            </div>
        </div>

        <h2 class="section-title mt-5 pt-4">Direktori Tools</h2>
        <p class="section-subtitle">Pilih dari koleksi kami yang terus berkembang untuk kebutuhan spesifik Anda.</p>
        
        <div class="tools-list" id="tools-list">
            <?php if (!empty($tools)): ?>
                <?php foreach ($tools as $tool): ?>
                    <?php
                        $is_maintenance = isset($tool['status']) && $tool['status'] === 'maintenance';
                        $tool_url = $is_maintenance ? '#' : ($base_url ?? '') . 'tools/' . htmlspecialchars($tool['slug'] ?? '') . '/';
                        $tool_name_attr = strtolower(htmlspecialchars($tool['name'] ?? ''));
                        $tool_desc_attr = strtolower(htmlspecialchars($tool['description'] ?? ''));
                        $usage_count = $tool['usage_count'] ?? 0;
                    ?>

                    <?php if ($is_maintenance): ?>
                        <div class="tool-item maintenance fade-in-up" data-tool-name="<?php echo $tool_name_attr; ?>" data-tool-description="<?php echo $tool_desc_attr; ?>">
                            <div class="tool-info">
                                <h3 class="tool-name"><?php echo htmlspecialchars($tool['name'] ?? 'Nama Tool'); ?></h3>
                                <p class="tool-description"><?php echo htmlspecialchars($tool['description'] ?? 'Deskripsi tidak tersedia.'); ?></p>
                            </div>
                            <div class="tool-stats">
                                <span class="maintenance-badge">Maintenance</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo $tool_url; ?>" class="tool-item fade-in-up" data-tool-name="<?php echo $tool_name_attr; ?>" data-tool-description="<?php echo $tool_desc_attr; ?>">
                            <div class="tool-info">
                                <h3 class="tool-name"><?php echo htmlspecialchars($tool['name'] ?? 'Nama Tool'); ?></h3>
                                <p class="tool-description"><?php echo htmlspecialchars($tool['description'] ?? 'Deskripsi tidak tersedia.'); ?></p>
                            </div>
                            <div class="tool-stats">
                                <span class="tool-usage"><?php echo number_format($usage_count); ?> x digunakan</span>
                                <i class="material-icons launch-indicator">arrow_forward</i>
                            </div>
                        </a>
                    <?php endif; ?>

                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?php echo date("Y"); ?> Webtools Directory. Dirancang untuk efisiensi Anda.</p>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Animated Background ---
    const canvas = document.getElementById('background-canvas');
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    let particlesArray;

    class Particle {
        constructor(x, y, directionX, directionY, size, color) {
            this.x = x; this.y = y; this.directionX = directionX;
            this.directionY = directionY; this.size = size; this.color = color;
        }
        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2, false);
            ctx.fillStyle = this.color;
            ctx.fill();
        }
        update() {
            if (this.x > canvas.width || this.x < 0) { this.directionX = -this.directionX; }
            if (this.y > canvas.height || this.y < 0) { this.directionY = -this.directionY; }
            this.x += this.directionX;
            this.y += this.directionY;
            this.draw();
        }
    }

    function connect() {
        let opacityValue = 1;
        for (let a = 0; a < particlesArray.length; a++) {
            for (let b = a; b < particlesArray.length; b++) {
                let distance = ((particlesArray[a].x - particlesArray[b].x) * (particlesArray[a].x - particlesArray[b].x))
                             + ((particlesArray[a].y - particlesArray[b].y) * (particlesArray[a].y - particlesArray[b].y));
                if (distance < (canvas.width/7) * (canvas.height/7)) {
                    opacityValue = 1 - (distance/20000);
                    ctx.strokeStyle = `rgba(107, 114, 128, ${opacityValue})`;
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    ctx.moveTo(particlesArray[a].x, particlesArray[a].y);
                    ctx.lineTo(particlesArray[b].x, particlesArray[b].y);
                    ctx.stroke();
                }
            }
        }
    }

    function init() {
        particlesArray = [];
        let numberOfParticles = (canvas.height * canvas.width) / 9000;
        for (let i = 0; i < numberOfParticles; i++) {
            let size = (Math.random() * 2) + 1;
            let x = (Math.random() * ((innerWidth - size * 2) - (size * 2)) + size * 2);
            let y = (Math.random() * ((innerHeight - size * 2) - (size * 2)) + size * 2);
            let directionX = (Math.random() * 0.4) - 0.2;
            let directionY = (Math.random() * 0.4) - 0.2;
            let color = 'rgba(107, 114, 128, 0.8)';
            particlesArray.push(new Particle(x, y, directionX, directionY, size, color));
        }
    }

    function animate() {
        requestAnimationFrame(animate);
        ctx.clearRect(0,0,innerWidth, innerHeight);
        for (let i = 0; i < particlesArray.length; i++) {
            particlesArray[i].update();
        }
        connect();
    }
    
    init();
    animate();

    window.addEventListener('resize', function(){
        canvas.width = innerWidth;
        canvas.height = innerHeight;
        init();
    });

    // --- Intersection Observer for Animations ---
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.fade-in-up').forEach(el => observer.observe(el));

    // --- Chart.js Initialization ---
    const chartCtx = document.getElementById('platformStatsChart');
    if (chartCtx) {
        const toolNames = <?php echo $top_tool_names; ?>;
        const toolUsage = <?php echo $top_tool_usage; ?>;
        const gradient = chartCtx.getContext('2d').createLinearGradient(0, 0, 0, 350);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.7)');
        gradient.addColorStop(1, 'rgba(124, 58, 237, 0.7)');

        new Chart(chartCtx, {
            type: 'bar',
            data: {
                labels: toolNames,
                datasets: [{
                    label: 'Jumlah Penggunaan',
                    data: toolUsage,
                    backgroundColor: gradient,
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: { grid: { color: 'var(--border-color)' }, ticks: { color: 'var(--text-gray)', font: { family: 'Inter' } } },
                    x: { grid: { display: false }, ticks: { color: 'var(--text-gray)', font: { family: 'Inter' } } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'var(--text-dark)', titleColor: 'var(--bg-secondary)', bodyColor: 'var(--text-gray)',
                        titleFont: { family: 'Space Grotesk', size: 14 }, bodyFont: { family: 'Inter' },
                        padding: 12, cornerRadius: 8
                    }
                }
            }
        });
    }

    // --- Search functionality for list view ---
    const searchInput = document.getElementById('tool-search-input');
    const toolsList = document.getElementById('tools-list');
    if (searchInput && toolsList) {
        const toolItems = document.querySelectorAll('.tool-item');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            toolItems.forEach(item => {
                const name = item.dataset.toolName || '';
                const desc = item.dataset.toolDescription || '';
                if (name.includes(searchTerm) || desc.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});
</script>
</body>
</html>
