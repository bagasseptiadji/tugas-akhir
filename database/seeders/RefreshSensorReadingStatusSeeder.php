<?php

namespace Database\Seeders;

use App\Models\SensorReading;
use App\Models\WaterQualityThreshold;
use Illuminate\Database\Seeder;

class RefreshSensorReadingStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SensorReading::query()->chunkById(100, function ($readings): void {
            foreach ($readings as $reading) {
                $reading->update([
                    'quality_status' => WaterQualityThreshold::statusFor([
                        'ph' => (float) $reading->ph,
                        'temperature_celsius' => (float) $reading->temperature_celsius,
                        'tds_ppm' => (int) $reading->tds_ppm,
                    ]),
                ]);
            }
        });
    }
}
