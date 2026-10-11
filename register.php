<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// 1. MENYERTAKAN KONEKSI DATABASE BAWAAN
include 'koneksi.php';

// Menangkap kiriman data JSON dari aplikasi Flutter
$input = json_decode(file_get_contents('php://input'), true);

if (
    isset($input['nomor_induk']) && 
    isset($input['nama_lengkap']) && 
    isset($input['email']) && 
    isset($input['password']) && 
    isset($input['role'])
) {
    foreach (['nomor_induk', 'nama_lengkap', 'email', 'role'] as $field) {
        if (!is_string($input[$field])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Format data pendaftaran tidak valid."]);
            exit();
        }
    }
    // Sanitasi data input untuk mencegah celah SQL Injection
    $nomor_induk   = mysqli_real_escape_string($koneksi, $input['nomor_induk']);
    $nama_lengkap  = mysqli_real_escape_string($koneksi, $input['nama_lengkap']);
    $email         = mysqli_real_escape_string($koneksi, $input['email']);
    $role          = mysqli_real_escape_string($koneksi, $input['role']);
    if (!is_string($input['password']) || strlen($input['password']) < 6) {
        echo json_encode(["status" => "error", "message" => "Password minimal 6 karakter."]);
        exit();
    }
    $password = mysqli_real_escape_string($koneksi, password_hash($input['password'], PASSWORD_DEFAULT));

    // MENANGKAP VARIABEL KELAS & ANGKATAN SECARA FLEKSIBEL
    $kelas_diajar   = isset($input['kelas_diajar']) ? mysqli_real_escape_string($koneksi, $input['kelas_diajar']) : 'Belum Diatur';
    
    // Deteksi kiriman tahun angkatan dari Flutter (Konversi string angka menjadi Integer murni)
    $tahun_angkatan_raw = isset($input['tahun_angkatan']) ? trim($input['tahun_angkatan']) : '';
    if ($tahun_angkatan_raw !== '' && $tahun_angkatan_raw !== 'null' && is_numeric($tahun_angkatan_raw)) {
        $tahun_angkatan = (int)$tahun_angkatan_raw;
    } else {
        $tahun_angkatan = "NULL";
    }

    // 2. CEK REDUNDANSI: Pastikan Nomor Induk atau Email belum pernah terdaftar di madrasah
    $cekUser = "SELECT id FROM users WHERE nomor_induk = '$nomor_induk' OR email = '$email'";
    $hasilCek = mysqli_query($koneksi, $cekUser);

    if (mysqli_num_rows($hasilCek) > 0) {
        echo json_encode([
            "status"  => "error",
            "message" => "Gagal! Nomor Induk atau Alamat Email tersebut sudah terdaftar di sistem sekolah."
        ]);
        exit();
    }

    // 3. KUERI EKSEKUSI DEFINITIF (Sudah dibersihkan dari duplikasi & anti-bias NULL)
    $query = "INSERT INTO users (nomor_induk, nama_lengkap, email, password, role, kelas_diajar, tahun_angkatan, is_deleted) 
              VALUES ('$nomor_induk', '$nama_lengkap', '$email', '$password', '$role', '$kelas_diajar', " . ($tahun_angkatan === "NULL" ? "NULL" : "$tahun_angkatan") . ", 0)";

    if (mysqli_query($koneksi, $query)) {
        echo json_encode([
            "status"  => "success",
            "message" => "Akun baru sebagai " . ucfirst($role) . " berhasil didaftarkan secara permanen!"
        ]);
    } else {
        echo json_encode([
            "status"  => "error",
            "message" => "Gagal menyimpan ke database: " . mysqli_error($koneksi)
        ]);
    }
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Parameter input pendaftaran dari aplikasi tidak lengkap."
    ]);
}

mysqli_close($koneksi);
?>
