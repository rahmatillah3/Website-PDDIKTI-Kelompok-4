<?php

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

$idPt = getenv('PDDIKTI_PT_ID') ?: 'B7OPrCaLnSM1jPcSLp7xV7tkDT1uIOvMpNlCjwgA2bq_SvK93yHFHs1lZQmipdXooHANmg==';
$url = "https://pddikti.kemdiktisaintek.go.id/api/pt/prodi/{$idPt}/20251";

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json, text/plain, */*",
        "Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7",
        "Referer: https://pddikti.kemdiktisaintek.go.id/",
        "Origin: https://pddikti.kemdiktisaintek.go.id",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/154.0.0.0 Safari/537.36"
    ]
]);

$response = curl_exec($ch);

if ($response === false) {
    die("Gagal mengambil data: " . curl_error($ch));
}

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($http_code < 200 || $http_code >= 300) {
    die("HTTP Error: " . $http_code);
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("JSON Error: " . json_last_error_msg());
}

if (!isset($data["data"]) || !is_array($data["data"])) {
    die("Struktur data PDDIKTI tidak sesuai.");
}

$prodi = $data["data"];

$file = resolve_data_file('data_prodi.json', 'DATA_PRODI_PATH');

// Periksa apakah sistem berkas dapat ditulis (lingkungan Vercel bersifat read-only)
$targetDir = dirname($file);
if (!is_writable($targetDir) && (!file_exists($file) || !is_writable($file))) {
    echo "<h2>Berhasil Mengambil Data dari PDDIKTI!</h2>";
    echo "<p>Jumlah program studi: <strong>" . count($prodi) . "</strong></p>";
    echo "<div style='color:#854d0e; background:#fef9c3; padding:15px; border-radius:10px; border:1px solid #fde047; margin-top:15px;'>";
    echo "<strong>Catatan untuk Vercel Serverless:</strong><br>";
    echo "Sistem berkas di lingkungan serverless Vercel bersifat <em>read-only</em>, sehingga file <code>data_prodi.json</code> tidak dapat langsung ditimpa di cloud.<br>";
    echo "Untuk memperbarui data di Vercel, jalankan skrip ini secara lokal di XAMPP lalu lakukan <code>git commit</code> dan <code>git push</code> ke GitHub.";
    echo "</div>";
    exit;
}

$hasil = file_put_contents(
    $file,
    json_encode(
        $prodi,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    )
);

if ($hasil === false) {
    die("Gagal menyimpan data_prodi.json");
}

// Salin juga ke folder api/ jika folder api ada dan dapat ditulis
$apiFile = dirname($file) . '/api/data_prodi.json';
if (file_exists(dirname($apiFile)) && is_writable(dirname($apiFile))) {
    @copy($file, $apiFile);
}

echo "<h2>Berhasil!</h2>";
echo "<p>Data program studi berhasil disimpan.</p>";
echo "<p>Jumlah program studi: <strong>" . count($prodi) . "</strong></p>";
echo "<p>File: <strong>" . htmlspecialchars($file) . "</strong></p>";