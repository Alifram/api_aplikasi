<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);
include "koneksi.php";

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['user_id']) && isset($input['kelas_baru'])) {
    $user_id    = mysqli_real_escape_string($koneksi, $input['user_id']);
    $kelas_baru = mysqli_real_escape_string($koneksi, $input['kelas_baru']);

    // Mengubah kelas pengguna di database (Bisa untuk naik kelas siswa)
    $query = "UPDATE users SET kelas_diajar = '$kelas_baru' WHERE id = '$user_id'";
    
    if (mysqli_query($koneksi, $query)) {
        echo json_encode(["status" => "success", "message" => "Pemindahan jenjang kelas berhasil diperbarui."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal mengubah kelas."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Data tidak lengkap."]);
}
?>