
<?php
require 'koneksi.php';

if (!empty($_SESSION['login_admin'])) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_login'])) {
    $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token    = $_POST['csrf'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!hash_equals($_SESSION['csrf_login'], $token)) {
        $error = 'Sesi login tidak valid. Silakan coba lagi.';
    } elseif (login_aplikasi($username, $password)) {
        session_regenerate_id(true);

        $_SESSION['login_admin'] = true;
        $_SESSION['nama_admin'] = 'Administrator';
        unset($_SESSION['csrf_login']);

        $tujuan = $_SESSION['redirect_setelah_login'] ?? 'index.php';
        unset($_SESSION['redirect_setelah_login']);

        if (
            !str_starts_with($tujuan, '/') &&
            !preg_match('/^https?:/i', $tujuan)
        ) {
            header('Location: ' . $tujuan);
        } else {
            header('Location: index.php');
        }

        exit;
    } else {
        $error = 'Username atau kata sandi salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login Administrator | Data Siswa</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* =========================================
   LOGIN DATA SISWA - PREMIUM UI
========================================= */

:root {
    --primary: #3478f6;
    --primary-dark: #2159d8;
    --navy: #102044;
    --text: #182541;
    --muted: #8995aa;
    --border: #e3e8f1;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    min-height: 100vh;
    background: #f4f7fc;
    color: var(--text);
    overflow-x: hidden;
}

button, input {
    font-family: inherit;
}

/* BACKGROUND */

.background {
    position: fixed;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
    z-index: 0;
}

.circle {
    position: absolute;
    border-radius: 50%;
}

.circle-1 {
    width: 500px;
    height: 500px;
    background: #dce9ff;
    top: -200px;
    right: -150px;
}

.circle-2 {
    width: 400px;
    height: 400px;
    background: #e6e9ff;
    bottom: -200px;
    left: -150px;
}

.circle-3 {
    width: 200px;
    height: 200px;
    background: #e1f3ff;
    top: 40%;
    left: 50%;
}

/* LAYOUT */

.login-shell {
    min-height: 100vh;
    width: 100%;
    display: grid;
    grid-template-columns: 1.05fr .95fr;
    position: relative;
    z-index: 1;
}

/* LEFT SIDE */

.login-art {
    min-height: 100vh;
    padding: 42px 7% 28px;
    background: linear-gradient(145deg, #102044, #172c57 55%, #1c3971);
    color: white;
    position: relative;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.login-art::before {
    content: "";
    position: absolute;
    width: 500px;
    height: 500px;
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 50%;
    top: 15%;
    right: -250px;
}

.login-art::after {
    content: "";
    position: absolute;
    width: 350px;
    height: 350px;
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 50%;
    bottom: 5%;
    left: -200px;
}

/* BRAND */

.brand {
    display: flex;
    align-items: center;
    gap: 13px;
    text-decoration: none;
    color: white;
    position: relative;
    z-index: 2;
}

.brand-mark {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: linear-gradient(135deg, #60a5fa, #2563eb);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(37,99,235,.3);
}

.brand-mark svg {
    width: 24px;
    height: 24px;
}

.brand-text {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.brand-text strong {
    font-size: 16px;
    font-weight: 800;
}

.brand-text span {
    font-size: 10px;
    color: #a8b8d7;
    letter-spacing: .5px;
}

/* LEFT CONTENT */

.art-copy {
    margin-top: 65px;
    position: relative;
    z-index: 2;
    max-width: 520px;
}

.eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    padding: 9px 13px;
    border: 1px solid rgba(255,255,255,.13);
    border-radius: 50px;
    background: rgba(255,255,255,.05);
    color: #b8c9e8;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.2px;
}

.eyebrow-dot {
    width: 7px;
    height: 7px;
    background: #60a5fa;
    border-radius: 50%;
    box-shadow: 0 0 12px #60a5fa;
}

.art-copy h1 {
    margin-top: 25px;
    font-size: clamp(36px, 4vw, 55px);
    line-height: 1.18;
    letter-spacing: -2.5px;
    font-weight: 800;
}

.art-copy h1 span {
    color: #73a9ff;
}

.art-copy p {
    color: #aebedb;
    margin-top: 20px;
    line-height: 1.9;
    font-size: 14px;
    max-width: 400px;
}

/* FEATURES */

.features {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-top: 28px;
}

.feature {
    display: flex;
    align-items: center;
    gap: 14px;
}

.feature-icon {
    width: 42px;
    height: 42px;
    border-radius: 13px;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.08);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #83b5ff;
}

.feature-icon svg {
    width: 20px;
    height: 20px;
}

.feature-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.feature-info strong {
    font-size: 12px;
}

.feature-info small {
    color: #a2b3d2;
    font-size: 11px;
}

/* DASHBOARD PREVIEW */

.art-illustration {
    margin-top: 38px;
    position: relative;
    z-index: 2;
    max-width: 500px;
    width: 100%;
    margin-left: auto;
    margin-right: auto;
}

.glow {
    position: absolute;
    inset: 15%;
    background: #4b83f4;
    opacity: .16;
    filter: blur(70px);
    border-radius: 50%;
}

.dashboard-preview {
    position: relative;
    padding: 20px;
    border-radius: 20px;
    background: rgba(255,255,255,.97);
    box-shadow: 0 30px 70px rgba(4,14,37,.3);
    transform: rotate(-1deg);
    color: #1b2946;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.preview-logo {
    display: flex;
    align-items: center;
    gap: 9px;
}

.preview-logo-icon {
    width: 31px;
    height: 31px;
    border-radius: 9px;
    background: #2563eb;
    color: white;
    display: grid;
    place-items: center;
    font-size: 14px;
    font-weight: 800;
}

.preview-logo-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.preview-logo-text strong {
    font-size: 11px;
}

.preview-logo-text span {
    font-size: 9px;
    color: #8a98b0;
}

.preview-avatar {
    width: 29px;
    height: 29px;
    background: #e8efff;
    border-radius: 50%;
    color: #2563eb;
    display: grid;
    place-items: center;
    font-size: 11px;
    font-weight: 800;
}

.preview-welcome {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-top: 23px;
}

.preview-welcome span {
    font-size: 10px;
    color: #8793aa;
}

.preview-welcome strong {
    font-size: 16px;
}

.stat-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 18px;
}

.stat {
    border: 1px solid #edf0f5;
    border-radius: 12px;
    padding: 13px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.stat-symbol {
    width: 27px;
    height: 27px;
    display: grid;
    place-items: center;
    border-radius: 8px;
    font-weight: 800;
    font-size: 12px;
    margin-bottom: 3px;
}

.blue {
    color: #2563eb;
    background: #eaf1ff;
}

.green {
    color: #16a36a;
    background: #e6f8ef;
}

.stat > span {
    font-size: 9px;
    color: #8290a8;
}

.stat > strong {
    font-size: 20px;
}

.stat > small {
    font-size: 9px;
    color: #9aa6b9;
}

/* TABLE PREVIEW */

.preview-table {
    margin-top: 18px;
    border: 1px solid #edf0f5;
    border-radius: 12px;
    padding: 12px;
}

.table-heading {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.table-heading strong {
    font-size: 10px;
}

.table-heading span {
    color: #8995aa;
    letter-spacing: 2px;
}

.preview-row {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 0;
    border-top: 1px solid #f0f2f7;
}

.mini-avatar {
    width: 27px;
    height: 27px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    font-size: 8px;
    font-weight: 800;
}

.avatar-blue {
    background: #e8f0ff;
    color: #2563eb;
}

.avatar-purple {
    background: #f0eaff;
    color: #8b5cf6;
}

.avatar-orange {
    background: #fff1e5;
    color: #e58b37;
}

.mini-name {
    display: flex;
    flex-direction: column;
    gap: 3px;
    flex: 1;
}

.mini-name strong {
    font-size: 9px;
}

.mini-name span {
    font-size: 8px;
    color: #95a0b4;
}

.mini-status {
    background: #eaf8f0;
    color: #15945d;
    padding: 5px 8px;
    border-radius: 20px;
    font-size: 8px;
    font-weight: 700;
}

/* FLOATING CARD */

.floating-card {
    position: absolute;
    bottom: -18px;
    right: -8px;
    padding: 12px 16px;
    border-radius: 14px;
    background: white;
    color: #1b2946;
    box-shadow: 0 15px 40px rgba(3,12,32,.22);
    display: flex;
    align-items: center;
    gap: 10px;
    animation: floating 4s ease-in-out infinite;
}

.floating-icon {
    width: 32px;
    height: 32px;
    background: #e5f8ee;
    color: #13a66b;
    border-radius: 10px;
    display: grid;
    place-items: center;
    font-weight: 800;
}

.floating-info {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.floating-info strong {
    font-size: 10px;
}

.floating-info span {
    font-size: 9px;
    color: #8b97ab;
}

@keyframes floating {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

/* FOOTER LEFT */

.art-footer {
    margin-top: auto;
    padding-top: 30px;
    display: flex;
    justify-content: space-between;
    color: #8295b8;
    font-size: 10px;
    position: relative;
    z-index: 2;
}

/* RIGHT SIDE */

.login-panel {
    min-height: 100vh;
    padding: 40px 7%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    position: relative;
}

.login-card {
    width: 100%;
    max-width: 430px;
}

/* HEADING */

.login-heading {
    margin-bottom: 35px;
}

.login-heading-tag {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #8a98b0;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.4px;
}

.login-heading-tag span {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #2563eb;
}

.login-heading h2 {
    margin-top: 18px;
    font-size: clamp(27px, 3vw, 35px);
    line-height: 1.3;
    letter-spacing: -1.5px;
    font-weight: 800;
    color: #182541;
}

.login-heading h2 span {
    color: #2563eb;
}

.login-heading p {
    color: #8995aa;
    font-size: 13px;
    margin-top: 10px;
    line-height: 1.7;
}

/* FORM */

.login-form {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.login-form label {
    display: flex;
    flex-direction: column;
    gap: 9px;
    color: #34425d;
    font-size: 12px;
    font-weight: 700;
}

.login-input {
    height: 55px;
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid #e3e8f1;
    background: white;
    border-radius: 13px;
    padding: 0 15px;
    transition: .25s ease;
}

.login-input > svg {
    width: 19px;
    height: 19px;
    flex-shrink: 0;
    color: #97a3b8;
}

.login-input:focus-within {
    border-color: #5b91f6;
    box-shadow: 0 0 0 4px rgba(37,99,235,.08);
    transform: translateY(-1px);
}

.login-input input {
    width: 100%;
    height: 100%;
    border: none;
    outline: none;
    background: transparent;
    color: #1d2b46;
    font-size: 13px;
}

.login-input input::placeholder {
    color: #a7b1c2;
}

/* TOGGLE PASSWORD */

.password-toggle {
    border: none;
    background: transparent;
    cursor: pointer;
    color: #8e9bb0;
    padding: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: .2s;
}

.password-toggle:hover {
    color: #2563eb;
}

.password-toggle svg {
    width: 19px;
    height: 19px;
}

/* BUTTON */

.btn {
    height: 54px;
    width: 100%;
    border: none;
    border-radius: 13px;
    background: linear-gradient(135deg, #3478f6, #2159d8);
    color: white;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-top: 5px;
    box-shadow: 0 10px 22px rgba(37,99,235,.20);
    transition: .25s ease;
}

.btn svg {
    width: 18px;
    height: 18px;
    transition: transform .25s;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(37,99,235,.28);
    background: linear-gradient(135deg, #2563eb, #1749c6);
}

.btn:hover svg {
    transform: translateX(4px);
}

.btn:active {
    transform: scale(.98);
}

/* ERROR */

.alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 15px;
    margin-bottom: 22px;
    border: 1px solid #fecaca;
    border-radius: 12px;
    background: #fff1f2;
    color: #be3348;
    font-size: 12px;
    line-height: 1.6;
}

.alert svg {
    width: 19px;
    height: 19px;
    flex-shrink: 0;
}

/* SECURITY */

.login-security {
    margin-top: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #99a4b7;
    font-size: 11px;
}

.login-security svg {
    width: 16px;
    height: 16px;
    color: #16a36a;
}

/* FOOTER RIGHT */

.login-panel-footer {
    position: absolute;
    bottom: 25px;
    left: 7%;
    right: 7%;
    display: flex;
    justify-content: space-between;
    color: #a0aabc;
    font-size: 10px;
}

/* MOBILE BRAND */

.mobile-brand {
    display: none;
}

/* TABLET */

@media (max-width: 1100px) {
    .login-shell {
        grid-template-columns: 1fr 1fr;
    }

    .login-art {
        padding-left: 6%;
        padding-right: 6%;
    }

    .art-copy h1 {
        font-size: 43px;
    }

    .art-illustration {
        margin-top: 30px;
    }

    .login-panel {
        padding: 35px 6%;
    }

    .dashboard-preview {
        padding: 15px;
    }
}

/* MOBILE */

@media (max-width: 800px) {
    .login-shell {
        grid-template-columns: 1fr;
    }

    .login-art {
        display: none;
    }

    .login-panel {
        min-height: 100vh;
        padding: 35px 24px 75px;
        background:
            radial-gradient(circle at top right, #e3edff 0%, transparent 45%),
            #f7f9fd;
    }

    .login-card {
        max-width: 430px;
    }

    .mobile-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 45px;
    }

    .mobile-brand strong {
        font-size: 17px;
        font-weight: 800;
        color: #1b2946;
    }

    .login-heading {
        margin-bottom: 30px;
    }

    .login-heading h2 {
        font-size: 32px;
    }

    .login-panel-footer {
        bottom: 20px;
    }
}

/* SMALL PHONE */

@media (max-width: 420px) {
    .login-panel {
        padding-left: 20px;
        padding-right: 20px;
    }

    .login-heading h2 {
        font-size: 28px;
    }

    .login-heading p {
        font-size: 12px;
    }

    .login-input {
        height: 52px;
    }

    .btn {
        height: 52px;
    }

    .login-panel-footer {
        font-size: 9px;
    }
}
</style>
</head>

<body>

<div class="background">
    <div class="circle circle-1"></div>
    <div class="circle circle-2"></div>
    <div class="circle circle-3"></div>
</div>

<main class="login-shell">

    <!-- PANEL KIRI -->
    <section class="login-art">

        <a class="brand" href="login.php">
            <div class="brand-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5V4.5A2.5 2.5 0 0 1 6.5 2Z"/>
                    <path d="M8 7h8M8 11h8M8 14h5"/>
                </svg>
            </div>

            <div class="brand-text">
                <strong>Data Siswa</strong>
                <span>Student Management System</span>
            </div>
        </a>

        <div class="art-copy">
            <span class="eyebrow">
                <span class="eyebrow-dot"></span>
                SISTEM INFORMASI SEKOLAH
            </span>

            <h1>
                Kelola data,<br>
                <span>lebih mudah.</span>
            </h1>

            <p>
                Platform pengelolaan data siswa yang praktis,
                terstruktur, dan dirancang untuk membantu pekerjaan
                administrasi menjadi lebih efisien.
            </p>

            <div class="features">
                <div class="feature">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="m17 11 2 2 4-4"/>
                        </svg>
                    </div>
                    <div class="feature-info">
                        <strong>Data Terorganisir</strong>
                        <small>Data siswa tersimpan dengan rapi</small>
                    </div>
                </div>

                <div class="feature">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="4"/>
                            <path d="m8 12 3 3 5-6"/>
                        </svg>
                    </div>
                    <div class="feature-info">
                        <strong>Akses Mudah</strong>
                        <small>Kelola informasi dengan cepat</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- ILUSTRASI DASHBOARD -->
        <div class="art-illustration">
            <div class="glow"></div>

            <div class="dashboard-preview">

                <div class="preview-header">
                    <div class="preview-logo">
                        <div class="preview-logo-icon">S</div>
                        <div class="preview-logo-text">
                            <strong>Dashboard</strong>
                            <span>Overview</span>
                        </div>
                    </div>
                    <div class="preview-avatar">A</div>
                </div>

                <div class="preview-welcome">
                    <span>Selamat datang kembali 👋</span>
                    <strong>Dashboard Admin</strong>
                </div>

                <div class="stat-grid">
                    <div class="stat">
                        <div class="stat-symbol blue">♙</div>
                        <span>Total Siswa</span>
                        <strong>1.248</strong>
                        <small>Data terdaftar</small>
                    </div>

                    <div class="stat">
                        <div class="stat-symbol green">✓</div>
                        <span>Data Aktif</span>
                        <strong>98.5%</strong>
                        <small>Data terverifikasi</small>
                    </div>
                </div>

                <div class="preview-table">
                    <div class="table-heading">
                        <strong>Data terbaru</strong>
                        <span>•••</span>
                    </div>

                    <div class="preview-row">
                        <div class="mini-avatar avatar-blue">AD</div>
                        <div class="mini-name">
                            <strong>Andi Darmawan</strong>
                            <span>XI RPL 1</span>
                        </div>
                        <div class="mini-status">Aktif</div>
                    </div>

                    <div class="preview-row">
                        <div class="mini-avatar avatar-purple">SR</div>
                        <div class="mini-name">
                            <strong>Siti Rahma</strong>
                            <span>XI RPL 2</span>
                        </div>
                        <div class="mini-status">Aktif</div>
                    </div>

                    <div class="preview-row">
                        <div class="mini-avatar avatar-orange">BP</div>
                        <div class="mini-name">
                            <strong>Budi Pratama</strong>
                            <span>XII RPL 1</span>
                        </div>
                        <div class="mini-status">Aktif</div>
                    </div>
                </div>
            </div>

            <div class="floating-card">
                <div class="floating-icon">✓</div>
                <div class="floating-info">
                    <strong>Sistem siap digunakan</strong>
                    <span>Data tersimpan dengan aman</span>
                </div>
            </div>
        </div>

        <div class="art-footer">
            <span>© <?= date('Y') ?> Data Siswa</span>
            <span>Digital • Simple • Efficient</span>
        </div>

    </section>

    <!-- PANEL LOGIN -->
    <section class="login-panel">

        <div class="login-card">

            <div class="mobile-brand">
                <div class="brand-mark">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5V4.5A2.5 2.5 0 0 1 6.5 2Z"/>
                    <path d="M8 7h8M8 11h8M8 14h5"/>
                    </svg>
                </div>
                <strong>Data Siswa</strong>
            </div>

            <div class="login-heading">
                <div class="login-heading-tag">
                    <span></span>
                    ADMINISTRATOR
                </div>

                <h2>Selamat datang <span>kembali!</span></h2>
                <p>Silakan masuk menggunakan akun administrator Anda.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 8v5M12 16h.01"/>
                    </svg>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form class="login-form" method="post" autocomplete="on">

                <input type="hidden" name="csrf"
                       value="<?= e($_SESSION['csrf_login']) ?>">

                <label for="username">
                    Username
                    <span class="login-input">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M4 21a8 8 0 0 1 16 0"/>
                        </svg>

                        <input
                            id="username"
                            type="text"
                            name="username"
                            value="<?= e($_POST['username'] ?? '') ?>"
                            placeholder="Masukkan username"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </span>
                </label>

                <label for="password">
                    Kata sandi
                    <span class="login-input">
                        <svg class="input-lock" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round">
                            <rect x="4" y="10" width="16" height="11" rx="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Masukkan kata sandi"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            class="password-toggle"
                            type="button"
                            id="togglePassword"
                            aria-label="Tampilkan kata sandi"
                            title="Tampilkan kata sandi"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </span>
                </label>

                <button class="btn" type="submit">
                    <span>Masuk ke Dashboard</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </button>

            </form>

            <div class="login-security">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11z"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
                <span>Login aman dan terlindungi</span>
            </div>

        </div>

        <div class="login-panel-footer">
            <span>© <?= date('Y') ?> Data Siswa</span>
            <span>Administrator Access</span>
        </div>

    </section>

</main>

<script>
const password = document.getElementById('password');
const toggle = document.getElementById('togglePassword');

toggle.addEventListener('click', function () {
    const show = password.type === 'password';

    password.type = show ? 'text' : 'password';

    toggle.setAttribute(
        'aria-label',
        show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'
    );

    toggle.setAttribute(
        'title',
        show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'
    );
});
</script>

</body>
</html>
