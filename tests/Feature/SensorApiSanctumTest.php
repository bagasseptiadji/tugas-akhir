<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WaterQualityThreshold;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SensorApiSanctumTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_client_can_read_sensor_history(): void
    {
        Sanctum::actingAs(User::factory()->create());

        WaterQualityThreshold::query()->create([
            'parameter' => 'ph',
            'label' => 'pH Air',
            'unit' => 'pH',
            'min_value' => 6.5,
            'max_value' => 8.5,
        ]);

        $this->postJson('/api/sensor', [
            'device_code' => 'esp32-aquarium-test',
            'ph' => 7.2,
            'suhu' => 27.4,
            'tds' => 310,
        ]);

        $response = $this->getJson('/api/readings');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.tds_ppm', 310);
    }
}
