# Latihan Tanya Jawab Sidang TA — SmartQua

Semua jawaban disusun dari kode yang benar-benar ada di project ini. Angka dan nama file sudah dicocokkan dengan kode.
Bagian **"Jawab jujur"** ditandai untuk kelemahan yang mungkin ditanyakan penguji. Lebih baik diakui dan dijelaskan solusinya daripada ketahuan.

Daftar isi: [A. Gambaran umum](#a-gambaran-umum) · [B. Hardware & ESP32](#b-hardware--esp32) · [C. Kalibrasi sensor](#c-kalibrasi-sensor) · [D. Backend Laravel](#d-backend-laravel) · [E. Logika status & Telegram](#e-logika-status--telegram) · [F. Database](#f-database) · [G. Frontend](#g-frontend) · [H. Keamanan](#h-keamanan) · [I. Pengujian](#i-pengujian) · [J. Pertanyaan umum TA](#j-pertanyaan-umum-ta) · [K. Kelemahan yang harus disiapkan](#k-kelemahan-yang-harus-disiapkan)

---

## A. Gambaran umum

**1. Jelaskan sistem Anda secara singkat.**
SmartQua adalah sistem monitoring kualitas air akuarium berbasis IoT. ESP32 membaca tiga parameter, yaitu pH, suhu (DS18B20), dan TDS. Hasilnya ditampilkan di LCD 16x2 dan dikirim lewat HTTP POST ke server Laravel setiap 10 detik. Server memvalidasi, menyimpan, dan menilai status air (Normal, Warning, atau Bahaya) berdasarkan batas yang bisa diatur pengguna. Dashboard web menampilkan data hampir real-time, dan kalau air bermasalah, notifikasi dikirim ke Telegram.

**2. Gambarkan alur data dari sensor sampai tampil di layar.** *(pasti ditanya)*
1. Sensor pH (pin 34), TDS (pin 35), DS18B20 (pin 4) dibaca ESP32.
2. ADC pH dan TDS dibaca 10 kali lalu dirata-rata, kemudian dikonversi ke tegangan, lalu ke nilai pH dan ppm.
3. ESP32 membuat JSON dan mengirim `POST /api/sensor` dengan header `Authorization: Bearer <token>`.
4. Laravel: middleware `auth:sanctum` memeriksa token, lalu `StoreSensorReadingRequest` memvalidasi data.
5. `SensorReadingController::store` membuat atau memperbarui `Device`, menghitung status lewat `WaterQualityThreshold::statusFor`, dan menyimpan ke tabel `sensor_readings`.
6. `TelegramAlertService::sendForReading` mengirim alert jika status bukan normal dan cooldown sudah lewat.
7. Server membalas HTTP 201. ESP32 menampilkan "OK" di LCD.
8. Browser memanggil `/dashboard/data` setiap 5 detik (AJAX polling) dan memperbarui kartu dan grafik.

**3. Apa masalah yang ingin diselesaikan?**
Pengecekan kualitas air akuarium biasanya manual, jarang, dan baru sadar setelah ikan stres atau mati. SmartQua memberi pemantauan terus-menerus, riwayat data, dan peringatan otomatis. Parameter yang dipilih (pH, suhu, TDS) adalah yang paling berpengaruh pada kesehatan ikan dan bisa diukur dengan sensor murah.

**4. Teknologi apa yang dipakai dan kenapa?**
- ESP32 karena WiFi bawaan, ADC 12-bit, harga murah, dan mudah diprogram lewat Arduino IDE.
- Laravel karena struktur MVC rapi, ada validasi, ORM, dan autentikasi API (Sanctum) bawaan, dan saya sudah familiar.
- MySQL sebagai database relasional untuk data time-series sederhana.
- Telegram Bot API karena gratis, real-time, dan sudah ada di HP pengguna, jadi tidak perlu membuat aplikasi mobile.
- Template Vuexy untuk tampilan, sedangkan logika bisnis, API, dan firmware saya kerjakan sendiri.

---

## B. Hardware & ESP32

**5. Kenapa ESP32, bukan Arduino Uno atau ESP8266?**
Uno tidak punya WiFi, jadi butuh modul tambahan. ESP8266 hanya punya 1 pin ADC dengan rentang 0–1 V, padahal saya butuh dua sensor analog (pH dan TDS). ESP32 punya WiFi bawaan, banyak pin ADC, dan resolusi 12-bit (0–4095).

**6. Kenapa sensor pH di pin 34 dan TDS di pin 35?**
Keduanya ADC1 dan input-only. ADC2 di ESP32 tidak bisa dipakai saat WiFi aktif, jadi sensor analog harus di ADC1 (GPIO 32–39). Pin 34 dan 35 memang cocok untuk itu.

**7. Apa fungsi `analogSetAttenuation(ADC_11db)`?**
Mengatur redaman input ADC supaya rentang baca sampai sekitar 3,3 V. Tanpa itu, rentang default lebih kecil dan tegangan sensor pH (2–3,3 V) akan terpotong.

**8. Kenapa ADC dibaca 10 kali lalu dirata-rata?**
ADC ESP32 cukup noisy. Rata-rata 10 sampel (jeda 5 ms) meredam noise acak sehingga hasilnya lebih stabil dan tampilan tidak melompat-lompat.

**9. Kenapa sensor dibaca tiap 1,5 detik, tapi data dikirim tiap 10 detik?**
Pembacaan dan LCD perlu responsif untuk kalibrasi dan dilihat langsung. Kualitas air berubah lambat, jadi mengirim tiap 10 detik sudah cukup dan tidak membebani server dan database. Keduanya memakai `millis()`, bukan `delay()` panjang, supaya program tidak terblokir (non-blocking).

**10. Bagaimana kalau WiFi putus?**
`jagaKoneksiWiFi()` mencoba menyambung ulang setiap 5 detik. Selama putus, ESP32 tetap membaca sensor dan menampilkan ke LCD. Saat mengirim, jika WiFi belum tersambung, LCD menampilkan status "WiFi".
**Jawab jujur:** data selama putus tidak disimpan dan dikirim ulang (tidak ada buffer offline). Pengembangan ke depan bisa memakai SPIFFS atau SD card sebagai antrean.

**11. Bagaimana sistem tahu sensor rusak?**
- Suhu: DS18B20 mengembalikan `DEVICE_DISCONNECTED_C` (-127) jika tidak terhubung, lalu `suhuValid = false`.
- pH dan TDS: jika rata-rata ADC bernilai 0 atau 4095 (mentok), sensor dianggap error.
- Status dikirim ke server lewat field `sensor_status` (contoh `SUHU_ERROR,PH_ERROR`) dan disimpan di tabel `devices`.

**12. Apa itu `millis()` dan kenapa tidak pakai `delay()`?**
`millis()` menghitung waktu sejak ESP32 menyala. Dengan membandingkan selisih waktu, beberapa tugas (baca sensor, kirim API, cek WiFi) bisa berjalan bergantian tanpa saling memblokir. `delay(10000)` akan membuat WiFi retry dan LCD berhenti selama 10 detik.

**13. Apa itu RSSI dan uptime yang dikirim?**
`wifi_rssi` adalah kekuatan sinyal WiFi (dBm, makin mendekati 0 makin kuat). `uptime_seconds` adalah lama ESP32 menyala sejak restart. Keduanya untuk diagnosis kesehatan perangkat di halaman Pengaturan, misalnya apakah alat sering restart atau sinyalnya lemah.

**14. Apa itu protokol OneWire dan kenapa DS18B20?**
OneWire memungkinkan sensor digital dibaca lewat satu kabel data. DS18B20 sudah dikalibrasi pabrik (akurasi ±0,5 °C), tersedia versi waterproof, dan keluarannya digital sehingga tidak terpengaruh noise ADC.

**15. Kenapa LCD memakai I2C alamat 0x27?**
I2C hanya butuh 2 kabel data (SDA dan SCL) dibanding LCD paralel yang butuh sekitar 6 pin. 0x27 adalah alamat umum modul PCF8574. Ini bisa dicek dengan I2C scanner.

---

## C. Kalibrasi sensor

**16. Bagaimana cara kalibrasi sensor pH Anda?** *(pertanyaan teknis paling mungkin)*
Memakai tiga larutan buffer standar. Probe dicelup di tiap buffer, lalu tegangan dari Serial Monitor dicatat:
- pH 7 → 2,53 V
- pH 4 → 3,30 V
- pH 9,18 → 2,07 V

Sensor pH analog bekerja linear terhadap tegangan, tapi tegangannya menurun saat pH naik.

**17. Tuliskan rumus konversi pH Anda.**
```
tegangan = (3.3 / 4095) * ADC

phStep     = (V_pH4 - V_pH7) / 3       = (3.30 - 2.53) / 3     ≈ 0.257 V per satuan pH (sisi asam)
phStepBasa = (V_pH7 - V_pH9) / 2.18    = (2.53 - 2.07) / 2.18  ≈ 0.211 V per satuan pH (sisi basa)

jika tegangan >= V_pH7 (asam) : pH = 7 - (tegangan - 2.53) / phStep
jika tegangan <  V_pH7 (basa) : pH = 7 + (2.53 - tegangan) / phStepBasa
lalu hasil dibatasi 0..14
```

**18. Kenapa memakai dua slope (asam dan basa)?**
Dari data kalibrasi sendiri, kemiringan sisi asam (≈0,257 V/pH) dan sisi basa (≈0,211 V/pH) tidak sama. Kalau memaksa satu garis lurus, pH di atas 7 akan meleset. Kalibrasi tiga titik dengan dua segmen linear memperbaiki akurasi di kedua sisi. Sesuai teori, elektroda pH sebenarnya bergeser dari respons ideal Nernst (59,16 mV/pH pada 25 °C), terutama karena usia probe dan modul penguatnya.

**19. Kenapa buffer 9,18 dan 4,00, bukan 10,01?**
Karena buffer 4,00, 7,00, dan 9,18 (borax) yang tersedia. Rentang air akuarium air tawar ada di sekitar 6–9, jadi titik kalibrasi mengapit rentang kerja. Akurasi paling baik di antara pH 4 dan 9,18.

**20. Rumus TDS Anda dari mana?**
```
koefisien = 1 + 0.02 * (suhu - 25)        // kompensasi suhu, 2% per °C
V_komp    = tegangan / koefisien
TDS (ppm) = (133.42·V³ − 255.86·V² + 857.39·V) × 0.5
```
Ini rumus polinomial standar sensor TDS analog (Gravity TDS DFRobot), yaitu konversi konduktivitas ke TDS dengan faktor 0,5.
**Jawab jujur:** koefisien polinomial ini dari referensi produsen, bukan hasil kalibrasi ulang saya. Kalau ditanya akurasinya, jawab bahwa hasilnya dibandingkan dengan TDS meter pembanding (siapkan angkanya, lihat bagian K).

**21. Kenapa TDS perlu kompensasi suhu?**
Konduktivitas air naik sekitar 2% per °C. Tanpa kompensasi, air yang sama akan terbaca berbeda pada suhu berbeda. Rumus menormalkan ke 25 °C. Jika sensor suhu error, dipakai 25 °C sebagai default.

**22. Apa hubungan TDS dan kualitas air ikan?**
TDS adalah total zat padat terlarut (mineral, garam, sisa pakan). Nilai naik terus menandakan akumulasi kotoran dan perlu ganti air. TDS tidak membedakan zat berbahaya dan tidak berbahaya, jadi ia indikator umum, bukan pengganti tes amonia atau nitrit.

**23. Kenapa ada `calibration_offset` dan `calibration_multiplier` di database?**
**Jawab jujur:** kolom itu ditambahkan lewat migration `add_calibration_fields_to_water_quality_thresholds_table`, tapi saat ini kalibrasi yang dipakai sebenarnya ada di firmware (konstanta tegangan). Kolom di database belum dipakai dalam perhitungan. Jika ditanya, jawab bahwa ini disiapkan untuk kalibrasi dari sisi server tanpa flash ulang ESP32, dan belum diaktifkan.

**24. Seberapa akurat sensor Anda?**
Isi dengan data uji Anda sendiri, contoh tabel:

| Parameter | Nilai referensi | Nilai sensor | Error (%) |
|---|---|---|---|
| pH buffer 7,00 | 7,00 | … | … |
| pH buffer 4,00 | 4,00 | … | … |
| Suhu (termometer) | … | … | … |
| TDS (TDS meter) | … | … | … |

Rumus error: `|sensor − referensi| / referensi × 100%`. Sebaiknya minimal 5–10 pengukuran per parameter.

**25. Seberapa sering harus dikalibrasi ulang?**
Probe pH sebaiknya dikalibrasi tiap 1–2 bulan, atau kalau pembacaan terlihat drift. Probe pH juga harus dijaga tetap basah dan tidak dibiarkan kering.

---

## D. Backend Laravel

**26. Jelaskan struktur MVC project Anda.**
- **Model**: `Device`, `SensorReading`, `WaterQualityThreshold`, `User`.
- **View**: file Blade di `resources/views` (dashboard, history, settings, api-tokens, api-docs).
- **Controller**: `DashboardController`, `HistoryController`, `SettingController`, `ThresholdController`, `ApiTokenController`, `ReportExportController`, dan `Api\SensorReadingController` untuk ESP32.
- **Service**: `TelegramAlertService` memisahkan logika Telegram dari controller.
- **Form Request**: `StoreSensorReadingRequest` khusus validasi input sensor.

**27. Kenapa memakai Form Request untuk validasi?**
Supaya controller tetap ringkas dan aturan validasi terpusat serta bisa diuji. Jika validasi gagal, Laravel otomatis mengembalikan HTTP 422 dengan pesan error dalam JSON.

**28. Apa saja aturan validasinya?**
- `ph`: wajib, numerik, 0–14.
- suhu: 0–80 °C. TDS: integer 0–5000 ppm.
- RSSI: −120 sampai 0.
- Suhu dan TDS wajib diisi salah satu nama field-nya (`withValidator`).

Tujuannya menolak data mustahil (misalnya pH 20) agar tidak merusak riwayat dan grafik.

**29. Kenapa ada `normalizePayload` yang menerima banyak nama field?**
Supaya API fleksibel: `suhu`, `temperature`, dan `temperature_celsius` semuanya diterima, begitu juga `tds` dan `tds_ppm`. Ini memudahkan integrasi dan testing dengan alat lain. Di dalam, semua dinormalkan ke satu nama.

**30. Apa yang terjadi di `store()` langkah demi langkah?**
1. Normalisasi payload.
2. `Device::firstOrCreate` berdasarkan `device_code`. Perangkat baru otomatis terdaftar.
3. Update info kesehatan perangkat (`last_seen_at`, RSSI, uptime, status sensor).
4. Hitung status dengan `WaterQualityThreshold::statusFor`.
5. Simpan `SensorReading`, termasuk `raw_payload` (JSON asli) dan `recorded_at`.
6. Kirim alert Telegram jika perlu.
7. Balas 201 dengan data lengkap plus severity, label, dan insight.

**31. Kenapa `raw_payload` disimpan?**
Untuk audit dan debugging. Kalau ada nilai aneh, bisa dilihat apa yang sebenarnya dikirim alat, termasuk `sensor_status`, tanpa kehilangan informasi akibat pembulatan.

**32. Kenapa waktu disimpan UTC tapi ditampilkan WIB?**
`config/app.php` memakai `timezone = UTC` untuk penyimpanan yang konsisten dan bebas masalah daylight-saving atau server berpindah zona. Saat tampil, dikonversi ke `Asia/Jakarta` lewat `display_timezone` (`recordedAtLocal()`). Filter histori dari pengguna (WIB) dikonversi ke UTC sebelum query.

**33. Apa beda `GET /api/readings` dan `POST /api/sensor`?**
POST dipakai ESP32 untuk mengirim data. GET dipakai untuk membaca riwayat (misalnya aplikasi lain), dengan parameter `limit` (dibatasi 1–200) dan filter `device_code`. Keduanya di bawah `auth:sanctum`.

**34. Kenapa `limit` dibatasi maksimum 200?**
Mencegah satu request menarik ribuan baris yang membebani server dan memori.

**35. Bagaimana laporan/export dibuat?**
`HistoryController::export` menghasilkan CSV. `ReportExportController` menghasilkan Excel dan laporan cetak berdasarkan rentang waktu yang dipilih pengguna.

**36. Apa itu `firstOrCreate` dan kenapa dipakai untuk device?**
Mengambil record berdasarkan kode, dan membuatnya jika belum ada. Dengan itu ESP32 baru langsung terdaftar saat pertama mengirim data, tanpa langkah manual. Tabel `devices` juga membuat sistem siap untuk banyak akuarium.

---

## E. Logika status & Telegram

**37. Bagaimana sistem menentukan Normal, Warning, dan Bahaya?** *(sering ditanya penguji perikanan)*
Ada dua langkah, di `WaterQualityThreshold`:
1. **Di luar batas?** Nilai dibandingkan dengan `min_value` dan `max_value`. Kalau di dalam rentang, tidak ada peringatan, walau dekat batas.
2. **Seberapa jauh?** Selisih (`gap`) antara nilai dan batas dibandingkan dengan `warning_tolerance`:
   - `gap ≤ warning_tolerance` → **Warning**
   - `gap > warning_tolerance` → **Bahaya (critical)**
   - Kalau `warning_tolerance` ≤ 0, langsung critical.

Status akhir adalah yang paling parah dari ketiga parameter.

**38. Berapa batas default dan dari mana referensinya?**

| Parameter | Min | Max | Toleransi warning |
|---|---|---|---|
| pH | 6,5 | 8,5 | 0,20 |
| Suhu (°C) | 24 | 30 | 0,50 |
| TDS (ppm) | 100 | 500 | 25 |

Nilai ini rentang umum ikan air tawar tropis dan bisa diubah pengguna di halaman Pengaturan, karena kebutuhan tiap spesies berbeda (misalnya cupang, koi, dan arwana). **Siapkan 1–2 referensi** (jurnal atau standar budidaya) untuk rentang ini, karena penguji hampir pasti minta sumbernya.

**39. Contoh perhitungan.**
Batas pH maksimum 8,5, toleransi 0,20. pH terbaca 8,6, gap 0,1 ≤ 0,2 → **Warning**. pH 9,0, gap 0,5 > 0,2 → **Bahaya**.

**40. Kenapa `quality_status` di database hanya "normal" atau "warning", padahal ada critical?**
`quality_status` disimpan sebagai status ringkas saat data masuk. Tingkat keparahan (`severity`) dihitung ulang saat dibaca dari batas yang berlaku sekarang. Kalau pengguna mengubah batas, tampilan langsung mengikuti tanpa mengubah data historis.
**Jawab jujur:** akibatnya data lama bisa tampil berbeda jika batas diubah. Itu disengaja agar konsisten dengan pengaturan terbaru, dan ada `RefreshSensorReadingStatusSeeder` untuk memperbarui kolom status.

**41. Bagaimana mencegah spam notifikasi Telegram?**
Dengan cooldown lewat `Cache::add`. Kunci cache dibentuk dari `kode perangkat + severity + md5(ringkasan peringatan)`. Selama kunci masih ada (default 10 menit, bisa diatur `TELEGRAM_ALERT_COOLDOWN_MINUTES`), alert yang sama tidak dikirim lagi. Kalau kondisinya berubah (parameter atau tingkat keparahan berbeda), kuncinya berbeda dan alert baru dikirim. Jika pengiriman gagal, kunci dihapus supaya bisa dicoba lagi.

**42. Kalau Telegram atau internet mati, apakah pengiriman data sensor ikut gagal?**
Tidak. Data sudah disimpan sebelum alert dikirim, dan pengiriman Telegram dibungkus `try/catch (Throwable)` dengan timeout 5 detik. Kegagalan hanya dicatat di log dan tidak mengganggu respons ke ESP32.
**Jawab jujur:** pengiriman Telegram masih sinkron di request, jadi bisa menambah latensi maksimal 5 detik. Solusi lanjutannya memakai queue (tabel `jobs` sudah ada di migration).

**43. Bagaimana token bot Telegram diamankan?**
Disimpan di `.env` (bukan di kode) dan dibaca lewat `config/services.php`. Saat error dicatat ke log, token diganti `[REDACTED]` oleh `safeExceptionMessage`.

**44. Bagaimana pengguna menghidupkan atau mematikan notifikasi?**
Lewat halaman Pengaturan (`settings.telegram.toggle`). Status disimpan di cache (`telegram_alert:enabled`). Ada juga tombol test notifikasi.

---

## F. Database

**45. Sebutkan tabel utamanya dan relasinya.**
- `users`: akun login.
- `devices`: perangkat (kode, nama, `last_seen_at`, RSSI, uptime, firmware, status sensor, status daya).
- `sensor_readings`: data pembacaan (`device_id`, `ph`, `temperature_celsius`, `tds_ppm`, `quality_status`, `raw_payload`, `recorded_at`). Relasi many-to-one ke `devices`.
- `water_quality_thresholds`: batas per parameter.
- `personal_access_tokens`: token Sanctum.

Satu device punya banyak sensor_readings (`hasMany` / `belongsTo`).

**46. Kenapa ada index pada `status + recorded_at`?**
Query histori sering memfilter status dan mengurutkan waktu. Index gabungan (migration `2026_06_15_000001`) mempercepat query itu ketika data sudah banyak.

**47. Bagaimana kalau datanya sudah jutaan baris?**
- Per perangkat, 10 detik sekali menghasilkan ≈ 8.640 baris/hari atau ≈ 3,1 juta/tahun.
- Mitigasi: index (sudah ada), pagination server-side (DataTables AJAX sudah begitu), batasi range grafik, dan ke depan agregasi (rata-rata per menit/jam) atau arsip data lama.

**48. Kenapa pH dan suhu bertipe decimal, bukan float?**
Decimal menyimpan angka presisi tetap (2 desimal) tanpa galat pembulatan biner. Cocok untuk nilai pengukuran yang ditampilkan dan dibandingkan dengan batas.

**49. Apa fungsi migration dan seeder?**
Migration adalah versi skema database yang bisa diulang dan dilacak (seperti git untuk database). Seeder mengisi data awal: `DatabaseSeeder` mengisi batas default, `HistoricalSensorReadingSeeder` mengisi data historis untuk demo.
**Jawab jujur:** kalau ada data dari `HistoricalSensorReadingSeeder` di database, bilang bahwa itu **data simulasi untuk demo/uji tampilan**, bukan hasil pengukuran. Data asli hanya yang dikirim ESP32.

---

## G. Frontend

**50. Bagaimana dashboard bisa real-time?**
Dengan **polling AJAX**: JavaScript memanggil `/dashboard/data` tiap 5 detik (`setInterval(refreshLatest, 5000)`), lalu memperbarui kartu, status, dan grafik tanpa reload halaman. Jam relatif ("5 detik lalu") diperbarui tiap 1 detik di sisi klien.

**51. Kenapa polling, bukan WebSocket?**
Data hanya berubah tiap 10 detik dan pengguna sedikit, jadi polling sederhana, tidak butuh server tambahan (Reverb atau Pusher), dan cukup responsif. WebSocket lebih efisien untuk pengguna banyak. Itu bisa jadi pengembangan lanjutan.

**52. Bagaimana sistem menandai alat offline?**
Dari `last_seen_at` di tabel `devices`. Jika sudah lama tidak ada data, dashboard menampilkan status offline. Ada test khusus untuk itu (`test_offline_dashboard_keeps_today_chart_and_alert_history`).

**53. Apa yang ada di halaman histori?**
Tabel DataTables dengan pagination, search, dan sorting di server. Filter rentang waktu (tanggal dan jam), grafik tren dengan garis batas normal, serta export CSV, Excel, dan laporan.

**54. Apakah Vuexy dibuat sendiri?**
Tidak. Vuexy adalah template admin berlisensi (folder `full-version` dan `starter-kit` adalah bawaan template). Yang saya kerjakan adalah integrasi ke Laravel, halaman dashboard dan histori, JavaScript polling dan grafik, seluruh backend, dan firmware. Jawab lugas, jangan mengaku membuat template-nya.

---

## H. Keamanan

**55. Bagaimana API diamankan?**
Dengan **Laravel Sanctum** (Bearer token). Endpoint `/api/sensor` dan `/api/readings` ada di grup `auth:sanctum`. Tanpa token yang valid, respons 401 (ada test `test_sensor_endpoint_requires_sanctum_token`). Token dibuat lewat menu Token API atau command `MakeApiTokenCommand`.

**56. Bagaimana token disimpan?**
Sanctum hanya menyimpan **hash SHA-256** token di `personal_access_tokens`. Token asli hanya tampil sekali saat dibuat.

**57. Apa risikonya kalau token ada di firmware ESP32?**
Siapa pun yang punya akses fisik ke ESP32 bisa membaca token dari flash. Mitigasi: token khusus perangkat dengan hak minimal, bisa dicabut dari menu Token API tanpa mengganggu akun lain, dan token tidak boleh ditampilkan di laporan atau slide (sudah ada peringatan di komentar kode).

**58. Kenapa HTTP, bukan HTTPS?**
Karena server dijalankan di jaringan lokal (`http://192.168.1.15:8000`) untuk prototipe. Di jaringan tertutup risikonya kecil. Untuk deployment publik wajib HTTPS (ESP32 mendukung `WiFiClientSecure`) karena token dikirim di header dan bisa disadap di HTTP.

**59. Bagaimana keamanan login dan web?**
Halaman web dilindungi autentikasi Laravel (guest diarahkan ke login, ada test-nya), password di-hash (bcrypt) oleh Laravel, form dilindungi CSRF, dan Eloquent memakai prepared statement sehingga aman dari SQL injection.

**60. Bagaimana dengan SSL Telegram di lokal?**
Disediakan opsi `ca_bundle` di config (`httpOptions()`) untuk lingkungan Windows lokal yang sering gagal verifikasi sertifikat, tanpa mematikan verifikasi SSL.

---

## I. Pengujian

**61. Pengujian apa yang sudah dilakukan?**
Pengujian otomatis (PHPUnit / Feature test) di folder `tests/Feature`:
- ESP32 dapat menyimpan data (201).
- Nilai di luar batas menghasilkan status warning.
- Endpoint menolak request tanpa token.
- Alert Telegram terkirim saat warning dan aktif, dan tidak terkirim saat normal.
- Nilai di dalam rentang (walau dekat batas) tidak memicu peringatan.
- Toleransi warning menentukan severity warning atau critical.
- Riwayat bisa dibaca dengan token Sanctum.
- Halaman dan tampilan (login, dashboard, redirect guest).

Jalankan dengan:
```
php artisan test
```

**62. Kenapa tes memakai Feature test, bukan unit test?**
Yang penting diuji adalah alur nyata lewat HTTP: request masuk, validasi, database, respons. Feature test menguji seluruh jalur itu. Unit test murni kurang bernilai untuk logika yang bergantung pada database dan konfigurasi.

**63. Apakah alatnya diuji juga (bukan hanya software)?**
Siapkan jawaban berikut dan datanya:
- **Uji akurasi sensor** terhadap alat pembanding (tabel di pertanyaan 24).
- **Uji pengiriman data**: berapa persen request berhasil (HTTP 201) dari total kiriman dalam N jam.
- **Uji delay**: selisih waktu sensor membaca sampai tampil di dashboard (perkiraan ≤ 10 detik kirim + ≤ 5 detik polling).
- **Uji ketahanan**: WiFi dimatikan lalu dinyalakan, apakah alat menyambung sendiri.
- **Uji alert**: air sengaja dibuat di luar batas (misal dicampur larutan buffer), lalu cek Telegram masuk.

**64. Apa itu black box testing?**
Pengujian fungsi dari sisi masukan dan keluaran tanpa melihat kode internal, misalnya "kirim pH 20 → harus ditolak 422". Tabel skenario, masukan, hasil yang diharapkan, dan hasil aktual biasanya masuk bab pengujian.

---

## J. Pertanyaan umum TA

**65. Apa kelebihan dan kekurangan sistem Anda?**
- *Kelebihan:* biaya rendah, real-time, ada riwayat dan laporan, batas bisa diatur per jenis ikan, ada notifikasi Telegram, mendukung banyak perangkat.
- *Kekurangan:* hanya 3 parameter, belum ada buffer offline, sensor pH perlu kalibrasi berkala, tidak mengukur amonia atau oksigen terlarut, HTTP belum HTTPS.

**66. Kenapa hanya pH, suhu, dan TDS?**
Ketiganya paling umum, murah, dan sensornya tersedia untuk mikrokontroler. Amonia dan DO lebih penting untuk budidaya intensif tapi sensornya mahal dan butuh perawatan khusus. Bisa dikembangkan.

**67. Apa bedanya dengan produk komersial?**
Produk komersial lebih akurat tapi mahal dan tertutup. SmartQua murah, terbuka, bisa disesuaikan (batas, notifikasi, laporan), dan bisa dikembangkan.

**68. Apa pengembangan ke depan?**
Kontrol otomatis (pompa air, heater, aerator lewat relay), buffer offline di ESP32, HTTPS, queue untuk notifikasi, agregasi data, aplikasi mobile, sensor tambahan (amonia, DO, turbidity), dan prediksi tren.

**69. Apa kontribusi utama Anda?**
Integrasi end-to-end: firmware ESP32 (pembacaan, kalibrasi tiga titik, pengiriman), REST API yang aman, logika penilaian status dengan toleransi bertingkat, dashboard, histori, laporan, dan notifikasi Telegram.

**70. Kenapa Laravel, bukan Node.js atau Firebase?**
Laravel memberi struktur, validasi, autentikasi, ORM, dan testing bawaan dalam satu paket, sehingga cepat dan rapi. Firebase membuat data bergantung pada layanan pihak ketiga. Dengan Laravel, data ada di server sendiri.

**71. Bagaimana kalau ikan berbeda punya kebutuhan berbeda?**
Batas normal bisa diubah di Pengaturan (`ThresholdController@update`), termasuk toleransi dan on/off alert per parameter. Cache batas otomatis di-reset saat disimpan, jadi langsung berlaku.

**72. Apakah sistem bisa dipakai untuk lebih dari satu akuarium?**
Ya. Tabel `devices` dan `device_code` membedakan perangkat. Tiap ESP32 punya kode sendiri. Dashboard saat ini menampilkan data terbaru, jadi tampilan multi-akuarium yang lebih rinci masih bisa dikembangkan.

---

## K. Kelemahan yang harus disiapkan

Ini hal yang saya temukan di kode dan mungkin ditemukan penguji. Akui, jelaskan dampaknya, sebutkan solusinya.

1. **Suhu error dikirim sebagai `0.0`.** Di `kirimDataKeAPI()`, jika sensor suhu terputus, payload berisi `"suhu": 0.0`. Nilai 0 lolos validasi (rentang 0–80), lalu server menilainya "suhu terlalu rendah" dan bisa memicu alert palsu. Padahal `sensor_status` sudah berisi `SUHU_ERROR`.
   *Solusi:* jangan kirim field suhu saat tidak valid dan tolak di server, atau abaikan penilaian suhu jika `sensor_status` memuat error. Bisa diperbaiki sebelum sidang kalau mau.
2. **pH dan TDS tetap dikirim walau `phValid`/`tdsValid` false**, sehingga nilai palsu bisa masuk riwayat.
3. **Tidak ada buffer offline** di ESP32, jadi data selama WiFi atau server mati hilang.
4. **Kolom `calibration_offset` dan `calibration_multiplier` belum dipakai**, kalibrasi hanya ada di firmware.
5. **HTTP tanpa TLS** dan token tertanam di firmware (hanya cocok untuk jaringan lokal).
6. **Telegram sinkron** dalam request API (timeout 5 detik), belum lewat queue.
7. **`quality_status` di database hanya normal/warning**, severity (critical) dihitung saat dibaca.
8. **Koefisien TDS adalah rumus standar produsen**, bukan hasil kalibrasi larutan TDS standar sendiri (misalnya 342 ppm atau 1413 µS/cm).
9. **`register` route ada** di `routes/web.php`. Kalau aplikasi dipublikasikan, siapa pun bisa mendaftar. Siapkan alasan (khusus demo) atau nonaktifkan.
10. **Data seeder historis** bisa dianggap data asli kalau tidak dijelaskan. Katakan dengan jelas mana data simulasi dan mana data pengukuran nyata.

---

## Checklist sebelum sidang

- [ ] Tabel uji akurasi sensor (pH, suhu, TDS) vs alat pembanding.
- [ ] 1–2 referensi ilmiah untuk batas pH, suhu, dan TDS.
- [ ] Diagram alur data dan diagram blok hardware (hafal).
- [ ] Bisa menjelaskan rumus pH 3 titik dan TDS dengan kompensasi suhu tanpa membaca catatan.
- [ ] Demo langsung: alat menyala, token terisi, IP server benar (`SERVER_URL`), dan cadangan video jika WiFi bermasalah.
- [ ] Token asli tidak tampil di slide atau laporan.
- [ ] Tahu mana data asli dan mana data seeder.
- [ ] Sudah menjalankan `php artisan test` dan semuanya hijau.
