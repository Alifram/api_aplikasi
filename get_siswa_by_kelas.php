<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);
include "koneksi.php";

// Menangkap parameter kelas yang dikirim dari Flutter Guru
$kelas = isset($_GET['kelas']) ? mysqli_real_escape_string($koneksi, $_GET['kelas']) : '';

if (!empty($kelas)) {
    // Mencari user yang rolenya siswa dan berada di kelas yang sesuai
    $query = "SELECT id, nomor_induk, nama_lengkap, kelas_diajar, tahun_angkatan FROM users WHERE role = 'siswa' AND kelas_diajar = '$kelas' AND is_deleted = 0 ORDER BY nama_lengkap ASC";
    $result = mysqli_query($koneksi, $query);
    $siswa = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $siswa[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $siswa]);
} else {
    echo json_encode(["status" => "error", "message" => "Parameter kelas tidak ditemukan."]);
}
?>