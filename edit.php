<?php
require 'koneksi.php';
wajib_login();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Escape HTML
if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

// CSRF Token
if (empty($_SESSION['csrf_edit'])) {
    $_SESSION['csrf_edit'] = bin2hex(random_bytes(32));
}

// Ambil ID siswa
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM siswa WHERE id = ?");
$stmt->execute([$id]);
$siswa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$siswa) {
    set_pesan('Data siswa tidak ditemukan.', 'gagal');
    header('Location: index.php');
    exit;
}

$error = [];

// Folder upload
$folderFoto = __DIR__ . '/uploads/foto_siswa/';
$folderRelatif = 'uploads/foto_siswa/';

// =========================================
// PROSES UPDATE
// =========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validasi CSRF
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_edit'], $token)) {
        $error[] = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    }

    // Ambil input
    foreach (['nis', 'nama', 'kelas', 'jurusan', 'alamat'] as $k) {
        $siswa[$k] = trim($_POST[$k] ?? '');
    }

    // Validasi field wajib
    foreach (['nis', 'nama', 'kelas', 'jurusan'] as $wajib) {
        if ($siswa[$wajib] === '') {
            $error[] = ucfirst($wajib) . ' wajib diisi.';
        }
    }

    // =========================================
    // VALIDASI FOTO
    // =========================================
    $fotoBaru = null;

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {

        $foto = $_FILES['foto'];

        if ($foto['error'] !== UPLOAD_ERR_OK) {
            $error[] = 'Upload foto gagal.';
        } elseif ($foto['size'] > 3 * 1024 * 1024) {
            $error[] = 'Ukuran foto maksimal 3 MB.';
        } else {

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($foto['tmp_name']);

            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($allowed[$mime])) {
                $error[] = 'Format foto harus JPG, PNG, atau WEBP.';
            } else {

                // Pastikan folder tersedia
                if (!is_dir($folderFoto)) {
                    if (!mkdir($folderFoto, 0755, true)) {
                        $error[] = 'Folder upload foto gagal dibuat.';
                    }
                }

                if (!$error) {
                    $namaFile = 'siswa_' . $id . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
                    $tujuan = $folderFoto . $namaFile;

                    if (move_uploaded_file($foto['tmp_name'], $tujuan)) {
                        $fotoBaru = $folderRelatif . $namaFile;
                    } else {
                        $error[] = 'Foto gagal disimpan ke folder.';
                    }
                }
            }
        }
    }

    // =========================================
    // SIMPAN DATABASE
    // =========================================
    if (!$error) {
        try {

            if ($fotoBaru !== null) {
                // Update data termasuk foto
                $stmt = $pdo->prepare("
                    UPDATE siswa
                    SET nis = :nis,
                        nama = :nama,
                        kelas = :kelas,
                        jurusan = :jurusan,
                        alamat = :alamat,
                        foto = :foto
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':nis' => $siswa['nis'],
                    ':nama' => $siswa['nama'],
                    ':kelas' => $siswa['kelas'],
                    ':jurusan' => $siswa['jurusan'],
                    ':alamat' => $siswa['alamat'],
                    ':foto' => $fotoBaru,
                    ':id' => $id
                ]);

            } else {
                // Update data tanpa mengganti foto
                $stmt = $pdo->prepare("
                    UPDATE siswa
                    SET nis = :nis,
                        nama = :nama,
                        kelas = :kelas,
                        jurusan = :jurusan,
                        alamat = :alamat
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':nis' => $siswa['nis'],
                    ':nama' => $siswa['nama'],
                    ':kelas' => $siswa['kelas'],
                    ':jurusan' => $siswa['jurusan'],
                    ':alamat' => $siswa['alamat'],
                    ':id' => $id
                ]);
            }

            // Hapus foto lama jika berhasil mengganti foto
            if ($fotoBaru !== null && !empty($siswa['foto'])) {
                $fotoLama = __DIR__ . '/' . ltrim($siswa['foto'], '/\\');

                if (is_file($fotoLama)) {
                    @unlink($fotoLama);
                }
            }

            set_pesan('Data siswa berhasil diperbarui.');
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            error_log($e->getMessage());

            // Jika database gagal, hapus foto baru agar tidak menjadi file sampah
            if ($fotoBaru !== null) {
                $pathFotoBaru = __DIR__ . '/' . $fotoBaru;

                if (is_file($pathFotoBaru)) {
                    @unlink($pathFotoBaru);
                }
            }

            $error[] = 'Data gagal diperbarui. Silakan coba kembali.';
        }
    }
}

// Foto lama untuk preview
$fotoLama = $siswa['foto'] ?? '';
$fotoLamaPath = $fotoLama !== ''
    ? __DIR__ . '/' . ltrim($fotoLama, '/\\')
    : '';

$fotoTersedia = $fotoLama !== '' && is_file($fotoLamaPath);

$inisial = '';
$kata = explode(' ', trim($siswa['nama']));

foreach ($kata as $k) {
    if ($k !== '') {
        $inisial .= strtoupper(substr($k, 0, 1));
    }
}
$inisial = substr($inisial, 0, 2);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Data Siswa</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 30px 15px;
    background: #f4f7fb;
    font-family: Arial, Helvetica, sans-serif;
    color: #1e293b;
}

.container {
    max-width: 750px;
    margin: auto;
}

.card {
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(15, 23, 42, .06);
}

.card-header {
    background: #172554;
    color: white;
    padding: 25px;
}

.card-header h1 {
    font-size: 24px;
    margin: 0 0 8px;
}

.card-header p {
    margin: 0;
    color: #cbd5e1;
    font-size: 14px;
}

.card-body {
    padding: 25px;
}

.alert {
    background: #fee2e2;
    color: #991b1b;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
}

.alert ul {
    margin: 0;
    padding-left: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 8px;
    color: #334155;
}

.form-group input,
.form-group textarea {
    width: 100%;
    border: 1px solid #dbe3ef;
    border-radius: 10px;
    padding: 12px;
    outline: none;
    font-size: 14px;
    background: #fff;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, .1);
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.required {
    color: #ef4444;
}

.hint {
    color: #94a3b8;
    font-size: 12px;
    margin-top: 6px;
}

.foto-area {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
}

.foto-preview {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid white;
    box-shadow: 0 3px 12px rgba(0,0,0,.1);
    margin-bottom: 15px;
}

.avatar {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    font-size: 36px;
    font-weight: bold;
    color: #2563eb;
    background: #dbeafe;
    margin-bottom: 15px;
}

#previewFoto {
    display: none;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 18px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
    font-weight: bold;
}

.btn-simpan {
    background: #2563eb;
    color: white;
}

.btn-simpan:hover {
    background: #1d4ed8;
}

.btn-kembali {
    background: #f1f5f9;
    color: #475569;
}

.btn-kembali:hover {
    background: #e2e8f0;
}

.actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 25px;
}

@media(max-width: 500px) {
    .card-body {
        padding: 18px;
    }

    .card-header {
        padding: 20px;
    }
}
</style>
</head>

<body>

<div class="container">

    <div class="card">

        <div class="card-header">
            <h1>✏️ Edit Data Siswa</h1>
            <p>Perbarui informasi siswa dan foto profil.</p>
        </div>

        <div class="card-body">

            <?php if (!empty($error)): ?>
                <div class="alert">
                    <strong>Data belum berhasil disimpan:</strong>
                    <ul>
                        <?php foreach ($error as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <input type="hidden" name="csrf_token"
                       value="<?= e($_SESSION['csrf_edit']) ?>">

                <!-- FOTO -->
                <div class="form-group">
                    <label>Foto Siswa</label>

                    <div class="foto-area">

                        <?php if ($fotoTersedia): ?>
                            <img
                                id="fotoTampil"
                                src="<?= e($fotoLama) ?>"
                                class="foto-preview"
                                alt="Foto siswa"
                            >
                        <?php else: ?>
                            <div id="avatarTampil" class="avatar">
                                <?= e($inisial ?: 'S') ?>
                            </div>

                            <img
                                id="fotoTampil"
                                src=""
                                class="foto-preview"
                                alt="Preview foto"
                                style="display:none"
                            >
                        <?php endif; ?>

                        <img
                            id="previewFoto"
                            class="foto-preview"
                            src=""
                            alt="Preview foto baru"
                        >

                        <input
                            type="file"
                            name="foto"
                            id="inputFoto"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <p class="hint">
                            Kosongkan jika tidak ingin mengganti foto.
                            Format JPG, PNG, WEBP. Maksimal 3 MB.
                        </p>
                    </div>
                </div>

                <!-- NIS -->
                <div class="form-group">
                    <label>
                        NIS <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        name="nis"
                        value="<?= e($siswa['nis']) ?>"
                        required
                    >
                </div>

                <!-- NAMA -->
                <div class="form-group">
                    <label>
                        Nama Lengkap <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        name="nama"
                        value="<?= e($siswa['nama']) ?>"
                        required
                    >
                </div>

                <!-- KELAS -->
                <div class="form-group">
                    <label>
                        Kelas <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        name="kelas"
                        value="<?= e($siswa['kelas']) ?>"
                        required
                    >
                </div>

                <!-- JURUSAN -->
                <div class="form-group">
                    <label>
                        Jurusan <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        name="jurusan"
                        value="<?= e($siswa['jurusan']) ?>"
                        required
                    >
                </div>

                <!-- ALAMAT -->
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea
                        name="alamat"
                        placeholder="Masukkan alamat siswa..."
                    ><?= e($siswa['alamat'] ?? '') ?></textarea>
                </div>

                <!-- BUTTON -->
                <div class="actions">
                    <button type="submit" class="btn btn-simpan">
                        💾 Simpan Perubahan
                    </button>

                    <a href="index.php" class="btn btn-kembali">
                        ← Kembali
                    </a>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
// Preview foto baru
const inputFoto = document.getElementById('inputFoto');
const previewFoto = document.getElementById('previewFoto');
const fotoTampil = document.getElementById('fotoTampil');
const avatarTampil = document.getElementById('avatarTampil');

inputFoto.addEventListener('change', function () {

    const file = this.files[0];

    if (!file) {
        previewFoto.style.display = 'none';
        if (fotoTampil) fotoTampil.style.display = '';
        if (avatarTampil) avatarTampil.style.display = '';
        return;
    }

    const allowed = ['image/jpeg', 'image/png', 'image/webp'];

    if (!allowed.includes(file.type)) {
        alert('Format foto harus JPG, PNG, atau WEBP.');
        this.value = '';
        return;
    }

    if (file.size > 3 * 1024 * 1024) {
        alert('Ukuran foto maksimal 3 MB.');
        this.value = '';
        return;
    }

    const reader = new FileReader();

    reader.onload = function (e) {
        previewFoto.src = e.target.result;
        previewFoto.style.display = 'inline-block';

        if (fotoTampil) fotoTampil.style.display = 'none';
        if (avatarTampil) avatarTampil.style.display = 'none';
    };

    reader.readAsDataURL(file);
});
</script>

</body>
</html>