<?php

// ======================================================
// DATA HISTORIS MAHASISWA - POLITEKNIK NEGERI LHOKSEUMAWE
// TAMPILAN MODERN DENGAN TAILWIND CSS, CHART.JS & DARK MODE
// ======================================================

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

$nama_kampus = getenv('CAMPUS_NAME') ?: 'Politeknik Negeri Lhokseumawe';
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
    die("File data_historis.json tidak ditemukan.");
}

$data = json_decode(file_get_contents($file), true);

if (!is_array($data)) {
    die("Data historis tidak dapat dibaca.");
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
                'kode'    => $kodeProdi,
                'nama'    => $namaProdi,
                'jenjang' => $jenjang,
                'jumlah'  => []
            ];
        }

        $jumlah = (int) ($item['jumlah_mahasiswa'] ?? 0);
        $prodi[$id]['jumlah'][$tahun] = $jumlah;
        $total[$tahun] += $jumlah;
    }
}

// Hitung pertumbuhan tahunan
$pertumbuhan = [];
$prevTotal = null;
foreach ($periode as $th) {
    if ($prevTotal !== null && $prevTotal > 0) {
        $selisih = $total[$th] - $prevTotal;
        $persen = round(($selisih / $prevTotal) * 100, 1);
        $pertumbuhan[$th] = [
            'selisih' => $selisih,
            'persen'  => $persen
        ];
    } else {
        $pertumbuhan[$th] = null;
    }
    $prevTotal = $total[$th];
}

// ======================================================
// EKSPOR KE EXCEL (XLS)
// ======================================================

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
            echo '<td>' . ($nilai === null ? '-' : number_format($nilai, 0, ',', '.')) . '</td>';
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

function getJenjangBadgeClass($jenjang)
{
    $j = strtoupper(trim($jenjang));
    switch ($j) {
        case 'D4':
        case 'S1':
            return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/70 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800';
        case 'D3':
            return 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-300 border-sky-200 dark:border-sky-800';
        case 'D2':
            return 'bg-teal-100 text-teal-800 dark:bg-teal-950/70 dark:text-teal-300 border-teal-200 dark:border-teal-800';
        case 'S2 TERAPAN':
        case 'S2':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-950/70 dark:text-purple-300 border-purple-200 dark:border-purple-800';
        default:
            return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700';
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Historis Mahasiswa | <?= htmlspecialchars($nama_kampus) ?></title>

    <!-- Google Font: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Chart.js 4 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                            950: '#172554',
                        }
                    }
                }
            }
        };
    </script>

    <!-- Prevent Theme Flash -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.4);
            border-radius: 999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.7);
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased min-h-screen flex flex-col transition-colors duration-300">

    <!-- ===================================================
         NAVBAR MODERN
    =================================================== -->
    <header class="sticky top-0 z-50 backdrop-blur-md bg-white/85 dark:bg-slate-900/85 border-b border-slate-200/80 dark:border-slate-800 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Brand / Logo -->
                <a href="index.php" class="flex items-center gap-3.5 group">
                    <div class="w-12 h-12 rounded-xl bg-white dark:bg-slate-800 p-1.5 shadow-sm border border-slate-200/80 dark:border-slate-700 flex items-center justify-center transition-transform group-hover:scale-105">
                        <img
                            src="https://pddikti.kemdiktisaintek.go.id/api/pt/logo/VvfhqKk2lEVgi9XyVdLnueMkOv6vlJUpDQIxANfgi4sXkBvhYZ3-ptzNyUjnPwriw-rwvg=="
                            alt="Logo PNL"
                            class="w-full h-full object-contain"
                            onerror="this.style.display='none'"
                        >
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-lg sm:text-xl tracking-tight text-slate-900 dark:text-white">PDDIKTI</span>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-brand-50 text-brand-700 dark:bg-brand-950/80 dark:text-brand-300 border border-brand-200 dark:border-brand-800">
                                Tren 5 Tahun
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium"><?= htmlspecialchars($nama_kampus) ?></p>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center gap-1 sm:gap-2">
                    <a
                        href="index.php"
                        class="px-3.5 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all"
                    >
                        Beranda
                    </a>
                    <a
                        href="index.php?lihat=prodi"
                        class="px-3.5 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all"
                    >
                        Program Studi
                    </a>
                    <a
                        href="historis.php"
                        class="px-3.5 py-2 rounded-xl text-sm font-semibold bg-brand-50 text-brand-700 dark:bg-brand-950/80 dark:text-brand-300 transition-all flex items-center gap-1.5"
                    >
                        <span>Tren Historis 5 Tahun</span>
                    </a>
                </nav>

                <!-- Actions: Theme Toggle & Mobile Menu Button -->
                <div class="flex items-center gap-2">
                    <!-- Dark Mode Toggle Button -->
                    <button
                        onclick="toggleDarkMode()"
                        type="button"
                        class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-brand-600 dark:hover:text-brand-400 flex items-center justify-center transition-all focus:outline-none focus:ring-2 focus:ring-brand-500"
                        title="Beralih tema terang/gelap"
                        aria-label="Toggle theme"
                    >
                        <svg class="moon-icon w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <svg class="sun-icon w-5 h-5 hidden transition-transform text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </button>

                    <!-- Mobile Hamburger Button -->
                    <button
                        onclick="toggleMobileMenu()"
                        type="button"
                        class="md:hidden w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center focus:outline-none"
                        aria-label="Buka menu navigasi"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Drawer -->
        <div id="mobileMenu" class="hidden md:hidden border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 pt-3 pb-5 space-y-1">
            <a href="index.php" class="block px-3.5 py-2.5 rounded-xl text-base font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">
                Beranda
            </a>
            <a href="index.php?lihat=prodi" class="block px-3.5 py-2.5 rounded-xl text-base font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">
                Daftar Program Studi
            </a>
            <a href="historis.php" class="block px-3.5 py-2.5 rounded-xl text-base font-semibold bg-brand-50 text-brand-700 dark:bg-brand-950/80 dark:text-brand-300">
                Tren Historis 5 Tahun
            </a>
        </div>
    </header>

    <!-- ===================================================
         MAIN CONTENT CONTAINER
    =================================================== -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 space-y-8">

        <!-- Page Header & Action Buttons -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-2">
            <div>
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-brand-600 dark:text-brand-400 mb-1">
                    <span>Analisis Longitudinal</span>
                    <span>•</span>
                    <span>2021 — 2025</span>
                </div>
                <h1 class="text-2xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Data Historis Mahasiswa
                </h1>
                <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mt-1">
                    Perbandingan jumlah mahasiswa per program studi <?= htmlspecialchars($nama_kampus) ?> berdasarkan periode semester ganjil API PDDIKTI.
                </p>
            </div>

            <!-- Action Buttons Bar -->
            <div class="flex flex-wrap items-center gap-3 self-start lg:self-auto">
                <a
                    href="index.php?lihat=prodi"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 shadow-sm transition-all"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a
                    href="historis.php?export=excel"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 shadow-md shadow-brand-500/20 transition-all hover:-translate-y-0.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Unduh Excel (.XLS)</span>
                </a>

                <a
                    href="ambil_historis.php"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/70 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900 transition-all"
                    title="Jalankan sinkronisasi cURL dari API PDDIKTI"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Refresh PDDIKTI</span>
                </a>
            </div>
        </div>

        <!-- 5-Year Period Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <?php foreach ($periode as $tahun): ?>
            <?php
                $jumlahTh = $total[$tahun];
                $growth = $pertumbuhan[$tahun];
            ?>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                    <?= htmlspecialchars($labelPeriode[$tahun]) ?>
                </span>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-2">
                    <?= number_format($jumlahTh, 0, ',', '.') ?>
                </div>

                <div class="mt-2 text-xs flex items-center gap-1.5">
                    <?php if ($growth !== null): ?>
                        <?php if ($growth['selisih'] >= 0): ?>
                            <span class="inline-flex items-center font-bold text-emerald-600 dark:text-emerald-400">
                                ↗ +<?= $growth['persen'] ?>%
                            </span>
                            <span class="text-slate-400">(+<?= number_format($growth['selisih'], 0, ',', '.') ?>)</span>
                        <?php else: ?>
                            <span class="inline-flex items-center font-bold text-rose-600 dark:text-rose-400">
                                ↘ <?= $growth['persen'] ?>%
                            </span>
                            <span class="text-slate-400">(<?= number_format($growth['selisih'], 0, ',', '.') ?>)</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-slate-400">Tahun Basis</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Grafik Perkembangan Mahasiswa Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>📊 Grafik Pertumbuhan Mahasiswa (2021 — 2025)</span>
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Visualisasi garis kurva jumlah mahasiswa terdaftar di setiap semester ganjil (kode berakhiran 1).
                    </p>
                </div>
                <div class="text-xs font-semibold px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 self-start sm:self-auto">
                    Total Mahasiswa PNL
                </div>
            </div>

            <div class="relative w-full h-[320px] sm:h-[380px] pt-4">
                <canvas id="grafikMahasiswa"></canvas>
            </div>
        </div>

        <!-- Tabel Komparasi Semua Program Studi -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">
                        📑 Tabel Mahasiswa per Program Studi
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Daftar lengkap 43 program studi dengan perbandingan data selama 5 periode semester ganjil.
                    </p>
                </div>

                <!-- Input Filter Cepat Tabel -->
                <div class="w-full md:w-72 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        type="text"
                        id="tableSearch"
                        placeholder="Cari program studi di tabel..."
                        class="w-full pl-9 pr-3.5 py-2 rounded-xl text-xs border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                </div>
            </div>

            <!-- Wrapper Tabel Responsif -->
            <div class="overflow-x-auto rounded-2xl border border-slate-200/80 dark:border-slate-800">
                <table class="w-full text-left border-collapse text-sm" id="historisTable">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider border-b border-slate-200/80 dark:border-slate-800">
                            <th class="py-4 px-4 text-center w-12">No</th>
                            <th class="py-4 px-4 min-w-[240px]">Program Studi</th>
                            <th class="py-4 px-3 text-center">Jenjang</th>
                            <?php foreach ($periode as $tahun): ?>
                            <th class="py-4 px-4 text-right whitespace-nowrap">
                                <?= htmlspecialchars($labelPeriode[$tahun]) ?>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70 text-slate-700 dark:text-slate-200">
                        <?php $no = 1; foreach ($prodi as $item): ?>
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors table-row">
                            <td class="py-3 px-4 text-center text-xs text-slate-400 font-medium">
                                <?= $no++ ?>
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white prodi-nama">
                                <?= htmlspecialchars($item['nama']) ?>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="px-2 py-0.5 rounded-lg text-xs font-bold border <?= getJenjangBadgeClass($item['jenjang']) ?>">
                                    <?= htmlspecialchars($item['jenjang']) ?>
                                </span>
                            </td>
                            <?php foreach ($periode as $tahun): ?>
                            <td class="py-3 px-4 text-right font-medium text-slate-800 dark:text-slate-100 font-mono">
                                <?php
                                    if (isset($item['jumlah'][$tahun])) {
                                        echo number_format($item['jumlah'][$tahun], 0, ',', '.');
                                    } else {
                                        echo '<span class="text-slate-400">-</span>';
                                    }
                                ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-brand-50/80 dark:bg-slate-800/90 text-slate-900 dark:text-white font-extrabold border-t-2 border-brand-200 dark:border-brand-800">
                            <td colspan="3" class="py-4 px-4 text-right uppercase tracking-wider text-xs">
                                TOTAL MAHASISWA KAMPUS
                            </td>
                            <?php foreach ($periode as $tahun): ?>
                            <td class="py-4 px-4 text-right font-mono text-base text-brand-700 dark:text-brand-300">
                                <?= number_format($total[$tahun], 0, ',', '.') ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </main>

    <!-- ===================================================
         FOOTER MODERN
    =================================================== -->
    <footer class="mt-auto border-t border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500 dark:text-slate-400">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>API Reader PDDIKTI • <?= htmlspecialchars($nama_kampus) ?></span>
            </div>
            <div>
                Data Historis Terkumpul: 5 Periode Semester Ganjil (20211 — 20251)
            </div>
        </div>
    </footer>

    <!-- ===================================================
         JAVASCRIPT LOGIC (CHART & TABLE FILTER)
    =================================================== -->
    <script>
        // Toggle Dark Mode
        function toggleDarkMode() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeIcons();
            if (window.myChart) {
                updateChartTheme(window.myChart, isDark);
            }
        }

        function updateThemeIcons() {
            const isDark = document.documentElement.classList.contains('dark');
            document.querySelectorAll('.sun-icon').forEach(el => el.classList.toggle('hidden', !isDark));
            document.querySelectorAll('.moon-icon').forEach(el => el.classList.toggle('hidden', isDark));
        }

        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }

        // Live Filter Tabel Historis
        const tableSearchInput = document.getElementById('tableSearch');
        if (tableSearchInput) {
            tableSearchInput.addEventListener('input', function() {
                const keyword = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('#historisTable tbody tr');

                rows.forEach(row => {
                    const text = row.querySelector('.prodi-nama')?.textContent.toLowerCase() || '';
                    if (!keyword || text.includes(keyword)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        // Inisialisasi Chart.js
        const chartLabels = <?= json_encode(array_map(fn($t) => $labelPeriode[$t], $periode), JSON_UNESCAPED_UNICODE) ?>;
        const chartTotals = <?= json_encode(array_values($total)) ?>;
        const canvas = document.getElementById('grafikMahasiswa');

        function updateChartTheme(chart, isDark) {
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';
            const textColor = isDark ? '#94a3b8' : '#64748b';

            chart.options.scales.x.grid.color = gridColor;
            chart.options.scales.x.ticks.color = textColor;
            chart.options.scales.y.grid.color = gridColor;
            chart.options.scales.y.ticks.color = textColor;
            chart.update();
        }

        if (canvas && typeof Chart !== 'undefined') {
            const isDarkInit = document.documentElement.classList.contains('dark');
            const gridColorInit = isDarkInit ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';
            const textColorInit = isDarkInit ? '#94a3b8' : '#64748b';

            const ctx = canvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 350);
            gradient.addColorStop(0, 'rgba(37, 99, 235, 0.35)');
            gradient.addColorStop(1, 'rgba(37, 99, 235, 0.0)');

            window.myChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Jumlah Mahasiswa PNL',
                        data: chartTotals,
                        borderColor: '#2563eb',
                        borderWidth: 3,
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#2563eb',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointHoverBackgroundColor: '#1d4ed8'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#ffffff',
                            bodyColor: '#93c5fd',
                            padding: 12,
                            cornerRadius: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y.toLocaleString('id-ID') + ' Mahasiswa';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: gridColorInit,
                                drawBorder: false
                            },
                            ticks: {
                                color: textColorInit,
                                font: {
                                    family: "'Plus Jakarta Sans', sans-serif",
                                    weight: 600
                                }
                            }
                        },
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: gridColorInit,
                                drawBorder: false
                            },
                            ticks: {
                                color: textColorInit,
                                font: {
                                    family: "'Plus Jakarta Sans', sans-serif"
                                },
                                callback: function(value) {
                                    return value.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', updateThemeIcons);
    </script>
</body>
</html>
