<?php

namespace App\Http\Controllers;

use App\Models\SensorReading;
use App\Models\WaterQualityThreshold;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportExportController extends Controller
{
    public function excel(Request $request): Response
    {
        $period = $this->periodFromRequest($request);
        $readings = $this->readings($period)->get();
        $filename = 'laporan-kualitas-air-'.$period['from_filename'].'-'.$period['to_filename'].'.xls';

        $html = view('exports.history-excel', compact('period', 'readings'))->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function report(Request $request): View
    {
        $period = $this->periodFromRequest($request);
        $readings = $this->readings($period)->get();
        $thresholds = $this->thresholds();

        $chart = $readings
            ->sortBy(fn (SensorReading $reading) => $reading->recorded_at?->timestamp)
            ->when($readings->count() > 600, function ($items) use ($readings) {
                $count = $readings->count();
                $step = max(1, (int) ceil($count / 600));

                return $items->values()->filter(fn ($reading, int $index) => $index % $step === 0 || $index === $count - 1);
            })
            ->map(fn (SensorReading $reading) => [
                'recorded_at' => $reading->recorded_at->toIso8601String(),
                'ph' => (float) $reading->ph,
                'temperature_celsius' => (float) $reading->temperature_celsius,
                'tds_ppm' => (int) $reading->tds_ppm,
            ])->values();

        return view('reports.history-print', compact('period', 'readings', 'chart', 'thresholds'));
    }

    private function readings(array $period)
    {
        return SensorReading::query()
            ->with('device')
            ->whereBetween('recorded_at', [$period['from'], $period['to']])
            ->latest('recorded_at');
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
