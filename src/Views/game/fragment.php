<?php
$game = $state['game'];
$me = $state['me'];
$stage = $game['stage'];
$night = $state['night'];
$vote = $state['vote'];
$settings = $state['settings'];
$postUrl = static fn (string $action): string => $url("/oyun/{$code}/{$action}");
?>
<div class="stage stage-<?= $e($stage) ?>" data-imza="<?= $e($state['signature']) ?>">

    <section id="ust" data-canli class="card game-card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="text-secondary small">Aşama</div>
                <div class="h4 mb-0"><?= $e($game['stage_label']) ?></div>
            </div>
            <?php if ($game['remaining_seconds'] !== null): ?>
                <div class="text-end">
                    <div class="text-secondary small">Kalan süre</div>
                    <div class="h3 mb-0 font-monospace" id="sayac" data-kalan="<?= $e($game['remaining_seconds']) ?>"><?= $e($game['remaining_seconds']) ?> sn</div>
                </div>
            <?php elseif ($stage === 'lobby'): ?>
                <div class="text-end">
                    <div class="text-secondary small">Oyuncular</div>
                    <div class="h3 mb-0"><?= $e($game['player_count']) ?> / <?= $e($game['max_players']) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="row g-3">
        <div class="col-lg-8">

            <?php if ($stage !== 'lobby'): ?>
                <section class="card game-card mb-3">
                    <div class="card-body">
                        <?php if ($me['role'] !== null): ?>
                            <div class="d-flex align-items-center gap-3">
                                <div class="role-emoji"><?= $e($me['role']['emoji']) ?></div>
                                <div>
                                    <div class="text-secondary small">Rolün</div>
                                    <div class="h4 mb-1"><?= $e($me['role']['label']) ?></div>
                                    <div class="text-secondary"><?= $e($me['role_description']) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($me['is_ghost']): ?>
                            <div class="alert alert-secondary mt-3 mb-0">
                                👻 Oyundan çıktın. Hayalet olarak oyunu izleyebilirsin; yalnızca herkese açık bilgileri görürsün.
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($stage === 'lobby'): ?>
                <section class="card game-card mb-3">
                    <div class="card-body">
                        <h2 class="h5">🏰 Lobi</h2>
                        <p>Arkadaşların <strong>Odalar</strong> sayfasından ya da <strong>Ana sayfa → Kodla katıl</strong> bölümünden şu kodla katılabilir:
                           <span class="room-code"><?= $e($code) ?></span></p>
                        <p class="text-secondary">Oyun <?= $e($game['min_players']) ?> ile <?= $e($game['max_players']) ?> oyuncuyla oynanır.
                           Oyunu yalnızca oda kurucusu başlatır; başladıktan sonra yeni oyuncu katılamaz.</p>

                        <?php if ($me['is_host']): ?>
                            <form method="post" action="<?= $e($postUrl('ayar')) ?>" class="row g-2 align-items-end mb-3">
                                <?= $csrf() ?>
                                <div class="col-auto">
                                    <label for="vampires" class="form-label small mb-1">🧛 Vampir sayısı</label>
                                    <select id="vampires" name="vampires" class="form-select form-select-sm">
                                        <?php foreach ($settings['vampire_options'] as $option): ?>
                                            <option value="<?= $e($option) ?>" <?= $settings['vampires'] === $option ? 'selected' : '' ?>><?= $e($option) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <label for="trappers" class="form-label small mb-1">🪤 Tuzakçı sayısı</label>
                                    <select id="trappers" name="trappers" class="form-select form-select-sm">
                                        <?php foreach ($settings['trapper_options'] as $option): ?>
                                            <option value="<?= $e($option) ?>" <?= $settings['trappers'] === $option ? 'selected' : '' ?>><?= $e($option) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-auto"><button class="btn btn-sm btn-outline-light">Ayarı kaydet</button></div>
                                <div class="col-12 form-text">Tuzakçı en az 8 oyuncuyla seçilebilir. 5-7 oyuncuda vampir sayısı 1'dir.</div>
                            </form>

                            <form method="post" action="<?= $e($postUrl('baslat')) ?>" class="d-inline">
                                <?= $csrf() ?>
                                <button class="btn btn-danger" <?= $game['player_count'] < $game['min_players'] ? 'disabled' : '' ?>>▶ Oyunu başlat</button>
                            </form>
                            <?php if ($game['player_count'] < $game['min_players']): ?>
                                <span class="text-secondary ms-2">En az <?= $e($game['min_players']) ?> oyuncu gerekli.</span>
                            <?php endif; ?>
                            <form method="post" action="<?= $e($postUrl('sil')) ?>" class="d-inline float-end"
                                  onsubmit="return confirm('Oda silinsin mi? Bu işlem geri alınamaz.');">
                                <?= $csrf() ?>
                                <button class="btn btn-outline-secondary btn-sm">🗑️ Odayı sil</button>
                            </form>
                        <?php else: ?>
                            <p class="mb-2">⚙️ Rol ayarı: <?= $e($settings['vampires']) ?> vampir, <?= $e($settings['trappers']) ?> tuzakçı.</p>
                            <p class="mb-2">⏳ Oda kurucusunun oyunu başlatması bekleniyor...</p>
                            <form method="post" action="<?= $e($postUrl('ayril')) ?>">
                                <?= $csrf() ?>
                                <button class="btn btn-outline-secondary btn-sm">Odadan ayrıl</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </section>

            <?php elseif ($stage === 'night'): ?>
                <section class="card game-card mb-3">
                    <div class="card-body">
                        <h2 class="h5">🌙 Gece</h2>
                        <?php if ($night === null): ?>
                            <p class="mb-0"><?= $me['alive'] ? 'Köy uyuyor. Rolünün gece yeteneği yok; sabahı bekle.' : 'Gece sürüyor.' ?></p>
                        <?php elseif ($night['chosen_id'] !== null): ?>
                            <p class="mb-0 text-success">✔ Seçimin kaydedildi: <strong><?= $e($night['chosen_name']) ?></strong>. Seçim değiştirilemez.</p>
                        <?php elseif (!$night['selection_open']): ?>
                            <p class="mb-0">⏳ Seçim süresi doldu, eylemler uygulanıyor.</p>
                        <?php else: ?>
                            <p class="text-secondary">Gecenin ilk bölümünde seçimini yap. Seçim kaydedilince değiştirilemez.</p>
                            <form method="post" action="<?= $e($postUrl('eylem')) ?>">
                                <?= $csrf() ?>
                                <div class="list-group mb-3">
                                    <?php foreach ($night['targets'] as $target): ?>
                                        <label class="list-group-item">
                                            <input class="form-check-input me-2" type="radio" name="target_id" value="<?= $e($target['id']) ?>" required>
                                            <?= $e($target['name']) ?><?= $target['is_me'] ? ' (sen)' : '' ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <button class="btn btn-danger"><?= $e($night['action_label']) ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </section>

            <?php elseif ($stage === 'day'): ?>
                <section class="card game-card mb-3">
                    <div class="card-body">
                        <h2 class="h5">☀️ Gündüz</h2>
                        <p class="mb-0">Sabah duyurusu aşağıdaki günlükte. Uygulamada sohbet yoktur; tartışmayı oyun dışında yapın.
                           Süre dolunca oylama başlayacak.</p>
                    </div>
                </section>

            <?php elseif ($stage === 'vote'): ?>
                <section class="card game-card mb-3">
                    <div class="card-body">
                        <h2 class="h5">🗳️ Oylama</h2>
                        <?php if ($vote['can_vote']): ?>
                            <form method="post" action="<?= $e($postUrl('oy')) ?>">
                                <?= $csrf() ?>
                                <div class="list-group mb-3">
                                    <?php foreach ($vote['targets'] as $target): ?>
                                        <label class="list-group-item">
                                            <input class="form-check-input me-2" type="radio" name="target_id" value="<?= $e($target['id']) ?>" required>
                                            <?= $e($target['name']) ?>
                                        </label>
                                    <?php endforeach; ?>
                                    <label class="list-group-item">
                                        <input class="form-check-input me-2" type="radio" name="target_id" value="pas" required>
                                        ⏭️ Pas geç
                                    </label>
                                </div>
                                <button class="btn btn-danger">Oyumu kullan</button>
                                <span class="text-secondary ms-2 small">Oy bir kez verilir, değiştirilemez.</span>
                            </form>
                        <?php elseif ($vote['has_voted']): ?>
                            <p class="text-success">✔ Oyun kaydedildi. Diğer oyuncuları bekliyorsun.</p>
                        <?php else: ?>
                            <p class="text-secondary">Oyundan çıktığın için oy veremezsin; oylamayı izleyebilirsin.</p>
                        <?php endif; ?>

                        <div id="oy-dagilimi" data-canli class="mt-3">
                            <div class="small text-secondary mb-2">Oy dağılımı (<?= $e($vote['voted_count']) ?>/<?= $e($vote['voter_count']) ?> oy kullandı)</div>
                            <?php foreach ($vote['tally'] as $row): ?>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="tally-name"><?= $e($row['name']) ?></span>
                                    <div class="progress flex-grow-1" role="progressbar" aria-label="<?= $e($row['name']) ?> oy sayısı">
                                        <div class="progress-bar bg-danger" style="width: <?= $e($vote['voter_count'] > 0 ? (int) round($row['votes'] * 100 / $vote['voter_count']) : 0) ?>%"></div>
                                    </div>
                                    <span class="tally-count"><?= $e($row['votes']) ?></span>
                                </div>
                            <?php endforeach; ?>
                            <div class="small text-secondary mt-2">Pas: <?= $e($vote['pass_count']) ?></div>
                        </div>
                    </div>
                </section>

            <?php elseif ($stage === 'finished'): ?>
                <section class="card game-card mb-3 winner-card">
                    <div class="card-body text-center">
                        <div class="display-3"><?= $e($game['winner']['emoji'] ?? '🏁') ?></div>
                        <h2 class="h3 mt-2">Oyun bitti: <?= $e($game['winner']['label'] ?? '') ?> kazandı!</h2>
                        <p class="text-secondary mb-3">Tüm roller sağdaki listede açıklandı. Olayların tam özeti aşağıda.</p>
                        <?php if ($me['is_host']): ?>
                            <form method="post" action="<?= $e($postUrl('sil')) ?>" class="d-inline"
                                  onsubmit="return confirm('Oda ve oyun kayıtları silinsin mi? Bu işlem geri alınamaz.');">
                                <?= $csrf() ?>
                                <button class="btn btn-outline-secondary btn-sm">🗑️ Odayı sil</button>
                            </form>
                        <?php endif; ?>
                        <a class="btn btn-danger btn-sm" href="<?= $e($url('/')) ?>">Ana sayfaya dön</a>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($state['seer_results'] !== []): ?>
                <section class="card game-card mb-3">
                    <div class="card-body">
                        <h2 class="h5">🔮 Öğrendiklerin</h2>
                        <ul class="mb-0">
                            <?php foreach ($state['seer_results'] as $result): ?>
                                <li><?= $e($result['name']) ?> → <strong><?= $e($result['role']['emoji'] . ' ' . $result['role']['label']) ?></strong></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($state['trap_notes'] !== []): ?>
                <section class="card game-card mb-3">
                    <div class="card-body">
                        <h2 class="h5">🪤 Tuzak bildirimleri</h2>
                        <ul class="mb-0">
                            <?php foreach ($state['trap_notes'] as $note): ?>
                                <li><?= $e($note) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>
            <?php endif; ?>

            <section id="duyurular" data-canli class="card game-card mb-3">
                <div class="card-body">
                    <h2 class="h5"><?= $state['report'] !== null ? '📋 Olay özeti' : '📣 Duyurular' ?></h2>
                    <?php $entries = $state['report'] ?? $state['announcements']; ?>
                    <?php if ($entries === []): ?>
                        <p class="text-secondary mb-0">Henüz bir olay yok.</p>
                    <?php else: ?>
                        <ul class="log list-unstyled mb-0">
                            <?php foreach ($entries as $entry): ?>
                                <li class="<?= !empty($entry['secret']) ? 'text-secondary' : '' ?>">
                                    <span class="font-monospace small text-secondary"><?= $e($entry['time']) ?></span>
                                    <?= !empty($entry['secret']) ? '🔒' : '' ?> <?= $e($entry['message']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="col-lg-4">
            <section id="oyuncular" data-canli class="card game-card">
                <div class="card-body">
                    <h2 class="h5">👥 Oyuncular (<?= $e($game['player_count']) ?>)</h2>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($state['players'] as $player): ?>
                            <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 <?= $player['alive'] ? '' : 'player-dead' ?>">
                                <span>
                                    <?= $player['is_host'] ? '👑 ' : '' ?><?= $e($player['name']) ?><?= $player['is_me'] ? ' <span class="text-secondary">(sen)</span>' : '' ?>
                                    <?php if ($stage !== 'lobby' && !$player['alive']): ?>
                                        <br><small class="text-secondary">💀 <?= $e($player['status']) ?></small>
                                    <?php endif; ?>
                                </span>
                                <span class="d-flex align-items-center gap-2">
                                    <?php if ($player['role'] !== null): ?>
                                        <span class="badge text-bg-<?= $player['role']['key'] === 'vampire' ? 'danger' : 'secondary' ?>">
                                            <?= $e($player['role']['emoji'] . ' ' . $player['role']['label']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($stage === 'lobby' && $me['is_host'] && !$player['is_me']): ?>
                                        <form method="post" action="<?= $e($postUrl('at')) ?>">
                                            <?= $csrf() ?>
                                            <input type="hidden" name="player_id" value="<?= $e($player['id']) ?>">
                                            <button class="btn btn-sm btn-outline-danger" title="Odadan at">✖</button>
                                        </form>
                                    <?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</div>
