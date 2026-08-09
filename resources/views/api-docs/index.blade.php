@extends('layouts.app')

@section('title', 'Dokumentasi API ESP32')

@section('content')
    <div class="api-docs-page">
    <style>
        .method-badge {
            border-radius: 999px;
            padding: .34rem .64rem;
            color: #fff;
            background: var(--app-primary);
            font-size: .78rem;
            font-weight: 800;
        }

        .code-panel {
            position: relative;
        }

        .code-panel pre {
            max-height: 480px;
            background: #101820;
            color: #e8f3f7;
            overflow: auto;
        }

        .code-copy {
            position: absolute;
            top: .65rem;
            right: .65rem;
            border-color: rgba(255, 255, 255, .22);
            color: #e8f3f7;
            background: rgba(255, 255, 255, .08);
        }
    </style>

    <section class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="app-card p-4 h-100">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <span class="metric-icon green"><i class="bi bi-send"></i></span>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="method-badge">POST</span>
                            <h2 class="h5 fw-bold mb-0">/api/sensor</h2>
                        </div>
                        <p class="small muted mb-0">Menerima data sensor pH, suhu, dan TDS dari ESP32.</p>
                    </div>
                </div>
                <p class="settings-help mb-3">Endpoint utama yang dipakai ESP32 untuk mengirim pembacaan sensor. Gunakan token dari menu Token API.</p>
                <ul class="nav nav-pills gap-2 mb-3" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#postRequest" type="button">Request</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#postResponse" type="button">Response</button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="postRequest">
                        <div class="code-panel">
                            <button class="ui-btn ui-btn-sm code-copy" type="button" data-copy-target="postRequestCode">Copy</button>
                            <pre class="rounded-4 p-3 mb-0"><code id="postRequestCode">Authorization: Bearer TOKEN_SANCTUM
Content-Type: application/json

{
  "device_code": "esp32-aquarium-01",
  "device_name": "Akuarium Utama",
  "ph": 7.25,
  "suhu": 27.80,
  "tds": 318,
  "wifi_rssi": -63,
  "uptime_seconds": 268400
}</code></pre>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="postResponse">
                        <div class="code-panel">
                            <button class="ui-btn ui-btn-sm code-copy" type="button" data-copy-target="postResponseCode">Copy</button>
                            <pre class="rounded-4 p-3 mb-0"><code id="postResponseCode">{
  "success": true,
  "message": "Data sensor berhasil disimpan.",
  "data": {
    "ph": 7.25,
    "temperature_celsius": 27.80,
    "tds_ppm": 318,
    "quality_status": "normal"
  }
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="app-card p-4 h-100">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <span class="metric-icon"><i class="bi bi-list-ul"></i></span>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="method-badge">GET</span>
                            <h2 class="h5 fw-bold mb-0">/api/readings</h2>
                        </div>
                        <p class="small muted mb-0">Mengambil histori pembacaan sensor terbaru.</p>
                    </div>
                </div>
                <p class="settings-help mb-3">Endpoint opsional untuk mengecek data terbaru melalui Postman atau aplikasi lain.</p>
                <ul class="nav nav-pills gap-2 mb-3" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#getRequest" type="button">Request</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#getResponse" type="button">Response</button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="getRequest">
                        <div class="code-panel">
                            <button class="ui-btn ui-btn-sm code-copy" type="button" data-copy-target="getRequestCode">Copy</button>
                            <pre class="rounded-4 p-3 mb-0"><code id="getRequestCode">GET /api/readings?limit=50&device_code=esp32-aquarium-01
Authorization: Bearer TOKEN_SANCTUM</code></pre>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="getResponse">
                        <div class="code-panel">
                            <button class="ui-btn ui-btn-sm code-copy" type="button" data-copy-target="getResponseCode">Copy</button>
                            <pre class="rounded-4 p-3 mb-0"><code id="getResponseCode">{
  "success": true,
  "message": "Data pembacaan sensor berhasil diambil.",
  "data": [],
  "meta": {
    "limit": 50,
    "count": 0
  }
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="app-card p-4">
        <h2 class="h5 fw-bold mb-3">Contoh Firmware ESP32</h2>
        <div class="code-panel">
            <button class="ui-btn ui-btn-sm code-copy" type="button" data-copy-target="firmwareCode">Copy</button>
            <pre class="rounded-4 p-3 mb-0"><code id="firmwareCode">HTTPClient http;
http.begin("http://IP_SERVER:8000/api/sensor");
http.addHeader("Content-Type", "application/json");
http.addHeader("Authorization", "Bearer TOKEN_SANCTUM");

String json = "{\"device_code\":\"esp32-aquarium-01\",\"ph\":7.25,\"suhu\":27.80,\"tds\":318}";
int statusCode = http.POST(json);
String response = http.getString();
http.end();</code></pre>
        </div>
    </section>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('click', async event => {
        const button = event.target.closest('[data-copy-target]');
        if (!button) return;
        const target = document.getElementById(button.dataset.copyTarget);
        if (!target) return;
        await navigator.clipboard.writeText(target.textContent.trim());
        button.textContent = 'Copied';
        setTimeout(() => button.textContent = 'Copy', 1500);
    });
</script>
@endpush
