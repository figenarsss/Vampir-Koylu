# Vampir Köylü – Gereksinim–Test İzlenebilirliği

Bu belge, Aşama 1'deki kullanıcı hikâyelerini (US), işlevsel gereksinimleri (FR) ve işlevsel olmayan gereksinimleri (NFR) **kodda gerçekten bulunan PHPUnit testleriyle** eşleştirir. Gereksinim metinleri ve kabul kriterleri için [srs.md](srs.md), testlerin ayrıntılı sonuçları için [test-raporu.md](test-raporu.md) dosyasına bakın.

- **Test no (T-01 … T-33):** ekibin SRS bölüm 8'deki test planındaki numaralardır. Planda önerilen test adları kodda değişti; bu belgedeki adlar kodda var olan gerçek adlardır (`tests/` klasörü, `Sınıf::yöntem`).
- **Nasıl çalışır:** testler iki kez çalıştırılır: `vendor/bin/phpunit` (PDO, bellek içi SQLite) ve `vendor/bin/phpunit -c phpunit.json.xml` (JSON repository'leri). `composer test` ikisini art arda çalıştırır.
- **Güncel durum (2026-11-11):** 166 test, 643 doğrulama; iki çalıştırmada da hepsi geçti.

## 1. Gereksinim → test eşleşmesi

| Gereksinim | Kullanıcı hikâyesi | Test no | Doğrulayan testler |
| :--- | :--- | :--- | :--- |
| FR-1 | US-1 | T-01, T-02 | T-01: `AuthServiceTest::testRegisterStoresAHashedPassword`<br>T-02: `AuthServiceTest::testRegisterRejectsInvalidInput` (aynı e-posta dahil) |
| FR-2 | US-1 | T-03 | T-03: `AuthServiceTest::testLoginRejectsWrongCredentialsWithTheSameMessage`; `HttpTest::testRegisterLoginAndLogoutFlow`, `testSessionIdIsRegeneratedOnLogin` |
| FR-3 | US-2 | T-04, T-05, T-06 | T-04: `LobbyServiceTest::testJoinWithRoomCode`, `testJoinWithUnknownCodeIsRejected`; `HttpTest::testOpenRoomsCanBeListedAndSearchedByCode`<br>T-05: `LobbyServiceTest::testThirteenthPlayerIsRejected`; `HttpTest::testJoiningAFullRoomIsRejectedWithinThreeSeconds`<br>T-06: `LobbyServiceTest::testCannotJoinAfterTheGameHasStarted` |
| FR-4 | US-3 | T-07, T-08 | T-07: `GameStartTest::testCannotStartWithFewerThanFivePlayers` (1, 2, 3, 4 oyuncu), `testCannotStartWithMoreThanTwelvePlayers`<br>T-08: `GameStartTest::testOnlyTheHostCanStartTheGame`, `testHostCanStartWithinTheLimits` (5 ve 12 oyuncu) |
| FR-5 | US-4 | T-09 | T-09: `RoleDistributorTest::testRoleCountsFollowTheSettings` (tuzakçılı ve tuzaksız), `testAtLeastFourDifferentRolesAreUsed`, `testTrapperTakesThePlaceOfAVillager`, `testRolesAreShuffledRandomly` |
| FR-6 | US-5 | T-10, T-11, T-12 | T-10: `PhaseManagerTest::testPhasesAdvanceInOrderWhenTimeIsUp`<br>T-11: `PhaseManagerTest::testPhaseDoesNotAdvanceBeforeItsTimeIsUp`; `HttpTest::testFragmentEndpointReturnsOnlyTheSectionAndIgnoresClientState`<br>T-12: `PhaseManagerTest::testOnlyOneOfTenConcurrentRequestsWinsTheVersionLock`, `testPhaseAdvancesOnlyOnceEvenIfManyRequestsSeeItExpired`; [test-raporu.md](test-raporu.md) bölüm 5'teki gerçek paralel deney |
| FR-7 | US-6 | T-13 | T-13: `NightPhaseTest::testActionIsAcceptedOnlyDuringTheFirstFifteenSeconds`, `testChoiceCannotBeChangedAfterItIsMade` |
| FR-8 | US-6 | T-14, T-15, T-16, T-17 | T-14: `NightPhaseTest::testVampireCannotTargetThemselves`, `testVampireMayTargetAnotherVampire`, `testNightWithoutActionsKillsNobody`, `testVampiresWithDifferentTargetsKillTwoPlayers`; `NightResolverTest::testVampiresWithDifferentTargetsKillBoth`<br>T-15: `NightResolverTest::testDoctorSavesOnlyTheProtectedVictim`, `testDoctorCanProtectThemselves`; `NightPhaseTest::testDoctorCannotProtectThemselvesTwoNightsInARow`<br>T-16: `NightPhaseTest::testSeerCannotInspectThemselves`, `testTrapperCannotTrapTheirOwnHouse`; `NightResolverTest::testOnlyTheFirstVisitorIsTrapped`<br>T-17: `NightResolverTest::testTrappedDoctorCannotSaveTheVictim`, `testTrappedSeerLearnsNothing`, `testInvalidActionsAreIgnored`; `GameStateTest::testGhostSeesNoSecretsAndCannotAct` |
| FR-9 | US-7 | T-18, T-19 | T-18: `GameStateTest::testPlayerSeesOnlyTheirOwnRole`, `testVampireDoesNotSeeTheOtherVampires`, `testSeerLearnsTheResultOnlyInTheMorning`<br>T-19: `GameStateTest::testNoRoleLeaksDuringAFullGame` (10 oyuncu, 2 vampir, 2 tuzakçı); `HttpTest::testStateEndpointReturnsDifferentDataForEachPlayer` |
| FR-10 | US-8 | T-20 | T-20: `NightPhaseTest::testMorningAnnouncesTheVictimWithoutRevealingTheDoctor`, `testMorningSaysNobodyDiedWhenTheDoctorSavesTheTarget`, `testVampiresWithDifferentTargetsKillTwoPlayers` |
| FR-11 | US-9 | T-21, T-22, T-23 | T-21: `VotingTest::testDeadPlayerCannotVote`, `testCannotVoteForYourself`<br>T-22: `VotingTest::testSecondVoteFromTheSamePlayerIsRejected`; `RepositoryTest::testVoteCanBeCastOnlyOncePerVoterAndPhase`<br>T-23: `PhaseManagerTest::testVotingEndsEarlyWhenEveryoneHasVoted`; `VotingTest::testNonVotersCountAsPassWhenTimeRunsOut` |
| FR-12 | US-9 | T-24 | T-24: `VoteCounterTest` (6 test); `VotingTest::testTieEliminatesNobody` |
| FR-13 | US-10 | T-25 | T-25: `VotingTest::testEliminatedPlayersRoleIsAnnouncedToEveryone`; `NightPhaseTest::testSeerKilledAtNightHasTheirRoleRevealed`, `testNightVictimsOtherThanTheSeerStayHidden` |
| FR-14 | US-11 | T-26, T-27 | T-26: `WinCheckerTest` (5 test, tuzakçı dahil)<br>T-27: `PhaseManagerTest::testGameEndsAfterTheNightWhenVampiresReachTheVillagerCount`, `testGameEndsAfterTheVoteWhenTheLastVampireIsEliminated` |
| FR-15 | US-12 | T-28 | T-28: `GameStateTest::testReportIsNotAvailableBeforeTheGameEnds`; `HttpTest::testFinishedGameShowsWinnerRolesAndFullReport` |
| FR-16 | US-13 | T-29 | T-29: `LobbyServiceTest::testThousandRoomsNeverShareACode`, `testCreateRoomGeneratesUniqueCodesAndAddsTheHost` |
| FR-17 | US-14 | T-30 | T-30: `LobbyServiceTest::testHostCanChangeTheRoleSettings`, `testOnlyTheHostCanChangeTheRoleSettings`, `testInvalidRoleSettingsAreRejected`; `GameStartTest::testChosenRoleCountsAreUsed`; `HttpTest::testHostChangesTheRoleSettingsOverHttp` |
| FR-18 | US-15 | T-31 | T-31: `GameStateTest::testGhostSeesNoSecretsAndCannotAct`, `testGhostKeepsWhatTheSeerLearnedBeforeDying`; `VotingTest::testDeadPlayerCannotVote` |
| FR-19 | US-16 | T-32 | T-32: `NightPhaseTest::testMorningSaysNobodyDiedWhenTheDoctorSavesTheTarget` (gizli kayıt); `RepositoryTest::testSecretLogsAreFilteredOut`; `HttpTest::testFinishedGameShowsWinnerRolesAndFullReport` |
| NFR-1 | US-2 | T-05 | T-05: `LobbyServiceTest::testThirteenthPlayerIsRejected`; `HttpTest::testJoiningAFullRoomIsRejectedWithinThreeSeconds` |
| NFR-2 | US-13 | T-29 | T-29: `LobbyServiceTest::testThousandRoomsNeverShareACode`, `testCreateRoomGeneratesUniqueCodesAndAddsTheHost` |
| NFR-3 | US-3 | T-07 | T-07: `GameStartTest::testCannotStartWithFewerThanFivePlayers` (1, 2, 3, 4 oyuncu), `testCannotStartWithMoreThanTwelvePlayers` |
| NFR-4 | US-11 | T-26, T-33 | T-26: `WinCheckerTest` (5 test, tuzakçı dahil)<br>T-33: `composer test` |
| NFR-5 | US-9 | T-22 | T-22: `VotingTest::testSecondVoteFromTheSamePlayerIsRejected`; `RepositoryTest::testVoteCanBeCastOnlyOncePerVoterAndPhase` |
| NFR-6 | US-5 | T-12 | T-12: `PhaseManagerTest::testOnlyOneOfTenConcurrentRequestsWinsTheVersionLock`, `testPhaseAdvancesOnlyOnceEvenIfManyRequestsSeeItExpired`; [test-raporu.md](test-raporu.md) bölüm 5'teki gerçek paralel deney |
| NFR-7 | US-7 | T-19 | T-19: `GameStateTest::testNoRoleLeaksDuringAFullGame` (10 oyuncu, 2 vampir, 2 tuzakçı); `HttpTest::testStateEndpointReturnsDifferentDataForEachPlayer` |

## 2. Kullanıcı hikâyesi → gereksinim → test

| Kullanıcı hikâyesi | Gereksinimler | Test no |
| :--- | :--- | :--- |
| US-1 | FR-1, FR-2 | T-01, T-02, T-03 |
| US-2 | FR-3, NFR-1 | T-04, T-05, T-06 |
| US-3 | FR-4, NFR-3 | T-07, T-08 |
| US-4 | FR-5 | T-09 |
| US-5 | FR-6, NFR-6 | T-10, T-11, T-12 |
| US-6 | FR-7, FR-8 | T-13, T-14, T-15, T-16, T-17 |
| US-7 | FR-9, NFR-7 | T-18, T-19 |
| US-8 | FR-10 | T-20 |
| US-9 | FR-11, FR-12, NFR-5 | T-21, T-22, T-23, T-24 |
| US-10 | FR-13 | T-25 |
| US-11 | FR-14, NFR-4 | T-26, T-27, T-33 |
| US-12 | FR-15 | T-28 |
| US-13 | FR-16, NFR-2 | T-29 |
| US-14 | FR-17 | T-30 |
| US-15 | FR-18 | T-31 |
| US-16 | FR-19 | T-32 |

Planda bir gereksinimi olmayan ama proje kartı ve ödev yönergesi nedeniyle yazılmış testler bölüm 4'tedir.

## 3. Çekirdek problem testleri

Projenin çekirdek problemi: sunucu kontrollü faz durum makinesi, role özel gizli bilgi, oylama sayımı ve eşitlik kuralı, kazanma koşulu denetimi ve gece eylemlerinin sıralı çözümü (SRS bölüm 3).

| Çekirdek problem | SRS | Başlıca testler | Test sayısı |
| :--- | :--- | :--- | :--- |
| Faz durum makinesi, eşzamanlı ilerletme | 3.1 | `PhaseManagerTest` | 9 |
| Role özel gizli bilgi | 3.2 | `GameStateTest`, `NightPhaseTest` (kâhin ve tuzak bildirimleri) | 12 + 18 |
| Oylama sayımı ve eşitlik | 3.3 | `VoteCounterTest`, `VotingTest` | 6 + 10 |
| Kazanma koşulu denetimi | 3.4 | `WinCheckerTest`, `PhaseManagerTest` | 5 + 9 |
| Gece eylemlerinin sıralı çözümü | 3.5 | `NightResolverTest`, `NightPhaseTest` | 15 + 18 |

(Sayılar `tests/` altındaki `function test…` yöntemlerinin sayısıdır; `NightPhaseTest` ve `PhaseManagerTest` birden fazla çekirdek problemi ilgilendirdiği için tekrar edilir.)

## 4. Ödev yönergesinden doğan ek testler

Bu testlerin SRS'te ayrı bir gereksinimi yoktur; yönergenin "kullanıcıya gösterilen davranış ve güvenlik" ile "JSON ile veri saklama" maddelerini doğrularlar.

| Yönerge maddesi | Test |
| :--- | :--- |
| Giriş yapmamış kullanıcı korumalı sayfaya erişemez | `HttpTest::testGuestsAreRedirectedToLogin` |
| CSRF belirteci olmayan POST reddedilir | `HttpTest::testPostWithoutCsrfTokenIsRejected` |
| Formda hata olunca anlaşılır mesaj gösterilir | `HttpTest::testRegisterShowsValidationErrorsInTheForm` |
| Başka kullanıcının oyununa id ile erişilemez (sahiplik) | `HttpTest::testOutsiderCannotOpenTheGame` |
| Yalnızca kurucu odayı silebilir (rol/sahiplik) | `HttpTest::testOnlyTheHostCanDeleteARoomOverHttp` |
| Yönetici sayfaları yalnızca yöneticiye açık (rol) | `HttpTest::testAdminPagesRequireTheAdminRole` |
| Kullanıcı verisi HTML'de kaçırılır (XSS) | `HttpTest::testPlayerNamesAreEscaped` |
| Kısmi yenileme uç noktası istemciden gelen süre/faz bilgisini yok sayar | `HttpTest::testFragmentEndpointReturnsOnlyTheSectionAndIgnoresClientState`, `testGamePageEmbedsTheRefreshScript` |
| Her oyuncuya aynı adresten farklı veri döner | `HttpTest::testStateEndpointReturnsDifferentDataForEachPlayer` |
| Bilinmeyen sayfa sade 404 verir | `HttpTest::testUnknownPageGives404` |
| Başlangıç verisi (seed) yüklenir ve girişler çalışır | `SeedFileTest::testSeedLoadsIntoTheSchemaAndLoginsWork`, `testSeedContainsFinishedGamesAndAWaitingRoom` |
| JSON ve PDO repository'leri aynı sözleşmeyi uygular | `RepositoryTest` (10 test; iki çalıştırmada da geçer) |
| JSON verisi kalıcıdır ve yeniden açılınca korunur | `JsonTableTest::testInsertedRowsSurviveANewTableInstance`, `testUpdateAndDeleteArePersisted` |
| JSON yazma: geçici dosyaya yazıp `rename` ile değiştirme | `JsonTableTest::testWriteLeavesNoTemporaryFilesBehind`, `testFileIsReplacedAtomicallyInsteadOfBeingRewrittenInPlace` |
| JSON yazma: `flock` ayrı kilit dosyasında tutulur | `JsonTableTest::testLockFileIsSeparateFromTheDataFile` |
| JSON yazma: yarıda kesilen işlem veriyi bozmaz | `JsonTableTest::testFailingChangeLeavesTheStoredDataUntouched` |
| JSON okuma: bozuk dosya sessizce ezilmez, hata verilir | `JsonTableTest::testCorruptFileIsReportedAndNotSilentlyOverwritten` |
| JSON okuma: olmayan veya boş dosya boş tablo sayılır | `JsonTableTest::testMissingOrEmptyFileIsTreatedAsAnEmptyTable` |

## 5. Önemli sınır durumları

| Sınır | Test |
| :--- | :--- |
| Başlatma: 4 oyuncu reddedilir, 5 ve 12 oyuncu kabul edilir, 13. oyuncu reddedilir | `GameStartTest::testCannotStartWithFewerThanFivePlayers`, `testHostCanStartWithinTheLimits`, `testCannotStartWithMoreThanTwelvePlayers` |
| Gece seçimi: 14. saniyede kabul, 15. saniyeden sonra red | `NightPhaseTest::testActionIsAcceptedOnlyDuringTheFirstFifteenSeconds` |
| Oylama: en çok oyu "pas" alırsa veya eşitlik olursa kimse elenmez | `VoteCounterTest::testNobodyIsEliminatedWhenPassHasTheMostVotes`, `testPassTyingWithTheLeaderMeansNobodyIsEliminated`, `testTieMeansNobodyIsEliminated` |
| Aynı faz, 10 eşzamanlı istekte yalnızca bir kez ilerler | `PhaseManagerTest::testOnlyOneOfTenConcurrentRequestsWinsTheVersionLock` |
| 1000 odada kod çakışması yok | `LobbyServiceTest::testThousandRoomsNeverShareACode` |

Testler gerçek saate ve rastgeleliğe bağlı değildir: süre `FakeClock` ile, rastgelelik tohumlu `Randomizer` (Mt19937) ile denetlenir (`tests/Support/TestApp.php`, `src/Support/FakeClock.php`).

## 6. Testlerin gerçekten ölçtüğünün kontrolü (kural bozma denemesi)

Önemli bir kural geçici olarak bozuldu; ilgili testin başarısız olduğu görüldü, sonra kural geri alındı (2026-11-11, `vendor/bin/phpunit`).

| # | Bozulan kural | Değişiklik | Başarısız olan testler |
| :--- | :--- | :--- | :--- |
| 1 | Oylamada eşitlik olursa kimse elenmez (`src/Game/VoteCounter.php`) | `return count($leaders) === 1 ? $leaders[0] : null;` satırı `return $leaders[0];` yapıldı | `VoteCounterTest::testTieMeansNobodyIsEliminated` ("Failed asserting that 3 is null") |
| 2 | En çok oyu pas alırsa kimse elenmez (`VoteCounter.php`) | `if ($passCount >= $highest)` koşulu etkisiz hâle getirildi | `VoteCounterTest` içinde 3 test ve `VotingTest::testNonVotersCountAsPassWhenTimeRunsOut` (toplam 4) |
| 3 | JSON yazma asıl dosyayı `rename` ile değiştirir (`src/Repositories/Json/JsonTable.php`) | `rename` yerine `copy` + `unlink` kullanıldı | `JsonTableTest::testFileIsReplacedAtomicallyInsteadOfBeingRewrittenInPlace` |

Üç denemede de değişiklik geri alındı ve testler yeniden geçti. Sunumda aynı deneme canlı gösterilebilir: bir kuralı bozun, `vendor/bin/phpunit tests/Game/VoteCounterTest.php` çalıştırın, hatayı gösterip geri alın.

