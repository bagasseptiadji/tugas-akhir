<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="project-title" content="{{ $projectMetadata['title'] ?? 'Monitoring Kualitas Air Akuarium' }}">
    <meta name="project-started-at" content="{{ $projectMetadata['started_at'] ?? '2025-07-15' }}">
    <title>@yield('title', 'Monitoring Kualitas Air Akuarium')</title>
    <script>
        const savedTheme = localStorage.getItem('aquarium-theme') || 'light';
        document.documentElement.dataset.theme = savedTheme;
        document.documentElement.dataset.bsTheme = savedTheme;
    </script>
    @vite('resources/js/app.js')
    <style>
        :root {
            --app-bg: #FFFFFF;
            --app-surface: #ffffff;
            --app-surface-2: #F8FAFC;
            --app-border: #E2E8F0;
            --app-text: #0F172A;
            --app-muted: #64748B;
            --app-primary: #0284C7;
            --app-primary-soft: #E0F2FE;
            --app-green: #059669;
            --app-green-soft: #D1FAE5;
            --app-orange: #EA580C;
            --app-orange-soft: #FFEDD5;
            --app-red: #DC2626;
            --app-red-soft: #FEF2F2;
            --app-shadow: 0 10px 24px rgba(15, 23, 42, .04);
            --app-radius: 6px;
        }

        [data-theme="dark"] {
            --app-bg: #111820;
            --app-surface: #18232d;
            --app-surface-2: #202c37;
            --app-border: #2b3a46;
            --app-text: #edf4f8;
            --app-muted: #a7b3bd;
            --app-primary: #6bb5d6;
            --app-primary-soft: #183846;
            --app-green: #61c9a0;
            --app-green-soft: #16352b;
            --app-orange: #f3a955;
            --app-orange-soft: #3c2a16;
            --app-red: #ff8b7e;
            --app-red-soft: #3c1f1c;
            --app-shadow: 0 18px 46px rgba(0, 0, 0, .22);
        }

        body {
            min-height: 100vh;
            color: var(--app-text);
            background: var(--app-bg);
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            letter-spacing: 0;
        }

        a { text-decoration: none; }

        .app-navbar {
            background: color-mix(in srgb, var(--app-surface) 96%, transparent);
            border-bottom: 1px solid var(--app-border);
            backdrop-filter: blur(16px);
        }

        .brand-mark {
            width: 34px;
            height: 34px;
            display: inline-grid;
            place-items: center;
            border-radius: 11px;
            color: var(--app-primary);
            background: var(--app-primary-soft);
        }

        .navbar-brand {
            font-size: 1rem;
        }

        .navbar-brand, h1, h2, h3, h4, h5 {
            color: var(--app-text);
            letter-spacing: 0;
        }

        .nav-link {
            color: var(--app-muted);
            border-radius: 10px;
            font-size: .93rem;
            font-weight: 700;
            padding: .46rem .68rem !important;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--app-primary);
            background: var(--app-primary-soft);
        }

        .dropdown-menu {
            background: var(--app-surface);
            border-color: var(--app-border);
            border-radius: 14px;
            min-width: 15rem;
            max-width: calc(100vw - 2rem);
            padding: .5rem;
            box-shadow: 0 18px 42px rgba(15, 23, 42, .10);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: .55rem;
            color: var(--app-text);
            border-radius: 10px;
            font-weight: 650;
            line-height: 1.2;
            margin-bottom: .15rem;
            padding: .7rem .8rem;
            transition: background-color .15s ease, color .15s ease;
            white-space: nowrap;
        }

        .dropdown-item:last-child {
            margin-bottom: 0;
        }

        .dropdown-item:hover,
        .dropdown-item.active {
            color: var(--app-primary);
            background: var(--app-primary-soft);
        }

        .dropdown-item.active:hover {
            color: var(--app-primary);
            background: color-mix(in srgb, var(--app-primary-soft) 82%, var(--app-surface));
        }

        .dropdown-divider {
            border-color: var(--app-border);
            margin: .45rem 0;
        }

        .app-card {
            background: var(--app-surface);
            border: 1px solid var(--app-border);
            border-radius: var(--app-radius);
            box-shadow: var(--app-shadow);
            transition: box-shadow .18s ease, border-color .18s ease;
        }

        .app-card:hover {
            border-color: var(--app-border);
            box-shadow: var(--app-shadow);
        }

        [data-theme="dark"] .app-card:hover {
            box-shadow: 0 22px 56px rgba(0, 0, 0, .28);
        }

        .muted { color: var(--app-muted); }

        .metric-label {
            color: var(--app-muted);
            font-size: .82rem;
            font-weight: 700;
        }

        .metric-value {
            color: var(--app-text);
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 800;
            line-height: 1;
        }

        .metric-icon {
            width: 42px;
            height: 42px;
            display: inline-grid;
            place-items: center;
            border-radius: 14px;
            color: var(--app-primary);
            background: var(--app-primary-soft);
        }

        .metric-icon.green { color: var(--app-green); background: var(--app-green-soft); }
        .metric-icon.orange { color: var(--app-orange); background: var(--app-orange-soft); }
        .metric-icon.red { color: var(--app-red); background: var(--app-red-soft); }

        .page-kicker {
            color: var(--app-muted);
            font-size: .82rem;
            font-weight: 700;
        }

        .page-title {
            font-size: clamp(1.45rem, 2.4vw, 2rem);
            font-weight: 800;
            line-height: 1.2;
        }

        .dashboard-page {
            max-width: 1560px;
        }

        .app-page-shell {
            max-width: 1560px;
        }

        .daily-card {
            background: linear-gradient(180deg, var(--app-surface), var(--app-surface-2));
        }

        .page-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: 1rem;
        }

        .page-toolbar-title {
            min-width: min(100%, 280px);
        }

        .page-actionbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: .55rem;
            margin-bottom: 1rem;
        }

        .page-control-card {
            padding: .9rem !important;
            margin-bottom: 1rem;
        }

        .page-control-inner {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: .8rem 1rem;
        }

        .page-control-form,
        .page-control-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: .6rem;
        }

        .page-control-actions {
            justify-content: flex-end;
            margin-left: auto;
        }

        .page-control-form .filter-field {
            width: 170px;
        }

        .page-control-form .form-label {
            margin-bottom: .35rem;
        }

        .alert-summary-strip {
            padding: 0 !important;
            overflow: hidden;
        }

        .alert-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .alert-summary-item {
            min-height: 94px;
            padding: 1.05rem 1.2rem;
            border-right: 1px solid var(--app-border);
        }

        .alert-summary-item:last-child {
            border-right: 0;
        }

        .alert-summary-value {
            color: var(--app-text);
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1;
        }

        .ui-btn {
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .42rem;
            border: 1px solid color-mix(in srgb, var(--app-primary) 30%, var(--app-border));
            border-radius: 999px;
            padding: .5rem .88rem;
            color: var(--app-primary);
            background: color-mix(in srgb, var(--app-surface) 92%, var(--app-primary-soft));
            font-size: .9rem;
            font-weight: 800;
            line-height: 1;
            text-decoration: none;
            letter-spacing: 0;
            transition: background-color .16s ease, border-color .16s ease, color .16s ease, box-shadow .16s ease, transform .16s ease;
        }

        .ui-btn:hover,
        .ui-btn:focus {
            color: var(--app-primary);
            border-color: color-mix(in srgb, var(--app-primary) 55%, var(--app-border));
            background: var(--app-primary-soft);
            box-shadow: 0 8px 18px rgba(2, 132, 199, .10);
            transform: translateY(-1px);
        }

        .ui-btn-primary {
            color: #fff;
            border-color: var(--app-primary);
            background: var(--app-primary);
        }

        .ui-btn-primary:hover,
        .ui-btn-primary:focus {
            color: #fff;
            background: color-mix(in srgb, var(--app-primary) 88%, #000);
        }

        .ui-btn-danger {
            color: var(--app-red);
            border-color: color-mix(in srgb, var(--app-red) 36%, var(--app-border));
        }

        .ui-btn-danger:hover,
        .ui-btn-danger:focus {
            color: var(--app-red);
            background: var(--app-red-soft);
        }

        .ui-btn-sm {
            min-height: 34px;
            padding: .38rem .65rem;
            font-size: .86rem;
        }

        .ui-segment {
            display: inline-flex;
            overflow: hidden;
            gap: .25rem;
            border: 1px solid var(--app-border);
            border-radius: 999px;
            padding: .18rem;
            background: var(--app-surface-2);
        }

        .ui-segment button,
        .ui-segment .ui-segment-item {
            min-width: 72px;
            border: 0;
            border-radius: 999px;
            padding: .42rem .82rem;
            color: var(--app-muted);
            background: transparent;
            font-weight: 800;
            line-height: 1;
            transition: background-color .16s ease, color .16s ease;
        }

        .ui-segment button.active,
        .ui-segment .ui-segment-item.active {
            color: #fff;
            background: var(--app-primary);
            box-shadow: 0 8px 18px rgba(2, 132, 199, .16);
        }

        .ui-segment button:focus,
        .ui-segment .ui-segment-item:focus {
            outline: 0;
            box-shadow: none;
        }

        .compact-filter-card {
            width: fit-content;
            max-width: 100%;
            padding: .95rem !important;
        }

        .compact-filter {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: .75rem;
        }

        .compact-filter .filter-field {
            min-width: 170px;
            max-width: 210px;
        }

        .compact-filter .form-label {
            margin-bottom: .35rem;
        }

        .compact-filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .parameter-list {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
        }

        .parameter-chip {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border: 1px solid var(--app-border);
            border-radius: 999px;
            padding: .5rem .72rem;
            color: var(--app-text);
            background: var(--app-surface-2);
            font-size: .85rem;
            font-weight: 750;
        }

        .parameter-chip.warning {
            color: var(--app-orange);
            background: var(--app-orange-soft);
            border-color: color-mix(in srgb, var(--app-orange) 28%, var(--app-border));
        }

        .parameter-chip.critical {
            color: var(--app-red);
            background: var(--app-red-soft);
            border-color: color-mix(in srgb, var(--app-red) 28%, var(--app-border));
        }

        .action-note {
            border-left: 3px solid var(--app-primary);
            padding-left: .9rem;
            color: var(--app-muted);
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

        .sensor-card {
            min-height: 154px;
        }

        .sensor-card:hover {
            transform: none;
            box-shadow: none;
        }

        .sensor-card .metric-value {
            font-size: clamp(2rem, 3.2vw, 2.55rem);
        }

        .compact-stat {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding-block: .8rem;
            border-bottom: 1px solid var(--app-border);
        }

        .compact-stat:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .compact-stat:first-child {
            padding-top: 0;
        }

        .compact-stat strong {
            color: var(--app-text);
            font-size: .98rem;
        }

        .status-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            margin-top: 1rem;
        }

        .status-summary-card {
            background: var(--app-surface);
            border: 1px solid var(--app-border);
            border-radius: var(--app-radius);
            padding: 1rem;
            box-shadow: var(--app-shadow);
        }

        .status-summary-card .status-icon {
            width: 46px;
            height: 46px;
            display: inline-grid;
            place-items: center;
            border-radius: 15px;
            font-size: 1.15rem;
        }

        .status-summary-card.status-normal .status-icon {
            color: var(--app-green);
            background: var(--app-green-soft);
        }

        .status-summary-card.status-warning .status-icon {
            color: var(--app-orange);
            background: var(--app-orange-soft);
        }

        .status-summary-card.status-critical .status-icon {
            color: var(--app-red);
            background: var(--app-red-soft);
        }

        .status-current-value {
            color: var(--app-text);
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .status-summary-card.status-normal .status-current-value { color: var(--app-green); }
        .status-summary-card.status-warning .status-current-value { color: var(--app-orange); }
        .status-summary-card.status-critical .status-current-value { color: var(--app-red); }

        .chart-filter {
            display: flex;
            flex-direction: column;
            gap: .75rem;
            align-items: flex-end;
            width: min(100%, 620px);
        }

        .chart-date-form {
            display: grid;
            grid-template-columns: minmax(132px, 1fr) minmax(132px, 1fr) auto auto;
            gap: .5rem;
            align-items: center;
        }

        .chart-date-form .form-control {
            min-height: 34px;
        }

        .js-history-chart-mode {
            min-width: 74px;
        }

        .btn {
            border-radius: 10px;
            font-weight: 700;
        }

        .btn-primary {
            --bs-btn-bg: var(--app-primary);
            --bs-btn-border-color: var(--app-primary);
            --bs-btn-hover-bg: color-mix(in srgb, var(--app-primary) 86%, #000);
            --bs-btn-hover-border-color: color-mix(in srgb, var(--app-primary) 86%, #000);
        }

        .btn-outline-primary {
            --bs-btn-color: var(--app-primary);
            --bs-btn-border-color: color-mix(in srgb, var(--app-primary) 40%, var(--app-border));
            --bs-btn-hover-bg: var(--app-primary);
            --bs-btn-hover-border-color: var(--app-primary);
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border-radius: 999px;
            padding: .36rem .68rem;
            font-weight: 800;
            font-size: .8rem;
        }

        .status-normal { color: var(--app-green); background: var(--app-green-soft); }
        .status-warning { color: var(--app-orange); background: var(--app-orange-soft); }
        .status-critical { color: var(--app-red); background: var(--app-red-soft); }

        .alert-light {
            color: var(--app-muted);
            background: var(--app-surface-2);
            border-color: var(--app-border) !important;
        }

        .chart-wrap {
            min-height: 390px;
            position: relative;
        }

        .table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--app-text);
            --bs-table-hover-bg: var(--app-surface-2);
            border-color: var(--app-border);
        }

        .table thead th {
            color: var(--app-muted);
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: 0;
            border-bottom-color: var(--app-border);
            background: var(--app-surface-2);
        }

        .form-control {
            color: var(--app-text);
            background: var(--app-surface-2);
            border-color: var(--app-border);
            border-radius: 12px;
        }

        .form-control:focus {
            border-color: var(--app-primary);
            box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--app-primary) 18%, transparent);
        }

        .dt-container .dt-search input,
        .dt-container .dt-length select {
            color: var(--app-text);
            background: var(--app-surface-2);
            border: 1px solid var(--app-border);
            border-radius: 12px;
            padding: .55rem .75rem;
            outline: 0;
        }

        .dt-container .dt-search input {
            min-width: min(320px, 100%);
        }

        .dt-container .dt-info,
        .dt-container .dt-length label,
        .dt-container .dt-search label {
            color: var(--app-muted);
            font-size: .9rem;
            font-weight: 600;
        }

        .dt-container .pagination {
            gap: .35rem;
            justify-content: flex-end;
        }

        .dt-container .page-link {
            color: var(--app-primary);
            background: var(--app-surface-2);
            border-color: var(--app-border);
            border-radius: 10px;
            font-weight: 700;
        }

        .dt-container .page-item.active .page-link {
            color: #fff;
            background: var(--app-primary);
            border-color: var(--app-primary);
        }

        .dt-container .page-item.disabled .page-link {
            color: var(--app-muted);
            background: var(--app-surface-2);
            border-color: var(--app-border);
            opacity: .65;
        }

        .theme-toggle {
            width: 36px;
            height: 36px;
            display: inline-grid;
            place-items: center;
            border: 1px solid var(--app-border);
            border-radius: 12px;
            color: var(--app-text);
            background: var(--app-surface-2);
        }

        .nav-link:focus,
        .dropdown-toggle:focus,
        .dropdown-item:focus {
            box-shadow: none;
            outline: 0;
        }

        .toast-alert {
            --toast-color: var(--app-red);
            --toast-bg: var(--app-red-soft);
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 1080;
            width: min(320px, calc(100vw - 2rem));
            opacity: 0;
            transform: translateY(1rem);
            pointer-events: none;
            transition: 180ms ease;
            border: 1px solid color-mix(in srgb, var(--toast-color) 34%, var(--app-border));
            border-left: 4px solid var(--toast-color);
            border-radius: 14px;
            background: var(--toast-bg);
            color: var(--toast-color);
        }

        .toast-alert.is-normal {
            --toast-color: var(--app-green);
            --toast-bg: var(--app-green-soft);
        }

        .toast-alert.is-warning {
            --toast-color: var(--app-orange);
            --toast-bg: var(--app-orange-soft);
        }

        .toast-alert.is-critical {
            --toast-color: var(--app-red);
            --toast-bg: var(--app-red-soft);
        }

        .toast-alert.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .toast-alert .toast-close {
            width: 28px;
            height: 28px;
            display: inline-grid;
            place-items: center;
            border: 0;
            border-radius: 8px;
            color: var(--toast-color);
            background: transparent;
            font-size: 1.1rem;
            line-height: 1;
        }

        .toast-alert .toast-close:hover {
            background: color-mix(in srgb, var(--toast-color) 12%, transparent);
        }

        .value-updated { animation: pulseValue 900ms ease-out; }

        @keyframes pulseValue {
            0% { background: var(--app-primary-soft); }
            100% { background: transparent; }
        }

        @media (max-width: 767.98px) {
            .chart-wrap { min-height: 310px; }
            .navbar-nav { padding-top: .75rem; }
            .page-control-actions,
            .page-control-form,
            .page-control-form .filter-field,
            .page-control-form .ui-btn {
                width: 100%;
            }
            .alert-summary-grid {
                grid-template-columns: 1fr 1fr;
            }
            .alert-summary-item:nth-child(2n) {
                border-right: 0;
            }
            .alert-summary-item:nth-child(-n+2) {
                border-bottom: 1px solid var(--app-border);
            }
            .compact-filter-card {
                width: 100%;
            }
            .compact-filter .filter-field,
            .compact-filter-actions,
            .compact-filter .ui-btn {
                width: 100%;
                max-width: none;
            }
            .js-history-chart-mode {
                flex: 1 1 auto;
            }
            .chart-filter {
                align-items: stretch;
            }

            .chart-date-form {
                grid-template-columns: 1fr;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    @vite('resources/css/playful-theme.css')
</head>
<body class="smartqua-app">
@auth
    @php
        [$shellTitle, $shellSubtitle] = match (true) {
            request()->routeIs('dashboard') => ['Smart Aquarium Monitor', 'Pemantauan kualitas air secara real-time'],
            request()->routeIs('history') => ['Histori Pembacaan', 'Telusuri grafik dan data sensor terdahulu'],
            request()->routeIs('settings') => ['Pengaturan Sistem', 'Atur perangkat, ambang sensor, dan notifikasi'],
            request()->routeIs('api-tokens.*') => ['Token API', 'Kelola akses aman untuk ESP32 dan integrasi'],
            request()->routeIs('api-docs') => ['Dokumentasi API', 'Panduan integrasi data sensor SmartQua'],
            default => ['SmartQua', 'Monitoring kualitas air akuarium'],
        };
    @endphp
    <div class="app-layout">
        <aside class="app-sidebar" id="appSidebar" aria-label="Navigasi utama">
            <div class="sidebar-brand">
                <span class="brand-mark"><i class="bi bi-droplet-half"></i></span>
                <span><strong>SmartQua</strong></span>
                <button class="sidebar-close" type="button" data-sidebar-close aria-label="Tutup menu"><i class="bi bi-x-lg"></i></button>
            </div>

            <div class="sidebar-section-label">Monitoring</div>
            <nav class="sidebar-nav">
                <a class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <span class="sidebar-icon"><i class="bi bi-grid-fill"></i></span><span>Dashboard</span>
                </a>
                <a class="sidebar-link {{ request()->routeIs('history') ? 'active' : '' }}" href="{{ route('history') }}">
                    <span class="sidebar-icon"><i class="bi bi-activity"></i></span><span>Histori</span>
                </a>
            </nav>

            <div class="sidebar-section-label">Sistem</div>
            <nav class="sidebar-nav">
                <a class="sidebar-link {{ request()->routeIs('settings') ? 'active' : '' }}" href="{{ route('settings') }}">
                    <span class="sidebar-icon"><i class="bi bi-gear"></i></span><span>Pengaturan</span>
                </a>
                <a class="sidebar-link {{ request()->routeIs('api-tokens.*') ? 'active' : '' }}" href="{{ route('api-tokens.index') }}">
                    <span class="sidebar-icon"><i class="bi bi-key"></i></span><span>Token API</span>
                </a>
                <a class="sidebar-link {{ request()->routeIs('api-docs') ? 'active' : '' }}" href="{{ route('api-docs') }}">
                    <span class="sidebar-icon"><i class="bi bi-braces"></i></span><span>Dokumentasi API</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="sidebar-link sidebar-logout" type="submit">
                        <span class="sidebar-icon"><i class="bi bi-box-arrow-right"></i></span><span>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>
        <button class="app-sidebar-backdrop" type="button" data-sidebar-close aria-label="Tutup menu"></button>

        <div class="app-workspace">
            <header class="app-topbar">
                <div class="topbar-heading">
                    <button class="sidebar-toggle" type="button" data-sidebar-open aria-label="Buka menu"><i class="bi bi-list"></i></button>
                    <div>
                        <h1>{{ $shellTitle }}</h1>
                        <p>{{ $shellSubtitle }}</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <div class="topbar-clock" aria-label="Tanggal dan waktu saat ini">
                        <i class="bi bi-calendar3"></i><span id="topbarDate">{{ now(config('app.display_timezone', 'Asia/Jakarta'))->format('d M Y') }}</span>
                        <span class="clock-divider"></span><span id="topbarTime">{{ now(config('app.display_timezone', 'Asia/Jakarta'))->format('H:i') }}</span>
                    </div>
                    <button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle dark mode">
                        <i class="bi bi-moon-stars" id="themeIcon"></i>
                    </button>
                </div>
            </header>

            <main class="app-content @yield('main_class', 'container-fluid px-3 px-lg-4 py-4')">
                <div class="@yield('page_shell_class', 'app-page-shell mx-auto')">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
@else
    <nav class="navbar app-navbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-2">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('login') }}">
                <span class="brand-mark"><i class="bi bi-droplet-half"></i></span><span>SmartQua</span>
            </a>
            <button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars" id="themeIcon"></i>
            </button>
        </div>
    </nav>
    <main class="@yield('main_class', 'container-fluid px-3 px-lg-4 py-4')">
        <div class="@yield('page_shell_class', 'app-page-shell mx-auto')">
            @yield('content')
        </div>
    </main>
@endauth

<div id="app-toast-root" aria-live="polite" aria-atomic="true"></div>

<script>
    function applyTheme(theme) {
        document.documentElement.dataset.theme = theme;
        document.documentElement.dataset.bsTheme = theme;
        localStorage.setItem('aquarium-theme', theme);
        const icon = document.getElementById('themeIcon');
        if (icon) icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
    }

    document.addEventListener('DOMContentLoaded', () => {
        applyTheme(localStorage.getItem('aquarium-theme') || 'light');
        document.getElementById('themeToggle')?.addEventListener('click', () => {
            applyTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark');
            window.dispatchEvent(new CustomEvent('theme-changed'));
        });

        const sidebar = document.getElementById('appSidebar');
        const setSidebarOpen = open => {
            sidebar?.classList.toggle('is-open', open);
            document.body.classList.toggle('sidebar-open', open);
        };
        document.querySelector('[data-sidebar-open]')?.addEventListener('click', () => setSidebarOpen(true));
        document.querySelectorAll('[data-sidebar-close]').forEach(button => button.addEventListener('click', () => setSidebarOpen(false)));

        const updateTopbarClock = () => {
            const now = new Date();
            const date = document.getElementById('topbarDate');
            const time = document.getElementById('topbarTime');
            if (date) date.textContent = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'Asia/Jakarta' });
            if (time) time.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' }).replace('.', ':');
        };
        updateTopbarClock();
        window.setInterval(updateTopbarClock, 30000);

        @if (session('error'))
            window.appToast?.error(@json(session('error')));
        @elseif (session('status'))
            window.appToast?.success(@json(session('status')));
        @endif

        document.querySelectorAll('.js-session-alert').forEach(alert => {
            window.setTimeout(() => {
                alert.style.transition = 'opacity .2s ease, transform .2s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-6px)';
                window.setTimeout(() => alert.remove(), 220);
            }, 3500);
        });
    });
</script>
@stack('scripts')
</body>
</html>
