<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);
include "koneksi.php";

// Ditambahkan ORDER BY tanggal_acara DESC, id DESC agar yang paling baru otomatis di atas
$query = "SELECT * FROM events ORDER BY tanggal_acara DESC, id DESC";
$result = mysqli_query($koneksi, $query);
$events = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $events]);
} else {
    echo json_encode(["status" => "error", "message" => mysqli_error($koneksi)]);
}
?>