@extends('layouts.excel-insights')

@section('title', 'Upload History')
@section('page-title', 'Upload History')

@section('content')
<div class="surface-card">
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="h5 mb-1">Processed Workbooks</h3><div class="text-secondary small">Recent workbook imports and their processing status.</div></div><a href="{{ route('upload.index') }}" class="btn btn-outline-primary rounded-pill">Upload New File</a></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Workbook</th><th>User</th><th>Status</th><th>Sheets</th><th>Processed</th><th class="text-end">Action</th></tr></thead><tbody>@forelse($uploads as $upload)<tr><td>{{ $upload->original_name }}</td><td>{{ $upload->user?->name ?? 'Unknown' }}</td><td><span class="badge {{ $upload->status === 'completed' ? 'bg-success' : ($upload->status === 'failed' ? 'bg-danger' : 'bg-secondary') }}">{{ ucfirst($upload->status) }}</span></td><td>{{ implode(', ', $upload->selected_tabs ?? []) }}</td><td>{{ optional($upload->processed_at)->format('d M Y, h:i A') ?? 'Not processed yet' }}</td><td class="text-end"><a href="{{ route('history.download', $upload) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3"><i class="fa fa-download me-1"></i> Download</a></td></tr>@empty<tr><td colspan="6" class="text-secondary text-center py-4">No uploads found yet.</td></tr>@endforelse</tbody></table></div>
    <div class="mt-3">{{ $uploads->links() }}</div>
</div>
@endsection
