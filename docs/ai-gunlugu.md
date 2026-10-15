# Yapay Zekâ Günlüğü

**Proje:** Vampir Köylü
**Araç:** Claude (Anthropic)

Yapay zekâ yalnızca aşağıdaki konularda yardımcı olarak kullanıldı. Karar ve doğrulama ekibe aittir. `[ ... ]` alanlarını ekip doldurur.

| # | Konu | Yapay zekâdan alınan yardım | Ekibin yaptığı | Durum |
| :-- | :--- | :--- | :--- | :--- |
| 1 | Aynı fazın eşzamanlı isteklerle iki kez ilerlemesi (NFR-6) | Sıralama diyagramında iyimser kilit (`surum`) ve PDO transaction çözümü önerildi | [Kodlama ve 10 eşzamanlı istekle test] | Bekliyor |
| 2 | Rol gizliliği ve sızıntı önleme (FR-9, NFR-7) | Tüm yanıtların tek bir filtre fonksiyonundan geçmesi önerildi | [Uygulama ve her rolle uç nokta taraması] | Bekliyor |
| 3 | SRS içindeki çelişkiler | Belge taranıp çelişkiler listelendi (aşağıda) | [Her madde için ekip kararı] | Karar bekliyor |

## SRS'te bulunan çelişkiler

- Gece süresi: faz tablosunda 60 sn, çekirdek problem ve FR-7'de 25 sn.
- Vampirler birbirini görür mü: kurallar bölümü "görür", FR-9 ve US-7 "görmez".
- Tuzakçı: kapsam dışı bölümünde "yok", FR-5, FR-8 ve US-6'da var.

## Not

Yapay zekânın çıktıları ekip tarafından gözden geçirilmeden kullanılmamıştır: [ekibin nasıl kontrol ettiğini kısaca yazın].
