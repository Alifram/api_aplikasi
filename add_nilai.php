<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include "koneksi.php";

$input = json_decode(file_get_contents('php://input'), true);
$fields = [
    'siswa_id', 'guru_id', 'semester', 'kelas', 'tahun_angkatan',
    'mata_pelajaran', 'jenis_nilai', 'skor_nilai'
];

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Format JSON tidak valid."]);
    exit();
}
foreach ($fields as $field) {
    if (!isset($input[$field]) || !is_scalar($input[$field]) || trim((string)$input[$field]) === '') {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Data input nilai tidak lengkap."]);
        exit();
    }
}

$siswaId = filter_var($input['siswa_id'], FILTER_VALIDATE_INT);
$guruId = filter_var($input['guru_id'], FILTER_VALIDATE_INT);
$year = filter_var($input['tahun_angkatan'], FILTER_VALIDATE_INT);
$semesterLabel = trim($input['semester']);
$kelas = trim($input['kelas']);
$mapel = trim($input['mata_pelajaran']);
$jenis = $input['jenis_nilai'];
$skor = filter_var($input['skor_nilai'], FILTER_VALIDATE_FLOAT);
$keterangan = isset($input['keterangan']) ? trim($input['keterangan']) : '';
$classes = ['Kelas 1', 'Kelas 2', 'Kelas 3', 'Kelas 4', 'Kelas 5', 'Kelas 6'];
$gradeColumns = ['tugas' => 'nilai_tugas', 'uts' => 'nilai_uts', 'uas' => 'nilai_uas'];

if ($siswaId === false || $siswaId <= 0 ||
    $guruId === false || $guruId <= 0 ||
    $year === false || $year <= 0 ||
    !in_array($semesterLabel, ['Semester 1', 'Semester 2'], true) ||
    !in_array($kelas, $classes, true) ||
    !isset($gradeColumns[$jenis]) ||
    $skor === false || $skor < 0 || $skor > 100 ||
    $mapel === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Data kelas, angkatan, semester, mapel, atau skor tidak valid."]);
    exit();
}

$semester = $semesterLabel === 'Semester 1' ? 1 : 2;
$column = $gradeColumns[$jenis];
mysqli_begin_transaction($koneksi);

$find = mysqli_prepare(
    $koneksi,
    "SELECT id FROM nilai_raport
     WHERE siswa_id = ? AND semester = ? AND kelas = ?
       AND tahun_angkatan = ? AND mata_pelajaran = ?
     ORDER BY id DESC LIMIT 1 FOR UPDATE"
);
if ($find === false) {
    mysqli_rollback($koneksi);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal menyiapkan pencarian nilai."]);
    exit();
}

mysqli_stmt_bind_param($find, "iisis", $siswaId, $semester, $kelas, $year, $mapel);
if (!mysqli_stmt_execute($find)) {
    mysqli_stmt_close($find);
    mysqli_rollback($koneksi);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal mencari data nilai."]);
    exit();
}

$result = mysqli_stmt_get_result($find);
$existing = mysqli_fetch_assoc($result);
mysqli_stmt_close($find);

if ($existing !== null) {
    $recordId = (int)$existing['id'];
    $updateSql = $keterangan === ''
        ? "UPDATE nilai_raport SET $column = ?, guru_id = ? WHERE id = ?"
        : "UPDATE nilai_raport SET $column = ?, guru_id = ?, keterangan = ? WHERE id = ?";
    $update = mysqli_prepare(
        $koneksi,
        $updateSql
    );
    if ($update === false) {
        mysqli_rollback($koneksi);
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Gagal menyiapkan pembaruan nilai."]);
        exit();
    }
    if ($keterangan === '') {
        mysqli_stmt_bind_param($update, "dii", $skor, $guruId, $recordId);
    } else {
        mysqli_stmt_bind_param($update, "disi", $skor, $guruId, $keterangan, $recordId);
    }
    $saved = mysqli_stmt_execute($update);
    mysqli_stmt_close($update);
} else {
    $tugas = $jenis === 'tugas' ? $skor : null;
    $uts = $jenis === 'uts' ? $skor : null;
    $uas = $jenis === 'uas' ? $skor : null;
    $insert = mysqli_prepare(
        $koneksi,
        "INSERT INTO nilai_raport
         (siswa_id, guru_id, semester, kelas, tahun_angkatan, mata_pelajaran,
          nilai_tugas, nilai_uts, nilai_uas, keterangan)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if ($insert === false) {
        mysqli_rollback($koneksi);
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Gagal menyiapkan penyimpanan nilai."]);
        exit();
    }
    mysqli_stmt_bind_param(
        $insert,
        "iiisisddds",
        $siswaId,
        $guruId,
        $semester,
        $kelas,
        $year,
        $mapel,
        $tugas,
        $uts,
        $uas,
        $keterangan
    );
    $saved = mysqli_stmt_execute($insert);
    mysqli_stmt_close($insert);
}

if (!$saved) {
    mysqli_rollback($koneksi);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal menyimpan nilai ke database."]);
    exit();
}

mysqli_commit($koneksi);
echo json_encode([
    "status" => "success",
    "message" => "Nilai " . strtoupper($jenis) . " berhasil disimpan ke rapor digital."
]);
mysqli_close($koneksi);
