<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSensorReadingRequest;
use App\Models\Device;
use App\Models\SensorReading;
use App\Models\WaterQualityThreshold;
use App\Services\TelegramAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SensorReadingController extends Controller
{
    public function __construct(private readonly TelegramAlertService $telegramAlertService) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 50);
        $limit = min(max($limit, 1), 200);

        $readings = SensorReading::query()
            ->with('device:id,code,name,last_seen_at,wifi_rssi,uptime_seconds,firmware_version,sensor_status,power_status')
            ->when($request->filled('device_code'), function ($query) use ($request) {
                $query->whereHas('device', fn ($device) => $device->where('code', $request->string('device_code')));
            })
            ->latest('recorded_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data pembacaan sensor berhasil diambil.',
            'data' => $readings->map(fn (SensorReading $reading) => $this->readingResponse($reading)),
            'meta' => [
                'limit' => $limit,
                'count' => $readings->count(),
            ],
        ]);
    }

    public function store(StoreSensorReadingRequest $request): JsonResponse
    {
        $payload = $this->normalizePayload($request);

        $device = Device::query()->firstOrCreate(
            ['code' => $payload['device_code']],
            [
                'name' => $payload['device_name'] ?? 'Akuarium Utama',
                'api_key' => $payload['api_key'] ?? null,
            ]
        );

        $device->update([
            'name' => $payload['device_name'] ?? $device->name,
            'last_seen_at' => now(),
            'wifi_rssi' => $payload['wifi_rssi'] ?? $device->wifi_rssi,
            'uptime_seconds' => $payload['uptime_seconds'] ?? $device->uptime_seconds,
            'firmware_version' => $payload['firmware_version'] ?? $device->firmware_version,
            'sensor_status' => $payload['sensor_status'] ?? $device->sensor_status,
            'power_status' => $payload['power_status'] ?? $device->power_status,
        ]);

        $sensorValues = [
            'ph' => (float) $payload['ph'],
            'temperature_celsius' => (float) $payload['temperature_celsius'],
            'tds_ppm' => (int) $payload['tds_ppm'],
        ];

        $status = WaterQualityThreshold::statusFor($sensorValues);

        $reading = SensorReading::query()->create([
            'device_id' => $device->id,
            'ph' => round($sensorValues['ph'], 2),
            'temperature_celsius' => round($sensorValues['temperature_celsius'], 2),
            'tds_ppm' => round($sensorValues['tds_ppm']),
            'quality_status' => $status,
            'raw_payload' => $request->all(),
            'recorded_at' => $payload['recorded_at'] ?? now(),
        ])->load('device:id,code,name,last_seen_at,wifi_rssi,uptime_seconds,firmware_version,sensor_status,power_status');

        $this->telegramAlertService->sendForReading($reading);

        return response()->json([
            'success' => true,
            'message' => 'Data sensor berhasil disimpan.',
            'data' => $this->readingResponse($reading),
        ], 201);
    }

    private function readingResponse(SensorReading $reading): array
    {
        return [
            ...$reading->toArray(),
            'severity' => $reading->severity(),
            'status_label' => $reading->statusLabel(),
            'warning_details' => $reading->warningDetails(),
            'warning_summary' => $reading->warningSummary(),
            'insight' => $reading->insight(),
        ];
    }

    private function normalizePayload(Request $request): array
    {
        $payload = $request->all();

        return [
            ...$payload,
            'device_code' => $payload['device_code'] ?? $payload['device_id'] ?? $payload['kode_perangkat'] ?? 'esp32-aquarium-01',
            'device_name' => $payload['device_name'] ?? $payload['nama_perangkat'] ?? null,
            'temperature_celsius' => $payload['temperature_celsius'] ?? $payload['temperature'] ?? $payload['suhu'] ?? null,
            'tds_ppm' => $payload['tds_ppm'] ?? $payload['tds'] ?? null,
            'wifi_rssi' => $payload['wifi_rssi'] ?? $payload['rssi'] ?? null,
            'uptime_seconds' => $payload['uptime_seconds'] ?? $payload['uptime'] ?? null,
        ];
    }
}
