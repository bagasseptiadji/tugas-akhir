<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\SensorReading;
use App\Models\WaterQualityThreshold;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class HistoricalSensorReadingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $device = Device::query()->firstOrCreate(
            ['code' => 'esp32-aquarium-01'],
            [
                'name' => 'Akuarium Utama',
                'api_key' => 'labrobotika-demo-key',
            ]
        );

        $start = CarbonImmutable::today()->subMonths(4)->startOfDay();
        $end = CarbonImmutable::now();

        $current = $start;
        $rows = [];

        while ($current->lessThanOrEqualTo($end)) {
            foreach ([6, 12, 18] as $hour) {
                $recordedAt = $current->setTime($hour, random_int(0, 45));

                if ($recordedAt->greaterThan($end)) {
                    continue;
                }

                if (SensorReading::query()
                    ->where('device_id', $device->id)
                    ->where('recorded_at', $recordedAt)
                    ->exists()) {
                    continue;
                }

                $dayIndex = $start->diffInDays($recordedAt);
                $seasonal = sin($dayIndex / 12);
                $daily = cos($hour / 5);

                $ph = 7.15 + ($seasonal * 0.42) + (random_int(-18, 18) / 100);
                $temperature = 27.1 + ($daily * 1.15) + ($seasonal * 0.45) + (random_int(-25, 25) / 100);
                $tds = 305 + (int) round($seasonal * 70) + random_int(-24, 24);

                if ($dayIndex % 29 === 0 && $hour === 12) {
                    $ph = 6.18 + random_int(-8, 8) / 100;
                }

                if ($dayIndex % 37 === 0 && $hour === 18) {
                    $tds = 540 + random_int(0, 80);
                }

                if ($dayIndex % 43 === 0 && $hour === 6) {
                    $temperature = 31.2 + random_int(0, 50) / 100;
                }

                $status = WaterQualityThreshold::statusFor([
                    'ph' => $ph,
                    'temperature_celsius' => $temperature,
                    'tds_ppm' => $tds,
                ]);

                $rows[] = [
                    'device_id' => $device->id,
                    'ph' => round($ph, 2),
                    'temperature_celsius' => round($temperature, 2),
                    'tds_ppm' => max(0, $tds),
                    'quality_status' => $status,
                    'raw_payload' => json_encode(['source' => 'historical-dump']),
                    'recorded_at' => $recordedAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($rows) >= 250) {
                    SensorReading::query()->insert($rows);
                    $rows = [];
                }
            }

            $current = $current->addDay();
        }

        if ($rows !== []) {
            SensorReading::query()->insert($rows);
        }

        $device->update(['last_seen_at' => now()]);
        $device->update([
            'wifi_rssi' => -63,
            'uptime_seconds' => 268400,
            'firmware_version' => '1.0.0-demo',
            'sensor_status' => 'OK',
            'power_status' => 'USB',
        ]);
    }
}
