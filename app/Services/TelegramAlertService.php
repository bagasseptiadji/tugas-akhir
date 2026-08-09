<?php

namespace App\Services;

use App\Models\SensorReading;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramAlertService
{
    public function sendTest(): array
    {
        if (! $this->configured()) {
            return [
                'success' => false,
                'message' => 'Token bot atau Chat ID Telegram belum dikonfigurasi.',
            ];
        }

        try {
            $response = Http::timeout(8)
                ->withOptions($this->httpOptions())
                ->asForm()
                ->post($this->endpoint(), [
                    'chat_id' => config('services.telegram_alert.chat_id'),
                    'text' => "Test notifikasi SmartQua\nKoneksi Telegram berhasil.",
                    'disable_web_page_preview' => true,
                ]);

            if ($response->failed()) {
                Log::warning('Telegram test gagal dikirim.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Test notifikasi gagal dikirim. Periksa token, chat ID, dan koneksi server.',
                ];
            }

            Cache::put('telegram_alert:last_sent_at', now(config('app.display_timezone', 'Asia/Jakarta'))->format('d M Y H:i'), now()->addDays(30));

            return [
                'success' => true,
                'message' => 'Notifikasi test berhasil dikirim.',
            ];
        } catch (Throwable $exception) {
            Log::warning('Telegram test gagal dikirim.', [
                'error' => $this->safeExceptionMessage($exception),
            ]);

            return [
                'success' => false,
                'message' => 'Test notifikasi gagal dikirim. Periksa token, chat ID, dan koneksi server.',
            ];
        }
    }

    public function sendForReading(SensorReading $reading): void
    {
        if (! $this->enabled() || $reading->severity() === 'normal') {
            return;
        }

        $cacheKey = $this->cooldownKey($reading);
        $cooldownSeconds = max((int) config('services.telegram_alert.cooldown_minutes', 10), 1) * 60;

        if (! Cache::add($cacheKey, true, $cooldownSeconds)) {
            return;
        }

        try {
            $response = Http::timeout(5)
                ->withOptions($this->httpOptions())
                ->asForm()
                ->post($this->endpoint(), [
                    'chat_id' => config('services.telegram_alert.chat_id'),
                    'text' => $this->message($reading),
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

            if ($response->failed()) {
                Cache::forget($cacheKey);

                Log::warning('Telegram alert gagal dikirim.', [
                    'reading_id' => $reading->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } else {
                Cache::put('telegram_alert:last_sent_at', now(config('app.display_timezone', 'Asia/Jakarta'))->format('d M Y H:i'), now()->addDays(30));
            }
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);

            Log::warning('Telegram alert gagal dikirim.', [
                'reading_id' => $reading->id,
                'error' => $this->safeExceptionMessage($exception),
            ]);
        }
    }

    public function configured(): bool
    {
        return filled(config('services.telegram_alert.bot_token'))
            && filled(config('services.telegram_alert.chat_id'));
    }

    public function enabled(): bool
    {
        return (bool) Cache::get('telegram_alert:enabled', config('services.telegram_alert.enabled', false))
            && $this->configured();
    }

    public function setEnabled(bool $enabled): void
    {
        Cache::forever('telegram_alert:enabled', $enabled);
    }

    private function endpoint(): string
    {
        return 'https://api.telegram.org/bot'.config('services.telegram_alert.bot_token').'/sendMessage';
    }

    private function safeExceptionMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();
        $token = (string) config('services.telegram_alert.bot_token');

        return $token !== '' ? str_replace($token, '[REDACTED]', $message) : $message;
    }

    private function httpOptions(): array
    {
        $caBundle = config('services.telegram_alert.ca_bundle');

        if (filled($caBundle) && is_file($caBundle)) {
            return ['verify' => $caBundle];
        }

        return [];
    }

    private function cooldownKey(SensorReading $reading): string
    {
        $deviceCode = $reading->device?->code ?? 'unknown-device';
        $summary = md5($reading->warningSummary());

        return "telegram-alert:{$deviceCode}:{$reading->severity()}:{$summary}";
    }

    private function message(SensorReading $reading): string
    {
        $deviceName = $reading->device?->name ?? 'Akuarium';
        $timezone = config('app.display_timezone', 'Asia/Jakarta');
        $recordedAt = $reading->recorded_at?->timezone($timezone)->format('d/m/Y H:i:s') ?? now($timezone)->format('d/m/Y H:i:s');

        return implode("\n", [
            '<b>Alert Kualitas Air Akuarium</b>',
            'Status: <b>'.$reading->statusLabel().'</b>',
            'Perangkat: '.$deviceName,
            'Waktu: '.$recordedAt.' WIB',
            '',
            'pH: '.number_format((float) $reading->ph, 2),
            'Suhu: '.number_format((float) $reading->temperature_celsius, 2).' C',
            'TDS: '.$reading->tds_ppm.' ppm',
            '',
            'Keterangan: '.$reading->warningSummary(),
            'Saran: '.$reading->insight(),
        ]);
    }
}
