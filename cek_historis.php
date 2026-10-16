<?php
header('Content-Type: text/plain; charset=utf-8');

$idProdiPT = getenv('PDDIKTI_PT_ID') ?: 'B7OPrCaLnSM1jPcSLp7xV7tkDT1uIOvMpNlCjwgA2bq_SvK93yHFHs1lZQmipdXooHANmg==';

$periode = ['20211', '20221', '20231', '20241', '20251'];

foreach ($periode as $tahun) {
    $url = "https://pddikti.kemdiktisaintek.go.id/api/pt/prodi/"
         . $idProdiPT . "/" . $tahun;

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0',
            'Referer: https://pddikti.kemdiktisaintek.go.id/'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    echo "Periode: $tahun\n";
    echo "HTTP: $httpCode\n";

    if ($response === false) {
        echo "Error: $error\n\n";
        continue;
    }

    $hasil = json_decode($response, true);

    if (isset($hasil['data']) && is_array($hasil['data'])) {
        echo "Jumlah prodi: " . count($hasil['data']) . "\n";

        $pertama = reset($hasil['data']);

        echo "Contoh prodi: "
            . ($pertama['nama_prodi'] ?? $pertama['nama'] ?? '-') . "\n";

        echo "Jumlah mahasiswa: "
            . ($pertama['jumlah_mahasiswa'] ?? '-') . "\n";
    } else {
        echo "Data tidak ditemukan atau format respons berbeda.\n";
        echo substr($response, 0, 250) . "\n";
    }

    echo "-------------------------\n\n";
}
?>
