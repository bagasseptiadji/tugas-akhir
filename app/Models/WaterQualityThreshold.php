<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterQualityThreshold extends Model
{
    private static $thresholdCache = null;

    protected $fillable = [
        'parameter',
        'label',
        'unit',
        'min_value',
        'max_value',
        'alert_enabled',
        'warning_tolerance',
        'critical_delta',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'min_value' => 'decimal:2',
            'max_value' => 'decimal:2',
            'alert_enabled' => 'boolean',
            'warning_tolerance' => 'decimal:2',
            'critical_delta' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            static::forgetThresholdCache();
        });

        static::deleted(function (): void {
            static::forgetThresholdCache();
        });
    }

    public static function statusFor(array $values): string
    {
        return static::warningsFor($values) === [] ? 'normal' : 'warning';
    }

    public static function warningsFor(array $values): array
    {
        $thresholds = static::thresholdMap();
        $warnings = [];

        foreach ($values as $parameter => $value) {
            $threshold = $thresholds->get($parameter);

            if (! $threshold || $value === null) {
                continue;
            }

            if (! $threshold->alert_enabled) {
                continue;
            }

            if ($threshold->min_value !== null && $value < $threshold->min_value) {
                $gap = abs((float) $value - (float) $threshold->min_value);
                $warnings[] = [
                    'parameter' => $parameter,
                    'label' => $threshold->label,
                    'type' => 'low',
                    'message' => $threshold->label.' terlalu rendah',
                    'value' => (float) $value,
                    'limit' => (float) $threshold->min_value,
                    'unit' => $threshold->unit,
                    'position' => 'outside',
                    'severity' => $threshold->outsideSeverity($gap),
                ];
            }

            if ($threshold->max_value !== null && $value > $threshold->max_value) {
                $gap = abs((float) $value - (float) $threshold->max_value);
                $warnings[] = [
                    'parameter' => $parameter,
                    'label' => $threshold->label,
                    'type' => 'high',
                    'message' => $threshold->label.' terlalu tinggi',
                    'value' => (float) $value,
                    'limit' => (float) $threshold->max_value,
                    'unit' => $threshold->unit,
                    'position' => 'outside',
                    'severity' => $threshold->outsideSeverity($gap),
                ];
            }

        }

        return $warnings;
    }

    public function outsideSeverity(float $gap): string
    {
        $warningLimit = (float) $this->warning_tolerance;

        if ($warningLimit <= 0) {
            return 'critical';
        }

        return round($gap, 2) > $warningLimit ? 'critical' : 'warning';
    }

    public static function forgetThresholdCache(): void
    {
        static::$thresholdCache = null;
    }

    private static function thresholdMap()
    {
        if (static::$thresholdCache === null) {
            static::$thresholdCache = static::query()->get()->keyBy('parameter');
        }

        return static::$thresholdCache;
    }
}
