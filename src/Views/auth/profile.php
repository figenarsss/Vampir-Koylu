<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card game-card mb-4">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">👤 Profilim</h1>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Ad</dt><dd class="col-sm-8"><?= $e($user->name) ?></dd>
                    <dt class="col-sm-4">E-posta</dt><dd class="col-sm-8"><?= $e($user->email) ?></dd>
                    <dt class="col-sm-4">Hesap türü</dt><dd class="col-sm-8"><?= $e($user->accountRole->label()) ?></dd>
                    <dt class="col-sm-4">Üyelik</dt><dd class="col-sm-8 mb-0"><?= $e($user->createdAt->format('d.m.Y')) ?></dd>
                </dl>
            </div>
        </div>

        <div class="card game-card">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">🔒 Şifre değiştir</h2>
                <form method="post" action="<?= $e($url('/profil/sifre')) ?>">
                    <?= $csrf() ?>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mevcut şifre</label>
                        <input id="current_password" type="password" name="current_password" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Yeni şifre</label>
                        <input id="new_password" type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label for="new_password_repeat" class="form-label">Yeni şifre (tekrar)</label>
                        <input id="new_password_repeat" type="password" name="new_password_repeat" class="form-control" required autocomplete="new-password">
                    </div>
                    <button class="btn btn-danger">Şifreyi değiştir</button>
                </form>
            </div>
        </div>
    </div>
</div>
