<div class="hero text-center py-5">
    <div class="display-1">🏰🌙</div>
    <h1 class="display-4 fw-bold mt-3">Vampir <span class="text-danger">Köylü</span></h1>
    <p class="lead text-secondary mx-auto" style="max-width: 40rem;">
        5 ile 12 oyuncunun tarayıcıdan oynadığı gizli rollü bir rol oyunu.
        Gece vampirler avlanır, gündüz köylüler oylayarak birini eler. Rolünü yalnızca sen bilirsin.
    </p>
    <div class="d-flex gap-2 justify-content-center mt-4">
        <a class="btn btn-danger btn-lg" href="<?= $e($url('/kayit')) ?>">Kayıt ol</a>
        <a class="btn btn-outline-light btn-lg" href="<?= $e($url('/giris')) ?>">Giriş yap</a>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-4"><div class="card game-card h-100"><div class="card-body">
        <h2 class="h5">🚪 Oda aç, kodla katıl</h2>
        <p class="mb-0 text-secondary">Odayı açan oyuncu bir kod alır; arkadaşların bu kodla lobiye katılır.</p>
    </div></div></div>
    <div class="col-md-4"><div class="card game-card h-100"><div class="card-body">
        <h2 class="h5">🎭 Gizli roller</h2>
        <p class="mb-0 text-secondary">Köylü, vampir, doktor ve kâhin. Roller sunucuda rastgele dağıtılır, kimse başkasının rolünü göremez.</p>
    </div></div></div>
    <div class="col-md-4"><div class="card game-card h-100"><div class="card-body">
        <h2 class="h5">⏱️ Gece, gündüz, oylama</h2>
        <p class="mb-0 text-secondary">Her faz süreli ve sunucu kontrolündedir. Bir taraf kazanınca oyun biter.</p>
    </div></div></div>
</div>
