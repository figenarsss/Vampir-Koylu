<div class="row g-4">
    <div class="col-md-6">
        <div class="card game-card h-100">
            <div class="card-body">
                <h2 class="h5">🚪 Yeni oda aç</h2>
                <p class="text-secondary">Bir oda açıp kodunu arkadaşlarınla paylaş. Oyunu yalnızca sen başlatırsın.</p>
                <form method="post" action="<?= $e($url('/oda')) ?>">
                    <?= $csrf() ?>
                    <button class="btn btn-danger">Oda oluştur</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card game-card h-100">
            <div class="card-body">
                <h2 class="h5">🔑 Kodla katıl</h2>
                <form method="post" action="<?= $e($url('/oda/katil')) ?>" class="row g-2">
                    <?= $csrf() ?>
                    <div class="col">
                        <label for="code" class="visually-hidden">Oda kodu</label>
                        <input id="code" name="code" class="form-control text-uppercase" placeholder="Oda kodu (örn. K7M2QX)"
                               maxlength="6" required autocomplete="off">
                    </div>
                    <div class="col-auto"><button class="btn btn-outline-light">Katıl</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<h2 class="h5 mt-5">🎲 Devam eden oyunlarım</h2>
<?php if ($activeGames === []): ?>
    <p class="text-secondary">Şu anda devam eden bir oyunun yok.</p>
<?php else: ?>
    <div class="list-group mb-4">
        <?php foreach ($activeGames as $game): ?>
            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
               href="<?= $e($url('/oyun/' . $game['room_code'])) ?>">
                <span><strong><?= $e($game['room_code']) ?></strong> · kurucu: <?= $e($game['host_name']) ?> · <?= $e($game['player_count']) ?> oyuncu</span>
                <span class="badge text-bg-secondary"><?= $e($game['status'] === 'lobby' ? 'Lobi' : 'Sürüyor') ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<h2 class="h5 mt-4">🏁 Son biten oyunlar</h2>
<?php if ($recentGames === []): ?>
    <p class="text-secondary">Henüz biten bir oyunun yok.</p>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($recentGames as $game): ?>
            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
               href="<?= $e($url('/oyun/' . $game['room_code'])) ?>">
                <span><strong><?= $e($game['room_code']) ?></strong> · <?= $e($game['player_count']) ?> oyuncu · <?= $e($game['created_at']) ?></span>
                <span class="badge text-bg-<?= $game['winner_team'] === 'vampires' ? 'danger' : 'success' ?>">
                    <?= $e($game['winner_team'] === 'vampires' ? '🧛 Vampirler kazandı' : '🏡 Köylüler kazandı') ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
