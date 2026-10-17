<?php
// ======================================================
// ROUTER & SERVERLESS FUNCTION UNTUK VERCEL
// Politeknik Negeri Lhokseumawe - PDDIKTI Dashboard
// ======================================================

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// ------------------------------------------------------
// 1. RESOLUSI DIREKTORI ROOT (SERVERLESS ENVIRONMENT)
// ------------------------------------------------------
$rootDir = dirname(__DIR__);
$apiDir  = __DIR__;

// Pastikan current working directory mengarah ke root proyek
if (is_dir($rootDir)) {
    chdir($rootDir);
}

// ------------------------------------------------------
// 2. PARSE URI & SINKRONISASI QUERY PARAMETERS
// ------------------------------------------------------
$requestUri  = $_SERVER['REQUEST_URI'] ?? '/';
$urlParts    = parse_url($requestUri);
$rawPath     = $urlParts['path'] ?? '/';
$queryString = $urlParts['query'] ?? '';

// Sinkronkan Query Parameters ke $_GET jika ada
if ($queryString !== '') {
    parse_str($queryString, $queryParams);
    $_GET = array_merge($queryParams, $_GET);
}

// Bersihkan path dari leading slash dan prefix folder lokal/api
$cleanPath = ltrim($rawPath, '/');
$cleanPath = preg_replace('#^api(-pddikti)?/#', '', $cleanPath);
$cleanPath = ltrim($cleanPath, '/');

// Dapatkan nama file dan ekstensi
$filename = basename($cleanPath);
$fileExt  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// ------------------------------------------------------
// 3. SERVIS FILE STATIS & CONTENT-TYPE (CSS, JS, JSON, GAMBAR, FONT)
// ------------------------------------------------------
$mimeTypes = [
    'css'   => 'text/css; charset=utf-8',
    'js'    => 'application/javascript; charset=utf-8',
    'mjs'   => 'application/javascript; charset=utf-8',
    'json'  => 'application/json; charset=utf-8',
    'png'   => 'image/png',
    'jpg'   => 'image/jpeg',
    'jpeg'  => 'image/jpeg',
    'gif'   => 'image/gif',
    'svg'   => 'image/svg+xml',
    'ico'   => 'image/x-icon',
    'webp'  => 'image/webp',
    'woff'  => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf'   => 'font/ttf',
    'otf'   => 'font/otf',
    'eot'   => 'application/vnd.ms-fontobject',
    'txt'   => 'text/plain; charset=utf-8',
    'csv'   => 'text/csv; charset=utf-8',
    'map'   => 'application/json; charset=utf-8',
    'pdf'   => 'application/pdf',
    'xml'   => 'application/xml; charset=utf-8',
];

// Jika request meminta file statis (bukan file PHP dan memiliki ekstensi yang dikenali)
if ($fileExt !== '' && $fileExt !== 'php' && isset($mimeTypes[$fileExt])) {
    $staticCandidates = [
        $rootDir . '/' . $cleanPath,
        $apiDir . '/' . $cleanPath,
        $rootDir . '/' . $filename,
        $apiDir . '/' . $filename,
    ];

    foreach ($staticCandidates as $staticFile) {
        if (file_exists($staticFile) && is_file($staticFile)) {
            header('Content-Type: ' . $mimeTypes[$fileExt]);
            header('Content-Length: ' . filesize($staticFile));

            if ($fileExt === 'json') {
                header('Cache-Control: public, max-age=3600');
            } else {
                header('Cache-Control: public, max-age=86400');
            }

            readfile($staticFile);
            exit;
        }
    }

    // Jika asset statis diminta tapi tidak ditemukan
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "404 Not Found: Asset '{$filename}' tidak ditemukan.";
    exit;
}

// ------------------------------------------------------
// 4. PENENTUAN TARGET SKRIP PHP (ROUTER UTAMA)
// ------------------------------------------------------
$targetScript = 'index.php';

if ($cleanPath === '' || $cleanPath === 'index' || $cleanPath === 'index.php') {
    $targetScript = 'index.php';
} elseif ($cleanPath === 'historis' || $cleanPath === 'historis.php') {
    $targetScript = 'historis.php';
} elseif ($cleanPath === 'unduh_historis' || $cleanPath === 'unduh_historis.php') {
    $targetScript = 'unduh_historis.php';
} elseif ($cleanPath === 'ambil_historis' || $cleanPath === 'ambil_historis.php') {
    $targetScript = 'ambil_historis.php';
} elseif ($cleanPath === 'cek_historis' || $cleanPath === 'cek_historis.php') {
    $targetScript = 'cek_historis.php';
} elseif ($cleanPath === 'ambil_data' || $cleanPath === 'ambil_data.php') {
    $targetScript = 'ambil_data.php';
} else {
    // Deteksi jika ada file PHP lain yang diminta
    $baseNameOnly = preg_replace('/\.php$/', '', $filename);
    if (file_exists($rootDir . '/' . $baseNameOnly . '.php')) {
        $targetScript = $baseNameOnly . '.php';
    } elseif (file_exists($apiDir . '/' . $baseNameOnly . '.php')) {
        $targetScript = $baseNameOnly . '.php';
    } else {
        $targetScript = 'index.php';
    }
}

// ------------------------------------------------------
// 5. PENYESUAIAN $_SERVER['SCRIPT_NAME'] & $_SERVER['REQUEST_URI']
// ------------------------------------------------------
// Di Vercel Serverless Function, SCRIPT_NAME secara default adalah '/api/index.php'.
// Kita sesuaikan agar skrip utama dan tautan relatif membaca root path dengan tepat.
$_SERVER['SCRIPT_NAME'] = '/' . $targetScript;
$_SERVER['PHP_SELF']    = '/' . $targetScript;

// Normalisasi REQUEST_URI jika sebelumnya memiliki prefix /api/
if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0) {
    $normalizedUri = preg_replace('#^/api#', '', $_SERVER['REQUEST_URI']);
    $_SERVER['REQUEST_URI'] = ($normalizedUri !== '' && $normalizedUri[0] === '/') ? $normalizedUri : '/' . $normalizedUri;
}

// ------------------------------------------------------
// 6. RESOLUSI PATH DAN EKSEKUSI SKRIP (REQUIRE)
// ------------------------------------------------------
$candidateScripts = [
    $rootDir . '/' . $targetScript,
    $apiDir . '/' . $targetScript,
];

$resolvedScript = null;
foreach ($candidateScripts as $cand) {
    if (file_exists($cand) && is_file($cand)) {
        $resolvedScript = $cand;
        break;
    }
}

if ($resolvedScript !== null) {
    $_SERVER['SCRIPT_FILENAME'] = $resolvedScript;
    require $resolvedScript;
    exit;
}

// Fallback jika skrip target tidak ditemukan, panggil index.php
$indexFallback = $rootDir . '/index.php';
if (file_exists($indexFallback) && is_file($indexFallback)) {
    $_SERVER['SCRIPT_FILENAME'] = $indexFallback;
    $_SERVER['SCRIPT_NAME']     = '/index.php';
    $_SERVER['PHP_SELF']        = '/index.php';
    require $indexFallback;
    exit;
}

http_response_code(500);
header('Content-Type: text/plain; charset=utf-8');
echo "Internal Server Error: Skrip target '{$targetScript}' tidak dapat ditemukan.";
exit;
