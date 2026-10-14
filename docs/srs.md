# Vampir Köylü – Gereksinim Belgesi (SRS)

## 1. Giriş

### 1.1 Amaç

Bu belge, Vampir Köylü web oyununun gereksinimlerini tanımlar. Projenin ne yapacağını, nelerin kapsam dışı olduğunu ve teknik olarak zor kısımlarını açıklar.

### 1.2 Proje özeti

Vampir Köylü, 5 ile 12 oyuncunun tarayıcıdan oynadığı bir rol oyunudur. Oyuncular oda koduyla lobiye katılır ve herkese gizlice bir rol verilir. Oyun gece ve gündüz fazlarıyla ilerler. Gece özel roller gizli eylem yapar, gündüz oyuncular oy vererek birini eler. Bir taraf kazanınca oyun biter.

### 1.3 Grup üyeleri

| Ad Soyad      | Sorumluluk                                                   |
| ------------- | ------------------------------------------------------------ |
| Figen Arslan  | Repo, giriş, kapsam dışı, çekirdek problem, birleştirme      |
| Melek Efe     | Kullanıcı hikâyeleri                                         |
| Zeynep Tunç   | İşlevsel gereksinimler                                       |
| Zeynep Çakmak | İşlevsel olmayan gereksinimler, use-case, yapay zekâ günlüğü |

### 1.4 Oyun kuralları

#### 1.4.1 Oda ve oyuncular

- Oyuncu oda açar, sistem oda kodu üretir. Diğer oyuncular bu kodla katılır.
- Odada en az 5, en fazla 12 oyuncu bulunur.
- Oyunu yalnızca oda sahibi başlatır. Oyun başladıktan sonra yeni oyuncu katılamaz.

#### 1.4.2 Roller

| Rol     | Taraf     | Gece yeteneği                                           |
| ------- | --------- | ------------------------------------------------------- |
| Köylü   | Köylüler  | Yok                                                     |
| Vampir  | Vampirler | Kurban seçer. Vampirler birbirini görür.                |
| Doktor  | Köylüler  | Kendi dahil bir kişiyi korur.                           |
| Kâhin   | Köylüler  | Bir oyuncunun rolünü öğrenir.                           |
| Tuzakçı | Köylüler  | İsteğe bağlı rol (bonus). Bu sürümde uygulanmayacaktır. |

Roller oyun başlarken sunucuda rastgele ve gizli dağıtılır.

| Oyuncu sayısı | Vampir | Doktor | Kâhin | Köylü      |
| ------------- | ------ | ------ | ----- | ---------- |
| 5-7           | 1      | 1      | 1     | geri kalan |
| 8-10          | 2      | 1      | 1     | geri kalan |
| 11-12         | 3      | 1      | 1     | geri kalan |

Vampir sayısı en fazla 3'tür. 8 oyuncudan azsa tam 1 vampir bulunur.

#### 1.4.3 Fazlar ve süreler

| Faz    | Süre           | Açıklama                                                                                         |
| ------ | -------------- | ------------------------------------------------------------------------------------------------ |
| Lobi   | Süresiz        | Oyuncular toplanır, oda sahibi başlatır.                                                         |
| Gece   | 60 sn          | Vampir, doktor ve kâhin eylemini seçer. Vampirlerin kurban seçimi gecenin ilk 15 saniyesindedir. |
| Gündüz | 75 sn          | Gece sonuçları açıklanır, oyuncular tartışır.                                                    |
| Oylama | En fazla 30 sn | Herkes oy kullanınca süre dolmadan biter.                                                        |
| Bitiş  | ,              | Kazanan açıklanır.                                                                               |

Gece, gündüz ve oylama kazanan çıkana kadar sırayla tekrarlanır. Süre dolunca fazı sunucu ilerletir. Uygulamada sohbet yoktur.

#### 1.4.4 Gece

- Vampirler kurban oylaması yapar. En çok oyu alan kişi hedef olur. Eşitlikte o gece kimse ölmez.
- Doktor, kendisi dahil bir oyuncuyu korur. Korunan kişi saldırıya uğrasa da ölmez.
- Kâhin bir oyuncunun rolünü öğrenir. Sonucu yalnızca kendisi görür.
- Süre dolana kadar eylem seçilmezse o eylem boş geçer.

#### 1.4.5 Sabah

- Gece sonuçları herkese toplu açıklanır: "Gece X öldü" veya "Gece kimse ölmedi".
- Doktorun birini kurtarıp kurtarmadığı söylenmez.

#### 1.4.6 Oylama

- Her canlı oyuncu bir kez oy kullanır, oyunu değiştiremez.
- Kimse kendine oy veremez.
- Oylamada pas geçilebilir.
- En çok oyu alan oyuncu elenir.
- Eşitlik kuralı: Oylamada eşitlik çıkarsa veya en çok oyu pas alırsa kimse elenmez, oyun geceye geçer.
- Elenen oyuncunun rolü (kâhin dahil) açıklanır.
- Ölü ve elenen oyuncular oy veremez.

#### 1.4.7 Ölü ve elenen oyuncular

- Ölenler oyunu hayalet olarak izleyebilir.
- Hayalet yalnızca herkese açık bilgileri görür: faz, süre, ölenler, açıklanmış roller ve oy sonuçları.
- Hayalet, canlı oyuncuların gizli rollerini göremez.

#### 1.4.8 Kazanma koşulları

Kontrol her gece sonuçlandığında ve her oylamadan sonra yapılır.

- Köylüler kazanır: Tüm vampirler elenince.
- Vampirler kazanır: Canlı vampir sayısı, canlı köylü tarafı oyuncularının (köylü, doktor, kâhin) sayısına eşit olunca.
- Oylamada köylü, doktor ve kâhin köylü tarafında sayılır.

#### 1.4.9 Gizlilik kuralı

- Her oyuncu yalnızca kendi rolünü görür.
- Vampir, yalnızca diğer vampirleri ek olarak görür.
- Sunucu yanıtında bu istisna dışında başkasının rolü bulunmaz.

## 2. Kapsam dışı

- Oyun içi sohbet yoktur. Tartışma uygulama dışında yapılır.
- Bot (bilgisayar) oyuncular yoktur.
- Tuzakçı isteğe bağlı (bonus) roldür, çekirdek oyun tamamlandıktan sonra eklenir.

## 3. Çekirdek problem

### 3.1 Faz durum makinesi

Oyun lobi, gece, gündüz, oylama ve bitiş fazlarından geçer. Her fazın bir süresi vardır ve süre dolunca fazı sunucu ilerletir. Gece 25 saniyedir: ilk 15 saniye seçim, kalan 10 saniye eylemlerin uygulanması içindir. Oylama, herkes oy kullanınca süre dolmadan da biter. İstemciden gelen süre veya faz bilgisine güvenilmez. Aynı faz iki eşzamanlı istekle iki kez ilerletilemez.

### 3.2 Role özel gizli bilgi

Hiçbir oyuncu başkasının rolünü sunucu yanıtından öğrenememelidir. Aynı sunucu adresi her oyuncuya farklı veri döndürür. Vampirler birbirini görmez. Kâhin ek olarak yalnızca sorguladığı oyuncuların rolünü görür. Hayalet yeni gizli bilgi görmez. Sunucu, vampirin başka bir vampiri seçmesini reddetmez, çünkü ret cevabı rolü sızdırır.

### 3.3 Oylama sayımı ve eşitlik kuralı

Oyuncular bir kişiye oy verir veya pas geçer. Oy vermeyen pas sayılır. En çok oyu alan oyuncu elenir. En yüksek oyda eşitlik varsa ya da en çok oyu pas aldıysa kimse elenmez.

### 3.4 Kazanma koşulu denetimi

Her gece çözümünden ve her oylamadan sonra kazanan kontrol edilir. Hayatta vampir kalmazsa köylü takımı, hayattaki vampir sayısı diğer oyuncuların sayısına eşit ya da fazla olursa vampir takımı kazanır.

### 3.5 Gece eylemlerinin sıralı çözümü

Gece eylemleri sunucuda sabit bir sırayla çözülür: tuzakçı, vampir, doktor, kâhin. Tuzağa takılan eylem iptal olur. Sonuçlar sabaha kadar gizli tutulur ve sabah toplu açıklanır.

## 2. Kapsam dışı

- Oyun içi sohbet yoktur. Tartışma oyuncular arasında oyun dışında yapılır.
- Bilgisayar (bot) oyuncular yoktur.
- Ek roller isteğe bağlıdır. Tuzakçı rolü bu sürümde uygulanmayacaktır.

## 3. Çekirdek problem

### 3.1 Faz durum makinesi

Oyun lobi, gece, gündüz, oylama ve bitiş fazlarından geçer. Her fazın bir süresi vardır ve süre dolunca fazı sunucu ilerletir. Tarayıcıdan gelen süre veya faz bilgisine güvenilmez. Oylama fazı, herkes oy kullanınca süre dolmadan da biter. Aynı fazın iki eşzamanlı istekle iki kez ilerletilmemesi gerekir.

### 3.2 Role özel gizli bilgi

Hiçbir oyuncu başkasının rolünü sunucu yanıtından öğrenememelidir. Aynı sunucu adresi her oyuncuya farklı veri döndürür. Vampir yalnızca diğer vampirleri, kâhin ise yalnızca baktığı kişinin rolünü ek olarak görür. Ölü oyuncular (hayaletler) gizli rolleri göremez.

### 3.3 Oylama sayımı ve eşitlik kuralı

Gündüz oyuncular oy verir veya pas geçer. En çok oyu alan oyuncu elenir. Eşit oyda veya en çok oyu pas aldığında kimse elenmez ve oyun geceye geçer. Gece vampir oylamasında eşitlik çıkarsa o gece kimse ölmez.

### 3.4 Kazanma koşulu denetimi

Her gece sonucundan ve her oylamadan sonra kazanan kontrol edilir. Tüm vampirler elenirse köylüler, canlı vampir sayısı canlı köylü tarafının sayısına eşit olursa vampirler kazanır.

## 4. Kullanıcı hikâyeleri

## 5. İşlevsel gereksinimler (FR)
| :--- | :--- | :--- |
| FR-1 | Sistem, kullanıcıların e-posta ve şifreyle kayıt olmasını sağlamalı. | US-1 |
| FR-2 | Sistem, kayıtlı kullanıcıların giriş ve çıkış işlemlerini yapabilmesini sağlamalı. | US-1 |
| FR-3 | Sistem, oyuncuların oda kodunu aratarak veya filtreleyerek mevcut bir odaya katılabilmesini sağlamalı. | US-2 |
| FR-4 | Sistem, odaya en az 5, en fazla 12 oyuncu katıldığında yalnızca oda kurucusunun oyunu başlatabilmesini sağlamalı; bu aralığın dışında başlatmayı reddetmeli. | US-3 |
| FR-5 | Sistem, oyun başlarken oyunculara en az 4 farklı rolü (Köylü, Vampir, Doktor, Kâhin ve isteğe bağlı Tuzakçı) rastgele dağıtabilmeli. | US-4 |
| FR-6 | Sistem, Lobi, Gece, Gündüz, Oylama ve Bitiş fazları arasındaki geçişleri sunucu süresini baz alarak ilerletmeli ve aynı fazı iki kez işletmemeli. | US-5 |
| FR-7 | Sistem, 25 saniyelik Gece fazının ilk 15 saniyesinde oyuncuların aksiyon seçimlerini almalı ve yapılan seçimin değiştirilmesini engellemeli. | US-6 |
| FR-8 | Sistem, gece eylemlerini (Tuzakçı, Vampir, Doktor, Kâhin) ve oyuncuların oylarını istemciye güvenmeden doğrudan sunucu tarafında doğrulamalı ve çözmeli. | US-6 |
| FR-9 | Sistem, her oyuncuya yalnızca kendi rolünü göstermeli; başka bir oyuncunun gizli rolü (vampirlerin kimliği dahil) hiçbir yanıtta yer almamalı. | US-7 |
| FR-10 | Sistem, 75 saniyelik Gündüz fazı başladığında, gece ölen oyuncuları veya "kimse ölmedi" bilgisini sabah duyurusu olarak göstermeli. | US-8 |
| FR-11 | Sistem, 30 saniyelik Oylama fazında yalnızca canlı oyuncuların birbirine oy vermesini veya pas geçmesini sağlamalı; her oyuncu yalnızca bir oy kullanabilmeli. | US-9 |
| FR-12 | Sistem, oylamada eşitlik çıkması veya en çok oyu "pas" seçeneğinin alması durumunda o gün kimseyi elememeli. | US-9 |
| FR-13 | Sistem, oylamayla elenen oyuncunun rolünü herkese açıklarken, kâhin istisnası dışında gece ölen oyuncuların rollerini gizli tutmalı. | US-10 |
| FR-14 | Sistem, her gece çözümünden ve her oylamadan sonra kazanma koşullarını kontrol ederek oyunun bitip bitmediğine karar vermeli. | US-11 |
| FR-15 | Sistem, oyun sona erdiğinde kazanan takımı, tüm oyuncuların rollerini ve olay özetini gösteren bir rapor ekranı sunabilmeli. | US-12 |
| FR-16 | Sistem, giriş yapmış kullanıcıya benzersiz kodlu yeni bir oda (lobi) kurma imkânı vermeli ve kullanıcıyı oda kurucusu yapmalı. | US-13 |
| FR-17 | Sistem, oda kurucusunun vampir ve tuzakçı sayısını oyuncu sayısına göre sınırlı seçeneklerle ayarlamasını sağlamalı. | US-14 |
| FR-18 | Sistem, ölen ve elenen oyuncuyu hayalet yapmalı: oyunu izleyebilir, oy veremez, eylem yapamaz, yeni gizli bilgi görmez. | US-15 |
| FR-19 | Sistem, oyun günlüğüne başlangıcı, faz değişimlerini, gece sonucunu, oylama sonucunu, elemeyi ve bitişi yazmalı; gizli gece eylemlerini oyun bitene kadar göstermemeli. | US-16 |

## 6. İşlevsel olmayan gereksinimler (NFR)

| NFR-1 | 12 oyunculu dolu bir odaya katılmaya çalışan oyuncuya, reddedilme uyarısı en geç 3 saniye içinde ekranda gösterilmelidir. | Dolu odaya katılım isteği atılır, uyarının görünme süresi ölçülür. | US-2 |
| NFR-2 | Oda kodları en az 6 karakter uzunluğunda olmalı ve 1000 oda oluşturulduğunda çakışan kod sayısı 0 olmalıdır. | 1000 kod üretilip tekrar edenler sayılır. | US-13 |
| NFR-3 | Oyuncu sayısı alt sınırın (5) altındayken başlatma isteklerinin %100'ü reddedilmelidir. | 1-4 oyunculu odada başlatma isteği atılır, reddedilme oranı ölçülür. | US-3 |
| NFR-4 | Çekirdek problem için en az 5 PHPUnit testi bulunmalı ve tümü başarıyla geçmelidir. | phpunit çalıştırılır, başarısız test sayısı 0 olmalıdır. | US-11 |
| NFR-5 | Aynı oylamada aynı oyuncudan gelen 2. ve sonraki oy girişimlerinin %100'ü reddedilmelidir; votes tablosunda oyuncu başına oy sayısı 1'i geçmemelidir. | Aynı oyuncudan birden fazla oy isteği atılır, votes tablosu sorgulanır. | US-9 |
| NFR-6 | Aynı faza aynı anda gelen birden fazla ilerletme isteğinden yalnızca 1'i fazı ilerletmelidir; faz geçiş sayısı faz başına 1'i geçmemelidir. | Eş zamanlı 10 istek gönderilir, günlükteki faz geçişleri sayılır. | US-5 |
| NFR-7 | Kâhin dışındaki bir oyuncunun API yanıtlarında başka bir oyuncunun gizli rolü bulunmamalıdır; sızan rol sayısı 0 olmalıdır. | Her rolle giriş yapılıp tüm uç nokta yanıtları taranır.

## 7. Kullanım senaryoları (use-case)
