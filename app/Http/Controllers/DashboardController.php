<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\SensorReading;
use App\Models\WaterQualityThreshold;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        ['period' => $period, 'latest' => $latest, 'previous' => $previous, 'readings' => $readings, 'alertReadings' => $alertReadings, 'summary' => $summary, 'thresholds' => $thresholds, 'hasActiveData' => $hasActiveData] = $this->dashboardData($request);

        return view('dashboard', compact('latest', 'previous', 'readings', 'alertReadings', 'summary', 'period', 'thresholds', 'hasActiveData'));
    }

    public function data(Request $request): JsonResponse
    {
        ['period' => $period, 'latest' => $latest, 'previous' => $previous, 'readings' => $readings, 'alertReadings' => $alertReadings, 'summary' => $summary, 'thresholds' => $thresholds, 'hasActiveData' => $hasActiveData] = $this->dashboardData($request);

        return response()->json([
            'success' => true,
            'message' => 'Data dashboard berhasil diambil.',
            'data' => [
                'period' => [
                    'from' => $period['from']->toIso8601String(),
                    'to' => $period['to']->toIso8601String(),
                    'from_date' => $period['from_date'],
                    'to_date' => $period['to_date'],
                    'range' => $period['range'],
                ],
                'latest' => $latest ? $this->readingPayload($latest, $previous) : null,
                'summary' => $summary,
                'thresholds' => $thresholds,
                'chart' => $readings->map(fn (SensorReading $reading) => [
                    'recorded_at' => $reading->recorded_at->toIso8601String(),
                    'ph' => (float) $reading->ph,
                    'temperature_celsius' => (float) $reading->temperature_celsius,
                    'tds_ppm' => (int) $reading->tds_ppm,
                ])->values(),
                'history' => $alertReadings->map(fn (SensorReading $reading) => $this->historyPayload($reading))->values(),
            ],
        ]);
    }

    private function dashboardData(Request $request): array
    {
        $period = $this->periodFromRequest($request);

        $latest = SensorReading::query()
            ->with('device')
            ->latest('recorded_at')
            ->first();

        $previous = SensorReading::query()
            ->where('id', '!=', $latest?->id)
            ->latest('recorded_at')
            ->first();

        $readings = SensorReading::query()
            ->with('device')
            ->whereBetween('recorded_at', [$period['from'], $period['to']])
            ->latest('recorded_at')
            ->limit(300)
            ->get()
            ->reverse()
            ->values();

        $alertReadings = SensorReading::query()
            ->with('device')
            ->whereBetween('recorded_at', [$period['from'], $period['to']])
            ->latest('recorded_at')
            ->limit(5)
            ->get();

        $summary = [
            'devices' => Device::query()->count(),
            'active_devices' => Device::query()->where('last_seen_at', '>=', now()->subMinutes(Device::offlineTimeoutMinutes()))->count(),
            'readings_period' => SensorReading::query()->whereBetween('recorded_at', [$period['from'], $period['to']])->count(),
            'warnings_period' => SensorReading::query()->whereBetween('recorded_at', [$period['from'], $period['to']])->where('quality_status', 'warning')->count(),
            'critical_period' => SensorReading::query()
                ->whereBetween('recorded_at', [$period['from'], $period['to']])
                ->where('quality_status', 'warning')
                ->get()
                ->filter(fn (SensorReading $reading): bool => $reading->severity() === 'critical')
                ->count(),
            'last_sync' => $latest?->recorded_at,
        ];

        $summary['device_status'] = match (true) {
            $latest?->device?->isOnline() => 'online',
            $summary['readings_period'] === 0 => 'not_in_use',
            default => 'offline',
        };

        $hasActiveData = $summary['device_status'] === 'online';

        $summary['today_status'] = match (true) {
            $summary['critical_period'] > 0 => 'Bahaya',
            $summary['warnings_period'] > 0 => 'Perlu Dipantau',
            $summary['readings_period'] > 0 => 'Stabil',
            default => 'Menunggu Data',
        };

        $summary['today_message'] = match ($summary['today_status']) {
            'Bahaya' => 'Ada pembacaan bahaya dalam 24 jam terakhir.',
            'Perlu Dipantau' => 'Ada parameter yang sempat melewati batas normal.',
            'Stabil' => 'Belum ada alert dalam 24 jam terakhir.',
            default => 'Belum ada data sensor dalam 24 jam terakhir.',
        };

        $thresholds = WaterQualityThreshold::query()
            ->get()
            ->mapWithKeys(fn (WaterQualityThreshold $threshold): array => [
                $threshold->parameter => [
                    'min' => $threshold->min_value !== null ? (float) $threshold->min_value : null,
                    'max' => $threshold->max_value !== null ? (float) $threshold->max_value : null,
                    'unit' => $threshold->unit,
                    'label' => $this->thresholdLabel($threshold),
                    'warning_tolerance' => (float) $threshold->warning_tolerance,
                    'critical_delta' => (float) $threshold->critical_delta,
                    'alert_enabled' => (bool) $threshold->alert_enabled,
                ],
            ])
            ->all();

        return compact('period', 'latest', 'previous', 'readings', 'alertReadings', 'summary', 'thresholds', 'hasActiveData');
    }

    private function historyPayload(SensorReading $reading): array
    {
        return [
            'recorded_at' => $reading->recorded_at->toIso8601String(),
            'ph' => (float) $reading->ph,
            'temperature_celsius' => (float) $reading->temperature_celsius,
            'tds_ppm' => (int) $reading->tds_ppm,
            'severity' => $reading->severity(),
            'status_label' => $reading->statusLabel(),
            'warning_details' => $reading->warningDetails(),
            'warning_summary' => $reading->warningSummary(),
            'device' => $reading->device ? [
                'name' => $reading->device->name,
                'code' => $reading->device->code,
            ] : null,
        ];
    }

    private function thresholdLabel(WaterQualityThreshold $threshold): string
    {
        $min = $threshold->min_value !== null ? rtrim(rtrim(number_format((float) $threshold->min_value, 2, '.', ''), '0'), '.') : '-';
        $max = $threshold->max_value !== null ? rtrim(rtrim(number_format((float) $threshold->max_value, 2, '.', ''), '0'), '.') : '-';
        $unit = $threshold->unit ? ' '.$threshold->unit : '';

        return "{$min} - {$max}{$unit}";
    }

    public function periodFromRequest(Request $request): array
    {
        $range = $request->string('range', 'today')->toString();
        $timezone = config('app.display_timezone', 'Asia/Jakarta');
        $now = CarbonImmutable::now($timezone);

        [$from, $to] = match ($range) {
            '1h' => [$now->subHour(), $now],
            'today' => [$now->startOfDay(), $now],
            '7d' => [$now->subDays(7)->startOfDay(), $now],
            '30d' => [$now->subDays(30)->startOfDay(), $now],
            'custom' => [
                $request->date('from') ? CarbonImmutable::parse($request->date('from'), $timezone)->startOfDay() : $now->startOfDay(),
                $request->date('to') ? CarbonImmutable::parse($request->date('to'), $timezone)->endOfDay() : $now,
            ],
            default => [$now->startOfDay(), $now],
        };

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        return [
            'from' => $from->setTimezone('UTC'),
            'to' => $to->setTimezone('UTC'),
            'from_local' => $from,
            'to_local' => $to,
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'range' => in_array($range, ['1h', 'today', '7d', '30d', 'custom'], true) ? $range : 'today',
        ];
    }

    private function readingPayload(SensorReading $reading, ?SensorReading $previous): array
    {
        return [
            'id' => $reading->id,
            'ph' => (float) $reading->ph,
            'temperature_celsius' => (float) $reading->temperature_celsius,
            'tds_ppm' => (int) $reading->tds_ppm,
            'recorded_at' => $reading->recorded_at->toIso8601String(),
            'severity' => $reading->severity(),
            'status_label' => $reading->statusLabel(),
            'warning_details' => $reading->warningDetails(),
            'warning_summary' => $reading->warningSummary(),
            'insight' => $reading->insight(),
            'trend' => [
                'ph' => $previous ? round((float) $reading->ph - (float) $previous->ph, 2) : null,
                'temperature_celsius' => $previous ? round((float) $reading->temperature_celsius - (float) $previous->temperature_celsius, 2) : null,
                'tds_ppm' => $previous ? round((int) $reading->tds_ppm - (int) $previous->tds_ppm, 2) : null,
            ],
            'device' => $reading->device ? [
                'id' => $reading->device->id,
                'name' => $reading->device->name,
                'code' => $reading->device->code,
                'last_seen_at' => $reading->device->last_seen_at?->toIso8601String(),
                'is_online' => $reading->device->isOnline(),
                'wifi_rssi' => $reading->device->wifi_rssi,
                'wifi_label' => $reading->device->wifiLabel(),
                'uptime_seconds' => $reading->device->uptime_seconds,
                'uptime_label' => $reading->device->uptimeLabel(),
                'sensor_status' => $reading->device->sensor_status,
                'firmware_version' => $reading->device->firmware_version,
            ] : null,
        ];
    }
}
