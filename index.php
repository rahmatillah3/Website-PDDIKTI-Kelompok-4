<?php

// ======================================================
// DASHBOARD PDDIKTI - POLITEKNIK NEGERI LHOKSEUMAWE
// TAMPILAN MODERN DENGAN TAILWIND CSS & DARK MODE
// ======================================================

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// ======================================================
// RESOLUSI JALUR FILE DATA DENGAN DUKUNGAN ENV
// ======================================================

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
$file = resolve_data_file('data_prodi.json', 'DATA_PRODI_PATH');

if (!file_exists($file)) {
    die("File data_prodi.json tidak ditemukan.");
}

$json = file_get_contents($file);
$prodi = json_decode($json, true);

if (!is_array($prodi)) {
    die("Data program studi tidak dapat dibaca.");
}

// ======================================================
// DATA DASAR & METRIK STATISTIK
// ======================================================

$total_prodi = count($prodi);
$total_mahasiswa = 0;
$total_dosen = 0;
$total_unggul = 0;
$jenjang_counts = [];

foreach ($prodi as $p) {
    if (isset($p["jumlah_mahasiswa"]) && is_numeric($p["jumlah_mahasiswa"])) {
        $total_mahasiswa += (int) $p["jumlah_mahasiswa"];
    }
    if (isset($p["jumlah_dosen"]) && is_numeric($p["jumlah_dosen"])) {
        $total_dosen += (int) $p["jumlah_dosen"];
    }
    $akr = strtolower(trim($p["akreditasi"] ?? ''));
    if ($akr === 'unggul' || $akr === 'a') {
        $total_unggul++;
    }
    $j = trim($p["jenjang_prodi"] ?? '');
    if ($j !== '') {
        $jenjang_counts[$j] = ($jenjang_counts[$j] ?? 0) + 1;
    }
}

// ======================================================
// PRODI DIPILIH
// ======================================================

$prodi_dipilih = null;
$prodi_index = -1;

if (isset($_GET["prodi"])) {
    $index = (int) $_GET["prodi"];
    if (isset($prodi[$index])) {
        $prodi_dipilih = $prodi[$index];
        $prodi_index = $index;
    }
}

// ======================================================
// DATA HISTORIS MAHASISWA PER PRODI
// ======================================================

$periode_historis = [
    '20211' => '2021 (Ganjil)',
    '20221' => '2022 (Ganjil)',
    '20231' => '2023 (Ganjil)',
    '20241' => '2024 (Ganjil)',
    '20251' => '2025 (Ganjil)'
];

$riwayat_mahasiswa = [];
foreach ($periode_historis as $kode => $label) {
    $riwayat_mahasiswa[$kode] = null;
}

$file_historis = resolve_data_file('data_historis.json', 'DATA_HISTORIS_PATH');

if ($prodi_dipilih !== null && file_exists($file_historis)) {
    $data_historis = json_decode(file_get_contents($file_historis), true);

    if (is_array($data_historis)) {
        $kode_dipilih = (string) ($prodi_dipilih['kode_prodi'] ?? '');
        $nama_dipilih = strtolower(trim($prodi_dipilih['nama_prodi'] ?? ''));
        $jenjang_dipilih = strtolower(trim($prodi_dipilih['jenjang_prodi'] ?? ''));

        foreach ($periode_historis as $kode_periode => $label) {
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
                        $riwayat_mahasiswa[$kode_periode] = (int) $item['jumlah_mahasiswa'];
                    }
                    break;
                }
            }
        }
    }
}

// ======================================================
// FILTER OPTIONS
// ======================================================

$jenjang_list = [];
$akreditasi_list = [];

foreach ($prodi as $p) {
    if (isset($p["jenjang_prodi"]) && $p["jenjang_prodi"] !== "") {
        $jenjang_list[] = $p["jenjang_prodi"];
    }
    if (isset($p["akreditasi"]) && $p["akreditasi"] !== "") {
        $akreditasi_list[] = $p["akreditasi"];
    }
}

$jenjang_list = array_values(array_unique($jenjang_list));
$akreditasi_list = array_values(array_unique($akreditasi_list));

sort($jenjang_list);
sort($akreditasi_list);

// Helper badge
function badgeAkreditasi($akreditasi)
{
    if (!$akreditasi || trim($akreditasi) === "") {
        return "Belum tersedia";
    }
    return $akreditasi;
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

function getAkreditasiBadgeClass($akreditasi)
{
    $a = strtolower(trim($akreditasi));
    if (strpos($a, 'unggul') !== false || $a === 'a') {
        return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
    } elseif (strpos($a, 'baik sekali') !== false || $a === 'b') {
        return 'bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300 border-blue-200 dark:border-blue-800';
    } elseif (strpos($a, 'baik') !== false || $a === 'c') {
        return 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border-amber-200 dark:border-amber-800';
    } else {
        return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border-slate-200 dark:border-slate-700';
    }
}

$isProdiListView = isset($_GET["lihat"]) && $_GET["lihat"] === "prodi";
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard PDDIKTI | <?= htmlspecialchars($nama_kampus) ?></title>

    <!-- Google Font: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

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
        /* Custom scrollbar */
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
                                Semester 20251
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium"><?= htmlspecialchars($nama_kampus) ?></p>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center gap-1 sm:gap-2">
                    <a
                        href="index.php"
                        class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all <?= (!$isProdiListView && $prodi_dipilih === null) ? 'bg-brand-50 text-brand-700 dark:bg-brand-950/80 dark:text-brand-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>"
                    >
                        Beranda
                    </a>
                    <a
                        href="index.php?lihat=prodi"
                        class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all <?= ($isProdiListView || $prodi_dipilih !== null) ? 'bg-brand-50 text-brand-700 dark:bg-brand-950/80 dark:text-brand-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>"
                    >
                        Program Studi
                    </a>
                    <a
                        href="historis.php"
                        class="px-3.5 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all flex items-center gap-1.5"
                    >
                        <span>Tren Historis 5 Tahun</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300">
                            Grafik
                        </span>
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
                        <!-- Moon icon for light mode -->
                        <svg class="moon-icon w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <!-- Sun icon for dark mode -->
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
            <a
                href="index.php"
                class="block px-3.5 py-2.5 rounded-xl text-base font-semibold <?= (!$isProdiListView && $prodi_dipilih === null) ? 'bg-brand-50 text-brand-700 dark:bg-brand-950/80 dark:text-brand-300' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' ?>"
            >
                Beranda
            </a>
            <a
                href="index.php?lihat=prodi"
                class="block px-3.5 py-2.5 rounded-xl text-base font-semibold <?= ($isProdiListView || $prodi_dipilih !== null) ? 'bg-brand-50 text-brand-700 dark:bg-brand-950/80 dark:text-brand-300' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' ?>"
            >
                Daftar Program Studi
            </a>
            <a
                href="historis.php"
                class="block px-3.5 py-2.5 rounded-xl text-base font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800"
            >
                Tren Historis Mahasiswa 5 Tahun
            </a>
        </div>
    </header>

    <!-- ===================================================
         MAIN CONTENT CONTAINER
    =================================================== -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">

        <?php if ($prodi_dipilih !== null): ?>

            <!-- ===============================================
                 TAMPILAN DETAIL PROGRAM STUDI
            =============================================== -->
            <div class="space-y-8 animate-fadeIn">
                <!-- Breadcrumbs & Navigasi Kembali -->
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                        <a href="index.php" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Beranda</a>
                        <span>/</span>
                        <a href="index.php?lihat=prodi" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Program Studi</a>
                        <span>/</span>
                        <span class="text-slate-800 dark:text-slate-200 font-semibold truncate max-w-[200px] sm:max-w-xs">
                            <?= htmlspecialchars($prodi_dipilih["nama_prodi"] ?? "-") ?>
                        </span>
                    </nav>

                    <a
                        href="index.php?lihat=prodi"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700/80 shadow-sm transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Kembali ke Daftar
                    </a>
                </div>

                <!-- Header Detail Card -->
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-900 via-brand-800 to-indigo-900 text-white p-6 sm:p-10 shadow-xl border border-brand-700/50">
                    <div class="relative z-10 space-y-4">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/20 backdrop-blur-sm border border-white/20 text-white uppercase tracking-wider">
                                <?= htmlspecialchars($prodi_dipilih["jenjang_prodi"] ?? "-") ?>
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/25 border border-emerald-400/40 text-emerald-200">
                                Akreditasi: <?= htmlspecialchars(badgeAkreditasi($prodi_dipilih["akreditasi"] ?? "")) ?>
                            </span>
                            <?php if (!empty($prodi_dipilih["status_prodi"])): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/10 text-slate-200">
                                Status: <?= htmlspecialchars($prodi_dipilih["status_prodi"]) ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight">
                            <?= htmlspecialchars($prodi_dipilih["nama_prodi"] ?? "-") ?>
                        </h1>

                        <p class="text-sm sm:text-base text-brand-100 max-w-2xl">
                            Kode Program Studi: <strong class="text-white"><?= htmlspecialchars($prodi_dipilih["kode_prodi"] ?? "-") ?></strong>
                            • Institut: <?= htmlspecialchars($nama_kampus) ?>
                        </p>
                    </div>

                    <!-- Decorative Background Graphic -->
                    <div class="absolute -right-10 -bottom-10 w-72 h-72 rounded-full bg-brand-500/10 blur-3xl pointer-events-none"></div>
                </div>

                <!-- Metrik Utama Prodi -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Mahasiswa Aktif</span>
                            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/70 text-brand-600 dark:text-brand-400 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
                            <?= number_format((int)($prodi_dipilih["jumlah_mahasiswa"] ?? 0), 0, ',', '.') ?>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Semester Ganjil 20251</p>
                    </div>

                    <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Dosen</span>
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
                            <?= number_format((int)($prodi_dipilih["jumlah_dosen"] ?? 0), 0, ',', '.') ?>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            NIDN: <?= htmlspecialchars((string)($prodi_dipilih["jumlah_dosen_nidn"] ?? 0)) ?> • NIDK: <?= htmlspecialchars((string)($prodi_dipilih["jumlah_dosen_nidk"] ?? 0)) ?>
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rasio Dosen : Mhs</span>
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
                            <?= htmlspecialchars($prodi_dipilih["rasio"] ?? "-") ?>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Dosen Ajar: <?= htmlspecialchars((string)($prodi_dipilih["jumlah_dosen_ajar"] ?? "-")) ?></p>
                    </div>

                    <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kelengkapan Data</span>
                            <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/70 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
                            <?= htmlspecialchars((string)($prodi_dipilih["indikator_kelengkapan_data"] ?? "0")) ?>%
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Status Pelaporan</p>
                    </div>
                </div>

                <!-- Bagian 1: Tabel Riwayat Jumlah Mahasiswa 5 Tahun -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span>📈 Riwayat Jumlah Mahasiswa (5 Tahun)</span>
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                Data mahasiswa semester ganjil periode 20211 hingga 20251.
                            </p>
                        </div>
                        <a
                            href="unduh_historis.php?prodi=<?= $prodi_index ?>"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 shadow-md shadow-brand-500/20 transition-all hover:-translate-y-0.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Unduh CSV (Excel)
                        </a>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 text-xs uppercase font-bold tracking-wider">
                                    <th class="py-3.5 px-4 rounded-l-xl">Tahun Periode</th>
                                    <th class="py-3.5 px-4">Semester</th>
                                    <th class="py-3.5 px-4 text-right">Jumlah Mahasiswa</th>
                                    <th class="py-3.5 px-4 rounded-r-xl text-center">Status Data</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                                <?php foreach ($periode_historis as $kode => $label): ?>
                                <?php $val = $riwayat_mahasiswa[$kode]; ?>
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">
                                        <?= htmlspecialchars(substr($kode, 0, 4)) ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">
                                        <?= htmlspecialchars($label) ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-bold text-brand-600 dark:text-brand-400 text-base">
                                        <?= $val === null ? '-' : number_format($val, 0, ',', '.') ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <?php if ($val !== null): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                Tersedia
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                                Tidak Ada
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Bagian 2: Seluruh Atribut Data PDDIKTI -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">
                        📋 Informasi Lengkap Program Studi
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                        Detail parameter mentah yang diperoleh dari API PDDIKTI Kemdiktisaintek.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($prodi_dipilih as $key => $value): ?>
                        <?php
                            if (is_array($value)) {
                                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                            }
                            if ($value === null || $value === "") {
                                $value = "-";
                            }
                            $label = ucwords(str_replace("_", " ", $key));
                        ?>
                        <div class="p-4 rounded-xl bg-slate-50/80 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800/80 flex flex-col justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                <?= htmlspecialchars($label) ?>
                            </span>
                            <span class="text-sm font-bold text-slate-900 dark:text-slate-100 break-words font-mono">
                                <?= htmlspecialchars((string)$value) ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($isProdiListView): ?>

            <!-- ===============================================
                 TAMPILAN DAFTAR PROGRAM STUDI
            =============================================== -->
            <div class="space-y-8 animate-fadeIn">
                <!-- Header Daftar Prodi -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            📚 Daftar Program Studi
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Seluruh program studi resmi di <?= htmlspecialchars($nama_kampus) ?> periode 20251.
                        </p>
                    </div>

                    <a
                        href="historis.php"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-brand-700 dark:text-brand-300 bg-brand-50 dark:bg-brand-950/80 border border-brand-200 dark:border-brand-800 hover:bg-brand-100 dark:hover:bg-brand-900 transition-all self-start md:self-auto"
                    >
                        <span>📊 Lihat Komparasi Historis 5 Tahun</span>
                        <span>→</span>
                    </a>
                </div>

                <!-- Box Filter & Pencarian Modern -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                    <div class="flex flex-col lg:flex-row gap-3.5">
                        <!-- Input Search Keyword -->
                        <div class="relative flex-grow">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                id="search"
                                placeholder="Cari nama atau kode program studi..."
                                class="w-full pl-10 pr-10 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
                            >
                            <button
                                id="clearSearch"
                                type="button"
                                class="hidden absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                aria-label="Hapus pencarian"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Filter Dropdown Jenjang -->
                        <div class="w-full lg:w-56">
                            <select
                                id="jenjang"
                                class="w-full py-3 px-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
                            >
                                <option value="">Semua Jenjang</option>
                                <?php foreach ($jenjang_list as $j): ?>
                                <option value="<?= htmlspecialchars($j) ?>"><?= htmlspecialchars($j) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filter Dropdown Akreditasi -->
                        <div class="w-full lg:w-56">
                            <select
                                id="akreditasi"
                                class="w-full py-3 px-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
                            >
                                <option value="">Semua Akreditasi</option>
                                <?php foreach ($akreditasi_list as $a): ?>
                                <option value="<?= htmlspecialchars($a) ?>"><?= htmlspecialchars($a) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Counter Bar -->
                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <span id="counterText">
                            Menampilkan <strong><?= $total_prodi ?></strong> dari <strong><?= $total_prodi ?></strong> program studi
                        </span>
                        <button
                            id="resetFiltersBtn"
                            type="button"
                            class="hidden text-brand-600 dark:text-brand-400 font-semibold hover:underline"
                        >
                            Reset Filter
                        </button>
                    </div>
                </div>

                <!-- Grid Kartu Program Studi -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="prodiGrid">
                    <?php foreach ($prodi as $index => $p): ?>
                    <?php
                        $nama = $p["nama_prodi"] ?? "-";
                        $jenjang = $p["jenjang_prodi"] ?? "-";
                        $akreditasi = $p["akreditasi"] ?? "";
                        $mahasiswa = $p["jumlah_mahasiswa"] ?? 0;
                        $dosen = $p["jumlah_dosen"] ?? 0;
                        $kode = $p["kode_prodi"] ?? "";
                    ?>
                    <div
                        class="prodi-card group bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-xl hover:border-brand-300 dark:hover:border-brand-700 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
                        data-nama="<?= htmlspecialchars(strtolower($nama)) ?>"
                        data-kode="<?= htmlspecialchars(strtolower($kode)) ?>"
                        data-jenjang="<?= htmlspecialchars($jenjang) ?>"
                        data-akreditasi="<?= htmlspecialchars($akreditasi) ?>"
                    >
                        <div>
                            <!-- Header Kartu -->
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">
                                    PRODI #<?= $index + 1 ?>
                                </span>
                                <?php if (!empty($kode)): ?>
                                <span class="font-mono text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                    <?= htmlspecialchars($kode) ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <!-- Judul Program Studi -->
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors line-clamp-2 min-h-[3.5rem]">
                                <?= htmlspecialchars($nama) ?>
                            </h3>

                            <!-- Badges (Jenjang & Akreditasi) -->
                            <div class="flex flex-wrap items-center gap-2 mt-3">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold border <?= getJenjangBadgeClass($jenjang) ?>">
                                    <?= htmlspecialchars($jenjang) ?>
                                </span>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold border <?= getAkreditasiBadgeClass($akreditasi) ?>">
                                    <?= htmlspecialchars(badgeAkreditasi($akreditasi)) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Data & Tombol Aksi -->
                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80 space-y-4">
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-xl">
                                    <span class="text-slate-500 dark:text-slate-400 block mb-0.5">Mahasiswa</span>
                                    <strong class="text-sm text-slate-900 dark:text-white">
                                        <?= is_numeric($mahasiswa) ? number_format((int)$mahasiswa, 0, ',', '.') : '-' ?>
                                    </strong>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-xl">
                                    <span class="text-slate-500 dark:text-slate-400 block mb-0.5">Dosen</span>
                                    <strong class="text-sm text-slate-900 dark:text-white">
                                        <?= is_numeric($dosen) ? number_format((int)$dosen, 0, ',', '.') : '-' ?>
                                    </strong>
                                </div>
                            </div>

                            <a
                                href="index.php?prodi=<?= $index ?>"
                                class="w-full inline-flex items-center justify-center gap-1.5 py-2.5 px-4 rounded-xl text-sm font-semibold text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-950/60 hover:bg-brand-600 hover:text-white dark:hover:bg-brand-600 dark:hover:text-white border border-brand-200 dark:border-brand-800/60 transition-all"
                            >
                                <span>Lihat Rincian</span>
                                <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Empty State (Jika Pencarian Tidak Ditemukan) -->
                <div id="noResults" class="hidden bg-white dark:bg-slate-900 rounded-3xl p-12 text-center border border-slate-200/80 dark:border-slate-800">
                    <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4 text-slate-400 text-2xl">
                        🔍
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Program Studi Tidak Ditemukan</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto mb-4">
                        Tidak ada program studi yang cocok dengan kata kunci atau filter yang Anda pilih.
                    </p>
                    <button
                        onclick="resetFilters()"
                        type="button"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-brand-600 bg-brand-50 hover:bg-brand-100 transition-colors"
                    >
                        Hapus Semua Filter
                    </button>
                </div>
            </div>

        <?php else: ?>

            <!-- ===============================================
                 TAMPILAN BERANDA UTAMA (OVERVIEW DASHBOARD)
            =============================================== -->
            <div class="space-y-10 animate-fadeIn">
                <!-- Hero Section Modern -->
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-brand-900 via-brand-800 to-indigo-950 text-white p-8 sm:p-12 shadow-xl border border-brand-700/40">
                    <div class="relative z-10 max-w-3xl space-y-4">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-brand-500/30 border border-brand-400/30 text-brand-200">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            Data Resmi PDDIKTI Terintegrasi
                        </span>

                        <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight">
                            Dashboard Program Studi & Mahasiswa
                        </h1>

                        <p class="text-base sm:text-lg text-brand-100/90 leading-relaxed">
                            Aplikasi pemantauan dan analisis statistik resmi Program Studi <span class="font-semibold text-white"><?= htmlspecialchars($nama_kampus) ?></span> berdasarkan laporan pangkalan data pendidikan tinggi semester 20251.
                        </p>

                        <div class="pt-4 flex flex-wrap items-center gap-3.5">
                            <a
                                href="index.php?lihat=prodi"
                                class="inline-flex items-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-bold text-brand-900 bg-white hover:bg-brand-50 shadow-lg shadow-black/10 transition-all hover:scale-105"
                            >
                                <span>Jelajahi Program Studi</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                            <a
                                href="historis.php"
                                class="inline-flex items-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-bold text-white bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 transition-all"
                            >
                                <span>📊 Analisis Tren 5 Tahun</span>
                            </a>
                        </div>
                    </div>

                    <!-- Glowing Decorative Circles -->
                    <div class="absolute -right-20 -top-20 w-96 h-96 rounded-full bg-brand-500/20 blur-3xl pointer-events-none"></div>
                    <div class="absolute -right-10 -bottom-20 w-80 h-80 rounded-full bg-indigo-500/20 blur-3xl pointer-events-none"></div>
                </div>

                <!-- Kartu Statistik Ringkasan (Summary Cards) -->
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">
                        📊 Ringkasan Statistik Kampus
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <!-- Card 1: Total Prodi -->
                        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-lg transition-all hover:-translate-y-1">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Program Studi</span>
                                <div class="w-12 h-12 rounded-2xl bg-brand-50 dark:bg-brand-950/70 text-brand-600 dark:text-brand-400 flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                                <?= $total_prodi ?>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Tersedia pada jenjang D2, D3, D4, & S2
                            </p>
                        </div>

                        <!-- Card 2: Total Mahasiswa -->
                        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-lg transition-all hover:-translate-y-1">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Mahasiswa Aktif</span>
                                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                                <?= number_format($total_mahasiswa, 0, ',', '.') ?>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                Total terdaftar di semester 20251
                            </p>
                        </div>

                        <!-- Card 3: Total Dosen -->
                        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-lg transition-all hover:-translate-y-1">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Dosen</span>
                                <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                                <?= number_format($total_dosen, 0, ',', '.') ?>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                Pengajar dan dosen tetap aktif
                            </p>
                        </div>

                        <!-- Card 4: Akreditasi Unggul -->
                        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-lg transition-all hover:-translate-y-1">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Akreditasi Unggul</span>
                                <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                                <?= $total_unggul ?> <span class="text-base font-semibold text-slate-400">Prodi</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Sertifikasi akreditasi tertinggi BAN-PT/LAM
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Bagian Distribusi Jenjang & Quick Access -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Jenjang Distribution Card -->
                    <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Distribusi Jenjang Pendidikan</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Komposisi program studi berdasarkan tingkat jenjang vokasi.</p>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                <?= count($jenjang_counts) ?> Kategori
                            </span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <?php foreach ($jenjang_counts as $j => $c): ?>
                            <div class="p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                                <span class="text-xs font-bold px-2 py-0.5 rounded border <?= getJenjangBadgeClass($j) ?>">
                                    <?= htmlspecialchars($j) ?>
                                </span>
                                <div class="text-2xl font-extrabold text-slate-900 dark:text-white mt-3">
                                    <?= $c ?>
                                </div>
                                <span class="text-xs text-slate-500 dark:text-slate-400">Program Studi</span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                Ingin melihat daftar lengkap beserta filter akreditasi?
                            </span>
                            <a href="index.php?lihat=prodi" class="text-xs font-bold text-brand-600 dark:text-brand-400 hover:underline inline-flex items-center gap-1">
                                <span>Buka Tabel Program Studi</span>
                                <span>→</span>
                            </a>
                        </div>
                    </div>

                    <!-- Side Card Quick Actions -->
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-3xl p-6 sm:p-8 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-xl mb-4">
                                🚀
                            </div>
                            <h3 class="text-lg font-bold">Akses Cepat & Laporan</h3>
                            <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                                Jelajahi data historis 5 tahun terakhir (20211 - 20251) dalam grafik interaktif serta unduh rekapitulasi Excel.
                            </p>
                        </div>

                        <div class="mt-6 space-y-2.5">
                            <a
                                href="historis.php"
                                class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-bold bg-brand-500 hover:bg-brand-600 text-white shadow-md transition-all hover:scale-[1.02]"
                            >
                                <span>Buka Grafik Historis</span>
                                <span>→</span>
                            </a>
                            <a
                                href="historis.php?export=excel"
                                class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-semibold bg-white/10 hover:bg-white/15 text-slate-200 border border-white/10 transition-all"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                <span>Unduh Rekapitulasi (.XLS)</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>

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
                Data diperbarui untuk Semester Ganjil <strong>20251</strong>
            </div>
        </div>
    </footer>

    <!-- ===================================================
         JAVASCRIPT LOGIC (THEME, FILTERING, DRAWER)
    =================================================== -->
    <script>
        // Toggle Dark Mode
        function toggleDarkMode() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeIcons();
        }

        function updateThemeIcons() {
            const isDark = document.documentElement.classList.contains('dark');
            document.querySelectorAll('.sun-icon').forEach(el => el.classList.toggle('hidden', !isDark));
            document.querySelectorAll('.moon-icon').forEach(el => el.classList.toggle('hidden', isDark));
        }

        // Toggle Mobile Menu Drawer
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }

        // Filter Logic Program Studi
        const searchInput = document.getElementById("search");
        const jenjangSelect = document.getElementById("jenjang");
        const akreditasiSelect = document.getElementById("akreditasi");
        const clearSearchBtn = document.getElementById("clearSearch");
        const resetFiltersBtn = document.getElementById("resetFiltersBtn");
        const counterText = document.getElementById("counterText");
        const noResults = document.getElementById("noResults");
        const cards = document.querySelectorAll(".prodi-card");
        const totalCards = cards.length;

        function filterData() {
            if (!cards.length) return;

            const keyword = searchInput ? searchInput.value.toLowerCase().trim() : "";
            const selectedJenjang = jenjangSelect ? jenjangSelect.value : "";
            const selectedAkreditasi = akreditasiSelect ? akreditasiSelect.value : "";

            if (clearSearchBtn) {
                clearSearchBtn.classList.toggle('hidden', keyword === "");
            }

            const hasActiveFilter = keyword !== "" || selectedJenjang !== "" || selectedAkreditasi !== "";
            if (resetFiltersBtn) {
                resetFiltersBtn.classList.toggle('hidden', !hasActiveFilter);
            }

            let visibleCount = 0;

            cards.forEach(card => {
                const nama = card.dataset.nama || "";
                const kode = card.dataset.kode || "";
                const cardJenjang = card.dataset.jenjang || "";
                const cardAkreditasi = card.dataset.akreditasi || "";

                const cocokKeyword = !keyword || nama.includes(keyword) || kode.includes(keyword);
                const cocokJenjang = !selectedJenjang || cardJenjang === selectedJenjang;
                const cocokAkreditasi = !selectedAkreditasi || cardAkreditasi === selectedAkreditasi;

                if (cocokKeyword && cocokJenjang && cocokAkreditasi) {
                    card.style.display = "";
                    visibleCount++;
                } else {
                    card.style.display = "none";
                }
            });

            if (counterText) {
                counterText.innerHTML = `Menampilkan <strong>${visibleCount}</strong> dari <strong>${totalCards}</strong> program studi`;
            }

            if (noResults) {
                noResults.classList.toggle('hidden', visibleCount > 0);
            }
        }

        function resetFilters() {
            if (searchInput) searchInput.value = "";
            if (jenjangSelect) jenjangSelect.value = "";
            if (akreditasiSelect) akreditasiSelect.value = "";
            filterData();
        }

        if (searchInput) {
            searchInput.addEventListener("input", filterData);
        }
        if (jenjangSelect) {
            jenjangSelect.addEventListener("change", filterData);
        }
        if (akreditasiSelect) {
            akreditasiSelect.addEventListener("change", filterData);
        }
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener("click", () => {
                searchInput.value = "";
                filterData();
                searchInput.focus();
            });
        }
        if (resetFiltersBtn) {
            resetFiltersBtn.addEventListener("click", resetFilters);
        }

        // Init on DOM ready
        document.addEventListener('DOMContentLoaded', updateThemeIcons);
    </script>
</body>
</html>