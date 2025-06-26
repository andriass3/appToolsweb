<?php
session_start();
// Base URL untuk path aset yang konsisten.
$base_url = '/';

// --- LOGIKA BARU: MENGAMBIL SLUG DARI URL ---
$page_slug = ''; // Inisialisasi variabel slug
// Ambil path URL tanpa query string
$request_uri = strtok($_SERVER['REQUEST_URI'], '?');

// Gunakan regex untuk mencocokkan pola /tools/slug-tool/ dan mengambil slug-nya
if (preg_match('/\/tools\/([a-zA-Z0-9_-]+)\/?$/', $request_uri, $matches)) {
    // Jika cocok, $matches[1] akan berisi slug-nya
    $page_slug = $matches[1];
}
// --- AKHIR LOGIKA BARU ---
?>
<!DOCTYPE html>
<html lang="id" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - Webtools Directory' : 'Webtools Directory'; ?></title>
    
    <?php // Meta tag ini hanya akan muncul jika $page_slug berhasil ditemukan dari URL
    if (!empty($page_slug)): ?>
    <meta name="tool-slug-stats" content="<?php echo htmlspecialchars($page_slug); ?>">
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #5a67d8;
            --secondary-color: #764ba2;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --bg-light: #f7fafc;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius: 12px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Google Sans', sans-serif;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* Enhanced Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
            text-decoration: none;
            transition: var(--transition);
        }

        .navbar-brand:hover {
            color: var(--primary-dark) !important;
            transform: translateY(-1px);
        }

        .navbar-brand .brand-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .navbar-brand .brand-icon {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            transition: var(--transition);
        }

        .navbar-brand:hover .brand-icon {
            transform: rotate(5deg) scale(1.05);
        }

        .navbar-brand .brand-text {
            font-family: 'Google Sans', sans-serif;
            font-weight: 700;
            font-size: 1.4rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            color: var(--text-primary) !important;
            padding: 0.75rem 1rem !important;
            border-radius: 8px;
            transition: var(--transition);
            position: relative;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
            background: rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .nav-link i {
            font-size: 1.1rem;
        }

        /* Container Enhancement */
        .container {
            max-width: 1200px;
        }

        main {
            min-height: calc(100vh - 200px);
        }

        /* Enhanced Footer */
        .footer {
            background: white;
            padding: 2rem 0;
            margin-top: 4rem;
            border-top: 1px solid var(--border-color);
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-link {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: var(--transition);
        }

        .footer-link:hover {
            color: var(--primary-color);
            background: rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .footer-copyright {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Enhanced Back to Top */
        .back-to-top-btn {
            display: none;
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            transition: var(--transition);
            z-index: 1000;
        }

        .back-to-top-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar-brand .brand-text {
                font-size: 1.2rem;
            }
            
            .navbar-brand .brand-icon {
                width: 40px;
                height: 40px;
            }
            
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }

        /* Loading Animation */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .loading {
            animation: pulse 2s infinite;
        }

        /* Smooth Scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Focus States */
        *:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        /* Selection Color */
        ::selection {
            background: rgba(102, 126, 234, 0.2);
            color: var(--text-primary);
        }
    </style>
</head>
<body class="d-flex flex-column h-100">

<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $base_url; ?>">
            <div class="brand-container">
                <div class="brand-icon">
                    <i class="material-icons">build_circle</i>
                </div>
                <span class="brand-text">Webtools Directory</span>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_url; ?>">
                        <i class="material-icons">home</i>
                        <span>Home</span>
                    </a>
                </li>
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>admin/dashboard.php">
                           <i class="material-icons">dashboard</i>
                           <span>Admin</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1">