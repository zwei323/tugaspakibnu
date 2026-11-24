<?php
require 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login admin
if (function_exists('wajib_login')) {
    wajib_login();
} elseif (empty($_SESSION['login_admin'])) {
    header('Location: login.php');
    exit;
}

// Helper escape HTML
if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

// Helper inisial nama
if (!function_exists('inisial')) {
    function inisial($nama) {
        $kata = explode(' ', trim($nama));
        $hasil = '';

        foreach ($kata as $k) {
            if ($k !== '') {
                $hasil .= strtoupper(substr($k, 0, 1));
            }
        }

        return substr($hasil, 0, 2);
    }
}

// Pastikan koneksi PDO tersedia
if (!isset($pdo)) {
    die('Koneksi database PDO tidak ditemukan. Periksa koneksi.php');
}

// CSRF token
if (empty($_SESSION['csrf_index'])) {
    $_SESSION['csrf_index'] = bin2hex(random_bytes(32));
}

$pesan = '';
$error = '';

// =====================================
// PROSES HAPUS DATA SISWA
// =====================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_index'], $token)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $nisHapus = trim($_POST['nis'] ?? '');

        try {
            // Cari foto siswa
            $stmtFoto = $pdo->prepare("SELECT foto FROM siswa WHERE nis = ?");
            $stmtFoto->execute([$nisHapus]);
            $dataFoto = $stmtFoto->fetch(PDO::FETCH_ASSOC);

            if (!$dataFoto) {
                $error = 'Data siswa tidak ditemukan.';
            } else {
                // Hapus data dari database
                $stmtHapus = $pdo->prepare("DELETE FROM siswa WHERE nis = ?");
                $stmtHapus->execute([$nisHapus]);

                // Hapus foto jika tersedia
                if (!empty($dataFoto['foto'])) {
                    $folderFoto = __DIR__ . '/uploads/foto_siswa/';
                    $namaFoto = basename($dataFoto['foto']);
                    $pathFoto = $folderFoto . $namaFoto;

                    if (is_file($pathFoto)) {
                        unlink($pathFoto);
                    }
                }

                $pesan = 'Data siswa berhasil dihapus.';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Data siswa gagal dihapus. Pastikan tidak ada data terkait.';
        }
    }
}

// =====================================
// PENCARIAN
// =====================================
$cari = trim($_GET['cari'] ?? '');
$filterKelas = trim($_GET['kelas'] ?? '');

// =====================================
// PAGINATION
// =====================================
$perHalaman = 10;
$halaman = max(1, (int) ($_GET['halaman'] ?? 1));
$offset = ($halaman - 1) * $perHalaman;

// =====================================
// FILTER DATA
// =====================================
$where = [];
$params = [];

if ($cari !== '') {
    $where[] = "(nama LIKE ? OR nis LIKE ? OR jurusan LIKE ? OR alamat LIKE ?)";
    $keyword = "%{$cari}%";
    array_push($params, $keyword, $keyword, $keyword, $keyword);
}

if ($filterKelas !== '') {
    $where[] = "kelas = ?";
    $params[] = $filterKelas;
}

$sqlWhere = '';

if (!empty($where)) {
    $sqlWhere = 'WHERE ' . implode(' AND ', $where);
}

// =====================================
// AMBIL TOTAL DATA
// =====================================
try {
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM siswa $sqlWhere");
    $stmtTotal->execute($params);
    $totalData = (int) $stmtTotal->fetchColumn();

    $totalHalaman = max(1, (int) ceil($totalData / $perHalaman));

    if ($halaman > $totalHalaman) {
        $halaman = $totalHalaman;
        $offset = ($halaman - 1) * $perHalaman;
    }

    // =====================================
    // AMBIL DATA SISWA
    // =====================================
    $sql = "SELECT nis, nama, foto, kelas, jurusan, alamat
            FROM siswa
            $sqlWhere
            ORDER BY nama ASC
            LIMIT $perHalaman OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // =====================================
    // AMBIL DAFTAR KELAS
    // =====================================
    $stmtKelas = $pdo->query("SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL AND kelas <> '' ORDER BY kelas ASC");
    $daftarKelas = $stmtKelas->fetchAll(PDO::FETCH_COLUMN);

    // =====================================
    // STATISTIK
    // =====================================
    $totalSemua = (int) $pdo->query("SELECT COUNT(*) FROM siswa")->fetchColumn();

    $totalKelas = (int) $pdo->query(
        "SELECT COUNT(DISTINCT kelas) FROM siswa WHERE kelas IS NOT NULL AND kelas <> ''"
    )->fetchColumn();

} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Terjadi kesalahan saat mengambil data siswa. Periksa database.');
}

// URL pagination
function urlHalaman($hal) {
    $query = $_GET;
    $query['halaman'] = $hal;
    return '?' . http_build_query($query);
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Siswa</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Inter, Arial, sans-serif;
    background: #f4f7fb;
    color: #1e293b;
}

.container {
    max-width: 1400px;
    margin: auto;
    padding: 30px;
}

/* HEADER */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 28px;
}

.header h1 {
    font-size: 28px;
    font-weight: 800;
    color: #172554;
}

.header p {
    color: #64748b;
    margin-top: 7px;
    font-size: 14px;
}

.btn {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 7px;
    padding: 11px 16px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    font-size: 13px;
    cursor: pointer;
    transition: .2s;
}

.btn-primary {
    background: #2563eb;
    color: white;
}

.btn-primary:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.btn-edit {
    background: #eff6ff;
    color: #2563eb;
}

.btn-edit:hover {
    background: #dbeafe;
}

.btn-delete {
    background: #fff1f2;
    color: #e11d48;
}

.btn-delete:hover {
    background: #ffe4e6;
}

/* STATISTIK */
.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    padding: 23px;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(15, 23, 42, .04);
    border: 1px solid #edf1f7;
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    width: 52px;
    height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    font-size: 24px;
    background: #eff6ff;
}

.stat-card:nth-child(2) .stat-icon {
    background: #ecfdf5;
}

.stat-card:nth-child(3) .stat-icon {
    background: #fff7ed;
}

.stat-card h3 {
    font-size: 24px;
    color: #172554;
}

.stat-card p {
    color: #64748b;
    font-size: 13px;
    margin-top: 5px;
}

/* NOTIFIKASI */
.alert {
    padding: 14px 17px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
}

/* PANEL */
.panel {
    background: white;
    border-radius: 16px;
    border: 1px solid #edf1f7;
    box-shadow: 0 4px 15px rgba(15, 23, 42, .04);
    overflow: hidden;
}

.panel-header {
    padding: 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    border-bottom: 1px solid #edf1f7;
}

.panel-header h2 {
    font-size: 18px;
    color: #172554;
}

.panel-header p {
    color: #64748b;
    font-size: 13px;
    margin-top: 5px;
}

/* SEARCH */
.filter {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter input,
.filter select {
    height: 42px;
    border: 1px solid #dbe3ef;
    border-radius: 9px;
    padding: 0 12px;
    outline: none;
    background: white;
    font-size: 13px;
    color: #334155;
}

.filter input {
    width: 240px;
}

.filter input:focus,
.filter select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, .1);
}

.btn-search {
    height: 42px;
    padding: 0 15px;
    background: #172554;
    color: white;
}

.btn-reset {
    height: 42px;
    background: #f1f5f9;
    color: #475569;
}

/* TABEL */
.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

thead {
    background: #f8fafc;
}

th {
    padding: 15px 18px;
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: #64748b;
    border-bottom: 1px solid #e9eef5;
}

td {
    padding: 14px 18px;
    border-bottom: 1px solid #f0f3f8;
    font-size: 13px;
    color: #475569;
    vertical-align: middle;
}

tbody tr:hover {
    background: #fafcff;
}

tbody tr:last-child td {
    border-bottom: none;
}

.no {
    color: #94a3b8;
    width: 50px;
}

/* FOTO SISWA */
.orang {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 200px;
}

.foto-siswa {
    width: 46px;
    height: 46px;
    min-width: 46px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e8edf5;
    display: block;
    box-shadow: 0 2px 6px rgba(0, 0, 0, .06);
    transition: .2s ease;
}

.foto-siswa:hover {
    transform: scale(1.08);
}

.avatar {
    width: 46px;
    height: 46px;
    min-width: 46px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 13px;
    font-weight: 800;
}

.nama {
    font-weight: 700;
    color: #1e293b;
}

.nama small {
    display: block;
    font-size: 11px;
    font-weight: 400;
    color: #94a3b8;
    margin-top: 4px;
}

/* BADGE */
.badge {
    display: inline-block;
    padding: 6px 10px;
    background: #eff6ff;
    color: #2563eb;
    border-radius: 7px;
    font-size: 11px;
    font-weight: 700;
}

/* AKSI */
.aksi {
    display: flex;
    gap: 7px;
    align-items: center;
}

.aksi .btn {
    padding: 8px 11px;
    font-size: 12px;
}

.form-hapus {
    display: inline;
}

/* EMPTY */
.empty {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 42px;
    margin-bottom: 12px;
}

.empty h3 {
    color: #334155;
    margin-bottom: 7px;
}

.empty p {
    color: #94a3b8;
    font-size: 13px;
}

/* FOOTER TABEL */
.table-footer {
    padding: 18px 22px;
    border-top: 1px solid #edf1f7;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.info-data {
    font-size: 12px;
    color: #64748b;
}

/* PAGINATION */
.pagination {
    display: flex;
    gap: 5px;
    align-items: center;
}

.pagination a,
.pagination span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 9px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 12px;
    color: #475569;
    background: #f1f5f9;
}

.pagination a:hover {
    background: #dbeafe;
    color: #2563eb;
}

.pagination .aktif {
    background: #2563eb;
    color: white;
}

.pagination .disabled {
    opacity: .4;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .container {
        padding: 18px;
    }

    .header h1 {
        font-size: 23px;
    }

    .stats {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .stat-card {
        padding: 17px;
    }

    .panel-header {
        padding: 18px;
    }

    .filter {
        width: 100%;
    }

    .filter input {
        width: 100%;
    }

    .filter input,
    .filter select,
    .filter button,
    .filter a {
        flex: 1;
    }

    .table-footer {
        padding: 15px;
    }
}
</style>
</head>

<body>

<div class="container">

    <!-- HEADER -->
    <div class="header">
        <div>
            <h1>👨‍🎓 Data Siswa</h1>
            <p>Kelola data siswa dengan mudah dan terorganisir.</p>
        </div>

        <a href="tambah.php" class="btn btn-primary">
            ＋ Tambah Siswa
        </a>
    </div>

    <!-- NOTIFIKASI -->
    <?php if ($pesan !== ''): ?>
        <div class="alert alert-success">
            ✓ <?= e($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error">
            ⚠ <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- STATISTIK -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon">👨‍🎓</div>
            <div>
                <h3><?= number_format($totalSemua, 0, ',', '.') ?></h3>
                <p>Total Siswa</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🏫</div>
            <div>
                <h3><?= number_format($totalKelas, 0, ',', '.') ?></h3>
                <p>Total Kelas</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🔎</div>
            <div>
                <h3><?= number_format($totalData, 0, ',', '.') ?></h3>
                <p>Hasil Pencarian</p>
            </div>
        </div>
    </div>

    <!-- PANEL DATA -->
    <div class="panel">

        <div class="panel-header">
            <div>
                <h2>Daftar Siswa</h2>
                <p>Data siswa yang terdaftar di sistem</p>
            </div>

            <!-- SEARCH DAN FILTER -->
            <form method="GET" class="filter">

                <input
                    type="text"
                    name="cari"
                    placeholder="Cari nama, NIS, jurusan..."
                    value="<?= e($cari) ?>"
                >

                <select name="kelas">
                    <option value="">Semua Kelas</option>

                    <?php foreach ($daftarKelas as $k): ?>
                        <option value="<?= e($k) ?>"
                            <?= $filterKelas === $k ? 'selected' : '' ?>>
                            <?= e($k) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-search">
                    🔍 Cari
                </button>

                <a href="index.php" class="btn btn-reset">
                    Reset
                </a>

            </form>
        </div>

        <!-- TABEL SISWA -->
        <div class="table-wrapper">

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Siswa</th>
                        <th>NIS</th>
                        <th>Kelas</th>
                        <th>Jurusan</th>
                        <th>Alamat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($siswa)): ?>

                    <?php foreach ($siswa as $i => $s): ?>

                        <?php
                        $h = abs(crc32($s['nama'])) % 360;
                        $nomor = $offset + $i + 1;

                        $pathFoto = !empty($s['foto'])
                            ? __DIR__ . '/' . ltrim($s['foto'], '/\\')
                            : '';

                        $fotoValid = $pathFoto !== '' && is_file($pathFoto);
                        ?>

                        <tr>

                            <!-- NOMOR -->
                            <td class="no">
                                <?= $nomor ?>
                            </td>

                            <!-- NAMA + FOTO -->
                            <td>
                                <div class="orang">

                                    <?php if ($fotoValid): ?>

                                        <img
                                            src="<?= e($s['foto']) ?>"
                                            alt="Foto <?= e($s['nama']) ?>"
                                            class="foto-siswa"
                                        >

                                    <?php else: ?>

                                        <span
                                            class="avatar"
                                            style="background:hsl(<?= $h ?> 55% 90%);color:hsl(<?= $h ?> 55% 28%);"
                                        >
                                            <?= e(inisial($s['nama'])) ?>
                                        </span>

                                    <?php endif; ?>

                                    <span class="nama">
                                        <?= e($s['nama']) ?>
                                        <small><?= e($s['jurusan']) ?></small>
                                    </span>

                                </div>
                            </td>

                            <!-- NIS -->
                            <td>
                                <?= e($s['nis']) ?>
                            </td>

                            <!-- KELAS -->
                            <td>
                                <span class="badge">
                                    <?= e($s['kelas']) ?>
                                </span>
                            </td>

                            <!-- JURUSAN -->
                            <td>
                                <?= e($s['jurusan']) ?>
                            </td>

                            <!-- ALAMAT -->
                            <td>
                                <?= e($s['alamat']) ?>
                            </td>

                            <!-- AKSI -->
                            <td>
                                <div class="aksi">

                                    <a
                                        href="edit.php?nis=<?= urlencode($s['nis']) ?>"
                                        class="btn btn-edit"
                                    >
                                        ✏ Edit
                                    </a>

                                    <form
                                        method="POST"
                                        class="form-hapus"
                                        onsubmit="return confirm('Yakin ingin menghapus data siswa <?= e($s['nama']) ?>?')"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION['csrf_index']) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="nis"
                                            value="<?= e($s['nis']) ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="hapus"
                                            value="1"
                                            class="btn btn-delete"
                                        >
                                            🗑 Hapus
                                        </button>

                                    </form>

                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7">
                            <div class="empty">
                                <div class="empty-icon">📭</div>
                                <h3>Data siswa tidak ditemukan</h3>
                                <p>Coba gunakan kata kunci lain atau reset filter pencarian.</p>
                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>

        </div>

        <!-- FOOTER + PAGINATION -->
        <div class="table-footer">

            <div class="info-data">
                Menampilkan
                <b><?= $totalData > 0 ? $offset + 1 : 0 ?></b>
                -
                <b><?= min($offset + $perHalaman, $totalData) ?></b>
                dari
                <b><?= $totalData ?></b> siswa
            </div>

            <?php if ($totalHalaman > 1): ?>

                <div class="pagination">

                    <?php if ($halaman > 1): ?>
                        <a href="<?= e(urlHalaman($halaman - 1)) ?>">‹</a>
                    <?php else: ?>
                        <span class="disabled">‹</span>
                    <?php endif; ?>

                    <?php
                    $awal = max(1, $halaman - 2);
                    $akhir = min($totalHalaman, $halaman + 2);

                    for ($p = $awal; $p <= $akhir; $p++):
                    ?>

                        <a
                            href="<?= e(urlHalaman($p)) ?>"
                            class="<?= $p === $halaman ? 'aktif' : '' ?>"
                        >
                            <?= $p ?>
                        </a>

                    <?php endfor; ?>

                    <?php if ($halaman < $totalHalaman): ?>
                        <a href="<?= e(urlHalaman($halaman + 1)) ?>">›</a>
                    <?php else: ?>
                        <span class="disabled">›</span>
                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>
</html>