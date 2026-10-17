<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include "koneksi.php";

$studentId = isset($_GET['siswa_id'])
    ? filter_var($_GET['siswa_id'], FILTER_VALIDATE_INT)
    : false;
if ($studentId === false || $studentId <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID siswa tidak valid."]);
    exit();
}

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT n.siswa_id, n.semester, k.nama AS kelas,
            n.tahun_angkatan, mp.nama AS mata_pelajaran,
            n.nilai_tugas, n.nilai_uts, n.nilai_uas, n.keterangan,
            guru.nama_lengkap AS nama_guru, n.kelas_mapel_id
     FROM nilai_raport n
     JOIN kelas_mapel km ON km.id = n.kelas_mapel_id
     JOIN kelas k ON k.id = km.kelas_id
     JOIN mata_pelajaran mp ON mp.id = km.mata_pelajaran_id
     JOIN users guru ON guru.id = n.guru_id
     WHERE n.siswa_id = ?
     ORDER BY n.tahun_angkatan ASC, k.nama ASC, n.semester ASC, mp.nama ASC"
);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal menyiapkan data rapor."]);
    exit();
}
mysqli_stmt_bind_param($stmt, "i", $studentId);
if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal mengambil data rapor."]);
    exit();
}

$result = mysqli_stmt_get_result($stmt);
$grades = [];
while ($row = mysqli_fetch_assoc($result)) {
    $grades[] = $row;
}
mysqli_stmt_close($stmt);

echo json_encode(["status" => "success", "data" => $grades]);
mysqli_close($koneksi);
?>
