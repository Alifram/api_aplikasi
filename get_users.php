<?php
// 1. PENGATURAN AKSES KEAMANAN BROWSER (CORS POLICY)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Menyertakan file koneksi database bawaan Anda
include "koneksi.php";

// 2. PERBAIKAN KUERI: Menarik kolom tahun_angkatan & memblokir akun terhapus (is_deleted = 1)
$query = "SELECT id, nomor_induk, nama_lengkap, email, role, kelas_diajar, tahun_angkatan, is_deleted 
          FROM users 
          WHERE is_deleted = 0 
          ORDER BY role ASC, nama_lengkap ASC";

$result = mysqli_query($koneksi, $query);

$users = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // PERBAIKAN SENSITIVITAS DATA: Membersihkan spasi tak terlihat di awal/akhir string teks database
        $row['id']             = (int)$row['id'];
        $row['nomor_induk']    = trim($row['nomor_induk']);
        $row['nama_lengkap']   = trim($row['nama_lengkap']);
        $row['email']          = trim($row['email']);
        $row['role']           = trim($row['role']);
        $row['kelas_diajar']   = trim($row['kelas_diajar']);
        
        // Memastikan tipe data tahun angkatan dikirim dalam wujud Integer murni ke Flutter
        $row['tahun_angkatan'] = ($row['tahun_angkatan'] !== null) ? (int)$row['tahun_angkatan'] : null;
        
        $users[] = $row;
    }
    
    // Mengirim respon sukses beserta seluruh array data pengguna yang valid ke Flutter
    echo json_encode([
        "status" => "success",
        "data" => $users
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Gagal mengambil data dari database: " . mysqli_error($koneksi)
    ]);
}

mysqli_close($koneksi);
?>
