@extends('layouts.app')

@section('title', 'Histori Pembacaan Sensor')

@section('content')
    <div class="history-page">
    @php
        $displayTimezone = config('app.display_timezone', 'Asia/Jakarta');
        $todayStart = now($displayTimezone)->startOfDay()->format('Y-m-d\TH:i:s');
        $nowInput = now($displayTimezone)->format('Y-m-d\TH:i:s');
        $last30Days = now($displayTimezone)->subDays(30)->startOfDay()->format('Y-m-d\TH:i:s');
    @endphp

    <section class="app-card page-control-card">
        <div class="page-control-inner">
            <form class="page-control-form" id="historyFilterForm" method="GET" action="{{ route('history') }}">
                <input id="fromDate" type="hidden" name="from" value="{{ $period['from_input'] }}">
                <input id="toDate" type="hidden" name="to" value="{{ $period['to_input'] }}">
                <div class="range-field">
                    <label class="range-label">Dari Waktu</label>
                    <div class="range-inputs">
                        <input class="form-control range-date" id="fromDateOnly" type="date" value="{{ $period['from_local']->toDateString() }}">
                        <input class="form-control range-time" id="fromTimeOnly" type="time" value="{{ $period['from_local']->format('H:i') }}">
                    </div>
                </div>
                <div class="range-separator">-</div>
                <div class="range-field">
                    <label class="range-label">Sampai Waktu</label>
                    <div class="range-inputs">
                        <input class="form-control range-date" id="toDateOnly" type="date" value="{{ $period['to_local']->toDateString() }}">
                        <input class="form-control range-time" id="toTimeOnly" type="time" value="{{ $period['to_local']->format('H:i') }}">
                    </div>
                </div>
                <button class="ui-btn ui-btn-primary" type="submit">Terapkan</button>
                <a class="ui-btn" href="{{ route('history', ['from' => $todayStart, 'to' => $nowInput]) }}">Hari Ini</a>
                <a class="ui-btn" href="{{ route('history', ['from' => $last30Days, 'to' => $nowInput]) }}">30 Hari</a>
            </form>

            <div class="page-control-actions">
                <a class="ui-btn" id="exportCsvButton" href="{{ route('history.export', ['from' => $period['from_input'], 'to' => $period['to_input']]) }}">
                    <i class="bi bi-download me-1"></i>CSV
                </a>
                <a class="ui-btn" id="exportExcelButton" href="{{ route('history.export.excel', ['from' => $period['from_input'], 'to' => $period['to_input']]) }}">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel
                </a>
                <a class="ui-btn" id="exportPdfButton" href="{{ route('history.report', ['from' => $period['from_input'], 'to' => $period['to_input']]) }}" target="_blank">
                    <i class="bi bi-file-earmark-pdf me-1"></i>PDF + Grafik
                </a>
            </div>
        </div>
    </section>

    <style>
        .page-control-form {
            align-items: end;
            flex-wrap: nowrap;
        }

        .range-field {
            width: 282px;
            flex: 0 0 282px;
        }

        .range-label {
            display: block;
            color: var(--app-muted);
            font-size: .78rem;
            font-weight: 800;
            margin-bottom: .45rem;
        }

        .range-inputs {
            display: grid;
            grid-template-columns: minmax(128px, 1fr) 104px;
            gap: .45rem;
        }

        .range-inputs .form-control {
            min-height: 42px;
            border: 1px solid var(--app-border);
            border-radius: 12px;
            background: var(--app-surface);
            color: var(--app-text);
            font-weight: 750;
            box-shadow: 0 6px 16px rgba(15, 23, 42, .03);
            padding-left: .65rem;
            padding-right: .55rem;
        }

        .range-date {
            font-size: .92rem;
        }

        .range-time {
            font-size: .9rem;
        }

        .range-inputs .form-control:focus {
            outline: 2px solid color-mix(in srgb, var(--app-primary) 32%, transparent);
            outline-offset: 0;
        }

        .range-separator {
            color: var(--app-muted);
            font-weight: 900;
            padding-bottom: .62rem;
        }

        .history-chart-wrap {
            height: 300px;
            position: relative;
        }

        .history-chart-wrap canvas {
            display: block;
            width: 100% !important;
            height: 100% !important;
        }

        .history-current-label {
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

        @media (max-width: 1199.98px) {
            .page-control-form {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 767.98px) {
            .page-control-form {
                flex-wrap: wrap;
            }

            .range-field {
                flex: 1 1 100%;
                min-width: 100%;
            }

            .range-inputs {
                grid-template-columns: 1fr;
            }

            .range-separator {
                display: none;
            }

            .history-chart-wrap {
                height: 260px;
            }
        }

        .history-detail-modal .recommendation-note {
            border-radius: 12px;
            background: var(--app-surface-2);
            color: var(--app-muted);
            padding: .75rem .85rem;
            font-size: .9rem;
        }
    </style>

    <section class="app-card history-chart-card mb-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">Grafik Histori</h2>
                <p class="small muted mb-0" id="historyChartPeriod">{{ $period['from_local']->format('d M Y H:i') }} - {{ $period['to_local']->format('d M Y H:i') }}</p>
            </div>
            <div class="ui-segment align-self-lg-start" role="group" aria-label="Mode grafik histori">
                <button class="chart-tab-btn js-history-chart-mode active" type="button" data-mode="ph">pH</button>
                <button class="chart-tab-btn js-history-chart-mode" type="button" data-mode="temperature">Suhu</button>
                <button class="chart-tab-btn js-history-chart-mode" type="button" data-mode="tds">TDS</button>
            </div>
        </div>
        <div class="alert alert-light border rounded-4 text-center mb-0 d-none" id="historyChartEmptyState">
            Tidak ada data sensor pada rentang tanggal ini.
        </div>
        <div class="history-chart-wrap" id="historyChartWrap">
            <div class="history-current-label" id="historyCurrentValueLabel">Nilai saat ini: -</div>
            <canvas id="historyTrendChart" aria-label="Grafik histori kualitas air"></canvas>
        </div>
    </section>

    <section class="app-card history-table-card">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">Data Sensor</h2>
                <p class="small muted mb-0">Gunakan pencarian, urutan kolom, dan pagination untuk menelusuri data.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="sensorHistoryTable">
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
            </table>
        </div>
    </section>

    <div class="modal fade" id="readingDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h2 class="modal-title h5 fw-bold">Detail Pembacaan</h2>
                    <button class="modal-close-btn" type="button" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </div>
                <div class="modal-body history-detail-modal">
                    <div id="readingDetailBody"></div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection

@push('scripts')
<script>
    const historyDataUrl = @json(route('history.data'));
    const historyChartUrl = @json(route('history.chart'));
    const exportBaseUrl = @json(route('history.export'));
    const exportExcelBaseUrl = @json(route('history.export.excel'));
    const reportBaseUrl = @json(route('history.report'));
    const displayTimezone = @json($displayTimezone);
    const historyOfflineGapMs = @json(\App\Models\Device::offlineTimeoutMinutes() * 60 * 1000);
    const fromInput = document.getElementById('fromDate');
    const toInput = document.getElementById('toDate');
    const fromDateOnly = document.getElementById('fromDateOnly');
    const fromTimeOnly = document.getElementById('fromTimeOnly');
    const toDateOnly = document.getElementById('toDateOnly');
    const toTimeOnly = document.getElementById('toTimeOnly');
    let historyTable = null;
    let historyChart = null;
    let historyChartReadings = [];
    let historyChartMode = 'ph';
    let historyThresholds = @json($thresholds);
    let historyPeriodTo = @json($period['to']->toIso8601String());

    const historySensorConfig = {
        ph: { label: 'pH Air', key: 'ph', thresholdKey: 'ph', color: '#22B968', unit: 'pH' },
        temperature: { label: 'Suhu Air', key: 'temperature_celsius', thresholdKey: 'temperature_celsius', color: '#3185FF', unit: '°C' },
        tds: { label: 'TDS Air', key: 'tds_ppm', thresholdKey: 'tds_ppm', color: '#8B5CF6', unit: 'ppm' },
    };

    function currentQuery() {
        syncRangeInputs();
        const params = new URLSearchParams();
        if (fromInput.value) params.set('from', fromInput.value);
        if (toInput.value) params.set('to', toInput.value);

        return params;
    }

    function normalizeTime(value, fallback) {
        if (!value) return fallback;
        if (/^\d{2}:\d{2}$/.test(value)) return `${value}:00`;

        return value;
    }

    function syncRangeInputs() {
        if (fromDateOnly.value) {
            fromInput.value = `${fromDateOnly.value}T${normalizeTime(fromTimeOnly.value, '00:00:00')}`;
        }

        if (toDateOnly.value) {
            toInput.value = `${toDateOnly.value}T${normalizeTime(toTimeOnly.value, '23:59:59')}`;
        }
    }

    function updateExportUrl() {
        const query = currentQuery().toString();
        document.getElementById('exportCsvButton').href = `${exportBaseUrl}?${query}`;
        document.getElementById('exportExcelButton').href = `${exportExcelBaseUrl}?${query}`;
        document.getElementById('exportPdfButton').href = `${reportBaseUrl}?${query}`;
    }

    function cssVar(name) {
        return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    }

    function chartLabels() {
        return historyChartRows().map(reading => new Date(reading.recorded_at).toLocaleString('id-ID', {
            timeZone: displayTimezone,
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        }));
    }

    function historyChartRows() {
        if (historyChartReadings.length === 0) return [];

        const rows = [];
        historyChartReadings.forEach((reading, index) => {
            if (index > 0) {
                const previous = historyChartReadings[index - 1];
                const previousTime = previous?.recorded_at ? new Date(previous.recorded_at).getTime() : 0;
                const currentTime = reading?.recorded_at ? new Date(reading.recorded_at).getTime() : 0;

                if (Number.isFinite(previousTime) && Number.isFinite(currentTime) && currentTime - previousTime > historyOfflineGapMs) {
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

        const latest = historyChartReadings.at(-1);
        const latestTime = latest?.recorded_at ? new Date(latest.recorded_at).getTime() : 0;
        const periodEndTime = historyPeriodTo ? new Date(historyPeriodTo).getTime() : 0;

        if (Number.isFinite(latestTime) && Number.isFinite(periodEndTime) && periodEndTime - latestTime > historyOfflineGapMs) {
            rows.push({
                recorded_at: new Date(periodEndTime).toISOString(),
                ph: null,
                temperature_celsius: null,
                tds_ppm: null,
            });
        }

        return rows;
    }

    function formatPeriodDateTime(value) {
        return new Date(value).toLocaleString('id-ID', {
            timeZone: displayTimezone,
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function formatNumber(value) {
        const number = Number(value);
        if (Number.isNaN(number)) return '-';
        return number.toFixed(2).replace(/\.00$/, '').replace(/(\.\d)0$/, '$1');
    }

    function thresholdLine(label, value, color) {
        if (value === null || value === undefined) return null;

        return {
            label,
            data: historyChartRows().map(() => Number(value)),
            borderColor: color,
            backgroundColor: color,
            borderDash: [8, 5],
            borderWidth: 2.2,
            pointRadius: 0,
            fill: false,
            tension: 0,
        };
    }

    const historyNormalRangePlugin = {
        id: 'historyNormalRangePlugin',
        beforeDatasetsDraw(chart) {
            const config = historySensorConfig[historyChartMode];
            const threshold = historyThresholds[config.thresholdKey] || {};
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

    function historyDataset(config) {
        const rows = historyChartRows();
        const lastValueIndex = rows
            .map((reading, index) => ({ reading, index }))
            .filter(item => item.reading[config.key] !== null && item.reading[config.key] !== undefined)
            .at(-1)?.index ?? -1;

        return {
            label: config.label,
            data: rows.map(reading => reading[config.key] === null || reading[config.key] === undefined ? null : Number(reading[config.key])),
            borderColor: config.color,
            backgroundColor: `${config.color}1A`,
            fill: false,
            spanGaps: false,
            tension: .35,
            borderWidth: 2.4,
            pointRadius(context) {
                return context.dataIndex === lastValueIndex ? 4 : 0;
            },
            pointBackgroundColor: cssVar('--app-surface'),
            pointBorderColor: config.color,
            pointBorderWidth: 2,
            pointHoverRadius: 5,
        };
    }

    function historyScales(gridColor, textColor) {
        const config = historySensorConfig[historyChartMode];
        const threshold = historyThresholds[config.thresholdKey] || {};

        return {
            x: { grid: { display: false }, ticks: { color: textColor, maxTicksLimit: 8, maxRotation: 0, font: { family: 'Nunito' } } },
            y: {
                type: 'linear',
                position: 'left',
                suggestedMin: threshold.min !== null && threshold.min !== undefined ? Number(threshold.min) - 1 : undefined,
                suggestedMax: threshold.max !== null && threshold.max !== undefined ? Number(threshold.max) + 1 : undefined,
                title: { display: true, text: config.unit, color: textColor, font: { family: 'Nunito', weight: 700 } },
                grid: { color: gridColor },
                ticks: { color: textColor, font: { family: 'Nunito' } },
            },
        };
    }

    function setHistoryChartMode(mode) {
        historyChartMode = mode;

        document.querySelectorAll('.js-history-chart-mode').forEach(button => {
            const active = button.dataset.mode === mode;
            button.classList.toggle('active', active);
        });

        drawHistoryChart();
    }

    function drawHistoryChart() {
        const canvas = document.getElementById('historyTrendChart');
        if (!canvas || !window.Chart || historyChartReadings.length === 0) return;
        if (historyChart) historyChart.destroy();

        const gridColor = cssVar('--app-border');
        const textColor = cssVar('--app-muted');
        const config = historySensorConfig[historyChartMode];
        const threshold = historyThresholds[config.thresholdKey] || {};
        const datasets = [
            historyDataset(config),
            thresholdLine('Batas minimum', threshold.min, '#2563EB'),
            thresholdLine('Batas maksimum', threshold.max, '#D97706'),
        ].filter(Boolean);

        historyChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: chartLabels(),
                datasets,
            },
            plugins: [historyNormalRangePlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        labels: {
                            color: textColor,
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
                        borderColor: gridColor,
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label(context) {
                                return `${context.dataset.label}: ${context.parsed.y} ${config.unit}`;
                            },
                        },
                    },
                },
                scales: historyScales(gridColor, textColor),
            },
        });

        const latest = historyChartReadings.at(-1);
        const label = document.getElementById('historyCurrentValueLabel');
        if (label && latest) label.textContent = `Nilai saat ini: ${formatNumber(latest[config.key])} ${config.unit}`;
    }

    async function refreshHistoryChart() {
        const response = await fetch(`${historyChartUrl}?${currentQuery().toString()}`, { headers: { Accept: 'application/json' } });
        const body = await response.json();
        historyChartReadings = body.data?.chart || [];
        historyThresholds = body.data?.thresholds || historyThresholds;
        historyPeriodTo = body.data?.period?.to || historyPeriodTo;

        document.getElementById('historyChartPeriod').textContent = `${formatPeriodDateTime(body.data.period.from)} - ${formatPeriodDateTime(body.data.period.to)}`;
        document.getElementById('historyChartWrap').classList.toggle('d-none', historyChartReadings.length === 0);
        document.getElementById('historyChartEmptyState').classList.toggle('d-none', historyChartReadings.length > 0);

        if (historyChartReadings.length === 0) {
            historyChart?.destroy();
            historyChart = null;
            return;
        }

        drawHistoryChart();
    }

    function statusBadge(severity, status) {
        const className = severity === 'critical' ? 'status-critical' : severity === 'warning' ? 'status-warning' : 'status-normal';
        return `<span class="status-pill ${className}">${status}</span>`;
    }

    document.addEventListener('DOMContentLoaded', () => {
        historyTable = new DataTable('#sensorHistoryTable', {
            serverSide: true,
            processing: true,
            responsive: true,
            searchDelay: 350,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            ajax: {
                url: historyDataUrl,
                data(data) {
                    syncRangeInputs();
                    data.from = fromInput.value;
                    data.to = toInput.value;
                },
            },
            order: [[0, 'desc']],
            columns: [
                { data: 'recorded_at', name: 'recorded_at' },
                { data: 'device', name: 'device' },
                { data: 'ph', name: 'ph' },
                { data: 'temperature', name: 'temperature_celsius' },
                { data: 'tds', name: 'tds_ppm' },
                {
                    data: 'status',
                    name: 'quality_status',
                    render(data, type, row) {
                        if (type !== 'display') return data;
                        return statusBadge(row.severity, data);
                    },
                },
                {
                    data: 'detail_payload',
                    name: 'warning',
                    orderable: false,
                    render(data, type, row) {
                        if (type !== 'display') return row.detail;
                        return `<button class="ui-btn ui-btn-sm js-reading-detail" type="button" data-detail="${encodeDetail(data)}">Detail</button>`;
                    },
                },
            ],
            language: {
                search: '',
                searchPlaceholder: 'Cari data sensor...',
                lengthMenu: 'Tampilkan _MENU_ data',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Tidak ada data',
                infoFiltered: '(difilter dari _MAX_ total data)',
                processing: 'Memuat data...',
                zeroRecords: 'Data tidak ditemukan',
                paginate: {
                    first: 'Awal',
                    last: 'Akhir',
                    next: 'Berikutnya',
                    previous: 'Sebelumnya',
                },
            },
            dom: "<'row g-3 align-items-center mb-3'<'col-md-6'l><'col-md-6'f>>" +
                "<'table-responsive'tr>" +
                "<'row g-3 align-items-center mt-3'<'col-md-6'i><'col-md-6'p>>",
        });

        document.getElementById('historyFilterForm').addEventListener('submit', event => {
            event.preventDefault();
            updateExportUrl();
            historyTable.ajax.reload();
            refreshHistoryChart();
        });

        [fromDateOnly, fromTimeOnly, toDateOnly, toTimeOnly].forEach(input => {
            input.addEventListener('change', updateExportUrl);
            input.addEventListener('input', updateExportUrl);
        });

        document.querySelectorAll('.js-history-chart-mode').forEach(button => {
            button.addEventListener('click', () => setHistoryChartMode(button.dataset.mode));
        });

        document.addEventListener('click', event => {
            const button = event.target.closest('.js-reading-detail');
            if (!button) return;
            renderDetailModal('readingDetailBody', parseDetail(button.dataset.detail));
            bootstrap.Modal.getOrCreateInstance(document.getElementById('readingDetailModal')).show();
        });

        refreshHistoryChart();
    });

    window.addEventListener('theme-changed', drawHistoryChart);

    function encodeDetail(detail) {
        return btoa(unescape(encodeURIComponent(JSON.stringify(detail))));
    }

    function parseDetail(rawDetail) {
        if (!rawDetail) return {};

        try {
            return JSON.parse(decodeURIComponent(escape(atob(rawDetail))));
        } catch (error) {
            return { detail: rawDetail };
        }
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
                            <td>${statusBadge(alert.severity, alert.status || 'Normal')}</td>
                        </tr>
                    `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }
</script>
@endpush
