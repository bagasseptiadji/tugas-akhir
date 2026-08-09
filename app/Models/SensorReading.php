<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends Model
{
    protected $fillable = [
        'device_id',
        'ph',
        'temperature_celsius',
        'tds_ppm',
        'quality_status',
        'raw_payload',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'ph' => 'decimal:2',
            'temperature_celsius' => 'decimal:2',
            'tds_ppm' => 'integer',
            'raw_payload' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function scopeRecent($query, int $limit = 30)
    {
        return $query->latest('recorded_at')->limit($limit);
    }

    public function recordedAtLocal(): ?CarbonInterface
    {
        return $this->recorded_at?->copy()->timezone(config('app.display_timezone', 'Asia/Jakarta'));
    }

    public function warningDetails(): array
    {
        return WaterQualityThreshold::warningsFor([
            'ph' => (float) $this->ph,
            'temperature_celsius' => (float) $this->temperature_celsius,
            'tds_ppm' => (int) $this->tds_ppm,
        ]);
    }

    public function warningSummary(): string
    {
        $warnings = $this->warningDetails();

        if ($warnings === []) {
            return 'Semua parameter dalam batas normal.';
        }

        return collect($warnings)
            ->map(function (array $warning): string {
                $value = rtrim(rtrim(number_format($warning['value'], 2, '.', ''), '0'), '.');
                $limit = rtrim(rtrim(number_format($warning['limit'], 2, '.', ''), '0'), '.');
                $direction = match ([$warning['position'] ?? 'outside', $warning['type']]) {
                    ['inside', 'low'] => 'mendekati batas minimum',
                    ['inside', 'high'] => 'mendekati batas maksimum',
                    ['outside', 'low'] => 'di bawah minimum',
                    default => 'di atas maksimum',
                };
                $unit = $warning['unit'] ? ' '.$warning['unit'] : '';

                return "{$warning['label']} {$direction} ({$value}{$unit}, batas {$limit}{$unit})";
            })
            ->implode('; ');
    }

    public function severity(): string
    {
        $warnings = $this->warningDetails();

        if ($warnings === []) {
            return 'normal';
        }

        foreach ($warnings as $warning) {
            if (($warning['severity'] ?? 'warning') === 'critical') {
                return 'critical';
            }
        }

        return 'warning';
    }

    public function statusLabel(): string
    {
        return match ($this->severity()) {
            'critical' => 'Bahaya',
            'warning' => 'Warning',
            default => 'Normal',
        };
    }

    public function insight(): string
    {
        if ($this->severity() === 'normal') {
            return 'Kualitas air stabil. pH, suhu, dan TDS berada dalam batas normal untuk akuarium.';
        }

        $warnings = collect($this->warningDetails())
            ->map(fn (array $warning): string => $warning['label'])
            ->unique()
            ->implode(', ');

        if ($this->severity() === 'critical') {
            return "Kondisi air membutuhkan tindakan cepat. Parameter bermasalah: {$warnings}. Periksa sensor, lakukan pengecekan air, dan pertimbangkan penggantian sebagian air.";
        }

        return "Kondisi air perlu dipantau lebih dekat. Parameter yang mulai melewati batas normal: {$warnings}.";
    }
}
