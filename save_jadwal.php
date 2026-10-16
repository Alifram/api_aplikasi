<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include "koneksi.php";
include "jadwal_helpers.php";

$input = json_decode(file_get_contents('php://input'), true);
$kelasValid = ['Kelas 1', 'Kelas 2', 'Kelas 3', 'Kelas 4', 'Kelas 5', 'Kelas 6'];
$hariValid = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$required = ['kelas', 'hari', 'jam_mulai', 'jam_selesai', 'mata_pelajaran', 'guru_pengampu', 'ruangan'];

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Format JSON tidak valid."]);
    exit();
}
if (!isset($input['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Akun admin tidak valid."]);
    exit();
}
if (!jadwal_require_admin($koneksi, $input['user_id'])) {
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
$scheduleId = null;
if (isset($input['id'])) {
    $scheduleId = filter_var($input['id'], FILTER_VALIDATE_INT);
    if ($scheduleId === false || $scheduleId <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "ID jadwal tidak valid."]);
        exit();
    }
}
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

if ($scheduleId !== null) {
    $existing = mysqli_prepare(
        $koneksi,
        "SELECT id FROM jadwal_pelajaran WHERE id = ? LIMIT 1 FOR UPDATE"
    );
    if ($existing === false) {
        mysqli_rollback($koneksi);
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Gagal memeriksa jadwal yang akan diubah."]);
        exit();
    }
    mysqli_stmt_bind_param($existing, "i", $scheduleId);
    if (!mysqli_stmt_execute($existing)) {
        mysqli_stmt_close($existing);
        mysqli_rollback($koneksi);
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Gagal memeriksa jadwal yang akan diubah."]);
        exit();
    }
    $existingResult = mysqli_stmt_get_result($existing);
    $exists = mysqli_num_rows($existingResult) === 1;
    mysqli_stmt_close($existing);
    if (!$exists) {
        mysqli_rollback($koneksi);
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Jadwal yang akan diubah tidak ditemukan."]);
        exit();
    }
}

$conflictSql = "SELECT id FROM jadwal_pelajaran
     WHERE hari = ? AND (kelas = ? OR guru_pengampu = ?)
       AND jam_mulai < ? AND jam_selesai > ?";
if ($scheduleId !== null) {
    $conflictSql .= " AND id <> ?";
}
$conflictSql .= " LIMIT 1 FOR UPDATE";
$check = mysqli_prepare(
    $koneksi,
    $conflictSql
);
if ($check === false) {
    mysqli_rollback($koneksi);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal memeriksa bentrok jadwal."]);
    exit();
}

$bindTypes = $scheduleId === null ? "sssss" : "sssssi";
if ($scheduleId === null) {
    mysqli_stmt_bind_param($check, $bindTypes, $hari, $kelas, $guru, $selesai, $mulai);
} else {
    mysqli_stmt_bind_param($check, $bindTypes, $hari, $kelas, $guru, $selesai, $mulai, $scheduleId);
}
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

$sql = $scheduleId === null
    ? "INSERT INTO jadwal_pelajaran
       (kelas, hari, jam_mulai, jam_selesai, mata_pelajaran, guru_pengampu, ruangan)
       VALUES (?, ?, ?, ?, ?, ?, ?)"
    : "UPDATE jadwal_pelajaran
       SET kelas = ?, hari = ?, jam_mulai = ?, jam_selesai = ?,
           mata_pelajaran = ?, guru_pengampu = ?, ruangan = ?
       WHERE id = ?";
$save = mysqli_prepare($koneksi, $sql);
if ($save === false) {
    mysqli_rollback($koneksi);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal menyiapkan penyimpanan jadwal."]);
    exit();
}

if ($scheduleId === null) {
    mysqli_stmt_bind_param($save, "sssssss", $kelas, $hari, $mulai, $selesai, $mapel, $guru, $ruangan);
} else {
    mysqli_stmt_bind_param($save, "sssssssi", $kelas, $hari, $mulai, $selesai, $mapel, $guru, $ruangan, $scheduleId);
}
if (!mysqli_stmt_execute($save)) {
    mysqli_stmt_close($save);
    mysqli_rollback($koneksi);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal menyimpan jadwal ke database."]);
    exit();
}

mysqli_stmt_close($save);
if (!mysqli_commit($koneksi)) {
    mysqli_rollback($koneksi);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal menyelesaikan penyimpanan jadwal."]);
    exit();
}
echo json_encode([
    "status" => "success",
    "message" => $scheduleId === null ? "Jadwal berhasil disimpan." : "Jadwal berhasil diperbarui."
]);
mysqli_close($koneksi);
