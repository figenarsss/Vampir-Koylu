# Vampir Köylü – Süreç Raporu (Aşama 2)

> **Teslimden önce:** `[ ... ]` ile işaretli yerleri grubunuzun gerçek bilgileriyle doldurun ve bu notu silin. Git geçmişinden, koddan ve testlerden doğrulanabilen her şey aşağıda hazır yazılmıştır; kimin ne yaptığı, eleştirmen geri bildirimi ve bağlantılar ise yalnızca grubun bildiği bilgilerdir.

## 1. Proje ve çekirdek problem

**Vampir Köylü**, 5–12 oyuncunun tarayıcıdan oynadığı gizli rollü bir rol oyunudur. Oyuncular oda koduyla lobiye katılır; herkese sunucuda rastgele ve gizli bir rol (köylü, vampir, doktor, kâhin, isteğe bağlı tuzakçı) verilir. Oyun gece, gündüz ve oylama fazlarıyla ilerler; bir taraf kazanınca biter. Uygulama PHP 8.2+ ile yazıldı, framework kullanmaz, verileri Aşama 2'de JSON dosyalarında tutar.

**Çekirdek problem** (SRS bölüm 3) beş parçadır ve hepsi sunucuda çözülür:

1. **Faz durum makinesi:** her fazın süresi vardır, süre dolunca fazı sunucu ilerletir; istemciden gelen süre/faz bilgisine güvenilmez; aynı faz eşzamanlı iki istekle iki kez ilerletilemez.
2. **Role özel gizli bilgi:** hiçbir oyuncu başkasının rolünü sunucu yanıtından öğrenemez; aynı adres her oyuncuya farklı veri döndürür.
3. **Oylama sayımı ve eşitlik:** oy vermeyen pas sayılır; eşitlikte ya da en çok oyu pas alırsa kimse elenmez.
4. **Kazanma koşulu denetimi:** her gece çözümünden ve her oylamadan sonra yapılır.
5. **Gece eylemlerinin sıralı çözümü:** tuzak, vampir ve doktor, kâhin sırasıyla.

## 2. Aşama 2 boyunca yapılan işler

Aşağıdaki tarihler ve kişiler depodaki commit geçmişinden alınmıştır (`git log`). Ekim satırları Aşama 1 belgeleridir (Aşama 2'nin temelidir); kod ve test çalışması 8 Kasım'dan itibaren görünür.

| Hafta | Yapılan iş | Üye (GitHub hesabı) |
| :--- | :--- | :--- |
| 7–14 Ekim | Depo, proje iskeleti, SRS girişi, oyun kuralları, kapsam dışı, çekirdek problem | Figen Arslan (`figenarsss`) |
| 14–15 Ekim | Kullanıcı hikâyeleri, ER diyagramı, ilk izlenebilirlik tablosu | Melek Efe (`melekcoder`) |
| 14–15 Ekim | 19 işlevsel gereksinim, tasarım belgesi, yapay zekâ günlüğü | Zeynep Tunç (`zeynep-tunc`) |
| 14–15 Ekim | 7 işlevsel olmayan gereksinim, kullanım senaryoları | Zeynep Çakmak |
| 8 Kasım | Oyun motoru, JSON ve PDO/MySQL repository'leri, servisler, arayüz, testler, test raporu (tek commit; yapay zekâ günlüğüne göre Claude Code ile üretildi) | Figen Arslan (`figenarsss`) |
| 11 Kasım | Yönerge kontrolü: JSON'da geçici dosya + `rename` ile güvenli yazma, 8 yeni test, `izlenebilirlik.md`, SRS'te yinelenen bölümlerin temizlenmesi, bu rapor | `[kim yaptı ... ]` |
| `[hafta ... ]` | `[ek işler, ekranlar, hata düzeltmeleri, elle test, demo hazırlığı ... ]` | `[üye ... ]` |

## 3. Tasarım ve uygulama kararları

**Karar 1 – Repository arayüzü, iki uygulama.** Servisler yalnızca `Repositories\*Interface` arayüzlerine bağımlıdır; `Json\*` (Aşama 2) ve `Pdo\*` (Aşama 3) aynı arayüzü uygular, hangisinin kullanılacağı `config` içindeki `depolama` ayarından gelir. *Alternatif:* önce yalnızca JSON yazıp Aşama 3'te servisleri yeniden yazmak. *Gerekçe:* ödev, Aşama 3'te controller ve servislerin büyük ölçüde aynı kalmasını istiyor; aynı 166 test iki depolamada da çalıştığı için iki uygulamanın aynı davranışı gösterdiği kanıtlanıyor.

**Karar 2 – Fazın tek kez ilerlemesi için sürüm sütunu.** PDO'da `UPDATE games SET version = version + 1 WHERE id = ? AND version = ?` koşullu güncellemesi kullanılır; etkilenen satır sayısı 1 olan istek fazı ilerletir. *Alternatifler:* oyun satırına `SELECT … FOR UPDATE` ile kilit; sürücü bağımsız uygulama düzeyinde mutex. *Gerekçe:* koşullu güncelleme tek sorguyla ve bekleme olmadan çalışır; JSON sürümünde aynı karşılaştır-ve-artır işlemi dosya kilidi altında yapılır.

**Karar 3 – Gizlilik kuralı tek yerde.** Rol filtrelemesi yalnızca `Services\GameStateBuilder` içindedir; JSON uç noktası ve HTML parçası aynı diziyi kullanır. *Alternatif:* her controller/uç nokta kendi filtresini yazar. *Gerekçe:* tek filtre, sızıntıyı tek noktada test etmeyi sağlar (`GameStateTest::testNoRoleLeaksDuringAFullGame`). Vampirler birbirini tanımadığı için sunucu vampirin başka bir vampiri seçmesini reddetmez, çünkü ret cevabı rolü sızdırırdı.

**Karar 4 – JSON'da ayrı kilit dosyası ve atomik yazma.** `JsonTable`, `flock` kilidini veri dosyasında değil `<ad>.json.lock` dosyasında tutar; veriyi önce rastgele adlı geçici dosyaya yazar, sonra `rename` ile asıl dosyanın yerine geçirir. Bozuk JSON sessizce boş sayılmaz, hata verilir. *Alternatif:* eski yöntem (asıl dosyayı açıp `ftruncate` + `fwrite`). *Gerekçe:* yazma yarıda kesilirse eski yöntem dosyayı boş bırakırdı; `rename` ile dosya ya eski ya yeni hâlindedir. Yazma sırasında dosya yer değiştirdiği için kilit ayrı dosyada olmak zorundadır.

## 4. Yapay zekâ kullanımı (üç örnek)

Ayrıntılar [ai-gunlugu.md](ai-gunlugu.md) içindedir.

1. **Eşzamanlı faz ilerletme (günlük #1).** Yapay zekâ iyimser kilit (`version`) ve PDO transaction çözümünü önerdi; öneri 10 eşzamanlı istekle denendi, yalnızca biri fazı ilerletti (`PhaseManagerTest::testOnlyOneOfTenConcurrentRequestsWinsTheVersionLock`; gerçek MariaDB'de 10 ayrı PHP süreciyle 3 tur).
2. **Yapay zekânın yanıldığı örnek – JSON yazma (günlük #7).** Yapay zekânın ürettiği JSON katmanı `flock` kullanıyordu ama veriyi doğrudan asıl dosyaya yazıyordu; yönerge geçici dosyaya yazıp dosyayı değiştirmeyi istiyor. Eksik, ödev yönergesi madde madde kontrol edilirken fark edildi. `JsonTable` yeniden yazıldı, 8 test eklendi ve kural bozma denemesiyle (`rename` yerine `copy`) testin başarısız olduğu görüldü.
3. **Yapay zekânın yanıldığı örnek – salt okunur dizi (günlük #8).** Üretilen kodda oylama sonucu günlüğe yazılırken `readonly` bir dizi sıralanmaya çalışılıyordu; her oylama bitişinde faz geçişi hata veriyordu. İlk tam test çalıştırmasında 10 test başarısız olunca fark edildi ve düzeltildi (`test-raporu.md` bölüm 6).

## 5. Testler

- **Test sayısı:** 166 test, 643 doğrulama. `vendor/bin/phpunit` (PDO, bellek içi SQLite) ve `vendor/bin/phpunit -c phpunit.json.xml` (JSON) ikisinde de hepsi geçer. Yönergenin en az 10 test beklentisi ve SRS NFR-4'ün (en az 5 çekirdek test) üzerindedir.
- **Önemli sınır durumları:** 4 oyuncuyla başlatma reddedilir, 5 ve 12 oyuncuyla kabul edilir, 13. oyuncu reddedilir; gece seçimi 14. saniyede kabul, 15. saniyeden sonra reddedilir; en çok oyu pas alırsa veya eşitlik olursa kimse elenmez; 1000 odada kod çakışması yoktur; aynı faza 10 eşzamanlı istekte yalnızca biri geçer.
- **Tekrarlanabilirlik:** süre `FakeClock` ile, rastgelelik tohumlu `Randomizer` ile denetlenir; testler gerçek saate bağlı değildir.
- **İzlenebilirlik tablosunun yeri:** [izlenebilirlik.md](izlenebilirlik.md) (kullanıcı hikâyesi → gereksinim → test).
- **Kural bozma kontrolü:** oylamadaki eşitlik kuralı bozulunca `VoteCounterTest::testTieMeansNobodyIsEliminated` başarısız oldu; ayrıntı izlenebilirlik belgesi bölüm 6'dadır.
- **PHPStan:** çalıştırılmadı (projede PHPStan yapılandırması yok).

## 6. Karşılaşılan üç önemli sorun

1. **JSON'da kayıp veri riski.** Doğrudan yazma, yazma yarıda kesilirse dosyayı bozabiliyordu → ayrı kilit dosyası, geçici dosya ve `rename`, bozuk dosyada hata (bölüm 3, karar 4).
2. **İki depolamada farklı davranış.** E-posta araması JSON'da büyük/küçük harf duyarsız, PDO'da (SQLite) duyarlıydı → `RepositoryTest` her iki çalıştırmada da aynı sonucu bekleyecek şekilde yazıldı ve fark düzeltildi.
3. **SRS'te çelişkiler ve yinelenen bölümler.** Gece süresi (60 sn / 25 sn), vampirlerin birbirini görmesi ve tuzakçının kapsamı farklı bölümlerde farklı yazılmıştı; "Kapsam dışı" ve "Çekirdek problem" bölümleri iki kez bulunuyordu → kodla uyumlu sürüm (gece 25 sn, vampirler birbirini görmez, tuzakçı kapsamda) tutuldu, eski kopya silindi.

## 7. Eleştirmen grubun geri bildirimleri

`[Eleştirmen grubun adı ... ]`

| Geri bildirim | Karar (kabul / ret) | Gerekçe veya yapılan düzeltme | Issue / PR bağlantısı |
| :--- | :--- | :--- | :--- |
| `[ ... ]` | `[ ... ]` | `[ ... ]` | `[ ... ]` |

## 8. Üyelerin katkıları

Commit geçmişine göre (`git log`, zip'teki depo):

| Üye | Commit'lerdeki katkı |
| :--- | :--- |
| Figen Arslan (`figenarsss`) | Depo ve iskelet, SRS giriş/kurallar/çekirdek problem, 8 Kasım'daki kod commit'i (oyun motoru, katmanlar, testler, belgeler) |
| Melek Efe (`melekcoder`) | Kullanıcı hikâyeleri, ER diyagramı, izlenebilirlik tablosu |
| Zeynep Tunç (`zeynep-tunc`) | 19 işlevsel gereksinim, tasarım belgesi, yapay zekâ günlüğü |
| Zeynep Çakmak | 7 işlevsel olmayan gereksinim, kullanım senaryoları |

Pull request ve inceleme: `[PR bağlantıları ve kimin kimi incelediği ... — yoksa burada açıkça belirtin]`.
Aşama 2 kod katkıları için `[her üyenin Aşama 2'de yazdığı kod, test veya belge ... ]`.

## 9. Aşama 3: MySQL'e geçiş

MySQL katmanı depoda hazırdır: `src/Repositories/Pdo/` altında `PdoUserRepository`, `PdoGameRepository`, `PdoPlayerRepository`, `PdoPhaseRepository`, `PdoActionRepository`, `PdoVoteRepository`, `PdoGameLogRepository`, `PdoReportRepository`, `PdoTransactionManager` ve `PdoRepositoryFactory`. Geçiş için `config.local.php` içinde `'depolama' => 'mysql'` yazmak yeterlidir; `database/schema.sql` ve `database/seed.sql` şemayı ve örnek veriyi kurar. Controller ve servis kodu değişmez, çünkü yalnızca arayüzlere bağımlıdır.

**Değişen sınıflar:** `Json\*` yerine `Pdo\*` (yukarıdaki liste) ve `JsonRepositoryFactory` yerine `PdoRepositoryFactory`.
**Riskler:** (1) iki uygulamanın aynı davranmaması (e-posta büyük/küçük harf farkı bu yüzden bulundu); (2) tarih/saat biçimleri (`Support\DbDate`); (3) eşzamanlılık: JSON'da tek dosya kilidi, MySQL'de koşullu `UPDATE` ve transaction; (4) 3NF ve kısıtların (CHECK, dış anahtar) JSON'da bulunmaması.
**Mimarinin desteği:** aynı 166 test iki depolamada çalıştığı ve MySQL şeması gerçek MariaDB'ye karşı doğrulandığı için geçişin davranışı değiştirmediği ölçülebilir (`test-raporu.md`, bölüm 5).
