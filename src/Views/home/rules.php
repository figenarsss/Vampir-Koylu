<h1 class="h3 mb-4">📜 Oyun kuralları</h1>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card game-card h-100"><div class="card-body">
            <h2 class="h5">Amaç</h2>
            <p>5–12 oyuncu bir odada toplanır, herkese gizlice bir rol verilir. <strong>Köylüler</strong> tüm vampirleri elemeye,
               <strong>vampirler</strong> ise sayıca köylülere eşitlenmeye çalışır.</p>
            <h2 class="h5 mt-4">Fazlar</h2>
            <ul class="mb-0">
                <li>🌙 <strong>Gece (25 sn):</strong> ilk 15 saniyede vampir, doktor, kâhin ve tuzakçı eylemini seçer. Seçim değiştirilemez.</li>
                <li>☀️ <strong>Gündüz (75 sn):</strong> gece sonucu açıklanır. Tartışma uygulama dışında yapılır.</li>
                <li>🗳️ <strong>Oylama (en fazla 30 sn):</strong> herkes oy verir ya da pas geçer; herkes oy verince süre dolmadan biter.</li>
            </ul>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card game-card h-100"><div class="card-body">
            <h2 class="h5">Roller</h2>
            <?php foreach ($roles as $role): ?>
                <p class="mb-2"><?= $e($role->emoji()) ?> <strong><?= $e($role->label()) ?></strong>
                    <span class="badge text-bg-secondary"><?= $e($role->team()->label()) ?></span><br>
                    <span class="text-secondary"><?= $e($role->description()) ?></span></p>
            <?php endforeach; ?>
            <p class="small text-secondary mb-0">Oda kurucusu vampir ve tuzakçı sayısını ayarlar: 5–7 oyuncuda vampir 1; 8–12 oyuncuda vampir 1–3 ve tuzakçı 0–2.
                Ayarlanmazsa vampir sayısı 5–7 oyuncuda 1, 8–10 oyuncuda 2, 11–12 oyuncuda 3'tür.</p>
        </div></div>
    </div>
    <div class="col-12">
        <div class="card game-card"><div class="card-body">
            <h2 class="h5">Oylama ve kazanma</h2>
            <ul class="mb-0">
                <li>Kimse kendine oy veremez; oy bir kez verilir ve değiştirilemez. Oylamada pas geçilebilir.</li>
                <li>En çok oyu alan elenir ve rolü açıklanır. Eşitlikte ya da en çok oyu pas aldıysa kimse elenmez.</li>
                <li>Vampirler birbirini tanımaz; her vampir kendi kurbanını seçer, farklı hedefler seçilirse birden fazla kişi ölür.
                    Doktorun koruduğu kişi saldırıdan kurtulur. Tuzağa takılan oyuncunun eylemi iptal olur.</li>
                <li>Ölenler hayalet olarak izler; yalnızca herkese açık bilgileri görür.</li>
                <li>Tüm vampirler elenirse köylüler, vampir sayısı köylü tarafına eşit olursa vampirler kazanır.</li>
            </ul>
        </div></div>
    </div>
</div>
