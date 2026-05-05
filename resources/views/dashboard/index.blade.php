@extends('layouts.excel-insights')

@section('title', 'Graphical Dashboard')
@section('page-title', 'Graphical View')

@section('content')
@if(!$latestUpload)
<div class="surface-card text-center"><h3 class="h4 mb-2">No workbook processed yet</h3><p class="text-secondary mb-4">Upload an `.xlsm` file first to generate charts and insights.</p><a href="{{ route('upload.index') }}" class="btn btn-primary rounded-pill px-4">Go to Upload</a></div>
@else
<div class="stat-grid mb-4">
    <div class="stat-card"><div class="text-secondary small">Total Records</div><div class="display-6 fw-bold">{{ number_format($stats['total_records']) }}</div></div>
    <div class="stat-card"><div class="text-secondary small">Active Records</div><div class="display-6 fw-bold text-primary">{{ number_format($stats['active_records']) }}</div></div>
    <div class="stat-card"><div class="text-secondary small">Suspended Records</div><div class="display-6 fw-bold text-warning">{{ number_format($stats['suspended_records']) }}</div></div>
    <div class="stat-card"><div class="text-secondary small">Resumed Records</div><div class="display-6 fw-bold" style="color:#8d67ff">{{ number_format($stats['resumed_records']) }}</div></div>
</div>
<div class="chart-grid">
    <div class="chart-card"><h3 class="h6 mb-3">Status Distribution</h3><div style="height:300px; position:relative;"><canvas id="statusChart"></canvas></div></div>
    <div class="chart-card"><h3 class="h6 mb-3">Approval Year Trend</h3><div style="height:300px; position:relative;"><canvas id="trendChart"></canvas></div></div>
    <div class="chart-card"><h3 class="h6 mb-3">Project Health</h3><div style="height:300px; position:relative;"><canvas id="healthChart"></canvas></div></div>
    <div class="chart-card"><h3 class="h6 mb-3">Suspension vs Resumption</h3><div style="height:300px; position:relative;"><canvas id="suspensionChart"></canvas></div></div>
    <div class="chart-card"><h3 class="h6 mb-3">Financials by Dept</h3><div style="height:300px; position:relative;"><canvas id="financialsChart"></canvas></div></div>
    <div class="chart-card"><h3 class="h6 mb-3">Contractor / Executor</h3><div style="height:300px; position:relative;"><canvas id="contractorChart"></canvas></div></div>
    <div class="chart-card"><h3 class="h6 mb-3">Strategic Driver Alignment</h3><div style="height:300px; position:relative;"><canvas id="driverChart"></canvas></div></div>
    <div class="chart-card"><h3 class="h6 mb-3">Top Project Owners</h3><div style="height:300px; position:relative;"><canvas id="ownerChart"></canvas></div></div>
    <div class="chart-card"><h3 class="h6 mb-3">Suspension Reasons</h3><div style="height:300px; position:relative;"><canvas id="reasonChart"></canvas></div></div>
    <div class="chart-card"><h3 class="h6 mb-3">Top Stage Gates</h3><div style="height:300px; position:relative;"><canvas id="stageGateChart"></canvas></div></div>
</div>
<div class="surface-card mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="h5 mb-1">Pivot Summary</h3><div class="text-secondary small">Stored from the `Pivot_Tables` sheet.</div></div><span class="pill-note"><i class="fa fa-database"></i> {{ $latestUpload->original_name }}</span></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Metric</th><th>Total Rows</th><th>Total Value</th></tr></thead><tbody>@forelse($tables['pivotSummary'] as $row)<tr><td>{{ $row->metric_title }}</td><td>{{ number_format($row->total_rows) }}</td><td>{{ number_format((float) $row->total_value, 2) }}</td></tr>@empty<tr><td colspan="3" class="text-secondary">No pivot entries were loaded.</td></tr>@endforelse</tbody></table></div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const charts = @json($charts ?? []);
function makeChart(id, type, dataset, color) {
    const el = document.getElementById(id); if (!el || !dataset) return;
    new Chart(el, { type, data: { labels: dataset.labels, datasets: [{ data: dataset.values, backgroundColor: Array.isArray(color) ? color : color, borderColor: Array.isArray(color) ? color[0] : color, borderWidth: 2, tension: .35, fill: type === 'line' }]}, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: type === 'pie' || type === 'doughnut' ? {} : { y: { beginAtZero: true } } } });
}
makeChart('statusChart', 'doughnut', charts.statusDistribution, ['#2f6bff', '#2fb66e', '#ff9c45', '#8d67ff', '#8b97ac']);
makeChart('trendChart', 'line', charts.approvalTrend, 'rgba(47, 107, 255, 0.85)');
makeChart('healthChart', 'pie', charts.healthDistribution, ['#2fb66e', '#ff9c45', '#ff5f72', '#2f6bff', '#8d67ff']);
makeChart('suspensionChart', 'bar', charts.suspensionStatus, ['#ff9c45', '#8d67ff', '#8b97ac']);
makeChart('financialsChart', 'bar', charts.financialsByDept, '#00cfd5');
makeChart('contractorChart', 'doughnut', charts.contractorDistribution, ['#2f6bff', '#2fb66e', '#ff9c45', '#8d67ff', '#8b97ac', '#ff5f72', '#00cfd5']);
makeChart('driverChart', 'bar', charts.strategicDriverAlignment, '#8d67ff');
makeChart('ownerChart', 'bar', charts.ownerDistribution, '#2fb66e');
makeChart('reasonChart', 'doughnut', charts.suspensionReasons, ['#ff5f72', '#ff9c45', '#8b97ac', '#2f6bff', '#2fb66e']);
makeChart('stageGateChart', 'bar', charts.stageGateBreakdown, '#2f6bff');
</script>
@endpush
