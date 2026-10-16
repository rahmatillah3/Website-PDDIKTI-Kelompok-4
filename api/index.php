<?php
// ======================================================
// ROUTER & SERVERLESS FUNCTION UNTUK VERCEL
// Politeknik Negeri Lhokseumawe - PDDIKTI Dashboard
// ======================================================

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// Fungsi bantu resolusi jalur file data (dengan dukungan Environment Variables)
if (!function_exists('resolve_data_file')) {
    function resolve_data_file($filename, $envVar = null)
    {
        if ($envVar) {
            $envPath = getenv($envVar);
            if ($envPath && file_exists($envPath)) {
                return $envPath;
            }
        }

        $candidates = [
            dirname(__DIR__) . '/' . $filename,
            __DIR__ . '/' . $filename,
            dirname(__DIR__) . '/api/' . $filename,
            __DIR__ . '/../' . $filename,
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return dirname(__DIR__) . '/' . $filename;
    }
}

// Tentukan direktori root proyek
$rootDir = dirname(__DIR__);

// Ubah working directory ke root agar include dan file_get_contents konsisten
chdir($rootDir);

// Dapatkan URI path dari request
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
$filename = basename($path);

// Sinkronisasi query parameters ke $_GET
$queryString = parse_url($requestUri, PHP_URL_QUERY);
if ($queryString) {
    parse_str($queryString, $queryParams);
    $_GET = array_merge($queryParams, $_GET);
}

// Normalisasi target file
if ($filename === '' || $filename === 'api-pddikti' || $filename === 'api' || $filename === 'index' || $filename === 'index.php') {
    $target = 'index.php';
} else {
    $target = $filename;
    // Dukungan Clean URL (misal: /historis -> historis.php)
    if (strpos($target, '.') === false) {
        if (file_exists($rootDir . '/' . $target . '.php')) {
            $target .= '.php';
        }
    }
}

// Pemetaan Rute (Routing)
switch ($target) {
    case 'historis.php':
        require $rootDir . '/historis.php';
        break;

    case 'unduh_historis.php':
        require $rootDir . '/unduh_historis.php';
        break;

    case 'ambil_data.php':
        require $rootDir . '/ambil_data.php';
        break;

    case 'ambil_historis.php':
        require $rootDir . '/ambil_historis.php';
        break;

    case 'cek_historis.php':
        require $rootDir . '/cek_historis.php';
        break;

    case 'data_prodi.json':
    case 'data_historis.json':
        $envKey = ($target === 'data_prodi.json') ? 'DATA_PRODI_PATH' : 'DATA_HISTORIS_PATH';
        $jsonFile = resolve_data_file($target, $envKey);
        if (file_exists($jsonFile)) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: public, max-age=3600');
            readfile($jsonFile);
            exit;
        }
        http_response_code(404);
        echo json_encode(['error' => "File {$target} tidak ditemukan"]);
        break;

    default:
        // Cek jika mengakses file statis yang ada di root
        $staticFile = $rootDir . '/' . $target;
        if (file_exists($staticFile) && !is_dir($staticFile)) {
            $ext = strtolower(pathinfo($staticFile, PATHINFO_EXTENSION));
            $mimeTypes = [
                'json' => 'application/json',
                'css'  => 'text/css',
                'js'   => 'application/javascript',
                'png'  => 'image/png',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'svg'  => 'image/svg+xml',
                'ico'  => 'image/x-icon',
            ];
            if (isset($mimeTypes[$ext])) {
                header('Content-Type: ' . $mimeTypes[$ext]);
            }
            readfile($staticFile);
            exit;
        }

        // Tampilkan halaman dashboard utama
        require $rootDir . '/index.php';
        break;
}