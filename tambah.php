
<?php
require 'koneksi.php';
wajib_login();

$siswa = [
    'nis' => '',
    'nama' => '',
    'kelas' => '',
    'jurusan' => '',
    'alamat' => ''
];

$error = [];
$fotoPath = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($siswa as $k => $_) {
        $siswa[$k] = trim($_POST[$k] ?? '');
    }

    foreach (['nis', 'nama', 'kelas', 'jurusan'] as $wajib) {
        if ($siswa[$wajib] === '') {
            $error[] = ucfirst($wajib) . ' wajib diisi.';
        }
    }

    // =============================
    // VALIDASI FOTO
    // =============================
    $foto = $_FILES['foto'] ?? null;

    if ($foto && $foto['error'] !== UPLOAD_ERR_NO_FILE) {

        if ($foto['error'] !== UPLOAD_ERR_OK) {
            $error[] = 'Upload foto gagal. Silakan coba lagi.';
        } elseif ($foto['size'] > 3 * 1024 * 1024) {
            $error[] = 'Ukuran foto maksimal 3 MB.';
        } else {

          $finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($foto['tmp_name']);

            $formatDiizinkan = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($formatDiizinkan[$mime])) {
                $error[] = 'Foto harus berformat JPG, PNG, atau WEBP.';
            } else {
                $ekstensi = $formatDiizinkan[$mime];
                $namaFile = bin2hex(random_bytes(16)) . '.' . $ekstensi;
                $folderFoto = __DIR__ . '/uploads/foto_siswa/';

                if (!is_dir($folderFoto)) {
                    mkdir($folderFoto, 0755, true);
                }

                $fotoPath = 'uploads/foto_siswa/' . $namaFile;
                $lokasiTujuan = $folderFoto . $namaFile;
            }
        }
    }

    // =============================
    // SIMPAN DATA
    // =============================
    if (!$error) {

        try {

            // Pindahkan foto jika ada
            if (!empty($fotoPath)) {
                if (!move_uploaded_file($foto['tmp_name'], $lokasiTujuan)) {
                    throw new RuntimeException('Foto gagal disimpan ke server.');
                }
            }

            $stmt = $pdo->prepare(
                "INSERT INTO siswa (nis, nama, foto, kelas, jurusan, alamat)
                 VALUES (:nis, :nama, :foto, :kelas, :jurusan, :alamat)"
            );

            $stmt->execute([
                'nis' => $siswa['nis'],
                'nama' => $siswa['nama'],
                'foto' => $fotoPath ?: null,
                'kelas' => $siswa['kelas'],
                'jurusan' => $siswa['jurusan'],
                'alamat' => $siswa['alamat']
            ]);

            set_pesan('Data siswa berhasil ditambahkan.');
            header('Location: index.php');
            exit;

        } catch (Throwable $e) {

            // Hapus foto jika database gagal menyimpan
            if (!empty($fotoPath) && file_exists($lokasiTujuan)) {
                unlink($lokasiTujuan);
            }

            $error[] = 'Data gagal disimpan. ' . $e->getMessage();
        }
    }
}

$judul = 'Tambah Siswa';
require 'header.php';
?>

<style>
.form-foto-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 13px;
    padding: 25px;
    background: #f7f9fc;
    border: 1px dashed #ccd6e5;
    border-radius: 16px;
    margin-bottom: 24px;
}

.preview-foto {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    overflow: hidden;
    background: #e8efff;
    border: 4px solid #fff;
    box-shadow: 0 4px 18px rgba(30,60,100,.12);
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview-foto img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: none;
}

.preview-foto .placeholder {
    font-size: 50px;
    color: #3b73d9;
}

.upload-foto-btn {
    display: inline-block;
    padding: 10px 18px;
    border-radius: 10px;
    background: #2563eb;
    color: white;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.upload-foto-btn:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.upload-info {
    font-size: 12px;
    color: #8491a7;
    text-align: center;
}

.input-foto-hidden {
    display: none !important;
}

.form-error-foto {
    background: #fff1f2;
    border: 1px solid #fecaca;
    color: #b42338;
    border-radius: 10px;
    padding: 12px 15px;
    margin-bottom: 15px;
    font-size: 13px;
}

.form-error-foto ul {
    margin: 5px 0 0 20px;
}

.form-siswa-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.form-siswa-grid .full {
    grid-column: 1 / -1;
}

@media(max-width:600px) {
    .form-siswa-grid {
        grid-template-columns: 1fr;
    }

    .form-siswa-grid .full {
        grid-column: auto;
    }
}
</style>

<section class="panel">

    <div class="toolbar" style="margin-bottom:20px">
        <div>
            <h2 style="margin:0">Tambah Data Siswa</h2>
            <p style="color:#8793a8;margin-top:5px;font-size:13px">
                Lengkapi identitas siswa dan tambahkan foto profil.
            </p>
        </div>
        <a class="btn ghost" href="index.php">← Kembali</a>
    </div>

    <?php if ($error): ?>
        <div class="form-error-foto">
            <strong>Periksa kembali data berikut:</strong>
            <ul>
                <?php foreach ($error as $pesanError): ?>
                    <li><?= e($pesanError) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">

        <!-- UPLOAD FOTO -->
        <div class="form-foto-wrapper">

            <div class="preview-foto">
                <span class="placeholder" id="placeholderFoto">👤</span>
                <img id="previewFoto" src="" alt="Preview foto siswa">
            </div>

            <label for="foto" class="upload-foto-btn">
                📷 Pilih Foto Siswa
            </label>

            <input
                type="file"
                name="foto"
                id="foto"
                class="input-foto-hidden"
                accept="image/jpeg,image/png,image/webp"
            >

            <div class="upload-info">
                JPG, PNG, WEBP • Maksimal 3 MB
            </div>
        </div>

        <!-- DATA SISWA -->
        <div class="form-siswa-grid">

            <div>
                <label for="nis">NIS *</label>
                <input
                    type="text"
                    name="nis"
                    id="nis"
                    value="<?= e($siswa['nis']) ?>"
                    placeholder="Masukkan NIS"
                    required
                >
            </div>

            <div>
                <label for="nama">Nama Lengkap *</label>
                <input
                    type="text"
                    name="nama"
                id="nama"
                    value="<?= e($siswa['nama']) ?>"
                    placeholder="Masukkan nama siswa"
                    required
                >
            </div>

            <div>
                <label for="kelas">Kelas *</label>
                <input
                    type="text"
                name="kelas"
                id="kelas"
                value="<?= e($siswa['kelas']) ?>"
                placeholder="Contoh: XII RPL 2"
                required
            >
            </div>

            <div>
                <label for="jurusan">Jurusan *</label>
                <input
                    type="text"
                    name="jurusan"
                id="jurusan"
                value="<?= e($siswa['jurusan']) ?>"
                placeholder="Contoh: Rekayasa Perangkat Lunak"
                required
            >
            </div>

            <div class="full">
                <label for="alamat">Alamat</label>
                <textarea
                    name="alamat"
                id="alamat"
                rows="3"
                placeholder="Masukkan alamat siswa"
                ><?= e($siswa['alamat']) ?></textarea>
            </div>

        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:25px;flex-wrap:wrap">
            <a href="index.php" class="btn ghost">Batal</a>
            <button type="submit" class="btn">💾 Simpan Data Siswa</button>
        </div>

    </form>
</section>

<script>
const inputFoto = document.getElementById('foto');
const previewFoto = document.getElementById('previewFoto');
const placeholderFoto = document.getElementById('placeholderFoto');

inputFoto.addEventListener('change', function () {
    const file = this.files[0];

    if (!file) {
        previewFoto.style.display = 'none';
        placeholderFoto.style.display = 'block';
        previewFoto.src = '';
        return;
    }

    if (file.size > 3 * 1024 * 1024) {
        alert('Ukuran foto maksimal 3 MB.');
        this.value = '';
        previewFoto.style.display = 'none';
        placeholderFoto.style.display = 'block';
        previewFoto.src = '';
        return;
    }

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        alert('Pilih foto JPG, PNG, atau WEBP.');
        this.value = '';
        previewFoto.style.display = 'none';
        placeholderFoto.style.display = 'block';
        previewFoto.src = '';
        return;
    }

    const reader = new FileReader();

    reader.onload = function (event) {
        previewFoto.src = event.target.result;
        previewFoto.style.display = 'block';
        placeholderFoto.style.display = 'none';
    };

    reader.readAsDataURL(file);
});
</script>

<?php require 'footer.php'; ?>
```