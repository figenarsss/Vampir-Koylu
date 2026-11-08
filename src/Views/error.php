<div class="text-center py-5">
    <div class="display-1">🦇</div>
    <h1 class="display-5 mt-3">Hata <?= $e($status) ?></h1>
    <p class="lead text-secondary"><?= $e($message) ?></p>
    <a class="btn btn-danger" href="<?= $e($url('/')) ?>">Ana sayfaya dön</a>
</div>
