@extends('layouts.app')

@section('title', 'Dashboard Monitoring Kualitas Air')
@section('main_class', 'dashboard-clean-main container-fluid px-3 px-lg-4 py-4')

@section('content')
    @php
        $device = $latest?->device;
        $isOnline = $device?->isOnline() ?? false;
        $isNotInUse = ($summary['device_status'] ?? null) === 'not_in_use';
        $hasActiveData = $hasActiveData ?? false;
        $isDashboardUnavailable = ! $hasActiveData;
        $unavailableLabel = $isNotInUse ? 'Tidak digunakan' : 'Offline';
        $unavailableMessage = $isNotInUse
            ? 'Perangkat tidak digunakan hari ini. Nilai lama tetap tersimpan di halaman Histori.'
            : 'Perangkat sedang offline. Data sensor aktif belum tersedia.';
        $severity = $isDashboardUnavailable ? 'inactive' : ($latest?->severity() ?? 'normal');
        $warnings = $isDashboardUnavailable ? collect() : collect($latest?->warningDetails() ?? []);
        $warningMap = $warnings->keyBy('parameter');
        $statusLabel = function (string $parameter) use ($warningMap, $isDashboardUnavailable, $unavailableLabel): string {
            if ($isDashboardUnavailable) {
                return $unavailableLabel;
            }

            $warning = $warningMap->get($parameter);

            if (! $warning) {
                return 'Normal';
            }

            if (($warning['position'] ?? 'outside') === 'inside') {
                return match ([$parameter, $warning['type']]) {
                    ['ph', 'low'] => 'Mendekati Asam',
                    ['ph', 'high'] => 'Mendekati Basa',
                    ['temperature_celsius', 'low'] => 'Mendekati Dingin',
                    ['temperature_celsius', 'high'] => 'Mendekati Panas',
                    ['tds_ppm', 'low'] => 'TDS Mendekati Rendah',
                    ['tds_ppm', 'high'] => 'TDS Mendekati Tinggi',
                    default => 'Mendekati Batas',
                };
            }

            return match ([$parameter, $warning['type']]) {
                ['ph', 'low'] => 'Terlalu Asam',
                ['ph', 'high'] => 'Terlalu Basa',
                ['temperature_celsius', 'high'] => 'Terlalu Panas',
                ['temperature_celsius', 'low'] => 'Terlalu Dingin',
                ['tds_ppm', 'low'] => 'TDS Rendah',
                ['tds_ppm', 'high'] => 'TDS Tinggi',
                default => 'Di Luar Batas',
            };
        };
        $parameterSeverity = function (string $parameter) use ($warningMap, $thresholds): string {
            $warning = $warningMap->get($parameter);

            if (! $warning) {
                return 'normal';
            }

            $warningLimit = (float) ($thresholds[$parameter]['warning_tolerance'] ?? 0);
            $gap = round(abs((float) $warning['value'] - (float) $warning['limit']), 2);

            return $warningLimit <= 0 || $gap > $warningLimit ? 'critical' : 'warning';
        };
        $statusClass = fn (string $parameter) => $isDashboardUnavailable ? 'bg-secondary' : match ($parameterSeverity($parameter)) {
            'critical' => 'bg-danger',
            'warning' => 'bg-warning text-dark',
            default => 'bg-success',
        };
        $metricCardClass = fn (string $parameter) => match ($parameterSeverity($parameter)) {
            'critical' => 'is-danger',
            'warning' => 'is-warning',
            default => '',
        };
        $metricGapClass = fn (string $parameter) => match ($parameterSeverity($parameter)) {
            'critical' => 'is-danger',
            'warning' => 'is-warning',
            default => '',
        };
        $formatNumber = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        $gapText = function (string $parameter) use ($warningMap, $formatNumber, $isDashboardUnavailable, $isNotInUse): string {
            if ($isDashboardUnavailable) {
                return $isNotInUse ? 'Belum ada data hari ini' : 'Menunggu perangkat online';
            }

            $warning = $warningMap->get($parameter);

            if (! $warning) {
                return 'Dalam batas aman';
            }

            $gap = abs((float) $warning['value'] - (float) $warning['limit']);
            $unit = $warning['unit'] ? ' '.$warning['unit'] : '';
            $direction = match ([$warning['position'] ?? 'outside', $warning['type']]) {
                ['inside', 'low'] => 'di atas minimum',
                ['inside', 'high'] => 'di bawah maksimum',
                ['outside', 'low'] => 'di bawah minimum',
                default => 'di atas maksimum',
            };
            $prefix = match ([$warning['position'] ?? 'outside', $warning['type']]) {
                ['inside', 'low'], ['outside', 'high'] => '+',
                default => '-',
            };

            return $prefix.$formatNumber($gap).$unit.' '.$direction;
        };
        $limitText = fn (array $warning) => ($warning['type'] === 'low' ? 'Minimum ' : 'Maksimum ').$formatNumber($warning['limit']).($warning['unit'] ? ' '.$warning['unit'] : '');
        $normalLimitText = function (string $parameter) use ($thresholds, $formatNumber): string {
            $threshold = $thresholds[$parameter] ?? null;

            if (! $threshold) {
                return '-';
            }

            $unit = $threshold['unit'] ? ' '.$threshold['unit'] : '';

            return $formatNumber($threshold['min']).' - '.$formatNumber($threshold['max']).$unit;
        };
        $deltaText = function (array $warning) use ($formatNumber): string {
            $gap = abs((float) $warning['value'] - (float) $warning['limit']);
            $prefix = match ([$warning['position'] ?? 'outside', $warning['type']]) {
                ['inside', 'low'], ['outside', 'high'] => '+',
                default => '-',
            };

            return $prefix.$formatNumber($gap).($warning['unit'] ? ' '.$warning['unit'] : '');
        };
        $recommendationText = fn (array $warning) => match ($warning['parameter']) {
            'ph' => $warning['type'] === 'low'
                ? 'Cek sensor pH dan ganti sebagian air.'
                : 'Cek sensor pH dan stabilkan kualitas air.',
            'temperature_celsius' => ($warning['position'] ?? 'outside') === 'inside'
                ? 'Pantau suhu dan pastikan tidak bergerak melewati batas.'
                : ($warning['type'] === 'high'
                    ? 'Kurangi sumber panas dan cek sensor suhu.'
                    : 'Cek heater dan posisi sensor suhu.'),
            'tds_ppm' => $warning['type'] === 'low'
                ? 'Pastikan probe TDS terendam dan koneksinya stabil.'
                : 'Cek sisa pakan dan ganti sebagian air.',
            default => 'Periksa sensor dan kondisi air sebelum melakukan tindakan lanjutan.',
        };
        $parameterRows = function ($reading) use ($formatNumber, $limitText, $normalLimitText, $deltaText, $recommendationText): array {
            $warningMap = collect($reading->warningDetails())->keyBy('parameter');

            return collect([
                'ph' => ['label' => 'pH Air', 'value' => $formatNumber($reading->ph).' pH'],
                'temperature_celsius' => ['label' => 'Suhu Air', 'value' => $formatNumber($reading->temperature_celsius).' C'],
                'tds_ppm' => ['label' => 'TDS Air', 'value' => $reading->tds_ppm.' ppm'],
            ])->map(function (array $parameter, string $key) use ($warningMap, $limitText, $normalLimitText, $deltaText, $recommendationText): array {
                $warning = $warningMap->get($key);
                $severity = $warning['severity'] ?? 'normal';

                return [
                    'parameter' => $parameter['label'],
                    'value' => $parameter['value'],
                    'limit' => $warning ? $limitText($warning) : $normalLimitText($key),
                    'gap' => $warning ? $deltaText($warning) : '-',
                    'status' => $warning ? ($severity === 'critical' ? 'Bahaya' : 'Warning') : 'Normal',
                    'severity' => $severity,
                    'recommendation' => $warning ? $recommendationText($warning) : 'Parameter normal. Lanjutkan pemantauan rutin.',
                ];
            })->values()->all();
        };
        $recommendationMessage = match ($severity) {
            'critical' => 'Pembacaan terakhir berstatus bahaya dan perlu tindakan cepat.',
            'warning' => 'Pembacaan terakhir berstatus warning. Pantau parameter yang mendekati atau melewati batas.',
            'inactive' => $unavailableMessage,
            default => 'Pembacaan terakhir normal. Semua parameter berada dalam batas aman.',
        };
        $warningValueClass = fn (array $warning) => $parameterSeverity($warning['parameter']) === 'critical'
            ? 'is-danger'
            : 'is-warning';
    @endphp

    <style>
        .dashboard-clean-main {
            background: var(--app-bg);
            color: var(--app-text);
        }

        .dashboard-shell {
            max-width: 1560px;
            margin: 0 auto;
        }

        .dashboard-card {
            background: var(--app-surface);
            border: 1px solid #E5EAF2;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
            padding: 20px;
        }

        .dashboard-card:hover {
            border-color: var(--app-border);
            box-shadow: var(--app-shadow);
        }

        .dashboard-muted {
            color: var(--app-muted);
        }

        .dashboard-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
            gap: 20px;
            align-items: start;
        }

        .dashboard-left,
        .dashboard-right {
            display: grid;
            gap: 20px;
            min-width: 0;
            grid-template-rows: 180px 520px;
            align-items: stretch;
        }

        .parameter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .top-card {
            height: 100%;
            min-height: 0;
        }

        .metric-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .metric-card.is-danger {
            border-left: 3px solid var(--app-red);
        }

        .metric-card.is-warning {
            border-left: 3px solid var(--app-orange);
        }

        .metric-card .metric-title {
            color: var(--app-muted);
            font-size: .84rem;
            font-weight: 700;
            letter-spacing: .01em;
        }

        .metric-card .metric-number {
            color: var(--app-text);
            font-size: clamp(2.25rem, 2.6vw, 2.5rem);
            font-weight: 800;
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }

        .metric-card .metric-limit {
            color: var(--app-muted);
            font-size: .8rem;
        }

        .metric-gap {
            color: var(--app-muted);
            font-size: .8rem;
            font-weight: 700;
        }

        .metric-gap.is-danger {
            color: var(--app-red);
        }

        .metric-gap.is-warning {
            color: var(--app-orange);
        }

        .metric-icon-sm {
            width: 34px;
            height: 34px;
            display: inline-grid;
            place-items: center;
            border-radius: 10px;
            color: var(--app-primary);
            background: var(--app-primary-soft);
        }

        .metric-icon-sm.orange {
            color: var(--app-orange);
            background: var(--app-orange-soft);
        }

        .metric-icon-sm.green {
            color: var(--app-green);
            background: var(--app-green-soft);
        }

        .device-stat {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: .58rem 0;
            border-bottom: 1px solid var(--app-border);
        }

        .device-stat:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .device-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0;
        }

        .device-summary-item {
            padding: .25rem 1.25rem;
            border-right: 1px solid var(--app-border);
        }

        .device-summary-item:first-child {
            padding-left: 0;
        }

        .device-summary-item:last-child {
            border-right: 0;
            padding-right: 0;
        }

        .device-health-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .62rem 1rem;
        }

        .device-health-item {
            min-width: 0;
            display: block;
        }

        .device-health-item .label {
            color: var(--app-muted);
            font-size: .76rem;
            font-weight: 750;
        }

        .device-health-item strong {
            display: block;
            margin-top: .04rem;
            color: var(--app-text);
            font-size: .84rem;
            text-align: left;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .device-status-card {
            overflow: hidden;
        }

        .device-status-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .device-status-pill {
            display: inline-flex;
            align-items: center;
            gap: .42rem;
            border-radius: 999px;
            padding: .38rem .62rem;
            color: var(--app-green);
            background: var(--app-green-soft);
            font-size: .8rem;
            font-weight: 850;
            line-height: 1;
            white-space: nowrap;
        }

        .device-status-pill::before {
            content: "";
            width: .5rem;
            height: .5rem;
            border-radius: 999px;
            background: currentColor;
            box-shadow: 0 0 0 3px color-mix(in srgb, currentColor 16%, transparent);
        }

        .device-status-pill.is-offline {
            color: var(--app-red);
            background: var(--app-red-soft);
        }

        .device-status-pill.is-inactive {
            color: var(--app-muted);
            background: color-mix(in srgb, var(--app-muted) 12%, transparent);
        }

        .chart-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .6rem;
        }

        .chart-tab-btn {
            border-radius: 999px;
            min-width: 72px;
            padding-inline: .9rem;
        }

        .chart-tab-btn.active {
            box-shadow: none;
        }

        .clean-chart-wrap {
            height: 420px;
            position: relative;
        }

        .trend-card,
        .recommendation-card {
            height: 520px;
            min-height: 520px;
        }

        .trend-card {
            display: flex;
            flex-direction: column;
        }

        .trend-card .clean-chart-wrap {
            flex: 1;
            height: auto;
            min-height: 350px;
            position: relative;
        }

        .trend-card #chartEmptyState {
            flex: 1;
        }

        .recommendation-card {
            display: flex;
            flex-direction: column;
        }

        .recommendation-card .recommendation-list {
            flex: 1;
            min-height: 0;
        }

        .profile-summary {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .35rem;
            margin-top: .45rem;
            color: var(--app-muted);
            font-size: .78rem;
            font-weight: 650;
        }

        .profile-summary .profile-name {
            color: var(--app-primary);
            background: var(--app-primary-soft);
            border-radius: 999px;
            padding: .2rem .48rem;
            font-weight: 800;
        }

        .clean-chart-wrap canvas {
            display: block;
            width: 100% !important;
            height: 100% !important;
        }

        .chart-current-label {
            position: absolute;
            top: .65rem;
            right: .75rem;
            border: 1px solid var(--app-border);
            border-radius: 999px;
            padding: .32rem .58rem;
            color: var(--app-muted);
            background: color-mix(in srgb, var(--app-surface) 88%, transparent);
            font-size: .78rem;
            font-weight: 800;
            pointer-events: none;
        }

        .recommendation-list {
            display: grid;
            gap: .55rem;
            align-content: start;
        }

        .recommendation-item {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: .65rem;
            align-items: start;
            padding: .58rem 0;
            border-bottom: 1px solid var(--app-border);
        }

        .recommendation-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .recommendation-item .label {
            font-weight: 800;
            color: var(--app-text);
        }

        .recommendation-item .meta {
            color: var(--app-muted);
            font-size: .86rem;
            display: none;
        }

        .recommendation-item .meta-clean {
            color: var(--app-muted);
            font-size: .86rem;
        }

        .recommendation-item .action {
            color: var(--app-text);
            font-size: .86rem;
            margin-top: .2rem;
        }

        .recommendation-item .value {
            font-weight: 800;
            white-space: nowrap;
        }

        .recommendation-item .value.is-danger {
            color: var(--app-red);
        }

        .recommendation-item .value.is-warning {
            color: var(--app-orange);
        }

        .recommendation-item .value.is-normal {
            color: var(--app-green);
        }

        .recommendation-summary {
            margin-top: auto;
            border: 1px solid var(--app-border);
            border-radius: 14px;
            background: var(--app-surface-2);
            padding: .9rem;
        }

        .recommendation-summary .title {
            color: var(--app-text);
            font-size: .88rem;
            font-weight: 850;
            margin-bottom: .35rem;
        }

        .recommendation-summary ul {
            margin: 0;
            padding-left: 1.05rem;
            color: var(--app-muted);
            font-size: .84rem;
        }

        .recommendation-note {
            border-radius: 12px;
            background: var(--app-surface-2);
            color: var(--app-muted);
            padding: .75rem .85rem;
            font-size: .9rem;
        }

        .alert-table td,
        .alert-table th {
            vertical-align: middle;
        }

        .alert-value {
            font-weight: 800;
            color: var(--app-text);
        }

        .alert-gap {
            color: var(--app-red);
            font-weight: 800;
            white-space: nowrap;
        }

        .alert-gap.is-warning {
            color: var(--app-orange);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        .detail-box {
            border: 1px solid var(--app-border);
            border-radius: 12px;
            padding: .72rem .8rem;
            background: var(--app-surface-2);
        }

        .detail-box .label {
            color: var(--app-muted);
            font-size: .76rem;
            font-weight: 750;
            margin-bottom: .15rem;
        }

        @media (max-width: 767.98px) {
            .dashboard-card {
                padding: 18px;
            }

            .clean-chart-wrap {
                height: 320px;
            }

            .trend-card,
            .recommendation-card {
                height: auto;
                min-height: 0;
            }

            .trend-card .clean-chart-wrap {
                flex: none;
                height: 320px;
                min-height: 0;
            }

            .device-summary-grid {
                grid-template-columns: 1fr;
                gap: .8rem;
            }

            .device-health-grid {
                grid-template-columns: 1fr;
            }

            .device-summary-item {
                padding: 0 0 .8rem;
                border-right: 0;
                border-bottom: 1px solid var(--app-border);
            }

            .device-summary-item:last-child {
                border-bottom: 0;
                padding-bottom: 0;
            }

            .detail-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 1199.98px) {
            .dashboard-main-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-left,
            .dashboard-right {
                grid-template-rows: auto;
            }

            .top-card {
                min-height: 168px;
            }

            .trend-card,
            .recommendation-card {
                height: auto;
                min-height: 0;
            }
        }

        @media (max-width: 991.98px) {
            .parameter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="dashboard-shell">
        <section class="dashboard-main-grid">
            <div class="dashboard-left">
                <div class="parameter-grid">
                <div class="dashboard-card top-card metric-card {{ $metricCardClass('ph') }}" id="phCard">
                    <div class="metric-sensor-layout">
                        <span class="metric-icon-sm"><i class="bi bi-droplet"></i></span>
                        <div class="metric-sensor-content">
                            <div class="metric-title">pH Air</div>
                            <div class="metric-value-line">
                                <span class="metric-number" id="phValue">{{ $isDashboardUnavailable ? '—' : ($latest?->ph ?? '—') }}</span>
                                <span class="metric-unit">pH</span>
                            </div>
                            <span class="badge {{ $statusClass('ph') }}" id="phStatus">{{ $statusLabel('ph') }}</span>
                            <div class="metric-limit">Ideal: {{ $thresholds['ph']['label'] ?? '-' }}</div>
                            <div class="metric-gap {{ $metricGapClass('ph') }}" id="phGap">{{ $gapText('ph') }}</div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card top-card metric-card {{ $metricCardClass('temperature_celsius') }}" id="temperatureCard">
                    <div class="metric-sensor-layout">
                        <span class="metric-icon-sm"><i class="bi bi-thermometer-half"></i></span>
                        <div class="metric-sensor-content">
                            <div class="metric-title">Suhu Air</div>
                            <div class="metric-value-line">
                                <span class="metric-number" id="temperatureValue">{{ $isDashboardUnavailable ? '—' : ($latest?->temperature_celsius ?? '—') }}</span>
                                <span class="metric-unit">°C</span>
                            </div>
                            <span class="badge {{ $statusClass('temperature_celsius') }}" id="temperatureStatus">{{ $statusLabel('temperature_celsius') }}</span>
                            <div class="metric-limit">Ideal: {{ $thresholds['temperature_celsius']['label'] ?? '-' }}</div>
                            <div class="metric-gap {{ $metricGapClass('temperature_celsius') }}" id="temperatureGap">{{ $gapText('temperature_celsius') }}</div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card top-card metric-card {{ $metricCardClass('tds_ppm') }}" id="tdsCard">
                    <div class="metric-sensor-layout">
                        <span class="metric-icon-sm"><i class="bi bi-water"></i></span>
                        <div class="metric-sensor-content">
                            <div class="metric-title">TDS Air</div>
                            <div class="metric-value-line">
                                <span class="metric-number" id="tdsValue">{{ $isDashboardUnavailable ? '—' : ($latest?->tds_ppm ?? '—') }}</span>
                                <span class="metric-unit">ppm</span>
                            </div>
                            <span class="badge {{ $statusClass('tds_ppm') }}" id="tdsStatus">{{ $statusLabel('tds_ppm') }}</span>
                            <div class="metric-limit">Ideal: {{ $thresholds['tds_ppm']['label'] ?? '-' }}</div>
                            <div class="metric-gap {{ $metricGapClass('tds_ppm') }}" id="tdsGap">{{ $gapText('tds_ppm') }}</div>
                        </div>
                    </div>
                </div>
                </div>

                <div class="dashboard-card trend-card">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                        <div>
                            <h2 class="h5 fw-bold mb-1">Tren Kualitas Air Hari Ini</h2>
                            <p class="small dashboard-muted mb-0" id="chartPeriodLabel">{{ $period['from_local']->format('d M Y H:i') }} - {{ $period['to_local']->format('d M Y H:i') }}</p>
                        </div>
                        <div class="chart-toolbar">
                            <div class="ui-segment" role="group" aria-label="Pilih parameter grafik">
                                <button class="chart-tab-btn active" type="button" data-sensor="ph">pH</button>
                                <button class="chart-tab-btn" type="button" data-sensor="temperature">Suhu</button>
                                <button class="chart-tab-btn" type="button" data-sensor="tds">TDS</button>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-light border rounded-4 text-center mb-0 {{ $readings->isEmpty() ? '' : 'd-none' }}" id="chartEmptyState">
                        Tidak ada data sensor pada rentang tanggal ini.
                    </div>
                    <div class="clean-chart-wrap {{ $readings->isEmpty() ? 'd-none' : '' }}" id="chartWrap">
                        <div class="chart-current-label" id="currentValueLabel">Nilai saat ini: -</div>
                        <canvas id="trendChart" aria-label="Grafik tren kualitas air"></canvas>
                    </div>
                </div>
            </div>

            <div class="dashboard-right">
                <div class="dashboard-card top-card device-status-card {{ $isNotInUse ? 'device-is-inactive' : ($isOnline ? 'device-is-online' : 'device-is-offline') }}">
                    <div class="device-compact-layout">
                        <span class="metric-icon-sm device-icon"><i class="bi bi-cpu"></i></span>
                        <div class="device-compact-content">
                            <div class="device-compact-title">Status Perangkat</div>
                            <div class="device-compact-row">
                                <span>ESP32</span>
                                <span class="device-status-pill {{ $isNotInUse ? 'is-inactive' : ($isOnline ? '' : 'is-offline') }}" id="deviceOnlineBadge">
                                    <span id="onlineIndicator">{{ $isNotInUse ? 'Tidak digunakan' : ($isOnline ? 'Online' : 'Offline') }}</span>
                                </span>
                            </div>
                            <div class="device-compact-row">
                                <span>Update Terakhir</span>
                                <strong id="syncText" data-recorded-at="{{ $summary['last_sync']?->toIso8601String() }}">
                                    {{ $summary['last_sync'] ? $summary['last_sync']->diffForHumans() : '-' }}
                                </strong>
                            </div>
                            <div class="device-compact-row">
                                <span>Sinyal Wi-Fi</span>
                                <strong class="device-positive-value" id="wifiRssi">{{ $device?->wifiLabel() ?? '—' }}</strong>
                            </div>
                            <div class="device-compact-row">
                                <span>Status Sensor</span>
                                <strong class="device-positive-value" id="sensorStatus">{{ $device?->sensor_status ?? 'OK' }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="d-none">
                        <span id="todayReadings">{{ $summary['readings_period'] }}</span>
                        <span id="todayWarnings">{{ $summary['warnings_period'] }}</span>
                        <span id="firmwareVersion">{{ $device?->firmware_version ?? '-' }}</span>
                        <span id="uptimeLabel">{{ $device?->uptimeLabel() ?? '—' }}</span>
                        <span id="summaryReadings">{{ $summary['readings_period'] }}</span>
                        <span id="summaryWarnings">{{ $summary['warnings_period'] }}</span>
                        <span id="summaryDevices">{{ $summary['devices'] }}</span>
                        <span id="summaryActiveDevices">{{ $summary['active_devices'] }}</span>
                    </div>
                </div>

                <div class="dashboard-card recommendation-card">
                    <div class="recommendation-head">
                        <div class="recommendation-heading">
                            <span class="section-title-icon"><i class="bi bi-lightbulb"></i></span>
                            <div class="min-w-0">
                                <h2 class="h5 fw-bold mb-1">Rekomendasi</h2>
                                <p class="small dashboard-muted mb-0" id="todayMessage">{{ $recommendationMessage }}</p>
                            </div>
                        </div>
                        <span class="badge {{ $severity === 'critical' ? 'bg-danger' : ($severity === 'warning' ? 'bg-warning text-dark' : ($severity === 'inactive' ? 'bg-secondary' : 'bg-success')) }}" id="statusBadge">{{ $isDashboardUnavailable ? $unavailableLabel : ($latest?->statusLabel() ?? 'Normal') }}</span>
                    </div>
                    <div class="recommendation-list" id="recommendationList">
                        @if ($isDashboardUnavailable)
                            <div class="recommendation-item">
                                <div>
                                    <div class="label">{{ $isNotInUse ? 'Tidak digunakan' : 'Perangkat offline' }}</div>
                                    <div class="meta-clean">{{ $isNotInUse ? 'Belum ada pembacaan sensor hari ini.' : 'Hubungkan ESP32 agar dashboard menampilkan data aktif.' }}</div>
                                </div>
                                <div class="value dashboard-muted">—</div>
                            </div>
                        @else
                        @forelse ($warnings as $warning)
                            <div class="recommendation-item">
                                <div>
                                    <div class="label">{{ $statusLabel($warning['parameter']) }}</div>
                                    <div class="meta-clean">{{ $formatNumber($warning['value']) }} {{ $warning['unit'] }} / {{ $limitText($warning) }}</div>
                                    <div class="meta">{{ $formatNumber($warning['value']) }} {{ $warning['unit'] }} · {{ $limitText($warning) }}</div>
                                    <div class="action">{{ $recommendationText($warning) }}</div>
                                </div>
                                <div class="value {{ $warningValueClass($warning) }}">{{ $deltaText($warning) }}</div>
                            </div>
                        @empty
                            <div class="recommendation-item">
                                <div>
                                    <div class="label">Kondisi stabil</div>
                                    <div class="meta">Tidak ada parameter melewati batas aman.</div>
                                </div>
                                <div class="value text-success">OK</div>
                            </div>
                        @endforelse
                        @endif
                    </div>
                    <div class="recommendation-summary" id="recommendationSummary">
                        <div class="title">{{ $isDashboardUnavailable ? 'Status pemantauan' : ($warnings->isEmpty() ? 'Pemantauan rutin' : 'Langkah prioritas') }}</div>
                        @if ($isDashboardUnavailable)
                            <ul>
                                <li>{{ $isNotInUse ? 'Nyalakan perangkat saat pemantauan akan dimulai.' : 'Pastikan ESP32 menyala dan terhubung ke Wi-Fi.' }}</li>
                                <li>Nilai lama tetap dapat dilihat pada halaman Histori.</li>
                            </ul>
                        @elseif ($warnings->isEmpty())
                            <ul>
                                <li>Pertahankan interval monitoring sensor.</li>
                                <li>Catat perubahan pH, suhu, dan TDS harian.</li>
                            </ul>
                        @else
                            <ul>
                                <li>Validasi ulang pembacaan sensor bermasalah.</li>
                                <li>Lakukan tindakan kecil dulu sebelum mengganti banyak air.</li>
                                <li>Cek histori untuk melihat apakah alert berulang.</li>
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="dashboard-card alert-section-card">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-2">
                <div>
                    <h2 class="h5 fw-bold mb-1">Pembacaan Terbaru</h2>
                    <p class="small dashboard-muted mb-0">Maksimal 5 data terbaru hari ini.</p>
                </div>
                <a class="ui-btn ui-btn-sm align-self-start" href="{{ route('history', ['from' => $period['from_date'], 'to' => $period['to_date']]) }}">
                    <i class="bi bi-table"></i>Histori Lengkap
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 alert-table">
                    <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Perangkat</th>
                        <th>pH</th>
                        <th>Suhu</th>
                        <th>TDS</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody id="dashboardAlertBody">
                    @forelse ($alertReadings as $reading)
                        <tr>
                            <td>{{ $reading->recorded_at->format('d M Y H:i') }}</td>
                            <td>{{ $reading->device?->name ?? $reading->device?->code ?? '-' }}</td>
                            <td><span class="alert-value">{{ $formatNumber($reading->ph) }} pH</span></td>
                            <td><span class="alert-value">{{ $formatNumber($reading->temperature_celsius) }} C</span></td>
                            <td><span class="alert-value">{{ $reading->tds_ppm }} ppm</span></td>
                            <td>
                                <span class="status-pill {{ $reading->severity() === 'critical' ? 'status-critical' : ($reading->severity() === 'warning' ? 'status-warning' : 'status-normal') }}">
                                    {{ $reading->statusLabel() }}
                                </span>
                            </td>
                            <td>
                                @php($readingWarnings = collect($reading->warningDetails()))
                                @php($detailPayload = [
                                    'recorded_at' => $reading->recorded_at->format('d M Y H:i'),
                                    'device' => $reading->device?->name ?? $reading->device?->code ?? '-',
                                    'ph' => $formatNumber($reading->ph).' pH',
                                    'temperature' => $formatNumber($reading->temperature_celsius).' C',
                                    'tds' => $reading->tds_ppm.' ppm',
                                    'status' => $reading->statusLabel(),
                                    'detail' => $reading->warningSummary(),
                                    'alerts' => $parameterRows($reading),
                                    'recommendation' => $readingWarnings->map(fn ($warning) => $recommendationText($warning))->unique()->implode(' '),
                                ])
                                <button class="ui-btn ui-btn-sm js-dashboard-alert-detail" type="button"
                                    data-detail="{{ e(base64_encode(json_encode($detailPayload))) }}">Detail</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center dashboard-muted py-4">Tidak ada data hari ini.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>


    <div class="modal fade" id="dashboardAlertDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h2 class="modal-title h5 fw-bold">Detail Alert</h2>
                    <button class="modal-close-btn" type="button" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </div>
                <div class="modal-body">
                    <div id="dashboardAlertDetailBody"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let readings = @json($readings);
    let currentDeviceStatus = @json($summary['device_status'] ?? 'not_in_use');
    let thresholds = @json($thresholds);
    let waterQualityChart = null;
    let currentSensor = 'ph';
    let lastWarningMessage = '';
    let currentRange = 'today';
    const dashboardDataBaseUrl = @json(route('dashboard.data'));
    const offlineGapMs = @json(\App\Models\Device::offlineTimeoutMinutes() * 60 * 1000);

    const sensorConfig = {
        ph: { key: 'ph', thresholdKey: 'ph', label: 'pH Air', unit: 'pH', color: '#22B968', cardId: 'phCard', gapId: 'phGap' },
        temperature: { key: 'temperature_celsius', thresholdKey: 'temperature_celsius', label: 'Suhu Air', unit: '°C', color: '#3185FF', cardId: 'temperatureCard', gapId: 'temperatureGap' },
        tds: { key: 'tds_ppm', thresholdKey: 'tds_ppm', label: 'TDS Air', unit: 'ppm', color: '#8B5CF6', cardId: 'tdsCard', gapId: 'tdsGap' },
    };

    function cssVar(name) {
        return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    }

    function formatNumber(value) {
        const number = Number(value);
        if (Number.isNaN(number)) return '-';
        return number.toFixed(2).replace(/\.00$/, '').replace(/(\.\d)0$/, '$1');
    }

    function formatRelativeTime(dateString) {
        if (!dateString) return '-';
        const seconds = Math.max(0, Math.floor((Date.now() - new Date(dateString).getTime()) / 1000));
        if (seconds < 10) return 'baru saja';
        if (seconds < 60) return `${seconds} detik lalu`;
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return `${minutes} menit lalu`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours} jam lalu`;
        return `${Math.floor(hours / 24)} hari lalu`;
    }

    function formatDateTime(dateString) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function buildDashboardQuery() {
        const query = new URLSearchParams();
        query.set('range', currentRange);

        return query;
    }

    function pulseIfChanged(elementId, nextValue) {
        const element = document.getElementById(elementId);
        if (!element) return;
        const normalized = String(nextValue);
        if (element.textContent !== normalized) {
            element.textContent = normalized;
            element.classList.remove('value-updated');
            void element.offsetWidth;
            element.classList.add('value-updated');
        }
    }

    function toastStatusLabel(severity) {
        if (severity === 'critical') return 'Bahaya';
        if (severity === 'warning') return 'Warning';

        return 'Normal';
    }

    function toastStatusClass(severity) {
        if (severity === 'critical') return 'is-critical';
        if (severity === 'warning') return 'is-warning';

        return 'is-normal';
    }

    function toastReason(warning) {
        const label = (warning.label || sensorMeta[warning.parameter]?.title || 'Parameter').replace(' Air', '');
        const direction = warning.type === 'low' ? 'rendah' : 'tinggi';

        return `${label} ${direction}`;
    }

    function showToast(latest) {
        if (!latest) return;

        const severity = latest.severity || 'normal';
        const list = Array.isArray(latest.warning_details) ? latest.warning_details : [];
        const status = toastStatusLabel(severity);
        const reasons = [...new Set(list.map(toastReason).filter(Boolean))];
        const message = reasons.length
            ? `${status}: ${reasons.join(', ')}.`
            : `${status}: pH, Suhu, dan TDS aman.`;

        if (message === lastWarningMessage) return;
        lastWarningMessage = message;
        const title = severity === 'critical' ? 'Status Bahaya' : severity === 'warning' ? 'Status Warning' : 'Status Normal';
        window.appToast?.[severity === 'critical' ? 'error' : severity === 'warning' ? 'warning' : 'success'](title, {
            description: message,
            id: `sensor-alert-${latest.id}`,
        });
    }


    function thresholdLine(label, value, color) {
        if (value === null || value === undefined) return null;

        return {
            label,
            data: chartRows().map(() => Number(value)),
            borderColor: color,
            backgroundColor: color,
            borderDash: [8, 5],
            borderWidth: 2.2,
            pointRadius: 0,
            fill: false,
            tension: 0,
        };
    }

    function chartRows() {
        if (readings.length === 0) return [];

        const rows = [];
        readings.forEach((reading, index) => {
            if (index > 0) {
                const previous = readings[index - 1];
                const previousTime = previous?.recorded_at ? new Date(previous.recorded_at).getTime() : 0;
                const currentTime = reading?.recorded_at ? new Date(reading.recorded_at).getTime() : 0;

                if (Number.isFinite(previousTime) && Number.isFinite(currentTime) && currentTime - previousTime > offlineGapMs) {
                    rows.push({
                        recorded_at: new Date(previousTime + 1000).toISOString(),
                        ph: null,
                        temperature_celsius: null,
                        tds_ppm: null,
                    });
                }
            }

            rows.push(reading);
        });

        if (currentDeviceStatus === 'online') return rows;

        const latestRow = readings.at(-1);
        const latestTime = latestRow?.recorded_at ? new Date(latestRow.recorded_at).getTime() : 0;

        if (Number.isFinite(latestTime) && Date.now() - latestTime > offlineGapMs) {
            rows.push({
                recorded_at: new Date().toISOString(),
                ph: null,
                temperature_celsius: null,
                tds_ppm: null,
            });
        }

        return rows;
    }

    const normalRangePlugin = {
        id: 'normalRangePlugin',
        beforeDatasetsDraw(chart) {
            const config = sensorConfig[currentSensor];
            const threshold = thresholds[config.thresholdKey] || {};
            if (threshold.min === null || threshold.min === undefined || threshold.max === null || threshold.max === undefined) return;

            const { ctx, chartArea, scales } = chart;
            const yScale = scales.y;
            const yMin = yScale.getPixelForValue(Number(threshold.min));
            const yMax = yScale.getPixelForValue(Number(threshold.max));
            const top = Math.min(yMin, yMax);
            const height = Math.abs(yMax - yMin);

            ctx.save();
            ctx.globalAlpha = .08;
            ctx.fillStyle = config.color;
            ctx.fillRect(chartArea.left, top, chartArea.right - chartArea.left, height);
            ctx.restore();
        },
    };

    function drawChart() {
        const canvas = document.getElementById('trendChart');
        if (!canvas || readings.length === 0 || !window.Chart) return;
        if (waterQualityChart) waterQualityChart.destroy();

        const config = sensorConfig[currentSensor];
        const threshold = thresholds[config.thresholdKey] || {};
        const rows = chartRows();
        const labels = rows.map(reading => new Date(reading.recorded_at).toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        }));
        const datasets = [
            {
                label: config.label,
                data: rows.map(reading => reading[config.key] === null || reading[config.key] === undefined ? null : Number(reading[config.key])),
                borderColor: config.color,
                backgroundColor: `${config.color}1A`,
                fill: false,
                spanGaps: false,
                tension: .35,
                borderWidth: 2.4,
                pointRadius(context) {
                    return context.dataIndex === readings.length - 1 ? 4 : 0;
                },
                pointBackgroundColor: cssVar('--app-surface'),
                pointBorderColor: config.color,
                pointBorderWidth: 2,
                pointHoverRadius: 5,
            },
            thresholdLine('Batas minimum', threshold.min, '#2563EB'),
            thresholdLine('Batas maksimum', threshold.max, '#D97706'),
        ].filter(Boolean);

        waterQualityChart = new Chart(canvas, {
            type: 'line',
            data: { labels, datasets },
            plugins: [normalRangePlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            color: cssVar('--app-muted'),
                            usePointStyle: true,
                            boxWidth: 8,
                            boxHeight: 8,
                            font: { family: 'Nunito', weight: 700 },
                        },
                    },
                    tooltip: {
                        backgroundColor: cssVar('--app-surface'),
                        titleColor: cssVar('--app-text'),
                        bodyColor: cssVar('--app-text'),
                        borderColor: cssVar('--app-border'),
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label(context) {
                                return `${context.dataset.label}: ${context.parsed.y} ${config.unit}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: cssVar('--app-muted'), maxRotation: 0, autoSkip: true, maxTicksLimit: 8, font: { family: 'Nunito' } },
                    },
                    y: {
                        suggestedMin: threshold.min !== null && threshold.min !== undefined ? Number(threshold.min) - 1 : undefined,
                        suggestedMax: threshold.max !== null && threshold.max !== undefined ? Number(threshold.max) + 1 : undefined,
                        title: { display: true, text: config.unit, color: cssVar('--app-muted'), font: { family: 'Nunito', weight: 700 } },
                        grid: { color: cssVar('--app-border') },
                        ticks: { color: cssVar('--app-muted'), font: { family: 'Nunito' } },
                    },
                },
            },
        });

        const latest = readings.at(-1);
        const currentLabel = document.getElementById('currentValueLabel');
        if (currentLabel && latest) currentLabel.textContent = `Nilai saat ini: ${formatNumber(latest[config.key])} ${config.unit}`;
    }

    function updateChart(nextReadings) {
        readings = nextReadings;
        const chartWrap = document.getElementById('chartWrap');
        const emptyState = document.getElementById('chartEmptyState');

        chartWrap?.classList.toggle('d-none', readings.length === 0);
        emptyState?.classList.toggle('d-none', readings.length > 0);

        if (readings.length === 0) {
            waterQualityChart?.destroy();
            waterQualityChart = null;
            return;
        }

        drawChart();
    }

    function statusFor(parameter, latest) {
        const warning = (latest.warning_details || []).find(item => item.parameter === parameter);
    if (!warning) return { label: 'Normal', className: 'bg-success' };

        const label = warningLabel(parameter, warning);

        return warningSeverity(warning) === 'critical'
    ? { label, className: 'bg-danger' }
    : { label, className: 'bg-warning text-dark' };
    }

    function warningLabel(parameter, warning) {
        if ((warning.position || 'outside') === 'inside') {
            if (parameter === 'ph') return warning.type === 'low' ? 'Mendekati Asam' : 'Mendekati Basa';
            if (parameter === 'temperature_celsius') return warning.type === 'high' ? 'Mendekati Panas' : 'Mendekati Dingin';
            if (parameter === 'tds_ppm') return warning.type === 'low' ? 'TDS Mendekati Rendah' : 'TDS Mendekati Tinggi';

            return 'Mendekati Batas';
        }

        if (parameter === 'ph') return warning.type === 'low' ? 'Terlalu Asam' : 'Terlalu Basa';
        if (parameter === 'temperature_celsius') return warning.type === 'high' ? 'Terlalu Panas' : 'Terlalu Dingin';
        if (parameter === 'tds_ppm') return warning.type === 'low' ? 'TDS Rendah' : 'TDS Tinggi';

        return 'Di Luar Batas';
    }

    function warningValueClass(warning) {
        return warningSeverity(warning) === 'critical'
            ? 'is-danger'
            : 'is-warning';
    }

    function warningSeverity(warning) {
        const threshold = thresholds[warning.parameter] || {};
        const warningLimit = Number(threshold.warning_tolerance || 0);
        const gap = Math.round(Math.abs(Number(warning.value) - Number(warning.limit)) * 100) / 100;

        return warningLimit <= 0 || gap > warningLimit ? 'critical' : 'warning';
    }

    function recommendationMessage(latest) {
        if (!latest) return '';
        if (latest.severity === 'critical') return 'Pembacaan terakhir berstatus bahaya dan perlu tindakan cepat.';
        if (latest.severity === 'warning') return 'Pembacaan terakhir berstatus warning. Pantau parameter yang mendekati atau melewati batas.';

        return 'Pembacaan terakhir normal. Semua parameter berada dalam batas aman.';
    }

    function warningGapText(parameter, latest) {
        const warning = (latest.warning_details || []).find(item => item.parameter === parameter);
        if (!warning) return 'Dalam batas aman';

        const gap = Math.abs(Number(warning.value) - Number(warning.limit));
        const unit = warning.unit ? ` ${warning.unit}` : '';
        const isInside = (warning.position || 'outside') === 'inside';
        const prefix = (isInside && warning.type === 'low') || (!isInside && warning.type === 'high') ? '+' : '-';
        const direction = isInside
            ? (warning.type === 'low' ? 'di atas minimum' : 'di bawah maksimum')
            : (warning.type === 'low' ? 'di bawah minimum' : 'di atas maksimum');

        return `${prefix}${formatNumber(gap)}${unit} ${direction}`;
    }

    function updateMetricState(sensor, parameter, latest) {
        const config = sensorConfig[sensor];
        const warning = (latest.warning_details || []).find(item => item.parameter === parameter);
        const severity = warning ? warningSeverity(warning) : 'normal';
        const card = document.getElementById(config.cardId);
        card?.classList.toggle('is-danger', severity === 'critical');
        card?.classList.toggle('is-warning', severity === 'warning');
        const gap = document.getElementById(config.gapId);
        if (!gap) return;
        gap.textContent = warningGapText(parameter, latest);
        gap.classList.toggle('is-danger', severity === 'critical');
        gap.classList.toggle('is-warning', severity === 'warning');
    }

    function updateBadge(id, state) {
        const badge = document.getElementById(id);
        if (!badge) return;
        badge.className = `badge ${state.className}`;
        badge.textContent = state.label;
    }

    function renderRecommendation(latest) {
        const list = document.getElementById('recommendationList');
        if (!list) return;

        const details = latest.warning_details || [];
        if (details.length === 0) {
            list.innerHTML = `
                <div class="recommendation-item">
                    <div>
                        <div class="label">Kondisi stabil</div>
                        <div class="meta">Tidak ada parameter melewati batas aman.</div>
                    </div>
                    <div class="value text-success">OK</div>
                </div>
            `;
            updateRecommendationSummary(false);
            return;
        }

        list.innerHTML = details.map(warning => `
            <div class="recommendation-item">
                <div>
                    <div class="label">${warningLabel(warning.parameter, warning)}</div>
                    <div class="meta-clean">${formatNumber(warning.value)} ${warning.unit || ''} / ${warning.type === 'low' ? 'Minimum' : 'Maksimum'} ${formatNumber(warning.limit)} ${warning.unit || ''}</div>
                    <div class="action">${recommendationFor(warning)}</div>
                </div>
                <div class="value ${warningValueClass(warning)}">${warningDelta(warning)}</div>
            </div>
        `).join('');
        updateRecommendationSummary(true);
    }

    function updateRecommendationSummary(hasWarnings) {
        const target = document.getElementById('recommendationSummary');
        if (!target) return;

        target.innerHTML = hasWarnings ? `
            <div class="title">Langkah prioritas</div>
            <ul>
                <li>Validasi ulang pembacaan sensor bermasalah.</li>
                <li>Lakukan tindakan kecil dulu sebelum mengganti banyak air.</li>
                <li>Cek histori untuk melihat apakah alert berulang.</li>
            </ul>
        ` : `
            <div class="title">Pemantauan rutin</div>
            <ul>
                <li>Pertahankan interval monitoring sensor.</li>
                <li>Catat perubahan pH, suhu, dan TDS harian.</li>
            </ul>
        `;
    }

    function recommendationFor(warning) {
        if (warning.parameter === 'ph') {
            return warning.type === 'low'
                ? 'Cek sensor pH dan ganti sebagian air.'
                : 'Cek sensor pH dan stabilkan kualitas air.';
        }
        if (warning.parameter === 'temperature_celsius') {
            if ((warning.position || 'outside') === 'inside') {
                return 'Pantau suhu dan pastikan tidak bergerak melewati batas.';
            }

            return warning.type === 'high'
                ? 'Kurangi sumber panas dan cek sensor suhu.'
                : 'Cek heater dan posisi sensor suhu.';
        }
        if (warning.parameter === 'tds_ppm') {
            return warning.type === 'low'
                ? 'Pastikan probe TDS terendam dan koneksinya stabil.'
                : 'Cek sisa pakan dan ganti sebagian air.';
        }

        return 'Periksa sensor dan kondisi air sebelum melakukan tindakan lanjutan.';
    }

    function warningDelta(warning) {
        const gap = Math.abs(Number(warning.value) - Number(warning.limit));
        const isInside = (warning.position || 'outside') === 'inside';
        const prefix = (isInside && warning.type === 'low') || (!isInside && warning.type === 'high') ? '+' : '-';

        return `${prefix}${formatNumber(gap)}${warning.unit ? ` ${warning.unit}` : ''}`;
    }

    function warningLimit(warning) {
        return `${warning.type === 'low' ? 'Minimum' : 'Maksimum'} ${formatNumber(warning.limit)}${warning.unit ? ` ${warning.unit}` : ''}`;
    }

    function normalLimit(parameter) {
        const threshold = thresholds[parameter] || {};
        if (threshold.min === undefined || threshold.max === undefined) return '-';

        return `${formatNumber(threshold.min)} - ${formatNumber(threshold.max)}${threshold.unit ? ` ${threshold.unit}` : ''}`;
    }

    function parameterDetailRows(reading) {
        const warningMap = new Map((reading.warning_details || []).map(warning => [warning.parameter, warning]));
        const rows = [
            { key: 'ph', label: 'pH Air', value: `${formatNumber(reading.ph)} pH` },
            { key: 'temperature_celsius', label: 'Suhu Air', value: `${formatNumber(reading.temperature_celsius)} C` },
            { key: 'tds_ppm', label: 'TDS Air', value: `${reading.tds_ppm} ppm` },
        ];

        return rows.map(row => {
            const warning = warningMap.get(row.key);
            const severity = warning?.severity || 'normal';

            return {
                parameter: row.label,
                value: row.value,
                limit: warning ? warningLimit(warning) : normalLimit(row.key),
                gap: warning ? warningDelta(warning) : '-',
                status: warning ? (severity === 'critical' ? 'Bahaya' : 'Warning') : 'Normal',
                severity,
                recommendation: warning ? recommendationFor(warning) : 'Parameter normal. Lanjutkan pemantauan rutin.',
            };
        });
    }

    function renderDetailModal(targetId, detail) {
        const target = document.getElementById(targetId);
        if (!target) return;

        target.innerHTML = `
            <div class="alert-detail-summary mb-3">
                <div class="alert-detail-identity">
                    <span class="alert-detail-icon"><i class="bi bi-droplet"></i></span>
                    <div>
                        <strong>${detail.device || '-'}</strong>
                        <span><i class="bi bi-clock"></i>${detail.recorded_at || '-'}</span>
                    </div>
                </div>
            </div>
            ${renderAlertList(detail.alerts || [])}
            <div class="recommendation-note alert-detail-action">
                <i class="bi bi-lightbulb"></i>
                <div><strong>Tindakan yang disarankan</strong><span>${detail.recommendation || 'Lanjutkan pemantauan rutin.'}</span></div>
            </div>
        `;
    }

    function renderAlertList(alerts) {
        if (!alerts.length) {
            return '<div class="recommendation-note mb-3">Tidak ada parameter yang melewati batas aman.</div>';
        }

        return `
            <div class="table-responsive mb-3">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Nilai</th>
                        <th>Batas</th>
                        <th>Selisih</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    ${alerts.map(alert => `
                        <tr>
                            <td>${alert.parameter || '-'}</td>
                            <td>${alert.value || '-'}</td>
                            <td>${alert.limit || '-'}</td>
                            <td><span class="alert-gap">${alert.gap || '-'}</span></td>
                            <td>${detailStatusBadge(alert.severity, alert.status)}</td>
                        </tr>
                    `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    function detailStatusBadge(severity, status) {
        const className = severity === 'critical'
            ? 'status-critical'
            : (severity === 'warning' ? 'status-warning' : 'status-normal');

        return `<span class="status-pill ${className}">${status || 'Normal'}</span>`;
    }

    function encodeDetail(detail) {
        return btoa(unescape(encodeURIComponent(JSON.stringify(detail))));
    }

    function parseDetail(rawDetail) {
        if (!rawDetail) return {};

        try {
            return JSON.parse(decodeURIComponent(escape(atob(rawDetail))));
        } catch (error) {
            try {
                return JSON.parse(decodeURIComponent(rawDetail));
            } catch (fallbackError) {
                try {
                    return JSON.parse(rawDetail);
                } catch (jsonError) {
                    return { detail: rawDetail };
                }
            }
        }
    }

    function updateSummary(summary) {
        if (!summary) return;
        pulseIfChanged('summaryDevices', summary.devices ?? 0);
        pulseIfChanged('summaryReadings', summary.readings_period ?? 0);
        pulseIfChanged('summaryWarnings', summary.warnings_period ?? 0);
        pulseIfChanged('summaryActiveDevices', summary.active_devices ?? 0);
            pulseIfChanged('todayReadings', summary.readings_period ?? 0);
            pulseIfChanged('todayWarnings', summary.warnings_period ?? 0);
    }

    function updatePeriodUi(period) {
        if (!period) return;
        document.getElementById('chartPeriodLabel').textContent = `${formatDateTime(period.from)} - ${formatDateTime(period.to)}`;

    }

    function updateAlertRows(history) {
        const tbody = document.getElementById('dashboardAlertBody');
        if (!tbody) return;

        const limitedRows = (history || []).slice(0, 5);

        if (limitedRows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center dashboard-muted py-4">Tidak ada data hari ini.</td></tr>';
            return;
        }

        tbody.innerHTML = limitedRows.map(reading => {
            const badgeClass = reading.severity === 'critical'
                ? 'status-critical'
                : (reading.severity === 'warning' ? 'status-warning' : 'status-normal');
            const status = reading.status_label || (reading.severity === 'critical' ? 'Bahaya' : (reading.severity === 'warning' ? 'Warning' : 'Normal'));
            const alerts = parameterDetailRows(reading);
            const detail = {
                recorded_at: formatDateTime(reading.recorded_at),
                device: reading.device?.name || reading.device?.code || '-',
                ph: `${formatNumber(reading.ph)} pH`,
                temperature: `${formatNumber(reading.temperature_celsius)} C`,
                tds: `${reading.tds_ppm} ppm`,
                status,
                detail: reading.warning_summary || 'Parameter melewati batas aman.',
                alerts,
                recommendation: [...new Set(alerts.map(alert => alert.recommendation).filter(Boolean))].join(' '),
            };

            return `
                <tr>
                    <td>${formatDateTime(reading.recorded_at)}</td>
                    <td>${reading.device?.name || reading.device?.code || '-'}</td>
                    <td><span class="alert-value">${formatNumber(reading.ph)} pH</span></td>
                    <td><span class="alert-value">${formatNumber(reading.temperature_celsius)} C</span></td>
                    <td><span class="alert-value">${reading.tds_ppm} ppm</span></td>
                    <td><span class="status-pill ${badgeClass}">${status}</span></td>
                    <td><button class="ui-btn ui-btn-sm js-dashboard-alert-detail" type="button" data-detail="${encodeDetail(detail)}">Detail</button></td>
                </tr>
            `;
        }).join('');
    }

    function renderUnavailableState(latest, deviceStatus = 'not_in_use') {
        const isOffline = deviceStatus === 'offline';
        const label = isOffline ? 'Offline' : 'Tidak digunakan';
        const message = isOffline
            ? 'Perangkat sedang offline. Data sensor aktif belum tersedia.'
            : 'Perangkat tidak digunakan hari ini. Nilai terakhir tetap tersimpan di halaman Histori.';

        pulseIfChanged('phValue', '—');
        pulseIfChanged('temperatureValue', '—');
        pulseIfChanged('tdsValue', '—');

        ['phStatus', 'temperatureStatus', 'tdsStatus'].forEach(id => {
            updateBadge(id, { label, className: 'bg-secondary' });
        });

        Object.values(sensorConfig).forEach(config => {
            const card = document.getElementById(config.cardId);
            card?.classList.remove('is-danger', 'is-warning');
            const gap = document.getElementById(config.gapId);
            if (gap) {
                gap.textContent = isOffline ? 'Menunggu perangkat online' : 'Belum ada data hari ini';
                gap.classList.remove('is-danger', 'is-warning');
            }
        });

        const statusBadge = document.getElementById('statusBadge');
        if (statusBadge) {
            statusBadge.className = 'badge bg-secondary';
            statusBadge.textContent = label;
        }

        const onlineBadge = document.getElementById('deviceOnlineBadge');
        onlineBadge?.classList.toggle('is-offline', isOffline);
        onlineBadge?.classList.toggle('is-inactive', !isOffline);
        const deviceCard = document.querySelector('.device-status-card');
        deviceCard?.classList.remove('device-is-online', 'device-is-offline', 'device-is-inactive');
        deviceCard?.classList.add(isOffline ? 'device-is-offline' : 'device-is-inactive');
        document.getElementById('onlineIndicator').textContent = label;
        document.getElementById('wifiRssi').textContent = latest?.device?.wifi_label || '-';
        document.getElementById('uptimeLabel').textContent = latest?.device?.uptime_label || '-';
        document.getElementById('sensorStatus').textContent = latest?.device?.sensor_status || '-';

        const todayMessage = document.getElementById('todayMessage');
        if (todayMessage) {
            todayMessage.textContent = message;
        }

        const emptyState = document.getElementById('chartEmptyState');
        if (emptyState) {
            emptyState.textContent = `Data sensor aktif tidak tersedia karena perangkat sedang ${label}.`;
        }

        const list = document.getElementById('recommendationList');
        if (list) {
            list.innerHTML = `
                <div class="recommendation-item">
                    <div>
                        <div class="label">${isOffline ? 'Perangkat offline' : 'Tidak digunakan'}</div>
                        <div class="meta-clean">${isOffline ? 'Hubungkan ESP32 agar dashboard menampilkan data aktif.' : 'Belum ada pembacaan sensor hari ini.'}</div>
                    </div>
                    <div class="value dashboard-muted">—</div>
                </div>
            `;
        }

        const recommendationSummary = document.getElementById('recommendationSummary');
        if (recommendationSummary) {
            recommendationSummary.innerHTML = `
                <div class="title">Status pemantauan</div>
                <ul>
                    <li>${isOffline ? 'Pastikan ESP32 menyala dan terhubung ke Wi-Fi.' : 'Nyalakan perangkat saat pemantauan akan dimulai.'}</li>
                    <li>Nilai lama tetap dapat dilihat pada halaman Histori.</li>
                </ul>
            `;
        }

        const syncText = document.getElementById('syncText');
        if (syncText && latest?.recorded_at) {
            syncText.dataset.recordedAt = latest.recorded_at;
            syncText.textContent = formatRelativeTime(latest.recorded_at);
        }

        const inactiveMessage = isOffline ? 'Perangkat sedang offline.' : 'Perangkat tidak digunakan hari ini.';
        if (lastWarningMessage !== inactiveMessage) {
            lastWarningMessage = inactiveMessage;
            window.appToast?.info(isOffline ? 'Perangkat offline' : 'Perangkat tidak aktif', {
                description: isOffline ? 'Dashboard menunggu data baru dari ESP32.' : 'Belum ada pembacaan sensor hari ini. Data sebelumnya tetap tersedia di Histori.',
                id: `sensor-device-${deviceStatus}`,
            });
        }
    }

    async function refreshLatest() {
        try {
            const response = await fetch(`${dashboardDataBaseUrl}?${buildDashboardQuery().toString()}`, { headers: { Accept: 'application/json' } });
            const body = await response.json();

            thresholds = body.data?.thresholds || thresholds;
            currentDeviceStatus = body.data?.summary?.device_status || 'not_in_use';
            updatePeriodUi(body.data?.period);
            updateSummary(body.data?.summary);
            updateChart(body.data?.chart || []);
            updateAlertRows(body.data?.history || []);

            if (currentDeviceStatus !== 'online') {
                renderUnavailableState(body.data?.latest, currentDeviceStatus);
                return;
            }

            if (!body.data?.latest) return;

            const latest = body.data.latest;

            pulseIfChanged('phValue', Number(latest.ph).toFixed(2));
            pulseIfChanged('temperatureValue', Number(latest.temperature_celsius).toFixed(2));
            pulseIfChanged('tdsValue', latest.tds_ppm);
            updateBadge('phStatus', statusFor('ph', latest));
            updateBadge('temperatureStatus', statusFor('temperature_celsius', latest));
            updateBadge('tdsStatus', statusFor('tds_ppm', latest));
            updateMetricState('ph', 'ph', latest);
            updateMetricState('temperature', 'temperature_celsius', latest);
            updateMetricState('tds', 'tds_ppm', latest);

            const statusBadge = document.getElementById('statusBadge');
            if (statusBadge) {
            const className = latest.severity === 'critical' ? 'bg-danger' : latest.severity === 'warning' ? 'bg-warning text-dark' : 'bg-success';
                statusBadge.className = `badge ${className}`;
                statusBadge.textContent = latest.status_label || 'Normal';
            }

            const syncText = document.getElementById('syncText');
            syncText.dataset.recordedAt = latest.recorded_at;
            syncText.textContent = formatRelativeTime(latest.recorded_at);

            renderRecommendation(latest);
            const todayMessage = document.getElementById('todayMessage');
            if (todayMessage) todayMessage.textContent = recommendationMessage(latest);

            const isOnline = latest.device?.is_online ?? false;
            document.getElementById('onlineIndicator').textContent = isOnline ? 'Online' : 'Offline';
            const onlineBadge = document.getElementById('deviceOnlineBadge');
            onlineBadge?.classList.remove('is-inactive');
            onlineBadge?.classList.toggle('is-offline', !isOnline);
            const deviceCard = document.querySelector('.device-status-card');
            deviceCard?.classList.remove('device-is-inactive');
            deviceCard?.classList.toggle('device-is-online', isOnline);
            deviceCard?.classList.toggle('device-is-offline', !isOnline);

            document.getElementById('wifiRssi').textContent = latest.device?.wifi_label || '-';
            document.getElementById('uptimeLabel').textContent = latest.device?.uptime_label || '-';
            document.getElementById('sensorStatus').textContent = latest.device?.sensor_status || '-';
            const firmwareVersion = document.getElementById('firmwareVersion');
            if (firmwareVersion) firmwareVersion.textContent = latest.device?.firmware_version || '-';

            showToast(latest);
        } catch (error) {
            console.warn('Gagal mengambil data dashboard', error);
        }
    }

    function refreshRelativeClock() {
        const syncText = document.getElementById('syncText');
        if (syncText) syncText.textContent = formatRelativeTime(syncText.dataset.recordedAt);
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.chart-tab-btn[data-sensor]').forEach(button => {
            button.addEventListener('click', () => {
                currentSensor = button.dataset.sensor;
                document.querySelectorAll('.ui-segment .chart-tab-btn[data-sensor]').forEach(item => item.classList.toggle('active', item.dataset.sensor === currentSensor));
                drawChart();
            });
        });


        document.addEventListener('click', event => {
            const button = event.target.closest('.js-dashboard-alert-detail');
            if (!button) return;
            renderDetailModal('dashboardAlertDetailBody', parseDetail(button.dataset.detail));
            bootstrap.Modal.getOrCreateInstance(document.getElementById('dashboardAlertDetailModal')).show();
        });

        drawChart();
        refreshRelativeClock();
    });

    window.addEventListener('theme-changed', () => {
        drawChart();
    });

    setInterval(refreshLatest, 5000);
    setInterval(refreshRelativeClock, 1000);
</script>
@endpush




