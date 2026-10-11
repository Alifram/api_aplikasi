<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include "koneksi.php";

$kelas = isset($_GET['kelas']) ? trim($_GET['kelas']) : '';
$hari = isset($_GET['hari']) ? trim($_GET['hari']) : '';
$guru = isset($_GET['guru']) ? trim($_GET['guru']) : '';
$kelasValid = ['Kelas 1', 'Kelas 2', 'Kelas 3', 'Kelas 4', 'Kelas 5', 'Kelas 6'];
$hariValid = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

if (($kelas !== '' && !in_array($kelas, $kelasValid, true)) ||
    ($hari !== '' && !in_array($hari, $hariValid, true))) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Filter kelas atau hari tidak valid."]);
    exit();
}

$filters = [];
if ($kelas !== '') {
    $filters[] = "kelas = '" . mysqli_real_escape_string($koneksi, $kelas) . "'";
}
if ($hari !== '') {
    $filters[] = "hari = '" . mysqli_real_escape_string($koneksi, $hari) . "'";
}
if ($guru !== '') {
    $filters[] = "guru_pengampu = '" . mysqli_real_escape_string($koneksi, $guru) . "'";
}

$where = count($filters) > 0 ? "WHERE " . implode(" AND ", $filters) : "";
$query = "SELECT id, kelas, hari, jam_mulai, jam_selesai,
                 TIME_FORMAT(jam_mulai, '%H:%i') AS jam_mulai_format,
                 TIME_FORMAT(jam_selesai, '%H:%i') AS jam_selesai_format,
                 mata_pelajaran, guru_pengampu, ruangan
          FROM jadwal_pelajaran
          $where
          ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), jam_mulai ASC";
$result = mysqli_query($koneksi, $query);

if ($result === false) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal mengambil jadwal pelajaran."]);
    exit();
}

$jadwal = [];
while ($row = mysqli_fetch_assoc($result)) {
    $jadwal[] = $row;
}

echo json_encode(["status" => "success", "data" => $jadwal]);
mysqli_close($koneksi);
