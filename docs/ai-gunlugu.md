# Yapay Zekâ Günlüğü

**Proje:** Vampir Köylü
**Araç:** Claude (Anthropic)

Yapay zekâ yalnızca aşağıdaki konularda yardımcı olarak kullanıldı. Karar ve doğrulama ekibe aittir. `[ ... ]` alanlarını ekip doldurur.

| # | Konu | Yapay zekâdan alınan yardım | Ekibin yaptığı | Durum |
| :-- | :--- | :--- | :--- | :--- |
| 1 | Aynı fazın eşzamanlı isteklerle iki kez ilerlemesi (NFR-6) | Sıralama diyagramında iyimser kilit (`surum`) ve PDO transaction çözümü önerildi | Sürüm alanlı koşullu güncelleme kodlandı; aynı faza 10 eşzamanlı istekte yalnızca birinin ilerlediği `PhaseManagerTest` ile doğrulandı | Tamamlandı |
| 2 | Rol gizliliği ve sızıntı önleme (FR-9, NFR-7) | Tüm yanıtların tek bir filtre fonksiyonundan geçmesi önerildi | Rol filtresi tek yerde (`GameStateBuilder`) uygulandı; her rolle durum ve parça uç noktaları `GameStateTest` ve `HttpTest` ile tarandı, sızıntı yok | Tamamlandı |
| 3 | SRS içindeki çelişkiler | Belge taranıp çelişkiler listelendi (aşağıda) | Ekip kararı: FR, US ve test planı esas alındı (gece 25 sn, vampirler birbirini görmez, tuzakçı kapsamda); eski kopya bölümler silindi (aşağıdaki güncelleme) | Tamamlandı |
| 4 | Projenin kodlanması (Aşama 2-3): oyun motoru, JSON ve PDO/MySQL repository'leri, servisler, arayüz, SQL şeması ve örnek veri | Claude Code ile kod, PHPUnit testleri ve SQL dosyaları üretildi (2026-11-08) | [Kodu okuma, açıklama ve SRS'e uygunluğun doğrulanması] | Bekliyor |
| 5 | MySQL doğrulaması ve test raporu | Testler gerçek bir MariaDB sunucusuna karşı çalıştırıldı; `docs/test-raporu.md` hazırlandı | [Raporun gözden geçirilmesi] | Bekliyor |
| 6 | SRS/tasarım belgeleri ile kodun karşılaştırılması | Ekibin belgeleri koddaki uygulamayla karşılaştırıldı; farklar `YapilmasiGerekenlerAfterClaude.md` içinde listelendi | [Her fark için ekip kararı] | Karar bekliyor |
| 7 | JSON repository'de güvenli yazma (yönerge bölüm 2) | Yapay zekânın ürettiği `JsonTable` `flock` kullanıyordu ama veriyi doğrudan asıl dosyaya yazıyordu; yönergenin istediği "geçici dosyaya yaz, sonra dosyayı değiştir" kuralı eksikti. | Yönerge madde madde kontrol edilirken (Claude yardımıyla) fark edildi. `JsonTable` yeniden yazıldı: ayrı `.lock` dosyası, rastgele adlı geçici dosya, `rename`, bozuk JSON'da hata. 8 test eklendi (`JsonTableTest`); `rename` yerine `copy` kullanılarak test bilerek bozuldu ve testin başarısız olduğu görüldü, sonra geri alındı. Bu, yapay zekânın yetersiz önerisi örneğidir. | Düzeltildi (2026-11-11) |
| 8 | Oylama sonucunun günlüğe yazılması | Üretilen kodda `readonly` bir dizi sıralanmaya çalışılıyordu; her oylama bitişinde faz geçişi hata veriyordu. | İlk tam test çalıştırmasında 10 test başarısız oldu, hata bulunup düzeltildi (`docs/test-raporu.md` bölüm 6). Bu, yapay zekânın yanıldığı ve testlerin yakaladığı örnektir. | Düzeltildi |
| 9 | Sohbet, lobiden ayrılma ve yeni lobi için birim testleri (FR-20, FR-21, FR-22) | Claude `ChatServiceTest` ve `LobbyLeaveRematchTest` taslaklarını yazdı; kurallar bilerek bozularak testlerin hata verdiği denendi | Ekip testleri kendi bilgisayarında çalıştırdı (193 test geçti), testleri okuyup kuralları anladı ve pull request #10 ile ekledi | Tamamlandı |

## SRS'te bulunan çelişkiler

- Gece süresi: faz tablosunda 60 sn, çekirdek problem ve FR-7'de 25 sn.
- Vampirler birbirini görür mü: kurallar bölümü "görür", FR-9 ve US-7 "görmez".
- Tuzakçı: kapsam dışı bölümünde "yok", FR-5, FR-8 ve US-6'da var.

Güncelleme (2026-11-11): Yukarıdaki üç çelişki SRS'te giderildi. Gece süresi 25 sn olarak tutuldu, vampirlerin birbirini görmediği (FR-9, US-7) ve tuzakçının kapsamda olduğu (FR-5, FR-8, US-6) sürüm bırakıldı; "Kapsam dışı" ve "Çekirdek problem" bölümlerinin eski kopyası silindi. [Bu kararı ekip onaylamalı: ... ]

## Not

Yapay zekânın çıktıları ekip tarafından gözden geçirilmeden kullanılmamıştır: [ekibin nasıl kontrol ettiğini kısaca yazın].
