<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'koneksi.php';

$input = json_decode(file_get_contents('php://input'), true);

if (
    isset($input['id_user']) && 
    isset($input['nama_lengkap']) && 
    isset($input['email']) && 
    isset($input['kelas_diajar'])
) {
    $id_user      = mysqli_real_escape_string($koneksi, $input['id_user']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $input['nama_lengkap']);
    $email        = mysqli_real_escape_string($koneksi, $input['email']);
    $kelas_diajar = mysqli_real_escape_string($koneksi, $input['kelas_diajar']);
    $password_baru = isset($input['password_baru']) ? trim($input['password_baru']) : '';
    $cekEmail = "SELECT id FROM users WHERE email = '$email' AND id != '$id_user' AND is_deleted = 0";
    $hasilCek = mysqli_query($koneksi, $cekEmail);

    if ($hasilCek && mysqli_num_rows($hasilCek) > 0) {
        echo json_encode([
            "status"  => "error",
            "message" => "Gagal! Alamat email tersebut sudah terpakai oleh akun pengguna lain."
        ]);
        exit();
    }

    if ($password_baru !== '') {
        if (strlen($password_baru) < 6) {
            echo json_encode(["status" => "error", "message" => "Password baru minimal 6 karakter."]);
            exit();
        }
        $password_safe = mysqli_real_escape_string($koneksi, password_hash($password_baru, PASSWORD_DEFAULT));
        $query = "UPDATE users 
                  SET nama_lengkap = '$nama_lengkap', email = '$email', kelas_diajar = '$kelas_diajar', password = '$password_safe' 
                  WHERE id = '$id_user'";
    } else {
        $query = "UPDATE users 
                  SET nama_lengkap = '$nama_lengkap', email = '$email', kelas_diajar = '$kelas_diajar' 
                  WHERE id = '$id_user'";
    }

    if (mysqli_query($koneksi, $query)) {
        echo json_encode([
            "status"  => "success",
            "message" => "Selamat! Informasi data akun pengguna berhasil diperbarui secara permanen."
        ]);
    } else {
        echo json_encode([
            "status"  => "error",
            "message" => "Gagal menyimpan ke database MySQL: " . mysqli_error($koneksi)
        ]);
    }
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Parameter input pembaruan data tidak lengkap."
    ]);
}

mysqli_close($koneksi);
?>