<h1 class="h3 mb-3">🚪 Açık odalar</h1>

<form method="get" action="<?= $e($url('/odalar')) ?>" class="row g-2 mb-4">
    <div class="col-md-6">
        <label for="q" class="visually-hidden">Oda kodu ya da kurucu</label>
        <input id="q" name="q" class="form-control" placeholder="Oda kodu ya da kurucu adı" value="<?= $e($term) ?>">
    </div>
    <div class="col-auto"><button class="btn btn-outline-light">Ara</button></div>
    <div class="col-auto"><a class="btn btn-outline-secondary" href="<?= $e($url('/odalar')) ?>">Temizle</a></div>
</form>

<?php if ($rooms === []): ?>
    <p class="text-secondary">Katılabileceğiniz açık oda bulunamadı.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Oda</th><th>Kurucu</th><th>Oyuncu</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rooms as $room): ?>
                <tr>
                    <td><strong><?= $e($room['room_code']) ?></strong></td>
                    <td><?= $e($room['host_name']) ?></td>
                    <td><?= $e($room['player_count']) ?> / <?= $e($maxPlayers) ?></td>
                    <td class="text-end">
                        <form method="post" action="<?= $e($url('/oda/katil')) ?>">
                            <?= $csrf() ?>
                            <input type="hidden" name="code" value="<?= $e($room['room_code']) ?>">
                            <button class="btn btn-sm btn-danger" <?= $room['player_count'] >= $maxPlayers ? 'disabled' : '' ?>>Katıl</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
