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
$adminId = is_array($input) && isset($input['user_id'])
    ? filter_var($input['user_id'], FILTER_VALIDATE_INT)
    : false;
if ($adminId === false || $adminId <= 0) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Akun admin tidak valid."]);
    exit();
}

$admin = mysqli_prepare(
    $koneksi,
    "SELECT id FROM users WHERE id = ? AND role = 'admin' AND is_deleted = 0 LIMIT 1"
);
if ($admin === false) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal memeriksa akun admin."]);
    exit();
}
mysqli_stmt_bind_param($admin, "i", $adminId);
if (!mysqli_stmt_execute($admin)) {
    mysqli_stmt_close($admin);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal memeriksa akun admin."]);
    exit();
}
$isAdmin = mysqli_num_rows(mysqli_stmt_get_result($admin)) === 1;
mysqli_stmt_close($admin);
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Hanya admin yang dapat menaikkan kelas."]);
    exit();
}

$classIds = [];
$classQuery = mysqli_prepare($koneksi, "SELECT id FROM kelas WHERE nama = ? AND aktif = 1 LIMIT 1");
if ($classQuery === false) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal menyiapkan daftar kelas."]);
    exit();
}
foreach (['Kelas 1', 'Kelas 2', 'Kelas 3', 'Kelas 4', 'Kelas 5', 'Kelas 6', 'Alumni'] as $name) {
    mysqli_stmt_bind_param($classQuery, "s", $name);
    if (!mysqli_stmt_execute($classQuery)) {
        mysqli_stmt_close($classQuery);
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Gagal mengambil daftar kelas."]);
        exit();
    }
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($classQuery));
    if ($row === null) {
        mysqli_stmt_close($classQuery);
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Kelas aktif '$name' wajib tersedia sebelum kenaikan kelas massal."]);
        exit();
    }
    $classIds[$name] = (int)$row['id'];
}
mysqli_stmt_close($classQuery);

mysqli_begin_transaction($koneksi);
$update = mysqli_prepare(
    $koneksi,
    "UPDATE users SET kelas_id = ? WHERE role = 'siswa' AND kelas_id = ? AND is_deleted = 0"
);
if ($update === false) {
    mysqli_rollback($koneksi);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal menyiapkan kenaikan kelas."]);
    exit();
}
$changes = [
    ['Kelas 6', 'Alumni'],
    ['Kelas 5', 'Kelas 6'],
    ['Kelas 4', 'Kelas 5'],
    ['Kelas 3', 'Kelas 4'],
    ['Kelas 2', 'Kelas 3'],
    ['Kelas 1', 'Kelas 2']
];
foreach ($changes as [$from, $to]) {
    $targetId = $classIds[$to];
    $sourceId = $classIds[$from];
    mysqli_stmt_bind_param($update, "ii", $targetId, $sourceId);
    if (!mysqli_stmt_execute($update)) {
        mysqli_stmt_close($update);
        mysqli_rollback($koneksi);
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Gagal menaikkan siswa dari $from ke $to."]);
        exit();
    }
}
mysqli_stmt_close($update);
if (!mysqli_commit($koneksi)) {
    mysqli_rollback($koneksi);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal menyelesaikan kenaikan kelas."]);
    exit();
}

echo json_encode(["status" => "success", "message" => "Kenaikan kelas massal berhasil."]);
mysqli_close($koneksi);
?>
