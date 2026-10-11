<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);
include "koneksi.php";

$kelas = isset($_GET['kelas']) ? mysqli_real_escape_string($koneksi, $_GET['kelas']) : '';

if (!empty($kelas)) {
    // Mengambil semua siswa yang berada di kelas yang sama
    $query = "SELECT nomor_induk, nama_lengkap, email FROM users WHERE role = 'siswa' AND kelas_diajar = '$kelas' ORDER BY nama_lengkap ASC";
    $result = mysqli_query($koneksi, $query);
    $teman = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $teman[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $teman]);
} else {
    echo json_encode(["status" => "error", "message" => "Parameter kelas tidak ditemukan."]);
}
?>