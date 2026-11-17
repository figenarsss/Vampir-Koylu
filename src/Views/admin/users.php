<h1 class="h3 mb-3">👥 Kullanıcılar</h1>

<form method="get" action="<?= $e($url('/yonetim/kullanicilar')) ?>" class="row g-2 mb-4">
    <div class="col-md-5">
        <label for="q" class="visually-hidden">Ara</label>
        <input id="q" name="q" class="form-control" placeholder="Ad ya da e-posta" value="<?= $e($term) ?>">
    </div>
    <div class="col-md-3">
        <label for="role" class="visually-hidden">Hesap türü</label>
        <select id="role" name="role" class="form-select">
            <option value="">Tüm hesap türleri</option>
            <?php foreach ($roles as $r): ?>
                <option value="<?= $e($r->value) ?>" <?= $role === $r->value ? 'selected' : '' ?>><?= $e($r->label()) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-outline-light">Ara</button></div>
    <div class="col-auto"><a class="btn btn-outline-secondary" href="<?= $e($url('/yonetim/kullanicilar')) ?>">Temizle</a></div>
</form>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr><th>#</th><th>Ad</th><th>E-posta</th><th>Hesap türü</th><th>Üyelik</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $row): ?>
            <tr>
                <td><?= $e($row->id) ?></td>
                <td><?= $e($row->name) ?></td>
                <td><?= $e($row->email) ?></td>
                <td><span class="badge text-bg-<?= $row->isAdmin() ? 'danger' : 'secondary' ?>"><?= $e($row->accountRole->label()) ?></span></td>
                <td><?= $e($row->createdAt->format('d.m.Y')) ?></td>
                <td class="text-end">
                    <?php if ($row->id !== $user->id): ?>
                        <form method="post" action="<?= $e($url('/yonetim/kullanicilar/' . $row->id . '/yetki')) ?>" class="d-inline">
                            <?= $csrf() ?>
                            <input type="hidden" name="role" value="<?= $e($row->isAdmin() ? 'player' : 'admin') ?>">
                            <button class="btn btn-sm btn-outline-light"><?= $row->isAdmin() ? 'Yöneticiliği al' : 'Yönetici yap' ?></button>
                        </form>
                    <?php else: ?>
                        <span class="text-secondary small">(sen)</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if ($users === []): ?>
    <p class="text-secondary">Bu ölçütlere uyan kullanıcı bulunamadı.</p>
<?php endif; ?>
