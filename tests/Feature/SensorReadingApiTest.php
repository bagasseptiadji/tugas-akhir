<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use App\Models\User;
use App\Models\WaterQualityThreshold;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SensorReadingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_esp32_can_store_sensor_reading(): void
    {
        Sanctum::actingAs(User::factory()->create());

        WaterQualityThreshold::query()->create([
            'parameter' => 'ph',
            'label' => 'pH Air',
            'unit' => 'pH',
            'min_value' => 6.5,
            'max_value' => 8.5,
        ]);

        $response = $this->postJson('/api/sensor', [
            'device_code' => 'esp32-aquarium-test',
            'ph' => 7.2,
            'suhu' => 27.4,
            'tds' => 310,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.device.code', 'esp32-aquarium-test')
            ->assertJsonPath('data.quality_status', 'normal');

        $this->assertDatabaseHas('sensor_readings', [
            'tds_ppm' => 310,
            'quality_status' => 'normal',
        ]);
    }

    public function test_out_of_range_value_returns_warning_status(): void
    {
        Sanctum::actingAs(User::factory()->create());

        WaterQualityThreshold::query()->create([
            'parameter' => 'ph',
            'label' => 'pH Air',
            'unit' => 'pH',
            'min_value' => 6.5,
            'max_value' => 8.5,
            'warning_tolerance' => 1,
        ]);

        $response = $this->postJson('/api/sensor', [
            'device_code' => 'esp32-aquarium-test',
            'ph' => 5.8,
            'temperature_celsius' => 27.4,
            'tds_ppm' => 310,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.quality_status', 'warning');

        $this->assertSame('warning', SensorReading::query()->first()->quality_status);
    }

    public function test_sensor_endpoint_requires_sanctum_token(): void
    {
        $response = $this->postJson('/api/sensor', [
            'device_code' => 'esp32-aquarium-test',
            'ph' => 7.2,
            'suhu' => 27.4,
            'tds' => 310,
        ]);

        $response->assertUnauthorized();
    }

    public function test_warning_reading_sends_telegram_alert_when_enabled(): void
    {
        Cache::flush();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);
        config([
            'services.telegram_alert.enabled' => true,
            'services.telegram_alert.bot_token' => 'telegram-test-token',
            'services.telegram_alert.chat_id' => '123456',
            'services.telegram_alert.cooldown_minutes' => 10,
        ]);

        Sanctum::actingAs(User::factory()->create());

        WaterQualityThreshold::query()->create([
            'parameter' => 'ph',
            'label' => 'pH Air',
            'unit' => 'pH',
            'min_value' => 6.5,
            'max_value' => 8.5,
            'warning_tolerance' => 1,
        ]);

        $this->postJson('/api/sensor', [
            'device_code' => 'esp32-aquarium-test',
            'ph' => 5.8,
            'suhu' => 27.4,
            'tds' => 310,
        ])->assertCreated();

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'api.telegram.org/bottelegram-test-token/sendMessage')
                && $request['chat_id'] === '123456'
                && str_contains($request['text'], 'Alert Kualitas Air Akuarium')
                && str_contains($request['text'], 'Status: <b>Warning</b>');
        });
    }

    public function test_normal_reading_does_not_send_telegram_alert(): void
    {
        Cache::flush();
        Http::fake();
        config([
            'services.telegram_alert.enabled' => true,
            'services.telegram_alert.bot_token' => 'telegram-test-token',
            'services.telegram_alert.chat_id' => '123456',
        ]);

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
        ])->assertCreated();

        Http::assertNothingSent();
    }

    public function test_values_inside_normal_range_do_not_create_warning_even_near_limit(): void
    {
        WaterQualityThreshold::query()->create([
            'parameter' => 'ph',
            'label' => 'pH Air',
            'unit' => 'pH',
            'min_value' => 6.5,
            'max_value' => 8.5,
            'warning_tolerance' => 0.5,
        ]);

        WaterQualityThreshold::query()->create([
            'parameter' => 'temperature_celsius',
            'label' => 'Suhu',
            'unit' => 'C',
            'min_value' => 24,
            'max_value' => 30,
            'warning_tolerance' => 0.5,
        ]);

        WaterQualityThreshold::query()->create([
            'parameter' => 'tds_ppm',
            'label' => 'TDS',
            'unit' => 'ppm',
            'min_value' => 100,
            'max_value' => 500,
            'warning_tolerance' => 25,
        ]);

        $warnings = WaterQualityThreshold::warningsFor([
            'ph' => 8.4,
            'temperature_celsius' => 29.6,
            'tds_ppm' => 498,
        ]);

        $this->assertSame([], $warnings);
    }

    public function test_outside_values_use_warning_tolerance_for_severity(): void
    {
        WaterQualityThreshold::query()->create([
            'parameter' => 'temperature_celsius',
            'label' => 'Suhu',
            'unit' => 'C',
            'min_value' => 24,
            'max_value' => 30,
            'warning_tolerance' => 0.5,
        ]);

        $warning = WaterQualityThreshold::warningsFor(['temperature_celsius' => 30.4]);
        $critical = WaterQualityThreshold::warningsFor(['temperature_celsius' => 30.7]);

        $this->assertSame('warning', $warning[0]['severity']);
        $this->assertSame('critical', $critical[0]['severity']);
    }
}
