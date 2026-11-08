# Vampir Köylü – Test Raporu

Tarih: 2026-11-08 · Kapsam: Aşama 3 (MySQL ile çalışan tam ürün)

## 1. Özet

| Depolama | Komut | Sonuç | Süre |
| --- | --- | --- | --- |
| PDO, bellek içi SQLite | `vendor/bin/phpunit` | **158 test, 623 doğrulama, hepsi geçti** | ≈ 37 sn |
| JSON dosyaları (Aşama 2) | `vendor/bin/phpunit -c phpunit.json.xml` | **158 test, 623 doğrulama, hepsi geçti** | ≈ 77 sn |
| PDO, **gerçek MariaDB sunucusu** | `composer test:mysql` | **158 test, 623 doğrulama, hepsi geçti** | ≈ 56 sn |

`composer test` ilk ikisini art arda çalıştırır (T-33). Üçüncüsü gerçek bir veritabanı sunucusu gerektirdiği için ayrı komuttur. JSON süresi uzundur çünkü 1000 odalık benzersiz kod testi (T-29) her oda için dosya yazar.

Aynı 158 test üç farklı depolamada geçtiği için iki repository uygulaması (JSON ve PDO) birbirinin yerine kullanılabilir, MySQL katmanı da gerçek sunucuda doğrulanmıştır.

**Ekibin test planına göre durum** (SRS bölüm 8, 33 senaryo): **33 senaryonun hepsi karşılanıyor.** Ayrıntı bölüm 3'te.

## 2. Test ortamı

| Bileşen | Sürüm |
| --- | --- |
| İşletim sistemi | Windows 11 |
| PHP | 8.3.36 (`pdo_mysql`, `pdo_sqlite`, `mbstring` açık) |
| PHPUnit | 11.5.57 |
| Veritabanı sunucusu | MariaDB 13.0.2 (taşınabilir kurulum, 3307 numaralı port) |

MySQL testleri, her test için geçici bir veritabanı (`vk_test_…`) kurar, **üretimdeki `database/schema.sql` dosyasının kendisini** çalıştırır ve sonunda siler. Uygulama bağlantısı üretimdeki `PdoRepositoryFactory::connectMysql` ile açılır; yani hazırlıklı ifadeler gerçek sunucuda çalışır.

### Nasıl çalıştırılır

```bash
composer test                  # PDO/SQLite + JSON
composer test:mysql            # gerçek MySQL/MariaDB
```

MySQL için ortam değişkenleri (varsayılan değerler parantez içinde): `TEST_MYSQL_HOST` (127.0.0.1), `TEST_MYSQL_PORT` (3306), `TEST_MYSQL_USER` (root), `TEST_MYSQL_PASSWORD` (boş). Kullanıcının veritabanı oluşturma/silme yetkisi olmalıdır. Mevcut veritabanlarına dokunulmaz.

## 3. Ekibin test planına göre karşılama durumu

Ekibin planı (SRS bölüm 8) 33 senaryo tanımlıyor. Planda önerilen test sınıfı/yöntem adları (`AuthServiceTest::testRegisterHashesPassword` vb.) kodda **farklı**: testler `tests/` altında İngilizce yöntem adlarıyla yazıldı; "Karşılayan testler" sütunu gerçek adları verir. Sınıf adları `Test` eklenmiş kısa adlardır (örn. `LobbyServiceTest`).

| Test | Gereksinim | Karşılayan testler | Not |
| --- | --- | --- | --- |
| T-01 | FR-1 | `AuthServiceTest::testRegisterStoresAHashedPassword` | |
| T-02 | FR-1 | `AuthServiceTest::testRegisterRejectsInvalidInput` (aynı e-posta dahil) | |
| T-03 | FR-2 | `AuthServiceTest::testLoginRejectsWrongCredentialsWithTheSameMessage`; `HttpTest::testRegisterLoginAndLogoutFlow`, `testSessionIdIsRegeneratedOnLogin` | `session_regenerate_id` çağrısı oturum sınıfındaki sayaçla doğrulanıyor. |
| T-04 | FR-3 | `LobbyServiceTest::testJoinWithRoomCode`, `testJoinWithUnknownCodeIsRejected`; `HttpTest::testOpenRoomsCanBeListedAndSearchedByCode` | Oda listesi ve kod araması dahil. |
| T-05 | FR-3, NFR-1 | `LobbyServiceTest::testThirteenthPlayerIsRejected`; `HttpTest::testJoiningAFullRoomIsRejectedWithinThreeSeconds` | Süre aynı süreçte ölçülüyor (3 sn altı). |
| T-06 | FR-3 | `LobbyServiceTest::testCannotJoinAfterTheGameHasStarted` | |
| T-07 | FR-4, NFR-3 | `GameStartTest::testCannotStartWithFewerThanFivePlayers` (1, 2, 3, 4 oyuncu), `testCannotStartWithMoreThanTwelvePlayers` | 13 oyuncu, depoya doğrudan eklenerek denenir (lobi zaten izin vermez). |
| T-08 | FR-4 | `GameStartTest::testOnlyTheHostCanStartTheGame`, `testHostCanStartWithinTheLimits` (5 ve 12 oyuncu) | |
| T-09 | FR-5 | `RoleDistributorTest::testRoleCountsFollowTheSettings` (tuzakçılı ve tuzaksız), `testAtLeastFourDifferentRolesAreUsed`, `testTrapperTakesThePlaceOfAVillager`, `testRolesAreShuffledRandomly` | |
| T-10 | FR-6 | `PhaseManagerTest::testPhasesAdvanceInOrderWhenTimeIsUp` | Gece 25, gündüz 75, oylama 30 sn. |
| T-11 | FR-6 | `PhaseManagerTest::testPhaseDoesNotAdvanceBeforeItsTimeIsUp`; `HttpTest::testFragmentEndpointReturnsOnlyTheSectionAndIgnoresClientState` | İstemcinin gönderdiği süre/faz okunmuyor. |
| T-12 | FR-6, NFR-6 | `PhaseManagerTest::testOnlyOneOfTenConcurrentRequestsWinsTheVersionLock`, `testPhaseAdvancesOnlyOnceEvenIfManyRequestsSeeItExpired`; bölüm 5'teki gerçek paralel deney | |
| T-13 | FR-7 | `NightPhaseTest::testActionIsAcceptedOnlyDuringTheFirstFifteenSeconds`, `testChoiceCannotBeChangedAfterItIsMade` | Sınır 14./15. saniye. |
| T-14 | FR-8 | `NightPhaseTest::testVampireCannotTargetThemselves`, `testVampireMayTargetAnotherVampire`, `testNightWithoutActionsKillsNobody`, `testVampiresWithDifferentTargetsKillTwoPlayers`; `NightResolverTest::testVampiresWithDifferentTargetsKillBoth` | |
| T-15 | FR-8 | `NightResolverTest::testDoctorSavesOnlyTheProtectedVictim`, `testDoctorCanProtectThemselves`; `NightPhaseTest::testDoctorCannotProtectThemselvesTwoNightsInARow` | |
| T-16 | FR-8 | `NightPhaseTest::testSeerCannotInspectThemselves`, `testTrapperCannotTrapTheirOwnHouse`; `NightResolverTest::testOnlyTheFirstVisitorIsTrapped` | |
| T-17 | FR-8 | `NightResolverTest::testTrappedDoctorCannotSaveTheVictim`, `testTrappedSeerLearnsNothing`, `testInvalidActionsAreIgnored`; `GameStateTest::testGhostSeesNoSecretsAndCannotAct` | Çözüm sırası: tuzak, vampir ve doktor, kâhin. |
| T-18 | FR-9 | `GameStateTest::testPlayerSeesOnlyTheirOwnRole`, `testVampireDoesNotSeeTheOtherVampires`, `testSeerLearnsTheResultOnlyInTheMorning` | |
| T-19 | FR-9, NFR-7 | `GameStateTest::testNoRoleLeaksDuringAFullGame` (10 oyuncu, 2 vampir, 2 tuzakçı); `HttpTest::testStateEndpointReturnsDifferentDataForEachPlayer` | Sızan rol: 0. |
| T-20 | FR-10 | `NightPhaseTest::testMorningAnnouncesTheVictimWithoutRevealingTheDoctor`, `testMorningSaysNobodyDiedWhenTheDoctorSavesTheTarget`, `testVampiresWithDifferentTargetsKillTwoPlayers` | "Bu gece X öldü." |
| T-21 | FR-11 | `VotingTest::testDeadPlayerCannotVote`, `testCannotVoteForYourself` | Pas kabulü `VoteCounterTest` içinde. |
| T-22 | FR-11, NFR-5 | `VotingTest::testSecondVoteFromTheSamePlayerIsRejected`; `RepositoryTest::testVoteCanBeCastOnlyOncePerVoterAndPhase` | |
| T-23 | FR-11 | `PhaseManagerTest::testVotingEndsEarlyWhenEveryoneHasVoted`; `VotingTest::testNonVotersCountAsPassWhenTimeRunsOut` | |
| T-24 | FR-12 | `VoteCounterTest` (6 test); `VotingTest::testTieEliminatesNobody` | |
| T-25 | FR-13 | `VotingTest::testEliminatedPlayersRoleIsAnnouncedToEveryone`; `NightPhaseTest::testSeerKilledAtNightHasTheirRoleRevealed`, `testNightVictimsOtherThanTheSeerStayHidden` | Kâhin istisnası dahil. |
| T-26 | FR-14, NFR-4 | `WinCheckerTest` (5 test, tuzakçı dahil) | |
| T-27 | FR-14 | `PhaseManagerTest::testGameEndsAfterTheNightWhenVampiresReachTheVillagerCount`, `testGameEndsAfterTheVoteWhenTheLastVampireIsEliminated` | |
| T-28 | FR-15 | `GameStateTest::testReportIsNotAvailableBeforeTheGameEnds`; `HttpTest::testFinishedGameShowsWinnerRolesAndFullReport` | |
| T-29 | FR-16, NFR-2 | `LobbyServiceTest::testThousandRoomsNeverShareACode`, `testCreateRoomGeneratesUniqueCodesAndAddsTheHost` | |
| T-30 | FR-17 | `LobbyServiceTest::testHostCanChangeTheRoleSettings`, `testOnlyTheHostCanChangeTheRoleSettings`, `testInvalidRoleSettingsAreRejected`; `GameStartTest::testChosenRoleCountsAreUsed`; `HttpTest::testHostChangesTheRoleSettingsOverHttp` | |
| T-31 | FR-18 | `GameStateTest::testGhostSeesNoSecretsAndCannotAct`, `testGhostKeepsWhatTheSeerLearnedBeforeDying`; `VotingTest::testDeadPlayerCannotVote` | |
| T-32 | FR-19 | `NightPhaseTest::testMorningSaysNobodyDiedWhenTheDoctorSavesTheTarget` (gizli kayıt); `RepositoryTest::testSecretLogsAreFilteredOut`; `HttpTest::testFinishedGameShowsWinnerRolesAndFullReport` | |
| T-33 | NFR-4 | `composer test` | Bölüm 1. |

Planda olmayan ek testler: CSRF belirteci olmadan POST'un reddi, kullanıcı adındaki HTML'in kaçırılması (XSS), kurucu olmayanın oda silememesi, yönetici sayfalarının yetkisi, oyun geçmişi araması, tuzak bildirimleri (`NightPhaseTest`), ayarların oyuncu sayısı değişince varsayılana dönmesi, 404 sayfası, JSON/PDO repository sözleşmesi, `schema.sql` ile `seed.sql` uyumu.

### Çekirdek problem testleri (NFR-4: en az 5 PHPUnit testi)

| Çekirdek problem | Başlıca testler |
| --- | --- |
| Faz durum makinesi | `PhaseManagerTest` |
| Role özel gizli bilgi | `GameStateTest`, `NightPhaseTest` (kâhin ve tuzak bildirimleri) |
| Oylama sayımı ve eşitlik | `VoteCounterTest`, `VotingTest` |
| Kazanma koşulu denetimi | `WinCheckerTest`, `PhaseManagerTest` |
| Gece eylemlerinin sıralı çözümü | `NightResolverTest`, `NightPhaseTest` |

Hepsi geçti; şart (≥ 5 test, hepsi geçer) karşılanıyor.

## 4. Elle yapılan testler

Elle test, kurallar son güncellenmeden önce yapıldı (JSON depolaması, tek oyunculu): giriş, lobi, oyun başlatma, gece seçimi, kendiliğinden faz geçişleri, oylama, oy dağılımı, biten oyun raporu, yönetici paneli ve telefon görünümü beklendiği gibi çalıştı. Güncellemeden sonra yeni ekranlar (oda listesi, rol ayarı formu, kurallar sayfası, biten oyun raporu) gerçek HTTP istekleriyle kontrol edildi; ayrıca tüm akış otomatik testlerle doğrulanıyor.

## 5. Gerçek MariaDB sunucusuyla doğrulama

| Deney | Sonuç |
| --- | --- |
| `database/schema.sql` içe aktarma (CHECK ve dış anahtar kısıtlarıyla, 5 rol) | Hatasız |
| `database/seed.sql` içe aktarma | Hatasız; 10 kullanıcı, 4 oyun (1'i tuzakçılı), Türkçe karakterler doğru |
| Üretim başlangıç yolu (`config` → `Bootstrap` → MySQL): giriş, oyun listesi, yönetici raporu | Doğru sonuçlar |
| 158 testin tamamı MySQL'e karşı | Geçti |
| **Gerçek paralel eşzamanlılık:** süresi dolmuş bir oyuna 10 ayrı PHP süreci aynı anda `PhaseManager::tick` çağırdı; 3 tur | Her turda **1 süreç fazı ilerletti, 9'u etkisiz kaldı**; oyunda tam 2 faz, sürüm 1 |

Son deney NFR-6'yı kanıtlar: `UPDATE games SET version = version + 1 WHERE id = ? AND version = ?` koşullu güncellemesi gerçek bir sunucuda eşzamanlı isteklerden yalnızca birini geçirir. (Önceki sürümde aynı deney 5 turla yapılmıştı.)

## 6. Testlerin yakaladığı hatalar

| Hata | Nasıl bulundu | Durum |
| --- | --- | --- |
| Oylama sonucu günlüğe yazılırken salt okunur (`readonly`) dizi sıralanmaya çalışılıyordu; her oylama bitişinde faz geçişi hata veriyordu. | İlk tam test çalıştırmasında 10 test başarısız oldu. | Düzeltildi |
| E-posta araması JSON depolamada büyük/küçük harf duyarsız, PDO'da (SQLite) duyarlıydı; iki repository uygulaması aynı davranmıyordu. | `RepositoryTest::testUserSearchByTermAndRole` | Düzeltildi |

## 7. Kapsam dışı kalanlar ve sınırlar

- Sunucu **MariaDB 13.0.2**'dir (MySQL'in uyumlu çatalı). Oracle MySQL ile ayrıca denenmedi; kullanılan SQL standarttır.
- **Kod kapsama oranı ölçülmedi** (Xdebug/PCOV kurulu değil).
- **Yük ve performans testi yapılmadı.** Oyun en fazla 12 oyuncuyla, 3 saniyelik yenilemeyle tasarlandı.
- **Birden çok gerçek kişiyle elle oyun** denenmedi; çok oyunculu akış otomatik testlerle (tam oyun simülasyonu, 3 depolamada) doğrulandı.
- Elle testler tek tarayıcıda (Chromium tabanlı) yapıldı.
- Paralel eşzamanlılık deneyi yalnızca MariaDB için yapıldı; JSON depolamada eşzamanlılık dosya kilidine dayanır ve yalnızca sıralı testle doğrulandı.
- Planda "5 oy isteği" yazan T-22 senaryosu, ikinci oyun reddi (aynı kural) ile doğrulanıyor.
