/**
 * Proyek : Monitoring pH, Suhu, dan TDS Akuarium Berbasis IoT
 * Board  : ESP32 Dev Module
 *
 * Alur kerja:
 * 1. ESP32 membaca sensor pH, suhu DS18B20, dan TDS.
 * 2. Nilai ditampilkan ke LCD 16x2.
 * 3. Data dikirim ke Laravel SmartQua lewat POST /api/sensor.
 * 4. API Laravel memvalidasi Bearer Token, menyimpan data, dan menampilkan ke dashboard.
 */

#include <DallasTemperature.h>
#include <HTTPClient.h>
#include <LiquidCrystal_I2C.h>
#include <OneWire.h>
#include <WiFi.h>
#include <Wire.h>

// ========================================================================
// PIN SENSOR
// ========================================================================
const int PIN_PH = 34;
const int PIN_TDS = 35;
const int PIN_SUHU = 4;

// ========================================================================
// INTERVAL SISTEM
// ========================================================================
const unsigned long SENSOR_INTERVAL_MS = 1500; // Baca sensor dan update LCD setiap 1.5 detik.
const unsigned long API_INTERVAL_MS = 10000;   // Kirim data ke server setiap 10 detik.
const unsigned long WIFI_RETRY_MS = 5000;      // Coba reconnect WiFi setiap 5 detik jika putus.

unsigned long lastSensorMillis = 0;
unsigned long lastApiMillis = 0;
unsigned long lastWifiRetryMillis = 0;

// ========================================================================
// WIFI DAN API
// ========================================================================
const char* WIFI_SSID = "ISI_SSID_WIFI";
const char* WIFI_PASSWORD = "ISI_PASSWORD_WIFI";

// Gunakan IP laptop/server yang menjalankan Laravel.
// Server Laravel pada IP Wi-Fi laptop. ESP32 harus berada di jaringan yang sama.
const String SERVER_URL = "http://192.168.1.15:8000/api/sensor";

// Buat token dari menu Token API di SmartQua.
// Jangan tampilkan token asli di laporan atau slide.
const String API_TOKEN = "ISI_TOKEN_SANCTUM";

// ========================================================================
// OBJEK LIBRARY
// ========================================================================
LiquidCrystal_I2C lcd(0x27, 16, 2);
OneWire oneWire(PIN_SUHU);
DallasTemperature sensorSuhu(&oneWire);

// ========================================================================
// KALIBRASI PH
// ========================================================================
const float PH_7_VOLTAGE = 2.53;
const float PH_4_VOLTAGE = 3.30;
float phStep = 0.0;

// ========================================================================
// DATA SENSOR
// ========================================================================
struct AquariumData {
  float suhu;
  float ph;
  float tds;
  bool suhuValid;
  bool phValid;
  bool tdsValid;
};

AquariumData dataAkuarium = {
  0.0,
  0.0,
  0.0,
  false,
  false,
  false
};

// ========================================================================
// DEKLARASI FUNGSI
// ========================================================================
void inisialisasiSistem();
void hubungkanWiFi();
void jagaKoneksiWiFi();
void bacaSemuaSensor();
float hitungPH(int rawADC);
float hitungTDS(int rawADC, float suhuAir);
String statusSensor();
void updateTampilanLCD();
void tampilkanStatusKirim(const String& pesan);
void kirimDataKeSerial();
void kirimDataKeAPI();

// ========================================================================
// SETUP DAN LOOP
// ========================================================================
void setup() {
  inisialisasiSistem();
}

void loop() {
  const unsigned long now = millis();

  jagaKoneksiWiFi();

  if (now - lastSensorMillis >= SENSOR_INTERVAL_MS) {
    lastSensorMillis = now;

    bacaSemuaSensor();
    updateTampilanLCD();
    kirimDataKeSerial();
  }

  if (now - lastApiMillis >= API_INTERVAL_MS) {
    lastApiMillis = now;
    kirimDataKeAPI();
  }
}

// ========================================================================
// IMPLEMENTASI FUNGSI
// ========================================================================
void inisialisasiSistem() {
  Serial.begin(115200);
  delay(200);

  analogSetAttenuation(ADC_11db);
  phStep = (PH_4_VOLTAGE - PH_7_VOLTAGE) / 3.0;

  sensorSuhu.begin();
  lcd.init();
  lcd.backlight();

  lcd.setCursor(0, 0);
  lcd.print(" SMARTQUA ESP32 ");
  lcd.setCursor(0, 1);
  lcd.print(" Initializing.. ");
  delay(1500);
  lcd.clear();

  hubungkanWiFi();
}

void hubungkanWiFi() {
  Serial.print("[WIFI] Menghubungkan ke ");
  Serial.println(WIFI_SSID);

  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("WiFi connecting");

  const unsigned long startMillis = millis();
  int dotIndex = 0;

  while (WiFi.status() != WL_CONNECTED && millis() - startMillis < 15000) {
    delay(500);
    Serial.print(".");
    lcd.setCursor(dotIndex % 16, 1);
    lcd.print(".");
    dotIndex++;
  }

  lcd.clear();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println();
    Serial.print("[WIFI] Terhubung. IP: ");
    Serial.println(WiFi.localIP());

    lcd.setCursor(0, 0);
    lcd.print("WiFi connected");
    lcd.setCursor(0, 1);
    lcd.print(WiFi.localIP().toString());
    delay(1500);
  } else {
    Serial.println();
    Serial.println("[WIFI] Gagal terhubung. Sistem tetap berjalan.");

    lcd.setCursor(0, 0);
    lcd.print("WiFi timeout");
    lcd.setCursor(0, 1);
    lcd.print("Retry otomatis");
    delay(1500);
  }

  lcd.clear();
}

void jagaKoneksiWiFi() {
  if (WiFi.status() == WL_CONNECTED) {
    return;
  }

  const unsigned long now = millis();

  if (now - lastWifiRetryMillis < WIFI_RETRY_MS) {
    return;
  }

  lastWifiRetryMillis = now;
  Serial.println("[WIFI] Terputus. Mencoba reconnect...");
  WiFi.disconnect();
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
}

void bacaSemuaSensor() {
  sensorSuhu.requestTemperatures();
  const float suhuMentah = sensorSuhu.getTempCByIndex(0);

  dataAkuarium.suhuValid = suhuMentah != DEVICE_DISCONNECTED_C;
  dataAkuarium.suhu = dataAkuarium.suhuValid ? suhuMentah : dataAkuarium.suhu;

  int totalPhRaw = 0;
  for (int i = 0; i < 10; i++) {
    totalPhRaw += analogRead(PIN_PH);
    delay(5);
  }

  const int rataPhRaw = totalPhRaw / 10;
  dataAkuarium.ph = hitungPH(rataPhRaw);
  dataAkuarium.phValid = rataPhRaw > 0 && rataPhRaw < 4095;

  int totalTdsRaw = 0;
  for (int i = 0; i < 10; i++) {
    totalTdsRaw += analogRead(PIN_TDS);
    delay(5);
  }

  const int rataTdsRaw = totalTdsRaw / 10;
  const float suhuUntukKompensasi = dataAkuarium.suhuValid ? dataAkuarium.suhu : 25.0;

  dataAkuarium.tds = hitungTDS(rataTdsRaw, suhuUntukKompensasi);
  dataAkuarium.tdsValid = rataTdsRaw > 0 && rataTdsRaw < 4095;
}

float hitungPH(int rawADC) {
  const float tegangan = (3.3 / 4095.0) * rawADC;
  float hasilPH = 7.00 - ((tegangan - PH_7_VOLTAGE) / phStep);

  if (hasilPH < 0.00) {
    hasilPH = 0.00;
  }

  if (hasilPH > 14.00) {
    hasilPH = 14.00;
  }

  return hasilPH;
}

float hitungTDS(int rawADC, float suhuAir) {
  const float tegangan = (3.3 / 4095.0) * rawADC;
  const float koefisienKompensasi = 1.0 + 0.02 * (suhuAir - 25.0);
  const float teganganKompensasi = tegangan / koefisienKompensasi;

  float hasilTDS =
    (133.42 * pow(teganganKompensasi, 3) -
     255.86 * pow(teganganKompensasi, 2) +
     857.39 * teganganKompensasi) *
    0.5;

  if (hasilTDS < 0.0) {
    hasilTDS = 0.0;
  }

  return hasilTDS;
}

String statusSensor() {
  if (dataAkuarium.suhuValid && dataAkuarium.phValid && dataAkuarium.tdsValid) {
    return "OK";
  }

  String status = "";

  if (!dataAkuarium.suhuValid) {
    status += "SUHU_ERROR";
  }

  if (!dataAkuarium.phValid) {
    if (status.length() > 0) {
      status += ",";
    }
    status += "PH_ERROR";
  }

  if (!dataAkuarium.tdsValid) {
    if (status.length() > 0) {
      status += ",";
    }
    status += "TDS_ERROR";
  }

  return status;
}

void updateTampilanLCD() {
  lcd.setCursor(0, 0);
  lcd.print("pH:");
  lcd.print(dataAkuarium.ph, 2);
  lcd.print("   ");

  lcd.setCursor(10, 0);
  lcd.print("S:");

  if (dataAkuarium.suhuValid) {
    lcd.print((int) dataAkuarium.suhu);
  } else {
    lcd.print("--");
  }

  lcd.print((char) 223);
  lcd.print("C");

  lcd.setCursor(0, 1);
  lcd.print("TDS:");
  lcd.print((int) dataAkuarium.tds);
  lcd.print(" ppm     ");
}

void tampilkanStatusKirim(const String& pesan) {
  lcd.setCursor(11, 1);
  lcd.print("     ");
  lcd.setCursor(11, 1);
  lcd.print(pesan.substring(0, 5));
}

void kirimDataKeSerial() {
  Serial.print("[DATA] pH: ");
  Serial.print(dataAkuarium.ph, 2);
  Serial.print(" | Suhu: ");

  if (dataAkuarium.suhuValid) {
    Serial.print(dataAkuarium.suhu, 1);
    Serial.print(" C");
  } else {
    Serial.print("ERROR");
  }

  Serial.print(" | TDS: ");
  Serial.print(dataAkuarium.tds, 0);
  Serial.print(" ppm");
  Serial.print(" | Sensor: ");
  Serial.println(statusSensor());
}

void kirimDataKeAPI() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[API] WiFi belum terhubung. Data belum dikirim.");
    tampilkanStatusKirim("WiFi");
    return;
  }

  if (API_TOKEN == "ISI_TOKEN_SANCTUM") {
    Serial.println("[API] API_TOKEN belum diisi.");
    tampilkanStatusKirim("Token");
    return;
  }

  HTTPClient http;

  http.begin(SERVER_URL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  http.addHeader("Authorization", "Bearer " + API_TOKEN);
  http.setTimeout(7000);

  String payload = "{";
  payload += "\"device_code\":\"esp32-aquarium-01\",";
  payload += "\"device_name\":\"Akuarium Utama\",";
  payload += "\"ph\":" + String(dataAkuarium.ph, 2) + ",";
  payload += "\"suhu\":" + String(dataAkuarium.suhuValid ? dataAkuarium.suhu : 0.0, 1) + ",";
  payload += "\"tds\":" + String((int) dataAkuarium.tds) + ",";
  payload += "\"wifi_rssi\":" + String(WiFi.RSSI()) + ",";
  payload += "\"uptime_seconds\":" + String(millis() / 1000) + ",";
  payload += "\"sensor_status\":\"" + statusSensor() + "\"";
  payload += "}";

  Serial.print("[API] POST ");
  Serial.println(SERVER_URL);
  Serial.print("[API] Payload: ");
  Serial.println(payload);

  const int responseCode = http.POST(payload);

  if (responseCode > 0) {
    const String response = http.getString();
    Serial.print("[API] Response Code: ");
    Serial.println(responseCode);
    Serial.print("[API] Response: ");
    Serial.println(response);

    tampilkanStatusKirim(responseCode == 201 ? "OK" : "ERR");
  } else {
    Serial.print("[API] Gagal POST. Error code: ");
    Serial.println(responseCode);
    tampilkanStatusKirim("ERR");
  }

  http.end();
}
