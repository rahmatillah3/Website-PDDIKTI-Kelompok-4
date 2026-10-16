<?php
header('Content-Type: text/plain; charset=utf-8');

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
            __DIR__ . '/' . $filename,
            dirname(__DIR__) . '/' . $filename,
            __DIR__ . '/api/' . $filename,
            dirname(__DIR__) . '/api/' . $filename,
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return __DIR__ . '/' . $filename;
    }
}

$idProdiPT = getenv('PDDIKTI_PT_ID') ?: 'B7OPrCaLnSM1jPcSLp7xV7tkDT1uIOvMpNlCjwgA2bq_SvK93yHFHs1lZQmipdXooHANmg==';

$periode = ['20211', '20221', '20231', '20241', '20251'];
$dataHistoris = [];

echo "MULAI MENGAMBIL DATA HISTORIS PDDIKTI\n\n";

foreach ($periode as $kode) {
    $url = 'https://pddikti.kemdiktisaintek.go.id/api/pt/prodi/'
         . $idProdiPT . '/' . $kode;

    echo "Mengambil periode $kode...\n";

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: Mozilla/5.0',
            'Referer: https://pddikti.kemdiktisaintek.go.id/',
            'Origin: https://pddikti.kemdiktisaintek.go.id'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    // Periksa koneksi.
    if ($response === false) {
        exit(
            "GAGAL: Koneksi periode $kode gagal.\n"
            . "Detail: $error\n"
        );
    }

    // Periksa status HTTP.
    if ($httpCode !== 200) {
        exit(
            "GAGAL: Periode $kode menghasilkan HTTP $httpCode.\n"
            . "Respons: " . substr($response, 0, 500) . "\n"
        );
    }

    // Periksa format JSON.
    $hasil = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        exit(
            "GAGAL: Respons periode $kode bukan JSON yang valid.\n"
            . "Detail: " . json_last_error_msg() . "\n"
        );
    }

    // Periksa struktur data.
    if (!isset($hasil['data']) || !is_array($hasil['data'])) {
        exit(
            "GAGAL: Struktur data periode $kode tidak sesuai.\n"
            . "Periksa respons API dan struktur JSON-nya.\n"
        );
    }

    // Hindari menyimpan respons kosong sebagai data valid.
    if (count($hasil['data']) === 0) {
        exit(
            "GAGAL: Data periode $kode kosong.\n"
            . "File historis lama tidak ditimpa.\n"
        );
    }

    $dataHistoris[$kode] = $hasil['data'];

    echo "BERHASIL: " . count($hasil['data'])
       . " data prodi ditemukan.\n\n";
}

// Pastikan semua periode sudah terkumpul.
if (count($dataHistoris) !== count($periode)) {
    exit("GAGAL: Tidak semua periode berhasil diproses.\n");
}

// Tentukan target file.
$file = resolve_data_file('data_historis.json', 'DATA_HISTORIS_PATH');
$targetDir = dirname($file);

// Periksa apakah direktori dapat ditulis (lingkungan Vercel bersifat read-only)
if (!is_writable($targetDir) && (!file_exists($file) || !is_writable($file))) {
    echo "====================================\n";
    echo "BERHASIL MENGAMBIL SELURUH DATA PDDIKTI!\n";
    echo "====================================\n";
    echo "Semua periode (" . count($dataHistoris) . " periode) berhasil diunduh dari API.\n\n";
    echo "CATATAN UNTUK VERCEL:\n";
    echo "Sistem berkas di lingkungan serverless Vercel bersifat read-only.\n";
    echo "File data_historis.json tidak dapat ditimpa langsung di cloud serverless.\n";
    echo "Untuk memperbarui data historis di Vercel, jalankan skrip ini secara lokal di XAMPP,\n";
    echo "kemudian lakukan git commit dan git push data_historis.json ke GitHub / Vercel.\n";
    exit;
}

// Siapkan JSON.
$json = json_encode(
    $dataHistoris,
    JSON_PRETTY_PRINT
    | JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
);

if ($json === false) {
    exit("GAGAL membuat JSON: " . json_last_error_msg() . "\n");
}

// Simpan sementara terlebih dahulu.
$fileSementara = $file . '.tmp';

if (file_put_contents($fileSementara, $json, LOCK_EX) === false) {
    exit("GAGAL menulis file sementara. Periksa izin folder.\n");
}

// Validasi file sementara.
$uji = json_decode(file_get_contents($fileSementara), true);

if (!is_array($uji) || count($uji) !== count($periode)) {
    @unlink($fileSementara);
    exit("GAGAL: Validasi file sementara tidak berhasil.\n");
}

// Ganti file lama setelah semua pemeriksaan berhasil.
if (!rename($fileSementara, $file)) {
    @unlink($fileSementara);
    exit("GAGAL mengganti data_historis.json.\n");
}

// Salin juga ke folder api/ jika folder api ada dan dapat ditulis
$apiFile = dirname($file) . '/api/data_historis.json';
if (file_exists(dirname($apiFile)) && is_writable(dirname($apiFile))) {
    @copy($file, $apiFile);
}

echo "====================================\n";
echo "BERHASIL! Semua periode telah diambil.\n";
echo "File: " . $file . "\n";
echo "====================================\n";
