<?php

namespace App\Http\Controllers;

use App\Models\SensorReading;
use App\Models\WaterQualityThreshold;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $period = $this->periodFromRequest($request);
        $thresholds = $this->thresholds();

        return view('history', compact('period', 'thresholds'));
    }

    public function data(Request $request): JsonResponse
    {
        $period = $this->periodFromRequest($request);
        $columns = ['recorded_at', 'device', 'ph', 'temperature_celsius', 'tds_ppm', 'quality_status', 'warning'];
        $draw = (int) $request->integer('draw', 1);
        $start = max((int) $request->integer('start', 0), 0);
        $length = (int) $request->integer('length', 10);
        $length = min(max($length, 5), 100);
        $search = trim((string) $request->input('search.value', ''));
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderColumn = $columns[$orderColumnIndex] ?? 'recorded_at';
        $orderDirection = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';

        $baseQuery = SensorReading::query()
            ->with('device')
            ->whereBetween('recorded_at', [$period['from'], $period['to']]);

        $recordsTotal = (clone $baseQuery)->count();

        if ($search !== '') {
            $baseQuery->where(function ($query) use ($search): void {
                $query->where('ph', 'like', "%{$search}%")
                    ->orWhere('temperature_celsius', 'like', "%{$search}%")
                    ->orWhere('tds_ppm', 'like', "%{$search}%")
                    ->orWhere('quality_status', 'like', "%{$search}%")
                    ->orWhereHas('device', function ($deviceQuery) use ($search): void {
                        $deviceQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        $recordsFiltered = (clone $baseQuery)->count();

        if ($orderColumn === 'device') {
            $baseQuery
                ->leftJoin('devices', 'sensor_readings.device_id', '=', 'devices.id')
                ->select('sensor_readings.*')
                ->orderBy('devices.name', $orderDirection);
        } elseif (in_array($orderColumn, ['recorded_at', 'ph', 'temperature_celsius', 'tds_ppm', 'quality_status'], true)) {
            $baseQuery->orderBy($orderColumn, $orderDirection);
        } else {
            $baseQuery->latest('recorded_at');
        }

        $readings = $baseQuery
            ->skip($start)
            ->take($length)
            ->get();

        $rows = $readings->map(fn (SensorReading $reading): array => $this->readingRow($reading));

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->values(),
        ]);
    }

    public function chart(Request $request): JsonResponse
    {
        $period = $this->periodFromRequest($request);

        $readings = SensorReading::query()
            ->whereBetween('recorded_at', [$period['from'], $period['to']])
            ->oldest('recorded_at')
            ->get();
        $chartReadings = $readings
            ->when($readings->count() > 600, function ($items) use ($readings) {
                $count = $readings->count();
                $step = max(1, (int) ceil($count / 600));

                return $items->values()->filter(fn ($reading, int $index) => $index % $step === 0 || $index === $count - 1);
            });

        return response()->json([
            'success' => true,
            'message' => 'Data grafik histori berhasil diambil.',
            'data' => [
                'period' => [
                    'from' => $period['from']->toIso8601String(),
                    'to' => $period['to']->toIso8601String(),
                    'from_date' => $period['from_date'],
                    'to_date' => $period['to_date'],
                ],
                'chart' => $chartReadings->map(fn (SensorReading $reading) => [
                    'recorded_at' => $reading->recorded_at->toIso8601String(),
                    'ph' => (float) $reading->ph,
                    'temperature_celsius' => (float) $reading->temperature_celsius,
                    'tds_ppm' => (int) $reading->tds_ppm,
                ])->values(),
                'thresholds' => $this->thresholds(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $period = $this->periodFromRequest($request);
        $filename = 'histori-kualitas-air-'.$period['from_filename'].'-'.$period['to_filename'].'.csv';

        return response()->streamDownload(function () use ($period): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['recorded_at', 'device', 'ph', 'temperature_celsius', 'tds_ppm', 'status', 'warning_summary']);

            SensorReading::query()
                ->with('device')
                ->whereBetween('recorded_at', [$period['from'], $period['to']])
                ->latest('recorded_at')
                ->chunk(200, function ($readings) use ($handle): void {
                    foreach ($readings as $reading) {
                        fputcsv($handle, [
                            $reading->recordedAtLocal()?->format('Y-m-d H:i:s'),
                            $reading->device?->name ?? $reading->device?->code ?? '-',
                            $reading->ph,
                            $reading->temperature_celsius,
                            $reading->tds_ppm,
                            $reading->statusLabel(),
                            $reading->warningSummary(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function periodFromRequest(Request $request): array
    {
        $timezone = config('app.display_timezone', 'Asia/Jakarta');
        $fromInput = trim((string) $request->input('from', ''));
        $toInput = trim((string) $request->input('to', ''));

        $fromLocal = $fromInput !== ''
            ? $this->parseDateTimeInput($fromInput, $timezone, true)
            : CarbonImmutable::now($timezone)->startOfDay();

        $toLocal = $toInput !== ''
            ? $this->parseDateTimeInput($toInput, $timezone, false)
            : CarbonImmutable::now($timezone);

        if ($fromLocal->greaterThan($toLocal)) {
            [$fromLocal, $toLocal] = [$toLocal, $fromLocal];
        }

        return [
            'from' => $fromLocal->setTimezone('UTC'),
            'to' => $toLocal->setTimezone('UTC'),
            'from_local' => $fromLocal,
            'to_local' => $toLocal,
            'from_date' => $fromLocal->toDateString(),
            'to_date' => $toLocal->toDateString(),
            'from_input' => $fromLocal->format('Y-m-d\TH:i:s'),
            'to_input' => $toLocal->format('Y-m-d\TH:i:s'),
            'from_filename' => $fromLocal->format('Ymd-His'),
            'to_filename' => $toLocal->format('Ymd-His'),
        ];
    }

    private function parseDateTimeInput(string $value, string $timezone, bool $startOfDay): CarbonImmutable
    {
        $date = CarbonImmutable::parse($value, $timezone);

        return str_contains($value, ':')
            ? $date
            : ($startOfDay ? $date->startOfDay() : $date->endOfDay());
    }

    private function readingRow(SensorReading $reading): array
    {
        return [
            'recorded_at' => $reading->recordedAtLocal()?->format('d M Y H:i:s'),
            'device' => $reading->device?->name ?? $reading->device?->code ?? '-',
            'ph' => rtrim(rtrim(number_format((float) $reading->ph, 2, '.', ''), '0'), '.').' pH',
            'temperature' => rtrim(rtrim(number_format((float) $reading->temperature_celsius, 2, '.', ''), '0'), '.').' C',
            'tds' => $reading->tds_ppm.' ppm',
            'status' => $reading->statusLabel(),
            'severity' => $reading->severity(),
            'detail' => $reading->warningSummary(),
            'detail_payload' => $this->readingDetailPayload($reading),
        ];
    }

    private function readingDetailPayload(SensorReading $reading): array
    {
        $warnings = collect($reading->warningDetails());

        return [
            'recorded_at' => $reading->recordedAtLocal()?->format('d M Y H:i:s'),
            'device' => $reading->device?->name ?? $reading->device?->code ?? '-',
            'ph' => rtrim(rtrim(number_format((float) $reading->ph, 2, '.', ''), '0'), '.').' pH',
            'temperature' => rtrim(rtrim(number_format((float) $reading->temperature_celsius, 2, '.', ''), '0'), '.').' C',
            'tds' => $reading->tds_ppm.' ppm',
            'status' => $reading->statusLabel(),
            'detail' => $reading->warningSummary(),
            'alerts' => $this->detailParameterRows($reading),
            'recommendation' => $warnings->isEmpty()
                ? 'Kualitas air berada dalam batas normal. Lanjutkan pemantauan rutin.'
                : $warnings->map(fn (array $warning): string => $this->recommendationText($warning))->unique()->implode(' '),
        ];
    }

    private function parameterRows(SensorReading $reading)
    {
        $warnings = collect($reading->warningDetails())->keyBy('parameter');
        $parameters = [
            'ph' => ['label' => 'pH Air', 'value' => (string) $reading->ph, 'unit' => 'pH'],
            'temperature_celsius' => ['label' => 'Suhu Air', 'value' => $reading->temperature_celsius, 'unit' => 'C'],
            'tds_ppm' => ['label' => 'TDS Air', 'value' => $reading->tds_ppm, 'unit' => 'ppm'],
        ];

        return collect($parameters)->map(function (array $parameter, string $key) use ($reading, $warnings): array {
            $warning = $warnings->get($key);
            $severity = $warning['severity'] ?? 'normal';

            return [
                'recorded_at' => $reading->recordedAtLocal()?->format('d M Y H:i:s'),
                'device' => $reading->device?->name ?? $reading->device?->code ?? '-',
                'parameter' => $parameter['label'],
                'value' => $parameter['value'].' '.$parameter['unit'],
                'gap' => $warning ? $this->warningGapText($warning) : '-',
                'status' => $warning ? $reading->statusLabel() : 'Normal',
                'severity' => $severity,
                'detail' => $warning ? $this->warningDetailText($warning) : 'Parameter berada dalam batas normal.',
                'detail_payload' => [
                    'recorded_at' => $reading->recordedAtLocal()?->format('d M Y H:i:s'),
                    'device' => $reading->device?->name ?? $reading->device?->code ?? '-',
                    'parameter' => $parameter['label'],
                    'value' => $parameter['value'].' '.$parameter['unit'],
                    'limit' => $warning ? $this->warningLimitText($warning) : $this->normalLimitText($key),
                    'gap' => $warning ? $this->warningGapText($warning) : '-',
                    'status' => $warning ? $reading->statusLabel() : 'Normal',
                    'detail' => $warning ? $this->warningDetailText($warning) : 'Parameter berada dalam batas normal.',
                    'recommendation' => $warning ? $this->recommendationText($warning) : 'Lanjutkan pemantauan rutin.',
                ],
            ];
        });
    }

    private function detailParameterRows(SensorReading $reading): array
    {
        $warnings = collect($reading->warningDetails())->keyBy('parameter');
        $parameters = [
            'ph' => ['label' => 'pH Air', 'value' => rtrim(rtrim(number_format((float) $reading->ph, 2, '.', ''), '0'), '.'), 'unit' => 'pH'],
            'temperature_celsius' => ['label' => 'Suhu Air', 'value' => rtrim(rtrim(number_format((float) $reading->temperature_celsius, 2, '.', ''), '0'), '.'), 'unit' => 'C'],
            'tds_ppm' => ['label' => 'TDS Air', 'value' => (string) $reading->tds_ppm, 'unit' => 'ppm'],
        ];

        return collect($parameters)->map(function (array $parameter, string $key) use ($warnings): array {
            $warning = $warnings->get($key);
            $severity = $warning['severity'] ?? 'normal';

            return [
                'parameter' => $parameter['label'],
                'value' => $parameter['value'].' '.$parameter['unit'],
                'limit' => $warning ? $this->warningLimitText($warning) : $this->normalLimitText($key),
                'gap' => $warning ? $this->warningGapText($warning) : '-',
                'status' => $warning ? ($severity === 'critical' ? 'Bahaya' : 'Warning') : 'Normal',
                'severity' => $severity,
                'recommendation' => $warning ? $this->recommendationText($warning) : 'Parameter normal. Lanjutkan pemantauan rutin.',
            ];
        })->values()->all();
    }

    private function warningDetailText(array $warning): string
    {
        $value = rtrim(rtrim(number_format($warning['value'], 2, '.', ''), '0'), '.');
        $limit = rtrim(rtrim(number_format($warning['limit'], 2, '.', ''), '0'), '.');
        $direction = match ([$warning['position'] ?? 'outside', $warning['type']]) {
            ['inside', 'low'] => 'mendekati batas minimum',
            ['inside', 'high'] => 'mendekati batas maksimum',
            ['outside', 'low'] => 'di bawah minimum',
            default => 'di atas maksimum',
        };
        $unit = $warning['unit'] ? ' '.$warning['unit'] : '';

        return "{$warning['label']} {$direction}. Nilai terbaca {$value}{$unit}, batas aman {$limit}{$unit}.";
    }

    private function warningGapText(array $warning): string
    {
        $gap = abs((float) $warning['value'] - (float) $warning['limit']);
        $prefix = match ([$warning['position'] ?? 'outside', $warning['type']]) {
            ['inside', 'low'], ['outside', 'high'] => '+',
            default => '-',
        };
        $unit = $warning['unit'] ? ' '.$warning['unit'] : '';

        return $prefix.rtrim(rtrim(number_format($gap, 2, '.', ''), '0'), '.').$unit;
    }

    private function warningLimitText(array $warning): string
    {
        $label = $warning['type'] === 'low' ? 'Minimum' : 'Maksimum';
        $unit = $warning['unit'] ? ' '.$warning['unit'] : '';

        return $label.' '.rtrim(rtrim(number_format((float) $warning['limit'], 2, '.', ''), '0'), '.').$unit;
    }

    private function normalLimitText(string $parameter): string
    {
        $threshold = $this->thresholds()[$parameter] ?? null;

        if (! $threshold) {
            return '-';
        }

        $min = $threshold['min'] !== null ? rtrim(rtrim(number_format((float) $threshold['min'], 2, '.', ''), '0'), '.') : '-';
        $max = $threshold['max'] !== null ? rtrim(rtrim(number_format((float) $threshold['max'], 2, '.', ''), '0'), '.') : '-';
        $unit = $threshold['unit'] ? ' '.$threshold['unit'] : '';

        return "{$min} - {$max}{$unit}";
    }

    private function recommendationText(array $warning): string
    {
        return match ($warning['parameter']) {
            'ph' => $warning['type'] === 'low'
                ? 'Cek sensor pH, pastikan probe stabil, dan ganti sebagian air.'
                : 'Cek sensor pH, pastikan larutan buffer benar, dan stabilkan kualitas air.',
            'temperature_celsius' => $warning['type'] === 'high'
                ? 'Cek suhu ruangan, kurangi sumber panas, dan pastikan sensor suhu terbaca benar.'
                : 'Cek heater atau suhu ruangan, lalu pastikan sensor suhu terpasang baik.',
            'tds_ppm' => $warning['type'] === 'low'
                ? 'Pastikan sensor TDS terendam dan cek koneksi probe.'
                : 'Cek sumber kenaikan TDS, kurangi sisa pakan, dan pertimbangkan penggantian sebagian air.',
            default => 'Periksa sensor dan kondisi air sebelum melakukan tindakan lanjutan.',
        };
    }

    private function thresholds(): array
    {
        return WaterQualityThreshold::query()
            ->get()
            ->mapWithKeys(fn (WaterQualityThreshold $threshold): array => [
                $threshold->parameter => [
                    'min' => $threshold->min_value !== null ? (float) $threshold->min_value : null,
                    'max' => $threshold->max_value !== null ? (float) $threshold->max_value : null,
                    'unit' => $threshold->unit,
                ],
            ])
            ->all();
    }
}
