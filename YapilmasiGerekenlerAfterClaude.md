# Yapılması Gerekenler (Claude'dan sonra)

Bu dosya, projeyi devralan kişi (proje sahibi) içindir. Claude Code, projenin kodunu, testlerini ve SQL dosyalarını ekibin SRS'ine ve proje şartlarına (`info/`) göre yazdı. Aşağıda **sizin kontrol etmeniz ya da tamamlamanız gereken** işler var. Teslimden önce okuyun; bitince bu dosyayı silebilirsiniz.

## Durum özeti

| Konu | Durum |
| --- | --- |
| Oyun, kayıt/giriş, oda, gece/gündüz/oylama, yönetici paneli | ✔ Çalışıyor |
| JSON depolaması (Aşama 2) ve MySQL depolaması (Aşama 3) | ✔ İkisi de çalışıyor |
| Ekibin SRS'indeki 19 FR ve 7 NFR | ✔ Kodda uygulandı (tuzakçı, rol sayısı ayarı, oda listesi dahil) |
| Ekibin test planı (T-01…T-33) | ✔ 33 senaryonun hepsi karşılanıyor ([docs/test-raporu.md](docs/test-raporu.md)) |
| Testler | ✔ 158 test: SQLite, JSON ve gerçek MariaDB'de geçti |
| MySQL katmanı gerçek sunucuda denendi | ✔ MariaDB 13.0.2 (şema, örnek veri, testler, paralel deney) |
| Kurulum talimatlı README | ✔ |
| Commit | ✘ **Yapılmadı**; tüm değişiklikler commitlenmemiş duruyor |
| GitHub: commit/PR/Projects panosu | ✘ **Sizin yapmanız gerekiyor** |
| Ekip üyelerinin kodu açıklayabilmesi | ✘ **Sizin yapmanız gerekiyor** |

## 1. Teslimden önce mutlaka yapın

- [ ] **Bölüm 2'deki kararları ekibe onaylatın.** SRS kendi içinde çelişiyordu; kod, FR/US/T planına göre yazıldı.
- [ ] **Kendi makinenizde bir kez kurulumu deneyin.** README'deki adımları (MySQL dahil) uygulayıp bir oyun oynayın. Claude'un makinesinde MySQL yerine MariaDB kullanıldı.
- [ ] **En az 5 kişiyle (ya da 5 tarayıcı/gizli pencereyle) gerçek bir oyun oynayın,** bir oyunda tuzakçı da kullanın (8+ oyuncu). Claude oyunu tek oyuncu olarak tarayıcıda denedi; çok oyunculu akış otomatik testlerle doğrulandı. Süreleri kısaltmak için `config/config.local.php` içine örn. `'sureler' => ['gece' => 10, 'gece_secim' => 6, 'gunduz' => 15, 'oylama' => 10]` yazın.
- [ ] **`composer install`, `composer test` ve (MySQL kuruluysa) `composer test:mysql` çalıştırın.** `pdo_sqlite` ve `pdo_mysql` eklentileri açık olmalı.
- [ ] **Her ekip üyesi kodu okuyup açıklayabilmeli** (şart). Okuma sırası bölüm 4'te.
- [ ] **GitHub sürecini tamamlayın.** Her üyenin kendi hesabıyla commit'i, pull request ve Projects panosu gerekiyor. Git kimliği şu an tutarsız (ad `Furkan-Bilici`, e-posta `medyaarslan3@gmail.com`); commitlemeden önce düzeltin. Commitlemeden önce **Fetch/Pull** yapın (ekipten yeni commit gelmiş olabilir), ayrı bir dal açın ve pull request gönderin.
- [ ] **`docs/ai-gunlugu.md` gerçeğe uygun kalmalı.** Zorunlu teslim kalemi. Claude'un kullanımı 4-6. satırlara eklendi; "Ekibin yaptığı" sütunundaki `[ ... ]` alanlarını ekip doldurmalı.

## 2. SRS çelişkilerinde alınan kararlar

SRS'in ilk bölümleri (giriş, oyun kuralları, çekirdek problem) ile ekibin sonradan yazdığı US/FR/NFR/test planı birbirinden ayrılıyordu. Kod, **FR/US ve test planına** uyduruldu; çünkü bunlar daha ayrıntılı ve birbiriyle tutarlı. Ekip aksine karar verirse ilgili kural kolayca değiştirilebilir.

| Konu | Giriş bölümleri | FR / US / test planı | Kodda uygulanan |
| --- | --- | --- | --- |
| Vampirler birbirini görür mü | Görür (1.4.2, 1.4.9) | Görmez (FR-9, US-7, T-18) | **Görmez** |
| Vampir kurbanı | Vampirler oylar, eşitlikte kimse ölmez (1.4.4) | Her vampir kendi hedefini seçer, farklı hedefte birden çok kişi ölür (US-6, T-14) | **Her vampirin hedefi ölür** |
| Gece seçimi değişir mi | Belirtilmemiş | Değişmez (FR-7, US-6, T-13) | **Değişmez** |
| Tuzakçı | Kapsam dışı (bölüm 2) | Var (FR-5, FR-8, US-6, T-09, T-16, T-17) | **Var** (rol sayısı ayarıyla 8+ oyuncuda) |
| Vampir sayısı | Sabit tablo (5-7: 1, 8-10: 2, 11-12: 3) | Kurucu ayarlar (FR-17, US-14) | **Kurucu ayarlar; ayarlamazsa tablo varsayılanı** |
| Kâhin ölünce rolü | Elenince açıklanır (1.4.6) | Gece ölse de açıklanır (US-10) | **Her ölümde açıklanır** |
| Sabah duyurusu | "Gece X öldü" (1.4.5) | "Bu gece X öldü" (FR-10, T-20) | **"Bu gece X öldü."** |
| Gece süresi | Tabloda 60 sn | 25 sn (FR-7) | **25 sn** (SRS 1.4.3 tablosunu düzeltin) |

Ek kurallar (planda açık yazmayan, kodda yorumlanan):

- Tuzak, hedef eve **eylem yapan ilk oyuncuyu** (eylem sırasına göre) yakalar; o oyuncunun eylemi iptal olur. Tuzakçı yalnızca "biri takıldı mı" bilgisini, takılan yalnızca "tuzağa takıldın" bilgisini sabahtan itibaren görür.
- Tuzakçı, 0-2 arasında seçilir ve **yalnızca 8+ oyuncuda** 1 ya da 2 olabilir; 5-7 oyuncuda vampir 1'dir, 8-12 oyuncuda 1-3. Oyuncu sayısı değişince ayar geçersiz kalırsa oyun başlarken varsayılana dönülür.
- Doktorun "art arda iki gece kendini koruma" kuralı, bir önceki gecenin eylemine bakar (araya başka birini korumak sayacı sıfırlar).
- Oylamada pas ve eşitlik kuralı SRS 1.4.6 ve FR-12 gibi: pas en çok oy kadar alırsa kimse elenmez.

### 2.1 Belge tarafında yapılacaklar

- `docs/srs.md` içinde "Kapsam dışı" ve "Çekirdek problem" başlıkları **iki kez** var; tek sürüme indirin. Giriş bölümlerini yukarıdaki kararlara göre güncelleyin (gece 25 sn, vampirler görmez, tuzakçı var).
- `docs/tasarim.md` State ve Strategy desenlerini anlatıyor. Kodda **Strategy** var (`src/Game/TargetRules/`: VampireRule, DoctorRule, SeerRule, TrapperRule). **State** deseni yok; fazlar `PhaseType` enum'u ve `match` ile yönetiliyor. Ya deseni ekleyin ya da belgeyi buna göre güncelleyin.
- `docs/tasarim.md` içindeki `img/arayuz-tasarimi.png` görseli depoda yok; bağlantı kırık.
- ER'ye göre kodda eklenen sütunlar: `users.name`, `users.account_role`, `games.version`, `actions.action_type`; takım değerleri `villagers`/`vampires`. ER'yi güncelleyin ya da şemayı değiştirin.
- Tasarım belgesi Türkçe sınıf adları kullanıyor, kod İngilizce (eşleşme: `docs/uygulama-notlari.md`).
- Sunum slaytları ile SRS da bazı yerlerde ayrışıyor; sunumda hangisini anlatacağınızı netleştirin.

## 3. Kod stili

İstek üzerine koddaki açıklama yorumları silindi; yalnızca birkaç kısa ve önemli yorum bırakıldı. Konteyner (`src/Core/Container.php`) kasıtlı olarak basit tutuldu: her servis `src/Core/Bootstrap.php` içinde açıkça kuruluyor, otomatik bağlama yok.

## 4. Kodu anlamak için okuma sırası

1. `docs/tasarim.md` (ekibin tasarımı), sonra `docs/uygulama-notlari.md` (kodun karşılığı)
2. `src/Game/PhaseManager.php` (faz durum makinesi, eşzamanlılık: koşullu `UPDATE`)
3. `src/Services/GameStateBuilder.php` (rol gizliliği tek yerde)
4. `src/Game/NightResolver.php`, `VoteCounter.php`, `WinChecker.php`, `RoleDistributor.php`, `src/Game/TargetRules/` (saf mantık, kısa)
5. `src/Services/GameService.php`, `LobbyService.php`, `AuthService.php`
6. `src/Repositories/` (arayüzler, sonra `Pdo/` ve `Json/`)
7. `src/Core/Bootstrap.php`, `src/Http/` ve `config/routes.php` (istek nasıl işleniyor, CSRF, yetki)
8. `src/Views/game/fragment.php` ve `public/assets/js/oyun.js` (kısmi yenileme)
9. `tests/` (özellikle `Services/PhaseManagerTest.php`, `GameStateTest.php`, `NightPhaseTest.php`, `VotingTest.php`)

Sunumda sorulması muhtemel sorular: "Aynı faz iki kez nasıl ilerlemez?" (koşullu `UPDATE` + sürüm; gerçek paralel deney raporda), "Başkasının rolü neden sızmaz?" (`GameStateBuilder`), "Tuzak nasıl çözülüyor?" (`NightResolver`), "Şifre/CSRF/XSS nasıl korunuyor?" (`docs/uygulama-notlari.md` bölüm 5).

## 5. Bilinen sınırlar ve ihtimaller

- **Fazı ilerleten, durum isteği atan oyuncudur.** Odadaki kimse sayfayı açık tutmuyorsa faz, ilk gelen istekle ilerler. Arka plan işi (cron) yoktur.
- **JSON depolamada gerçek geri alma (rollback) yoktur;** yalnızca dosya kilidi vardır. Bu yüzden JSON prototiptir, asıl ürün MySQL'dir.
- **Giriş denemesi sınırı (rate limit) yok.** İsterseniz bonus olarak ekleyin.
- **Bootstrap CDN'den yüklenir;** sunum yaptığınız bilgisayarda internet olmalı.
- **Örnek veri şifreleri herkesçe bilinir** (`Admin1234`, `Parola123`).
- **Yapılmayan bonuslar:** PSR-12 denetimi, PHPStan, sayfalama, CSV dışa aktarma.
- **Lobi kopyala düğmesi** ve ayrı "oyun günlüğü ekranı" yok; oda kodu lobide görünür, günlük oyun sayfasında "Duyurular" bölümündedir.
- Test raporunun "Kapsam dışı kalanlar" bölümüne bakın (kod kapsama oranı ölçülmedi, yük testi yok, 5 gerçek kişiyle elle test yok).

## 6. Zip'lemeden önce

- `vendor/` klasörünü zip'e koymayın (büyük); alıcı `composer install` ile kurar.
- `storage/veri/` ve `storage/phpunit-cache/` varsa silin; zaten git'e girmezler.
- `config/config.local.php` oluşturduysanız (MySQL şifreniz içinde olabilir) **zip'e koymayın**.
- `composer.lock` dosyası kalsın.
- Bu dosyayı (`YapilmasiGerekenlerAfterClaude.md`) teslim öncesi silmek ya da bırakmak sizin kararınız.
