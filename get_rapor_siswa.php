<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);
include "koneksi.php";

// Menangkap parameter ID siswa yang dikirim dari Flutter Orang Tua
$siswa_id = isset($_GET['siswa_id']) ? mysqli_real_escape_string($koneksi, $_GET['siswa_id']) : '';

if (!empty($siswa_id)) {
    // Mengambil riwayat nilai raport siswa dan menggabungkannya dengan nama guru penilai
    $query = "SELECT base.siswa_id, base.semester, base.kelas, base.tahun_angkatan,
                     base.mata_pelajaran,
                     (SELECT n2.nilai_tugas FROM nilai_raport n2
                      WHERE n2.siswa_id = base.siswa_id AND n2.semester = base.semester
                        AND n2.kelas = base.kelas AND n2.tahun_angkatan = base.tahun_angkatan
                        AND n2.mata_pelajaran = base.mata_pelajaran AND n2.nilai_tugas IS NOT NULL
                      ORDER BY n2.id DESC LIMIT 1) AS nilai_tugas,
                     (SELECT n2.nilai_uts FROM nilai_raport n2
                      WHERE n2.siswa_id = base.siswa_id AND n2.semester = base.semester
                        AND n2.kelas = base.kelas AND n2.tahun_angkatan = base.tahun_angkatan
                        AND n2.mata_pelajaran = base.mata_pelajaran AND n2.nilai_uts IS NOT NULL
                      ORDER BY n2.id DESC LIMIT 1) AS nilai_uts,
                     (SELECT n2.nilai_uas FROM nilai_raport n2
                      WHERE n2.siswa_id = base.siswa_id AND n2.semester = base.semester
                        AND n2.kelas = base.kelas AND n2.tahun_angkatan = base.tahun_angkatan
                        AND n2.mata_pelajaran = base.mata_pelajaran AND n2.nilai_uas IS NOT NULL
                      ORDER BY n2.id DESC LIMIT 1) AS nilai_uas,
                     (SELECT n2.keterangan FROM nilai_raport n2
                      WHERE n2.siswa_id = base.siswa_id AND n2.semester = base.semester
                        AND n2.kelas = base.kelas AND n2.tahun_angkatan = base.tahun_angkatan
                        AND n2.mata_pelajaran = base.mata_pelajaran
                      ORDER BY n2.id DESC LIMIT 1) AS keterangan,
                     (SELECT u.nama_lengkap FROM nilai_raport n2
                      JOIN users u ON n2.guru_id = u.id
                      WHERE n2.siswa_id = base.siswa_id AND n2.semester = base.semester
                        AND n2.kelas = base.kelas AND n2.tahun_angkatan = base.tahun_angkatan
                        AND n2.mata_pelajaran = base.mata_pelajaran
                      ORDER BY n2.id DESC LIMIT 1) AS nama_guru
              FROM (
                  SELECT DISTINCT siswa_id, semester, kelas, tahun_angkatan, mata_pelajaran
                  FROM nilai_raport
                  WHERE siswa_id = '$siswa_id'
              ) base
              ORDER BY base.tahun_angkatan ASC, base.kelas ASC,
                       base.semester ASC, base.mata_pelajaran ASC";
              
    $result = mysqli_query($koneksi, $query);
    $rapor = [];

    if ($result === false) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Gagal mengambil data rapor."]);
        exit();
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $rapor[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $rapor]);
} else {
    echo json_encode(["status" => "error", "message" => "Parameter ID Siswa kosong."]);
}
?>
