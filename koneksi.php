<?php
// Pertemuan 1: Koneksi PHP ke MySQL menggunakan PDO
session_start();

$host = 'localhost';
$db   = 'db_sekolah2';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()));
}

// Helper escape output (mencegah XSS)
function e($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

// Flash message sederhana
function set_pesan($teks, $tipe = 'sukses') {
    $_SESSION['pesan'] = ['teks' => $teks, 'tipe' => $tipe];
}
function ambil_pesan() {
    $p = $_SESSION['pesan'] ?? null;
    unset($_SESSION['pesan']);
    return $p;
}

// Autentikasi sederhana untuk aplikasi
function login_aplikasi($username, $password) {
    $akun = [
        'admin' => '$2y$10$OaE0SoWGdkrShAUljgcuqeQs3WDi1txZGY3O.naFOzUPOXHq0/NEW',
    ];

    if (!isset($akun[$username])) {
        return false;
    }

    return password_verify($password, $akun[$username]);
}

function wajib_login() {
    if (empty($_SESSION['login_admin'])) {
        $_SESSION['redirect_setelah_login'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
        header('Location: login.php');
        exit;
    }
}
