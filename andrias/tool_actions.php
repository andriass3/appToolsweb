<?php
// tool_actions.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth.php'; // Pastikan admin login

$tools_file = '../tools.json'; // Path ke file JSON dari folder admin

// Fungsi untuk membaca tools
function get_all_tools() {
    global $tools_file;
    if (!file_exists($tools_file)) {
        return [];
    }
    $json_data = file_get_contents($tools_file);
    return json_decode($json_data, true) ?: [];
}

// Fungsi untuk menyimpan tools
function save_tools($tools_array) {
    global $tools_file;
    // Pastikan direktori tools.json writable
    if (!is_writable(dirname($tools_file)) || (file_exists($tools_file) && !is_writable($tools_file))) {
         header('Location: dashboard.php?error=Error: File tools.json atau direktorinya tidak writable.');
         exit;
    }
    $json_data = json_encode($tools_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($tools_file, $json_data) === false) {
        header('Location: dashboard.php?error=Gagal menyimpan data tools.');
        exit;
    }
}

// Fungsi untuk membuat slug
function create_slug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string); // Hapus karakter non-alfanumerik kecuali spasi dan strip
    $string = preg_replace('/[\s-]+/', '-', $string); // Ganti spasi dan strip berulang dengan satu strip
    $string = trim($string, '-'); // Hapus strip di awal dan akhir
    return $string;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $tools = get_all_tools();

    switch ($action) {
        case 'add_tool':
            $name = trim($_POST['tool_name']);
            $slug_input = trim($_POST['tool_slug']);
            $icon = trim($_POST['tool_icon']);
            $description = trim($_POST['tool_description']);
            $status = $_POST['tool_status'];

            if (empty($name) || empty($slug_input) || empty($icon) || empty($description)) {
                header('Location: dashboard.php?error=Semua field wajib diisi.');
                exit;
            }
            
            // Validasi slug
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug_input)) {
                header('Location: dashboard.php?error=Format slug tidak valid. Gunakan huruf kecil, angka, dan tanda hubung.');
                exit;
            }

            // Cek apakah slug sudah ada
            foreach ($tools as $existing_tool) {
                if ($existing_tool['slug'] === $slug_input) {
                    header('Location: dashboard.php?error=Slug sudah digunakan. Harap pilih slug lain.');
                    exit;
                }
            }

            $new_tool = [
                'id' => uniqid('tool_'), // ID unik untuk setiap tool
                'name' => $name,
                'slug' => $slug_input,
                'icon' => $icon,
                'description' => $description,
                'status' => $status
            ];
            $tools[] = $new_tool;
            save_tools($tools);

            // Buat folder untuk tool baru
            $tool_dir = "../tools/" . $slug_input;
            if (!file_exists($tool_dir)) {
                if (mkdir($tool_dir, 0755, true)) {
                    // Buat file index.php dasar di dalam folder tool
                    $default_tool_content = "<?php \n\$page_title = \"" . htmlspecialchars($name) . "\";\n\$path_prefix = '../../'; // Path relatif dari tools/slug/ ke root\ninclude \$path_prefix . 'header.php'; \n?>\n\n<div class='tool-page-container'>\n    <h1><i class='" . htmlspecialchars($icon) . " me-2'></i><?php echo \$page_title; ?></h1>\n    <p>" . htmlspecialchars($description) . "</p>\n    <hr>\n    <p class='text-muted'>Tool \"<?php echo \$page_title; ?>\" sedang dalam pengembangan.</p>\n    \n\n</div>\n\n<?php include \$path_prefix . 'footer.php'; ?>";
                    file_put_contents($tool_dir . "/index.php", $default_tool_content);
                     header('Location: dashboard.php?message=Tool berhasil ditambahkan dan folder tool dibuat.');
                } else {
                     header('Location: dashboard.php?message=Tool berhasil ditambahkan, namun gagal membuat folder tool otomatis. Buat manual: ' . $tool_dir);
                }
            } else {
                 header('Location: dashboard.php?message=Tool berhasil ditambahkan. Folder tool sudah ada.');
            }
            exit;

        case 'edit_tool':
            $tool_id = $_POST['tool_id'];
            $name = trim($_POST['tool_name']);
            // Slug tidak diubah saat edit
            $icon = trim($_POST['tool_icon']);
            $description = trim($_POST['tool_description']);
            $status = $_POST['tool_status'];

            if (empty($name) || empty($icon) || empty($description)) {
                header('Location: dashboard.php?error=Semua field wajib diisi saat edit.');
                exit;
            }

            $updated = false;
            foreach ($tools as $key => $tool) {
                if ($tool['id'] === $tool_id) {
                    $tools[$key]['name'] = $name;
                    $tools[$key]['icon'] = $icon;
                    $tools[$key]['description'] = $description;
                    $tools[$key]['status'] = $status;
                    $updated = true;
                    break;
                }
            }

            if ($updated) {
                save_tools($tools);
                header('Location: dashboard.php?message=Tool berhasil diperbarui.');
            } else {
                header('Location: dashboard.php?error=Tool tidak ditemukan untuk diedit.');
            }
            exit;

        case 'delete_tool':
            $tool_id = $_POST['tool_id'];
            $tool_slug_to_delete = null;
            $tools_filtered = [];
            foreach ($tools as $tool) {
                if ($tool['id'] === $tool_id) {
                    $tool_slug_to_delete = $tool['slug']; // Simpan slug untuk pesan
                    // Jangan tambahkan tool ini ke array baru (efeknya menghapus)
                } else {
                    $tools_filtered[] = $tool;
                }
            }

            if (count($tools) !== count($tools_filtered)) { // Jika ada tool yang dihapus
                save_tools($tools_filtered);
                $message = "Tool berhasil dihapus dari daftar.";
                if ($tool_slug_to_delete) {
                     $message .= " Ingat untuk menghapus folder 'tools/" . htmlspecialchars($tool_slug_to_delete) . "/' secara manual jika ada.";
                }
                header('Location: dashboard.php?message=' . urlencode($message));
            } else {
                header('Location: dashboard.php?error=Tool tidak ditemukan untuk dihapus.');
            }
            exit;

        case 'toggle_maintenance':
            $tool_id = $_POST['tool_id'];
            $toggled = false;
            foreach ($tools as $key => $tool) {
                if ($tool['id'] === $tool_id) {
                    $tools[$key]['status'] = ($tool['status'] === 'active') ? 'maintenance' : 'active';
                    $toggled = true;
                    break;
                }
            }

            if ($toggled) {
                save_tools($tools);
                header('Location: dashboard.php?message=Status tool berhasil diubah.');
            } else {
                header('Location: dashboard.php?error=Tool tidak ditemukan untuk diubah statusnya.');
            }
            exit;
            
        default:
            header('Location: dashboard.php?error=Aksi tidak valid.');
            exit;
    }
} else {
    header('Location: dashboard.php'); // Redirect jika akses langsung atau metode salah
    exit;
}
?>