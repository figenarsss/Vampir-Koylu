<?php

$cards = [
    ['👥', 'Kullanıcı', $overview['users']],
    ['🎲', 'Toplam oyun', $overview['games']],
    ['🏁', 'Biten oyun', $overview['finished']],
    ['🔥', 'Sürüyor', $overview['in_progress']],
    ['🏰', 'Lobide', $overview['lobby']],
    ['📊', 'Ort. oyuncu / oyun', $overview['average_players']],
];
?>
<h1 class="h3 mb-4">📊 Yönetim paneli · Özet rapor</h1>

<div class="row g-3 mb-4">
    <?php foreach ($cards as [$icon, $label, $value]): ?>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card game-card h-100 text-center">
                <div class="card-body">
                    <div class="fs-2"><?= $e($icon) ?></div>
                    <div class="h3 mb-0"><?= $e($value) ?></div>
                    <div class="small text-secondary"><?= $e($label) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card game-card h-100"><div class="card-body">
            <h2 class="h5">⚔️ Takım kazanma oranı</h2>
            <?php if ($overview['finished'] === 0): ?>
                <p class="text-secondary mb-0">Henüz biten oyun yok.</p>
            <?php else: ?>
                <p class="mb-1">🏡 Köylüler — <?= $e($overview['villager_wins']) ?> oyun (%<?= $e($villager_rate) ?>)</p>
                <div class="progress mb-3" role="progressbar" aria-label="Köylülerin kazanma oranı">
                    <div class="progress-bar bg-success" style="width: <?= $e($villager_rate) ?>%"></div>
                </div>
                <p class="mb-1">🧛 Vampirler — <?= $e($overview['vampire_wins']) ?> oyun (%<?= $e($vampire_rate) ?>)</p>
                <div class="progress" role="progressbar" aria-label="Vampirlerin kazanma oranı">
                    <div class="progress-bar bg-danger" style="width: <?= $e($vampire_rate) ?>%"></div>
                </div>
            <?php endif; ?>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card game-card h-100"><div class="card-body">
            <h2 class="h5">🏆 En çok kazanan oyuncular</h2>
            <?php if ($leaderboard === []): ?>
                <p class="text-secondary mb-0">Henüz biten oyun yok.</p>
            <?php else: ?>
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>#</th><th>Oyuncu</th><th>Oyun</th><th>Galibiyet</th></tr></thead>
                    <tbody>
                    <?php foreach ($leaderboard as $i => $row): ?>
                        <tr>
                            <td><?= $e($i + 1) ?></td>
                            <td><?= $e($row['name']) ?></td>
                            <td><?= $e($row['games']) ?></td>
                            <td><?= $e($row['wins']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div></div>
    </div>
</div>
