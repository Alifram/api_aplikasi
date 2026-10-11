<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Menyertakan file koneksi database bawaan Anda
include 'koneksi.php'; 

// Tangkap parameter angkatan dari Flutter (Otomatis ke tahun berjalan jika kosong)
$angkatan = isset($_GET['angkatan']) ? mysqli_real_escape_string($koneksi, $_GET['angkatan']) : date("Y");

// Inisialisasi struktur template data agar Flutter tidak crash
$responData = [];
for ($i = 1; $i <= 6; $i++) {
    $responData["Kelas $i"] = [
        "semester_1" => 0.0,
        "semester_2" => 0.0
    ];
}

// Kolom NULL berarti belum diinput; nilai 0 tetap dihitung sebagai nilai yang sah.
$query = "SELECT 
            CASE TRIM(kelas)
              WHEN '1' THEN 'Kelas 1'
              WHEN '2' THEN 'Kelas 2'
              WHEN '3' THEN 'Kelas 3'
              WHEN '4' THEN 'Kelas 4'
              WHEN '5' THEN 'Kelas 5'
              WHEN '6' THEN 'Kelas 6'
              ELSE TRIM(kelas)
            END AS nama_kelas,
            semester AS nomor_semester,
            SUM(COALESCE(nilai_tugas, 0) + COALESCE(nilai_uts, 0) + COALESCE(nilai_uas, 0)) /
              NULLIF(SUM((nilai_tugas IS NOT NULL) + (nilai_uts IS NOT NULL) + (nilai_uas IS NOT NULL)), 0) AS rerata_nilai
          FROM nilai_raport 
          WHERE tahun_angkatan = '$angkatan'
          GROUP BY nama_kelas, semester";

$result = mysqli_query($koneksi, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $kelasKey = trim($row['nama_kelas']); 
        $semId = ((int)$row['nomor_semester'] === 1) ? "semester_1" : "semester_2";
        
        if (isset($responData[$kelasKey])) {
            $responData[$kelasKey][$semId] = round((double)$row['rerata_nilai'], 1);
        }
    }

    echo json_encode([
        "status" => "success",
        "data" => $responData
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Gagal memproses data statistik: " . mysqli_error($koneksi)
    ]);
}
?>
