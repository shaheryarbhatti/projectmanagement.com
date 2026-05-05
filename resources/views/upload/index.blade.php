@extends('layouts.excel-insights')

@section('title', 'Upload Workbook')
@section('page-title', 'Upload File')

@section('content')
<div class="surface-card">
    <div class="text-secondary small mb-3">Upload your `.xlsm` workbooks and process the sheets you need for live analysis.</div>
    <form method="POST" action="{{ route('upload.store') }}" enctype="multipart/form-data" class="d-grid gap-4">
        @csrf
        <label class="upload-dropzone" for="insight-file">
            <div class="display-6 text-primary mb-3"><i class="fa fa-cloud-upload"></i></div>
            <h3 class="h5 mb-2">Drag and drop your `.xlsm` files here</h3>
            <p class="text-secondary mb-4">or click to browse from your device</p>
            <span class="btn btn-primary rounded-pill px-4">Browse Files</span>
            <input id="insight-file" type="file" name="files[]" accept=".xlsm" class="d-none" required multiple>
            <div id="selected-file-name" class="mt-4 text-secondary small">Accepted format: `.xlsm`</div>
        </label>
        <div>
            <div class="fw-semibold mb-2">Select Tabs to Process</div>
            <div class="text-secondary small mb-3">Existing related tables will be truncated before new rows are inserted.</div>
            <div class="tab-picker">
                @foreach($allowedSheets as $sheet)
                <label class="tab-card">
                    <div>
                        <div class="fw-semibold">{{ $sheet }}</div>
                        <div class="text-secondary small mt-1">
                            @if($sheet === 'Database_Smart')
                                Smart project database and core records.
                            @elseif($sheet === 'Suspension & Resumption')
                                Suspension, resumption, and duration tracking.
                            @else
                                Pivot summaries for high-level totals and counts.
                            @endif
                        </div>
                    </div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="tabs[]" value="{{ $sheet }}" checked></div>
                </label>
                @endforeach
            </div>
        </div>
        <div class="d-flex justify-content-center">
            <button type="submit" class="btn btn-primary rounded-pill px-5 py-3"><i class="fa fa-upload me-2"></i>Upload & Process</button>
        </div>
    </form>
</div>
@if($latestUpload)
<div class="surface-card mt-4">
    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center">
        <div>
            <div class="fw-semibold">{{ $latestUpload->original_name }}</div>
            <div class="text-secondary small">Status: {{ ucfirst($latestUpload->status) }} @if($latestUpload->processed_at)   Processed {{ $latestUpload->processed_at->diffForHumans() }} @endif</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('dashboard.index') }}" class="btn btn-outline-primary rounded-pill">Open Dashboard</a>
            <a href="{{ route('chat.index') }}" class="btn btn-outline-secondary rounded-pill">Open Chat</a>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.getElementById('insight-file').addEventListener('change', function (event) {
    const files = event.target.files;
    let name = 'Accepted format: `.xlsm`';
    if (files.length > 0) {
        const names = Array.from(files).map(f => f.name).join(', ');
        name = names.length > 60 ? files.length + ' files selected' : names;
    }
    document.getElementById('selected-file-name').textContent = name;
});
</script>
@endpush
