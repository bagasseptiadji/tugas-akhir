<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Device extends Model
{
    protected $fillable = [
        'code',
        'name',
        'api_key',
        'last_seen_at',
        'wifi_rssi',
        'uptime_seconds',
        'firmware_version',
        'sensor_status',
        'power_status',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'wifi_rssi' => 'integer',
            'uptime_seconds' => 'integer',
        ];
    }

    public function readings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at?->greaterThanOrEqualTo(now()->subMinutes($this->offlineTimeoutMinutes())) ?? false;
    }

    public static function offlineTimeoutMinutes(): int
    {
        return max((int) Cache::get('device_offline_timeout_minutes', 10), 1);
    }

    public function uptimeLabel(): string
    {
        if (! $this->uptime_seconds) {
            return '-';
        }

        $days = intdiv($this->uptime_seconds, 86400);
        $hours = intdiv($this->uptime_seconds % 86400, 3600);

        if ($days > 0) {
            return "{$days} hari {$hours} jam";
        }

        return "{$hours} jam";
    }

    public function wifiLabel(): string
    {
        return $this->wifi_rssi ? "{$this->wifi_rssi} dBm" : '-';
    }
}
