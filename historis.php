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

$file = resolve_data_file('data_historis.json', 'DATA_HISTORIS_PATH');
$periode = ['20211', '20221', '20231', '20241', '20251'];
$labelPeriode = [
    '20211' => '2021 (Ganjil)',
    '20221' => '2022 (Ganjil)',
    '20231' => '2023 (Ganjil)',
    '20241' => '2024 (Ganjil)',
    '20251' => '2025 (Ganjil)'
];

if (!file_exists($file)) {
    exit('File data_historis.json tidak ditemukan.');
}

$data = json_decode(file_get_contents($file), true);

if (!is_array($data)) {
    exit('Data historis tidak dapat dibaca.');
}

$prodi = [];
$total = array_fill_keys($periode, 0);

foreach ($periode as $tahun) {
    foreach (($data[$tahun] ?? []) as $item) {
        $kodeProdi = $item['kode_prodi'] ?? '';
        $namaProdi = $item['nama_prodi'] ?? '-';
        $jenjang = $item['jenjang_prodi'] ?? '-';

        // Gabungkan prodi berdasarkan kode, nama, dan jenjang.
        $id = $kodeProdi . '|' . $namaProdi . '|' . $jenjang;

        if (!isset($prodi[$id])) {
            $prodi[$id] = [
                'nama' => $namaProdi,
                'jenjang' => $jenjang,
                'jumlah' => []
            ];
        }

        $jumlah = (int) ($item['jumlah_mahasiswa'] ?? 0);
        $prodi[$id]['jumlah'][$tahun] = $jumlah;
        $total[$tahun] += $jumlah;
    }
}

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="data_mahasiswa_PNL_2021-2025.xls"');

    echo "\xEF\xBB\xBF";
    echo '<table border="1">';
    echo '<tr><th>No</th><th>Program Studi</th><th>Jenjang</th>';

    foreach ($periode as $tahun) {
        echo '<th>' . $labelPeriode[$tahun] . '</th>';
    }

    echo '</tr>';

    $no = 1;
    foreach ($prodi as $item) {
        echo '<tr><td>' . $no++ . '</td>';
        echo '<td>' . htmlspecialchars($item['nama']) . '</td>';
        echo '<td>' . htmlspecialchars($item['jenjang']) . '</td>';

        foreach ($periode as $tahun) {
            $nilai = $item['jumlah'][$tahun] ?? null;

echo '<td>' . (
    $nilai === null
        ? '-'
        : number_format($nilai, 0, ',', '.')
) . '</td>';
        }

        echo '</tr>';
    }

    echo '<tr><th colspan="3">TOTAL MAHASISWA</th>';
    foreach ($periode as $tahun) {
        echo '<th>' . $total[$tahun] . '</th>';
    }
    echo '</tr></table>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Historis Mahasiswa PNL</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
* { box-sizing: border-box; }
body {
    margin: 0;
    padding: 24px;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    color: #1e293b;
}
.container { max-width: 1200px; margin: auto; }
.header {
    background: #164eaa;
    color: white;
    padding: 24px;
    border-radius: 14px;
    margin-bottom: 20px;
}
.header h1 { margin: 0 0 8px; }
.actions { display: flex; flex-wrap: wrap; gap: 10px; margin: 18px 0; }
.btn {
    display: inline-block;
    padding: 11px 16px;
    border-radius: 8px;
    text-decoration: none;
    border: 0;
    cursor: pointer;
    font-size: 14px;
}
.blue { background: #2563eb; color: white; }
.green { background: #15803d; color: white; }
.gray { background: #e2e8f0; color: #1e293b; }
.card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px #0f172a0d;
}
.summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
}
.number { font-size: 25px; font-weight: bold; color: #1d4ed8; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; background: white; }
th, td { border: 1px solid #e2e8f0; padding: 11px; text-align: left; }
th { background: #eaf1ff; }
tfoot th { background: #dbeafe; }
.note { color: #64748b; font-size: 13px; }
@media (max-width: 600px) {
    body { padding: 12px; }
    .header { padding: 18px; }
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Data Historis Mahasiswa</h1>
        <div>Politeknik Negeri Lhokseumawe</div>
        <p>Perbandingan jumlah mahasiswa per program studi berdasarkan periode API PDDIKTI.</p>
    </div>

    <div class="actions">
        <a class="btn gray" href="index.php?lihat=prodi">← Kembali ke Dashboard</a>
        <a class="btn blue" href="historis.php?export=excel">Unduh Excel</a>
        <a class="btn green" href="ambil_historis.php" target="_blank">↻ Refresh dari PDDIKTI</a>
    </div>

    <div class="summary">
        <?php foreach ($periode as $tahun): ?>
        <div class="card">
            <div><?= htmlspecialchars($labelPeriode[$tahun]) ?></div>
            <div class="number"><?= number_format($total[$tahun], 0, ',', '.') ?></div>
            <div class="note">Total mahasiswa dari data prodi</div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h2>Grafik Perkembangan Mahasiswa</h2>
        <canvas id="grafikMahasiswa"></canvas>
        <p class="note">
            Periode yang ditampilkan adalah semester ganjil (kode berakhiran 1).
            Total merupakan penjumlahan jumlah mahasiswa pada 43 prodi yang berhasil diambil.
        </p>
    </div>

    <div class="card">
        <h2>Tabel Mahasiswa per Program Studi</h2>
        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Program Studi</th>
                    <th>Jenjang</th>
                    <?php foreach ($periode as $tahun): ?>
                        <th><?= htmlspecialchars($labelPeriode[$tahun]) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($prodi as $item): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($item['nama']) ?></td>
                    <td><?= htmlspecialchars($item['jenjang']) ?></td>
                    <?php foreach ($periode as $tahun): ?>
					<td>
					<?php
						if (isset($item['jumlah'][$tahun])) {
							echo number_format(
							$item['jumlah'][$tahun],
							0,
							',',
							'.'
							);
						} else {
								echo '-';
						}
						?>
					</td>
					<?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">TOTAL MAHASISWA</th>
                    <?php foreach ($periode as $tahun): ?>
                        <th><?= number_format($total[$tahun], 0, ',', '.') ?></th>
                    <?php endforeach; ?>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>
</div>

<script>
const labels = <?= json_encode(
    array_map(fn($tahun) => $labelPeriode[$tahun], $periode),
    JSON_UNESCAPED_UNICODE
) ?>;

const totals = <?= json_encode(array_values($total)) ?>;

const canvas = document.getElementById('grafikMahasiswa');

if (typeof Chart === 'undefined') {
    canvas.insertAdjacentHTML(
        'afterend',
        '<p style="color:red">Chart.js gagal dimuat. Periksa koneksi internet.</p>'
    );
} else {
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah Mahasiswa',
                data: totals,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.15)',
                fill: true,
                tension: 0.3,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
</script>
</body>
</html>

