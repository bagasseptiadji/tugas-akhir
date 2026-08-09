<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\SensorReading;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VuexyLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_pages_render_the_sidebar_application_shell(): void
    {
        $user = User::factory()->create();

        foreach (['dashboard', 'history', 'settings', 'api-docs', 'api-tokens.index'] as $route) {
            $this->actingAs($user)->get(route($route))
                ->assertOk()
                ->assertSee('id="appSidebar"', false)
                ->assertSee('id="themeToggle"', false)
                ->assertSee('id="app-toast-root"', false)
                ->assertSee('class="app-topbar"', false)
                ->assertSee('class="app-workspace"', false);
        }
    }

    public function test_guest_pages_render_the_compact_guest_shell_without_sidebar(): void
    {
        foreach (['login', 'register'] as $route) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee('id="themeToggle"', false)
                ->assertSee('id="app-toast-root"', false)
                ->assertDontSee('id="appSidebar"', false);
        }
    }

    public function test_dashboard_renders_reference_style_grid_regions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Smart Aquarium Monitor')
            ->assertSee('id="phCard"', false)
            ->assertSee('id="temperatureCard"', false)
            ->assertSee('id="tdsCard"', false)
            ->assertSee('device-status-card', false)
            ->assertSee('trend-card', false)
            ->assertSee('recommendation-card', false);
    }

    public function test_vuexy_auth_pages_preserve_authentication_field_contracts(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="remember"', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_offline_dashboard_keeps_today_chart_and_alert_history(): void
    {
        Cache::flush();
        $this->travelTo(CarbonImmutable::parse('2026-07-19 10:00:00', 'Asia/Jakarta'));

        $user = User::factory()->create();
        $todayReadingTime = CarbonImmutable::now('Asia/Jakarta')->startOfDay()->addHour()->setTimezone('UTC');
        $device = Device::query()->create([
            'code' => 'esp32-aquarium-test',
            'name' => 'Akuarium Utama',
            'last_seen_at' => now()->subMinutes(Device::offlineTimeoutMinutes() + 5),
            'wifi_rssi' => -67,
            'sensor_status' => 'OK',
        ]);

        SensorReading::query()->create([
            'device_id' => $device->id,
            'ph' => 7.79,
            'temperature_celsius' => 30.80,
            'tds_ppm' => 200,
            'quality_status' => 'warning',
            'recorded_at' => $todayReadingTime,
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.data'));

        $response
            ->assertOk()
            ->assertJsonPath('data.summary.device_status', 'offline')
            ->assertJsonCount(1, 'data.chart')
            ->assertJsonCount(1, 'data.history');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="phValue">—', false)
            ->assertSee('id="temperatureValue">—', false)
            ->assertSee('id="tdsValue">—', false)
            ->assertSee('Perangkat offline')
            ->assertSee('-67 dBm');
    }
}
