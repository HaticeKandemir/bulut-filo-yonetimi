# Bulut Filo Yönetimi

> Proje adı bir özel isimdir ve Türkçe yazılır. Bunun dışında **kodun tamamı İngilizcedir.**
> Teknik tanımlayıcılarda ASCII karşılığı kullanılır: `bulut-filo-yonetimi`.

## Proje özeti

A fleet management application that imports vehicle and address data from user-uploaded Excel
files. Addresses are normalised into a standard format via the ChatGPT API, converted to
coordinates via Google Geocoding, persisted, and rendered as routes on a map.

Internship assessment project. Timeline: one sprint (10 working days). Single developer.

## Teknoloji yığını

**Backend:** PHP 8.4, Laravel 13, MySQL 8, Redis (queue + cache), Laravel Horizon
**Frontend:** React 19 + TypeScript + Vite, TanStack Query, TanStack Table, React Router,
@vis.gl/react-google-maps, Tailwind CSS, react-hook-form + zod
**Altyapı:** Docker Compose (php-fpm, nginx, mysql, redis, queue worker)
**Dış servisler:** OpenAI Chat Completions API, Google Geocoding API, Google Routes API

## Adlandırma

| Yer                                  | Değer                     |
| ------------------------------------ | ------------------------- |
| Görünen ad / `APP_NAME`              | Bulut Filo Yönetimi       |
| Repo, Docker Compose, GCP project id | `bulut-filo-yonetimi`     |
| MySQL veritabanı                     | `bulut_filo_yonetimi`     |
| Frontend paket adı                   | `bulut-filo-yonetimi-web` |
| API taban yolu                       | `/api/v1`                 |

## Komutlar

```
docker compose up -d              # tüm servisler
docker compose exec app bash      # container içine gir
php artisan migrate --seed        # şema + dummy veri
php artisan horizon               # kuyruk worker
./vendor/bin/pest                 # testler
./vendor/bin/pint                 # kod stili
./vendor/bin/phpstan analyse      # statik analiz
npm run dev / lint / typecheck    # frontend
```

---

# DİL KURALI — İSTİSNASIZ

**Kod İngilizce, arayüz Türkçe.**

İngilizce olacaklar: sınıf, metod, değişken, dosya ve klasör adları; veritabanı tablo ve kolon
adları; API alan adları; enum değerleri; commit mesajları; branch adları; kod yorumları ve
PHPDoc; log mesajları; exception mesajları; test adları; README ve teknik dokümanlar.

Türkçe olacaklar: yalnızca son kullanıcının ekranda gördüğü metinler ve doğrulama hata mesajları.

Türkçe metin JSX veya Blade içine **gömülmez**. Frontend'de `src/locales/tr.json`, backend'de
`lang/tr/` altında tutulur ve anahtarla çağrılır.

```tsx
// YANLIŞ
<button>Kaydet</button>

// DOĞRU
<button>{t('vehicle.detail.save')}</button>
```

Doğru örnekler: `VehicleImportService`, `vehicle_plates.released_at`, `feat: add plate transfer
resolver`, `enum VehicleStatus { Active, Passive, LeftFleet }`.
Yanlış örnekler: `AracImportServisi`, `arac_plakalari`, `feat: plaka devri eklendi`, `$plakaListesi`.

**Türkçe locale tuzağı:** `strtolower` / `strtoupper` / `ucfirst` locale'e bağımlıdır ve
`I` ↔ `ı` dönüşümünü bozabilir. Plaka normalizasyonu gibi yerlerde daima
`mb_strtoupper($value, 'UTF-8')` kullan.

---

# KOD STANDARTLARI

## Adlandırma sözleşmeleri

- Tablolar: `snake_case`, çoğul — `vehicles`, `vehicle_plates`, `import_batches`
- Kolonlar: `snake_case` — `institution_id`, `released_at`, `distance_meters`
- Modeller: `PascalCase`, tekil — `Vehicle`, `VehiclePlate`
- Metodlar ve değişkenler: `camelCase`
- Sabitler ve enum case'leri: `PascalCase` backed enum içinde
- Boolean alanlar `is_` / `has_` ile başlar — `is_active`, `has_coordinates`
- Interface'ler `-Interface` son ekiyle: `GeocoderInterface`
- React bileşenleri: `PascalCase.tsx`; hook'lar: `useSomething.ts`

## Katmanlar

```
HTTP Controller  →  yalnızca yönlendirme; iş mantığı yok, 20 satırı geçmez
Form Request     →  girdi doğrulama ve yetkilendirme
Service          →  iş mantığı; app/Services altında
Repository       →  veri erişimi (yalnızca sorgu karmaşıksa)
DTO              →  katmanlar arası veri taşıma; readonly class
API Resource     →  çıktı serialize etme; model asla doğrudan dönülmez
Job              →  kuyrukta çalışan uzun işler
```

- Dış servisler **daima interface arkasında** olur: `AddressFormatterInterface`,
  `GeocoderInterface`, `RouteProviderInterface`. Somut sınıflar service provider'da bağlanır.
- Bağımlılıklar constructor injection ile alınır. Facade ve `app()` helper'ı servis
  sınıflarında kullanılmaz.
- Domain hataları için özel exception sınıfları yazılır: `PlateConflictException`.
  Genel `\Exception` fırlatılmaz.

## Tip güvenliği

- Her PHP dosyasında `declare(strict_types=1);`
- Tüm parametre ve dönüş tipleri yazılır. `mixed` gerekçesiz kullanılmaz.
- Dizi yapıları için PHPDoc generic: `/** @return Collection<int, Vehicle> */`
- TypeScript `strict: true`. `any` yasak; bilinmeyen için `unknown` kullanılır.
- API cevap tipleri frontend'de tek yerde tanımlanır: `src/types/api.ts`

## Yorumlar

Yorum **neden**'i açıklar, **ne**'yi değil. Kodun kendisinin anlattığı şeyi tekrar etme.

```php
// YANLIŞ: increment the counter
$processed++;

// DOĞRU: Google Geocoding enforces 50 QPS; batching below that avoids 429 responses.
$chunkSize = 40;
```

Public metodlarda kısa PHPDoc bulunur. Fırlatılabilecek exception'lar `@throws` ile belirtilir.

## Otomatik denetim — CI'da zorunlu

```
./vendor/bin/pint --test          # PSR-12
./vendor/bin/phpstan analyse      # Larastan, minimum level 6
./vendor/bin/pest                 # testler
npm run lint                      # ESLint
npm run typecheck                 # tsc --noEmit
```

Bunlar GitHub Actions'ta her push'ta çalışır. Üçü de yeşil değilse iş tamamlanmış sayılmaz.

## Git

- Conventional commits: `feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`
- Commit mesajı İngilizce, emir kipinde: `feat: add plate transfer resolver`
- Her iş paketi kendi feature branch'inde: `feature/excel-import`
- Tek bir dev commit'i atma; anlamlı parçalara böl.

---

# MİMARİ KURALLAR

- Uzun süren hiçbir iş HTTP isteği içinde çalışmaz. Excel işleme, ChatGPT çağrısı, geocode
  ve rota hesaplama **kuyruk işlerinde** yapılır.
- Dış servis sonuçları adres hash'i ile cache'lenir. Aynı adres iki kez sorgulanmaz.
- Okuma endpoint'leri cache'lenir; ilgili model güncellenince cache tag'i temizlenir.
- `Http::retry()` ile geri çekilmeli yeniden deneme. Rate limit için `Redis::throttle`.
- Migration'lar geriye dönük düzenlenmez; yeni migration yazılır.
- N+1 sorgu bırakılmaz; ilişkiler `with()` ile eager load edilir.

## Alan modeli — önemli

**Aracın kimliği plakası değil, şasi numarasıdır (VIN).** Plaka zamana bağlı bir özelliktir.

```
vehicles        id, vin (unique), brand, model, institution_id, status
vehicle_plates  id, vehicle_id, plate, assigned_at, released_at
```

- `status` bir backed enum'dur: `active`, `passive`, `left_fleet`. Araç asla hard delete edilmez.
- Aktif plaka kaydında `released_at` sentinel değeri `9999-12-31 00:00:00` olur ve
  `UNIQUE(plate, released_at)` kısıtı "aynı anda tek aktif plaka" kuralını garantiler.

**İçe aktarma karar ağacı:**

1. VIN mevcut → aracı güncelle; plaka değiştiyse eski kaydı kapat, yeni kayıt aç;
   araç pasifse yeniden aktifleştir ve logla.
2. VIN yeni, plaka pasif / filodan çıkmış bir araçta → plaka devri; eski kaydı kapat, yeniye ata.
3. VIN yeni, plaka **aktif** bir araçta → çakışma. Satır işlenmez, `needs_review` işaretlenir,
   kullanıcıya çakışan aracın VIN'i gösterilir.
4. VIN yeni, plaka boşta → doğrudan ata.

Sessizce üzerine yazma asla yapılmaz. Belirsizlik varsa satır insana bırakılır.
Bu dört senaryonun her biri için feature test yazılır.

## Kurum hiyerarşisi

Kurumlar ağaç yapısındadır ve **en az 3 seviye** derinlik desteklemelidir. CRUD gerekmez,
sadece okuma. Seeder ile dummy veri eklenir:

```
PTT
├── PTT ANADOLUM
└── PTT E-AVM
    ├── PTTEM
    └── PTT POSTA KARGO
```

Seviye sayısı koda gömülmez; recursive çözülür.

## Google Maps — dikkat

Directions API ve Distance Matrix API 1 Mart 2025'te Legacy oldu ve **yeni Cloud
projelerinde etkinleştirilemiyor**. Bunları önerme.

- Sunucu tarafında rota ve mesafe: Routes API `POST /directions/v2:computeRoutes`
- Tarayıcı tarafında: yeni `Route` sınıfı (`Route.computeRoutes`, `createPolylines`,
  `createWaypointAdvancedMarkers`). `DirectionsService` / `DirectionsRenderer` deprecated.
- Geocoding API legacy değildir, normal kullanılır.

Mesafe ve polyline bir kez hesaplanır ve `routes` tablosuna yazılır; sayfa açılışında
yeniden hesaplanmaz.

## Frontend kuralları

- Tablo sıralama, filtreleme ve sayfalandırma **server-side**'dır.
- Filtre state'i **URL query string'inde** tutulur. Sayfa yenilendiğinde filtreler kaybolmaz.
  Bu açık bir proje gereksinimidir.
- Sunucu state'i TanStack Query ile yönetilir; `useEffect` içinde manuel fetch yapılmaz.
- Hover ile açılan bilgi kutuları mobilde çalışmaz; tıklama ile de açılmalıdır.
- Güzergahlar ekranında çoklu seçim desteklenir; haritada aynı anda birden fazla rota çizilir.

---

# YAPMA

- `.env` dosyasını veya herhangi bir API anahtarını commit'leme.
- Kod içinde Türkçe tanımlayıcı kullanma.
- Kullanıcıya görünen Türkçe metni bileşen içine gömme; çeviri dosyasına koy.
- İstenmediği sürece yeni paket kurma; önce gerekçesini söyle.
- Kurum tablosuna CRUD ekleme; gereksinimde yok.
- Bir görevi tamamlandı ilan etmeden önce ilgili komutu gerçekten çalıştır ve çıktısını göster.
