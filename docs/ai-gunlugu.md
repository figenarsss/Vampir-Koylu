# Yapay Zekâ Günlüğü

**Proje:** Vampir Köylü
**Araç:** Claude (Anthropic)

Yapay zekâ yalnızca aşağıdaki konularda yardımcı olarak kullanıldı. Karar ve doğrulama ekibe aittir. `[ ... ]` alanlarını ekip doldurur.

| # | Konu | Yapay zekâdan alınan yardım | Ekibin yaptığı | Durum |
| :-- | :--- | :--- | :--- | :--- |
| 1 | Aynı fazın eşzamanlı isteklerle iki kez ilerlemesi (NFR-6) | Sıralama diyagramında iyimser kilit (`surum`) ve PDO transaction çözümü önerildi | [Kodlama ve 10 eşzamanlı istekle test] | Bekliyor |
| 2 | Rol gizliliği ve sızıntı önleme (FR-9, NFR-7) | Tüm yanıtların tek bir filtre fonksiyonundan geçmesi önerildi | [Uygulama ve her rolle uç nokta taraması] | Bekliyor |
| 3 | SRS içindeki çelişkiler | Belge taranıp çelişkiler listelendi (aşağıda) | [Her madde için ekip kararı] | Karar bekliyor |
| 4 | Projenin kodlanması (Aşama 2-3): oyun motoru, JSON ve PDO/MySQL repository'leri, servisler, arayüz, SQL şeması ve örnek veri | Claude Code ile kod, PHPUnit testleri ve SQL dosyaları üretildi (2026-11-08) | [Kodu okuma, açıklama ve SRS'e uygunluğun doğrulanması] | Bekliyor |
| 5 | MySQL doğrulaması ve test raporu | Testler gerçek bir MariaDB sunucusuna karşı çalıştırıldı; `docs/test-raporu.md` hazırlandı | [Raporun gözden geçirilmesi] | Bekliyor |
| 6 | SRS/tasarım belgeleri ile kodun karşılaştırılması | Ekibin belgeleri koddaki uygulamayla karşılaştırıldı; farklar `YapilmasiGerekenlerAfterClaude.md` içinde listelendi | [Her fark için ekip kararı] | Karar bekliyor |
| 7 | JSON repository'de güvenli yazma (yönerge bölüm 2) | Yapay zekânın ürettiği `JsonTable` `flock` kullanıyordu ama veriyi doğrudan asıl dosyaya yazıyordu; yönergenin istediği "geçici dosyaya yaz, sonra dosyayı değiştir" kuralı eksikti. | Yönerge madde madde kontrol edilirken (Claude yardımıyla) fark edildi. `JsonTable` yeniden yazıldı: ayrı `.lock` dosyası, rastgele adlı geçici dosya, `rename`, bozuk JSON'da hata. 8 test eklendi (`JsonTableTest`); `rename` yerine `copy` kullanılarak test bilerek bozuldu ve testin başarısız olduğu görüldü, sonra geri alındı. Bu, yapay zekânın yetersiz önerisi örneğidir. | Düzeltildi (2026-11-11) |
| 8 | Oylama sonucunun günlüğe yazılması | Üretilen kodda `readonly` bir dizi sıralanmaya çalışılıyordu; her oylama bitişinde faz geçişi hata veriyordu. | İlk tam test çalıştırmasında 10 test başarısız oldu, hata bulunup düzeltildi (`docs/test-raporu.md` bölüm 6). Bu, yapay zekânın yanıldığı ve testlerin yakaladığı örnektir. | Düzeltildi |

## SRS'te bulunan çelişkiler

- Gece süresi: faz tablosunda 60 sn, çekirdek problem ve FR-7'de 25 sn.
- Vampirler birbirini görür mü: kurallar bölümü "görür", FR-9 ve US-7 "görmez".
- Tuzakçı: kapsam dışı bölümünde "yok", FR-5, FR-8 ve US-6'da var.

Güncelleme (2026-11-11): Yukarıdaki üç çelişki SRS'te giderildi. Gece süresi 25 sn olarak tutuldu, vampirlerin birbirini görmediği (FR-9, US-7) ve tuzakçının kapsamda olduğu (FR-5, FR-8, US-6) sürüm bırakıldı; "Kapsam dışı" ve "Çekirdek problem" bölümlerinin eski kopyası silindi. [Bu kararı ekip onaylamalı: ... ]

## Not

Yapay zekânın çıktıları ekip tarafından gözden geçirilmeden kullanılmamıştır: [ekibin nasıl kontrol ettiğini kısaca yazın].
