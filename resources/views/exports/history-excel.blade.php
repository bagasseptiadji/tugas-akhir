<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Kualitas Air</title>
</head>
<body>
<table>
    <tr>
        <th colspan="7">Laporan Monitoring Kualitas Air Akuarium</th>
    </tr>
    <tr>
        <td colspan="7">Periode: {{ $period['from_local']->format('d M Y H:i:s') }} - {{ $period['to_local']->format('d M Y H:i:s') }} WIB</td>
    </tr>
    <tr>
        <th>Waktu</th>
        <th>Perangkat</th>
        <th>pH</th>
        <th>Suhu (C)</th>
        <th>TDS (ppm)</th>
        <th>Status</th>
        <th>Keterangan</th>
    </tr>
    @foreach ($readings as $reading)
        <tr>
            <td>{{ $reading->recordedAtLocal()?->format('Y-m-d H:i:s') }}</td>
            <td>{{ $reading->device?->name ?? $reading->device?->code ?? '-' }}</td>
            <td>{{ $reading->ph }}</td>
            <td>{{ $reading->temperature_celsius }}</td>
            <td>{{ $reading->tds_ppm }}</td>
            <td>{{ $reading->statusLabel() }}</td>
            <td>{{ $reading->warningSummary() }}</td>
        </tr>
    @endforeach
</table>
</body>
</html>
