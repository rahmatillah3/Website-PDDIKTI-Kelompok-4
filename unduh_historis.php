<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

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

// Pastikan prodi dipilih
if (!isset($_GET['prodi']) || !is_numeric($_GET['prodi'])) {
    exit('Program studi tidak valid.');
}

$index = (int) $_GET['prodi'];

// Baca data program studi
$file_prodi = resolve_data_file('data_prodi.json', 'DATA_PRODI_PATH');

if (!file_exists($file_prodi)) {
    exit('File data_prodi.json tidak ditemukan.');
}

$semua_prodi = json_decode(
    file_get_contents($file_prodi),
    true
);

if (!is_array($semua_prodi) || !isset($semua_prodi[$index])) {
    exit('Data program studi tidak ditemukan.');
}

$prodi = $semua_prodi[$index];

// Periode historis
$periode = [
    '20211' => '2021 (Ganjil)',
    '20221' => '2022 (Ganjil)',
    '20231' => '2023 (Ganjil)',
    '20241' => '2024 (Ganjil)',
    '20251' => '2025 (Ganjil)'
];

// Baca data historis
$file_historis = resolve_data_file('data_historis.json', 'DATA_HISTORIS_PATH');

if (!file_exists($file_historis)) {
    exit('File data_historis.json tidak ditemukan.');
}

$data_historis = json_decode(
    file_get_contents($file_historis),
    true
);

if (!is_array($data_historis)) {
    exit('Data historis tidak dapat dibaca.');
}

// Siapkan pencocokan data prodi
$kode_dipilih = (string) ($prodi['kode_prodi'] ?? '');
$nama_dipilih = strtolower(trim($prodi['nama_prodi'] ?? ''));
$jenjang_dipilih = strtolower(trim($prodi['jenjang_prodi'] ?? ''));

// Nama file unduhan
$nama_file = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '_',
    $prodi['nama_prodi'] ?? 'program_studi'
);

if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/csv; charset=UTF-8');
header(
    'Content-Disposition: attachment; filename="Riwayat_' .
    $nama_file . '.csv"'
);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Buat file CSV yang dapat dibuka di Excel
$output = fopen('php://output', 'w');

// BOM agar karakter Bahasa Indonesia terbaca dengan baik
fwrite($output, "\xEF\xBB\xBF");

// Judul dan informasi prodi
fputcsv($output, ['RIWAYAT JUMLAH MAHASISWA PDDIKTI'], ';');
fputcsv($output, ['Nama Program Studi', $prodi['nama_prodi'] ?? '-'], ';');
fputcsv($output, ['Jenjang', $prodi['jenjang_prodi'] ?? '-'], ';');
fputcsv($output, [], ';');
fputcsv($output, ['Periode', 'Jumlah Mahasiswa'], ';');

// Isi data lima tahun
foreach ($periode as $kode_periode => $label) {
    $jumlah = null;

    foreach (($data_historis[$kode_periode] ?? []) as $item) {
        $kode_item = (string) ($item['kode_prodi'] ?? '');
        $nama_item = strtolower(trim($item['nama_prodi'] ?? ''));
        $jenjang_item = strtolower(trim($item['jenjang_prodi'] ?? ''));

        $cocok_kode = (
            $kode_dipilih !== '' &&
            $kode_item !== '' &&
            $kode_dipilih === $kode_item
        );

        $cocok_nama = (
            $nama_dipilih !== '' &&
            $nama_dipilih === $nama_item &&
            $jenjang_dipilih === $jenjang_item
        );

        if ($cocok_kode || $cocok_nama) {
            if (array_key_exists('jumlah_mahasiswa', $item)) {
                $jumlah = (int) $item['jumlah_mahasiswa'];
            }
            break;
        }
    }

    fputcsv(
        $output,
        [$label, $jumlah === null ? '-' : $jumlah],
        ';'
    );
}

fclose($output);
exit;
