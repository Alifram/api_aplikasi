<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);
include "koneksi.php";

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['judul_acara']) && isset($input['deskripsi']) && isset($input['target_kelas']) && isset($input['tanggal_acara']) && isset($input['dibuat_oleh'])) {
    
    $judul   = mysqli_real_escape_string($koneksi, $input['judul_acara']);
    $desk    = mysqli_real_escape_string($koneksi, $input['deskripsi']);
    $target  = mysqli_real_escape_string($koneksi, $input['target_kelas']);
    $tanggal = mysqli_real_escape_string($koneksi, $input['tanggal_acara']);
    $oleh    = mysqli_real_escape_string($koneksi, $input['dibuat_oleh']);
    
    $nama_gambar_final = null;

    // Menangkap gambar base64 yang dikirim dari galeri/kamera HP Android
    if (!empty($input['gambar_base64'])) {
        $img_data = base64_decode($input['gambar_base64']);
        $nama_gambar_final = "img_" . uniqid() . ".jpg";
        file_put_contents("uploads/" . $nama_gambar_final, $img_data);
    }

    $query = "INSERT INTO events (judul_acara, deskripsi, target_kelas, gambar_url, tanggal_acara, dibuat_oleh) 
              VALUES ('$judul', '$desk', '$target', '$nama_gambar_final', '$tanggal', '$oleh')";
              
    if (mysqli_query($koneksi, $query)) {
        echo json_encode(["status" => "success", "message" => "Berita acara berhasil dipublikasikan."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan ke database."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Data tidak lengkap."]);
}
?>
