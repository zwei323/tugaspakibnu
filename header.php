<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Siswa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="appbar">
    <div class="appbar-inner">
        <a class="brand" href="index.php">
            <span class="brand-mark">S</span>
            <span>Data Siswa</span>
        </a>
        <div class="appbar-actions">
            <span class="brand-sub">Sekolah</span>
            <a class="logout-link" href="logout.php" title="Keluar">
                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/></svg>
                Keluar
            </a>
        </div>
    </div>
</header>
<main class="wrap">
    <?php if ($p = ambil_pesan()): ?>
        <div class="alert <?= e($p['tipe']) ?>" role="status"><?= e($p['teks']) ?></div>
    <?php endif; ?>
