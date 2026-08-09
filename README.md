# SmartQua - Monitoring Kualitas Air Akuarium

Project tugas akhir: **Rancang Bangun Sistem Monitoring Kualitas Air (pH, Suhu dan TDS) Berbasis Internet of Things (IoT) dengan Integrasi Web API pada Akuarium**.

SmartQua adalah aplikasi web Laravel untuk menerima data sensor dari ESP32, menyimpan data ke database, menampilkan dashboard monitoring, histori pembacaan, export laporan, dan notifikasi Telegram.

## Fitur Utama

- Dashboard realtime untuk pH, suhu, TDS, status perangkat, grafik hari ini, rekomendasi tindakan, dan alert terbaru.
- Histori pembacaan sensor dengan filter tanggal/jam, DataTables AJAX, grafik per parameter, export CSV, export Excel, dan laporan printable PDF + grafik.
- Pengaturan perangkat, batas normal sensor, aturan warning/bahaya, dan notifikasi Telegram.
- REST API untuk ESP32 menggunakan Laravel Sanctum Bearer Token.
- Token API dapat dibuat dan dicabut dari halaman Token API.

## Menjalankan Project

```bash
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve --host=0.0.0.0 --port=8000
```

Dashboard:

```text
http://127.0.0.1:8000
```

## API ESP32

Endpoint utama untuk alat:

```http
POST /api/sensor
Authorization: Bearer TOKEN_SANCTUM
Content-Type: application/json
Accept: application/json
```

Contoh payload:

```json
{
  "device_code": "esp32-aquarium-01",
  "ph": 7.2,
  "suhu": 27.4,
  "tds": 310,
  "wifi_rssi": -63,
  "uptime_seconds": 268400
}
```

Field alternatif yang didukung:

- `suhu`, `temperature`, atau `temperature_celsius`
- `tds` atau `tds_ppm`
- `device_code`, `device_id`, atau `kode_perangkat`

Ambil histori lewat API:

```http
GET /api/readings?limit=50
Authorization: Bearer TOKEN_SANCTUM
Accept: application/json
```

Status code penting:

- `201 Created`: data sensor berhasil disimpan.
- `200 OK`: data berhasil dibaca.
- `401 Unauthorized`: token API tidak valid atau tidak dikirim.
- `422 Unprocessable Entity`: payload tidak valid.

## Contoh Arduino HTTPClient

```cpp
HTTPClient http;
http.begin("http://IP-LAPTOP:8000/api/sensor");
http.addHeader("Content-Type", "application/json");
http.addHeader("Accept", "application/json");
http.addHeader("Authorization", "Bearer TOKEN_SANCTUM");

String payload = "{";
payload += "\"device_code\":\"esp32-aquarium-01\",";
payload += "\"ph\":7.20,";
payload += "\"suhu\":27.40,";
payload += "\"tds\":310";
payload += "}";

int statusCode = http.POST(payload);
String response = http.getString();
http.end();
```

Ganti `IP-LAPTOP` dengan IP komputer yang menjalankan Laravel.

## Batas Normal Default

- pH: 6.5 sampai 8.5
- Suhu: 24 sampai 30 C
- TDS: 100 sampai 500 ppm

Nilai batas normal dan aturan warning/bahaya dapat diubah dari menu **Sistem > Pengaturan**.
# tugas-akhir
