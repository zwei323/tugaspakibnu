<?php
// DELETE: menghapus data siswa (hanya lewat POST, dengan WHERE id)
require 'koneksi.php';
wajib_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM siswa WHERE id = :id");
    $stmt->execute([':id' => $id]);
    set_pesan($stmt->rowCount() ? 'Data siswa berhasil dihapus.' : 'Data tidak ditemukan.',
              $stmt->rowCount() ? 'sukses' : 'gagal');
}
header('Location: index.php');
exit;
