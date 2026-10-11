<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);
include "koneksi.php";

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['event_id']) && isset($input['role_user']) && isset($input['nama_user'])) {
    $event_id  = mysqli_real_escape_string($koneksi, $input['event_id']);
    $role_user = mysqli_real_escape_string($koneksi, $input['role_user']);
    $nama_user = mysqli_real_escape_string($koneksi, $input['nama_user']);
    
    // 1. Ambil data berita untuk mengecek siapa pembuat aslinya
    $cek = mysqli_query($koneksi, "SELECT dibuat_oleh, gambar_url FROM events WHERE id = '$event_id'");
    
    if (mysqli_num_rows($cek) == 0) {
        echo json_encode(["status" => "error", "message" => "Postingan tidak ditemukan."]);
        exit();
    }
    
    $data = mysqli_fetch_assoc($cek);
    
    // 2. VALIDASI KEAMANAN: Guru hanya boleh menghapus postingannya sendiri, sedangkan Admin bebas menghapus semuanya
    if ($role_user !== 'admin' && $data['dibuat_oleh'] !== $nama_user) {
        echo json_encode([
            "status"  => "error",
            "message" => "Akses ditolak! Anda tidak diperbolehkan menghapus postingan milik orang lain."
        ]);
        exit();
    }
    
    // Hapus gambar fisik di folder uploads jika ada
    if (!empty($data['gambar_url']) && file_exists("uploads/" . $data['gambar_url'])) {
        unlink("uploads/" . $data['gambar_url']);
    }

    // Jalankan perintah hapus dari tabel database
    $query = "DELETE FROM events WHERE id = '$event_id'";
    if (mysqli_query($koneksi, $query)) {
        echo json_encode(["status" => "success", "message" => "Postingan berita kegiatan berhasil dihapus."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal menghapus dari database."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Parameter identitas hapus tidak lengkap."]);
}
?>
