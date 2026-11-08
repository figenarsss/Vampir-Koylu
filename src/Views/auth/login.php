<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card game-card">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">🔑 Giriş yap</h1>
                <?php if ($error !== null): ?>
                    <div class="alert alert-danger"><?= $e($error) ?></div>
                <?php endif; ?>
                <form method="post" action="<?= $e($url('/giris')) ?>">
                    <?= $csrf() ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta</label>
                        <input id="email" type="email" name="email" class="form-control" required
                               value="<?= $e($old['email'] ?? '') ?>" autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input id="password" type="password" name="password" class="form-control" required autocomplete="current-password">
                    </div>
                    <button class="btn btn-danger w-100">Giriş yap</button>
                </form>
                <p class="mt-3 mb-0 text-center text-secondary">Hesabın yok mu? <a href="<?= $e($url('/kayit')) ?>">Kayıt ol</a></p>
            </div>
        </div>
    </div>
</div>
