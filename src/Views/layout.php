<?php

$alertClass = ['success' => 'success', 'error' => 'danger', 'info' => 'info'];
?>
<!doctype html>
<html lang="tr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($title) ?> · <?= $e($appName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $e($url('/assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-md border-bottom border-secondary-subtle">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= $e($url('/')) ?>">🦇 <?= $e($appName) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-label="Menüyü aç">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= $e($url('/kurallar')) ?>">📜 Kurallar</a></li>
                <?php if ($user !== null): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $e($url('/odalar')) ?>">🚪 Odalar</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $e($url('/oyunlarim')) ?>">🎲 Oyunlarım</a></li>
                <?php endif; ?>
                <?php if ($user !== null && $user->isAdmin()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $e($url('/yonetim')) ?>">📊 Yönetim</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $e($url('/yonetim/kullanicilar')) ?>">👥 Kullanıcılar</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $e($url('/yonetim/oyunlar')) ?>">🗂️ Tüm oyunlar</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav align-items-md-center gap-md-2">
                <?php if ($user !== null): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $e($url('/profil')) ?>">👤 <?= $e($user->name) ?></a></li>
                    <li class="nav-item">
                        <form method="post" action="<?= $e($url('/cikis')) ?>">
                            <?= $csrf() ?>
                            <button class="btn btn-sm btn-outline-secondary">Çıkış</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $e($url('/giris')) ?>">Giriş</a></li>
                    <li class="nav-item"><a class="btn btn-sm btn-danger" href="<?= $e($url('/kayit')) ?>">Kayıt ol</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= $e($alertClass[$flash['type']] ?? 'info') ?> alert-dismissible fade show" role="alert">
            <?= $e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
        </div>
    <?php endforeach; ?>

    <?= $content ?>
</main>

<footer class="container py-4 text-center text-secondary small">
    🌙 <?= $e($appName) ?> · PHP dönem projesi
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
