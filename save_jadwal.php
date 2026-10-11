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
$kelasValid = ['Kelas 1', 'Kelas 2', 'Kelas 3', 'Kelas 4', 'Kelas 5', 'Kelas 6'];
$hariValid = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$required = ['kelas', 'hari', 'jam_mulai', 'jam_selesai', 'mata_pelajaran', 'guru_pengampu', 'ruangan'];

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Format JSON tidak valid."]);
    exit();
}
foreach ($required as $field) {
    if (!isset($input[$field]) || !is_scalar($input[$field]) || trim((string)$input[$field]) === '') {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Data jadwal belum lengkap."]);
        exit();
    }
}

$kelas = trim($input['kelas']);
$hari = trim($input['hari']);
$mulai = trim($input['jam_mulai']);
$selesai = trim($input['jam_selesai']);
$mulai = strlen($mulai) === 5 ? $mulai . ':00' : $mulai;
$selesai = strlen($selesai) === 5 ? $selesai . ':00' : $selesai;
$mapel = trim($input['mata_pelajaran']);
$guru = trim($input['guru_pengampu']);
$ruangan = trim($input['ruangan']);
$timePattern = '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/';

if (!in_array($kelas, $kelasValid, true) ||
    !in_array($hari, $hariValid, true) ||
    !preg_match($timePattern, $mulai) ||
    !preg_match($timePattern, $selesai) ||
    $mulai >= $selesai) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Kelas, hari, atau rentang waktu jadwal tidak valid."]);
    exit();
}

mysqli_begin_transaction($koneksi);

$check = mysqli_prepare(
    $koneksi,
    "SELECT id FROM jadwal_pelajaran
     WHERE hari = ? AND (kelas = ? OR guru_pengampu = ?)
       AND jam_mulai < ? AND jam_selesai > ?
     LIMIT 1 FOR UPDATE"
);
if ($check === false) {
    mysqli_rollback($koneksi);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal memeriksa bentrok jadwal."]);
    exit();
}

mysqli_stmt_bind_param($check, "sssss", $hari, $kelas, $guru, $selesai, $mulai);
if (!mysqli_stmt_execute($check)) {
    mysqli_stmt_close($check);
    mysqli_rollback($koneksi);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal memeriksa bentrok jadwal."]);
    exit();
}
$conflicts = mysqli_stmt_get_result($check);
$hasConflict = mysqli_num_rows($conflicts) > 0;
mysqli_stmt_close($check);

if ($hasConflict) {
    mysqli_rollback($koneksi);
    http_response_code(409);
    echo json_encode(["status" => "error", "message" => "Jadwal bertabrakan dengan kelas atau jam mengajar guru yang sudah ada."]);
    exit();
}

$insert = mysqli_prepare(
    $koneksi,
    "INSERT INTO jadwal_pelajaran (kelas, hari, jam_mulai, jam_selesai, mata_pelajaran, guru_pengampu, ruangan)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
if ($insert === false) {
    mysqli_rollback($koneksi);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal menyiapkan penyimpanan jadwal."]);
    exit();
}

mysqli_stmt_bind_param($insert, "sssssss", $kelas, $hari, $mulai, $selesai, $mapel, $guru, $ruangan);
if (!mysqli_stmt_execute($insert)) {
    mysqli_stmt_close($insert);
    mysqli_rollback($koneksi);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal menyimpan jadwal ke database."]);
    exit();
}

mysqli_stmt_close($insert);
mysqli_commit($koneksi);
echo json_encode(["status" => "success", "message" => "Jadwal berhasil disimpan."]);
mysqli_close($koneksi);
