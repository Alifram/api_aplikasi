<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// 1. MENYERTAKAN KONEKSI DATABASE BAWAAN ANDA
include 'koneksi.php';

// Menangkap data JSON input dari Admin (misal dikirim id_admin penanggung jawab)
$input = json_decode(file_get_contents('php://input'), true);

// Matikan fungsi autocommit MySQL untuk memulai sistem Transaksi Aman (Safety Transaction)
mysqli_begin_transaction($koneksi);

try {
    // A. Mutasi Tahap 1: Mengubah siswa Kelas 6 menjadi Alumni
    $queryKelas6 = "UPDATE users SET kelas_diajar = 'Alumni' WHERE role = 'siswa' AND kelas_diajar = 'Kelas 6'";
    if (!mysqli_query($koneksi, $queryKelas6)) {
        throw new Exception("Gagal memutasi lulusan Kelas 6: " . mysqli_error($koneksi));
    }

    // B. Mutasi Tahap 2: Menaikkan Kelas 5 ke Kelas 6
    $queryKelas5 = "UPDATE users SET kelas_diajar = 'Kelas 6' WHERE role = 'siswa' AND kelas_diajar = 'Kelas 5'";
    if (!mysqli_query($koneksi, $queryKelas5)) {
        throw new Exception("Gagal menaikkan Kelas 5: " . mysqli_error($koneksi));
    }

    // C. Mutasi Tahap 3: Menaikkan Kelas 4 ke Kelas 5
    $queryKelas4 = "UPDATE users SET kelas_diajar = 'Kelas 5' WHERE role = 'siswa' AND kelas_diajar = 'Kelas 4'";
    if (!mysqli_query($koneksi, $queryKelas4)) {
        throw new Exception("Gagal menaikkan Kelas 4: " . mysqli_error($koneksi));
    }

    // D. Mutasi Tahap 4: Menaikkan Kelas 3 ke Kelas 4
    $queryKelas3 = "UPDATE users SET kelas_diajar = 'Kelas 4' WHERE role = 'siswa' AND kelas_diajar = 'Kelas 3'";
    if (!mysqli_query($koneksi, $queryKelas3)) {
        throw new Exception("Gagal menaikkan Kelas 3: " . mysqli_error($koneksi));
    }

    // E. Mutasi Tahap 5: Menaikkan Kelas 2 ke Kelas 3
    $queryKelas2 = "UPDATE users SET kelas_diajar = 'Kelas 3' WHERE role = 'siswa' AND kelas_diajar = 'Kelas 2'";
    if (!mysqli_query($koneksi, $queryKelas2)) {
        throw new Exception("Gagal menaikkan Kelas 2: " . mysqli_error($koneksi));
    }

    // F. Mutasi Tahap 6: Menaikkan Kelas 1 ke Kelas 2
    $queryKelas1 = "UPDATE users SET kelas_diajar = 'Kelas 2' WHERE role = 'siswa' AND kelas_diajar = 'Kelas 1'";
    if (!mysqli_query($koneksi, $queryKelas1)) {
        throw new Exception("Gagal menaikkan Kelas 1: " . mysqli_error($koneksi));
    }

    // JIKA SEMUA TAHAPAN UPDATE DI ATAS BERHASIL TANPA EROR, SIMPAN PERUBAHAN PERMANEN KEDATABASE
    mysqli_commit($koneksi);

    echo json_encode([
        "status" => "success",
        "message" => "Proses otomatisasi kenaikan kelas massal dan kelulusan alumni sukses diterapkan!"
    ]);

} catch (Exception $e) {
    // JIKA ADA SATU SAJA PROSES YANG EROR, BATALKAN SEMUA PERUBAHAN SECARA TOTAL (Database Rollback)
    mysqli_rollback($koneksi);

    echo json_encode([
        "status" => "error",
        "message" => "Proses gagal diterapkan demi keamanan data. Alasan: " . $e->getMessage()
    ]);
}

mysqli_close($koneksi);
?>