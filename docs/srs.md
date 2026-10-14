# Vampir Köylü – Gereksinim Belgesi (SRS)

## 1. Giriş

### 1.1 Amaç
Bu belge, Vampir Köylü web oyununun gereksinimlerini tanımlar. Projenin ne yapacağını, nelerin kapsam dışı olduğunu ve teknik olarak zor kısımlarını açıklar.

### 1.2 Proje özeti
Vampir Köylü, 5 ile 12 oyuncunun tarayıcıdan oynadığı bir rol oyunudur. Oyuncular oda koduyla lobiye katılır ve herkese gizlice bir rol verilir. Oyun gece ve gündüz fazlarıyla ilerler. Gece özel roller gizli eylem yapar, gündüz oyuncular oy vererek birini eler. Bir taraf kazanınca oyun biter.

### 1.3 Grup üyeleri

| Ad Soyad | Sorumluluk |
| --- | --- |
| Figen Arslan | Repo, giriş, kapsam dışı, çekirdek problem, birleştirme |
| Melek Efe | Kullanıcı hikâyeleri |
| Zeynep Tunç | İşlevsel gereksinimler |
| Zeynep Çakmak | İşlevsel olmayan gereksinimler, use-case, yapay zekâ günlüğü |


### 1.4 Oyun kuralları

#### 1.4.1 Oda ve oyuncular
- Oyuncu oda açar, sistem oda kodu üretir. Diğer oyuncular bu kodla katılır.
- Odada en az 5, en fazla 12 oyuncu bulunur.
- Oyunu yalnızca oda sahibi başlatır. Oyun başladıktan sonra yeni oyuncu katılamaz.

#### 1.4.2 Roller

| Rol | Taraf | Gece yeteneği |
| --- | --- | --- |
| Köylü | Köylüler | Yok |
| Vampir | Vampirler | Kurban seçer. Vampirler birbirini görür. |
| Doktor | Köylüler | Kendi dahil bir kişiyi korur. |
| Kâhin | Köylüler | Bir oyuncunun rolünü öğrenir. |
| Tuzakçı | Köylüler | İsteğe bağlı rol (bonus). Bu sürümde uygulanmayacaktır. |

Roller oyun başlarken sunucuda rastgele ve gizli dağıtılır.

| Oyuncu sayısı | Vampir | Doktor | Kâhin | Köylü |
| --- | --- | --- | --- | --- |
| 5-7 | 1 | 1 | 1 | geri kalan |
| 8-10 | 2 | 1 | 1 | geri kalan |
| 11-12 | 3 | 1 | 1 | geri kalan |

Vampir sayısı en fazla 3'tür. 8 oyuncudan azsa tam 1 vampir bulunur.

#### 1.4.3 Fazlar ve süreler

| Faz | Süre | Açıklama |
| --- | --- | --- |
| Lobi | Süresiz | Oyuncular toplanır, oda sahibi başlatır. |
| Gece | 60 sn | Vampir, doktor ve kâhin eylemini seçer. Vampirlerin kurban seçimi gecenin ilk 15 saniyesindedir. |
| Gündüz | 75 sn | Gece sonuçları açıklanır, oyuncular tartışır. |
| Oylama | En fazla 30 sn | Herkes oy kullanınca süre dolmadan biter. |
| Bitiş | , | Kazanan açıklanır. |

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

## 6. İşlevsel olmayan gereksinimler (NFR)

## 7. Kullanım senaryoları (use-case)