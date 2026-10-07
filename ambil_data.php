<?php

$url = "https://pddikti.kemdiktisaintek.go.id/api/pt/prodi/B7OPrCaLnSM1jPcSLp7xV7tkDT1uIOvMpNlCjwgA2bq_SvK93yHFHs1lZQmipdXooHANmg==/20251";

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

$file = __DIR__ . "/data_prodi.json";

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

echo "<h2>Berhasil!</h2>";
echo "<p>Data program studi berhasil disimpan.</p>";
echo "<p>Jumlah program studi: <strong>" . count($prodi) . "</strong></p>";
echo "<p>File: <strong>data_prodi.json</strong></p>";