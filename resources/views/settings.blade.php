@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
    @php
        $thresholdMap = $thresholds->keyBy('parameter');
        $parameterMeta = [
            'ph' => ['title' => 'pH Air', 'icon' => 'bi-droplet', 'color' => ''],
            'temperature_celsius' => ['title' => 'Suhu', 'icon' => 'bi-thermometer-half', 'color' => 'orange'],
            'tds_ppm' => ['title' => 'TDS', 'icon' => 'bi-water', 'color' => 'green'],
        ];
    @endphp

    <style>
        .settings-page {
            display: grid;
            gap: 1rem;
        }

        .settings-section-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.15rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--app-border);
        }

        .settings-section-title {
            font-size: 1.08rem;
            font-weight: 800;
            margin-bottom: .25rem;
        }

        .settings-soft-box {
            border: 1px solid var(--app-border);
            border-radius: 14px;
            background: var(--app-surface-2);
            padding: 1rem;
        }

        .settings-group-title {
            color: var(--app-text);
            font-size: .92rem;
            font-weight: 800;
            margin-bottom: .85rem;
        }

        .settings-help {
            color: var(--app-muted);
            font-size: .84rem;
            line-height: 1.55;
        }

        .setting-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .82rem 0;
            border-bottom: 1px solid var(--app-border);
        }

        .setting-row:last-child {
            border-bottom: 0;
        }

        .setting-row strong {
            color: var(--app-text);
        }

        .settings-footer {
            display: flex;
            justify-content: flex-end;
            gap: .6rem;
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid var(--app-border);
        }

        @media (max-width: 768px) {
            .settings-section-head,
            .settings-footer {
                align-items: stretch;
                flex-direction: column;
            }
        }
    </style>

    @if ($errors->any())
        <div class="alert alert-danger rounded-4 border-0 shadow-sm">
            <strong>Pengaturan belum bisa disimpan.</strong>
            <div class="small mt-1">{{ $errors->first() }}</div>
        </div>
    @endif

    <div class="settings-page">
        <form method="POST" action="{{ route('settings.device.update') }}" class="js-settings-form">
            @csrf
            @method('PUT')

            <section class="app-card p-4">
                <div class="settings-section-head">
                    <div class="d-flex gap-3">
                        <div>
                            <h1 class="settings-section-title">Pengaturan Perangkat</h1>
                            <p class="muted mb-0">Nama perangkat untuk dashboard dan batas waktu perangkat dianggap offline.</p>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-8">
                        <label class="form-label small fw-bold muted">Nama Perangkat</label>
                        <input class="form-control" type="text" name="device_name" value="{{ old('device_name', $device?->name ?? 'Akuarium Utama') }}" maxlength="120" required>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label small fw-bold muted">Timeout Offline (menit)</label>
                        <input class="form-control" type="number" name="offline_timeout_minutes" min="1" max="1440" value="{{ old('offline_timeout_minutes', $offlineTimeoutMinutes) }}" required>
                        <div class="settings-help mt-2">Jika tidak ada data baru melewati waktu ini, ESP32 dianggap offline.</div>
                    </div>
                </div>

                <div class="settings-footer">
                    <button class="ui-btn ui-btn-primary js-save-button" type="submit">
                        <i class="bi bi-check2-circle"></i>Simpan Pengaturan Perangkat
                    </button>
                </div>
            </section>
        </form>

        <form method="POST" action="{{ route('thresholds.update') }}" class="js-settings-form">
            @csrf
            @method('PUT')

            <section class="app-card p-4">
                <div class="settings-section-head">
                    <div class="d-flex gap-3">
                        <div>
                            <h1 class="settings-section-title">Sensor</h1>
                            <p class="muted mb-0">Atur batas normal pembacaan pH, suhu, dan TDS.</p>
                        </div>
                    </div>
                    <a class="ui-btn align-self-start" href="{{ route('api-docs') }}">
                        <i class="bi bi-journal-code"></i>Buka Dokumentasi API
                    </a>
                </div>

                <div class="row g-3">
                    @foreach ($thresholds as $threshold)
                        @php($meta = $parameterMeta[$threshold->parameter] ?? ['title' => $threshold->label, 'icon' => 'bi-activity', 'color' => ''])
                        <div class="col-xl-4">
                            <div class="settings-soft-box h-100">
                                <div class="d-flex justify-content-between gap-3 mb-3">
                                    <div>
                                        <div class="metric-label mb-1">{{ $threshold->unit }}</div>
                                        <h2 class="h5 fw-bold mb-0">{{ $meta['title'] }}</h2>
                                    </div>
                                    <span class="metric-icon {{ $meta['color'] }}"><i class="bi {{ $meta['icon'] }}"></i></span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold muted">Minimum</label>
                                        <input class="form-control" type="number" step="0.01" name="thresholds[{{ $threshold->id }}][min_value]" value="{{ old("thresholds.{$threshold->id}.min_value", $threshold->min_value) }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold muted">Maksimum</label>
                                        <input class="form-control" type="number" step="0.01" name="thresholds[{{ $threshold->id }}][max_value]" value="{{ old("thresholds.{$threshold->id}.max_value", $threshold->max_value) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="settings-footer">
                    <button class="ui-btn ui-btn-primary js-save-button" type="submit">
                        <i class="bi bi-check2-circle"></i>Simpan Pengaturan Sensor
                    </button>
                </div>
            </section>
        </form>

        <form method="POST" action="{{ route('thresholds.update') }}" class="js-settings-form">
            @csrf
            @method('PUT')

            <section class="app-card p-4">
                <div class="settings-section-head">
                    <div class="d-flex gap-3">
                        <div>
                            <h1 class="settings-section-title">Status Alert</h1>
                            <p class="muted mb-0">Normal mengikuti batas sensor. Jika nilai keluar batas, selisih sampai Batas Warning ditandai Warning; jika lebih dari itu ditandai Bahaya.</p>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    @foreach ($parameterMeta as $parameter => $meta)
                        @php($threshold = $thresholdMap->get($parameter))
                        <div class="col-lg-4">
                            <div class="settings-soft-box h-100">
                                <div class="d-flex justify-content-between gap-3 mb-3">
                                    <div>
                                        <h2 class="h5 fw-bold mb-1">{{ $meta['title'] }}</h2>
                                        <span class="status-pill {{ $threshold?->alert_enabled ? 'status-normal' : 'status-warning' }}" data-alert-status-pill>{{ $threshold?->alert_enabled ? 'Aktif' : 'Nonaktif' }}</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="thresholds[{{ $threshold?->id }}][alert_enabled]" value="0">
                                        <input class="form-check-input js-alert-enabled-toggle" type="checkbox" name="thresholds[{{ $threshold?->id }}][alert_enabled]" value="1" @checked($threshold?->alert_enabled)>
                                    </div>
                                </div>

                                <div class="setting-row">
                                    <span class="muted">Normal</span>
                                    <strong>{{ $threshold?->min_value }} - {{ $threshold?->max_value }} {{ $threshold?->unit }}</strong>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label small fw-bold muted">Batas Warning</label>
                                    <input class="form-control" type="number" step="0.01" min="0" name="thresholds[{{ $threshold?->id }}][warning_tolerance]" value="{{ old("thresholds.{$threshold?->id}.warning_tolerance", $threshold?->warning_tolerance ?? 0) }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="settings-footer">
                    <button class="ui-btn ui-btn-primary js-save-button" type="submit">
                        <i class="bi bi-check2-circle"></i>Simpan Pengaturan Alert
                    </button>
                </div>
            </section>
        </form>

        <section class="app-card p-4">
            <div class="settings-section-head">
                <div class="d-flex gap-3">
                    <div>
                        <h1 class="settings-section-title">Notifikasi</h1>
                        <p class="muted mb-0">Aktifkan atau matikan pesan alert ke bot Telegram.</p>
                    </div>
                </div>
            </div>

            <div class="settings-soft-box">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div>
                        <div class="settings-group-title mb-2">Status Telegram</div>
                        <span class="status-pill {{ $telegramAlert['enabled'] ? 'status-normal' : 'status-warning' }}" id="telegramStatusBadge">
                            {{ $telegramAlert['enabled'] ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        <p class="settings-help mb-0 mt-2">Jika aktif, alert Warning dan Bahaya akan dikirim ke Telegram.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('settings.telegram.toggle') }}" class="js-telegram-toggle-form">
                            @csrf
                            <input type="hidden" name="enabled" id="telegramToggleValue" value="{{ $telegramAlert['enabled'] ? 0 : 1 }}">
                            <button class="ui-btn {{ $telegramAlert['enabled'] ? 'ui-btn-danger' : 'ui-btn-primary' }}" id="telegramToggleButton" type="submit">
                                {{ $telegramAlert['enabled'] ? 'Matikan Notifikasi' : 'Aktifkan Notifikasi' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('settings.telegram.test') }}" class="js-telegram-test-form">
                            @csrf
                            <button class="ui-btn js-telegram-test-button" type="submit">Test Notifikasi</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>

@endsection

@push('scripts')
<script>
    function showSettingsToast(message = 'Pengaturan berhasil disimpan.', isError = false) {
        window.appToast?.update('settings-request', { title: message, type: isError ? 'error' : 'success' });
    }



    function setButtonLoading(button, loading, loadingText = 'Menyimpan...') {
        if (!button) return;
        if (loading) {
            window.appToast?.loading(loadingText, { id: 'settings-request', duration: 4200 });
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = `<span class="spinner-border spinner-border-sm"></span>${loadingText}`;
            return;
        }

        button.disabled = false;
        button.innerHTML = button.dataset.originalHtml || button.innerHTML;
    }

    document.querySelectorAll('.js-telegram-test-form').forEach(form => {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const button = form.querySelector('.js-telegram-test-button');
            setButtonLoading(button, true, 'Mengirim...');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const body = await response.json().catch(() => ({}));

                showSettingsToast(body.message || (response.ok ? 'Notifikasi test berhasil dikirim.' : 'Notifikasi test gagal dikirim.'), !response.ok);
            } catch (error) {
                showSettingsToast('Gagal mengirim test notifikasi Telegram.', true);
            } finally {
                setButtonLoading(button, false);
            }
        });
    });

    document.querySelectorAll('.js-telegram-toggle-form').forEach(form => {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const button = form.querySelector('#telegramToggleButton');
            const toggleValue = form.querySelector('#telegramToggleValue');
            const badge = document.getElementById('telegramStatusBadge');
            setButtonLoading(button, true, 'Memproses...');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    showSettingsToast(body.message || 'Status Telegram gagal diubah.', true);
                    return;
                }

                const enabled = Boolean(body.enabled);
                setButtonLoading(button, false);

                if (badge) {
                    badge.textContent = enabled ? 'Aktif' : 'Nonaktif';
                    badge.classList.toggle('status-normal', enabled);
                    badge.classList.toggle('status-warning', !enabled);
                }

                if (toggleValue) {
                    toggleValue.value = enabled ? '0' : '1';
                }

                if (button) {
                    button.classList.toggle('ui-btn-danger', enabled);
                    button.classList.toggle('ui-btn-primary', !enabled);
                    button.textContent = enabled ? 'Matikan Notifikasi' : 'Aktifkan Notifikasi';
                    button.dataset.originalHtml = button.innerHTML;
                }

                showSettingsToast(body.message || 'Status Telegram berhasil diubah.');
            } catch (error) {
                showSettingsToast('Status Telegram gagal diubah.', true);
            } finally {
                setButtonLoading(button, false);
            }
        });
    });

    document.querySelectorAll('.js-settings-form').forEach(form => {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const button = form.querySelector('.js-save-button');
            setButtonLoading(button, true);

            try {
                const formData = new FormData(form);
                for (const [key, value] of formData.entries()) {
                    if (typeof value === 'string' && /^thresholds\[\d+\]\[(min_value|max_value|warning_tolerance)\]$/.test(key)) {
                        formData.set(key, value.replace(',', '.'));
                    }
                }

                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    showSettingsToast(body.message || 'Pengaturan belum bisa disimpan.', true);
                    return;
                }

                form.querySelectorAll('.js-alert-enabled-toggle').forEach(toggle => {
                    const pill = toggle.closest('.settings-soft-box')?.querySelector('[data-alert-status-pill]');
                    if (!pill) return;

                    pill.textContent = toggle.checked ? 'Aktif' : 'Nonaktif';
                    pill.classList.toggle('status-normal', toggle.checked);
                    pill.classList.toggle('status-warning', !toggle.checked);
                });

                showSettingsToast(body.message || 'Pengaturan berhasil disimpan.');
            } catch (error) {
                showSettingsToast('Gagal menyimpan pengaturan. Coba lagi.', true);
            } finally {
                setButtonLoading(button, false);
            }
        });
    });
</script>
@endpush
