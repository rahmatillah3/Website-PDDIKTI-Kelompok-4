<?php

// ======================================================
// DASHBOARD PDDIKTI
// POLITEKNIK NEGERI LHOKSEUMAWE
// DATA DARI data_prodi.json
// ======================================================

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);


// ======================================================
// BACA DATA JSON
// ======================================================

$file = dirname(__DIR__) . "/data_prodi.json";

if (!file_exists($file)) {
    die("File data_prodi.json tidak ditemukan.");
}

$json = file_get_contents($file);

$prodi = json_decode($json, true);

if (!is_array($prodi)) {
    die("Data program studi tidak dapat dibaca.");
}


// ======================================================
// DATA DASAR
// ======================================================

$total_prodi = count($prodi);

$total_mahasiswa = 0;

foreach ($prodi as $p) {

    if (
        isset($p["jumlah_mahasiswa"]) &&
        is_numeric($p["jumlah_mahasiswa"])
    ) {
        $total_mahasiswa += (int) $p["jumlah_mahasiswa"];
    }

}


// ======================================================
// PRODI DIPILIH
// ======================================================

$prodi_dipilih = null;

if (isset($_GET["prodi"])) {

    $index = (int) $_GET["prodi"];

    if (isset($prodi[$index])) {
        $prodi_dipilih = $prodi[$index];
    }

}


// ======================================================
// FILTER
// ======================================================

$jenjang_list = [];
$akreditasi_list = [];

foreach ($prodi as $p) {

    if (
        isset($p["jenjang_prodi"]) &&
        $p["jenjang_prodi"] !== ""
    ) {
        $jenjang_list[] = $p["jenjang_prodi"];
    }

    if (
        isset($p["akreditasi"]) &&
        $p["akreditasi"] !== ""
    ) {
        $akreditasi_list[] = $p["akreditasi"];
    }

}

$jenjang_list = array_unique($jenjang_list);
$akreditasi_list = array_unique($akreditasi_list);

sort($jenjang_list);
sort($akreditasi_list);


// ======================================================
// FUNGSI AKREDITASI
// ======================================================

function badgeAkreditasi($akreditasi)
{

    if (!$akreditasi) {
        return "Belum tersedia";
    }

    return $akreditasi;
}

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard PDDIKTI | Politeknik Negeri Lhokseumawe</title>

<style>

/* =====================================================
   RESET
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


/* =====================================================
   BODY
===================================================== */

body {

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        linear-gradient(
            135deg,
            #f8fafc 0%,
            #eef4ff 100%
        );

    color: #172033;

    min-height: 100vh;

}


/* =====================================================
   HEADER
===================================================== */

.top-header {

    background:
        linear-gradient(
            135deg,
            #0f4c81,
            #2563eb
        );

    color: white;

    padding: 22px 7%;

    box-shadow:
        0 8px 25px rgba(15, 76, 129, .20);

}


.header-content {

    max-width: 1200px;

    margin: auto;

    display: flex;

    align-items: center;

    gap: 18px;

}


.logo {

    width: 65px;

    height: 65px;

    object-fit: contain;

    background: white;

    padding: 6px;

    border-radius: 15px;

}


.header-title h1 {

    font-size: 24px;

    margin-bottom: 5px;

}


.header-title p {

    opacity: .85;

    font-size: 14px;

}


/* =====================================================
   CONTAINER
===================================================== */

.container {

    max-width: 1200px;

    margin: auto;

    padding: 40px 20px 60px;

}


/* =====================================================
   HERO
===================================================== */

.hero {

    background: white;

    border-radius: 24px;

    padding: 40px;

    margin-bottom: 30px;

    box-shadow:
        0 10px 35px rgba(15, 23, 42, .07);

}


.hero h2 {

    font-size: 34px;

    margin-bottom: 10px;

}


.hero p {

    color: #64748b;

    font-size: 16px;

}


/* =====================================================
   STATISTIC
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 20px;

    margin-top: 30px;

}


.stat-card {

    padding: 25px;

    border-radius: 20px;

    background:
        linear-gradient(
            135deg,
            #eff6ff,
            #dbeafe
        );

    border: 1px solid #bfdbfe;

}


.stat-icon {

    font-size: 28px;

    margin-bottom: 10px;

}


.stat-number {

    font-size: 36px;

    font-weight: 800;

    color: #1d4ed8;

}


.stat-title {

    color: #64748b;

    margin-top: 4px;

}


/* =====================================================
   BUTTON
===================================================== */

.btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    text-decoration: none;

    border: none;

    cursor: pointer;

    padding: 13px 22px;

    border-radius: 12px;

    font-weight: 700;

    transition: .2s;

}


.btn-primary {

    background: #2563eb;

    color: white;

    margin-top: 25px;

    box-shadow:
        0 6px 18px rgba(37, 99, 235, .25);

}


.btn-primary:hover {

    transform: translateY(-2px);

    background: #1d4ed8;

}


.btn-outline {

    background: white;

    color: #2563eb;

    border: 1px solid #dbeafe;

}


.btn-outline:hover {

    background: #eff6ff;

}


/* =====================================================
   SECTION
===================================================== */

.section {

    margin-top: 30px;

}


.section-header {

    margin-bottom: 20px;

}


.section-header h2 {

    font-size: 26px;

}


.section-header p {

    color: #64748b;

    margin-top: 6px;

}


/* =====================================================
   FILTER
===================================================== */

.filter-box {

    background: white;

    padding: 22px;

    border-radius: 18px;

    box-shadow:
        0 5px 20px rgba(15, 23, 42, .05);

    margin-bottom: 25px;

}


.filter-title {

    font-weight: 700;

    margin-bottom: 15px;

}


.filter-grid {

    display: grid;

    grid-template-columns:
        2fr 1fr 1fr;

    gap: 12px;

}


input,
select {

    width: 100%;

    padding: 13px 15px;

    border-radius: 11px;

    border: 1px solid #dbe2ea;

    background: #f8fafc;

    outline: none;

    font-size: 14px;

}


input:focus,
select:focus {

    border-color: #2563eb;

    background: white;

    box-shadow:
        0 0 0 3px rgba(37,99,235,.10);

}


/* =====================================================
   PRODI GRID
===================================================== */

.prodi-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;

}


/* =====================================================
   PRODI CARD
===================================================== */

.prodi-card {

    background: white;

    border-radius: 20px;

    padding: 22px;

    border: 1px solid #e5e7eb;

    box-shadow:
        0 6px 22px rgba(15,23,42,.05);

    transition: .25s;

}


.prodi-card:hover {

    transform: translateY(-5px);

    box-shadow:
        0 15px 35px rgba(15,23,42,.10);

    border-color: #bfdbfe;

}


.prodi-number {

    font-size: 13px;

    color: #64748b;

    font-weight: 700;

    margin-bottom: 10px;

}


.prodi-name {

    font-size: 18px;

    font-weight: 750;

    line-height: 1.4;

    min-height: 52px;

}


.meta {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;

    margin-top: 15px;

}


.badge {

    padding: 6px 10px;

    border-radius: 999px;

    font-size: 12px;

    font-weight: 700;

    background: #eff6ff;

    color: #1d4ed8;

}


.badge-gray {

    background: #f1f5f9;

    color: #475569;

}


.mahasiswa {

    margin-top: 18px;

    padding-top: 15px;

    border-top: 1px solid #edf0f4;

    display: flex;

    align-items: center;

    gap: 9px;

}


.mahasiswa-icon {

    width: 38px;

    height: 38px;

    border-radius: 10px;

    background: #eff6ff;

    display: flex;

    align-items: center;

    justify-content: center;

}


.mahasiswa strong {

    display: block;

    font-size: 17px;

}


.mahasiswa span {

    font-size: 12px;

    color: #64748b;

}


/* =====================================================
   DETAIL
===================================================== */

.detail-header {

    background:
        linear-gradient(
            135deg,
            #0f4c81,
            #2563eb
        );

    color: white;

    border-radius: 24px;

    padding: 35px;

    margin-bottom: 20px;

}


.detail-header h2 {

    font-size: 30px;

    line-height: 1.3;

}


.detail-header p {

    margin-top: 8px;

    opacity: .85;

}


.detail-stats {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 20px;

    margin-bottom: 25px;

}


.detail-stat {

    background: white;

    padding: 25px;

    border-radius: 18px;

    box-shadow:
        0 5px 20px rgba(15,23,42,.06);

}


.detail-stat .icon {

    font-size: 25px;

}


.detail-stat .value {

    font-size: 30px;

    font-weight: 800;

    margin-top: 8px;

    color: #1d4ed8;

}


.detail-stat .label {

    color: #64748b;

    margin-top: 3px;

}


.detail-table {

    background: white;

    border-radius: 20px;

    padding: 25px;

    box-shadow:
        0 5px 20px rgba(15,23,42,.06);

}


.detail-table h3 {

    margin-bottom: 15px;

}


.detail-row {

    display: grid;

    grid-template-columns:
        220px 1fr;

    padding: 15px 0;

    border-bottom: 1px solid #edf0f4;

}


.detail-label {

    color: #64748b;

    font-weight: 600;

}


.detail-value {

    font-weight: 600;

    word-break: break-word;

}


/* =====================================================
   FOOTER
===================================================== */

.footer {

    text-align: center;

    color: #64748b;

    padding: 30px 20px;

    font-size: 13px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 900px) {

    .prodi-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }

    .filter-grid {

        grid-template-columns:
            1fr;

    }

}


@media (max-width: 600px) {

    .top-header {

        padding: 18px 20px;

    }

    .header-title h1 {

        font-size: 19px;

    }

    .hero {

        padding: 25px;

    }

    .hero h2 {

        font-size: 27px;

    }

    .stats {

        grid-template-columns:
            1fr;

    }

    .prodi-grid {

        grid-template-columns:
            1fr;

    }

    .detail-stats {

        grid-template-columns:
            1fr;

    }

    .detail-row {

        grid-template-columns:
            1fr;

        gap: 5px;

    }

}

</style>

</head>

<body>


<!-- ===================================================
     HEADER
=================================================== -->

<header class="top-header">

<div class="header-content">

<img
    class="logo"
    src="https://pddikti.kemdiktisaintek.go.id/api/pt/logo/VvfhqKk2lEVgi9XyVdLnueMkOv6vlJUpDQIxANfgi4sXkBvhYZ3-ptzNyUjnPwriw-rwvg=="
    alt="Logo PNL"
>

<div class="header-title">

<h1>Dashboard PDDIKTI</h1>

<p>
Politeknik Negeri Lhokseumawe
</p>

</div>

</div>

</header>


<div class="container">


<?php if ($prodi_dipilih !== null): ?>


<!-- ===================================================
     DETAIL PRODI
=================================================== -->

<div class="detail-header">

<h2>
<?= htmlspecialchars(
    $prodi_dipilih["nama_prodi"] ?? "-"
) ?>
</h2>

<p>
Detail Program Studi berdasarkan data PDDIKTI
</p>

<a
    href="?lihat=prodi"
    class="btn btn-outline"
    style="margin-top:20px;"
>
← Kembali ke Daftar Prodi
</a>

</div>


<?php

$jumlah_mahasiswa =
    $prodi_dipilih["jumlah_mahasiswa"] ?? 0;

$jumlah_dosen =
    $prodi_dipilih["jumlah_dosen"] ?? 0;

?>


<div class="detail-stats">


<div class="detail-stat">

<div class="icon">
👨‍🎓
</div>

<div class="value">

<?= htmlspecialchars(
    (string)$jumlah_mahasiswa
) ?>

</div>

<div class="label">
Jumlah Mahasiswa
</div>

</div>


<div class="detail-stat">

<div class="icon">
👨‍🏫
</div>

<div class="value">

<?= htmlspecialchars(
    (string)$jumlah_dosen
) ?>

</div>

<div class="label">
Jumlah Dosen
</div>

</div>


</div>


<div class="detail-table">

<h3>
Informasi Program Studi
</h3>


<?php foreach ($prodi_dipilih as $key => $value): ?>

<?php

if (is_array($value)) {

    $value = json_encode(
        $value,
        JSON_UNESCAPED_UNICODE
    );

}

if ($value === null || $value === "") {

    $value = "-";

}

$label = ucwords(
    str_replace("_", " ", $key)
);

?>


<div class="detail-row">

<div class="detail-label">

<?= htmlspecialchars($label) ?>

</div>

<div class="detail-value">

<?= htmlspecialchars(
    (string)$value
) ?>

</div>

</div>


<?php endforeach; ?>


</div>


<?php elseif (
    isset($_GET["lihat"]) &&
    $_GET["lihat"] === "prodi"
): ?>


<!-- ===================================================
     DAFTAR PRODI
=================================================== -->

<div class="section">

<div class="section-header">

<h2>
📚 Daftar Program Studi
</h2>

<p>
Pilih program studi untuk melihat informasi lengkap.
</p>

</div>


<div class="filter-box">

<div class="filter-title">
🔎 Cari dan Filter Program Studi
</div>


<div class="filter-grid">


<input
    type="text"
    id="search"
    placeholder="Cari nama program studi..."
>


<select id="jenjang">

<option value="">
Semua Jenjang
</option>

<?php foreach ($jenjang_list as $j): ?>

<option value="<?= htmlspecialchars($j) ?>">

<?= htmlspecialchars($j) ?>

</option>

<?php endforeach; ?>

</select>


<select id="akreditasi">

<option value="">
Semua Akreditasi
</option>

<?php foreach ($akreditasi_list as $a): ?>

<option value="<?= htmlspecialchars($a) ?>">

<?= htmlspecialchars($a) ?>

</option>

<?php endforeach; ?>

</select>


</div>

</div>


<div class="prodi-grid" id="prodiGrid">


<?php foreach ($prodi as $index => $p): ?>


<?php

$nama =
    $p["nama_prodi"] ?? "-";

$jenjang =
    $p["jenjang_prodi"] ?? "-";

$akreditasi =
    $p["akreditasi"] ?? "";

$mahasiswa =
    $p["jumlah_mahasiswa"] ?? "-";

?>


<div
    class="prodi-card"

    data-nama="<?= htmlspecialchars(
        strtolower($nama)
    ) ?>"

    data-jenjang="<?= htmlspecialchars(
        $jenjang
    ) ?>"

    data-akreditasi="<?= htmlspecialchars(
        $akreditasi
    ) ?>"
>


<div class="prodi-number">

PROGRAM STUDI <?= $index + 1 ?>

</div>


<div class="prodi-name">

<?= htmlspecialchars($nama) ?>

</div>


<div class="meta">

<span class="badge">

<?= htmlspecialchars($jenjang) ?>

</span>


<span class="badge badge-gray">

<?= htmlspecialchars(
    badgeAkreditasi($akreditasi)
) ?>

</span>

</div>


<div class="mahasiswa">

<div class="mahasiswa-icon">
👨‍🎓
</div>

<div>

<strong>

<?= htmlspecialchars(
    (string)$mahasiswa
) ?>

</strong>

<span>
Jumlah Mahasiswa
</span>

</div>

</div>


<a
    href="?prodi=<?= $index ?>"
    class="btn btn-primary"
>

Lihat Detail →

</a>


</div>


<?php endforeach; ?>


</div>

</div>


<?php else: ?>


<!-- ===================================================
     DASHBOARD
=================================================== -->

<div class="hero">

<h2>
Dashboard Data PDDIKTI
</h2>

<p>
Informasi Program Studi Politeknik Negeri Lhokseumawe
berdasarkan data PDDIKTI semester 20251.
</p>


<div class="stats">


<div class="stat-card">

<div class="stat-icon">
🎓
</div>

<div class="stat-number">

<?= $total_prodi ?>

</div>

<div class="stat-title">
Program Studi
</div>

</div>


<div class="stat-card">

<div class="stat-icon">
👨‍🎓
</div>

<div class="stat-number">

<?= number_format(
    $total_mahasiswa,
    0,
    ",",
    "."
) ?>

</div>

<div class="stat-title">
Total Mahasiswa
</div>

</div>


</div>


<a
    href="?lihat=prodi"
    class="btn btn-primary"
>

Lihat Daftar Program Studi →

</a>

</div>


<?php endif; ?>


</div>


<!-- ===================================================
     FOOTER
=================================================== -->

<footer class="footer">

API Reader PDDIKTI
•
Politeknik Negeri Lhokseumawe
•
Semester 20251

</footer>


<script>

/* =====================================================
   FILTER PROGRAM STUDI
===================================================== */

const search =
    document.getElementById("search");

const jenjang =
    document.getElementById("jenjang");

const akreditasi =
    document.getElementById("akreditasi");


if (search) {

    function filterData() {

        const keyword =
            search.value.toLowerCase();

        const selectedJenjang =
            jenjang.value;

        const selectedAkreditasi =
            akreditasi.value;


        document
            .querySelectorAll(".prodi-card")
            .forEach(card => {


                const nama =
                    card.dataset.nama;

                const cardJenjang =
                    card.dataset.jenjang;

                const cardAkreditasi =
                    card.dataset.akreditasi;


                const cocokNama =
                    nama.includes(keyword);


                const cocokJenjang =
                    !selectedJenjang ||
                    cardJenjang === selectedJenjang;


                const cocokAkreditasi =
                    !selectedAkreditasi ||
                    cardAkreditasi === selectedAkreditasi;


                if (
                    cocokNama &&
                    cocokJenjang &&
                    cocokAkreditasi
                ) {

                    card.style.display = "";

                } else {

                    card.style.display = "none";

                }

            });

    }


    search.addEventListener(
        "input",
        filterData
    );


    jenjang.addEventListener(
        "change",
        filterData
    );


    akreditasi.addEventListener(
        "change",
        filterData
    );

}

</script>


</body>

</html>