<?php

// ======================================================
// API READER PDDIKTI
// POLITEKNIK NEGERI LHOKSEUMAWE
// ======================================================

// PHP 8.5 tidak lagi membutuhkan curl_close()
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);


// ======================================================
// FUNGSI MENGAMBIL DATA API
// ======================================================

function ambilAPI($url)
{
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
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36"
        ]
    ]);

    $response = curl_exec($ch);

    // Cek error cURL
    if ($response === false) {
        return [
            "success" => false,
            "error" => "cURL Error: " . curl_error($ch)
        ];
    }

    // Ambil HTTP status
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Cek HTTP error
    if ($http_code < 200 || $http_code >= 300) {
        return [
            "success" => false,
            "error" => "HTTP Error: " . $http_code,
            "response" => substr($response, 0, 500)
        ];
    }

    // Decode JSON
    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            "success" => false,
            "error" => "JSON Error: " . json_last_error_msg(),
            "response" => substr($response, 0, 500)
        ];
    }

    return [
        "success" => true,
        "data" => $data
    ];
}
// ======================================================
// 1. API PERGURUAN TINGGI
// ======================================================

$url_pt = "https://pddikti.kemdiktisaintek.go.id/api/pt/detail/VvfhqKk2lEVgi9XyVdLnueMkOv6vlJUpDQIxANfgi4sXkBvhYZ3-ptzNyUjnPwriw-rwvg==";


// Coba ambil data PT
$result_pt = ambilAPI($url_pt);


// Jangan hentikan website jika API PT ditolak.
// Data PT tidak diperlukan untuk menghitung jumlah prodi.
$pt = [];

if (
    $result_pt["success"]
    &&
    isset($result_pt["data"]["data"])
    &&
    is_array($result_pt["data"]["data"])
) {

    $pt = $result_pt["data"]["data"];

}


// Nama perguruan tinggi
$nama_pt = $pt["nama_pt"]
    ?? "Politeknik Negeri Lhokseumawe";


// ======================================================
// 2. API PROGRAM STUDI
// ======================================================

$url_prodi = "https://pddikti.kemdiktisaintek.go.id/api/pt/prodi/B7OPrCaLnSM1jPcSLp7xV7tkDT1uIOvMpNlCjwgA2bq_SvK93yHFHs1lZQmipdXooHANmg==/20251";


$result_prodi = ambilAPI($url_prodi);


// ======================================================
// CEK API PROGRAM STUDI
// ======================================================

if (!$result_prodi["success"]) {

    die("

        <div style='
            font-family:Arial,sans-serif;
            max-width:700px;
            margin:60px auto;
            padding:30px;
            background:#fee2e2;
            color:#991b1b;
            border-radius:15px;
            box-shadow:0 5px 20px rgba(0,0,0,.08);
        '>

            <h2 style='margin-bottom:15px;'>
                Gagal Mengambil Data Program Studi
            </h2>

            <p>
                <strong>Error:</strong>
            </p>

            <p style='margin-top:8px;'>
                "
                .
                htmlspecialchars(
                    $result_prodi["error"]
                )
                .
                "
            </p>

        </div>

    ");

}


$data_prodi = $result_prodi["data"];


// ======================================================
// CEK STRUKTUR DATA PRODI
// ======================================================

if (
    !is_array($data_prodi)
    ||
    !isset($data_prodi["data"])
) {

    die("

        <div style='
            font-family:Arial,sans-serif;
            max-width:700px;
            margin:60px auto;
            padding:30px;
            background:#fee2e2;
            color:#991b1b;
            border-radius:15px;
        '>

            <h2>
                Data Program Studi Tidak Sesuai
            </h2>

            <p style='margin-top:10px;'>
                API PDDIKTI berhasil diakses,
                tetapi data program studi tidak ditemukan.
            </p>

        </div>

    ");

}


$prodi = $data_prodi["data"];


// Pastikan data prodi berbentuk array

if (!is_array($prodi)) {

    $prodi = [];

}

// ======================================================
// 3. PERHITUNGAN JUMLAH PRODI
// ======================================================

$total_prodi = count($prodi);


// ======================================================
// 4. PRODI YANG DIPILIH
// ======================================================

$prodi_dipilih = null;

if (isset($_GET["prodi"])) {

    $index = (int) $_GET["prodi"];

    if (isset($prodi[$index])) {

        $prodi_dipilih = $prodi[$index];

    }

}
// ======================================================
// 5. DATA FILTER
// ======================================================

$jenjang_list = [];

$akreditasi_list = [];


foreach (
    $prodi
    as $p
) {

    if (
        isset($p["jenjang_prodi"])
        &&
        $p["jenjang_prodi"] !== ""
    ) {

        $jenjang_list[] =
        $p["jenjang_prodi"];

    }


    if (
        isset($p["akreditasi"])
        &&
        $p["akreditasi"] !== ""
    ) {

        $akreditasi_list[] =
        $p["akreditasi"];

    }

}


$jenjang_list =
array_unique(
    $jenjang_list
);


$akreditasi_list =
array_unique(
    $akreditasi_list
);


sort($jenjang_list);

sort($akreditasi_list);

?>



<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    PDDIKTI - Politeknik Negeri Lhokseumawe
</title>


<style>

/* ======================================================
   RESET
   ====================================================== */

* {

    margin: 0;

    padding: 0;

    box-sizing: border-box;

}


/* ======================================================
   BODY
   ====================================================== */

body {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f1f5f9;

    color: #1e293b;

}


/* ======================================================
   HEADER
   ====================================================== */

.header {

    background:
        linear-gradient(
            135deg,
            #075985,
            #0284c7
        );

    color: white;

    padding: 28px 20px;

}


.header-content {

    max-width: 1100px;

    margin: auto;

}


.logo {

    display: flex;

    align-items: center;

    gap: 18px;

}


.logo-icon {

    width: 70px;

    height: 70px;

    background: white;

    border-radius: 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;

    padding: 6px;

    flex-shrink: 0;

}


.logo-img {

    width: 100%;

    height: 100%;

    object-fit: contain;

}


.logo h1 {

    font-size: 25px;

    margin-bottom: 6px;

}


.logo p {

    font-size: 14px;

    opacity: .85;

}


/* ======================================================
   CONTAINER
   ====================================================== */

.container {

    max-width: 1100px;

    margin: auto;

    padding: 40px 20px 60px;

}


/* ======================================================
   MAIN CARD
   ====================================================== */

.main-card {

    background: white;

    border-radius: 20px;

    padding: 45px;

    box-shadow:
        0 8px 30px
        rgba(0,0,0,.07);

    text-align: center;

}


/* ======================================================
   ICON
   ====================================================== */

.big-icon {

    width: 90px;

    height: 90px;

    margin: 0 auto 20px;

    background: #e0f2fe;

    border-radius: 25px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 45px;

}


/* ======================================================
   JUMLAH PRODI
   ====================================================== */

.total-number {

    font-size: 60px;

    font-weight: bold;

    color: #0284c7;

    margin: 10px 0;

}


.total-label {

    font-size: 18px;

    color: #64748b;

    margin-bottom: 30px;

}


/* ======================================================
   BUTTON
   ====================================================== */

.btn {

    display: inline-block;

    text-decoration: none;

    border: none;

    cursor: pointer;

    padding: 13px 22px;

    border-radius: 10px;

    font-size: 14px;

    font-weight: bold;

    transition: .2s;

}


.btn-primary {

    background: #0284c7;

    color: white;

}


.btn-primary:hover {

    background: #0369a1;

    transform: translateY(-2px);

}


.btn-secondary {

    background: #e2e8f0;

    color: #334155;

}


.btn-secondary:hover {

    background: #cbd5e1;

}


/* ======================================================
   SECTION
   ====================================================== */

.section {

    margin-top: 30px;

}


.section-title {

    margin-bottom: 18px;

}


.section-title h2 {

    font-size: 24px;

    margin-bottom: 6px;

}


.section-title p {

    color: #64748b;

    font-size: 14px;

}


/* ======================================================
   FILTER
   ====================================================== */

.filter-card {

    background: white;

    padding: 22px;

    border-radius: 16px;

    margin-bottom: 20px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.05);

}


.filter-title {

    font-size: 16px;

    font-weight: bold;

    margin-bottom: 15px;

}


.filter-container {

    display: grid;

    grid-template-columns:
        2fr 1fr 1fr auto;

    gap: 12px;

}


.filter-input,
.filter-select {

    width: 100%;

    padding: 12px 14px;

    border:
        1px solid #cbd5e1;

    border-radius: 10px;

    font-size: 14px;

    outline: none;

    background: white;

}


.filter-input:focus,
.filter-select:focus {

    border-color: #0284c7;

    box-shadow:
        0 0 0 3px
        rgba(2,132,199,.1);

}


.btn-reset {

    background: #e2e8f0;

    color: #334155;

}


.btn-reset:hover {

    background: #cbd5e1;

}


.result-info {

    margin-top: 15px;

    font-size: 13px;

    color: #64748b;

}


/* ======================================================
   PRODI LIST
   ====================================================== */

.prodi-list {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 18px;

}


.prodi-item {

    background: white;

    padding: 22px;

    border-radius: 15px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.05);

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    transition: .2s;

}


.prodi-item:hover {

    transform: translateY(-3px);

    box-shadow:
        0 8px 25px
        rgba(0,0,0,.08);

}


.prodi-info {

    display: flex;

    align-items: center;

    gap: 15px;

}


.prodi-number {

    min-width: 40px;

    height: 40px;

    border-radius: 10px;

    background: #e0f2fe;

    color: #0284c7;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: bold;

}


.prodi-name {

    font-weight: bold;

    font-size: 15px;

}


.prodi-level {

    margin-top: 5px;

    font-size: 12px;

    color: #64748b;

}


/* ======================================================
   DETAIL
   ====================================================== */

.detail-card {

    background: white;

    border-radius: 20px;

    padding: 35px;

    box-shadow:
        0 8px 30px
        rgba(0,0,0,.07);

}


.detail-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    margin-bottom: 30px;

    padding-bottom: 20px;

    border-bottom:
        1px solid #e2e8f0;

}


.detail-header h2 {

    font-size: 25px;

}


.detail-header p {

    margin-top: 6px;

    color: #64748b;

    font-size: 13px;

}


.detail-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 18px;

}


.detail-item {

    padding: 18px;

    background: #f8fafc;

    border-radius: 12px;

    border:
        1px solid #e2e8f0;

}


.detail-item label {

    display: block;

    font-size: 11px;

    color: #64748b;

    text-transform: uppercase;

    margin-bottom: 7px;

    font-weight: bold;

}


.detail-item div {

    font-size: 14px;

    font-weight: 500;

    word-break: break-word;

}


/* ======================================================
   EMPTY DATA
   ====================================================== */

.empty-data {

    background: white;

    padding: 30px;

    text-align: center;

    border-radius: 15px;

    color: #64748b;

}


/* ======================================================
   FOOTER
   ====================================================== */

.footer {

    text-align: center;

    margin-top: 35px;

    color: #94a3b8;

    font-size: 12px;

}


/* ======================================================
   RESPONSIVE
   ====================================================== */

@media(max-width: 850px) {

    .filter-container {

        grid-template-columns:
            1fr 1fr;

    }

}


@media(max-width: 750px) {

    .header {

        padding: 22px 15px;

    }


    .logo-icon {

        width: 60px;

        height: 60px;

    }


    .logo {

        gap: 12px;

    }


    .logo h1 {

        font-size: 20px;

    }


    .logo p {

        font-size: 12px;

    }


    .container {

        padding:
            25px 15px 50px;

    }


    .main-card {

        padding:
            30px 20px;

    }


    .total-number {

        font-size: 48px;

    }


    .filter-container {

        grid-template-columns:
            1fr;

    }


    .prodi-list {

        grid-template-columns:
            1fr;

    }


    .detail-grid {

        grid-template-columns:
            1fr;

    }


    .detail-header {

        flex-direction: column;

        align-items: flex-start;

    }


    .prodi-item {

        flex-direction: column;

        align-items: stretch;

    }


    .prodi-item .btn {

        text-align: center;

    }

}

</style>

</head>


<body>


<!-- ======================================================
     HEADER
     ====================================================== -->

<header class="header">

    <div class="header-content">

        <div class="logo">

            <div class="logo-icon">

                <img
                    src="https://pddikti.kemdiktisaintek.go.id/api/pt/logo/VvfhqKk2lEVgi9XyVdLnueMkOv6vlJUpDQIxANfgi4sXkBvhYZ3-ptzNyUjnPwriw-rwvg=="
                    alt="Logo Politeknik Negeri Lhokseumawe"
                    class="logo-img"
                >

            </div>


            <div>

                <h1>
                    Dashboard PDDIKTI
                </h1>

                <p>
                    Politeknik Negeri Lhokseumawe
                </p>

            </div>

        </div>

    </div>

</header>



<main class="container">


<?php if ($prodi_dipilih === null): ?>


<!-- ======================================================
     HALAMAN JUMLAH PRODI
     ====================================================== -->

<div class="main-card">

    <div class="big-icon">
        🎓
    </div>


    <div class="total-number">

        <?= $total_prodi ?>

    </div>


    <div class="total-label">

        Program Studi

    </div>


    <a
        href="?lihat=prodi"
        class="btn btn-primary"
    >

        Lihat Daftar Prodi →

    </a>

</div>



<?php if (
    isset($_GET["lihat"])
    &&
    $_GET["lihat"] === "prodi"
): ?>


<!-- ======================================================
     DAFTAR PRODI
     ====================================================== -->

<section class="section">


    <div class="section-title">

        <h2>
            Daftar Program Studi
        </h2>

        <p>
            Pilih program studi untuk melihat seluruh detail data.
        </p>

    </div>



    <!-- ==================================================
         FILTER
         ================================================== -->

    <div class="filter-card">

        <div class="filter-title">
            🔎 Cari dan Filter Program Studi
        </div>


        <div class="filter-container">


            <input
                type="text"
                id="searchProdi"
                class="filter-input"
                placeholder="Cari nama program studi..."
            >


            <select
                id="filterJenjang"
                class="filter-select"
            >

                <option value="">
                    Semua Jenjang
                </option>


                <?php foreach (
                    $jenjang_list
                    as $jenjang
                ): ?>

                    <option
                        value="<?= htmlspecialchars($jenjang) ?>"
                    >

                        <?= htmlspecialchars($jenjang) ?>

                    </option>

                <?php endforeach; ?>

            </select>



            <select
                id="filterAkreditasi"
                class="filter-select"
            >

                <option value="">
                    Semua Akreditasi
                </option>


                <?php foreach (
                    $akreditasi_list
                    as $akreditasi
                ): ?>

                    <option
                        value="<?= htmlspecialchars($akreditasi) ?>"
                    >

                        <?= htmlspecialchars($akreditasi) ?>

                    </option>

                <?php endforeach; ?>

            </select>



            <button
                type="button"
                class="btn btn-reset"
                onclick="resetFilter()"
            >

                Reset

            </button>


        </div>


        <div
            class="result-info"
            id="resultInfo"
        >

            Menampilkan
            <?= $total_prodi ?>
            program studi

        </div>

    </div>



    <!-- ==================================================
         LIST PRODI
         ================================================== -->

    <div
        class="prodi-list"
        id="prodiList"
    >


    <?php

    $no = 1;


    foreach (
        $prodi
        as $index => $p
    ):


        $nama =
        isset($p["nama_prodi"])
            ? trim($p["nama_prodi"])
            : "-";


        $jenjang =
        isset($p["jenjang_prodi"])
            ? $p["jenjang_prodi"]
            : "-";


        $akreditasi =
        isset($p["akreditasi"])
            ? $p["akreditasi"]
            : "-";

    ?>


        <div
            class="prodi-item"

            data-nama="<?= htmlspecialchars(
                strtolower($nama)
            ) ?>"

            data-jenjang="<?= htmlspecialchars(
                strtolower($jenjang)
            ) ?>"

            data-akreditasi="<?= htmlspecialchars(
                strtolower($akreditasi)
            ) ?>"
        >


            <div class="prodi-info">


                <div class="prodi-number">

                    <?= $no++ ?>

                </div>


                <div>

                    <div class="prodi-name">

                        <?= htmlspecialchars($nama) ?>

                    </div>


                    <div class="prodi-level">

                        Jenjang:
                        <?= htmlspecialchars($jenjang) ?>

                    </div>


                    <?php if (
                        $akreditasi !== "-"
                    ): ?>

                        <div class="prodi-level">

                            Akreditasi:
                            <?= htmlspecialchars($akreditasi) ?>

                        </div>

                    <?php endif; ?>


                </div>

            </div>



            <a
                href="?prodi=<?= $index ?>"
                class="btn btn-primary"
            >

                Detail

            </a>


        </div>


    <?php endforeach; ?>


    </div>



    <div
        id="emptyData"
        class="empty-data"
        style="display:none;"
    >

        Data program studi tidak ditemukan.

    </div>


</section>


<?php endif; ?>



<?php else: ?>


<!-- ======================================================
     DETAIL PRODI
     ====================================================== -->

<section class="section">


    <div class="detail-card">


        <div class="detail-header">


            <div>

                <h2>

                    <?= htmlspecialchars(
                        $prodi_dipilih["nama_prodi"]
                        ??
                        "Detail Program Studi"
                    ) ?>

                </h2>


                <p>

                    Detail lengkap berdasarkan data API PDDIKTI

                </p>

            </div>



            <a
                href="?lihat=prodi"
                class="btn btn-secondary"
            >

                ← Kembali

            </a>


        </div>



        <div class="detail-grid">


        <?php


        /*
         * Menampilkan SEMUA data
         * yang diberikan API untuk prodi.
         */

        foreach (
            $prodi_dipilih
            as $key => $value
        ):


            /*
             * Jika data berbentuk array
             */

            if (
                is_array($value)
            ) {

                $value =
                json_encode(
                    $value,
                    JSON_UNESCAPED_UNICODE
                );

            }


            /*
             * Jika data null atau kosong
             */

            if (
                $value === null
                ||
                $value === ""
            ) {

                $value = "-";

            }


            /*
             * Membuat nama field
             * menjadi lebih mudah dibaca
             */

            $label =
            str_replace(
                "_",
                " ",
                $key
            );


            $label =
            ucwords($label);

        ?>


            <div class="detail-item">


                <label>

                    <?= htmlspecialchars($label) ?>

                </label>


                <div>

                    <?= htmlspecialchars(
                        (string)$value
                    ) ?>

                </div>


            </div>


        <?php endforeach; ?>


        </div>


    </div>


</section>


<?php endif; ?>


<!-- ======================================================
     FOOTER
     ====================================================== -->

<div class="footer">

    API Reader PDDIKTI
    •
    Politeknik Negeri Lhokseumawe
    •
    Semester 20251

</div>


</main>



<!-- ======================================================
     JAVASCRIPT FILTER
     ====================================================== -->

<script>

const searchInput =
    document.getElementById("searchProdi");


const filterJenjang =
    document.getElementById("filterJenjang");


const filterAkreditasi =
    document.getElementById("filterAkreditasi");


const prodiItems =
    document.querySelectorAll(".prodi-item");


const resultInfo =
    document.getElementById("resultInfo");


const emptyData =
    document.getElementById("emptyData");



function filterProdi() {


    if (!prodiItems.length) {

        return;

    }


    const search =
        searchInput.value
        .toLowerCase()
        .trim();


    const jenjang =
        filterJenjang.value
        .toLowerCase()
        .trim();


    const akreditasi =
        filterAkreditasi.value
        .toLowerCase()
        .trim();


    let jumlah = 0;


    prodiItems.forEach(
        function(item)
        {


            const nama =
                item.dataset.nama
                ||
                "";


            const itemJenjang =
                item.dataset.jenjang
                ||
                "";


            const itemAkreditasi =
                item.dataset.akreditasi
                ||
                "";


            const cocokNama =
                nama.includes(search);


            const cocokJenjang =
                jenjang === ""
                ||
                itemJenjang === jenjang;


            const cocokAkreditasi =
                akreditasi === ""
                ||
                itemAkreditasi === akreditasi;


            if (
                cocokNama
                &&
                cocokJenjang
                &&
                cocokAkreditasi
            ) {

                item.style.display =
                    "flex";

                jumlah++;

            }
            else {

                item.style.display =
                    "none";

            }

        }
    );


    if (resultInfo) {

        resultInfo.innerHTML =
            "Menampilkan <strong>"
            +
            jumlah
            +
            "</strong> program studi";

    }


    if (emptyData) {

        if (jumlah === 0) {

            emptyData.style.display =
                "block";

        }
        else {

            emptyData.style.display =
                "none";

        }

    }

}



// ======================================================
// RESET FILTER
// ======================================================

function resetFilter() {


    if (searchInput) {

        searchInput.value = "";

    }


    if (filterJenjang) {

        filterJenjang.value = "";

    }


    if (filterAkreditasi) {

        filterAkreditasi.value = "";

    }


    filterProdi();

}



// ======================================================
// EVENT SEARCH
// ======================================================

if (searchInput) {

    searchInput.addEventListener(
        "input",
        filterProdi
    );

}



// ======================================================
// EVENT FILTER JENJANG
// ======================================================

if (filterJenjang) {

    filterJenjang.addEventListener(
        "change",
        filterProdi
    );

}



// ======================================================
// EVENT FILTER AKREDITASI
// ======================================================

if (filterAkreditasi) {

    filterAkreditasi.addEventListener(
        "change",
        filterProdi
    );

}

</script>


</body>

</html>
```
