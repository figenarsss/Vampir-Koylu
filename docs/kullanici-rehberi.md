# Vampir Köylü – Kullanıcı Rehberi

Bu rehber oyunu oynayacak kişiler (oyuncu, oda kurucusu) ve yöneticiler içindir. Kurulum için [README](../README.md) dosyasına bakın.

## 1. Oyun nedir?

5 ile 12 kişinin tarayıcıdan oynadığı gizli rollü bir oyundur. Herkese gizlice bir rol verilir. **Köylüler** tüm vampirleri bulup elemeye, **vampirler** ise sayıca köylülere yetişmeye çalışır. Oyun ekranındaki **💬 Sohbet** kartından (gece hariç) diğer oyuncularla yazışabilirsiniz.

## 2. Başlarken

1. Sağ üstteki **Kayıt ol** ile hesap açın (ad, e-posta, en az 8 karakterlik harf ve rakam içeren şifre).
2. **Giriş yap** ile girin. Şifrenizi **Profilim** sayfasından değiştirebilirsiniz.
3. Üstteki menüde: **Kurallar**, **Odalar**, **Oyunlarım**, **Profilim**, **Çıkış** vardır.

## 3. Odaya girmek

| Ne yapmak istiyorsunuz | Nasıl |
| --- | --- |
| Yeni oda açmak | Ana sayfada **Oda oluştur**. 6 karakterlik bir oda kodu alırsınız; arkadaşlarınıza verin. Siz oda kurucusu olursunuz. |
| Kodla katılmak | Ana sayfada **Kodla katıl** kutusuna kodu yazın. |
| Açık odalara bakmak | **Odalar** sayfası: lobideki odalar listelenir, kod ya da kurucu adıyla arayabilirsiniz, **Katıl** ile girersiniz. |
| Odadan çıkmak | Lobide **Odadan ayrıl** (kurucu ayrılamaz; odayı silebilir). |

Oda dolu (12 kişi) ya da oyun başlamışsa katılım reddedilir ve ekranda uyarı görürsünüz.

## 4. Lobi (oda kurucusu için)

- **Oyuncu atma:** listede oyuncunun yanındaki **✖** düğmesi.
- **Rol ayarı:** vampir ve tuzakçı sayısını seçip **Ayarı kaydet**.
  - 5–7 oyuncu: vampir 1, tuzakçı yok.
  - 8–12 oyuncu: vampir 1–3, tuzakçı 0–2.
  - Ayarlamazsanız vampir sayısı 5–7 oyuncuda 1, 8–10 oyuncuda 2, 11–12 oyuncuda 3 olur.
- **Oyunu başlat:** en az 5 oyuncu gerekir. Başlayınca yeni oyuncu katılamaz ve ayar değişmez.
- **Odayı sil:** lobideki ya da biten odayı silersiniz (geri alınamaz).
- **Odadan ayrıl (🚪):** oyun başlamadan odadan çıkabilirsiniz. Kurucu ayrılırsa kurucu yetkisi **en erken katılan** oyuncuya geçer. Odada kimse kalmazsa oda kapanır. Oyun başladıktan sonra ayrılamazsınız. Ayrılan oyuncu kodla yeniden katılabilir.

Diğer oyuncular kurucunun başlatmasını bekler. Ekran kendiliğinden güncellenir, sayfayı yenilemeniz gerekmez.

## 5. Roller

Her oyuncu yalnızca **kendi rolünü** görür. Vampirler birbirini tanımaz.

| Rol | Takım | Gece yeteneği |
| --- | --- | --- |
| 🧑‍🌾 Köylü | Köylüler | Yok. |
| 🧛 Vampir | Vampirler | Bir kurban seçer. Kendini seçemez. |
| 🩺 Doktor | Köylüler | Kendisi dahil birini korur. Art arda iki gece kendini koruyamaz. |
| 🔮 Kâhin | Köylüler | Bir oyuncunun rolünü öğrenir. Kendini sorgulayamaz. |
| 🪤 Tuzakçı | Köylüler | Bir oyuncunun evine tuzak kurar. Kendi evine kuramaz. |

## 6. Oyun akışı

Fazlar sırayla tekrarlanır: **Gece → Gündüz → Oylama → Gece ...** Süreyi ekrandaki sayaçtan izlersiniz; süre dolunca faz kendiliğinden ilerler.

### 🌙 Gece (25 sn)
- Gecenin **ilk 15 saniyesinde** rolünüzün eylemini seçin. Seçim **kaydedilince değiştirilemez**. Süre dolana kadar seçim yapmazsanız eylem boş geçer.
- Köylüsünüzse yapacak bir şey yoktur, sabahı beklersiniz.
- Gece sonunda eylemler sırayla çözülür: önce tuzaklar, sonra vampir saldırısı ve doktor koruması, en son kâhin sorgusu.
  - Vampirler farklı kurbanlar seçerse birden fazla kişi ölebilir.
  - Doktorun koruduğu kişi saldırıdan kurtulur.
  - Tuzak, o eve eylem yapan **ilk** oyuncunun eylemini iptal eder.

### ☀️ Gündüz (75 sn)
- Sabah duyurusunu okursunuz: "Bu gece X öldü." ya da "Bu gece kimse ölmedi." Doktorun kimi kurtardığı söylenmez.
- Kâhin gece ölürse rolü herkese açıklanır. Diğer gece ölenlerin rolü gizli kalır.
- Kâhin sonucunu, tuzağa takılan "tuzağa takıldın", tuzakçı "tuzağına biri takıldı / kimse takılmadı" bildirimini **yalnızca kendisi** görür.
- Tartışma uygulama dışında yapılır.

### 🗳️ Oylama (en fazla 30 sn)
- Canlı oyuncular birine oy verir ya da **Pas geç** der. Kendinize oy veremezsiniz. Oy bir kez verilir, **değiştirilemez**.
- Oy dağılımı canlı görünür (kimin kime oy verdiği görünmez).
- Herkes oy kullanırsa oylama süre dolmadan biter. Oy vermeyenler pas sayılır.
- En çok oyu alan **elenir ve rolü herkese açıklanır**. Eşitlik varsa ya da en çok oyu "pas" aldıysa kimse elenmez.

### 💬 Sohbet
- Sohbet **lobide, gündüz, oylama sırasında ve oyun bittikten sonra** açıktır; **gece kapalıdır** ("Gece sohbet kapalı" uyarısı çıkar).
- Mesaj en fazla **300 karakter** olabilir, boş mesaj gönderilemez. Ekranda son **60 mesaj** görünür.
- Ölen ya da elenen oyuncular sohbeti **okuyabilir, yazamaz**.
- Yalnızca o odadaki oyuncular yazabilir.

### 🏁 Oyun sonu
- **Köylüler**, tüm vampirler elenince kazanır.
- **Vampirler**, canlı vampir sayısı canlı köylü tarafına (köylü, doktor, kâhin, tuzakçı) eşit ya da fazla olunca kazanır.
- Kazanan, tüm roller ve gizli olaylar dahil **olay özeti** gösterilir.

### 🔁 Yeni oyun (lobiye dön)
Oyun bitince **🔁 Lobiye dön** düğmesine basın. **İlk basan** oyuncu yeni lobiyi açar ve kurucusu olur; diğerleri düğmede **(yeni lobi hazır)** yazısını görür ve aynı yeni lobiye katılır. Eski oyun **Oyunlarım** sayfasında sonuçlarıyla birlikte kalır. Yeni lobi başladıysa geç kalan oyuncu katılamaz ("Yeni oyun başlamış").

## 7. Ölünce ne olur?

Ölen ya da elenen oyuncu **hayalet** olur: oyunu izleyebilir, duyuruları ve günlüğü görür; ama oy veremez, eylem yapamaz ve canlı oyuncuların gizli rollerini göremez.

## 8. Oyunlarım

**Oyunlarım** sayfasında oynadığınız oyunlar listelenir. Oda koduna ya da kurucu adına göre arayabilir, duruma göre (Lobi / Sürüyor / Bitti) süzebilirsiniz. Bir oyuna tıklayınca sonuçları ve olay özetini görürsünüz.

## 9. Yönetici ekranları

Yönetici hesabıyla menüde ek sayfalar görünür:

| Sayfa | Ne yapılır |
| --- | --- |
| **Yönetim** | Özet rapor: kullanıcı ve oyun sayıları, takımların kazanma oranı, en çok kazanan oyuncular. |
| **Kullanıcılar** | Ad/e-postaya göre arama, hesap türüne göre süzme; kullanıcıyı yönetici yapma ya da yöneticiliğini alma (kendi yetkinizi değiştiremezsiniz). |
| **Tüm oyunlar** | Tüm odaları arama/süzme ve silme (sürmekte olan oyun dahil). |

## 10. Sık karşılaşılan durumlar

| Durum | Açıklama |
| --- | --- |
| "Oda dolu" / "Bu oyun başlamış" | Oda 12 kişiyse ya da oyun başladıysa katılamazsınız. |
| "Seçim süresi doldu" | Gece eylemi yalnızca ilk 15 saniyede seçilir. |
| "Gece sohbet kapalı" | Sohbet yalnızca gece kapalıdır; gündüz açılınca yazabilirsiniz. |
| "Seçimini yaptın; değiştiremezsin" | Gece eyleminiz ve oyunuz bir kez verilir. |
| "Oturumunuz kapatıldı" | 30 dakika işlem yapılmazsa oturum kapanır, yeniden giriş yapın. |
| Ekran güncellenmiyor | Sayfayı yenileyin; oturumunuz kapanmış olabilir. Ekran normalde 3 saniyede bir kendiliğinden güncellenir. |
| Telefondan oynama | Arayüz telefonda da kullanılabilir. |
