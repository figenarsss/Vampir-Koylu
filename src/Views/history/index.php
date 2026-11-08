<h1 class="h3 mb-3">🎲 Oyunlarım</h1>

<form method="get" action="<?= $e($url('/oyunlarim')) ?>" class="row g-2 mb-4">
    <div class="col-md-5">
        <label for="q" class="visually-hidden">Ara</label>
        <input id="q" name="q" class="form-control" placeholder="Oda kodu ya da kurucu adı" value="<?= $e($term) ?>">
    </div>
    <div class="col-md-3">
        <label for="status" class="visually-hidden">Durum</label>
        <select id="status" name="status" class="form-select">
            <option value="">Tüm durumlar</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= $e($s->value) ?>" <?= $status === $s->value ? 'selected' : '' ?>><?= $e($s->label()) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-outline-light">Ara</button></div>
    <div class="col-auto"><a class="btn btn-outline-secondary" href="<?= $e($url('/oyunlarim')) ?>">Temizle</a></div>
</form>

<?php if ($games === []): ?>
    <p class="text-secondary">Bu ölçütlere uyan oyun bulunamadı.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Oda</th><th>Kurucu</th><th>Oyuncu</th><th>Durum</th><th>Sonuç</th><th>Tarih</th></tr></thead>
            <tbody>
            <?php foreach ($games as $game): ?>
                <tr>
                    <td><a href="<?= $e($url('/oyun/' . $game['room_code'])) ?>"><strong><?= $e($game['room_code']) ?></strong></a></td>
                    <td><?= $e($game['host_name']) ?></td>
                    <td><?= $e($game['player_count']) ?></td>
                    <td><?= $e(match ($game['status']) { 'lobby' => 'Lobi', 'in_progress' => 'Sürüyor', default => 'Bitti' }) ?></td>
                    <td><?= $e(match ($game['winner_team']) { 'vampires' => '🧛 Vampirler', 'villagers' => '🏡 Köylüler', default => '-' }) ?></td>
                    <td><?= $e($game['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
