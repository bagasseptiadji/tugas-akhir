# Dokumentasi Teknis SmartQua

Dokumen ini dibuat dari audit langsung struktur project Laravel SmartQua. Tujuannya supaya alur aplikasi mudah dipahami untuk persiapan sidang TA.

## 1. Struktur Folder Project

Folder penting:

- `app/`
  Berisi kode utama Laravel: Controller, Model, Service, Request validation, Provider, dan Command.
- `routes/`
  Berisi definisi URL aplikasi. `web.php` untuk halaman web, `api.php` untuk endpoint API ESP32.
- `resources/`
  Berisi tampilan Blade, file JavaScript, dan CSS.
- `database/`
  Berisi migration, seeder, dan factory.
- `public/`
  Entry point aplikasi web. File utama: `public/index.php`.
- `config/`
  Berisi konfigurasi aplikasi, database, service Telegram, cache, session, dan lain-lain.
- `tests/`
  Berisi pengujian otomatis, terutama API sensor dan Sanctum.

File halaman penting:

- Dashboard: `resources/views/dashboard.blade.php`
- Histori: `resources/views/history.blade.php`
- Pengaturan/System: `resources/views/settings.blade.php`
- Token API: `resources/views/api-tokens/index.blade.php`
- Dokumentasi API: `resources/views/api-docs/index.blade.php`
- Layout utama/navbar: `resources/views/layouts/app.blade.php`
- Login: `resources/views/auth/login.blade.php`

File backend penting:

- API ESP32: `app/Http/Controllers/Api/SensorReadingController.php`
- Validasi API sensor: `app/Http/Requests/StoreSensorReadingRequest.php`
- Dashboard: `app/Http/Controllers/DashboardController.php`
- Histori: `app/Http/Controllers/HistoryController.php`
- Pengaturan: `app/Http/Controllers/SettingController.php`
- Batas normal sensor: `app/Http/Controllers/ThresholdController.php`
- Telegram: `app/Services/TelegramAlertService.php`
- Model data sensor: `app/Models/SensorReading.php`
- Model perangkat: `app/Models/Device.php`
- Model batas normal: `app/Models/WaterQualityThreshold.php`

## 2. Daftar Route Aplikasi

### Route Web

Semua route web utama ada di `routes/web.php`.

| Method | URL | Nama Route | Controller | Method |
|---|---|---|---|---|
| GET | `/login` | `login` | `Auth\LoginController` | `create` |
| POST | `/login` | `login.store` | `Auth\LoginController` | `store` |
| POST | `/logout` | `logout` | `Auth\LoginController` | `destroy` |
| GET | `/` | `dashboard` | `DashboardController` | `__invoke` |
| GET | `/dashboard/data` | `dashboard.data` | `DashboardController` | `data` |
| GET | `/history` | `history` | `HistoryController` | `__invoke` |
| GET | `/history/data` | `history.data` | `HistoryController` | `data` |
| GET | `/history/chart` | `history.chart` | `HistoryController` | `chart` |
| GET | `/history/export` | `history.export` | `HistoryController` | `export` |
| GET | `/history/export/excel` | `history.export.excel` | `ReportExportController` | `excel` |
| GET | `/history/report` | `history.report` | `ReportExportController` | `report` |
| GET | `/settings` | `settings` | `SettingController` | `__invoke` |
| PUT | `/settings/device` | `settings.device.update` | `SettingController` | `updateDevice` |
| POST | `/settings/telegram/test` | `settings.telegram.test` | `SettingController` | `testTelegram` |
| POST | `/settings/telegram/toggle` | `settings.telegram.toggle` | `SettingController` | `toggleTelegram` |
| PUT | `/thresholds` | `thresholds.update` | `ThresholdController` | `update` |
| GET | `/api-tokens` | `api-tokens.index` | `ApiTokenController` | `index` |
| POST | `/api-tokens` | `api-tokens.store` | `ApiTokenController` | `store` |
| DELETE | `/api-tokens/{token}` | `api-tokens.destroy` | `ApiTokenController` | `destroy` |
| GET | `/api-docs` | `api-docs` | `ApiDocumentationController` | `__invoke` |

### Route API

Route API ada di `routes/api.php`.

| Method | URL | Controller | Method | Auth |
|---|---|---|---|---|
| POST | `/api/sensor` | `Api\SensorReadingController` | `store` | Sanctum Bearer Token |
| GET | `/api/readings` | `Api\SensorReadingController` | `index` | Sanctum Bearer Token |

Endpoint utama alat ESP32 adalah `POST /api/sensor`.

## 3. Controller

### `DashboardController`

Lokasi: `app/Http/Controllers/DashboardController.php`

Fungsi utama: menyiapkan data dashboard realtime.

Method penting:

- `__invoke(Request $request)`
  Mengambil data dashboard melalui `dashboardData()`, lalu mengirim ke `resources/views/dashboard.blade.php`.
- `data(Request $request)`
  Menghasilkan JSON untuk polling AJAX dashboard tanpa reload halaman.
- `dashboardData(Request $request)`
  Mengambil data terbaru, data sebelumnya, data grafik hari ini, summary, dan threshold.
- `periodFromRequest(Request $request)`
  Menentukan range waktu dashboard. Default sekarang adalah hari ini dari jam 00.00 sampai waktu sekarang.
- `readingPayload(SensorReading $reading, ?SensorReading $previous)`
  Membentuk data JSON lengkap untuk card sensor, status perangkat, warning, insight, dan trend.

Data yang dikirim ke view:

- `$latest`: data sensor terbaru
- `$previous`: data sensor sebelumnya
- `$readings`: data grafik
- `$summary`: jumlah perangkat, data periode, alert, status hari ini
- `$period`: rentang waktu grafik
- `$thresholds`: batas normal pH, suhu, TDS

### `HistoryController`

Lokasi: `app/Http/Controllers/HistoryController.php`

Fungsi utama: menampilkan histori pembacaan sensor, grafik histori, DataTables AJAX, dan export CSV.

Method penting:

- `__invoke(Request $request)`
  Membuka halaman histori dan mengirim `$period` serta `$thresholds`.
- `data(Request $request)`
  Endpoint AJAX DataTables. Mengatur pagination, search, sorting, dan mengembalikan row histori.
- `chart(Request $request)`
  Mengambil data grafik histori sesuai rentang waktu.
- `export(Request $request)`
  Export CSV data mentah satu baris per pembacaan sensor.
- `periodFromRequest(Request $request)`
  Membaca filter `from` dan `to`, termasuk jam-menit-detik, lalu dikonversi ke UTC untuk query database.

Data yang dikirim ke view:

- `$period`: dari/sampai waktu lokal
- `$thresholds`: batas normal untuk garis grafik

### `Api\SensorReadingController`

Lokasi: `app/Http/Controllers/Api/SensorReadingController.php`

Fungsi utama: menerima data dari ESP32 dan menyediakan API pembacaan sensor.

Method penting:

- `store(StoreSensorReadingRequest $request)`
  Menerima JSON ESP32, validasi, normalisasi nama field, simpan/update device, hitung status, simpan sensor, lalu trigger Telegram.
- `index(Request $request)`
  Mengambil data sensor terbaru dengan limit maksimal 200.
- `normalizePayload(Request $request)`
  Membuat nama field fleksibel. Contoh: `suhu`, `temperature`, dan `temperature_celsius` sama-sama diterima.
- `readingResponse(SensorReading $reading)`
  Menambahkan `severity`, `status_label`, `warning_details`, `warning_summary`, dan `insight` ke response API.

### `SettingController`

Lokasi: `app/Http/Controllers/SettingController.php`

Fungsi utama: halaman pengaturan sistem.

Method penting:

- `__invoke(TelegramAlertService $telegramAlertService)`
  Menampilkan halaman pengaturan dan mengirim threshold, device, timeout offline, serta status Telegram.
- `updateDevice(Request $request)`
  Menyimpan nama perangkat dan timeout offline.
- `testTelegram(Request $request, TelegramAlertService $telegramAlertService)`
  Mengirim test pesan Telegram.
- `toggleTelegram(Request $request, TelegramAlertService $telegramAlertService)`
  Mengaktifkan atau mematikan notifikasi Telegram.

### `ThresholdController`

Lokasi: `app/Http/Controllers/ThresholdController.php`

Fungsi utama: menyimpan batas normal sensor dan aturan warning.

Method penting:

- `update(Request $request)`
  Validasi batas minimum/maksimum, warning tolerance, dan status aktif alert. Jika valid, update tabel `water_quality_thresholds`.

### `ReportExportController`

Lokasi: `app/Http/Controllers/ReportExportController.php`

Fungsi utama: export laporan histori.

Method penting:

- `excel(Request $request)`
  Menghasilkan file `.xls` dari view `resources/views/exports/history-excel.blade.php`.
- `report(Request $request)`
  Membuka halaman laporan printable dengan grafik dan data.

### `ApiTokenController`

Lokasi: `app/Http/Controllers/ApiTokenController.php`

Fungsi utama: membuat dan mencabut token API Sanctum untuk ESP32.

Method penting:

- `index()`
  Menampilkan daftar token aktif.
- `store(Request $request)`
  Membuat token baru memakai `$request->user()->createToken(...)`.
- `destroy(PersonalAccessToken $token)`
  Mencabut token.

### `LoginController`

Lokasi: `app/Http/Controllers/Auth/LoginController.php`

Fungsi utama: autentikasi admin.

Method penting:

- `create()`: tampilkan login
- `store()`: validasi email/password dan login
- `destroy()`: logout dan hapus sesi

## 4. View / Halaman Tampilan

### Dashboard

File: `resources/views/dashboard.blade.php`

Data yang ditampilkan:

- Card pH, suhu, TDS
- Status perangkat
- Grafik tren kualitas air hari ini
- Rekomendasi tindakan
- Alert terbaru maksimal beberapa baris
- Modal detail alert
- Toast realtime

Komponen penting:

- Chart.js pada canvas `trendChart`
- Polling AJAX ke `/dashboard/data`
- Detail modal untuk melihat status semua parameter

### Histori

File: `resources/views/history.blade.php`

Data yang ditampilkan:

- Filter tanggal dan waktu
- Grafik histori pH/suhu/TDS per tab
- Tabel DataTables server-side
- Tombol export CSV, Excel, PDF/grafik
- Modal detail pembacaan

Komponen penting:

- AJAX DataTables ke `/history/data`
- AJAX grafik ke `/history/chart`
- Export ke route `history.export`, `history.export.excel`, dan `history.report`

### Pengaturan / System

File: `resources/views/settings.blade.php`

Data yang ditampilkan:

- Pengaturan perangkat: nama perangkat dan timeout offline
- Sensor: batas minimum/maksimum pH, suhu, TDS
- Status Alert: aktif/nonaktif alert dan batas warning
- Notifikasi: status Telegram, tombol aktif/nonaktif, test notifikasi

Komponen penting:

- Form AJAX untuk menyimpan pengaturan
- Toast sukses/error
- Toggle Telegram tanpa reload halaman

### Token API

File: `resources/views/api-tokens/index.blade.php`

Data yang ditampilkan:

- Status API
- Jumlah token aktif
- Last request token
- Form buat token
- Daftar token aktif

### Dokumentasi API

File: `resources/views/api-docs/index.blade.php`

Data yang ditampilkan:

- Endpoint `POST /api/sensor`
- Endpoint `GET /api/readings`
- Contoh header
- Contoh payload JSON
- Contoh response
- Contoh kode firmware ESP32

### Layout Utama

File: `resources/views/layouts/app.blade.php`

Isi penting:

- Import asset lokal via `@vite('resources/js/app.js')`
- Navbar SmartQua
- Menu Dashboard, Histori, Sistem
- Dropdown Sistem: Pengaturan, Token API, Dokumentasi API
- Dark mode toggle
- Toast session

## 5. Database

### `sensor_readings`

Fungsi: menyimpan semua data pembacaan sensor dari ESP32.

Kolom penting:

- `id`
- `device_id`
- `ph`
- `temperature_celsius`
- `tds_ppm`
- `quality_status`
- `raw_payload`
- `recorded_at`
- `created_at`, `updated_at`

Relasi:

- `sensor_readings.device_id` menuju `devices.id`
- Satu device punya banyak sensor readings.

Ini tabel utama penyimpan data sensor.

### `devices`

Fungsi: menyimpan identitas ESP32/perangkat.

Kolom penting:

- `id`
- `code`
- `name`
- `api_key`
- `last_seen_at`
- `wifi_rssi`
- `uptime_seconds`
- `firmware_version`
- `sensor_status`
- `power_status`

Catatan: yang aktif dipakai di dashboard/pengaturan saat ini adalah nama perangkat, status online berdasarkan `last_seen_at`, RSSI, uptime, dan timeout offline dari cache.

### `water_quality_thresholds`

Fungsi: menyimpan batas normal dan aturan alert.

Kolom penting:

- `parameter`: `ph`, `temperature_celsius`, `tds_ppm`
- `label`
- `unit`
- `min_value`
- `max_value`
- `alert_enabled`
- `warning_tolerance`
- `critical_delta`
- `description`

Tabel ini menyimpan pengaturan batas normal dan alert.

Catatan: migration lama masih menambahkan `calibration_offset` dan `calibration_multiplier`, tetapi fitur kalibrasi sudah tidak ditampilkan/dipakai di alur saat ini.

### `personal_access_tokens`

Fungsi: menyimpan token Laravel Sanctum untuk API ESP32.

Kolom penting:

- `tokenable_type`
- `tokenable_id`
- `name`
- `token`
- `abilities`
- `last_used_at`
- `expires_at`

### `users`

Fungsi: menyimpan akun admin.

Kolom penting:

- `name`
- `email`
- `password`

### Tabel Laravel internal

- `sessions`: menyimpan session login karena `SESSION_DRIVER=database`.
- `cache`: menyimpan cache, termasuk toggle Telegram dan timeout offline.
- `jobs`, `job_batches`, `failed_jobs`: tabel queue bawaan Laravel.
- `migrations`: mencatat migration yang sudah dijalankan.
- `password_reset_tokens`: token reset password bawaan Laravel.

### Tabel Alert

Tidak ada tabel `alerts` khusus. Alert dihitung dari:

- data sensor di `sensor_readings`
- aturan batas di `water_quality_thresholds`
- method `warningDetails()`, `severity()`, dan `statusLabel()` di `SensorReading`

## 6. Migration dan Model

### `SensorReading`

Lokasi model: `app/Models/SensorReading.php`

Tabel: `sensor_readings`

Migration utama:

- `database/migrations/2026_05_20_150148_create_sensor_readings_table.php`
- `database/migrations/2026_06_15_000001_add_status_recorded_at_index_to_sensor_readings_table.php`

Fillable:

- `device_id`
- `ph`
- `temperature_celsius`
- `tds_ppm`
- `quality_status`
- `raw_payload`
- `recorded_at`

Relasi:

- `device()`: belongsTo `Device`

Method penting:

- `warningDetails()`
- `warningSummary()`
- `severity()`
- `statusLabel()`
- `insight()`
- `recordedAtLocal()`

### `Device`

Lokasi model: `app/Models/Device.php`

Tabel: `devices`

Migration utama:

- `database/migrations/2026_05_20_150148_create_devices_table.php`
- `database/migrations/2026_05_20_162715_add_health_fields_to_devices_table.php`

Fillable:

- `code`
- `name`
- `api_key`
- `last_seen_at`
- `wifi_rssi`
- `uptime_seconds`
- `firmware_version`
- `sensor_status`
- `power_status`

Relasi:

- `readings()`: hasMany `SensorReading`

Method penting:

- `isOnline()`
- `offlineTimeoutMinutes()`
- `uptimeLabel()`
- `wifiLabel()`

### `WaterQualityThreshold`

Lokasi model: `app/Models/WaterQualityThreshold.php`

Tabel: `water_quality_thresholds`

Migration utama:

- `database/migrations/2026_05_20_150148_create_water_quality_thresholds_table.php`
- `database/migrations/2026_06_15_000002_add_alert_rule_fields_to_water_quality_thresholds_table.php`

Fillable:

- `parameter`
- `label`
- `unit`
- `min_value`
- `max_value`
- `alert_enabled`
- `warning_tolerance`
- `critical_delta`
- `description`

Method penting:

- `statusFor(array $values)`
- `warningsFor(array $values)`
- `outsideSeverity(float $gap)`
- `forgetThresholdCache()`

### `User`

Lokasi model: `app/Models/User.php`

Tabel: `users`

Fungsi:

- Login admin web
- Pemilik token API Sanctum

Trait penting:

- `HasApiTokens`
- `HasFactory`
- `Notifiable`

## 7. Alur Data ESP32 ke Website

1. ESP32 membaca sensor pH, suhu, dan TDS.
2. ESP32 membuat JSON, misalnya berisi `device_code`, `ph`, `suhu`, `tds`, `wifi_rssi`, dan `uptime_seconds`.
3. ESP32 mengirim HTTP POST ke `http://IP_SERVER:8000/api/sensor`.
4. Header request membawa `Authorization: Bearer TOKEN_SANCTUM`.
5. Laravel Sanctum mengecek token di tabel `personal_access_tokens`.
6. Request masuk ke `SensorReadingController@store`.
7. Data divalidasi oleh `StoreSensorReadingRequest`.
8. `normalizePayload()` menyamakan field seperti `suhu` menjadi `temperature_celsius`, `tds` menjadi `tds_ppm`.
9. Laravel mencari atau membuat device di tabel `devices`.
10. Laravel update `last_seen_at`, RSSI, uptime, dan data device lain.
11. Laravel menghitung status lewat `WaterQualityThreshold::statusFor()`.
12. Laravel menyimpan data ke tabel `sensor_readings`.
13. `TelegramAlertService::sendForReading()` dipanggil.
14. Jika status normal, Telegram tidak dikirim.
15. Jika warning/bahaya dan Telegram aktif, pesan dikirim ke Telegram.
16. Dashboard mengambil data terbaru lewat polling `/dashboard/data`.
17. Histori mengambil data dari `sensor_readings` lewat `/history/data`.

## 8. API

### POST `/api/sensor`

Fungsi: menerima data sensor dari ESP32.

Header:

```http
Authorization: Bearer TOKEN_SANCTUM
Content-Type: application/json
Accept: application/json
```

Contoh request:

```json
{
  "device_code": "esp32-aquarium-01",
  "device_name": "Akuarium Utama",
  "ph": 7.25,
  "suhu": 27.8,
  "tds": 318,
  "wifi_rssi": -63,
  "uptime_seconds": 268400
}
```

Contoh response sukses:

```json
{
  "success": true,
  "message": "Data sensor berhasil disimpan.",
  "data": {
    "ph": "7.25",
    "temperature_celsius": "27.80",
    "tds_ppm": 318,
    "quality_status": "normal",
    "severity": "normal",
    "status_label": "Normal",
    "warning_details": [],
    "warning_summary": "Semua parameter dalam batas normal."
  }
}
```

Validasi penting:

- `ph` wajib, numeric, 0 sampai 14
- suhu wajib melalui salah satu field: `suhu`, `temperature`, atau `temperature_celsius`
- TDS wajib melalui salah satu field: `tds` atau `tds_ppm`
- TDS 0 sampai 5000
- RSSI -120 sampai 0

### GET `/api/readings`

Fungsi: mengambil data pembacaan sensor terbaru.

Header:

```http
Authorization: Bearer TOKEN_SANCTUM
Accept: application/json
```

Contoh URL:

```http
GET /api/readings?limit=50&device_code=esp32-aquarium-01
```

Contoh response:

```json
{
  "success": true,
  "message": "Data pembacaan sensor berhasil diambil.",
  "data": [],
  "meta": {
    "limit": 50,
    "count": 0
  }
}
```

### Cara Bearer Token Bekerja

1. Admin membuat token di halaman `Token API`.
2. Laravel Sanctum menyimpan hash token di tabel `personal_access_tokens`.
3. Token asli hanya tampil sekali.
4. ESP32 memakai token itu di header `Authorization: Bearer TOKEN`.
5. Middleware `auth:sanctum` memvalidasi token.
6. Jika valid, request boleh masuk controller.
7. Jika tidak valid, Laravel mengembalikan `401 Unauthorized`.

## 9. Fitur Notifikasi Telegram

File utama:

- Service: `app/Services/TelegramAlertService.php`
- Controller tombol pengaturan: `app/Http/Controllers/SettingController.php`
- Config: `config/services.php`
- ENV: `.env`

Konfigurasi `.env`:

```env
TELEGRAM_ALERT_ENABLED=false
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
TELEGRAM_ALERT_COOLDOWN_MINUTES=10
TELEGRAM_CA_BUNDLE=
```

Cara trigger bekerja:

1. Data sensor masuk ke `SensorReadingController@store`.
2. Setelah data tersimpan, controller memanggil `TelegramAlertService::sendForReading($reading)`.
3. Service mengecek `enabled()`.
4. Jika status `normal`, proses berhenti.
5. Jika status `warning` atau `critical`, service membuat `cooldownKey`.
6. Jika cooldown belum aktif, service mengirim pesan ke Telegram API `sendMessage`.

Kapan notifikasi dikirim:

- Telegram aktif.
- Token bot dan chat ID sudah dikonfigurasi.
- Data sensor terbaru tidak normal.
- Cooldown untuk masalah yang sama sudah habis.

Fungsi cooldown:

- Mencegah Telegram mengirim pesan berulang-ulang untuk masalah yang sama.
- Key cooldown dibuat dari device, severity, dan ringkasan warning.
- Default cooldown dari `.env.example` adalah 10 menit.

Tombol test notifikasi:

- Ada di `resources/views/settings.blade.php`.
- Mengirim request AJAX ke `/settings/telegram/test`.
- Controller menjalankan `SettingController@testTelegram`.
- Service menjalankan `TelegramAlertService::sendTest()`.
- Pesan test dikirim tanpa harus menunggu sensor bermasalah.

## 10. Library / Modul yang Digunakan

### Backend Composer

File: `composer.json`

Package utama:

- `laravel/framework` versi `^13.8`
- `laravel/sanctum` untuk Bearer Token API
- `laravel/tinker` untuk command/testing manual

Package development:

- `phpunit/phpunit` untuk testing
- `laravel/pint` untuk formatter
- `fakerphp/faker` untuk data dummy
- `nunomaduro/collision` untuk tampilan error CLI

### Frontend NPM

File: `package.json`

Package utama:

- `bootstrap` untuk layout UI
- `bootstrap-icons` untuk icon
- `chart.js` untuk grafik
- `datatables.net`, `datatables.net-bs5`, `datatables.net-responsive-bs5` untuk tabel histori
- `@fontsource/inter` untuk font Inter lokal
- `@popperjs/core` untuk dropdown Bootstrap
- `vite` dan `laravel-vite-plugin` untuk bundling asset

File utama asset:

- `resources/js/app.js`
- `resources/css/app.css`

Catatan: asset utama dipasang lokal via Vite dan `node_modules`, bukan CDN.

## 11. File Konfigurasi Penting

### `.env`

Fungsi: menyimpan konfigurasi lokal yang tidak masuk git.

Bagian penting:

- `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `SESSION_DRIVER=database`
- `CACHE_STORE=database`
- `TELEGRAM_ALERT_ENABLED`
- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_CHAT_ID`
- `TELEGRAM_ALERT_COOLDOWN_MINUTES`

### `config/database.php`

Mengatur koneksi database. `.env.example` memakai MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smartquadb
DB_USERNAME=root
DB_PASSWORD=
```

### `config/services.php`

Mengatur Telegram:

```php
'telegram_alert' => [
    'enabled' => env('TELEGRAM_ALERT_ENABLED', false),
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'chat_id' => env('TELEGRAM_CHAT_ID'),
    'cooldown_minutes' => env('TELEGRAM_ALERT_COOLDOWN_MINUTES', 10),
    'ca_bundle' => env('TELEGRAM_CA_BUNDLE'),
],
```

### `config/app.php`

Hal penting:

- Timezone internal Laravel: `UTC`
- Timezone tampilan: `APP_DISPLAY_TIMEZONE`, default `Asia/Jakarta`

### `config/project.php`

Menyimpan metadata project:

- judul project
- tanggal mulai project: 15 Juli 2025

### `composer.json`

Mengatur dependency PHP dan script Laravel.

### `package.json`

Mengatur dependency frontend dan script Vite:

- `npm run dev`
- `npm run build`

## 12. Ringkasan Untuk Sidang

### Alur Sistem dalam 10 Langkah

1. Sensor pH, suhu, dan TDS membaca kondisi air akuarium.
2. ESP32 mengubah hasil sensor menjadi JSON.
3. ESP32 mengirim JSON ke `POST /api/sensor`.
4. Request membawa Bearer Token Sanctum.
5. Laravel memvalidasi token dan data sensor.
6. Laravel menyimpan/update data perangkat di tabel `devices`.
7. Laravel menghitung status berdasarkan `water_quality_thresholds`.
8. Laravel menyimpan pembacaan ke tabel `sensor_readings`.
9. Dashboard dan histori mengambil data dari database melalui AJAX.
10. Jika data warning/bahaya dan Telegram aktif, sistem mengirim notifikasi.

### Bagian Kode Paling Penting Dipahami

- `routes/api.php`: pintu masuk API ESP32.
- `app/Http/Controllers/Api/SensorReadingController.php`: proses simpan data sensor.
- `app/Http/Requests/StoreSensorReadingRequest.php`: validasi JSON dari ESP32.
- `app/Models/WaterQualityThreshold.php`: logika status normal/warning/bahaya.
- `app/Models/SensorReading.php`: ringkasan warning, severity, insight.
- `app/Services/TelegramAlertService.php`: notifikasi Telegram.
- `resources/views/dashboard.blade.php`: tampilan realtime monitoring.
- `resources/views/history.blade.php`: histori, grafik, export.
- `resources/views/settings.blade.php`: pengaturan perangkat, sensor, alert, notifikasi.

### Pertanyaan Sidang yang Mungkin Muncul

**Kenapa pakai Laravel?**  
Laravel sudah menyediakan routing, validation, ORM Eloquent, authentication, migration, dan Sanctum sehingga cocok untuk membuat backend API dan dashboard monitoring.

**Data sensor disimpan di mana?**  
Di tabel `sensor_readings`, dengan kolom pH, suhu, TDS, status kualitas, raw payload, dan waktu pembacaan.

**Alert disimpan di tabel apa?**  
Tidak ada tabel alert terpisah. Alert dihitung dari data sensor di `sensor_readings` dan batas normal di `water_quality_thresholds`.

**Bagaimana status Normal, Warning, Bahaya ditentukan?**  
Nilai dibandingkan dengan batas minimum/maksimum. Jika masih di rentang, Normal. Jika keluar batas dengan selisih sampai batas warning, Warning. Jika selisih lebih besar, Bahaya.

**Bagaimana ESP32 mengirim data?**  
ESP32 melakukan HTTP POST ke `/api/sensor`, membawa JSON dan header `Authorization: Bearer TOKEN`.

**Kenapa pakai Bearer Token?**  
Supaya endpoint API tidak bisa diakses sembarang alat. Token dibuat dari Laravel Sanctum dan disimpan di tabel `personal_access_tokens`.

**Apa fungsi raw_payload?**  
Menyimpan JSON asli dari ESP32 untuk audit/debugging jika ada perbedaan data.

**Bagaimana dashboard terasa realtime?**  
Dashboard melakukan polling AJAX ke `/dashboard/data`, sehingga data, grafik, dan status perangkat bisa berubah tanpa reload halaman.

**Kapan perangkat dianggap offline?**  
Jika `last_seen_at` lebih lama dari timeout offline yang diatur di halaman Pengaturan.

**Kenapa Telegram perlu cooldown?**  
Supaya sistem tidak spam notifikasi ketika kondisi sensor masih bermasalah terus-menerus.

**Apakah grafik memakai CDN?**  
Tidak. Chart.js, Bootstrap, Bootstrap Icons, DataTables, dan font Inter dipasang dari `node_modules` dan dibundle lewat Vite.

## 13. Peta Project

```text
routes/web.php -> DashboardController->__invoke -> resources/views/dashboard.blade.php
routes/web.php -> DashboardController@data -> JSON polling dashboard

routes/web.php -> HistoryController->__invoke -> resources/views/history.blade.php
routes/web.php -> HistoryController@data -> DataTables histori -> sensor_readings table
routes/web.php -> HistoryController@chart -> Chart histori -> sensor_readings table
routes/web.php -> HistoryController@export -> Export CSV -> sensor_readings table

routes/web.php -> ReportExportController@excel -> resources/views/exports/history-excel.blade.php
routes/web.php -> ReportExportController@report -> resources/views/reports/history-print.blade.php

routes/web.php -> SettingController->__invoke -> resources/views/settings.blade.php
routes/web.php -> SettingController@updateDevice -> devices table + cache timeout offline
routes/web.php -> SettingController@testTelegram -> TelegramAlertService@sendTest
routes/web.php -> SettingController@toggleTelegram -> cache telegram_alert:enabled

routes/web.php -> ThresholdController@update -> water_quality_thresholds table

routes/web.php -> ApiTokenController@index -> resources/views/api-tokens/index.blade.php
routes/web.php -> ApiTokenController@store -> personal_access_tokens table
routes/web.php -> ApiTokenController@destroy -> personal_access_tokens table

routes/web.php -> ApiDocumentationController->__invoke -> resources/views/api-docs/index.blade.php
routes/web.php -> LoginController@create -> resources/views/auth/login.blade.php
routes/web.php -> LoginController@store -> users table + sessions table
routes/web.php -> LoginController@destroy -> logout session

routes/api.php -> SensorReadingController@store -> StoreSensorReadingRequest -> devices table -> water_quality_thresholds table -> sensor_readings table -> TelegramAlertService
routes/api.php -> SensorReadingController@index -> sensor_readings table

app/Models/SensorReading.php -> warningDetails/statusLabel/insight -> WaterQualityThreshold::warningsFor
app/Models/WaterQualityThreshold.php -> statusFor/warningsFor -> water_quality_thresholds table
app/Models/Device.php -> isOnline/wifiLabel/uptimeLabel -> devices table + cache timeout
```

## Kesimpulan Singkat

SmartQua adalah aplikasi Laravel untuk menerima data sensor ESP32, menyimpan pembacaan kualitas air, menghitung status kualitas air berdasarkan batas normal, menampilkan monitoring realtime di dashboard, menyediakan histori dan export laporan, serta mengirim notifikasi Telegram jika kondisi air warning atau bahaya.
