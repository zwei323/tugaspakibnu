# CRUD Data Siswa (PHP Native + MySQL + PDO)

## Cara menjalankan
1. Install XAMPP/Laragon, jalankan Apache dan MySQL.
2. Salin folder `crud_siswa` ke `htdocs` (XAMPP) atau `www` (Laragon).
3. Buka phpMyAdmin, import `database.sql`.
4. Sesuaikan user/password di `koneksi.php` bila perlu.
5. Buka http://localhost/crud_siswa/

## Struktur file & pertemuan
| File | Fungsi | Pertemuan |
|---|---|---|
| database.sql | Membuat database & tabel | 1 |
| koneksi.php | Koneksi PDO + helper | 1 |
| tambah.php | Create | 2 |
| index.php | Read (+ pencarian) | 2 |
| edit.php | Update | 3 |
| hapus.php | Delete (POST, pakai WHERE) | 3 |
| form.php, header.php, footer.php, style.css | Tampilan | 4 |
