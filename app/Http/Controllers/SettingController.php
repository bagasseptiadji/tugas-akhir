<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\SensorReading;
use App\Models\WaterQualityThreshold;
use App\Services\TelegramAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __invoke(TelegramAlertService $telegramAlertService): View
    {
        $thresholds = WaterQualityThreshold::query()
            ->orderByRaw("case parameter when 'ph' then 1 when 'temperature_celsius' then 2 when 'tds_ppm' then 3 else 4 end")
            ->get();

        $totalReadings = SensorReading::query()->count();

        return view('settings', [
            'thresholds' => $thresholds,
            'device' => Device::query()->latest('last_seen_at')->first(),
            'offlineTimeoutMinutes' => Device::offlineTimeoutMinutes(),
            'totalReadings' => $totalReadings,
            'telegramAlert' => [
                'enabled' => $telegramAlertService->enabled(),
                'configured' => $telegramAlertService->configured(),
                'cooldown_minutes' => config('services.telegram_alert.cooldown_minutes', 10),
                'last_sent_at' => cache('telegram_alert:last_sent_at'),
            ],
        ]);
    }

    public function testTelegram(Request $request, TelegramAlertService $telegramAlertService): RedirectResponse|JsonResponse
    {
        $result = $telegramAlertService->sendTest();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ], $result['success'] ? 200 : 422);
        }

        return back()->with($result['success'] ? 'status' : 'error', $result['message']);
    }

    public function toggleTelegram(Request $request, TelegramAlertService $telegramAlertService): RedirectResponse|JsonResponse
    {
        $enabled = $request->boolean('enabled');

        if ($enabled && ! $telegramAlertService->configured()) {
            $message = 'Token bot atau Chat ID Telegram belum dikonfigurasi.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'enabled' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }

        $telegramAlertService->setEnabled($enabled);

        $message = $enabled
            ? 'Notifikasi Telegram diaktifkan.'
            : 'Notifikasi Telegram dinonaktifkan.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'enabled' => $telegramAlertService->enabled(),
                'message' => $message,
            ]);
        }

        return back()->with('status', $message);
    }

    public function updateDevice(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'device_name' => ['required', 'string', 'max:120'],
            'offline_timeout_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ], [
            'device_name.required' => 'Nama perangkat wajib diisi.',
            'offline_timeout_minutes.required' => 'Timeout offline wajib diisi.',
            'offline_timeout_minutes.integer' => 'Timeout offline harus berupa angka menit.',
            'offline_timeout_minutes.min' => 'Timeout offline minimal 1 menit.',
            'offline_timeout_minutes.max' => 'Timeout offline maksimal 1440 menit.',
        ]);

        $device = Device::query()->latest('last_seen_at')->first()
            ?? Device::query()->firstOrCreate(['code' => 'esp32-aquarium-01']);

        $device->update([
            'name' => $validated['device_name'],
        ]);

        Cache::forever('device_offline_timeout_minutes', (int) $validated['offline_timeout_minutes']);

        $message = 'Pengaturan perangkat berhasil disimpan.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return back()->with('status', $message);
    }
}
