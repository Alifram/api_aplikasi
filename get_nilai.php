<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include "koneksi.php";

// Menangkap parameter user_id dan role dari Flutter
$user_id = isset($_GET['user_id']) ? mysqli_real_escape_string($koneksi, $_GET['user_id']) : '';
$role    = isset($_GET['role']) ? mysqli_real_escape_string($koneksi, $_GET['role']) : '';

if (empty($user_id) || empty($role)) {
    echo json_encode(["status" => "error", "message" => "Parameter tidak lengkap."]);
    exit();
}

$data_nilai = [];

if ($role == 'siswa') {
    // Jika yang diklik akun SISWA, ambil data nilai siswa tersebut beserta nama guru yang menilai
    $query = "SELECT n.*, u.nama_lengkap as nama_guru 
              FROM nilai_raport n
              JOIN users u ON n.guru_id = u.id
              WHERE n.siswa_id = '$user_id' 
              ORDER BY n.semester ASC, n.mata_pelajaran ASC";
              
    $result = mysqli_query($koneksi, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $data_nilai[] = $row;
    }
    
    echo json_encode(["status" => "success", "type" => "siswa", "data" => $data_nilai]);

} else if ($role == 'guru') {
    // Jika yang diklik akun GURU, ambil daftar nilai yang pernah dimasukkan oleh guru tersebut beserta nama siswanya
    $query = "SELECT n.*, u.nama_lengkap as nama_siswa 
              FROM nilai_raport n
              JOIN users u ON n.siswa_id = u.id
              WHERE n.guru_id = '$user_id' 
              ORDER BY n.semester ASC, u.nama_lengkap ASC";
              
    $result = mysqli_query($koneksi, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $data_nilai[] = $row;
    }
    
    echo json_encode(["status" => "success", "type" => "guru", "data" => $data_nilai]);
} else {
    echo json_encode(["status" => "success", "type" => "admin", "data" => []]);
}
?>
