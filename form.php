<?php
// Partial form yang dipakai tambah.php dan edit.php
// Variabel: $judul, $siswa (array), $error (array)
?>
<a class="kembali" href="index.php">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    Kembali ke daftar
</a>

<section class="panel form-panel">
    <h2><?= e($judul) ?></h2>

    <?php if (!empty($error)): ?>
        <div class="alert gagal" role="alert">
            <ul><?php foreach ($error as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" class="form">
        <label class="f-nis">NIS
            <input type="text" name="nis" maxlength="20" value="<?= e($siswa['nis']) ?>" required>
        </label>
        <label class="f-nama">Nama lengkap
            <input type="text" name="nama" maxlength="100" value="<?= e($siswa['nama']) ?>" required>
        </label>
        <label>Kelas
            <input type="text" name="kelas" maxlength="20" value="<?= e($siswa['kelas']) ?>" placeholder="Contoh: X RPL 1" required>
        </label>
        <label>Jurusan
            <input type="text" name="jurusan" maxlength="50" value="<?= e($siswa['jurusan']) ?>" placeholder="Contoh: RPL" required>
        </label>
        <label class="full">Alamat
            <textarea name="alamat" rows="3"><?= e($siswa['alamat']) ?></textarea>
        </label>
        <div class="full tombol">
            <button class="btn" type="submit">Simpan</button>
            <a class="btn ghost" href="index.php">Batal</a>
        </div>
    </form>
</section>
