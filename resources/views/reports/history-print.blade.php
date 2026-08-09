@extends('layouts.app')

@section('title', 'Laporan Monitoring Kualitas Air')

@section('content')
    <style>
        .report-page {
            max-width: 1240px;
            margin: 0 auto;
        }

        .report-header {
            align-items: flex-start;
        }

        .print-hint {
            color: var(--app-muted);
            font-size: .85rem;
        }

        .report-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
        }

        .report-summary .app-card {
            min-height: 78px;
            padding: .9rem !important;
        }

        .report-chart-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
        }

        .report-chart-panel {
            border: 1px solid var(--app-border);
            border-radius: 6px;
            padding: .75rem;
            background: var(--app-surface);
        }

        .report-chart-panel h3 {
            font-size: .82rem;
            font-weight: 800;
            margin-bottom: .4rem;
        }

        .report-chart-wrap {
            height: 190px;
            position: relative;
        }

        .report-chart-wrap canvas {
            display: block;
            width: 100% !important;
            height: 100% !important;
        }

        .report-table-card {
            margin-top: .75rem;
        }

        .report-table {
            width: 100%;
            table-layout: fixed;
        }

        @media (max-width: 991.98px) {
            .report-chart-grid {
                grid-template-columns: 1fr;
            }

            .report-chart-wrap {
                height: 220px;
            }
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm;
            }

            .app-sidebar,
            .app-sidebar-backdrop,
            .app-topbar,
            .app-navbar,
            .print-actions,
            .print-hint {
                display: none !important;
            }

            html[data-theme],
            html[data-theme] body.smartqua-app {
                --app-bg: #ffffff;
                --app-surface: #ffffff;
                --app-surface-2: #f1f7fa;
                --app-border: #dceaf0;
                --app-text: #12253a;
                --app-muted: #637991;
                background: #fff !important;
                color: #12253a !important;
                color-scheme: light !important;
                font-size: 9px;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .app-layout,
            .app-workspace,
            .app-content,
            .app-page-shell,
            main.container,
            main.container-fluid {
                display: block !important;
                width: 100% !important;
                max-width: none;
                padding: 0 !important;
                margin: 0 !important;
                background: #fff !important;
            }

            .report-page {
                width: 100%;
                max-width: none;
                margin: 0;
                color: #12253a !important;
                background: #fff !important;
            }

            .report-page .app-card {
                border: 0;
                box-shadow: none;
                padding: 0 !important;
                background: #fff !important;
            }

            .report-header {
                display: block !important;
                margin-bottom: 4mm !important;
            }

            .page-kicker {
                font-size: 8px;
                margin-bottom: 4px !important;
            }

            .page-title {
                font-size: 16px;
                margin-bottom: 4px !important;
            }

            .muted {
                color: #333 !important;
            }

            .report-summary {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 4mm;
                margin-bottom: 4mm !important;
            }

            .report-summary .app-card {
                min-height: 18mm;
                border: 1px solid #ddd;
                border-radius: 3px;
                padding: 3.5mm !important;
                break-inside: avoid;
            }

            .metric-label {
                font-size: 7px;
            }

            .report-chart-card {
                break-inside: auto !important;
                page-break-inside: auto !important;
                margin-bottom: 0 !important;
            }

            .report-chart-card h2,
            .report-table-card h2 {
                font-size: 11px;
                margin-bottom: 5px !important;
            }

            .report-chart-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 3mm;
            }

            .report-chart-panel {
                border: 1px solid #ddd;
                border-radius: 3px;
                padding: 3mm;
                break-inside: avoid;
            }

            .report-chart-panel h3 {
                font-size: 8px;
                margin-bottom: 2mm;
            }

            .report-chart-wrap {
                height: 48mm;
                max-height: 48mm;
                overflow: hidden;
            }

            .report-chart-wrap canvas {
                width: 100% !important;
                height: 48mm !important;
            }

            .report-table-card {
                break-before: auto;
                page-break-before: auto;
                margin-top: 4mm !important;
            }

            .report-table-card .table-responsive {
                overflow: visible !important;
            }

            .report-table {
                width: 100% !important;
                table-layout: fixed;
                border-collapse: collapse;
                font-size: 7px;
                line-height: 1.25;
            }

            .report-table thead {
                display: table-header-group;
            }

            .report-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .report-table th,
            .report-table td {
                padding: 1.2mm 1.4mm !important;
                vertical-align: top;
                overflow-wrap: anywhere;
                word-break: normal;
                color: #12253a !important;
                border-color: #dceaf0 !important;
                background: #fff !important;
            }

            .report-table th {
                font-size: 6.5px;
                white-space: nowrap;
                color: #526a82 !important;
                background: #eef5f8 !important;
            }

            .report-table td:nth-child(-n+5) {
                white-space: nowrap;
            }
        }
    </style>

    <div class="report-page">
        <section class="report-header d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
            <div>
                <div class="page-kicker mb-2">Laporan</div>
                <h1 class="page-title mb-2">Monitoring Kualitas Air Akuarium</h1>
                <p class="muted mb-0">Periode {{ $period['from_local']->format('d M Y H:i:s') }} - {{ $period['to_local']->format('d M Y H:i:s') }} WIB</p>
            </div>
            <div class="print-actions d-flex flex-wrap gap-2 align-self-start">
                <button class="btn btn-primary" type="button" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Cetak / Simpan PDF
                </button>
                <a class="btn btn-outline-primary" href="{{ route('history', ['from' => $period['from_input'], 'to' => $period['to_input']]) }}">
                    <i class="bi bi-table me-1"></i>Kembali Histori
                </a>
                <div class="print-hint w-100">Saat dialog print terbuka, matikan opsi Headers and footers agar tanggal/judul browser tidak ikut tercetak.</div>
            </div>
        </section>

        <section class="report-summary mb-4">
            <div class="app-card">
                <div class="metric-label">Total Data</div>
                <div class="h2 fw-bold mb-0 mt-2">{{ $readings->count() }}</div>
            </div>
            <div class="app-card">
                <div class="metric-label">Alert</div>
                <div class="h2 fw-bold mb-0 mt-2">{{ $readings->filter(fn ($reading) => $reading->severity() !== 'normal')->count() }}</div>
            </div>
            <div class="app-card">
                <div class="metric-label">Perangkat</div>
                <div class="fw-bold mt-2">{{ $readings->first()?->device?->name ?? 'Akuarium Utama' }}</div>
            </div>
        </section>

        <section class="report-chart-card app-card p-4 mb-4">
            <h2 class="h5 fw-bold mb-3">Grafik Kualitas Air</h2>
            @if ($chart->isEmpty())
                <div class="alert alert-light border rounded-4 text-center mb-0">Tidak ada data pada range ini.</div>
            @else
                <div class="report-chart-grid">
                    <div class="report-chart-panel">
                        <h3>pH Air</h3>
                        <div class="report-chart-wrap">
                            <canvas id="reportPhChart" aria-label="Grafik laporan pH air"></canvas>
                        </div>
                    </div>
                    <div class="report-chart-panel">
                        <h3>Suhu Air</h3>
                        <div class="report-chart-wrap">
                            <canvas id="reportTemperatureChart" aria-label="Grafik laporan suhu air"></canvas>
                        </div>
                    </div>
                    <div class="report-chart-panel">
                        <h3>TDS Air</h3>
                        <div class="report-chart-wrap">
                            <canvas id="reportTdsChart" aria-label="Grafik laporan TDS air"></canvas>
                        </div>
                    </div>
                </div>
            @endif
        </section>

        <section class="report-table-card app-card p-4">
            <h2 class="h5 fw-bold mb-3">Data Sensor</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 report-table">
                    <colgroup>
                        <col style="width:18%">
                        <col style="width:8%">
                        <col style="width:10%">
                        <col style="width:10%">
                        <col style="width:10%">
                        <col style="width:44%">
                    </colgroup>
                    <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>pH</th>
                        <th>Suhu</th>
                        <th>TDS</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($readings as $reading)
                    <tr>
                        <td>{{ $reading->recordedAtLocal()?->format('d M Y H:i:s') }}</td>
                        <td>{{ $reading->ph }}</td>
                        <td>{{ $reading->temperature_celsius }} C</td>
                        <td>{{ $reading->tds_ppm }} ppm</td>
                        <td>{{ $reading->statusLabel() }}</td>
                        <td class="small">{{ $reading->warningSummary() }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center muted py-4">Tidak ada data.</td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    const reportReadings = @json($chart);
    const reportThresholds = @json($thresholds);
    const reportDisplayTimezone = @json(config('app.display_timezone', 'Asia/Jakarta'));
    const reportOfflineGapMs = @json(\App\Models\Device::offlineTimeoutMinutes() * 60 * 1000);
    const reportPeriodTo = @json($period['to']->toIso8601String());

    function reportCssVar(name) {
        return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (!window.Chart || reportReadings.length === 0) return;

        const screenPalette = () => ({
            text: reportCssVar('--app-muted'),
            grid: reportCssVar('--app-border'),
        });
        const initialPalette = screenPalette();
        const textColor = initialPalette.text;
        const gridColor = initialPalette.grid;
        const charts = [];

        function applyChartPalette(chart, palette) {
            chart.options.plugins.legend.labels.color = palette.text;
            chart.options.scales.x.ticks.color = palette.text;
            chart.options.scales.y.ticks.color = palette.text;
            chart.options.scales.y.title.color = palette.text;
            chart.options.scales.y.grid.color = palette.grid;
            chart.update('none');
        }

        function reportChartRows() {
            if (reportReadings.length === 0) return [];

            const rows = [];
            reportReadings.forEach((reading, index) => {
                if (index > 0) {
                    const previous = reportReadings[index - 1];
                    const previousTime = previous?.recorded_at ? new Date(previous.recorded_at).getTime() : 0;
                    const currentTime = reading?.recorded_at ? new Date(reading.recorded_at).getTime() : 0;

                    if (Number.isFinite(previousTime) && Number.isFinite(currentTime) && currentTime - previousTime > reportOfflineGapMs) {
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

            const latest = reportReadings.at(-1);
            const latestTime = latest?.recorded_at ? new Date(latest.recorded_at).getTime() : 0;
            const periodEndTime = reportPeriodTo ? new Date(reportPeriodTo).getTime() : 0;

            if (Number.isFinite(latestTime) && Number.isFinite(periodEndTime) && periodEndTime - latestTime > reportOfflineGapMs) {
                rows.push({
                    recorded_at: new Date(periodEndTime).toISOString(),
                    ph: null,
                    temperature_celsius: null,
                    tds_ppm: null,
                });
            }

            return rows;
        }

        function labels() {
            return reportChartRows().map(reading => new Date(reading.recorded_at).toLocaleString('id-ID', {
                timeZone: reportDisplayTimezone,
                day: '2-digit',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
            }));
        }

        function thresholdDataset(label, value, color) {
            if (value === null || value === undefined) return null;

            return {
                label,
                data: reportChartRows().map(() => Number(value)),
                borderColor: color,
                backgroundColor: color,
                borderDash: [8, 5],
                borderWidth: 2.2,
                pointRadius: 0,
                fill: false,
                tension: 0,
            };
        }

        function normalRangePlugin(id, config, threshold) {
            return {
                id,
                beforeDatasetsDraw(chart) {
                    if (threshold.min === null || threshold.min === undefined || threshold.max === null || threshold.max === undefined) return;

                    const { ctx, chartArea, scales } = chart;
                    const yMin = scales.y.getPixelForValue(Number(threshold.min));
                    const yMax = scales.y.getPixelForValue(Number(threshold.max));
                    const top = Math.min(yMin, yMax);
                    const height = Math.abs(yMax - yMin);

                    ctx.save();
                    ctx.globalAlpha = .08;
                    ctx.fillStyle = config.color;
                    ctx.fillRect(chartArea.left, top, chartArea.right - chartArea.left, height);
                    ctx.restore();
                },
            };
        }

        function createReportChart(canvasId, config) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return null;
            const threshold = reportThresholds[config.thresholdKey] || {};
            const rows = reportChartRows();
            const lastValueIndex = rows
                .map((reading, index) => ({ reading, index }))
                .filter(item => item.reading[config.key] !== null && item.reading[config.key] !== undefined)
                .at(-1)?.index ?? -1;
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
                        return context.dataIndex === lastValueIndex ? 3 : 0;
                    },
                    pointBackgroundColor: '#FFFFFF',
                    pointBorderColor: config.color,
                    pointBorderWidth: 2,
                },
                thresholdDataset('Batas minimum', threshold.min, '#2563EB'),
                thresholdDataset('Batas maksimum', threshold.max, '#D97706'),
            ].filter(Boolean);

            return new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels(),
                datasets,
            },
            plugins: [normalRangePlugin(`${canvasId}NormalRange`, config, threshold)],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                devicePixelRatio: 2,
                plugins: {
                    legend: {
                        labels: {
                            color: textColor,
                            usePointStyle: true,
                            boxWidth: 7,
                            boxHeight: 7,
                            font: { family: 'Nunito', size: 9, weight: 700 },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor, maxTicksLimit: 6, maxRotation: 0, font: { family: 'Nunito', size: 8 } },
                    },
                    y: {
                        position: 'left',
                        grid: { color: gridColor },
                        ticks: { color: textColor, font: { family: 'Nunito', size: 8 } },
                        title: { display: true, text: config.unit, color: textColor, font: { family: 'Nunito', size: 8, weight: 700 } },
                        suggestedMin: threshold.min !== null && threshold.min !== undefined ? Number(threshold.min) - config.padding : undefined,
                        suggestedMax: threshold.max !== null && threshold.max !== undefined ? Number(threshold.max) + config.padding : undefined,
                    },
                },
            },
        });
        }

        charts.push(createReportChart('reportPhChart', { label: 'pH Air', key: 'ph', thresholdKey: 'ph', color: '#22B968', unit: 'pH', padding: 1 }));
        charts.push(createReportChart('reportTemperatureChart', { label: 'Suhu Air', key: 'temperature_celsius', thresholdKey: 'temperature_celsius', color: '#3185FF', unit: 'C', padding: 1 }));
        charts.push(createReportChart('reportTdsChart', { label: 'TDS Air', key: 'tds_ppm', thresholdKey: 'tds_ppm', color: '#8B5CF6', unit: 'ppm', padding: 25 }));
        document.documentElement.dataset.reportReady = 'true';

        window.addEventListener('beforeprint', () => {
            charts.filter(Boolean).forEach(chart => {
                applyChartPalette(chart, { text: '#637991', grid: '#DCEAF0' });
                chart.resize();
            });
        });

        window.addEventListener('afterprint', () => {
            const palette = screenPalette();
            charts.filter(Boolean).forEach(chart => {
                applyChartPalette(chart, palette);
                chart.resize();
            });
        });

        window.addEventListener('theme-changed', () => {
            const palette = screenPalette();
            charts.filter(Boolean).forEach(chart => applyChartPalette(chart, palette));
        });
    });
</script>
@endpush
