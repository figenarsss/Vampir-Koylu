<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h3 mb-0">Oda <span class="room-code"><?= $e($code) ?></span></h1>
    <a class="btn btn-sm btn-outline-secondary" href="<?= $e($url('/')) ?>">← Ana sayfa</a>
</div>

<div id="bolum" data-url="<?= $e($fragmentUrl) ?>" data-imza="<?= $e($state['signature']) ?>">
    <?= $partial('game/fragment', ['state' => $state, 'code' => $code]) ?>
</div>

<script src="<?= $e($url('/assets/js/oyun.js')) ?>"></script>
