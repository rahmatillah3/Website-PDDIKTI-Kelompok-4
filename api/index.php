<?php
// ======================================================
// ROUTER & SERVERLESS FUNCTION UNTUK VERCEL
// Politeknik Negeri Lhokseumawe - PDDIKTI Dashboard
// ======================================================

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$rootDir = dirname(__DIR__);
chdir($rootDir);

// Dapatkan URI & Parse Request
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$urlParts = parse_url($requestUri);
$path = $urlParts['path'] ?? '/';

// Sinkronkan Query Parameters ke $_GET
if (isset($urlParts['query'])) {
    parse_str($urlParts['query'], $queryParams);
    $_GET = array_merge($queryParams, $_GET);
}

// Dapatkan nama file atau endpoint yang dituju
$filename = basename($path);

// Normalisasi target file
if ($filename === '' || $filename === 'api-pddikti' || $filename === 'api' || $filename === 'index' || $filename === 'index.php') {
    $target = 'index.php';
} else {
    $target = $filename;
    // Dukungan Clean URL (misal: /unduh_historis -> unduh_historis.php)
    if (strpos($target, '.') === false) {
        if (file_exists($rootDir . '/' . $target . '.php')) {
            $target .= '.php';
        } elseif (file_exists(__DIR__ . '/' . $target . '.php')) {
            $target .= '.php';
        }
    }
}

// Pemetaan Rute (Routing)
switch ($target) {
    case 'unduh_historis.php':
    case 'unduh_historis':
        if (file_exists($rootDir . '/unduh_historis.php')) {
            require $rootDir . '/unduh_historis.php';
        } elseif (file_exists(__DIR__ . '/unduh_historis.php')) {
            require __DIR__ . '/unduh_historis.php';
        } else {
            http_response_code(404);
            echo "Error: File unduh_historis.php tidak ditemukan.";
        }
        exit;

    case 'historis.php':
    case 'historis':
        if (file_exists($rootDir . '/historis.php')) {
            require $rootDir . '/historis.php';
        } elseif (file_exists(__DIR__ . '/historis.php')) {
            require __DIR__ . '/historis.php';
        } else {
            http_response_code(404);
            echo "Error: File historis.php tidak ditemukan.";
        }
        exit;

    case 'ambil_data.php':
    case 'ambil_data':
        if (file_exists($rootDir . '/ambil_data.php')) {
            require $rootDir . '/ambil_data.php';
        } else {
            require __DIR__ . '/ambil_data.php';
        }
        exit;

    case 'ambil_historis.php':
    case 'ambil_historis':
        if (file_exists($rootDir . '/ambil_historis.php')) {
            require $rootDir . '/ambil_historis.php';
        } else {
            require __DIR__ . '/ambil_historis.php';
        }
        exit;

    case 'cek_historis.php':
    case 'cek_historis':
        if (file_exists($rootDir . '/cek_historis.php')) {
            require $rootDir . '/cek_historis.php';
        } else {
            require __DIR__ . '/cek_historis.php';
        }
        exit;

    case 'data_prodi.json':
    case 'data_historis.json':
        $candidates = [
            $rootDir . '/' . $target,
            __DIR__ . '/' . $target,
        ];
        foreach ($candidates as $f) {
            if (file_exists($f)) {
                header('Content-Type: application/json; charset=utf-8');
                header('Cache-Control: public, max-age=3600');
                readfile($f);
                exit;
            }
        }
        http_response_code(404);
        echo json_encode(['error' => "File {$target} tidak ditemukan"]);
        exit;

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
        exit;
}
