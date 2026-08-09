<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\WaterQualityThreshold;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $thresholds = [
            [
                'parameter' => 'ph',
                'label' => 'pH Air',
                'unit' => 'pH',
                'min_value' => 6.50,
                'max_value' => 8.50,
                'description' => 'Rentang umum yang aman untuk akuarium air tawar.',
            ],
            [
                'parameter' => 'temperature_celsius',
                'label' => 'Suhu',
                'unit' => 'C',
                'min_value' => 24.00,
                'max_value' => 30.00,
                'description' => 'Suhu air stabil membantu ikan tidak mudah stres.',
            ],
            [
                'parameter' => 'tds_ppm',
                'label' => 'TDS',
                'unit' => 'ppm',
                'min_value' => 100.00,
                'max_value' => 500.00,
                'description' => 'TDS menunjukkan total zat padat terlarut dalam air.',
            ],
        ];

        foreach ($thresholds as $threshold) {
            WaterQualityThreshold::query()->updateOrCreate(
                ['parameter' => $threshold['parameter']],
                $threshold
            );
        }

        Device::query()->firstOrCreate(
            ['code' => 'esp32-aquarium-01'],
            [
                'name' => 'Akuarium Utama',
                'api_key' => 'labrobotika-demo-key',
                'last_seen_at' => now(),
                'wifi_rssi' => -63,
                'uptime_seconds' => 268400,
                'firmware_version' => '1.0.0-demo',
                'sensor_status' => 'OK',
                'power_status' => 'USB',
            ]
        );
    }
}
