# Vampir-Koylu

**Vampir Köylü**, 5 ile 12 oyuncunun tarayıcıdan oynadığı gizli rollü bir rol oyunudur (PHP dönem projesi). Oyuncular oda koduyla lobiye katılır, herkese gizlice bir rol (köylü, vampir, doktor, kâhin) verilir. Oyun gece, gündüz ve oylama fazlarıyla ilerler; bir taraf kazanınca biter.

- Gereksinimler ve kurallar: [docs/srs.md](docs/srs.md)
- Mimari, veritabanı ve tasarım kararları: [docs/tasarim.md](docs/tasarim.md); kodda nasıl uygulandığı ve tasarımdan farkları: [docs/uygulama-notlari.md](docs/uygulama-notlari.md)
- Test raporu: [docs/test-raporu.md](docs/test-raporu.md)
- Yapay zekâ kullanım kayıtları: [docs/ai-gunlugu.md](docs/ai-gunlugu.md)

## Özellikler

- Kayıt, giriş, çıkış, şifre değiştirme; oyuncu ve yönetici hesap türleri
- Oda açma, açık odaları listeleme/arama, kodla katılma, oyuncu atma, oyunu kurucunun başlatması ve vampir/tuzakçı sayısını ayarlaması
- Sunucu kontrollü fazlar: gece (25 sn), gündüz (75 sn), oylama (en fazla 30 sn)
- Gizli roller (köylü, vampir, doktor, kâhin, tuzakçı): her oyuncu yalnızca kendi rolünü görür, vampirler birbirini tanımaz
- Gece eylemleri (seçim kilitlenir), tuzak, oylama, eşitlik kuralı, kazanma denetimi, hayalet izleyiciler
- Sayfa yenilenmeden 3 saniyede bir güncellenen oyun ekranı
- Oyun geçmişi (arama/süzme) ve yönetici paneli (özet rapor, kullanıcılar, tüm oyunlar)
- İki depolama: **JSON dosyaları** (Aşama 2) ve **MySQL** (Aşama 3); ikisi de aynı repository arayüzünün arkasındadır

## Gereksinimler

- PHP 8.2 veya üstü (`pdo_mysql` ve `mbstring` eklentileri açık olmalı; XAMPP'te varsayılan olarak açıktır)
- [Composer](https://getcomposer.org/)
- MySQL 8 / MariaDB 10.4 (yalnızca MySQL depolaması için; JSON depolaması veritabanı gerektirmez)

## Kurulum

### 1. Bağımlılıkları yükleyin

```bash
composer install
```

### 2. Depolamayı seçin

Varsayılan depolama JSON'dır; ek ayar gerekmez. Örnek veriyi yükleyin:

```bash
php bin/ornek-veri.php
```

MySQL kullanmak için aşağıdaki "MySQL ile çalıştırma" bölümüne bakın.

### 3. Sunucuyu başlatın

```bash
composer serve
```

Tarayıcıda <http://localhost:8000> adresini açın.

### Örnek hesaplar

| Hesap | E-posta | Şifre |
| --- | --- | --- |
| Yönetici | `admin@vampirkoylu.test` | `Admin1234` |
| Oyuncu | `figen@vampirkoylu.test`, `melek@vampirkoylu.test`, `zeynep.t@vampirkoylu.test`, `zeynep.c@vampirkoylu.test`, `ahmet@vampirkoylu.test`, `selin@vampirkoylu.test`, `burak@vampirkoylu.test`, `derya@vampirkoylu.test`, `mert@vampirkoylu.test` | `Parola123` |

Örnek veride 3 biten oyun (biri tuzakçılı) ve lobide bekleyen 1 oda vardır. Canlı bir oyun denemek için en az 5 oyuncu gerekir: her oyuncu için ayrı bir tarayıcı (ya da gizli pencere) açıp farklı hesapla giriş yapın.

## MySQL ile çalıştırma

1. Veritabanını ve tabloları oluşturun (`vampir_koylu` adlı veritabanını da oluşturur):

   ```bash
   mysql -u root -p < database/schema.sql
   ```

2. Örnek veriyi yükleyin:

   ```bash
   mysql -u root -p < database/seed.sql
   ```

   phpMyAdmin kullanıyorsanız "İçe aktar" sekmesinden önce `schema.sql`, sonra `seed.sql` dosyasını seçin.

3. `config/config.local.example.php` dosyasını `config/config.local.php` adıyla kopyalayıp kullanıcı adı ve şifrenizi yazın:

   ```php
   return [
       'depolama' => 'mysql',
       'mysql' => [
           'sunucu' => '127.0.0.1',
           'port' => 3306,
           'veritabani' => 'vampir_koylu',
           'kullanici' => 'root',
           'sifre' => '',
       ],
   ];
   ```

4. `composer serve` ile başlatın. MySQL'e bağlanılamazsa sayfa "Uygulama başlatılamadı" der; ayrıntı için ayarlara geçici olarak `'uygulama' => ['hata_ayrintisi' => true]` ekleyin.

`database/seed.sql`, `php bin/seed-sql-uret.php` komutuyla yeniden üretilebilir.

## XAMPP (Apache) ile çalıştırma

1. Proje klasörünü `C:\xampp\htdocs\Vampir-Koylu` altına koyun ve klasörde `composer install` çalıştırın.
2. Apache'de `mod_rewrite` açık olmalıdır (XAMPP'te varsayılan olarak açıktır).
3. <http://localhost/Vampir-Koylu/public/> adresini açın. Uygulama alt klasörde çalıştığını kendiliğinden algılar.

## Testler

```bash
composer test
```

Bu komut tüm PHPUnit testlerini iki kez çalıştırır: önce PDO repository'leri (bellek içi SQLite; MySQL şemasının uyarlanmış hâli), sonra JSON repository'leri. Ekibin test planı (T-01 … T-33) SRS bölüm 8'dedir; testlerin bu plana göre karşılama durumu [docs/test-raporu.md](docs/test-raporu.md) içindedir. Yalnızca birini çalıştırmak için:

```bash
vendor/bin/phpunit                       # PDO / SQLite
vendor/bin/phpunit -c phpunit.json.xml   # JSON
```

PDO testleri için PHP'nin `pdo_sqlite` eklentisi gerekir. Bu iki komut MySQL sunucusu gerektirmez.

Aynı testleri **gerçek bir MySQL/MariaDB sunucusuna** karşı çalıştırmak için (her test geçici bir veritabanı kurup siler, mevcut veritabanlarınıza dokunmaz):

```bash
composer test:mysql
```

Bağlantı ortam değişkenleriyle verilir: `TEST_MYSQL_HOST` (varsayılan 127.0.0.1), `TEST_MYSQL_PORT` (3306), `TEST_MYSQL_USER` (root), `TEST_MYSQL_PASSWORD` (boş). Test sonuçları ve gereksinim eşleşmesi: [docs/test-raporu.md](docs/test-raporu.md).

## Ayarlar

`config/config.php` varsayılanları içerir; kendi değişikliklerinizi `config/config.local.php` içine yazın (git'e girmez). Önemli ayarlar:

| Ayar | Anlamı |
| --- | --- |
| `depolama` | `json` ya da `mysql` |
| `sureler` | Faz sürelerini değiştirir (örn. denemek için `['gece' => 10, 'gece_secim' => 6, 'gunduz' => 15, 'oylama' => 10]`) |
| `uygulama.oturum_zaman_asimi` | Hareketsiz oturumun kapanma süresi (saniye) |
| `uygulama.hata_ayrintisi` | `true` ise hata ayrıntısı gösterilir (yalnızca geliştirmede) |

## Ekip

| Ad Soyad | Sorumluluk |
| --- | --- |
| Figen Arslan | Repo, giriş, kapsam dışı, çekirdek problem, birleştirme |
| Melek Efe | Kullanıcı hikâyeleri |
| Zeynep Tunç | İşlevsel gereksinimler |
| Zeynep Çakmak | İşlevsel olmayan gereksinimler, use-case, yapay zekâ günlüğü |