<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card game-card">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">🧛 Kayıt ol</h1>
                <?php if ($error !== null): ?>
                    <div class="alert alert-danger"><?= $e($error) ?></div>
                <?php endif; ?>
                <form method="post" action="<?= $e($url('/kayit')) ?>">
                    <?= $csrf() ?>
                    <div class="mb-3">
                        <label for="name" class="form-label">Görünen ad</label>
                        <input id="name" name="name" class="form-control" required minlength="2" maxlength="40"
                               value="<?= $e($old['name'] ?? '') ?>" autocomplete="nickname">
                        <div class="form-text">Oyunda diğer oyuncular seni bu adla görür.</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta</label>
                        <input id="email" type="email" name="email" class="form-control" required
                               value="<?= $e($old['email'] ?? '') ?>" autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input id="password" type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                        <div class="form-text">En az 8 karakter; en az bir harf ve bir rakam.</div>
                    </div>
                    <div class="mb-3">
                        <label for="password_repeat" class="form-label">Şifre (tekrar)</label>
                        <input id="password_repeat" type="password" name="password_repeat" class="form-control" required autocomplete="new-password">
                    </div>
                    <button class="btn btn-danger w-100">Kayıt ol</button>
                </form>
                <p class="mt-3 mb-0 text-center text-secondary">Zaten hesabın var mı? <a href="<?= $e($url('/giris')) ?>">Giriş yap</a></p>
            </div>
        </div>
    </div>
</div>
