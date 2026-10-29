<?php
mysqli_report(MYSQLI_REPORT_OFF);
$koneksi = @mysqli_connect("localhost", "root", "", "db_nilai_mahasiswa");

function aman($teks) {
    return htmlspecialchars($teks, ENT_QUOTES);
}

function formatAngka($angka) {
    return number_format($angka, 2, ",", ".");
}

function cekNilaiValid($nilai) {
    if (is_numeric($nilai) && $nilai >= 0 && $nilai <= 100) {
        return true;
    } else {
        return false;
    }
}

function hitungNilaiAkhir($tugas, $uts, $uas) {
    $hasil = (($tugas * 30) + ($uts * 30) + ($uas * 40)) / 100;
    return round($hasil, 2);
}

function tentukanGrade($nilaiAkhir) {
    if ($nilaiAkhir >= 85) {
        return "A";
    } elseif ($nilaiAkhir >= 75) {
        return "B";
    } elseif ($nilaiAkhir >= 65) {
        return "C";
    } elseif ($nilaiAkhir >= 50) {
        return "D";
    } else {
        return "E";
    }
}

function tentukanStatus($nilaiAkhir) {
    if ($nilaiAkhir >= 65) {
        return "Lulus";
    } else {
        return "Tidak Lulus";
    }
}

function tentukanPredikat($nilaiAkhir) {
    if ($nilaiAkhir >= 85) {
        return "Sangat Baik";
    } elseif ($nilaiAkhir >= 75) {
        return "Baik";
    } elseif ($nilaiAkhir >= 65) {
        return "Cukup";
    } elseif ($nilaiAkhir >= 50) {
        return "Kurang";
    } else {
        return "Sangat Kurang";
    }
}

$jumlahBaris = 5;

$daftarMahasiswa = array(
    array("nama" => "Andi", "nim" => "231234567", "tugas" => 80, "uts" => 75, "uas" => 90),
    array("nama" => "Budi", "nim" => "231234568", "tugas" => 60, "uts" => 65, "uas" => 70),
    array("nama" => "Citra", "nim" => "231234569", "tugas" => 40, "uts" => 45, "uas" => 50)
);

$dataForm = array();
for ($i = 0; $i < $jumlahBaris; $i++) {
    $dataForm[$i] = array("nama" => "", "nim" => "", "tugas" => "", "uts" => "", "uas" => "");
}
for ($i = 0; $i < count($daftarMahasiswa); $i++) {
    $dataForm[$i] = $daftarMahasiswa[$i];
}

$pesanError = array();
$pesanDb = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $daftarMahasiswa = array();

    for ($i = 0; $i < $jumlahBaris; $i++) {
        $nama = trim($_POST["nama"][$i]);
        $nim = trim($_POST["nim"][$i]);
        $tugas = trim($_POST["tugas"][$i]);
        $uts = trim($_POST["uts"][$i]);
        $uas = trim($_POST["uas"][$i]);

        $dataForm[$i] = array("nama" => $nama, "nim" => $nim, "tugas" => $tugas, "uts" => $uts, "uas" => $uas);

        if ($nama == "" && $nim == "" && $tugas == "" && $uts == "" && $uas == "") {
            continue;
        }

        $baris = $i + 1;
        $adaError = false;

        if ($nama == "" || $nim == "") {
            array_push($pesanError, "Baris " . $baris . ": nama dan NIM tidak boleh kosong.");
            $adaError = true;
        }
        if (!cekNilaiValid($tugas)) {
            array_push($pesanError, "Baris " . $baris . ": nilai tugas harus angka antara 0 sampai 100.");
            $adaError = true;
        }
        if (!cekNilaiValid($uts)) {
            array_push($pesanError, "Baris " . $baris . ": nilai UTS harus angka antara 0 sampai 100.");
            $adaError = true;
        }
        if (!cekNilaiValid($uas)) {
            array_push($pesanError, "Baris " . $baris . ": nilai UAS harus angka antara 0 sampai 100.");
            $adaError = true;
        }

        if (!$adaError) {
            array_push($daftarMahasiswa, array(
                "nama" => $nama,
                "nim" => $nim,
                "tugas" => (float)$tugas,
                "uts" => (float)$uts,
                "uas" => (float)$uas
            ));
        }
    }

    if (count($daftarMahasiswa) > 0) {
        if ($koneksi) {
            $jumlahTersimpan = 0;
            foreach ($daftarMahasiswa as $mhs) {
                $namaSimpan = $mhs["nama"];
                $nimSimpan = $mhs["nim"];
                $tugasSimpan = $mhs["tugas"];
                $utsSimpan = $mhs["uts"];
                $uasSimpan = $mhs["uas"];
                $nilaiAkhirSimpan = hitungNilaiAkhir($tugasSimpan, $utsSimpan, $uasSimpan);
                $gradeSimpan = tentukanGrade($nilaiAkhirSimpan);
                $statusSimpan = tentukanStatus($nilaiAkhirSimpan);

                $perintah = mysqli_prepare($koneksi, "INSERT INTO mahasiswa (nama, nim, tugas, uts, uas, nilai_akhir, grade, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($perintah) {
                    mysqli_stmt_bind_param($perintah, "ssddddss", $namaSimpan, $nimSimpan, $tugasSimpan, $utsSimpan, $uasSimpan, $nilaiAkhirSimpan, $gradeSimpan, $statusSimpan);
                    if (mysqli_stmt_execute($perintah)) {
                        $jumlahTersimpan++;
                    }
                    mysqli_stmt_close($perintah);
                }
            }
            if ($jumlahTersimpan == count($daftarMahasiswa)) {
                $pesanDb = $jumlahTersimpan . " data berhasil disimpan ke database.";
            } else {
                $pesanDb = "Sebagian data gagal disimpan. Cek tabel mahasiswa di phpMyAdmin.";
            }
        } else {
            $pesanDb = "Database belum tersambung, jadi data hanya dihitung dan tidak disimpan.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Penghitung Nilai Mahasiswa</title>
<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f2f4f8;
    margin: 0;
    padding: 20px;
    color: #222;
}
.wadah {
    max-width: 950px;
    margin: 0 auto;
}
h1 {
    text-align: center;
    color: #2c5fa8;
}
.kotak {
    background-color: white;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}
.tabel-geser {
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: center;
}
th {
    background-color: #2c5fa8;
    color: white;
}
td.kiri {
    text-align: left;
}
input {
    width: 90%;
    padding: 6px;
    box-sizing: border-box;
}
button {
    margin-top: 15px;
    padding: 10px 25px;
    background-color: #2c5fa8;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
}
button:hover {
    background-color: #1f4680;
}
.lulus {
    color: green;
    font-weight: bold;
}
.tidak {
    color: red;
    font-weight: bold;
}
.error {
    background-color: #ffe0e0;
    border: 1px solid #d33;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 5px;
}
.info {
    background-color: #e3f0ff;
    border: 1px solid #2c5fa8;
    padding: 10px;
    margin-top: 10px;
    border-radius: 5px;
}
</style>
</head>
<body>
<div class="wadah">
    <h1>Program Penghitung Nilai Mahasiswa</h1>

    <div class="kotak">
        <h2>Input Data Mahasiswa</h2>
        <p>Bobot: Tugas 30%, UTS 30%, UAS 40%. Nilai harus 0 sampai 100 (pakai titik untuk desimal, contoh 82.5). Baris yang kosong diabaikan.</p>
        <?php
        foreach ($pesanError as $pesan) {
            echo "<div class='error'>" . $pesan . "</div>";
        }
        ?>
        <form method="post" action="index.php">
            <div class="tabel-geser">
            <table>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIM</th>
                    <th>Nilai Tugas</th>
                    <th>Nilai UTS</th>
                    <th>Nilai UAS</th>
                </tr>
                <?php
                for ($i = 0; $i < $jumlahBaris; $i++) {
                    $d = $dataForm[$i];
                    echo "<tr>";
                    echo "<td>" . ($i + 1) . "</td>";
                    echo "<td><input type='text' name='nama[]' value='" . aman($d["nama"]) . "'></td>";
                    echo "<td><input type='text' name='nim[]' value='" . aman($d["nim"]) . "'></td>";
                    echo "<td><input type='text' name='tugas[]' value='" . aman($d["tugas"]) . "'></td>";
                    echo "<td><input type='text' name='uts[]' value='" . aman($d["uts"]) . "'></td>";
                    echo "<td><input type='text' name='uas[]' value='" . aman($d["uas"]) . "'></td>";
                    echo "</tr>";
                }
                ?>
            </table>
            </div>
            <button type="submit">Hitung Nilai</button>
        </form>
    </div>

    <div class="kotak">
        <h2>Hasil Perhitungan</h2>
        <p>Rumus: Nilai Akhir = (Tugas x 30%) + (UTS x 30%) + (UAS x 40%). Lulus jika nilai akhir minimal 65.</p>
        <p>Jumlah mahasiswa yang dihitung: <?php echo count($daftarMahasiswa); ?></p>
        <?php
        if (count($daftarMahasiswa) == 0) {
            echo "<p>Belum ada data valid yang bisa dihitung.</p>";
        } else {
            echo "<div class='tabel-geser'><table>";
            echo "<tr>";
            echo "<th>No</th><th>Nama</th><th>NIM</th><th>Tugas</th><th>UTS</th><th>UAS</th>";
            echo "<th>Nilai Akhir</th><th>Grade</th><th>Predikat</th><th>Status</th>";
            echo "</tr>";

            $nomor = 1;
            foreach ($daftarMahasiswa as $mhs) {
                $nama = $mhs["nama"];
                $nim = $mhs["nim"];
                $tugas = $mhs["tugas"];
                $uts = $mhs["uts"];
                $uas = $mhs["uas"];

                $nilaiAkhir = hitungNilaiAkhir($tugas, $uts, $uas);
                $grade = tentukanGrade($nilaiAkhir);
                $status = tentukanStatus($nilaiAkhir);
                $predikat = tentukanPredikat($nilaiAkhir);

                if ($status == "Lulus") {
                    $kelas = "lulus";
                } else {
                    $kelas = "tidak";
                }

                echo "<tr>";
                echo "<td>" . $nomor . "</td>";
                echo "<td class='kiri'>" . aman($nama) . "</td>";
                echo "<td>" . aman($nim) . "</td>";
                echo "<td>" . formatAngka($tugas) . "</td>";
                echo "<td>" . formatAngka($uts) . "</td>";
                echo "<td>" . formatAngka($uas) . "</td>";
                echo "<td><b>" . formatAngka($nilaiAkhir) . "</b></td>";
                echo "<td>" . $grade . "</td>";
                echo "<td>" . $predikat . "</td>";
                echo "<td class='" . $kelas . "'>" . $status . "</td>";
                echo "</tr>";

                $nomor++;
            }

            echo "</table></div>";
        }

        if ($pesanDb != "") {
            echo "<div class='info'>" . $pesanDb . "</div>";
        }
        ?>
    </div>

    <div class="kotak">
        <h2>Data Tersimpan di Database</h2>
        <?php
        $hasilDb = false;
        if ($koneksi) {
            $hasilDb = mysqli_query($koneksi, "SELECT * FROM mahasiswa ORDER BY id DESC");
        }

        if (!$hasilDb) {
            echo "<p>Database atau tabel mahasiswa belum bisa dibaca. Pastikan MySQL hidup dan tabel sudah dibuat di phpMyAdmin.</p>";
        } else {
            echo "<div class='tabel-geser'><table>";
            echo "<tr>";
            echo "<th>ID</th><th>Nama</th><th>NIM</th><th>Tugas</th><th>UTS</th><th>UAS</th>";
            echo "<th>Nilai Akhir</th><th>Grade</th><th>Status</th>";
            echo "</tr>";
            while ($baris = mysqli_fetch_assoc($hasilDb)) {
                echo "<tr>";
                echo "<td>" . $baris["id"] . "</td>";
                echo "<td class='kiri'>" . aman($baris["nama"]) . "</td>";
                echo "<td>" . aman($baris["nim"]) . "</td>";
                echo "<td>" . formatAngka($baris["tugas"]) . "</td>";
                echo "<td>" . formatAngka($baris["uts"]) . "</td>";
                echo "<td>" . formatAngka($baris["uas"]) . "</td>";
                echo "<td>" . formatAngka($baris["nilai_akhir"]) . "</td>";
                echo "<td>" . aman($baris["grade"]) . "</td>";
                echo "<td>" . aman($baris["status"]) . "</td>";
                echo "</tr>";
            }
            echo "</table></div>";
        }
        ?>
    </div>
</div>
</body>
</html>