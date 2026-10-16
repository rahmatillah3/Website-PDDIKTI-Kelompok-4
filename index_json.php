<?php

// ======================================================
// WEBSITE PDDIKTI - MEMBACA DATA DARI JSON
// ======================================================

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);


// ======================================================
// BACA DATA PRODI DARI FILE JSON
// ======================================================

$file = __DIR__ . "/data_prodi.json";

if (!file_exists($file)) {
    die("File data_prodi.json tidak ditemukan.");
}

$json = file_get_contents($file);

$prodi = json_decode($json, true);

if (!is_array($prodi)) {
    die("Data prodi tidak dapat dibaca.");
}


// ======================================================
// JUMLAH PRODI
// ======================================================

$total_prodi = count($prodi);


// ======================================================
// PRODI YANG DIPILIH
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

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard PDDIKTI</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f7fb;
    color: #1f2937;
}

.container {
    max-width: 1100px;
    margin: auto;
    padding: 30px 20px;
}

.header {
    background: white;
    padding: 25px;
    border-radius: 18px;
    margin-bottom: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,.06);
}

.logo {
    width: 80px;
    height: 80px;
    object-fit: contain;
}

h1 {
    margin-bottom: 5px;
}

.subtitle {
    color: #6b7280;
}

.stat {
    background: #eef2ff;
    padding: 25px;
    border-radius: 18px;
    margin-top: 20px;
}

.stat-number {
    font-size: 42px;
    font-weight: bold;
}

.button {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 20px;
    background: #2563eb;
    color: white;
    text-decoration: none;
    border-radius: 10px;
}

.card {
    background: white;
    padding: 20px;
    border-radius: 15px;
    margin-top: 15px;
    box-shadow: 0 3px 15px rgba(0,0,0,.05);
}

.detail {
    background: white;
    padding: 25px;
    border-radius: 18px;
    box-shadow: 0 5px 20px rgba(0,0,0,.06);
}

.detail-row {
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.detail-label {
    font-weight: bold;
    margin-bottom: 5px;
}

input,
select {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 10px;
    margin: 5px;
}

</style>

</head>

<body>

<div class="container">

<div class="header">

<img
    class="logo"
    src="https://pddikti.kemdiktisaintek.go.id/api/pt/logo/VvfhqKk2lEVgi9XyVdLnueMkOv6vlJUpDQIxANfgi4sXkBvhYZ3-ptzNyUjnPwriw-rwvg=="
    alt="Logo Politeknik Negeri Lhokseumawe"
>

<h1>Dashboard PDDIKTI</h1>

<p class="subtitle">
Politeknik Negeri Lhokseumawe
</p>

<div class="stat">

<div class="stat-number">
<?= $total_prodi ?>
</div>

<div>
Program Studi
</div>

</div>

<a class="button" href="?lihat=prodi">
Lihat Daftar Prodi →
</a>

</div>


<?php if ($prodi_dipilih !== null): ?>

<div class="detail">

<h2>
<?= htmlspecialchars(
    $prodi_dipilih["nama_prodi"] ?? "-"
) ?>
</h2>

<p>
Detail lengkap berdasarkan data PDDIKTI
</p>

<a class="button" href="?lihat=prodi">
← Kembali
</a>

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

<div>
<?= htmlspecialchars((string)$value) ?>
</div>

</div>

<?php endforeach; ?>

</div>


<?php elseif (isset($_GET["lihat"]) && $_GET["lihat"] === "prodi"): ?>

<div class="header">

<h2>Daftar Program Studi</h2>

<p>
Pilih program studi untuk melihat seluruh detail data.
</p>

<input
    type="text"
    id="search"
    placeholder="🔎 Cari Program Studi..."
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


<div id="daftar">

<?php foreach ($prodi as $index => $p): ?>

<div
    class="card"
    data-nama="<?= strtolower(
        $p["nama_prodi"] ?? ""
    ) ?>"
    data-jenjang="<?= htmlspecialchars(
        $p["jenjang_prodi"] ?? ""
    ) ?>"
    data-akreditasi="<?= htmlspecialchars(
        $p["akreditasi"] ?? ""
    ) ?>"
>

<h3>
<?= ($index + 1) ?>.
<?= htmlspecialchars(
    $p["nama_prodi"] ?? "-"
) ?>
</h3>

<p>
Jenjang:
<strong>
<?= htmlspecialchars(
    $p["jenjang_prodi"] ?? "-"
) ?>
</strong>
</p>

<p>
Akreditasi:
<strong>
<?= htmlspecialchars(
    $p["akreditasi"] ?? "-"
) ?>
</strong>
</p>

<p>
Jumlah Mahasiswa:
<strong>
<?= htmlspecialchars(
    (string)(
        $p["jumlah_mahasiswa"] ?? "-"
    )
) ?>
</strong>
</p>

<a
    class="button"
    href="?prodi=<?= $index ?>"
>
Detail
</a>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

</div>


<script>

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

        const j =
            jenjang.value;

        const a =
            akreditasi.value;

        document
            .querySelectorAll(".card")
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
                    !j ||
                    cardJenjang === j;

                const cocokAkreditasi =
                    !a ||
                    cardAkreditasi === a;

                card.style.display =
                    cocokNama &&
                    cocokJenjang &&
                    cocokAkreditasi
                    ? "block"
                    : "none";

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